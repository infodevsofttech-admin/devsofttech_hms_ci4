<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== Max id now ===\n";
$res = $db->query("SELECT MAX(id) maxid FROM abdm_hiu_workflows");
print_r($res->fetch_assoc());

echo "\n=== Show tables like %health% or %record% or %m3% or %hiu% ===\n";
$res2 = $db->query("SHOW TABLES");
while ($row = $res2->fetch_array()) {
    $t = $row[0];
    if (stripos($t, 'health') !== false || stripos($t, 'record') !== false || stripos($t, 'm3') !== false || stripos($t, 'hiu') !== false || stripos($t, 'abdm') !== false) {
        echo "$t\n";
    }
}
$db->close();
