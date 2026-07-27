<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$abha = '91510165305101@sbx';

echo "=== Columns ===\n";
$res = $db->query("SHOW COLUMNS FROM abdm_hiu_workflows");
while ($row = $res->fetch_assoc()) { echo $row['Field'] . " (" . $row['Type'] . ")\n"; }

echo "\n=== Latest 20 rows for {$abha} ===\n";
$res2 = $db->query("SELECT * FROM abdm_hiu_workflows WHERE abha_address = '{$abha}' ORDER BY id DESC LIMIT 20");
while ($row = $res2->fetch_assoc()) {
    echo "--- id={$row['id']} ---\n";
    foreach ($row as $k => $v) {
        if (in_array($k, ['request_payload', 'response_payload', 'raw_response'])) {
            echo "$k: " . substr((string)$v, 0, 500) . "\n";
        } else {
            echo "$k: $v\n";
        }
    }
    echo "\n";
}
$db->close();
