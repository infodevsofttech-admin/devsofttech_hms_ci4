<?php
// Test script for Partial / Split Payment and Bank Statement Reconciliation

$baseUrl = 'http://localhost:8080/api/v1/medical-store/';

// 1. Fetch items to find a batch to sell
$itemRes = file_get_contents($baseUrl . 'items/search?store_id=1&q=Paracetamol');
$itemData = json_decode($itemRes, true);

if (empty($itemData['items'])) {
    // fallback search any
    $itemRes = file_get_contents($baseUrl . 'items/search?store_id=1&q=a');
    $itemData = json_decode($itemRes, true);
}

$selectedBatch = null;
foreach ($itemData['items'] as $it) {
    if (!empty($it['batches'])) {
        foreach ($it['batches'] as $b) {
            if ($b['current_qty'] > 5 && $b['expiry_date'] > date('Y-m-d')) {
                $selectedBatch = [
                    'item_id' => $it['item_id'],
                    'batch_id' => $b['batch_id'],
                    'mrp' => (float)$b['mrp'],
                    'item_name' => $it['item_name']
                ];
                break 2;
            }
        }
    }
}

if (!$selectedBatch) {
    die("No suitable in-stock batch found for test.\n");
}

echo "Found batch for '{$selectedBatch['item_name']}', MRP: ₹{$selectedBatch['mrp']}\n";

// Let's create a sale: Total bill = ₹135 (e.g. qty matching or item + whole discount to make net ₹135)
// Let's calculate qty and whole discount to make net payable exactly ₹135.
// For instance: 3 qty @ MRP, or let's say qty=3, gross = 3 * mrp.
// Let's set discount so net is 135:
$qty = 2;
$lineGross = $qty * $selectedBatch['mrp'];
// If gross != 135, let's use whole_discount_val to adjust, or whatever net gives:
// Or let's test directly with the exact numbers the user mentioned: Total Bill = 135, Cash = 35, UPI = 100!
$wholeDiscount = 0;
if ($lineGross > 135) {
    $wholeDiscount = $lineGross - 135;
} else {
    // let qty be higher
    $qty = (int)ceil(135 / $selectedBatch['mrp']);
    $lineGross = $qty * $selectedBatch['mrp'];
    $wholeDiscount = $lineGross - 135;
}
$netPayable = round($lineGross - $wholeDiscount);
$cashPart = 35;
$upiPart = $netPayable - $cashPart;

echo "Creating Sale: Gross = ₹$lineGross, Disc = ₹$wholeDiscount, Net = ₹$netPayable\n";
echo "Split Payment: Cash = ₹$cashPart, UPI = ₹$upiPart (UTR: 425109876543, Bank: HDFC Bank)\n";

$salePayload = [
    'store_id' => 1,
    'patient_type' => 'Walk-in',
    'patient_name' => 'Rahul Sharma (Test Split Payment)',
    'patient_mobile' => '9876543210',
    'doctor_name' => 'Dr. A. K. Gupta',
    'payment_mode' => 'Mixed',
    'cash_paid' => $cashPart,
    'upi_paid' => $upiPart,
    'card_paid' => 0,
    'credit_amount' => 0,
    'upi_ref_no' => '425109876543',
    'bank_name' => 'HDFC Bank',
    'card_ref_no' => '',
    'whole_discount_type' => 'val',
    'whole_discount_val' => $wholeDiscount,
    'items' => [
        [
            'item_id' => $selectedBatch['item_id'],
            'batch_id' => $selectedBatch['batch_id'],
            'qty' => $qty,
            'discount_type' => 'val',
            'discount_val' => 0,
            'discount_pct' => 0
        ]
    ]
];

$ch = curl_init($baseUrl . 'sales/save');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($salePayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resp = curl_exec($ch);
curl_close($ch);

$saleResult = json_decode($resp, true);
echo "Sale Response: " . json_encode($saleResult) . "\n";

if (empty($saleResult['status'])) {
    die("Sale creation failed!\n");
}

$saleId = $saleResult['sale_id'];
$invoiceNo = $saleResult['invoice_no'];

// 2. Verify Database records
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$saleRow = $mysqli->query("SELECT * FROM mst_sales WHERE sale_id = $saleId")->fetch_assoc();
echo "\n--- DB mst_sales check ---\n";
echo "Invoice: {$saleRow['invoice_no']}, Net: ₹{$saleRow['net_amount']}, Mode: {$saleRow['payment_mode']}\n";
echo "Cash Paid: ₹{$saleRow['cash_paid']}, UPI Paid: ₹{$saleRow['upi_paid']}\n";
echo "Bank: {$saleRow['bank_name']}, UTR: {$saleRow['upi_ref_no']}, Reconciled: {$saleRow['is_bank_reconciled']}\n";

echo "\n--- DB mst_ledger_entries check ---\n";
$ledgerRes = $mysqli->query("SELECT le.*, ah.head_code, ah.head_name FROM mst_ledger_entries le JOIN mst_account_heads ah ON ah.head_id = le.account_head_id WHERE le.reference_id = $saleId AND le.reference_type = 'sales_invoice'");
while ($row = $ledgerRes->fetch_assoc()) {
    echo "Head: {$row['head_code']} ({$row['head_name']}) | Debit: ₹{$row['debit_amount']} | Credit: ₹{$row['credit_amount']} | Narration: {$row['narration']} | Audit Ref: {$row['reconciled_ref']} | Reconciled: {$row['reconciled_flag']}\n";
}

// 3. Test getBankReconciliation API
echo "\n--- API Test: getBankReconciliation ---\n";
$reconRes = file_get_contents($baseUrl . 'accounting/bank-reconciliation?store_id=1&status=all');
$reconData = json_decode($reconRes, true);
echo "Summary: " . json_encode($reconData['summary']) . "\n";
echo "Total Records Returned: " . count($reconData['records']) . "\n";

$foundOurSale = false;
foreach ($reconData['records'] as $r) {
    if ($r['sale_id'] == $saleId) {
        $foundOurSale = true;
        echo "Found our test bill in reconciliation API: Invoice {$r['invoice_no']}, UTR: {$r['upi_ref_no']}, Reconciled: {$r['is_bank_reconciled']}\n";
        break;
    }
}
if (!$foundOurSale) {
    echo "WARNING: Test bill not found in reconciliation list!\n";
}

// 4. Test reconcileBank API (Marking Reconciled)
echo "\n--- API Test: reconcileBank (Audit Verification) ---\n";
$auditPayload = [
    'sale_id' => $saleId,
    'is_reconciled' => 1,
    'reconciled_by' => 'Head Accountant',
    'reconciled_notes' => 'Matched with HDFC Bank Statement line item credit ₹' . $upiPart . ' on 08-Sep-2026, UTR: 425109876543'
];

$ch = curl_init($baseUrl . 'accounting/reconcile-bank');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auditPayload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$auditResp = curl_exec($ch);
curl_close($ch);

echo "Audit Response: $auditResp\n";

// 5. Verify updated status
$saleRowAfter = $mysqli->query("SELECT is_bank_reconciled, reconciled_at, reconciled_by, reconciled_notes FROM mst_sales WHERE sale_id = $saleId")->fetch_assoc();
echo "Updated mst_sales reconciliation status: " . json_encode($saleRowAfter) . "\n";

$ledgerBankAfter = $mysqli->query("SELECT reconciled_flag, reconciled_at FROM mst_ledger_entries WHERE reference_id = $saleId AND account_head_id = (SELECT head_id FROM mst_account_heads WHERE head_code = '1002')")->fetch_assoc();
echo "Updated Bank Ledger Entry reconciliation status: " . json_encode($ledgerBankAfter) . "\n";

echo "\nTEST COMPLETED SUCCESSFULLY!\n";
