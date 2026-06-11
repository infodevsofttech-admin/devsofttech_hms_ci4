<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "Checking IPD ID: 2\n";
echo "===================\n\n";

// Check IPD master data
echo "IPD Master Data:\n";
$result = $db->query("
    SELECT 
        i.id,
        i.ipd_code,
        i.ipd_status,
        DATE_FORMAT(i.register_date, '%d-%m-%Y') as register_date,
        p.p_fname,
        p.p_lname
    FROM ipd_master i
    LEFT JOIN patient_master p ON p.id = i.p_id
    WHERE i.id = 2
");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "IPD Code: {$row['ipd_code']}\n";
    echo "Patient: {$row['p_fname']} {$row['p_lname']}\n";
    echo "Status: " . ($row['ipd_status'] == 0 ? 'Admitted' : 'Discharged') . "\n";
    echo "Register Date: {$row['register_date']}\n\n";
} else {
    echo "IPD ID 2 not found!\n\n";
}

// Check bed_assignment_history
echo "Bed Assignment History:\n";
$result = $db->query("
    SELECT 
        bah.id,
        bah.ipd_id,
        bah.bed_id,
        bm.bed_number,
        bm.bed_code,
        w.ward_name,
        DATE_FORMAT(bah.assigned_date, '%d-%m-%Y %H:%i') as assigned_date,
        DATE_FORMAT(bah.released_date, '%d-%m-%Y %H:%i') as released_date,
        bah.assignment_type,
        bah.remarks
    FROM bed_assignment_history bah
    LEFT JOIN bed_master bm ON bm.id = bah.bed_id
    LEFT JOIN ward_master w ON w.id = bah.ward_id
    WHERE bah.ipd_id = 2
    ORDER BY bah.id DESC
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Assignment ID: {$row['id']}\n";
        echo "Bed: {$row['bed_number']} ({$row['bed_code']})\n";
        echo "Ward: {$row['ward_name']}\n";
        echo "Assigned: {$row['assigned_date']}\n";
        echo "Released: " . ($row['released_date'] ?? 'NOT RELEASED') . "\n";
        echo "Type: {$row['assignment_type']}\n";
        echo "Remarks: {$row['remarks']}\n";
        echo "---\n";
    }
} else {
    echo "No bed assignment history found for IPD ID 2\n\n";
}

// Check current bed assignment in bed_master
echo "Current Bed in bed_master:\n";
$result = $db->query("
    SELECT 
        bm.id,
        bm.bed_number,
        bm.bed_code,
        bm.bed_status,
        bm.current_ipd_id,
        w.ward_name
    FROM bed_master bm
    LEFT JOIN ward_master w ON w.id = bm.ward_id
    WHERE bm.current_ipd_id = 2
");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "Bed ID: {$row['id']}\n";
    echo "Bed: {$row['bed_number']} ({$row['bed_code']})\n";
    echo "Ward: {$row['ward_name']}\n";
    echo "Status: {$row['bed_status']}\n";
} else {
    echo "No bed currently assigned in bed_master.current_ipd_id for IPD ID 2\n";
}

$db->close();
