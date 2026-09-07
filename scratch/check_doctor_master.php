<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
$res = $mysqli->query("SELECT * FROM doctor_master LIMIT 5");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
