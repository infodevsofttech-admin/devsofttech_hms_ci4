<?php
require __DIR__ . '/../vendor/codeigniter4/framework/system/Test/bootstrap.php';

$config = [
    'DSN'      => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'hms_data_ci4',
    'DBDriver' => 'MySQLi',
    'DBPrefix' => '',
    'pConnect' => false,
    'DBDebug'  => true,
    'charset'  => 'utf8',
    'DBCollat' => 'utf8_general_ci',
    'swapPre'  => '',
    'encrypt'  => false,
    'compress' => false,
    'strictOn' => false,
    'failover' => [],
    'port'     => 3306,
];
$db = \Config\Database::connect($config);

$gateway = new \App\Controllers\AbdmGateway();
$req = \Config\Services::request();
$resp = \Config\Services::response();
$logger = \Config\Services::logger();
$gateway->initController($req, $resp, $logger);

// inject MySQL db into gateway
$ref = new ReflectionClass($gateway);
$prop = $ref->getProperty('db');
$prop->setAccessible(true);
$prop->setValue($gateway, $db);

$method = $ref->getMethod('buildImmunizationGatewayPayload');
$method->setAccessible(true);
$payload = $method->invoke($gateway, 11, 10, '', false);

if (!$payload) {
    echo "Payload is null!\n";
    exit;
}

echo "Care Context: " . $payload['care_context_reference'] . "\n";
foreach ($payload['bundle']['entry'] as $e) {
    $r = $e['resource'];
    echo "=== {$r['resourceType']} ===\n";
    unset($r['text']);
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

