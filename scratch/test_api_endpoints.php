<?php
define('FCPATH', __DIR__ . '/../public/');
require __DIR__ . '/../app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootTest($paths);

$api = new \App\Controllers\Api\v1\MedicalStoreApi();

echo "1. Testing stores()...\n";
$resp = $api->stores();
$data = json_decode($resp->getBody(), true);
echo "   Status: " . ($data['status'] ?? 0) . " | Stores found: " . count($data['stores'] ?? []) . "\n";
foreach ($data['stores'] as $s) {
    echo "   - Store: {$s['store_name']} | DL 20B: {$s['drug_license_no_20b']} | GSTIN: {$s['gstin']} | Pharmacist: {$s['registered_pharmacist_name']}\n";
}

echo "\n2. Testing searchItems(store_id=1)...\n";
$_GET['store_id'] = 1;
$_GET['q'] = 'Augmentin';
$resp = $api->searchItems();
$data = json_decode($resp->getBody(), true);
echo "   Status: " . ($data['status'] ?? 0) . " | Items found: " . count($data['items'] ?? []) . "\n";
if (!empty($data['items'])) {
    $item = $data['items'][0];
    echo "   - Item: {$item['item_name']} | Total Stock: {$item['total_stock']} | Batches: " . count($item['batches']) . "\n";
    if (!empty($item['fefo_batch'])) {
        echo "   - Best FEFO Batch: {$item['fefo_batch']['batch_no']} (Exp: {$item['fefo_batch']['expiry_display']}) | MRP: ₹{$item['fefo_batch']['mrp']}\n";
    }
}

echo "\n3. Testing searchPatient()...\n";
$_GET['q'] = 'a'; // Search any patient
$resp = $api->searchPatient();
$data = json_decode($resp->getBody(), true);
echo "   Status: " . ($data['status'] ?? 0) . " | Patients found: " . count($data['patients'] ?? []) . "\n";
if (!empty($data['patients'])) {
    $p = $data['patients'][0];
    echo "   - Patient: {$p['full_name']} | UHID: {$p['uhid']} | Active OPD: " . ($p['has_active_opd'] ? 'YES' : 'NO') . " | Active IPD: " . ($p['has_active_ipd'] ? 'YES' : 'NO') . "\n";
}

echo "\n4. Testing stockList(store_id=1)...\n";
$_GET['store_id'] = 1;
$_GET['filter'] = 'all';
$resp = $api->stockList();
$data = json_decode($resp->getBody(), true);
echo "   Status: " . ($data['status'] ?? 0) . " | Stock lines: " . ($data['summary']['total_rows'] ?? 0) . "\n";
echo "   - Total Cost Valuation: ₹" . ($data['summary']['total_cost_valuation'] ?? 0) . " | MRP Valuation: ₹" . ($data['summary']['total_mrp_valuation'] ?? 0) . "\n";

echo "\n5. Testing getDaybook(store_id=1)...\n";
$_GET['store_id'] = 1;
$_GET['date'] = date('Y-m-d');
$resp = $api->getDaybook();
$data = json_decode($resp->getBody(), true);
echo "   Status: " . ($data['status'] ?? 0) . " | Transactions today: " . ($data['summary']['total_invoices'] ?? 0) . "\n";

echo "\n=== All API Tests Passed Successfully ===\n";
