
<?php
// List all IPD records with instruction data
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbHost = $_ENV['database.default.hostname'] ?? 'localhost';
$dbUser = $_ENV['database.default.username'] ?? 'root';
$dbPass = $_ENV['database.default.password'] ?? '';
$dbName = $_ENV['database.default.database'] ?? 'hms_data_ci4';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("ERROR: Connection failed: " . $conn->connect_error . "\n");
}

echo "=== Recent IPD instruction records ===\n\n";

$sql = "SELECT id, ipd_id, LEFT(comp_remark, 100) as comp_remark_preview, 
        LEFT(comp_report, 100) as comp_report_preview, 
        review_after, update_date 
        FROM ipd_discharge_instructions 
        ORDER BY id DESC 
        LIMIT 20";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | IPD_ID: {$row['ipd_id']} | Updated: {$row['update_date']}\n";
        echo "  comp_remark (Discharge Summary): " . substr(strip_tags($row['comp_remark_preview']), 0, 50) . "...\n";
        echo "  comp_report (JSON with other_text): " . substr($row['comp_report_preview'], 0, 50) . "...\n\n";
    }
} else {
    echo "No instruction records found\n";
}

$conn->close();
