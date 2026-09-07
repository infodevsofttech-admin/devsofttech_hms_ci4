<?php

$ex04 = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-DischargeSummary-example-04.json'), true);
foreach ($ex04['entry'] as $e) {
    if ($e['resource']['resourceType'] === 'Patient') {
        echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n";
    }
}
