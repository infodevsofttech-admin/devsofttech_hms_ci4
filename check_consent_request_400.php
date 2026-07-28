<?php
/**
 * One-off diagnostic: dumps the request_json and response_json for the most
 * recent "consent_request" workflow rows, to see the RAW bridge response
 * behind a generic "HTTP 400" error shown in the New Consent Request modal.
 *
 * Usage:
 *   php83 check_consent_request_400.php          -> last 5 consent_request rows
 *   php83 check_consent_request_400.php <id>     -> full dump for one row id
 *
 * Reads DB credentials from the app's own .env (does not print them).
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

if ($db === '' || $user === '') {
    fwrite(STDERR, "Could not resolve DB credentials from .env (looked for database.default.* keys)\n");
    exit(1);
}

$mysqli = @new mysqli($host, $user, $pass, $db, $port ?: 3306);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connect failed: " . $mysqli->connect_error . "\n");
    exit(1);
}

echo "Connected to DB '{$db}' on {$host}.\n\n";

$id = (int) ($argv[1] ?? 0);

if ($id > 0) {
    $stmt = $mysqli->prepare("SELECT id, operation, workflow_state, status, http_code, is_retryable,
                                      consent_id, abha_address, transaction_id, last_error, created_at,
                                      request_json, response_json
                               FROM abdm_hiu_workflows WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (! $row) {
        echo "No row found for id={$id}\n";
        exit(0);
    }
    foreach (['id', 'operation', 'workflow_state', 'status', 'http_code', 'is_retryable', 'consent_id', 'abha_address', 'transaction_id', 'last_error', 'created_at'] as $col) {
        echo str_pad($col, 16) . ": " . ($row[$col] ?? '') . "\n";
    }
    echo "\n--- request_json ---\n";
    $reqDecoded = json_decode((string) $row['request_json'], true);
    echo $reqDecoded !== null ? json_encode($reqDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $row['request_json'];
    echo "\n\n--- response_json ---\n";
    $respDecoded = json_decode((string) $row['response_json'], true);
    echo $respDecoded !== null ? json_encode($respDecoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $row['response_json'];
    echo "\n";
    exit(0);
}

$res = $mysqli->query("SELECT id, operation, workflow_state, status, http_code, is_retryable,
                               consent_id, abha_address, transaction_id, last_error, created_at
                        FROM abdm_hiu_workflows
                        WHERE operation = 'consent_request'
                        ORDER BY id DESC LIMIT 5");
if (! $res) {
    fwrite(STDERR, "Query failed: " . $mysqli->error . "\n");
    exit(1);
}

echo "Last 5 'consent_request' workflow rows:\n";
while ($row = $res->fetch_assoc()) {
    echo "----\n";
    foreach (['id', 'operation', 'workflow_state', 'status', 'http_code', 'is_retryable', 'consent_id', 'abha_address', 'transaction_id', 'last_error', 'created_at'] as $col) {
        echo str_pad($col, 16) . ": " . ($row[$col] ?? '') . "\n";
    }
}
echo "\nRun 'php83 check_consent_request_400.php <id>' on one of the above ids to see the full raw request/response JSON.\n";
