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

echo "=== BUNDLE HEADER ===\n";
echo "Example meta: " . json_encode($example['meta'] ?? []) . "\n";
echo "Our meta:     " . json_encode($ourBundle['meta'] ?? []) . "\n";
echo "Example identifier: " . json_encode($example['identifier'] ?? []) . "\n";
echo "Our identifier:     " . json_encode($ourBundle['identifier'] ?? []) . "\n";

echo "\n=== RESOURCE TYPES IN ENTRIES ===\n";
$exTypes = array_map(fn($e) => $e['resource']['resourceType'], $example['entry']);
$ourTypes = array_map(fn($e) => $e['resource']['resourceType'], $ourBundle['entry']);
echo "Example types: " . implode(', ', $exTypes) . "\n";
echo "Our types:     " . implode(', ', $ourTypes) . "\n";

echo "\n=== COMPOSITION COMPARISON ===\n";
$exComp = $example['entry'][0]['resource'];
$ourComp = $ourBundle['entry'][0]['resource'];

echo "Example keys: " . implode(', ', array_keys($exComp)) . "\n";
echo "Our keys:     " . implode(', ', array_keys($ourComp)) . "\n\n";

foreach (['id', 'meta', 'language', 'text', 'identifier', 'status', 'type', 'category', 'subject', 'encounter', 'date', 'author', 'title', 'custodian', 'section'] as $k) {
    echo "--- Field: $k ---\n";
    echo "Example: " . json_encode($exComp[$k] ?? 'MISSING', JSON_UNESCAPED_SLASHES) . "\n";
    echo "Our:     " . json_encode($ourComp[$k] ?? 'MISSING', JSON_UNESCAPED_SLASHES) . "\n\n";
}
