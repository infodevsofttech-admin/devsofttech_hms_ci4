
<?php
// Direct database check - no CI4 bootstrap needed
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("ERROR: .env file not found\n");
}

// Manually parse .env because parse_ini_file doesn't handle CI4 format well
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

// Get IPD ID from command line or use default
$ipdId = isset($argv[1]) ? intval($argv[1]) : 1;

echo "=== Checking instruction data for IPD ID: {$ipdId} ===\n\n";

$stmt = $conn->prepare("SELECT id, ipd_id, comp_report, comp_remark, review_after FROM ipd_discharge_instructions WHERE ipd_id = ? LIMIT 1");
$stmt->bind_param("i", $ipdId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "IPD ID: " . $row['ipd_id'] . "\n\n";
    
    echo "=== comp_remark (Discharge Summary field) ===\n";
    echo $row['comp_remark'] . "\n\n";
    
    echo "=== comp_report JSON (contains other_text = Other Advice field) ===\n";
    echo $row['comp_report'] . "\n\n";
    
    if (!empty($row['comp_report'])) {
        $decoded = json_decode($row['comp_report'], true);
        if ($decoded && isset($decoded['other_text'])) {
            echo "=== Extracted other_text (Other Advice) ===\n";
            echo $decoded['other_text'] . "\n\n";
        }
    }
    
    echo "=== Review After ===\n";
    echo ($row['review_after'] ?? '(empty)') . "\n\n";
} else {
    echo "No instruction record found for IPD ID {$ipdId}\n";
}

$stmt->close();
$conn->close();
echo "=== Done ===\n";
