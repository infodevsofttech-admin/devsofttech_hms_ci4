# Bed Management Fix Summary - bed_master System

## System Overview

Your hospital management system uses the **NEW bed_master table** for bed management (not the legacy hc_bed_master).

### Database Schema

**bed_master table columns:**
- `id` - Primary key
- `bed_number` - Bed number/name
- `bed_code` - Unique bed code
- `ward_id` - Reference to ward_master
- `bed_status` - Status: 'available', 'occupied', 'maintenance', 'blocked', 'reserved', 'cleaning'
- `current_ipd_id` - IPD ID of patient currently occupying this bed (NULL when available)
- `status` - Record status: 'active', 'inactive'

### How It Works

1. **Bed Assignment**: When a patient is admitted, `bed_master.current_ipd_id` is set to the IPD ID, and `bed_status` is set to 'occupied'
2. **Bed Release**: When a patient is discharged, `current_ipd_id` is cleared (set to NULL) and `bed_status` is set to 'available'
3. **Bed Status Dashboard**: Queries `bed_master` joining with `ipd_master` on `current_ipd_id` to show current occupancy

## Issues Identified

### 1. Beds Not Released on Discharge  
**Problem**: When patients are discharged (ipd_status = 1), beds remain occupied:
- `bed_master.bed_status` still shows 'occupied'
- `bed_master.current_ipd_id` still has the IPD ID instead of NULL

**Impact**: Bed status dashboard shows 5 occupied beds but only 1 current admission, causing confusion and preventing bed reuse for new patients.

### 2. Blank "Bed Assign" Column
**Problem**: The Current Admission view may show blank in the "Bed No [Type]" column.

**Root Cause**: The query looks for beds where `current_ipd_id` matches the IPD, but if discharge didn't clear this field, the join fails.

## Solutions Implemented

### Fix 1: Add Bed Release Logic

**File**: `app/Models/IpdModel.php`

Added new method `releaseBed(int $ipdId)` that:
1. Clears `current_ipd_id` (sets to NULL) in `bed_master` table
2. Sets `bed_status = 'available'` in `bed_master` table
3. Updates `bed_assignment_history` with release date and reason (if that table is used)
4. Also handles legacy `hc_bed_master` for backward compatibility (if it exists)

### Fix 2: Call Bed Release on Discharge

**File**: `app/Controllers/Billing/Ipd.php`

Modified the `updateDischargeStatus()` method to call `releaseBed()` when ipd_status is set to 1.

### Fix 3: Update Bed Assignment Logic

**File**: `app/Models/IpdModel.php`

Updated `bedAssign()` method to:
1. Set `bed_master.current_ipd_id` to the IPD ID when assigning
2. Set `bed_master.bed_status` to 'occupied'
3. Clear any previous bed assigned to the same IPD (in case of bed transfer)

## Additional Recommendations

### 1. Run the SQL Fix to Clean Up Existing Data

For existing IPDs where beds weren't released properly, run the SQL script `fix_bed_management.sql`:

**Main fix (Step 2):**
```sql
UPDATE bed_master bm
INNER JOIN ipd_master i ON i.id = bm.current_ipd_id
SET 
    bm.bed_status = 'available',
    bm.current_ipd_id = NULL
WHERE i.ipd_status = 1
AND bm.current_ipd_id IS NOT NULL;
```

This releases all beds for patients who have been discharged.

### 2. Verify the Fix

After running the SQL, check the verification queries (Step 4) to confirm:
- All discharged patients' beds show as available
- Occupied bed count matches current admission count
- No beds have status mismatch

### 3. Monitor Bed Management

Use these pages to monitor:
- `/setting/admin/bed-status` - View current bed occupancy by ward/floor
- `/setting/admin/bed-management` - Manage bed master data
- `/billing/ipd/current-admission` - View patients with their assigned beds

## Testing Checklist

1. **Test Bed Assignment**: 
   - Admit a new patient
   - Assign a bed
   - Verify `bed_master.bed_status = 'occupied'`
   - Verify `bed_master.current_ipd_id` = IPD ID

2. **Test Discharge**:
   - Discharge a patient
   - Verify `bed_master.bed_status = 'available'`
   - Verify `bed_master.current_ipd_id IS NULL`

3. **Test Current Admission View**:
   - Open `/billing/ipd/current-admission`
   - Verify "Bed No [Type]" column shows bed information
   - Verify all admitted patients show their bed assignments

4. **Test Bed Status Panel**:
   - Open `/setting/admin/bed-status`
   - Verify Available count is correct
   - Verify Occupied count matches current admissions
   - Click on occupied beds to see patient details

## Files Modified

1. **app/Models/IpdModel.php**
   - Updated `releaseBed()` method - releases beds on discharge
   - Updated `bedAssign()` method - sets current_ipd_id when assigning

2. **app/Controllers/Billing/Ipd.php**
   - Modified `updateDischargeStatus()` to call bed release

3. **fix_bed_management.sql**
   - SQL script to fix existing data (discharged patients' beds)

4. **BED_MANAGEMENT_FIX_SUMMARY.md**
   - This documentation file

## Database Schema Reference

### Tables Used

1. **bed_master** (Primary table - NEW system)
   - `bed_status`: 'available' | 'occupied' | 'maintenance' | 'blocked' | 'reserved' | 'cleaning'
   - `current_ipd_id`: IPD ID using this bed (NULL = available)
   - `bed_number`: Bed identifier
   - `bed_code`: Unique code
   - `ward_id`: Reference to ward_master
   - `status`: 'active' | 'inactive'

2. **bed_assignment_history** (Optional tracking table)
   - Records all bed assignments with dates
   - `released_date`: When bed was freed
   - `release_reason`: Why bed was freed
   - Provides audit trail for bed usage

3. **ipd_master** (Patient admission records)
   - `ipd_status`: 0=admitted, 1=discharged
   - `register_date`: Admission date
   - `discharge_date`: Discharge date

4. **ward_master** (Ward/room information)
   - `ward_name`: Ward/room name
   - `floor_number`: Floor level
   - `ward_type`: Type of ward

## How Bed Management Works Now

### Bed Assignment Flow:
1. Patient admitted to IPD
2. Bed selected from available beds
3. `IpdModel::bedAssign()` called:
   - Creates entry in `bed_assignment_history`
   - Sets `bed_master.current_ipd_id` = IPD ID
   - Sets `bed_master.bed_status` = 'occupied'

### Bed Release Flow:
1. Patient discharged (ipd_status = 1)
2. `IpdModel::releaseBed()` called automatically:
   - Sets `bed_master.current_ipd_id` = NULL
   - Sets `bed_master.bed_status` = 'available'
   - Updates `bed_assignment_history.released_date`

### Bed Status Dashboard Query:
```sql
SELECT b.*, i.ipd_code, i.register_date, p.p_fname
FROM bed_master b
LEFT JOIN ipd_master i ON i.id = b.current_ipd_id AND i.ipd_status = 0
LEFT JOIN patient_master p ON p.id = i.p_id
WHERE b.status = 'active'
```

## Implementation Notes

- Legacy `hc_bed_master` support kept for backward compatibility (optional)
- Main system now uses `bed_master` exclusively
- `current_ipd_id` is the key field linking beds to patients
- Bed status transitions: available → occupied → available

## Support

If issues persist after running the fix:
1. Check `ipd_master.ipd_status` (0=admitted, 1=discharged)
2. Check `bed_master.current_ipd_id` values (should be NULL for available beds)
3. Check `bed_master.bed_status` values (should match occupancy)
4. Review application logs for errors in IpdModel methods
5. Run the verification queries from Step 4 in the SQL script
6. Check `/setting/admin/bed-status` dashboard for accurate counts

## Quick Troubleshooting

**Problem**: Beds still show occupied after discharge
- **Solution**: Run Step 2 of fix_bed_management.sql

**Problem**: "Bed Assign" column is blank
- **Solution**: Ensure bed_master.current_ipd_id is set during admission

**Problem**: Bed count mismatch
- **Solution**: Run verification queries (Step 4) to identify discrepancies

**Problem**: Cannot assign bed (shows as occupied)
- **Solution**: Check if current_ipd_id is stuck with old IPD - run fix SQL

## Contact

For additional support, check:
- Application logs in `writable/logs/`
- Database for constraint violations
- CI4 debug toolbar for query errors
