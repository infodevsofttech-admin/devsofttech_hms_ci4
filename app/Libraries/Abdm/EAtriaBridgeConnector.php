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
    private int    $timeoutSec;

    public function __construct()
    {
        $config = config('AbdmConnector');

        $this->baseUrl    = rtrim((string) ($config->eatriaBridgeUrl ?? 'https://abdm-bridge.e-atria.in/api'), '/');
        $this->token      = (string) ($config->eatriaBridgeToken ?? '');
        $this->timeoutSec = (int) ($config->eatriaBridgeTimeoutSec ?? 30);

        // Fall back to hospital_setting DB (written by Admin Panel → ABDM Gateway Config)
        if ($this->token === '' || $this->baseUrl === '') {
            try {
                $db = \Config\Database::connect();
                if ($db->tableExists('hospital_setting')) {
                    $rows = $db->table('hospital_setting')
                        ->select('s_name, s_value')
                        ->whereIn('s_name', ['EATRIA_BRIDGE_TOKEN', 'EATRIA_BRIDGE_URL'])
                        ->get()
                        ->getResultArray();

                    $dbSettings = array_column($rows, 's_value', 's_name');

                    if ($this->token === '' && ! empty($dbSettings['EATRIA_BRIDGE_TOKEN'])) {
                        $this->token = trim($dbSettings['EATRIA_BRIDGE_TOKEN']);
                    }
                    if (! empty($dbSettings['EATRIA_BRIDGE_URL'])) {
                        $this->baseUrl = rtrim(trim($dbSettings['EATRIA_BRIDGE_URL']), '/');
                    }
                }
            } catch (\Throwable $e) {
                // DB unavailable — continue with config values
            }
        }
    }

    public function getConnectorName(): string
    {
        return 'eatria_bridge';
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
        return $this->httpCall('GET', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function httpCall(string $method, string $path, array $body = [], array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

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

        // Log outgoing request (mask token)
        $maskedToken = $this->token !== '' ? (substr($this->token, 0, 6) . '***' . substr($this->token, -4)) : '(none)';
        log_message('debug', '[EAtriaBridge] --> ' . $method . ' ' . $url
            . ' | token=' . $maskedToken
            . ' | body=' . json_encode($body));

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        log_message('debug', '[EAtriaBridge] <-- HTTP ' . $httpCode . ' | raw=' . (string) $raw);

        if ($curlErr !== '') {
            log_message('error', '[EAtriaBridge] cURL error on ' . $url . ': ' . $curlErr);
            return ['ok' => 0, 'error_text' => 'cURL error: ' . $curlErr, 'http_code' => 0];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return ['ok' => 0, 'error_text' => 'Non-JSON response', 'http_code' => $httpCode, 'raw' => (string) $raw];
        }

        $ok = ($httpCode >= 200 && $httpCode < 300) ? (int) ($decoded['ok'] ?? 1) : 0;
        return array_merge($decoded, ['ok' => $ok, 'http_code' => $httpCode]);
    }

    // -------------------------------------------------------------------------
    // ABHA
    // -------------------------------------------------------------------------

    public function validateAbha(string $abhaId, array $fullPayload = []): array
    {
        $body = $fullPayload !== [] ? $fullPayload : ['abha_id' => $abhaId];
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
        return $this->post('/v3/abha/aadhaar/verify-otp', $body);
    }

    public function abhaMobileGenerateOtp(array $payload): array
    {
        // Gateway API format: { "mobile": "<10-digit>" }
        // Gateway handles RSA encryption and M3 format conversion internally.
        $body = [
            'mobile' => (string) ($payload['mobile'] ?? $payload['loginId'] ?? ''),
        ];
        return $this->post('/v3/abha/mobile/generate-otp', $body);
    }

    public function abhaMobileVerifyOtp(array $payload): array
    {
        // Gateway API format: { "txnId": "...", "otp": "123456" }
        $body = [
            'txnId' => (string) ($payload['txnId'] ?? $payload['txn_id'] ?? ''),
            'otp'   => (string) ($payload['otp'] ?? ''),
        ];
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
        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_handle'] ?? $payload['consent_id'] ?? ''),
            'hi_type'     => (string) ($payload['hi_type'] ?? 'OPConsultation'),
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    public function shareIpdDischargeBundle(array $payload, string $entityId = ''): array
    {
        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_handle'] ?? $payload['consent_id'] ?? ''),
            'hi_type'     => 'DischargeSummary',
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    public function shareDiagnosisReportBundle(array $payload, string $entityId = ''): array
    {
        return $this->post('/v3/bundle/push', [
            'consent_id'  => (string) ($payload['consent_handle'] ?? $payload['consent_id'] ?? ''),
            'hi_type'     => 'DiagnosticReport',
            'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Health Records — store-and-link flow  POST /api/v3/records/push
    // -------------------------------------------------------------------------

    public function pushRecord(array $data): array
    {
        // Map HMS hi_type → gateway record_type enum
        $hiType     = (string) ($data['hi_type'] ?? '');
        $recordType = match (true) {
            in_array($hiType, ['OPConsultRecord', 'PrescriptionRecord', 'OPConsultation'], true) => 'prescription',
            in_array($hiType, ['DiagnosticReport', 'DiagnosticReportRecord'], true)             => 'lab_report',
            in_array($hiType, ['DischargeSummary', 'DischargeSummaryRecord'], true)             => 'discharge_summary',
            $hiType === 'WellnessRecord'                                                          => 'wellness_record',
            default                                                                               => 'health_document',
        };

        $body = [
            'patient_id'   => (string) ($data['patient_id'] ?? ''),
            'patient_name' => (string) ($data['patient_name'] ?? ''),
            'record_type'  => $recordType,
            'visit_date'   => (string) ($data['visit_date'] ?? date('Y-m-d')),
            'record_data'  => $data['record_data'] ?? $data['bundle'] ?? (object) [],
        ];

        foreach (['abha_id', 'abha_address', 'doctor_name', 'department', 'care_context_reference', 'notes', 'queue_id'] as $optional) {
            if (! empty($data[$optional])) {
                $body[$optional] = (string) $data[$optional];
            }
        }

        return $this->post('/v3/records/push', $body);
    }

    // -------------------------------------------------------------------------
    // Scan & Share
    // -------------------------------------------------------------------------

    public function scanShareLookup(string $qrPayload, string $abhaIdHint = '', array $fullPayload = []): array
    {
        // Scan & Share uses SNOMED search endpoint as the gateway proxy
        return $this->get('/v3/snomed/search', [
            'term'         => $qrPayload,
            'return_limit' => '10',
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
        return $this->post('/v3/opd/token', $payload);
    }

    public function opdTokenUpdateStatus(int $tokenId, string $status): array
    {
        return $this->patch('/v3/opd/token/' . $tokenId, ['status' => $status]);
    }
}
