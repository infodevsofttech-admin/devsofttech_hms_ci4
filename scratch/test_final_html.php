<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch medicines for IPD 9
$stmt = $pdo->query("SELECT * FROM ipd_discharge_prescrption_prescribed WHERE ipd_id = 9");
$medRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== MED ROWS FOR IPD 9 ===\n";
print_r($medRows);

// Test resolution
foreach ($medRows as $med) {
    $rawName = trim((string)($med['med_name'] ?? ''));
    $generic = trim((string)($med['med_salt'] ?? ''));

    if ($generic === '') {
        $cleanMedName = trim((string) preg_replace('/^(TAB|CAP|SYP|INJ|CREAM|OINT|GEL|DROPS|SPRAY)\s+/i', '', $rawName));
        if ($cleanMedName !== '') {
            $stmt = $pdo->prepare("SELECT genericname, salt_name FROM opd_med_master WHERE item_name = ? OR item_name LIKE ? OR ? LIKE CONCAT(item_name, '%') LIMIT 1");
            $stmt->execute([$cleanMedName, $cleanMedName . '%', $cleanMedName]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $generic = trim((string)(!empty($row['genericname']) ? $row['genericname'] : ($row['salt_name'] ?? '')));
            }

            if ($generic === '') {
                $words = preg_split('/[\s\-_]+/', $cleanMedName);
                if (is_array($words)) {
                    foreach ($words as $w) {
                        if (strlen($w) >= 4) {
                            $stmt = $pdo->prepare("SELECT genericname, salt_name FROM opd_med_master WHERE item_name LIKE ? OR genericname LIKE ? OR salt_name LIKE ? LIMIT 1");
                            $stmt->execute([$w . '%', $w . '%', $w . '%']);
                            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $generic = trim((string)(!empty($row['genericname']) ? $row['genericname'] : ($row['salt_name'] ?? '')));
                                if ($generic !== '') break;
                            }
                        }
                    }
                }
            }
        }
    }

    echo "Medicine: $rawName => Generic/Salt: $generic\n";
}
