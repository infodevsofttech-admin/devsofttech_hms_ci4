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

        CLI::write("\nTesting DOC-43156 push...", 'yellow');
        $docRes = $gw->executeAutoPushRecord('health_document', 43156, 15350);
        CLI::write("executeAutoPushRecord: " . json_encode($docRes));

        $docHr = $db->table('health_records')
            ->where('entity_id', '43156')
            ->where('hi_type', 'HealthDocumentRecord')
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (! empty($docHr['record_data'])) {
            $bundle = json_decode((string)$docHr['record_data'], true);
            CLI::write("Loaded bundle for DOC-43156, type: " . ($bundle['resourceType'] ?? 'none'));
            $res = $connector->pushRecord([
                'patient_id' => '15350',
                'patient_name' => 'DEVENDER SINGH',
                'abha_address' => 'singhdevender0328@sbx',
                'care_context_reference' => $docHr['care_context_reference'] ?: 'DOC-43156-20261008',
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
                $db->table('health_records')->where('id', (int)$docHr['id'])->update([
                    'push_status' => 'linked',
                    'push_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } else {
            CLI::write("DOC record_data is empty in HR #" . ($docHr['id'] ?? 0), 'red');
        }
    }
}
