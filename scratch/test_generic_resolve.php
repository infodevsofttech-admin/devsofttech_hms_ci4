<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function resolveGenericName($pdo, $rawName, $medId = 0) {
    if ($medId > 0) {
        $stmt = $pdo->prepare("SELECT genericname, salt_name FROM opd_med_master WHERE id = ? LIMIT 1");
        $stmt->execute([$medId]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $gen = trim((string)($row['genericname'] ?: $row['salt_name']));
            if ($gen !== '') return $gen;
        }
    }

    $clean = trim(preg_replace('/^(TAB|CAP|SYP|INJ|CREAM|OINT|GEL|DROPS|SPRAY)\s+/i', '', $rawName));
    if ($clean === '') return '';

    // 1. Direct match on item_name in opd_med_master
    $stmt = $pdo->prepare("SELECT genericname, salt_name FROM opd_med_master WHERE item_name = ? OR item_name LIKE ? OR ? LIKE CONCAT(item_name, '%') LIMIT 1");
    $stmt->execute([$clean, $clean . '%', $clean]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $gen = trim((string)($row['genericname'] ?: $row['salt_name']));
        if ($gen !== '') return $gen;
    }

    // 2. Token match (e.g. "PANTOP" or "PANTOPRAZOLE" from "CAP PANTOP DSR")
    $words = preg_split('/[\s\-_]+/', $clean);
    foreach ($words as $w) {
        if (strlen($w) >= 4) {
            $stmt = $pdo->prepare("SELECT genericname, salt_name FROM opd_med_master WHERE item_name LIKE ? OR genericname LIKE ? OR salt_name LIKE ? LIMIT 1");
            $stmt->execute([$w . '%', $w . '%', $w . '%']);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $gen = trim((string)($row['genericname'] ?: $row['salt_name']));
                if ($gen !== '') return $gen;
            }
        }
    }

    return '';
}

echo "ACILOC => " . resolveGenericName($pdo, 'ACILOC') . "\n";
echo "CAP PANTOP DSR => " . resolveGenericName($pdo, 'CAP PANTOP DSR') . "\n";
echo "TAB ACILOC 150 => " . resolveGenericName($pdo, 'TAB ACILOC 150') . "\n";
echo "AZITHROMYCIN 500 => " . resolveGenericName($pdo, 'AZITHROMYCIN 500') . "\n";
