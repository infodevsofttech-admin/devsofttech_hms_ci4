<?php
define('FCPATH', __DIR__ . '/../public/');
require __DIR__ . '/../vendor/autoload.php';
$bootstrap = require __DIR__ . '/../app/Config/Boot/development.php';
$app = \Config\Services::codeigniter();
$app->initialize();

$gw = new \App\Controllers\AbdmGateway();
$payload = $gw->buildIpdDischargeGatewayPayload(9, 11, '91510165305101', true);
if (!$payload) {
    echo "Payload is null\n";
    exit(1);
}
$bundle = $payload['bundle'];
echo "Bundle ID: " . $bundle['id'] . "\n";
echo "Total entries: " . count($bundle['entry']) . "\n";
$validator = new \App\Libraries\Abdm\Fhir\Validators\FhirBundleValidator();
$val = $validator->validate($bundle);
echo "Validation passed: " . ($val['valid'] ? 'YES' : 'NO') . "\n";
if (!$val['valid']) {
    print_r($val['errors']);
}
foreach ($bundle['entry'] as $idx => $entry) {
    $res = $entry['resource'];
    echo sprintf("[%02d] %-25s ID: %s\n", $idx, $res['resourceType'], $res['id'] ?? 'none');
}
