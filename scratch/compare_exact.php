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

echo "=== Top-level Bundle comparison ===\n";
echo "Our Bundle id: " . $ourBundle['id'] . "\n";
echo "Sample 07 id: " . $sample07['id'] . "\n";
echo "Our Bundle identifier: " . json_encode($ourBundle['identifier']) . "\n";
echo "Sample 07 identifier: " . json_encode($sample07['identifier']) . "\n";

echo "\n=== Resources in Bundle ===\n";
$ourResTypes = array_map(fn($e) => $e['resource']['resourceType'], $ourBundle['entry']);
$sampleResTypes = array_map(fn($e) => $e['resource']['resourceType'], $sample07['entry']);
echo "Our resources: " . implode(', ', $ourResTypes) . "\n";
echo "Sample 07 resources: " . implode(', ', $sampleResTypes) . "\n";

echo "\n=== Composition comparison ===\n";
$ourComp = $ourBundle['entry'][0]['resource'];
$sampleComp = $sample07['entry'][0]['resource'];

echo "Our Composition fields: " . implode(', ', array_keys($ourComp)) . "\n";
echo "Sample 07 Comp fields: " . implode(', ', array_keys($sampleComp)) . "\n";

echo "\nOur Composition:\n" . json_encode($ourComp, JSON_PRETTY_PRINT) . "\n";
echo "\nSample 07 Composition:\n" . json_encode($sampleComp, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Organization comparison ===\n";
$ourOrg = null;
foreach ($ourBundle['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Organization') $ourOrg = $e['resource'];
}
$sampleOrg = null;
foreach ($sample07['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Organization') $sampleOrg = $e['resource'];
}
echo "Our Organization:\n" . json_encode($ourOrg, JSON_PRETTY_PRINT) . "\n";
echo "Sample 07 Organization:\n" . json_encode($sampleOrg, JSON_PRETTY_PRINT) . "\n";

