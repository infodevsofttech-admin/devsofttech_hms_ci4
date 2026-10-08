<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Controllers\AbdmGateway;
use App\Libraries\Abdm\AbdmConnectorFactory;

class TestBridgePushCommand extends BaseCommand
{
    protected $group       = 'ABDM';
    protected $name        = 'abdm:test-bridge-push';
    protected $description = 'Test pushing LAB and DOC records to Bridge';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $connector = AbdmConnectorFactory::make();
        $gw = new AbdmGateway();

        CLI::write("Testing LAB-51467 push...", 'yellow');
        $labHr = $db->table('health_records')->where('id', 370)->get(1)->getRowArray();
        if (! empty($labHr['record_data'])) {
            $bundle = json_decode((string)$labHr['record_data'], true);
            CLI::write("Loaded bundle for LAB-51467, type: " . ($bundle['resourceType'] ?? 'none'));
            $res = $connector->pushRecord([
                'patient_id' => '15350',
                'patient_name' => 'DEVENDER SINGH',
                'abha_address' => 'singhdevender0328@sbx',
                'care_context_reference' => 'LAB-51467-20261008',
                'care_context_display' => 'Diagnostic Report - 08 Oct 2026',
                'hi_type' => 'DiagnosticReportRecord',
                'record_type' => 'DiagnosticReportRecord',
                'visit_date' => '2026-10-08',
                'record_data' => $bundle,
            ]);
            CLI::write("LAB push result: " . json_encode($res), ($res['ok'] ?? 0) === 1 ? 'green' : 'red');
            if (($res['ok'] ?? 0) === 1 && ! empty($res['queue_id'])) {
                $db->table('record_links')->where('care_context_reference', 'LAB-51467-20261008')->update([
                    'abdm_txn_id' => $res['queue_id'],
                    'link_status' => 'linked',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        CLI::write("\nTesting DOC-43156 step-by-step...", 'yellow');
        try {
            $docCtrl = new \App\Controllers\DoctorDocument();
            $src = $docCtrl->buildHealthDocumentSource(43156);
            CLI::write("Source result: " . json_encode($src));
            if (! empty($src)) {
                $factory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
                $gen = $factory->healthDocument()->generate($src);
                CLI::write("Generated bundle: " . ($gen['bundle']['resourceType'] ?? 'none'));
                $adapter = new \App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter();
                $hfrId = (string) ($src['hfr_id'] ?? 'HFR-IN-HMS');
                $payload = $adapter->toGatewayPayload($gen, $src, $hfrId);
                CLI::write("Adapter payload care context: " . ($payload['care_context_reference'] ?? 'none'));

                if (! empty($payload['fhir_bundle'])) {
                    $bundle = $payload['fhir_bundle'];
                    CLI::write("Loaded bundle for DOC-43156, type: " . ($bundle['resourceType'] ?? 'none'));
                    $res = $connector->pushRecord([
                        'patient_id' => '15350',
                        'patient_name' => 'DEVENDER SINGH',
                        'abha_address' => 'singhdevender0328@sbx',
                        'care_context_reference' => $payload['care_context_reference'] ?: 'DOC-43156-20261008',
                        'care_context_display' => 'Health Document - 08 Oct 2026',
                        'hi_type' => 'HealthDocumentRecord',
                        'record_type' => 'HealthDocumentRecord',
                        'visit_date' => '2026-10-08',
                        'record_data' => $bundle,
                    ]);
                    CLI::write("DOC push result: " . json_encode($res), ($res['ok'] ?? 0) === 1 ? 'green' : 'red');
                    if (($res['ok'] ?? 0) === 1 && ! empty($res['queue_id'])) {
                        $db->table('record_links')->where('care_context_reference', 'DOC-43156-20261008')->update([
                            'abdm_txn_id' => $res['queue_id'],
                            'link_status' => 'linked',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $db->table('health_records')->where('id', 373)->update([
                            'push_status' => 'linked',
                            'record_data' => json_encode($bundle),
                            'push_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            CLI::write("DoctorDocument error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine(), 'red');
        }
    }
}
