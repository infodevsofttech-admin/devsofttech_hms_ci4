<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$colsToAdd = [
    'sell_unit' => "VARCHAR(30) DEFAULT 'Strip' AFTER `qty`",
    'units_per_pack' => "INT DEFAULT 1 AFTER `sell_unit`",
    'total_units' => "INT DEFAULT 1 AFTER `units_per_pack`",
    'unit_price' => "DECIMAL(10,2) DEFAULT 0.00 AFTER `total_units`",
    'loose_qty' => "INT DEFAULT 0 AFTER `unit_price`",
    'pack_qty' => "DECIMAL(10,2) DEFAULT 1.00 AFTER `loose_qty`"
];

$existing = [];
$res = $db->query("SHOW FULL COLUMNS FROM mst_sales_items");
while($r = $res->fetch_assoc()) {
    $existing[$r['Field']] = true;
}

foreach ($colsToAdd as $col => $def) {
    if (!isset($existing[$col])) {
        $db->query("ALTER TABLE mst_sales_items ADD COLUMN `$col` $def");
        echo "Added column $col to mst_sales_items\n";
    } else {
        echo "Column $col already exists in mst_sales_items\n";
    }
}
$db->close();
