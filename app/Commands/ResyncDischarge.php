<?php

namespace App\Commands;

use App\Libraries\Abdm\Sync\AbdmSyncWorkerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ResyncDischarge extends BaseCommand
{
    protected $group = 'ABDM';
    protected $name = 'abdm:resync-discharge';
    protected $description = 'Resync IPD Discharge FHIR Bundle for ABDM';

    public function run(array $params)
    {
        $ipdId = (int) ($params[0] ?? CLI::getOption('ipd') ?? 3222);
        CLI::write("Triggering discharge sync for IPD: {$ipdId}...", 'yellow');

        $ctrl = new \App\Controllers\Ipd_discharge();
        $request = \Config\Services::request();
        $response = \Config\Services::response();
        $logger = \Config\Services::logger();
        $ctrl->initController($request, $response, $logger);

        $ctrl->enqueueIpdDischargeSync($ipdId);
        CLI::write("Discharge sync enqueued successfully.", 'green');

        CLI::write("Processing ABDM outbox...", 'yellow');
        $service = new AbdmSyncWorkerService();
        $summary = $service->process(10, 'spark-resync-discharge');
        CLI::write("Outbox processed: " . json_encode($summary), 'green');

        // Inspect gateway DB
        CLI::write("\n=== Verifying Record in Gateway DB ===", 'cyan');
        $db = \Config\Database::connect();
        try {
            $gatewayDb = \Config\Database::connect([
                'DSN'      => '',
                'hostname' => 'localhost',
                'username' => 'root',
                'password' => '',
                'database' => 'abdm_gateway_db',
                'DBDriver' => 'MySQLi',
                'charset'  => 'utf8mb4',
            ]);
            $row = $gatewayDb->table('abdm_health_records')
                ->where('care_context_reference LIKE', "%{$ipdId}%")
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($row)) {
                CLI::write("Latest Record ID: " . $row['id'] . " | Ref: " . $row['care_context_reference'] . " | Updated: " . $row['updated_at'], 'green');
                $bundle = is_string($row['fhir_bundle']) ? json_decode($row['fhir_bundle'], true) : $row['fhir_bundle'];
                $entries = (array) ($bundle['entry'] ?? []);
                
                $typeCounts = [];
                foreach ($entries as $e) {
                    $t = $e['resource']['resourceType'] ?? 'Unknown';
                    $typeCounts[$t] = ($typeCounts[$t] ?? 0) + 1;
                }
                CLI::write("Resource Types: " . json_encode($typeCounts), 'yellow');

                foreach ($entries as $e) {
                    if (($e['resource']['resourceType'] ?? '') === 'Composition') {
                        CLI::write("\nComposition Sections:", 'cyan');
                        foreach (($e['resource']['section'] ?? []) as $s) {
                            $title = $s['title'] ?? '';
                            $codings = array_map(function ($c) {
                                return ($c['code'] ?? '') . ' (' . ($c['display'] ?? '') . ')';
                            }, (array) ($s['code']['coding'] ?? []));
                            $refs = array_map(function ($r) {
                                return $r['reference'] ?? '';
                            }, (array) ($s['entry'] ?? []));
                            CLI::write("  - [{$title}] Codes: " . implode(', ', $codings) . " -> " . json_encode($refs));
                        }
                    }
                }

                CLI::write("\nMedicationRequests:", 'cyan');
                foreach ($entries as $e) {
                    if (($e['resource']['resourceType'] ?? '') === 'MedicationRequest') {
                        $m = $e['resource'];
                        CLI::write("  - ID: " . ($m['id'] ?? '') . " | Name: " . ($m['medicationCodeableConcept']['text'] ?? ''));
                    }
                }

                CLI::write("\nCarePlans:", 'cyan');
                foreach ($entries as $e) {
                    if (($e['resource']['resourceType'] ?? '') === 'CarePlan') {
                        $cp = $e['resource'];
                        CLI::write("  - ID: " . ($cp['id'] ?? '') . " | Title: " . ($cp['title'] ?? '') . " | Desc: " . substr(str_replace("\n", " ", (string) ($cp['description'] ?? '')), 0, 90));
                    }
                }

                foreach ($entries as $e) {
                    if (($e['resource']['resourceType'] ?? '') === 'DocumentReference') {
                        foreach (($e['resource']['content'] ?? []) as $c) {
                            $data = (string) ($c['attachment']['data'] ?? '');
                            $decodedBytes = strlen(base64_decode($data));
                            CLI::write("\nDocumentReference Attached PDF Size: {$decodedBytes} bytes (base64: " . strlen($data) . ")", 'green');
                        }
                    }
                }
            } else {
                CLI::write("No record found for IPD {$ipdId} in abdm_gateway_db.", 'red');
            }
        } catch (\Throwable $e) {
            CLI::write("Error inspecting gateway db: " . $e->getMessage(), 'red');
        }
    }
}
