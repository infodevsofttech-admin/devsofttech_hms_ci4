<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
foreach ($lines as $i => $line) {
    $data = json_decode($line, true);
    if (($data['type'] ?? '') === 'USER_INPUT') {
        echo "Line $i: " . substr(json_encode($data['content'] ?? ''), 0, 200) . "\n";
    }
}
