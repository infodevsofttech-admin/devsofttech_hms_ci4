<?php
$files = glob(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-*.json');
foreach ($files as $f) {
    $json = json_decode(file_get_contents($f), true);
    if (!isset($json['entry'])) continue;
    foreach ($json['entry'] as $e) {
        $res = $e['resource'] ?? [];
        if (($res['resourceType'] ?? '') === 'Organization') {
            echo basename($f) . ":\n";
            echo "  name: " . ($res['name'] ?? '') . "\n";
            echo "  telecom: " . json_encode($res['telecom'] ?? '') . "\n";
            echo "  address: " . json_encode($res['address'] ?? '') . "\n";
            echo "  type: " . json_encode($res['type'] ?? '') . "\n";
            break;
        }
    }
}
