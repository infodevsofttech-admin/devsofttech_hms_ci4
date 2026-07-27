<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$abha = 'meerabisht1981@sbx';
echo "=== Distinct operations ever seen for {$abha} ===\n";
$res = $db->query("SELECT operation, COUNT(*) c FROM abdm_hiu_workflows WHERE abha_address = '{$abha}' GROUP BY operation");
while ($row = $res->fetch_assoc()) { print_r($row); }

echo "\n=== Compare: patient 11 ABHA operations (for reference) ===\n";
$res2 = $db->query("SELECT operation, COUNT(*) c FROM abdm_hiu_workflows WHERE abha_address = '91510165305101@sbx' GROUP BY operation");
while ($row = $res2->fetch_assoc()) { print_r($row); }
$db->close();
