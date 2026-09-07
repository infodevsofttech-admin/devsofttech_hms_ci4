<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';



try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

$ipdId = 6;
echo "=== CHECKING MEDICATION TABLES FOR IPD ID $ipdId in DB [$dbname] ===\n";

$tables = [
    'ipd_discharge_prescrption_prescribed',
    'ipd_discharge_prescription_prescribed',
    'ipd_discharge_drug',
    'ipd_discharge_medication',
];

foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT * FROM `$t` LIMIT 10");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Table $t: " . count($rows) . " rows found\n";
        foreach ($rows as $r) {
            print_r($r);
        }
    } catch (Exception $e) {
        echo "Table $t: " . $e->getMessage() . "\n";
    }
}
