<?php

declare(strict_types=1);

/**
 * First-time filesystem setup for CI4 writable/public upload folders.
 *
 * Run:
 *   php scripts/setup_fs_cli.php
 *   php scripts/setup_fs_cli.php --owner=www-data --group=www-data
 *
 * Notes:
 * - Best effort only; some operations may require sudo/root.
 * - Safe to run multiple times.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$dirs = [
    $projectRoot . '/writable',
    $projectRoot . '/writable/cache',
    $projectRoot . '/writable/logs',
    $projectRoot . '/writable/session',
    $projectRoot . '/writable/uploads',
    $projectRoot . '/writable/debugbar',
    $projectRoot . '/writable/tmp',
    $projectRoot . '/public/uploads',
];

$options = getopt('', ['owner::', 'group::', 'help']);
if (isset($options['help'])) {
    echo "Usage:\n";
    echo "  php scripts/setup_fs_cli.php [--owner=www-data] [--group=www-data]\n\n";
    echo "Examples:\n";
    echo "  php scripts/setup_fs_cli.php\n";
    echo "  sudo php scripts/setup_fs_cli.php --owner=www-data --group=www-data\n";
    exit(0);
}

$owner = isset($options['owner']) && is_string($options['owner']) ? trim($options['owner']) : '';
$group = isset($options['group']) && is_string($options['group']) ? trim($options['group']) : '';

$created = [];
$failedCreate = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0775, true)) {
            $created[] = $dir;
        } else {
            $failedCreate[] = $dir;
        }
    }
}

$chmodDirFail = [];
$chmodFileFail = [];
$chownFail = [];
$chgrpFail = [];

$applyToTree = static function (string $root, callable $onDir, callable $onFile): void {
    if (!is_dir($root)) {
        return;
    }

    $onDir($root);

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $item) {
        $path = $item->getPathname();
        if ($item->isDir()) {
            $onDir($path);
        } else {
            $onFile($path);
        }
    }
};

foreach ($dirs as $dir) {
    $applyToTree(
        $dir,
        static function (string $path) use (&$chmodDirFail, $owner, $group, &$chownFail, &$chgrpFail): void {
            if (!@chmod($path, 0775)) {
                $chmodDirFail[] = $path;
            }

            if ($owner !== '' && function_exists('chown') && !@chown($path, $owner)) {
                $chownFail[] = $path;
            }

            if ($group !== '' && function_exists('chgrp') && !@chgrp($path, $group)) {
                $chgrpFail[] = $path;
            }
        },
        static function (string $path) use (&$chmodFileFail, $owner, $group, &$chownFail, &$chgrpFail): void {
            if (!@chmod($path, 0664)) {
                $chmodFileFail[] = $path;
            }

            if ($owner !== '' && function_exists('chown') && !@chown($path, $owner)) {
                $chownFail[] = $path;
            }

            if ($group !== '' && function_exists('chgrp') && !@chgrp($path, $group)) {
                $chgrpFail[] = $path;
            }
        }
    );
}

$writableFail = [];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        $writableFail[] = $dir;
        continue;
    }

    $probe = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.write_test_' . str_replace('.', '', uniqid('', true)) . '.tmp';
    $ok = @file_put_contents($probe, 'ok');
    if ($ok === false) {
        $writableFail[] = $dir;
        continue;
    }
    @unlink($probe);
}

echo "Setup filesystem report\n";
echo "Project root: {$projectRoot}\n";
echo "Created dirs: " . count($created) . "\n";
echo "Failed create: " . count($failedCreate) . "\n";
echo "chmod dir failures: " . count($chmodDirFail) . "\n";
echo "chmod file failures: " . count($chmodFileFail) . "\n";
echo "chown failures: " . count($chownFail) . "\n";
echo "chgrp failures: " . count($chgrpFail) . "\n";
echo "Writable failures: " . count($writableFail) . "\n\n";

if ($created !== []) {
    echo "Created:\n";
    foreach ($created as $path) {
        echo " - {$path}\n";
    }
    echo "\n";
}

$printUnique = static function (string $title, array $items): void {
    $items = array_values(array_unique($items));
    if ($items === []) {
        return;
    }

    echo $title . "\n";
    foreach ($items as $path) {
        echo " - {$path}\n";
    }
    echo "\n";
};

$printUnique('Failed to create:', $failedCreate);
$printUnique('Not writable:', $writableFail);

if ($failedCreate !== [] || $writableFail !== []) {
    echo "Shell fallback (Linux):\n";
    echo '  sudo mkdir -p "' . $projectRoot . '/writable/cache" "' . $projectRoot . '/writable/logs" "' . $projectRoot . '/writable/session" "' . $projectRoot . '/writable/uploads" "' . $projectRoot . '/writable/debugbar" "' . $projectRoot . '/writable/tmp" "' . $projectRoot . '/public/uploads"' . "\n";
    echo '  sudo chown -R www-data:www-data "' . $projectRoot . '/writable" "' . $projectRoot . '/public/uploads"' . "\n";
    echo '  sudo find "' . $projectRoot . '/writable" -type d -exec chmod 775 {} \;' . "\n";
    echo '  sudo find "' . $projectRoot . '/writable" -type f -exec chmod 664 {} \;' . "\n";
    echo '  sudo chmod -R 775 "' . $projectRoot . '/public/uploads"' . "\n";
    exit(2);
}

echo "All required folders are ready and writable.\n";
exit(0);
