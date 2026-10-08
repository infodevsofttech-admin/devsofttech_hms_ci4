<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestAbdmPdf extends BaseCommand
{
    protected $group = 'Test';
    protected $name = 'test:abdm-pdf';
    protected $description = 'Test ABDM Health Records PDF generation';
    protected $usage = 'test:abdm-pdf [patient_id] [consent_request_id]';
    protected $arguments = [
        'patient_id' => 'Patient ID to test (default 15350)',
        'consent_request_id' => 'Optional consent request ID filter',
    ];

    public function run(array $params)
    {
        $patientId = (int) ($params[0] ?? 15350);
        $consentReqId = (string) ($params[1] ?? '');

        CLI::write("Testing ABDM PDF generation for patient: {$patientId}", 'yellow');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $get = [];
        if ($consentReqId !== '') {
            if (is_numeric($consentReqId) && strlen($consentReqId) < 10) {
                $get['doc_id'] = (int) $consentReqId;
            } else {
                $get['consent_request_id'] = $consentReqId;
            }
        }
        $_GET = $get;

        $request = \Config\Services::request(null, false);
        $request->setGlobal('get', $get);
        \Config\Services::injectMock('request', $request);

        $controller = new \App\Controllers\Patient();
        $response = \Config\Services::response();
        $logger = service('logger');
        $controller->initController($request, $response, $logger);

        $startTime = microtime(true);
        $res = $controller->abdm_records_pdf($patientId);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $status = $res->getStatusCode();
        $contentType = $res->getHeaderLine('Content-Type');
        $disposition = $res->getHeaderLine('Content-Disposition');
        $body = $res->getBody();
        $len = strlen($body);

        CLI::write("Status: {$status}", $status === 200 ? 'green' : 'red');
        CLI::write("Content-Type: {$contentType}");
        CLI::write("Disposition: {$disposition}");
        CLI::write("PDF Size: {$len} bytes (generated in {$duration} ms)");

        if (str_starts_with($body, '%PDF-')) {
            CLI::write("VALID PDF HEADER: " . substr($body, 0, 8), 'green');
            $outPath = WRITEPATH . 'cache/test_abdm_records.pdf';
            file_put_contents($outPath, $body);
            CLI::write("Saved test PDF to: {$outPath}", 'green');
        } else {
            CLI::write("ERROR: Output is not a valid PDF: " . substr($body, 0, 200), 'red');
        }
    }
}
