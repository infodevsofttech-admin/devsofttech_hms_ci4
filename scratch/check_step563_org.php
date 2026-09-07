<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
$d = json_decode($lines[563], true);
if (preg_match('/\{[\s\S]*"resourceType"\s*:\s*"Bundle"[\s\S]*\}/', $d['content'], $m)) {
    $bundle = json_decode($m[0], true);
    foreach ($bundle['entry'] as $e) {
        if ($e['resource']['resourceType'] === 'Organization') {
            echo "Organization in step 563:\n" . json_encode($e['resource'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
}
