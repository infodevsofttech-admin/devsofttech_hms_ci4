<?php
/**
 * Database Migration Script for Medical Store & Multi-Building Pharmacy Application
 * All tables use 'mst_' prefix to ensure ZERO disturbance to legacy pharmacy tables.
 */

$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . "\n");
}

$db->set_charset('utf8mb4');

echo "=== Initializing Medical Store (mst_*) Database Schema ===\n";

$queries = [
    // 1. Stores Master (Multi-Building / Counters with Indian Drug Licenses & GST)
    "CREATE TABLE IF NOT EXISTS `mst_stores` (
        `store_id` INT AUTO_INCREMENT PRIMARY KEY,
        `store_code` VARCHAR(50) NOT NULL UNIQUE,
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
        `next_invoice_no` INT DEFAULT 1001,
        `bank_name` VARCHAR(100) DEFAULT NULL,
        `bank_account_no` VARCHAR(50) DEFAULT NULL,
        `bank_ifsc` VARCHAR(25) DEFAULT NULL,
        `upi_id` VARCHAR(100) DEFAULT NULL,
        `terms_conditions` TEXT DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 2. Store Staff / User Mapping
    "CREATE TABLE IF NOT EXISTS `mst_store_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `store_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `user_role` VARCHAR(50) DEFAULT 'pharmacist' COMMENT 'manager, cashier, pharmacist',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_store_user` (`store_id`, `user_id`),
        INDEX `idx_store` (`store_id`),
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 3. Drug / Product Master Catalog (Shared or Store-specific)
    "CREATE TABLE IF NOT EXISTS `mst_items` (
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
        INDEX `idx_item_name` (`item_name`),
        INDEX `idx_generic` (`generic_name`),
        INDEX `idx_barcode` (`barcode`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 4. Batch Inventory Master
    "CREATE TABLE IF NOT EXISTS `mst_batches` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 5. Store-wise Current Stock Level
    "CREATE TABLE IF NOT EXISTS `mst_stock` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 6. Suppliers / Distributors Master
    "CREATE TABLE IF NOT EXISTS `mst_suppliers` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 7. Purchase Inward Master
    "CREATE TABLE IF NOT EXISTS `mst_purchases` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 8. Purchase Inward Items
    "CREATE TABLE IF NOT EXISTS `mst_purchase_items` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 9. Sales Invoices Master (POS & Dispensing)
    "CREATE TABLE IF NOT EXISTS `mst_sales` (
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
        `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `taxable_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `cgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `sgst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `igst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `round_off` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        `net_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        `payment_mode` VARCHAR(30) DEFAULT 'Cash' COMMENT 'Cash, UPI, Card, Mixed, IPD_Credit',
        `cash_paid` DECIMAL(10,2) DEFAULT 0.00,
        `upi_paid` DECIMAL(10,2) DEFAULT 0.00,
        `card_paid` DECIMAL(10,2) DEFAULT 0.00,
        `credit_amount` DECIMAL(10,2) DEFAULT 0.00,
        `payment_reference` VARCHAR(100) DEFAULT NULL,
        `ipd_charge_id` INT DEFAULT NULL COMMENT 'Reference in ipd_invoice_item if billed to IPD running bill',
        `schedule_h1_flag` TINYINT(1) DEFAULT 0,
        `status` VARCHAR(30) DEFAULT 'completed' COMMENT 'completed, cancelled, refunded',
        `created_by` INT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_store_date` (`store_id`, `sale_date`),
        INDEX `idx_uhid` (`uhid`),
        INDEX `idx_opd` (`opd_id`),
        INDEX `idx_ipd` (`ipd_id`),
        INDEX `idx_invoice` (`invoice_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 10. Sales Items Breakdown
    "CREATE TABLE IF NOT EXISTS `mst_sales_items` (
        `sale_item_id` INT AUTO_INCREMENT PRIMARY KEY,
        `sale_id` INT NOT NULL,
        `item_id` INT NOT NULL,
        `batch_id` INT NOT NULL,
        `batch_no` VARCHAR(100) NOT NULL,
        `expiry_date` DATE NOT NULL,
        `qty` INT NOT NULL DEFAULT 1 COMMENT 'Quantity in basic loose units dispensed',
        `unit_mrp` DECIMAL(10,2) NOT NULL,
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 11. Multi-Building Inter-Store Transfers & Indents
    "CREATE TABLE IF NOT EXISTS `mst_transfers` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 12. Transfer Items
    "CREATE TABLE IF NOT EXISTS `mst_transfer_items` (
        `transfer_item_id` INT AUTO_INCREMENT PRIMARY KEY,
        `transfer_id` INT NOT NULL,
        `item_id` INT NOT NULL,
        `batch_id` INT DEFAULT NULL,
        `requested_qty` INT NOT NULL,
        `dispatched_qty` INT DEFAULT 0,
        `received_qty` INT DEFAULT 0,
        INDEX `idx_transfer` (`transfer_id`),
        INDEX `idx_item` (`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 13. Chart of Accounts for Indian Pharmacy Double-Entry
    "CREATE TABLE IF NOT EXISTS `mst_account_heads` (
        `head_id` INT AUTO_INCREMENT PRIMARY KEY,
        `head_code` VARCHAR(50) NOT NULL UNIQUE,
        `head_name` VARCHAR(150) NOT NULL,
        `head_type` VARCHAR(50) NOT NULL COMMENT 'Asset, Liability, Equity, Income, Expense',
        `parent_id` INT DEFAULT NULL,
        `is_system` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 14. Ledger Entries / Journal Vouchers
    "CREATE TABLE IF NOT EXISTS `mst_ledger_entries` (
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
        `created_by` INT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_store_date` (`store_id`, `voucher_date`),
        INDEX `idx_head` (`account_head_id`),
        INDEX `idx_voucher` (`voucher_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // 15. Stock Audit & Adjustments (Physical Verification, Expiry Dump, Opening Stock)
    "CREATE TABLE IF NOT EXISTS `mst_stock_audit` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $q) {
    if (!$db->query($q)) {
        echo "Error executing table creation: " . $db->error . "\n";
        exit(1);
    }
}
echo "All 15 Medical Store (mst_*) tables created successfully.\n";

// Seed System Account Heads
$systemHeads = [
    ['1001', 'Cash in Hand', 'Asset'],
    ['1002', 'Bank Account / UPI Clearing', 'Asset'],
    ['1003', 'Stock in Hand / Inventory Valuation', 'Asset'],
    ['1004', 'Sundry Debtors / Patient Receivables', 'Asset'],
    ['1005', 'IPD Credit Hospital Running Ledger', 'Asset'],
    ['2001', 'Sundry Creditors / Supplier Accounts', 'Liability'],
    ['2002', 'Output CGST Payable', 'Liability'],
    ['2003', 'Output SGST Payable', 'Liability'],
    ['2004', 'Output IGST Payable', 'Liability'],
    ['1006', 'Input CGST Credit', 'Asset'],
    ['1007', 'Input SGST Credit', 'Asset'],
    ['1008', 'Input IGST Credit', 'Asset'],
    ['3001', 'Pharmacy Sales Account (Taxable)', 'Income'],
    ['3002', 'Pharmacy Sales Account (Exempt)', 'Income'],
    ['4001', 'Cost of Goods Sold / Purchase Inward', 'Expense'],
    ['4002', 'Discounts Allowed on Sales', 'Expense'],
    ['3003', 'Discounts Received on Purchases', 'Income'],
    ['4003', 'Medicine Expiry & Breakage Loss', 'Expense'],
    ['3004', 'Opening Stock Capital Balance', 'Equity']
];

$headStmt = $db->prepare("INSERT IGNORE INTO `mst_account_heads` (`head_code`, `head_name`, `head_type`, `is_system`) VALUES (?, ?, ?, 1)");
foreach ($systemHeads as $h) {
    $headStmt->bind_param("sss", $h[0], $h[1], $h[2]);
    $headStmt->execute();
}
echo "System Chart of Accounts seeded.\n";

// Seed Default Stores (Central Pharmacy + OPD Building Counter)
$checkStore = $db->query("SELECT COUNT(*) as cnt FROM `mst_stores`");
$storeCount = $checkStore->fetch_assoc()['cnt'];

if ($storeCount == 0) {
    $store1 = [
        'store_code' => 'ST-MAIN',
        'store_name' => 'City Hospital Central Pharmacy',
        'building_name' => 'Main Hospital Block A',
        'floor_no' => 'Ground Floor',
        'room_no' => 'Room 101',
        'is_main_store' => 1,
        'drug_license_no_20b' => 'DL-20B-DEL-10928',
        'drug_license_no_21b' => 'DL-21B-DEL-10929',
        'gstin' => '07AAAAA0000A1Z5',
        'pan_no' => 'AAAAA0000A',
        'fssai_no' => '10020011000123',
        'state_code' => '07',
        'state_name' => 'Delhi',
        'registered_pharmacist_name' => 'Rajesh Kumar, B.Pharm',
        'pharmacist_reg_no' => 'DPC-Reg-45892',
        'contact_phone' => '+91 9876543210',
        'contact_email' => 'pharmacy@cityhospital.com',
        'address' => 'Plot 12, Medical Enclave, Main Hospital Block, Delhi',
        'invoice_prefix' => 'MAIN/26-27/',
        'next_invoice_no' => 1001,
        'upi_id' => 'cityhospital@upi',
        'terms_conditions' => '1. Goods once sold will be returned as per Drug Rules within 7 days with bill.\n2. Keep medicines stored below 25°C away from direct sunlight.'
    ];

    $store2 = [
        'store_code' => 'ST-OPD-B2',
        'store_name' => 'City Medicos - OPD Building Counter',
        'building_name' => 'OPD & Diagnostic Block B',
        'floor_no' => '1st Floor',
        'room_no' => 'Counter 2',
        'is_main_store' => 0,
        'drug_license_no_20b' => 'DL-20B-DEL-11450',
        'drug_license_no_21b' => 'DL-21B-DEL-11451',
        'gstin' => '07BBBBB1111B2Z8',
        'pan_no' => 'BBBBB1111B',
        'fssai_no' => '10020011000456',
        'state_code' => '07',
        'state_name' => 'Delhi',
        'registered_pharmacist_name' => 'Sunil Sharma, D.Pharm',
        'pharmacist_reg_no' => 'DPC-Reg-67210',
        'contact_phone' => '+91 9811223344',
        'contact_email' => 'opdpharmacy@cityhospital.com',
        'address' => 'OPD Wing, Block B, 1st Floor, Medical Enclave, Delhi',
        'invoice_prefix' => 'OPD/26-27/',
        'next_invoice_no' => 5001,
        'upi_id' => 'citymedicos@upi',
        'terms_conditions' => '1. Check expiry and batch at counter.\n2. No exchange on Schedule H/H1 drugs without valid prescription.'
    ];

    $insertStore = $db->prepare("INSERT INTO `mst_stores` (
        `store_code`, `store_name`, `building_name`, `floor_no`, `room_no`, `is_main_store`,
        `drug_license_no_20b`, `drug_license_no_21b`, `gstin`, `pan_no`, `fssai_no`,
        `state_code`, `state_name`, `registered_pharmacist_name`, `pharmacist_reg_no`,
        `contact_phone`, `contact_email`, `address`, `invoice_prefix`, `next_invoice_no`, `upi_id`, `terms_conditions`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ([$store1, $store2] as $s) {
        $insertStore->bind_param(
            "sssssisssssssssssssiss",
            $s['store_code'], $s['store_name'], $s['building_name'], $s['floor_no'], $s['room_no'], $s['is_main_store'],
            $s['drug_license_no_20b'], $s['drug_license_no_21b'], $s['gstin'], $s['pan_no'], $s['fssai_no'],
            $s['state_code'], $s['state_name'], $s['registered_pharmacist_name'], $s['pharmacist_reg_no'],
            $s['contact_phone'], $s['contact_email'], $s['address'], $s['invoice_prefix'], $s['next_invoice_no'],
            $s['upi_id'], $s['terms_conditions']
        );
        $insertStore->execute();
    }
    echo "Default Stores (Central Pharmacy + OPD Counter) seeded.\n";
}

// Seed sample standard Indian medicines into mst_items & batches
$checkItems = $db->query("SELECT COUNT(*) as cnt FROM `mst_items`");
if ($checkItems->fetch_assoc()['cnt'] == 0) {
    $sampleItems = [
        ['Augmentin 625 Duo Tablet', 'Amoxycillin (500mg) + Clavulanic Acid (125mg)', 'Tablet', '3004', 12.00, '10 Tablets', 10, 'Schedule H1', 'GSK Pharmaceuticals', '890103000001', 20],
        ['Pan 40 Tablet', 'Pantoprazole (40mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'Schedule H', 'Alkem Laboratories', '890103000002', 30],
        ['Dolo 650 Tablet', 'Paracetamol (650mg)', 'Tablet', '3004', 12.00, '15 Tablets', 15, 'OTC', 'Micro Labs Ltd', '890103000003', 50],
        ['Azithral 500 Tablet', 'Azithromycin (500mg)', 'Tablet', '3004', 12.00, '5 Tablets', 5, 'Schedule H1', 'Alembic Pharmaceuticals', '890103000004', 15],
        ['Monocef 1g Injection', 'Ceftriaxone (1000mg)', 'Injection', '3004', 12.00, '1 Vial', 1, 'Schedule H1', 'Aristo Pharmaceuticals', '890103000005', 25],
        ['Betadine 10% Ointment', 'Povidone Iodine (10% w/w)', 'Ointment', '3004', 12.00, '20 gm Tube', 1, 'OTC', 'Win-Medicare', '890103000006', 15],
        ['Ascoril LS Syrup', 'Levosalbutamol + Ambroxol + Guaifenesin', 'Syrup', '3004', 12.00, '100 ml Bottle', 1, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000007', 20],
        ['Telma 40 Tablet', 'Telmisartan (40mg)', 'Tablet', '3004', 12.00, '30 Tablets', 30, 'Schedule H', 'Glenmark Pharmaceuticals', '890103000008', 25],
        ['Normal Saline 0.9% IV', 'Sodium Chloride (0.9% w/v)', 'Consumable', '3004', 12.00, '500 ml Bottle', 1, 'Schedule H', 'Aculife Healthcare', '890103000009', 40]
    ];

    $itemStmt = $db->prepare("INSERT INTO `mst_items` (`item_name`, `generic_name`, `category`, `hsn_code`, `gst_rate`, `unit_pack`, `units_per_pack`, `drug_schedule`, `manufacturer_name`, `barcode`, `min_reorder_level`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sampleItems as $it) {
        $itemStmt->bind_param("ssssdsisssi", $it[0], $it[1], $it[2], $it[3], $it[4], $it[5], $it[6], $it[7], $it[8], $it[9], $it[10]);
        $itemStmt->execute();
    }
    echo "Sample Indian Medicines Catalog seeded.\n";

    // Seed Batches and Stock for Store 1 and Store 2
    // Batch 1: Augmentin 625 (FEFO test: Batch A expiring sooner, Batch B expiring later)
    $db->query("INSERT INTO `mst_batches` (`store_id`, `item_id`, `batch_no`, `mfg_date`, `expiry_date`, `mrp`, `ptr`, `purchase_rate_net`, `gst_rate`) VALUES
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

    // Stock quantities in basic units
    $db->query("INSERT INTO `mst_stock` (`store_id`, `item_id`, `batch_id`, `current_qty`) VALUES
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
    echo "Initial Sample Batches & Stock seeded.\n";
}

// Seed Sample Supplier
$checkSup = $db->query("SELECT COUNT(*) as cnt FROM `mst_suppliers`");
if ($checkSup->fetch_assoc()['cnt'] == 0) {
    $db->query("INSERT INTO `mst_suppliers` (
        `supplier_name`, `dl_no_20b`, `dl_no_21b`, `gstin`, `pan_no`, `contact_person`,
        `phone`, `email`, `address`, `state_code`, `credit_days`
    ) VALUES (
        'Apex Healthcare Distributors', 'DL-20B-DEL-98441', 'DL-21B-DEL-98442', '07AACCA1234F1Z9',
        'AACCA1234F', 'Ramesh Gupta', '+91 9811002233', 'sales@apexhealth.in',
        '45, Wholesale Medicine Market, Bhagirath Palace, Chandni Chowk, Delhi', '07', 30
    )");
    echo "Sample Supplier seeded.\n";
}

$db->close();
echo "=== Medical Store Migration Completed Successfully ===\n";
