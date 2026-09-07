<?php
$conn = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$res = $conn->query("SHOW COLUMNS FROM nursing_treatment_entries");
echo "COLUMNS IN nursing_treatment_entries:\n";
while ($r = $res->fetch_assoc()) {
    echo "- " . $r['Field'] . " (" . $r['Type'] . ")\n";
}
