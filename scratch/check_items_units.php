<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT item_id, item_name, unit_pack, units_per_pack, category FROM mst_items LIMIT 25");
while($r = $res->fetch_assoc()) {
    echo "ID {$r['item_id']}: {$r['item_name']} | Pack: '{$r['unit_pack']}' | UnitsPerPack: {$r['units_per_pack']} | Cat: {$r['category']}\n";
}
$db->close();
