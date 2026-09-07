<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = ['opd_med_master', 'med_product_master', 'med_genric_name', 'inv_med_item'];
foreach ($tables as $t) {
    try {
        echo "=== Table $t ===\n";
        $stmt = $pdo->query("SELECT * FROM `$t` LIMIT 5");
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
}
