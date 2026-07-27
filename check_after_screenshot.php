<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
echo "=== Max id now ===\n";
print_r($db->query("SELECT MAX(id) maxid FROM abdm_hiu_workflows")->fetch_assoc());

echo "\n=== rows id > 89 for abha 91510165305101@sbx ===\n";
$res = $db->query("SELECT id, operation, workflow_state, status, http_code, request_id, gateway_request_id, created_at FROM abdm_hiu_workflows WHERE abha_address = '91510165305101@sbx' AND id > 89 ORDER BY id ASC");
while ($row = $res->fetch_assoc()) { print_r($row); }

echo "\n=== search for 6dbbe21b anywhere ===\n";
$res2 = $db->query("SELECT id, operation, request_id, gateway_request_id, created_at FROM abdm_hiu_workflows WHERE request_id LIKE '%6dbbe21b%' OR gateway_request_id LIKE '%6dbbe21b%'");
while ($row = $res2->fetch_assoc()) { print_r($row); }

echo "\n=== doc count for abha now ===\n";
print_r($db->query("SELECT COUNT(*) c FROM abdm_hiu_documents WHERE abha_address = '91510165305101@sbx'")->fetch_assoc());
$db->close();
