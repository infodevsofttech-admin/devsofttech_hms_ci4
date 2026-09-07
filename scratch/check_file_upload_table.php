<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("SHOW COLUMNS FROM file_upload_data");
$cols = [];
while ($row = $res->fetch_assoc()) {
    $cols[] = $row['Field'] . ' (' . $row['Type'] . ')';
}
echo "FILE_UPLOAD_DATA COLUMNS:\n";
print_r($cols);

$res = $mysqli->query("SELECT * FROM file_upload_data ORDER BY id DESC LIMIT 5");
echo "\nLAST 5 ROWS IN FILE_UPLOAD_DATA:\n";
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
