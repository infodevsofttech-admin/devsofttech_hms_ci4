<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);

require __DIR__ . '/vendor/autoload.php';

// Bootstrap minimal CI4
$paths = new \Config\Paths();
$app = \CodeIgniter\Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$row = $db->table('ipd_discharge_instructions')->where('ipd_id', 1007141)->get()->getRowArray();

if ($row) {
    echo "=== IPD 1007141 - ipd_discharge_instructions ===" . PHP_EOL . PHP_EOL;
    echo "comp_report (contains other_text JSON): " . PHP_EOL;
    echo substr($row['comp_report'] ?? '', 0, 300) . PHP_EOL . PHP_EOL;
    echo "comp_remark (Discharge Summary field): " . PHP_EOL;
    echo substr($row['comp_remark'] ?? '', 0, 300) . PHP_EOL;
} else {
    echo "No record found for IPD 1007141" . PHP_EOL;
}
