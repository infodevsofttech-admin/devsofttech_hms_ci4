<?php
/**
 * Database Migration for B2C Retail Pharmacy Returns and Same-Invoice Exchanges
 * Strictly isolates to mst_* tables
 */

$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "Starting B2C Sales Returns & Exchanges Migration...\n";

// 1. Add return-related columns to mst_sales
$salesCols = [
    "return_amount"    => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER gross_amount",
    "is_exchange_bill" => "TINYINT(1) NOT NULL DEFAULT 0 AFTER return_amount",
    "refund_amount"    => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER net_amount",
    "refund_mode"      => "VARCHAR(30) NULL AFTER refund_amount",
    "refund_ref_no"    => "VARCHAR(100) NULL AFTER refund_mode"
];

foreach ($salesCols as $col => $def) {
    $check = $db->query("SHOW COLUMNS FROM mst_sales LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE mst_sales ADD COLUMN $col $def";
        if ($db->query($sql)) {
            echo "[mst_sales] Added column: $col\n";
        } else {
            echo "[mst_sales] Error adding $col: " . $db->error . "\n";
        }
    } else {
        echo "[mst_sales] Column $col already exists.\n";
    }
}

// 2. Add return-related columns to mst_sales_items
$itemCols = [
    "item_type"        => "ENUM('SALE', 'RETURN') NOT NULL DEFAULT 'SALE' AFTER qty",
    "return_condition" => "VARCHAR(30) NOT NULL DEFAULT 'RESTOCKED' AFTER item_type",
    "ref_sale_id"      => "INT NULL AFTER return_condition",
    "ref_invoice_no"   => "VARCHAR(100) NULL AFTER ref_sale_id",
    "return_reason"    => "VARCHAR(255) NULL AFTER ref_invoice_no",
    "is_restocked"     => "TINYINT(1) NOT NULL DEFAULT 1 AFTER return_reason"
];

foreach ($itemCols as $col => $def) {
    $check = $db->query("SHOW COLUMNS FROM mst_sales_items LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE mst_sales_items ADD COLUMN $col $def";
        if ($db->query($sql)) {
            echo "[mst_sales_items] Added column: $col\n";
        } else {
            echo "[mst_sales_items] Error adding $col: " . $db->error . "\n";
        }
    } else {
        echo "[mst_sales_items] Column $col already exists.\n";
    }
}

// 3. Create standalone mst_sale_returns & mst_sale_return_items (for pure credit notes/returns)
$createReturnsSql = "CREATE TABLE IF NOT EXISTS `mst_sale_returns` (
    `return_id` INT AUTO_INCREMENT PRIMARY KEY,
    `store_id` INT NOT NULL,
    `credit_note_no` VARCHAR(100) NOT NULL,
    `return_date` DATETIME NOT NULL,
    `original_sale_id` INT NULL,
    `original_invoice_no` VARCHAR(100) NOT NULL,
    `new_sale_id` INT NULL,
    `patient_id` INT NULL,
    `uhid` VARCHAR(50) NULL,
    `patient_name` VARCHAR(150) NOT NULL,
    `patient_mobile` VARCHAR(50) NULL,
    `return_reason` VARCHAR(255) NULL,
    `gross_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_reversed_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `taxable_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cgst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `sgst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `igst_refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `round_off` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `net_refund_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `refund_mode` VARCHAR(30) NOT NULL DEFAULT 'Cash',
    `refund_ref_no` VARCHAR(100) NULL,
    `restock_condition` VARCHAR(50) NOT NULL DEFAULT 'RESTOCKED',
    `remarks` TEXT NULL,
    `created_by` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL,
    INDEX idx_store_date (store_id, return_date),
    INDEX idx_orig_invoice (original_invoice_no),
    INDEX idx_cn_no (credit_note_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($createReturnsSql)) {
    echo "Table mst_sale_returns created / verified.\n";
} else {
    echo "Error creating mst_sale_returns: " . $db->error . "\n";
}

$createReturnItemsSql = "CREATE TABLE IF NOT EXISTS `mst_sale_return_items` (
    `return_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `return_id` INT NOT NULL,
    `sale_item_id` INT NULL,
    `item_id` INT NOT NULL,
    `item_name` VARCHAR(200) NOT NULL,
    `batch_id` INT NOT NULL,
    `batch_no` VARCHAR(100) NOT NULL,
    `expiry_date` DATE NULL,
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
    INDEX idx_return_id (return_id),
    INDEX idx_item_batch (item_id, batch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($db->query($createReturnItemsSql)) {
    echo "Table mst_sale_return_items created / verified.\n";
} else {
    echo "Error creating mst_sale_return_items: " . $db->error . "\n";
}

// 4. Ensure mst_stores has credit note columns
$storeCols = [
    "credit_note_prefix" => "VARCHAR(20) NOT NULL DEFAULT 'CRN/' AFTER invoice_prefix",
    "next_credit_note_no" => "INT NOT NULL DEFAULT 1 AFTER next_invoice_no"
];
foreach ($storeCols as $scol => $sdef) {
    $check = $db->query("SHOW COLUMNS FROM mst_stores LIKE '$scol'");
    if ($check && $check->num_rows == 0) {
        $sql = "ALTER TABLE mst_stores ADD COLUMN $scol $sdef";
        if ($db->query($sql)) {
            echo "[mst_stores] Added column: $scol\n";
        } else {
            echo "[mst_stores] Error adding $scol: " . $db->error . "\n";
        }
    } else {
        echo "[mst_stores] Column $scol already exists.\n";
    }
}

// 5. Ensure account head for Sales Returns exists
$headCheck = $db->query("SELECT head_id FROM mst_account_heads WHERE head_code = '3005'");
if ($headCheck && $headCheck->num_rows == 0) {
    $db->query("INSERT INTO mst_account_heads (head_code, head_name, head_type) 
                VALUES ('3005', 'Sales Returns & Customer Refunds', 'Expense')");
    echo "Account head 3005 (Sales Returns & Customer Refunds) created.\n";
} else {
    echo "Account head 3005 already exists.\n";
}

$db->close();
echo "Migration complete!\n";
