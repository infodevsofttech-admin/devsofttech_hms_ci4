<?php

$p6 = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-Prescription-example-06.json'), true);
foreach ($p6['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'MedicationRequest') {
        echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n\n";
    }
}
