<?php
$data = json_decode(file_get_contents('ABDM_FHIR/examples.json/Bundle-DiagnosticReport-Lab-example-03.json'), true);
foreach ($data['entry'] as $i => $e) {
    $r = $e['resource'];
    echo "Entry $i: " . $r['resourceType'] . "\n";
    if ($r['resourceType'] === 'Composition') {
        echo "  title: " . ($r['title'] ?? '') . "\n";
        echo "  custodian: " . json_encode($r['custodian'] ?? '') . "\n";
        echo "  author: " . json_encode($r['author'] ?? '') . "\n";
        echo "  encounter: " . json_encode($r['encounter'] ?? '') . "\n";
    }
}
