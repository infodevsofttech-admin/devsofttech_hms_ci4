<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id, ipd_code, p_id FROM ipd_master ORDER BY id DESC LIMIT 15");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT DISTINCT ipd_id FROM ipd_discharge_prescrption_prescribed");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM ipd_discharge_prescrption_prescribed LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
