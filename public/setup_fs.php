<?php

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$projectRoot = dirname(__DIR__);
$envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

$envKey = '';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }

            if (stripos($line, 'setup.dbToolsKey') !== 0) {
                continue;
            }

            $parts = explode('=', $line, 2);
            $envKey = trim((string) ($parts[1] ?? ''), " \t\n\r\0\x0B\"'");
            break;
        }
    }
}

$requestKey = trim((string) ($_GET['key'] ?? ''));
if ($envKey === '') {
    http_response_code(403);
    echo "setup.dbToolsKey is not configured in .env\n";
    exit(1);
}

if ($requestKey === '' || !hash_equals($envKey, $requestKey)) {
    http_response_code(403);
    echo "Invalid or missing key. Use: /setup_fs.php?key=YOUR_KEY\n";
    exit(1);
}

$paths = [
    $projectRoot . DIRECTORY_SEPARATOR . 'writable',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'cache',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'logs',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'session',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'uploads',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'debugbar',
    $projectRoot . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR . 'tmp',
    $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
];

$created = [];
$chmodOk = [];
$writableOk = [];
$failed = [];

foreach ($paths as $path) {
    if (!is_dir($path)) {
        if (@mkdir($path, 0775, true)) {
            $created[] = $path;
        }
    }

    if (is_dir($path)) {
        if (@chmod($path, 0775)) {
            $chmodOk[] = $path;
        }

        $probe = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.write_test_' . str_replace('.', '', uniqid('', true)) . '.tmp';
        $written = @file_put_contents($probe, 'ok');
        if ($written !== false) {
            @unlink($probe);
            $writableOk[] = $path;
        } else {
            $failed[] = $path;
        }
    } else {
        $failed[] = $path;
    }
}

echo "Filesystem preparation complete\n";
echo "Project root: {$projectRoot}\n";
echo "Created dirs: " . count($created) . "\n";
echo "chmod attempted: " . count($chmodOk) . "\n";
echo "Writable OK: " . count($writableOk) . "/" . count($paths) . "\n\n";

if (!empty($created)) {
    echo "Created:\n";
    foreach ($created as $item) {
        echo " - {$item}\n";
    }
    echo "\n";
}

if (!empty($failed)) {
    echo "FAILED paths (need server shell fix):\n";
    foreach (array_values(array_unique($failed)) as $item) {
        echo " - {$item}\n";
    }

    $writablePath = $projectRoot . '/writable';
    $uploadsPath = $projectRoot . '/public/uploads';

    echo "\nRun these on server shell (example www-data):\n";
    echo "sudo chown -R www-data:www-data \"{$writablePath}\" \"{$uploadsPath}\"\n";
    echo "sudo find \"{$writablePath}\" -type d -exec chmod 775 {} \\;\n";
    echo "sudo find \"{$writablePath}\" -type f -exec chmod 664 {} \\;\n";
    echo "sudo chmod -R 775 \"{$uploadsPath}\"\n";
} else {
    echo "All required paths are writable.\n";
}

echo "\nNext:\n";
echo "1) Open /setup/db-tools?key=... again\n";
echo "2) Delete this file after success: public/setup_fs.php\n";
