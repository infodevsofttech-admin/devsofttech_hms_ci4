<?php

namespace App\Libraries;

use App\Models\BridgeSyncQueueModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class BridgeSyncService
{
    private BaseConnection $db;
    private BridgeSyncQueueModel $queueModel;

    public function __construct()
    {
        $this->db = db_connect();
        $this->queueModel = new BridgeSyncQueueModel();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $eventType, array $payload, string $entityType = '', string $entityId = '', int $maxAttempts = 10): ?int
    {
        if (! $this->db->tableExists('bridge_sync_queue')) {
            return null;
        }

        $payloadJson = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === '' || $payloadJson === 'null') {
            return null;
        }

        $this->queueModel->insert([
            'channel' => 'bridge',
            'event_type' => $eventType,
            'entity_type' => $entityType !== '' ? $entityType : null,
            'entity_id' => $entityId !== '' ? $entityId : null,
            'payload_json' => $payloadJson,
            'payload_hash' => hash('sha256', $eventType . '|' . $entityType . '|' . $entityId . '|' . $payloadJson),
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => max(1, $maxAttempts),
            'next_attempt_at' => null,
        ]);

        $insertId = $this->queueModel->getInsertID();
        return $insertId > 0 ? (int) $insertId : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function processPending(int $limit = 50, string $workerName = 'spark-bridge-sync'): array
    {
        $summary = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'message' => '',
        ];

        if (! $this->db->tableExists('bridge_sync_queue')) {
            $summary['message'] = 'bridge_sync_queue table not found';
            return $summary;
        }

        $rows = $this->db->table('bridge_sync_queue')
            ->whereIn('status', ['pending', 'retry'])
            ->groupStart()
            ->where('next_attempt_at IS NULL', null, false)
            ->orWhere('next_attempt_at <=', Time::now('Asia/Kolkata')->toDateTimeString())
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            $summary['message'] = 'no pending queue records';
            return $summary;
        }

        $clientOptions = [
            'timeout' => 20,
            'http_errors' => false,
        ];
        if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
            $clientOptions['verify'] = false;
        }

        $client = service('curlrequest', $clientOptions);

        foreach ($rows as $row) {
            $queueId = (int) ($row['id'] ?? 0);
            if ($queueId <= 0) {
                $summary['skipped']++;
                continue;
            }

            $locked = $this->db->table('bridge_sync_queue')
                ->where('id', $queueId)
                ->whereIn('status', ['pending', 'retry'])
                ->update([
                    'status' => 'processing',
                    'locked_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    'locked_by' => $workerName,
                ]);

            if (! $locked) {
                $summary['skipped']++;
                continue;
            }

            $summary['processed']++;

            $attempts = (int) ($row['attempts'] ?? 0);
            $maxAttempts = max(1, (int) ($row['max_attempts'] ?? 10));
            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = ['raw' => (string) ($row['payload_json'] ?? '')];
            }

            $dispatch = $this->buildDispatchContext($row, $payload);
            if (($dispatch['ok'] ?? false) !== true) {
                $nextAttempts = $attempts + 1;
                $isFinalFailure = $nextAttempts >= $maxAttempts;
                $delaySeconds = min(3600, (int) pow(2, min(10, $nextAttempts)) * 30);
                $reason = trim((string) ($dispatch['error'] ?? 'No dispatch configuration'));

                $this->db->table('bridge_sync_queue')->where('id', $queueId)->update([
                    'status' => $isFinalFailure ? 'failed' : 'retry',
                    'attempts' => $nextAttempts,
                    'next_attempt_at' => $isFinalFailure ? null : date('Y-m-d H:i:s', time() + $delaySeconds),
                    'last_error' => mb_substr($reason, 0, 500),
                    'locked_at' => null,
                    'locked_by' => null,
                ]);
                $summary['failed']++;
                continue;
            }

            $channel = (string) ($dispatch['channel'] ?? 'bridge');
            $endpoint = (string) ($dispatch['endpoint'] ?? '');
            $method = strtoupper((string) ($dispatch['method'] ?? 'POST'));
            $headers = (array) ($dispatch['headers'] ?? ['Content-Type' => 'application/json']);
            $postBody = (array) ($dispatch['body'] ?? []);

            try {
                $response = $client->request($method, $endpoint, [
                    'headers' => $headers,
                    'json' => $postBody,
                ]);

                $status = $response->getStatusCode();
                $body = (string) $response->getBody();

                $this->logApiCall(
                    $channel,
                    (string) ($row['event_type'] ?? ''),
                    $endpoint,
                    $method,
                    (string) ($row['entity_type'] ?? ''),
                    (string) ($row['entity_id'] ?? ''),
                    $postBody,
                    $status,
                    $body,
                    $status >= 200 && $status < 300 ? 'success' : 'error',
                    $status >= 200 && $status < 300 ? '' : ('HTTP ' . $status)
                );

                if ($status >= 200 && $status < 300) {
                    $this->db->table('bridge_sync_queue')->where('id', $queueId)->update([
                        'status' => 'sent',
                        'sent_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                        'last_error' => null,
                        'locked_at' => null,
                        'locked_by' => null,
                    ]);
                    $summary['sent']++;
                    continue;
                }

                $nextAttempts = $attempts + 1;
                $isFinalFailure = $nextAttempts >= $maxAttempts;
                $delaySeconds = min(3600, (int) pow(2, min(10, $nextAttempts)) * 30);

                $this->db->table('bridge_sync_queue')->where('id', $queueId)->update([
                    'status' => $isFinalFailure ? 'failed' : 'retry',
                    'attempts' => $nextAttempts,
                    'next_attempt_at' => $isFinalFailure ? null : date('Y-m-d H:i:s', time() + $delaySeconds),
                    'last_error' => 'HTTP ' . $status . ' ' . mb_substr(trim($body), 0, 500),
                    'locked_at' => null,
                    'locked_by' => null,
                ]);
                $summary['failed']++;
            } catch (\Throwable $e) {
                $this->logApiCall(
                    $channel,
                    (string) ($row['event_type'] ?? ''),
                    $endpoint,
                    $method,
                    (string) ($row['entity_type'] ?? ''),
                    (string) ($row['entity_id'] ?? ''),
                    $postBody,
                    null,
                    '',
                    'error',
                    $e->getMessage()
                );

                $nextAttempts = $attempts + 1;
                $isFinalFailure = $nextAttempts >= $maxAttempts;
                $delaySeconds = min(3600, (int) pow(2, min(10, $nextAttempts)) * 30);

                $this->db->table('bridge_sync_queue')->where('id', $queueId)->update([
                    'status' => $isFinalFailure ? 'failed' : 'retry',
                    'attempts' => $nextAttempts,
                    'next_attempt_at' => $isFinalFailure ? null : date('Y-m-d H:i:s', time() + $delaySeconds),
                    'last_error' => mb_substr($e->getMessage(), 0, 500),
                    'locked_at' => null,
                    'locked_by' => null,
                ]);
                $summary['failed']++;
            }
        }

        $summary['message'] = 'queue processing completed';
        return $summary;
    }

    /**
     * @param array<string, mixed> $requestPayload
     */
    private function logApiCall(
        string $channel,
        string $eventType,
        string $endpoint,
        string $method,
        string $entityType,
        string $entityId,
        array $requestPayload,
        ?int $responseCode,
        string $responseBody,
        string $status,
        string $errorMessage
    ): void {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        $requestJson = (string) json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $decodedBody = json_decode($responseBody, true);
        $responseJson = is_array($decodedBody)
            ? (string) json_encode($decodedBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : trim($responseBody);

        $this->db->table('abdm_api_logs')->insert([
            'channel' => $channel !== '' ? $channel : 'bridge',
            'event_type' => $eventType,
            'endpoint' => $endpoint,
            'http_method' => strtoupper($method),
            'entity_type' => $entityType !== '' ? $entityType : null,
            'entity_id' => $entityId !== '' ? $entityId : null,
            'request_json' => $requestJson,
            'response_code' => $responseCode,
            'response_json' => $responseJson !== '' ? $responseJson : null,
            'status' => $status,
            'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
            'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ]);
    }

    private function readSetting(string $name): string
    {
        $envValue = getenv($name);
        if ($envValue !== false) {
            $value = trim((string) $envValue);
            if ($value !== '') {
                return $value;
            }
        }

        if (defined($name)) {
            $value = trim((string) constant($name));
            if ($value !== '') {
                return $value;
            }
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildDispatchContext(array $row, array $payload): array
    {
        $eventType = (string) ($row['event_type'] ?? '');
        if ($eventType === '') {
            return ['ok' => false, 'error' => 'Missing event_type'];
        }

        $isAbdmOrNhcx = str_starts_with($eventType, 'abdm.') || str_starts_with($eventType, 'nhcx.');
        $provider = strtolower($this->readSetting('ABDM_SYNC_PROVIDER'));
        if ($provider === '' || ! $isAbdmOrNhcx) {
            $provider = strtolower($this->readSetting('BRIDGE_SYNC_PROVIDER'));
        }
        if ($provider === '') {
            $provider = 'bridge';
        }

        if ($provider === 'eka' && $isAbdmOrNhcx) {
            return $this->buildEkaDispatchContext($row, $payload);
        }

        return $this->buildBridgeDispatchContext($row, $payload);
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildBridgeDispatchContext(array $row, array $payload): array
    {
        $endpoint = $this->readSetting('BRIDGE_SYNC_URL');
        if ($endpoint === '') {
            return ['ok' => false, 'error' => 'BRIDGE_SYNC_URL is not configured'];
        }

        $token = $this->readSetting('BRIDGE_SYNC_TOKEN');
        $source = $this->readSetting('BRIDGE_SOURCE_CODE');
        if ($source === '') {
            $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');
        }

        $headers = ['Content-Type' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return [
            'ok' => true,
            'channel' => 'bridge',
            'endpoint' => $endpoint,
            'method' => 'POST',
            'headers' => $headers,
            'body' => [
                'queue_id' => (int) ($row['id'] ?? 0),
                'source' => $source,
                'event_type' => (string) ($row['event_type'] ?? ''),
                'entity_type' => (string) ($row['entity_type'] ?? ''),
                'entity_id' => (string) ($row['entity_id'] ?? ''),
                'payload' => $payload,
                'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildEkaDispatchContext(array $row, array $payload): array
    {
        $baseUrl = rtrim($this->readSetting('EKA_BASE_URL'), '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'error' => 'EKA_BASE_URL is not configured'];
        }

        $eventType = (string) ($row['event_type'] ?? '');
        $eventPath = $this->resolveEkaEventPath($eventType);
        if ($eventPath === '') {
            return ['ok' => false, 'error' => 'No Eka endpoint mapping found for event ' . $eventType];
        }

        $token = $this->readSetting('EKA_BEARER_TOKEN');
        $apiKey = $this->readSetting('EKA_API_KEY');
        $clientId = $this->readSetting('EKA_CLIENT_ID');

        $headers = ['Content-Type' => 'application/json'];
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        } elseif ($apiKey !== '') {
            $headers['x-api-key'] = $apiKey;
        }
        if ($clientId !== '') {
            $headers['x-client-id'] = $clientId;
        }

        $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');

        return [
            'ok' => true,
            'channel' => 'eka',
            'endpoint' => $baseUrl . $eventPath,
            'method' => 'POST',
            'headers' => $headers,
            'body' => [
                'queue_id' => (int) ($row['id'] ?? 0),
                'source' => $source,
                'event_type' => $eventType,
                'entity_type' => (string) ($row['entity_type'] ?? ''),
                'entity_id' => (string) ($row['entity_id'] ?? ''),
                'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
                'payload' => $payload,
            ],
        ];
    }

    private function resolveEkaEventPath(string $eventType): string
    {
        $mapping = [
            'abdm.abha.validate' => '/api/v1/abdm/abha/validate',
            'abdm.abha.profile.fetch.requested' => '/api/v1/abdm/abha/profile',
            'abdm.abha.create.requested' => '/api/v1/abdm/abha/create',
            'abdm.abha.update.requested' => '/api/v1/abdm/abha/update',
            'abdm.scan_share.lookup' => '/api/v1/abdm/scan-share/lookup',
            'abdm.consent.requested' => '/api/v1/abdm/consents/request',
            'abdm.fhir.share.requested' => '/api/v1/abdm/health-information/share',
            'abdm.opd.prescription.share.requested' => '/api/v1/abdm/health-information/share',
            'abdm.ipd.admission.share.requested' => '/api/v1/abdm/health-information/ipd-admission/share',
            'abdm.ipd.discharge.share.requested' => '/api/v1/abdm/health-information/ipd-discharge/share',
            'abdm.diagnosis.report.share.requested' => '/api/v1/abdm/health-information/diagnosis-report/share',
            'nhcx.claim.created' => '/api/v1/nhcx/claims/create',
            'nhcx.claim.status.requested' => '/api/v1/nhcx/claims/status',
        ];

        $jsonMap = trim($this->readSetting('EKA_EVENT_ENDPOINTS_JSON'));
        if ($jsonMap !== '') {
            $decoded = json_decode($jsonMap, true);
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    if (is_string($key) && is_string($value) && $key !== '' && $value !== '') {
                        $mapping[$key] = $value;
                    }
                }
            }
        }

        $path = trim((string) ($mapping[$eventType] ?? ''));
        if ($path === '') {
            return '';
        }

        return '/' . ltrim($path, '/');
    }
}
