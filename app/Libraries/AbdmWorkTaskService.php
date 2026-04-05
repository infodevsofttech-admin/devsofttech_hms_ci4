<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class AbdmWorkTaskService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function createOrRefreshTask(
        string $taskType,
        string $sourceModule,
        string $entityType,
        string $entityId,
        int $patientId,
        string $patientName,
        string $abhaId,
        string $actionMode,
        array $meta = []
    ): ?int {
        if (! $this->db->tableExists('abdm_work_tasks')) {
            return null;
        }

        $entityId = trim($entityId);
        if ($taskType === '' || $entityType === '' || $entityId === '') {
            return null;
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $payload = [
            'task_type' => $taskType,
            'source_module' => $sourceModule,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'patient_id' => $patientId,
            'patient_name' => $patientName,
            'abha_id' => $abhaId,
            'action_mode' => $actionMode,
            'meta' => $meta,
        ];

        $existing = $this->db->table('abdm_work_tasks')
            ->where('task_type', $taskType)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $row = [
            'source_module' => $sourceModule,
            'patient_id' => $patientId > 0 ? $patientId : null,
            'patient_name' => $patientName !== '' ? $patientName : null,
            'abha_id' => $abhaId !== '' ? $abhaId : null,
            'action_mode' => $actionMode !== '' ? $actionMode : null,
            'payload_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ];

        if (! empty($existing)) {
            $this->db->table('abdm_work_tasks')->where('id', (int) $existing['id'])->update($row);
            return (int) $existing['id'];
        }

        $row['task_code'] = $this->generateTaskCode();
        $row['task_type'] = $taskType;
        $row['entity_type'] = $entityType;
        $row['entity_id'] = $entityId;
        $row['status'] = 'pending';
        $row['priority'] = 'normal';
        $row['created_at'] = $now;

        $this->db->table('abdm_work_tasks')->insert($row);
        $insertId = (int) $this->db->insertID();

        return $insertId > 0 ? $insertId : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOpenTasks(int $limit = 200): array
    {
        if (! $this->db->tableExists('abdm_work_tasks')) {
            return [];
        }

        $this->syncPatientAbhaBacklog(max(200, $limit));

        return $this->db->table('abdm_work_tasks')
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();
    }

    public function markTaskStatus(int $taskId, string $status, string $resultText = ''): bool
    {
        if (! $this->db->tableExists('abdm_work_tasks') || $taskId <= 0) {
            return false;
        }

        $status = strtolower(trim($status));
        if (! in_array($status, ['pending', 'in_progress', 'completed', 'failed', 'cancelled'], true)) {
            $status = 'pending';
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $row = [
            'status' => $status,
            'last_action_result' => $resultText !== '' ? mb_substr($resultText, 0, 1000) : null,
            'updated_at' => $now,
            'completed_at' => in_array($status, ['completed', 'cancelled'], true) ? $now : null,
        ];

        return (bool) $this->db->table('abdm_work_tasks')->where('id', $taskId)->update($row);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTask(int $taskId): ?array
    {
        if (! $this->db->tableExists('abdm_work_tasks') || $taskId <= 0) {
            return null;
        }

        $row = $this->db->table('abdm_work_tasks')->where('id', $taskId)->get(1)->getRowArray();
        return ! empty($row) ? $row : null;
    }

    private function generateTaskCode(): string
    {
        return 'ABDM-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private function syncPatientAbhaBacklog(int $limit): void
    {
        if (! $this->db->tableExists('patient_master')) {
            return;
        }

        $abhaField = $this->resolvePatientAbhaField();
        if ($abhaField === null) {
            return;
        }

        $select = 'id, p_fname';
        if ($abhaField !== '') {
            $select .= ', ' . $abhaField;
        }

        $rows = $this->db->table('patient_master')
            ->select($select)
            ->orderBy('id', 'DESC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $patientId = (int) ($row['id'] ?? 0);
            if ($patientId <= 0) {
                continue;
            }

            $abhaId = trim((string) ($row[$abhaField] ?? ''));
            if (preg_match('/^\d{14}$/', $abhaId) === 1) {
                continue;
            }

            $this->createOrRefreshTask(
                'patient_abha_create',
                'patient_registration',
                'patient',
                (string) $patientId,
                $patientId,
                trim((string) ($row['p_fname'] ?? '')),
                $abhaId,
                'create_abha',
                [
                    'trigger' => 'patient.backlog.sync',
                ]
            );
        }
    }

    private function resolvePatientAbhaField(): ?string
    {
        if (! $this->db->tableExists('patient_master')) {
            return null;
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        foreach (['abha_id', 'abha_no', 'abha'] as $field) {
            if (in_array($field, $fields, true)) {
                return $field;
            }
        }

        if (in_array('abha_address', $fields, true)) {
            return 'abha_address';
        }

        return null;
    }
}
