<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SHOW TABLES LIKE '%ipd%'");
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
$db->close();
