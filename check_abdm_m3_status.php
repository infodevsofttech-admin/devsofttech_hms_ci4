<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$pid = isset($argv[1]) ? (int) $argv[1] : 15;

echo "=== Patient ===\n";
$r = $db->query("SELECT id, p_fname, p_lname, abha_address FROM patient_master WHERE id = {$pid}");
$patient = $r ? $r->fetch_assoc() : null;
print_r($patient);

$abhaAddress = $patient['abha_address'] ?? '';

echo "\n=== abdm_hiu_workflows columns ===\n";
$colsResult = $db->query("SHOW COLUMNS FROM abdm_hiu_workflows");
$workflowCols = [];
while ($c = $colsResult->fetch_assoc()) {
    $workflowCols[] = $c['Field'];
}
echo implode(', ', $workflowCols) . "\n";

echo "\n=== abdm_hiu_workflows (latest 15 for this ABHA / patient) ===\n";
$where = $abhaAddress !== '' ? "abha_address = '" . $db->real_escape_string($abhaAddress) . "'" : "1=0";
$result = $db->query("
    SELECT *
    FROM abdm_hiu_workflows
    WHERE {$where}
    ORDER BY id DESC
    LIMIT 15
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed or table missing: " . $db->error . "\n";
}

echo "\n=== abdm_api_logs (latest 15) ===\n";
$result2 = $db->query("SHOW TABLES LIKE 'abdm_api_logs'");
if ($result2 && $result2->num_rows > 0) {
    $cols = $db->query("SHOW COLUMNS FROM abdm_api_logs");
    $colNames = [];
    while ($c = $cols->fetch_assoc()) {
        $colNames[] = $c['Field'];
    }
    echo "Columns: " . implode(', ', $colNames) . "\n\n";
    $result3 = $db->query("SELECT * FROM abdm_api_logs ORDER BY id DESC LIMIT 15");
    while ($row = $result3->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "abdm_api_logs table not found.\n";
}

$db->close();
