<?php
require 'd:/Workplace/HMS_CI4_OLD/vendor/codeigniter4/framework/system/Test/bootstrap.php';

$gateway = new App\Controllers\AbdmGateway();
$db = \Config\Database::connect('default');
$dbProperty = new ReflectionProperty($gateway, 'db');
$dbProperty->setValue($gateway, $db);

$reflection = new ReflectionMethod($gateway, 'buildImmunizationGatewayPayload');
$payload = $reflection->invoke($gateway, 11, 10, '91510165305101', false);
if ($payload === null) {
    echo "PAYLOAD IS NULL";
} else {
    foreach ($payload['bundle']['entry'] as $entry) {
        if ($entry['resource']['resourceType'] === 'Organization') {
            echo "ORGANIZATION RESOURCE:\n";
            echo json_encode($entry['resource'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}
