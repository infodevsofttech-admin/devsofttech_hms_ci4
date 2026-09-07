<?php

$dir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/definitions.json/';
$files = glob($dir . '*Invoice*.json');
echo "Definitions with Invoice:\n";
foreach ($files as $f) {
    echo " - " . basename($f) . "\n";
}

$exDir = 'd:/Workplace/HMS_CI4_OLD/ABDM_FHIR/examples.json/';
$exFiles = glob($exDir . '*Invoice*.json');
echo "Examples with Invoice:\n";
foreach ($exFiles as $f) {
    echo " - " . basename($f) . "\n";
}
