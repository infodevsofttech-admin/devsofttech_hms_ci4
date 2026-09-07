<?php
$working = json_decode(file_get_contents('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5e3441ca-83ce-4600-b2b4-b5bba532b999/.user_uploaded/media_1788555350253.txt'), true);
$our = json_decode(file_get_contents('d:/Workplace/HMS_CI4_OLD/writable/ipd9_bundle.json'), true);

echo "Working Bundle ID: " . ($working['id'] ?? '') . "\n";
echo "Our Bundle ID: " . ($our['id'] ?? '') . "\n";

echo "\nWorking Entries:\n";
foreach ($working['entry'] as $idx => $e) {
    $r = $e['resource'];
    echo sprintf("[%02d] %-25s fullUrl: %s\n", $idx, $r['resourceType'], $e['fullUrl']);
}

echo "\nOur Entries:\n";
foreach ($our['entry'] as $idx => $e) {
    $r = $e['resource'];
    echo sprintf("[%02d] %-25s fullUrl: %s\n", $idx, $r['resourceType'], $e['fullUrl']);
}
