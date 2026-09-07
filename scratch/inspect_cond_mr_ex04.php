<?php

$ex04 = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json'), true);

echo "=== CONDITIONS IN EX04 ===\n";
foreach ($ex04['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Condition') {
        echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n\n";
    }
}

echo "=== MEDICATION REQUESTS IN EX04 ===\n";
foreach ($ex04['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'MedicationRequest') {
        echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n\n";
    }
}
