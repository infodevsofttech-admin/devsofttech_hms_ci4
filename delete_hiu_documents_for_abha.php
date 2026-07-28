<?php
/**
 * One-off diagnostic: delete abdm_hiu_documents rows for a given ABHA address
 * so a fresh end-to-end fetch test can be re-run without old persisted rows
 * masking whether new document_persisted counts are real. Does NOT touch
 * abdm_hiu_workflows (audit trail is preserved).
 *
 * Run from the CI4 project root on the server. Delete this file after use.
 *
 * Usage:
 *   php83 delete_hiu_documents_for_abha.php <abha_address> --confirm
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
    fwrite(STDERR, "Usage: php83 delete_hiu_documents_for_abha.php <abha_address> --confirm\n");
    exit(1);
}

$stmt = $mysqli->prepare('SELECT id, care_context_reference, document_title, document_date FROM abdm_hiu_documents WHERE abha_address = ?');
$stmt->bind_param('s', $abha);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}

echo "\n=== Matching abdm_hiu_documents rows for abha_address='{$abha}' ===\n";
if (empty($rows)) {
    echo "(no rows found — nothing to delete)\n";
    exit(0);
}
foreach ($rows as $row) {
    print_r($row);
}
echo "\nTotal: " . count($rows) . " row(s)\n";

if (! $confirm) {
    echo "\nDry run only. Re-run with --confirm to actually delete these rows.\n";
    exit(0);
}

$del = $mysqli->prepare('DELETE FROM abdm_hiu_documents WHERE abha_address = ?');
$del->bind_param('s', $abha);
$del->execute();

echo "\nDeleted " . $mysqli->affected_rows . " row(s) for abha_address='{$abha}'.\n";
