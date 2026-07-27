<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$abha = '91510165305101@sbx';
$res = $db->query("SELECT id, document_title, document_date, care_context_reference, created_at FROM abdm_hiu_documents WHERE abha_address = '{$abha}' ORDER BY id DESC LIMIT 5");
while ($row = $res->fetch_assoc()) { print_r($row); }
$db->close();
