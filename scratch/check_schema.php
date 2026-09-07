<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SELECT * FROM hospital_setting LIMIT 1");
if ($r = $res->fetch_assoc()) {
    foreach ($r as $k => $v) {
        if (preg_match('/abdm|hfr|bridge|name|address/i', $k)) {
            echo "  $k: $v\n";
        }
    }
}
$db->close();
