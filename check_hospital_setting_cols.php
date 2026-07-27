<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$cols = $db->query('SHOW COLUMNS FROM hospital_setting');
$colNames = [];
while ($c = $cols->fetch_assoc()) {
    $colNames[] = $c['Field'];
}
echo "Columns: " . implode(', ', $colNames) . "\n";
$rows = $db->query('SELECT * FROM hospital_setting LIMIT 5');
while ($r = $rows->fetch_assoc()) {
    print_r($r);
}
$db->close();
