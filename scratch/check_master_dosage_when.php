<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id, item_name, dosage, dosage_when, dosage_freq, remark FROM opd_med_master LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
