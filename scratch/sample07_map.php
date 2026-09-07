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

$ref = new ReflectionClass($gateway);
$prop = $ref->getProperty('db');
$prop->setAccessible(true);
$prop->setValue($gateway, $db);

$method = $ref->getMethod('buildImmunizationGatewayPayload');
$method->setAccessible(true);
$payload = $method->invoke($gateway, 11, 10, '', false);

$sample07 = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);

echo "=== Sample 07 structure ===\n";
foreach ($sample07['entry'] as $idx => $entry) {
    $res = $entry['resource'];
    echo "Entry $idx: " . $res['resourceType'] . " (" . $res['id'] . ")\n";
    foreach ($res as $k => $v) {
        if ($k === 'text') continue;
        $type = gettype($v);
        $valSummary = is_array($v) ? (isset($v[0]) ? 'array['.count($v).']' : 'assoc{'.implode(',', array_keys($v)).'}') : (string)$v;
        echo "  - $k: $valSummary\n";
    }
}
