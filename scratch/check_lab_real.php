<?php
$d = json_decode(file_get_contents('writable/tmp/abdm_lab_bundle_real_sample.json'), true);
echo "Composition:\n";
print_r($d['entry'][0]['resource']);
foreach ($d['entry'] as $i => $e) {
    echo "$i: " . $e['resource']['resourceType'] . "\n";
}
