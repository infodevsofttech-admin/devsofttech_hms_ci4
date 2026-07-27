<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "=== Before cleanup: total rows ===\n";
print_r($db->query("SELECT COUNT(*) c FROM abdm_hiu_documents")->fetch_assoc());

// Group by the new dedup key (transaction_id, consent_ref, care_context_reference, bundle_id)
// and keep only the newest row (highest id) per group; delete the rest.
$res = $db->query("
    SELECT transaction_id, consent_ref, care_context_reference, bundle_id, GROUP_CONCAT(id ORDER BY id DESC) ids
    FROM abdm_hiu_documents
    GROUP BY transaction_id, consent_ref, care_context_reference, bundle_id
    HAVING COUNT(*) > 1
");

$totalDeleted = 0;
while ($row = $res->fetch_assoc()) {
    $ids = explode(',', $row['ids']);
    $keepId = array_shift($ids); // newest (first in DESC order)
    if (empty($ids)) {
        continue;
    }
    $idsList = implode(',', array_map('intval', $ids));
    $db->query("DELETE FROM abdm_hiu_documents WHERE id IN ($idsList)");
    $totalDeleted += $db->affected_rows;
    echo "Kept id={$keepId}, deleted ids=[{$idsList}]\n";
}

echo "\nTotal deleted: {$totalDeleted}\n";

// Recompute doc_hash for all remaining rows using the new (request_id-free) formula,
// so future fetches correctly match and UPDATE instead of INSERT duplicates.
echo "\n=== Recomputing doc_hash for remaining rows ===\n";
$res2 = $db->query("SELECT id, transaction_id, consent_ref, care_context_reference, bundle_id FROM abdm_hiu_documents");
$updated = 0;
while ($row = $res2->fetch_assoc()) {
    $hash = sha1(implode('|', [
        (string) $row['transaction_id'],
        (string) $row['consent_ref'],
        (string) $row['care_context_reference'],
        (string) $row['bundle_id'],
    ]));
    $escapedHash = $db->real_escape_string($hash);
    $db->query("UPDATE abdm_hiu_documents SET doc_hash = '{$escapedHash}' WHERE id = " . (int) $row['id']);
    $updated++;
}
echo "Recomputed hash for {$updated} rows.\n";

echo "\n=== After cleanup: total rows ===\n";
print_r($db->query("SELECT COUNT(*) c FROM abdm_hiu_documents")->fetch_assoc());

$db->close();
