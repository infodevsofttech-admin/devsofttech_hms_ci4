<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SHOW COLUMNS FROM opd_visit_list");
while ($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }
echo "---\n";
$res2 = $db->query("SELECT * FROM opd_visit_list WHERE p_id = 11 ORDER BY id DESC LIMIT 5");
while ($row = $res2->fetch_assoc()) { print_r($row); }
$db->close();
