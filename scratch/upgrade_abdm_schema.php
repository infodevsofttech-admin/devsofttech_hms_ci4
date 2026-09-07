<?php
/**
 * Add ABDM columns to mst_stores, mst_items, and mst_sales
 */

$db = new mysqli('127.0.0.1', 'root', '', 'hms_data_ci4', 3306);
if ($db->connect_error) {
    die("DB Connection failed: " . $db->connect_error . "\n");
}

function addColumnIfNotExists($db, $table, $column, $definition) {
    $check = $db->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD `$column` $definition";
        if ($db->query($sql)) {
            echo "   ✓ Added `$column` to `$table`\n";
        } else {
            echo "   ✗ Error adding `$column` to `$table`: " . $db->error . "\n";
        }
    } else {
        echo "   - Column `$column` already exists in `$table`\n";
    }
}

echo "=== 1. UPDATING mst_stores FOR ABDM HFR & HPR ===\n";
addColumnIfNotExists($db, 'mst_stores', 'abdm_hfr_id', "VARCHAR(100) DEFAULT NULL COMMENT 'Health Facility Registry ID'");
addColumnIfNotExists($db, 'mst_stores', 'abdm_hip_id', "VARCHAR(100) DEFAULT NULL COMMENT 'Health Information Provider ID'");
addColumnIfNotExists($db, 'mst_stores', 'pharmacist_hpr_id', "VARCHAR(100) DEFAULT NULL COMMENT 'Healthcare Professional Registry ID of Pharmacist'");

// Set sample ABDM HFR ID for default stores
$db->query("UPDATE `mst_stores` SET `abdm_hfr_id` = 'IN0710001234', `abdm_hip_id` = 'HIP-CITYHOSP-01', `pharmacist_hpr_id` = '91-8822-4411-9901' WHERE `store_id` = 1 AND (`abdm_hfr_id` IS NULL OR `abdm_hfr_id` = '')");
$db->query("UPDATE `mst_stores` SET `abdm_hfr_id` = 'IN0710001234-OPD', `abdm_hip_id` = 'HIP-CITYHOSP-02', `pharmacist_hpr_id` = '91-8822-4411-9902' WHERE `store_id` = 2 AND (`abdm_hfr_id` IS NULL OR `abdm_hfr_id` = '')");

echo "\n=== 2. UPDATING mst_items FOR ABDM SNOMED-CT CODES ===\n";
addColumnIfNotExists($db, 'mst_items', 'snomed_ct_code', "VARCHAR(50) DEFAULT NULL COMMENT 'SNOMED-CT clinical drug code'");
addColumnIfNotExists($db, 'mst_items', 'snomed_display', "VARCHAR(255) DEFAULT NULL COMMENT 'SNOMED-CT clinical term'");

// Seed some standard SNOMED codes
$db->query("UPDATE `mst_items` SET `snomed_ct_code` = '372687004', `snomed_display` = 'Amoxicillin and clavulanic acid' WHERE `item_name` LIKE '%Augmentin%'");
$db->query("UPDATE `mst_items` SET `snomed_ct_code` = '387517004', `snomed_display` = 'Paracetamol' WHERE `item_name` LIKE '%Paracetamol%'");
$db->query("UPDATE `mst_items` SET `snomed_ct_code` = '387207008', `snomed_display` = 'Azithromycin' WHERE `item_name` LIKE '%Azithral%'");
$db->query("UPDATE `mst_items` SET `snomed_ct_code` = '386965005', `snomed_display` = 'Pantoprazole' WHERE `item_name` LIKE '%Pantocid%'");
$db->query("UPDATE `mst_items` SET `snomed_ct_code` = '387325003', `snomed_display` = 'Ceftriaxone' WHERE `item_name` LIKE '%Monocef%'");

echo "\n=== 3. UPDATING mst_sales FOR ABDM CARE CONTEXT & FHIR BUNDLE ===\n";
addColumnIfNotExists($db, 'mst_sales', 'abha_id', "VARCHAR(50) DEFAULT NULL COMMENT '14-Digit ABHA Number'");
addColumnIfNotExists($db, 'mst_sales', 'abha_address', "VARCHAR(120) DEFAULT NULL COMMENT 'ABHA Address (user@abdm)'");
addColumnIfNotExists($db, 'mst_sales', 'abdm_care_context_ref', "VARCHAR(100) DEFAULT NULL COMMENT 'ABDM Care Context Reference ID'");
addColumnIfNotExists($db, 'mst_sales', 'abdm_care_context_display', "VARCHAR(255) DEFAULT NULL COMMENT 'ABDM Care Context Display Description'");
addColumnIfNotExists($db, 'mst_sales', 'abdm_fhir_bundle_json', "LONGTEXT DEFAULT NULL COMMENT 'ABDM FHIR R4 MedicationDispense JSON'");
addColumnIfNotExists($db, 'mst_sales', 'abdm_sync_status', "VARCHAR(30) DEFAULT 'PENDING' COMMENT 'PENDING, SYNCED, NOT_APPLICABLE'");

echo "\n=== ABDM SCHEMA UPGRADE COMPLETE ===\n";
