<?php

namespace App\Libraries\Abdm\Sync;

use App\Models\AbdmSyncOutboxModel;
use App\Models\AbdmSyncPatientModel;
use App\Models\AbdmSyncRecordModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class AbdmSyncOutboxService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DEAD = 'dead';

    private BaseConnection $db;
    private AbdmSyncPatientModel $patientModel;
    private AbdmSyncRecordModel $recordModel;
    private AbdmSyncOutboxModel $outboxModel;

    /** @var int[] */
    private array $retryScheduleSeconds = [60, 300, 900, 1800, 3600, 7200, 14400, 28800];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->patientModel = new AbdmSyncPatientModel($this->db);
        $this->recordModel = new AbdmSyncRecordModel($this->db);
        $this->outboxModel = new AbdmSyncOutboxModel($this->db);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function enqueuePatientSync(array $payload): ?int
    {
        if (! $this->requiredTablesPresent()) {
            return null;
        }

        $localPatientId = (int) ($payload['local_patient_id'] ?? 0);
        $hfrId = trim((string) ($payload['hfr_id'] ?? ''));
        if ($localPatientId <= 0 || $hfrId === '') {
            return null;
        }

        $sourceUpdatedAt = $this->normalizeSourceUpdatedAt((string) ($payload['source_updated_at'] ?? ''));
        $idempotencyKey = $this->buildPatientIdempotencyKey($hfrId, $localPatientId, $sourceUpdatedAt);

        $row = [
            'local_patient_id' => $localPatientId,
            'abha_id' => $this->normalizeAbhaDigits((string) ($payload['abha_id'] ?? '')),
            'abha_address' => trim((string) ($payload['abha_address'] ?? '')) ?: null,
            'name' => trim((string) ($payload['name'] ?? '')),
            'gender' => trim((string) ($payload['gender'] ?? '')) ?: null,
            'dob' => trim((string) ($payload['dob'] ?? '')) ?: null,
            'mobile' => trim((string) ($payload['mobile'] ?? '')) ?: null,
            'email' => trim((string) ($payload['email'] ?? '')) ?: null,
            'source_updated_at' => $sourceUpdatedAt,
            'sync_status' => self::STATUS_PENDING,
            'last_error' => null,
        ];

        $existing = $this->patientModel->where('local_patient_id', $localPatientId)->first();
        if ($existing) {
            $this->patientModel->update((int) $existing['id'], $row);
            $entityId = (string) $existing['id'];
        } else {
            $this->patientModel->insert($row);
            $entityId = (string) $this->patientModel->getInsertID();
        }

        return $this->upsertOutbox('patient', $entityId, $idempotencyKey, $payload);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function enqueueRecordSync(array $payload): ?int
    {
        if (! $this->requiredTablesPresent()) {
            return null;
        }

        $localRecordId = trim((string) ($payload['local_record_id'] ?? ''));
        $localPatientId = (int) ($payload['local_patient_id'] ?? 0);
        $hfrId = trim((string) ($payload['hfr_id'] ?? ''));
        $careContextReference = trim((string) ($payload['care_context_reference'] ?? ''));
        $hiType = trim((string) ($payload['hi_type'] ?? ''));
        if ($localRecordId === '' || $localPatientId <= 0 || $hfrId === '' || $careContextReference === '' || $hiType === '') {
            return null;
        }

        if (empty($payload['outbound_event'])) {
            $payload['outbound_event'] = 'care_context_link';
        }
        if (empty($payload['retry_schedule_seconds']) || ! is_array($payload['retry_schedule_seconds'])) {
            $payload['retry_schedule_seconds'] = [10, 30, 60];
        }

        $sourceUpdatedAt = $this->normalizeSourceUpdatedAt((string) ($payload['source_updated_at'] ?? ''));
        $idempotencyKey = $this->buildRecordIdempotencyKey($hfrId, $careContextReference, $sourceUpdatedAt);

        $row = [
            'local_record_id' => $localRecordId,
            'local_patient_id' => $localPatientId,
            'hi_type' => $hiType,
            'care_context_reference' => $careContextReference,
            'care_context_display' => trim((string) ($payload['care_context_display'] ?? '')) ?: null,
            'visit_date' => trim((string) ($payload['visit_date'] ?? '')) ?: null,
            'department' => trim((string) ($payload['department'] ?? '')) ?: null,
            'doctor_name' => trim((string) ($payload['doctor_name'] ?? '')) ?: null,
            'fhir_bundle_json' => (string) json_encode($payload['fhir_bundle'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'consent_id' => trim((string) ($payload['consent_id'] ?? '')) ?: null,
            'hfr_id' => $hfrId,
            'source_updated_at' => $sourceUpdatedAt,
            'sync_status' => self::STATUS_PENDING,
            'last_error' => null,
        ];

        $existing = $this->recordModel->where('local_record_id', $localRecordId)->first();
        if ($existing) {
            $this->recordModel->update((int) $existing['id'], $row);
            $entityId = (string) $existing['id'];
        } else {
            $this->recordModel->insert($row);
            $entityId = (string) $this->recordModel->getInsertID();
        }

        return $this->upsertOutbox('record', $entityId, $idempotencyKey, $payload);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchProcessableBatch(int $limit = 20): array
    {
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        return $this->db->table('abdm_sync_outbox')
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
            ->groupStart()
            ->where('next_retry_at IS NULL', null, false)
            ->orWhere('next_retry_at <=', $now)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();
    }

    public function tryLockRow(int $id, string $workerId): bool
    {
        $updated = $this->db->table('abdm_sync_outbox')
            ->where('id', $id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_FAILED])
            ->update([
                'status' => self::STATUS_IN_PROGRESS,
                'locked_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'worker_id' => $workerId,
            ]);

        return (bool) $updated;
    }

    public function markDone(int $id): void
    {
        $this->outboxModel->update($id, [
            'status' => self::STATUS_DONE,
            'next_retry_at' => null,
            'last_error' => null,
            'locked_at' => null,
            'worker_id' => null,
        ]);
    }

    public function markRetryOrDead(int $id, int $retryCount, string $error, ?array $retryScheduleSeconds = null): void
    {
        $next = $this->getNextRetryAt($retryCount, $retryScheduleSeconds);
        $status = $next === null ? self::STATUS_DEAD : self::STATUS_FAILED;

        $this->outboxModel->update($id, [
            'status' => $status,
            'retry_count' => $retryCount,
            'next_retry_at' => $next,
            'last_error' => mb_substr($error, 0, 1000),
            'locked_at' => null,
            'worker_id' => null,
        ]);
    }

    public function replayDeadRow(int $id): bool
    {
        $row = $this->outboxModel->find($id);
        if (! is_array($row)) {
            return false;
        }

        return (bool) $this->outboxModel->update($id, [
            'status' => self::STATUS_PENDING,
            'next_retry_at' => null,
            'last_error' => null,
            'locked_at' => null,
            'worker_id' => null,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function getCounters(): array
    {
        $rows = $this->db->table('abdm_sync_outbox')
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $counts = [
            'pending' => 0,
            'in_progress' => 0,
            'success' => 0,
            'failed' => 0,
            'dead' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            if ($status === self::STATUS_DONE) {
                $counts['success'] += $total;
            } elseif (array_key_exists($status, $counts)) {
                $counts[$status] += $total;
            }
        }

        $lastSuccess = $this->db->table('abdm_sync_outbox')
            ->select('updated_at')
            ->where('status', self::STATUS_DONE)
            ->orderBy('updated_at', 'DESC')
            ->get(1)
            ->getRowArray();

        $avgLatency = $this->db->query(
            'SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) AS avg_seconds FROM abdm_sync_outbox WHERE status = ?',
            [self::STATUS_DONE]
        )->getRowArray();

        $counts['avg_latency_seconds'] = (float) ($avgLatency['avg_seconds'] ?? 0.0);
        $counts['last_success_at'] = (string) ($lastSuccess['updated_at'] ?? '');

        return $counts;
    }

    public function buildPatientIdempotencyKey(string $hfrId, int $localPatientId, string $sourceUpdatedAt): string
    {
        $epoch = strtotime($sourceUpdatedAt) ?: time();
        return $hfrId . '|' . $localPatientId . '|' . $epoch;
    }

    public function buildRecordIdempotencyKey(string $hfrId, string $careContextReference, string $sourceUpdatedAt): string
    {
        $epoch = strtotime($sourceUpdatedAt) ?: time();
        return $hfrId . '|' . $careContextReference . '|' . $epoch;
    }

    public function getNextRetryAt(int $retryCount, ?array $retryScheduleSeconds = null): ?string
    {
        $schedule = $this->normalizeRetrySchedule($retryScheduleSeconds);

        if ($retryCount <= 0) {
            return Time::now('Asia/Kolkata')->toDateTimeString();
        }

        if ($retryCount > count($schedule)) {
            return null;
        }

        $delay = $schedule[$retryCount - 1];
        return date('Y-m-d H:i:s', time() + $delay);
    }

    private function requiredTablesPresent(): bool
    {
        return $this->db->tableExists('abdm_sync_patient')
            && $this->db->tableExists('abdm_sync_record')
            && $this->db->tableExists('abdm_sync_outbox');
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function upsertOutbox(string $entityType, string $entityId, string $idempotencyKey, array $payload): ?int
    {
        $payloadJson = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payloadJson === '' || $payloadJson === 'null') {
            return null;
        }

        $existing = $this->outboxModel->where('idempotency_key', $idempotencyKey)->first();
        $row = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'idempotency_key' => $idempotencyKey,
            'payload_json' => $payloadJson,
            'status' => self::STATUS_PENDING,
            'next_retry_at' => null,
            'last_error' => null,
            'locked_at' => null,
            'worker_id' => null,
        ];

        if ($existing) {
            $this->outboxModel->update((int) $existing['id'], $row);
            return (int) $existing['id'];
        }

        $this->outboxModel->insert($row);
        $id = $this->outboxModel->getInsertID();
        return $id > 0 ? (int) $id : null;
    }

    private function normalizeSourceUpdatedAt(string $sourceUpdatedAt): string
    {
        $ts = strtotime($sourceUpdatedAt);
        if ($ts === false) {
            $ts = time();
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function normalizeAbhaDigits(string $abha): string
    {
        $digits = preg_replace('/\D/', '', $abha);
        return is_string($digits) ? $digits : '';
    }

    /**
     * @param array<int, mixed>|null $retryScheduleSeconds
     * @return int[]
     */
    private function normalizeRetrySchedule(?array $retryScheduleSeconds): array
    {
        $schedule = [];
        if (is_array($retryScheduleSeconds)) {
            foreach ($retryScheduleSeconds as $seconds) {
                $value = (int) $seconds;
                if ($value > 0) {
                    $schedule[] = $value;
                }
            }
        }

        if (empty($schedule)) {
            return $this->retryScheduleSeconds;
        }

        return array_values($schedule);
    }
}
