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

            $rows = $db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', [
                    'EATRIA_BRIDGE_TOKEN',
                    'EKA_GATEWAY_TOKEN',
                    'GATEWAY_TO_HMS_TOKEN',
                    'ABDM_GATEWAY_TO_HMS_TOKEN',
                    'ABDM_BRIDGE_TOKEN',
                    'ABDM_GATEWAY_TOKEN',
                    'EATRIA_BRIDGE_API_KEY',
                    'EATRIA_BRIDGE_URL',
                    'ABDM_HFR_ID',
                    'H_HFR_ID',
                    'ABDM_HOSPITAL_HFR_ID',
                    'ABDM_BRIDGE_HOSPITAL_ID',
                    'EATRIA_BRIDGE_HOSPITAL_ID',
                ])
                ->get()
                ->getResultArray();

            $dbSettings = array_column($rows, 's_value', 's_name');

            $tokenCandidates = [];
            $tokenSourceByValue = [];
            foreach ([
                'EATRIA_BRIDGE_TOKEN',
                'EKA_GATEWAY_TOKEN',
                'GATEWAY_TO_HMS_TOKEN',
                'ABDM_GATEWAY_TO_HMS_TOKEN',
                'ABDM_BRIDGE_TOKEN',
                'ABDM_GATEWAY_TOKEN',
                'EATRIA_BRIDGE_API_KEY',
            ] as $tokenKey) {
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
                $this->baseUrl = rtrim($dbUrl, '/');
            }

            $dbHfrId = trim((string) (
                $dbSettings['ABDM_HFR_ID']
                ?? $dbSettings['H_HFR_ID']
                ?? $dbSettings['ABDM_HOSPITAL_HFR_ID']
                ?? ''
            ));
            if ($dbHfrId !== '') {
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

        return trim($token, " \t\n\r\0\x0B\"'");
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
            if (in_array($lowerKey, ['token', 'refresh_token', 'refreshtoken', 'authorization', 'auth', 'otp'], true)) {
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
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        // e-Atria bridge expects hfr_id alongside Bearer auth for GET endpoints too.
        if ($this->hfrId !== '' && empty($query['hfr_id'])) {
            $query['hfr_id'] = $this->hfrId;
        }
        return $this->httpCall('GET', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function httpCall(string $method, string $path, array $body = [], array $query = []): array
    {
        // Credentials can be rotated from settings without restarting PHP workers.
        // Refresh before each call to avoid stale token/HFR causing 401/403.
        $this->refreshRuntimeSettingsFromDb();

        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $safeRequestBody = $this->redactSensitiveForLog($body);

        $callWithToken = function (string $tokenValue) use ($method, $url, $body, $safeRequestBody): array {
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

            $maskedToken = $tokenValue !== '' ? (substr($tokenValue, 0, 6) . '***' . substr($tokenValue, -4)) : '(none)';
            $tokenSource = $this->tokenSourceByValue[$tokenValue] ?? $this->tokenSource;
            log_message('debug', '[EAtriaBridge] --> ' . $method . ' ' . $url
                . ' | token=' . $maskedToken
                . ' | token_source=' . $tokenSource
                . ' | body=' . json_encode($safeRequestBody));

            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeoutSec,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
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
            log_message('warning', '[EAtriaBridge] Authorization token missing for request: ' . $method . ' ' . $url);
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

        if ($curlErr !== '') {
            log_message('error', '[EAtriaBridge] cURL error on ' . $url . ': ' . $curlErr);
            $this->dbLog($method, $path, $url, $body, 0, '', 'error', 'cURL error: ' . $curlErr);
            return ['ok' => 0, 'error_text' => 'cURL error: ' . $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            $this->dbLog($method, $path, $url, $body, $httpCode, (string) $raw, 'error', 'Non-JSON response');
            return ['ok' => 0, 'error_text' => 'Non-JSON response', 'http_code' => $httpCode, 'raw' => (string) $raw];
        }

        $ok = ($httpCode >= 200 && $httpCode < 300) ? (int) ($decoded['ok'] ?? 1) : 0;
        $this->dbLog($method, $path, $url, $body, $httpCode, (string) $raw, $ok === 1 ? 'success' : 'error', $ok === 0 ? (string) ($decoded['message'] ?? $decoded['error_text'] ?? '') : '');
        if (($httpCode === 401 || $httpCode === 403) && ! isset($decoded['auth_hint'])) {
            $decoded['auth_hint'] = 'Verify gateway token in hospital_setting (EATRIA_BRIDGE_TOKEN / EKA_GATEWAY_TOKEN) and ABDM_HFR_ID. Current token source=' . $this->tokenSource . ', HFR=' . ($this->hfrId !== '' ? $this->hfrId : '(empty)');
        }

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
        $body = $fullPayload !== [] ? $fullPayload : ['abha_id' => $abhaId];
        if ($this->hfrId !== '' && empty($body['hfr_id'])) {
            $body['hfr_id'] = $this->hfrId;
        }
        return $this->post('/v3/abha/validate', $body);
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
        return $this->post('/v3/abha/aadhaar/verify-otp', $body);
    }

    public function abhaMobileGenerateOtp(array $payload): array
    {
        // Gateway API format: { "mobile": "<10-digit>" }
        // Gateway handles RSA encryption and M3 format conversion internally.
        $body = [
            'mobile' => (string) ($payload['mobile'] ?? $payload['loginId'] ?? ''),
        ];
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
        return $this->post('/v3/abha/mobile/verify-otp', $body);
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
        $body = [
            'patient_abha' => $abhaId,
            'purpose'      => $purposeCode !== '' ? $purposeCode : 'TREATMENT',
            'hi_types'     => $rawPayload['hi_types'] ?? ['OPConsultation'],
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

        $result = $this->post('/v3/records/push', $body);

        $httpCode = (int) ($result['http_code'] ?? 0);
        $authErrorText = strtolower(trim((string) ($result['message'] ?? $result['error_text'] ?? $result['error'] ?? '')));
        $isAuthFailure = in_array($httpCode, [401, 403], true)
            && ($authErrorText === ''
                || str_contains($authErrorText, 'unauthorized')
                || str_contains($authErrorText, 'invalid authorization token')
                || str_contains($authErrorText, 'invalid token'));

        // Some hospital keys are scoped to /v1/bridge dispatcher paths.
        // Fallback to event dispatch when direct /v3/records/push returns auth failure.
        if (! $isAuthFailure) {
            return $result;
        }

        $dispatchPayload = [
            'event_type' => 'abdm.fhir.share.requested',
            'payload' => [
                'hi_type' => $hiType,
                'record_type' => $recordType,
                'fhir_bundle' => $body['fhir_bundle'] ?? (object) [],
                'bundle' => $body['fhir_bundle'] ?? (object) [],
                'care_context_reference' => $careContextReference,
                'care_context_display' => $careContextDisplay,
                'queue_id' => $queueId,
                'abha_id' => $abhaId,
                'abha_address' => $abhaAddress,
                'patient_id' => (string) ($data['patient_id'] ?? ''),
                'patient_name' => $patientName,
                'visit_date' => (string) ($data['visit_date'] ?? date('Y-m-d')),
                'hfr_id' => $this->hfrId,
                'hospital_id' => $this->bridgeHospitalId,
            ],
        ];

        $dispatch = $this->post('/v1/bridge', $dispatchPayload);
        $dispatchOk = (int) ($dispatch['ok'] ?? 0) === 1;
        $dispatchNode = is_array($dispatch['dispatch'] ?? null) ? $dispatch['dispatch'] : [];
        $dispatchHttp = (int) ($dispatchNode['http_code'] ?? $dispatch['http_code'] ?? 0);
        $dispatchResponse = is_array($dispatchNode['response'] ?? null) ? $dispatchNode['response'] : [];
        $dispatchErr = trim((string) (
            $dispatchResponse['message']
            ?? $dispatchResponse['error_text']
            ?? $dispatchResponse['error']
            ?? $dispatch['message']
            ?? $dispatch['error_text']
            ?? $dispatch['error']
            ?? ''
        ));

        if (! $dispatchOk) {
            return array_merge($result, [
                'fallback' => 'v1_bridge_dispatch_failed',
                'fallback_http_code' => $dispatchHttp,
                'fallback_error' => $dispatchErr !== '' ? $dispatchErr : 'Bridge dispatcher failed',
            ]);
        }

        return [
            'ok' => (int) ($dispatchResponse['ok'] ?? 1),
            'http_code' => $dispatchHttp > 0 ? $dispatchHttp : 200,
            'id' => (int) ($dispatchResponse['id'] ?? $dispatchResponse['record_id'] ?? 0),
            'queue_id' => (string) ($dispatchResponse['queue_id'] ?? $dispatchResponse['request_id'] ?? $dispatch['request_id'] ?? $queueId),
            'message' => (string) ($dispatchResponse['message'] ?? 'Record dispatched via v1 bridge'),
            'dispatch' => $dispatch,
            'fallback' => 'v1_bridge_dispatch',
        ];
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

        return $this->post('/v3/opd/token', $payload);
    }

    public function opdTokenUpdateStatus(int $tokenId, string $status): array
    {
        return $this->patch('/v3/opd/token/' . $tokenId, ['status' => $status]);
    }

    public function opdRunningTokenStatus(): array
    {
        return $this->get('/v3/opd/running-token-status');
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
