<?php
/**
 * One-off diagnostic: wipe ALL M3 HIU data for a given ABHA address
 * (abdm_hiu_documents + abdm_hiu_workflows + abdm_hiu_consent_artifacts)
 * so a completely fresh consent request can be tested end-to-end without
 * any leftover test data/state from prior runs.
 *
 * Run from the CI4 project root on the server. Delete this file after use.
 *
 * Usage:
 *   php83 wipe_hiu_data_for_abha.php <abha_address>            (dry run)
 *   php83 wipe_hiu_data_for_abha.php <abha_address> --confirm   (actually delete)
 */

$envPath = __DIR__ . '/.env';
if (! is_file($envPath)) {
    fwrite(STDERR, "Could not find .env at {$envPath}\n");
    exit(1);
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}

$host = $env['database.default.hostname'] ?? 'localhost';
$db   = $env['database.default.database'] ?? '';
$user = $env['database.default.username'] ?? '';
$pass = $env['database.default.password'] ?? '';
$port = (int) ($env['database.default.port'] ?? 3306);

$mysqli = @new mysqli($host, $user, $pass, $db, $port ?: 3306);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: " . $mysqli->connect_error . "\n");
    exit(1);
}

echo "Connected to DB '{$db}' on {$host}.\n";

$abha = $argv[1] ?? '';
$confirm = in_array('--confirm', $argv, true);

if ($abha === '') {
    fwrite(STDERR, "Usage: php83 wipe_hiu_data_for_abha.php <abha_address> [--confirm]\n");
    exit(1);
}

$tables = [
    'abdm_hiu_documents'         => 'abha_address',
    'abdm_hiu_workflows'         => 'abha_address',
    'abdm_hiu_consent_artifacts' => 'abha_address',
];

$counts = [];
foreach ($tables as $table => $column) {
    $checkTable = $mysqli->query("SHOW TABLES LIKE '{$table}'");
    if (! $checkTable || $checkTable->num_rows === 0) {
        echo "\n(table '{$table}' does not exist, skipping)\n";
        continue;
    }

    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM {$table} WHERE {$column} = ?");
    $stmt->bind_param('s', $abha);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $counts[$table] = (int) ($row['c'] ?? 0);

    echo "\n=== {$table} rows matching {$column}='{$abha}' ===\n";
    echo "Count: {$counts[$table]}\n";
}

$total = array_sum($counts);
if ($total === 0) {
    echo "\n(no rows found in any table — nothing to delete)\n";
    exit(0);
}

if (! $confirm) {
    echo "\nDry run only. Total rows across all tables: {$total}.\n";
    echo "Re-run with --confirm to actually delete these rows.\n";
    exit(0);
}

foreach ($tables as $table => $column) {
    if (! isset($counts[$table]) || $counts[$table] === 0) {
        continue;
    }
    $del = $mysqli->prepare("DELETE FROM {$table} WHERE {$column} = ?");
    $del->bind_param('s', $abha);
    $del->execute();
    echo "\nDeleted {$mysqli->affected_rows} row(s) from {$table} for {$column}='{$abha}'.\n";
}

echo "\nDone. All M3 HIU data wiped for abha_address='{$abha}'. Ready for a fresh consent request test.\n";
