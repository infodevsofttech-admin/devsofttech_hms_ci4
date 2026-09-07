<?php
$b = json_decode(file_get_contents('writable/ipd9_bundle.json'), true);
echo "Bundle ID: " . $b['id'] . "\n";
foreach ($b['entry'] as $i => $e) {
    $r = $e['resource'];
    echo "[$i] " . $r['resourceType'] . " (id=" . $r['id'] . ")\n";
    echo "    Keys: " . implode(', ', array_keys($r)) . "\n";
    if ($r['resourceType'] === 'Composition') {
        echo "    Sections:\n";
        foreach ($r['section'] as $s) {
            $code = $s['code']['coding'][0]['code'] ?? '';
            $title = $s['title'] ?? '';
            $hasText = isset($s['text']) ? 'YES' : 'NO';
            $entries = array_map(function($en) { return $en['reference']; }, $s['entry'] ?? []);
            echo "      - [$code] $title (text: $hasText) -> " . implode(', ', $entries) . "\n";
        }
    }
}
