<?php

$dir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/examples.json/';
$files = glob($dir . '*.json');

$mrs = [];
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (!str_contains($content, 'MedicationRequest')) continue;
    $data = json_decode($content, true);
    if (!$data) continue;

    if (($data['resourceType'] ?? '') === 'MedicationRequest') {
        $mrs[basename($f)] = $data;
    } elseif (($data['resourceType'] ?? '') === 'Bundle' && isset($data['entry'])) {
        foreach ($data['entry'] as $idx => $entry) {
            if (($entry['resource']['resourceType'] ?? '') === 'MedicationRequest') {
                $mrs[basename($f) . " [entry $idx]"] = $entry['resource'];
            }
        }
    }
}

echo "Found " . count($mrs) . " MedicationRequests in examples:\n";
foreach ($mrs as $name => $mr) {
    echo "\n=== $name ===\n";
    echo "Keys: " . implode(', ', array_keys($mr)) . "\n";
    if (isset($mr['category'])) {
        echo "category: " . json_encode($mr['category']) . "\n";
    }
    if (isset($mr['medicationCodeableConcept'])) {
        echo "medicationCodeableConcept: " . json_encode($mr['medicationCodeableConcept']) . "\n";
    }
    if (isset($mr['dosageInstruction'])) {
        echo "dosageInstruction keys: " . implode(', ', array_keys($mr['dosageInstruction'][0] ?? [])) . "\n";
        echo "dosageInstruction[0]: " . json_encode($mr['dosageInstruction'][0]) . "\n";
    }
}
