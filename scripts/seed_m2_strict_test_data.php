<?php

declare(strict_types=1);

$abha = $argv[1] ?? '91510165305101@sbx';
$patientId = (int)($argv[2] ?? 900001);

$envPath = __DIR__ . '/../.env';
$env = [];
if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trim = trim((string) $line);
        if ($trim === '' || str_starts_with($trim, '#')) {
            continue;
        }
        $parts = explode('=', $trim, 2);
        if (count($parts) === 2) {
            $env[trim((string) $parts[0])] = trim((string) $parts[1]);
        }
    }
}

$dbHost = $env['database.default.hostname'] ?? 'localhost';
$dbUser = $env['database.default.username'] ?? 'root';
$dbPass = $env['database.default.password'] ?? '';
$dbName = $env['database.default.database'] ?? 'hms_data_ci4';
$dbPort = (int) ($env['database.default.port'] ?? 3306);

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($mysqli->connect_errno) {
    fwrite(STDERR, 'DB connection failed: ' . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$check = $mysqli->query("SHOW TABLES LIKE 'patient_records'");
if (! $check || ! $check->fetch_row()) {
    fwrite(STDERR, "Table patient_records not found in {$dbName}" . PHP_EOL);
    exit(1);
}

$fhirBundle = [
    'resourceType' => 'Bundle',
    'type' => 'collection',
    'entry' => [
        [
            'resource' => [
                'resourceType' => 'Patient',
                'id' => 'p-' . $patientId,
                'identifier' => [
                    [
                        'system' => 'https://healthid.abdm.gov.in',
                        'value' => $abha,
                    ],
                ],
                'name' => [
                    [
                        'text' => 'M2 Strict Test Patient',
                    ],
                ],
            ],
        ],
    ],
];

$fhirJson = json_encode($fhirBundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (! is_string($fhirJson)) {
    fwrite(STDERR, 'Failed to encode sample FHIR bundle' . PHP_EOL);
    exit(1);
}

$now = date('Y-m-d H:i:s');
$expiry = date('Y-m-d', strtotime('+3 years'));
$consentId = 'CONS-STRICT-' . date('YmdHis');

$deleteStmt = $mysqli->prepare('DELETE FROM patient_records WHERE abha_id = ? AND patient_id = ?');
$deleteStmt->bind_param('si', $abha, $patientId);
$deleteStmt->execute();
$deleteStmt->close();

$insertSql = 'INSERT INTO patient_records (patient_id, abha_id, consent_id, record_type, fhir_resource, created_at, updated_at, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
$insertStmt = $mysqli->prepare($insertSql);
if (! $insertStmt) {
    fwrite(STDERR, 'Prepare failed: ' . $mysqli->error . PHP_EOL);
    exit(1);
}

$recordType = 'OPD';
$status = 'ACTIVE';
$insertStmt->bind_param('issssssss', $patientId, $abha, $consentId, $recordType, $fhirJson, $now, $now, $expiry, $status);
$ok = $insertStmt->execute();
if (! $ok) {
    fwrite(STDERR, 'Insert failed: ' . $insertStmt->error . PHP_EOL);
    $insertStmt->close();
    exit(1);
}

$recordId = $insertStmt->insert_id;
$insertStmt->close();
$mysqli->close();

echo 'Seeded patient_records row id=' . $recordId . ', abha=' . $abha . ', patient_id=' . $patientId . PHP_EOL;
