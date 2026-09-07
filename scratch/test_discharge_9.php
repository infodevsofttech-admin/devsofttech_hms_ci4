<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_ci4_2026';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Find all rows in all tables where id = 9 or ipd_id = 9 or p_id = 9 or opd_id = 9
$tables = $pdo->query("SHOW TABLES LIKE '%ipd%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    try {
        $cols = $pdo->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
        $where = [];
        if (in_array('id', $cols, true)) $where[] = '`id` = 9';
        if (in_array('ipd_id', $cols, true)) $where[] = '`ipd_id` = 9';
        if (in_array('p_id', $cols, true)) $where[] = '`p_id` = 9';
        if (!empty($where)) {
            $stmt = $pdo->query("SELECT * FROM `$t` WHERE " . implode(' OR ', $where));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                echo "=== Table $t has " . count($rows) . " rows for 9 ===\n";
                print_r($rows);
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}
