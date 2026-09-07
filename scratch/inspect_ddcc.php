<?php
$bundle = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-for-WHO-DDCC.json'), true);
echo "Entries in WHO-DDCC:\n";
foreach ($bundle['entry'] as $i => $entry) {
    echo "Entry $i: " . ($entry['resource']['resourceType'] ?? '') . "\n";
}
