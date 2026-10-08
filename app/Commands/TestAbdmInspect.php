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

        if (CLI::getOption('hiu')) {
            CLI::newLine();
            CLI::write("=== RECENT HIU CONSENTS ===", 'yellow');
            if ($db->tableExists('abdm_hiu_consents')) {
                $cons = $db->table('abdm_hiu_consents')
                    ->orderBy('id', 'DESC')
                    ->limit(10)
                    ->get()
                    ->getResultArray();
                $conTable = [];
                foreach ($cons as $c) {
                    $conTable[] = [
                        'ID' => $c['id'],
                        'Pat' => $c['patient_id'] ?? 'N/A',
                        'ReqID' => substr((string)($c['consent_request_id'] ?? ''), 0, 18),
                        'ArtID' => substr((string)($c['consent_id'] ?? ''), 0, 18),
                        'Status' => $c['status'] ?? '',
                        'Created' => $c['created_at'] ?? '',
                    ];
                }
                CLI::table($conTable, ['ID', 'Pat', 'ReqID', 'ArtID', 'Status', 'Created']);
            }

            CLI::newLine();
            CLI::write("=== RECENT HIU DOCUMENTS ===", 'yellow');
            if ($db->tableExists('abdm_hiu_documents')) {
                $docs = $db->table('abdm_hiu_documents')
                    ->orderBy('id', 'DESC')
                    ->limit(15)
                    ->get()
                    ->getResultArray();
                $docTable = [];
                foreach ($docs as $d) {
                    $docTable[] = [
                        'ID' => $d['id'],
                        'Pat' => $d['patient_id'] ?? 'N/A',
                        'Ref' => substr((string)($d['care_context_reference'] ?? ''), 0, 22),
                        'Title' => substr((string)($d['document_title'] ?? ''), 0, 22),
                        'Org' => substr((string)($d['organization_name'] ?? 'N/A'), 0, 20),
                        'ArtID' => substr((string)($d['consent_artifact_id'] ?? 'N/A'), 0, 16),
                        'Date' => substr((string)($d['document_date'] ?? $d['created_at'] ?? ''), 0, 10),
                    ];
                }
                CLI::table($docTable, ['ID', 'Pat', 'Ref', 'Title', 'Org', 'ArtID', 'Date']);
            }
        }

        if (CLI::getOption('test-doc-filter')) {
            $filterRef = CLI::getOption('filter-ref') ?? '7f3ca078-c426-42ca-96cb-9e02a05ca19b';
            CLI::newLine();
            CLI::write("=== TESTING abdm_documents FILTER FOR REF: {$filterRef} ===", 'cyan');

            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET['consent_request_id'] = $filterRef;
            $_GET['limit'] = '200';

            $patientController = new \App\Controllers\Patient();
            $patientController->initController($request, $response, $logger);
            $res = $patientController->abdm_documents($patientId);
            $data = json_decode((string) $res->getBody(), true);

            CLI::write("Response ok: " . ($data['ok'] ?? 0) . ", items count: " . count($data['items'] ?? []), 'green');
            $testTable = [];
            foreach (($data['items'] ?? []) as $it) {
                $testTable[] = [
                    'ID' => $it['id'] ?? 0,
                    'Org' => substr((string) ($it['organization_name'] ?? 'N/A'), 0, 25),
                    'Title' => substr((string) ($it['document_title'] ?? 'N/A'), 0, 25),
                    'CareContext' => substr((string) ($it['care_context_reference'] ?? 'N/A'), 0, 25),
                    'ConsentReqId' => substr((string) ($it['consent_request_id'] ?? 'N/A'), 0, 16),
                ];
            }
            if (! empty($testTable)) {
                CLI::table($testTable, ['ID', 'Org', 'Title', 'CareContext', 'ConsentReqId']);
            }
        }
    }
}
