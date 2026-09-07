<?php

$content = file_get_contents('app/Database/master_seed_data.json');
preg_match_all('/"resourceType":"MedicationRequest",.*?"id":"[^"]+"/', $content, $matches);
echo "Found matches: " . count($matches[0]) . "\n";

// Find lines containing MedicationRequest in master_seed_data.json
$handle = fopen('app/Database/master_seed_data.json', 'r');
$count = 0;
while (($line = fgets($handle)) !== false) {
    if (strpos($line, 'MedicationRequest') !== false && strpos($line, 'bundle_json') !== false) {
        // extract bundle_json
        if (preg_match('/"bundle_json":\s*"(.*?)"\s*,\s*"created_at"/s', $line, $m)) {
            $bundleJson = stripslashes($m[1]);
            $bundle = json_decode($bundleJson, true);
            if ($bundle && isset($bundle['entry'])) {
                foreach ($bundle['entry'] as $e) {
                    if (($e['resource']['resourceType'] ?? '') === 'MedicationRequest') {
                        echo "Sample MedicationRequest:\n";
                        echo json_encode($e['resource'], JSON_PRETTY_PRINT) . "\n\n";
                        $count++;
                        if ($count >= 3) break 2;
                    }
                }
            }
        }
    }
}
fclose($handle);
