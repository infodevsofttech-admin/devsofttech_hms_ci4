<?php
$file = 'C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript_full.jsonl';
$fh = fopen($file, 'r');
$i = 0;
$userJson = '';
while (($line = fgets($fh)) !== false) {
    if ($i === 0) {
        $d = json_decode($line, true);
        $userJson = $d['content'] ?? '';
        break;
    }
    $i++;
}
fclose($fh);

if (preg_match('/\{[\s\S]*"resourceType"\s*:\s*"Bundle"[\s\S]*\}/', $userJson, $m)) {
    $bundle = json_decode($m[0], true);
    echo "Bundle ID: " . ($bundle['id'] ?? '') . "\n";
    foreach ($bundle['entry'] as $e) {
        $r = $e['resource'];
        echo "Resource: " . $r['resourceType'] . " (id: " . ($r['id'] ?? '') . ")\n";
        if ($r['resourceType'] === 'Composition') {
            echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
        }
        if ($r['resourceType'] === 'Organization') {
            echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
        }
        if ($r['resourceType'] === 'Practitioner') {
            echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
        }
    }
}
