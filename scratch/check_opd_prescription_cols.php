<?php
$conn = new mysqli('localhost', 'root', '', 'hms_ci4_2026');
$tables = ['opd_prescrption_prescribed', 'opd_prescription_prescribed', 'opd_prescription_item'];

foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if ($res && $res->num_rows > 0) {
        echo "TABLE $t EXISTS:\n";
        $c = $conn->query("SHOW COLUMNS FROM $t");
        while ($r = $c->fetch_assoc()) {
            echo "  - " . $r['Field'] . "\n";
        }
    }
}
