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
    private int    $timeoutSec;

    public function __construct()
    {
        $config = config('AbdmConnector');

        $this->baseUrl    = rtrim((string) ($config->eatriaBridgeUrl ?? 'https://abdm-bridge.e-atria.in/api'), '/');
        $this->token      = (string) ($config->eatriaBridgeToken ?? '');
        $this->hfrId      = '';
        $this->timeoutSec = (int) ($config->eatriaBridgeTimeoutSec ?? 30);

        // DB (Admin Panel → ABDM Gateway Config) is the authoritative source for
        // the token, URL, and HFR ID. Always read from DB so that saving a new key
        // in HMS settings takes effect immediately without a server restart or
        // .env change. Fall back to config/env only if DB is unavailable.
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('hospital_setting')) {
                $rows = $db->table('hospital_setting')
                    ->select('s_name, s_value')
                    ->whereIn('s_name', ['EATRIA_BRIDGE_TOKEN', 'EATRIA_BRIDGE_URL', 'ABDM_HFR_ID'])
                    ->get()
                    ->getResultArray();

                $dbSettings = array_column($rows, 's_value', 's_name');

                if (! empty($dbSettings['EATRIA_BRIDGE_TOKEN'])) {
                    $this->token = trim($dbSettings['EATRIA_BRIDGE_TOKEN']);
                }
                if (! empty($dbSettings['EATRIA_BRIDGE_URL'])) {
                    $this->baseUrl = rtrim(trim($dbSettings['EATRIA_BRIDGE_URL']), '/');
                }
                if (! empty($dbSettings['ABDM_HFR_ID'])) {
                    $this->hfrId = trim($dbSettings['ABDM_HFR_ID']);
                }
            }
        } catch (\Throwable $e) {
            // DB unavailable — continue with config/env values
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
                ? (string) json_encode($decodedResp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (trim($rawResponse) !== '' ? $rawResponse : null);

            $db->table('abdm_api_logs')->insert([
                'channel'       => 'eatria_bridge',
                'event_type'    => $eventType !== '' ? $eventType : $path,
                'endpoint'      => $fullUrl,
                'http_method'   => strtoupper($method),
                'entity_type'   => null,
                'entity_id'     => null,
                'request_json'  => (string) json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
        // Normalize to gateway-enforced HI types for /v3/records/push.
        // Runtime valid_types include *Record variants.
        $hiTypeRaw = (string) ($data['hi_type'] ?? '');
        $hiType    = match ($hiTypeRaw) {
            'OPConsultRecord', 'OPConsultation'  => 'OPConsultRecord',
            'PrescriptionRecord', 'Prescription' => 'PrescriptionRecord',
            'DiagnosticReportRecord', 'DiagnosticReport' => 'DiagnosticReportRecord',
            'DischargeSummaryRecord', 'DischargeSummary' => 'DischargeSummaryRecord',
            'WellnessRecord'                     => 'WellnessRecord',
            'ImmunizationRecord'                 => 'ImmunizationRecord',
            'InvoiceRecord'                      => 'InvoiceRecord',
            'HealthDocumentRecord'               => 'HealthDocumentRecord',
            ''                                   => '',
            default                              => $hiTypeRaw,
        };

        // Bridge internal record_type enum (sent alongside hi_type for categorisation)
        $recordTypeIn = (string) ($data['record_type'] ?? '');
        $validRecordTypes = ['prescription', 'lab_report', 'discharge_summary', 'wellness_record', 'health_document'];
        if (in_array($recordTypeIn, $validRecordTypes, true)) {
            $recordType = $recordTypeIn;
        } else {
            // Derive record_type from hi_type
            $recordType = match ($hiType) {
                'OPConsultation', 'Prescription' => 'prescription',
                'DiagnosticReport'               => 'lab_report',
                'DischargeSummary'               => 'discharge_summary',
                'WellnessRecord', 'ImmunizationRecord'  => 'wellness_record',
                default                                 => 'health_document',
            };
        }

        // Derive hi_type from record_type if still empty.
        // Gateway requires hi_type with the exact ABDM HI type name.
        if ($hiType === '') {
            $hiType = match ($recordType) {
                'prescription'      => 'OPConsultRecord',
                'lab_report'        => 'DiagnosticReportRecord',
                'discharge_summary' => 'DischargeSummaryRecord',
                'wellness_record'   => 'WellnessRecord',
                default             => 'HealthDocumentRecord',
            };
        }

        // Gateway requires BOTH record_type (lowercase alias) and hi_type (official ABDM name).
        $body = [
            'patient_id'   => (string) ($data['patient_id'] ?? ''),
            'patient_name' => (string) ($data['patient_name'] ?? ''),
            'record_type'  => $recordType,
            'hi_type'      => $hiType,
            'visit_date'   => (string) ($data['visit_date'] ?? date('Y-m-d')),
            'record_data'  => $data['record_data'] ?? $data['bundle'] ?? (object) [],
            'fhir_bundle'  => $data['record_data'] ?? $data['bundle'] ?? (object) [],
        ];

        // hfr_id is required in every push request alongside the Bearer token.
        if ($this->hfrId !== '') {
            $body['hfr_id'] = $this->hfrId;
        }

        foreach (['abha_id', 'abha_address', 'doctor_name', 'department', 'care_context_reference', 'care_context_display', 'notes', 'queue_id', 'gender', 'year_of_birth'] as $optional) {
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
