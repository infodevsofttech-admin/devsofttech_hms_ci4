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
        $this->backfillImmunizationTasks();
        $this->backfillHealthDocumentTasks();
        $tasks = $this->enrichImmunizationPushState($this->taskService->getOpenTasks(300));

        $dateFrom = trim((string) ($this->request->getGet('date_from') ?? date('Y-m-d')));
        $dateTo = trim((string) ($this->request->getGet('date_to') ?? date('Y-m-d')));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = date('Y-m-d');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = date('Y-m-d');
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return view('abdm/task_board', [
            'tasks'                  => $tasks,
            'dashboard_metrics'      => $this->getDashboardMetrics($dateFrom, $dateTo),
            'dashboard_date_from'    => $dateFrom,
            'dashboard_date_to'      => $dateTo,
            'today_credit_opd_rows'  => $this->getTodayCreditOpdConsultRows(),
            'opd_book_rows'          => $this->getOpdBookRows(),
            'opd_consult_rows'       => $this->getOpdConsultPublishRows(),
            'invoice_rows'           => $this->getInvoiceRows(),
        ]);
    }

    public function list()
    {
        $this->backfillPatientAbhaTasks();
        $this->backfillLabRadiologyTasks();
        $this->backfillImmunizationTasks();
        $this->backfillHealthDocumentTasks();
        return $this->response->setJSON([
            'ok' => 1,
            'tasks' => $this->enrichImmunizationPushState($this->taskService->getOpenTasks(300)),
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

    /** @param array<int,array<string,mixed>> $tasks */
    private function enrichImmunizationPushState(array $tasks): array
    {
        if ($tasks === [] || ! $this->db->tableExists('health_records')) {
            return $tasks;
        }

        $entityIds = [];
        foreach ($tasks as $task) {
            if (($task['task_type'] ?? '') === 'immunization_record_publish') {
                $entityIds[] = (string) ($task['entity_id'] ?? '');
            }
        }
        $entityIds = array_values(array_filter(array_unique($entityIds), static fn (string $id): bool => $id !== ''));
        if ($entityIds === []) {
            return $tasks;
        }

        $latest = [];
        $rows = $this->db->table('health_records')
            ->select('id, entity_id, push_status, care_context_reference, linked_at')
            ->where('hi_type', 'ImmunizationRecord')
            ->where('entity_type', 'immunization')
            ->whereIn('entity_id', $entityIds)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();
        foreach ($rows as $row) {
            $entityId = (string) ($row['entity_id'] ?? '');
            if ($entityId !== '' && ! isset($latest[$entityId])) {
                $latest[$entityId] = $row;
            }
        }

        foreach ($tasks as &$task) {
            if (($task['task_type'] ?? '') !== 'immunization_record_publish') {
                continue;
            }
            $healthRecord = $latest[(string) ($task['entity_id'] ?? '')] ?? null;
            $pushStatus = strtolower(trim((string) ($healthRecord['push_status'] ?? '')));
            $task['bridge_health_record_id'] = (int) ($healthRecord['id'] ?? 0);
            $task['bridge_push_status'] = $pushStatus;
            $task['bridge_care_context_reference'] = trim((string) ($healthRecord['care_context_reference'] ?? ''));
            $task['bridge_submitted'] = in_array($pushStatus, ['queued', 'pushed', 'linked'], true) ? 1 : 0;
        }
        unset($task);

        return $tasks;
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
            if ($taskType === 'health_document_publish') {
                return 'abdm.health_document.share.requested';
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

    private function backfillImmunizationTasks(): void
    {
        if (! $this->db->tableExists('abdm_work_tasks') || ! $this->db->tableExists('immunization_records') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaField === null) {
            return;
        }

        $rows = $this->db->table('immunization_records r')
            ->select('r.id, r.patient_id, r.vaccine_name, r.given_date, p.p_fname AS patient_name, p.' . $abhaField . ' AS abha_id', false)
            ->join('patient_master p', 'p.id = r.patient_id', 'left')
            ->where('r.status', 'completed')
            ->where('p.' . $abhaField . ' !=', '')
            ->orderBy('r.id', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $recordId = (int) ($row['id'] ?? 0);
            $patientId = (int) ($row['patient_id'] ?? 0);
            $abhaId = trim((string) ($row['abha_id'] ?? ''));
            if ($recordId <= 0 || $patientId <= 0 || $abhaId === '') {
                continue;
            }

            $linked = $this->db->tableExists('health_records') && ! empty($this->db->table('health_records')
                ->select('id')
                ->where('hi_type', 'ImmunizationRecord')
                ->where('entity_type', 'immunization')
                ->where('entity_id', (string) $recordId)
                ->where('push_status', 'linked')
                ->get(1)
                ->getRowArray());
            if ($linked) {
                continue;
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', 'immunization_record_publish')
                ->where('entity_type', 'immunization')
                ->where('entity_id', (string) $recordId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $this->taskService->createOrRefreshTask(
                'immunization_record_publish',
                'immunization',
                'immunization',
                (string) $recordId,
                $patientId,
                trim((string) ($row['patient_name'] ?? '')),
                $abhaId,
                'register_m2',
                [
                    'record_id' => $recordId,
                    'vaccine_name' => (string) ($row['vaccine_name'] ?? ''),
                    'given_date' => (string) ($row['given_date'] ?? ''),
                    'hi_type' => 'ImmunizationRecord',
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
                ->whereIn('bundle_type', ['OPConsultRecord', 'MedicationRequestBundle', 'PrescriptionRecord'])
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getInvoiceRows(): array
    {
        $rows = [];

        if ($this->db->tableExists('opd_master')) {
            foreach ($this->db->table('opd_master')
                ->select('opd_id AS bill_id, opd_code AS bill_code, p_id AS patient_id, P_name AS patient_name, apointment_date AS bill_date, opd_fee_amount AS amount')
                ->orderBy('opd_id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() as $row) {
                $rows[] = array_merge($row, ['source' => 'OPD', 'source_key' => 'opd_invoice']);
            }
        }

        if ($this->db->tableExists('invoice_master')) {
            foreach ($this->db->table('invoice_master i')
                ->select('i.id AS bill_id, i.invoice_code AS bill_code, i.attach_id AS patient_id, COALESCE(NULLIF(i.inv_name, ""), p.p_fname) AS patient_name, i.inv_date AS bill_date, i.net_amount AS amount')
                ->join('patient_master p', 'p.id = i.attach_id AND i.attach_type = 0', 'left')
                ->orderBy('i.id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() as $row) {
                $rows[] = array_merge($row, ['source' => 'Charges', 'source_key' => 'charges_invoice']);
            }
        }

        if ($this->db->tableExists('ipd_master')) {
            foreach ($this->db->table('ipd_master i')
                ->select("i.id AS bill_id, i.ipd_code AS bill_code, i.p_id AS patient_id, COALESCE(NULLIF(NULLIF(TRIM(i.P_name), ''), '0'), TRIM(CONCAT_WS(' ', p.p_fname, p.p_lname))) AS patient_name, COALESCE(i.discharge_date, i.register_date) AS bill_date, i.net_amount AS amount", false)
                ->join('patient_master p', 'p.id = i.p_id', 'left')
                ->orderBy('i.id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() as $row) {
                $rows[] = array_merge($row, ['source' => 'IPD Billing', 'source_key' => 'ipd_invoice']);
            }
        }

        $healthRecords = [];
        if ($this->db->tableExists('health_records')) {
            $hrFields = $this->db->getFieldNames('health_records') ?? [];
            $select = ['id', 'entity_type', 'entity_id', 'push_status', 'abdm_txn_id', 'care_context_reference', 'push_at', 'linked_at'];
            if (in_array('bridge_record_id', $hrFields, true)) {
                $select[] = 'bridge_record_id';
            }

            $hrRows = $this->db->table('health_records')
                ->select(implode(',', $select))
                ->where('hi_type', 'InvoiceRecord')
                ->whereIn('entity_type', ['invoice', 'charges_invoice', 'opd_invoice', 'ipd_invoice'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($hrRows as $hr) {
                $key = (string) ($hr['entity_type'] ?? '') . ':' . (string) ($hr['entity_id'] ?? '');
                if ($key !== ':' && ! isset($healthRecords[$key])) {
                    $healthRecords[$key] = $hr;
                }
            }
        }

        $recordLinks = [];
        $healthRecordIds = array_values(array_filter(array_map(
            static fn (array $hr): int => (int) ($hr['id'] ?? 0),
            $healthRecords
        )));
        if ($healthRecordIds !== [] && $this->db->tableExists('record_links')) {
            $linkRows = $this->db->table('record_links')
                ->select('health_record_id, link_status, linked_at')
                ->whereIn('health_record_id', $healthRecordIds)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();
            foreach ($linkRows as $link) {
                $healthRecordId = (int) ($link['health_record_id'] ?? 0);
                if ($healthRecordId > 0 && ! isset($recordLinks[$healthRecordId])) {
                    $recordLinks[$healthRecordId] = $link;
                }
            }
        }

        foreach ($rows as &$row) {
            $billId = (string) ($row['bill_id'] ?? '');
            $keys = [(string) $row['source_key'] . ':' . $billId];
            if ($row['source_key'] === 'charges_invoice') {
                $keys[] = 'invoice:' . $billId;
            }

            $hr = null;
            foreach ($keys as $key) {
                if (isset($healthRecords[$key])) {
                    $hr = $healthRecords[$key];
                    break;
                }
            }

            $pushStatus = strtolower(trim((string) ($hr['push_status'] ?? '')));
            $link = $hr !== null ? ($recordLinks[(int) ($hr['id'] ?? 0)] ?? null) : null;
            $linkStatus = strtolower(trim((string) ($link['link_status'] ?? '')));
            $statusLabel = 'Not Pushed';
            $statusTone = 'secondary';

            if ($pushStatus === 'queued') {
                $statusLabel = 'Submitted';
                $statusTone = 'warning';
            } elseif ($pushStatus === 'pushed') {
                $statusLabel = 'Pushed';
                $statusTone = 'primary';
            } elseif ($pushStatus === 'linked') {
                $statusLabel = 'Linked';
                $statusTone = 'success';
            } elseif ($pushStatus === 'failed') {
                $statusLabel = 'Failed';
                $statusTone = 'danger';
            } elseif ($pushStatus !== '') {
                $statusLabel = ucwords(str_replace('_', ' ', $pushStatus));
                $statusTone = 'info';
            }

            if ($linkStatus === 'linked') {
                $statusLabel = 'Linked';
                $statusTone = 'success';
            } elseif ($linkStatus === 'failed') {
                $statusLabel = 'Link Failed';
                $statusTone = 'danger';
            }

            $row['record_status_label'] = $statusLabel;
            $row['record_status_tone'] = $statusTone;
            $row['push_status'] = $pushStatus;
            $row['link_status'] = $linkStatus;
            $row['queue_id'] = trim((string) ($hr['abdm_txn_id'] ?? ''));
            $row['bridge_record_id'] = (int) ($hr['bridge_record_id'] ?? 0);
            $row['care_context_reference'] = trim((string) ($hr['care_context_reference'] ?? ''));
        }
        unset($row);

        usort($rows, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($right['bill_date'] ?? ''), (string) ($left['bill_date'] ?? ''));
            return $dateCompare !== 0 ? $dateCompare : ((int) ($right['bill_id'] ?? 0) <=> (int) ($left['bill_id'] ?? 0));
        });

        return array_slice($rows, 0, 300);
    }

    /**
     * @return array<string, int|string>
     */
    private function getDashboardMetrics(string $dateFrom, string $dateTo): array
    {
        $metrics = [
            'total_patients' => 0,
            'without_abha' => 0,
            'abha_verified' => 0,
            'verified_in_range' => 0,
            'records_pushed' => 0,
            'records_pushed_in_range' => 0,
            'opd_tokens' => 0,
            'opd_tokens_in_range' => 0,
            'verification_date_source' => '',
        ];

        if ($this->db->tableExists('patient_master')) {
            $fields = $this->db->getFieldNames('patient_master') ?? [];
            $abhaCol = $this->resolveExistingColumn($fields, ['abha_id', 'abha_no', 'abha']);
            $verifiedCol = $this->resolveExistingColumn($fields, ['abha_verified_status']);
            $verifiedDateCol = $this->resolveExistingColumn($fields, ['abha_verified_at', 'abdm_linked_at', 'last_update']);

            $metrics['total_patients'] = $this->db->table('patient_master')->countAllResults();
            if ($abhaCol !== null) {
                $validAbhaSql = $abhaCol . " REGEXP '^[0-9]{14}$'";
                $metrics['without_abha'] = $this->db->table('patient_master')
                    ->where("COALESCE(" . $abhaCol . ", '') NOT REGEXP '^[0-9]{14}$'", null, false)
                    ->countAllResults();

                $verifiedBuilder = $this->db->table('patient_master')->where($validAbhaSql, null, false);
                if ($verifiedCol !== null) {
                    $verifiedBuilder->where($verifiedCol, 'VERIFIED');
                }
                $metrics['abha_verified'] = $verifiedBuilder->countAllResults();

                if ($verifiedDateCol !== null) {
                    $rangeBuilder = $this->db->table('patient_master')
                        ->where($validAbhaSql, null, false)
                        ->where($verifiedDateCol . ' >=', $dateFrom . ' 00:00:00')
                        ->where($verifiedDateCol . ' <=', $dateTo . ' 23:59:59');
                    if ($verifiedCol !== null) {
                        $rangeBuilder->where($verifiedCol, 'VERIFIED');
                    }
                    $metrics['verified_in_range'] = $rangeBuilder->countAllResults();
                    $metrics['verification_date_source'] = $verifiedDateCol;
                }
            } else {
                $metrics['without_abha'] = $metrics['total_patients'];
            }
        }

        if ($this->db->tableExists('health_records')) {
            $fields = $this->db->getFieldNames('health_records') ?? [];
            $dateCol = $this->resolveExistingColumn($fields, ['push_at', 'linked_at', 'updated_at', 'created_at']);
            $pushedStatuses = ['queued', 'pushed', 'linked'];
            $metrics['records_pushed'] = $this->db->table('health_records')
                ->whereIn('push_status', $pushedStatuses)
                ->countAllResults();
            if ($dateCol !== null) {
                $metrics['records_pushed_in_range'] = $this->db->table('health_records')
                    ->whereIn('push_status', $pushedStatuses)
                    ->where($dateCol . ' >=', $dateFrom . ' 00:00:00')
                    ->where($dateCol . ' <=', $dateTo . ' 23:59:59')
                    ->countAllResults();
            }
        }

        if ($this->db->tableExists('abdm_opd_tokens')) {
            $metrics['opd_tokens'] = $this->db->table('abdm_opd_tokens')->countAllResults();
            $metrics['opd_tokens_in_range'] = $this->db->table('abdm_opd_tokens')
                ->where('queue_date >=', $dateFrom)
                ->where('queue_date <=', $dateTo)
                ->countAllResults();
        }

        return $metrics;
    }

    /**
     * OPD Book rows: locally synced ABDM OPD tokens only.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getOpdBookRows(): array
    {
        if (! $this->db->tableExists('abdm_opd_tokens')) {
            return [];
        }

        return $this->db->table('abdm_opd_tokens')
            ->where('queue_date >=', date('Y-m-d', strtotime('-7 days')))
            ->orderBy('queue_date', 'DESC')
            ->orderBy('gateway_token_id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();
    }

    /**
     * OPD Consult Publish rows: done OPD (opd_status=2) with ABHA, last 30 days.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getOpdConsultPublishRows(): array
    {
        if (! $this->db->tableExists('opd_master') || ! $this->db->tableExists('patient_master')) {
            return [];
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaCol = $this->resolveExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaCol === null) {
            return [];
        }

        $rows = $this->db->table('opd_master o')
            ->select('o.opd_id, o.p_id, o.P_name, o.apointment_date, o.opd_status, o.doc_name, p.' . $abhaCol . ' as abha_id', false)
            ->join('patient_master p', 'p.id = o.p_id', 'left')
            ->where('o.opd_status', 2)
            ->where('DATE(o.apointment_date) >=', date('Y-m-d', strtotime('-30 days')), false)
            ->where('p.' . $abhaCol . ' !=', '')
            ->orderBy('o.opd_id', 'DESC')
            ->limit(300)
            ->get()
            ->getResultArray();

        $rows = array_values(array_filter($rows, static fn ($r) => preg_match('/^\d{14}$/', trim((string) ($r['abha_id'] ?? ''))) === 1));
        if (empty($rows)) {
            return [];
        }

        $opdIds = array_values(array_unique(array_map(static fn ($r) => (int) ($r['opd_id'] ?? 0), $rows)));

        $latestDocByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('opd_fhir_documents')) {
            $docRows = $this->db->table('opd_fhir_documents')
                ->select('id, opd_id, opd_session_id, generated_at, created_at')
                ->whereIn('opd_id', $opdIds)
                ->whereIn('bundle_type', ['OPConsultRecord', 'MedicationRequestBundle', 'PrescriptionRecord'])
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

        $latestHrByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('health_records')) {
            $hrFields = $this->db->getFieldNames('health_records') ?? [];
            $hrSelect = ['id', 'entity_id', 'push_status', 'abdm_txn_id', 'care_context_reference', 'push_at', 'linked_at', 'updated_at'];
            if (in_array('bridge_record_id', $hrFields, true)) {
                $hrSelect[] = 'bridge_record_id';
            }

            $opdIdStrings = array_map(static fn ($v) => (string) $v, $opdIds);
            $hrRows = $this->db->table('health_records')
                ->select(implode(',', $hrSelect))
                ->where('entity_type', 'opd')
                ->whereIn('entity_id', $opdIdStrings)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($hrRows as $hr) {
                $k = (int) ($hr['entity_id'] ?? 0);
                if ($k <= 0 || isset($latestHrByOpd[$k])) {
                    continue;
                }
                $latestHrByOpd[$k] = $hr;
            }
        }

        $recordLinksByContext = [];
        if ($this->db->tableExists('record_links')) {
            $contexts = [];
            foreach ($latestHrByOpd as $hr) {
                $cc = trim((string) ($hr['care_context_reference'] ?? ''));
                if ($cc !== '') {
                    $contexts[] = $cc;
                }
            }
            $contexts = array_values(array_unique($contexts));

            if (! empty($contexts)) {
                $rlRows = $this->db->table('record_links')
                    ->select('id, care_context_reference, link_status, linked_at, updated_at')
                    ->whereIn('care_context_reference', $contexts)
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getResultArray();

                foreach ($rlRows as $rl) {
                    $cc = trim((string) ($rl['care_context_reference'] ?? ''));
                    if ($cc === '' || isset($recordLinksByContext[$cc])) {
                        continue;
                    }
                    $recordLinksByContext[$cc] = $rl;
                }
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $opdId = (int) ($row['opd_id'] ?? 0);
            if ($opdId <= 0) {
                continue;
            }

            $doc = $latestDocByOpd[$opdId] ?? null;
            $hr = $latestHrByOpd[$opdId] ?? null;
            $sessionId = (int) ($doc['opd_session_id'] ?? 0);

            $consultDate = (string) ($row['apointment_date'] ?? '');
            $visitDate = $consultDate !== '' ? date('Y-m-d', strtotime($consultDate)) : date('Y-m-d');
            $derivedCareContext = 'OPD-' . $opdId . '-S' . ($sessionId > 0 ? $sessionId : 0) . '-' . $visitDate;

            $careContextRef = trim((string) ($hr['care_context_reference'] ?? ''));
            if ($careContextRef === '') {
                $careContextRef = $derivedCareContext;
            }

            $rl = $recordLinksByContext[$careContextRef] ?? null;
            $pushStatus = strtolower(trim((string) ($hr['push_status'] ?? '')));
            $linkStatus = strtolower(trim((string) ($rl['link_status'] ?? '')));

            $statusLabel = 'Not Registered';
            $statusTone = 'secondary';
            $statusNote = 'FHIR not registered in health_records yet.';

            if ($doc !== null) {
                $statusLabel = 'FHIR Generated';
                $statusTone = 'info';
                $statusNote = 'FHIR bundle generated; awaiting registration.';
            }

            if ($pushStatus !== '') {
                if ($pushStatus === 'local_discovery_ready') {
                    $statusLabel = 'Discovery Ready';
                    $statusTone = 'primary';
                    $statusNote = 'Registered in HMS for M2 discovery/fetch callbacks.';
                } elseif ($pushStatus === 'local_only') {
                    $statusLabel = 'Local Only';
                    $statusTone = 'secondary';
                    $statusNote = 'Stored locally only (ABHA/consent not ready).';
                } elseif ($pushStatus === 'queued') {
                    $statusLabel = 'Submitted';
                    $statusTone = 'warning';
                    $statusNote = 'Submitted to gateway and waiting for link workflow.';
                } elseif ($pushStatus === 'linked') {
                    $statusLabel = 'Linked';
                    $statusTone = 'success';
                    $statusNote = 'Care context linked successfully.';
                } elseif ($pushStatus === 'failed') {
                    $statusLabel = 'Failed';
                    $statusTone = 'danger';
                    $statusNote = 'Last registration/share attempt failed.';
                }
            }

            if ($linkStatus === 'linked') {
                $statusLabel = 'Linked';
                $statusTone = 'success';
                $statusNote = 'Care context link confirmed by callback.';
            } elseif ($linkStatus === 'pending_discovery' && $statusLabel === 'Discovery Ready') {
                $statusNote = 'Waiting for ABDM discovery and consent fetch callbacks.';
            } elseif ($linkStatus === 'failed') {
                $statusLabel = 'Link Failed';
                $statusTone = 'danger';
                $statusNote = 'Link callback reported failure.';
            }

            $queueId = trim((string) ($hr['abdm_txn_id'] ?? ''));
            $bridgeRecordId = (int) ($hr['bridge_record_id'] ?? 0);

            $out[] = array_merge($row, [
                'opd_session_id' => $sessionId,
                'care_context_reference' => $careContextRef,
                'record_status_label' => $statusLabel,
                'record_status_tone' => $statusTone,
                'record_status_note' => $statusNote,
                'push_status' => $pushStatus,
                'link_status' => $linkStatus,
                'queue_id' => $queueId,
                'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
                'has_fhir' => $doc !== null ? 1 : 0,
            ]);
        }

        return $out;
    }

    private function backfillHealthDocumentTasks(): void
    {
        if (! $this->db->tableExists('patient_doc') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaCol = $this->resolveExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaCol === null) {
            return;
        }

        $rows = $this->db->table('patient_doc pd')
            ->select('pd.id, pd.p_id, pd.date_issue, pd.created_at, p.p_fname, p.' . $abhaCol . ' as abha_id', false)
            ->join('patient_master p', 'p.id = pd.p_id', 'inner')
            ->where('p.' . $abhaCol . ' !=', '')
            ->where('pd.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->orderBy('pd.id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $patientId = (int) ($row['p_id'] ?? 0);
            $docId = (int) ($row['id'] ?? 0);
            $abhaId = trim((string) ($row['abha_id'] ?? ''));
            if ($patientId <= 0 || $docId <= 0 || preg_match('/^\d{14}$/', $abhaId) !== 1) {
                continue;
            }

            $patientName = trim((string) ($row['p_fname'] ?? ''));
            $this->taskService->createOrRefreshTask(
                'health_document_publish',
                'patient_doc',
                'doctor_document',
                (string) $docId,
                $patientId,
                $patientName,
                $abhaId,
                'submit',
                [
                    'patient_doc_id' => $docId,
                    'trigger' => 'patient_doc.compiled',
                ]
            );
        }
    }
}
