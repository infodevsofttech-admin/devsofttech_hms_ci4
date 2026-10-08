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
    }
}
