<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query('SHOW FULL COLUMNS FROM mst_stores');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . "\n";
}
$db->close();
