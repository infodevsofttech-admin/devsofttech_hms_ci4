<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error . "\n");
}

echo "=== mst_ledger_entries ===\n";
$res = $db->query("DESCRIBE mst_ledger_entries");
while ($row = $res->fetch_assoc()) {
    echo "  {$row['Field']} - {$row['Type']}\n";
}
