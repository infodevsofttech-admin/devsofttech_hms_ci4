<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$res = $mysqli->query("SELECT sale_id, invoice_no, sale_date, patient_name, uhid, net_amount, return_amount FROM mst_sales ORDER BY sale_id DESC LIMIT 5");
while ($s = $res->fetch_assoc()) {
    echo "ID: {$s['sale_id']} | Bill: {$s['invoice_no']} | Date: {$s['sale_date']} | Patient: {$s['patient_name']} ({$s['uhid']}) | Net: ₹{$s['net_amount']} | Ret: ₹{$s['return_amount']}\n";
    $iRes = $mysqli->query("SELECT si.*, i.item_name FROM mst_sales_items si JOIN mst_items i ON i.item_id = si.item_id WHERE si.sale_id = {$s['sale_id']}");
    while ($it = $iRes->fetch_assoc()) {
        echo "   -> [{$it['item_type']}] {$it['item_name']} | Batch: {$it['batch_no']} | Qty: {$it['total_units']} tabs | Rate: ₹{$it['unit_price']} | Total: ₹{$it['total_amount']}\n";
    }
}
