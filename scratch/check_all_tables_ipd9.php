<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = ['ipd_discharge_prescrption_prescribed', 'ipd_discharge_drug', 'ipd_discharge', 'ipd_discharge_instructions'];
foreach ($tables as $t) {
    if ($pdo->query("SHOW TABLES LIKE '$t'")->fetch()) {
        $stmt = $pdo->query("SELECT * FROM $t WHERE ipd_id = 9");
        echo "=== Table $t ===\n";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
