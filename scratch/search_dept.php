<?php
$files = glob(__DIR__ . '/../ABDM_FHIR/examples.json/*.json');
foreach ($files as $f) {
    $content = file_get_contents($f);
    if (stripos($content, 'department') !== false || stripos($content, 'serviceType') !== false) {
        echo basename($f) . ":\n";
        if (preg_match_all('/"(?:serviceType|department)[^"]*"\s*:\s*[^,\n\}]+/i', $content, $m)) {
            print_r(array_unique($m[0]));
        }
        if (preg_match_all('/[^\n]*department[^\n]*/i', $content, $m2)) {
            foreach (array_slice(array_unique($m2[0]), 0, 5) as $line) {
                echo "  " . trim($line) . "\n";
            }
        }
    }
}
