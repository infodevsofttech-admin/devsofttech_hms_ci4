<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT im.id as ipd_id, im.ipd_code, im.p_id, p.* FROM ipd_master im LEFT JOIN patient_master p ON p.id = im.p_id WHERE im.ipd_code = 'A26090000121'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($row);
