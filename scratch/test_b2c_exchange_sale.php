<?php
/**
 * Automated test script for B2C Retail Pharmacy Same-Invoice Exchange & Return
 */

$baseUrl = 'http://localhost:8080/api/v1/medical-store/';

function makePost($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($resp, true), 'raw' => $resp];
}

function makeGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'data' => json_decode($resp, true), 'raw' => $resp];
}

$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

// 1. Initial Sale of 1 Strip Dolo 650 (15 tablets)
$doloBatch = $db->query("SELECT b.batch_id, b.item_id, b.mrp, s.current_qty 
                         FROM mst_batches b 
                         JOIN mst_stock s ON s.batch_id = b.batch_id AND s.store_id = 1 
                         WHERE b.item_id = 3 LIMIT 1")->fetch_assoc();

$doloInitialQty = (int)$doloBatch['current_qty'];
echo "Initial Dolo 650 stock: $doloInitialQty units\n";

$sale1Payload = [
    'store_id' => 1,
    'patient_name' => 'Exchange Test Patient',
    'patient_mobile' => '9876543210',
    'payment_mode' => 'Cash',
    'items' => [
        [
            'item_id' => 3,
            'batch_id' => (int)$doloBatch['batch_id'],
            'sell_unit' => 'Strip',
            'qty' => 1,
            'discount_pct' => 0
        ]
    ]
];

$sale1Res = makePost($baseUrl . 'sales/save', $sale1Payload);
echo "Sale 1 Response (Dolo 650 Strip):\n";
echo "HTTP Code: " . $sale1Res['code'] . "\n";
print_r($sale1Res['data']);

$sale1Id = $sale1Res['data']['sale_id'] ?? 0;
$sale1Inv = $sale1Res['data']['invoice_no'] ?? '';
assert($sale1Id > 0, "Sale 1 failed");

// 2. Test lookupInvoiceForReturn endpoint
$lookupRes = makeGet($baseUrl . "returns/lookup-invoice?store_id=1&q=" . urlencode($sale1Inv));
echo "\nLookup Invoice Response for $sale1Inv:\n";
echo "HTTP Code: " . $lookupRes['code'] . "\n";
print_r($lookupRes['data']);
assert(!empty($lookupRes['data']['results']), "Lookup returned no results");

// 3. Perform Same-Invoice Return / Exchange:
// Dispense: 1 Strip (10 tabs) Augmentin 625 (+₹204.50)
// Return: 5 Tablets Dolo 650 (-₹11.33)
$augBatch = $db->query("SELECT b.batch_id, b.item_id, b.mrp, s.current_qty 
                        FROM mst_batches b 
                        JOIN mst_stock s ON s.batch_id = b.batch_id AND s.store_id = 1 
                        WHERE b.item_id = 1 LIMIT 1")->fetch_assoc();
$augInitialQty = (int)$augBatch['current_qty'];
echo "\nInitial Augmentin stock: $augInitialQty units\n";

$exchangePayload = [
    'store_id' => 1,
    'patient_name' => 'Exchange Test Patient',
    'patient_mobile' => '9876543210',
    'payment_mode' => 'Cash',
    // New items being purchased
    'items' => [
        [
            'item_id' => 1,
            'batch_id' => (int)$augBatch['batch_id'],
            'sell_unit' => 'Strip',
            'qty' => 1,
            'discount_pct' => 0
        ]
    ],
    // Old items being returned/exchanged on the same bill
    'return_items' => [
        [
            'item_id' => 3,
            'batch_id' => (int)$doloBatch['batch_id'],
            'sell_unit' => 'Tablet',
            'qty' => 5,
            'ref_sale_id' => $sale1Id,
            'ref_invoice_no' => $sale1Inv,
            'return_condition' => 'RESTOCKED',
            'return_reason' => 'Doctor changed medicine to Augmentin'
        ]
    ]
];

$exchangeRes = makePost($baseUrl . 'sales/save', $exchangePayload);
echo "\nExchange Bill Response:\n";
echo "HTTP Code: " . $exchangeRes['code'] . "\n";
print_r($exchangeRes['data']);

$exchangeSaleId = $exchangeRes['data']['sale_id'] ?? 0;
$exchangeInv = $exchangeRes['data']['invoice_no'] ?? '';
assert($exchangeSaleId > 0, "Exchange sale failed");
assert($exchangeRes['data']['is_exchange_bill'] == 1, "is_exchange_bill should be 1");
assert($exchangeRes['data']['return_amount'] > 0, "return_amount should be > 0");

// 4. Verify Stock Movement
$doloAfterQty = (int)$db->query("SELECT current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = " . (int)$doloBatch['batch_id'])->fetch_assoc()['current_qty'];
$augAfterQty  = (int)$db->query("SELECT current_qty FROM mst_stock WHERE store_id = 1 AND batch_id = " . (int)$augBatch['batch_id'])->fetch_assoc()['current_qty'];

echo "\nDolo stock after exchange: $doloAfterQty (expected: " . ($doloInitialQty - 15 + 5) . ")\n";
echo "Augmentin stock after exchange: $augAfterQty (expected: " . ($augInitialQty - 10) . ")\n";
assert($doloAfterQty === ($doloInitialQty - 15 + 5), "Dolo stock mismatch");
assert($augAfterQty === ($augInitialQty - 10), "Augmentin stock mismatch");

// 5. Test getInvoice endpoint for the Exchange Bill
$invRes = makeGet($baseUrl . "sales/invoice/" . $exchangeSaleId);
echo "\nInvoice Details for Exchange Bill #$exchangeSaleId:\n";
echo "Gross: " . $invRes['data']['sale']['gross_amount'] . ", Return: " . $invRes['data']['sale']['return_amount'] . ", Net: " . $invRes['data']['sale']['net_amount'] . "\n";
echo "Dispensed Items count: " . count($invRes['data']['items']) . "\n";
echo "Returned Items count: " . count($invRes['data']['return_items']) . "\n";
echo "Credit Note No: " . ($invRes['data']['credit_note']['credit_note_no'] ?? 'N/A') . "\n";

assert(count($invRes['data']['items']) === 1, "Expected 1 dispensed item");
assert(count($invRes['data']['return_items']) === 1, "Expected 1 return item");

// 6. Test Standalone Return (Pure Refund)
$pureReturnPayload = [
    'store_id' => 1,
    'patient_name' => 'Refund Patient',
    'patient_mobile' => '9876543210',
    'refund_mode' => 'Cash',
    'items' => [],
    'return_items' => [
        [
            'item_id' => 3,
            'batch_id' => (int)$doloBatch['batch_id'],
            'sell_unit' => 'Tablet',
            'qty' => 5,
            'ref_sale_id' => $sale1Id,
            'ref_invoice_no' => $sale1Inv,
            'return_condition' => 'RESTOCKED',
            'return_reason' => 'Patient discharged early'
        ]
    ]
];

$pureReturnRes = makePost($baseUrl . 'sales/save', $pureReturnPayload);
echo "\nPure Standalone Return Response:\n";
echo "HTTP Code: " . $pureReturnRes['code'] . "\n";
print_r($pureReturnRes['data']);
assert($pureReturnRes['data']['refund_amount'] > 0, "Expected refund_amount > 0");
assert($pureReturnRes['data']['net_amount'] == 0, "Expected net_amount == 0 for pure refund");

$db->close();
echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
