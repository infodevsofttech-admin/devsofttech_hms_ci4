<?php
require __DIR__ . '/../vendor/autoload.php';

// Check if directory uploads/abdm/ipd/9 exists or let's clean it to test auto-generation
$uploadDir = __DIR__ . '/../writable/uploads/abdm/ipd/9';
echo "Upload dir: " . realpath($uploadDir) . "\n";
if (is_dir($uploadDir)) {
    $files = glob($uploadDir . '/*');
    foreach ($files as $f) {
        echo "File: " . basename($f) . " (" . filesize($f) . " bytes)\n";
    }
}
