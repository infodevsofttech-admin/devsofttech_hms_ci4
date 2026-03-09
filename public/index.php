<?php

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * EMERGENCY FILESYSTEM PREPARE (PRE-BOOTSTRAP)
 *---------------------------------------------------------------
 * Usage: /?fs_prepare=1&key=YOUR_SETUP_KEY
 * Runs before CodeIgniter boot so it works even when cache init fails.
 */
if (isset($_GET['fs_prepare']) && (string) $_GET['fs_prepare'] === '1') {
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
        echo "Invalid or missing key. Use /?fs_prepare=1&key=YOUR_KEY\n";
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
    $writableOk = [];
    $failed = [];

    foreach ($paths as $path) {
        if (!is_dir($path)) {
            if (@mkdir($path, 0775, true)) {
                $created[] = $path;
            }
        }

        if (is_dir($path)) {
            @chmod($path, 0775);

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
    echo "Writable OK: " . count($writableOk) . "/" . count($paths) . "\n\n";

    if (!empty($failed)) {
        echo "FAILED paths (run on server shell):\n";
        foreach (array_values(array_unique($failed)) as $item) {
            echo " - {$item}\n";
        }

        $writablePath = $projectRoot . '/writable';
        $uploadsPath = $projectRoot . '/public/uploads';

        echo "\nCommands:\n";
        echo "sudo chown -R www-data:www-data \"{$writablePath}\" \"{$uploadsPath}\"\n";
        echo "sudo find \"{$writablePath}\" -type d -exec chmod 775 {} \\;\n";
        echo "sudo find \"{$writablePath}\" -type f -exec chmod 664 {} \\;\n";
        echo "sudo chmod -R 775 \"{$uploadsPath}\"\n";
    } else {
        echo "All required paths are writable.\n";
    }

    exit(0);
}

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.2'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * TEMPORARY SERVER DEBUG FALLBACK
 *---------------------------------------------------------------
 * New servers sometimes force CI_ENVIRONMENT=production via Apache/PHP-FPM,
 * which hides the real 500 error details. Keep this block while debugging
 * migration issues, then remove it after root cause is fixed.
 */
$_SERVER['CI_ENVIRONMENT'] = 'development';
putenv('CI_ENVIRONMENT=development');
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');

$fallbackErrorLog = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'hms_ci4_php_errors.log';
ini_set('error_log', $fallbackErrorLog);

register_shutdown_function(static function () use ($fallbackErrorLog): void {
    $lastError = error_get_last();
    if ($lastError === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (! in_array((int) ($lastError['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    $line = sprintf(
        "[%s] FATAL %s in %s:%d\n",
        date('Y-m-d H:i:s'),
        (string) ($lastError['message'] ?? 'Unknown fatal error'),
        (string) ($lastError['file'] ?? 'unknown'),
        (int) ($lastError['line'] ?? 0)
    );

    @file_put_contents($fallbackErrorLog, $line, FILE_APPEND);
});

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
