<?php
/**
 * One-off diagnostic: dump the full raw response_json for a given
 * abdm_hiu_workflows.id, pretty-printed. Run from the CI4 project root on
 * the server. Delete this file after use.
 *
 * Usage:
 *   php83 check_workflow_response.php <id>
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

$id = (int) ($argv[1] ?? 0);
if ($id <= 0) {
    fwrite(STDERR, "Usage: php83 check_workflow_response.php <workflow_id>\n");
    exit(1);
}

$stmt = $mysqli->prepare("SELECT id, operation, request_json, response_json FROM abdm_hiu_workflows WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (! $row) {
    echo "No workflow row found for id={$id}\n";
    exit(0);
}

echo "=== workflow id={$row['id']} op={$row['operation']} ===\n\n";
echo "--- request_json ---\n";
echo json_encode(json_decode((string) $row['request_json'], true), JSON_PRETTY_PRINT) . "\n\n";
echo "--- response_json ---\n";
echo json_encode(json_decode((string) $row['response_json'], true), JSON_PRETTY_PRINT) . "\n";

$mysqli->close();
