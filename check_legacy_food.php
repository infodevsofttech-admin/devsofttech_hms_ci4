<?php
// Check if legacy food_text is interfering
$envFile = __DIR__ . '/.env';
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'hms_data_ci4';

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    if (strpos($line, 'database.default.hostname') !== false) {
        $dbHost = trim(explode('=', $line, 2)[1] ?? 'localhost');
    } elseif (strpos($line, 'database.default.username') !== false) {
        $dbUser = trim(explode('=', $line, 2)[1] ?? 'root');
    } elseif (strpos($line, 'database.default.password') !== false) {
        $dbPass = trim(explode('=', $line, 2)[1] ?? '');
    } elseif (strpos($line, 'database.default.database') !== false) {
        $dbName = trim(explode('=', $line, 2)[1] ?? 'hms_data_ci4');
    }
}

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("ERROR: Connection failed: " . $conn->connect_error . "\n");
}

$ipdId = isset($argv[1]) ? intval($argv[1]) : 1;

echo "=== Checking LEGACY ipd_discharge_drug_food_interaction for IPD ID: {$ipdId} ===\n\n";

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'ipd_discharge_drug_food_interaction'");
if ($tableCheck->num_rows === 0) {
    echo "Table ipd_discharge_drug_food_interaction does NOT exist\n";
    $conn->close();
    exit(0);
}

echo "Table exists. Checking for food_text column...\n";
$colCheck = $conn->query("SHOW COLUMNS FROM ipd_discharge_drug_food_interaction LIKE 'food_text'");
if ($colCheck->num_rows === 0) {
    echo "Column food_text does NOT exist\n";
} else {
    echo "Column food_text EXISTS\n\n";
    
    $stmt = $conn->prepare("SELECT id, ipd_id, food_id_list, food_text FROM ipd_discharge_drug_food_interaction WHERE ipd_id = ? LIMIT 1");
    $stmt->bind_param("i", $ipdId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "=== LEGACY RECORD FOUND ===\n";
        echo "ID: " . $row['id'] . "\n";
        echo "IPD ID: " . $row['ipd_id'] . "\n";
        echo "food_id_list: " . ($row['food_id_list'] ?? '(null)') . "\n";
        echo "food_text: " . ($row['food_text'] ?? '(null)') . "\n\n";
        
        if (!empty($row['food_text'])) {
            echo "⚠️ WARNING: Legacy food_text has content! This might override instruction_other\n";
        }
    } else {
        echo "No legacy record found for IPD ID {$ipdId}\n";
    }
    $stmt->close();
}

$conn->close();
echo "\n=== Done ===\n";
