<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
foreach(['mst_items', 'mst_batches', 'mst_stock', 'mst_sales_items'] as $tbl) {
    echo "=== $tbl ===\n";
    $res = $db->query("SHOW FULL COLUMNS FROM $tbl");
    while($r = $res->fetch_assoc()) {
        echo $r['Field'] . " | " . $r['Type'] . "\n";
    }
}
$db->close();
