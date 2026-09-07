<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT head_id, head_code, head_name, head_type FROM mst_account_heads");
while($r = $res->fetch_assoc()) {
    echo $r['head_code'] . " | " . $r['head_name'] . " (" . $r['head_type'] . ")\n";
}
$db->close();
