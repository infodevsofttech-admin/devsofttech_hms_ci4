<?php
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$gateway = new \App\Controllers\AbdmGateway();
$req = \Config\Services::request();
$resp = \Config\Services::response();
$logger = \Config\Services::logger();
$gateway->initController($req, $resp, $logger);

$db = \Config\Database::connect();
echo "DB: " . $db->getDatabase() . "\n";

$row = $db->table('patient_master')->where('id', 11)->get(1)->getRowArray();
echo "Patient 11: " . json_encode($row) . "\n";
