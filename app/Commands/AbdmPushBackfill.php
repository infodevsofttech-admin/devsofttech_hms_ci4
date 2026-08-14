<?php

namespace App\Commands;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\FhirEncryptionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmPushBackfill extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:push-backfill';
    protected $description = 'Push stored health_records that never reached the ABDM bridge.';
    protected $usage = 'abdm:push-backfill [--limit 20] [--id 5] [--dry-run]';
    protected $arguments = [];
    protected $options = [
        '--limit' => 'Maximum records to push (default: 20).',
        '--id' => 'Push a single health_records id.',
        '--dry-run' => 'List eligible records without pushing.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }
        $onlyId = (int) (CLI::getOption('id') ?? 0);
        $dryRun = CLI::getOption('dry-run') !== null;

        $db = \Config\Database::connect();
        if (! $db->tableExists('health_records')) {
            CLI::error('health_records table not found.');

            return;
        }

        $builder = $db->table('health_records')
            ->whereNotIn('push_status', ['pushed', 'linked'])
            ->where('care_context_reference !=', '')
            ->where('abha_id !=', '')
            ->orderBy('id', 'ASC');
        if ($onlyId > 0) {
            $builder->where('id', $onlyId);
        }

        $rows = $builder->get($limit)->getResultArray();
        CLI::write('ABDM Push Backfill' . ($dryRun ? ' (dry-run)' : ''), 'yellow');
        CLI::write('Eligible: ' . count($rows));

        if ($rows === [] || $dryRun) {
            foreach ($rows as $row) {
                CLI::write('  id=' . $row['id'] . ' ' . $row['hi_type'] . ' ' . $row['care_context_reference']);
            }

            return;
        }

        $connector = AbdmConnectorFactory::make();
        $pushed = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $bundle = $this->resolveBundle($row);
            if ($bundle === null) {
                CLI::write('  id=' . $row['id'] . ' — no readable FHIR bundle, skipped', 'red');
                $failed++;
                continue;
            }

            $patient = $this->loadPatient($db, (int) ($row['patient_id'] ?? 0));
            $abhaId = trim((string) ($row['abha_id'] ?? ''));

            try {
                $result = $connector->pushRecord([
                    'patient_id' => (string) ($row['patient_id'] ?? ''),
                    'patient_name' => $patient['name'] !== '' ? $patient['name'] : ('PATIENT-' . (int) $row['patient_id']),
                    'abha_id' => str_contains($abhaId, '@') ? '' : $abhaId,
                    'abha_address' => str_contains($abhaId, '@') ? $abhaId : $patient['abha_address'],
                    'gender' => $patient['gender'],
                    'year_of_birth' => $patient['year_of_birth'],
                    'hi_type' => (string) ($row['hi_type'] ?? ''),
                    'visit_date' => substr((string) ($row['created_at'] ?? date('Y-m-d')), 0, 10),
                    'care_context_reference' => (string) ($row['care_context_reference'] ?? ''),
                    'care_context_display' => (string) ($row['hi_type'] ?? '') . ' ' . substr((string) ($row['created_at'] ?? ''), 0, 10),
                    'record_data' => $bundle,
                ]);
            } catch (\Throwable $e) {
                CLI::write('  id=' . $row['id'] . ' — exception: ' . $e->getMessage(), 'red');
                $failed++;
                continue;
            }

            $ok = ! empty($result['ok']) && (int) $result['ok'] === 1;
            $httpCode = (int) ($result['http_code'] ?? 0);
            if ($ok || $httpCode === 201 || $httpCode === 409) {
                $db->table('health_records')->where('id', $row['id'])->update([
                    'push_status' => 'pushed',
                    'push_at' => date('Y-m-d H:i:s'),
                    'bridge_record_id' => (int) ($result['record_id'] ?? 0) ?: null,
                    'abdm_txn_id' => (string) ($result['queue_id'] ?? $row['abdm_txn_id'] ?? ''),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                CLI::write('  id=' . $row['id'] . ' — pushed (record_id=' . (int) ($result['record_id'] ?? 0) . ')', 'green');
                $pushed++;
                continue;
            }

            $error = trim((string) ($result['message'] ?? $result['error_text'] ?? ('HTTP ' . $httpCode)));
            foreach ((array) ($result['errors'] ?? []) as $detail) {
                if (is_array($detail)) {
                    $error .= ' | ' . trim(implode(' ', array_filter([
                        (string) ($detail['code'] ?? ''),
                        (string) ($detail['field'] ?? ''),
                        (string) ($detail['message'] ?? ''),
                    ])));
                }
            }

            $db->table('health_records')->where('id', $row['id'])->update([
                'push_status' => 'failed',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            CLI::write('  id=' . $row['id'] . ' — failed: ' . $error, 'red');
            $failed++;
        }

        CLI::write('Pushed: ' . $pushed, 'green');
        CLI::write('Failed: ' . $failed, $failed > 0 ? 'red' : 'green');
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function resolveBundle(array $row): ?array
    {
        $plain = trim((string) ($row['record_data'] ?? ''));
        if ($plain === '') {
            $encrypted = trim((string) ($row['fhir_bundle_enc'] ?? ''));
            if ($encrypted === '') {
                return null;
            }
            try {
                $plain = (new FhirEncryptionService())->decrypt($encrypted);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) {
            return null;
        }

        $bundle = $decoded['bundle'] ?? $decoded['fhir_bundle'] ?? $decoded;

        return is_array($bundle) && ($bundle['resourceType'] ?? '') === 'Bundle' ? $bundle : null;
    }

    /**
     * @return array{name:string,gender:string,year_of_birth:string,abha_address:string}
     */
    private function loadPatient($db, int $patientId): array
    {
        $out = ['name' => '', 'gender' => '', 'year_of_birth' => '', 'abha_address' => ''];
        if ($patientId <= 0 || ! $db->tableExists('patient_master')) {
            return $out;
        }

        $fields = $db->getFieldNames('patient_master') ?? [];
        $row = $db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        if (! $row) {
            return $out;
        }

        $out['name'] = trim((string) ($row['p_fname'] ?? ''));
        $gender = (int) ($row['gender'] ?? 0);
        $out['gender'] = $gender === 1 ? 'M' : ($gender === 2 ? 'F' : '');

        $dob = trim((string) ($row['dob'] ?? ''));
        if ($dob !== '' && $dob !== '0000-00-00') {
            $out['year_of_birth'] = substr($dob, 0, 4);
        }

        if (in_array('abha_address', $fields, true)) {
            $out['abha_address'] = trim((string) ($row['abha_address'] ?? ''));
        }

        return $out;
    }
}
