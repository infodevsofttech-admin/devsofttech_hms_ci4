<?php
define('FCPATH', __DIR__ . '/../public/');
require __DIR__ . '/../vendor/autoload.php';
$bootstrap = require __DIR__ . '/../app/Config/Boot/development.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$rows = $db->table('health_records')->orderBy('id', 'DESC')->limit(10)->get()->getResultArray();
echo "=== Last 10 Health Records in DB ===\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Entity: {$r['entity_type']} #{$r['entity_id']} | HI Type: {$r['hi_type']} | Status: {$r['push_status']} | Queue: {$r['queue_id']} | Date: {$r['created_at']}\n";
    if (!empty($r['error_message'])) {
        echo "   Error: {$r['error_message']}\n";
    }
}
