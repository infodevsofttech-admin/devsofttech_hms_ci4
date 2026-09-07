<?php
$json = json_decode(file_get_contents(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-OPConsultNote-example-05.json'), true);
echo "=== Resources in OPConsultNote-example-05 ===\n";
foreach ($json['entry'] as $e) {
    $r = $e['resource'];
    echo $r['resourceType'] . " (id: " . ($r['id'] ?? '') . ")\n";
    if ($r['resourceType'] === 'Composition') {
        echo "  title: " . ($r['title'] ?? '') . "\n";
        echo "  type: " . json_encode($r['type'] ?? '') . "\n";
        echo "  encounter: " . json_encode($r['encounter'] ?? '') . "\n";
        echo "  custodian: " . json_encode($r['custodian'] ?? '') . "\n";
        echo "  author: " . json_encode($r['author'] ?? '') . "\n";
    }
    if ($r['resourceType'] === 'Organization') {
        echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
    }
    if ($r['resourceType'] === 'Encounter') {
        echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
    }
}
