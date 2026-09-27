<?php

namespace App\Libraries\Abdm\Sync;

use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\AbdmConnectorInterface;
use App\Libraries\AbdmWorkTaskService;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

class AbdmTaskBoardSyncService
{
    private BaseConnection $db;
    private AbdmConnectorInterface $connector;
    private AbdmWorkTaskService $taskService;

    public function __construct(
        ?BaseConnection $db = null,
        ?AbdmConnectorInterface $connector = null
    ) {
        $this->db = $db ?? db_connect();
        $this->connector = $connector ?? AbdmConnectorFactory::make();
        $this->taskService = new AbdmWorkTaskService();
    }

    /**
     * Run full ABDM Work Task Board sync (both OPD Consult and Work Queue tasks).
     *
     * @return array{
     *     opd: array{eligible: int, linked: int, failed: int, skipped: int, details: array<int, array<string, mixed>>},
     *     tasks: array{eligible: int, linked: int, failed: int, skipped: int, details: array<int, array<string, mixed>>}
     * }
     */
    public function syncAll(int $limit = 20, bool $dryRun = false): array
    {
        $opdSummary = $this->syncOpdConsultRecords($limit, $dryRun);
        $tasksSummary = $this->syncOpenWorkTasks($limit, $dryRun);

        return [
            'opd'   => $opdSummary,
            'tasks' => $tasksSummary,
        ];
    }

    /**
     * Scan and link Done OPD records that have an ABHA identity and a generated FHIR bundle.
     *
     * @return array{
     *     eligible: int,
     *     linked: int,
     *     failed: int,
     *     skipped: int,
     *     details: array<int, array<string, mixed>>
     * }
     */
    public function syncOpdConsultRecords(int $limit = 20, bool $dryRun = false): array
    {
        $summary = [
            'eligible' => 0,
            'linked'   => 0,
            'failed'   => 0,
            'skipped'  => 0,
            'details'  => [],
        ];

        if (! $this->db->tableExists('opd_master') || ! $this->db->tableExists('patient_master')) {
            return $summary;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaCol = $this->resolveFirstExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaCol === null) {
            return $summary;
        }

        // 1. Fetch done appointments from the last 30 days where patient has ABHA
        $rows = $this->db->table('opd_master o')
            ->select('o.opd_id, o.p_id, o.P_name, o.apointment_date, o.opd_status, o.doc_name, p.' . $abhaCol . ' as abha_id, p.p_fname, p.gender, p.dob', false)
            ->join('patient_master p', 'p.id = o.p_id', 'left')
            ->where('o.opd_status', 2)
            ->where('DATE(o.apointment_date) >=', date('Y-m-d', strtotime('-30 days')), false)
            ->where('p.' . $abhaCol . ' !=', '')
            ->orderBy('o.opd_id', 'DESC')
            ->limit(max(50, $limit * 3))
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return $summary;
        }

        // Filter for valid ABHA (either 14-digit number or contains @)
        $validRows = [];
        foreach ($rows as $r) {
            $rawAbha = trim((string) ($r['abha_id'] ?? ''));
            if (preg_match('/^\d{14}$/', $rawAbha) === 1 || str_contains($rawAbha, '@')) {
                $validRows[] = $r;
            }
        }

        if (empty($validRows)) {
            return $summary;
        }

        $opdIds = array_values(array_unique(array_map(static fn ($r) => (int) ($r['opd_id'] ?? 0), $validRows)));

        // 2. Fetch latest FHIR documents for these OPD records
        $docByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('opd_fhir_documents')) {
            $docRows = $this->db->table('opd_fhir_documents')
                ->select('id, opd_id, opd_session_id, bundle_type, bundle_json, generated_at')
                ->whereIn('opd_id', $opdIds)
                ->whereIn('bundle_type', ['OPConsultRecord', 'MedicationRequestBundle', 'PrescriptionRecord'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($docRows as $doc) {
                $oid = (int) ($doc['opd_id'] ?? 0);
                if ($oid > 0 && ! isset($docByOpd[$oid])) {
                    $docByOpd[$oid] = $doc;
                }
            }
        }

        // 3. Fetch existing health_records
        $hrByOpd = [];
        if (! empty($opdIds) && $this->db->tableExists('health_records')) {
            $sessionIds = [];
            foreach ($docByOpd as $d) {
                $sId = (int) ($d['opd_session_id'] ?? 0);
                if ($sId > 0) {
                    $sessionIds[] = (string) $sId;
                }
            }

            $searchEntityIds = array_merge(
                array_map(static fn ($v) => (string) $v, $opdIds),
                $sessionIds
            );

            $hrRows = $this->db->table('health_records')
                ->select('id, entity_id, push_status, abdm_txn_id, bridge_record_id, care_context_reference, push_at, linked_at, updated_at')
                ->where('entity_type', 'opd')
                ->whereIn('entity_id', $searchEntityIds)
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($hrRows as $hr) {
                $eid = (int) ($hr['entity_id'] ?? 0);
                if ($eid <= 0) {
                    continue;
                }
                if (! isset($hrByOpd[$eid])) {
                    $hrByOpd[$eid] = $hr;
                }
            }
        }

        // 4. Process each eligible row
        $processedCount = 0;
        foreach ($validRows as $row) {
            if ($processedCount >= $limit) {
                break;
            }

            $opdId = (int) ($row['opd_id'] ?? 0);
            if ($opdId <= 0) {
                continue;
            }

            $doc = $docByOpd[$opdId] ?? null;
            if ($doc === null) {
                // No FHIR bundle generated yet for this OPD
                continue;
            }

            $sessionId = (int) ($doc['opd_session_id'] ?? 0);

            // Reconcile: check if health_records row was stored under opd_id or session_id
            $hr = $hrByOpd[$opdId] ?? ($sessionId > 0 ? ($hrByOpd[$sessionId] ?? null) : null);

            // If stored under session_id, reconcile entity_id to opdId
            if ($hr !== null && (string) ($hr['entity_id'] ?? '') === (string) $sessionId && (string) $sessionId !== (string) $opdId) {
                try {
                    $this->db->table('health_records')
                        ->where('id', (int) $hr['id'])
                        ->update(['entity_id' => (string) $opdId]);
                    $hr['entity_id'] = (string) $opdId;
                } catch (\Throwable) {
                }
            }

            $pushStatus = strtolower(trim((string) ($hr['push_status'] ?? '')));

            // Already pushed/submitted/linked successfully?
            if (in_array($pushStatus, ['queued', 'linked', 'pushed'], true)) {
                $summary['skipped']++;
                continue;
            }

            $summary['eligible']++;

            $consultDate = (string) ($row['apointment_date'] ?? '');
            $visitDate = $consultDate !== '' ? date('Y-m-d', strtotime($consultDate)) : date('Y-m-d');
            $derivedCcRef = 'OPD-' . $opdId . '-S' . ($sessionId > 0 ? $sessionId : 0) . '-' . $visitDate;

            $careContextRef = trim((string) ($hr['care_context_reference'] ?? ''));
            if ($careContextRef === '') {
                $careContextRef = $derivedCcRef;
            }

            if ($dryRun) {
                $summary['details'][] = [
                    'opd_id' => $opdId,
                    'patient' => trim((string) ($row['P_name'] ?? '')),
                    'abha' => trim((string) ($row['abha_id'] ?? '')),
                    'care_context' => $careContextRef,
                    'status' => 'dry_run_eligible',
                ];
                $processedCount++;
                continue;
            }

            // Perform care context linking and push to ABDM bridge
            $pushResult = $this->linkAndPushOpdRecord($row, $doc, $careContextRef, $visitDate, $hr);
            $processedCount++;

            if ($pushResult['ok'] === 1) {
                $summary['linked']++;
                $summary['details'][] = [
                    'opd_id' => $opdId,
                    'patient' => trim((string) ($row['P_name'] ?? '')),
                    'care_context' => $careContextRef,
                    'queue_id' => $pushResult['queue_id'] ?? '',
                    'bridge_record_id' => $pushResult['bridge_record_id'] ?? null,
                    'status' => 'linked',
                ];
            } else {
                $summary['failed']++;
                $summary['details'][] = [
                    'opd_id' => $opdId,
                    'patient' => trim((string) ($row['P_name'] ?? '')),
                    'care_context' => $careContextRef,
                    'error' => $pushResult['error'] ?? 'Unknown push error',
                    'status' => 'failed',
                ];
            }
        }

        return $summary;
    }

    /**
     * Link and push a single OPD record to ABDM gateway/bridge.
     *
     * @param array<string, mixed> $opdRow
     * @param array<string, mixed> $docRow
     * @param array<string, mixed>|null $existingHr
     * @return array{ok: int, queue_id?: string|null, bridge_record_id?: int|null, error?: string}
     */
    private function linkAndPushOpdRecord(
        array $opdRow,
        array $docRow,
        string $careContextRef,
        string $visitDate,
        ?array $existingHr
    ): array {
        $opdId = (int) ($opdRow['opd_id'] ?? 0);
        $patientId = (int) ($opdRow['p_id'] ?? 0);
        $patientName = trim((string) ($opdRow['p_fname'] ?? $opdRow['P_name'] ?? ''));
        $rawAbha = trim((string) ($opdRow['abha_id'] ?? ''));
        $genderRaw = (string) ($opdRow['gender'] ?? '');
        $gender = match ((string) $genderRaw) {
            '1', 'M', 'm', 'Male' => 'M',
            '2', 'F', 'f', 'Female' => 'F',
            default => 'O',
        };

        $yearOfBirth = '';
        $dob = trim((string) ($opdRow['dob'] ?? ''));
        if ($dob !== '' && $dob !== '0000-00-00') {
            $yearOfBirth = substr($dob, 0, 4);
        }

        $abhaNumber = preg_match('/^\d{14}$/', $rawAbha) === 1 ? $rawAbha : '';
        $abhaAddress = str_contains($rawAbha, '@') ? $rawAbha : '';

        // If patient_master has abha_address separately, resolve it
        if ($abhaAddress === '' && $this->db->tableExists('patient_master')) {
            $pFields = $this->db->getFieldNames('patient_master') ?? [];
            $selectCols = array_values(array_intersect(['abha_address', 'abha_id', 'abha_number', 'abha_no', 'abha'], $pFields));
            if (! empty($selectCols)) {
                $pm = $this->db->table('patient_master')
                    ->select(implode(', ', $selectCols))
                    ->where('id', $patientId)
                    ->get(1)
                    ->getRowArray();
                if (! empty($pm)) {
                    if (! empty($pm['abha_address'])) {
                        $abhaAddress = trim((string) $pm['abha_address']);
                    }
                    foreach (['abha_number', 'abha_id', 'abha_no', 'abha'] as $c) {
                        if ($abhaNumber === '' && ! empty($pm[$c]) && preg_match('/^\d{14}$/', trim((string) $pm[$c]))) {
                            $abhaNumber = trim((string) $pm[$c]);
                        }
                    }
                }
            }
        }

        $effectiveAbha = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        if ($effectiveAbha === '') {
            return ['ok' => 0, 'error' => 'No valid ABHA found for patient #' . $patientId];
        }

        $bundleJson = (string) ($docRow['bundle_json'] ?? '{}');
        $bundle = json_decode($bundleJson, true);
        if (! is_array($bundle)) {
            $bundle = ['raw' => $bundleJson];
        }

        $bundleType = trim((string) ($docRow['bundle_type'] ?? 'OPConsultRecord'));
        $hiType = match ($bundleType) {
            'PrescriptionRecord' => 'PrescriptionRecord',
            default => 'OPConsultRecord',
        };
        $careContextDisplay = $hiType === 'PrescriptionRecord'
            ? 'Prescription - ' . $visitDate
            : 'Consultation Record - ' . $visitDate;

        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        // 1. Upsert into health_records
        $healthRecordId = (int) ($existingHr['id'] ?? 0);
        $hrData = [
            'patient_id'             => $patientId,
            'abha_id'                => $effectiveAbha,
            'hi_type'                => $hiType,
            'entity_type'            => 'opd',
            'entity_id'              => (string) $opdId,
            'care_context_reference' => $careContextRef,
            'record_data'            => $bundleJson,
            'push_status'            => 'pending',
            'updated_at'             => $now,
        ];

        try {
            if ($healthRecordId > 0) {
                $this->db->table('health_records')->where('id', $healthRecordId)->update($hrData);
            } else {
                $hrData['created_at'] = $now;
                $this->db->table('health_records')->insert($hrData);
                $healthRecordId = (int) $this->db->insertID();
            }
        } catch (\Throwable $e) {
            log_message('error', "[AbdmTaskBoardSync] health_records upsert error: " . $e->getMessage());
        }

        // 2. Push to ABDM Bridge Gateway
        $pushData = [
            'patient_id'             => (string) $patientId,
            'patient_name'           => $patientName !== '' ? $patientName : ('PATIENT-' . $patientId),
            'abha_id'                => $abhaNumber,
            'abha_address'           => $abhaAddress,
            'year_of_birth'          => $yearOfBirth,
            'gender'                 => $gender,
            'hi_type'                => $hiType,
            'record_type'            => $hiType,
            'visit_date'             => $visitDate,
            'care_context_reference' => $careContextRef,
            'care_context_display'   => $careContextDisplay,
            'notes'                  => $careContextDisplay,
            'queue_id'               => $careContextRef,
            'record_data'            => $bundle,
            'fhir_bundle'            => $bundle,
        ];

        $queueId = null;
        $bridgeRecordId = 0;
        $connectorError = null;
        $isSuccess = false;

        try {
            $result = $this->connector->pushRecord($pushData);

            $okVal = (int) ($result['ok'] ?? 0);
            $httpCode = (int) ($result['http_code'] ?? 0);
            $statusVal = strtolower((string) ($result['status'] ?? ''));

            if ($okVal === 1 || in_array($httpCode, [200, 201, 202, 409], true) || in_array($statusVal, ['queued', 'pushed', 'linked', 'duplicate'], true)) {
                $isSuccess = true;
                $queueId = (string) ($result['queue_id'] ?? $result['request_id'] ?? $careContextRef);
                $bridgeRecordId = (int) ($result['record_id'] ?? $result['id'] ?? 0);
            } else {
                $connectorError = trim((string) ($result['message'] ?? $result['error_text'] ?? $result['error'] ?? 'Bridge push failed'));
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
            log_message('warning', "[AbdmTaskBoardSync] Network exception pushing OPD #{$opdId}: " . $connectorError);
        }

        // 3. Update health_records with final push status
        if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
            $hrUpdate = [
                'push_status' => $isSuccess ? 'queued' : 'failed',
                'updated_at'  => $now,
            ];
            if ($queueId !== null && $queueId !== '') {
                $hrUpdate['abdm_txn_id'] = $queueId;
            }
            if ($bridgeRecordId > 0) {
                $hrFields = $this->db->getFieldNames('health_records') ?? [];
                if (in_array('bridge_record_id', $hrFields, true)) {
                    $hrUpdate['bridge_record_id'] = $bridgeRecordId;
                }
            }
            if ($isSuccess) {
                $hrFields = $this->db->getFieldNames('health_records') ?? [];
                if (in_array('push_at', $hrFields, true)) {
                    $hrUpdate['push_at'] = $now;
                }
            }

            try {
                $this->db->table('health_records')->where('id', $healthRecordId)->update($hrUpdate);
            } catch (\Throwable) {
            }
        }

        // 4. Update record_links
        if ($this->db->tableExists('record_links')) {
            try {
                $existingLink = $this->db->table('record_links')
                    ->where('care_context_reference', $careContextRef)
                    ->get(1)
                    ->getRowArray();

                $linkData = [
                    'abha_id'                => $effectiveAbha,
                    'care_context_reference' => $careContextRef,
                    'health_record_id'       => $healthRecordId > 0 ? $healthRecordId : null,
                    'abdm_txn_id'            => $queueId !== '' ? $queueId : null,
                    'link_status'            => $isSuccess ? 'pending' : 'failed',
                    'updated_at'             => $now,
                ];

                if (! empty($existingLink)) {
                    $this->db->table('record_links')->where('id', (int) $existingLink['id'])->update($linkData);
                } else {
                    $linkData['created_at'] = $now;
                    $this->db->table('record_links')->insert($linkData);
                }
            } catch (\Throwable) {
            }
        }

        // 5. Complete open work task if any exists
        if ($isSuccess && $this->db->tableExists('abdm_work_tasks')) {
            try {
                $this->db->table('abdm_work_tasks')
                    ->where('task_type', 'opd_prescription_publish')
                    ->where('entity_id', (string) $opdId)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->update([
                        'status'             => 'completed',
                        'last_action_result' => 'Linked by cron. Queue: ' . ($queueId ?? '-') . ' Bridge: #' . $bridgeRecordId,
                        'completed_at'       => $now,
                        'updated_at'         => $now,
                    ]);
            } catch (\Throwable) {
            }
        }

        // 6. Log API activity
        if ($this->db->tableExists('abdm_api_logs')) {
            try {
                $this->db->table('abdm_api_logs')->insert([
                    'channel'       => 'bridge',
                    'event_type'    => 'abdm.opd.prescription.share.result',
                    'endpoint'      => '/api/v3/records/push',
                    'http_method'   => 'POST',
                    'entity_type'   => 'opd',
                    'entity_id'     => (string) $opdId,
                    'status'        => $isSuccess ? 'success' : 'error',
                    'error_message' => $connectorError !== null ? mb_substr($connectorError, 0, 1000) : null,
                    'created_at'    => $now,
                ]);
            } catch (\Throwable) {
            }
        }

        if ($isSuccess) {
            return [
                'ok'               => 1,
                'queue_id'         => $queueId,
                'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            ];
        }

        return [
            'ok'    => 0,
            'error' => $connectorError ?? 'Bridge push failed',
        ];
    }

    /**
     * Process open tasks on the ABDM Work Task Board (Lab, Radiology, Immunization, Wellness, Health Docs, Discharge).
     *
     * @return array{
     *     eligible: int,
     *     linked: int,
     *     failed: int,
     *     skipped: int,
     *     details: array<int, array<string, mixed>>
     * }
     */
    public function syncOpenWorkTasks(int $limit = 20, bool $dryRun = false): array
    {
        $summary = [
            'eligible' => 0,
            'linked'   => 0,
            'failed'   => 0,
            'skipped'  => 0,
            'details'  => [],
        ];

        if (! $this->db->tableExists('abdm_work_tasks')) {
            return $summary;
        }

        $supportedTaskTypes = [
            'opd_prescription_publish',
            'lab_report_publish',
            'radiology_report_publish',
            'immunization_record_publish',
            'wellness_record_publish',
            'health_document_publish',
            'ipd_discharge_publish',
        ];

        $tasks = $this->db->table('abdm_work_tasks')
            ->whereIn('task_type', $supportedTaskTypes)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('id', 'ASC')
            ->limit(max(1, $limit))
            ->get()
            ->getResultArray();

        if (empty($tasks)) {
            return $summary;
        }

        $summary['eligible'] = count($tasks);

        if ($dryRun) {
            foreach ($tasks as $t) {
                $summary['details'][] = [
                    'task_id'   => (int) $t['id'],
                    'task_type' => $t['task_type'],
                    'entity_id' => $t['entity_id'],
                    'patient'   => $t['patient_name'] ?? '',
                    'status'    => 'dry_run_eligible',
                ];
            }
            return $summary;
        }

        foreach ($tasks as $task) {
            $taskId = (int) $task['id'];
            $taskType = (string) $task['task_type'];
            $entityId = (int) ($task['entity_id'] ?? 0);
            $patientId = (int) ($task['patient_id'] ?? 0);
            $abhaId = trim((string) ($task['abha_id'] ?? ''));

            $result = $this->processIndividualWorkTask($task);

            if ($result['ok'] === 1) {
                $summary['linked']++;
                $this->taskService->markTaskStatus(
                    $taskId,
                    'completed',
                    'Linked by cron: ' . ($result['queue_id'] ?? 'done')
                );
                $summary['details'][] = [
                    'task_id'   => $taskId,
                    'task_type' => $taskType,
                    'entity_id' => $entityId,
                    'status'    => 'linked',
                    'queue_id'  => $result['queue_id'] ?? '',
                ];
            } else {
                $summary['failed']++;
                $this->taskService->markTaskStatus(
                    $taskId,
                    'pending', // remain pending for retry on next cron tick
                    'Cron retry pending: ' . ($result['error'] ?? 'Sync failed')
                );
                $summary['details'][] = [
                    'task_id'   => $taskId,
                    'task_type' => $taskType,
                    'entity_id' => $entityId,
                    'status'    => 'failed',
                    'error'     => $result['error'] ?? 'Sync failed',
                ];
            }
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $task
     * @return array{ok: int, queue_id?: string|null, error?: string}
     */
    private function processIndividualWorkTask(array $task): array
    {
        $taskType = (string) ($task['task_type'] ?? '');
        $entityId = (int) ($task['entity_id'] ?? 0);
        $patientId = (int) ($task['patient_id'] ?? 0);
        $abhaId = trim((string) ($task['abha_id'] ?? ''));

        if ($entityId <= 0 || $patientId <= 0) {
            return ['ok' => 0, 'error' => 'Invalid task entity or patient'];
        }

        // If it's an OPD prescription publish task, delegate to OPD consult push
        if ($taskType === 'opd_prescription_publish') {
            $docRow = null;
            if ($this->db->tableExists('opd_fhir_documents')) {
                $docRow = $this->db->table('opd_fhir_documents')
                    ->where('opd_id', $entityId)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
            }
            if (empty($docRow)) {
                return ['ok' => 0, 'error' => 'No FHIR bundle for OPD #' . $entityId];
            }

            $opdRow = $this->db->table('opd_master')->where('opd_id', $entityId)->get(1)->getRowArray();
            if (empty($opdRow)) {
                return ['ok' => 0, 'error' => 'OPD record not found'];
            }

            $opdRow['abha_id'] = $abhaId;
            $visitDate = ! empty($opdRow['apointment_date']) ? date('Y-m-d', strtotime((string) $opdRow['apointment_date'])) : date('Y-m-d');
            $ccRef = 'OPD-' . $entityId . '-S' . ($docRow['opd_session_id'] ?? 0) . '-' . $visitDate;

            return $this->linkAndPushOpdRecord($opdRow, $docRow, $ccRef, $visitDate, null);
        }

        // Other record types: map and push care context
        $hiType = match ($taskType) {
            'lab_report_publish', 'radiology_report_publish' => 'DiagnosticReportRecord',
            'immunization_record_publish' => 'ImmunizationRecord',
            'wellness_record_publish' => 'WellnessRecord',
            'health_document_publish' => 'HealthDocumentRecord',
            'ipd_discharge_publish' => 'DischargeSummaryRecord',
            default => 'HealthDocumentRecord',
        };

        $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray();
        $patientName = trim((string) ($patientRow['p_fname'] ?? $task['patient_name'] ?? ''));
        $gender = match ((string) ($patientRow['gender'] ?? '')) {
            '1', 'M', 'm', 'Male' => 'M',
            '2', 'F', 'f', 'Female' => 'F',
            default => 'O',
        };
        $dob = trim((string) ($patientRow['dob'] ?? ''));
        $yearOfBirth = ($dob !== '' && $dob !== '0000-00-00') ? substr($dob, 0, 4) : '';

        $abhaNumber = preg_match('/^\d{14}$/', $abhaId) === 1 ? $abhaId : '';
        $abhaAddress = str_contains($abhaId, '@') ? $abhaId : '';
        if ($abhaAddress === '' && ! empty($patientRow['abha_address'])) {
            $abhaAddress = trim((string) $patientRow['abha_address']);
        }
        if ($abhaNumber === '' && ! empty($patientRow['abha_id']) && preg_match('/^\d{14}$/', trim((string) $patientRow['abha_id']))) {
            $abhaNumber = trim((string) $patientRow['abha_id']);
        }

        $visitDate = date('Y-m-d');
        $prefix = match ($taskType) {
            'lab_report_publish', 'radiology_report_publish' => 'LAB-',
            'immunization_record_publish' => 'IMM-',
            'wellness_record_publish' => 'WELLNESS-',
            'health_document_publish' => 'DOC-',
            'ipd_discharge_publish' => 'IPD-',
            default => 'REC-',
        };
        $careContextRef = $prefix . $entityId . '-' . $visitDate;
        $careContextDisplay = $hiType . ' ' . $visitDate;

        $pushData = [
            'patient_id'             => (string) $patientId,
            'patient_name'           => $patientName !== '' ? $patientName : ('PATIENT-' . $patientId),
            'abha_id'                => $abhaNumber,
            'abha_address'           => $abhaAddress,
            'year_of_birth'          => $yearOfBirth,
            'gender'                 => $gender,
            'hi_type'                => $hiType,
            'record_type'            => $hiType,
            'visit_date'             => $visitDate,
            'care_context_reference' => $careContextRef,
            'care_context_display'   => $careContextDisplay,
            'notes'                  => $careContextDisplay,
            'queue_id'               => $careContextRef,
        ];

        try {
            $result = $this->connector->pushRecord($pushData);
            $ok = (int) ($result['ok'] ?? 0);
            $httpCode = (int) ($result['http_code'] ?? 0);
            $statusVal = strtolower((string) ($result['status'] ?? ''));

            if ($ok === 1 || in_array($httpCode, [200, 201, 202, 409], true) || in_array($statusVal, ['queued', 'pushed', 'linked', 'duplicate'], true)) {
                $queueId = (string) ($result['queue_id'] ?? $careContextRef);

                // Upsert health_records & record_links
                $this->recordLinkedState(
                    $patientId,
                    $abhaAddress !== '' ? $abhaAddress : $abhaNumber,
                    $hiType,
                    $taskType,
                    (string) $entityId,
                    $careContextRef,
                    $queueId,
                    (int) ($result['record_id'] ?? 0)
                );

                return ['ok' => 1, 'queue_id' => $queueId];
            }

            return ['ok' => 0, 'error' => (string) ($result['message'] ?? $result['error_text'] ?? 'Push failed')];
        } catch (\Throwable $e) {
            return ['ok' => 0, 'error' => $e->getMessage()];
        }
    }

    private function recordLinkedState(
        int $patientId,
        string $abhaId,
        string $hiType,
        string $entityType,
        string $entityId,
        string $careContextRef,
        string $queueId,
        int $bridgeRecordId
    ): void {
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        if ($this->db->tableExists('health_records')) {
            try {
                $existing = $this->db->table('health_records')
                    ->where('care_context_reference', $careContextRef)
                    ->get(1)
                    ->getRowArray();

                $hrData = [
                    'patient_id'             => $patientId,
                    'abha_id'                => $abhaId,
                    'hi_type'                => $hiType,
                    'entity_type'            => $entityType,
                    'entity_id'              => $entityId,
                    'care_context_reference' => $careContextRef,
                    'push_status'            => 'queued',
                    'abdm_txn_id'            => $queueId,
                    'updated_at'             => $now,
                ];
                if ($bridgeRecordId > 0 && in_array('bridge_record_id', $this->db->getFieldNames('health_records') ?? [], true)) {
                    $hrData['bridge_record_id'] = $bridgeRecordId;
                }

                if (! empty($existing)) {
                    $this->db->table('health_records')->where('id', (int) $existing['id'])->update($hrData);
                    $hrId = (int) $existing['id'];
                } else {
                    $hrData['created_at'] = $now;
                    $this->db->table('health_records')->insert($hrData);
                    $hrId = (int) $this->db->insertID();
                }

                if ($this->db->tableExists('record_links')) {
                    $this->db->table('record_links')->insert([
                        'health_record_id'       => $hrId,
                        'care_context_reference' => $careContextRef,
                        'abdm_txn_id'            => $queueId,
                        'abha_id'                => $abhaId,
                        'link_status'            => 'pending',
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);
                }
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @param string[] $fields
     * @param string[] $candidates
     */
    private function resolveFirstExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
