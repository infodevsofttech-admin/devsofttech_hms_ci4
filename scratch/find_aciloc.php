<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Find all tables that have a row with 'ACILOC' in any column
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    try {
        $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
        $where = [];
        foreach ($cols as $c) {
            $where[] = "`$c` LIKE '%ACILOC%'";
        }
        $sql = "SELECT * FROM `$t` WHERE " . implode(' OR ', $where);
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            echo "=== FOUND IN TABLE $t (" . count($rows) . " rows) ===\n";
            print_r($rows);
        }
    } catch (Exception $e) {
        // ignore
    }
}
