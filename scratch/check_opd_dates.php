<?php
$conn = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$res = $conn->query("SELECT opd_id, opd_code, p_id, doc_id, apointment_date, opd_status FROM opd_master ORDER BY opd_id DESC LIMIT 20");
echo "OPD MASTER DATES IN hms_data_ci4:\n";
while ($r = $res->fetch_assoc()) {
    print_r($r);
}
