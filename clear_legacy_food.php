<?php
// Clear corrupt legacy food_text for IPD ID
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

echo "=== Clearing corrupt legacy food_text for IPD ID: {$ipdId} ===\n\n";

// Check if table and column exist
$tableCheck = $conn->query("SHOW TABLES LIKE 'ipd_discharge_drug_food_interaction'");
if ($tableCheck->num_rows === 0) {
    echo "Table does not exist, nothing to clean\n";
    $conn->close();
    exit(0);
}

$colCheck = $conn->query("SHOW COLUMNS FROM ipd_discharge_drug_food_interaction LIKE 'food_text'");
if ($colCheck->num_rows === 0) {
    echo "Column food_text does not exist, nothing to clean\n";
    $conn->close();
    exit(0);
}

// Clear the food_text
$stmt = $conn->prepare("UPDATE ipd_discharge_drug_food_interaction SET food_text = '' WHERE ipd_id = ?");
$stmt->bind_param("i", $ipdId);

if ($stmt->execute()) {
    echo "✅ Cleared food_text for IPD ID {$ipdId}\n";
    echo "Affected rows: " . $stmt->affected_rows . "\n";
} else {
    echo "❌ Failed to clear: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
echo "\n=== Done ===\n";
