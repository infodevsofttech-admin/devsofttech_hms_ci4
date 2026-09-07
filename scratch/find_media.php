<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
foreach ($lines as $i => $l) {
    if (strpos($l, 'media_1788762') !== false) {
        echo "Found at line $i: " . substr($l, 0, 300) . "\n";
    }
}
