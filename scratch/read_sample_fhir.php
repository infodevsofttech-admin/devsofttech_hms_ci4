<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5e3441ca-83ce-4600-b2b4-b5bba532b999/.system_generated/logs/transcript.jsonl');
for ($i = 2630; $i <= 2640; $i++) {
    if (isset($lines[$i])) {
        $item = json_decode($lines[$i], true);
        echo "=== STEP $i (Type: " . ($item['type']??'') . ") ===\n";
        echo ($item['content'] ?? '') . "\n\n";
    }
}
