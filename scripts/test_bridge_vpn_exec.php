<?php
define('ENVIRONMENT', 'development');
define('FCPATH', '/mnt/volume_blr1_1790148792836_eatria/www/abdm-bridge-gateway/public/');
chdir(FCPATH);

require '/mnt/volume_blr1_1790148792836_eatria/www/abdm-bridge-gateway/app/Config/Paths.php';
$paths = new Config\Paths();
define('APPPATH', realpath($paths->appDirectory) . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath($paths->appDirectory . '/../') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);

require $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootTest($paths);

$gw = new \App\Controllers\AbdmGateway();
$refl = new \ReflectionClass($gw);

// 1. Check resolveHospitalVpnUrl
$method = $refl->getMethod('resolveHospitalVpnUrl');
$method->setAccessible(true);
$url = $method->invoke($gw, 'IN0510000871');
echo 'RESOLVED_VPN_URL=' . $url . PHP_EOL;

// 2. Check forwardToHms
$res = $refl->getMethod('forwardToHms')->invoke($gw, 'IN0510000871', 'records/fetch', [
    'requestId' => 'test-req',
    'transactionId' => 'test-tx',
    'consentId' => 'b7580b49-a8ba-40cb-b9d1-cbf3c59733d8',
    'hipId' => 'IN0510000871',
    'careContextReferences' => ['OPD-12-S28-20260923'],
]);

echo 'FORWARD_RESULT_OK=' . ($res['ok'] ?? 0) . PHP_EOL;
if (!empty($res['records'])) {
    echo 'RECORD_COUNT=' . count($res['records']) . PHP_EOL;
    echo 'FIRST_RECORD_REF=' . ($res['records'][0]['careContextReference'] ?? '') . PHP_EOL;
    echo 'BUNDLE_TYPE=' . ($res['records'][0]['bundle']['resourceType'] ?? '') . PHP_EOL;
} else {
    echo 'RAW_RESULT=' . json_encode($res) . PHP_EOL;
}
