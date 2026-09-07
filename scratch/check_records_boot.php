<?php
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootTest($paths);

$db = \Config\Database::connect();
$rows = $db->table('health_records')->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();
echo "=== Last 10 Health Records in DB ===\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Entity: {$r['entity_type']} #{$r['entity_id']} | HI Type: {$r['hi_type']} | Status: {$r['push_status']} | Queue: {$r['queue_id']} | Date: {$r['created_at']}\n";
    if (!empty($r['error_message'])) {
        echo "   Error: {$r['error_message']}\n";
    }
}
