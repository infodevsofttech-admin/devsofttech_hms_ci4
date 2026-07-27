<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$abha = '91510165305101@sbx';
$res = $db->query("SELECT id, operation, workflow_state, status, http_code, request_id, gateway_request_id, created_at, updated_at, completed_at FROM abdm_hiu_workflows WHERE abha_address = '{$abha}' ORDER BY id ASC");
while ($row = $res->fetch_assoc()) { print_r($row); }
echo "\n=== doc count ===\n";
$res2 = $db->query("SELECT COUNT(*) as c FROM abdm_hiu_documents WHERE abha_address = '{$abha}'");
print_r($res2->fetch_assoc());
$db->close();
