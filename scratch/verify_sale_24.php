<?php
$mysqli = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($mysqli->connect_error) die($mysqli->connect_error);

echo "=== SALE RECORD ===\n";
$res = $mysqli->query("SELECT * FROM mst_sales ORDER BY sale_id DESC LIMIT 1");
$sale = $res->fetch_assoc();
print_r($sale);

echo "\n=== SALE ITEMS ===\n";
$iRes = $mysqli->query("SELECT * FROM mst_sales_items WHERE sale_id = {$sale['sale_id']}");
while ($row = $iRes->fetch_assoc()) {
    print_r($row);
}

echo "\n=== CREDIT NOTE RECORD ===\n";
$cnRes = $mysqli->query("SELECT * FROM mst_sale_returns WHERE new_sale_id = {$sale['sale_id']}");
while ($row = $cnRes->fetch_assoc()) {
    print_r($row);
}

echo "\n=== STOCK AUDIT TRAIL ===\n";
$audRes = $mysqli->query("SELECT * FROM mst_stock_audit WHERE remarks LIKE '%{$sale['invoice_no']}%'");
while ($row = $audRes->fetch_assoc()) {
    echo "Audit ID: {$row['audit_id']} | Item: {$row['item_id']} | Var: {$row['variation_qty']} | Rate: {$row['rate']} | Remarks: {$row['remarks']}\n";
}
