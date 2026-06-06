<?php
/**
 * Clear discharge content for specific IPD to force regeneration with new template logic
 * 
 * Usage:
 * php clear_discharge_content.php [ipd_id]
 * 
 * Example:
 * php clear_discharge_content.php 1
 */

$ipdId = isset($argv[1]) ? (int) $argv[1] : 0;

if ($ipdId <= 0) {
    echo "Usage: php clear_discharge_content.php [ipd_id]\n";
    echo "Example: php clear_discharge_content.php 1\n";
    exit(1);
}

echo "Clearing discharge content for IPD ID: $ipdId\n\n";

// Database connection settings - UPDATE THESE IF NEEDED
$host = 'localhost';
$database = 'hms_data_ci4';
$username = 'root';
$password = '';
$port = 3306;

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if content exists
    $stmt = $pdo->prepare('SELECT id, ipd_id FROM ipd_discharge WHERE ipd_id = ?');
    $stmt->execute([$ipdId]);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($existing)) {
        echo "✓ No discharge content found for IPD ID $ipdId\n";
        echo "  The content will be auto-generated on next view.\n";
        exit(0);
    }
    
    echo "Found " . count($existing) . " discharge record(s) for IPD ID $ipdId\n";
    
    // Delete the content
    $stmt = $pdo->prepare('DELETE FROM ipd_discharge WHERE ipd_id = ?');
    $deleted = $stmt->execute([$ipdId]);
    
    if ($deleted) {
        echo "\n✓ Successfully cleared discharge content for IPD ID $ipdId\n";
        echo "  Next time you view the discharge summary, it will regenerate with the new logic.\n";
        echo "  If your template has demographic tokens ({{PATIENT_NAME}}, {{UHID}}, etc.),\n";
        echo "  the auto-generated content will NOT include the patient information table.\n";
    } else {
        echo "\n✗ Failed to clear discharge content\n";
        exit(1);
    }
    
} catch (\Throwable $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
