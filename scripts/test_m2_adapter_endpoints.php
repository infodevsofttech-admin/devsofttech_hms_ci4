<?php

declare(strict_types=1);

/**
 * Local M2 adapter endpoint tester for HMS.
 *
 * Usage (dry run, prints equivalent curl):
 *   php scripts/test_m2_adapter_endpoints.php --base=http://127.0.0.1:8080 --token=YOUR_TOKEN --secret=YOUR_HMAC_SECRET --dry-run
 *
 * Usage (real HTTP calls):
 *   php scripts/test_m2_adapter_endpoints.php --base=http://127.0.0.1:8080 --token=YOUR_TOKEN --secret=YOUR_HMAC_SECRET
 */

$options = getopt('', ['base::', 'token::', 'secret::', 'abha::', 'dry-run', 'auto-credentials']);

$baseUrl = rtrim((string) ($options['base'] ?? getenv('M2_BASE_URL') ?: 'http://127.0.0.1:8080'), '/');
$token = trim((string) ($options['token'] ?? getenv('M2_TOKEN') ?: ''));
$secret = trim((string) ($options['secret'] ?? getenv('M2_SECRET') ?: ''));
$abha = trim((string) ($options['abha'] ?? getenv('M2_ABHA') ?: '91510165305101@sbx'));
$dryRun = array_key_exists('dry-run', $options);

if (array_key_exists('auto-credentials', $options)) {
    $dotEnv = loadDotEnv(__DIR__ . '/../.env');
    $dbConfig = [
        'host' => trim((string) ($dotEnv['database.default.hostname'] ?? getenv('database.default.hostname') ?: 'localhost')),
        'user' => trim((string) ($dotEnv['database.default.username'] ?? getenv('database.default.username') ?: 'root')),
        'pass' => trim((string) ($dotEnv['database.default.password'] ?? getenv('database.default.password') ?: '')),
        'name' => trim((string) ($dotEnv['database.default.database'] ?? getenv('database.default.database') ?: 'hms_ci4_2026')),
        'port' => (int) ($dotEnv['database.default.port'] ?? getenv('database.default.port') ?: 3306),
    ];

    $mysqli = @new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name'], $dbConfig['port']);
    if (! $mysqli->connect_errno) {
        if ($token === '') {
            $token = resolveRuntimeSetting(
                ['GATEWAY_TO_HMS_TOKEN', 'ABDM_GATEWAY_TO_HMS_TOKEN', 'EKA_GATEWAY_TOKEN', 'EATRIA_BRIDGE_TOKEN', 'ABDM_BRIDGE_TOKEN'],
                $dotEnv,
                $mysqli
            );
            $token = preg_replace('/^Bearer\s+/i', '', $token) ?? $token;
        }

        if ($secret === '') {
            $secret = resolveRuntimeSetting(['GATEWAY_TO_HMS_HMAC_SECRET', 'EKA_WEBHOOK_SECRET'], $dotEnv, $mysqli);
        }

        $mysqli->close();
    }
}

if ($token === '') {
    fwrite(STDERR, "Error: missing token. Provide --token or M2_TOKEN, or use --auto-credentials.\n");
    exit(1);
}

$nowUtc = gmdate('Y-m-d\\TH:i:s\\Z');
$reqBase = 'REQ-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
$txBase = 'TX-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(2)));

$tests = [
    [
        'name' => 'Health Check',
        'method' => 'GET',
        'path' => '/api/v1/abdm/gateway/health',
        'body' => null,
    ],
    [
        'name' => 'Discovery Care Contexts',
        'method' => 'POST',
        'path' => '/api/v1/abdm/gateway/discovery/care-contexts',
        'body' => [
            'request_id' => $reqBase . '-DISC',
            'transaction_id' => $txBase . '-DISC',
            'hospital_hfr_id' => 'IN0510000828',
            'abha_id' => $abha,
            'abha_address' => $abha,
            'patient' => [
                'name' => 'Demo Patient',
                'gender' => 'M',
                'year_of_birth' => 1985,
            ],
        ],
    ],
    [
        'name' => 'Health Information Fetch',
        'method' => 'POST',
        'path' => '/api/v1/abdm/gateway/health-information/fetch',
        'body' => [
            'request_id' => $reqBase . '-FETCH',
            'transaction_id' => $txBase . '-FETCH',
            'hospital_hfr_id' => 'IN0510000828',
            'consent_id' => 'CONS-DEMO-001',
            'abha_id' => $abha,
            'abha_address' => $abha,
            'date_range' => [
                'from' => '2026-01-01T00:00:00Z',
                'to' => $nowUtc,
            ],
            'care_context_references' => [],
        ],
    ],
    [
        'name' => 'Consent Upsert',
        'method' => 'POST',
        'path' => '/api/v1/abdm/gateway/consent/upsert',
        'body' => [
            'request_id' => $reqBase . '-CONS',
            'hospital_hfr_id' => 'IN0510000828',
            'patient_id' => 1,
            'consent_id' => 'CONS-DEMO-001',
            'status' => 'GRANTED',
            'abha_id' => $abha,
            'abha_address' => $abha,
            'purpose' => 'treatment',
            'date_range' => [
                'from' => '2026-01-01T00:00:00Z',
                'to' => $nowUtc,
            ],
            'hi_types' => ['OPConsultRecord', 'DiagnosticReportRecord'],
            'raw_notification' => ['source' => 'local-test-runner'],
        ],
    ],
    [
        'name' => 'Link Status',
        'method' => 'POST',
        'path' => '/api/v1/abdm/gateway/link/status',
        'body' => [
            'request_id' => $reqBase . '-LINK',
            'hospital_hfr_id' => 'IN0510000828',
            'abha_id' => $abha,
            'abha_address' => $abha,
            'care_context_reference' => 'OPD-1-' . gmdate('Ymd'),
            'status' => 'linked',
            'linked_at' => $nowUtc,
            'source' => 'hip_initiated',
        ],
    ],
];

foreach ($tests as $test) {
    $method = (string) $test['method'];
    $path = (string) $test['path'];
    $body = $test['body'];
    $json = $body === null ? '' : (string) json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $requestId = 'REQ-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $timestamp = gmdate('Y-m-d\\TH:i:s\\Z');
    $signature = 'sha256=' . hash_hmac('sha256', $json, $secret !== '' ? $secret : '');

    $headers = [
        'Authorization: Bearer ' . $token,
        'X-Request-Id: ' . $requestId,
        'X-Timestamp: ' . $timestamp,
        'X-Eka-Signature: ' . $signature,
        'Accept: application/json',
    ];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
    }

    $url = $baseUrl . $path;

    echo "\n============================================================\n";
    echo 'TEST: ' . $test['name'] . "\n";
    echo $method . ' ' . $url . "\n";

    if ($dryRun) {
        $escapedHeaders = '';
        foreach ($headers as $h) {
            $escapedHeaders .= ' -H ' . escapeshellarg($h);
        }

        if ($method === 'GET') {
            echo 'curl -i -X GET ' . escapeshellarg($url) . $escapedHeaders . "\n";
        } else {
            echo 'curl -i -X POST ' . escapeshellarg($url) . $escapedHeaders . ' --data ' . escapeshellarg($json) . "\n";
        }
        echo "(dry-run only; request not sent)\n";
        continue;
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POSTFIELDS] = $json;
    }
    curl_setopt_array($ch, $opts);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        echo 'cURL error: [' . $errno . '] ' . $err . "\n";
        continue;
    }

    $decoded = json_decode((string) $raw, true);
    echo 'HTTP ' . $code . "\n";
    if (is_array($decoded)) {
        echo (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo (string) $raw . "\n";
    }
}

exit(0);

function loadDotEnv(string $envPath): array
{
    if (! is_file($envPath)) {
        return [];
    }

    $result = [];
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trimmed = trim((string) $line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim((string) $parts[0]);
        $val = trim((string) $parts[1]);
        $result[$key] = $val;
    }

    return $result;
}

function resolveRuntimeSetting(array $candidates, array $dotEnv, mysqli $mysqli): string
{
    foreach ($candidates as $name) {
        $v = getenv($name);
        if ($v !== false && trim((string) $v) !== '') {
            return trim((string) $v);
        }

        if (! empty($dotEnv[$name])) {
            return trim((string) $dotEnv[$name]);
        }

        $nameEsc = $mysqli->real_escape_string((string) $name);
        $sql = "SELECT s_value FROM hospital_setting WHERE s_name = '" . $nameEsc . "' LIMIT 1";
        $res = $mysqli->query($sql);
        if (! $res) {
            continue;
        }

        $row = $res->fetch_assoc();
        $dbValue = trim((string) ($row['s_value'] ?? ''));
        if ($dbValue !== '') {
            return $dbValue;
        }
    }

    return '';
}
