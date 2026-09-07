-- =========================================================================
-- Medical Store & Multi-Building Pharmacy: Complete Schema & B2C Returns
-- Target: Remote Server MySQL / MariaDB Database Migration
-- Date: 2026-09-08
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. mst_stores
CREATE TABLE IF NOT EXISTS `mst_stores` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. mst_store_devices
CREATE TABLE IF NOT EXISTS `mst_store_devices` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. mst_store_users
CREATE TABLE IF NOT EXISTS `mst_store_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `store_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `user_role` VARCHAR(50) DEFAULT 'pharmacist' COMMENT 'manager, cashier, pharmacist',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_store_user` (`store_id`, `user_id`),
    INDEX `idx_store` (`store_id`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. mst_items
CREATE TABLE IF NOT EXISTS `mst_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. mst_batches
CREATE TABLE IF NOT EXISTS `mst_batches` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. mst_stock
CREATE TABLE IF NOT EXISTS `mst_stock` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. mst_suppliers
CREATE TABLE IF NOT EXISTS `mst_suppliers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. mst_purchases
CREATE TABLE IF NOT EXISTS `mst_purchases` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. mst_purchase_items
CREATE TABLE IF NOT EXISTS `mst_purchase_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. mst_sales
CREATE TABLE IF NOT EXISTS `mst_sales` (
    `sale_id` INT AUTO_INCREMENT PRIMARY KEY,
    `store_id` INT NOT NULL,
    `invoice_no` VARCHAR(100) NOT NULL UNIQUE,
    `sale_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `patient_type` VARCHAR(30) DEFAULT 'Walk-in' COMMENT 'Walk-in, OPD, IPD',
    `uhid` VARCHAR(50) DEFAULT NULL,
    `patient_id` INT DEFAULT NULL,
    `opd_id` INT DEFAULT NULL,
    `ipd_id` INT DEFAULT NULL,
    `patient_name` VARCHAR(150) NOT NULL,
    `patient_mobile` VARCHAR(50) DEFAULT NULL,
    `patient_address` TEXT DEFAULT NULL,
    `age` VARCHAR(30) DEFAULT NULL,
    `gender` VARCHAR(20) DEFAULT NULL,
    `doctor_id` INT DEFAULT NULL,
    `doctor_name` VARCHAR(150) DEFAULT NULL,
    `doctor_reg_no` VARCHAR(100) DEFAULT NULL,
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
    `ipd_charge_id` INT DEFAULT NULL,
    `schedule_h1_flag` TINYINT(1) DEFAULT 0,
    `status` VARCHAR(30) DEFAULT 'completed' COMMENT 'completed, cancelled, refunded',
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `abha_id` VARCHAR(50) DEFAULT NULL,
    `abha_address` VARCHAR(120) DEFAULT NULL,
    `abdm_care_context_ref` VARCHAR(100) DEFAULT NULL,
    `abdm_care_context_display` VARCHAR(255) DEFAULT NULL,
    `abdm_fhir_bundle_json` LONGTEXT DEFAULT NULL,
    `abdm_sync_status` VARCHAR(30) DEFAULT 'PENDING',
    INDEX `idx_store_date` (`store_id`, `sale_date`),
    INDEX `idx_uhid` (`uhid`),
    INDEX `idx_opd` (`opd_id`),
    INDEX `idx_ipd` (`ipd_id`),
    INDEX `idx_invoice` (`invoice_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. mst_sales_items
CREATE TABLE IF NOT EXISTS `mst_sales_items` (
    `sale_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `sale_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT NOT NULL,
    `batch_no` VARCHAR(100) NOT NULL,
    `expiry_date` DATE NOT NULL,
    `qty` INT NOT NULL DEFAULT 1,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. mst_sale_returns (Sequential GST Credit Notes)
CREATE TABLE IF NOT EXISTS `mst_sale_returns` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. mst_sale_return_items
CREATE TABLE IF NOT EXISTS `mst_sale_return_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. mst_transfers
CREATE TABLE IF NOT EXISTS `mst_transfers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. mst_transfer_items
CREATE TABLE IF NOT EXISTS `mst_transfer_items` (
    `transfer_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `transfer_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    `batch_id` INT DEFAULT NULL,
    `requested_qty` INT NOT NULL,
    `dispatched_qty` INT DEFAULT 0,
    `received_qty` INT DEFAULT 0,
    INDEX `idx_transfer` (`transfer_id`),
    INDEX `idx_item` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. mst_account_heads
CREATE TABLE IF NOT EXISTS `mst_account_heads` (
    `head_id` INT AUTO_INCREMENT PRIMARY KEY,
    `head_code` VARCHAR(50) NOT NULL UNIQUE,
    `head_name` VARCHAR(150) NOT NULL,
    `head_type` VARCHAR(50) NOT NULL COMMENT 'Asset, Liability, Equity, Income, Expense',
    `parent_id` INT DEFAULT NULL,
    `is_system` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. mst_ledger_entries
CREATE TABLE IF NOT EXISTS `mst_ledger_entries` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. mst_stock_audit
CREATE TABLE IF NOT EXISTS `mst_stock_audit` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- SEED DATA (System Accounts, Stores, Catalog, Batches, Stock)
-- -------------------------------------------------------------

INSERT IGNORE INTO `mst_account_heads` (`head_code`, `head_name`, `head_type`, `is_system`, `created_at`) VALUES
('1001', 'Cash in Hand', 'Asset', 1, NOW()),
('1002', 'Bank Account / UPI Clearing', 'Asset', 1, NOW()),
('1003', 'Stock in Hand / Inventory Valuation', 'Asset', 1, NOW()),
('1004', 'Sundry Debtors / Patient Receivables', 'Asset', 1, NOW()),
('1005', 'IPD Credit Hospital Running Ledger', 'Asset', 1, NOW()),
('1006', 'Input CGST Credit', 'Asset', 1, NOW()),
('1007', 'Input SGST Credit', 'Asset', 1, NOW()),
('1008', 'Input IGST Credit', 'Asset', 1, NOW()),
('2001', 'Sundry Creditors / Supplier Accounts', 'Liability', 1, NOW()),
('2002', 'Output CGST Payable', 'Liability', 1, NOW()),
('2003', 'Output SGST Payable', 'Liability', 1, NOW()),
('2004', 'Output IGST Payable', 'Liability', 1, NOW()),
('3001', 'Pharmacy Sales Account (Taxable)', 'Income', 1, NOW()),
('3002', 'Pharmacy Sales Account (Exempt)', 'Income', 1, NOW()),
('3003', 'Discounts Received on Purchases', 'Income', 1, NOW()),
('3004', 'Opening Stock Capital Balance', 'Equity', 1, NOW()),
('3005', 'Sales Returns & Customer Refunds', 'Expense', 1, NOW()),
('4001', 'Cost of Goods Sold / Purchase Inward', 'Expense', 1, NOW()),
('4002', 'Discounts Allowed on Sales', 'Expense', 1, NOW()),
('4003', 'Medicine Expiry & Breakage Loss', 'Expense', 1, NOW());

-- Seed Default Stores if none exist
INSERT INTO `mst_stores` (
    `store_id`, `store_code`, `store_slug`, `store_name`, `building_name`, `floor_no`, `room_no`, `is_main_store`,
    `drug_license_no_20b`, `drug_license_no_21b`, `gstin`, `pan_no`, `fssai_no`, `state_code`, `state_name`,
    `registered_pharmacist_name`, `pharmacist_reg_no`, `contact_phone`, `contact_email`, `address`,
    `invoice_prefix`, `credit_note_prefix`, `next_invoice_no`, `next_credit_note_no`, `upi_id`, `security_key`,
    `abdm_hfr_id`, `abdm_hip_id`, `pharmacist_hpr_id`, `terms_conditions`, `is_active`, `created_at`
)
SELECT 1, 'ST-MAIN', 'storea', 'City Hospital Central Pharmacy', 'Main Hospital Block A', 'Ground Floor', 'Room 101', 1,
       'DL-20B-DEL-10928', 'DL-21B-DEL-10929', '07AAAAA0000A1Z5', 'AAAAA0000A', '10020011000123', '07', 'Delhi',
       'Rajesh Kumar, B.Pharm', 'DPC-Reg-45892', '+91 9876543210', 'pharmacy@cityhospital.com', 'Plot 12, Medical Enclave, Main Hospital Block, Delhi',
       'MAIN/26-27/', 'CRN/', 1001, 1, 'cityhospital@upi', 'HMS-80F6ED-297',
       'IN0710001234', 'HIP-CITYHOSP-01', '91-8822-4411-9901', '1. Goods once sold will be returned as per Drug Rules within 7 days with bill.\n2. Keep medicines stored below 25°C away from direct sunlight.', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_stores` WHERE `store_id` = 1);

INSERT INTO `mst_stores` (
    `store_id`, `store_code`, `store_slug`, `store_name`, `building_name`, `floor_no`, `room_no`, `is_main_store`,
    `drug_license_no_20b`, `drug_license_no_21b`, `gstin`, `pan_no`, `fssai_no`, `state_code`, `state_name`,
    `registered_pharmacist_name`, `pharmacist_reg_no`, `contact_phone`, `contact_email`, `address`,
    `invoice_prefix`, `credit_note_prefix`, `next_invoice_no`, `next_credit_note_no`, `upi_id`, `security_key`,
    `abdm_hfr_id`, `abdm_hip_id`, `pharmacist_hpr_id`, `terms_conditions`, `is_active`, `created_at`
)
SELECT 2, 'ST-OPD-B2', 'storeb', 'City Medicos - OPD Building Counter', 'OPD & Diagnostic Block B', '1st Floor', 'Counter 2', 0,
       'DL-20B-DEL-11450', 'DL-21B-DEL-11451', '07BBBBB1111B2Z8', 'BBBBB1111B', '10020011000456', '07', 'Delhi',
       'Sunil Sharma, D.Pharm', 'DPC-Reg-67210', '+91 9811223344', 'opdpharmacy@cityhospital.com', 'OPD Wing, Block B, 1st Floor, Medical Enclave, Delhi',
       'OPD/26-27/', 'CRN/', 5001, 1, 'citymedicos@upi', 'HMS-72A1CF-415',
       'IN0710001234-OPD', 'HIP-CITYHOSP-02', '91-8822-4411-9902', '1. Check expiry and batch at counter.\n2. No exchange on Schedule H/H1 drugs without valid prescription.', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_stores` WHERE `store_id` = 2);

-- Seed Sample Supplier
INSERT INTO `mst_suppliers` (
    `supplier_id`, `supplier_name`, `dl_no_20b`, `dl_no_21b`, `gstin`, `pan_no`, `contact_person`,
    `phone`, `email`, `address`, `state_code`, `credit_days`, `is_active`, `created_at`
)
SELECT 1, 'Apex Healthcare Distributors', 'DL-20B-DEL-98441', 'DL-21B-DEL-98442', '07AACCA1234F1Z9',
       'AACCA1234F', 'Ramesh Gupta', '+91 9811002233', 'sales@apexhealth.in',
       '45, Wholesale Medicine Market, Bhagirath Palace, Chandni Chowk, Delhi', '07', 30, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM `mst_suppliers` WHERE `supplier_id` = 1);

-- Seed Sample Medicines Catalog
INSERT IGNORE INTO `mst_items` (`item_id`, `item_name`, `generic_name`, `category`, `hsn_code`, `gst_rate`, `unit_pack`, `units_per_pack`, `drug_schedule`, `manufacturer_name`, `barcode`, `min_reorder_level`, `snomed_ct_code`, `snomed_display`, `is_active`, `created_at`) VALUES
(1, 'Augmentin 625 Duo Tablet', 'Amoxycillin (500mg) + Clavulanic Acid (125mg)', 'Tablet', '3004', 12.00, '10 Tablets', 10, 'Schedule H1', 'GSK Pharmaceuticals', '890103000001', 20, '372687004', 'Amoxicillin and clavulanic acid', 1, NOW()),
(2, 'Pan 40 Tablet', 'Pantoprazole (40mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'Schedule H', 'Alkem Laboratories', '890103000002', 30, '386965005', 'Pantoprazole', 1, NOW()),
(3, 'Dolo 650 Tablet', 'Paracetamol (650mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'OTC', 'Micro Labs Ltd', '890103000003', 50, '387517004', 'Paracetamol', 1, NOW()),
(4, 'Azithral 500 Tablet', 'Azithromycin (500mg)', 'Tablet', '3004', 12.00, '5 Tablets', 5, 'Schedule H1', 'Alembic Pharmaceuticals', '890103000004', 15, '387207008', 'Azithromycin', 1, NOW()),
(5, 'Monocef 1g Injection', 'Ceftriaxone (1000mg)', 'Injection', '3004', 12.00, '1 Vial', 1, 'Schedule H1', 'Aristo Pharmaceuticals', '890103000005', 25, '387325003', 'Ceftriaxone', 1, NOW()),
(6, 'Betadine 10% Ointment', 'Povidone Iodine (10% w/w)', 'Ointment', '3004', 12.00, '20 gm Tube', 1, 'OTC', 'Win-Medicare', '890103000006', 15, NULL, NULL, 1, NOW()),
(7, 'Ascoril LS Syrup', 'Levosalbutamol + Ambroxol + Guaifenesin', 'Syrup', '3004', 12.00, '100 ml Bottle', 1, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000007', 20, NULL, NULL, 1, NOW()),
(8, 'Telma 40 Tablet', 'Telmisartan (40mg)', 'Tablet', '3004', 12.00, '30 Tablets', 30, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000008', 25, NULL, NULL, 1, NOW()),
(9, 'Normal Saline 0.9% IV', 'Sodium Chloride (0.9% w/v)', 'Consumable', '3004', 12.00, '500 ml Bottle', 1, 'Schedule H', 'Aculife Healthcare', '890103000009', 40, NULL, NULL, 1, NOW());

-- Seed Sample Batches & Stock if empty
INSERT IGNORE INTO `mst_batches` (`batch_id`, `store_id`, `item_id`, `batch_no`, `mfg_date`, `expiry_date`, `mrp`, `ptr`, `purchase_rate_net`, `gst_rate`) VALUES
(1, 1, 1, 'AUG-2401', '2024-01-01', '2026-10-31', 204.50, 155.00, 173.60, 12.00),
(2, 1, 1, 'AUG-2409', '2024-09-01', '2027-04-30', 204.50, 155.00, 173.60, 12.00),
(3, 1, 2, 'PAN-5512', '2024-03-01', '2027-02-28', 162.00, 115.00, 128.80, 12.00),
(4, 1, 3, 'DOL-9901', '2024-06-01', '2027-06-30', 34.00, 22.50, 25.20, 12.00),
(5, 1, 4, 'AZI-3321', '2024-02-01', '2026-12-31', 125.00, 89.00, 99.68, 12.00),
(6, 1, 5, 'MON-8810', '2024-05-01', '2026-11-30', 68.00, 48.00, 53.76, 12.00),
(7, 2, 1, 'AUG-2401', '2024-01-01', '2026-10-31', 204.50, 155.00, 173.60, 12.00),
(8, 2, 2, 'PAN-5512', '2024-03-01', '2027-02-28', 162.00, 115.00, 128.80, 12.00),
(9, 2, 3, 'DOL-9901', '2024-06-01', '2027-06-30', 34.00, 22.50, 25.20, 12.00);

INSERT IGNORE INTO `mst_stock` (`stock_id`, `store_id`, `item_id`, `batch_id`, `current_qty`) VALUES
(1, 1, 1, 1, 150),
(2, 1, 1, 2, 200),
(3, 1, 2, 3, 300),
(4, 1, 3, 4, 450),
(5, 1, 4, 5, 120),
(6, 1, 5, 6, 80),
(7, 2, 1, 7, 50),
(8, 2, 2, 8, 90),
(9, 2, 3, 9, 150);

SET FOREIGN_KEY_CHECKS = 1;
