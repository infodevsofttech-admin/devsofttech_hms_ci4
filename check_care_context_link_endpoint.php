<?php
/**
 * One-off diagnostic: test the e-Atria bridge's care-context-link endpoint
 * under two candidate paths to determine which one is actually live, since
 * app/Libraries/Abdm/Sync/AbdmGatewayPushClient.php::pushCareContextLink()
 * calls '/api/v1/abdm/gateway/care-context/link' and gets HTTP 404, while
 * the sibling pushRecord() correctly uses '/api/v3/records/push'.
 *
 * Does NOT print the bridge token. Only prints HTTP status codes + short
 * response bodies so we can see which path (if any) the bridge recognizes.
 *
 * Usage: php83 check_care_context_link_endpoint.php
 * Delete this file after use.
 */

$envPath = __DIR__ . '/.env';
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

$settings = [];
$res = $mysqli->query("SELECT s_name, s_value FROM hospital_setting WHERE s_name IN ('EATRIA_BRIDGE_URL','EATRIA_BRIDGE_TOKEN','ABDM_HFR_ID','H_HFR_ID')");
while ($row = $res->fetch_assoc()) {
    $settings[$row['s_name']] = $row['s_value'];
}

$baseUrl = rtrim($settings['EATRIA_BRIDGE_URL'] ?? '', '/');
$token   = trim($settings['EATRIA_BRIDGE_TOKEN'] ?? '');
$hfrId   = trim($settings['ABDM_HFR_ID'] ?? ($settings['H_HFR_ID'] ?? ''));

echo "Base URL: {$baseUrl}\n";
echo "HFR ID: {$hfrId}\n";
echo "Token configured: " . ($token !== '' ? 'yes (len=' . strlen($token) . ')' : 'NO') . "\n\n";

if ($baseUrl === '' || $token === '') {
    fwrite(STDERR, "Missing bridge URL or token in hospital_setting; aborting.\n");
    exit(1);
}

// Sanity check: confirm auth/connectivity works against a known endpoint
// before testing the candidate care-context-link paths.
$sanityUrl = rtrim($baseUrl, '/') . '/api/v3/gateway/status';
$sanityUrl = (string) preg_replace('#/api/api/#', '/api/', $sanityUrl);
$ch = curl_init($sanityUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    CURLOPT_TIMEOUT => 15,
]);
$sanityBody = curl_exec($ch);
$sanityCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "=== Sanity check: GET {$sanityUrl} ===\n";
echo "HTTP {$sanityCode}\n";
echo "Body: " . substr((string) $sanityBody, 0, 300) . "\n\n";

$paths = [
    '/api/v1/abdm/gateway/care-context/link',
    '/api/v3/care-context/link',
    '/api/v3/records/care-context/link',
    '/api/v1/hip/care-context/link',
    '/api/v3/gateway/care-context/link',
];

$payload = json_encode([
    'hfr_id' => $hfrId,
    'hospital_id' => 1,
    'patient' => [
        'patient_id' => 'TEST-DIAG-0001',
        'name' => 'Test Diagnostic Patient',
        'mobile' => '9999999999',
        'gender' => 'M',
        'year_of_birth' => 1990,
    ],
    'care_contexts' => [
        ['reference_number' => 'TEST-CC-0001', 'display' => 'Diagnostic Test Care Context'],
    ],
]);

foreach ($paths as $path) {
    // Mirror AbdmGatewayPushClient::buildGatewayUrl(), which collapses a
    // duplicated "/api/api/" segment when $baseUrl already ends in "/api".
    $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    $url = (string) preg_replace('#/api/api/#', '/api/', $url);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    echo "=== POST {$url} ===\n";
    echo "HTTP {$httpCode}" . ($err ? " (curl error: {$err})" : '') . "\n";
    echo "Body: " . substr((string) $body, 0, 400) . "\n\n";
}

$mysqli->close();
