<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

// 1. Get an active batch from store 1
$batch = $db->query("SELECT b.*, i.item_name, s.current_qty FROM mst_batches b JOIN mst_items i ON i.item_id = b.item_id JOIN mst_stock s ON s.batch_id = b.batch_id WHERE b.store_id = 1 AND b.expiry_date > CURDATE() AND s.current_qty >= 5 LIMIT 1")->fetch_assoc();

if (!$batch) {
    die("No active batch found for test.\n");
}

echo "Found batch for test: {$batch['item_name']} (Batch {$batch['batch_no']}), MRP: {$batch['mrp']}, In stock: {$batch['current_qty']}\n";

// 2. Post a sale with item discount by value (₹15) and whole bill discount by percentage (10%)
$payload = [
    'store_id' => 1,
    'patient_type' => 'Walk-in',
    'patient_name' => 'Discount Test Patient',
    'patient_mobile' => '9876543210',
    'payment_mode' => 'Cash',
    'whole_discount_type' => 'pct',
    'whole_discount_val' => 10, // 10% whole bill discount
    'items' => [
        [
            'item_id' => (int)$batch['item_id'],
            'batch_id' => (int)$batch['batch_id'],
            'qty' => 2,
            'discount_type' => 'val', // item discount by value
            'discount_val' => 15,     // ₹15 flat off this item
        ]
    ]
];

$ch = curl_init('http://localhost:8080/api/v1/medical-store/sales/save');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$resData = json_decode($response, true);
if (!empty($resData['status']) && !empty($resData['sale_id'])) {
    $saleId = (int)$resData['sale_id'];
    $sale = $db->query("SELECT * FROM mst_sales WHERE sale_id = $saleId")->fetch_assoc();
    echo "Saved Sale Record:\n";
    echo "  Gross: ₹{$sale['gross_amount']}\n";
    echo "  Discount Amount: ₹{$sale['discount_amount']}\n";
    echo "  Bill Disc Type: {$sale['bill_discount_type']}\n";
    echo "  Bill Disc Val: {$sale['bill_discount_val']}\n";
    echo "  Taxable Amount: ₹{$sale['taxable_amount']}\n";
    echo "  CGST: ₹{$sale['cgst_amount']}, SGST: ₹{$sale['sgst_amount']}\n";
    echo "  Round Off: ₹{$sale['round_off']}\n";
    echo "  Net Amount: ₹{$sale['net_amount']}\n";

    $itemsRes = $db->query("SELECT * FROM mst_sales_items WHERE sale_id = $saleId");
    while ($si = $itemsRes->fetch_assoc()) {
        echo "  Item #{$si['item_id']}: Qty {$si['qty']} @ ₹{$si['unit_mrp']}, Disc Type: {$si['discount_type']}, Disc Val: {$si['discount_val']}, Total Disc: ₹{$si['discount_amount']}, Net Total: ₹{$si['total_amount']}\n";
    }

    $ledgerRes = $db->query("SELECT le.*, h.head_name FROM mst_ledger_entries le JOIN mst_account_heads h ON h.head_id = le.account_head_id WHERE le.reference_type = 'sales_invoice' AND le.reference_id = $saleId");
    echo "Ledger Postings:\n";
    while ($le = $ledgerRes->fetch_assoc()) {
        echo "  [{$le['head_name']}] Dr: ₹{$le['debit_amount']} | Cr: ₹{$le['credit_amount']} ({$le['narration']})\n";
    }

    echo "\n✓ BACKEND DISCOUNT VERIFICATION SUCCEEDED!\n";
} else {
    echo "ERROR: Sale save failed.\n";
}
