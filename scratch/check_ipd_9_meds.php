<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM ipd_discharge WHERE ipd_id = 9 OR id = 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM ipd_discharge_1_b WHERE ipd_id = 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM ipd_discharge_1_b_final WHERE ipd_id = 9");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
