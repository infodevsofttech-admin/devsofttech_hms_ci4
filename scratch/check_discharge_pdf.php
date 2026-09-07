<?php
require __DIR__ . '/../vendor/autoload.php';

// Bootstrap CodeIgniter
$_SERVER['CI_ENVIRONMENT'] = 'development';
$app = require __DIR__ . '/../app/Config/Boot/production.php';

// Let's test generating the discharge summary PDF for IPD 9
$controller = new \App\Controllers\Ipd_discharge();
$request = \Config\Services::request();
$response = \Config\Services::response();
$logger = \Config\Services::logger();
$controller->initController($request, $response, $logger);

$pdfPath = WRITEPATH . 'uploads/abdm/ipd/9/discharge-summary.pdf';
echo "Checking PDF at: $pdfPath\n";
if (file_exists($pdfPath)) {
    echo "Existing PDF size: " . filesize($pdfPath) . " bytes\n";
} else {
    echo "PDF does not exist yet on disk.\n";
}
