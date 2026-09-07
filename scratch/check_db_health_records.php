<?php
$db = \Config\Database::connect();
if ($db->tableExists('health_records')) {
    $rows = $db->table('health_records')
        ->where('entity_type', 'ipd')
        ->orderBy('id', 'DESC')
        ->limit(5)
        ->get()
        ->getResultArray();
    echo "Found " . count($rows) . " health_records for IPD:\n";
    foreach ($rows as $r) {
        echo "ID: " . $r['id'] . " | Entity ID: " . $r['entity_id'] . " | Care Context: " . ($r['care_context_reference'] ?? '') . " | Status: " . ($r['push_status'] ?? '') . " | Updated: " . ($r['updated_at'] ?? '') . "\n";
        $data = $r['record_data'] ?? '';
        if ($data !== '') {
            $bundle = json_decode($data, true);
            echo "   Bundle ID in DB: " . ($bundle['id'] ?? 'unknown') . " | Entry count: " . count($bundle['entry'] ?? []) . "\n";
        }
    }
} else {
    echo "health_records table does not exist.\n";
}
