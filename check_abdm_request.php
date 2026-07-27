<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$requestId = $argv[1] ?? 'REQ-20260728030135-90ee8841';
$escaped = $db->real_escape_string($requestId);

echo "=== abdm_hiu_workflows matching request_id/gateway_request_id/hms_request_id = {$requestId} ===\n";
$result = $db->query("
    SELECT *
    FROM abdm_hiu_workflows
    WHERE request_id = '{$escaped}' OR gateway_request_id = '{$escaped}' OR hms_request_id = '{$escaped}'
    ORDER BY id ASC
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Query failed: " . $db->error . "\n";
}

echo "\n=== abdm_api_logs matching entity_id = {$requestId} ===\n";
$result2 = $db->query("SELECT * FROM abdm_api_logs WHERE entity_id = '{$escaped}' ORDER BY id ASC");
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        print_r($row);
    }
}

$db->close();
