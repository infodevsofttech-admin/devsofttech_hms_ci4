<?php
$files = glob(__DIR__ . '/../ABDM_FHIR/examples.json/*.json');
foreach ($files as $f) {
    $c = file_get_contents($f);
    if (stripos($c, 'ImmunizationRecord') !== false) {
        echo basename($f) . "\n";
    }
}
