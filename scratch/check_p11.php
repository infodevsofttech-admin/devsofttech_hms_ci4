<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4', 3306);
$res = $mysqli->query("SELECT id, p_fname, p_lname, abha_address, abha_id FROM patient_master WHERE id = 11");
$row = $res->fetch_assoc();
print_r($row);
