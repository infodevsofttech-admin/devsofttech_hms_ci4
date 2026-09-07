<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

$res = $db->query("SHOW COLUMNS FROM mst_sales LIKE 'bill_discount_type'");
if ($res->num_rows == 0) {
    $db->query("ALTER TABLE mst_sales ADD COLUMN bill_discount_type varchar(10) DEFAULT 'pct' AFTER discount_amount");
    $db->query("ALTER TABLE mst_sales ADD COLUMN bill_discount_val decimal(10,2) DEFAULT 0.00 AFTER bill_discount_type");
    echo "Columns bill_discount_type and bill_discount_val added to mst_sales.\n";
} else {
    echo "Columns already exist in mst_sales.\n";
}

$resItem = $db->query("SHOW COLUMNS FROM mst_sales_items LIKE 'discount_type'");
if ($resItem->num_rows == 0) {
    $db->query("ALTER TABLE mst_sales_items ADD COLUMN discount_type varchar(10) DEFAULT 'pct' AFTER unit_mrp");
    $db->query("ALTER TABLE mst_sales_items ADD COLUMN discount_val decimal(10,2) DEFAULT 0.00 AFTER discount_type");
    echo "Columns discount_type and discount_val added to mst_sales_items.\n";
} else {
    echo "Columns already exist in mst_sales_items.\n";
}
