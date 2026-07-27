<?php
/**
 * One-off diagnostic: run this from the CI4 project root on the SERVER
 * (e.g. /var/www/html/hms_etria) to inspect why a "Completed" ABDM data_fetch
 * produced zero rows in abdm_hiu_documents.
 *
 * Usage:
 *   php check_remote_abdm_fetch.php                 -> shows last 5 data_fetch workflow rows
 *   php check_remote_abdm_fetch.php REQ-xxxxxxxxx    -> shows detail for that request_id
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

$requestId = trim($argv[1] ?? '');

if ($requestId !== '') {
    $stmt = $mysqli->prepare("SELECT id, operation, workflow_state, status, http_code, is_retryable, retry_count,
                                      consent_id, abdm_consent_artifact_id, abha_address, transaction_id,
                                      last_error, created_at, updated_at, completed_at,
                                      LENGTH(response_json) AS response_len, LENGTH(request_json) AS request_len
                               FROM abdm_hiu_workflows WHERE request_id = ? ORDER BY id ASC");
    $stmt->bind_param('s', $requestId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo "No abdm_hiu_workflows rows found for request_id={$requestId}\n";
    }
    while ($row = $res->fetch_assoc()) {
        echo "--- workflow id={$row['id']} op={$row['operation']} ---\n";
        foreach ($row as $k => $v) {
            echo "  {$k}: " . (is_null($v) ? 'NULL' : $v) . "\n";
        }
        echo "\n";
    }

    // Try to show a snippet of response_json for the data_fetch op specifically,
    // to see if the bridge actually returned any careContexts/entries.
    $stmt2 = $mysqli->prepare("SELECT id, response_json FROM abdm_hiu_workflows WHERE request_id = ? AND operation = 'data_fetch' ORDER BY id DESC LIMIT 1");
    $stmt2->bind_param('s', $requestId);
    $stmt2->execute();
    $r2 = $stmt2->get_result()->fetch_assoc();
    if ($r2 && $r2['response_json']) {
        $json = json_decode($r2['response_json'], true);
        echo "=== data_fetch response_json summary (id={$r2['id']}) ===\n";
        if (is_array($json)) {
            echo "Top-level keys: " . implode(', ', array_keys($json)) . "\n";
            // Common shapes to look for
            foreach (['careContexts', 'entries', 'records', 'bundles', 'data', 'result'] as $key) {
                if (isset($json[$key]) && is_array($json[$key])) {
                    echo "  {$key}: " . count($json[$key]) . " item(s)\n";
                }
            }
            echo "Raw length: " . strlen($r2['response_json']) . " bytes\n";
        } else {
            echo "response_json could not be decoded as JSON. Length: " . strlen($r2['response_json']) . "\n";
        }
    } else {
        echo "No data_fetch response_json stored for this request_id.\n";
    }

    // Check whether any documents got persisted for this request_id / transaction_id
    $stmt3 = $mysqli->prepare("SELECT COUNT(*) AS c FROM abdm_hiu_documents WHERE request_id = ?");
    $stmt3->bind_param('s', $requestId);
    $stmt3->execute();
    $c = $stmt3->get_result()->fetch_assoc();
    echo "\nabdm_hiu_documents rows with this exact request_id: {$c['c']}\n";
} else {
    echo "=== Last 5 data_fetch workflow rows ===\n";
    $res = $mysqli->query("SELECT id, request_id, workflow_state, status, http_code, last_error, created_at
                            FROM abdm_hiu_workflows WHERE operation = 'data_fetch' ORDER BY id DESC LIMIT 5");
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
    echo "\nTotal abdm_hiu_documents rows: ";
    print_r($mysqli->query("SELECT COUNT(*) c FROM abdm_hiu_documents")->fetch_assoc());
    echo "\nRun again with the request_id as an argument for full detail, e.g.:\n";
    echo "  php check_remote_abdm_fetch.php REQ-20260728043833-7c0d9947\n";
}

$mysqli->close();
