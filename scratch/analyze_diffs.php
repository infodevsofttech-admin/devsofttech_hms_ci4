<?php
$our_json = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/writable/ipd9_bundle.json'), true);
$f1 = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/scratch/fhir_1.json'), true);

echo "=== FHIR_1 vs our_FHIR Analysis ===\n";

echo "\n--- FHIR_1 Sections in Composition ---\n";
foreach ($f1['entry'][0]['resource']['section'] as $s) {
    echo "Title: " . $s['title'] . " | Code: " . $s['code']['coding'][0]['code'] . " | Entries: " . count($s['entry']) . "\n";
}

echo "\n--- our_FHIR Sections in Composition ---\n";
foreach ($our_json['entry'][0]['resource']['section'] as $s) {
    echo "Title: " . $s['title'] . " | Code: " . $s['code']['coding'][0]['code'] . " | Entries: " . count($s['entry']) . "\n";
}

// Let's check differences in:
// 1. Encounter resource
echo "\n--- FHIR_1 Encounter ---\n";
print_r($f1['entry'][4]['resource']);

echo "\n--- our_FHIR Encounter ---\n";
foreach ($our_json['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Encounter') {
        print_r($e['resource']);
    }
}

// Let's check differences in:
// 2. Patient resource
echo "\n--- FHIR_1 Patient identifiers ---\n";
print_r($f1['entry'][1]['resource']['identifier']);

echo "\n--- our_FHIR Patient identifiers ---\n";
print_r($our_json['entry'][1]['resource']['identifier']);

// Let's check differences in:
// 3. Organization resource
echo "\n--- FHIR_1 Organization identifiers ---\n";
print_r($f1['entry'][3]['resource']['identifier']);

echo "\n--- our_FHIR Organization identifiers ---\n";
print_r($our_json['entry'][3]['resource']['identifier']);

// Let's check differences in:
// 4. Practitioner resource
echo "\n--- FHIR_1 Practitioner identifiers ---\n";
print_r($f1['entry'][2]['resource']['identifier']);

echo "\n--- our_FHIR Practitioner identifiers ---\n";
print_r($our_json['entry'][2]['resource']['identifier']);
