<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = ['opd_dose_when', 'opd_dose_shed', 'opd_dose_frequency', 'opd_dose_where'];
foreach ($tables as $t) {
    if ($pdo->query("SHOW TABLES LIKE '$t'")->fetch()) {
        $stmt = $pdo->query("SELECT * FROM $t WHERE dose_sign LIKE '%AC%' OR dose_sign_desc LIKE '%MEAL%' OR dose_sign LIKE '%MEAL%'");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "=== Table $t ===\n";
        print_r($rows);
    }
}
