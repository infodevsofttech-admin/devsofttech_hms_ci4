<?php
$example = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);

require 'd:/Workplace/HMS_CI4_OLD/vendor/codeigniter4/framework/system/Test/bootstrap.php';
$gateway = new App\Controllers\AbdmGateway();
$db = \Config\Database::connect('default');
$dbProperty = new ReflectionProperty($gateway, 'db');
$dbProperty->setValue($gateway, $db);

$reflection = new ReflectionMethod($gateway, 'buildImmunizationGatewayPayload');
$payload = $reflection->invoke($gateway, 11, 10, '91510165305101', false);
$ourBundle = $payload['bundle'];

$exImm = null;
foreach ($example['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Immunization') $exImm = $e['resource'];
}
$ourImm = null;
foreach ($ourBundle['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Immunization') $ourImm = $e['resource'];
}

echo "=== EXAMPLE IMMUNIZATION ===\n" . json_encode($exImm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
echo "=== OUR IMMUNIZATION ===\n" . json_encode($ourImm, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
