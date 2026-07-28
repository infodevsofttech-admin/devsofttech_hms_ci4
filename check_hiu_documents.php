<?php
/**
 * One-off diagnostic: lists abdm_hiu_documents rows (columns + rows for a
 * given ABHA address + total count). Reads DB credentials from the app's
 * own .env so it works unmodified on both local and production.
 *
 * Usage:
 *   php83 check_hiu_documents.php                     -> rows for 91510165305101@sbx
 *   php83 check_hiu_documents.php <abha_address>       -> rows for a specific ABHA
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
$dbName = $env['database.default.database'] ?? '';
$user = $env['database.default.username'] ?? '';
$pass = $env['database.default.password'] ?? '';
$port = (int) ($env['database.default.port'] ?? 3306);

if ($dbName === '' || $user === '') {
    fwrite(STDERR, "Could not resolve DB credentials from .env (looked for database.default.* keys)\n");
    exit(1);
}

$db = @new mysqli($host, $user, $pass, $dbName, $port ?: 3306);
if ($db->connect_errno) {
    fwrite(STDERR, "DB connect failed: " . $db->connect_error . "\n");
    exit(1);
}

$abha = trim((string) ($argv[1] ?? '91510165305101@sbx'));

echo "Connected to DB '{$dbName}' on {$host}.\n\n";

echo "=== abdm_hiu_documents columns ===\n";
$res = $db->query("SHOW COLUMNS FROM abdm_hiu_documents");
while ($row = $res->fetch_assoc()) { echo $row['Field'] . "\n"; }

echo "\n=== rows for abha {$abha} ===\n";
$stmt = $db->prepare("SELECT id, workflow_id, request_id, transaction_id, consent_ref, abha_address, patient_id, patient_name, care_context_reference, document_title, document_date, created_at, updated_at FROM abdm_hiu_documents WHERE abha_address = ?");
$stmt->bind_param('s', $abha);
$stmt->execute();
$res2 = $stmt->get_result();
while ($row = $res2->fetch_assoc()) { print_r($row); }

echo "\n=== total row count in table ===\n";
$res3 = $db->query("SELECT COUNT(*) c FROM abdm_hiu_documents");
print_r($res3->fetch_assoc());
$db->close();
