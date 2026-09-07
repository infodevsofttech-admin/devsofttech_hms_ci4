<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5e3441ca-83ce-4600-b2b4-b5bba532b999/.system_generated/logs/transcript.jsonl');
for ($i = 2580; $i <= 2610; $i++) {
    if (isset($lines[$i])) {
        $item = json_decode($lines[$i], true);
        if (($item['type']??'') === 'USER_INPUT' || ($item['type']??'') === 'PLANNER_RESPONSE') {
            echo "=== STEP $i (Type: " . ($item['type']??'') . ") ===\n";
            echo substr($item['content'] ?? '', 0, 500) . "\n\n";
        }
    }
}
