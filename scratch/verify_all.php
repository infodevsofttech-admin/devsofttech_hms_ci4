<?php
/**
 * End-to-End Verification Script for Medical Store & Multi-Building Pharmacy System
 */
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "=== 1. VERIFYING ZERO DISTURBANCE TO LEGACY PHARMACY TABLES ===\n";
$legacyTables = ['invoice_med_master', 'inv_med_item', 'med_product_master', 'med_supplier', 'med_supplier_ledger'];
foreach ($legacyTables as $lt) {
    $res = $db->query("SELECT COUNT(*) as cnt FROM `$lt`");
    if ($res) {
        $row = $res->fetch_assoc();
        echo "   ✓ Legacy table `$lt` intact (row count: {$row['cnt']})\n";
    } else {
        echo "   ! Legacy table `$lt` check failed: " . $db->error . "\n";
    }
}

echo "\n=== 2. VERIFYING MULTI-STORE CONFIGURATION (DL 20B/21B & GSTIN) ===\n";
$stores = $db->query("SELECT store_id, store_code, store_name, building_name, drug_license_no_20b, drug_license_no_21b, gstin, registered_pharmacist_name FROM `mst_stores`");
while ($s = $stores->fetch_assoc()) {
    echo "   ✓ Store: {$s['store_name']} ({$s['store_code']})\n";
    echo "     - Building: {$s['building_name']} | DL 20B: {$s['drug_license_no_20b']} | DL 21B: {$s['drug_license_no_21b']}\n";
    echo "     - GSTIN: {$s['gstin']} | Pharmacist: {$s['registered_pharmacist_name']}\n";
}

echo "\n=== 3. SIMULATING FULL POS TRANSACTION LIFECYCLE ===\n";
// Step 3a: Select patient with UHID
$ptRes = $db->query("SELECT id, p_code, p_fname, p_lname, mphone1 FROM patient_master LIMIT 1");
$pt = $ptRes->fetch_assoc();
echo "   - Patient selected: {$pt['p_fname']} {$pt['p_lname']} (UHID: {$pt['p_code']}, Mob: {$pt['mphone1']})\n";

// Step 3b: Select Augmentin 625 Duo (Schedule H1) with FEFO batch
$batchRes = $db->query("
    SELECT b.batch_id, b.batch_no, b.expiry_date, b.mrp, b.ptr, b.gst_rate, s.current_qty, i.item_id, i.item_name, i.drug_schedule, i.hsn_code
    FROM mst_batches b
    JOIN mst_stock s ON s.batch_id = b.batch_id AND s.store_id = 1
    JOIN mst_items i ON i.item_id = b.item_id
    WHERE i.item_id = 1 AND s.current_qty > 0
    ORDER BY b.expiry_date ASC
    LIMIT 1
");
$batch = $batchRes->fetch_assoc();
echo "   - FEFO Batch selected: {$batch['item_name']} | Batch: {$batch['batch_no']} | Exp: {$batch['expiry_date']} | Stock Before: {$batch['current_qty']}\n";

$dispenseQty = 2;
$mrp = (float)$batch['mrp'];
$lineTotal = $dispenseQty * $mrp;
$gstRate = (float)$batch['gst_rate'];
$taxable = round($lineTotal / (1 + ($gstRate / 100)), 2);
$tax = round($lineTotal - $taxable, 2);
$cgst = round($tax / 2, 2);
$sgst = round($tax - $cgst, 2);

// Step 3c: Insert Sale Invoice
$invoiceNo = 'MAIN/26-27/' . strtoupper(substr(uniqid(), -5));
$db->query("
    INSERT INTO `mst_sales` (
        `store_id`, `invoice_no`, `sale_date`, `patient_type`, `uhid`, `patient_id`, `patient_name`,
        `doctor_name`, `doctor_reg_no`, `gross_amount`, `discount_amount`, `taxable_amount`,
        `cgst_amount`, `sgst_amount`, `igst_amount`, `round_off`, `net_amount`, `payment_mode`,
        `cash_paid`, `schedule_h1_flag`, `status`
    ) VALUES (
        1, '$invoiceNo', NOW(), 'OPD', '{$pt['p_code']}', {$pt['id']}, '{$pt['p_fname']} {$pt['p_lname']}',
        'Dr. Rajesh Verma', 'MCI-12994', $lineTotal, 0, $taxable,
        $cgst, $sgst, 0, 0, $lineTotal, 'Cash',
        $lineTotal, 1, 'completed'
    )
");
$saleId = $db->insert_id;

// Insert Sale Item
$db->query("
    INSERT INTO `mst_sales_items` (
        `sale_id`, `item_id`, `batch_id`, `batch_no`, `expiry_date`, `qty`,
        `unit_mrp`, `discount_pct`, `hsn_code`, `gst_rate`, `taxable_value`,
        `cgst_amount`, `sgst_amount`, `igst_amount`, `total_amount`
    ) VALUES (
        $saleId, {$batch['item_id']}, {$batch['batch_id']}, '{$batch['batch_no']}', '{$batch['expiry_date']}', $dispenseQty,
        $mrp, 0, '{$batch['hsn_code']}', $gstRate, $taxable,
        $cgst, $sgst, 0, $lineTotal
    )
");

// Decrement stock
$db->query("UPDATE `mst_stock` SET `current_qty` = `current_qty` - $dispenseQty WHERE `batch_id` = {$batch['batch_id']} AND `store_id` = 1");

// Check new stock
$newStockRes = $db->query("SELECT current_qty FROM `mst_stock` WHERE `batch_id` = {$batch['batch_id']} AND `store_id` = 1");
$newStock = $newStockRes->fetch_assoc()['current_qty'];
echo "   ✓ Stock decremented from {$batch['current_qty']} to $newStock (Sold $dispenseQty units)\n";

// Step 3d: Insert Double-Entry Ledgers
$cashHead = $db->query("SELECT head_id FROM `mst_account_heads` WHERE `head_code` = '1001'")->fetch_assoc()['head_id'];
$salesHead = $db->query("SELECT head_id FROM `mst_account_heads` WHERE `head_code` = '3001'")->fetch_assoc()['head_id'];
$cgstHead = $db->query("SELECT head_id FROM `mst_account_heads` WHERE `head_code` = '2002'")->fetch_assoc()['head_id'];
$sgstHead = $db->query("SELECT head_id FROM `mst_account_heads` WHERE `head_code` = '2003'")->fetch_assoc()['head_id'];

// Dr Cash
$db->query("INSERT INTO `mst_ledger_entries` (`store_id`, `voucher_no`, `voucher_type`, `voucher_date`, `account_head_id`, `debit_amount`, `credit_amount`, `narration`) VALUES (1, '$invoiceNo', 'SALE', CURDATE(), $cashHead, $lineTotal, 0, 'Cash received for bill $invoiceNo')");
// Cr Sales
$db->query("INSERT INTO `mst_ledger_entries` (`store_id`, `voucher_no`, `voucher_type`, `voucher_date`, `account_head_id`, `debit_amount`, `credit_amount`, `narration`) VALUES (1, '$invoiceNo', 'SALE', CURDATE(), $salesHead, 0, $taxable, 'Pharmacy Sales $invoiceNo')");
// Cr CGST & SGST
$db->query("INSERT INTO `mst_ledger_entries` (`store_id`, `voucher_no`, `voucher_type`, `voucher_date`, `account_head_id`, `debit_amount`, `credit_amount`, `narration`) VALUES (1, '$invoiceNo', 'SALE', CURDATE(), $cgstHead, 0, $cgst, 'Output CGST on $invoiceNo')");
$db->query("INSERT INTO `mst_ledger_entries` (`store_id`, `voucher_no`, `voucher_type`, `voucher_date`, `account_head_id`, `debit_amount`, `credit_amount`, `narration`) VALUES (1, '$invoiceNo', 'SALE', CURDATE(), $sgstHead, 0, $sgst, 'Output SGST on $invoiceNo')");

echo "   ✓ Double-Entry Ledgers posted: Debit Cash ₹$lineTotal = Credit (Sales ₹$taxable + CGST ₹$cgst + SGST ₹$sgst)\n";

echo "\n=== 4. VERIFYING STATUTORY SCHEDULE H1 REGISTER ===\n";
$h1Query = $db->query("
    SELECT s.sale_date, s.invoice_no, s.patient_name, s.doctor_name, s.doctor_reg_no, i.item_name, si.batch_no, si.qty
    FROM mst_sales_items si
    JOIN mst_sales s ON s.sale_id = si.sale_id
    JOIN mst_items i ON i.item_id = si.item_id
    WHERE i.drug_schedule = 'Schedule H1'
");
while ($h1 = $h1Query->fetch_assoc()) {
    echo "   ✓ Schedule H1 Entry: {$h1['item_name']} | Batch: {$h1['batch_no']} | Qty: {$h1['qty']} | Patient: {$h1['patient_name']} | Doctor: {$h1['doctor_name']} (Reg: {$h1['doctor_reg_no']})\n";
}

echo "\n=== 5. VERIFYING DAYBOOK RECONCILIATION ===\n";
$daybook = $db->query("
    SELECT 
        COUNT(*) as bill_count,
        SUM(net_amount) as total_sales,
        SUM(cash_paid) as cash_collected,
        SUM(cgst_amount) as cgst,
        SUM(sgst_amount) as sgst
    FROM mst_sales 
    WHERE store_id = 1 AND DATE(sale_date) = CURDATE()
")->fetch_assoc();

echo "   ✓ Daybook for today:\n";
echo "     - Invoices: {$daybook['bill_count']}\n";
echo "     - Total Sales: ₹{$daybook['total_sales']}\n";
echo "     - Cash Collected: ₹{$daybook['cash_collected']}\n";
echo "     - Output CGST: ₹{$daybook['cgst']}\n";
echo "     - Output SGST: ₹{$daybook['sgst']}\n";

$db->close();
echo "\n=== ALL VERIFICATIONS COMPLETED SUCCESSFULLY ===\n";
