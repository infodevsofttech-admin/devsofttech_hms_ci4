<?php

require_once 'vendor/codeigniter4/framework/system/Test/bootstrap.php';
$db = \Config\Database::connect();

echo "=== IPD_DETAILS COLUMNS ===\n";
$cols = $db->getFieldNames('ipd_details');
echo implode(', ', $cols) . "\n\n";

if ($db->tableExists('ipd_discharge')) {
    echo "=== IPD_DISCHARGE COLUMNS ===\n";
    $dcols = $db->getFieldNames('ipd_discharge');
    echo implode(', ', $dcols) . "\n\n";
    $drow = $db->table('ipd_discharge')->where('ipd_id', 9)->get()->getRowArray();
    echo "IPD 9 discharge row: " . json_encode($drow, JSON_PRETTY_PRINT) . "\n\n";
}

$irow = $db->table('ipd_details')->where('id', 9)->get()->getRowArray();
echo "IPD 9 details row: " . json_encode($irow, JSON_PRETTY_PRINT) . "\n";
