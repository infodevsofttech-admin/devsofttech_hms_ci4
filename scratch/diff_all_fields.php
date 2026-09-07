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

$ourBundle = $payload['bundle'];
$sample07 = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);

function getStructure($data, $prefix = '') {
    $keys = [];
    if (!is_array($data)) return [$prefix => gettype($data)];
    foreach ($data as $k => $v) {
        $path = $prefix === '' ? (string)$k : $prefix . '.' . (is_numeric($k) ? '*' : $k);
        if (is_array($v)) {
            $keys = array_merge($keys, getStructure($v, $path));
        } else {
            $keys[$path] = gettype($v);
        }
    }
    return $keys;
}

echo "=== Fields present in Sample 07 but MISSING in Our Bundle ===\n";
$ourStruct = getStructure($ourBundle);
$sampleStruct = getStructure($sample07);

foreach ($sampleStruct as $path => $type) {
    // Simplify array indices
    if (!array_key_exists($path, $ourStruct)) {
        // Exclude specific uuid differences
        echo "Missing: $path ($type)\n";
    }
}

echo "\n=== Fields present in Our Bundle but NOT in Sample 07 ===\n";
foreach ($ourStruct as $path => $type) {
    if (!array_key_exists($path, $sampleStruct)) {
        echo "Extra: $path ($type)\n";
    }
}
