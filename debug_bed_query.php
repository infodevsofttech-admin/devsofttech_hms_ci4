<?php
/**
 * Debug bed assignment query - Direct DB approach
 */

$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "Testing bed_assignment_history query for IPD ID 2\n";
echo "==================================================\n\n";

$query = "
    SELECT 
        bed_assignment_history.*, 
        bed_master.bed_code, 
        bed_master.bed_number, 
        ward_master.ward_name
    FROM bed_assignment_history
    LEFT JOIN bed_master ON bed_master.id = bed_assignment_history.bed_id
    LEFT JOIN ward_master ON ward_master.id = bed_assignment_history.ward_id
    WHERE bed_assignment_history.ipd_id = 2
    ORDER BY bed_assignment_history.id DESC
";

$result = $db->query($query);

echo "Query executed\n";
echo "Rows found: " . $result->num_rows . "\n\n";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_object()) {
        echo "Record ID: {$row->id}\n";
        echo "bed_code: " . ($row->bed_code ?? 'NULL') . "\n";
        echo "bed_number: " . ($row->bed_number ?? 'NULL') . "\n";
        echo "ward_name: " . ($row->ward_name ?? 'NULL') . "\n";
        echo "assigned_date: " . ($row->assigned_date ?? 'NULL') . "\n";
        echo "released_date: " . ($row->released_date ?? 'NULL') . "\n";
        echo "assignment_type: " . ($row->assignment_type ?? 'NULL') . "\n";
        echo "remarks: " . ($row->remarks ?? 'NULL') . "\n";
        echo "---\n";
    }
} else {
    echo "No records found\n";
}

$db->close();
