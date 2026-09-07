<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');

if ($mysqli->connect_error) {
    die("Connect Error: " . $mysqli->connect_error);
}

echo "=== patient_doc with ID 5 ===\n";
$res = $mysqli->query("SELECT * FROM patient_doc WHERE id = 5");
if ($row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "No row with id=5 in patient_doc\n";
}

echo "\n=== recent patient_doc rows ===\n";
$res = $mysqli->query("SELECT id, p_id, dr_id, doc_format_id, created_at FROM patient_doc ORDER BY id DESC LIMIT 5");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n=== file_upload_data with ID 5 ===\n";
$res = $mysqli->query("SELECT * FROM file_upload_data WHERE id = 5");
if ($row = $res->fetch_assoc()) {
    print_r($row);
} else {
    echo "No row with id=5 in file_upload_data\n";
}

echo "\n=== recent file_upload_data rows ===\n";
$res = $mysqli->query("SELECT id, pid, insert_date FROM file_upload_data ORDER BY id DESC LIMIT 5");
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "\n=== abdm_work_tasks with ID 585 ===\n";
$res = $mysqli->query("SELECT * FROM abdm_work_tasks WHERE id = 585");
if ($row = $res->fetch_assoc()) {
    print_r($row);
}
