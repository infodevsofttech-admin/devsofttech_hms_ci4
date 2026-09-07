<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$json = file_get_contents('app/Database/master_seed_data.json');
$data = json_decode($json, true);
$seedMeds = $data['tables']['opd_med_master'] ?? $data['opd_med_master'] ?? [];

$inserted = 0;
foreach ($seedMeds as $m) {
    $itemName = trim((string)($m['item_name'] ?? ''));
    if ($itemName === '') continue;

    $stmt = $pdo->prepare("SELECT id FROM opd_med_master WHERE item_name = ?");
    $stmt->execute([$itemName]);
    if (!$stmt->fetch()) {
        $cols = array_keys($m);
        // remove id to avoid collision
        unset($m['id']);
        $fields = array_keys($m);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO opd_med_master (`" . implode('`,`', $fields) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmtIns = $pdo->prepare($sql);
        $stmtIns->execute(array_values($m));
        $inserted++;
        echo "Inserted medicine: $itemName\n";
    }
}

echo "Total inserted into hms_data_ci4.opd_med_master: $inserted\n";
