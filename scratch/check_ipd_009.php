<?php
$conn = new mysqli('localhost', 'root', '', 'hms_ci4_2026');

echo "=== DOCTOR MASTER RECORDS ===\n";
$docRes = $conn->query("SELECT * FROM doctor_master");
while ($d = $docRes->fetch_assoc()) {
    echo "ID: " . $d['id'] . " | Title: " . ($d['p_title'] ?? '') . " | Name: " . ($d['p_fname'] ?? '') . " " . ($d['p_lname'] ?? '') . "\n";
}

echo "\n=== IPD MASTER RECORDS ===\n";
$ipdRes = $conn->query("SELECT id, ipd_code, p_id, r_doc_id, r_doc_name, ipd_status, register_date FROM ipd_master ORDER BY id DESC LIMIT 10");
while ($i = $ipdRes->fetch_assoc()) {
    print_r($i);
}

echo "\n=== IPD MASTER DOC LIST ===\n";
$docListRes = $conn->query("SELECT * FROM ipd_master_doc_list ORDER BY id DESC LIMIT 20");
while ($dl = $docListRes->fetch_assoc()) {
    print_r($dl);
}
