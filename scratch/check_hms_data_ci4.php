<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

$pdo1 = new PDO("mysql:host=$host;dbname=hms_ci4_2026;charset=utf8mb4", $user, $pass);
echo "Count in hms_ci4_2026 opd_med_master: " . $pdo1->query("SELECT COUNT(*) FROM opd_med_master")->fetchColumn() . "\n";

$stmt = $pdo1->query("SELECT id, item_name, genericname, salt_name FROM opd_med_master WHERE item_name LIKE '%PANTOP%' LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
