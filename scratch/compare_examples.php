<?php
$files = [
    'OPConsultNote' => 'ABDM_FHIR/examples.json/Bundle-OPConsultNote-example-05.json',
    'Prescription' => 'ABDM_FHIR/examples.json/Bundle-Prescription-example-06.json',
    'DischargeSummary' => 'ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json',
    'DiagnosticLab' => 'ABDM_FHIR/examples.json/Bundle-DiagnosticReport-Lab-example-03.json',
    'Immunization' => 'ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json',
    'Wellness' => 'ABDM_FHIR/examples.json/Bundle-WellnessRecord-example-01.json',
    'HealthDoc' => 'ABDM_FHIR/examples.json/Bundle-HealthDocumentRecord-example-01.json',
];

foreach ($files as $name => $path) {
    if (!file_exists($path)) continue;
    $b = json_decode(file_get_contents($path), true);
    $comp = $b['entry'][0]['resource'] ?? [];
    echo "=== $name ===\n";
    echo "title: " . ($comp['title'] ?? 'NONE') . "\n";
    echo "type.coding: " . json_encode($comp['type']['coding'] ?? []) . "\n";
    echo "type.text: " . ($comp['type']['text'] ?? 'NONE') . "\n";
    echo "category: " . json_encode($comp['category'] ?? 'NONE') . "\n";
    echo "custodian: " . json_encode($comp['custodian'] ?? 'NONE') . "\n";
    echo "author: " . json_encode($comp['author'] ?? 'NONE') . "\n";
    echo "encounter: " . json_encode($comp['encounter'] ?? 'NONE') . "\n";
    echo "section count: " . count($comp['section'] ?? []) . "\n";
    foreach ($comp['section'] ?? [] as $s) {
        echo "  section title: " . ($s['title'] ?? '') . " | code: " . json_encode($s['code'] ?? []) . "\n";
    }
    echo "\n";
}
