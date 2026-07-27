<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== Rows 80-89 for abha 91510165305101@sbx ===\n";
$res = $db->query("SELECT * FROM abdm_hiu_workflows WHERE abha_address = '91510165305101@sbx' AND id >= 80 ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    echo "--- id={$row['id']} ---\n";
    foreach ($row as $k => $v) {
        if (in_array($k, ['request_json', 'response_json'])) {
            echo "$k: " . substr((string)$v, 0, 900) . "\n";
        } else {
            echo "$k: $v\n";
        }
    }
    echo "\n";
}
$db->close();
