<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT i.item_id, i.item_name, i.unit_pack, i.units_per_pack, b.batch_no, b.mrp, s.current_qty 
    FROM mst_items i 
    LEFT JOIN mst_batches b ON b.item_id = i.item_id 
    LEFT JOIN mst_stock s ON s.batch_id = b.batch_id 
    LIMIT 10");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
$db->close();
