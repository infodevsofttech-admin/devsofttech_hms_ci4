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
            'cooling'  => 0,
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
                ->orderBy("CASE WHEN bundle_type = 'OPConsultRecord' THEN 1 ELSE 2 END", 'ASC', false)
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

            $consultDate = (string) ($row['apointment_date'] ?? '');
            $visitDate = $consultDate !== '' ? date('Y-m-d', strtotime($consultDate)) : date('Y-m-d');
            $cleanDate = str_replace('-', '', $visitDate);
            $derivedCcRef = 'OPD-' . $patientId . '-S' . ($sessionId > 0 ? $sessionId : 0) . '-' . $cleanDate;

            $careContextRef = trim((string) ($hr['care_context_reference'] ?? ''));
            if ($careContextRef === '') {
                $careContextRef = $derivedCcRef;
            }

            // Check cooling period from the latest modification/generation timestamp
            $docTimestamps = array_filter([
                ! empty($doc['updated_at']) ? (string) $doc['updated_at'] : null,
                ! empty($doc['generated_at']) ? (string) $doc['generated_at'] : null,
                ! empty($hr['updated_at']) ? (string) $hr['updated_at'] : null,
                ! empty($doc['created_at']) ? (string) $doc['created_at'] : null,
            ]);
            $lastModified = ! empty($docTimestamps) ? max($docTimestamps) : ($row['apointment_date'] ?? date('Y-m-d H:i:s'));

            $cooling = self::calculateCooling('opd_prescription_publish', $lastModified);
            if ($cooling['is_cooling_active']) {
                $summary['cooling']++;
                $summary['details'][] = [
                    'opd_id'            => $opdId,
                    'patient'           => trim((string) ($row['P_name'] ?? '')),
                    'abha'              => trim((string) ($row['abha_id'] ?? '')),
                    'care_context'      => $careContextRef,
                    'last_modified'     => $lastModified,
                    'remaining_minutes' => $cooling['remaining_minutes'],
                    'auto_link_at'      => $cooling['auto_link_at'],
                    'status'            => 'cooling',
                ];
                continue;
            }

            $summary['eligible']++;

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
    /**
     * Resolve comprehensive demographics (name, gender, year_of_birth, abha_address, abha_number)
     * strictly and reliably from patient_master.
     *
     * @return array{patient_name: string, gender: string, year_of_birth: string, abha_address: string, abha_number: string, effective_abha: string}
     */
    private function resolvePatientMasterDemographics(int $patientId, string $fallbackName = '', string $fallbackAbha = ''): array
    {
        $patientRow = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }

        $patientName = trim((string) ($patientRow['p_fname'] ?? $fallbackName));
        if ($patientName === '') {
            $patientName = 'PATIENT-' . $patientId;
        }

        $genderRaw = (string) ($patientRow['gender'] ?? '');
        $gender = match ((string) $genderRaw) {
            '1', 'M', 'm', 'Male' => 'M',
            '2', 'F', 'f', 'Female' => 'F',
            default => 'O',
        };

        // Resolve Year of Birth (from DOB, Age, or ABHA regex)
        $yearOfBirth = '';
        $dob = trim((string) ($patientRow['dob'] ?? ''));
        if ($dob !== '' && $dob !== '0000-00-00' && ! str_starts_with($dob, '0000')) {
            $yearOfBirth = substr($dob, 0, 4);
        } elseif (! empty($patientRow['age']) && (int) $patientRow['age'] > 0) {
            $yearOfBirth = (string) ((int) date('Y') - (int) $patientRow['age']);
        }

        // Resolve ABHA Address and Number
        $abhaAddress = '';
        $abhaNumber = '';
        $candidates = [
            trim((string) ($patientRow['abha_address'] ?? '')),
            trim($fallbackAbha),
            trim((string) ($patientRow['abha_id'] ?? '')),
            trim((string) ($patientRow['abha_no'] ?? '')),
            trim((string) ($patientRow['abha'] ?? '')),
        ];

        foreach ($candidates as $cand) {
            if ($cand === '') {
                continue;
            }
            if ($abhaAddress === '' && str_contains($cand, '@')) {
                $abhaAddress = $cand;
            }
            $clean14 = preg_replace('/\D/', '', $cand);
            if ($abhaNumber === '' && strlen($clean14) === 14) {
                $abhaNumber = $clean14;
            }
        }

        // If YOB is still empty, try extracting 4-digit year from ABHA address or number
        if ($yearOfBirth === '' && ($abhaAddress !== '' || $abhaNumber !== '')) {
            $src = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
            if (preg_match('/(19\d{2}|20\d{2})/', $src, $m) === 1) {
                $yearOfBirth = $m[1];
            }
        }

        $effectiveAbha = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;

        return [
            'name'           => $patientName,
            'patient_name'   => $patientName,
            'gender'         => $gender,
            'year_of_birth'  => $yearOfBirth,
            'abha_address'   => $abhaAddress,
            'abha_number'    => $abhaNumber,
            'effective_abha' => $effectiveAbha,
        ];
    }

    /**
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
        $rawAbha = trim((string) ($opdRow['abha_id'] ?? ''));
        $rawName = trim((string) ($opdRow['p_fname'] ?? $opdRow['P_name'] ?? ''));

        // Always resolve full patient demographics from patient_master
        $demo = $this->resolvePatientMasterDemographics($patientId, $rawName, $rawAbha);
        $patientName = $demo['patient_name'];
        $gender = $demo['gender'];
        $yearOfBirth = $demo['year_of_birth'];
        $abhaAddress = $demo['abha_address'];
        $abhaNumber = $demo['abha_number'];
        $effectiveAbha = $demo['effective_abha'];

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
        if ($healthRecordId <= 0) {
            $foundHr = $this->db->table('health_records')
                ->select('id')
                ->where('care_context_reference', $careContextRef)
                ->get(1)
                ->getRowArray();
            if (! empty($foundHr['id'])) {
                $healthRecordId = (int) $foundHr['id'];
            }
        }
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

        // In ABDM standard, OPConsultRecord is a single unified bundle that already includes
        // consultation note, physical examination (wellness/vitals), prescriptions (medications),
        // and attached health documents. We intentionally do NOT create duplicate care contexts.

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
            'cooling'  => 0,
            'failed'   => 0,
            'skipped'  => 0,
            'details'  => [],
        ];

        if (! $this->db->tableExists('abdm_work_tasks')) {
            return $summary;
        }

        // Auto-discover any newly completed clinical records with ABHA
        $this->backfillMissingWorkTasks(30, 100);

        $supportedTaskTypes = [
            'opd_prescription_publish',
            'lab_report_publish',
            'radiology_report_publish',
            'immunization_record_publish',
            'wellness_record_publish',
            'health_document_publish',
            'ipd_discharge_publish',
        ];

        // Fetch candidate batch wider than $limit to bypass cooling or retrying items without queue starvation
        $tasks = $this->db->table('abdm_work_tasks')
            ->whereIn('task_type', $supportedTaskTypes)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('id', 'ASC')
            ->limit(max(60, $limit * 3))
            ->get()
            ->getResultArray();

        if (empty($tasks)) {
            return $summary;
        }

        $processedCount = 0;

        foreach ($tasks as $task) {
            $taskId = (int) $task['id'];
            $taskType = (string) $task['task_type'];
            $entityId = (int) ($task['entity_id'] ?? 0);
            $patientId = (int) ($task['patient_id'] ?? 0);
            $abhaId = trim((string) ($task['abha_id'] ?? ''));

            // Check cooling period from the actual clinical event/modification timestamp
            $lastModified = self::resolveTaskClinicalTimestamp($task, $this->db);
            $cooling = self::calculateCooling($taskType, $lastModified);

            if ($cooling['is_cooling_active']) {
                $summary['cooling']++;
                $summary['details'][] = [
                    'task_id'           => $taskId,
                    'task_type'         => $taskType,
                    'entity_id'         => $entityId,
                    'patient'           => $task['patient_name'] ?? '',
                    'last_modified'     => $lastModified,
                    'remaining_minutes' => $cooling['remaining_minutes'],
                    'auto_link_at'      => $cooling['auto_link_at'],
                    'status'            => 'cooling',
                ];
                continue;
            }

            if ($dryRun) {
                $summary['eligible']++;
                $summary['details'][] = [
                    'task_id'   => $taskId,
                    'task_type' => $taskType,
                    'entity_id' => $entityId,
                    'patient'   => $task['patient_name'] ?? '',
                    'status'    => 'dry_run_eligible',
                ];
                $processedCount++;
                if ($processedCount >= $limit) {
                    break;
                }
                continue;
            }

            $summary['eligible']++;
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
                $payload = ! empty($task['payload_json']) ? json_decode((string) $task['payload_json'], true) : [];
                $retryCount = (int) ($payload['retry_count'] ?? 0) + 1;
                $payload['retry_count'] = $retryCount;
                $payload['last_error'] = (string) ($result['error'] ?? 'Sync failed');
                $payload['last_failed_at'] = Time::now('Asia/Kolkata')->toDateTimeString();

                $errJson = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $this->db->table('abdm_work_tasks')->where('id', $taskId)->update(['payload_json' => $errJson]);

                if ($retryCount >= 5) {
                    $this->taskService->markTaskStatus(
                        $taskId,
                        'failed',
                        'Max cron retries (5) exceeded: ' . ($result['error'] ?? 'Sync failed')
                    );
                } else {
                    $this->taskService->markTaskStatus(
                        $taskId,
                        'pending', // remain pending for retry on next cron tick
                        'Cron retry #' . $retryCount . ' pending: ' . ($result['error'] ?? 'Sync failed')
                    );
                }

                $summary['details'][] = [
                    'task_id'   => $taskId,
                    'task_type' => $taskType,
                    'entity_id' => $entityId,
                    'status'    => 'failed',
                    'error'     => $result['error'] ?? 'Sync failed',
                ];
            }

            $processedCount++;
            if ($processedCount >= $limit) {
                break;
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
                    ->orderBy("CASE WHEN bundle_type = 'OPConsultRecord' THEN 1 ELSE 2 END", 'ASC', false)
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
            $cleanDate = str_replace('-', '', $visitDate);
            $targetPatientId = (int) ($opdRow['p_id'] ?? $patientId);
            $targetSessionId = (int) ($docRow['opd_session_id'] ?? 0);
            $ccRef = 'OPD-' . $targetPatientId . '-S' . $targetSessionId . '-' . $cleanDate;

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

        $demo = $this->resolvePatientMasterDemographics($patientId, (string) ($task['patient_name'] ?? ''), $abhaId);
        $patientName = $demo['patient_name'];
        $gender = $demo['gender'];
        $yearOfBirth = $demo['year_of_birth'];
        $abhaAddress = $demo['abha_address'];
        $abhaNumber = $demo['abha_number'];

        $clinTs = self::resolveTaskClinicalTimestamp($task, $this->db);
        $visitDate = ! empty($clinTs) && strtotime($clinTs) > 0 ? date('Y-m-d', strtotime($clinTs)) : date('Y-m-d');
        $prefix = match ($taskType) {
            'radiology_report_publish'    => 'RAD-',
            'lab_report_publish'          => 'LAB-',
            'immunization_record_publish' => 'IMM-',
            'wellness_record_publish'     => 'WELLNESS-',
            'health_document_publish'     => 'DOC-',
            'ipd_discharge_publish'       => 'DISCHARGE-',
            default => 'REC-',
        };
        $cleanVisitDate = str_replace('-', '', $visitDate);
        $careContextRef = $prefix . $entityId . '-' . $cleanVisitDate;
        if ($taskType === 'immunization_record_publish' && $this->db->tableExists('immunization_records')) {
            $immRec = $this->db->table('immunization_records')->select('abdm_care_context_reference')->where('id', (int) $entityId)->get(1)->getRowArray();
            if (! empty($immRec['abdm_care_context_reference'])) {
                $careContextRef = trim((string) $immRec['abdm_care_context_reference']);
            }
        }
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

        $bundleJson = null;
        if ($taskType === 'wellness_record_publish' && class_exists('\App\Controllers\DoctorDocument')) {
            try {
                $docCtrl = new \App\Controllers\DoctorDocument();
                $wSource = $docCtrl->buildWellnessRecordSource($patientId, $entityId);
                if (! empty($wSource)) {
                    $wFactory = new \App\Libraries\Abdm\Fhir\FhirGeneratorFactory();
                    $wGen = $wFactory->wellness()->generate($wSource);
                    if (! empty($wGen['bundle'])) {
                        $pushData['bundle'] = $wGen['bundle'];
                        $pushData['fhir_bundle'] = $wGen['bundle'];
                        $bundleJson = (string) json_encode($wGen['bundle'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            } catch (\Throwable $we) {
                log_message('warning', '[processIndividualWorkTask] Wellness bundle generation error: ' . $we->getMessage());
            }
        }

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
                    (int) ($result['record_id'] ?? 0),
                    $bundleJson
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
        int $bridgeRecordId,
        ?string $recordData = null
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
                if ($recordData !== null && $recordData !== '') {
                    $hrData['record_data'] = $recordData;
                }
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

    /**
     * Calculate cooling period state for any task or document.
     *
     * Rules:
     * - IPD Discharge Summary: 24 hours from last modified/printed time.
     * - All other HI types (OPD Consult/Prescription, Lab, Radiology, Wellness, Immunization, etc.):
     *   60 minutes from last modified/updated/printed time.
     * - Any modification/update in HMS refreshes the timestamp, resetting the cooling period.
     *
     * @param string $taskType Task type, HI type, or entity name
     * @param string|null $lastModified DateTime string of the last update/generation
     * @return array{
     *     is_cooling_active: bool,
     *     remaining_seconds: int,
     *     remaining_minutes: int,
     *     required_seconds: int,
     *     last_modified: string,
     *     auto_link_at: string
     * }
     */
    public static function calculateCooling(string $taskType, ?string $lastModified): array
    {
        $cfg = config('AbdmConnector');
        $autoLinkEnabled = (bool) ($cfg->autoLinkEnabled ?? true);
        if (! $autoLinkEnabled) {
            return [
                'is_cooling_active' => true,
                'cooling_active'    => true,
                'remaining_seconds' => PHP_INT_MAX,
                'remaining_minutes' => PHP_INT_MAX,
                'required_seconds'  => PHP_INT_MAX,
                'last_modified'     => (string) $lastModified,
                'auto_link_at'      => 'disabled',
            ];
        }

        $typeLower = strtolower(trim($taskType));
        if (in_array($typeLower, ['ipd_discharge_publish', 'ipd_discharge', 'dischargesummaryrecord', 'ipd'], true)) {
            $hours = max(1, (int) ($cfg->autoLinkDelayDischargeHours ?? 24));
            $requiredSeconds = $hours * 3600;
        } else {
            $minutes = max(1, (int) ($cfg->autoLinkDelayMinutes ?? 60));
            $requiredSeconds = $minutes * 60;
        }

        $lastTimestamp = ! empty($lastModified) ? strtotime($lastModified) : 0;
        if ($lastTimestamp <= 0) {
            $lastTimestamp = time();
        }

        $now = time();
        $elapsedSeconds = max(0, $now - $lastTimestamp);
        $remainingSeconds = max(0, $requiredSeconds - $elapsedSeconds);
        $isCoolingActive = ($remainingSeconds > 0);
        $remainingMinutes = (int) ceil($remainingSeconds / 60);
        $autoLinkAt = date('Y-m-d H:i:s', $lastTimestamp + $requiredSeconds);

        return [
            'is_cooling_active' => $isCoolingActive,
            'cooling_active'    => $isCoolingActive,
            'remaining_seconds' => $remainingSeconds,
            'remaining_minutes' => $remainingMinutes,
            'required_seconds'  => $requiredSeconds,
            'last_modified'     => (string) $lastModified,
            'auto_link_at'      => $autoLinkAt,
        ];
    }

    /**
     * Instance wrapper for calculateCooling.
     *
     * @return array{
     *     is_cooling_active: bool,
     *     remaining_seconds: int,
     *     remaining_minutes: int,
     *     required_seconds: int,
     *     last_modified: string,
     *     auto_link_at: string
     * }
     */
    public function resolveCoolingState(string $taskType, ?string $lastModified): array
    {
        return self::calculateCooling($taskType, $lastModified);
    }

    /**
     * Resolves the actual clinical / event timestamp for a task to accurately measure cooling.
     * Checks task payload meta, underlying clinical record tables, and falls back to created_at.
     *
     * @param array<string, mixed> $task
     */
    public static function resolveTaskClinicalTimestamp(array $task, ?BaseConnection $db = null): string
    {
        $payload = ! empty($task['payload_json']) ? json_decode((string) $task['payload_json'], true) : [];
        $meta = is_array($payload) ? ($payload['meta'] ?? []) : [];

        // 1. Direct meta timestamps
        foreach (['clinical_timestamp', 'event_date', 'reported_time', 'collected_time', 'given_date', 'date_opd_visit', 'discharge_date'] as $key) {
            if (! empty($meta[$key]) && strtotime((string) $meta[$key]) > 0) {
                return (string) $meta[$key];
            }
        }

        $taskType = (string) ($task['task_type'] ?? '');
        $entityType = (string) ($task['entity_type'] ?? '');
        $entityId = (int) ($task['entity_id'] ?? 0);

        if ($entityId > 0) {
            try {
                $dbConn = $db ?? db_connect();

                // 2. Health Documents: file_upload_data or patient_doc
                if (in_array($entityType, ['patient_document', 'file_upload_data'], true) || $taskType === 'health_document_publish') {
                    if ($dbConn->tableExists('file_upload_data')) {
                        $fRow = $dbConn->table('file_upload_data')->select('insert_date')->where('id', $entityId)->get(1)->getRowArray();
                        if (! empty($fRow['insert_date']) && strtotime((string) $fRow['insert_date']) > 0) {
                            return (string) $fRow['insert_date'];
                        }
                    }
                    if ($dbConn->tableExists('patient_doc')) {
                        $pRow = $dbConn->table('patient_doc')->select('created_at, date_issue')->where('id', $entityId)->get(1)->getRowArray();
                        $date = ! empty($pRow['created_at']) ? $pRow['created_at'] : ($pRow['date_issue'] ?? null);
                        if (! empty($date) && strtotime((string) $date) > 0) {
                            return (string) $date;
                        }
                    }
                }

                // 3. Lab / Radiology: lab_request
                if ($entityType === 'lab_request' || in_array($taskType, ['lab_report_publish', 'radiology_report_publish'], true)) {
                    if ($dbConn->tableExists('lab_request')) {
                        $fields = $dbConn->getFieldNames('lab_request');
                        $selectCols = array_intersect(['reported_time', 'collected_time', 'Request_Date'], $fields);
                        if (! empty($selectCols)) {
                            $lRow = $dbConn->table('lab_request')->select(implode(', ', $selectCols))->where('id', $entityId)->get(1)->getRowArray();
                            foreach (['reported_time', 'collected_time', 'Request_Date'] as $col) {
                                if (! empty($lRow[$col]) && strtotime((string) $lRow[$col]) > 0) {
                                    return (string) $lRow[$col];
                                }
                            }
                        }
                    }
                }

                // 4. Immunization: immunization_records
                if ($entityType === 'immunization' || $taskType === 'immunization_record_publish') {
                    if ($dbConn->tableExists('immunization_records')) {
                        $iRow = $dbConn->table('immunization_records')->select('given_date, created_at')->where('id', $entityId)->get(1)->getRowArray();
                        $date = ! empty($iRow['given_date']) ? $iRow['given_date'] : ($iRow['created_at'] ?? null);
                        if (! empty($date) && strtotime((string) $date) > 0) {
                            return (string) $date;
                        }
                    }
                }

                // 5. Wellness: opd_prescription
                if ($entityType === 'opd_prescription' || $entityType === 'opd_vitals' || $taskType === 'wellness_record_publish') {
                    if ($dbConn->tableExists('opd_prescription')) {
                        $fields = $dbConn->getFieldNames('opd_prescription');
                        $dateCol = in_array('date_opd_visit', $fields, true) ? 'date_opd_visit' : 'id';
                        $rxRow = $dbConn->table('opd_prescription')->select($dateCol)->where('id', $entityId)->get(1)->getRowArray();
                        if (! empty($rxRow['date_opd_visit']) && strtotime((string) $rxRow['date_opd_visit']) > 0) {
                            return (string) $rxRow['date_opd_visit'];
                        }
                    }
                }

                // 6. IPD Discharge: ipd_discharge
                if ($entityType === 'ipd_discharge' || $taskType === 'ipd_discharge_publish') {
                    if ($dbConn->tableExists('ipd_discharge')) {
                        $dRow = $dbConn->table('ipd_discharge')->select('discharge_date, created_at')->where('id', $entityId)->get(1)->getRowArray();
                        $date = ! empty($dRow['discharge_date']) ? $dRow['discharge_date'] : ($dRow['created_at'] ?? null);
                        if (! empty($date) && strtotime((string) $date) > 0) {
                            return (string) $date;
                        }
                    }
                }
            } catch (\Throwable) {
                // Silently fallback
            }
        }

        // Fallback to task's created_at, or updated_at
        return ! empty($task['created_at'])
            ? (string) $task['created_at']
            : (! empty($task['updated_at']) ? (string) $task['updated_at'] : date('Y-m-d H:i:s'));
    }

    /**
     * Backfill missing work tasks for finalized clinical records with ABHA.
     * Ensures cron discovers and queues tasks even if staff haven't opened the web Task Board.
     */
    public function backfillMissingWorkTasks(int $days = 30, int $limit = 100): void
    {
        if (! $this->db->tableExists('abdm_work_tasks') || ! $this->db->tableExists('patient_master')) {
            return;
        }

        $patientFields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaCol = $this->resolveFirstExistingColumn($patientFields, ['abha_id', 'abha_no', 'abha_address', 'abha']);
        if ($abhaCol === null) {
            return;
        }

        $sinceDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // 1. Health Documents: file_upload_data
        if ($this->db->tableExists('file_upload_data')) {
            $fileRows = $this->db->table('file_upload_data f')
                ->select('f.id, f.pid, f.insert_date, p.p_fname, p.' . $abhaCol . ' as abha_id', false)
                ->join('patient_master p', 'p.id = f.pid', 'inner')
                ->where('p.' . $abhaCol . ' !=', '')
                ->where('f.insert_date >=', $sinceDate)
                ->orderBy('f.id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($fileRows as $fRow) {
                $fileId = (int) ($fRow['id'] ?? 0);
                $patientId = (int) ($fRow['pid'] ?? 0);
                $abhaId = trim((string) ($fRow['abha_id'] ?? ''));
                if ($fileId <= 0 || $patientId <= 0 || preg_match('/^\d{14}$/', $abhaId) !== 1) {
                    continue;
                }

                $exists = $this->db->table('abdm_work_tasks')
                    ->select('id')
                    ->where('task_type', 'health_document_publish')
                    ->where('entity_type', 'patient_document')
                    ->where('entity_id', (string) $fileId)
                    ->get(1)
                    ->getRowArray();
                if (! empty($exists)) {
                    continue;
                }

                $this->taskService->createOrRefreshTask(
                    'health_document_publish',
                    'file_upload_data',
                    'patient_document',
                    (string) $fileId,
                    $patientId,
                    trim((string) ($fRow['p_fname'] ?? '')),
                    $abhaId,
                    'submit',
                    [
                        'file_upload_id'     => $fileId,
                        'clinical_timestamp' => (string) ($fRow['insert_date'] ?? ''),
                        'trigger'            => 'cron.backfill',
                    ]
                );
            }
        }

        // 2. Health Documents: patient_doc
        if ($this->db->tableExists('patient_doc')) {
            $docRows = $this->db->table('patient_doc pd')
                ->select('pd.id, pd.p_id, pd.date_issue, pd.created_at, p.p_fname, p.' . $abhaCol . ' as abha_id', false)
                ->join('patient_master p', 'p.id = pd.p_id', 'inner')
                ->where('p.' . $abhaCol . ' !=', '')
                ->where('pd.created_at >=', $sinceDate)
                ->orderBy('pd.id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($docRows as $dRow) {
                $docId = (int) ($dRow['id'] ?? 0);
                $patientId = (int) ($dRow['p_id'] ?? 0);
                $abhaId = trim((string) ($dRow['abha_id'] ?? ''));
                if ($docId <= 0 || $patientId <= 0 || preg_match('/^\d{14}$/', $abhaId) !== 1) {
                    continue;
                }

                $exists = $this->db->table('abdm_work_tasks')
                    ->select('id')
                    ->where('task_type', 'health_document_publish')
                    ->where('entity_type', 'doctor_document')
                    ->where('entity_id', (string) $docId)
                    ->get(1)
                    ->getRowArray();
                if (! empty($exists)) {
                    continue;
                }

                $this->taskService->createOrRefreshTask(
                    'health_document_publish',
                    'patient_doc',
                    'doctor_document',
                    (string) $docId,
                    $patientId,
                    trim((string) ($dRow['p_fname'] ?? '')),
                    $abhaId,
                    'submit',
                    [
                        'patient_doc_id'     => $docId,
                        'clinical_timestamp' => (string) (! empty($dRow['created_at']) ? $dRow['created_at'] : ($dRow['date_issue'] ?? '')),
                        'trigger'            => 'cron.backfill',
                    ]
                );
            }
        }

        // 3. Lab & Radiology: lab_request
        if ($this->db->tableExists('lab_request')) {
            $labFields = $this->db->getFieldNames('lab_request') ?? [];
            $dateFields = array_intersect(['reported_time', 'collected_time', 'Request_Date'], $labFields);
            $dateSelect = ! empty($dateFields) ? (', r.' . implode(', r.', $dateFields)) : '';

            $abhaParts = [];
            foreach (['abha_id', 'abha_no', 'abha_address', 'abha'] as $f) {
                if (in_array($f, $patientFields, true)) {
                    $abhaParts[] = 'p.' . $f;
                }
            }
            $abhaSelect = ! empty($abhaParts) ? (', ' . implode(', ', $abhaParts)) : '';

            $labRows = $this->db->table('lab_request r')
                ->select('r.id, r.patient_id, r.patient_name, r.lab_type, r.charge_id, r.status' . $dateSelect . $abhaSelect, false)
                ->join('patient_master p', 'p.id = r.patient_id', 'inner')
                ->where('r.status >=', 2)
                ->orderBy('r.id', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($labRows as $lRow) {
                $labReqId = (int) ($lRow['id'] ?? 0);
                $patientId = (int) ($lRow['patient_id'] ?? 0);
                if ($labReqId <= 0 || $patientId <= 0) {
                    continue;
                }

                $rawAbha = '';
                $abhaAddress = '';
                foreach (['abha_address', 'abha_id', 'abha_no', 'abha'] as $f) {
                    $val = trim((string) ($lRow[$f] ?? ''));
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

                $abhaId = $rawAbha !== '' ? $rawAbha : $abhaAddress;
                if ($abhaId === '') {
                    continue;
                }

                $labType = (int) ($lRow['lab_type'] ?? 0);
                $taskType = in_array($labType, [1, 2, 3, 4, 6], true) ? 'radiology_report_publish' : 'lab_report_publish';

                $exists = $this->db->table('abdm_work_tasks')
                    ->select('id')
                    ->where('task_type', $taskType)
                    ->where('entity_type', 'lab_request')
                    ->where('entity_id', (string) $labReqId)
                    ->get(1)
                    ->getRowArray();
                if (! empty($exists)) {
                    continue;
                }

                $clinTs = ! empty($lRow['reported_time'])
                    ? $lRow['reported_time']
                    : (! empty($lRow['collected_time']) ? $lRow['collected_time'] : ($lRow['Request_Date'] ?? ''));

                $this->taskService->createOrRefreshTask(
                    $taskType,
                    'diagnosis',
                    'lab_request',
                    (string) $labReqId,
                    $patientId,
                    trim((string) ($lRow['patient_name'] ?? '')),
                    $abhaId,
                    'submit',
                    [
                        'lab_type'           => $labType,
                        'invoice_id'         => (int) ($lRow['charge_id'] ?? 0),
                        'clinical_timestamp' => (string) $clinTs,
                        'trigger'            => 'cron.backfill',
                    ]
                );
            }
        }
    }
}

