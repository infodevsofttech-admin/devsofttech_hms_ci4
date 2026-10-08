<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestAbdmInspect extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:inspect-patient';
    protected $description = 'Inspect patient care contexts and link status';

    public function run(array $params)
    {
        $abha = $params[0] ?? CLI::getOption('abha') ?? 'meerabisht1981@sbx';
        $patientId = (int) ($params[1] ?? CLI::getOption('patient_id') ?? 15352);

        $db = \Config\Database::connect();
        if (CLI::getOption('fix-link')) {
            $now = date('Y-m-d H:i:s');
            $db->table('patient_master')->where('id', $patientId)->update([
                'abha_verified_status' => 'LINKED',
                'abdm_linked_at' => $now,
            ]);
            $existing = $db->table('record_links')->where('care_context_reference', 'REG-P261010000' . $patientId)->get(1)->getRowArray();
            if (empty($existing)) {
                $db->table('record_links')->insert([
                    'abha_id' => $abha,
                    'care_context_reference' => 'REG-P261010000' . $patientId,
                    'link_status' => 'linked',
                    'linked_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $db->table('record_links')->where('id', $existing['id'])->update([
                    'link_status' => 'linked',
                    'linked_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            CLI::write("Recorded link for Patient {$patientId} in DB.", 'green');
        }

        CLI::write("Inspecting ABHA: {$abha} (Patient ID: {$patientId})...", 'yellow');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['abha_address'] = $abha;
        $_GET['patient_id'] = (string) $patientId;

        $gateway = new \App\Controllers\AbdmGateway();
        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $logger = service('logger');
        $gateway->initController($request, $response, $logger);

        $res = $gateway->hipPatientCareContexts();
        $body = $res->getBody();
        $data = json_decode($body, true);

        if (empty($data['ok'])) {
            CLI::error('Error: ' . ($data['error_text'] ?? 'Unknown error'));
            return;
        }

        $contexts = $data['care_contexts'] ?? [];
        CLI::write("Total Care Contexts: " . count($contexts), 'green');
        CLI::newLine();

        $table = [];
        foreach ($contexts as $cc) {
            $table[] = [
                'Reference' => $cc['ref'],
                'HI Type'   => $cc['hi_type'],
                'FHIR'      => $cc['is_fhir_ready'] ? 'Ready' : 'Draft',
                'Status'    => $cc['is_linked'] ? 'LINKED' : 'UNLINKED',
                'Display'   => $cc['display'],
            ];
        }

        CLI::table($table, ['Reference', 'HI Type', 'FHIR', 'Status', 'Display']);

        $linksRes = $gateway->hipPatientLinks();
        $linksData = json_decode($linksRes->getBody(), true);
        $linkedContexts = $linksData['care_contexts'] ?? [];
        if (! empty($linkedContexts)) {
            CLI::newLine();
            CLI::write("HIP Linked Records ({$linksData['total_linked']}):", 'green');
            $linkTable = [];
            foreach ($linkedContexts as $lc) {
                $linkTable[] = [
                    'Reference' => $lc['referenceNumber'] ?? '',
                    'Display'   => $lc['display'] ?? '',
                    'Status'    => strtoupper($lc['status'] ?? 'linked'),
                    'Linked At' => $lc['linked_at'] ?? 'N/A',
                    'Source'    => $lc['source'] ?? 'bridge',
                ];
            }
            CLI::table($linkTable, ['Reference', 'Display', 'Status', 'Linked At', 'Source']);
        }
    }
}
