<?php
/**
 * Run Bed Management Fix
 * This script executes the SQL fixes for bed management issues
 */

// Database credentials from .env
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'hms_data_ci4';

// Connect to database
$db = new mysqli($hostname, $username, $password, $database);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "==============================================\n";
echo "Bed Management Fix Script\n";
echo "==============================================\n\n";

// Step 0: Check current state BEFORE fix
echo "BEFORE FIX - Current State:\n";
echo "----------------------------\n";

$query = "
    SELECT 
        COUNT(CASE WHEN bed_status = 'available' THEN 1 END) as available_beds,
        COUNT(CASE WHEN bed_status = 'occupied' THEN 1 END) as occupied_beds,
        COUNT(*) as total_beds
    FROM bed_master
    WHERE status = 'active'
";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Total Beds: {$row->total_beds}\n";
echo "Available: {$row->available_beds}\n";
echo "Occupied: {$row->occupied_beds}\n";

$query = "SELECT COUNT(*) as current_admissions FROM ipd_master WHERE ipd_status = 0";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Current Admissions: {$row->current_admissions}\n";

$query = "
    SELECT COUNT(*) as problem_beds
    FROM bed_master bm
    INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
    WHERE i.ipd_status = 1
    AND bm.current_ipd_id IS NOT NULL
";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Beds stuck with discharged patients: {$row->problem_beds}\n\n";

// Step 1: Release beds for discharged patients (MAIN FIX)
echo "Step 1: Releasing beds for discharged patients...\n";
$sql = "
    UPDATE bed_master bm
    INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
    SET 
        bm.bed_status = 'available',
        bm.current_ipd_id = NULL
    WHERE i.ipd_status = 1
    AND bm.current_ipd_id IS NOT NULL
";
$db->query($sql);
$affected = $db->affected_rows;
echo "✓ Released {$affected} beds\n\n";

// Step 1b: Fix phantom occupied beds (occupied status but no patient assigned)
echo "Step 1b: Fixing phantom occupied beds (occupied but no current_ipd_id)...\n";
$sql = "
    UPDATE bed_master
    SET bed_status = 'available'
    WHERE bed_status = 'occupied'
    AND current_ipd_id IS NULL
";
$db->query($sql);
$affected = $db->affected_rows;
echo "✓ Fixed {$affected} phantom occupied beds\n\n";

// Step 1c: Sync bed_assignment_history to bed_master.current_ipd_id
echo "Step 1c: Syncing bed assignments from history to bed_master...\n";
$sql = "
    UPDATE bed_master bm
    INNER JOIN bed_assignment_history bah ON bah.bed_id = bm.id
    INNER JOIN ipd_master i ON i.id = bah.ipd_id
    SET 
        bm.current_ipd_id = bah.ipd_id,
        bm.bed_status = 'occupied'
    WHERE i.ipd_status = 0
    AND bah.released_date IS NULL
    AND bm.current_ipd_id IS NULL
";
$db->query($sql);
$affected = $db->affected_rows;
echo "✓ Synced {$affected} bed assignments\n\n";

// Step 2: Update bed_assignment_history (if table exists)
$tableCheck = $db->query("SHOW TABLES LIKE 'bed_assignment_history'");
if ($tableCheck->num_rows > 0) {
    echo "Step 2: Updating bed_assignment_history...\n";
    $sql = "
        UPDATE bed_assignment_history bah
        INNER JOIN ipd_master i ON i.id = bah.ipd_id
        SET 
            bah.released_date = COALESCE(i.discharge_date, NOW()),
            bah.release_reason = 'Patient Discharged (Data Fix)'
        WHERE i.ipd_status = 1
        AND bah.released_date IS NULL
    ";
    $db->query($sql);
    $affected = $db->affected_rows;
    echo "✓ Updated {$affected} history records\n\n";
} else {
    echo "Step 2: bed_assignment_history table not found, skipping...\n\n";
}

// Step 3: Verify the fix
echo "AFTER FIX - Verification:\n";
echo "-------------------------\n";

$query = "
    SELECT 
        COUNT(CASE WHEN bed_status = 'available' THEN 1 END) as available_beds,
        COUNT(CASE WHEN bed_status = 'occupied' THEN 1 END) as occupied_beds,
        COUNT(*) as total_beds
    FROM bed_master
    WHERE status = 'active'
";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Total Beds: {$row->total_beds}\n";
echo "Available: {$row->available_beds}\n";
echo "Occupied: {$row->occupied_beds}\n";

$query = "SELECT COUNT(*) as current_admissions FROM ipd_master WHERE ipd_status = 0";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Current Admissions: {$row->current_admissions}\n";

$query = "
    SELECT COUNT(*) as problem_beds
    FROM bed_master bm
    INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
    WHERE i.ipd_status = 1
    AND bm.current_ipd_id IS NOT NULL
";
$result = $db->query($query);
$row = $result->fetch_object();
echo "Beds stuck with discharged patients: {$row->problem_beds}\n\n";

// Check for any mismatches
$query = "
    SELECT 
        bm.id,
        bm.bed_number,
        bm.bed_code,
        bm.bed_status,
        bm.current_ipd_id
    FROM bed_master bm
    LEFT JOIN ward_master w ON w.id = bm.ward_id
    WHERE bm.bed_status = 'occupied' 
    AND bm.current_ipd_id IS NULL
    LIMIT 5
";
$result = $db->query($query);
if ($result->num_rows > 0) {
    echo "⚠ Warning: Found beds marked 'occupied' but no current_ipd_id:\n";
    while ($bed = $result->fetch_assoc()) {
        echo "  - Bed #{$bed['bed_number']} (ID: {$bed['id']})\n";
    }
    echo "\n";
} else {
    echo "✓ No status mismatches found\n\n";
}

$db->close();

echo "==============================================\n";
echo "Fix completed successfully!\n";
echo "==============================================\n";
echo "\nNext steps:\n";
echo "1. Check bed status at: /setting/admin/bed-status\n";
echo "2. Test discharge a patient and verify bed is released\n";
echo "3. Test admitting a patient and verify bed is assigned\n";
