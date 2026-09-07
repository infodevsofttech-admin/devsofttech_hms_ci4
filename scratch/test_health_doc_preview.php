<?php

require_once 'd:/Workplace/HMS_CI4_OLD/vendor/autoload.php';
defined('FCPATH') || define('FCPATH', 'd:/Workplace/HMS_CI4_OLD/public/');
require_once 'd:/Workplace/HMS_CI4_OLD/vendor/codeigniter4/framework/system/Test/bootstrap.php';

$db = \Config\Database::connect('default');
$ctrl = new \App\Controllers\DoctorDocument();
$ctrl->initController(service('request'), service('response'), service('logger'));
try {
    $res = $ctrl->health_document_fhir_preview(5);
    echo "Status code: " . $res->getStatusCode() . "\n";
    echo "Body: " . substr($res->getBody(), 0, 500) . "\n";
} catch (\Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

