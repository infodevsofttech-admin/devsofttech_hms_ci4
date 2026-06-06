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

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require __DIR__ . '/vendor/autoload.php';

// Bootstrap CI4
$paths = new Config\Paths();
$paths->systemDirectory = __DIR__ . '/system/';
$paths->appDirectory = __DIR__ . '/app/';

require_once FCPATH . '../system/bootstrap.php';
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

$ipdId = isset($argv[1]) ? (int) $argv[1] : 0;

if ($ipdId <= 0) {
    echo "Usage: php clear_discharge_content.php [ipd_id]\n";
    echo "Example: php clear_discharge_content.php 1\n";
    exit(1);
}

echo "Clearing discharge content for IPD ID: $ipdId\n\n";

try {
    // Check if content exists
    $existing = $db->table('ipd_discharge')
        ->where('ipd_id', $ipdId)
        ->get()
        ->getResultArray();
    
    if (empty($existing)) {
        echo "✓ No discharge content found for IPD ID $ipdId\n";
        echo "  The content will be auto-generated on next view.\n";
        exit(0);
    }
    
    echo "Found " . count($existing) . " discharge record(s) for IPD ID $ipdId\n";
    
    // Delete the content
    $deleted = $db->table('ipd_discharge')
        ->where('ipd_id', $ipdId)
        ->delete();
    
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
