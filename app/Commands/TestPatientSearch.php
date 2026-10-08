<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestPatientSearch extends BaseCommand
{
    protected $group = 'Test';
    protected $name = 'patient:test-search';
    protected $description = 'Test patient search by ABHA ID or Address in search_ajax';

    public function run(array $params)
    {
        $testCases = [
            ['label' => 'Direct ABHA Address (Meera)', 'get' => ['search_query' => 'meerabisht1981@sbx']],
            ['label' => 'Direct 14-digit ABHA Number (Meera)', 'get' => ['search_query' => '91178766183200']],
            ['label' => 'Direct Hyphenated ABHA Number (Meera)', 'get' => ['search_query' => '91-1787-6618-3200']],
            ['label' => 'Partial ABHA Address (meera)', 'get' => ['search_query' => 'meera']],
            ['label' => 'Partial ABHA Address (janvibisht)', 'get' => ['search_query' => 'janvibisht']],
            ['label' => 'DataTables Search Box with ABHA Address (Meera)', 'get' => ['dt_search' => 'meerabisht1981@sbx']],
            ['label' => 'DataTables Search Box with 14-digit ABHA Number (Meera)', 'get' => ['dt_search' => '91178766183200']],
            ['label' => 'DataTables Search Box with Hyphenated ABHA Number (Meera)', 'get' => ['dt_search' => '91-1787-6618-3200']],
            ['label' => 'Advanced Search by ABHA Address (Meera)', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => 'meerabisht1981@sbx']],
            ['label' => 'Advanced Search by 14-digit ABHA Number (Meera)', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => '91178766183200']],
            ['label' => 'Advanced Search by Hyphenated ABHA Number (Meera)', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => '91-1787-6618-3200']],
            ['label' => 'Advanced Search by Partial ABHA (janvi)', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => 'janvi']],
            ['label' => 'Direct ABHA Address (Rahul - has abha_address only)', 'get' => ['search_query' => 'rahulsingh@sbx']],
            ['label' => 'Direct ABHA Address with 14 digits in handle (Dhairya)', 'get' => ['search_query' => '91310013085603@sbx']],
        ];

        foreach ($testCases as $tc) {
            CLI::write("Testing: " . $tc['label'], 'yellow');
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $params = array_merge([
                'start' => 0,
                'length' => 10,
            ], $tc['get']);
            $_GET = $params;

            $request = \Config\Services::request(null, false);
            $request->setGlobal('get', $params);
            \Config\Services::injectMock('request', $request);

            $controller = new \App\Controllers\Patient();
            $response = \Config\Services::response();
            $logger = service('logger');
            $controller->initController($request, $response, $logger);

            $res = $controller->search_ajax();
            $body = $res->getBody();
            $json = json_decode($body, true);

            $total = $json['recordsFiltered'] ?? 0;
            $data = $json['data'] ?? [];

            CLI::write("  Records found: " . $total, $total > 0 ? 'green' : 'red');
            foreach ($data as $idx => $row) {
                // $row is [sr_no, p_code_cell, name_cell, age, last_visit, insurance, history]
                $pCode = strip_tags($row[1] ?? '');
                $pName = strip_tags($row[2] ?? '');
                CLI::write("    [#{$idx}] Code/ABHA: {$pCode} | Name: {$pName}");
            }
            CLI::newLine();
        }
    }
}
