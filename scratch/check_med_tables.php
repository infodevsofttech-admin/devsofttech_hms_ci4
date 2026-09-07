<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $db->query("SHOW TABLES LIKE '%med%'");
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
$res = $db->query("SHOW TABLES LIKE '%pharm%'");
while ($r = $res->fetch_array()) {
    echo $r[0] . "\n";
}
$db->close();
