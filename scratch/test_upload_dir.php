<?php
define('FCPATH', __DIR__ . '/../public/');

$dir = FCPATH . 'uploads/doctor_notes/';
echo "Target Dir: " . $dir . "\n";

if (!is_dir($dir)) {
    $created = @mkdir($dir, 0777, true);
    echo "Directory created: " . ($created ? "YES" : "NO") . "\n";
} else {
    echo "Directory exists: YES\n";
}

$files = scandir($dir);
echo "Files in dir (" . count($files) . "):\n";
print_r($files);
