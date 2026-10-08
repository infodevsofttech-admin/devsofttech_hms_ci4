<?php

namespace App\Controllers;

use App\Libraries\AbdmWorkTaskService;
use App\Libraries\BridgeSyncService;
use App\Libraries\Abdm\Sync\AbdmTaskBoardSyncService;

class AbdmTaskBoard extends BaseController
{
    private AbdmWorkTaskService $taskService;

    public function __construct()
    {
        $this->taskService = new AbdmWorkTaskService();
        $this->db = db_connect();
    }

    public function index()
    {
        $this->backfillPatientAbhaTasks();
        $this->backfillLabRadiologyTasks();
        $this->backfillImmunizationTasks();
        $this->backfillHealthDocumentTasks();
        $this->backfillWellnessTasks();

        $taskStatus = strtolower(trim((string) ($this->request->getGet('task_status') ?? 'all')));
        if (! in_array($taskStatus, ['all', 'open', 'completed', 'failed'], true)) {
            $taskStatus = 'all';
        }

        $rawTasks = $this->taskService->getTasks($taskStatus, 600);
        $tasks = $this->enrichTasksWithCoolingState($this->enrichTasksWithHealthRecordState($rawTasks));

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

        $filterAbha = trim((string) ($this->request->getGet('abha_address') ?? $this->request->getGet('abha') ?? ''));

        return view('abdm/task_board', [
            'tasks'                  => $tasks,
            'task_status'            => $taskStatus,
            'dashboard_metrics'      => $this->getDashboardMetrics($dateFrom, $dateTo),
            'dashboard_date_from'    => $dateFrom,
            'dashboard_date_to'      => $dateTo,
            'today_credit_opd_rows'  => $this->getTodayCreditOpdConsultRows(),
            'opd_book_rows'          => $this->getOpdBookRows(),
            'opd_consult_rows'       => $this->getOpdConsultPublishRows(),
            'invoice_rows'           => $this->getInvoiceRows(),
            'abha_patients'          => $this->getAbhaPatientsList(),
            'filter_abha_address'    => $filterAbha,
        ]);
    }

    public function list()
    {
        $this->backfillPatientAbhaTasks();
        $this->backfillLabRadiologyTasks();
        $this->backfillImmunizationTasks();
        $this->backfillHealthDocumentTasks();
        $this->backfillWellnessTasks();

        $taskStatus = strtolower(trim((string) ($this->request->getGet('task_status') ?? 'all')));
        if (! in_array($taskStatus, ['all', 'open', 'completed', 'failed'], true)) {
            $taskStatus = 'all';
        }

        $rawTasks = $this->taskService->getTasks($taskStatus, 600);
        return $this->response->setJSON([
            'ok' => 1,
            'task_status' => $taskStatus,
            'tasks' => $this->enrichTasksWithCoolingState($this->enrichTasksWithHealthRecordState($rawTasks)),
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
    private function enrichTasksWithHealthRecordState(array $tasks): array
    {
        if ($tasks === []) {
            return [];
        }

        $db = $this->db ?? db_connect();

        // 0. Batch-resolve patient_master for ABHA addresses, ABHA numbers, p_code, and phone
        $patientIds = [];
        foreach ($tasks as $task) {
            $pId = (int) ($task['patient_id'] ?? 0);
            if ($pId > 0) {
                $patientIds[] = $pId;
            }
        }
        $patientIds = array_values(array_filter(array_unique($patientIds)));
        $patientMap = [];
        if (! empty($patientIds) && $db->tableExists('patient_master')) {
            $pFields = $db->getFieldNames('patient_master') ?? [];
            $pSel = ['id', 'p_code', 'p_fname', 'gender', 'dob'];
            if (in_array('p_lname', $pFields, true)) {
                $pSel[] = 'p_lname';
            }
            if (in_array('mphone1', $pFields, true)) {
                $pSel[] = 'mphone1';
            }
            if (in_array('abha_address', $pFields, true)) {
                $pSel[] = 'abha_address';
            }
            if (in_array('abha_id', $pFields, true)) {
                $pSel[] = 'abha_id';
            }
            if (in_array('abha_no', $pFields, true)) {
                $pSel[] = 'abha_no';
            }

            $pRows = $db->table('patient_master')->select(implode(', ', $pSel))->whereIn('id', $patientIds)->get()->getResultArray();
            foreach ($pRows as $pr) {
                $rawAddr = trim((string) ($pr['abha_address'] ?? ''));
                $rawId   = trim((string) ($pr['abha_id'] ?? $pr['abha_no'] ?? ''));
                $addr = '';
                $num = '';
                if (str_contains($rawAddr, '@')) {
                    $addr = $rawAddr;
                } elseif (str_contains($rawId, '@')) {
                    $addr = $rawId;
                }
                $d1 = preg_replace('/\D/', '', $rawId);
                $d2 = preg_replace('/\D/', '', $rawAddr);
                if (is_string($d1) && strlen($d1) === 14) {
                    $num = $d1;
                } elseif (is_string($d2) && strlen($d2) === 14) {
                    $num = $d2;
                }
                $patientMap[(int) $pr['id']] = [
                    'p_code'       => (string) ($pr['p_code'] ?? ''),
                    'abha_address' => $addr,
                    'abha_number'  => $num,
                    'phone'        => (string) ($pr['mphone1'] ?? ''),
                ];
            }
        }

        // 1. Extract invoice metadata and resolve invoice_code
        $invoiceIds = [];
        foreach ($tasks as &$task) {
            $payload = json_decode((string) ($task['payload_json'] ?? ''), true);
            $meta = (is_array($payload) && isset($payload['meta']) && is_array($payload['meta'])) ? $payload['meta'] : [];
            if (! empty($meta['invoice_id'])) {
                $task['invoice_id'] = (int) $meta['invoice_id'];
                $invoiceIds[] = (int) $meta['invoice_id'];
            }
            if (! empty($meta['invoice_code'])) {
                $task['invoice_code'] = (string) $meta['invoice_code'];
            }
        }
        unset($task);

        if (! empty($invoiceIds) && $db->tableExists('invoice_master')) {
            $invRows = $db->table('invoice_master')
                ->select('id, invoice_code')
                ->whereIn('id', array_unique($invoiceIds))
                ->get()
                ->getResultArray();
            $codeMap = [];
            foreach ($invRows as $ir) {
                $codeMap[(int) $ir['id']] = (string) ($ir['invoice_code'] ?? '');
            }
            foreach ($tasks as &$task) {
                if (! empty($task['invoice_id']) && empty($task['invoice_code']) && isset($codeMap[(int) $task['invoice_id']])) {
                    $task['invoice_code'] = $codeMap[(int) $task['invoice_id']];
                }
            }
            unset($task);
        }

        // 2. Lookup health_records by entity_id with strict entity_type / patient_id scoping
        $latestHr = [];
        if ($db->tableExists('health_records')) {
            $entityIds = [];
            foreach ($tasks as $task) {
                $entityId = trim((string) ($task['entity_id'] ?? ''));
                if ($entityId !== '') {
                    $entityIds[] = $entityId;
                }
            }
            $entityIds = array_values(array_filter(array_unique($entityIds)));

            if ($entityIds !== []) {
                $rows = $db->table('health_records')
                    ->select('id, patient_id, entity_type, hi_type, entity_id, push_status, care_context_reference, linked_at, abdm_txn_id')
                    ->whereIn('entity_id', $entityIds)
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getResultArray();
                foreach ($rows as $row) {
                    $pId = (int) ($row['patient_id'] ?? 0);
                    $eType = strtolower(trim((string) ($row['entity_type'] ?? '')));
                    $hiType = strtolower(trim((string) ($row['hi_type'] ?? '')));
                    $eid = (string) ($row['entity_id'] ?? '');
                    if ($eid !== '') {
                        if ($pId > 0 && $eType !== '') {
                            $k1 = $pId . '_' . $eType . '_' . $eid;
                            if (! isset($latestHr[$k1])) {
                                $latestHr[$k1] = $row;
                            }
                        }
                        if ($pId > 0 && $hiType !== '') {
                            $k2 = $pId . '_' . $hiType . '_' . $eid;
                            if (! isset($latestHr[$k2])) {
                                $latestHr[$k2] = $row;
                            }
                        }
                        if ($eType !== '') {
                            $k3 = $eType . '_' . $eid;
                            if (! isset($latestHr[$k3])) {
                                $latestHr[$k3] = $row;
                            }
                        }
                        if (! isset($latestHr[$eid])) {
                            $latestHr[$eid] = $row;
                        }
                    }
                }
            }
        }

        foreach ($tasks as &$task) {
            $pId = (int) ($task['patient_id'] ?? 0);
            $taskType = strtolower(trim((string) ($task['task_type'] ?? '')));
            $taskEntityType = strtolower(trim((string) ($task['entity_type'] ?? '')));
            $eid = (string) ($task['entity_id'] ?? '');

            // Map task type or entity type to standard health_record entity types / hi_types
            $candidateTypes = array_values(array_filter([$taskEntityType]));
            if ($taskType === 'immunization_record_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['immunization', 'immunizationrecord'])));
            } elseif ($taskType === 'opd_prescription_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['opd', 'opd_prescription', 'opconsultrecord'])));
            } elseif ($taskType === 'ipd_discharge_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['ipd', 'ipd_discharge', 'dischargesummaryrecord'])));
            } elseif ($taskType === 'lab_report_publish' || $taskType === 'radiology_report_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['lab', 'radiology', 'diagnosticreportrecord'])));
            } elseif ($taskType === 'wellness_record_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['opd_vitals', 'opd', 'wellness', 'wellnessrecord'])));
            } elseif ($taskType === 'health_document_publish') {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['patient_document', 'doctor_document', 'file_upload_data', 'patient_doc', 'healthdocumentrecord'])));
            } elseif (in_array($taskType, ['invoice_publish', 'invoice_record_publish'], true)) {
                $candidateTypes = array_values(array_unique(array_merge($candidateTypes, ['invoice', 'opd_invoice', 'charges_invoice', 'ipd_invoice', 'invoicerecord'])));
            }

            $hr = null;
            // 1) Match with patient_id and entity/hi type
            if ($pId > 0) {
                foreach ($candidateTypes as $cType) {
                    $key = $pId . '_' . strtolower($cType) . '_' . $eid;
                    if (isset($latestHr[$key])) {
                        $hr = $latestHr[$key];
                        break;
                    }
                }
            }
            // 2) Match with entity/hi type without patient_id
            if ($hr === null) {
                foreach ($candidateTypes as $cType) {
                    $key = strtolower($cType) . '_' . $eid;
                    if (isset($latestHr[$key])) {
                        $hr = $latestHr[$key];
                        break;
                    }
                }
            }
            // 3) Only fallback to raw $eid if task has no specific taskType or candidateTypes
            if ($hr === null && empty($candidateTypes) && isset($latestHr[$eid])) {
                $hr = $latestHr[$eid];
            }

            $pushStatus = strtolower(trim((string) ($hr['push_status'] ?? '')));
            $task['bridge_health_record_id'] = (int) ($hr['id'] ?? 0);
            $task['bridge_push_status'] = $pushStatus;

            $careContext = trim((string) ($hr['care_context_reference'] ?? ''));
            if ($careContext === '') {
                $resultText = (string) ($task['last_action_result'] ?? '');
                if (preg_match('/((?:RAD|LAB|OPD|INVOICE|IMM|DISCHARGE|DIS|WELLNESS|DOC)-[A-Za-z0-9_-]+)/i', $resultText, $ccm)) {
                    $careContext = $ccm[1];
                }
            }

            $pData = $patientMap[$pId] ?? null;
            $taskAbha = trim((string) ($task['abha_id'] ?? ''));
            $taskAbhaAddr = (string) ($pData['abha_address'] ?? '');
            $taskAbhaNum  = (string) ($pData['abha_number'] ?? '');

            if (str_contains($taskAbha, '@') && $taskAbhaAddr === '') {
                $taskAbhaAddr = $taskAbha;
            }
            $cleanAbha = preg_replace('/\D/', '', $taskAbha);
            if (strlen($cleanAbha) === 14 && $taskAbhaNum === '') {
                $taskAbhaNum = $cleanAbha;
            }

            $task['patient_p_code']       = (string) ($pData['p_code'] ?? '');
            $task['patient_abha_address'] = $taskAbhaAddr;
            $task['patient_abha_number']  = $taskAbhaNum;
            $task['patient_phone']        = (string) ($pData['phone'] ?? '');

            $task['bridge_care_context_reference'] = $careContext;
            $task['bridge_submitted'] = in_array($pushStatus, ['queued', 'pushed', 'linked'], true) ? 1 : 0;
        }
        unset($task);

        return $tasks;
    }

    /** @param array<int,array<string,mixed>> $tasks */
    private function enrichImmunizationPushState(array $tasks): array
    {
        return $this->enrichTasksWithHealthRecordState($tasks);
    }

    /** @param array<int,array<string,mixed>> $tasks */
    private function enrichTasksWithCoolingState(array $tasks): array
    {
        if (empty($tasks)) {
            return [];
        }

        foreach ($tasks as &$task) {
            $taskStatus = strtolower(trim((string) ($task['status'] ?? 'pending')));
            if (in_array($taskStatus, ['completed', 'linked', 'cancelled'], true)) {
                $task['cooling_active'] = false;
                $task['cooling_remaining_minutes'] = 0;
                $task['cooling_remaining_seconds'] = 0;
                $task['auto_link_at'] = null;
                $task['ready_for_autolink'] = false;
                continue;
            }

            $taskType = (string) ($task['task_type'] ?? '');
            $lastModified = AbdmTaskBoardSyncService::resolveTaskClinicalTimestamp($task, $this->db);
            $cooling = AbdmTaskBoardSyncService::calculateCooling($taskType, $lastModified);

            $task['cooling_active'] = ! empty($cooling['is_cooling_active']);
            $task['cooling_remaining_minutes'] = (int) ($cooling['remaining_minutes'] ?? 0);
            $task['cooling_remaining_seconds'] = (int) ($cooling['remaining_seconds'] ?? 0);
            $task['auto_link_at'] = $cooling['auto_link_at'] ?? null;
            $task['ready_for_autolink'] = false;

            if ($task['cooling_active']) {
                $remMin = $task['cooling_remaining_minutes'];
                if ($remMin >= 60) {
                    $hours = intdiv($remMin, 60);
                    $mins = $remMin % 60;
                    $timeStr = $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
                } else {
                    $timeStr = "{$remMin}m";
                }
                $task['cooling_label'] = "Cooling ({$timeStr} left)";
                $task['cooling_tooltip'] = "Cooling active until {$task['auto_link_at']} to allow clinician edits. Modifications reset the timer. Click Link to bypass.";
            } elseif (! empty($task['auto_link_at']) && $task['auto_link_at'] !== 'disabled') {
                $task['ready_for_autolink'] = true;
                $task['cooling_label'] = "Ready for Link";
                $task['cooling_tooltip'] = "Cooling period elapsed at {$task['auto_link_at']}. Queued for automated linking.";
            }
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
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be a 14-digit number or valid ABHA address']);
        }
        $digits = preg_replace('/\D/', '', $abhaId);
        if (strlen($digits) === 14) {
            $abhaId = $digits;
        }

        $taskType = (string) ($task['task_type'] ?? '');
        $patientId = (int) ($task['patient_id'] ?? 0);
        $entityId = (int) ($task['entity_id'] ?? 0);

        if (strtolower($action) === 'submit') {
            $gateway = new AbdmGateway();
            $gateway->initController($this->request, $this->response, service('logger'));

            $_POST['task_id'] = $taskId;
            $_POST['patient_id'] = $patientId;
            $_POST['abha_id'] = $abhaId;
            $_POST['push_to_gateway'] = 1;

            if ($taskType === 'wellness_record_publish') {
                $_POST['opd_id'] = $entityId;
                return $gateway->shareWellnessBundle();
            }
            if ($taskType === 'health_document_publish') {
                $_POST['record_id'] = $entityId;
                $_POST['patient_doc_id'] = $entityId;
                return $gateway->shareHealthDocumentBundle();
            }
            if ($taskType === 'immunization_record_publish') {
                $_POST['record_id'] = $entityId;
                return $gateway->shareImmunizationBundle();
            }
            if ($taskType === 'opd_prescription_publish') {
                $_POST['opd_id'] = $entityId;
                return $gateway->sharePrescriptionBundle();
            }
            if ($taskType === 'lab_report_publish' || $taskType === 'radiology_report_publish') {
                $_POST['lab_req_id'] = $entityId;
                return $gateway->shareDiagnosisReportBundle();
            }
            if ($taskType === 'ipd_discharge_publish') {
                $_POST['ipd_id'] = $entityId;
                return $gateway->shareIpdDischargeBundle();
            }
        }

        $payload = [
            'task_id' => $taskId,
            'task_code' => (string) ($task['task_code'] ?? ''),
            'task_type' => $taskType,
            'patient_id' => $patientId,
            'patient_name' => (string) ($task['patient_name'] ?? ''),
            'abha_id' => $abhaId,
            'entity_type' => (string) ($task['entity_type'] ?? ''),
            'entity_id' => (string) ($task['entity_id'] ?? ''),
            'opd_session_id' => (int) $this->request->getPost('opd_session_id'),
        ];

        $eventType = $this->resolveActionEventType($action, $taskType);
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
            if ($taskType === 'wellness_record_publish') {
                return 'abdm.wellness_record.share.requested';
            }
            if ($taskType === 'immunization_record_publish') {
                return 'abdm.immunization_record.share.requested';
            }
        }

        return '';
    }

    private function isValidAbhaNumber(string $abhaId): bool
    {
        $clean = preg_replace('/\D/', '', $abhaId);
        if (strlen($clean) === 14) {
            return true;
        }

        return str_contains($abhaId, '@') && preg_match('/^[a-zA-Z0-9.\-_]{3,}@[a-zA-Z]{3,}$/', $abhaId) === 1;
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
            if ($this->isValidAbhaNumber($abha)) {
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

        $labFields = $this->db->getFieldNames('lab_request') ?? [];
        $dateFields = array_intersect(['reported_time', 'collected_time', 'Request_Date'], $labFields);
        $dateSelect = ! empty($dateFields) ? (', r.' . implode(', r.', $dateFields)) : '';
        $select = 'r.id, r.patient_id, r.patient_name, r.lab_type, r.charge_id, r.status' . $dateSelect . ', ' . implode(', ', $abhaSelectParts);

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

            $rawAbha = '';
            $abhaAddress = '';
            foreach (['abha_address', 'abha_id', 'abha_no', 'abha'] as $f) {
                $val = trim((string) ($row[$f] ?? ''));
                if ($val === '') {
                    continue;
                }
                if ($abhaAddress === '' && str_contains($val, '@')) {
                    $abhaAddress = $val;
                    continue;
                }
                $digits = preg_replace('/\D/', '', $val);
                if ($rawAbha === '' && strlen($digits) === 14) {
                    $rawAbha = $digits;
                }
            }

            $abha = $rawAbha !== '' ? $rawAbha : $abhaAddress;
            if ($abha === '') {
                continue;
            }

            $labType = (int) ($row['lab_type'] ?? 0);
            $taskType = in_array($labType, [1, 2, 3, 4, 6], true) ? 'radiology_report_publish' : 'lab_report_publish';

            // Skip if already linked in health_records
            if ($this->db->tableExists('health_records')) {
                $alreadyLinked = $this->db->table('health_records')
                    ->select('id')
                    ->where('patient_id', $patientId)
                    ->groupStart()
                        ->where('entity_id', (string) $labReqId)
                        ->orLike('care_context_reference', 'RAD-' . $labReqId . '-', 'after')
                        ->orLike('care_context_reference', 'LAB-' . $labReqId . '-', 'after')
                    ->groupEnd()
                    ->whereIn('push_status', ['linked', 'pushed', 'shared'])
                    ->get(1)
                    ->getRowArray();
                if (! empty($alreadyLinked)) {
                    continue;
                }
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', $taskType)
                ->where('entity_type', 'lab_request')
                ->where('entity_id', (string) $labReqId)
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $clinTs = ! empty($row['reported_time'])
                ? $row['reported_time']
                : (! empty($row['collected_time']) ? $row['collected_time'] : ($row['Request_Date'] ?? ''));

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
                    'lab_type'           => $labType,
                    'invoice_id'         => (int) ($row['charge_id'] ?? 0),
                    'clinical_timestamp' => (string) $clinTs,
                    'trigger'            => 'task_board.backfill',
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
                ->whereIn('entity_type', ['immunization', 'immunization_record_publish'])
                ->where('entity_id', (string) $recordId)
                ->whereIn('push_status', ['linked', 'queued', 'pushed', 'done'])
                ->get(1)
                ->getRowArray());
            if ($linked) {
                continue;
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', 'immunization_record_publish')
                ->whereIn('entity_type', ['immunization', 'immunization_records', 'immunization_record_publish'])
                ->where('entity_id', (string) $recordId)
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
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
            $builder = $this->db->table('opd_master o')
                ->select("o.opd_id AS bill_id, o.opd_code AS bill_code, o.p_id AS patient_id, COALESCE(NULLIF(o.P_name, ''), p.p_fname, '') AS patient_name, o.apointment_date AS bill_date, o.opd_fee_amount AS amount, COALESCE(p.p_code, '') AS p_code, COALESCE(p.abha_address, '') AS patient_abha_address, COALESCE(p.abha_id, '') AS patient_abha_number, COALESCE(NULLIF(p.abha_address, ''), NULLIF(p.abha_id, ''), '') AS abha_id", false);
            if ($this->db->tableExists('patient_master')) {
                $builder->join('patient_master p', 'p.id = o.p_id', 'left');
            }
            foreach ($builder
                ->orderBy('o.opd_id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() as $row) {
                $rows[] = array_merge($row, ['source' => 'OPD', 'source_key' => 'opd_invoice']);
            }
        }

        if ($this->db->tableExists('invoice_master')) {
            $builder = $this->db->table('invoice_master i')
                ->select("i.id AS bill_id, i.invoice_code AS bill_code, i.attach_id AS patient_id, COALESCE(NULLIF(i.inv_name, ''), p.p_fname, '') AS patient_name, i.inv_date AS bill_date, i.net_amount AS amount, COALESCE(p.p_code, '') AS p_code, COALESCE(p.abha_address, '') AS patient_abha_address, COALESCE(p.abha_id, '') AS patient_abha_number, COALESCE(NULLIF(p.abha_address, ''), NULLIF(p.abha_id, ''), '') AS abha_id", false);
            if ($this->db->tableExists('patient_master')) {
                $builder->join('patient_master p', 'p.id = i.attach_id AND i.attach_type = 0', 'left');
            }
            foreach ($builder
                ->orderBy('i.id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray() as $row) {
                $rows[] = array_merge($row, ['source' => 'Charges', 'source_key' => 'charges_invoice']);
            }
        }

        if ($this->db->tableExists('ipd_master')) {
            $builder = $this->db->table('ipd_master i')
                ->select("i.id AS bill_id, i.ipd_code AS bill_code, i.p_id AS patient_id, COALESCE(NULLIF(NULLIF(TRIM(i.P_name), ''), '0'), NULLIF(TRIM(p.p_fname), ''), '') AS patient_name, COALESCE(i.discharge_date, i.register_date) AS bill_date, i.net_amount AS amount, COALESCE(p.p_code, '') AS p_code, COALESCE(p.abha_address, '') AS patient_abha_address, COALESCE(p.abha_id, '') AS patient_abha_number, COALESCE(NULLIF(p.abha_address, ''), NULLIF(p.abha_id, ''), '') AS abha_id", false);
            if ($this->db->tableExists('patient_master')) {
                $builder->join('patient_master p', 'p.id = i.p_id', 'left');
            }
            foreach ($builder
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
            $rawAddress = trim((string) ($row['patient_abha_address'] ?? ''));
            $rawNumber = trim((string) ($row['patient_abha_number'] ?? ''));
            $addr = '';
            $num = '';
            if (str_contains($rawAddress, '@')) {
                $addr = $rawAddress;
            } elseif (str_contains($rawNumber, '@')) {
                $addr = $rawNumber;
            }
            $d1 = preg_replace('/\D/', '', $rawNumber);
            $d2 = preg_replace('/\D/', '', $rawAddress);
            if (is_string($d1) && strlen($d1) === 14) {
                $num = $d1;
            } elseif (is_string($d2) && strlen($d2) === 14) {
                $num = $d2;
            }
            $row['abha_address'] = $addr;
            $row['abha_number']  = $num;
            $row['abha_id']      = $addr !== '' ? $addr : $num;
            $row['p_code']       = (string) ($row['p_code'] ?? '');

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

            $billDate = ! empty($row['bill_date']) ? date('Y-m-d', strtotime((string) $row['bill_date'])) : date('Y-m-d');
            $sourcePrefix = $row['source_key'] === 'opd_invoice' ? 'OPD' : ($row['source_key'] === 'ipd_invoice' ? 'IPD' : 'CHG');
            $defaultCareContext = 'INVOICE-' . $sourcePrefix . '-' . $billId . '-' . $billDate;

            $row['record_status_label'] = $statusLabel;
            $row['record_status_tone'] = $statusTone;
            $row['push_status'] = $pushStatus;
            $row['link_status'] = $linkStatus;
            $row['queue_id'] = trim((string) ($hr['abdm_txn_id'] ?? ''));
            $row['bridge_record_id'] = (int) ($hr['bridge_record_id'] ?? 0);
            $row['care_context_reference'] = trim((string) ($hr['care_context_reference'] ?? '')) ?: $defaultCareContext;
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
    /**
     * Distinct patients with an ABHA address or ABHA number for the board selector & datalist.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAbhaPatientsList(): array
    {
        if (! $this->db->tableExists('patient_master')) {
            return [];
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $hasAbhaAddress = in_array('abha_address', $fields, true);
        $hasAbhaId = in_array('abha_id', $fields, true);
        $hasAbhaNo = in_array('abha_no', $fields, true);

        if (! $hasAbhaAddress && ! $hasAbhaId && ! $hasAbhaNo) {
            return [];
        }

        $select = ['id', 'p_code', 'p_fname', 'gender', 'dob'];
        if (in_array('p_lname', $fields, true)) {
            $select[] = 'p_lname';
        }
        if (in_array('mphone1', $fields, true)) {
            $select[] = 'mphone1';
        }
        if ($hasAbhaAddress) {
            $select[] = 'abha_address';
        }
        if ($hasAbhaId) {
            $select[] = 'abha_id';
        }
        if ($hasAbhaNo) {
            $select[] = 'abha_no';
        }

        $builder = $this->db->table('patient_master')->select(implode(', ', $select));

        $whereOr = [];
        if ($hasAbhaAddress) {
            $whereOr[] = "NULLIF(TRIM(abha_address), '') IS NOT NULL";
        }
        if ($hasAbhaId) {
            $whereOr[] = "NULLIF(TRIM(abha_id), '') IS NOT NULL";
        }
        if ($hasAbhaNo) {
            $whereOr[] = "NULLIF(TRIM(abha_no), '') IS NOT NULL";
        }

        if ($whereOr !== []) {
            $builder->where('(' . implode(' OR ', $whereOr) . ')', null, false);
        }

        $rows = $builder->orderBy('id', 'DESC')->limit(500)->get()->getResultArray();
        $patients = [];

        foreach ($rows as $row) {
            $rawAddress = trim((string) ($row['abha_address'] ?? ''));
            $rawId = trim((string) ($row['abha_id'] ?? $row['abha_no'] ?? ''));

            $addr = '';
            $num = '';

            if (str_contains($rawAddress, '@')) {
                $addr = $rawAddress;
            } elseif (str_contains($rawId, '@')) {
                $addr = $rawId;
            }

            $d1 = preg_replace('/\D/', '', $rawId);
            $d2 = preg_replace('/\D/', '', $rawAddress);
            if (is_string($d1) && strlen($d1) === 14) {
                $num = $d1;
            } elseif (is_string($d2) && strlen($d2) === 14) {
                $num = $d2;
            }

            if ($addr === '' && $num === '') {
                continue;
            }

            $fname = trim((string) ($row['p_fname'] ?? ''));
            $lname = trim((string) ($row['p_lname'] ?? ''));
            $fullName = trim($fname . ' ' . $lname);
            if ($fullName === '') {
                $fullName = 'Patient #' . ($row['id'] ?? '');
            }

            $genderInt = (int) ($row['gender'] ?? 0);
            $genderStr = ($genderInt === 1 ? 'M' : ($genderInt === 2 ? 'F' : 'O'));

            $dob = trim((string) ($row['dob'] ?? ''));
            $yob = 0;
            if ($dob !== '' && ! str_starts_with($dob, '0000')) {
                $ts = strtotime($dob);
                if ($ts !== false) {
                    $yob = (int) date('Y', $ts);
                }
            }

            $patients[] = [
                'id'           => (int) ($row['id'] ?? 0),
                'p_code'       => (string) ($row['p_code'] ?? ''),
                'name'         => $fullName,
                'abha_address' => $addr,
                'abha_number'  => $num,
                'phone'        => (string) ($row['mphone1'] ?? ''),
                'gender'       => $genderStr,
                'dob'          => $dob,
                'yob'          => $yob,
            ];
        }

        return $patients;
    }

    private function getOpdBookRows(): array
    {
        if (! $this->db->tableExists('abdm_opd_tokens')) {
            return [];
        }

        $builder = $this->db->table('abdm_opd_tokens t');
        if ($this->db->tableExists('patient_master')) {
            $builder->select('t.*, p.p_code, p.mphone1 as patient_phone')
                ->join('patient_master p', 'p.id = t.patient_id', 'left');
        } else {
            $builder->select('t.*');
        }

        $rows = $builder->where('t.queue_date >=', date('Y-m-d', strtotime('-7 days')))
            ->orderBy('t.queue_date', 'DESC')
            ->orderBy('t.gateway_token_id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        foreach ($rows as &$r) {
            $rawAddress = trim((string) ($r['abha_address'] ?? ''));
            $rawNumber  = trim((string) ($r['abha_number'] ?? ''));
            if (str_contains($rawNumber, '@') && $rawAddress === '') {
                $rawAddress = $rawNumber;
            }
            $r['abha_address'] = $rawAddress;
            $r['abha_number']  = preg_replace('/\D/', '', $rawNumber);
            $r['p_code']       = (string) ($r['p_code'] ?? '');
        }
        unset($r);

        return $rows;
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
        $selectCols = [
            'o.opd_id', 'o.p_id', 'o.P_name', 'o.apointment_date', 'o.opd_status', 'o.doc_name',
        ];
        if (in_array('p_code', $patientFields, true)) {
            $selectCols[] = 'p.p_code';
        }
        if (in_array('abha_address', $patientFields, true)) {
            $selectCols[] = 'p.abha_address';
        }
        if (in_array('abha_id', $patientFields, true)) {
            $selectCols[] = 'p.abha_id';
        }
        if (in_array('abha_no', $patientFields, true)) {
            $selectCols[] = 'p.abha_no';
        }
        if (in_array('mphone1', $patientFields, true)) {
            $selectCols[] = 'p.mphone1 as patient_phone';
        }

        $builder = $this->db->table('opd_master o')
            ->select(implode(', ', $selectCols), false)
            ->join('patient_master p', 'p.id = o.p_id', 'left')
            ->where('o.opd_status', 2)
            ->where('DATE(o.apointment_date) >=', date('Y-m-d', strtotime('-30 days')), false);

        $whereAbhaOr = [];
        if (in_array('abha_address', $patientFields, true)) {
            $whereAbhaOr[] = "NULLIF(TRIM(p.abha_address), '') IS NOT NULL";
        }
        if (in_array('abha_id', $patientFields, true)) {
            $whereAbhaOr[] = "NULLIF(TRIM(p.abha_id), '') IS NOT NULL";
        }
        if (in_array('abha_no', $patientFields, true)) {
            $whereAbhaOr[] = "NULLIF(TRIM(p.abha_no), '') IS NOT NULL";
        }
        if ($whereAbhaOr !== []) {
            $builder->where('(' . implode(' OR ', $whereAbhaOr) . ')', null, false);
        }

        $rawRows = $builder->orderBy('o.opd_id', 'DESC')->limit(300)->get()->getResultArray();

        $rows = [];
        foreach ($rawRows as $r) {
            $rawAddress = trim((string) ($r['abha_address'] ?? ''));
            $rawId = trim((string) ($r['abha_id'] ?? $r['abha_no'] ?? ''));
            $addr = '';
            $num = '';
            if (str_contains($rawAddress, '@')) {
                $addr = $rawAddress;
            } elseif (str_contains($rawId, '@')) {
                $addr = $rawId;
            }
            $d1 = preg_replace('/\D/', '', $rawId);
            $d2 = preg_replace('/\D/', '', $rawAddress);
            if (is_string($d1) && strlen($d1) === 14) {
                $num = $d1;
            } elseif (is_string($d2) && strlen($d2) === 14) {
                $num = $d2;
            }

            if ($addr === '' && $num === '') {
                continue;
            }

            $r['abha_address'] = $addr;
            $r['abha_number']  = $num;
            $r['abha_id']      = $addr !== '' ? $addr : $num;
            $r['p_code']       = (string) ($r['p_code'] ?? '');
            $rows[] = $r;
        }

        if (empty($rows)) {
            return [];
        }

        $opdIds = array_values(array_unique(array_map(static fn ($r) => (int) ($r['opd_id'] ?? 0), $rows)));

        $latestDocByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('opd_fhir_documents')) {
            $docRows = $this->db->table('opd_fhir_documents')
                ->select('id, opd_id, opd_session_id, generated_at, created_at, updated_at')
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

            $docModified = null;
            if ($doc !== null) {
                $statusLabel = 'FHIR Generated';
                $statusTone = 'info';
                $statusNote = 'FHIR bundle generated; awaiting registration.';

                $docTimestamps = array_filter([
                    ! empty($doc['updated_at']) ? (string) $doc['updated_at'] : null,
                    ! empty($doc['generated_at']) ? (string) $doc['generated_at'] : null,
                    ! empty($doc['created_at']) ? (string) $doc['created_at'] : null,
                ]);
                $docModified = ! empty($docTimestamps) ? max($docTimestamps) : null;
            }

            $cooling = AbdmTaskBoardSyncService::calculateCooling('opd_prescription_publish', $docModified);
            $isCoolingActive = ! empty($cooling['is_cooling_active']);
            $coolingRemainingMinutes = (int) ($cooling['remaining_minutes'] ?? 0);
            $autoLinkAt = $cooling['auto_link_at'] ?? null;

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

            // If not yet linked or submitted to gateway, reflect cooling window state
            $isAlreadyLinkedOrQueued = in_array($linkStatus, ['linked', 'registered'], true) || in_array($pushStatus, ['linked', 'pushed', 'queued'], true);
            if ($doc !== null && ! $isAlreadyLinkedOrQueued) {
                if ($isCoolingActive) {
                    $remMin = $coolingRemainingMinutes;
                    if ($remMin >= 60) {
                        $hours = intdiv($remMin, 60);
                        $mins = $remMin % 60;
                        $timeStr = $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
                    } else {
                        $timeStr = "{$remMin}m";
                    }
                    $statusLabel = 'Cooling (' . $timeStr . ' left)';
                    $statusTone = 'warning';
                    $statusNote = 'Cooling active until ' . $autoLinkAt . ' to allow clinician prescription edits before auto-link.';
                } elseif (! empty($autoLinkAt) && $autoLinkAt !== 'disabled') {
                    $statusLabel = 'Ready for Auto-Link';
                    $statusTone = 'primary';
                    $statusNote = 'Cooling period elapsed. Ready for automated link push.';
                }
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
                'cooling_active' => $isCoolingActive ? 1 : 0,
                'cooling_remaining_minutes' => $coolingRemainingMinutes,
                'auto_link_at' => $autoLinkAt,
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
            if ($patientId <= 0 || $docId <= 0 || ! $this->isValidAbhaNumber($abhaId)) {
                continue;
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', 'health_document_publish')
                ->where('entity_type', 'doctor_document')
                ->where('entity_id', (string) $docId)
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
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
                    'patient_doc_id'     => $docId,
                    'clinical_timestamp' => (string) (! empty($row['created_at']) ? $row['created_at'] : ($row['date_issue'] ?? '')),
                    'trigger'            => 'patient_doc.compiled',
                ]
            );
        }

        if ($this->db->tableExists('file_upload_data')) {
            $fileRows = $this->db->table('file_upload_data f')
                ->select('f.id, f.pid, f.insert_date, p.p_fname, p.' . $abhaCol . ' as abha_id', false)
                ->join('patient_master p', 'p.id = f.pid', 'inner')
                ->where('p.' . $abhaCol . ' !=', '')
                ->where('f.insert_date >=', date('Y-m-d H:i:s', strtotime('-30 days')))
                ->orderBy('f.id', 'DESC')
                ->limit(200)
                ->get()
                ->getResultArray();

            foreach ($fileRows as $fRow) {
                $patientId = (int) ($fRow['pid'] ?? 0);
                $fileId = (int) ($fRow['id'] ?? 0);
                $abhaId = trim((string) ($fRow['abha_id'] ?? ''));
                if ($patientId <= 0 || $fileId <= 0 || ! $this->isValidAbhaNumber($abhaId)) {
                    continue;
                }

                $exists = $this->db->table('abdm_work_tasks')
                    ->select('id')
                    ->where('task_type', 'health_document_publish')
                    ->where('entity_type', 'patient_document')
                    ->where('entity_id', (string) $fileId)
                    ->whereIn('status', ['pending', 'in_progress', 'completed'])
                    ->get(1)
                    ->getRowArray();
                if (! empty($exists)) {
                    continue;
                }

                $patientName = trim((string) ($fRow['p_fname'] ?? ''));
                $this->taskService->createOrRefreshTask(
                    'health_document_publish',
                    'file_upload_data',
                    'patient_document',
                    (string) $fileId,
                    $patientId,
                    $patientName,
                    $abhaId,
                    'submit',
                    [
                        'file_upload_id'     => $fileId,
                        'clinical_timestamp' => (string) ($fRow['insert_date'] ?? ''),
                        'trigger'            => 'file_upload_data.created',
                    ]
                );
            }
        }
    }

    private function backfillWellnessTasks(): void
    {
        // 1. Backfill from patient_wellness_records (ABDM M2 Comprehensive Wellness & Vitals)
        if ($this->db->tableExists('patient_wellness_records') && $this->db->tableExists('patient_master')) {
            $patientFields = $this->db->getFieldNames('patient_master') ?? [];
            $abhaCol = $this->resolveExistingColumn($patientFields, ['abha_address', 'abha_id', 'abha_no', 'abha']);
            if ($abhaCol !== null) {
                $wellnessRows = $this->db->table('patient_wellness_records w')
                    ->select('w.id, w.patient_id, w.recorded_at, w.care_context_reference, w.bridge_record_id, w.abdm_status, p.p_fname, p.p_lname, p.' . $abhaCol . ' as abha_id', false)
                    ->join('patient_master p', 'p.id = w.patient_id', 'inner')
                    ->where('p.' . $abhaCol . ' !=', '')
                    ->orderBy('w.id', 'DESC')
                    ->limit(200)
                    ->get()
                    ->getResultArray();

                foreach ($wellnessRows as $wRow) {
                    $patientId = (int) ($wRow['patient_id'] ?? 0);
                    $wellnessId = (int) ($wRow['id'] ?? 0);
                    $abhaId = trim((string) ($wRow['abha_id'] ?? ''));
                    if ($patientId <= 0 || $wellnessId <= 0 || ! $this->isValidAbhaNumber($abhaId)) {
                        continue;
                    }

                    $exists = $this->db->table('abdm_work_tasks')
                        ->select('id, status')
                        ->where('task_type', 'wellness_record_publish')
                        ->where('entity_type', 'wellness')
                        ->where('entity_id', (string) $wellnessId)
                        ->get(1)
                        ->getRowArray();
                    if (! empty($exists)) {
                        continue;
                    }

                    $patientName = trim(($wRow['p_fname'] ?? '') . ' ' . ($wRow['p_lname'] ?? ''));
                    $isPushed = (! empty($wRow['bridge_record_id']) || in_array($wRow['abdm_status'] ?? '', ['queued', 'pushed', 'linked'], true));
                    $taskId = $this->taskService->createOrRefreshTask(
                        'wellness_record_publish',
                        'patient_wellness_records',
                        'wellness',
                        (string) $wellnessId,
                        $patientId,
                        $patientName,
                        $abhaId,
                        'submit',
                        [
                            'wellness_id'            => $wellnessId,
                            'care_context_reference' => (string) ($wRow['care_context_reference'] ?? ''),
                            'bridge_record_id'       => (int) ($wRow['bridge_record_id'] ?? 0),
                            'clinical_timestamp'     => (string) ($wRow['recorded_at'] ?? ''),
                            'trigger'                => 'wellness_records.backfilled',
                        ]
                    );

                    if ($isPushed && $taskId > 0) {
                        $this->db->table('abdm_work_tasks')->where('id', $taskId)->update([
                            'status'             => 'completed',
                            'last_action_result' => 'Pushed to ABDM Bridge (ID #' . ($wRow['bridge_record_id'] ?? '') . ')',
                            'completed_at'       => date('Y-m-d H:i:s'),
                            'updated_at'         => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        // 2. Also backfill from opd_prescription (for legacy OPD vitals)
        if (! $this->db->tableExists('opd_prescription') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaCol = $this->resolveExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaCol === null) {
            return;
        }

        $opdPrescFields = $this->db->getFieldNames('opd_prescription') ?? [];
        $vitalCols = array_values(array_intersect(['bp', 'diastolic', 'pulse', 'height', 'weight', 'temp', 'rr_min', 'spo2', 'glucose'], $opdPrescFields));
        if ($vitalCols === []) {
            return;
        }

        $coalesceExpr = [];
        foreach ($vitalCols as $vc) {
            $coalesceExpr[] = "NULLIF(TRIM(pr." . $vc . "), '')";
        }
        $whereSql = "COALESCE(" . implode(', ', $coalesceExpr) . ") IS NOT NULL";

        $dateCol = in_array('date_opd_visit', $opdPrescFields, true) ? 'pr.date_opd_visit' : 'pr.id';

        $rows = $this->db->table('opd_prescription pr')
            ->select('pr.id, pr.p_id, pr.opd_id, ' . $dateCol . ', p.p_fname, p.' . $abhaCol . ' as abha_id', false)
            ->join('patient_master p', 'p.id = pr.p_id', 'inner')
            ->where('p.' . $abhaCol . ' !=', '')
            ->where($whereSql, null, false)
            ->orderBy('pr.id', 'DESC')
            ->limit(200)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $patientId = (int) ($row['p_id'] ?? 0);
            $rxId = (int) ($row['id'] ?? 0);
            $opdId = (int) ($row['opd_id'] ?? 0);
            $abhaId = trim((string) ($row['abha_id'] ?? ''));
            if ($patientId <= 0 || $rxId <= 0 || ! $this->isValidAbhaNumber($abhaId)) {
                continue;
            }

            $exists = $this->db->table('abdm_work_tasks')
                ->select('id')
                ->where('task_type', 'wellness_record_publish')
                ->where('entity_type', 'opd_vitals')
                ->where('entity_id', (string) $rxId)
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->get(1)
                ->getRowArray();
            if (! empty($exists)) {
                continue;
            }

            $patientName = trim((string) ($row['p_fname'] ?? ''));
            $this->taskService->createOrRefreshTask(
                'wellness_record_publish',
                'opd_prescription',
                'opd_vitals',
                (string) $rxId,
                $patientId,
                $patientName,
                $abhaId,
                'submit',
                [
                    'opd_prescription_id' => $rxId,
                    'opd_session_id'      => $opdId,
                    'clinical_timestamp'  => (string) ($row['date_opd_visit'] ?? ''),
                    'trigger'             => 'vitals.backfilled',
                ]
            );
        }
    }
}
