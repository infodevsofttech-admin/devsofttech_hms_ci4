-- =========================================================================
-- Medical Store: B2C Sales Returns & Same-Invoice Exchanges (Marg ERP Standard)
-- Target: Remote Server MySQL / MariaDB Database Migration
-- Date: 2026-09-08
-- =========================================================================

-- 1. Add Return and Refund tracking columns to mst_sales (if not exists)
SET @dbname = DATABASE();

-- mst_sales.return_amount
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales' AND COLUMN_NAME = 'return_amount');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales` ADD COLUMN `return_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `gross_amount`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales.is_exchange_bill
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales' AND COLUMN_NAME = 'is_exchange_bill');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales` ADD COLUMN `is_exchange_bill` TINYINT(1) DEFAULT 0 AFTER `return_amount`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales.refund_amount
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales' AND COLUMN_NAME = 'refund_amount');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales` ADD COLUMN `refund_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `net_amount`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales.refund_mode
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales' AND COLUMN_NAME = 'refund_mode');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales` ADD COLUMN `refund_mode` VARCHAR(50) NULL AFTER `refund_amount`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales.refund_ref_no
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales' AND COLUMN_NAME = 'refund_ref_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales` ADD COLUMN `refund_ref_no` VARCHAR(100) NULL AFTER `refund_mode`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 2. Add Return Item tracking columns to mst_sales_items (if not exists)

-- mst_sales_items.item_type
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'item_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `item_type` ENUM(\'SALE\', \'RETURN\') NOT NULL DEFAULT \'SALE\' AFTER `qty`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales_items.return_condition
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'return_condition');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `return_condition` ENUM(\'RESTOCKED\', \'DAMAGED\', \'EXPIRED\') DEFAULT \'RESTOCKED\' AFTER `item_type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales_items.ref_sale_id
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'ref_sale_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `ref_sale_id` INT UNSIGNED NULL AFTER `return_condition`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales_items.ref_invoice_no
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'ref_invoice_no');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `ref_invoice_no` VARCHAR(50) NULL AFTER `ref_sale_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales_items.return_reason
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'return_reason');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `return_reason` VARCHAR(255) NULL AFTER `ref_invoice_no`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- mst_sales_items.is_restocked
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'mst_sales_items' AND COLUMN_NAME = 'is_restocked');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `mst_sales_items` ADD COLUMN `is_restocked` TINYINT(1) DEFAULT 1 AFTER `return_reason`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 3. Create mst_sale_returns (Sequential GST Credit Notes)
CREATE TABLE IF NOT EXISTS `mst_sale_returns` (
    `return_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `store_id` INT UNSIGNED NOT NULL,
    `credit_note_no` VARCHAR(50) NOT NULL UNIQUE,
    `return_date` DATETIME NOT NULL,
    `original_sale_id` INT UNSIGNED NULL,
    `original_invoice_no` VARCHAR(50) NULL,
    `new_sale_id` INT UNSIGNED NULL,
    `patient_id` INT UNSIGNED NULL,
    `uhid` VARCHAR(50) NULL,
    `patient_name` VARCHAR(150) NULL,
    `patient_mobile` VARCHAR(20) NULL,
    `return_reason` VARCHAR(255) NULL,
    `gross_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `discount_reversed_amount` DECIMAL(12,2) DEFAULT 0.00,
    `taxable_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `cgst_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `sgst_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `igst_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `round_off` DECIMAL(6,2) DEFAULT 0.00,
    `net_refund_amount` DECIMAL(12,2) DEFAULT 0.00,
    `refund_mode` VARCHAR(50) DEFAULT 'EXCHANGE_BILL_ADJUSTMENT',
    `refund_ref_no` VARCHAR(100) NULL,
    `restock_condition` ENUM('RESTOCKED', 'DAMAGED', 'EXPIRED') DEFAULT 'RESTOCKED',
    `remarks` TEXT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    KEY `idx_store_date` (`store_id`, `return_date`),
    KEY `idx_orig_sale` (`original_sale_id`),
    KEY `idx_new_sale` (`new_sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. Create mst_sale_return_items
CREATE TABLE IF NOT EXISTS `mst_sale_return_items` (
    `return_item_id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT UNSIGNED NOT NULL,
    `sale_item_id` INT UNSIGNED NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `batch_id` INT UNSIGNED NOT NULL,
    `batch_no` VARCHAR(50) NOT NULL,
    `expiry_date` DATE NOT NULL,
    `hsn_code` VARCHAR(20) NULL,
    `units_per_pack` INT NOT NULL DEFAULT 1,
    `return_sell_unit` VARCHAR(20) NOT NULL DEFAULT 'Tablet',
    `return_qty` INT NOT NULL DEFAULT 1,
    `return_total_units` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `taxable_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_refund_amount` DECIMAL(12,2) NOT NULL,
    `return_condition` ENUM('RESTOCKED', 'DAMAGED', 'EXPIRED') DEFAULT 'RESTOCKED',
    `is_restocked` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    KEY `idx_return_id` (`return_id`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_batch_id` (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. Seed Account Head 3005: Sales Returns & Customer Refunds
INSERT INTO `mst_account_heads` (`head_code`, `head_name`, `head_type`, `is_system`, `status`, `created_at`)
SELECT '3005', 'Sales Returns & Customer Refunds', 'Expense', 1, 'active', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `mst_account_heads` WHERE `head_code` = '3005'
);
