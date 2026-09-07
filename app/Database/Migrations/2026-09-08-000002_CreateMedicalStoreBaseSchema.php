<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalStoreBaseSchema extends Migration
{
    public function up(): void
    {
        // 1. mst_stores
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_stores` (
            `store_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_code` VARCHAR(50) NOT NULL UNIQUE,
            `store_slug` VARCHAR(80) DEFAULT NULL,
            `store_name` VARCHAR(150) NOT NULL,
            `building_name` VARCHAR(100) DEFAULT NULL,
            `floor_no` VARCHAR(50) DEFAULT NULL,
            `room_no` VARCHAR(50) DEFAULT NULL,
            `is_main_store` TINYINT(1) DEFAULT 0,
            `drug_license_no_20b` VARCHAR(100) DEFAULT NULL COMMENT 'Form 20-B (Retail Allopathic)',
            `drug_license_no_21b` VARCHAR(100) DEFAULT NULL COMMENT 'Form 21-B (Schedule C & C1)',
            `drug_license_no_20f_x` VARCHAR(100) DEFAULT NULL COMMENT 'Form 20-F / Schedule X Narcotics',
            `gstin` VARCHAR(20) DEFAULT NULL,
            `pan_no` VARCHAR(20) DEFAULT NULL,
            `fssai_no` VARCHAR(30) DEFAULT NULL,
            `state_code` VARCHAR(5) DEFAULT '07' COMMENT '2-digit Indian State Code for GST',
            `state_name` VARCHAR(100) DEFAULT 'Delhi',
            `registered_pharmacist_name` VARCHAR(120) DEFAULT NULL,
            `pharmacist_reg_no` VARCHAR(100) DEFAULT NULL,
            `contact_phone` VARCHAR(50) DEFAULT NULL,
            `contact_email` VARCHAR(100) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `invoice_prefix` VARCHAR(20) DEFAULT 'INV/',
            `credit_note_prefix` VARCHAR(20) NOT NULL DEFAULT 'CRN/',
            `next_invoice_no` INT DEFAULT 1001,
            `next_credit_note_no` INT NOT NULL DEFAULT 1,
            `bank_name` VARCHAR(100) DEFAULT NULL,
            `bank_account_no` VARCHAR(50) DEFAULT NULL,
            `bank_ifsc` VARCHAR(25) DEFAULT NULL,
            `upi_id` VARCHAR(100) DEFAULT NULL,
            `terms_conditions` TEXT DEFAULT NULL,
            `security_key` VARCHAR(100) DEFAULT NULL,
            `current_otp` VARCHAR(10) DEFAULT NULL,
            `otp_expiry` DATETIME DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `abdm_hfr_id` VARCHAR(100) DEFAULT NULL COMMENT 'Health Facility Registry ID',
            `abdm_hip_id` VARCHAR(100) DEFAULT NULL COMMENT 'Health Information Provider ID',
            `pharmacist_hpr_id` VARCHAR(100) DEFAULT NULL COMMENT 'Healthcare Professional Registry ID of Pharmacist',
            UNIQUE KEY `idx_store_slug` (`store_slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Ensure missing columns in mst_stores if table previously existed
        $storeCols = [
            'store_slug'          => "VARCHAR(80) DEFAULT NULL AFTER `store_code`",
            'credit_note_prefix'  => "VARCHAR(20) NOT NULL DEFAULT 'CRN/' AFTER `invoice_prefix`",
            'next_credit_note_no' => "INT NOT NULL DEFAULT 1 AFTER `next_invoice_no`",
            'security_key'        => "VARCHAR(100) DEFAULT NULL AFTER `terms_conditions`",
            'current_otp'         => "VARCHAR(10) DEFAULT NULL AFTER `security_key`",
            'otp_expiry'          => "DATETIME DEFAULT NULL AFTER `current_otp`",
            'abdm_hfr_id'         => "VARCHAR(100) DEFAULT NULL COMMENT 'Health Facility Registry ID'",
            'abdm_hip_id'         => "VARCHAR(100) DEFAULT NULL COMMENT 'Health Information Provider ID'",
            'pharmacist_hpr_id'   => "VARCHAR(100) DEFAULT NULL COMMENT 'Healthcare Professional Registry ID of Pharmacist'"
        ];
        foreach ($storeCols as $col => $def) {
            if (!$this->hasField('mst_stores', $col)) {
                $this->db->query("ALTER TABLE `mst_stores` ADD COLUMN `{$col}` {$def}");
            }
        }
        $this->ensureIndex('mst_stores', 'idx_store_slug', 'UNIQUE INDEX `idx_store_slug` (`store_slug`)');

        // 2. mst_store_devices
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_store_devices` (
            `device_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `machine_name` VARCHAR(150) DEFAULT 'Pharmacy Counter PC',
            `device_token` VARCHAR(128) NOT NULL UNIQUE,
            `device_fingerprint` VARCHAR(128) DEFAULT NULL,
            `ip_address` VARCHAR(50) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `verified_by_method` VARCHAR(30) DEFAULT 'SECURITY_KEY' COMMENT 'SECURITY_KEY, OTP, ADMIN_MANUAL',
            `status` VARCHAR(20) DEFAULT 'authorized' COMMENT 'authorized, revoked',
            `authorized_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `last_active_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_store` (`store_id`),
            INDEX `idx_token` (`device_token`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 3. mst_store_users
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_store_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `user_role` VARCHAR(50) DEFAULT 'pharmacist' COMMENT 'manager, cashier, pharmacist',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_store_user` (`store_id`, `user_id`),
            INDEX `idx_store` (`store_id`),
            INDEX `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 4. mst_items
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_items` (
            `item_id` INT AUTO_INCREMENT PRIMARY KEY,
            `item_name` VARCHAR(200) NOT NULL,
            `generic_name` VARCHAR(250) DEFAULT NULL,
            `category` VARCHAR(100) DEFAULT 'Tablet' COMMENT 'Tablet, Capsule, Syrup, Injection, Ointment, Surgical, Consumable, Food',
            `hsn_code` VARCHAR(20) DEFAULT '3004',
            `gst_rate` DECIMAL(5,2) DEFAULT 12.00,
            `unit_pack` VARCHAR(50) DEFAULT '10 Tablets' COMMENT 'e.g. 10 Tablets, 100ml, 1 Vial',
            `units_per_pack` INT DEFAULT 10 COMMENT 'Basic sellable loose units in 1 pack',
            `drug_schedule` VARCHAR(30) DEFAULT 'Schedule H' COMMENT 'OTC, Schedule H, Schedule H1, Schedule X, Narcotic',
            `manufacturer_name` VARCHAR(150) DEFAULT NULL,
            `barcode` VARCHAR(100) DEFAULT NULL,
            `min_reorder_level` INT DEFAULT 10,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `snomed_ct_code` VARCHAR(50) DEFAULT NULL COMMENT 'SNOMED-CT clinical drug code',
            `snomed_display` VARCHAR(255) DEFAULT NULL COMMENT 'SNOMED-CT clinical term',
            INDEX `idx_item_name` (`item_name`),
            INDEX `idx_generic` (`generic_name`),
            INDEX `idx_barcode` (`barcode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        if (!$this->hasField('mst_items', 'snomed_ct_code')) {
            $this->db->query("ALTER TABLE `mst_items` ADD COLUMN `snomed_ct_code` VARCHAR(50) DEFAULT NULL COMMENT 'SNOMED-CT clinical drug code'");
        }
        if (!$this->hasField('mst_items', 'snomed_display')) {
            $this->db->query("ALTER TABLE `mst_items` ADD COLUMN `snomed_display` VARCHAR(255) DEFAULT NULL COMMENT 'SNOMED-CT clinical term'");
        }

        // 5. mst_batches
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_batches` (
            `batch_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_no` VARCHAR(100) NOT NULL,
            `mfg_date` DATE DEFAULT NULL,
            `expiry_date` DATE NOT NULL COMMENT 'Used for FEFO ordering and expired stock locks',
            `mrp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `ptr` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Price to Retailer / Purchase rate before GST',
            `purchase_rate_net` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Net landing cost after GST and discount',
            `gst_rate` DECIMAL(5,2) DEFAULT 12.00,
            `barcode` VARCHAR(100) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_store_item` (`store_id`, `item_id`),
            INDEX `idx_batch_no` (`batch_no`),
            INDEX `idx_expiry` (`expiry_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 6. mst_stock
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_stock` (
            `stock_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_id` INT NOT NULL,
            `current_qty` INT NOT NULL DEFAULT 0 COMMENT 'Stock in basic units (e.g. loose tablets or bottles)',
            `reserved_qty` INT NOT NULL DEFAULT 0,
            `last_updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_store_item_batch` (`store_id`, `item_id`, `batch_id`),
            INDEX `idx_store` (`store_id`),
            INDEX `idx_item` (`item_id`),
            INDEX `idx_batch` (`batch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 7. mst_suppliers
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_suppliers` (
            `supplier_id` INT AUTO_INCREMENT PRIMARY KEY,
            `supplier_name` VARCHAR(150) NOT NULL,
            `dl_no_20b` VARCHAR(100) DEFAULT NULL,
            `dl_no_21b` VARCHAR(100) DEFAULT NULL,
            `gstin` VARCHAR(20) DEFAULT NULL,
            `pan_no` VARCHAR(20) DEFAULT NULL,
            `contact_person` VARCHAR(100) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `state_code` VARCHAR(5) DEFAULT '07',
            `credit_days` INT DEFAULT 30,
            `bank_details` TEXT DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_supplier_name` (`supplier_name`),
            INDEX `idx_gstin` (`gstin`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 8. mst_purchases
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_purchases` (
            `purchase_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `supplier_id` INT NOT NULL,
            `supplier_invoice_no` VARCHAR(100) NOT NULL,
            `invoice_date` DATE NOT NULL,
            `received_date` DATE NOT NULL,
            `due_date` DATE DEFAULT NULL,
            `taxable_amount` DECIMAL(12,2) DEFAULT 0.00,
            `cgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `sgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `igst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
            `round_off` DECIMAL(5,2) DEFAULT 0.00,
            `net_amount` DECIMAL(12,2) DEFAULT 0.00,
            `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
            `payment_status` VARCHAR(20) DEFAULT 'unpaid' COMMENT 'unpaid, partial, paid',
            `remarks` TEXT DEFAULT NULL,
            `created_by` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_store` (`store_id`),
            INDEX `idx_supplier` (`supplier_id`),
            INDEX `idx_invoice_date` (`invoice_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 9. mst_purchase_items
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_purchase_items` (
            `purchase_item_id` INT AUTO_INCREMENT PRIMARY KEY,
            `purchase_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_no` VARCHAR(100) NOT NULL,
            `expiry_date` DATE NOT NULL,
            `qty_packs` INT NOT NULL DEFAULT 1,
            `free_qty_packs` INT DEFAULT 0 COMMENT 'e.g. 10 + 1 scheme',
            `units_per_pack` INT DEFAULT 10,
            `total_units` INT NOT NULL,
            `mrp` DECIMAL(10,2) NOT NULL,
            `ptr` DECIMAL(10,2) NOT NULL,
            `discount_pct` DECIMAL(5,2) DEFAULT 0.00,
            `hsn_code` VARCHAR(20) DEFAULT '3004',
            `gst_rate` DECIMAL(5,2) DEFAULT 12.00,
            `taxable_value` DECIMAL(10,2) NOT NULL,
            `cgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `sgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `igst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `total_amount` DECIMAL(10,2) NOT NULL,
            `net_unit_landing_cost` DECIMAL(10,2) NOT NULL,
            INDEX `idx_purchase` (`purchase_id`),
            INDEX `idx_item` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 10. mst_sales
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_sales` (
            `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `invoice_no` VARCHAR(100) NOT NULL UNIQUE,
            `sale_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `patient_type` VARCHAR(30) DEFAULT 'Walk-in' COMMENT 'Walk-in, OPD, IPD',
            `uhid` VARCHAR(50) DEFAULT NULL COMMENT 'patient_master.p_code',
            `patient_id` INT DEFAULT NULL COMMENT 'patient_master.id',
            `opd_id` INT DEFAULT NULL COMMENT 'opd_master.opd_id',
            `ipd_id` INT DEFAULT NULL COMMENT 'ipd_master.id',
            `patient_name` VARCHAR(150) NOT NULL,
            `patient_mobile` VARCHAR(50) DEFAULT NULL,
            `patient_address` TEXT DEFAULT NULL,
            `age` VARCHAR(30) DEFAULT NULL,
            `gender` VARCHAR(20) DEFAULT NULL,
            `doctor_id` INT DEFAULT NULL,
            `doctor_name` VARCHAR(150) DEFAULT NULL,
            `doctor_reg_no` VARCHAR(100) DEFAULT NULL COMMENT 'Mandatory for Schedule H/H1 drugs',
            `ward_name` VARCHAR(100) DEFAULT NULL,
            `bed_no` VARCHAR(50) DEFAULT NULL,
            `gross_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `return_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `is_exchange_bill` TINYINT(1) NOT NULL DEFAULT 0,
            `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `bill_discount_type` VARCHAR(10) DEFAULT 'pct',
            `bill_discount_val` DECIMAL(10,2) DEFAULT 0.00,
            `taxable_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `round_off` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `refund_mode` VARCHAR(30) DEFAULT NULL,
            `refund_ref_no` VARCHAR(100) DEFAULT NULL,
            `payment_mode` VARCHAR(30) DEFAULT 'Cash' COMMENT 'Cash, UPI, Card, Mixed, IPD_Credit',
            `cash_paid` DECIMAL(10,2) DEFAULT 0.00,
            `upi_paid` DECIMAL(10,2) DEFAULT 0.00,
            `card_paid` DECIMAL(10,2) DEFAULT 0.00,
            `credit_amount` DECIMAL(10,2) DEFAULT 0.00,
            `payment_reference` VARCHAR(100) DEFAULT NULL,
            `bank_name` VARCHAR(100) DEFAULT '',
            `upi_ref_no` VARCHAR(100) DEFAULT '',
            `card_ref_no` VARCHAR(100) DEFAULT '',
            `is_bank_reconciled` TINYINT(1) DEFAULT 0,
            `reconciled_at` DATETIME DEFAULT NULL,
            `reconciled_by` VARCHAR(100) DEFAULT '',
            `reconciled_notes` TEXT DEFAULT NULL,
            `ipd_charge_id` INT DEFAULT NULL COMMENT 'Reference in ipd_invoice_item if billed to IPD running bill',
            `schedule_h1_flag` TINYINT(1) DEFAULT 0,
            `status` VARCHAR(30) DEFAULT 'completed' COMMENT 'completed, cancelled, refunded',
            `created_by` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `abha_id` VARCHAR(50) DEFAULT NULL COMMENT '14-Digit ABHA Number',
            `abha_address` VARCHAR(120) DEFAULT NULL COMMENT 'ABHA Address (user@abdm)',
            `abdm_care_context_ref` VARCHAR(100) DEFAULT NULL COMMENT 'ABDM Care Context Reference ID',
            `abdm_care_context_display` VARCHAR(255) DEFAULT NULL COMMENT 'ABDM Care Context Display Description',
            `abdm_fhir_bundle_json` LONGTEXT DEFAULT NULL COMMENT 'ABDM FHIR R4 MedicationDispense JSON',
            `abdm_sync_status` VARCHAR(30) DEFAULT 'PENDING' COMMENT 'PENDING, SYNCED, NOT_APPLICABLE',
            INDEX `idx_store_date` (`store_id`, `sale_date`),
            INDEX `idx_uhid` (`uhid`),
            INDEX `idx_opd` (`opd_id`),
            INDEX `idx_ipd` (`ipd_id`),
            INDEX `idx_invoice` (`invoice_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Ensure sales extra columns
        $salesCols = [
            'return_amount'             => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `gross_amount`",
            'is_exchange_bill'          => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `return_amount`",
            'bill_discount_type'        => "VARCHAR(10) DEFAULT 'pct' AFTER `discount_amount`",
            'bill_discount_val'         => "DECIMAL(10,2) DEFAULT 0.00 AFTER `bill_discount_type`",
            'refund_amount'             => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `net_amount`",
            'refund_mode'               => "VARCHAR(30) DEFAULT NULL AFTER `refund_amount`",
            'refund_ref_no'             => "VARCHAR(100) DEFAULT NULL AFTER `refund_mode`",
            'cash_paid'                 => "DECIMAL(10,2) DEFAULT 0.00",
            'upi_paid'                  => "DECIMAL(10,2) DEFAULT 0.00",
            'card_paid'                 => "DECIMAL(10,2) DEFAULT 0.00",
            'credit_amount'             => "DECIMAL(10,2) DEFAULT 0.00",
            'payment_reference'         => "VARCHAR(100) DEFAULT NULL",
            'bank_name'                 => "VARCHAR(100) DEFAULT ''",
            'upi_ref_no'                => "VARCHAR(100) DEFAULT ''",
            'card_ref_no'               => "VARCHAR(100) DEFAULT ''",
            'is_bank_reconciled'        => "TINYINT(1) DEFAULT 0",
            'reconciled_at'             => "DATETIME DEFAULT NULL",
            'reconciled_by'             => "VARCHAR(100) DEFAULT ''",
            'reconciled_notes'          => "TEXT DEFAULT NULL",
            'abha_id'                   => "VARCHAR(50) DEFAULT NULL",
            'abha_address'              => "VARCHAR(120) DEFAULT NULL",
            'abdm_care_context_ref'     => "VARCHAR(100) DEFAULT NULL",
            'abdm_care_context_display' => "VARCHAR(255) DEFAULT NULL",
            'abdm_fhir_bundle_json'     => "LONGTEXT DEFAULT NULL",
            'abdm_sync_status'          => "VARCHAR(30) DEFAULT 'PENDING'"
        ];
        foreach ($salesCols as $col => $def) {
            if (!$this->hasField('mst_sales', $col)) {
                $this->db->query("ALTER TABLE `mst_sales` ADD COLUMN `{$col}` {$def}");
            }
        }

        // 11. mst_sales_items
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_sales_items` (
            `sale_item_id` INT AUTO_INCREMENT PRIMARY KEY,
            `sale_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_id` INT NOT NULL,
            `batch_no` VARCHAR(100) NOT NULL,
            `expiry_date` DATE NOT NULL,
            `qty` INT NOT NULL DEFAULT 1 COMMENT 'Quantity in basic loose units dispensed',
            `item_type` VARCHAR(30) NOT NULL DEFAULT 'SALE',
            `return_condition` VARCHAR(30) NOT NULL DEFAULT 'RESTOCKED',
            `ref_sale_id` INT DEFAULT NULL,
            `ref_invoice_no` VARCHAR(100) DEFAULT NULL,
            `return_reason` VARCHAR(255) DEFAULT NULL,
            `is_restocked` TINYINT(1) NOT NULL DEFAULT 1,
            `sell_unit` VARCHAR(30) DEFAULT 'Strip',
            `units_per_pack` INT DEFAULT 1,
            `total_units` INT DEFAULT 1,
            `unit_price` DECIMAL(10,2) DEFAULT 0.00,
            `loose_qty` INT DEFAULT 0,
            `pack_qty` DECIMAL(10,2) DEFAULT 1.00,
            `unit_mrp` DECIMAL(10,2) NOT NULL,
            `discount_type` VARCHAR(10) DEFAULT 'pct',
            `discount_val` DECIMAL(10,2) DEFAULT 0.00,
            `discount_pct` DECIMAL(5,2) DEFAULT 0.00,
            `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
            `hsn_code` VARCHAR(20) DEFAULT '3004',
            `gst_rate` DECIMAL(5,2) DEFAULT 12.00,
            `taxable_value` DECIMAL(10,2) NOT NULL,
            `cgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `sgst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `igst_amount` DECIMAL(10,2) DEFAULT 0.00,
            `total_amount` DECIMAL(10,2) NOT NULL,
            INDEX `idx_sale` (`sale_id`),
            INDEX `idx_item` (`item_id`),
            INDEX `idx_batch` (`batch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $itemCols = [
            'item_type'        => "VARCHAR(30) NOT NULL DEFAULT 'SALE' AFTER `qty`",
            'return_condition' => "VARCHAR(30) NOT NULL DEFAULT 'RESTOCKED' AFTER `item_type`",
            'ref_sale_id'      => "INT DEFAULT NULL AFTER `return_condition`",
            'ref_invoice_no'   => "VARCHAR(100) DEFAULT NULL AFTER `ref_sale_id`",
            'return_reason'    => "VARCHAR(255) DEFAULT NULL AFTER `ref_invoice_no`",
            'is_restocked'     => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `return_reason`",
            'sell_unit'        => "VARCHAR(30) DEFAULT 'Strip'",
            'units_per_pack'   => "INT DEFAULT 1",
            'total_units'      => "INT DEFAULT 1",
            'unit_price'       => "DECIMAL(10,2) DEFAULT 0.00",
            'loose_qty'        => "INT DEFAULT 0",
            'pack_qty'         => "DECIMAL(10,2) DEFAULT 1.00",
            'discount_type'    => "VARCHAR(10) DEFAULT 'pct'",
            'discount_val'     => "DECIMAL(10,2) DEFAULT 0.00"
        ];
        foreach ($itemCols as $col => $def) {
            if (!$this->hasField('mst_sales_items', $col)) {
                $this->db->query("ALTER TABLE `mst_sales_items` ADD COLUMN `{$col}` {$def}");
            }
        }

        // 12. mst_sale_returns
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_sale_returns` (
            `return_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `credit_note_no` VARCHAR(100) NOT NULL,
            `return_date` DATETIME NOT NULL,
            `original_sale_id` INT DEFAULT NULL,
            `original_invoice_no` VARCHAR(100) NOT NULL,
            `new_sale_id` INT DEFAULT NULL,
            `patient_id` INT DEFAULT NULL,
            `uhid` VARCHAR(50) DEFAULT NULL,
            `patient_name` VARCHAR(150) NOT NULL,
            `patient_mobile` VARCHAR(50) DEFAULT NULL,
            `return_reason` VARCHAR(255) DEFAULT NULL,
            `gross_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `discount_reversed_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `taxable_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `cgst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `sgst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `igst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `round_off` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `net_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `refund_mode` VARCHAR(30) NOT NULL DEFAULT 'Cash',
            `refund_ref_no` VARCHAR(100) DEFAULT NULL,
            `restock_condition` VARCHAR(50) NOT NULL DEFAULT 'RESTOCKED',
            `remarks` TEXT DEFAULT NULL,
            `created_by` INT NOT NULL DEFAULT 1,
            `created_at` DATETIME NOT NULL,
            KEY `idx_store_date` (`store_id`, `return_date`),
            KEY `idx_orig_invoice` (`original_invoice_no`),
            KEY `idx_cn_no` (`credit_note_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 13. mst_sale_return_items
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_sale_return_items` (
            `return_item_id` INT AUTO_INCREMENT PRIMARY KEY,
            `return_id` INT NOT NULL,
            `sale_item_id` INT DEFAULT NULL,
            `item_id` INT NOT NULL,
            `item_name` VARCHAR(200) NOT NULL,
            `batch_id` INT NOT NULL,
            `batch_no` VARCHAR(100) NOT NULL,
            `expiry_date` DATE DEFAULT NULL,
            `return_sell_unit` VARCHAR(30) NOT NULL DEFAULT 'Tablet',
            `return_qty` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
            `units_per_pack` INT NOT NULL DEFAULT 1,
            `return_total_units` INT NOT NULL DEFAULT 1,
            `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `refund_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `discount_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `hsn_code` VARCHAR(20) NOT NULL DEFAULT '3004',
            `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 12.00,
            `taxable_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `is_restocked` TINYINT(1) NOT NULL DEFAULT 1,
            KEY `idx_return_id` (`return_id`),
            KEY `idx_item_batch` (`item_id`, `batch_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 14. mst_transfers
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_transfers` (
            `transfer_id` INT AUTO_INCREMENT PRIMARY KEY,
            `transfer_no` VARCHAR(100) NOT NULL UNIQUE,
            `from_store_id` INT NOT NULL,
            `to_store_id` INT NOT NULL,
            `status` VARCHAR(30) DEFAULT 'requested' COMMENT 'requested, approved, dispatched, received, rejected',
            `requested_by` INT DEFAULT NULL,
            `dispatched_by` INT DEFAULT NULL,
            `received_by` INT DEFAULT NULL,
            `request_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `dispatch_date` DATETIME DEFAULT NULL,
            `receive_date` DATETIME DEFAULT NULL,
            `remarks` TEXT DEFAULT NULL,
            INDEX `idx_from_to` (`from_store_id`, `to_store_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 15. mst_transfer_items
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_transfer_items` (
            `transfer_item_id` INT AUTO_INCREMENT PRIMARY KEY,
            `transfer_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_id` INT DEFAULT NULL,
            `requested_qty` INT NOT NULL,
            `dispatched_qty` INT DEFAULT 0,
            `received_qty` INT DEFAULT 0,
            INDEX `idx_transfer` (`transfer_id`),
            INDEX `idx_item` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 16. mst_account_heads
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_account_heads` (
            `head_id` INT AUTO_INCREMENT PRIMARY KEY,
            `head_code` VARCHAR(50) NOT NULL UNIQUE,
            `head_name` VARCHAR(150) NOT NULL,
            `head_type` VARCHAR(50) NOT NULL COMMENT 'Asset, Liability, Equity, Income, Expense',
            `parent_id` INT DEFAULT NULL,
            `is_system` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 17. mst_ledger_entries
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_ledger_entries` (
            `entry_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `voucher_no` VARCHAR(100) NOT NULL,
            `voucher_type` VARCHAR(50) NOT NULL COMMENT 'SALE, PURCHASE, PAYMENT, RECEIPT, CONTRA, ADJUSTMENT',
            `voucher_date` DATE NOT NULL,
            `account_head_id` INT NOT NULL,
            `debit_amount` DECIMAL(12,2) DEFAULT 0.00,
            `credit_amount` DECIMAL(12,2) DEFAULT 0.00,
            `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'sales_invoice, purchase_invoice, supplier_payment, patient_credit',
            `reference_id` INT DEFAULT NULL,
            `narration` TEXT DEFAULT NULL,
            `reconciled_flag` TINYINT(1) DEFAULT 0,
            `reconciled_at` DATETIME DEFAULT NULL,
            `reconciled_ref` VARCHAR(100) DEFAULT '',
            `created_by` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_store_date` (`store_id`, `voucher_date`),
            INDEX `idx_head` (`account_head_id`),
            INDEX `idx_voucher` (`voucher_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // 18. mst_stock_audit
        $this->db->query("CREATE TABLE IF NOT EXISTS `mst_stock_audit` (
            `audit_id` INT AUTO_INCREMENT PRIMARY KEY,
            `store_id` INT NOT NULL,
            `item_id` INT NOT NULL,
            `batch_id` INT NOT NULL,
            `audit_type` VARCHAR(50) NOT NULL COMMENT 'OPENING_STOCK, DAMAGE, BREAKAGE, EXPIRY_DUMP, PHYSICAL_VARIATION',
            `system_qty` INT NOT NULL DEFAULT 0,
            `physical_qty` INT NOT NULL DEFAULT 0,
            `variation_qty` INT NOT NULL DEFAULT 0,
            `rate` DECIMAL(10,2) DEFAULT 0.00,
            `total_value` DECIMAL(12,2) DEFAULT 0.00,
            `remarks` TEXT DEFAULT NULL,
            `conducted_by` INT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_store_audit` (`store_id`, `audit_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // ==========================================
        // SEED DATA (Idempotent: Only if tables empty)
        // ==========================================

        // Seed 1: System Account Heads
        $systemHeads = [
            ['1001', 'Cash in Hand', 'Asset'],
            ['1002', 'Bank Account / UPI Clearing', 'Asset'],
            ['1003', 'Stock in Hand / Inventory Valuation', 'Asset'],
            ['1004', 'Sundry Debtors / Patient Receivables', 'Asset'],
            ['1005', 'IPD Credit Hospital Running Ledger', 'Asset'],
            ['1006', 'Input CGST Credit', 'Asset'],
            ['1007', 'Input SGST Credit', 'Asset'],
            ['1008', 'Input IGST Credit', 'Asset'],
            ['2001', 'Sundry Creditors / Supplier Accounts', 'Liability'],
            ['2002', 'Output CGST Payable', 'Liability'],
            ['2003', 'Output SGST Payable', 'Liability'],
            ['2004', 'Output IGST Payable', 'Liability'],
            ['3001', 'Pharmacy Sales Account (Taxable)', 'Income'],
            ['3002', 'Pharmacy Sales Account (Exempt)', 'Income'],
            ['3003', 'Discounts Received on Purchases', 'Income'],
            ['3004', 'Opening Stock Capital Balance', 'Equity'],
            ['3005', 'Sales Returns & Customer Refunds', 'Expense'],
            ['4001', 'Cost of Goods Sold / Purchase Inward', 'Expense'],
            ['4002', 'Discounts Allowed on Sales', 'Expense'],
            ['4003', 'Medicine Expiry & Breakage Loss', 'Expense']
        ];
        foreach ($systemHeads as $h) {
            $exists = $this->db->table('mst_account_heads')->where('head_code', $h[0])->countAllResults();
            if ($exists === 0) {
                $this->db->table('mst_account_heads')->insert([
                    'head_code'  => $h[0],
                    'head_name'  => $h[1],
                    'head_type'  => $h[2],
                    'is_system'  => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Seed 2: Default Stores (Central Pharmacy + OPD Counter)
        $storeCount = $this->db->table('mst_stores')->countAllResults();
        if ($storeCount === 0) {
            $this->db->table('mst_stores')->insertBatch([
                [
                    'store_code'                 => 'ST-MAIN',
                    'store_slug'                 => 'storea',
                    'store_name'                 => 'City Hospital Central Pharmacy',
                    'building_name'              => 'Main Hospital Block A',
                    'floor_no'                   => 'Ground Floor',
                    'room_no'                    => 'Room 101',
                    'is_main_store'              => 1,
                    'drug_license_no_20b'        => 'DL-20B-DEL-10928',
                    'drug_license_no_21b'        => 'DL-21B-DEL-10929',
                    'gstin'                      => '07AAAAA0000A1Z5',
                    'pan_no'                     => 'AAAAA0000A',
                    'fssai_no'                   => '10020011000123',
                    'state_code'                 => '07',
                    'state_name'                 => 'Delhi',
                    'registered_pharmacist_name' => 'Rajesh Kumar, B.Pharm',
                    'pharmacist_reg_no'          => 'DPC-Reg-45892',
                    'contact_phone'              => '+91 9876543210',
                    'contact_email'              => 'pharmacy@cityhospital.com',
                    'address'                    => 'Plot 12, Medical Enclave, Main Hospital Block, Delhi',
                    'invoice_prefix'             => 'MAIN/26-27/',
                    'credit_note_prefix'         => 'CRN/',
                    'next_invoice_no'            => 1001,
                    'next_credit_note_no'        => 1,
                    'upi_id'                     => 'cityhospital@upi',
                    'security_key'               => 'HMS-80F6ED-297',
                    'abdm_hfr_id'                => 'IN0710001234',
                    'abdm_hip_id'                => 'HIP-CITYHOSP-01',
                    'pharmacist_hpr_id'          => '91-8822-4411-9901',
                    'terms_conditions'           => "1. Goods once sold will be returned as per Drug Rules within 7 days with bill.\n2. Keep medicines stored below 25°C away from direct sunlight.",
                    'is_active'                  => 1,
                    'created_at'                 => date('Y-m-d H:i:s')
                ],
                [
                    'store_code'                 => 'ST-OPD-B2',
                    'store_slug'                 => 'storeb',
                    'store_name'                 => 'City Medicos - OPD Building Counter',
                    'building_name'              => 'OPD & Diagnostic Block B',
                    'floor_no'                   => '1st Floor',
                    'room_no'                    => 'Counter 2',
                    'is_main_store'              => 0,
                    'drug_license_no_20b'        => 'DL-20B-DEL-11450',
                    'drug_license_no_21b'        => 'DL-21B-DEL-11451',
                    'gstin'                      => '07BBBBB1111B2Z8',
                    'pan_no'                     => 'BBBBB1111B',
                    'fssai_no'                   => '10020011000456',
                    'state_code'                 => '07',
                    'state_name'                 => 'Delhi',
                    'registered_pharmacist_name' => 'Sunil Sharma, D.Pharm',
                    'pharmacist_reg_no'          => 'DPC-Reg-67210',
                    'contact_phone'              => '+91 9811223344',
                    'contact_email'              => 'opdpharmacy@cityhospital.com',
                    'address'                    => 'OPD Wing, Block B, 1st Floor, Medical Enclave, Delhi',
                    'invoice_prefix'             => 'OPD/26-27/',
                    'credit_note_prefix'         => 'CRN/',
                    'next_invoice_no'            => 5001,
                    'next_credit_note_no'        => 1,
                    'upi_id'                     => 'citymedicos@upi',
                    'security_key'               => 'HMS-72A1CF-415',
                    'abdm_hfr_id'                => 'IN0710001234-OPD',
                    'abdm_hip_id'                => 'HIP-CITYHOSP-02',
                    'pharmacist_hpr_id'          => '91-8822-4411-9902',
                    'terms_conditions'           => "1. Check expiry and batch at counter.\n2. No exchange on Schedule H/H1 drugs without valid prescription.",
                    'is_active'                  => 1,
                    'created_at'                 => date('Y-m-d H:i:s')
                ]
            ]);
        }

        // Seed 3: Sample Medicines Catalog
        $itemCount = $this->db->table('mst_items')->countAllResults();
        if ($itemCount === 0) {
            $sampleItems = [
                ['Augmentin 625 Duo Tablet', 'Amoxycillin (500mg) + Clavulanic Acid (125mg)', 'Tablet', '3004', 12.00, '10 Tablets', 10, 'Schedule H1', 'GSK Pharmaceuticals', '890103000001', 20, '372687004', 'Amoxicillin and clavulanic acid'],
                ['Pan 40 Tablet', 'Pantoprazole (40mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'Schedule H', 'Alkem Laboratories', '890103000002', 30, '386965005', 'Pantoprazole'],
                ['Dolo 650 Tablet', 'Paracetamol (650mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'OTC', 'Micro Labs Ltd', '890103000003', 50, '387517004', 'Paracetamol'],
                ['Azithral 500 Tablet', 'Azithromycin (500mg)', 'Tablet', '3004', 12.00, '5 Tablets', 5, 'Schedule H1', 'Alembic Pharmaceuticals', '890103000004', 15, '387207008', 'Azithromycin'],
                ['Monocef 1g Injection', 'Ceftriaxone (1000mg)', 'Injection', '3004', 12.00, '1 Vial', 1, 'Schedule H1', 'Aristo Pharmaceuticals', '890103000005', 25, '387325003', 'Ceftriaxone'],
                ['Betadine 10% Ointment', 'Povidone Iodine (10% w/w)', 'Ointment', '3004', 12.00, '20 gm Tube', 1, 'OTC', 'Win-Medicare', '890103000006', 15, NULL, NULL],
                ['Ascoril LS Syrup', 'Levosalbutamol + Ambroxol + Guaifenesin', 'Syrup', '3004', 12.00, '100 ml Bottle', 1, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000007', 20, NULL, NULL],
                ['Telma 40 Tablet', 'Telmisartan (40mg)', 'Tablet', '3004', 12.00, '30 Tablets', 30, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000008', 25, NULL, NULL],
                ['Normal Saline 0.9% IV', 'Sodium Chloride (0.9% w/v)', 'Consumable', '3004', 12.00, '500 ml Bottle', 1, 'Schedule H', 'Aculife Healthcare', '890103000009', 40, NULL, NULL]
            ];
            foreach ($sampleItems as $it) {
                $this->db->table('mst_items')->insert([
                    'item_name'         => $it[0],
                    'generic_name'      => $it[1],
                    'category'          => $it[2],
                    'hsn_code'          => $it[3],
                    'gst_rate'          => $it[4],
                    'unit_pack'         => $it[5],
                    'units_per_pack'    => $it[6],
                    'drug_schedule'     => $it[7],
                    'manufacturer_name' => $it[8],
                    'barcode'           => $it[9],
                    'min_reorder_level' => $it[10],
                    'snomed_ct_code'    => $it[11],
                    'snomed_display'    => $it[12],
                    'is_active'         => 1,
                    'created_at'        => date('Y-m-d H:i:s')
                ]);
            }
        }

        // Seed 4: Sample Batches & Stock
        $batchCount = $this->db->table('mst_batches')->countAllResults();
        if ($batchCount === 0) {
            $this->db->query("INSERT INTO `mst_batches` (`store_id`, `item_id`, `batch_no`, `mfg_date`, `expiry_date`, `mrp`, `ptr`, `purchase_rate_net`, `gst_rate`) VALUES
                (1, 1, 'AUG-2401', '2024-01-01', '2026-10-31', 204.50, 155.00, 173.60, 12.00),
                (1, 1, 'AUG-2409', '2024-09-01', '2027-04-30', 204.50, 155.00, 173.60, 12.00),
                (1, 2, 'PAN-5512', '2024-03-01', '2027-02-28', 162.00, 115.00, 128.80, 12.00),
                (1, 3, 'DOL-9901', '2024-06-01', '2027-06-30', 34.00, 22.50, 25.20, 12.00),
                (1, 4, 'AZI-3321', '2024-02-01', '2026-12-31', 125.00, 89.00, 99.68, 12.00),
                (1, 5, 'MON-8810', '2024-05-01', '2026-11-30', 68.00, 48.00, 53.76, 12.00),
                (2, 1, 'AUG-2401', '2024-01-01', '2026-10-31', 204.50, 155.00, 173.60, 12.00),
                (2, 2, 'PAN-5512', '2024-03-01', '2027-02-28', 162.00, 115.00, 128.80, 12.00),
                (2, 3, 'DOL-9901', '2024-06-01', '2027-06-30', 34.00, 22.50, 25.20, 12.00)
            ");

            $this->db->query("INSERT INTO `mst_stock` (`store_id`, `item_id`, `batch_id`, `current_qty`) VALUES
                (1, 1, 1, 150),
                (1, 1, 2, 200),
                (1, 2, 3, 300),
                (1, 3, 4, 450),
                (1, 4, 5, 120),
                (1, 5, 6, 80),
                (2, 1, 7, 50),
                (2, 2, 8, 90),
                (2, 3, 9, 150)
            ");
        }

        // Seed 5: Sample Supplier
        $supCount = $this->db->table('mst_suppliers')->countAllResults();
        if ($supCount === 0) {
            $this->db->table('mst_suppliers')->insert([
                'supplier_name'  => 'Apex Healthcare Distributors',
                'dl_no_20b'      => 'DL-20B-DEL-98441',
                'dl_no_21b'      => 'DL-21B-DEL-98442',
                'gstin'          => '07AACCA1234F1Z9',
                'pan_no'         => 'AACCA1234F',
                'contact_person' => 'Ramesh Gupta',
                'phone'          => '+91 9811002233',
                'email'          => 'sales@apexhealth.in',
                'address'        => '45, Wholesale Medicine Market, Bhagirath Palace, Chandni Chowk, Delhi',
                'state_code'     => '07',
                'credit_days'    => 30,
                'is_active'      => 1,
                'created_at'     => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function down(): void
    {
        // Safe rollback: drop in reverse dependency order
        $tables = [
            'mst_sale_return_items',
            'mst_sale_returns',
            'mst_sales_items',
            'mst_sales',
            'mst_transfer_items',
            'mst_transfers',
            'mst_stock_audit',
            'mst_ledger_entries',
            'mst_stock',
            'mst_batches',
            'mst_purchase_items',
            'mst_purchases',
            'mst_suppliers',
            'mst_items',
            'mst_store_users',
            'mst_store_devices',
            'mst_account_heads',
            'mst_stores'
        ];

        foreach ($tables as $tbl) {
            if ($this->hasTable($tbl)) {
                $this->forge->dropTable($tbl, true);
            }
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $rows = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasField(string $table, string $field): bool
    {
        if (!$this->hasTable($table)) {
            return false;
        }

        try {
            $safeTable = str_replace('`', '``', $table);
            $sql = 'SHOW COLUMNS FROM `' . $safeTable . '` LIKE ' . $this->db->escape($field);
            $rows = $this->db->query($sql)->getResultArray();
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function ensureIndex(string $table, string $indexName, string $createIndexSql): void
    {
        try {
            $safeTable = str_replace('`', '``', $table);
            $check = $this->db->query("SHOW INDEX FROM `{$safeTable}` WHERE Key_name = " . $this->db->escape($indexName))->getResultArray();
            if (empty($check)) {
                $this->db->query("ALTER TABLE `{$safeTable}` ADD {$createIndexSql}");
            }
        } catch (\Throwable $e) {
            // Index already exists or error
        }
    }
}
