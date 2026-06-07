
<?php
// Debug script to check what's in the generated discharge content
require 'vendor/autoload.php';

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

echo "=== Checking discharge content for IPD ID: {$ipdId} ===\n\n";

// Check if cached content exists
$stmt = $conn->prepare("SELECT content FROM ipd_discharge WHERE ipd_id = ? LIMIT 1");
$stmt->bind_param("i", $ipdId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $content = $row['content'];
    echo "=== CACHED CONTENT FOUND ===\n\n";
    
    // Check for signature block
    if (stripos($content, 'Signature of Consultant') !== false) {
        echo "✅ 'Signature of Consultant' text FOUND in content\n";
    } else {
        echo "❌ 'Signature of Consultant' text NOT FOUND in content\n";
    }
    
    // Check for Other Advice
    if (stripos($content, 'Other Advice') !== false) {
        echo "✅ 'Other Advice' text FOUND in content\n";
    } else {
        echo "❌ 'Other Advice' text NOT FOUND in content\n";
    }
    
    // Check for Discharge Summary
    if (stripos($content, 'Discharge Summary:') !== false) {
        echo "✅ 'Discharge Summary:' label FOUND in content\n";
    } else {
        echo "❌ 'Discharge Summary:' label NOT FOUND in content\n";
    }
    
    echo "\n=== CONTENT PREVIEW (first 2000 chars) ===\n";
    echo substr($content, 0, 2000) . "\n...\n\n";
    
    echo "=== CONTENT PREVIEW (last 1000 chars) ===\n";
    echo "..." . substr($content, -1000) . "\n\n";
} else {
    echo "No cached content found for IPD ID {$ipdId}\n";
    echo "Content is generated on-demand. Try accessing the preview page first.\n";
}

$stmt->close();
$conn->close();
echo "=== Done ===\n";
