<?php

namespace App\Libraries\Abdm;

class M3HiuGatewayClient
{
    private \CodeIgniter\Database\BaseConnection $db;
    private string $baseUrl;
    private int $timeoutSec = 45;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->baseUrl = rtrim((string) (getenv('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api'), '/');
    }

    /**
     * SSL peer/host verification is ON by default (secure). Some local
     * WAMP/XAMPP/Laragon installs ship a stale bundled cacert.pem which makes
     * outbound HTTPS calls fail with cURL error 60 (SSL certificate problem).
     * Rather than disabling verification for everyone, allow an explicit,
     * per-deployment opt-out configured in Admin → ABDM Gateway
     * (hospital_setting.ABDM_BRIDGE_SSL_VERIFY), or via env for that specific
     * machine (ABDM_BRIDGE_SSL_VERIFY=false, takes precedence over the DB
     * setting). The correct long-term fix on the affected machine is to
     * update PHP's CA bundle (curl.cainfo in php.ini), not to disable
     * verification.
     */
    private function sslVerifyEnabled(string $dbSetting = ''): bool
    {
        $envRaw = strtolower(trim((string) (getenv('ABDM_BRIDGE_SSL_VERIFY') ?: '')));
        if ($envRaw !== '') {
            return ! in_array($envRaw, ['0', 'false', 'no', 'off'], true);
        }

        $dbRaw = strtolower(trim($dbSetting));
        if ($dbRaw !== '') {
            return ! in_array($dbRaw, ['0', 'false', 'no', 'off'], true);
        }

        return true;
    }

    private function sanitizeBearerToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }

        $token = trim($token, " \t\n\r\0\x0B\"'");
        $token = (string) preg_replace('/\s+/u', '', $token);

        return $token;
    }

    public function consentRequest(array $payload): array
    {
        return $this->call('/v3/hiu/consent/request', $payload, 'consent.request');
    }

    public function consentRequestStatus(array $payload): array
    {
        return $this->call('/v3/hiu/consent/request/status', $payload, 'consent.request.status');
    }

    public function reconcileConsentStatus(array $payload): array
    {
        return $this->callGet('/v1/hiu/consent/status', $payload, 'consent.status.reconcile');
    }

    public function fetchDecryptedData(array $payload): array
    {
        return $this->callGet('/v1/hiu/data/fetch', $payload, 'data.fetch');
    }

    private function call(string $path, array $payload, string $eventKey): array
    {
        $settings = $this->resolveRuntimeSettings();
        if (($settings['ok'] ?? 0) !== 1) {
            return $settings;
        }

        $token = (string) ($settings['token'] ?? '');
        $hfrId = (string) ($settings['hfr_id'] ?? '');
        $hospitalId = (string) ($settings['hospital_id'] ?? '');
        $runtimeBaseUrl = trim((string) ($settings['base_url'] ?? ''));
        if ($runtimeBaseUrl !== '') {
            $this->baseUrl = rtrim($runtimeBaseUrl, '/');
        }

        $payloadHfrId = trim((string) ($payload['hfr_id'] ?? ''));
        if ($payloadHfrId !== '' && strcasecmp($payloadHfrId, $hfrId) !== 0) {
            return [
                'ok' => 0,
                'http_code' => 400,
                'error_text' => 'hfr_id mismatch: payload hfr_id does not match hospital_setting.ABDM_HFR_ID',
                'retryable' => 0,
            ];
        }
        $payload['hfr_id'] = $hfrId;
        $payload = $this->clampConsentDateRangeToNow($payload);

        $url = $this->baseUrl . $path;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        if ($hospitalId !== '') {
            $headers[] = 'X-Hospital-Id: ' . $hospitalId;
        }

        $sslVerify = $this->sslVerifyEnabled((string) ($settings['ssl_verify'] ?? ''));
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSec,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);
        $isJson = is_array($decoded);
        $ok = $isJson ? (int) ($decoded['ok'] ?? ($httpCode >= 200 && $httpCode < 300 ? 1 : 0)) : (int) ($httpCode >= 200 && $httpCode < 300);

        $errorText = '';
        if ($curlErr !== '') {
            $errorText = $curlErr;
        } elseif ($ok !== 1) {
            $errorText = $this->extractErrorText($decoded, $httpCode, $isJson);
        }

        if ($this->isCloudFrontBlockedResponse($httpCode, $raw, $decoded ?? [])) {
            $errorText = 'Gateway edge block (CloudFront 403 Request blocked). Retry later or ask gateway team to unblock the endpoint.';
        }

        $retryable = 0;
        if ($curlErr !== '' || $httpCode >= 500 || $httpCode === 0 || str_contains(strtolower($errorText), 'timed out')) {
            $retryable = 1;
        }

        $response = $isJson ? $decoded : ['raw' => (string) $raw];
        $response['ok'] = $ok;
        $response['http_code'] = $httpCode;
        $response['error_text'] = $errorText;
        $response['retryable'] = $retryable;

        if ($this->isCloudFrontBlockedResponse($httpCode, $raw, $response)) {
            $response['retryable'] = 1;
            $response['error_text'] = 'Gateway edge block (CloudFront 403 Request blocked). Retry later or ask gateway team to unblock the endpoint.';
        }

        $gatewayRequestId = trim((string) ($response['request_id'] ?? $response['requestId'] ?? ''));
        if ($gatewayRequestId === '') {
            $gatewayRequestId = trim((string) ($payload['requestId'] ?? $payload['request_id'] ?? ''));
        }
        $response['gateway_request_id'] = $gatewayRequestId;

        $abdmConsentRequestId = '';
        if (isset($response['consent']) && is_array($response['consent'])) {
            $abdmConsentRequestId = trim((string) (
                $response['consent']['consent_request_id']
                ?? $response['consent']['consentRequestId']
                ?? $response['consent']['abdm_consent_request_id']
                ?? ''
            ));
        }
        if ($abdmConsentRequestId === '' && isset($response['consentDetail']) && is_array($response['consentDetail'])) {
            $abdmConsentRequestId = trim((string) (
                $response['consentDetail']['consent_request_id']
                ?? $response['consentDetail']['consentRequestId']
                ?? ''
            ));
        }
        if ($abdmConsentRequestId === '' && isset($response['data']) && is_array($response['data'])) {
            if (isset($response['data']['consent']) && is_array($response['data']['consent'])) {
                $abdmConsentRequestId = trim((string) (
                    $response['data']['consent']['consent_request_id']
                    ?? $response['data']['consent']['consentRequestId']
                    ?? ''
                ));
            }
            if ($abdmConsentRequestId === '') {
                $abdmConsentRequestId = trim((string) (
                    $response['data']['consent_request_id']
                    ?? $response['data']['consentRequestId']
                    ?? $response['data']['abdm_consent_request_id']
                    ?? ''
                ));
            }
        }
        if ($abdmConsentRequestId === '') {
            $abdmConsentRequestId = $this->findFirstValueByKeys($response, [
                'consentRequestId',
                'consent_request_id',
                'abdm_consent_request_id',
            ]);
        }
        if ($abdmConsentRequestId !== '' && ! $this->isGatewayRefId($abdmConsentRequestId)) {
            $response['abdm_consent_request_id'] = $abdmConsentRequestId;
            $response['consent_request_id'] = $abdmConsentRequestId;
        }

        $abdmConsentId = '';
        if (isset($response['consent']) && is_array($response['consent'])) {
            $abdmConsentId = trim((string) (
                $response['consent']['id']
                ?? $response['consent']['consent_id']
                ?? $response['consent']['consentId']
                ?? $response['consent']['consent_artifact_id']
                ?? $response['consent']['consent_artefact_id']
                ?? ''
            ));
        }
        if ($abdmConsentId === '' && isset($response['consentDetail']) && is_array($response['consentDetail'])) {
            $abdmConsentId = trim((string) (
                $response['consentDetail']['id']
                ?? $response['consentDetail']['consent_id']
                ?? $response['consentDetail']['consentId']
                ?? ''
            ));
        }
        if ($abdmConsentId === '' && isset($response['data']) && is_array($response['data'])) {
            if (isset($response['data']['consent']) && is_array($response['data']['consent'])) {
                $abdmConsentId = trim((string) (
                    $response['data']['consent']['id']
                    ?? $response['data']['consent']['consent_id']
                    ?? $response['data']['consent']['consentId']
                    ?? ''
                ));
            }
            if ($abdmConsentId === '') {
                $abdmConsentId = trim((string) (
                    $response['data']['consent_id']
                    ?? $response['data']['consentId']
                    ?? $response['data']['consent_artifact_id']
                    ?? ''
                ));
            }
        }
        if ($abdmConsentId === '') {
            $abdmConsentId = $this->findFirstValueByKeys($response, [
                'consent_id',
                'consentId',
                'abdm_consent_artifact_id',
                'consent_artifact_id',
                'consent_artefact_id',
                'consentArtifactId',
                'consentArtefactId',
            ]);
        }
        if ($abdmConsentId !== '' && ! $this->isGatewayRefId($abdmConsentId)) {
            $response['consent_id'] = $abdmConsentId;
            $response['abdm_consent_artifact_id'] = $abdmConsentId;
        }

        if (! isset($response['request_id'])) {
            $response['request_id'] = $gatewayRequestId;
        }
        if (! isset($response['transaction_id'])) {
            $response['transaction_id'] = (string) ($response['transactionId'] ?? $payload['transactionId'] ?? $payload['transaction_id'] ?? '');
        }

        $this->writeApiLog($eventKey, $path, $payload, $response, $httpCode, $ok, $errorText, $hospitalId);

        return $response;
    }

    private function clampConsentDateRangeToNow(array $payload): array
    {
        if (! isset($payload['consent']) || ! is_array($payload['consent'])) {
            return $payload;
        }
        if (! isset($payload['consent']['permission']) || ! is_array($payload['consent']['permission'])) {
            return $payload;
        }
        if (! isset($payload['consent']['permission']['dateRange']) || ! is_array($payload['consent']['permission']['dateRange'])) {
            return $payload;
        }

        $toRaw = trim((string) ($payload['consent']['permission']['dateRange']['to'] ?? ''));
        if ($toRaw === '') {
            return $payload;
        }

        $safeNowUtc = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->modify('-120 seconds');
        $safeNowIso = $safeNowUtc->format('Y-m-d\TH:i:s.000\Z');

        try {
            $toAt = new \DateTimeImmutable($toRaw);
            if ($toAt->getTimestamp() > $safeNowUtc->getTimestamp()) {
                $payload['consent']['permission']['dateRange']['to'] = $safeNowIso;
            }
        } catch (\Throwable $e) {
            $payload['consent']['permission']['dateRange']['to'] = $safeNowIso;
        }

        return $payload;
    }

    private function callGet(string $path, array $query, string $eventKey): array
    {
        $settings = $this->resolveRuntimeSettings();
        if (($settings['ok'] ?? 0) !== 1) {
            return $settings;
        }

        $token = (string) ($settings['token'] ?? '');
        $hfrId = (string) ($settings['hfr_id'] ?? '');
        $hospitalId = (string) ($settings['hospital_id'] ?? '');
        $runtimeBaseUrl = trim((string) ($settings['base_url'] ?? ''));
        if ($runtimeBaseUrl !== '') {
            $this->baseUrl = rtrim($runtimeBaseUrl, '/');
        }

        $payloadHfrId = trim((string) ($query['hfr_id'] ?? ''));
        if ($payloadHfrId !== '' && strcasecmp($payloadHfrId, $hfrId) !== 0) {
            return [
                'ok' => 0,
                'http_code' => 400,
                'error_text' => 'hfr_id mismatch: payload hfr_id does not match hospital_setting.ABDM_HFR_ID',
                'retryable' => 0,
            ];
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        if ($hospitalId !== '') {
            $headers[] = 'X-Hospital-Id: ' . $hospitalId;
        }

        $cleanQuery = [];
        foreach ($query as $k => $v) {
            if ($v === null) {
                continue;
            }
            $val = trim((string) $v);
            if ($val === '') {
                continue;
            }
            $cleanQuery[$k] = $val;
        }

        // Bridge polling APIs require hfr_id in query for auth scope validation.
        if ($hfrId !== '') {
            $cleanQuery['hfr_id'] = $hfrId;
        }

        $url = $this->baseUrl . $path;
        if (! empty($cleanQuery)) {
            $url .= '?' . http_build_query($cleanQuery);
        }

        $sslVerify = $this->sslVerifyEnabled((string) ($settings['ssl_verify'] ?? ''));
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSec,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string) $raw, true);
        $isJson = is_array($decoded);
        $ok = $isJson ? (int) ($decoded['ok'] ?? ($httpCode >= 200 && $httpCode < 300 ? 1 : 0)) : (int) ($httpCode >= 200 && $httpCode < 300);

        $errorText = '';
        if ($curlErr !== '') {
            $errorText = $curlErr;
        } elseif ($ok !== 1) {
            $errorText = $this->extractErrorText($decoded, $httpCode, $isJson);
        }

        if ($this->isCloudFrontBlockedResponse($httpCode, $raw, $decoded ?? [])) {
            $errorText = 'Gateway edge block (CloudFront 403 Request blocked). Retry later or ask gateway team to unblock the endpoint.';
        }

        $retryable = 0;
        if ($curlErr !== '' || $httpCode >= 500 || $httpCode === 0 || str_contains(strtolower($errorText), 'timed out')) {
            $retryable = 1;
        }

        $response = $isJson ? $decoded : ['raw' => (string) $raw];
        $response['ok'] = $ok;
        $response['http_code'] = $httpCode;
        $response['error_text'] = $errorText;
        $response['retryable'] = $retryable;

        if ($this->isCloudFrontBlockedResponse($httpCode, $raw, $response)) {
            $response['retryable'] = 1;
            $response['error_text'] = 'Gateway edge block (CloudFront 403 Request blocked). Retry later or ask gateway team to unblock the endpoint.';
        }

        $gatewayRequestId = trim((string) ($response['request_id'] ?? $response['requestId'] ?? $cleanQuery['request_id'] ?? ''));
        $response['gateway_request_id'] = $gatewayRequestId;

        $abdmConsentRequestId = '';
        if (isset($response['consent']) && is_array($response['consent'])) {
            $abdmConsentRequestId = trim((string) (
                $response['consent']['consent_request_id']
                ?? $response['consent']['consentRequestId']
                ?? $response['consent']['abdm_consent_request_id']
                ?? ''
            ));
        }
        if ($abdmConsentRequestId === '' && isset($response['consentDetail']) && is_array($response['consentDetail'])) {
            $abdmConsentRequestId = trim((string) (
                $response['consentDetail']['consent_request_id']
                ?? $response['consentDetail']['consentRequestId']
                ?? ''
            ));
        }
        if ($abdmConsentRequestId === '' && isset($response['data']) && is_array($response['data'])) {
            if (isset($response['data']['consent']) && is_array($response['data']['consent'])) {
                $abdmConsentRequestId = trim((string) (
                    $response['data']['consent']['consent_request_id']
                    ?? $response['data']['consent']['consentRequestId']
                    ?? ''
                ));
            }
            if ($abdmConsentRequestId === '') {
                $abdmConsentRequestId = trim((string) (
                    $response['data']['consent_request_id']
                    ?? $response['data']['consentRequestId']
                    ?? $response['data']['abdm_consent_request_id']
                    ?? ''
                ));
            }
        }
        if ($abdmConsentRequestId === '') {
            $abdmConsentRequestId = $this->findFirstValueByKeys($response, [
                'consentRequestId',
                'consent_request_id',
                'abdm_consent_request_id',
            ]);
        }
        if ($abdmConsentRequestId !== '' && ! $this->isGatewayRefId($abdmConsentRequestId)) {
            $response['abdm_consent_request_id'] = $abdmConsentRequestId;
            $response['consent_request_id'] = $abdmConsentRequestId;
        }

        $abdmConsentId = '';
        if (isset($response['consent']) && is_array($response['consent'])) {
            $abdmConsentId = trim((string) (
                $response['consent']['id']
                ?? $response['consent']['consent_id']
                ?? $response['consent']['consentId']
                ?? $response['consent']['consent_artifact_id']
                ?? $response['consent']['consent_artefact_id']
                ?? ''
            ));
        }
        if ($abdmConsentId === '' && isset($response['consentDetail']) && is_array($response['consentDetail'])) {
            $abdmConsentId = trim((string) (
                $response['consentDetail']['id']
                ?? $response['consentDetail']['consent_id']
                ?? $response['consentDetail']['consentId']
                ?? ''
            ));
        }
        if ($abdmConsentId === '' && isset($response['data']) && is_array($response['data'])) {
            if (isset($response['data']['consent']) && is_array($response['data']['consent'])) {
                $abdmConsentId = trim((string) (
                    $response['data']['consent']['id']
                    ?? $response['data']['consent']['consent_id']
                    ?? $response['data']['consent']['consentId']
                    ?? ''
                ));
            }
            if ($abdmConsentId === '') {
                $abdmConsentId = trim((string) (
                    $response['data']['consent_id']
                    ?? $response['data']['consentId']
                    ?? $response['data']['consent_artifact_id']
                    ?? ''
                ));
            }
        }
        if ($abdmConsentId === '') {
            $abdmConsentId = $this->findFirstValueByKeys($response, [
                'consent_id',
                'consentId',
                'abdm_consent_artifact_id',
                'consent_artifact_id',
                'consent_artefact_id',
                'consentArtifactId',
                'consentArtefactId',
            ]);
        }
        if ($abdmConsentId !== '' && ! $this->isGatewayRefId($abdmConsentId)) {
            $response['consent_id'] = $abdmConsentId;
            $response['abdm_consent_artifact_id'] = $abdmConsentId;
        }

        // Per bridge contract, consent status/date_range/expiry come back nested
        // under `consent{}` (e.g. { consent: { status: "GRANTED", ... } }) rather
        // than at the top level. Flatten them so resolveState()/snapshot logic
        // (which reads $response['consent_status']) can see the real value —
        // otherwise GRANTED/EXPIRED/REVOKED consents keep getting misclassified.
        $abdmConsentStatus = '';
        if (isset($response['consent']) && is_array($response['consent'])) {
            $abdmConsentStatus = trim((string) (
                $response['consent']['status']
                ?? $response['consent']['consent_status']
                ?? $response['consent']['consentStatus']
                ?? $response['consent']['state']
                ?? ''
            ));
        }
        if ($abdmConsentStatus === '' && isset($response['consentDetail']) && is_array($response['consentDetail'])) {
            $abdmConsentStatus = trim((string) (
                $response['consentDetail']['status']
                ?? $response['consentDetail']['consent_status']
                ?? $response['consentDetail']['consentStatus']
                ?? ''
            ));
        }
        if ($abdmConsentStatus === '' && isset($response['data']) && is_array($response['data'])) {
            if (isset($response['data']['consent']) && is_array($response['data']['consent'])) {
                $abdmConsentStatus = trim((string) (
                    $response['data']['consent']['status']
                    ?? $response['data']['consent']['consent_status']
                    ?? $response['data']['consent']['consentStatus']
                    ?? ''
                ));
            }
            if ($abdmConsentStatus === '') {
                $abdmConsentStatus = trim((string) (
                    $response['data']['consent_status']
                    ?? $response['data']['consentStatus']
                    ?? ''
                ));
            }
        }
        if ($abdmConsentStatus === '') {
            $abdmConsentStatus = trim((string) (
                $response['consent_status']
                ?? $response['consentStatus']
                ?? ''
            ));
        }
        if ($abdmConsentStatus === '') {
            $potentialStatus = $this->findFirstValueByKeys($response, ['consent_status', 'consentStatus', 'status']);
            if (! in_array(strtolower($potentialStatus), ['success', 'ok', 'failed', 'error', 'true', 'false', '1', '0'], true)) {
                $abdmConsentStatus = $potentialStatus;
            }
        }
        if ($abdmConsentStatus !== '') {
            $response['consent_status'] = strtoupper($abdmConsentStatus);
        }
        if (isset($response['consent']) && is_array($response['consent'])) {
            foreach (['date_range', 'dateRange', 'expiry', 'purpose', 'hi_types', 'abha_address', 'granted_at'] as $flattenKey) {
                if (! isset($response[$flattenKey]) && isset($response['consent'][$flattenKey])) {
                    $response[$flattenKey] = $response['consent'][$flattenKey];
                }
            }
        }

        if (! isset($response['transaction_id'])) {
            $response['transaction_id'] = (string) ($response['transactionId'] ?? $cleanQuery['transaction_id'] ?? '');
        }

        $this->writeApiLog($eventKey, $path, $cleanQuery, $response, $httpCode, $ok, $errorText, $hospitalId, 'GET');

        return $response;
    }

    private function resolveRuntimeSettings(): array
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return ['ok' => 0, 'http_code' => 500, 'error_text' => 'hospital_setting table not found', 'retryable' => 0];
        }

        $settingFields = $this->db->getFieldNames('hospital_setting') ?? [];
        $orderCol = null;
        foreach (['id', 's_id'] as $candidateOrderCol) {
            if (in_array($candidateOrderCol, $settingFields, true)) {
                $orderCol = $candidateOrderCol;
                break;
            }
        }

        $rowsBuilder = $this->db->table('hospital_setting')
            ->select('s_name, s_value')
            ->whereIn('s_name', [
                'EATRIA_BRIDGE_TOKEN',
                'ABDM_HFR_ID',
                'H_HFR_ID',
                'ABDM_HOSPITAL_HFR_ID',
                'ABDM_HOSPITAL_ID',
                'ABDM_BRIDGE_HOSPITAL_ID',
                'EATRIA_BRIDGE_HOSPITAL_ID',
                'EATRIA_BRIDGE_URL',
                'ABDM_BRIDGE_SSL_VERIFY',
            ]);
        if ($orderCol !== null) {
            $rowsBuilder->orderBy($orderCol, 'DESC');
        }

        $rows = $rowsBuilder->get()->getResultArray();

        $kv = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['s_name'] ?? ''));
            if ($key === '' || array_key_exists($key, $kv)) {
                continue;
            }
            $kv[$key] = trim((string) ($row['s_value'] ?? ''));
        }

        $token = $this->sanitizeBearerToken((string) ($kv['EATRIA_BRIDGE_TOKEN'] ?? ''));
        $hfrId = (string) ($kv['ABDM_HFR_ID'] ?? $kv['H_HFR_ID'] ?? $kv['ABDM_HOSPITAL_HFR_ID'] ?? '');
        $hospitalId = (string) ($kv['ABDM_HOSPITAL_ID'] ?? $kv['ABDM_BRIDGE_HOSPITAL_ID'] ?? $kv['EATRIA_BRIDGE_HOSPITAL_ID'] ?? '');
        $baseUrl = trim((string) ($kv['EATRIA_BRIDGE_URL'] ?? ''));

        if ($token === '') {
            return [
                'ok' => 0,
                'http_code' => 401,
                'error_text' => 'Missing token: set hospital_setting.EATRIA_BRIDGE_TOKEN',
                'retryable' => 0,
            ];
        }
        if ($hfrId === '') {
            return [
                'ok' => 0,
                'http_code' => 400,
                'error_text' => 'Missing hfr_id: set hospital_setting.ABDM_HFR_ID',
                'retryable' => 0,
            ];
        }

        return [
            'ok' => 1,
            'token' => $token,
            'hfr_id' => $hfrId,
            'hospital_id' => $hospitalId,
            'base_url' => $baseUrl,
            'ssl_verify' => (string) ($kv['ABDM_BRIDGE_SSL_VERIFY'] ?? ''),
        ];
    }

    private function writeApiLog(
        string $eventKey,
        string $path,
        array $request,
        array $response,
        int $httpCode,
        int $ok,
        string $errorText,
        string $hospitalId,
        string $httpMethod = 'POST'
    ): void {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        $status = $ok === 1 ? 'success' : 'error';
        $entityId = trim((string) (
            $request['consent_id']
            ?? $request['consentId']
            ?? $request['consentRequestId']
            ?? $request['request_id']
            ?? $request['requestId']
            ?? $request['transaction_id']
            ?? $request['transactionId']
            ?? ''
        ));

        $this->db->table('abdm_api_logs')->insert([
            'channel' => 'eatria_bridge',
            'event_type' => 'abdm.m3.hiu.' . $eventKey,
            'endpoint' => $this->baseUrl . $path,
            'http_method' => strtoupper(trim($httpMethod)) !== '' ? strtoupper(trim($httpMethod)) : 'POST',
            'entity_type' => 'abdm_hiu',
            'entity_id' => $entityId,
            'request_json' => (string) json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => $httpCode,
            'response_json' => (string) json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $status,
            'error_message' => $errorText !== '' ? mb_substr($errorText, 0, 1000) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function extractErrorText($decoded, int $httpCode, bool $isJson): string
    {
        if (! $isJson || ! is_array($decoded)) {
            return 'HTTP ' . $httpCode . ' non-JSON response';
        }

        // Some bridge error responses wrap the error object in a list, e.g.
        // {"data": [{"error": {"code": "...", "message": "..."}}]}, instead of
        // a single {"data": {"error": {...}}} object. Normalize both shapes.
        $errorNode = $decoded['data']['error'] ?? $decoded['error'] ?? null;
        if (! is_array($errorNode) && isset($decoded['data']) && is_array($decoded['data'])) {
            foreach ($decoded['data'] as $item) {
                if (is_array($item) && isset($item['error']) && is_array($item['error'])) {
                    $errorNode = $item['error'];
                    break;
                }
            }
        }

        $code = trim((string) ($errorNode['code'] ?? ''));
        $message = trim((string) (
            $errorNode['message']
            ?? $decoded['message']
            ?? $decoded['error_text']
            ?? (is_string($decoded['error'] ?? null) ? $decoded['error'] : '')
        ));

        $combined = trim(($code !== '' ? $code . ' ' : '') . $message);
        if ($combined !== '') {
            return $combined;
        }

        return 'HTTP ' . $httpCode;
    }

    private function findFirstValueByKeys(array $root, array $keys): string
    {
        $queue = [$root];
        while (! empty($queue)) {
            $node = array_shift($queue);
            if (! is_array($node)) {
                continue;
            }

            foreach ($keys as $key) {
                if (isset($node[$key])) {
                    $value = trim((string) $node[$key]);
                    if ($value !== '') {
                        return $value;
                    }
                }
            }

            foreach ($node as $child) {
                if (is_array($child)) {
                    $queue[] = $child;
                }
            }
        }

        return '';
    }

    private function isGatewayRefId(string $value): bool
    {
        return preg_match('/^REQ-/i', trim($value)) === 1;
    }

    private function isCloudFrontBlockedResponse(int $httpCode, $raw, array $response): bool
    {
        if ($httpCode !== 403) {
            return false;
        }

        $text = strtolower((string) $raw);
        if ($text === '' && isset($response['data']['raw'])) {
            $text = strtolower((string) $response['data']['raw']);
        }

        return str_contains($text, 'generated by cloudfront')
            || str_contains($text, 'request blocked')
            || str_contains($text, 'the request could not be satisfied');
    }
}
