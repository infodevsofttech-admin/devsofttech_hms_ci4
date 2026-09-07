<?php
$conn = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$res = $conn->query("SHOW COLUMNS FROM ipd_nursing_entries");
echo "COLUMNS IN ipd_nursing_entries:\n";
while ($r = $res->fetch_assoc()) {
    echo "- " . $r['Field'] . " (" . $r['Type'] . ")\n";
}
