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
            $this->outbox->markRetryOrDead($id, $retryCount, (string) ($result['message'] ?? 'Sync failed'));
            if ($this->outbox->getNextRetryAt($retryCount) === null) {
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

        $fhirBundle = $payload['fhir_bundle'] ?? json_decode((string) ($record['fhir_bundle_json'] ?? '{}'), true);
        if (! is_array($fhirBundle)) {
            $fhirBundle = [];
        }

        $gatewayPayload = [
            'hfr_id' => (string) ($record['hfr_id'] ?? ''),
            'hi_type' => (string) ($record['hi_type'] ?? ''),
            'record_type' => (string) ($record['hi_type'] ?? ''),
            'abha_id' => $this->normalizeAbhaDigits((string) ($payload['abha_id'] ?? '')),
            'abha_address' => trim((string) ($payload['abha_address'] ?? '')),
            'patient_name' => trim((string) ($payload['patient_name'] ?? '')),
            'local_patient_id' => (string) ($record['local_patient_id'] ?? ''),
            'care_context_reference' => (string) ($record['care_context_reference'] ?? ''),
            'care_context_display' => (string) ($record['care_context_display'] ?? ''),
            'visit_date' => (string) ($record['visit_date'] ?? ''),
            'department' => (string) ($record['department'] ?? ''),
            'doctor_name' => (string) ($record['doctor_name'] ?? ''),
            'fhir_bundle' => $fhirBundle,
        ];

        $result = $this->gatewayClient->pushRecord($gatewayPayload, $idempotencyKey);

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
}
