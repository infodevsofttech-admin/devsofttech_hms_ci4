<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5e3441ca-83ce-4600-b2b4-b5bba532b999/.system_generated/logs/transcript_full.jsonl');
for ($i = 2587; $i <= 2600; $i++) {
    if (isset($lines[$i])) {
        $item = json_decode($lines[$i], true);
        echo "=== STEP $i (Type: " . ($item['type']??'') . ", Tool: " . json_encode($item['tool_calls'] ?? []) . ") ===\n";
        if (!empty($item['thinking'])) {
            echo "Thinking: " . substr($item['thinking'], 0, 400) . "\n";
        }
        if (!empty($item['content'])) {
            echo "Content: " . substr($item['content'], 0, 400) . "\n";
        }
        echo "\n";
    }
}
