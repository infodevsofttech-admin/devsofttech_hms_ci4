<?php
$sample07 = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);

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

echo "=== SAMPLE 07 ENTRIES ===\n";
foreach ($sample07['entry'] as $idx => $e) {
    echo "  $idx: " . $e['resource']['resourceType'] . "\n";
}

echo "\n=== OUR BUNDLE ENTRIES ===\n";
foreach ($ourBundle['entry'] as $idx => $e) {
    echo "  $idx: " . $e['resource']['resourceType'] . "\n";
}

echo "\n=== COMPOSITION COMPARISON ===\n";
$cSample = $sample07['entry'][0]['resource'];
$cOurs = $ourBundle['entry'][0]['resource'];
unset($cSample['text'], $cOurs['text']);
echo "Sample 07 Composition:\n" . json_encode($cSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Our Composition:\n" . json_encode($cOurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n=== ORGANIZATION COMPARISON ===\n";
$orgSample = null;
foreach ($sample07['entry'] as $e) { if ($e['resource']['resourceType'] === 'Organization') $orgSample = $e['resource']; }
$orgOurs = null;
foreach ($ourBundle['entry'] as $e) { if ($e['resource']['resourceType'] === 'Organization') $orgOurs = $e['resource']; }
unset($orgSample['text'], $orgOurs['text']);
echo "Sample 07 Organization:\n" . json_encode($orgSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Our Organization:\n" . json_encode($orgOurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n=== IMMUNIZATION COMPARISON ===\n";
$immSample = null;
foreach ($sample07['entry'] as $e) { if ($e['resource']['resourceType'] === 'Immunization') $immSample = $e['resource']; }
$immOurs = null;
foreach ($ourBundle['entry'] as $e) { if ($e['resource']['resourceType'] === 'Immunization') $immOurs = $e['resource']; }
unset($immSample['text'], $immOurs['text']);
echo "Sample 07 Immunization:\n" . json_encode($immSample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
echo "Our Immunization:\n" . json_encode($immOurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
