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

        $endpoint = $this->readSetting('BRIDGE_SYNC_URL');
        if ($endpoint === '') {
            $summary['message'] = 'BRIDGE_SYNC_URL is not configured';
            return $summary;
        }

        $token = $this->readSetting('BRIDGE_SYNC_TOKEN');
        $source = $this->readSetting('BRIDGE_SOURCE_CODE');
        if ($source === '') {
            $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');
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

            $postBody = [
                'queue_id' => $queueId,
                'source' => $source,
                'event_type' => (string) ($row['event_type'] ?? ''),
                'entity_type' => (string) ($row['entity_type'] ?? ''),
                'entity_id' => (string) ($row['entity_id'] ?? ''),
                'payload' => $payload,
                'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
            ];

            $headers = ['Content-Type' => 'application/json'];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            try {
                $response = $client->post($endpoint, [
                    'headers' => $headers,
                    'json' => $postBody,
                ]);

                $status = $response->getStatusCode();
                $body = (string) $response->getBody();

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

    private function readSetting(string $name): string
    {
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
}
