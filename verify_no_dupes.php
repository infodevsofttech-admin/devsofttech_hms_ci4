<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
print_r($db->query("SELECT COUNT(*) c FROM abdm_hiu_documents WHERE abha_address = '91510165305101@sbx'")->fetch_assoc());
$res = $db->query("SELECT id, request_id, care_context_reference, updated_at FROM abdm_hiu_documents WHERE abha_address = '91510165305101@sbx'");
while ($row = $res->fetch_assoc()) { print_r($row); }
$db->close();
