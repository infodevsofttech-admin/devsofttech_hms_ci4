<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM ipd_discharge_prescrption_prescribed WHERE ipd_id = 9");
$meds = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($meds) . " meds\n";
