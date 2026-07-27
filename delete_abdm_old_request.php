<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$abhaAddress = $argv[1] ?? 'meerabisht1981@sbx';
$escaped = $db->real_escape_string($abhaAddress);

$countResult = $db->query("SELECT COUNT(*) AS c FROM abdm_hiu_workflows WHERE abha_address = '{$escaped}'");
$count = $countResult->fetch_assoc()['c'] ?? 0;
echo "Rows to delete for {$abhaAddress}: {$count}\n";

$db->query("DELETE FROM abdm_hiu_workflows WHERE abha_address = '{$escaped}'");
echo "Deleted. Affected rows: " . $db->affected_rows . "\n";

$docCountResult = $db->query("SHOW TABLES LIKE 'abdm_hiu_documents'");
if ($docCountResult && $docCountResult->num_rows > 0) {
    $docRows = $db->query("SELECT COUNT(*) AS c FROM abdm_hiu_documents WHERE abha_address = '{$escaped}'");
    echo "Existing abdm_hiu_documents rows for {$abhaAddress}: " . ($docRows->fetch_assoc()['c'] ?? 0) . "\n";
}

$db->close();
