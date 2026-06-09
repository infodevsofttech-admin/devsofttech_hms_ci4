-- =====================================================
-- Bed Management Fix SQL Scripts  
-- For systems using bed_master table (NEW CI4 system)
-- Run these to fix existing data issues
-- =====================================================
--
-- IMPORTANT NOTES:
-- 1. This script is for bed_master table (NEW system)
-- 2. bed_master columns: bed_number, bed_code, ward_id, bed_status, current_ipd_id
-- 3. bed_status values: 'available', 'occupied', 'maintenance', 'blocked', 'reserved', 'cleaning'
-- 4. current_ipd_id: stores the IPD ID when bed is occupied (NULL when available)
-- 5. Always backup your database before running!
-- =====================================================

-- =====================================================
-- Step 1: NOT NEEDED for bed_master system
-- =====================================================
-- The bed_master table tracks current occupancy via current_ipd_id column
-- No historical backfill needed - just release discharged patients' beds in Step 2

-- =====================================================
-- Step 2: Release beds for discharged patients
-- =====================================================
-- This is the MAIN FIX - releases beds when patients are discharged

UPDATE bed_master bm
INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
SET 
    bm.bed_status = 'available',
    bm.current_ipd_id = NULL
WHERE i.ipd_status = 1
AND bm.current_ipd_id IS NOT NULL;

-- =====================================================
-- Step 2b: Fix phantom occupied beds
-- =====================================================
-- This fixes beds marked as 'occupied' but with no patient assigned
-- These are "phantom" occupied beds that block bed availability

UPDATE bed_master
SET bed_status = 'available'
WHERE bed_status = 'occupied'
AND current_ipd_id IS NULL;

-- =====================================================
-- Step 2c: Sync bed_assignment_history to bed_master.current_ipd_id
-- =====================================================
-- This syncs active bed assignments from history table to bed_master
-- Fixes cases where bed was assigned in history but current_ipd_id wasn't set

UPDATE bed_master bm
INNER JOIN bed_assignment_history bah ON bah.bed_id = bm.id
INNER JOIN ipd_master i ON i.id = bah.ipd_id
SET 
    bm.current_ipd_id = bah.ipd_id,
    bm.bed_status = 'occupied'
WHERE i.ipd_status = 0
AND bah.released_date IS NULL
AND bm.current_ipd_id IS NULL;

-- =====================================================
-- Step 3: Update bed_assignment_history (if you use it)
-- =====================================================
-- This marks bed assignments as released in the history table

UPDATE bed_assignment_history bah
INNER JOIN ipd_master i ON i.id = bah.ipd_id
SET 
    bah.released_date = COALESCE(i.discharge_date, NOW()),
    bah.release_reason = 'Patient Discharged (Data Fix)'
WHERE i.ipd_status = 1
AND bah.released_date IS NULL;

-- =====================================================
-- Step 4: Verification Queries
-- =====================================================

-- 4a. Check beds still marked as occupied for discharged patients
SELECT 
    bm.id,
    bm.bed_number,
    bm.bed_code,
    bm.bed_status,
    bm.current_ipd_id,
    i.ipd_code,
    i.ipd_status,
    w.ward_name,
    DATE_FORMAT(i.discharge_date, '%d-%m-%Y') as discharge_date
FROM bed_master bm
INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
LEFT JOIN ward_master w ON w.id = bm.ward_id
WHERE i.ipd_status = 1
AND bm.current_ipd_id IS NOT NULL;

-- Expected: Should return 0 rows after fix

-- 4b. Check currently admitted patients with their beds
SELECT 
    i.id as ipd_id,
    i.ipd_code,
    i.ipd_status,
    bm.bed_number,
    bm.bed_code,
    bm.bed_status,
    w.ward_name
FROM ipd_master i
LEFT JOIN bed_master bm ON bm.current_ipd_id = i.id
LEFT JOIN ward_master w ON w.id = bm.ward_id
WHERE i.ipd_status = 0
ORDER BY i.ipd_code;

-- Expected: All active IPDs should show their assigned beds

-- 4c. Check current bed status summary
SELECT 
    COUNT(CASE WHEN bed_status = 'available' THEN 1 END) as available_beds,
    COUNT(CASE WHEN bed_status = 'occupied' THEN 1 END) as occupied_beds,
    COUNT(CASE WHEN bed_status = 'maintenance' THEN 1 END) as maintenance_beds,
    COUNT(CASE WHEN bed_status = 'blocked' THEN 1 END) as blocked_beds,
    COUNT(CASE WHEN bed_status = 'reserved' THEN 1 END) as reserved_beds,
    COUNT(CASE WHEN bed_status = 'cleaning' THEN 1 END) as cleaning_beds,
    COUNT(*) as total_beds
FROM bed_master
WHERE status = 'active';

-- 4d. Compare with current admissions count
SELECT COUNT(*) as current_admissions 
FROM ipd_master 
WHERE ipd_status = 0;

-- The occupied_beds count should match current_admissions count

-- 4e. Find beds with status mismatch (occupied but no IPD assigned)
SELECT 
    bm.id,
    bm.bed_number,
    bm.bed_code,
    bm.bed_status,
    bm.current_ipd_id,
    w.ward_name
FROM bed_master bm
LEFT JOIN ward_master w ON w.id = bm.ward_id
WHERE bm.bed_status = 'occupied' 
AND bm.current_ipd_id IS NULL;

-- Expected: Should return 0 rows (all occupied beds should have current_ipd_id)

-- =====================================================
-- Step 5: Optional - Clean up orphaned bed assignments
-- =====================================================
-- This removes bed_assignment_history records for IPDs that don't exist
-- Only run if you use bed_assignment_history table

DELETE bah FROM bed_assignment_history bah
LEFT JOIN ipd_master i ON i.id = bah.ipd_id
WHERE i.id IS NULL;

-- =====================================================
-- NOTES:
-- =====================================================
-- 1. Always backup your database before running these scripts
-- 2. Run Step 4 verification queries before and after to see the improvement
-- 3. Step 2 is the main fix - it releases beds for discharged patients
-- 4. These scripts are safe to run multiple times (idempotent)
-- 5. Monitor the application after running to ensure no issues
-- 6. For bed_master system: focuses on bed_status and current_ipd_id columns
-- 7. If you don't use bed_assignment_history, skip Steps 3 and 5
--
-- WHAT THIS FIXES:
-- - Beds marked as 'occupied' for patients who have been discharged
-- - current_ipd_id not cleared when patients leave
-- - Bed count mismatches in bed status dashboard
-- - "Bed Assign" column showing blank because current_ipd_id not set properly
--
-- AFTER RUNNING:
-- - All discharged patients' beds will be marked as 'available'
-- - current_ipd_id will be NULL for available beds
-- - Bed counts in dashboard will match actual occupancy
-- - New bed assignments will work correctly with CI4 code
