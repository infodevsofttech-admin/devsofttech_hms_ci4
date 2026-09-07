<?php
$file = 'C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl';
$fh = fopen($file, 'r');
$i = 0;
while (($line = fgets($fh)) !== false) {
    $d = json_decode($line, true);
    if (($d['type'] ?? '') === 'USER_INPUT' && $i < 300) {
        echo "=== User Input at step $i ===\n";
        echo substr($d['content'] ?? '', 0, 1000) . "\n\n";
    }
    $i++;
}
fclose($fh);
