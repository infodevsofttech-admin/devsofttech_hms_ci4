<?php
$lines = file('C:/Users/d_s_b/.gemini/antigravity-ide/brain/5d74b30a-f6db-45f5-988c-4e3f71d58774/.system_generated/logs/transcript.jsonl');
$d = json_decode($lines[0], true);
preg_match('/"resourceType": "Immunization".*?\}/s', $d['content'], $m);
if ($m) {
    echo $m[0] . "\n";
} else {
    echo "Not found in regex, showing lines with location or Immunization:\n";
    foreach (explode("\n", $d['content']) as $l) {
        if (stripos($l, 'location') !== false || stripos($l, 'resourceType') !== false) {
            echo $l . "\n";
        }
    }
}
