<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
$res = $mysqli->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name LIKE '%H_%' OR s_name LIKE '%ABDM%' OR s_name LIKE '%addr%' OR s_name LIKE '%phone%'");
while ($row = $res->fetch_assoc()) {
    echo $row['s_name'] . ' = ' . $row['s_value'] . PHP_EOL;
}
