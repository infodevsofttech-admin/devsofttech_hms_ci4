<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== OPD visits for patient_id = 11 ===\n";
$res = $db->query("SHOW TABLES LIKE '%opd%'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
$db->close();
