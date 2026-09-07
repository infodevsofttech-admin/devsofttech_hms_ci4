<?php
$bundle = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-ImmunizationRecord-example-07.json'), true);
echo "Bundle Type: " . ($bundle['resourceType'] ?? '') . "\n";
echo "Profile: " . json_encode($bundle['meta']['profile'] ?? []) . "\n";
echo "Identifier: " . json_encode($bundle['identifier'] ?? []) . "\n";

foreach ($bundle['entry'] as $i => $entry) {
    $res = $entry['resource'] ?? [];
    echo "Entry $i: " . ($res['resourceType'] ?? '') . " | id: " . ($res['id'] ?? '') . " | fullUrl: " . ($entry['fullUrl'] ?? '') . "\n";
}
