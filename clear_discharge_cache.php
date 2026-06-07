<?php
// Clear discharge cache to force regeneration
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

echo "=== Clearing discharge cache for IPD ID: {$ipdId} ===\n\n";

$stmt = $conn->prepare("DELETE FROM ipd_discharge WHERE ipd_id = ?");
$stmt->bind_param("i", $ipdId);

if ($stmt->execute()) {
    echo "✅ Cleared cached content for IPD ID {$ipdId}\n";
    echo "Affected rows: " . $stmt->affected_rows . "\n\n";
    echo "Now visit the discharge preview page to regenerate content with the new 'Other Advice:' label.\n";
    echo "URL: /Ipd_discharge/preview_discharge_report/{$ipdId}?regen=1\n";
} else {
    echo "❌ Failed to clear: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
echo "\n=== Done ===\n";
