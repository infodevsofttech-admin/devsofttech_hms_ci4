<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
$res = $mysqli->query("SELECT * FROM immunization_records WHERE id = 10");
$row = $res->fetch_assoc();
print_r($row);
