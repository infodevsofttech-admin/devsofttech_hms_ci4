<?php

require_once __DIR__ . '/verify_user_bundle.php'; // loads $bundle

$ex04 = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json'), true);

echo "\n================ COMPARISON WITH EXAMPLE 04 ================\n";

// 1. Bundle root
echo "\n1. Bundle Root:\n";
echo "Ex04 resourceType: {$ex04['resourceType']}, User: {$bundle['resourceType']}\n";
echo "Ex04 profile: " . json_encode($ex04['meta']['profile']) . "\nUser: " . json_encode($bundle['meta']['profile']) . "\n";
echo "Ex04 security: " . json_encode($ex04['meta']['security']) . "\nUser: " . json_encode($bundle['meta']['security']) . "\n";
echo "Ex04 type: {$ex04['type']}, User: {$bundle['type']}\n";

// 2. Composition
echo "\n2. Composition comparison:\n";
$compEx = $ex04['entry'][0]['resource'];
$compUs = $bundle['entry'][0]['resource'];

foreach (['status', 'type', 'title', 'subject', 'encounter', 'date', 'author', 'custodian'] as $f) {
    $v1 = isset($compEx[$f]) ? 'YES' : 'NO';
    $v2 = isset($compUs[$f]) ? 'YES' : 'NO';
    echo "  $f -> Ex04: $v1, User: $v2\n";
}

// 3. Compare each resource type against ex04 equivalent
function checkFields($exResource, $userResource, $type) {
    echo "\n--- $type comparison ---\n";
    $exKeys = array_keys($exResource);
    $usKeys = array_keys($userResource);
    $diffExtra = array_diff($usKeys, $exKeys);
    $diffMissing = array_diff($exKeys, $usKeys);
    echo "User extra fields: " . (empty($diffExtra) ? "None" : implode(', ', $diffExtra)) . "\n";
    echo "User missing fields: " . (empty($diffMissing) ? "None" : implode(', ', $diffMissing)) . "\n";
}

// Map ex04 resources by type
$exByType = [];
foreach ($ex04['entry'] as $e) {
    $t = $e['resource']['resourceType'];
    if (!isset($exByType[$t])) $exByType[$t] = [];
    $exByType[$t][] = $e['resource'];
}

foreach ($bundle['entry'] as $e) {
    $t = $e['resource']['resourceType'];
    if ($t === 'Composition') continue;
    if (isset($exByType[$t][0])) {
        checkFields($exByType[$t][0], $e['resource'], $t);
        // Only check once per type
        unset($exByType[$t]);
    }
}
