<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "=== Latest 5 Sales Invoices ===\n";
$res = $db->query("SELECT sale_id, store_id, invoice_no, gross_amount, discount_amount, net_amount FROM mst_sales ORDER BY sale_id DESC LIMIT 5");
while ($r = $res->fetch_assoc()) {
    echo "  Sale #{$r['sale_id']}: {$r['invoice_no']} - Net: ₹{$r['net_amount']}\n";
}

echo "=== Stores next_invoice_no ===\n";
$sRes = $db->query("SELECT store_id, store_name, invoice_prefix, next_invoice_no FROM mst_stores");
while ($st = $sRes->fetch_assoc()) {
    echo "  Store #{$st['store_id']} ({$st['store_name']}): Prefix '{$st['invoice_prefix']}', Next Seq: {$st['next_invoice_no']}\n";
    // Check highest invoice
    $maxRes = $db->query("SELECT invoice_no FROM mst_sales WHERE store_id = {$st['store_id']} ORDER BY sale_id DESC LIMIT 1");
    if ($maxRes && $maxRow = $maxRes->fetch_assoc()) {
        $inv = $maxRow['invoice_no'];
        if (preg_match('/(\d+)$/', $inv, $m)) {
            $lastNum = (int)$m[1];
            if ($lastNum >= (int)$st['next_invoice_no']) {
                $newNext = $lastNum + 1;
                $db->query("UPDATE mst_stores SET next_invoice_no = $newNext WHERE store_id = {$st['store_id']}");
                echo "    -> Updated next_invoice_no to $newNext\n";
            }
        }
    }
}
