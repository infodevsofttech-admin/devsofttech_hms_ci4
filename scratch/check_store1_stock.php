<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT s.store_id, i.item_id, i.item_name, i.unit_pack, i.units_per_pack, b.batch_no, b.mrp, s.current_qty 
    FROM mst_stock s 
    JOIN mst_items i ON i.item_id = s.item_id 
    JOIN mst_batches b ON b.batch_id = s.batch_id 
    WHERE s.store_id = 1");
while($r = $res->fetch_assoc()) {
    $strips = $r['units_per_pack'] > 0 ? floor($r['current_qty'] / $r['units_per_pack']) : $r['current_qty'];
    $loose = $r['units_per_pack'] > 0 ? ($r['current_qty'] % $r['units_per_pack']) : 0;
    echo "{$r['item_name']} ({$r['batch_no']}) | MRP: {$r['mrp']} | UnitsPerPack: {$r['units_per_pack']} | Stock: {$r['current_qty']} units (~{$strips} strips, {$loose} tabs)\n";
}
$db->close();
