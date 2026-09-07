<?php
$lines = file(__DIR__ . '/../app/Libraries/FhirR4Builder.php');
foreach ($lines as $i => $l) {
    if (strpos($l, "'encounter'") !== false && strpos($l, '=>') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
