<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
$line0 = json_decode($lines[0], true);
echo "Media paths in line 0:\n";
print_r($line0['media_paths'] ?? []);
print_r($line0['tool_calls'] ?? []);
