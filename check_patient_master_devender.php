<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== patient_master rows matching Devender Singh or abha 91510165305101@sbx ===\n";
$res = $db->query("SELECT id, p_fname, p_lname FROM patient_master WHERE p_fname LIKE '%Devender%' OR p_fname LIKE '%devender%'");
while ($row = $res->fetch_assoc()) { print_r($row); }

echo "\n=== Search abha columns (guess col names) ===\n";
$res2 = $db->query("SHOW COLUMNS FROM patient_master LIKE '%abha%'");
while ($row = $res2->fetch_assoc()) { echo $row['Field'] . "\n"; }
$db->close();
