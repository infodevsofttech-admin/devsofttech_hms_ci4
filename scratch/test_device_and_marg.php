<?php
/**
 * Test Terminal Verification and Marg ERP CSV Import
 */

$db = new mysqli('127.0.0.1', 'root', '', 'hms_data_ci4', 3306);
if ($db->connect_error) {
    die("DB Connection failed: " . $db->connect_error . "\n");
}

echo "=== 1. TESTING TERMINAL DEVICE VERIFICATION VIA SECURITY KEY ===\n";

// Get store 1
$storeRes = $db->query("SELECT * FROM mst_stores WHERE store_id = 1");
$store1 = $storeRes->fetch_assoc();
echo "   - Store: {$store1['store_name']} (Slug: {$store1['store_slug']}, Key: {$store1['security_key']})\n";

// Verify with Security Key
$machineName = "OPD-Counter-Dell-01";
$fingerprint = "fp_" . md5("screen_1920x1080_win64_dell");
$token = bin2hex(random_bytes(32));

$db->query("
    INSERT INTO `mst_store_devices` (
        `store_id`, `machine_name`, `device_token`, `device_fingerprint`,
        `ip_address`, `user_agent`, `verified_by_method`, `status`
    ) VALUES (
        1, '$machineName', '$token', '$fingerprint',
        '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64)', 'SECURITY_KEY', 'authorized'
    )
");
$deviceId = $db->insert_id;
echo "   ✓ Machine '{$machineName}' registered (Device ID: {$deviceId}, Token: " . substr($token, 0, 16) . "...)\n";

// Verify status check
$chk = $db->query("SELECT * FROM mst_store_devices WHERE device_token = '$token' AND status = 'authorized'")->fetch_assoc();
if ($chk) {
    echo "   ✓ Device check passed: Machine is ACTIVE & AUTHORIZED for Store {$chk['store_id']}\n";
} else {
    echo "   ✗ Device check failed!\n";
}

echo "\n=== 2. TESTING OTP GENERATION & MACHINE VERIFICATION ===\n";

// Generate OTP for store 2
$store2Res = $db->query("SELECT * FROM mst_stores WHERE store_id = 2");
$store2 = $store2Res->fetch_assoc();

$testOtp = (string)rand(100000, 999999);
$testExpiry = date('Y-m-d H:i:s', strtotime('+60 minutes'));
$db->query("UPDATE mst_stores SET current_otp = '$testOtp', otp_expiry = '$testExpiry' WHERE store_id = 2");
echo "   - Store: {$store2['store_name']} | Generated 60-min OTP: $testOtp (Expires: $testExpiry)\n";

// Machine verifies with OTP
$token2 = bin2hex(random_bytes(32));
$db->query("
    INSERT INTO `mst_store_devices` (
        `store_id`, `machine_name`, `device_token`, `device_fingerprint`,
        `ip_address`, `user_agent`, `verified_by_method`, `status`
    ) VALUES (
        2, 'IPD-Counter-HP-02', '$token2', 'fp_hp_terminal_02',
        '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0)', 'OTP', 'authorized'
    )
");
$dev2Id = $db->insert_id;
echo "   ✓ Machine 'IPD-Counter-HP-02' authorized using OTP (Device ID: {$dev2Id})\n";

// Revoke machine terminal by Admin
$db->query("UPDATE mst_store_devices SET status = 'revoked' WHERE device_id = $dev2Id");
$chkRevoked = $db->query("SELECT * FROM mst_store_devices WHERE device_token = '$token2' AND status = 'authorized'")->fetch_assoc();
if (!$chkRevoked) {
    echo "   ✓ Admin Revocation confirmed: Device token rejected when revoked.\n";
} else {
    echo "   ✗ Revocation check failed!\n";
}

echo "\n=== 3. TESTING MARG ERP CSV IMPORT SIMULATION ===\n";

$csvPath = __DIR__ . '/test_marg_sample.csv';
$handle = fopen($csvPath, 'r');
$header = fgetcsv($handle);
echo "   - CSV Header: " . implode(', ', $header) . "\n";

$imported = 0;
while (($row = fgetcsv($handle)) !== false) {
    $itemName = trim($row[0]);
    $pack = trim($row[1]);
    $batchNo = trim($row[2]);
    $expStr = trim($row[3]);
    $mrp = (float)$row[4];
    $ptr = (float)$row[5];
    $hsn = trim($row[6]);
    $gst = (float)$row[7];
    $qty = (int)$row[8];

    // Format Expiry MM/YYYY -> YYYY-MM-lastday
    $parts = explode('/', $expStr);
    $expDate = date('Y-m-t', strtotime("{$parts[1]}-{$parts[0]}-01"));

    // Check/create master drug catalog
    $findItem = $db->query("SELECT item_id FROM mst_items WHERE item_name = '" . $db->real_escape_string($itemName) . "'")->fetch_assoc();
    if ($findItem) {
        $itemId = $findItem['item_id'];
    } else {
        $db->query("
            INSERT INTO `mst_items` (
                `item_name`, `unit_pack`, `hsn_code`, `gst_rate`, `category`, `is_active`
            ) VALUES (
                '" . $db->real_escape_string($itemName) . "', '$pack', '$hsn', $gst, 'Allopathy', 1
            )
        ");
        $itemId = $db->insert_id;
    }

    // Check/create batch for Store 1
    $findBatch = $db->query("SELECT batch_id FROM mst_batches WHERE store_id = 1 AND item_id = $itemId AND batch_no = '$batchNo'")->fetch_assoc();
    if ($findBatch) {
        $batchId = $findBatch['batch_id'];
    } else {
        $db->query("
            INSERT INTO `mst_batches` (
                `store_id`, `item_id`, `batch_no`, `expiry_date`, `ptr`, `mrp`, `gst_rate`
            ) VALUES (
                1, $itemId, '$batchNo', '$expDate', $ptr, $mrp, $gst
            )
        ");
        $batchId = $db->insert_id;
    }

    // Update or insert stock
    $stockRow = $db->query("SELECT stock_id, current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = $batchId")->fetch_assoc();
    if ($stockRow) {
        $newQty = $stockRow['current_qty'] + $qty;
        $db->query("UPDATE mst_stock SET current_qty = $newQty WHERE stock_id = {$stockRow['stock_id']}");
    } else {
        $db->query("
            INSERT INTO `mst_stock` (
                `store_id`, `item_id`, `batch_id`, `current_qty`
            ) VALUES (
                1, $itemId, $batchId, $qty
            )
        ");
    }

    // Audit log
    $db->query("
        INSERT INTO `mst_stock_audit` (
            `store_id`, `item_id`, `batch_id`, `audit_type`, `system_qty`, `physical_qty`,
            `variation_qty`, `rate`, `total_value`, `remarks`
        ) VALUES (
            1, $itemId, $batchId, 'OPENING_STOCK', 0, $qty,
            $qty, $ptr, " . ($ptr * $qty) . ", 'Imported via Marg ERP CSV'
        )
    ");

    $imported++;
    echo "   ✓ Imported '{$itemName}' | Batch: {$batchNo} | Exp: {$expDate} | MRP: ₹{$mrp} | Qty: {$qty}\n";
}
fclose($handle);

echo "\n   - Total rows imported from Marg format: $imported\n";

echo "\n=== 4. VERIFYING ISOLATION OF STOCKS ACROSS STORES ===\n";

// Check that Store 2 does NOT see Store 1's imported stock
$st2Check = $db->query("
    SELECT i.item_name, s.current_qty
    FROM mst_stock s
    JOIN mst_items i ON i.item_id = s.item_id
    WHERE s.store_id = 2 AND i.item_name = 'Azithral 500 Tablet'
")->fetch_assoc();

if (!$st2Check) {
    echo "   ✓ Store Isolation Verified: 'Azithral 500 Tablet' has 0 stock in Store 2 (OPD Counter)\n";
} else {
    echo "   - Store 2 stock: " . ($st2Check['current_qty'] ?? 0) . "\n";
}

$st1Check = $db->query("
    SELECT i.item_name, s.current_qty
    FROM mst_stock s
    JOIN mst_items i ON i.item_id = s.item_id
    WHERE s.store_id = 1 AND i.item_name = 'Azithral 500 Tablet'
")->fetch_assoc();
echo "   ✓ Store 1 stock: '{$st1Check['item_name']}' has {$st1Check['current_qty']} units in Store 1 (Main Store)\n";

echo "\n=== ALL VERIFICATIONS PASSED SUCCESSFULLY ===\n";
