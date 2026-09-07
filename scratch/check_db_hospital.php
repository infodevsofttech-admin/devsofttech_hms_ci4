<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$res = $mysqli->query("SELECT s_name, s_value FROM hospital_setting");
while ($row = $res->fetch_assoc()) {
    echo $row['s_name'] . ' = ' . $row['s_value'] . PHP_EOL;
}
