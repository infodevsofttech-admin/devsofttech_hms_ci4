<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
$d = json_decode($lines[0], true);
if (preg_match('/\{[\s\S]*"resourceType"\s*:\s*"Bundle"[\s\S]*\}/', $d['content'], $m)) {
    $bundle = json_decode($m[0], true);
    if ($bundle && isset($bundle['entry'])) {
        foreach ($bundle['entry'] as $idx => $e) {
            $r = $e['resource'];
            echo "$idx: " . $r['resourceType'] . " id=" . ($r['id'] ?? '') . "\n";
            if ($r['resourceType'] === 'Immunization') {
                echo "Immunization keys: " . implode(', ', array_keys($r)) . "\n";
                if (isset($r['location'])) {
                    echo "Location: " . json_encode($r['location']) . "\n";
                }
                if (isset($r['encounter'])) {
                    echo "Encounter: " . json_encode($r['encounter']) . "\n";
                }
            }
        }
    }
}
