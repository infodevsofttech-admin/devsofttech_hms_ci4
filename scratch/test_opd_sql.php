<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "SELECT o.opd_id, o.opd_code, o.opd_no, o.p_id, o.doc_id, o.apointment_date, o.opd_status, coalesce(o.opd_fee_type, 'Cash') as opd_type,
               p.p_fname as fname, p.p_rname as rname, p.p_code as uhid, p.mphone1, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob,
               concat('Dr. ', d.p_fname, ' ', coalesce(d.p_lname, '')) as doctor_name,
               pr.temp, pr.pulse, pr.bp, pr.diastolic, pr.spo2, pr.weight, pr.height, pr.rr_min
        FROM opd_master o
        JOIN patient_master p ON o.p_id = p.id
        LEFT JOIN doctor_master d ON o.doc_id = d.id
        LEFT JOIN opd_prescription pr ON o.opd_id = pr.opd_id
        ORDER BY o.opd_id DESC";

$res = $mysqli->query($sql);
echo "TOTAL OPD RECORDS FETCHED: " . $res->num_rows . "\n";
while ($row = $res->fetch_assoc()) {
    echo "OPD #" . $row['opd_id'] . " | " . $row['opd_code'] . " | " . $row['fname'] . " | " . $row['doctor_name'] . " | " . $row['apointment_date'] . "\n";
}
