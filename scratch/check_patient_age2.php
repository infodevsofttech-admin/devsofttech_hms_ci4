<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id, ipd_code, p_id FROM ipd_master WHERE ipd_code LIKE '%121%' OR p_id IN (SELECT id FROM patient_master WHERE p_fname LIKE '%JIVAN%')");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);

if (!empty($rows)) {
    $pId = $rows[0]['p_id'];
    $stmt2 = $pdo->query("SELECT * FROM patient_master WHERE id = $pId");
    print_r($stmt2->fetch(PDO::FETCH_ASSOC));
}
