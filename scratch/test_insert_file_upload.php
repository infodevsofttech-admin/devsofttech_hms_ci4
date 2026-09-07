<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT id, opd_id, ipd_id, pid, file_name, full_path, document_type, upload_by FROM file_upload_data WHERE opd_id > 0 OR ipd_id > 0 ORDER BY id DESC LIMIT 5");
echo "OPD/IPD FILES IN file_upload_data:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
