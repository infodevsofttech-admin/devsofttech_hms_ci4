<?php

namespace App\Libraries\Abdm;

/**
 * EAtriaBridgeConnector
 *
 * Calls the e-Atria ABDM Bridge Gateway (abdm-bridge.e-atria.in) directly.
 * All operations are synchronous HTTP calls to /api/v3/* endpoints.
 *
 * Gateway API reference: https://abdm-bridge.e-atria.in/api/v3/health
 *
 * Configure via .env:
 *   abdm.connector          = eatria_bridge
 *   EATRIA_BRIDGE_URL       = https://abdm-bridge.e-atria.in/api
 *   EATRIA_BRIDGE_TOKEN     = <api-key-from-gateway-admin>
 *   EATRIA_BRIDGE_TIMEOUT   = 30
 */
class EAtriaBridgeConnector implements AbdmConnectorInterface
{
    private string $baseUrl;
    private string $token;
    private string $hfrId;
    private string $bridgeHospitalId;
    private int    $timeoutSec;
    private bool   $sslVerify = true;
    /** @var array<int, string> */
    private array $tokenCandidates = [];
    /** @var array<string, string> */
    private array $tokenSourceByValue = [];
    private string $tokenSource = 'config';

    public function __construct()
    {
        $config = config('AbdmConnector');

        $this->baseUrl    = rtrim((string) ($config->eatriaBridgeUrl ?? 'https://abdm-bridge.e-atria.in/api'), '/');
        $this->token      = $this->sanitizeBearerToken((string) ($config->eatriaBridgeToken ?? ''));
        if ($this->token !== '') {
            $this->tokenCandidates = [$this->token];
            $this->tokenSourceByValue[$this->token] = 'config.eatriaBridgeToken';
            $this->tokenSource = 'config.eatriaBridgeToken';
        }
        $this->hfrId      = '';
        $this->bridgeHospitalId = '';
        $this->timeoutSec = (int) ($config->eatriaBridgeTimeoutSec ?? 30);

        // DB (Admin Panel → ABDM Gateway Config) is the authoritative source for
        // the token, URL, and HFR ID. Initial load here; per-request refresh happens in httpCall().
        $this->refreshRuntimeSettingsFromDb();
    }

    private function refreshRuntimeSettingsFromDb(): void
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('hospital_setting')) {
                return;
            }

            $settingFields = $db->getFieldNames('hospital_setting') ?? [];
            $orderCol = null;
            foreach (['id', 's_id'] as $candidateOrderCol) {
                if (in_array($candidateOrderCol, $settingFields, true)) {
                    $orderCol = $candidateOrderCol;
                    break;
                }
            }

            $rowsBuilder = $db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', [
                    'EATRIA_BRIDGE_TOKEN',
                    'EATRIA_BRIDGE_URL',
                    'ABDM_HFR_ID',
                    'H_HFR_ID',
                    'ABDM_HOSPITAL_HFR_ID',
                    'ABDM_BRIDGE_HOSPITAL_ID',
                    'EATRIA_BRIDGE_HOSPITAL_ID',
                    'ABDM_BRIDGE_SSL_VERIFY',
                ]);

            // Ensure deterministic override when duplicate setting rows exist.
            // Read newest-first and keep first occurrence per s_name.
            if ($orderCol !== null) {
                $rowsBuilder->orderBy($orderCol, 'DESC');
            }

            $rows = $rowsBuilder->get()->getResultArray();

            $dbSettings = [];
            foreach ($rows as $row) {
                $key = trim((string) ($row['s_name'] ?? ''));
                if ($key === '' || array_key_exists($key, $dbSettings)) {
                    continue;
                }
                $dbSettings[$key] = (string) ($row['s_value'] ?? '');
            }

            $tokenCandidates = [];
            $tokenSourceByValue = [];
            foreach (['EATRIA_BRIDGE_TOKEN'] as $tokenKey) {
                $rawToken = trim((string) ($dbSettings[$tokenKey] ?? ''));
                if ($rawToken === '') {
                    continue;
                }
                $sanitized = $this->sanitizeBearerToken($rawToken);
                if ($sanitized === '' || in_array($sanitized, $tokenCandidates, true)) {
                    continue;
                }
                $tokenCandidates[] = $sanitized;
                $tokenSourceByValue[$sanitized] = 'hospital_setting.' . $tokenKey;
            }

            if (! empty($tokenCandidates)) {
                $this->tokenCandidates = $tokenCandidates;
                $this->tokenSourceByValue = $tokenSourceByValue;
                $this->token = $tokenCandidates[0];
                $this->tokenSource = $tokenSourceByValue[$this->token] ?? 'hospital_setting';
            }

            $dbUrl = trim((string) ($dbSettings['EATRIA_BRIDGE_URL'] ?? ''));
            if ($dbUrl !== '') {
                $host = (string) (parse_url($dbUrl, PHP_URL_HOST) ?? '');
                if ($host !== '' && in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true)) {
                    log_message('warning', '[EAtriaBridge] Ignoring localhost EATRIA_BRIDGE_URL from hospital_setting');
                } else {
                    $this->baseUrl = rtrim($dbUrl, '/');
                }
            }

            $dbHfrId = trim((string) (
                $dbSettings['ABDM_HFR_ID']
                ?? $dbSettings['H_HFR_ID']
                ?? $dbSettings['ABDM_HOSPITAL_HFR_ID']
                ?? ''
            ));
            if ($dbHfrId === '') {
                log_message('warning', '[EAtriaBridge] ABDM_HFR_ID is empty in hospital_setting; bridge requests may fail with hfr_id errors.');
            } else {
                $this->hfrId = $dbHfrId;
            }

            $dbBridgeHospitalId = trim((string) (
                $dbSettings['ABDM_BRIDGE_HOSPITAL_ID']
                ?? $dbSettings['EATRIA_BRIDGE_HOSPITAL_ID']
                ?? ''
            ));
            if ($dbBridgeHospitalId !== '') {
                $this->bridgeHospitalId = $dbBridgeHospitalId;
            }

            // SSL verification stays ON by default (secure). Admin → ABDM Gateway
            // page can opt out per-deployment for servers with a stale local CA
            // bundle (cURL error 60); an env override on that specific machine
            // takes precedence over the DB setting.
            $dbSslVerifyRaw = strtolower(trim((string) ($dbSettings['ABDM_BRIDGE_SSL_VERIFY'] ?? '')));
            if ($dbSslVerifyRaw !== '') {
                $this->sslVerify = ! in_array($dbSslVerifyRaw, ['0', 'false', 'no', 'off'], true);
            }
        } catch (\Throwable $e) {
            log_message('warning', '[EAtriaBridge] refreshRuntimeSettingsFromDb failed: ' . $e->getMessage());
        }
    }

    public function getConnectorName(): string
    {
        return 'eatria_bridge';
    }

    private function sanitizeBearerToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        // Operators sometimes paste "Bearer <token>" into settings.
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $token = trim($token, " \t\n\r\0\x0B\"'");
        // Operators may paste tokens with hidden whitespace/newlines.
        $token = (string) preg_replace('/\s+/u', '', $token);

        return $token;
    }

    private function normalizeBridgeGender($value): string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '1' || $v === 'M' || $v === 'MALE') {
            return 'M';
        }
        if ($v === '2' || $v === 'F' || $v === 'FEMALE') {
            return 'F';
        }
        if ($v === '3' || $v === 'O' || $v === 'OTHER' || $v === 'OTHERS') {
            return 'O';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function redactSensitiveForLog(array $payload): array
    {
        $walk = function ($value, string $key = '') use (&$walk) {
            if (is_array($value)) {
                $out = [];
                foreach ($value as $k => $v) {
                    $kk = (string) $k;
                    $out[$k] = $walk($v, $kk);
                }
                return $out;
            }

            $lowerKey = strtolower($key);
            if (in_array($lowerKey, [
                'token', 'refresh_token', 'refreshtoken', 'authorization', 'auth', 'otp',
                'loginid', 'login_id', 'mobile', 'mobilenumber', 'mobile_number', 'phone', 'phone_number',
            ], true)) {
                return '[REDACTED]';
            }

            if (in_array($lowerKey, ['photo', 'profilephoto', 'profile_photo'], true)) {
                return '[REDACTED_IMAGE_BASE64]';
            }

            if (is_string($value)) {
                $trimmed = trim($value);

                // Redact JWT-like values by shape.
                if (preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $trimmed)) {
                    return '[REDACTED_JWT]';
                }

                // Redact probable base64 blobs (e.g., profile photo) to keep logs small.
                if (strlen($trimmed) > 400 && preg_match('/^[A-Za-z0-9+\/=\r\n]+$/', $trimmed)) {
                    return '[REDACTED_LARGE_BASE64]';
                }
            }

            return $value;
        };

        return $walk($payload);
    }

    // -------------------------------------------------------------------------
    // Internal HTTP helper
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function post(string $path, array $body): array
    {
        return $this->httpCall('POST', $path, $body);
    }

    private function patch(string $path, array $body): array
    {
        return $this->httpCall('PATCH', $path, $body);
    }

    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $extraHeaders
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = [], array $extraHeaders = []): array
    {
        // e-Atria bridge expects hfr_id alongside Bearer auth for GET endpoints too.
        if ($this->hfrId !== '' && empty($query['hfr_id'])) {
            $query['hfr_id'] = $this->hfrId;
        }
        return $this->httpCall('GET', $path, [], $query, $extraHeaders);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, mixed>  $query
     * @param array<string, string> $extraHeaders
     * @return array<string, mixed>
     */
    private function httpCall(string $method, string $path, array $body = [], array $query = [], array $extraHeaders = []): array
    {
        // Credentials can be rotated from settings without restarting PHP workers.
        // Refresh before each call to avoid stale token/HFR causing 401/403.
        $this->refreshRuntimeSettingsFromDb();

        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $safeRequestBody = $this->redactSensitiveForLog($body);

        $callWithToken = function (string $tokenValue) use ($method, $url, $body, $safeRequestBody, $extraHeaders): array {
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
            ];
            if ($tokenValue !== '') {
                $headers[] = 'Authorization: Bearer ' . $tokenValue;
            }
            if ($this->bridgeHospitalId !== '') {
                $headers[] = 'X-Hospital-Id: ' . $this->bridgeHospitalId;
            }
            foreach ($extraHeaders as $headerName => $headerValue) {
                if (trim((string) $headerValue) !== '') {
                    $headers[] = $headerName . ': ' . $headerValue;
                }
            }

            $maskedToken = $tokenValue !== '' ? (substr($tokenValue, 0, 6) . '***' . substr($tokenValue, -4)) : '(none)';
            $tokenSource = $this->tokenSourceByValue[$tokenValue] ?? $this->tokenSource;
            log_message('debug', '[EAtriaBridge] --> ' . $method . ' ' . $url
                . ' | token=' . $maskedToken
                . ' | token_source=' . $tokenSource
                . ' | body=' . json_encode($safeRequestBody));

            // SSL verification stays ON by default. Some local WAMP/XAMPP
            // installs ship a stale bundled cacert.pem, causing cURL error 60
            // on outbound HTTPS calls. Fix that by updating curl.cainfo in
            // php.ini on the affected machine, or opt out for this specific
            // deployment via Admin → ABDM Gateway (or ABDM_BRIDGE_SSL_VERIFY=false
            // in that machine's .env, which takes precedence).
            $sslVerifyEnvRaw = strtolower(trim((string) (getenv('ABDM_BRIDGE_SSL_VERIFY') ?: '')));
            $sslVerify = $sslVerifyEnvRaw !== ''
                ? ! in_array($sslVerifyEnvRaw, ['0', 'false', 'no', 'off'], true)
                : $this->sslVerify;

            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeoutSec,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => $sslVerify,
                CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
                CURLOPT_CUSTOMREQUEST  => $method,
            ];

            if ($method !== 'GET' && $body !== []) {
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body);
            }

            curl_setopt_array($ch, $curlOptions);
            $raw = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            return [
                'raw' => (string) $raw,
                'http_code' => $httpCode,
                'curl_error' => $curlErr,
                'token' => $tokenValue,
            ];
        };

        if ($this->token === '') {
            log_message('error', '[EAtriaBridge] Authorization token missing for request: ' . $method . ' ' . $url);
            return [
                'ok' => 0,
                'http_code' => 0,
                'error_text' => 'Missing EATRIA_BRIDGE_TOKEN in hospital_setting',
            ];
        }

        if ($this->tokenSource !== 'hospital_setting.EATRIA_BRIDGE_TOKEN') {
            $tokenSha256 = hash('sha256', $this->token);
            log_message('error', '[EAtriaBridge] Rejecting non-hospital-setting token source=' . $this->tokenSource . ' for request: ' . $method . ' ' . $url);
            return [
                'ok' => 0,
                'http_code' => 0,
                'error_text' => 'Gateway auth token must come from hospital_setting.EATRIA_BRIDGE_TOKEN',
                'auth_debug' => [
                    'token_source' => $this->tokenSource,
                    'token_sha12' => substr($tokenSha256, 0, 12),
                    'token_sha256' => $tokenSha256,
                    'hfr_id' => $this->hfrId,
                    'base_url' => $this->baseUrl,
                ],
            ];
        }

        $attempt = $callWithToken($this->token);
        $raw = (string) ($attempt['raw'] ?? '');
        $httpCode = (int) ($attempt['http_code'] ?? 0);
        $curlErr = (string) ($attempt['curl_error'] ?? '');

        // Retry with alternate token candidates when gateway reports auth failure.
        if ($curlErr === '' && in_array($httpCode, [401, 403], true) && count($this->tokenCandidates) > 1) {
            foreach ($this->tokenCandidates as $candidateToken) {
                if ($candidateToken === (string) ($attempt['token'] ?? '')) {
                    continue;
                }

                log_message('warning', '[EAtriaBridge] auth failed with token source=' . ($this->tokenSourceByValue[(string) ($attempt['token'] ?? '')] ?? $this->tokenSource) . '; retrying with alternate token source=' . ($this->tokenSourceByValue[$candidateToken] ?? 'unknown'));
                $retryAttempt = $callWithToken($candidateToken);
                $retryHttpCode = (int) ($retryAttempt['http_code'] ?? 0);
                $retryCurlErr = (string) ($retryAttempt['curl_error'] ?? '');

                if ($retryCurlErr === '' && ! in_array($retryHttpCode, [401, 403], true)) {
                    $this->token = $candidateToken;
                    $this->tokenSource = $this->tokenSourceByValue[$candidateToken] ?? $this->tokenSource;
                    $attempt = $retryAttempt;
                    $raw = (string) ($retryAttempt['raw'] ?? '');
                    $httpCode = $retryHttpCode;
                    $curlErr = $retryCurlErr;
                    break;
                }
            }
        }

        $safeRawForLog = (string) $raw;
        $decodedRawForLog = json_decode((string) $raw, true);
        if (is_array($decodedRawForLog)) {
            $safeRawForLog = (string) json_encode($this->redactSensitiveForLog($decodedRawForLog), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        log_message('debug', '[EAtriaBridge] <-- HTTP ' . $httpCode . ' | raw=' . $safeRawForLog);

        $tokenSha256 = $this->token !== '' ? hash('sha256', $this->token) : '';
        $authDebug = [
            'token_source' => $this->tokenSource,
            'token_sha12' => $tokenSha256 !== '' ? substr($tokenSha256, 0, 12) : '',
            'token_sha256' => $tokenSha256,
            'hfr_id' => $this->hfrId,
            'base_url' => $this->baseUrl,
        ];

        if ($curlErr !== '') {
            log_message('error', '[EAtriaBridge] cURL error on ' . $url . ': ' . $curlErr);
            $this->dbLog($method, $path, $url, $body, 0, '', 'error', 'cURL error: ' . $curlErr . ' | auth=' . json_encode($authDebug));
            return ['ok' => 0, 'error_text' => 'cURL error: ' . $curlErr, 'http_code' => 0, 'auth_debug' => $authDebug];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $this->dbLog($method, $path, $url, $body, $httpCode, (string) $raw, 'error', 'Non-JSON response | auth=' . json_encode($authDebug));
            return ['ok' => 0, 'error_text' => 'Non-JSON response', 'http_code' => $httpCode, 'raw' => (string) $raw, 'auth_debug' => $authDebug];
        }

        $ok = ($httpCode >= 200 && $httpCode < 300) ? (int) ($decoded['ok'] ?? 1) : 0;
        $logErr = $ok === 0 ? (string) ($decoded['message'] ?? $decoded['error_text'] ?? '') : '';
        if ($ok === 0) {
            $logErr .= ($logErr !== '' ? ' | ' : '') . 'auth=' . json_encode($authDebug);
        }
        $this->dbLog($method, $path, $url, $body, $httpCode, (string) $raw, $ok === 1 ? 'success' : 'error', $logErr);
        if (($httpCode === 401 || $httpCode === 403) && ! isset($decoded['auth_hint'])) {
            $decoded['auth_hint'] = 'Verify hospital_setting.EATRIA_BRIDGE_TOKEN is mapped to the same HFR as hospital_setting.ABDM_HFR_ID. Current token source=' . $this->tokenSource . ', HFR=' . ($this->hfrId !== '' ? $this->hfrId : '(empty)');
        }
        $decoded['auth_debug'] = $authDebug;

        return array_merge($decoded, ['ok' => $ok, 'http_code' => $httpCode]);
    }

    // -------------------------------------------------------------------------
    // DB Audit Log
    // -------------------------------------------------------------------------

    /**
     * Write one row to abdm_api_logs (fail-open — never throws).
     *
     * @param array<string, mixed> $requestBody
     */
    private function dbLog(
        string $method,
        string $path,
        string $fullUrl,
        array  $requestBody,
        int    $httpCode,
        string $rawResponse,
        string $status,
        string $errorMessage
    ): void {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('abdm_api_logs')) {
                return;
            }

            // Derive a human-readable event type from the path, e.g. /v3/records/push → records.push
            $eventType = ltrim($path, '/');
            $eventType = preg_replace('#^v\d+/#', '', $eventType) ?? $eventType; // strip version prefix
            $eventType = str_replace('/', '.', $eventType);

            $decodedResp = json_decode($rawResponse, true);
            $responseJson = is_array($decodedResp)
                ? (string) json_encode($this->redactSensitiveForLog($decodedResp), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (trim($rawResponse) !== '' ? $rawResponse : null);

            $safeRequestBody = $this->redactSensitiveForLog($requestBody);

            $db->table('abdm_api_logs')->insert([
                'channel'       => 'eatria_bridge',
                'event_type'    => $eventType !== '' ? $eventType : $path,
                'endpoint'      => $fullUrl,
                'http_method'   => strtoupper($method),
                'entity_type'   => null,
                'entity_id'     => null,
                'request_json'  => (string) json_encode($safeRequestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_code' => $httpCode > 0 ? $httpCode : null,
                'response_json' => $responseJson,
                'status'        => $status,
                'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('warning', '[EAtriaBridge] dbLog failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // ABHA
    // -------------------------------------------------------------------------

    public function validateAbha(string $abhaId, array $fullPayload = []): array
    {
        $inputValue = '';
        $inputType  = 'abha-number';

        if ($fullPayload !== []) {
            $abhaAddress = trim((string) ($fullPayload['abha_address'] ?? ''));
            $abhaNumber  = trim((string) ($fullPayload['abha_id'] ?? $abhaId));

            if ($abhaAddress !== '') {
                $inputType  = 'abha-address';
                $inputValue = $abhaAddress;
            } else {
                $digits = preg_replace('/\D/', '', $abhaNumber);
                $inputValue = $digits !== '' ? $digits : $abhaNumber;
            }
        } else {
            $abhaRaw = trim((string) $abhaId);
            if (str_contains($abhaRaw, '@')) {
                $inputType  = 'abha-address';
                $inputValue = $abhaRaw;
            } else {
                $digits = preg_replace('/\D/', '', $abhaRaw);
                $inputValue = $digits !== '' ? $digits : $abhaRaw;
            }
        }

        $body = [
            'type'  => $inputType,
            'value' => $inputValue,
        ];
        if ($inputType === 'abha-address') {
            $body['abha_address'] = $inputValue;
        } else {
            $body['abha_id'] = $inputValue;
        }
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/login/search', $body);

        // Backward-compat: if a bridge does not yet expose login/search, retry legacy validate.
        if (empty($result['ok']) || (int) ($result['ok'] ?? 0) !== 1) {
            $httpCode = (int) ($result['http_code'] ?? 0);
            if (in_array($httpCode, [404, 405], true)) {
                $legacyBody = $inputType === 'abha-address'
                    ? ['abha_address' => $inputValue]
                    : ['abha_id' => $inputValue];
                if ($this->hfrId !== '') {
                    $legacyBody['hfr_id'] = $this->hfrId;
                }
                $result = $this->post('/v3/abha/validate', $legacyBody);
            }
        }

        if (empty($result['ok']) || (int) ($result['ok'] ?? 0) !== 1) {
            return $result;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $nestedResult = is_array($result['result'] ?? null) ? $result['result'] : [];
        $accounts = is_array($data['accounts'] ?? null) ? $data['accounts'] : [];
        $firstAccount = isset($accounts[0]) && is_array($accounts[0])
            ? $accounts[0]
            : (is_array($data['account'] ?? null) ? $data['account'] : []);

        $txnId = trim((string) (
            $result['txn_id'] ?? $result['txnId']
            ?? $data['txn_id'] ?? $data['txnId']
            ?? $data['transaction_id'] ?? $data['transactionId']
            ?? $nestedResult['txn_id'] ?? $nestedResult['txnId']
            ?? $nestedResult['transaction_id'] ?? $nestedResult['transactionId']
            ?? $firstAccount['txn_id'] ?? $firstAccount['txnId'] ?? ''
        ));

        $status = '';
        foreach ([
            $result['status'] ?? null,
            $result['account_status'] ?? null,
            $data['status'] ?? null,
            $data['account_status'] ?? null,
            $data['accountStatus'] ?? null,
            $data['abhaStatus'] ?? null,
            $firstAccount['status'] ?? null,
            $firstAccount['accountStatus'] ?? null,
            $firstAccount['abhaStatus'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $status = strtoupper(trim($candidate));
                break;
            }
        }

        $authMethods = [];
        foreach ([
            $result['authMethods'] ?? null,
            $result['auth_methods'] ?? null,
            $data['authMethods'] ?? null,
            $data['auth_methods'] ?? null,
            $data['availableAuthMethods'] ?? null,
            $nestedResult['authMethods'] ?? null,
            $nestedResult['auth_methods'] ?? null,
            $nestedResult['availableAuthMethods'] ?? null,
            $firstAccount['authMethods'] ?? null,
            $firstAccount['auth_methods'] ?? null,
            $firstAccount['availableAuthMethods'] ?? null,
        ] as $candidate) {
            if (is_array($candidate) && ! empty($candidate)) {
                foreach ($candidate as $method) {
                    $m = strtoupper(trim((string) $method));
                    if ($m !== '' && ! in_array($m, $authMethods, true)) {
                        $authMethods[] = $m;
                    }
                }
            }
        }

        if (! empty($authMethods)) {
            $result['auth_methods'] = $authMethods;
        }

        if ($txnId !== '') {
            $result['txn_id'] = $txnId;
        }

        $maskedMobile = trim((string) (
            $result['masked_mobile'] ?? $result['maskedMobile']
            ?? $data['masked_mobile'] ?? $data['maskedMobile'] ?? $data['mobile']
            ?? $data['mobile_number'] ?? $data['phone'] ?? $data['phone_number']
            ?? $firstAccount['masked_mobile'] ?? $firstAccount['maskedMobile'] ?? $firstAccount['mobile']
            ?? $firstAccount['mobile_number'] ?? $firstAccount['phone'] ?? $firstAccount['phone_number'] ?? ''
        ));
        if ($maskedMobile !== '') {
            $result['masked_mobile'] = $maskedMobile;
        }

        // HMS callers expect a status-like field; treat successful search as VALID when absent.
        $result['status'] = $status !== '' ? $status : 'VALID';
        return $result;
    }

    public function abhaLoginRequestOtp(array $payload): array
    {
        $authMethod = strtoupper((string) ($payload['auth_method'] ?? $payload['authMethod'] ?? ''));
        $body = [
            'txn_id' => (string) ($payload['txn_id'] ?? $payload['txnId'] ?? ''),
            'auth_method' => $authMethod,
        ];
        if (($payload['abha_id'] ?? '') !== '') { $body['abha_id'] = (string) $payload['abha_id']; }
        if (($payload['abha_address'] ?? '') !== '') { $body['abha_address'] = (string) $payload['abha_address']; }
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        return $this->post('/v3/abha/login/request-otp', $body);
    }

    public function abhaLoginVerifyOtp(array $payload): array
    {
        $authMethod = strtoupper((string) ($payload['auth_method'] ?? $payload['authMethod'] ?? ''));
        $body = [
            'txn_id' => (string) ($payload['txn_id'] ?? $payload['txnId'] ?? ''),
            'txnId' => (string) ($payload['txn_id'] ?? $payload['txnId'] ?? ''),
            'auth_method' => $authMethod,
            'otp' => (string) ($payload['otp'] ?? ''),
            'scope' => $authMethod === 'AADHAAR_OTP'
                ? ['abha-login', 'aadhaar-verify']
                : ['abha-login', 'mobile-verify'],
        ];
        if (isset($payload['authData']) && is_array($payload['authData'])) {
            $body['authData'] = $payload['authData'];
        }
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/login/verify-otp', $body);
        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        return $this->attachOfficialAbhaCard($result);
    }

    public function fetchOfficialAbhaCard(array $payload): array
    {
        $abhaNumber = trim((string) ($payload['abha_number'] ?? $payload['abhaNumber'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abhaAddress'] ?? ''));

        if ($abhaNumber === '' && $abhaAddress === '') {
            return ['ok' => 0, 'error_text' => 'ABHA number or ABHA address is required'];
        }

        $result = $this->get('/v3/abha/card', array_filter([
            'abha_number' => $abhaNumber,
            'abha_address' => $abhaAddress,
        ], static fn($value): bool => trim((string) $value) !== ''));

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : $result;
        $card = $this->extractOfficialCard($result) ?: $this->extractOfficialCard($data);
        $source = $this->extractCardSource($result) ?: $this->extractCardSource($data);

        if ($card === '' || $source !== 'abdm') {
            return [
                'ok' => 0,
                'error_text' => trim((string) ($result['message'] ?? $data['message'] ?? '')) ?: 'The Bridge did not return an official ABHA card.',
                'card_source' => $source,
                'request_id' => (string) ($result['request_id'] ?? ''),
            ];
        }

        return [
            'ok' => 1,
            'card_base64' => $card,
            'card_content_type' => $this->resolveCardContentType($data),
            'card_source' => $source,
            'request_id' => (string) ($result['request_id'] ?? ''),
        ];
    }

    /**
     * Ensure a verify/enrol response carries the official ABHA card, fetching it
     * from the Bridge card endpoint when the response itself omits it.
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function attachOfficialAbhaCard(array $result): array
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $account = is_array($result['account'] ?? null)
            ? $result['account']
            : (is_array($data['account'] ?? null) ? $data['account'] : (is_array($data['accounts'][0] ?? null) ? $data['accounts'][0] : []));
        $profile = is_array($data['ABHAProfile'] ?? null)
            ? $data['ABHAProfile']
            : (is_array($data['profile'] ?? null) ? $data['profile'] : []);
        $abhaNumber = trim((string) ($account['ABHANumber'] ?? $account['abhaNumber'] ?? $account['abha_id'] ?? $data['ABHANumber'] ?? $data['abhaNumber'] ?? $data['abha_id'] ?? $profile['ABHANumber'] ?? $profile['abhaNumber'] ?? $profile['abha_id'] ?? ''));
        $abhaAddress = trim((string) ($account['abhaAddress'] ?? $account['preferredAddress'] ?? $account['preferredAbhaAddress'] ?? $account['abha_address'] ?? $data['abhaAddress'] ?? $data['preferredAddress'] ?? $data['abha_address'] ?? $profile['preferredAbhaAddress'] ?? $profile['preferredAddress'] ?? $profile['abhaAddress'] ?? ''));

        $card = $this->extractOfficialCard($result)
            ?: $this->extractOfficialCard($data)
            ?: $this->extractOfficialCard($account)
            ?: $this->extractOfficialCard($profile)
            ?: $this->extractOfficialCard(is_array($result['ABHAProfile'] ?? null) ? $result['ABHAProfile'] : []);

        $inlineSource = $this->extractCardSource($result) ?: $this->extractCardSource($data);
        if ($card !== '') {
            $result['card_base64'] = $card;
            $result['card_content_type'] = $this->resolveCardContentType($result) ?: $this->resolveCardContentType($data);
            $result['card_source'] = $inlineSource;
        }

        // Verify responses often embed a Bridge-generated card under official_card,
        // so only the dedicated card endpoint can supply the real ABDM card.
        if (($card === '' || $inlineSource === 'generated') && ($abhaNumber !== '' || $abhaAddress !== '')) {
            // Without the patient X-Token the Bridge can only return a generated
            // card; with it, ABDM's genuine card is fetched.
            $patientToken = $this->extractPatientXToken($result);
            $cardResult = $this->get(
                '/v3/abha/card',
                array_filter([
                    'abha_number' => $abhaNumber,
                    'abha_address' => $abhaAddress,
                ], static fn($value): bool => trim((string) $value) !== ''),
                $patientToken !== '' ? ['X-Token' => 'Bearer ' . $patientToken] : []
            );
            if (! empty($cardResult['ok']) && (int) $cardResult['ok'] === 1) {
                $cardData = is_array($cardResult['data'] ?? null) ? $cardResult['data'] : $cardResult;
                $fetchedCard = $this->extractOfficialCard($cardResult) ?: $this->extractOfficialCard($cardData);
                $fetchedSource = $this->extractCardSource($cardResult) ?: $this->extractCardSource($cardData);
                // Never trade a card we already hold for another generated one.
                if ($fetchedCard !== '' && ($fetchedSource === 'abdm' || $card === '')) {
                    $result['card_base64'] = $fetchedCard;
                    $result['card_content_type'] = $this->resolveCardContentType($cardData);
                    $result['card_source'] = $fetchedSource;
                } elseif ($fetchedCard === '') {
                    // Bridge reports the upstream ABDM reason here (e.g. token expired).
                    $result['card_message'] = trim((string) ($cardResult['message'] ?? $cardData['message'] ?? ''));
                }
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractCardSource(array $source): string
    {
        foreach ([$source['card_source'] ?? null, $source['source'] ?? null] as $candidate) {
            $value = strtolower(trim((string) $candidate));
            if ($value === '') {
                continue;
            }
            if (str_contains($value, 'abdm')) {
                return 'abdm';
            }

            // "none" means ABDM issued no card for this session, not a local render.
            return $value === 'none' ? 'none' : 'generated';
        }

        return '';
    }

    /**
     * @param array<string, mixed> $result
     */
    private function extractPatientXToken(array $result): string
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $tokens = is_array($result['tokens'] ?? null) ? $result['tokens'] : (is_array($data['tokens'] ?? null) ? $data['tokens'] : []);

        foreach ([
            $result['X-Token'] ?? null,
            $result['x_token'] ?? null,
            $data['X-Token'] ?? null,
            $data['x_token'] ?? null,
            $result['token'] ?? null,
            $data['token'] ?? null,
            $tokens['token'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return preg_replace('/^Bearer\s+/i', '', trim($candidate));
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractOfficialCard(array $source): string
    {
        foreach (['official_card', 'card_data_uri', 'card_base64', 'card_data', 'abhaCard', 'abha_card', 'cardData', 'card'] as $key) {
            $value = $source[$key] ?? '';
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
            if (is_array($value)) {
                foreach (['base64', 'data', 'card_base64', 'card_data'] as $nestedKey) {
                    if (is_string($value[$nestedKey] ?? null) && trim($value[$nestedKey]) !== '') {
                        return trim($value[$nestedKey]);
                    }
                }
            }
        }

        return '';
    }

    private function resolveCardContentType(array $payload): string
    {
        $format = strtolower(trim((string) ($payload['card_content_type'] ?? $payload['content_type'] ?? $payload['card_format'] ?? '')));
        if ($format === 'png') { return 'image/png'; }
        if ($format === 'jpg' || $format === 'jpeg') { return 'image/jpeg'; }
        if ($format === 'pdf') { return 'application/pdf'; }
        return $format !== '' && str_contains($format, '/') ? $format : 'image/png';
    }

    // -------------------------------------------------------------------------
    // M1 ABHA OTP Flows
    // -------------------------------------------------------------------------

    public function abhaAadhaarGenerateOtp(array $payload): array
    {
        // Gateway simple format: { aadhaar: "<12-digit>" }
        $body = [
            'aadhaar' => (string) ($payload['aadhaar'] ?? $payload['loginId'] ?? ''),
        ];
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/abha/aadhaar/generate-otp', $body);
    }

    public function abhaAadhaarVerifyOtp(array $payload): array
    {
        // Gateway simple format: { txnId: "...", otp: "123456", mobile: "9876543210" (optional) }
        $body = [
            'txnId' => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'otp'   => (string) ($payload['otp'] ?? ''),
        ];
        // Only include mobile when a valid 10-digit number is supplied;
        // sending an empty string causes ABDM sandbox to reject the verify request.
        $mobile = (string) ($payload['mobile'] ?? '');
        if (preg_match('/^\d{10}$/', $mobile)) {
            $body['mobile'] = $mobile;
        }
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/aadhaar/verify-otp', $body);
        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        return $this->attachOfficialAbhaCard($result);
    }

    public function abhaMobileGenerateOtp(array $payload): array
    {
        // txnId is optional for standalone mobile lookup, but required when
        // updating the communication mobile during Aadhaar enrolment.
        // Gateway handles RSA encryption and M3 format conversion internally.
        $body = [
            'mobile' => (string) ($payload['mobile'] ?? $payload['loginId'] ?? ''),
        ];
        $txnId = trim((string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''));
        if ($txnId !== '') {
            $body['txnId'] = $txnId;
        }
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/abha/mobile/generate-otp', $body);
    }

    public function abhaMobileVerifyOtp(array $payload): array
    {
        // Gateway API format: { "txnId": "...", "otp": "123456" }
        $body = [
            'txnId' => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'otp'   => (string) ($payload['otp'] ?? ''),
        ];
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/mobile/verify-otp', $body);
        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        return $this->attachOfficialAbhaCard($result);
    }

    public function abhaEnrolMobileRequestOtp(array $payload): array
    {
        $body = [
            'txnId' => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'mobile' => (string) ($payload['mobile'] ?? ''),
        ];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        return $this->post('/v3/abha/enrol/mobile/request-otp', $body);
    }

    public function abhaEnrolMobileVerifyOtp(array $payload): array
    {
        $body = [
            'txnId' => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'otp' => (string) ($payload['otp'] ?? ''),
        ];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/enrol/mobile/verify-otp', $body);
        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        return $this->attachOfficialAbhaCard($result);
    }

    public function abhaLoginSelectAccount(array $payload): array
    {
        $body = [
            'txnId'        => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'token'        => (string) ($payload['token'] ?? ''),
            'abha_number'  => (string) ($payload['abha_number'] ?? $payload['abhaNumber'] ?? ''),
            'abha_address' => (string) ($payload['abha_address'] ?? $payload['abhaAddress'] ?? ''),
        ];
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/login/select-account', $body);
        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        return $this->attachOfficialAbhaCard($result);
    }

    public function abhaAddressSuggestions(array $payload): array
    {
        $txnId = (string) ($payload['txn_id'] ?? $payload['txnId'] ?? '');

        $result = $this->get('/v3/abha/suggestions', ['txnId' => $txnId]);

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $result;
        }

        $result['suggestions'] = $this->normalizeAbhaAddressSuggestions($result);
        $result['bridge_mock'] = $result['suggestions'] === [] && $this->isMockBridgeResponse($result);

        return $result;
    }

    /**
     * The Bridge answers stubbed endpoints with {"ok":1,"mode":"test"} and no real
     * payload; treating that as success would report false results to the operator.
     *
     * @param array<string, mixed> $result
     */
    private function isMockBridgeResponse(array $result): bool
    {
        if (strtolower(trim((string) ($result['mode'] ?? ''))) === 'test') {
            return true;
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        return stripos((string) ($data['message'] ?? ''), 'mock') !== false;
    }

    public function abhaSetAddress(array $payload): array
    {
        $txnId   = (string) ($payload['txn_id'] ?? $payload['txnId'] ?? '');
        $address = (string) ($payload['abha_address'] ?? $payload['abhaAddress'] ?? '');
        // ABDM expects the handle without the @provider suffix.
        $address = explode('@', $address)[0];
        $body    = [
            'txnId'       => $txnId,
            'abhaAddress' => $address,
            'preferred'   => 1,
        ];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        $result = $this->post('/v3/abha/set-address', $body);

        if (! empty($result['ok']) && (int) $result['ok'] === 1) {
            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $preferred = trim((string) ($data['preferredAbhaAddress'] ?? $data['preferred_abha_address'] ?? $data['abhaAddress'] ?? ''));
            if ($preferred !== '') {
                $result['abha_address'] = $preferred;
            }
            $result['bridge_mock'] = $preferred === '' && $this->isMockBridgeResponse($result);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @return string[]
     */
    private function normalizeAbhaAddressSuggestions(array $result): array
    {
        $data = is_array($result['data'] ?? null) ? $result['data'] : [];

        $raw = [];
        foreach ([
            $result['suggestions'] ?? null,
            $result['abhaAddressList'] ?? null,
            $result['abha_address_list'] ?? null,
            $data['suggestions'] ?? null,
            $data['abhaAddressList'] ?? null,
            $data['abha_address_list'] ?? null,
        ] as $candidate) {
            if (is_array($candidate) && $candidate !== []) {
                $raw = $candidate;
                break;
            }
        }

        $addresses = [];
        foreach ($raw as $entry) {
            $value = is_array($entry)
                ? (string) ($entry['abhaAddress'] ?? $entry['abha_address'] ?? $entry['address'] ?? $entry['value'] ?? '')
                : (string) $entry;
            $value = trim($value);
            if ($value !== '' && ! in_array($value, $addresses, true)) {
                $addresses[] = $value;
            }
        }

        return $addresses;
    }

    // -------------------------------------------------------------------------
    // Consent
    // -------------------------------------------------------------------------

    public function requestConsent(
        int    $patientId,
        string $abhaId,
        string $purposeCode,
        string $expiresAt,
        string $consentHandle,
        array  $rawPayload = []
    ): array {
        [$safeFrom, $safeTo] = $this->buildSafeConsentDateRange();
        $body = [
            'patient_abha' => $abhaId,
            'purpose'      => $purposeCode !== '' ? $purposeCode : 'TREATMENT',
            'hi_types'     => $rawPayload['hi_types'] ?? ['OPConsultation'],
            'date_range_from' => (string) ($rawPayload['date_range_from'] ?? $safeFrom),
            'date_range_to'   => $this->clampUtcIsoToSafePast((string) ($rawPayload['date_range_to'] ?? $safeTo)),
        ];
        if ($expiresAt !== '') {
            $body['expires_at'] = $expiresAt;
        }
        $result = $this->post('/v3/consent/request', $body);
        // Merge consent_id from gateway response so callers can persist it
        if (isset($result['consent_id'])) {
            $result['gateway_consent_id'] = $result['consent_id'];
        }
        return $result;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function buildSafeConsentDateRange(): array
    {
        $nowUtc = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $safeTo = $nowUtc->modify('-120 seconds');
        $safeFrom = $safeTo->modify('-365 days');

        return [
            $safeFrom->format('Y-m-d\TH:i:s.000\Z'),
            $safeTo->format('Y-m-d\TH:i:s.000\Z'),
        ];
    }

    private function clampUtcIsoToSafePast(string $candidate): string
    {
        $candidate = trim($candidate);
        $safeNowUtc = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('-120 seconds');
        $safeNowIso = $safeNowUtc->format('Y-m-d\TH:i:s.000\Z');

        if ($candidate === '') {
            return $safeNowIso;
        }

        try {
            $candidateAt = new \DateTimeImmutable($candidate);
            if ($candidateAt->getTimestamp() > $safeNowUtc->getTimestamp()) {
                return $safeNowIso;
            }

            return $candidateAt
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s.000\Z');
        } catch (\Throwable $e) {
            return $safeNowIso;
        }
    }

    // -------------------------------------------------------------------------
    // Health Record Sharing
    // -------------------------------------------------------------------------

    public function sharePrescriptionBundle(array $payload, string $entityId = ''): array
    {
        $hiTypeRaw = (string) ($payload['hi_type'] ?? 'OPConsultRecord');
        $hiType = match ($hiTypeRaw) {
            'OPConsultation', 'OPConsultRecord' => 'OPConsultRecord',
            'Prescription', 'PrescriptionRecord' => 'PrescriptionRecord',
            'DiagnosticReport', 'DiagnosticReportRecord' => 'DiagnosticReportRecord',
            'DischargeSummary', 'DischargeSummaryRecord' => 'DischargeSummaryRecord',
            'ImmunizationRecord' => 'ImmunizationRecord',
            'WellnessRecord' => 'WellnessRecord',
            'HealthDocumentRecord' => 'HealthDocumentRecord',
            'InvoiceRecord' => 'InvoiceRecord',
            default => $hiTypeRaw,
        };

        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_id'] ?? $payload['consent_handle'] ?? ''),
            'hi_type'     => $hiType,
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    public function shareIpdDischargeBundle(array $payload, string $entityId = ''): array
    {
        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_id'] ?? $payload['consent_handle'] ?? ''),
            'hi_type'     => 'DischargeSummaryRecord',
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    public function shareDiagnosisReportBundle(array $payload, string $entityId = ''): array
    {
        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_id'] ?? $payload['consent_handle'] ?? ''),
            'hi_type'     => 'DiagnosticReportRecord',
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Health Records — store-and-link flow  POST /api/v3/records/push
    // -------------------------------------------------------------------------

    public function pushRecord(array $data): array
    {
        $recordTypeRaw = (string) ($data['record_type'] ?? '');
        $hiTypeRaw = (string) ($data['hi_type'] ?? '');

        $normalizeHiType = static function (string $value): string {
            return match ($value) {
                'OPConsultation', 'OPConsultRecord' => 'OPConsultRecord',
                'Prescription', 'PrescriptionRecord' => 'PrescriptionRecord',
                'DiagnosticReport', 'DiagnosticReportRecord', 'Lab_Report', 'lab_report' => 'DiagnosticReportRecord',
                'DischargeSummary', 'DischargeSummaryRecord' => 'DischargeSummaryRecord',
                'ImmunizationRecord' => 'ImmunizationRecord',
                'WellnessRecord' => 'WellnessRecord',
                'HealthDocumentRecord' => 'HealthDocumentRecord',
                'InvoiceRecord' => 'InvoiceRecord',
                default => '',
            };
        };

        $normalizeRecordType = static function (string $value) use ($normalizeHiType): string {
            return $normalizeHiType($value);
        };

        $recordType = $normalizeRecordType($recordTypeRaw);
        if ($recordType === '') {
            $recordType = $normalizeRecordType($hiTypeRaw);
        }
        if ($recordType === '') {
            $recordType = 'HealthDocumentRecord';
        }

        $hiType = $normalizeHiType($hiTypeRaw);
        if ($hiType === '') {
            $hiType = $recordType;
        }

        $patientName = trim((string) ($data['patient_name'] ?? ''));
        $abhaAddress = trim((string) ($data['abha_address'] ?? ''));
        $abhaId = trim((string) ($data['abha_id'] ?? ''));
        $careContextReference = trim((string) ($data['care_context_reference'] ?? $data['careContextId'] ?? ''));
        $careContextDisplay = trim((string) ($data['care_context_display'] ?? $data['notes'] ?? ''));
        $queueId = trim((string) ($data['queue_id'] ?? ''));

        if ($patientName === '') {
            return ['ok' => 0, 'http_code' => 0, 'error_text' => 'patient_name is required'];
        }
        if ($abhaAddress === '' && $abhaId === '') {
            return ['ok' => 0, 'http_code' => 0, 'error_text' => 'Either abha_address or abha_id is required before pushing health record'];
        }
        if ($careContextReference === '') {
            return ['ok' => 0, 'http_code' => 0, 'error_text' => 'care_context_reference is required'];
        }
        if ($careContextDisplay === '') {
            $careContextDisplay = $recordType;
        }
        if ($queueId === '') {
            $queueId = $careContextReference;
        }

        // Gateway requires BOTH record_type (lowercase alias) and hi_type (official ABDM name).
        $body = [
            'patient_id'   => (string) ($data['patient_id'] ?? ''),
            'patient_name' => $patientName,
            'record_type'  => $recordType,
            'hi_type'      => $hiType,
            'visit_date'   => (string) ($data['visit_date'] ?? date('Y-m-d')),
            'record_data'  => $data['record_data'] ?? $data['bundle'] ?? (object) [],
            'fhir_bundle'  => $data['record_data'] ?? $data['bundle'] ?? (object) [],
            'care_context_reference' => $careContextReference,
            'care_context_display' => $careContextDisplay,
            'queue_id' => $queueId,
        ];

        if ($this->bridgeHospitalId !== '') {
            $body['hospital_id'] = $this->bridgeHospitalId;
            $body['bridge_hospital_id'] = $this->bridgeHospitalId;
        }

        if ($abhaAddress !== '') {
            $body['abha_address'] = $abhaAddress;
        }
        if ($abhaId !== '') {
            $body['abha_id'] = $abhaId;
        }

        // Also send ABDM-style careContexts wrapper so consent linkage remains explicit.
        $body['careContexts'] = [[
            'careContextReference' => $careContextReference,
            'description'          => $careContextDisplay,
        ]];

        // hfr_id is required in every push request alongside the Bearer token.
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        foreach (['abha_id', 'doctor_name', 'department', 'notes', 'gender', 'year_of_birth'] as $optional) {
            if (empty($data[$optional])) {
                continue;
            }

            if ($optional === 'gender') {
                $gender = $this->normalizeBridgeGender($data[$optional]);
                if ($gender !== '') {
                    $body['gender'] = $gender;
                }
                continue;
            }

            $body[$optional] = (string) $data[$optional];
        }

        return self::normalizePushRecordResponse($this->post('/v3/records/push', $body));
    }

    /**
     * @param array<string,mixed> $result
     * @return array<string,mixed>
     */
    private static function normalizePushRecordResponse(array $result): array
    {
        $httpCode = (int) ($result['http_code'] ?? 0);
        $error = $result['error'] ?? null;
        $responseError = $result['response']['error'] ?? null;
        $dataError = $result['data']['error'] ?? null;
        $errorCode = strtoupper(trim((string) (
            $result['error_code']
            ?? (is_array($error) ? ($error['code'] ?? null) : $error)
            ?? ($result['response']['error_code'] ?? null)
            ?? (is_array($responseError) ? ($responseError['code'] ?? null) : $responseError)
            ?? ($result['data']['error_code'] ?? null)
            ?? (is_array($dataError) ? ($dataError['code'] ?? null) : $dataError)
            ?? ''
        )));
        $duplicate = $httpCode === 409 && $errorCode === 'DUPLICATE_RECORD';

        $recordId = self::firstPositiveInteger([
            $result['record_id'] ?? null,
            $result['existing_record_id'] ?? null,
            $result['response']['record_id'] ?? null,
            $result['response']['existing_record_id'] ?? null,
            $result['data']['record_id'] ?? null,
            $result['data']['existing_record_id'] ?? null,
        ]);
        $queueId = self::firstNonEmptyString([
            $result['queue_id'] ?? null,
            $result['existing_queue_id'] ?? null,
            $result['response']['queue_id'] ?? null,
            $result['response']['existing_queue_id'] ?? null,
            $result['data']['queue_id'] ?? null,
            $result['data']['existing_queue_id'] ?? null,
        ]);
        $firstPushedAt = self::firstNonEmptyString([
            $result['first_pushed_at'] ?? null,
            $result['response']['first_pushed_at'] ?? null,
            $result['data']['first_pushed_at'] ?? null,
        ]);

        if ($recordId > 0) {
            $result['record_id'] = $recordId;
        }
        if ($queueId !== '') {
            $result['queue_id'] = $queueId;
        }
        if ($firstPushedAt !== '') {
            $result['first_pushed_at'] = $firstPushedAt;
        }
        $result['error_code'] = $errorCode !== '' ? $errorCode : ($result['error_code'] ?? null);
        $result['duplicate'] = $duplicate ? 1 : 0;
        $result['submitted'] = ($httpCode === 201 || $duplicate) ? 1 : 0;

        if ($httpCode === 201 || $duplicate) {
            $result['ok'] = 1;
            return $result;
        }

        if ($httpCode === 422) {
            $result['ok'] = 0;
            $message = trim((string) ($result['message'] ?? $result['error_text'] ?? 'Validation failed'));
            $details = [];
            foreach ((array) ($result['errors'] ?? []) as $error) {
                $details[] = is_array($error)
                    ? trim(implode(' ', array_filter([$error['field'] ?? '', $error['message'] ?? ''])))
                    : trim((string) $error);
            }
            $details = array_values(array_filter($details));
            $result['error_text'] = $message . ($details !== [] ? ' | ' . implode(' ; ', $details) : '');
        }

        return $result;
    }

    /** @param array<int,mixed> $values */
    private static function firstPositiveInteger(array $values): int
    {
        foreach ($values as $value) {
            if ((int) $value > 0) {
                return (int) $value;
            }
        }
        return 0;
    }

    /** @param array<int,mixed> $values */
    private static function firstNonEmptyString(array $values): string
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }
        return '';
    }

    // -------------------------------------------------------------------------
    // Scan & Share
    // -------------------------------------------------------------------------

    public function scanShareLookup(string $qrPayload, string $abhaIdHint = '', array $fullPayload = []): array
    {
        // Per API docs: scan & share uses POST /api/v1/bridge with event_type abdm.scan_share.lookup
        return $this->post('/v1/bridge', [
            'event_type' => 'abdm.scan_share.lookup',
            'payload'    => [
                'term'         => $qrPayload,
                'return_limit' => (int) ($fullPayload['return_limit'] ?? 10),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // NHCX Claims
    // -------------------------------------------------------------------------

    public function nhcxClaimCreate(
        array $bundle,
        int   $documentId,
        int   $patientId,
        int   $encounterId
    ): array {
        // Gateway does not yet expose an NHCX endpoint — queue via bridge dispatch
        throw new \RuntimeException(
            'EAtriaBridgeConnector::nhcxClaimCreate() — NHCX endpoint not yet available on gateway. '
            . 'Use DreamsoftConnector or direct NHCX integration.'
        );
    }

    public function nhcxClaimStatusRequest(
        int    $documentId,
        string $externalRef,
        string $currentStatus,
        array  $fullPayload = []
    ): array {
        throw new \RuntimeException(
            'EAtriaBridgeConnector::nhcxClaimStatusRequest() — NHCX endpoint not yet available on gateway.'
        );
    }

    // -------------------------------------------------------------------------
    // OPD Queue (Scan & Share + Manual walk-in tokens)
    // -------------------------------------------------------------------------

    public function getRecord(int $bridgeId): array
    {
        $query = [];
        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }
        return $this->get('/v3/records/' . $bridgeId, $query);
    }

    public function triggerShare(int $bridgeId): array
    {
        $body = [];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/records/' . $bridgeId . '/share', $body);
    }

    public function linkAndShare(int $bridgeId): array
    {
        $body = [];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/records/' . $bridgeId . '/link-and-share', $body);
    }

    public function workflowStatus(int $bridgeId): array
    {
        $query = [];
        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }
        return $this->get('/v3/records/' . $bridgeId . '/workflow-status', $query);
    }

    public function opdQueueFetch(string $date = '', string $status = '', int $page = 1, int $limit = 100): array
    {
        $params = ['limit' => $limit, 'page' => $page];
        if ($date !== '') {
            $params['date'] = $date;
        }
        if ($status !== '') {
            $params['status'] = $status;
        }
        return $this->get('/v3/opd/queue', $params);
    }

    public function facilityQrCode(): array
    {
        return $this->get('/v3/facility/qr-code');
    }

    public function opdTokenCreate(array $payload): array
    {
        if (array_key_exists('gender', $payload)) {
            $gender = $this->normalizeBridgeGender($payload['gender']);
            if ($gender !== '') {
                $payload['gender'] = $gender;
            } else {
                unset($payload['gender']);
            }
        }

        if ($this->hfrId !== '' && empty($payload['hfr_id'])) {
            $payload['hfr_id'] = $this->hfrId;
        }

        return $this->post('/v3/opd/token', $payload);
    }

    public function opdTokenUpdateStatus(int $tokenId, string $status): array
    {
        $body = ['status' => $status];
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        return $this->patch('/v3/opd/token/' . $tokenId, $body);
    }

    public function opdRunningTokenStatus(): array
    {
        return $this->get('/v3/opd/running-token-status');
    }

    // -------------------------------------------------------------------------
    // Immunization Master Data
    // -------------------------------------------------------------------------

    public function immunizationUipVersion(): array
    {
        return $this->get('/v3/master-data/immunization/uip/version');
    }

    public function immunizationUipSchedule(): array
    {
        return $this->get('/v3/master-data/immunization/uip');
    }

    public function immunizationUipChanges(string $sinceVersion): array
    {
        $query = [];
        if ($sinceVersion !== '') {
            $query['since_version'] = $sinceVersion;
        }

        return $this->get('/v3/master-data/immunization/uip/changes', $query);
    }

    // -------------------------------------------------------------------------
    // Bridge Records — list
    // -------------------------------------------------------------------------

    public function getRecords(array $filters = []): array
    {
        return $this->get('/v3/records', $filters);
    }

    // -------------------------------------------------------------------------
    // System / Hospital Info
    // -------------------------------------------------------------------------

    public function gatewayStatus(): array
    {
        return $this->get('/v3/gateway/status');
    }

    // -------------------------------------------------------------------------
    // HIP-Initiated Linking
    // -------------------------------------------------------------------------

    public function hipLinkToken(array $payload): array
    {
        if (array_key_exists('gender', $payload)) {
            $gender = $this->normalizeBridgeGender($payload['gender']);
            if ($gender !== '') {
                $payload['gender'] = $gender;
            } else {
                unset($payload['gender']);
            }
        }

        if ($this->hfrId !== '' && empty($payload['hfr_id'])) {
            $payload['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/hip/link-token', $payload);
    }

    public function hipLinkCareContext(array $payload): array
    {
        if ($this->hfrId !== '' && empty($payload['hfr_id'])) {
            $payload['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/hip/link/carecontext', $payload);
    }

    public function hipGetPatientLinks(array $filters = []): array
    {
        return $this->get('/v3/hip/link/patient/links', $filters);
    }

    public function hipLinkNotify(array $payload): array
    {
        return $this->post('/v3/hip/link/notify', $payload);
    }

    public function hipSmsNotify(array $payload): array
    {
        return $this->post('/v3/hip/link/sms-notify', $payload);
    }

    // -------------------------------------------------------------------------
    // Drug Terminology (CDCI)
    // -------------------------------------------------------------------------

    public function drugsAutocomplete(string $q, string $type = 'generic', int $limit = 10): array
    {
        $query = [
            'q' => $q,
            'type' => $type,
            'limit' => max(1, min(50, $limit)),
        ];

        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }

        return $this->get('/v3/drugs/autocomplete', $query);
    }

    public function drugsDetail(string $type, string $identifier): array
    {
        $query = [
            'type' => $type,
            'identifier' => $identifier,
        ];

        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }

        return $this->get('/v3/drugs/detail', $query);
    }

    public function drugsVersion(): array
    {
        $query = [];
        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }

        return $this->get('/v3/drugs/version', $query);
    }

    // -------------------------------------------------------------------------
    // Pathology Terminology (LOINC)
    // -------------------------------------------------------------------------

    /**
     * Fetch pathology panel masters from the Bridge API.
     *
     * @param string $subCategory  'PATHOLOGY' | 'BIOPSY' | '' for all
     * @param int    $limit        items per page (max 500)
     * @param int    $offset       pagination offset
     * @param string $updatedSince ISO-8601 datetime for incremental sync
     * @return array<string, mixed>
     */
    public function pathologyMasters(
        string $subCategory  = '',
        int    $limit        = 200,
        int    $offset       = 0,
        string $updatedSince = ''
    ): array {
        $query = ['limit' => max(1, min(500, $limit)), 'offset' => $offset];
        if ($subCategory !== '') {
            $query['sub_category'] = strtoupper($subCategory);
        }
        if ($updatedSince !== '') {
            $query['updated_since'] = $updatedSince;
        }
        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }

        return $this->get('/v3/pathology/masters', $query);
    }

    /**
     * Fetch LOINC component details for a specific pathology panel.
     *
     * @param string $parentTest   Exact panel name (e.g. 'Complete Blood Count Panel')
     * @param int    $limit        items per page
     * @param int    $offset       pagination offset
     * @param string $updatedSince ISO-8601 datetime for incremental sync
     * @return array<string, mixed>
     */
    public function pathologyComponents(
        string $parentTest   = '',
        int    $limit        = 200,
        int    $offset       = 0,
        string $updatedSince = ''
    ): array {
        $query = ['limit' => max(1, min(500, $limit)), 'offset' => $offset];
        if ($parentTest !== '') {
            $query['parent_test'] = $parentTest;
        }
        if ($updatedSince !== '') {
            $query['updated_since'] = $updatedSince;
        }
        if ($this->hfrId !== '') {
            $query['hfr_id'] = $this->hfrId;
        }

        return $this->get('/v3/pathology/components', $query);
    }
}
