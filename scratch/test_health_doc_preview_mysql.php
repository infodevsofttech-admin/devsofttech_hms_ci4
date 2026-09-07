<?php
define('FCPATH', 'd:/Workplace/HMS_CI4_OLD/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootTest($paths);


echo "Testing DoctorDocument::health_document_fhir_preview(5)...\n";
$start = microtime(true);
$ctrl = new \App\Controllers\DoctorDocument();
$ctrl->initController(service('request'), service('response'), service('logger'));

try {
    $res = $ctrl->health_document_fhir_preview(5);
    $time = microtime(true) - $start;
    echo "Completed in " . round($time, 3) . "s\n";
    echo "Status code: " . $res->getStatusCode() . "\n";
    echo "Body length: " . strlen($res->getBody()) . "\n";
    echo "Body preview:\n" . substr($res->getBody(), 0, 1000) . "\n";
} catch (\Throwable $e) {
    $time = microtime(true) - $start;
    echo "FAILED in " . round($time, 3) . "s: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
