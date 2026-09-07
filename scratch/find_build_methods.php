<?php
$lines = file(__DIR__ . '/../app/Libraries/FhirR4Builder.php');
foreach ($lines as $i => $l) {
    if (preg_match('/public function (build\w+)/', $l, $m)) {
        echo ($i + 1) . ': ' . $m[1] . PHP_EOL;
    }
}
