<?php

$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$res = $mysqli->query("SELECT * FROM ipd_master WHERE id = 9");
echo "=== ipd_master row 9 ===\n";
print_r($res->fetch_assoc());

$res = $mysqli->query("SELECT * FROM ipd_discharge WHERE ipd_id = 9");
echo "=== ipd_discharge row for ipd 9 ===\n";
print_r($res->fetch_assoc());

$res = $mysqli->query("SELECT * FROM ipd_discharg_status");
echo "=== ipd_discharg_status rows ===\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
