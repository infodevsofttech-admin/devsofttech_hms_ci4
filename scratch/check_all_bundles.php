<?php
$dir = 'ABDM_FHIR/examples.json/';
$files = glob($dir . 'Bundle-*.json');
foreach ($files as $file) {
    $b = json_decode(file_get_contents($file), true);
    if (!isset($b['entry'][0]['resource']['resourceType'])) continue;
    $comp = $b['entry'][0]['resource'];
    echo basename($file) . ":\n";
    echo "  title: " . ($comp['title'] ?? 'NONE') . "\n";
    echo "  type: " . ($comp['type']['text'] ?? $comp['type']['coding'][0]['display'] ?? 'NONE') . "\n";
    if (isset($comp['category'])) {
        echo "  category: " . json_encode($comp['category']) . "\n";
    }
    if (isset($comp['encounter'])) {
        echo "  encounter: " . json_encode($comp['encounter']) . "\n";
    }
    if (isset($comp['custodian'])) {
        echo "  custodian: " . json_encode($comp['custodian']) . "\n";
    }
    if (isset($comp['author'])) {
        echo "  author: " . json_encode($comp['author']) . "\n";
    }
}
