<?php
/**
 * One-off diagnostic: search abdm_hiu_workflows and abdm_hiu_documents for a
 * given transaction_id / consent_id / care_context_reference substring.
 * Run from the CI4 project root on the server. Delete this file after use.
 *
 * Usage:
 *   php83 check_transaction_lookup.php <search_string> [operation_filter] [limit]
 *   php83 check_transaction_lookup.php 10b01dd7-062b-4fc1-91f1-82b3b1491eb6 consent_reconcile
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

$needle = $argv[1] ?? '';
if ($needle === '') {
    fwrite(STDERR, "Usage: php83 check_transaction_lookup.php <search_string>\n");
    exit(1);
}

$like = '%' . $mysqli->real_escape_string($needle) . '%';
$opFilter = trim((string) ($argv[2] ?? ''));
$limit = (int) ($argv[3] ?? 20);
if ($limit <= 0) {
    $limit = 20;
}

echo "\n=== abdm_hiu_workflows rows matching '{$needle}'" . ($opFilter !== '' ? " AND operation='{$opFilter}'" : '') . " (in request_json/response_json) ===\n";
$opClause = $opFilter !== '' ? " AND operation = '" . $mysqli->real_escape_string($opFilter) . "'" : '';
$sql = "SELECT id, operation, workflow_state, status, http_code, is_retryable, consent_id,
               abha_address, transaction_id, last_error, created_at, updated_at
        FROM abdm_hiu_workflows
        WHERE (request_json LIKE '{$like}' OR response_json LIKE '{$like}'
           OR transaction_id LIKE '{$like}' OR consent_id LIKE '{$like}'){$opClause}
        ORDER BY id DESC LIMIT {$limit}";
$res = $mysqli->query($sql);
if (! $res) {
    fwrite(STDERR, "Query failed: " . $mysqli->error . "\n");
} else {
    $found = 0;
    while ($row = $res->fetch_assoc()) {
        $found++;
        print_r($row);
    }
    if ($found === 0) {
        echo "(no matching rows)\n";
    }
}

echo "\n=== abdm_hiu_documents rows matching '{$needle}' ===\n";
$sql2 = "SELECT id, workflow_id, request_id, transaction_id, consent_ref, consent_artifact_id,
                abha_address, patient_id, patient_name, care_context_reference, document_title,
                document_date, created_at, updated_at
         FROM abdm_hiu_documents
         WHERE transaction_id LIKE '{$like}' OR consent_ref LIKE '{$like}'
            OR care_context_reference LIKE '{$like}' OR abha_address LIKE '{$like}'
         ORDER BY id DESC LIMIT 20";
$res2 = $mysqli->query($sql2);
if (! $res2) {
    fwrite(STDERR, "Query failed: " . $mysqli->error . "\n");
} else {
    $found2 = 0;
    while ($row = $res2->fetch_assoc()) {
        $found2++;
        print_r($row);
    }
    if ($found2 === 0) {
        echo "(no matching rows)\n";
    }
}
