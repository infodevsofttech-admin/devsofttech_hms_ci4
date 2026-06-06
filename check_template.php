<?php
// Load CodeIgniter bootstrap
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
require __DIR__ . '/vendor/autoload.php';

// Bootstrap the application
$app = require_once FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require FCPATH . '../system/bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();

echo "Checking discharge_template table...\n\n";

$query = $db->query('SELECT id, template_name, template_html, is_default FROM discharge_template WHERE id = 1 OR is_default = 1 LIMIT 5');

foreach($query->getResult() as $row) {
    echo "ID: " . $row->id . "\n";
    echo "Name: " . $row->template_name . "\n";
    echo "Is Default: " . $row->is_default . "\n";
    echo "HTML Length: " . strlen($row->template_html) . " chars\n";
    echo "HTML Preview:\n" . substr($row->template_html, 0, 500) . "\n";
    echo str_repeat("-", 80) . "\n\n";
}

echo "\nChecking discharge content for IPD ID 1...\n\n";

$query2 = $db->query('SELECT ipd_id, LEFT(content, 500) as content_preview FROM ipd_discharge WHERE ipd_id = 1 LIMIT 1');
$result = $query2->getResult();

if (!empty($result)) {
    $row = $result[0];
    echo "IPD ID: " . $row->ipd_id . "\n";
    echo "Content Preview:\n" . $row->content_preview . "\n";
} else {
    echo "No discharge content found for IPD ID 1\n";
}
