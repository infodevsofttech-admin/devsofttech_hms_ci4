<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== Patients with non-empty abha_address (excluding the known test patient id=15) ===\n";
$sql = "SELECT id, p_fname, p_lname, abha_address
        FROM patient_master
        WHERE abha_address IS NOT NULL AND abha_address != '' AND id != 15
        ORDER BY id DESC
        LIMIT 20";
$res = $db->query($sql);
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "\n=== hospital_setting HFR-related keys ===\n";
$res2 = $db->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name LIKE '%HFR%' OR s_name LIKE '%ABDM%'");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}

$db->close();
