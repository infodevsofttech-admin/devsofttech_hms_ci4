<?php

$dir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/definitions.json/';
$files = glob($dir . '*diagnosis-use*.json');
foreach ($files as $f) {
    echo "Found: " . basename($f) . "\n";
    $data = json_decode(file_get_contents($f), true);
    if (isset($data['compose']['include'])) {
        echo json_encode($data['compose']['include'], JSON_PRETTY_PRINT) . "\n";
    }
}
