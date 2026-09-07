<?php
define('FCPATH', __DIR__ . '/../public/');
require 'vendor/codeigniter4/framework/system/Common.php';
require 'vendor/autoload.php';

$app = new \CodeIgniter\CodeIgniter(new \Config\App());
$app->initialize();

$controller = new \App\Controllers\Api\v1\DoctorApi();

for ($d = 0; $d <= 15; $d++) {
    $res = $controller->ipdList($d);
    $json = json_decode($res->getBody(), true);
    if ($json['status'] == 1 && count($json['patients']) > 0) {
        echo "docId = $d returns " . count($json['patients']) . " patients:\n";
        foreach ($json['patients'] as $p) {
            echo "  - " . $p['ipd_code'] . " | " . $p['patient_display_name'] . " | " . ($p['assigned_doctors'] ?? '') . "\n";
        }
    }
}
