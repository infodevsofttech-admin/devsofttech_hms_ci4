<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
for ($i = 560; $i < count($lines); $i++) {
    $d = json_decode($lines[$i], true);
    $type = $d['type'] ?? '';
    if ($type === 'USER_INPUT') {
        echo "\n=== STEP $i (USER_INPUT) ===\n";
        echo $d['content'] . "\n";
    } elseif ($type === 'PLANNER_RESPONSE') {
        // echo first 300 chars of planner response or tool calls
        $c = $d['content'] ?? '';
        echo "Step $i (PLANNER_RESPONSE): " . substr($c, 0, 150) . "...\n";
    }
}
