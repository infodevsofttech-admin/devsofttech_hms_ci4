<?php

namespace App\Libraries\Abdm\Sync;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Models\AbdmSyncOutboxModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class AbdmSmsNotifyService
{
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE        = 'done';
    public const STATUS_FAILED      = 'failed';
    public const STATUS_DEAD        = 'dead';

    private BaseConnection $db;
    private AbdmSyncOutboxModel $outboxModel;

    /** @var int[] Backoff schedule in seconds: 1m, 5m, 15m, 30m, 1h, 2h, 4h, 8h */
    private array $retryScheduleSeconds = [60, 300, 900, 1800, 3600, 7200, 14400, 28800];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->outboxModel = new AbdmSyncOutboxModel($this->db);
    }

    /**
     * Enqueue a deep-link SMS notification for a patient.
     * Takes < 1ms and operates strictly in local DB, so internet downtime never blocks caller.
     *
     * @param int    $patientId   patient_master ID
     * @param string $phoneNumber 10-digit mobile number
     * @param string $hipName     Optional facility display name
     * @param string $trigger     Trigger source event, e.g. 'patient.created'
     */
    public function enqueue(
        int $patientId,
        string $phoneNumber,
        string $hipName = '',
        string $trigger = 'patient.created'
    ): ?int {
        if (! $this->db->tableExists('abdm_sync_outbox')) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $phoneNumber);
        if (strlen($phone) < 10) {
            return null;
        }
        $phone = substr($phone, -10);

        if ($hipName === '' && $this->db->tableExists('hospital_setting')) {
            $hs = $this->db->table('hospital_setting')->where('s_name', 'H_name')->get(1)->getRowArray();
            $hipName = trim((string) ($hs['s_value'] ?? ''));
        }

        // Idempotency key per patient & phone prevents duplicate spam on same day
        $idempotencyKey = 'sms_notify:' . $patientId . ':' . $phone . ':' . date('Ymd');

        $payload = [
            'patient_id'   => $patientId,
            'phone_number' => $phone,
            'hip_name'     => $hipName,
            'trigger'      => $trigger,
            'enqueued_at'  => Time::now('Asia/Kolkata')->toDateTimeString(),
        ];

        $payloadJson = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === '' || $payloadJson === 'null') {
            return null;
        }

        $existing = $this->outboxModel->where('idempotency_key', $idempotencyKey)->first();

        // If already completed today, don't re-queue
        if ($existing && ($existing['status'] ?? '') === self::STATUS_DONE) {
            return (int) $existing['id'];
        }

        $row = [
            'entity_type'     => 'sms_notify',
            'entity_id'       => (string) $patientId,
            'idempotency_key' => $idempotencyKey,
            'payload_json'    => $payloadJson,
            'status'          => self::STATUS_PENDING,
            'next_retry_at'   => null,
            'last_error'      => null,
            'locked_at'       => null,
            'worker_id'       => null,
        ];

        if ($existing) {
            $this->outboxModel->update((int) $existing['id'], $row);
            return (int) $existing['id'];
        }

        $this->outboxModel->insert($row);
        $insertId = $this->outboxModel->getInsertID();

        return $insertId > 0 ? (int) $insertId : null;
    }

    /**
     * Process pending and retryable SMS notify tasks from the outbox.
     * Gracefully catches network/internet issues and sets exponential backoff.
     *
     * @param int    $limit    Max records to process per run
     * @param string $workerId Identifier of the worker process
     * @param bool   $force    If true, ignores next_retry_at delay
     * @return array<string, mixed>
     */
    public function processBatch(int $limit = 20, string $workerId = 'cron-abdm-sms', bool $force = false): array
    {
        $summary = [
            'processed' => 0,
            'sent'      => 0,
            'failed'    => 0,
            'dead'      => 0,
            'skipped'   => 0,
        ];

        if (! $this->db->tableExists('abdm_sync_outbox')) {
            return $summary;
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        $builder = $this->db->table('abdm_sync_outbox')
            ->where('entity_type', 'sms_notify')
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED]);

        if (! $force) {
            $builder->groupStart()
                ->where('next_retry_at IS NULL', null, false)
                ->orWhere('next_retry_at <=', $now)
                ->groupEnd();
        }

        $rows = $builder->orderBy('id', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $summary;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                $summary['skipped']++;
                continue;
            }

            // Atomic optimistic locking
            $locked = $this->db->table('abdm_sync_outbox')
                ->where('id', $id)
                ->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
                ->update([
                    'status'    => self::STATUS_IN_PROGRESS,
                    'locked_at' => $now,
                    'worker_id' => $workerId,
                ]);

            if (! $locked) {
                $summary['skipped']++;
                continue;
            }

            $summary['processed']++;

            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }

            $patientId = (int) ($payload['patient_id'] ?? $row['entity_id'] ?? 0);
            $phone = preg_replace('/\D/', '', (string) ($payload['phone_number'] ?? ''));
            if (strlen($phone) < 10) {
                $this->outboxModel->update($id, [
                    'status'     => self::STATUS_DEAD,
                    'last_error' => 'Invalid phone number in payload: ' . ($payload['phone_number'] ?? ''),
                    'locked_at'  => null,
                    'worker_id'  => null,
                ]);
                $summary['dead']++;
                continue;
            }
            $phone = substr($phone, -10);

            $hipName = trim((string) ($payload['hip_name'] ?? ''));
            if ($hipName === '' && $this->db->tableExists('hospital_setting')) {
                $hs = $this->db->table('hospital_setting')->where('s_name', 'H_name')->get(1)->getRowArray();
                $hipName = trim((string) ($hs['s_value'] ?? ''));
            }

            $result = $this->dispatchSmsNotify($phone, $hipName, $patientId);

            if ((bool) ($result['ok'] ?? false)) {
                $this->outboxModel->update($id, [
                    'status'        => self::STATUS_DONE,
                    'next_retry_at' => null,
                    'last_error'    => null,
                    'locked_at'     => null,
                    'worker_id'     => null,
                ]);

                // Also update corresponding abdm_work_tasks if present
                if ($patientId > 0 && $this->db->tableExists('abdm_work_tasks')) {
                    $reqId = trim((string) ($result['request_id'] ?? ''));
                    $workMsg = 'Deep-link SMS dispatched to ' . $phone . ($reqId !== '' ? ' (Req: ' . $reqId . ')' : '');
                    $this->db->table('abdm_work_tasks')
                        ->where('patient_id', $patientId)
                        ->where('task_type', 'patient_abha_create')
                        ->update([
                            'last_action_result' => $workMsg,
                            'updated_at'         => Time::now('Asia/Kolkata')->toDateTimeString(),
                        ]);
                }

                $summary['sent']++;
                continue;
            }

            // Failure handling — distinguish retryable vs dead
            $retryCount = ((int) ($row['retry_count'] ?? 0)) + 1;
            $isRetryable = (bool) ($result['retryable'] ?? true);
            $errMsg = (string) ($result['message'] ?? 'SMS dispatch failed');

            if (! $isRetryable || $retryCount > count($this->retryScheduleSeconds)) {
                $this->outboxModel->update($id, [
                    'status'      => self::STATUS_DEAD,
                    'retry_count' => $retryCount,
                    'last_error'  => mb_substr($errMsg, 0, 1000),
                    'locked_at'   => null,
                    'worker_id'   => null,
                ]);
                $summary['dead']++;
            } else {
                $delaySec = $this->retryScheduleSeconds[$retryCount - 1] ?? 1800;
                $nextRetryAt = date('Y-m-d H:i:s', time() + $delaySec);

                $this->outboxModel->update($id, [
                    'status'        => self::STATUS_FAILED,
                    'retry_count'   => $retryCount,
                    'next_retry_at' => $nextRetryAt,
                    'last_error'    => mb_substr($errMsg, 0, 1000),
                    'locked_at'     => null,
                    'worker_id'     => null,
                ]);
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * Dispatch single SMS notify via configured connector.
     *
     * @return array{ok: bool, retryable: bool, message: string, request_id?: string}
     */
    public function dispatchSmsNotify(string $phone, string $hipName, int $patientId = 0): array
    {
        try {
            $connector = AbdmConnectorFactory::make();
            $response = $connector->hipSmsNotify([
                'phone_number' => $phone,
                'hip_name'     => $hipName,
            ]);

            $ok = (int) ($response['ok'] ?? 0);
            $httpCode = (int) ($response['http_code'] ?? 0);

            if ($ok === 1 || ($httpCode >= 200 && $httpCode < 300)) {
                return [
                    'ok'         => true,
                    'retryable'  => false,
                    'request_id' => (string) ($response['request_id'] ?? ''),
                    'message'    => (string) ($response['message'] ?? 'SMS notification accepted by ABDM Gateway.'),
                ];
            }

            $errMsg = (string) ($response['error_text'] ?? $response['error'] ?? $response['message'] ?? 'ABDM SMS notification failed');
            // 400 Bad Request is permanent; 500, 502, 503, 504, 0 (network down) are retryable
            $isRetryable = ! in_array($httpCode, [400, 404, 422], true);

            return [
                'ok'        => false,
                'retryable' => $isRetryable,
                'message'   => 'ABDM Error: ' . $errMsg . ' (HTTP ' . $httpCode . ')',
            ];
        } catch (\Throwable $e) {
            return [
                'ok'        => false,
                'retryable' => true,
                'message'   => 'Network/Connection error (will retry): ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Scan recent patients registered without ABHA and queue them if not already present.
     */
    public function backfillUnnotifiedPatients(int $limit = 50, int $daysBack = 7): int
    {
        if (! $this->db->tableExists('patient_master') || ! $this->db->tableExists('abdm_sync_outbox')) {
            return 0;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime("-{$daysBack} days"));

        $builder = $this->db->table('patient_master')
            ->select('id, mphone1, p_fname')
            ->where('insert_date >=', $cutoff)
            ->groupStart()
                ->where('abha_id IS NULL', null, false)
                ->orWhere('abha_id', '')
            ->groupEnd()
            ->groupStart()
                ->where('abha_address IS NULL', null, false)
                ->orWhere('abha_address', '')
            ->groupEnd()
            ->where('mphone1 IS NOT NULL', null, false)
            ->where('mphone1 !=', '')
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit));

        $patients = $builder->get()->getResultArray();
        $enqueued = 0;

        foreach ($patients as $p) {
            $pid = (int) ($p['id'] ?? 0);
            $phone = preg_replace('/\D/', '', (string) ($p['mphone1'] ?? ''));
            if ($pid <= 0 || strlen($phone) < 10) {
                continue;
            }

            // Check if already in outbox
            $existing = $this->outboxModel
                ->where('entity_type', 'sms_notify')
                ->where('entity_id', (string) $pid)
                ->first();

            if ($existing) {
                continue;
            }

            $id = $this->enqueue($pid, substr($phone, -10), '', 'backfill');
            if ($id !== null) {
                $enqueued++;
            }
        }

        return $enqueued;
    }

    /**
     * Get queue statistics for monitoring.
     *
     * @return array<string, int>
     */
    public function getCounters(): array
    {
        $counts = [
            'pending'     => 0,
            'in_progress' => 0,
            'done'        => 0,
            'failed'      => 0,
            'dead'        => 0,
        ];

        if (! $this->db->tableExists('abdm_sync_outbox')) {
            return $counts;
        }

        $rows = $this->db->table('abdm_sync_outbox')
            ->select('status, COUNT(*) as total')
            ->where('entity_type', 'sms_notify')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            if (array_key_exists($status, $counts)) {
                $counts[$status] += $total;
            }
        }

        return $counts;
    }
}
