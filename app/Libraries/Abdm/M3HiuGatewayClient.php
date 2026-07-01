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

    public function consentRequest(array $payload): array
    {
        return $this->call('/v3/hiu/consent/request', $payload, 'consent.request');
    }

    public function consentRequestStatus(array $payload): array
    {
        return $this->call('/v3/hiu/consent/request/status', $payload, 'consent.request.status');
    }

    public function consentRequestFetch(array $payload): array
    {
        return $this->call('/v3/hiu/consent/request/fetch', $payload, 'consent.request.fetch');
    }

    public function healthInformationRequest(array $payload): array
    {
        return $this->call('/v3/hiu/health-information/request', $payload, 'health-information.request');
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

        $url = $this->baseUrl . $path;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        if ($hospitalId !== '') {
            $headers[] = 'X-Hospital-Id: ' . $hospitalId;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSec,
            CURLOPT_SSL_VERIFYPEER => true,
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
            $errorText = $isJson
                ? (string) ($decoded['message'] ?? $decoded['error_text'] ?? $decoded['error'] ?? ('HTTP ' . $httpCode))
                : ('HTTP ' . $httpCode . ' non-JSON response');
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
        if (! isset($response['request_id'])) {
            $response['request_id'] = (string) ($response['requestId'] ?? $payload['requestId'] ?? $payload['request_id'] ?? '');
        }
        if (! isset($response['transaction_id'])) {
            $response['transaction_id'] = (string) ($response['transactionId'] ?? $payload['transactionId'] ?? $payload['transaction_id'] ?? '');
        }
        if (! isset($response['consent_id'])) {
            $response['consent_id'] = (string) ($response['consentId'] ?? $response['consentRequestId'] ?? $payload['consentId'] ?? $payload['consentRequestId'] ?? $payload['consent_id'] ?? '');
        }

        $this->writeApiLog($eventKey, $path, $payload, $response, $httpCode, $ok, $errorText, $hospitalId);

        return $response;
    }

    private function resolveRuntimeSettings(): array
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return ['ok' => 0, 'http_code' => 500, 'error_text' => 'hospital_setting table not found', 'retryable' => 0];
        }

        $rows = $this->db->table('hospital_setting')
            ->select('s_name, s_value')
            ->whereIn('s_name', ['EATRIA_BRIDGE_TOKEN', 'ABDM_HFR_ID', 'ABDM_HOSPITAL_ID'])
            ->get()
            ->getResultArray();

        $kv = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row['s_name'] ?? ''));
            if ($key === '') {
                continue;
            }
            $kv[$key] = trim((string) ($row['s_value'] ?? ''));
        }

        $token = (string) ($kv['EATRIA_BRIDGE_TOKEN'] ?? '');
        $hfrId = (string) ($kv['ABDM_HFR_ID'] ?? '');
        $hospitalId = (string) ($kv['ABDM_HOSPITAL_ID'] ?? '');

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
        string $hospitalId
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
            'http_method' => 'POST',
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
}
