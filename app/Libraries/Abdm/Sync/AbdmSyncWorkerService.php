<?php

namespace App\Libraries\Abdm\Sync;

use App\Models\AbdmSyncPatientModel;
use App\Models\AbdmSyncRecordModel;
use CodeIgniter\Database\BaseConnection;
use App\Libraries\Abdm\Sync\AbdmSyncOutboxService;
use App\Libraries\Abdm\Sync\AbdmGatewayPushClient;

class AbdmSyncWorkerService
{
    private BaseConnection $db;
    private AbdmSyncOutboxService $outbox;
    private AbdmGatewayPushClient $gatewayClient;
    private AbdmSyncPatientModel $patientModel;
    private AbdmSyncRecordModel $recordModel;

    public function __construct(
        ?BaseConnection $db = null,
        ?AbdmSyncOutboxService $outbox = null,
        ?AbdmGatewayPushClient $gatewayClient = null
    ) {
        $this->db = $db ?? db_connect();
        $this->outbox = $outbox ?? new AbdmSyncOutboxService($this->db);
        $this->gatewayClient = $gatewayClient ?? new AbdmGatewayPushClient($this->db);
        $this->patientModel = new AbdmSyncPatientModel($this->db);
        $this->recordModel = new AbdmSyncRecordModel($this->db);
    }

    /**
     * @return array<string,mixed>
     */
    public function process(int $limit = 20, string $workerId = 'abdm-push-sync'): array
    {
        $summary = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'dead' => 0,
            'skipped' => 0,
        ];

        $rows = $this->outbox->fetchProcessableBatch($limit);
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                $summary['skipped']++;
                continue;
            }

            if (! $this->outbox->tryLockRow($id, $workerId)) {
                $summary['skipped']++;
                continue;
            }

            $summary['processed']++;
            $entityType = (string) ($row['entity_type'] ?? '');
            $entityId = (int) ($row['entity_id'] ?? 0);
            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            if (! is_array($payload)) {
                $payload = [];
            }

            if ($entityType === 'record') {
                $result = $this->processRecord($entityId, $payload, (string) ($row['idempotency_key'] ?? ''));
            } elseif ($entityType === 'patient') {
                $result = $this->processPatient($entityId, $payload, (string) ($row['idempotency_key'] ?? ''));
            } else {
                $result = [
                    'ok' => false,
                    'retryable' => false,
                    'message' => 'Unknown entity type: ' . $entityType,
                ];
            }

            if ((bool) ($result['ok'] ?? false)) {
                $this->outbox->markDone($id);
                $summary['success']++;
                continue;
            }

            $retryCount = ((int) ($row['retry_count'] ?? 0)) + 1;
            $retrySchedule = $this->resolveRetryScheduleSeconds($payload);
            $this->outbox->markRetryOrDead($id, $retryCount, (string) ($result['message'] ?? 'Sync failed'), $retrySchedule);
            if ($this->outbox->getNextRetryAt($retryCount, $retrySchedule) === null) {
                $summary['dead']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function processRecord(int $syncRecordId, array $payload, string $idempotencyKey): array
    {
        $record = $this->recordModel->find($syncRecordId);
        if (! is_array($record)) {
            return ['ok' => false, 'retryable' => false, 'message' => 'Sync record not found'];
        }

        $patientProfile = $this->resolvePatientProfile((int) ($record['local_patient_id'] ?? 0), $payload);

        $hospitalId = (int) ($payload['hospital_id'] ?? 0);
        if ($hospitalId <= 0) {
            $hospitalId = $this->resolveHospitalIdFromSettings();
        }

        $recordType = $this->mapHiTypeToRecordType((string) ($record['hi_type'] ?? ''));
        $careContextPayload = [
            'hospital_id' => $hospitalId,
            'hfr_id' => (string) ($record['hfr_id'] ?? ''),
            'patient' => [
                'patient_id' => $patientProfile['patient_id'],
                'name' => $patientProfile['name'],
                'mobile' => $patientProfile['mobile'],
                'gender' => $patientProfile['gender'],
                'year_of_birth' => $patientProfile['year_of_birth'],
            ],
            'care_contexts' => [[
                'reference_number' => (string) ($record['care_context_reference'] ?? ''),
                'display' => (string) ($record['care_context_display'] ?? $recordType),
                'record_type' => $recordType,
                'visit_date' => $this->normalizeVisitDateTime((string) ($record['visit_date'] ?? '')),
                'doctor_name' => (string) ($record['doctor_name'] ?? ''),
                'department' => (string) ($record['department'] ?? ''),
            ]],
        ];

        $abhaAddress = trim((string) ($patientProfile['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $careContextPayload['patient']['abha_address'] = $abhaAddress;
        }

        $abhaNumber = trim((string) ($patientProfile['abha_number'] ?? ''));
        if ($abhaNumber !== '') {
            $careContextPayload['patient']['abha_number'] = $abhaNumber;
        }

        $result = $this->gatewayClient->pushCareContextLink($careContextPayload, $idempotencyKey);

        $this->recordModel->update($syncRecordId, [
            'sync_status' => (bool) ($result['ok'] ?? false) ? 'done' : ((bool) ($result['retryable'] ?? false) ? 'failed' : 'dead'),
            'gateway_record_id' => (int) ($result['gateway_record_id'] ?? 0) ?: null,
            'gateway_queue_id' => (string) ($result['gateway_queue_id'] ?? '') ?: null,
            'last_synced_at' => date('Y-m-d H:i:s'),
            'last_error' => (bool) ($result['ok'] ?? false) ? null : (string) ($result['message'] ?? 'Sync failed'),
            'retry_count' => (int) ($record['retry_count'] ?? 0) + ((bool) ($result['ok'] ?? false) ? 0 : 1),
        ]);

        return $result;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function processPatient(int $syncPatientId, array $payload, string $idempotencyKey): array
    {
        $patient = $this->patientModel->find($syncPatientId);
        if (! is_array($patient)) {
            return ['ok' => false, 'retryable' => false, 'message' => 'Sync patient not found'];
        }

        $patientName = trim((string) ($patient['name'] ?? ''));
        $careContextReference = 'PAT-' . (int) ($patient['local_patient_id'] ?? 0) . '-' . date('YmdHis', strtotime((string) ($patient['source_updated_at'] ?? 'now')));

        $fhirBundle = [
            'resourceType' => 'Bundle',
            'type' => 'document',
            'timestamp' => date(DATE_ATOM),
            'entry' => [
                [
                    'fullUrl' => 'urn:uuid:composition-' . $syncPatientId,
                    'resource' => [
                        'resourceType' => 'Composition',
                        'id' => 'composition-' . $syncPatientId,
                        'status' => 'final',
                        'type' => [
                            'coding' => [[
                                'system' => 'http://loinc.org',
                                'code' => '34133-9',
                                'display' => 'Summary of episode note',
                            ]],
                        ],
                        'date' => date(DATE_ATOM),
                        'title' => 'Patient Demographics Snapshot',
                        'subject' => ['reference' => 'urn:uuid:patient-' . $syncPatientId],
                    ],
                ],
                [
                    'fullUrl' => 'urn:uuid:patient-' . $syncPatientId,
                    'resource' => [
                        'resourceType' => 'Patient',
                        'id' => 'patient-' . $syncPatientId,
                        'identifier' => [
                            [
                                'system' => 'https://hms.local/patient-id',
                                'value' => (string) ($patient['local_patient_id'] ?? ''),
                            ],
                        ],
                        'name' => [[
                            'text' => $patientName,
                        ]],
                    ],
                ],
            ],
        ];

        $abhaDigits = $this->normalizeAbhaDigits((string) ($patient['abha_id'] ?? ''));
        if ($abhaDigits !== '') {
            $fhirBundle['entry'][1]['resource']['identifier'][] = [
                'system' => 'https://healthid.ndhm.gov.in',
                'value' => $abhaDigits,
            ];
        }

        $abhaAddress = trim((string) ($patient['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $fhirBundle['entry'][1]['resource']['identifier'][] = [
                'system' => 'https://abdm.gov.in/health-address',
                'value' => $abhaAddress,
            ];
        }

        $gatewayPayload = [
            'hfr_id' => trim((string) ($payload['hfr_id'] ?? '')),
            'hi_type' => 'HealthDocumentRecord',
            'record_type' => 'HealthDocumentRecord',
            'abha_id' => $abhaDigits,
            'abha_address' => $abhaAddress,
            'patient_name' => $patientName,
            'local_patient_id' => (string) ($patient['local_patient_id'] ?? ''),
            'care_context_reference' => $careContextReference,
            'care_context_display' => 'Patient profile sync',
            'visit_date' => date('Y-m-d'),
            'department' => 'Registration',
            'doctor_name' => 'System',
            'fhir_bundle' => $fhirBundle,
        ];

        $result = $this->gatewayClient->pushRecord($gatewayPayload, $idempotencyKey);

        $this->patientModel->update($syncPatientId, [
            'sync_status' => (bool) ($result['ok'] ?? false) ? 'done' : ((bool) ($result['retryable'] ?? false) ? 'failed' : 'dead'),
            'last_synced_at' => date('Y-m-d H:i:s'),
            'last_error' => (bool) ($result['ok'] ?? false) ? null : (string) ($result['message'] ?? 'Patient sync failed'),
            'retry_count' => (int) ($patient['retry_count'] ?? 0) + ((bool) ($result['ok'] ?? false) ? 0 : 1),
        ]);

        return $result;
    }

    private function normalizeAbhaDigits(string $abha): string
    {
        $digits = preg_replace('/\D/', '', $abha);
        return is_string($digits) ? $digits : '';
    }

    /**
     * @param array<string,mixed> $payload
     * @return int[]|null
     */
    private function resolveRetryScheduleSeconds(array $payload): ?array
    {
        $raw = $payload['retry_schedule_seconds'] ?? null;
        if (! is_array($raw)) {
            return null;
        }

        $schedule = [];
        foreach ($raw as $seconds) {
            $value = (int) $seconds;
            if ($value > 0) {
                $schedule[] = $value;
            }
        }

        return $schedule !== [] ? array_values($schedule) : null;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function resolvePatientProfile(int $localPatientId, array $payload): array
    {
        $row = [];
        if ($localPatientId > 0 && $this->db->tableExists('patient_master')) {
            $row = $this->db->table('patient_master')->where('id', $localPatientId)->get(1)->getRowArray() ?? [];
        }

        $name = trim((string) ($payload['patient_name'] ?? ''));
        if ($name === '') {
            $name = trim(trim((string) ($row['p_fname'] ?? '')) . ' ' . trim((string) ($row['p_lname'] ?? '')));
        }
        if ($name === '') {
            $name = 'PATIENT-' . $localPatientId;
        }

        $mobile = trim((string) ($payload['mobile'] ?? $payload['patient_mobile'] ?? ''));
        if ($mobile === '') {
            foreach (['mphone1', 'mobile', 'phone', 'p_mobile'] as $field) {
                $candidate = trim((string) ($row[$field] ?? ''));
                if ($candidate !== '') {
                    $mobile = $candidate;
                    break;
                }
            }
        }
        $mobileDigits = preg_replace('/\D/', '', $mobile);
        $mobile = is_string($mobileDigits) ? $mobileDigits : '';
        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        $genderRaw = trim((string) ($payload['gender'] ?? $payload['patient_gender'] ?? ''));
        if ($genderRaw === '') {
            $genderRaw = trim((string) ($row['gender'] ?? $row['xgender'] ?? ''));
        }

        $dobRaw = trim((string) ($payload['dob'] ?? $payload['patient_dob'] ?? ''));
        if ($dobRaw === '') {
            foreach (['dob', 'p_dob', 'birth_date', 'date_of_birth'] as $field) {
                $candidate = trim((string) ($row[$field] ?? ''));
                if ($candidate !== '') {
                    $dobRaw = $candidate;
                    break;
                }
            }
        }

        $abhaAddress = trim((string) ($payload['abha_address'] ?? ''));
        if ($abhaAddress === '') {
            $abhaAddress = trim((string) ($row['abha_address'] ?? ''));
        }

        $abhaDigits = $this->normalizeAbhaDigits((string) ($payload['abha_id'] ?? ''));
        if ($abhaDigits === '') {
            foreach (['abha_id', 'abha_no', 'abha_number', 'abha'] as $field) {
                $candidate = $this->normalizeAbhaDigits((string) ($row[$field] ?? ''));
                if ($candidate !== '') {
                    $abhaDigits = $candidate;
                    break;
                }
            }
        }

        return [
            'patient_id' => (string) ($localPatientId > 0 ? $localPatientId : ($payload['local_patient_id'] ?? '')),
            'name' => $name,
            'mobile' => $mobile,
            'gender' => $this->normalizeBridgeGenderCode($genderRaw),
            'year_of_birth' => $this->extractBirthYear($dobRaw),
            'abha_address' => $abhaAddress,
            'abha_number' => $abhaDigits,
        ];
    }

    private function normalizeBridgeGenderCode(string $gender): string
    {
        $value = strtoupper(trim($gender));
        if ($value === 'M' || $value === 'MALE' || $value === '1') {
            return 'M';
        }
        if ($value === 'F' || $value === 'FEMALE' || $value === '2') {
            return 'F';
        }
        if ($value === 'O' || $value === 'OTHER' || $value === 'OTHERS' || $value === '3') {
            return 'O';
        }

        return 'O';
    }

    private function extractBirthYear(string $dob): int
    {
        $dob = trim($dob);
        if ($dob !== '') {
            $ts = strtotime($dob);
            if ($ts !== false) {
                return (int) date('Y', $ts);
            }
        }

        return (int) date('Y');
    }

    private function mapHiTypeToRecordType(string $hiType): string
    {
        $type = trim($hiType);
        return match ($type) {
            'PrescriptionRecord' => 'PrescriptionRecord',
            'DiagnosticReportRecord' => 'DiagnosticReportRecord',
            'DischargeSummaryRecord' => 'DischargeSummaryRecord',
            'OPConsultRecord' => 'OPConsultationRecord',
            'OPConsultationRecord' => 'OPConsultationRecord',
            'WellnessRecord' => 'WellnessRecord',
            'HealthDocumentRecord' => 'HealthDocumentRecord',
            default => 'HealthDocumentRecord',
        };
    }

    private function normalizeVisitDateTime(string $visitDate): string
    {
        $visitDate = trim($visitDate);
        if ($visitDate === '') {
            return date('Y-m-d H:i:s');
        }

        $ts = strtotime($visitDate);
        if ($ts === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function resolveHospitalIdFromSettings(): int
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return 0;
        }

        foreach (['ABDM_BRIDGE_HOSPITAL_ID', 'EATRIA_BRIDGE_HOSPITAL_ID', 'HOSPITAL_ID'] as $settingName) {
            $row = $this->db->table('hospital_setting')
                ->select('s_value')
                ->where('s_name', $settingName)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            $value = (int) trim((string) ($row['s_value'] ?? '0'));
            if ($value > 0) {
                return $value;
            }
        }

        return 0;
    }
}
