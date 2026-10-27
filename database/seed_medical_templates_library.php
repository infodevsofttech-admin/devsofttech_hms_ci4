<?php

/**
 * Standalone Medical Templates Library Seeder Runner
 *
 * Can be executed directly from command line:
 *   php database/seed_medical_templates_library.php
 */

$cmd = 'php ' . escapeshellarg(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'spark') . ' db:seed MedicalTemplatesLibrarySeeder';
passthru($cmd, $returnVar);

if ($returnVar === 0) {
    echo PHP_EOL . "Medical templates library successfully seeded!" . PHP_EOL;
} else {
    echo PHP_EOL . "Error running seeder (exit code: {$returnVar})." . PHP_EOL;
    exit($returnVar);
}
