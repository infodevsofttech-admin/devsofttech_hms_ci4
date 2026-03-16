<?php
require __DIR__ . '/../vendor/autoload.php';
$tmpDir = __DIR__ . '/cache/mpdf';
if (!is_dir($tmpDir)) { mkdir($tmpDir, 0755, true); }
try {
    $m = new \Mpdf\Mpdf(['tempDir' => $tmpDir]);
    $m->WriteHTML('<p>Test PDF output</p>');
    $bytes = $m->Output('test.pdf', 'S');
    echo 'OK: ' . strlen($bytes) . ' bytes';
} catch (\Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
}
