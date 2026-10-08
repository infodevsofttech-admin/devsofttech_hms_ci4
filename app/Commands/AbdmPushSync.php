<?php

namespace App\Commands;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\Sync\AbdmSyncWorkerService;
use App\Libraries\Abdm\Sync\AbdmTaskBoardSyncService;
use App\Libraries\FhirEncryptionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AbdmPushSync extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:push-sync';
    protected $description = 'Process ABDM M2 sync outbox, ABDM Work Task Board, and pending health_records care-context linking.';
    protected $usage = 'abdm:push-sync [--limit 20] [--worker worker-name] [--taskboard-only]';
    protected $arguments = [];
    protected $options = [
        '--limit'          => 'Maximum outbox, taskboard, and health_records rows to process in this run.',
        '--worker'         => 'Worker identifier for lock tracking.',
        '--taskboard-only' => 'Skip outbox queue and only sync ABDM Work Task Board records.',
    ];

    public function run(array $params)
    {
        $limit = (int) (CLI::getOption('limit') ?? 20);
        if ($limit <= 0) {
            $limit = 20;
        }

        $worker = trim((string) (CLI::getOption('worker') ?? 'spark-abdm-push-sync'));
        if ($worker === '') {
            $worker = 'spark-abdm-push-sync';
        }

        $taskboardOnly = CLI::getOption('taskboard-only') !== null;

        if (! $taskboardOnly) {
            $service = new AbdmSyncWorkerService();
            $summary = $service->process($limit, $worker);

            CLI::write('ABDM Push Sync (Outbox)', 'yellow');
            CLI::write('Processed: ' . (int) ($summary['processed'] ?? 0));
            CLI::write('Success: ' . (int) ($summary['success'] ?? 0), 'green');
            CLI::write('Failed: ' . (int) ($summary['failed'] ?? 0), ((int) ($summary['failed'] ?? 0) > 0 ? 'red' : 'green'));
            CLI::write('Dead: ' . (int) ($summary['dead'] ?? 0), ((int) ($summary['dead'] ?? 0) > 0 ? 'red' : 'green'));
            CLI::write('Skipped: ' . (int) ($summary['skipped'] ?? 0));
        }

        // 2. Sync ABDM Work Task Board records (OPD Consults & Work Queue items)
        CLI::newLine();
        CLI::write('ABDM Work Task Board Sync', 'cyan');
        $tbService = new AbdmTaskBoardSyncService();
        $tbSummary = $tbService->syncAll($limit);

        CLI::write('OPD Consults -> Eligible: ' . ($tbSummary['opd']['eligible'] ?? 0) . ' | Linked: ' . ($tbSummary['opd']['linked'] ?? 0) . ' | Cooling: ' . ($tbSummary['opd']['cooling'] ?? 0) . ' | Failed: ' . ($tbSummary['opd']['failed'] ?? 0), 'green');
        CLI::write('Work Tasks   -> Eligible: ' . ($tbSummary['tasks']['eligible'] ?? 0) . ' | Linked: ' . ($tbSummary['tasks']['linked'] ?? 0) . ' | Cooling: ' . ($tbSummary['tasks']['cooling'] ?? 0) . ' | Failed: ' . ($tbSummary['tasks']['failed'] ?? 0), 'green');
        CLI::write('Invoices     -> Eligible: ' . ($tbSummary['invoices']['eligible'] ?? 0) . ' | Linked: ' . ($tbSummary['invoices']['linked'] ?? 0) . ' | Failed: ' . ($tbSummary['invoices']['failed'] ?? 0), 'green');

        // 3. Process pending health_records directly (instant background sync for newly saved records)
        $this->pushPendingHealthRecords($limit);
    }

    private function pushPendingHealthRecords(int $limit): void
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('health_records')) {
            return;
        }

        $rows = $db->table('health_records')
            ->whereNotIn('push_status', ['pushed', 'linked', 'local_only', 'failed'])
            ->where('care_context_reference !=', '')
            ->where('abha_id !=', '')
            ->groupStart()
                ->where('record_data IS NOT NULL', null, false)
                ->where('record_data !=', '')
                ->orGroupStart()
                    ->where('fhir_bundle_enc IS NOT NULL', null, false)
                    ->where('fhir_bundle_enc !=', '')
                ->groupEnd()
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get($limit)
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        CLI::newLine();
        CLI::write('--- Pending Health Records Sync (Immediate Cron Link) ---', 'cyan');
        CLI::write('Found ' . count($rows) . ' pending record(s) to push...', 'yellow');

        $connector = AbdmConnectorFactory::make();
        $pushed = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $bundle = $this->resolveFhirBundle($row);
            if ($bundle === null) {
                $db->table('health_records')->where('id', (int) $row['id'])->update(['push_status' => 'local_only']);
                CLI::write('  Record #' . $row['id'] . ' (' . ($row['hi_type'] ?? '') . ') - marked local_only (no valid FHIR bundle)', 'yellow');
                continue;
            }

            $patientId = (int) ($row['patient_id'] ?? 0);
            $patient = $this->loadPatientDemographics($db, $patientId);
            $abhaId = trim((string) ($row['abha_id'] ?? ''));

            try {
                $result = $connector->pushRecord([
                    'patient_id'             => (string) $patientId,
                    'patient_name'           => $patient['name'] !== '' ? $patient['name'] : ('PATIENT-' . $patientId),
                    'abha_id'                => str_contains($abhaId, '@') ? '' : $abhaId,
                    'abha_address'           => str_contains($abhaId, '@') ? $abhaId : $patient['abha_address'],
                    'gender'                 => $patient['gender'],
                    'year_of_birth'          => $patient['year_of_birth'],
                    'hi_type'                => (string) ($row['hi_type'] ?? ''),
                    'record_type'            => (string) ($row['hi_type'] ?? ''),
                    'visit_date'             => substr((string) ($row['created_at'] ?? date('Y-m-d')), 0, 10),
                    'care_context_reference' => (string) ($row['care_context_reference'] ?? ''),
                    'care_context_display'   => (string) ($row['care_context_display'] ?? (($row['hi_type'] ?? '') . ' ' . substr((string) ($row['created_at'] ?? ''), 0, 10))),
                    'record_data'            => $bundle,
                ]);

                $ok = ! empty($result['ok']) && (int) $result['ok'] === 1;
                $httpCode = (int) ($result['http_code'] ?? 0);
                $statusVal = strtolower((string) ($result['status'] ?? ''));

                if ($ok || in_array($httpCode, [200, 201, 202, 409], true) || in_array($statusVal, ['queued', 'pushed', 'linked', 'duplicate'], true)) {
                    $db->table('health_records')->where('id', $row['id'])->update([
                        'push_status' => 'pushed',
                        'push_at'     => date('Y-m-d H:i:s'),
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                    $pushed++;
                    CLI::write('  Record #' . $row['id'] . ' (' . ($row['hi_type'] ?? '') . ' ' . ($row['care_context_reference'] ?? '') . ') -> pushed', 'green');
                } else {
                    $failed++;
                    $errMsg = (string) ($result['message'] ?? $result['error'] ?? 'Push failed');
                    $db->table('health_records')->where('id', $row['id'])->update([
                        'push_status' => 'failed',
                        'updated_at'  => date('Y-m-d H:i:s'),
                    ]);
                    CLI::write('  Record #' . $row['id'] . ' (' . ($row['hi_type'] ?? '') . ') -> failed: ' . $errMsg, 'red');
                }
            } catch (\Throwable $e) {
                $failed++;
                CLI::write('  Record #' . $row['id'] . ' (' . ($row['hi_type'] ?? '') . ') -> error: ' . $e->getMessage(), 'red');
            }
        }

        CLI::write('Pending Records: ' . $pushed . ' pushed, ' . $failed . ' failed.', $pushed > 0 ? 'green' : 'yellow');
    }

    private function resolveFhirBundle(array $row): ?array
    {
        $plain = trim((string) ($row['fhir_bundle'] ?? $row['record_data'] ?? ''));
        if ($plain === '') {
            $encrypted = trim((string) ($row['fhir_bundle_enc'] ?? ''));
            if ($encrypted !== '') {
                try {
                    $plain = (new FhirEncryptionService())->decrypt($encrypted);
                } catch (\Throwable $e) {
                    $plain = '';
                }
            }
        }

        if ($plain === '') {
            return null;
        }

        $decoded = json_decode($plain, true);
        if (! is_array($decoded)) {
            return null;
        }

        $bundle = $decoded['bundle'] ?? $decoded['fhir_bundle'] ?? $decoded;

        return is_array($bundle) && ($bundle['resourceType'] ?? '') === 'Bundle' ? $bundle : null;
    }

    private function loadPatientDemographics($db, int $patientId): array
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
