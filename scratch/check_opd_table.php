<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT opd_id, opd_code, p_id, doc_id, apointment_date, opd_book_date, opd_status FROM opd_master ORDER BY opd_id DESC LIMIT 10");
echo "\nLAST 10 OPD MASTER ROWS:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

$today = date('Y-m-d');
echo "\nTODAY DATE IS: " . $today . "\n";

foreach (['apointment_date', 'opd_book_date'] as $c) {
    $res = $mysqli->query("SELECT count(*) as count FROM opd_master WHERE DATE({$c}) = '{$today}'");
    $row = $res->fetch_assoc();
    echo "Count for DATE({$c}) = '{$today}': " . $row['count'] . "\n";
}
