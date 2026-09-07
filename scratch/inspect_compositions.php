<?php
$files = glob(__DIR__ . '/../ABDM_FHIR/examples.json/Bundle-*.json');
foreach ($files as $f) {
    $json = json_decode(file_get_contents($f), true);
    if (!isset($json['entry'][0]['resource'])) continue;
    $res = $json['entry'][0]['resource'];
    if ($res['resourceType'] !== 'Composition') continue;
    echo basename($f) . PHP_EOL;
    echo '  title: ' . ($res['title'] ?? 'NONE') . PHP_EOL;
    echo '  type: ' . json_encode($res['type'] ?? 'NONE') . PHP_EOL;
    echo '  category: ' . json_encode($res['category'] ?? 'NONE') . PHP_EOL;
    echo '  encounter: ' . json_encode($res['encounter'] ?? 'NONE') . PHP_EOL;
    echo '  author: ' . json_encode($res['author'] ?? 'NONE') . PHP_EOL;
    echo '  custodian: ' . json_encode($res['custodian'] ?? 'NONE') . PHP_EOL;
    echo '  attester: ' . json_encode($res['attester'] ?? 'NONE') . PHP_EOL;
    
    // Also check all resource types in the bundle
    $types = [];
    foreach ($json['entry'] as $e) {
        $rt = $e['resource']['resourceType'] ?? 'unknown';
        $types[$rt] = ($types[$rt] ?? 0) + 1;
    }
    echo '  resource types: ' . json_encode($types) . PHP_EOL;
    echo PHP_EOL;
}
