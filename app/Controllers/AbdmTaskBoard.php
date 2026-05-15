<?php

namespace App\Controllers;

use App\Libraries\AbdmWorkTaskService;
use App\Libraries\BridgeSyncService;

class AbdmTaskBoard extends BaseController
{
    private AbdmWorkTaskService $taskService;

    public function __construct()
    {
        $this->taskService = new AbdmWorkTaskService();
    }

    public function index()
    {
        $this->backfillPatientAbhaTasks();
        $this->backfillLabRadiologyTasks();
        $tasks = $this->taskService->getOpenTasks(300);

        return view('abdm/task_board', [
            'tasks' => $tasks,
            'today_credit_opd_rows' => $this->getTodayCreditOpdConsultRows(),
        ]);
    }

    public function list()
    {
        $this->backfillPatientAbhaTasks();
        $this->backfillLabRadiologyTasks();
        return $this->response->setJSON([
            'ok' => 1,
            'tasks' => $this->taskService->getOpenTasks(300),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function markStatus()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $taskId = (int) $this->request->getPost('task_id');
        $status = trim((string) $this->request->getPost('status'));
        $note = trim((string) $this->request->getPost('note'));

        if ($taskId <= 0 || $status === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'task_id and status are required']);
        }

        $ok = $this->taskService->markTaskStatus($taskId, $status, $note);

        return $this->response->setJSON([
            'ok' => $ok ? 1 : 0,
            'task_id' => $taskId,
            'status' => $status,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function performAction()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $taskId = (int) $this->request->getPost('task_id');
        $action = trim((string) $this->request->getPost('action'));

        $task = $this->taskService->getTask($taskId);
        if ($task === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Task not found']);
        }

        $abhaId = trim((string) $this->request->getPost('abha_id'));
        if (! $this->isValidAbhaNumber($abhaId)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be a 14-digit number']);
        }

        $payload = [
            'task_id' => $taskId,
            'task_code' => (string) ($task['task_code'] ?? ''),
            'task_type' => (string) ($task['task_type'] ?? ''),
            'patient_id' => (int) ($task['patient_id'] ?? 0),
            'patient_name' => (string) ($task['patient_name'] ?? ''),
            'abha_id' => $abhaId,
            'entity_type' => (string) ($task['entity_type'] ?? ''),
            'entity_id' => (string) ($task['entity_id'] ?? ''),
            'opd_session_id' => (int) $this->request->getPost('opd_session_id'),
        ];

        $eventType = $this->resolveActionEventType($action, (string) ($task['task_type'] ?? ''));
        if ($eventType === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Unsupported action']);
        }

        $queueId = null;
        try {
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue($eventType, $payload, 'abdm_task', (string) $taskId);
        } catch (\Throwable $e) {
            $this->taskService->markTaskStatus($taskId, 'failed', $e->getMessage());
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Queue failure: ' . $e->getMessage()]);
        }

        $this->taskService->markTaskStatus($taskId, 'in_progress', 'Action queued: ' . $eventType);

        return $this->response->setJSON([
            'ok' => 1,
            'queue_id' => $queueId,
            'event_type' => $eventType,
            'task_id' => $taskId,
            'status' => 'in_progress',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function resolveActionEventType(string $action, string $taskType): string
    {
        $action = strtolower(trim($action));
        $taskType = strtolower(trim($taskType));

        if ($action === 'create_abha') {
            return 'abdm.abha.create.requested';
        }

        if ($action === 'update_abha') {
            return 'abdm.abha.update.requested';
        }

        if ($action === 'submit') {
            if ($taskType === 'opd_prescription_publish') {
                return 'abdm.opd.prescription.share.requested';
            }
            if ($taskType === 'ipd_admission_publish') {
                return 'abdm.ipd.admission.share.requested';
            }
            if ($taskType === 'ipd_discharge_publish') {
                return 'abdm.ipd.discharge.share.requested';
            }
            if ($taskType === 'lab_report_publish' || $taskType === 'radiology_report_publish') {
                return 'abdm.diagnosis.report.share.requested';
            }
        }

        return '';
    }

    private function isValidAbhaNumber(string $abhaId): bool
    {
        return preg_match('/^\d{14}$/', $abhaId) === 1;
    }

    private function backfillPatientAbhaTasks(): void
    {
        if (! $this->db->tableExists('abdm_work_tasks') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $abhaField = $this->resolvePatientAbhaField();
        if ($abhaField === null) {
            return;
        }

        $rows = $this->db->table('patient_master')
            ->select('id,p_fname,' . $abhaField)
            ->orderBy('id', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $patientId = (int) ($row['id'] ?? 0);
            if ($patientId <= 0) {
                continue;
            }

            $abha = trim((string) ($row[$abhaField] ?? ''));
            if (preg_match('/^\d{14}$/', $abha) === 1) {
                continue;
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->whereIn('task_type', ['patient_abha_create', 'patient_abha_link'])
                ->where('entity_type', 'patient')
                ->where('entity_id', (string) $patientId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get(1)
                ->getRowArray();

            if (! empty($exists)) {
                continue;
            }

            $this->taskService->createOrRefreshTask(
                'patient_abha_create',
                'patient_registration',
                'patient',
                (string) $patientId,
                $patientId,
                trim((string) ($row['p_fname'] ?? '')),
                $abha,
                'create_abha',
                ['trigger' => 'task_board.backfill']
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

    private function backfillLabRadiologyTasks(): void
    {
        if (! $this->db->tableExists('abdm_work_tasks') || ! $this->db->tableExists('lab_request') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaSelectParts = [];
        foreach (['abha_id', 'abha_no', 'abha_address', 'abha'] as $field) {
            if (in_array($field, $patientFields, true)) {
                $abhaSelectParts[] = 'p.' . $field;
            }
        }

        if (empty($abhaSelectParts)) {
            return;
        }

        $select = 'r.id, r.patient_id, r.patient_name, r.lab_type, r.charge_id, r.status, ' . implode(', ', $abhaSelectParts);

        $rows = $this->db->table('lab_request r')
            ->select($select)
            ->join('patient_master p', 'p.id = r.patient_id', 'left')
            ->where('r.status >=', 2)
            ->orderBy('r.id', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $labReqId = (int) ($row['id'] ?? 0);
            $patientId = (int) ($row['patient_id'] ?? 0);
            if ($labReqId <= 0 || $patientId <= 0) {
                continue;
            }

            $abha = trim((string) ($row['abha_id'] ?? $row['abha_no'] ?? $row['abha_address'] ?? $row['abha'] ?? ''));
            if (preg_match('/^\d{14}$/', $abha) !== 1) {
                continue;
            }

            $labType = (int) ($row['lab_type'] ?? 0);
            $taskType = in_array($labType, [1, 2, 3, 4, 6], true) ? 'radiology_report_publish' : 'lab_report_publish';

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', $taskType)
                ->where('entity_type', 'lab_request')
                ->where('entity_id', (string) $labReqId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $this->taskService->createOrRefreshTask(
                $taskType,
                'diagnosis',
                'lab_request',
                (string) $labReqId,
                $patientId,
                trim((string) ($row['patient_name'] ?? '')),
                $abha,
                'submit',
                [
                    'lab_type' => $labType,
                    'invoice_id' => (int) ($row['charge_id'] ?? 0),
                    'trigger' => 'task_board.backfill',
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTodayCreditOpdConsultRows(): array
    {
        if (! $this->db->tableExists('opd_master')) {
            return [];
        }

        $opdFields = $this->db->getFieldNames('opd_master') ?? [];
        $opdIdCol = $this->resolveExistingColumn($opdFields, ['opd_id']);
        $patientFkCol = $this->resolveExistingColumn($opdFields, ['p_id', 'patient_id']);
        $dateCol = $this->resolveExistingColumn($opdFields, ['apointment_date', 'appointment_date', 'created_at', 'entry_date']);
        if ($opdIdCol === null || $patientFkCol === null || $dateCol === null) {
            return [];
        }

        $patientNameCol = $this->resolveExistingColumn($opdFields, ['P_name', 'p_name', 'patient_name']);
        $creditCaseCol = $this->resolveExistingColumn($opdFields, ['insurance_case_id', 'organization_case_id']);
        $paymentModeCol = $this->resolveExistingColumn($opdFields, ['payment_mode']);

        $builder = $this->db->table('opd_master o')
            ->select('o.' . $opdIdCol . ' as opd_id', false)
            ->select('o.' . $patientFkCol . ' as patient_id', false)
            ->select('o.' . $dateCol . ' as consult_datetime', false)
            ->where('DATE(o.' . $dateCol . ') =', date('Y-m-d'), false)
            ->orderBy('o.' . $opdIdCol, 'DESC')
            ->limit(300);

        if ($patientNameCol !== null) {
            $builder->select('o.' . $patientNameCol . ' as opd_patient_name', false);
        } else {
            $builder->select("'' as opd_patient_name", false);
        }

        if ($creditCaseCol !== null) {
            $builder->where('COALESCE(o.' . $creditCaseCol . ',0) >', 0, false);
            $builder->select('o.' . $creditCaseCol . ' as credit_ref', false);
        } else {
            $builder->select('0 as credit_ref', false);
            if ($paymentModeCol !== null) {
                $builder->where('COALESCE(o.' . $paymentModeCol . ',0) >', 1, false);
            }
        }

        $patientFields = $this->db->tableExists('patient_master') ? ($this->db->getFieldNames('patient_master') ?? []) : [];
        $patientPkCol = $this->resolveExistingColumn($patientFields, ['id']);
        $patientNameJoinCol = $this->resolveExistingColumn($patientFields, ['p_fname', 'patient_name', 'name']);
        $abhaCol = $this->resolveExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);

        if ($patientPkCol !== null && $patientNameJoinCol !== null) {
            $builder->join('patient_master p', 'p.' . $patientPkCol . ' = o.' . $patientFkCol, 'left');
            $builder->select('p.' . $patientNameJoinCol . ' as patient_name', false);
            if ($abhaCol !== null) {
                $builder->select('p.' . $abhaCol . ' as patient_abha', false);
            } else {
                $builder->select("'' as patient_abha", false);
            }
        } else {
            $builder->select("'' as patient_name", false);
            $builder->select("'' as patient_abha", false);
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return [];
        }

        $opdIds = array_values(array_unique(array_map(static fn ($r) => (int) ($r['opd_id'] ?? 0), $rows)));
        $latestDocByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('opd_fhir_documents')) {
            $docRows = $this->db->table('opd_fhir_documents')
                ->select('id, opd_id, opd_session_id, generated_at')
                ->whereIn('opd_id', $opdIds)
                ->where('bundle_type', 'MedicationRequestBundle')
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($docRows as $doc) {
                $k = (int) ($doc['opd_id'] ?? 0);
                if ($k <= 0 || isset($latestDocByOpd[$k])) {
                    continue;
                }
                $latestDocByOpd[$k] = $doc;
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $opdId = (int) ($row['opd_id'] ?? 0);
            if ($opdId <= 0) {
                continue;
            }
            $doc = $latestDocByOpd[$opdId] ?? null;
            $sessionId = (int) ($doc['opd_session_id'] ?? 0);

            $previewUrl = $sessionId > 0
                ? base_url('Opd_prescription/fhir_bundle_preview/' . $opdId . '/' . $sessionId)
                : base_url('Opd_prescription/fhir_bundle_preview/' . $opdId);

            $out[] = [
                'opd_id' => $opdId,
                'patient_id' => (int) ($row['patient_id'] ?? 0),
                'patient_name' => trim((string) ($row['patient_name'] ?? $row['opd_patient_name'] ?? '')),
                'abha_id' => trim((string) ($row['patient_abha'] ?? '')),
                'consult_datetime' => (string) ($row['consult_datetime'] ?? ''),
                'credit_ref' => (string) ($row['credit_ref'] ?? ''),
                'opd_session_id' => $sessionId,
                'fhir_generated_at' => (string) ($doc['generated_at'] ?? ''),
                'has_fhir' => $doc !== null,
                'preview_url' => $previewUrl,
            ];
        }

        return $out;
    }

    private function resolveExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
