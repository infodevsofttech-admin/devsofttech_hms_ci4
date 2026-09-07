<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript_full.jsonl');
$d = json_decode($lines[563], true);
$content = $d['content'];
$start = strpos($content, '{');
$end = strrpos($content, '}');
if ($start !== false && $end !== false) {
    $jsonStr = substr($content, $start, $end - $start + 1);
    $bundle = json_decode($jsonStr, true);
    if ($bundle && isset($bundle['entry'])) {
        foreach ($bundle['entry'] as $idx => $e) {
            $r = $e['resource'];
            echo "$idx: " . $r['resourceType'] . " (id: " . ($r['id'] ?? '') . ")\n";
            if ($r['resourceType'] === 'Organization') {
                echo "Organization:\n" . json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
            if ($r['resourceType'] === 'Encounter') {
                echo "Encounter:\n" . json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
            if ($r['resourceType'] === 'Immunization') {
                echo "Immunization:\n" . json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
        }
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
}
