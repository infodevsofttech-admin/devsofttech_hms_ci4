<?php
define('FCPATH', __DIR__ . '/../public/');
chdir(__DIR__ . '/../');
require 'vendor/autoload.php';
require 'app/Config/Paths.php';
$paths = new Config\Paths();
require 'system/bootstrap.php';
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$ipd = $db->table('ipd_master')->where('id', 9)->get()->getRowArray();
echo "IPD 9: " . json_encode($ipd, JSON_PRETTY_PRINT) . "\n";
