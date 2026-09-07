<?php
foreach (['Bundle-OPConsultNote-example-05.json', 'Bundle-DischargeSummary-example-04.json'] as $file) {
    $json = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/' . $file), true);
    echo "=== $file Encounter ===\n";
    foreach ($json['entry'] as $e) {
        if ($e['resource']['resourceType'] === 'Encounter') {
            echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n";
        }
    }
}
