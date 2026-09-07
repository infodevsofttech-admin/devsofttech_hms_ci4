<?php
$conn = new mysqli('localhost', 'root', '', 'hms_data_ci4');
$res = $conn->query("SHOW TABLES");
echo "TABLES IN hms_data_ci4:\n";
while ($r = $res->fetch_array()) {
    if (strpos($r[0], 'nurs') !== false || strpos($r[0], 'treat') !== false || strpos($r[0], 'ipd') !== false) {
        echo "- " . $r[0] . "\n";
    }
}
