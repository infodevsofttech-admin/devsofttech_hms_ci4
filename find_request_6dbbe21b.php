<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== Search request_id/gateway_request_id containing 6dbbe21b ===\n";
$res = $db->query("SELECT * FROM abdm_hiu_workflows WHERE request_id LIKE '%6dbbe21b%' OR gateway_request_id LIKE '%6dbbe21b%' OR hms_request_id LIKE '%6dbbe21b%'");
$found = 0;
while ($row = $res->fetch_assoc()) {
    $found++;
    echo "--- id={$row['id']} ---\n";
    foreach ($row as $k => $v) {
        if (in_array($k, ['request_json', 'response_json'])) {
            echo "$k: " . substr((string)$v, 0, 800) . "\n";
        } else {
            echo "$k: $v\n";
        }
    }
    echo "\n";
}
echo "Found: $found\n";

echo "\n=== Count of rows for abha 91510165305101@sbx ===\n";
$res2 = $db->query("SELECT COUNT(*) c, MAX(id) maxid FROM abdm_hiu_workflows WHERE abha_address = '91510165305101@sbx'");
print_r($res2->fetch_assoc());
$db->close();
