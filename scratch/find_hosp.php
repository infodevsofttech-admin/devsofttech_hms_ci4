<?php
$lines = file(__DIR__ . '/../app/Controllers/AbdmGateway.php');
foreach ($lines as $i => $l) {
    if (strpos($l, 'getHospitalProfile') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
