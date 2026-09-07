<?php
/**
 * Test script for Retail Unit (Loose Tablet/Capsule) and Strip Dispensing
 */
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "=== 1. CHECKING DOLO 650 ITEM & BATCH DETAILS ===\n";
$res = $db->query("
    SELECT b.batch_id, b.item_id, b.batch_no, b.mrp, b.store_id, i.item_name, i.units_per_pack, s.current_qty
    FROM mst_batches b
    JOIN mst_items i ON i.item_id = b.item_id
    JOIN mst_stock s ON s.batch_id = b.batch_id AND s.store_id = b.store_id
    WHERE b.store_id = 1 AND i.item_name LIKE '%Dolo 650%'
    LIMIT 1
");

$dolo = $res->fetch_assoc();
if (!$dolo) {
    die("Dolo 650 not found in store 1\n");
}

echo "Found: {$dolo['item_name']} (Batch {$dolo['batch_no']}, MRP ₹{$dolo['mrp']}, Pack of {$dolo['units_per_pack']} Tabs)\n";
echo "Initial Stock in Database: {$dolo['current_qty']} Tablets\n";

$initialStock = (int)$dolo['current_qty'];
$batchId = (int)$dolo['batch_id'];
$itemId = (int)$dolo['item_id'];
$mrp = (float)$dolo['mrp'];
$upp = (int)$dolo['units_per_pack'];
$perUnitMrp = round($mrp / $upp, 2);

function callSaleApi($payload) {
    $ch = curl_init('http://localhost:8080/api/v1/medical-store/sales/save');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($resp, true), 'raw' => $resp];
}

function getInvoiceApi($saleId) {
    $ch = curl_init("http://localhost:8080/api/v1/medical-store/sales/invoice/{$saleId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// TEST A: Loose 5 Tablets
echo "\n=== TEST A: DISPENSING 5 LOOSE TABLETS ===\n";
$payloadA = [
    'store_id' => 1,
    'patient_type' => 'Walkin',
    'patient_name' => 'Unit Test Patient (Loose Tabs)',
    'payment_mode' => 'Cash',
    'items' => [
        [
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'sell_unit' => 'Tablet',
            'units_per_pack' => $upp,
            'loose_qty' => 5,
            'qty' => 5,
            'total_units' => 5,
            'effective_rate' => $perUnitMrp
        ]
    ]
];

$resA = callSaleApi($payloadA);
echo "Sale Response HTTP {$resA['code']}: " . ($resA['data']['message'] ?? $resA['raw']) . "\n";
if ($resA['code'] === 200 && $resA['data']['status'] == 1) {
    $saleIdA = $resA['data']['sale_id'];
    $invA = getInvoiceApi($saleIdA);
    $itemA = $invA['items'][0];
    echo "  ✓ Invoice: {$invA['sale']['invoice_no']}\n";
    echo "  ✓ Net Amount: ₹{$invA['sale']['net_amount']} (Gross: ₹{$invA['sale']['gross_amount']})\n";
    echo "  ✓ Item Sell Unit: {$itemA['sell_unit']}, Units/Pack: {$itemA['units_per_pack']}, Total Units: {$itemA['total_units']}\n";
    echo "  ✓ Loose Qty: {$itemA['loose_qty']}, Unit Price: ₹{$itemA['unit_price']}/tab, Total Amount: ₹{$itemA['total_amount']}\n";
    
    // Check stock
    $stockRow = $db->query("SELECT current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = $batchId")->fetch_assoc();
    $newStock = (int)$stockRow['current_qty'];
    $diff = $initialStock - $newStock;
    echo "  ✓ Stock: Initial=$initialStock -> Current=$newStock (Decremented by $diff tablets, expected 5)\n";
    if ($diff === 5) {
        echo "  >>> TEST A PASSED: Stock decremented by exactly 5 tablets!\n";
    } else {
        echo "  >>> TEST A FAILED: Stock decrement mismatch!\n";
    }
    $initialStock = $newStock;
} else {
    echo "  >>> TEST A FAILED to complete sale.\n";
}

// TEST B: Whole Strip (1 Strip = 15 Tablets)
echo "\n=== TEST B: DISPENSING 1 WHOLE STRIP (15 TABLETS) ===\n";
$payloadB = [
    'store_id' => 1,
    'patient_type' => 'Walkin',
    'patient_name' => 'Unit Test Patient (Whole Strip)',
    'payment_mode' => 'Cash',
    'items' => [
        [
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'sell_unit' => 'Strip',
            'units_per_pack' => $upp,
            'strip_qty' => 1,
            'qty' => 1,
            'total_units' => $upp,
            'effective_rate' => $mrp
        ]
    ]
];

$resB = callSaleApi($payloadB);
echo "Sale Response HTTP {$resB['code']}: " . ($resB['data']['message'] ?? $resB['raw']) . "\n";
if ($resB['code'] === 200 && $resB['data']['status'] == 1) {
    $saleIdB = $resB['data']['sale_id'];
    $invB = getInvoiceApi($saleIdB);
    $itemB = $invB['items'][0];
    echo "  ✓ Invoice: {$invB['sale']['invoice_no']}\n";
    echo "  ✓ Net Amount: ₹{$invB['sale']['net_amount']} (Gross: ₹{$invB['sale']['gross_amount']})\n";
    echo "  ✓ Item Sell Unit: {$itemB['sell_unit']}, Units/Pack: {$itemB['units_per_pack']}, Total Units: {$itemB['total_units']}\n";
    echo "  ✓ Qty (Strips): {$itemB['qty']}, Strip MRP: ₹{$itemB['unit_mrp']}, Total Amount: ₹{$itemB['total_amount']}\n";
    
    // Check stock
    $stockRow = $db->query("SELECT current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = $batchId")->fetch_assoc();
    $newStock = (int)$stockRow['current_qty'];
    $diff = $initialStock - $newStock;
    echo "  ✓ Stock: Initial=$initialStock -> Current=$newStock (Decremented by $diff tablets, expected 15)\n";
    if ($diff === 15) {
        echo "  >>> TEST B PASSED: Stock decremented by exactly 15 tablets (1 strip)!\n";
    } else {
        echo "  >>> TEST B FAILED: Stock decrement mismatch!\n";
    }
    $initialStock = $newStock;
} else {
    echo "  >>> TEST B FAILED to complete sale.\n";
}

// TEST C: Combo (1 Strip + 3 Loose Tablets = 18 Tablets)
echo "\n=== TEST C: DISPENSING COMBO (1 STRIP + 3 LOOSE TABLETS = 18 TABLETS) ===\n";
$payloadC = [
    'store_id' => 1,
    'patient_type' => 'Walkin',
    'patient_name' => 'Unit Test Patient (Combo)',
    'payment_mode' => 'Cash',
    'items' => [
        [
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'sell_unit' => 'Combo',
            'units_per_pack' => $upp,
            'strip_qty' => 1,
            'loose_qty' => 3,
            'qty' => 1,
            'total_units' => $upp + 3,
            'effective_rate' => $mrp
        ]
    ]
];

$resC = callSaleApi($payloadC);
echo "Sale Response HTTP {$resC['code']}: " . ($resC['data']['message'] ?? $resC['raw']) . "\n";
if ($resC['code'] === 200 && $resC['data']['status'] == 1) {
    $saleIdC = $resC['data']['sale_id'];
    $invC = getInvoiceApi($saleIdC);
    $itemC = $invC['items'][0];
    echo "  ✓ Invoice: {$invC['sale']['invoice_no']}\n";
    echo "  ✓ Net Amount: ₹{$invC['sale']['net_amount']} (Gross: ₹{$invC['sale']['gross_amount']})\n";
    echo "  ✓ Item Sell Unit: {$itemC['sell_unit']}, Units/Pack: {$itemC['units_per_pack']}, Total Units: {$itemC['total_units']}\n";
    echo "  ✓ Strip Qty: {$itemC['qty']}, Loose Qty: {$itemC['loose_qty']}, Total Amount: ₹{$itemC['total_amount']}\n";
    
    // Check stock
    $stockRow = $db->query("SELECT current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = $batchId")->fetch_assoc();
    $newStock = (int)$stockRow['current_qty'];
    $diff = $initialStock - $newStock;
    echo "  ✓ Stock: Initial=$initialStock -> Current=$newStock (Decremented by $diff tablets, expected 18)\n";
    if ($diff === 18) {
        echo "  >>> TEST C PASSED: Stock decremented by exactly 18 tablets (1 strip + 3 loose)!\n";
    } else {
        echo "  >>> TEST C FAILED: Stock decrement mismatch!\n";
    }
} else {
    echo "  >>> TEST C FAILED to complete sale.\n";
}

echo "\n=== ALL TESTS COMPLETED ===\n";
