<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "Current Admissions (ipd_status = 0):\n";
echo "====================================\n\n";

$result = $db->query("
    SELECT 
        i.id,
        i.ipd_code,
        DATE_FORMAT(i.register_date, '%d-%m-%Y') as register_date,
        p.p_fname,
        p.p_lname,
        bm.bed_number,
        bm.current_ipd_id
    FROM ipd_master i
    LEFT JOIN patient_master p ON p.id = i.p_id
    LEFT JOIN bed_master bm ON bm.current_ipd_id = i.id
    WHERE i.ipd_status = 0
    ORDER BY i.register_date DESC
");

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "IPD ID: {$row['id']}\n";
        echo "IPD Code: {$row['ipd_code']}\n";
        echo "Patient: {$row['p_fname']} {$row['p_lname']}\n";
        echo "Register Date: {$row['register_date']}\n";
        echo "Bed Assigned: " . ($row['bed_number'] ?? 'NOT ASSIGNED') . "\n";
        echo "---\n";
    }
} else {
    echo "No current admissions found.\n";
}

$db->close();
