<?php
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

echo "Checking bed_assignment_history for IPD ID: 1\n";
echo "=============================================\n\n";

$result = $db->query("
    SELECT 
        bah.id,
        bah.ipd_id,
        bah.bed_id,
        bm.bed_number,
        bm.bed_code,
        bm.bed_status,
        bm.current_ipd_id,
        DATE_FORMAT(bah.assigned_date, '%d-%m-%Y %H:%i') as assigned_date,
        bah.released_date
    FROM bed_assignment_history bah
    LEFT JOIN bed_master bm ON bm.id = bah.bed_id
    WHERE bah.ipd_id = 1
    ORDER BY bah.assigned_date DESC
");

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Assignment ID: {$row['id']}\n";
        echo "Bed: {$row['bed_number']} ({$row['bed_code']})\n";
        echo "Assigned Date: {$row['assigned_date']}\n";
        echo "Released Date: " . ($row['released_date'] ?? 'NOT RELEASED') . "\n";
        echo "Bed Status in master: {$row['bed_status']}\n";
        echo "Current IPD ID in bed: " . ($row['current_ipd_id'] ?? 'NULL') . "\n";
        echo "---\n";
    }
} else {
    echo "No bed assignment history found for this patient.\n";
    echo "This patient may need a bed assigned manually.\n";
}

$db->close();
