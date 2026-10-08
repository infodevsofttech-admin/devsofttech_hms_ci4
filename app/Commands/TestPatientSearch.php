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
            ['label' => 'Direct ABHA Address', 'get' => ['search_query' => 'kajolallu2001@sbx']],
            ['label' => 'Direct 14-digit ABHA Number', 'get' => ['search_query' => '91408143313273']],
            ['label' => 'Direct Hyphenated ABHA Number', 'get' => ['search_query' => '91-4081-4331-3273']],
            ['label' => 'Partial ABHA Address (kajol)', 'get' => ['search_query' => 'kajol']],
            ['label' => 'DataTables Search Box with ABHA Address', 'get' => ['dt_search' => 'kajolallu2001@sbx']],
            ['label' => 'DataTables Search Box with 14-digit ABHA Number', 'get' => ['dt_search' => '91408143313273']],
            ['label' => 'Advanced Search by ABHA Address', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => 'kajolallu2001@sbx']],
            ['label' => 'Advanced Search by 14-digit ABHA Number', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => '91408143313273']],
            ['label' => 'Advanced Search by Hyphenated ABHA Number', 'get' => ['adv_search_by' => 'abha', 'adv_search_value' => '91-4081-4331-3273']],
        ];

        foreach ($testCases as $tc) {
            CLI::write("Testing: " . $tc['label'], 'yellow');
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_GET = array_merge([
                'start' => 0,
                'length' => 10,
            ], $tc['get']);

            $controller = new \App\Controllers\Patient();
            $request = \Config\Services::request();
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
