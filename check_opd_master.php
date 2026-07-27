<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SHOW COLUMNS FROM opd_master");
$cols = [];
while ($row = $res->fetch_assoc()) { $cols[] = $row['Field']; }
echo implode(', ', $cols) . "\n---\n";
$res2 = $db->query("SELECT opd_id, opd_code, p_id, P_name, opd_book_date, running_opd, running_opd_id, no_visit FROM opd_master WHERE p_id = 11 ORDER BY opd_id DESC LIMIT 5");
while ($row = $res2->fetch_assoc()) { print_r($row); }
$db->close();
