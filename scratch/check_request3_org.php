<?php
// Extract bundle from transcript step 563
$file = 'C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript_full.jsonl';
$fh = fopen($file, 'r');
$i = 0;
$userJson = '';
while (($line = fgets($fh)) !== false) {
    if ($i === 563) {
        $d = json_decode($line, true);
        $userJson = $d['content'] ?? '';
        break;
    }
    $i++;
}
fclose($fh);

if (preg_match('/\{[\s\S]*"resourceType"\s*:\s*"Bundle"[\s\S]*\}/', $userJson, $m)) {
    $bundle = json_decode($m[0], true);
    foreach ($bundle['entry'] as $e) {
        $r = $e['resource'];
        echo "Resource: " . $r['resourceType'] . " (id: " . ($r['id'] ?? '') . ")\n";
        if ($r['resourceType'] === 'Organization') {
            echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
        }
        if ($r['resourceType'] === 'Composition') {
            echo "Composition:\n";
            echo "  title: " . ($r['title'] ?? '') . "\n";
            echo "  type: " . json_encode($r['type'] ?? '') . "\n";
            echo "  category: " . json_encode($r['category'] ?? '') . "\n";
            echo "  encounter: " . json_encode($r['encounter'] ?? '') . "\n";
            echo "  custodian: " . json_encode($r['custodian'] ?? '') . "\n";
            echo "  author: " . json_encode($r['author'] ?? '') . "\n";
        }
        if ($r['resourceType'] === 'Encounter') {
            echo "Encounter:\n";
            echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
        }
    }
}
