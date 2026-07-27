<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== abdm_hiu_documents columns ===\n";
$res = $db->query("SHOW COLUMNS FROM abdm_hiu_documents");
while ($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }

echo "\n=== rows for abha 91510165305101@sbx ===\n";
$res2 = $db->query("SELECT id, workflow_id, request_id, transaction_id, consent_ref, abha_address, patient_id, patient_name, care_context_reference, document_title, document_date, created_at, updated_at FROM abdm_hiu_documents WHERE abha_address = '91510165305101@sbx'");
while ($row = $res2->fetch_assoc()) { print_r($row); }

echo "\n=== total row count in table ===\n";
$res3 = $db->query("SELECT COUNT(*) c FROM abdm_hiu_documents");
print_r($res3->fetch_assoc());
$db->close();
