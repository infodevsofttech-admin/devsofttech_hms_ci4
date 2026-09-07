<?php

$json = file_get_contents('app/Database/master_seed_data.json');
$data = json_decode($json, true);
$meds = $data['tables']['opd_med_master'] ?? $data['opd_med_master'] ?? [];
echo "Count of opd_med_master in seed data: " . count($meds) . "\n";

$found = 0;
foreach ($meds as $m) {
    if (stripos($m['item_name'] ?? '', 'pant') !== false || stripos($m['genericname'] ?? '', 'pant') !== false || stripos($m['item_name'] ?? '', 'aciloc') !== false) {
        print_r($m);
        $found++;
        if ($found >= 5) break;
    }
}
