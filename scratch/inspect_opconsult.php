<?php
$b = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-OPConsultNote-example-05.json'), true);
echo "Entries in OPConsult:\n";
foreach ($b['entry'] as $i => $entry) {
    $res = $entry['resource'];
    echo "Entry $i: " . $res['resourceType'] . " | id: " . ($res['id'] ?? '') . "\n";
    if ($res['resourceType'] === 'Encounter') {
        echo "  Encounter: " . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    if ($res['resourceType'] === 'Organization') {
        echo "  Organization: " . json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    if ($res['resourceType'] === 'Composition') {
        echo "  Composition title: " . ($res['title'] ?? '') . "\n";
        echo "  Composition type: " . json_encode($res['type'] ?? []) . "\n";
        echo "  Composition encounter: " . json_encode($res['encounter'] ?? []) . "\n";
    }
}
