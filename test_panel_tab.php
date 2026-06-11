<?php
/**
 * Test the panelTab endpoint
 */

// Simulate what the controller does
$db = new mysqli('localhost', 'root', '', 'hms_data_ci4');

$ipdId = 2;

echo "Simulating panelTab(2, 'bed-assign')\n";
echo "=====================================\n\n";

// Step 1: Get panel data
echo "Step 1: Get IpdPanelInfo...\n";
$query = "SELECT * FROM ipd_master WHERE id = {$ipdId}";
$result = $db->query($query);
if ($result && $result->num_rows > 0) {
    echo "✓ IPD data found\n\n";
} else {
    echo "✗ IPD not found\n";
    exit;
}

// Step 2: Get bed assignments
echo "Step 2: Get bed assignments...\n";
$query = "
    SELECT 
        bed_assignment_history.*, 
        bed_master.bed_code, 
        bed_master.bed_number, 
        ward_master.ward_name
    FROM bed_assignment_history
    LEFT JOIN bed_master ON bed_master.id = bed_assignment_history.bed_id
    LEFT JOIN ward_master ON ward_master.id = bed_assignment_history.ward_id
    WHERE bed_assignment_history.ipd_id = {$ipdId}
    ORDER BY bed_assignment_history.id DESC
";
$result = $db->query($query);
echo "✓ Query executed\n";
echo "✓ Rows found: {$result->num_rows}\n\n";

if ($result->num_rows > 0) {
    echo "Step 3: Render view with data...\n";
    $bed_assignments = [];
    while ($row = $result->fetch_object()) {
        $bed_assignments[] = $row;
    }
    
    echo "✓ Data prepared\n";
    echo "✓ Array count: " . count($bed_assignments) . "\n\n";
    
    echo "Step 4: Check if view can access data...\n";
    echo "Sample data from first record:\n";
    if (!empty($bed_assignments)) {
        $first = $bed_assignments[0];
        echo "  bed_code: " . ($first->bed_code ?? 'NULL') . "\n";
        echo "  bed_number: " . ($first->bed_number ?? 'NULL') . "\n";
        echo "  ward_name: " . ($first->ward_name ?? 'NULL') . "\n";
        echo "  assigned_date: " . ($first->assigned_date ?? 'NULL') . "\n";
        echo "  assignment_type: " . ($first->assignment_type ?? 'NULL') . "\n";
    }
    
    echo "\n✓ View should display this data\n";
} else {
    echo "✗ No bed assignments found - view will show empty message\n";
}

$db->close();
