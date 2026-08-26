<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorInterface;
use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\Abdm\Fhir\FhirGeneratorFactory;
use App\Libraries\Abdm\Fhir\Support\GatewayPayloadAdapter;
use App\Libraries\Abdm\M2LinkStatusResolver;
use App\Libraries\FhirR4Builder;
use App\Libraries\FhirEncryptionService;
use App\Libraries\AbdmAuditService;
use CodeIgniter\I18n\Time;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;

class AbdmGateway extends BaseController
{
    private AbdmConnectorInterface $connector;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface  $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface            $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->connector = AbdmConnectorFactory::make();
    }

    public function abhaValidate()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $abhaId = trim((string) $this->request->getPost('abha_id'));
        // Accept both dash-format (14-1234-5678-9012) and raw 14-digit format
        $abhaDigits = str_replace('-', '', $abhaId);
        if ($abhaId === '' || strlen($abhaDigits) !== 14 || ! ctype_digit($abhaDigits)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be 14 digits (e.g. 14-1234-5678-9012).']);
        }

        $payload = [
            'abha_id' => $abhaId,
            'requested_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ];

        $queueId = null;
        try {
            $result  = $this->connector->validateAbha($abhaId, $payload);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => 'queued',
            'queue_id' => $queueId,
            'message' => 'ABHA validation request queued to center server.',
        ]);
    }

    public function bridgeTestEvent()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $abhaId = trim((string) $this->request->getPost('abha_id'));
        if ($abhaId === '') {
            $abhaId = '14-1234-5678-9012';
        }

        // Accept both dash-format (14-1234-5678-9012) and raw 14-digit format
        $abhaDigits = str_replace('-', '', $abhaId);
        if (strlen($abhaDigits) !== 14 || ! ctype_digit($abhaDigits)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be 14 digits (e.g. 14-1234-5678-9012).']);
        }

        $payload = [
            'abha_id' => $abhaId,
            'requested_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            'source' => 'billing.patient.ui',
            'test_ping' => 1,
        ];

        try {
            $result = $this->connector->validateAbha($abhaId, $payload);
            $cfg = config('AbdmConnector');

            return $this->response->setJSON([
                'ok' => 1,
                'message' => 'Bridge test queued successfully.',
                'connector' => $this->connector->getConnectorName(),
                'bridge_url' => (string) ($cfg->dreamsoftBridgeUrl ?? ''),
                'queue_id' => $result['queue_id'] ?? null,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error_text' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // HMS M2 Adapter Endpoints (Gateway -> HMS)
    // =========================================================================

    /**
     * GET /api/v1/abdm/gateway/health
     */
    public function m2GatewayHealth()
    {
        $authFailure = $this->validateGatewayToHmsAuth();
        if ($authFailure !== null) {
            return $authFailure;
        }

        return $this->response->setJSON([
            'ok' => 1,
            'service' => 'hms-abdm-adapter',
            'time' => gmdate('Y-m-d\TH:i:s\Z'),
        ]);
    }

    /**
     * POST /api/v1/abdm/gateway/discovery/care-contexts
     */
    public function m2DiscoveryCareContexts()
    {
        $authFailure = $this->validateGatewayToHmsAuth();
        if ($authFailure !== null) {
            return $authFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $requestId = trim((string) ($payload['request_id'] ?? $payload['requestId'] ?? $this->request->getHeaderLine('X-Request-Id')));
        $abhaId = trim((string) ($payload['abha_id'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abhaAddress'] ?? ''));

        if ($abhaId === '' && $abhaAddress === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'MISSING_FIELD',
                'message' => 'abha_id or abha_address is required',
                'request_id' => $requestId,
            ]);
        }

        if (! $this->db->tableExists('patient_records') && ! $this->db->tableExists('health_records')) {
            return $this->response->setJSON(['ok' => 1, 'patient' => null, 'care_contexts' => []]);
        }

        $abhaLookup = $abhaAddress !== '' ? $abhaAddress : $abhaId;
        $rows = [];
        if ($this->db->tableExists('patient_records')) {
            $rows = $this->db->table('patient_records')
                ->select('patient_id, abha_id, record_type, created_at, consent_id')
                ->where('abha_id', $abhaLookup)
                ->where('status', 'ACTIVE')
                ->orderBy('record_id', 'DESC')
                ->limit(200)
                ->get()
                ->getResultArray();
        }

        $healthRows = [];
        if ($this->db->tableExists('health_records')) {
            $healthFields = $this->db->getFieldNames('health_records') ?? [];
            if (in_array('care_context_reference', $healthFields, true)) {
                $healthRows = $this->db->table('health_records')
                    ->select('id, patient_id, abha_id, hi_type, entity_type, entity_id, care_context_reference, created_at')
                    ->where('abha_id', $abhaLookup)
                    ->where('care_context_reference !=', '')
                    ->whereIn('push_status', ['local_discovery_ready', 'queued', 'pushed', 'linked'])
                    ->orderBy('id', 'DESC')
                    ->limit(300)
                    ->get()
                    ->getResultArray();
            }
        }

        if (empty($rows) && empty($healthRows)) {
            return $this->response->setJSON(['ok' => 1, 'patient' => null, 'care_contexts' => []]);
        }

        $contexts = [];
        foreach ($healthRows as $row) {
            $ccRef = trim((string) ($row['care_context_reference'] ?? ''));
            $hiType = trim((string) ($row['hi_type'] ?? ''));
            if ($ccRef === '' || $hiType === '') {
                continue;
            }
            $createdAt = trim((string) ($row['created_at'] ?? ''));
            $datePart = $createdAt !== '' ? date('Y-m-d', strtotime($createdAt)) : date('Y-m-d');
            $contexts[$ccRef] = [
                'referenceNumber' => $ccRef,
                'display' => $hiType . ' - ' . $datePart,
                'hiType' => $hiType,
            ];
        }
        foreach ($rows as $row) {
            $patientId = (int) ($row['patient_id'] ?? 0);
            $createdAt = trim((string) ($row['created_at'] ?? ''));
            $datePart = $createdAt !== '' ? date('Y-m-d', strtotime($createdAt)) : date('Y-m-d');
            $recordType = strtoupper(trim((string) ($row['record_type'] ?? 'OTHER')));

            $ccRef = $recordType . '-' . $patientId . '-' . str_replace('-', '', $datePart);
            $hiType = $this->mapPatientRecordTypeToHiType($recordType);

            $contexts[$ccRef] = [
                'referenceNumber' => $ccRef,
                'display' => $recordType . ' - ' . $datePart,
                'hiType' => $hiType,
            ];
        }

        $patientDisplay = trim((string) ($payload['patient']['name'] ?? ''));
        if ($patientDisplay === '') {
            $patientDisplay = 'ABHA Patient';
        }

        $contextsOut = array_values($contexts);
        $hiTypeSummary = ! empty($contextsOut)
            ? (string) ($contextsOut[0]['hiType'] ?? 'HealthDocumentRecord')
            : 'HealthDocumentRecord';

        $this->getAuditService()->log([
            'action' => 'discovery_records',
            'entity_type' => 'patient_records',
            'abha_id' => $abhaLookup,
            'patient_id' => (int) ($healthRows[0]['patient_id'] ?? $rows[0]['patient_id'] ?? 0),
            'request' => $payload,
            'response' => ['count' => count($contextsOut)],
            'outcome' => 'success',
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'patient' => [
                'referenceNumber' => $abhaLookup,
                'display' => $patientDisplay,
                'count' => count($contextsOut),
                'hiType' => $hiTypeSummary,
            ],
            'care_contexts' => $contextsOut,
        ]);
    }

    /**
     * POST /api/v1/abdm/gateway/health-information/fetch
     */
    public function m2HealthInformationFetch()
    {
        $authFailure = $this->validateGatewayToHmsAuth();
        if ($authFailure !== null) {
            return $authFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $requestId = trim((string) ($payload['request_id'] ?? $this->request->getHeaderLine('X-Request-Id')));
        $abhaId = trim((string) ($payload['abha_id'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? ''));
        $abhaLookup = $abhaAddress !== '' ? $abhaAddress : $abhaId;

        if ($abhaLookup === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'MISSING_FIELD',
                'message' => 'abha_id or abha_address is required',
                'request_id' => $requestId,
            ]);
        }

        if (! $this->db->tableExists('patient_records') && ! $this->db->tableExists('health_records')) {
            return $this->response->setJSON(['ok' => 1, 'entries' => []]);
        }

        $requestedRefs = [];
        $incomingRefs = $payload['care_context_references'] ?? $payload['careContextReferences'] ?? [];
        if (is_array($incomingRefs)) {
            foreach ($incomingRefs as $ref) {
                $v = trim((string) $ref);
                if ($v !== '') {
                    $requestedRefs[] = $v;
                }
            }
        }

        $rows = [];
        if ($this->db->tableExists('patient_records')) {
            $rows = $this->db->table('patient_records')
                ->select('record_id, patient_id, abha_id, consent_id, record_type, fhir_resource, created_at')
                ->where('abha_id', $abhaLookup)
                ->where('status', 'ACTIVE')
                ->orderBy('record_id', 'DESC')
                ->limit(300)
                ->get()
                ->getResultArray();
        }

        $entries = [];
        $resolvedRefs = [];
        $deliveredHealthRows = [];
        if ($this->db->tableExists('health_records')) {
            $healthFields = $this->db->getFieldNames('health_records') ?? [];
            if (in_array('record_data', $healthFields, true) && in_array('care_context_reference', $healthFields, true)) {
                $healthBuilder = $this->db->table('health_records')
                    ->select('id, care_context_reference, record_data, entity_type, entity_id, push_status')
                    ->where('abha_id', $abhaLookup)
                    ->where('care_context_reference !=', '')
                    ->whereIn('push_status', ['local_discovery_ready', 'queued', 'pushed', 'linked'])
                    ->orderBy('id', 'DESC')
                    ->limit(300);
                if (! empty($requestedRefs)) {
                    $healthBuilder->whereIn('care_context_reference', $requestedRefs);
                }
                foreach ($healthBuilder->get()->getResultArray() as $healthRow) {
                    $ccRef = trim((string) ($healthRow['care_context_reference'] ?? ''));
                    if ($ccRef === '' || isset($resolvedRefs[$ccRef])) {
                        continue;
                    }
                    $fhir = json_decode((string) ($healthRow['record_data'] ?? ''), true);
                    if (! is_array($fhir)) {
                        continue;
                    }
                    $resolvedRefs[$ccRef] = true;
                    $deliveredHealthRows[] = $healthRow;
                    $entries[] = [
                        'careContextReference' => $ccRef,
                        'media' => 'application/fhir+json',
                        'fhir' => $fhir,
                    ];
                }
            }
        }
        foreach ($rows as $row) {
            $patientId = (int) ($row['patient_id'] ?? 0);
            $recordType = strtoupper(trim((string) ($row['record_type'] ?? 'OTHER')));
            $createdAt = trim((string) ($row['created_at'] ?? ''));
            $datePart = $createdAt !== '' ? date('Y-m-d', strtotime($createdAt)) : date('Y-m-d');
            $ccRef = $recordType . '-' . $patientId . '-' . str_replace('-', '', $datePart);

            if (isset($resolvedRefs[$ccRef]) || (! empty($requestedRefs) && ! in_array($ccRef, $requestedRefs, true))) {
                continue;
            }

            $fhir = json_decode((string) ($row['fhir_resource'] ?? ''), true);
            if (! is_array($fhir)) {
                continue;
            }

            $entries[] = [
                'careContextReference' => $ccRef,
                'media' => 'application/fhir+json',
                'fhir' => $fhir,
            ];
            $resolvedRefs[$ccRef] = true;
        }

        $this->confirmFetchedHealthRecordsLinked($deliveredHealthRows, $abhaLookup, $requestId, $payload);

        $this->getAuditService()->log([
            'action' => 'fetch_record',
            'entity_type' => 'patient_records',
            'abha_id' => $abhaLookup,
            'request' => $payload,
            'response' => ['count' => count($entries)],
            'outcome' => 'success',
            'consent_id' => (string) ($payload['consent_id'] ?? ''),
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'entries' => $entries,
        ]);
    }

    /**
     * A successful authenticated fetch proves that these canonical care contexts
     * were linked and delivered, even when the bridge omits a link-status callback.
     *
     * @param array<int, array<string, mixed>> $healthRows
     * @param array<string, mixed> $payload
     */
    private function confirmFetchedHealthRecordsLinked(array $healthRows, string $abhaId, string $requestId, array $payload): void
    {
        if ($healthRows === [] || ! $this->db->tableExists('health_records')) {
            return;
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $this->db->transStart();
        foreach ($healthRows as $healthRow) {
            $healthRecordId = (int) ($healthRow['id'] ?? 0);
            $careContextRef = trim((string) ($healthRow['care_context_reference'] ?? ''));
            if ($healthRecordId <= 0 || $careContextRef === '') {
                continue;
            }

            if (strtolower(trim((string) ($healthRow['push_status'] ?? ''))) !== 'linked') {
                $this->db->table('health_records')->where('id', $healthRecordId)->update([
                    'push_status' => 'linked',
                    'linked_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($this->db->tableExists('record_links')) {
                $existing = $this->db->table('record_links')
                    ->where('care_context_reference', $careContextRef)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
                $linkData = [
                    'health_record_id' => $healthRecordId,
                    'abdm_txn_id' => $requestId !== '' ? $requestId : null,
                    'link_status' => 'linked',
                    'updated_at' => $now,
                    'response_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
                if (! empty($existing)) {
                    if (strtolower(trim((string) ($existing['link_status'] ?? ''))) !== 'linked') {
                        $linkData['linked_at'] = $now;
                    }
                    $this->db->table('record_links')->where('id', (int) $existing['id'])->update($linkData);
                } else {
                    $this->db->table('record_links')->insert(array_merge($linkData, [
                        'abha_id' => $abhaId,
                        'care_context_reference' => $careContextRef,
                        'linked_at' => $now,
                        'created_at' => $now,
                    ]));
                }
            }

            if ($this->db->tableExists('abdm_work_tasks')) {
                $this->db->table('abdm_work_tasks')
                    ->where('entity_type', (string) ($healthRow['entity_type'] ?? ''))
                    ->where('entity_id', (string) ($healthRow['entity_id'] ?? ''))
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->update([
                        'status' => 'completed',
                        'last_action_result' => 'Care context delivered through authenticated M2 fetch.',
                        'completed_at' => $now,
                        'updated_at' => $now,
                    ]);
            }
        }
        $this->db->transComplete();
    }

    /**
     * POST /api/v1/abdm/gateway/consent/upsert
     */
    public function m2ConsentUpsert()
    {
        $authFailure = $this->validateGatewayToHmsAuth();
        if ($authFailure !== null) {
            return $authFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $requestId = trim((string) ($payload['request_id'] ?? $this->request->getHeaderLine('X-Request-Id')));
        $patientId = (int) ($payload['patient_id'] ?? 0);
        $abhaId = trim((string) ($payload['abha_id'] ?? $payload['abha_address'] ?? ''));
        $consentId = trim((string) ($payload['consent_id'] ?? ''));
        $status = strtoupper(trim((string) ($payload['status'] ?? '')));
        $purpose = trim((string) ($payload['purpose'] ?? ''));
        $expiresAt = trim((string) ($payload['date_range']['to'] ?? ''));

        if ($abhaId === '' || $consentId === '' || $status === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'MISSING_FIELD',
                'message' => 'abha_id, consent_id and status are required',
                'request_id' => $requestId,
            ]);
        }

        if ($this->db->tableExists('consent_logs')) {
            $this->db->table('consent_logs')->insert([
                'patient_id' => $patientId > 0 ? $patientId : 0,
                'abha_id' => $abhaId,
                'consent_id' => $consentId,
                'purpose' => $purpose !== '' ? $purpose : null,
                'status' => in_array($status, ['GRANTED', 'REVOKED', 'EXPIRED'], true) ? $status : 'EXPIRED',
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'expires_at' => $expiresAt !== '' ? $expiresAt : null,
            ]);
        }

        return $this->response->setJSON(['ok' => 1]);
    }

    /**
     * POST /api/v1/abdm/gateway/link/status
     */
    public function m2LinkStatus()
    {
        $authFailure = $this->validateGatewayToHmsAuth();
        if ($authFailure !== null) {
            return $authFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $callback = M2LinkStatusResolver::parse($payload, $this->request->getHeaderLine('X-Request-Id'));
        $requestId = $callback['request_id'];
        $abhaId = $callback['abha_id'];
        $careContextRef = $callback['care_context_reference'];
        $linkedAt = $callback['linked_at'];

        if ($abhaId === '' || $careContextRef === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'MISSING_FIELD',
                'message' => 'abha_id and care_context_reference are required',
                'request_id' => $requestId,
            ]);
        }

        $incomingLinkStatus = $callback['status'];
        $persistedLinkStatus = $incomingLinkStatus;
        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $healthRecord = null;

        if ($this->db->tableExists('health_records')) {
            $healthRecord = $this->db->table('health_records')
                ->select('id, push_status, entity_type, entity_id')
                ->where('care_context_reference', $careContextRef)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        $this->db->transStart();
        if ($this->db->tableExists('record_links')) {
            $existing = $this->db->table('record_links')
                ->where('care_context_reference', $careContextRef)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            $existingStatus = strtolower(trim((string) ($existing['link_status'] ?? '')));
            $linkStatus = M2LinkStatusResolver::merge($existingStatus, $incomingLinkStatus);
            $persistedLinkStatus = $linkStatus;
            $linkData = [
                'health_record_id' => ! empty($healthRecord) ? (int) $healthRecord['id'] : null,
                'abdm_txn_id' => $requestId !== '' ? $requestId : null,
                'link_status' => $linkStatus,
                'linked_at' => $linkStatus === 'linked' ? ($linkedAt !== '' ? $linkedAt : $now) : null,
                'updated_at' => $now,
                'response_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
            if (! empty($existing)) {
                if ($existingStatus === 'linked') {
                    unset($linkData['linked_at']);
                }
                $this->db->table('record_links')->where('id', (int) $existing['id'])->update($linkData);
            } else {
                $this->db->table('record_links')->insert(array_merge($linkData, [
                    'abha_id' => $abhaId,
                    'care_context_reference' => $careContextRef,
                    'created_at' => $now,
                ]));
            }
        }

        if (! empty($healthRecord)) {
            $currentPushStatus = strtolower(trim((string) ($healthRecord['push_status'] ?? '')));
            if ($currentPushStatus === 'linked') {
                $persistedLinkStatus = 'linked';
            }
            if ($currentPushStatus !== 'linked' && in_array($incomingLinkStatus, ['linked', 'failed'], true)) {
                $healthRecordData = [
                    'push_status' => $incomingLinkStatus,
                    'updated_at' => $now,
                ];
                if ($incomingLinkStatus === 'linked') {
                    $healthRecordData['linked_at'] = $linkedAt !== '' ? $linkedAt : $now;
                }
                $this->db->table('health_records')->where('id', (int) $healthRecord['id'])->update($healthRecordData);
            }

            if ($incomingLinkStatus === 'linked' && $this->db->tableExists('abdm_work_tasks')) {
                $this->db->table('abdm_work_tasks')
                    ->where('entity_type', (string) ($healthRecord['entity_type'] ?? ''))
                    ->where('entity_id', (string) ($healthRecord['entity_id'] ?? ''))
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->update([
                        'status' => 'completed',
                        'last_action_result' => 'Care context link confirmed by M2 callback.',
                        'completed_at' => $linkedAt !== '' ? $linkedAt : $now,
                        'updated_at' => $now,
                    ]);
            }
        }
        $this->db->transComplete();

        return $this->response->setJSON([
            'ok' => $this->db->transStatus() ? 1 : 0,
            'request_id' => $requestId,
            'care_context_reference' => $careContextRef,
            'status' => $persistedLinkStatus,
        ]);
    }

    public function consentRequest()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) $this->request->getPost('abha_id'));
        $purposeCode = trim((string) $this->request->getPost('purpose_code'));
        $expiresAt = trim((string) $this->request->getPost('expires_at'));

        if ($patientId <= 0 || $abhaId === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id and abha_id are required']);
        }

        $hasAbdmConsentTable = $this->db->tableExists('abdm_consent_records');
        $hasConsentAliasTable = $this->db->tableExists('consents');
        if (! $hasAbdmConsentTable && ! $hasConsentAliasTable) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'consent storage table not found']);
        }

        $consentHandle = 'CH-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $requestedAt = Time::now('Asia/Kolkata')->toDateTimeString();

        $rawPayload = [
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'purpose_code' => $purposeCode,
            'expires_at' => $expiresAt,
            'consent_handle' => $consentHandle,
            'requested_at' => $requestedAt,
        ];

        if ($hasAbdmConsentTable) {
            $this->db->table('abdm_consent_records')->insert([
                'patient_id' => $patientId,
                'abha_id' => $abhaId,
                'consent_handle' => $consentHandle,
                'consent_status' => 'requested',
                'purpose_code' => $purposeCode,
                'requested_at' => $requestedAt,
                'expires_at' => $expiresAt !== '' ? $expiresAt : null,
                'raw_payload_json' => (string) json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $requestedAt,
                'updated_at' => $requestedAt,
            ]);
        }

        if ($hasConsentAliasTable) {
            $consentFields = $this->db->getFieldNames('consents') ?? [];
            $consentRow = [];
            if (in_array('patient_id', $consentFields, true)) {
                $consentRow['patient_id'] = $patientId;
            }
            if (in_array('token', $consentFields, true)) {
                $consentRow['token'] = $consentHandle;
            }
            if (in_array('expiry', $consentFields, true)) {
                $consentRow['expiry'] = $expiresAt !== '' ? $expiresAt : null;
            }
            if (in_array('scope', $consentFields, true)) {
                $consentRow['scope'] = $purposeCode !== '' ? $purposeCode : 'TREATMENT';
            }
            if (! empty($consentRow)) {
                $this->db->table('consents')->insert($consentRow);
            }
        }

        $queueId = null;
        $gatewayConsentId = '';
        try {
            $result  = $this->connector->requestConsent($patientId, $abhaId, $purposeCode, $expiresAt, $consentHandle, $rawPayload);
            $queueId = $result['queue_id'] ?? null;
            $gatewayConsentId = trim((string) ($result['gateway_consent_id'] ?? $result['consent_id'] ?? ''));

            if ($gatewayConsentId !== '' && $hasAbdmConsentTable) {
                $tableFields = $this->db->getFieldNames('abdm_consent_records') ?? [];
                $updateData = [
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                ];

                // Persist gateway consent id when a compatible column exists.
                if (in_array('consent_id', $tableFields, true)) {
                    $updateData['consent_id'] = $gatewayConsentId;
                } elseif (in_array('gateway_consent_id', $tableFields, true)) {
                    $updateData['gateway_consent_id'] = $gatewayConsentId;
                }

                $rawPayload['gateway_consent_id'] = $gatewayConsentId;
                $updateData['raw_payload_json'] = (string) json_encode($rawPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $this->db->table('abdm_consent_records')
                    ->where('consent_handle', $consentHandle)
                    ->update($updateData);
            }
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'consent_handle' => $consentHandle,
            'gateway_consent_id' => $gatewayConsentId !== '' ? $gatewayConsentId : null,
            'queue_id' => $queueId,
            'status' => 'requested',
        ]);
    }

    public function consentCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload['consent_handle'])) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'consent_handle is required']);
        }

        $hasAbdmConsentTable = $this->db->tableExists('abdm_consent_records');
        $hasConsentAliasTable = $this->db->tableExists('consents');
        if (! $hasAbdmConsentTable && ! $hasConsentAliasTable) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'consent storage table not found']);
        }

        $handle = trim((string) $payload['consent_handle']);
        $status = trim((string) ($payload['consent_status'] ?? 'approved'));
        $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
        $incomingConsentId = trim((string) ($payload['consent_id'] ?? $payload['gateway_consent_id'] ?? ''));
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        $existing = [];
        if ($hasAbdmConsentTable) {
            $existing = $this->db->table('abdm_consent_records')
                ->where('consent_handle', $handle)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        if (empty($existing) && $hasConsentAliasTable) {
            $consentAliasRow = $this->db->table('consents')
                ->where('token', $handle)
                ->get(1)
                ->getRowArray();
            if (! empty($consentAliasRow)) {
                $existing = [
                    'patient_id' => (int) ($consentAliasRow['patient_id'] ?? 0),
                    'consent_status' => 'approved',
                    'expires_at' => (string) ($consentAliasRow['expiry'] ?? ''),
                    'raw_payload_json' => '',
                    'granted_at' => null,
                ];
            }
        }

        if (empty($existing)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error_text' => 'consent_handle not found',
            ]);
        }

        $currentStatus = strtolower(trim((string) ($existing['consent_status'] ?? '')));
        $incomingStatus = strtolower($status);
        $currentExpires = trim((string) ($existing['expires_at'] ?? ''));
        $incomingExpires = $expiresAt !== '' ? $expiresAt : '';

        if ($this->isStaleConsentTransition($currentStatus, $incomingStatus)) {
            return $this->response->setJSON([
                'ok' => 1,
                'consent_handle' => $handle,
                'status' => $currentStatus,
                'ignored' => 1,
                'reason' => 'stale_transition',
            ]);
        }

        $rawPayloadJson = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $existingPayload = trim((string) ($existing['raw_payload_json'] ?? ''));
        $isDuplicate = ($currentStatus === $incomingStatus) && ($currentExpires === $incomingExpires) && ($existingPayload === $rawPayloadJson);
        if ($isDuplicate) {
            return $this->response->setJSON([
                'ok' => 1,
                'consent_handle' => $handle,
                'status' => $incomingStatus,
                'duplicate' => 1,
            ]);
        }

        $update = [
            'consent_status' => $incomingStatus,
            'granted_at' => $incomingStatus === 'approved'
                ? ((string) ($existing['granted_at'] ?? '') !== '' ? (string) $existing['granted_at'] : $now)
                : (string) ($existing['granted_at'] ?? ''),
            'expires_at' => $expiresAt !== '' ? $expiresAt : null,
            'raw_payload_json' => $rawPayloadJson,
            'updated_at' => $now,
        ];

        if ($incomingConsentId !== '') {
            $tableFields = $this->db->getFieldNames('abdm_consent_records') ?? [];
            if (in_array('consent_id', $tableFields, true)) {
                $update['consent_id'] = $incomingConsentId;
            } elseif (in_array('gateway_consent_id', $tableFields, true)) {
                $update['gateway_consent_id'] = $incomingConsentId;
            }
        }

        if ($hasAbdmConsentTable) {
            $this->db->table('abdm_consent_records')
                ->where('consent_handle', $handle)
                ->update($update);
        }

        if ($hasConsentAliasTable) {
            $consentFields = $this->db->getFieldNames('consents') ?? [];
            $consentUpdate = [];
            if (in_array('expiry', $consentFields, true)) {
                $consentUpdate['expiry'] = $expiresAt !== '' ? $expiresAt : null;
            }
            if (in_array('scope', $consentFields, true) && $incomingStatus !== '') {
                $consentUpdate['scope'] = strtoupper($incomingStatus);
            }
            if (! empty($consentUpdate)) {
                $this->db->table('consents')->where('token', $handle)->update($consentUpdate);
            }
        }

        $this->syncM2ConsentLog(
            (int) ($existing['patient_id'] ?? 0),
            trim((string) ($existing['abha_id'] ?? '')) !== ''
                ? trim((string) ($existing['abha_id'] ?? ''))
                : trim((string) ($payload['abha_id'] ?? $payload['abha_address'] ?? '')),
            $incomingConsentId !== '' ? $incomingConsentId : $handle,
            (string) ($existing['purpose_code'] ?? ''),
            $incomingStatus,
            $expiresAt !== '' ? $expiresAt : ((string) ($existing['expires_at'] ?? ''))
        );

        return $this->response->setJSON([
            'ok' => 1,
            'consent_handle' => $handle,
            'status' => $incomingStatus,
            'gateway_consent_id' => $incomingConsentId !== '' ? $incomingConsentId : null,
        ]);
    }

    // =========================================================================
    // HIP Consent Notify Callback — gateway forwards consent notify updates.
    // POST /AbdmGateway/hip_consent_notify_callback (no auth filter — public webhook)
    // =========================================================================

    public function hipConsentNotifyCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error_text' => 'Invalid JSON payload',
            ]);
        }

        $consentObj = [];
        if (is_array($payload['consent'] ?? null)) {
            $consentObj = (array) $payload['consent'];
        } elseif (is_array($payload['consentDetail'] ?? null)) {
            $consentObj = (array) $payload['consentDetail'];
        }

        $requestId = trim((string) (
            $payload['request_id']
            ?? $payload['requestId']
            ?? ($payload['response']['requestId'] ?? '')
            ?? ''
        ));
        $consentId = trim((string) (
            $consentObj['id']
            ?? $payload['consent_id']
            ?? $payload['consentId']
            ?? ''
        ));
        $abhaAddress = trim((string) (
            $consentObj['patient']['id']
            ?? $payload['abha_address']
            ?? $payload['abha_id']
            ?? ''
        ));
        $statusRaw = trim((string) (
            $consentObj['status']
            ?? $payload['consent_status']
            ?? $payload['status']
            ?? 'status_checked'
        ));
        $status = strtolower($statusRaw);

        // Keep existing M3 workflow in sync for dateRange and consent status lookups.
        try {
            $m3Service = new \App\Libraries\Abdm\M3HiuWorkflowService();
            $m3Service->ingestConsentUpdateWebhook($payload);
        } catch (\Throwable $e) {
            // Fail-open: callback must still ACK to avoid upstream retry storms.
        }

        if ($this->db->tableExists('consent_logs')) {
            $logStatus = match ($status) {
                'granted', 'approved', 'active' => 'GRANTED',
                'revoked', 'denied' => 'REVOKED',
                'expired' => 'EXPIRED',
                default => 'GRANTED',
            };

            $dateRange = $consentObj['permission']['dateRange'] ?? [];
            $expiresAt = trim((string) ($dateRange['to'] ?? $payload['expires_at'] ?? ''));

            $this->db->table('consent_logs')->insert([
                'patient_id' => (int) ($payload['patient_id'] ?? 0),
                'abha_id' => $abhaAddress !== '' ? $abhaAddress : null,
                'consent_id' => $consentId !== '' ? $consentId : ($requestId !== '' ? $requestId : null),
                'purpose' => trim((string) ($consentObj['purpose']['text'] ?? '')) ?: null,
                'status' => $logStatus,
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'expires_at' => $expiresAt !== '' ? $expiresAt : null,
            ]);
        }

        if ($this->db->tableExists('abdm_api_logs')) {
            $this->db->table('abdm_api_logs')->insert([
                'channel' => 'bridge',
                'event_type' => 'abdm.hip.consent.notify.callback',
                'endpoint' => '/AbdmGateway/hip_consent_notify_callback',
                'http_method' => 'POST',
                'entity_type' => 'consent',
                'entity_id' => $consentId !== '' ? $consentId : $requestId,
                'request_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_code' => 202,
                'response_json' => (string) json_encode([
                    'ok' => 1,
                    'status' => 'accepted',
                    'request_id' => $requestId,
                    'consent_id' => $consentId,
                    'consent_status' => $statusRaw,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => 'success',
                'error_message' => null,
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            ]);
        }

        return $this->response->setStatusCode(202)->setJSON([
            'ok' => 1,
            'status' => 'accepted',
            'request_id' => $requestId,
            'consent_id' => $consentId !== '' ? $consentId : null,
            'consent_status' => $statusRaw,
        ]);
    }

    public function scanShareLookup()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $qrPayload = trim((string) $this->request->getPost('qr_payload'));
        if ($qrPayload === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'qr_payload is required']);
        }

        if (strlen($qrPayload) < 8) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid QR payload']);
        }

        $abhaId = '';
        if (preg_match('/\b(\d{14})\b/', $qrPayload, $match) === 1) {
            $abhaId = (string) ($match[1] ?? '');
        }

        $payload = [
            'qr_payload' => $qrPayload,
            'abha_id_hint' => $abhaId,
            'requested_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ];

        $queueId = null;
        $result = [];
        try {
            $result  = $this->connector->scanShareLookup($qrPayload, $abhaId, $payload);
            $queueId = (int) ($result['queue_id'] ?? $result['id'] ?? (($result['data']['queue_id'] ?? 0)));
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error_text' => $e->getMessage(),
                'abha_id_hint' => $abhaId,
                'abha_address' => $abhaId,
            ]);
        }

        $scanStatus = [
            'ok' => 1,
            'status' => 'queued',
            'queue_id' => $queueId > 0 ? $queueId : null,
            'abha_id_hint' => $abhaId,
            'abha_address' => $abhaId,
            'status_url' => $queueId > 0 ? base_url('AbdmGateway/scan_share_lookup_status/' . $queueId) : null,
            'message' => 'Scan & Share lookup queued to center server.',
        ];

        if (is_array($result) && ! empty($result)) {
            $scanStatus['bridge_result'] = $result;
        }

        return $this->response->setJSON($scanStatus);
    }

    public function scanShareLookupStatus(int $queueId)
    {
        if ($queueId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid queue id']);
        }

        if (! $this->db->tableExists('bridge_sync_queue')) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'bridge_sync_queue table not found']);
        }

        $row = $this->db->table('bridge_sync_queue')
            ->select('id, event_type, status, attempts, max_attempts, last_error, created_at, updated_at, sent_at, entity_type, entity_id')
            ->where('id', $queueId)
            ->get(1)
            ->getRowArray();

        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Queue record not found']);
        }

        if ((string) ($row['event_type'] ?? '') !== 'abdm.scan_share.lookup') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Queue record is not a scan-share lookup event']);
        }

        $status = strtolower((string) ($row['status'] ?? 'pending'));
        $isDone = in_array($status, ['sent', 'failed'], true) ? 1 : 0;

        $responseLog = null;
        if ($this->db->tableExists('abdm_api_logs')) {
            $likeToken = '"queue_id":' . $queueId;
            $responseLog = $this->db->table('abdm_api_logs')
                ->select('id, response_code, response_json, status, error_message, created_at')
                ->where('event_type', 'abdm.scan_share.lookup')
                ->like('request_json', $likeToken)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        $resultPayload = $this->getScanShareResultPayload($queueId);
        $resolved = is_array($resultPayload) ? $this->extractScanShareIdentity($resultPayload) : [];

        return $this->response->setJSON([
            'ok' => 1,
            'queue_id' => (int) ($row['id'] ?? 0),
            'event_type' => (string) ($row['event_type'] ?? ''),
            'status' => $status,
            'done' => $isDone,
            'attempts' => (int) ($row['attempts'] ?? 0),
            'max_attempts' => (int) ($row['max_attempts'] ?? 0),
            'last_error' => (string) ($row['last_error'] ?? ''),
            'entity_type' => (string) ($row['entity_type'] ?? ''),
            'entity_id' => (string) ($row['entity_id'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'sent_at' => (string) ($row['sent_at'] ?? ''),
            'bridge_log' => $responseLog,
            'resolved_data' => $resolved,
        ]);
    }

    public function scanShareLookupResultCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload) || empty($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid callback payload']);
        }

        $queueId = (int) ($payload['queue_id'] ?? $payload['queueId'] ?? $payload['meta']['queue_id'] ?? 0);
        if ($queueId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'queue_id is required']);
        }

        $ok = (int) ($payload['ok'] ?? 1) === 1;
        $status = strtolower(trim((string) ($payload['status'] ?? ($ok ? 'sent' : 'failed'))));
        $errorText = trim((string) ($payload['error_text'] ?? $payload['error'] ?? ''));

        if ($this->db->tableExists('bridge_sync_queue')) {
            $this->db->table('bridge_sync_queue')
                ->where('id', $queueId)
                ->where('event_type', 'abdm.scan_share.lookup')
                ->update([
                    'status' => $status === 'failed' ? 'failed' : 'sent',
                    'sent_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    'last_error' => $errorText !== '' ? mb_substr($errorText, 0, 500) : null,
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                ]);
        }

        if ($this->db->tableExists('abdm_api_logs')) {
            $this->db->table('abdm_api_logs')->insert([
                'channel' => 'bridge',
                'event_type' => 'abdm.scan_share.lookup.result',
                'endpoint' => '/AbdmGateway/scan_share_lookup_result_callback',
                'http_method' => 'POST',
                'entity_type' => 'abha_scan',
                'entity_id' => (string) $queueId,
                'request_json' => (string) json_encode(['queue_id' => $queueId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_code' => $ok ? 200 : 500,
                'response_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $ok ? 'success' : 'error',
                'error_message' => $errorText !== '' ? mb_substr($errorText, 0, 1000) : null,
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
            ]);
        }

        return $this->response->setJSON(['ok' => 1, 'queue_id' => $queueId, 'status' => $status !== '' ? $status : 'sent']);
    }

    public function scanShareResolvePatient(int $queueId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $resultPayload = $this->getScanShareResultPayload($queueId);
        if (! is_array($resultPayload)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'No scan-share result found for queue']);
        }

        $identity = $this->extractScanShareIdentity($resultPayload);
        $matches = $this->findScanSharePatientMatches($identity);

        return $this->response->setJSON([
            'ok' => 1,
            'queue_id' => $queueId,
            'identity' => $identity,
            'matches' => $matches,
            'requires_confirmation' => count($matches) > 0 ? 1 : 0,
        ]);
    }

    public function scanShareLinkPatient(int $queueId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $action = strtolower(trim((string) ($this->request->getPost('action') ?? 'check')));
        if (! in_array($action, ['link_existing', 'create_new'], true)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid action']);
        }

        $identity = [
            'abha_number' => preg_replace('/\D/', '', (string) ($this->request->getPost('abha_number') ?? '')),
            'abha_address' => trim((string) ($this->request->getPost('abha_address') ?? '')),
            'patient_name' => trim((string) ($this->request->getPost('patient_name') ?? '')),
            'phone' => trim((string) ($this->request->getPost('phone') ?? '')),
            'gender' => strtoupper(trim((string) ($this->request->getPost('gender') ?? ''))),
            'dob' => trim((string) ($this->request->getPost('dob') ?? '')),
            'age' => trim((string) ($this->request->getPost('age') ?? $this->request->getPost('patient_age') ?? '')),
            'birth_year' => trim((string) ($this->request->getPost('birth_year') ?? $this->request->getPost('birthYear') ?? $this->request->getPost('year_of_birth') ?? '')),
        ];

        $db = $this->db;
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveFirstExistingColumn($fields, ['abha_id', 'abha_no', 'abha', 'abha_address']);

        $patientId = 0;
        $pCode = '';
        $isNew = false;

        if ($action === 'link_existing') {
            $existingPatientId = (int) ($this->request->getPost('existing_patient_id') ?? 0);
            if ($existingPatientId <= 0) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'existing_patient_id is required']);
            }

            $existing = $db->table('patient_master')
                ->select('id,p_code,mphone1' . ($abhaField ? ',' . $abhaField . ' AS patient_abha' : ''))
                ->where('id', $existingPatientId)
                ->get(1)
                ->getRowArray();
            if (! $existing) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'Selected patient not found']);
            }

            $patientId = (int) ($existing['id'] ?? 0);
            $pCode = (string) ($existing['p_code'] ?? '');

            $backfill = [];
            if ($abhaField && trim((string) ($existing['patient_abha'] ?? '')) === '' && ($identity['abha_number'] !== '' || $identity['abha_address'] !== '')) {
                $backfill[$abhaField] = $identity['abha_number'] !== '' ? $identity['abha_number'] : $identity['abha_address'];
            }
            if (trim((string) ($existing['mphone1'] ?? '')) === '' && $identity['phone'] !== '') {
                $backfill['mphone1'] = $identity['phone'];
            }
            if (! empty($backfill)) {
                $db->table('patient_master')->where('id', $patientId)->update($backfill);
            }
        }

        if ($action === 'create_new') {
            if ($identity['patient_name'] === '') {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_name is required to create patient']);
            }

            $created = $this->createPatientFromScanIdentity($identity, $abhaField);
            $patientId = (int) ($created['patient_id'] ?? 0);
            $pCode = (string) ($created['p_code'] ?? '');
            $isNew = true;
        }

        if ($patientId <= 0) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => 'Unable to resolve patient']);
        }

        if ($db->tableExists('bridge_sync_queue')) {
            $db->table('bridge_sync_queue')
                ->where('id', $queueId)
                ->where('event_type', 'abdm.scan_share.lookup')
                ->update([
                    'entity_type' => 'patient',
                    'entity_id' => (string) $patientId,
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                ]);
        }

        $this->getAuditService()->log([
            'action' => 'scan_share_patient_link',
            'entity_type' => 'abha_scan',
            'entity_id' => (string) $queueId,
            'patient_id' => $patientId,
            'abha_id' => (string) ($identity['abha_address'] !== '' ? $identity['abha_address'] : $identity['abha_number']),
            'request' => ['action' => $action, 'identity' => $identity],
            'response' => ['patient_id' => $patientId, 'p_code' => $pCode],
            'outcome' => 'success',
            'error_message' => null,
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'queue_id' => $queueId,
            'patient_id' => $patientId,
            'p_code' => $pCode,
            'is_new' => $isNew ? 1 : 0,
            'profile_url' => base_url('Patient/person_record/' . $patientId),
            'edit_url' => base_url('Patient/person_record/' . $patientId . '/1'),
        ]);
    }

    public function sharePrescriptionBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $opdId = (int) $this->request->getPost('opd_id');
        $sessionId = (int) $this->request->getPost('opd_session_id');
        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) $this->request->getPost('abha_id'));
        $abhaAddressPost = trim((string) $this->request->getPost('abha_address'));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));
        $pushToGateway = $this->resolveGatewayPushMode();
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        $logBridge = function (string $status, array $responsePayload, string $errorMessage = '', string $eventType = 'abdm.opd.prescription.share') use ($opdId, $patientId, $now, $pushToGateway): void {
            if (! $this->db->tableExists('abdm_api_logs')) {
                return;
            }

            $this->db->table('abdm_api_logs')->insert([
                'channel' => 'bridge',
                'event_type' => $eventType,
                'endpoint' => '/AbdmGateway/share_prescription_bundle',
                'http_method' => 'POST',
                'entity_type' => 'opd',
                'entity_id' => (string) $opdId,
                'request_json' => (string) json_encode([
                    'opd_id' => $opdId,
                    'patient_id' => $patientId,
                    'push_to_gateway' => $pushToGateway ? 1 : 0,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_code' => 200,
                'response_json' => (string) json_encode($responsePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
                'created_at' => $now,
            ]);
        };

        if ($opdId <= 0 || $patientId <= 0) {
            $logBridge('error', ['ok' => 0, 'error_text' => 'opd_id and patient_id are required'], 'opd_id and patient_id are required', 'abdm.opd.prescription.share.validation');
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_id and patient_id are required']);
        }

        if (! $this->db->tableExists('opd_fhir_documents')) {
            $logBridge('error', ['ok' => 0, 'error_text' => 'opd_fhir_documents table not found'], 'opd_fhir_documents table not found', 'abdm.opd.prescription.share.validation');
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_fhir_documents table not found']);
        }

        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId, $abhaAddressPost);
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        $hasAbha = $abhaNumber !== '' || $abhaAddress !== '';
        $consent = null;
        $consentWarning = '';
        $consentEventType = 'abdm.opd.prescription.share.consent_missing';
        $consentLogStatus = 'warning';
        if ($hasAbha) {
            $consent = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
            if ($consent === null) {
                if ($pushToGateway) {
                    $consentWarning = 'No active consent found. Proceeding with care-context push only.';
                } else {
                    $consentWarning = 'No active consent found now; this is expected in M2 local mode until ABDM consent callback arrives.';
                    $consentEventType = 'abdm.opd.prescription.share.consent_pending';
                    $consentLogStatus = 'success';
                }

                $logBridge(
                    $consentLogStatus,
                    [
                        'ok' => 1,
                        'note' => $consentWarning,
                        'push_to_gateway' => $pushToGateway ? 1 : 0,
                    ],
                    $pushToGateway ? $consentWarning : '',
                    $consentEventType
                );
            }
        }

        $builder = $this->db->table('opd_fhir_documents')
            ->where('opd_id', $opdId)
            ->whereIn('bundle_type', ['OPConsultRecord', 'MedicationRequestBundle', 'PrescriptionRecord']);
        if ($sessionId > 0) {
            $builder->where('opd_session_id', $sessionId);
        }
        $bundleRow = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();

        if (empty($bundleRow)) {
            $logBridge('error', ['ok' => 0, 'error_text' => 'No FHIR bundle found for selected OPD/session'], 'No FHIR bundle found for selected OPD/session', 'abdm.opd.prescription.share.validation');
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'No FHIR bundle found for selected OPD/session']);
        }

        $bundleJson = (string) ($bundleRow['bundle_json'] ?? '{}');
        $bundle = json_decode($bundleJson, true);
        if (! is_array($bundle)) {
            $bundle = ['raw' => $bundleJson];
        }

        $patientRow = $this->loadPatientRow($patientId);
        $patientName = $this->patientDisplayName($patientRow);

        $patientBirthYear = $this->resolvePatientBirthYear($patientRow, $abhaAddress, $abhaNumber);

        if ($abhaAddress === '' && $abhaNumber === '') {
            $logBridge('error', ['ok' => 0, 'error_text' => 'ABHA address or ABHA number is required for record push.'], 'ABHA address or ABHA number is required for record push.', 'abdm.opd.prescription.share.validation');
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'ABHA address or ABHA number is required for record push.',
            ]);
        }

        $bundleType = trim((string) ($bundleRow['bundle_type'] ?? 'OPConsultRecord'));
        $hiType = match ($bundleType) {
            'MedicationRequestBundle', 'Prescription', 'PrescriptionRecord' => 'PrescriptionRecord',
            default => 'OPConsultRecord',
        };
        $consentHandleResolved = is_array($consent) ? trim((string) ($consent['consent_handle'] ?? '')) : '';
        $consentExternalId = is_array($consent) ? $this->resolveConsentExternalId($consent) : '';
        $sessionForRef = $sessionId > 0 ? $sessionId : (int) ($bundleRow['opd_session_id'] ?? 0);
        $visitDateRaw = trim((string) ($bundleRow['created_at'] ?? ''));
        $visitDate = $visitDateRaw !== '' ? date('Y-m-d', strtotime($visitDateRaw)) : date('Y-m-d');
        $careContextRef = trim((string) ($this->request->getPost('careContextId') ?? $this->request->getPost('care_context_reference') ?? ''));
        if ($careContextRef === '') {
            $careContextRef = $hiType === 'PrescriptionRecord'
                ? 'PRESCRIPTION-' . $opdId . '-S' . ($sessionForRef > 0 ? $sessionForRef : 0) . '-' . $visitDate
                : 'OPD-' . $opdId . '-S' . ($sessionForRef > 0 ? $sessionForRef : 0) . '-' . $visitDate;
        }
        $careContextDisplay = $hiType === 'PrescriptionRecord'
            ? 'Prescription - ' . $visitDate
            : 'OPD Visit - ' . $visitDate;

        $payload = [
            'opd_id' => $opdId,
            'opd_session_id' => (int) ($bundleRow['opd_session_id'] ?? 0),
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'consent_handle' => $consentHandleResolved,
            'consent_id' => $consentExternalId,
            'bundle_type' => (string) ($bundleRow['bundle_type'] ?? 'MedicationRequestBundle'),
            'bundle' => $bundle,
        ];

        // Reuse the latest matching OPD health record to avoid duplicate rows on repeat submit.
        $healthRecordId = 0;
        if ($this->db->tableExists('health_records')) {
            $hrFields = $this->db->getFieldNames('health_records') ?? [];
            $hrSelectFields = array_values(array_unique(array_filter([
                'id',
                'push_status',
                'care_context_reference',
                in_array('record_data', $hrFields, true) ? 'record_data' : null,
                in_array('updated_at', $hrFields, true) ? 'updated_at' : null,
                in_array('created_at', $hrFields, true) ? 'created_at' : null,
            ])));

            $hrBuilder = $this->db->table('health_records')
                ->select(implode(',', $hrSelectFields))
                ->where('entity_type', 'opd')
                ->where('entity_id', (string) $opdId);
            if (in_array('care_context_reference', $hrFields, true)) {
                $hrBuilder->where('care_context_reference', $careContextRef);
            }

            $existingHr = $hrBuilder
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();

            if (! empty($existingHr)) {
                $existingPush = strtolower(trim((string) ($existingHr['push_status'] ?? '')));
                $existingRecordData = (string) ($existingHr['record_data'] ?? '');
                $isSameBundle = $existingRecordData !== '' ? hash('sha256', $existingRecordData) === hash('sha256', $bundleJson) : false;
                if ($isSameBundle || in_array($existingPush, ['queued', 'linked', 'local_discovery_ready', 'local_only'], true)) {
                    $healthRecordId = (int) ($existingHr['id'] ?? 0);
                }
            }
        }

        if ($healthRecordId <= 0) {
            // Store FHIR payload in health_records before pushing
            $healthRecordId = $this->storeHealthRecord([
                'patient_id'     => $patientId,
                'abha_id'        => $hasAbha ? $abhaId : '',
                'hi_type'        => 'OPConsultRecord',
                'entity_type'    => 'opd',
                'entity_id'      => (string) $opdId,
                'fhir_bundle'    => $bundleJson,
                'care_context_reference' => $careContextRef,
                'consent_handle' => $consentHandleResolved,
            ]);
        }

        if (! $hasAbha) {
            if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update([
                        'push_status' => 'local_only',
                        'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    ]);
            }

            $this->getAuditService()->log([
                'action'      => 'store_local_record',
                'entity_type' => 'opd',
                'entity_id'   => (string) $opdId,
                'patient_id'  => $patientId,
                'request'     => ['opd_id' => $opdId, 'care_context_reference' => $careContextRef],
                'response'    => ['health_record_id' => $healthRecordId],
                'outcome'     => 'success',
            ]);

            return $this->response->setJSON([
                'ok' => 1,
                'status' => 'local_stored',
                'queue_id' => null,
                'bridge_record_id' => null,
                'care_context_reference' => $careContextRef,
                'message' => 'ABHA not available. Record stored locally for later discovery.',
            ]);
        }

        // M2 contract mode: HMS remains source-of-truth and gateway reads via
        // discovery + health-information/fetch callbacks. Do not proactively push
        // to /records/push unless explicitly requested.
        if (! $pushToGateway) {
            if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update([
                        'push_status' => 'local_discovery_ready',
                        'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    ]);
            }

            if ($this->db->tableExists('record_links')) {
                $existingLink = $this->db->table('record_links')
                    ->where('care_context_reference', $careContextRef)
                    ->where('abha_id', $abhaAddress !== '' ? $abhaAddress : $abhaNumber)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();

                $linkData = [
                    'abha_id' => $abhaAddress !== '' ? $abhaAddress : $abhaNumber,
                    'care_context_reference' => $careContextRef,
                    'link_status' => 'pending_discovery',
                    'linked_at' => null,
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    'response_json' => (string) json_encode([
                        'mode' => 'm2_hms_source',
                        'push_to_gateway' => 0,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

                if (! empty($existingLink)) {
                    $this->db->table('record_links')
                        ->where('id', (int) $existingLink['id'])
                        ->update($linkData);
                } else {
                    $linkData['created_at'] = Time::now('Asia/Kolkata')->toDateTimeString();
                    $this->db->table('record_links')->insert($linkData);
                }
            }

            $responsePayload = [
                'ok' => 1,
                'queue_id' => null,
                'bridge_record_id' => null,
                'consent_handle' => $consentHandleResolved,
                'gateway_consent_id' => $consentExternalId !== '' ? $consentExternalId : null,
                'status' => 'local_discovery_ready',
                'care_context_reference' => $careContextRef,
                'mode' => 'm2_hms_source',
                'push_to_gateway' => 0,
                'message' => 'M2 mode active: care context registered in HMS. Gateway will discover and fetch consent-scoped FHIR from HMS callbacks.',
                'info' => $consentWarning !== '' ? $consentWarning : null,
                'warning' => null,
                'error' => null,
            ];

            $logBridge(
                'success',
                $responsePayload,
                '',
                'abdm.opd.prescription.share.result'
            );

            return $this->response->setJSON($responsePayload);
        }

        $queueId = null;
        $bridgeRecordId = 0;
        $status = 'queued';
        $connectorError = null;
        try {
            $result = $this->connector->pushRecord([
                'patient_id'             => (string) $patientId,
                'patient_name'           => $patientName !== '' ? $patientName : ('PATIENT-' . $patientId),
                'abha_id'                => $abhaNumber,
                'abha_address'           => $abhaAddress,
                'year_of_birth'          => $patientBirthYear,
                'hi_type'                => $hiType,
                'record_type'            => $hiType,
                'visit_date'             => $visitDate,
                'care_context_reference' => $careContextRef,
                'care_context_display'   => $careContextDisplay,
                'notes'                  => $careContextDisplay,
                'queue_id'               => $careContextRef,
                'record_data'            => $bundle,
            ]);
            $this->logGatewayPushResolution('opd', $result);

            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);

            $httpCode = $this->extractGatewayPushHttpCode($result);
            $resultOk = $this->isGatewayPushSubmitted($result);
            $isDuplicate = $this->isGatewayPushDuplicate($result);

            if ($isDuplicate) {
                $resultOk = true;
                $status = 'duplicate';
            }

            if (! $resultOk) {
                $connectorError = $this->extractGatewayPushErrorText($result);
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
                $status = 'failed';
            } elseif (! $isDuplicate) {
                $status = 'queued';
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
            $status = 'failed';
        }

        // Update health_record with txn_id and create record_link
        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError, $bridgeRecordId);
        }

        $this->getAuditService()->log([
            'action'      => 'push_record',
            'entity_type' => 'opd',
            'entity_id'   => (string) $opdId,
            'abha_id'     => $abhaId,
            'patient_id'  => $patientId,
                'request'     => ['opd_id' => $opdId, 'hi_type' => 'OPConsultRecord', 'consent_handle' => $consentHandleResolved],
            'response'    => ['queue_id' => $queueId],
            'outcome'     => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        $message = $connectorError === null
            ? ($consentWarning !== ''
                ? 'Record pushed to gateway without active consent. Recheck link status in M2 ABDM Gateway.'
                : 'Record pushed to gateway successfully.')
            : ($connectorError !== '' ? $connectorError : 'Bridge push failed');

        $responsePayload = [
            'ok' => $connectorError === null ? 1 : 0,
            'queue_id' => $queueId,
            'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            'consent_handle' => $consentHandleResolved,
            'gateway_consent_id' => $consentExternalId !== '' ? $consentExternalId : null,
            'status' => $status,
            'message' => $message,
            'warning' => $consentWarning !== '' ? $consentWarning : null,
            'error' => $connectorError,
        ];

        $logBridge(
            $connectorError === null ? 'success' : 'error',
            $responsePayload,
            $connectorError !== null ? (string) $connectorError : ($consentWarning !== '' ? $consentWarning : ''),
            'abdm.opd.prescription.share.result'
        );

        return $this->response->setJSON($responsePayload);
    }

    public function shareIpdDischargeBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $ipdId         = (int) $this->request->getPost('ipd_id');
        $patientId     = (int) $this->request->getPost('patient_id');
        $abhaId        = trim((string) $this->request->getPost('abha_id'));
        $abhaAddressPost = trim((string) $this->request->getPost('abha_address'));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($ipdId <= 0 || $patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ipd_id and patient_id are required']);
        }

        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId, $abhaAddressPost);
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        $hasAbha = $abhaNumber !== '' || $abhaAddress !== '';
        $consent = null;
        $consentWarning = '';
        if ($hasAbha) {
            $consent = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
            if ($consent === null) {
                $consentWarning = 'No active consent found. Proceeding with care-context push only.';
                $this->logMissingConsentShareWarning('ipd', $ipdId, $patientId, $abhaId, $consentHandle, 'abdm.ipd.discharge.share.consent_missing', $consentWarning);
            }
        }
        $effectiveConsent = (string) ($consent['consent_handle'] ?? $consentHandle);

        $fhirPayload = $this->buildIpdDischargeGatewayPayload($ipdId, $patientId, $abhaId);
        if ($fhirPayload === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Unable to prepare IPD discharge FHIR payload']);
        }

        $ipdRow = (array) ($fhirPayload['ipd_row'] ?? []);
        $patName = (string) ($fhirPayload['patient_name'] ?? '');
        $doctorName = (string) ($fhirPayload['doctor_name'] ?? '');
        $visitDate = (string) ($fhirPayload['visit_date'] ?? date('Y-m-d'));
        $birthYear = (int) ($fhirPayload['year_of_birth'] ?? 0);
        $ccRef = (string) ($fhirPayload['care_context_reference'] ?? ('IPD-' . $ipdId . '-' . $visitDate));
        $ccDisplay = (string) ($fhirPayload['care_context_display'] ?? ('Discharge Summary ' . $visitDate));
        $bundle = (array) ($fhirPayload['bundle'] ?? []);
        $attachmentPath = trim((string) ($fhirPayload['attachment_path'] ?? ''));
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ── Store health_record ───────────────────────────────────────────────
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'DischargeSummaryRecord',
            'entity_type'    => 'ipd',
            'entity_id'      => (string) $ipdId,
            'fhir_bundle'    => $bundleJson,
            'care_context_reference' => $ccRef,
            'consent_handle' => $effectiveConsent,
            'attachment_path' => $attachmentPath,
        ]);

        if (! $hasAbha) {
            if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update([
                        'push_status' => 'local_only',
                        'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    ]);
            }

            $this->getAuditService()->log([
                'action'      => 'store_local_record',
                'entity_type' => 'ipd',
                'entity_id'   => (string) $ipdId,
                'patient_id'  => $patientId,
                'request'     => ['ipd_id' => $ipdId, 'care_context_reference' => $ccRef],
                'response'    => ['health_record_id' => $healthRecordId],
                'outcome'     => 'success',
            ]);

            return $this->response->setJSON([
                'ok' => 1,
                'status' => 'local_stored',
                'queue_id' => null,
                'consent_handle' => null,
                'message' => 'ABHA not available. Record stored locally for later discovery.',
            ]);
        }

        // ── Push via pushRecord() (POST /v3/records/push) ─────────────────────
        $queueId       = null;
        $bridgeRecordId = 0;
        $connectorError = null;
        try {
            $result = $this->connector->pushRecord([
                'patient_id'             => (string) $patientId,
                'patient_name'           => $patName !== '' ? $patName : ('PATIENT-' . $patientId),
                'abha_id'                => $abhaNumber,
                'abha_address'           => $abhaAddress,
                'year_of_birth'          => $birthYear,
                'hi_type'                => 'DischargeSummaryRecord',
                'record_type'            => 'DischargeSummaryRecord',
                'visit_date'             => $visitDate,
                'doctor_name'            => $doctorName,
                'care_context_reference' => $ccRef,
                'care_context_display'    => $ccDisplay,
                'notes'                  => $ccDisplay,
                'queue_id'               => 'IPD-' . $ipdId . '-' . $visitDate,
                'record_data'            => $bundle,
            ]);
            $this->logGatewayPushResolution('ipd_discharge', $result);
            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);
            if (! $this->isGatewayPushSubmitted($result)) {
                $connectorError = $this->extractGatewayPushErrorText($result);
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, $queueId ?? '', $connectorError, $bridgeRecordId);
        }

        $this->getAuditService()->log([
            'action'        => 'push_record',
            'entity_type'   => 'ipd',
            'entity_id'     => (string) $ipdId,
            'abha_id'       => $abhaId,
            'patient_id'    => $patientId,
            'request'       => ['ipd_id' => $ipdId, 'hi_type' => 'DischargeSummaryRecord', 'consent_handle' => $effectiveConsent],
            'response'      => ['queue_id' => $queueId, 'warning' => $consentWarning],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'             => $connectorError === null ? 1 : 0,
            'queue_id'       => $queueId,
            'consent_handle' => $effectiveConsent,
            'status'         => $connectorError === null ? 'queued' : 'failed',
            'message'        => $connectorError === null && $consentWarning !== '' ? 'Record pushed to gateway without active consent. Recheck link status in M2 ABDM Gateway.' : null,
            'warning'        => $consentWarning !== '' ? $consentWarning : null,
            'error'          => $connectorError,
        ]);
    }

    public function shareDiagnosisReportBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'AJAX only']);
        }

        $labReqId      = (int) ($this->request->getPost('lab_req_id') ?? 0);
        $patientId     = (int) ($this->request->getPost('patient_id') ?? 0);
        $abhaId        = trim((string) ($this->request->getPost('abha_id') ?? ''));
        $abhaAddressPost = trim((string) ($this->request->getPost('abha_address') ?? ''));
        $consentHandle = trim((string) ($this->request->getPost('consent_handle') ?? ''));

        if ($labReqId <= 0 || $patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error' => 'lab_req_id and patient_id are required']);
        }

        // ── Load lab request ──────────────────────────────────────────────────
        $labReq = $this->db->table('lab_request')
            ->select('id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time')
            ->where('id', $labReqId)
            ->get(1)
            ->getRow();

        if (! $labReq) {
            return $this->response->setJSON(['ok' => 0, 'error' => 'Lab request not found']);
        }

        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId, $abhaAddressPost);
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        $hasAbha = $abhaNumber !== '' || $abhaAddress !== '';
        $consentRecord = null;
        $consentWarning = '';
        if ($hasAbha) {
            $consentRecord = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
            if ($consentRecord === null) {
                $consentWarning = 'No active consent found. Proceeding with care-context push only.';
                $this->logMissingConsentShareWarning('lab', $labReqId, $patientId, $abhaId, $consentHandle, 'abdm.diagnosis.report.share.consent_missing', $consentWarning);
            }
        }
        $effectiveConsent = (string) ($consentRecord['consent_handle'] ?? $consentHandle);

        // ── Load patient ──────────────────────────────────────────────────────
        $patientRow = [];
        if ($this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }
        $patName = $this->patientDisplayName($patientRow);
        if ($patName === '') {
            $patName = trim((string) ($labReq->patient_name ?? ''));
        }
        $patientBirthYear = $this->resolvePatientBirthYear($patientRow, str_contains($abhaId, '@') ? $abhaId : '', str_contains($abhaId, '@') ? '' : $abhaId);

        // ── Load test / charge name ────────────────────────────────────────────
        $testTitle = '';
        $chargeId  = (int) ($labReq->charge_id ?? 0);
        if ($chargeId > 0 && $this->db->tableExists('charge_master')) {
            $chargeRow = $this->db->table('charge_master')->select('charge_name, charge_type')->where('id', $chargeId)->get(1)->getRowArray() ?? [];
            $testTitle = trim((string) ($chargeRow['charge_name'] ?? ''));
        }
        if ($testTitle === '') {
            $testTitle = $this->mapLabTypeToTitle((int) ($labReq->lab_type ?? 0));
        }

        // ── Load hospital profile ─────────────────────────────────────────────
        $hospitalProfile = $this->getHospitalProfileForFhir();

        // ── Parse reported time ───────────────────────────────────────────────
        $reportedRaw  = trim((string) ($labReq->reported_time ?? ''));
        $reportedAt   = $reportedRaw !== '' ? (new \DateTime($reportedRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';
        $visitDate    = $reportedRaw !== '' ? date('Y-m-d', strtotime($reportedRaw)) : date('Y-m-d');

        // ── Build FHIR DiagnosticReportRecord bundle ───────────────────────────
        $fhir = new FhirR4Builder();

        $patient = [
            'id'          => (string) $patientId,
            'name'        => $patName,
            'gender'      => trim((string) ($patientRow['gender'] ?? '')),
            'birthDate'   => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
            'abhaAddress' => $abhaId,
        ];

        $diagnosticReport = [
            'id'           => (string) $labReqId,
            'title'        => $testTitle ?: 'Laboratory Report',
            'status'       => $labReq->status == 1 ? 'final' : 'preliminary',
            'conclusion'   => trim((string) ($labReq->report_data_Impression ?? '')),
            'reported_at'  => $reportedAt,
            'report_html'  => trim((string) ($labReq->Report_Data ?? '')),
        ];
        $isImaging = (int) ($labReq->lab_type ?? 0) === 6;
        if ($isImaging) {
            $diagnosticReport['is_imaging'] = true;
            $diagnosticReport['report_domain'] = 'imaging';
            $diagnosticReport['section_title'] = 'Computed tomography imaging report';
            $diagnosticReport['section_snomed_code'] = '371531008';
            $diagnosticReport['section_snomed_display'] = 'Computed tomography imaging report';
        }

        // ── Load LOINC code for the panel from lab_repo ───────────────────────
        $labRepoRow = $this->db->table('lab_request lr')
            ->select('lr.lab_repo_id, repo.loinc_code AS repo_loinc_code, repo.Title')
            ->join('lab_repo repo', 'repo.mstRepoKey = lr.lab_repo_id', 'left')
            ->where('lr.id', $labReqId)
            ->get(1)
            ->getRowArray() ?? [];

        $repoLoincCode = trim((string) ($labRepoRow['repo_loinc_code'] ?? ''));
        if ($repoLoincCode !== '') {
            $diagnosticReport['loinc_code'] = $repoLoincCode;
        }

        // ── Build structured observations from lab_request_item + lab_tests ───
        $observations = $this->buildLabObservations($labReqId, (string) ($labReq->status ?? '0'));

        $organization = $hospitalProfile['name'] !== ''
            ? ['name' => $hospitalProfile['name'], 'hfr_id' => $hospitalProfile['hfr_id']]
            : null;
        $practitioner = $this->resolveLabPractitionerForFhir($labReqId, $labReq);

        $encounter = [
            'id'           => 'LAB-' . $labReqId,
            'status'       => 'finished',
            'class_code'   => 'AMB',
            'period_start' => $reportedAt,
        ];

        $pdfAttachment = $this->buildDiagnosticDigitalSharePdf(
            $patientRow,
            $abhaIdentity,
            $diagnosticReport,
            $observations,
            $practitioner,
            $hospitalProfile,
            $labReqId
        );

        $bundle     = $fhir->buildLabReportBundle($patient, $diagnosticReport, $observations, $practitioner, $organization, $encounter, $pdfAttachment);
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ── Store health_record ───────────────────────────────────────────────
        $ccRef = 'LAB-' . $labReqId . '-' . $visitDate;
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'DiagnosticReportRecord',
            'entity_type'    => 'lab',
            'entity_id'      => (string) $labReqId,
            'fhir_bundle'    => $bundleJson,
            'care_context_reference' => $ccRef,
            'consent_handle' => $effectiveConsent,
        ]);

        if (! $hasAbha) {
            if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update([
                        'push_status' => 'local_only',
                        'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                    ]);
            }

            $this->getAuditService()->log([
                'action'      => 'store_local_record',
                'entity_type' => 'lab',
                'entity_id'   => (string) $labReqId,
                'patient_id'  => $patientId,
                'request'     => ['lab_req_id' => $labReqId, 'care_context_reference' => $ccRef],
                'response'    => ['health_record_id' => $healthRecordId],
                'outcome'     => 'success',
            ]);

            return $this->response->setJSON([
                'ok' => 1,
                'status' => 'local_stored',
                'queue_id' => null,
                'consent_handle' => null,
                'message' => 'ABHA not available. Record stored locally for later discovery.',
            ]);
        }

        // ── Push via pushRecord() (POST /v3/records/push) ─────────────────────
        $queueId        = null;
        $bridgeRecordId = 0;
        $connectorError = null;
        try {
            $result = $this->connector->pushRecord([
                'patient_id'             => (string) $patientId,
                'patient_name'           => $patName,
                'abha_id'                => $abhaNumber,
                'abha_address'           => $abhaAddress,
                'year_of_birth'          => $patientBirthYear,
                'hi_type'                => 'DiagnosticReportRecord',
                'record_type'            => 'DiagnosticReportRecord',
                'visit_date'             => $visitDate,
                'care_context_reference' => $ccRef,
                'care_context_display'    => $testTitle !== '' ? $testTitle : 'Lab Report',
                'notes'                  => $testTitle !== '' ? $testTitle : 'Lab Report',
                'queue_id'               => 'LAB-' . $labReqId . '-' . $visitDate,
                'record_data'            => $bundle,
            ]);
            $this->logGatewayPushResolution('diagnostic_report', $result);
            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);
            if (! $this->isGatewayPushSubmitted($result)) {
                $connectorError = $this->extractGatewayPushErrorText($result);
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, $queueId ?? '', $connectorError, $bridgeRecordId);
        }

        $this->getAuditService()->log([
            'action'        => 'push_record',
            'entity_type'   => 'lab',
            'entity_id'     => (string) $labReqId,
            'abha_id'       => $abhaId,
            'patient_id'    => $patientId,
            'request'       => ['lab_req_id' => $labReqId, 'hi_type' => 'DiagnosticReportRecord', 'consent_handle' => $effectiveConsent],
            'response'      => ['queue_id' => $queueId, 'warning' => $consentWarning],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'             => $connectorError === null ? 1 : 0,
            'queue_id'       => $queueId,
            'consent_handle' => $effectiveConsent,
            'status'         => $connectorError === null ? 'queued' : 'failed',
            'message'        => $connectorError === null && $consentWarning !== '' ? 'Record pushed to gateway without active consent. Recheck link status in M2 ABDM Gateway.' : null,
            'warning'        => $consentWarning !== '' ? $consentWarning : null,
            'error'          => $connectorError,
        ]);
    }

    public function diagnosisReportFhirPreview()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $labReqId = (int) ($this->request->getGet('lab_req_id') ?? $this->request->getPost('lab_req_id') ?? 0);
        $patientId = (int) ($this->request->getGet('patient_id') ?? $this->request->getPost('patient_id') ?? 0);
        $abhaId = trim((string) ($this->request->getGet('abha_id') ?? $this->request->getPost('abha_id') ?? ''));

        if ($labReqId <= 0 || $patientId <= 0 || $abhaId === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'lab_req_id, patient_id and abha_id are required.',
            ]);
        }

        $labReq = $this->db->table('lab_request')
            ->select('id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time')
            ->where('id', $labReqId)
            ->get(1)
            ->getRow();

        if (! $labReq) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Lab request not found.',
            ]);
        }

        $patientRow = [];
        if ($this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }
        $patName = $this->patientDisplayName($patientRow);
        if ($patName === '') {
            $patName = trim((string) ($labReq->patient_name ?? ''));
        }

        $testTitle = '';
        $chargeId  = (int) ($labReq->charge_id ?? 0);
        if ($chargeId > 0 && $this->db->tableExists('charge_master')) {
            $chargeRow = $this->db->table('charge_master')->select('charge_name')->where('id', $chargeId)->get(1)->getRowArray() ?? [];
            $testTitle = trim((string) ($chargeRow['charge_name'] ?? ''));
        }
        if ($testTitle === '') {
            $testTitle = $this->mapLabTypeToTitle((int) ($labReq->lab_type ?? 0));
        }

        $hospitalProfile = $this->getHospitalProfileForFhir();

        $reportedRaw = trim((string) ($labReq->reported_time ?? ''));
        $reportedAt = $reportedRaw !== '' ? (new \DateTime($reportedRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';

        $diagnosticReport = [
            'id'           => (string) $labReqId,
            'title'        => $testTitle ?: 'Laboratory Report',
            'status'       => ((string) ($labReq->status ?? '0')) === '1' ? 'final' : 'preliminary',
            'conclusion'   => trim((string) ($labReq->report_data_Impression ?? '')),
            'reported_at'  => $reportedAt,
            'report_html'  => trim((string) ($labReq->Report_Data ?? '')),
        ];
        $isImaging = (int) ($labReq->lab_type ?? 0) === 6;
        if ($isImaging) {
            $diagnosticReport['is_imaging'] = true;
            $diagnosticReport['report_domain'] = 'imaging';
            $diagnosticReport['section_title'] = 'Computed tomography imaging report';
            $diagnosticReport['section_snomed_code'] = '371531008';
            $diagnosticReport['section_snomed_display'] = 'Computed tomography imaging report';
        }

        $labRepoRow = $this->db->table('lab_request lr')
            ->select('lr.lab_repo_id, repo.loinc_code AS repo_loinc_code')
            ->join('lab_repo repo', 'repo.mstRepoKey = lr.lab_repo_id', 'left')
            ->where('lr.id', $labReqId)
            ->get(1)
            ->getRowArray() ?? [];

        $repoLoincCode = trim((string) ($labRepoRow['repo_loinc_code'] ?? ''));
        if ($repoLoincCode !== '') {
            $diagnosticReport['loinc_code'] = $repoLoincCode;
        }

        $observations = $this->buildLabObservations($labReqId, (string) ($labReq->status ?? '0'));

        $organization = $hospitalProfile['name'] !== ''
            ? ['name' => $hospitalProfile['name'], 'hfr_id' => $hospitalProfile['hfr_id']]
            : null;
        $practitioner = $this->resolveLabPractitionerForFhir($labReqId, $labReq);

        $encounter = [
            'id'           => 'LAB-' . $labReqId,
            'status'       => 'finished',
            'class_code'   => 'AMB',
            'period_start' => $reportedAt,
        ];

        $patient = [
            'id'          => (string) $patientId,
            'name'        => $patName,
            'gender'      => trim((string) ($patientRow['gender'] ?? '')),
            'birthDate'   => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
            'abhaAddress' => $abhaId,
        ];

        $fhir = new FhirR4Builder();
        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId);
        $pdfAttachment = $this->buildDiagnosticDigitalSharePdf(
            $patientRow,
            $abhaIdentity,
            $diagnosticReport,
            $observations,
            $practitioner,
            $hospitalProfile,
            $labReqId
        );

        $bundle = $fhir->buildLabReportBundle($patient, $diagnosticReport, $observations, $practitioner, $organization, $encounter, $pdfAttachment);

        return $this->response->setJSON([
            'status' => 'ok',
            'lab_req_id' => $labReqId,
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'bundle' => $bundle,
        ]);
    }

    public function immunizationFhirPreview()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $patientId = (int) ($this->request->getGet('patient_id') ?? $this->request->getPost('patient_id') ?? 0);
        $recordId = (int) ($this->request->getGet('record_id') ?? $this->request->getPost('record_id') ?? 0);
        $abhaId = trim((string) ($this->request->getGet('abha_id') ?? $this->request->getPost('abha_id') ?? ''));

        if ($patientId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'patient_id is required.',
            ]);
        }

        $payload = $this->buildImmunizationGatewayPayload($patientId, $recordId, $abhaId);
        if ($payload === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Unable to prepare ImmunizationRecord FHIR payload.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'patient_id' => $patientId,
            'record_id' => $recordId > 0 ? $recordId : null,
            'abha_id' => $abhaId,
            'care_context_reference' => (string) ($payload['care_context_reference'] ?? ''),
            'bundle' => (array) ($payload['bundle'] ?? []),
        ]);
    }

    public function shareImmunizationBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $recordId = (int) $this->request->getPost('record_id');
        $abhaId = trim((string) $this->request->getPost('abha_id'));
        $abhaAddressPost = trim((string) $this->request->getPost('abha_address'));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id is required']);
        }
        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId, $abhaAddressPost);
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        if ($abhaId === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'error_text' => 'ABHA ID is required to push ImmunizationRecord to ABDM Bridge.',
            ]);
        }

        $payload = $this->buildImmunizationGatewayPayload($patientId, $recordId, $abhaId);
        if ($payload === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Unable to prepare ImmunizationRecord FHIR payload']);
        }

        $bundle = (array) ($payload['bundle'] ?? []);
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ccRef = (string) ($payload['care_context_reference'] ?? ('IMM-' . ($recordId > 0 ? $recordId : ('PAT-' . $patientId))));
        $visitDate = (string) ($payload['visit_date'] ?? date('Y-m-d'));
        $patientName = (string) ($payload['patient_name'] ?? ('PATIENT-' . $patientId));

        $consent = $abhaId !== '' ? $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle) : null;
        $effectiveConsent = (string) ($consent['consent_handle'] ?? $consentHandle);

        $healthRecordId = $this->storeHealthRecord([
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'hi_type' => 'ImmunizationRecord',
            'entity_type' => 'immunization',
            'entity_id' => $recordId > 0 ? (string) $recordId : (string) $patientId,
            'fhir_bundle' => $bundleJson,
            'care_context_reference' => $ccRef,
            'consent_handle' => $effectiveConsent,
            'reuse_existing' => true,
        ]);
        if ($healthRecordId <= 0) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error_text' => 'Unable to persist ImmunizationRecord for M2 discovery.',
                'care_context_reference' => $ccRef,
            ]);
        }

        $queueId = null;
        $bridgeRecordId = 0;
        $firstPushedAt = '';
        $connectorError = null;
        $bridgeResponse = [];
        try {
            $result = $this->connector->pushRecord([
                'patient_id' => (string) $patientId,
                'patient_name' => $patientName,
                'abha_id' => $abhaNumber,
                'abha_address' => $abhaAddress,
                'year_of_birth' => (int) ($payload['year_of_birth'] ?? 0),
                'hi_type' => 'ImmunizationRecord',
                'record_type' => 'ImmunizationRecord',
                'visit_date' => $visitDate,
                'care_context_reference' => $ccRef,
                'care_context_display' => 'Immunization Record - ' . $visitDate,
                'notes' => 'Immunization Record - ' . $visitDate,
                'queue_id' => $ccRef,
                'record_data' => $bundle,
            ]);
            $this->logGatewayPushResolution('immunization', $result);
            $bridgeResponse = $result;
            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);
            $firstPushedAt = $this->extractGatewayPushFirstPushedAt($result);

            if (! $this->isGatewayPushSubmitted($result)) {
                $connectorError = $this->extractGatewayPushErrorText($result);
                if ($connectorError === '' && ! empty($result['errors'])) {
                    $connectorError = (string) json_encode($result['errors'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError, $bridgeRecordId, $firstPushedAt);
        }

        $this->getAuditService()->log([
            'action' => 'push_record',
            'entity_type' => 'immunization',
            'entity_id' => $recordId > 0 ? (string) $recordId : (string) $patientId,
            'abha_id' => $abhaId,
            'patient_id' => $patientId,
            'request' => ['patient_id' => $patientId, 'record_id' => $recordId, 'hi_type' => 'ImmunizationRecord'],
            'response' => [
                'queue_id' => $queueId,
                'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
                'http_code' => $bridgeResponse['http_code'] ?? null,
                'error_code' => $bridgeResponse['error_code'] ?? null,
            ],
            'outcome' => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        if ($this->db->tableExists('abdm_work_tasks')) {
            $taskStatus = $connectorError === null ? 'in_progress' : 'failed';
            $taskResult = $connectorError === null
                ? 'Submitted to ABDM Bridge at ' . $ccRef
                    . ($queueId ? '; queue ID ' . $queueId : '')
                    . ($bridgeRecordId > 0 ? '; record ID ' . $bridgeRecordId : '') . '.'
                : 'ABDM Bridge push failed for ' . $ccRef . ': ' . $connectorError;
            $this->db->table('abdm_work_tasks')
                ->where('task_type', 'immunization_record_publish')
                ->where('entity_type', 'immunization')
                ->where('entity_id', (string) $recordId)
                ->whereIn('status', ['pending', 'in_progress', 'failed'])
                ->update([
                    'status' => $taskStatus,
                    'last_action_result' => $taskResult,
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                ]);
        }

        return $this->response->setJSON([
            'ok' => $connectorError === null ? 1 : 0,
            'queue_id' => $queueId,
            'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            'health_record_id' => $healthRecordId,
            'consent_handle' => $effectiveConsent,
            'care_context_reference' => $ccRef,
            'status' => $connectorError === null ? 'queued' : 'failed',
            'error' => $connectorError,
        ]);
    }

    public function shareWellnessBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $opdId = (int) $this->request->getPost('opd_id');
        $abhaId = trim((string) ($this->request->getPost('abha_id') ?? $this->request->getPost('abha_address') ?? ''));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id is required']);
        }

        $payload = $this->buildWellnessRecordPayload($patientId, $opdId, $abhaId);
        if ($payload === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'No wellness/vital data found for this patient']);
        }

        if ($abhaId === '') {
            $abhaId = $this->resolvePatientAbhaIdentifier($patientId);
        }

        return $this->pushAdditionalHiRecord($payload, $patientId, $abhaId, $consentHandle);
    }

    public function shareHealthDocumentBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) ($this->request->getPost('abha_id') ?? $this->request->getPost('abha_address') ?? ''));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id is required']);
        }

        $payload = $this->buildHealthDocumentRecordPayload($patientId, $abhaId);
        if ($payload === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'document_title with document_text/document_base64/file_path is required']);
        }

        if ($abhaId === '') {
            $abhaId = $this->resolvePatientAbhaIdentifier($patientId);
        }

        return $this->pushAdditionalHiRecord($payload, $patientId, $abhaId, $consentHandle);
    }

    public function shareInvoiceBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $invoiceId = (int) $this->request->getPost('invoice_id');
        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) ($this->request->getPost('abha_id') ?? $this->request->getPost('abha_address') ?? ''));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($invoiceId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'invoice_id is required']);
        }

        $payload = $this->buildInvoiceRecordPayload($invoiceId, $patientId, $abhaId);
        if ($payload === null) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invoice not found or patient could not be resolved']);
        }

        if ($abhaId === '') {
            $abhaId = $this->resolvePatientAbhaIdentifier((int) $payload['patient_id']);
        }

        return $this->pushAdditionalHiRecord($payload, (int) $payload['patient_id'], $abhaId, $consentHandle);
    }

    public function invoiceFhirPreview()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $source = strtolower(trim((string) $this->request->getGet('source')));
        $billId = (int) $this->request->getGet('bill_id');
        $patientId = (int) $this->request->getGet('patient_id');
        $abhaId = trim((string) $this->request->getGet('abha_id'));

        if (! in_array($source, ['opd_invoice', 'charges_invoice', 'ipd_invoice'], true) || $billId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'A valid source and bill_id are required.',
            ]);
        }

        try {
            $payload = $this->buildInvoiceSourceRecordPayload($source, $billId, $patientId, $abhaId);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
        if ($payload === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Invoice not found or patient could not be resolved.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'source' => $source,
            'bill_id' => $billId,
            'patient_id' => (int) $payload['patient_id'],
            'hi_type' => 'InvoiceRecord',
            'care_context_reference' => (string) $payload['care_context_reference'],
            'bundle' => (array) $payload['bundle'],
        ]);
    }

    public function shareInvoiceSourceBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $source = strtolower(trim((string) $this->request->getPost('source')));
        $billId = (int) $this->request->getPost('bill_id');
        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) ($this->request->getPost('abha_id') ?? $this->request->getPost('abha_address') ?? ''));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if (! in_array($source, ['opd_invoice', 'charges_invoice', 'ipd_invoice'], true) || $billId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'A valid source and bill_id are required']);
        }

        try {
            $payload = $this->buildInvoiceSourceRecordPayload($source, $billId, $patientId, $abhaId);
        } catch (\RuntimeException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'error_text' => $e->getMessage(),
            ]);
        }
        if ($payload === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Invoice not found or patient could not be resolved']);
        }

        if ($abhaId === '') {
            $abhaId = $this->resolvePatientAbhaIdentifier((int) $payload['patient_id']);
        }

        return $this->pushAdditionalHiRecord($payload, (int) $payload['patient_id'], $abhaId, $consentHandle);
    }

    public function ipdDischargeFhirPreview()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'AJAX only']);
        }

        $ipdId = (int) ($this->request->getGet('ipd_id') ?? $this->request->getPost('ipd_id') ?? 0);
        $patientId = (int) ($this->request->getGet('patient_id') ?? $this->request->getPost('patient_id') ?? 0);
        $abhaId = trim((string) ($this->request->getGet('abha_id') ?? $this->request->getPost('abha_id') ?? ''));

        if ($ipdId <= 0 || $patientId <= 0 || $abhaId === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 'error',
                'message' => 'ipd_id, patient_id and abha_id are required.',
            ]);
        }

        $fhirPayload = $this->buildIpdDischargeGatewayPayload($ipdId, $patientId, $abhaId);
        if ($fhirPayload === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Unable to prepare IPD discharge FHIR payload.',
            ]);
        }
        $bundle = (array) ($fhirPayload['bundle'] ?? []);

        return $this->response->setJSON([
            'status' => 'ok',
            'ipd_id' => $ipdId,
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'bundle' => $bundle,
        ]);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildIpdDischargeGatewayPayload(int $ipdId, int $patientId, string $preferredAbhaId = ''): ?array
    {
        $ipdRow = $this->db->tableExists('ipd_master')
            ? ($this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [])
            : [];
        if (empty($ipdRow)) {
            return null;
        }

        $patientRow = [];
        if ($patientId > 0 && $this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }

        $patientName = $this->patientDisplayName($patientRow);
        if ($patientName === '') {
            $patientName = trim((string) ($ipdRow['P_name'] ?? ''));
        }

        $doctorName = trim((string) ($ipdRow['r_doc_name'] ?? ''));
        $doctorId = (int) ($ipdRow['r_doc_id'] ?? 0);
        if ($doctorId <= 0 && $this->db->tableExists('ipd_master_doc_list')) {
            $doctorLink = $this->db->table('ipd_master_doc_list')
                ->select('doc_id')
                ->where('ipd_id', $ipdId)
                ->where('doc_id >', 0)
                ->orderBy('id', 'ASC')
                ->get(1)
                ->getRowArray();
            $doctorId = (int) ($doctorLink['doc_id'] ?? 0);
        }
        $doctorRegNo = '';
        if ($doctorId > 0 && $this->db->tableExists('doctor_master')) {
            $dFields = $this->db->getFieldNames('doctor_master') ?? [];
            $dSelect = ['p_fname', 'p_lname'];
            foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $field) {
                if (in_array($field, $dFields, true)) {
                    $dSelect[] = $field;
                }
            }
            $dRow = $this->db->table('doctor_master')
                ->select(implode(',', array_unique($dSelect)))
                ->where('id', $doctorId)
                ->get(1)
                ->getRowArray() ?? [];
            if ($doctorName === '') {
                $doctorName = trim(trim((string) ($dRow['p_fname'] ?? '')) . ' ' . trim((string) ($dRow['p_lname'] ?? '')));
            }
            foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $field) {
                $candidate = trim((string) ($dRow[$field] ?? ''));
                if ($candidate !== '') {
                    $doctorRegNo = $candidate;
                    break;
                }
            }
        }

        $rawAbha = trim($preferredAbhaId);
        if ($rawAbha === '') {
            foreach (['abha_id', 'abha_no', 'abha'] as $field) {
                $candidate = trim((string) ($patientRow[$field] ?? ''));
                if ($candidate !== '') {
                    $rawAbha = $candidate;
                    break;
                }
            }
        }
        $abhaAddress = trim((string) ($patientRow['abha_address'] ?? ''));
        if ($abhaAddress === '' && strpos($rawAbha, '@') !== false) {
            $abhaAddress = $rawAbha;
        }

        $abhaDigits = preg_replace('/\D/', '', $rawAbha);
        $abhaDigits = is_string($abhaDigits) ? $abhaDigits : '';

        $admissionRaw = trim((string) ($ipdRow['register_date'] ?? ''));
        $dischargeRaw = trim((string) ($ipdRow['discharge_date'] ?? ''));
        $visitDate = $dischargeRaw !== ''
            ? date('Y-m-d', strtotime($dischargeRaw))
            : ($admissionRaw !== '' ? date('Y-m-d', strtotime($admissionRaw)) : date('Y-m-d'));

        $diagnoses = [];
        foreach ($this->ipdRows('ipd_discharge_diagnosis', ['comp_report'], $ipdId) as $row) {
            $text = trim((string) ($row['comp_report'] ?? ''));
            if ($text !== '') {
                $diagnoses[] = ['text' => $text, 'code' => ''];
            }
        }
        $chiefComplaints = [];
        foreach ($this->ipdRows('ipd_discharge_complaint', ['comp_report'], $ipdId) as $row) {
            $text = trim((string) ($row['comp_report'] ?? ''));
            if ($text !== '') {
                $chiefComplaints[] = ['text' => $text, 'code' => ''];
            }
        }
        if (empty($diagnoses)) {
            $problem = trim((string) ($ipdRow['problem'] ?? ''));
            if ($problem !== '') {
                $diagnoses[] = ['text' => $problem, 'code' => ''];
            }
        }
        $chiefComplaintNarrative = trim((string) (($this->ipdRows('ipd_discharge_complaint_remark', ['comp_remark'], $ipdId)[0]['comp_remark'] ?? '')));
        $diagnosisNarrative = trim((string) (($this->ipdRows('ipd_discharge_diagnosis_remark', ['comp_remark'], $ipdId)[0]['comp_remark'] ?? '')));

        $procedures = [];
        foreach ($this->ipdRows('ipd_discharge_surgery', ['surgery_name', 'surgery_date'], $ipdId) as $row) {
            $text = trim((string) ($row['surgery_name'] ?? ''));
            if ($text !== '') {
                $procedures[] = [
                    'text' => $text,
                    'code' => '',
                    'performed_at' => $this->toIsoDateTimeOrNow((string) ($row['surgery_date'] ?? '')),
                ];
            }
        }
        foreach ($this->ipdRows('ipd_discharge_procedure', ['procedure_name', 'procedure_date'], $ipdId) as $row) {
            $text = trim((string) ($row['procedure_name'] ?? ''));
            if ($text !== '') {
                $procedures[] = [
                    'text' => $text,
                    'code' => '',
                    'performed_at' => $this->toIsoDateTimeOrNow((string) ($row['procedure_date'] ?? '')),
                ];
            }
        }

        $medications = [];
        $legacyMeds = $this->ipdRows('ipd_discharge_prescrption_prescribed', ['med_name', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days'], $ipdId);
        if (empty($legacyMeds)) {
            $legacyMeds = $this->ipdRows('ipd_discharge_prescription_prescribed', ['med_name', 'dosage', 'dosage_when', 'dosage_freq', 'no_of_days'], $ipdId);
        }
        foreach ($legacyMeds as $row) {
            $name = trim((string) ($row['med_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $dosageText = $this->resolveDischargeMedDosageLabel(
                (int) ($row['dosage'] ?? 0),
                (int) ($row['dosage_when'] ?? 0),
                (int) ($row['dosage_freq'] ?? 0)
            );
            $days = trim((string) ($row['no_of_days'] ?? ''));
            $dosage = trim($dosageText . (($days !== '' && $days !== '0') ? ' for ' . $days . ' days' : ''));
            $medications[] = ['name' => $name, 'dosage' => $dosage];
        }
        if (empty($medications)) {
            foreach ($this->ipdRows('ipd_discharge_drug', ['drug_name', 'drug_dose', 'drug_day'], $ipdId) as $row) {
                $name = trim((string) ($row['drug_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $dosage = trim(implode(' ', array_filter([
                    (string) ($row['drug_dose'] ?? ''),
                    (string) ($row['drug_day'] ?? ''),
                ], static fn ($v) => trim((string) $v) !== '')));
                $medications[] = ['name' => $name, 'dosage' => $dosage];
            }
        }

        $observations = [];
        foreach ($this->ipdRows('ipd_discharge_1_b', ['short_head', 'rdata'], $ipdId, 'ipd_d_id') as $row) {
            $label = trim((string) ($row['short_head'] ?? ''));
            $value = trim((string) ($row['rdata'] ?? ''));
            if ($label === '' || ! $this->isMeaningfulClinicalValue($value)) {
                continue;
            }
            $observations[] = [
                'text' => $label,
                'value' => $value,
                'category' => 'Condition on Admission Time',
                'category_code' => 'vital-signs',
                'effective_at' => $this->toIsoDateTimeOrNow($admissionRaw),
            ];
        }
        foreach ($this->ipdRows('ipd_discharge_1_b_final', ['short_head', 'rdata'], $ipdId, 'ipd_d_id') as $row) {
            $label = trim((string) ($row['short_head'] ?? ''));
            $value = trim((string) ($row['rdata'] ?? ''));
            if ($label === '' || ! $this->isMeaningfulClinicalValue($value)) {
                continue;
            }
            $observations[] = [
                'text' => $label,
                'value' => $value,
                'category' => 'Condition on Discharge Time',
                'category_code' => 'vital-signs',
                'effective_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
            ];
        }

        $investigations = [];
        foreach ($this->ipdRows('ipd_discharge_1_d', ['short_head', 'rdata'], $ipdId, 'ipd_d_id') as $row) {
            $label = trim((string) ($row['short_head'] ?? ''));
            $value = trim((string) ($row['rdata'] ?? ''));
            if ($label === '' || ! $this->isMeaningfulClinicalValue($value)) {
                continue;
            }
            $investigations[] = [
                'text' => $label . ': ' . $value,
                'authored_on' => $this->toIsoDateTimeOrNow($admissionRaw),
            ];
        }
        foreach ($this->ipdRows('ipd_discharge_1_e', ['short_head', 'rdata'], $ipdId, 'ipd_d_id') as $row) {
            $label = trim((string) ($row['short_head'] ?? ''));
            $value = trim((string) ($row['rdata'] ?? ''));
            if ($label === '' || ! $this->isMeaningfulClinicalValue($value)) {
                continue;
            }
            $investigations[] = [
                'text' => $label . ': ' . $value,
                'authored_on' => $this->toIsoDateTimeOrNow($admissionRaw),
            ];
        }

        $allergies = [];
        $carePlans = [];
        $instructionRows = $this->ipdRows('ipd_discharge_instructions', ['comp_report', 'comp_remark', 'review_after'], $ipdId);
        if (! empty($instructionRows)) {
            $instructionRow = $instructionRows[0];
            $instructionMeta = $this->parseJsonArray((string) ($instructionRow['comp_report'] ?? ''));
            $nabhMeta = is_array($instructionMeta['nabh'] ?? null) ? $instructionMeta['nabh'] : [];

            $allergyStatus = strtolower(trim((string) ($nabhMeta['drug_allergy_status'] ?? '')));
            $allergyDetails = trim((string) ($nabhMeta['drug_allergy_details'] ?? ''));
            if ($allergyStatus !== '' || $allergyDetails !== '') {
                $allergies[] = [
                    'text' => $allergyDetails !== '' ? $allergyDetails : ('Drug allergy status: ' . $allergyStatus),
                    'note' => $allergyStatus,
                    'criticality' => $allergyStatus === 'yes' ? 'high' : 'low',
                    'recorded_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
                ];
            }

            $dischargeAdvice = trim((string) ($instructionRow['comp_remark'] ?? ''));
            if ($dischargeAdvice !== '') {
                $carePlans[] = [
                    'title' => 'Discharge Advice',
                    'description' => $dischargeAdvice,
                    'created_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
                ];
            }

            $otherAdvice = trim((string) ($instructionMeta['other_text'] ?? ''));
            if ($otherAdvice !== '') {
                $carePlans[] = [
                    'title' => 'Other Advice',
                    'description' => $otherAdvice,
                    'created_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
                ];
            }

            $reviewAfter = trim((string) ($instructionRow['review_after'] ?? ''));
            if ($reviewAfter !== '') {
                $carePlans[] = [
                    'title' => 'Follow Up',
                    'description' => 'Review After: ' . $reviewAfter,
                    'created_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
                ];
            }
        }

        $courseRows = $this->ipdRows('ipd_discharge_course_remark', ['comp_remark'], $ipdId);
        if (! empty($courseRows)) {
            $courseText = trim((string) ($courseRows[0]['comp_remark'] ?? ''));
            if ($courseText !== '') {
                $carePlans[] = [
                    'title' => 'Course In Hospital',
                    'description' => $courseText,
                    'created_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
                ];
            }
        }

        $hospital = $this->getHospitalProfileForFhir();
        $hfrId = trim((string) ($hospital['hfr_id'] ?? ''));
        $documents = $this->buildIpdPdfDocuments($ipdId, $dischargeRaw);

        $source = [
            'record_id' => (string) $ipdId,
            'bundle_identifier' => 'discharge-' . (trim((string) ($ipdRow['ipd_code'] ?? '')) ?: (string) $ipdId),
            'session_id' => (string) $ipdId,
            'visit_date' => $visitDate,
            'completed_at' => $this->toIsoDateTimeOrNow($dischargeRaw),
            'department' => trim((string) ($ipdRow['dept_id'] ?? '')),
            'doctor_name' => $doctorName,
            'doctor' => [
                'id' => $doctorId > 0 ? (string) $doctorId : '',
                'name' => $doctorName,
                'registration_number' => $doctorRegNo,
            ],
            'organization' => [
                'id' => $hfrId,
                'name' => trim((string) ($hospital['name'] ?? '')),
            ],
            'patient' => [
                'id' => (string) $patientId,
                'name' => $patientName,
                'gender' => $this->normalizeFhirGender(
                    ($g = trim((string) ($patientRow['gender'] ?? ''))) !== '' ? $g : trim((string) ($patientRow['xgender'] ?? ''))
                ),
                'dob' => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
                'abha_id' => $abhaDigits,
                'abha_address' => $abhaAddress,
            ],
            'encounter' => [
                'id' => trim((string) ($ipdRow['ipd_code'] ?? '')) ?: (string) $ipdId,
                'class_code' => 'IMP',
                'start' => $this->toIsoDateTimeOrNow($admissionRaw),
                'end' => $this->toIsoDateTimeOrNow($dischargeRaw),
            ],
            'chief_complaints' => $chiefComplaints,
            'chief_complaint_narrative' => $chiefComplaintNarrative,
            'diagnoses' => $diagnoses,
            'diagnosis_narrative' => $diagnosisNarrative,
            'procedures' => $procedures,
            'medications' => $medications,
            'observations' => $observations,
            'investigations' => $investigations,
            'allergies' => $allergies,
            'care_plans' => $carePlans,
            'documents' => $documents,
        ];

        $factory = new FhirGeneratorFactory();
        $output = $factory->discharge()->generate($source);
        $payload = (new GatewayPayloadAdapter())->toGatewayPayload($output, $source, $hfrId);

        return [
            'bundle' => (array) ($payload['fhir_bundle'] ?? []),
            'care_context_reference' => (string) ($payload['care_context_reference'] ?? ''),
            'care_context_display' => (string) ($payload['care_context_display'] ?? ''),
            'patient_name' => (string) ($payload['patient_name'] ?? $patientName),
            'doctor_name' => (string) ($payload['doctor_name'] ?? $doctorName),
            'visit_date' => (string) ($payload['visit_date'] ?? $visitDate),
            'ipd_row' => $ipdRow,
            'attachment_path' => (string) ($documents[0]['path'] ?? ''),
        ];
    }

    /** @return array<int,array<string,string>> */
    private function buildIpdPdfDocuments(int $ipdId, string $dischargeRaw): array
    {
        $directory = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'abdm' . DIRECTORY_SEPARATOR . 'ipd' . DIRECTORY_SEPARATOR . $ipdId;
        $createdAt = $this->toIsoDateTimeOrNow($dischargeRaw);
        $documents = [];
        foreach ([
            ['file' => 'discharge-summary.pdf', 'title' => 'IPD Discharge Summary', 'loinc' => '18842-5'],
        ] as $definition) {
            $path = $directory . DIRECTORY_SEPARATOR . $definition['file'];
            if (! is_file($path) || filesize($path) === 0) {
                continue;
            }
            $binary = file_get_contents($path);
            if (! is_string($binary) || ! str_starts_with($binary, '%PDF-')) {
                continue;
            }
            $documents[] = [
                'title' => $definition['title'],
                'loinc_code' => $definition['loinc'],
                'content_type' => 'application/pdf',
                'data' => base64_encode($binary),
                'created_at' => $createdAt,
                'path' => $path,
            ];
        }

        return $documents;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildImmunizationGatewayPayload(int $patientId, int $recordId = 0, string $preferredAbhaId = ''): ?array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_master') || ! $this->db->tableExists('immunization_records')) {
            return null;
        }

        $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        if (empty($patientRow)) {
            return null;
        }

        $recordsBuilder = $this->db->table('immunization_records r')
            ->select("r.*, s.series_name, s.series_doses, s.age_label,
                COALESCE(NULLIF(r.vaccine_code, ''), v.vaccine_code) AS vaccine_code,
                COALESCE(NULLIF(r.vaccine_code_system, ''), v.vaccine_code_system) AS vaccine_code_system,
                COALESCE(NULLIF(r.vaccine_name, ''), NULLIF(v.vaccine_display, ''), v.vaccine_name) AS vaccine_name,
                COALESCE(NULLIF(r.route_code, ''), v.route_code) AS route_code,
                COALESCE(NULLIF(r.route_name, ''), v.route_name) AS route_name,
                COALESCE(NULLIF(r.site_code, ''), v.site_code) AS site_code,
                COALESCE(NULLIF(r.site_name, ''), v.site_name) AS site_name,
                v.target_disease_code, v.target_disease_name", false)
            ->join('immunization_schedule_master s', 's.id = r.schedule_id', 'left')
            ->join('immunization_vaccine_master v', 'v.id = r.vaccine_master_id', 'left')
            ->where('r.patient_id', $patientId)
            ->orderBy('COALESCE(r.given_date, r.due_date)', 'ASC', false)
            ->orderBy('r.id', 'ASC');

        if ($recordId > 0) {
            $recordsBuilder->where('r.id', $recordId);
        }

        $records = $recordsBuilder->get()->getResultArray();
        if (empty($records)) {
            return null;
        }

        $patientName = $this->patientDisplayName($patientRow);
        $rawAbha = trim($preferredAbhaId);
        if ($rawAbha === '') {
            foreach (['abha_address', 'abha_id', 'abha_no', 'abha'] as $field) {
                $candidate = trim((string) ($patientRow[$field] ?? ''));
                if ($candidate !== '') {
                    $rawAbha = $candidate;
                    break;
                }
            }
        }

        $hospital = $this->getHospitalProfileForFhir();
        $practitioner = null;
        $performerId = 0;
        foreach ($records as $record) {
            $performerId = (int) ($record['performer_id'] ?? 0);
            if ($performerId > 0) {
                break;
            }
        }
        if ($performerId > 0 && $this->db->tableExists('doctor_master')) {
            $doctorRow = $this->db->table('doctor_master')->where('id', $performerId)->get(1)->getRowArray() ?? [];
            if (! empty($doctorRow)) {
                $doctorName = trim(trim((string) ($doctorRow['p_fname'] ?? '')) . ' ' . trim((string) ($doctorRow['p_lname'] ?? '')));
                $doctorRegNo = '';
                foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $field) {
                    $candidate = trim((string) ($doctorRow[$field] ?? ''));
                    if ($candidate !== '') {
                        $doctorRegNo = $candidate;
                        break;
                    }
                }
                $practitioner = ['id' => (string) $performerId, 'name' => $doctorName, 'registration_number' => $doctorRegNo];
            }
        }

        $latestDate = '';
        foreach ($records as $record) {
            $candidate = trim((string) ($record['given_date'] ?? $record['due_date'] ?? ''));
            if ($candidate !== '' && ($latestDate === '' || strtotime($candidate) > strtotime($latestDate))) {
                $latestDate = $candidate;
            }
        }
        $visitDate = $latestDate !== '' ? date('Y-m-d', strtotime($latestDate)) : date('Y-m-d');
        $ccRef = self::resolveImmunizationCareContextReference($records, $recordId, $patientId);
        if ($recordId > 0 && trim((string) ($records[0]['abdm_care_context_reference'] ?? '')) === '') {
            $this->db->table('immunization_records')
                ->where('id', $recordId)
                ->groupStart()
                    ->where('abdm_care_context_reference', null)
                    ->orWhere('abdm_care_context_reference', '')
                ->groupEnd()
                ->update(['abdm_care_context_reference' => $ccRef]);
        }

        $birthDate = '';
        foreach (['dob', 'birth_date', 'date_of_birth', 'p_dob'] as $field) {
            $candidate = trim((string) ($patientRow[$field] ?? ''));
            if ($candidate !== '' && strtotime($candidate) !== false) {
                $birthDate = date('Y-m-d', strtotime($candidate));
                break;
            }
        }

        $patient = [
            'id' => (string) $patientId,
            'name' => $patientName !== '' ? $patientName : ('PATIENT-' . $patientId),
            'gender' => (string) ($patientRow['gender'] ?? $patientRow['xgender'] ?? ''),
            'birthDate' => $birthDate,
            'abhaAddress' => $rawAbha,
            'phone' => (string) ($patientRow['mphone1'] ?? ''),
        ];

        $fhir = new FhirR4Builder();
        $bundle = $fhir->buildImmunizationRecordBundle($patient, $records, [
            'practitioner' => $practitioner,
            'organization' => ['name' => (string) ($hospital['name'] ?? ''), 'hfr_id' => (string) ($hospital['hfr_id'] ?? '')],
            'encounter' => ['id' => $recordId > 0 ? ('IMM-' . $recordId) : ('IMM-' . $patientId), 'status' => 'finished', 'period_start' => $latestDate],
            'care_context_reference' => $ccRef,
        ]);

        return [
            'bundle' => $bundle,
            'care_context_reference' => $ccRef,
            'care_context_display' => 'Immunization Record - ' . $visitDate,
            'patient_name' => $patient['name'],
            'year_of_birth' => $this->resolvePatientBirthYear($patientRow, str_contains($rawAbha, '@') ? $rawAbha : '', str_contains($rawAbha, '@') ? '' : $rawAbha),
            'visit_date' => $visitDate,
            'records' => $records,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $records
     */
    private static function resolveImmunizationCareContextReference(array $records, int $recordId, int $patientId): string
    {
        if ($recordId > 0) {
            $storedReference = trim((string) ($records[0]['abdm_care_context_reference'] ?? ''));
            return $storedReference !== '' ? $storedReference : 'IMM-' . $recordId;
        }

        $sourceIds = [];
        foreach ($records as $record) {
            $sourceId = (int) ($record['id'] ?? 0);
            if ($sourceId > 0) {
                $sourceIds[] = $sourceId;
            }
        }
        $sourceIds = array_values(array_unique($sourceIds));
        sort($sourceIds, SORT_NUMERIC);

        if (count($sourceIds) === 1) {
            return 'IMM-' . $sourceIds[0];
        }
        if ($sourceIds !== []) {
            return 'IMM-SET-' . substr(hash('sha256', implode(',', $sourceIds)), 0, 20);
        }

        return 'IMM-PAT-' . $patientId;
    }

    /**
     * @param array<int,string> $columns
     * @return array<int,array<string,mixed>>
     */
    private function ipdRows(string $table, array $columns, int $ipdId, string $ipdKey = 'ipd_id'): array
    {
        if ($ipdId <= 0 || ! $this->db->tableExists($table) || ! $this->db->fieldExists($ipdKey, $table)) {
            return [];
        }

        $available = [];
        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, $table)) {
                $available[] = $column;
            }
        }
        if (empty($available)) {
            return [];
        }
        if ($this->db->fieldExists('id', $table) && ! in_array('id', $available, true)) {
            $available[] = 'id';
        }

        $builder = $this->db->table($table)
            ->select(implode(',', $available))
            ->where($ipdKey, $ipdId);

        if ($this->db->fieldExists('id', $table)) {
            $builder->orderBy('id', 'ASC');
        }

        return $builder->get()->getResultArray();
    }

    /**
     * @return array<string,mixed>
     */
    private function parseJsonArray(string $json): array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function isMeaningfulClinicalValue(string $value): bool
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return false;
        }

        return ! in_array($normalized, ['-', '--', 'na', 'n/a', 'nil', 'none', 'not done'], true);
    }

    /** Resolve dose/when/freq IDs to a human-readable label for FHIR dosage text. */
    private function resolveDischargeMedDosageLabel(int $doseId, int $whenId, int $freqId): string
    {
        static $doseCache = null;
        static $whenCache = null;
        static $freqCache = null;
        $load = function (string $table) {
            $map = [];
            if (! $this->db->tableExists($table)) {
                return $map;
            }
            $fields = $this->db->getFieldNames($table) ?? [];
            $labelField = in_array('dose_desc', $fields, true) ? 'dose_desc'
                : (in_array('freq_desc', $fields, true) ? 'freq_desc'
                : (in_array('when_desc', $fields, true) ? 'when_desc' : null));
            if ($labelField === null) {
                return $map;
            }
            foreach ($this->db->table($table)->select('id,' . $labelField)->get()->getResultArray() as $r) {
                $map[(int) $r['id']] = trim((string) $r[$labelField]);
            }
            return $map;
        };
        if ($doseCache === null) { $doseCache = $load('opd_dose_shed'); }
        if ($whenCache === null) { $whenCache = $load('opd_dose_when'); }
        if ($freqCache === null) { $freqCache = $load('opd_dose_frequency'); }
        $parts = array_filter([
            $doseId > 0 ? ($doseCache[$doseId] ?? '') : '',
            $whenId > 0 ? ($whenCache[$whenId] ?? '') : '',
            $freqId > 0 ? ($freqCache[$freqId] ?? '') : '',
        ], fn ($v) => $v !== '');
        return implode(' ', $parts);
    }

    private function normalizeFhirGender(string $gender): string
    {
        $value = strtolower(trim($gender));
        if ($value === '1' || $value === 'm' || $value === 'male') {
            return 'male';
        }
        if ($value === '2' || $value === 'f' || $value === 'female') {
            return 'female';
        }
        if ($value === '3' || $value === 'other') {
            return 'other';
        }
        return 'unknown';
    }

    private function toIsoDateTimeOrNow(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return date(DATE_ATOM);
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return date(DATE_ATOM);
        }

        return date(DATE_ATOM, $timestamp);
    }

    public function nhcxClaimCreate()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('nhcx_claim_documents')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'nhcx_claim_documents table not found']);
        }

        $patientId = (int) $this->request->getPost('patient_id');
        $encounterId = (int) $this->request->getPost('encounter_id');
        $ipdId = (int) $this->request->getPost('ipd_id');
        $caseId = (int) $this->request->getPost('case_id');
        $totalAmount = (float) $this->request->getPost('total_amount');
        $claimType = trim((string) $this->request->getPost('claim_type')) ?: 'institutional';

        if ($patientId <= 0 || $encounterId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id and encounter_id are required']);
        }

        $patient = [
            'id' => (string) $patientId,
            'name' => (string) $this->request->getPost('patient_name'),
            'gender' => (string) $this->request->getPost('patient_gender'),
            'birthDate' => (string) $this->request->getPost('patient_birthdate'),
            'abhaAddress' => (string) $this->request->getPost('abha_address'),
        ];

        $encounter = [
            'id' => (string) $encounterId,
            'status' => 'finished',
        ];

        $items = $this->request->getPost('items');
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($items)) {
            $items = [];
        }

        $claim = [
            'id' => 'claim-' . $patientId . '-' . $encounterId . '-' . date('YmdHis'),
            'status' => 'active',
            'use' => 'claim',
            'type' => $claimType,
            'total' => $totalAmount,
            'provider' => (string) $this->request->getPost('provider_name'),
            'insurer' => (string) $this->request->getPost('insurer_name'),
            'priority' => (string) $this->request->getPost('priority'),
            'items' => $items,
        ];

        $fhir = new FhirR4Builder();
        $bundle = $fhir->buildClaimBundle($patient, $encounter, $claim);
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $this->db->table('nhcx_claim_documents')->insert([
            'ipd_id' => $ipdId > 0 ? $ipdId : null,
            'case_id' => $caseId > 0 ? $caseId : null,
            'patient_id' => $patientId,
            'claim_type' => $claimType,
            'claim_json' => $bundleJson,
            'claim_status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $documentId = (int) $this->db->insertID();
        $queueId = null;
        try {
            $result  = $this->connector->nhcxClaimCreate($bundle, $documentId, $patientId, $encounterId);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'document_id' => $documentId,
            'queue_id' => $queueId,
            'claim_status' => 'draft',
        ]);
    }

    public function nhcxClaimStatusRequest()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('nhcx_claim_documents')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'nhcx_claim_documents table not found']);
        }

        $documentId = (int) $this->request->getPost('document_id');
        if ($documentId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'document_id is required']);
        }

        $row = $this->db->table('nhcx_claim_documents')->where('id', $documentId)->get(1)->getRowArray();
        if (empty($row)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'claim document not found']);
        }

        $payload = [
            'nhcx_claim_document_id' => $documentId,
            'external_ref' => (string) ($row['external_ref'] ?? ''),
            'claim_status' => (string) ($row['claim_status'] ?? ''),
            'requested_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ];

        $queueId = null;
        try {
            $result  = $this->connector->nhcxClaimStatusRequest($documentId, $payload['external_ref'], $payload['claim_status'], $payload);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'document_id' => $documentId,
            'queue_id' => $queueId,
            'status' => 'queued',
        ]);
    }

    public function nhcxClaimStatusCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid JSON payload']);
        }

        if (! $this->db->tableExists('nhcx_claim_documents')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'nhcx_claim_documents table not found']);
        }

        $documentId = (int) ($payload['document_id'] ?? 0);
        $externalRef = trim((string) ($payload['external_ref'] ?? ''));
        $status = trim((string) ($payload['claim_status'] ?? 'unknown'));
        $errorMessage = trim((string) ($payload['error_message'] ?? ''));

        if ($documentId <= 0 && $externalRef === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'document_id or external_ref is required']);
        }

        $existingBuilder = $this->db->table('nhcx_claim_documents');
        if ($documentId > 0) {
            $existingBuilder->where('id', $documentId);
        } else {
            $existingBuilder->where('external_ref', $externalRef);
        }
        $existing = $existingBuilder->get(1)->getRowArray();
        if (empty($existing)) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error_text' => 'claim document not found',
            ]);
        }

        $currentStatus = strtolower(trim((string) ($existing['claim_status'] ?? 'unknown')));
        $incomingStatus = strtolower($status);
        $currentExternalRef = trim((string) ($existing['external_ref'] ?? ''));
        $currentError = trim((string) ($existing['error_message'] ?? ''));

        if ($this->isStaleClaimTransition($currentStatus, $incomingStatus)) {
            return $this->response->setJSON([
                'ok' => 1,
                'document_id' => (int) ($existing['id'] ?? $documentId),
                'external_ref' => $currentExternalRef,
                'claim_status' => $currentStatus,
                'ignored' => 1,
                'reason' => 'stale_transition',
            ]);
        }

        $isDuplicate = ($currentStatus === $incomingStatus)
            && ($currentExternalRef === $externalRef)
            && ($currentError === $errorMessage);
        if ($isDuplicate) {
            return $this->response->setJSON([
                'ok' => 1,
                'document_id' => (int) ($existing['id'] ?? $documentId),
                'external_ref' => $currentExternalRef,
                'claim_status' => $currentStatus,
                'duplicate' => 1,
            ]);
        }

        $builder = $this->db->table('nhcx_claim_documents')->where('id', (int) $existing['id']);

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $builder->update([
            'claim_status' => $incomingStatus,
            'external_ref' => $externalRef !== '' ? $externalRef : null,
            'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
            'pushed_at' => $incomingStatus === 'submitted'
                ? ((string) ($existing['pushed_at'] ?? '') !== '' ? (string) $existing['pushed_at'] : $now)
                : (string) ($existing['pushed_at'] ?? ''),
            'updated_at' => $now,
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'document_id' => (int) ($existing['id'] ?? $documentId),
            'external_ref' => $externalRef,
            'claim_status' => $incomingStatus,
        ]);
    }

    // =========================================================================
    // Record Linked Callback — ABDM notifies HMS when a record is successfully
    // linked to an ABHA address.
    // POST /AbdmGateway/record_linked_callback (no auth filter — public webhook)
    // =========================================================================

    public function recordLinkedCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid JSON payload']);
        }

        $abdmTxnId           = trim((string) ($payload['queue_id'] ?? $payload['abdm_txn_id'] ?? ''));
        $careContextRef      = trim((string) ($payload['care_context_reference'] ?? $payload['care_context_ref'] ?? ''));
        $abhaId              = trim((string) ($payload['abha_id'] ?? ''));
        $status              = strtolower(trim((string) ($payload['status'] ?? 'linked')));
        $now                 = Time::now('Asia/Kolkata')->toDateTimeString();

        if ($abdmTxnId === '' && $careContextRef === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'queue_id or care_context_reference is required']);
        }

        // Update health_records
        if ($this->db->tableExists('health_records')) {
            $hrBuilder = $this->db->table('health_records');
            if ($abdmTxnId !== '') {
                $hrBuilder->where('abdm_txn_id', $abdmTxnId);
            } elseif ($careContextRef !== '') {
                $hrBuilder->where('care_context_reference', $careContextRef);
            }
            $hrBuilder->update([
                'push_status'            => 'linked',
                'linked_at'              => $now,
                'care_context_reference' => $careContextRef !== '' ? $careContextRef : null,
                'updated_at'             => $now,
            ]);
        }

        // Insert / update record_links
        if ($this->db->tableExists('record_links')) {
            $existing = $this->db->table('record_links')
                ->where('abdm_txn_id', $abdmTxnId)
                ->get(1)
                ->getRowArray();

            $responseJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (! empty($existing)) {
                $this->db->table('record_links')
                    ->where('id', (int) $existing['id'])
                    ->update([
                        'link_status'            => $status,
                        'care_context_reference' => $careContextRef !== '' ? $careContextRef : (string) ($existing['care_context_reference'] ?? ''),
                        'response_json'          => $responseJson,
                        'linked_at'              => $now,
                        'updated_at'             => $now,
                    ]);
            } else {
                $this->db->table('record_links')->insert([
                    'abdm_txn_id'            => $abdmTxnId !== '' ? $abdmTxnId : null,
                    'care_context_reference' => $careContextRef !== '' ? $careContextRef : null,
                    'abha_id'                => $abhaId !== '' ? $abhaId : null,
                    'link_status'            => $status,
                    'response_json'          => $responseJson,
                    'linked_at'              => $now,
                    'created_at'             => $now,
                    'updated_at'             => $now,
                ]);
            }
        }

        $this->getAuditService()->log([
            'action'      => 'record_linked',
            'entity_type' => 'health_record',
            'entity_id'   => $abdmTxnId,
            'abha_id'     => $abhaId,
            'response'    => $payload,
            'outcome'     => $status === 'linked' ? 'success' : 'failure',
        ]);

        // Update patient_master.abdm_linked_at when a record is confirmed linked
        if ($status === 'linked' && $abhaId !== '' && $this->db->tableExists('patient_master')) {
            $pmFields = $this->db->getFieldNames('patient_master') ?? [];
            if (in_array('abdm_linked_at', $pmFields, true)) {
                $abhaFieldCandidates = ['abha_id', 'abha_no', 'abha_address', 'abha'];
                foreach ($abhaFieldCandidates as $col) {
                    if (in_array($col, $pmFields, true)) {
                        $this->db->table('patient_master')
                            ->where($col, $abhaId)
                            ->where('abdm_linked_at IS NULL', null, false)
                            ->update(['abdm_linked_at' => $now]);
                        break;
                    }
                }
            }
        }

        return $this->response->setJSON(['ok' => 1, 'status' => $status]);
    }

    // =========================================================================
    // Consent Revoked Callback — ABDM notifies HMS when consent is revoked.
    // POST /AbdmGateway/consent_revoked_callback (no auth filter — public webhook)
    // =========================================================================

    public function consentRevokedCallback()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid JSON payload']);
        }

        $consentHandle = trim((string) ($payload['consent_handle'] ?? ''));
        $abhaId        = trim((string) ($payload['abha_id'] ?? ''));
        $revokedAt     = trim((string) ($payload['revoked_at'] ?? ''));
        $now           = Time::now('Asia/Kolkata')->toDateTimeString();

        if ($consentHandle === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'consent_handle is required']);
        }

        $hasAbdmConsentTable = $this->db->tableExists('abdm_consent_records');
        $hasConsentAliasTable = $this->db->tableExists('consents');
        if (! $hasAbdmConsentTable && ! $hasConsentAliasTable) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'consent storage table not found']);
        }

        $existing = [];
        if ($hasAbdmConsentTable) {
            $existing = $this->db->table('abdm_consent_records')
                ->where('consent_handle', $consentHandle)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
        }

        if (empty($existing) && $hasConsentAliasTable) {
            $consentAliasRow = $this->db->table('consents')
                ->where('token', $consentHandle)
                ->get(1)
                ->getRowArray();
            if (! empty($consentAliasRow)) {
                $existing = [
                    'patient_id' => (int) ($consentAliasRow['patient_id'] ?? 0),
                    'consent_status' => 'approved',
                ];
            }
        }

        if (empty($existing)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'consent_handle not found']);
        }

        // Idempotency — already revoked
        if (strtolower((string) ($existing['consent_status'] ?? '')) === 'revoked') {
            return $this->response->setJSON(['ok' => 1, 'consent_handle' => $consentHandle, 'status' => 'revoked', 'duplicate' => 1]);
        }

        if ($hasAbdmConsentTable) {
            $this->db->table('abdm_consent_records')
                ->where('consent_handle', $consentHandle)
                ->update([
                    'consent_status'  => 'revoked',
                    'raw_payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at'      => $now,
                ]);
        }

        if ($hasConsentAliasTable) {
            $consentFields = $this->db->getFieldNames('consents') ?? [];
            $consentUpdate = [];
            if (in_array('scope', $consentFields, true)) {
                $consentUpdate['scope'] = 'REVOKED';
            }
            if (! empty($consentUpdate)) {
                $this->db->table('consents')->where('token', $consentHandle)->update($consentUpdate);
            }
        }

        $this->getAuditService()->log([
            'action'      => 'consent_revoke',
            'entity_type' => 'consent',
            'entity_id'   => $consentHandle,
            'abha_id'     => $abhaId,
            'patient_id'  => (int) ($existing['patient_id'] ?? 0),
            'response'    => $payload,
            'outcome'     => 'success',
        ]);

        $this->syncM2ConsentLog(
            (int) ($existing['patient_id'] ?? 0),
            $abhaId,
            $consentHandle,
            (string) ($existing['purpose_code'] ?? ''),
            'revoked',
            $revokedAt
        );

        return $this->response->setJSON(['ok' => 1, 'consent_handle' => $consentHandle, 'status' => 'revoked']);
    }

    // =========================================================================
    // Push a specific health_record to ABDM (user-triggered retry / initial push)
    // POST /AbdmGateway/push_health_record
    // =========================================================================

    public function pushHealthRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $hrId          = (int) $this->request->getPost('health_record_id');
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($hrId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'health_record_id is required']);
        }

        if (! $this->db->tableExists('health_records')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'health_records table not found']);
        }

        $hr = $this->db->table('health_records')
            ->where('id', $hrId)
            ->get(1)
            ->getRowArray();

        if (empty($hr)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Health record not found']);
        }

        $patientId = (int) ($hr['patient_id'] ?? 0);
        $abhaId    = trim((string) ($hr['abha_id'] ?? ''));
        $hiType    = trim((string) ($hr['hi_type'] ?? ''));

        if ($abhaId === '') {
            // Try from POST param as override
            $abhaId = trim((string) $this->request->getPost('abha_id'));
        }
        $abhaIdentity = $this->resolvePatientAbhaIdentity(
            $patientId,
            $abhaId,
            trim((string) $this->request->getPost('abha_address'))
        );
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;

        // Prefer the plain stored bundle; fall back to the encrypted copy for older rows.
        $storedPayload = [];
        $plainPayload = trim((string) ($hr['record_data'] ?? ''));
        if ($plainPayload !== '') {
            $storedPayload = json_decode($plainPayload, true) ?? [];
        } else {
            $encPayload = trim((string) ($hr['fhir_bundle_enc'] ?? ''));
            if ($encPayload !== '') {
                try {
                    $enc           = new \App\Libraries\FhirEncryptionService();
                    $decrypted     = $enc->decrypt($encPayload);
                    $storedPayload = json_decode($decrypted, true) ?? [];
                } catch (\Throwable $e) {
                    return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => 'Could not decrypt FHIR bundle: ' . $e->getMessage()]);
                }
            }
        }

        // Extract just the FHIR bundle from the stored payload (stored as full HMS payload)
        $fhirBundle = $storedPayload['bundle'] ?? $storedPayload['fhir_bundle'] ?? $storedPayload;

        $entityType  = trim((string) ($hr['entity_type'] ?? ''));
        $entityId    = trim((string) ($hr['entity_id'] ?? ''));
        $visitDate   = trim((string) ($storedPayload['visit_date'] ?? $storedPayload['reg_date'] ?? $storedPayload['reported_time'] ?? ''));
        $doctorName  = trim((string) ($storedPayload['doctor_name'] ?? $storedPayload['doctor'] ?? ''));
        $patientName = trim((string) ($storedPayload['patient_name'] ?? ''));
        $department  = trim((string) ($storedPayload['department'] ?? $storedPayload['dept_name'] ?? ''));

        $queueId      = null;
        $connectorErr = null;
        try {
            $patientRowForPush = $this->loadPatientRow($patientId);
            $sanitizedPatientName = trim((string) $patientName);
            if ($sanitizedPatientName === '' || preg_match('/\s0$/', $sanitizedPatientName) === 1) {
                $sanitizedPatientName = $this->patientDisplayName($patientRowForPush);
            }
            $patientBirthYear = $this->resolvePatientBirthYear(
                $patientRowForPush,
                str_contains($abhaId, '@') ? $abhaId : '',
                str_contains($abhaId, '@') ? '' : $abhaId
            );

            // Use the new store-and-link flow (POST /v3/records/push) for re-push:
            // No consent needed — bridge stores record and serves it when ABDM requests data.
            $result  = $this->connector->pushRecord([
                'patient_id'             => (string) $patientId,
                'patient_name'           => $sanitizedPatientName !== '' ? $sanitizedPatientName : ('PATIENT-' . $patientId),
                'abha_id'                => $abhaNumber,
                'abha_address'           => $abhaAddress,
                'year_of_birth'          => $patientBirthYear,
                'hi_type'                => $hiType,
                'record_type'            => $this->mapHiTypeToRecordType($hiType),
                'visit_date'             => $visitDate !== '' ? $visitDate : date('Y-m-d'),
                'doctor_name'            => $doctorName,
                'department'             => $department,
                'care_context_reference' => trim((string) ($hr['care_context_reference'] ?? '')),
                'care_context_display'    => trim((string) ($storedPayload['care_context_display'] ?? '')),
                'notes'                  => trim((string) ($storedPayload['care_context_display'] ?? '')),
                'queue_id'               => trim((string) ($hr['care_context_reference'] ?? '')),
                'record_data'            => $fhirBundle,
            ]);
            $this->logGatewayPushResolution('health_record_repush', $result);
            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);
            if (! $this->isGatewayPushSubmitted($result)) {
                $connectorErr = $this->extractGatewayPushErrorText($result);
                if ($connectorErr === '') {
                    $connectorErr = 'Bridge push failed';
                }
            }
        } catch (\Throwable $e) {
            $connectorErr   = $e->getMessage();
            $bridgeRecordId = 0;
        }

        $this->updateHealthRecordTxn($hrId, (string) ($queueId ?? ''), $connectorErr, $bridgeRecordId);

        $this->getAuditService()->log([
            'action'        => 'push_record',
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'abha_id'       => $abhaId,
            'patient_id'    => $patientId,
            'request'       => ['health_record_id' => $hrId, 'hi_type' => $hiType],
            'response'      => ['queue_id' => $queueId],
            'outcome'       => $connectorErr === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorErr ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'       => $connectorErr === null ? 1 : 0,
            'queue_id' => $queueId,
            'error'    => $connectorErr,
            'status'   => $connectorErr === null ? 'queued' : 'failed',
        ]);
    }

    // =========================================================================
    // List health_records for a patient (AJAX — used by ABDM task board)
    // GET /AbdmGateway/health_records_list?patient_id=X&abha_id=Y
    // =========================================================================

    public function healthRecordsList()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('health_records')) {
            return $this->response->setJSON(['ok' => 1, 'records' => [], 'total' => 0]);
        }

        $patientId = (int) $this->request->getGet('patient_id');
        $abhaId    = trim((string) $this->request->getGet('abha_id'));

        if ($patientId <= 0 && $abhaId === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id or abha_id is required']);
        }

        $builder = $this->db->table('health_records')
            ->select('id, patient_id, abha_id, hi_type, entity_type, entity_id, push_status, abdm_txn_id, care_context_reference, consent_handle, push_at, linked_at, created_at')
            ->orderBy('id', 'DESC')
            ->limit(50);

        if ($patientId > 0) {
            $builder->where('patient_id', $patientId);
        }
        if ($abhaId !== '') {
            $builder->where('abha_id', $abhaId);
        }

        $rows = $builder->get()->getResultArray();

        return $this->response->setJSON(['ok' => 1, 'records' => $rows, 'total' => count($rows)]);
    }

    // =========================================================================
    // M2 Discovery endpoint for gateway callbacks.
    // POST /records/discover
    // =========================================================================

    public function recordsDiscover()
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        if (! $this->db->tableExists('health_records')) {
            return $this->response->setJSON(['ok' => 1, 'careContexts' => [], 'count' => 0]);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $abhaAddress = trim((string) (
            $payload['abha_address']
            ?? $payload['abhaAddress']
            ?? $payload['patient']['abha_address']
            ?? $payload['patient']['id']
            ?? ''
        ));
        $mobile = preg_replace('/\D/', '', (string) (
            $payload['mobile']
            ?? $payload['phone']
            ?? $payload['patient']['mobile']
            ?? ''
        ));
        $patientIdentifier = trim((string) (
            $payload['patient_id']
            ?? $payload['patientId']
            ?? $payload['uhid']
            ?? $payload['UHID']
            ?? $payload['patient']['patient_id']
            ?? $payload['patient']['patientId']
            ?? $payload['patient']['uhid']
            ?? ''
        ));
        $birthYear = (int) (
            $payload['birth_year']
            ?? $payload['birthYear']
            ?? $payload['year_of_birth']
            ?? $payload['patient']['birth_year']
            ?? $payload['patient']['birthYear']
            ?? $payload['patient']['year_of_birth']
            ?? 0
        );
        $hospitalId = trim((string) (
            $payload['hospital_id']
            ?? $payload['patient_id']
            ?? $payload['patientRef']
            ?? $payload['patient_ref']
            ?? ''
        ));

        $patientIds = [];
        if ($this->db->tableExists('patient_master')) {
            $pmFields = $this->db->getFieldNames('patient_master') ?? [];
            $abhaCols = array_values(array_filter(['abha_address', 'abha_id', 'abha_no', 'abha'], fn ($c) => in_array($c, $pmFields, true)));

            if ($abhaAddress !== '' && ! empty($abhaCols)) {
                $b = $this->db->table('patient_master')->select('id');
                $b->groupStart();
                foreach ($abhaCols as $col) {
                    $b->orWhere($col, $abhaAddress);
                }
                $b->groupEnd();
                $rows = $b->get()->getResultArray();
                foreach ($rows as $r) {
                    $patientIds[] = (int) ($r['id'] ?? 0);
                }
            }

            if ($mobile !== '' && in_array('mphone1', $pmFields, true)) {
                $rows = $this->db->table('patient_master')->select('id')->where('mphone1', $mobile)->get()->getResultArray();
                foreach ($rows as $r) {
                    $patientIds[] = (int) ($r['id'] ?? 0);
                }
            }

            // PHR non-ABHA lookup: patient ID/UHID with optional birth year.
            if ($patientIdentifier !== '') {
                $idCols = array_values(array_filter(['p_code', 'uhid', 'uhid_no', 'patient_id', 'patient_code'], fn ($c) => in_array($c, $pmFields, true)));
                $b = $this->db->table('patient_master')->select('id');
                $b->groupStart();
                foreach ($idCols as $col) {
                    $b->orWhere($col, $patientIdentifier);
                }
                if (ctype_digit($patientIdentifier)) {
                    $b->orWhere('id', (int) $patientIdentifier);
                }
                $b->groupEnd();

                if ($birthYear >= 1900 && $birthYear <= (int) date('Y') && in_array('dob', $pmFields, true)) {
                    $b->where('YEAR(dob)', $birthYear, false);
                }

                $rows = $b->get()->getResultArray();
                foreach ($rows as $r) {
                    $patientIds[] = (int) ($r['id'] ?? 0);
                }
            }

            if ($hospitalId !== '' && in_array('p_code', $pmFields, true)) {
                $rows = $this->db->table('patient_master')->select('id')->where('p_code', $hospitalId)->get()->getResultArray();
                foreach ($rows as $r) {
                    $patientIds[] = (int) ($r['id'] ?? 0);
                }
            }
        }

        $patientIds = array_values(array_unique(array_filter($patientIds, fn ($v) => $v > 0)));

        $hrBuilder = $this->db->table('health_records')
            ->select('id, patient_id, abha_id, hi_type, care_context_reference, created_at, updated_at')
            ->orderBy('id', 'DESC')
            ->limit(200);

        if ($abhaAddress !== '') {
            $hrBuilder->groupStart()
                ->where('abha_id', $abhaAddress)
                ->orWhere('abha_id', preg_replace('/\D/', '', $abhaAddress))
            ->groupEnd();
        } elseif (! empty($patientIds)) {
            $hrBuilder->whereIn('patient_id', $patientIds);
        } else {
            return $this->response->setJSON(['ok' => 1, 'careContexts' => [], 'count' => 0]);
        }

        $rows = $hrBuilder->get()->getResultArray();
        $careContexts = [];
        foreach ($rows as $row) {
            $careContextId = trim((string) ($row['care_context_reference'] ?? ''));
            if ($careContextId === '') {
                $careContextId = 'HR-' . (int) ($row['id'] ?? 0);
            }
            $careContexts[] = [
                'careContextId' => $careContextId,
                'display' => (string) (($row['hi_type'] ?? 'HealthDocumentRecord') . ' - ' . ($row['created_at'] ?? $row['updated_at'] ?? '')),
                'record_type' => (string) ($row['hi_type'] ?? ''),
                'patient_id' => (int) ($row['patient_id'] ?? 0),
            ];
        }

        $this->getAuditService()->log([
            'action' => 'discovery_records',
            'entity_type' => 'health_record',
            'request' => $payload,
            'response' => ['count' => count($careContexts)],
            'outcome' => 'success',
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'careContexts' => $careContexts,
            'count' => count($careContexts),
        ]);
    }

    // =========================================================================
    // M2/M3 Fetch endpoint for care-context specific FHIR payload.
    // GET /records/fetch/{careContextId}
    // =========================================================================

    public function recordsFetch(string $careContextId)
    {
        $signatureFailure = $this->validateWebhookSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        if (! $this->db->tableExists('health_records')) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'health_records table not found']);
        }

        $careContextId = trim($careContextId);
        if ($careContextId === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'careContextId is required']);
        }

        $builder = $this->db->table('health_records')
            ->select('id, patient_id, abha_id, hi_type, care_context_reference, record_data, fhir_bundle_enc, created_at, updated_at')
            ->limit(1);

        if (preg_match('/^HR\-(\d+)$/i', $careContextId, $m) === 1) {
            $builder->where('id', (int) ($m[1] ?? 0));
        } else {
            $builder->where('care_context_reference', $careContextId);
        }

        $row = $builder->get()->getRowArray();
        if (empty($row)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'Record not found for careContextId']);
        }

        $payload = [];
        $plainPayload = trim((string) ($row['record_data'] ?? ''));
        if ($plainPayload !== '') {
            $payload = json_decode($plainPayload, true) ?? [];
        } else {
            $encPayload = trim((string) ($row['fhir_bundle_enc'] ?? ''));
            if ($encPayload !== '') {
                try {
                    $enc = new FhirEncryptionService();
                    $decrypted = $enc->decrypt($encPayload);
                    $payload = json_decode($decrypted, true) ?? [];
                } catch (\Throwable $e) {
                    return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => 'Unable to decode stored payload']);
                }
            }
        }

        $this->getAuditService()->log([
            'action' => 'fetch_record',
            'entity_type' => 'health_record',
            'entity_id' => (string) ($row['id'] ?? ''),
            'patient_id' => (int) ($row['patient_id'] ?? 0),
            'abha_id' => (string) ($row['abha_id'] ?? ''),
            'request' => ['careContextId' => $careContextId],
            'response' => ['ok' => 1],
            'outcome' => 'success',
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'careContextId' => $careContextId,
            'record_type' => (string) ($row['hi_type'] ?? ''),
            'patient_id' => (int) ($row['patient_id'] ?? 0),
            'abha_address' => (string) ($row['abha_id'] ?? ''),
            'fhir_payload' => $payload,
        ]);
    }

    // =========================================================================
    // Bridge record status — proxy to GET /api/v3/records/{id}
    // GET /AbdmGateway/bridge_record_status/:id
    // =========================================================================

    public function bridgeRecordStatus(int $bridgeId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        try {
            $result = $this->connector->workflowStatus($bridgeId);
        } catch (\Throwable $e) {
            // Backward compatibility with older connectors.
            $result = $this->connector->getRecord($bridgeId);
        }
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // Trigger HIP-initiated ABDM linking — POST /api/v3/records/{id}/share
    // POST /AbdmGateway/bridge_record_share/:id
    // =========================================================================

    public function bridgeRecordShare(int $bridgeId)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        try {
            $result = $this->connector->linkAndShare($bridgeId);
        } catch (\Throwable $e) {
            // Backward compatibility with older connectors.
            $result = $this->connector->triggerShare($bridgeId);
        }
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // Explicit e-Atria orchestration aliases for HMS integration docs.
    // =========================================================================

    public function bridgeRecordLinkShare(int $bridgeId)
    {
        return $this->bridgeRecordShare($bridgeId);
    }

    public function bridgeRecordWorkflowStatus(int $bridgeId)
    {
        return $this->bridgeRecordStatus($bridgeId);
    }

    // =========================================================================
    // OPD running token status — GET /api/v3/opd/running-token-status
    // GET /AbdmGateway/opd_running_token_status
    // =========================================================================

    public function opdRunningTokenStatus()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $result = $this->connector->opdRunningTokenStatus();
        return $this->response->setJSON($result);
    }

    /**
     * Resolve latest PDF for a lab/radiology context and return base64 attachment payload.
     *
     * @return array{content_type: string, data_base64: string, title: string}|null
     */
    private function loadLatestLabPdfAttachment(int $invoiceId, int $labType, int $labReqId = 0, string $defaultTitle = 'Lab Report PDF'): ?array
    {
        if ($invoiceId <= 0 || $labType <= 0 || ! $this->db->tableExists('file_upload_data')) {
            return null;
        }

        $fields = $this->db->getFieldNames('file_upload_data') ?? [];
        if (empty($fields)) {
            return null;
        }

        $selectParts = [];
        foreach (['id', 'file_name', 'orig_name', 'full_path', 'file_type', 'repo_id'] as $col) {
            if (in_array($col, $fields, true)) {
                $selectParts[] = $col;
            }
        }
        if (empty($selectParts)) {
            return null;
        }

        $builder = $this->db->table('file_upload_data')
            ->select(implode(',', $selectParts))
            ->where('charge_id', $invoiceId)
            ->where('charge_type', $labType);

        if (in_array('isdelete', $fields, true)) {
            $builder->where('isdelete', 0);
        }

        // Prefer exact report-level file; fall back to invoice-level compiled (repo_id=0).
        if (in_array('repo_id', $fields, true)) {
            $builder->whereIn('repo_id', [$labReqId, 0]);
            $builder->orderBy('CASE WHEN repo_id = ' . (int) $labReqId . ' THEN 0 WHEN repo_id = 0 THEN 1 ELSE 2 END', '', false);
        }

        $rows = $builder->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();
        if (empty($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            $fullPath = trim((string) ($row['full_path'] ?? ''));
            $fileName = trim((string) ($row['file_name'] ?? ''));
            $mimeType = trim((string) ($row['file_type'] ?? ''));
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $isPdf = $ext === 'pdf' || $mimeType === 'application/pdf';
            if (! $isPdf) {
                continue;
            }

            $absolutePath = $this->resolveStoredFileAbsolutePath($fullPath);
            if ($absolutePath === '' || ! is_file($absolutePath)) {
                continue;
            }

            $bytes = @file_get_contents($absolutePath);
            if ($bytes === false || $bytes === '') {
                continue;
            }

            $title = trim((string) ($row['orig_name'] ?? ''));
            if ($title === '') {
                $title = $fileName !== '' ? $fileName : $defaultTitle;
            }

            return [
                'content_type' => 'application/pdf',
                'data_base64' => base64_encode($bytes),
                'title' => $title,
            ];
        }

        if ($labReqId > 0) {
            $fallbackRow = $this->db->table('lab_request')
                ->select('Report_Data, patient_name')
                ->where('id', $labReqId)
                ->get(1)
                ->getRowArray() ?? [];

            $reportHtml = trim((string) ($fallbackRow['Report_Data'] ?? ''));
            if ($reportHtml !== '') {
                $fallbackTitle = trim((string) ($defaultTitle !== '' ? $defaultTitle : 'Lab Report PDF'));
                $generatedPdf = $this->renderReportHtmlToPdfAttachment($reportHtml, $fallbackTitle);
                if ($generatedPdf !== null) {
                    return $generatedPdf;
                }
            }
        }

        return null;
    }

    /**
     * Render report HTML to a PDF attachment payload without persisting it.
     *
     * @return array{content_type: string, data_base64: string, title: string}|null
     */
    private function renderReportHtmlToPdfAttachment(string $reportHtml, string $title = 'Lab Report PDF'): ?array
    {
        $reportHtml = trim($reportHtml);
        if ($reportHtml === '') {
            return null;
        }

        try {
            $wrappedHtml = '<!doctype html><html><head><meta charset="utf-8">'
                . '<style>body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111} table{border-collapse:collapse;width:100%} td,th{vertical-align:top}</style>'
                . '</head><body>' . $reportHtml . '</body></html>';

            $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
            if (! is_dir($mpdfTempDir)) {
                @mkdir($mpdfTempDir, 0755, true);
            }

            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTempDir,
                'default_font' => 'freeserif',
            ]);
            $mpdf->WriteHTML($wrappedHtml, HTMLParserMode::HTML_BODY);

            return [
                'content_type' => 'application/pdf',
                'data_base64'  => base64_encode($mpdf->Output($title . '.pdf', 'S')),
                'title'        => $title,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string,mixed> $patientRow
     * @param array{abha_id:string,abha_address:string} $abhaIdentity
     * @param array<string,mixed> $diagnosticReport
     * @param array<int,array<string,mixed>> $observations
     * @param array<string,mixed>|null $practitioner
     * @param array<string,mixed> $hospitalProfile
     */
    private function buildDiagnosticDigitalSharePdf(
        array $patientRow,
        array $abhaIdentity,
        array $diagnosticReport,
        array $observations,
        ?array $practitioner,
        array $hospitalProfile,
        int $labReqId
    ): ?array {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contentHtml = trim((string) ($diagnosticReport['report_html'] ?? ''));
        if ($contentHtml === '' && $observations !== []) {
            $rows = '';
            foreach ($observations as $observation) {
                $value = trim((string) ($observation['value'] ?? ''));
                $unit = trim((string) ($observation['unit'] ?? ''));
                $range = trim(implode(' - ', array_filter([
                    trim((string) ($observation['ref_low'] ?? '')),
                    trim((string) ($observation['ref_high'] ?? '')),
                ], static fn (string $part): bool => $part !== '')));
                $rows .= '<tr><td>' . $escape((string) ($observation['test_name'] ?? 'Test')) . '</td>'
                    . '<td>' . $escape(trim($value . ' ' . $unit)) . '</td>'
                    . '<td>' . $escape($range) . '</td>'
                    . '<td>' . $escape((string) ($observation['interpretation'] ?? '')) . '</td></tr>';
            }
            $contentHtml = '<table class="share-content-table"><thead><tr><th>Test</th><th>Result</th><th>Reference</th>'
                . '<th>Flag</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        }
        if ($contentHtml === '') {
            return null;
        }

        $conclusion = trim((string) ($diagnosticReport['conclusion'] ?? ''));
        if ($conclusion !== '') {
            $contentHtml .= '<div class="share-conclusion"><strong>Conclusion:</strong> ' . $escape($conclusion) . '</div>';
        }
        $title = trim((string) ($diagnosticReport['title'] ?? 'Diagnostic Report')) ?: 'Diagnostic Report';
        $abha = trim(implode(' / ', array_filter([
            $abhaIdentity['abha_id'],
            $abhaIdentity['abha_address'],
        ], static fn (string $value): bool => $value !== '')));

        return $this->renderDigitalSharePdf($contentHtml, 'Digital-Share-LAB-' . $labReqId, [
            'document_title' => $title,
            'patient_name' => $this->patientDisplayName($patientRow),
            'uhid' => trim((string) ($patientRow['p_code'] ?? '')),
            'abha' => $abha,
            'record_label' => 'Diagnostic Report',
            'record_code' => 'LAB-' . $labReqId,
            'reported_at' => (string) ($diagnosticReport['reported_at'] ?? ''),
            'practitioner' => trim((string) ($practitioner['name'] ?? '')),
            'facility_name' => trim((string) ($hospitalProfile['name'] ?? '')),
        ]);
    }

    /**
     * Render the stable PDF layout used for digital/PHR sharing.
     *
     * @param array<string,string> $metadata
     * @return array{content_type:string,data_base64:string,title:string,size:int,hash:string}|null
     */
    private function renderDigitalSharePdf(string $contentHtml, string $fileStem, array $metadata): ?array
    {
        $contentHtml = trim($contentHtml);
        if ($contentHtml === '') {
            return null;
        }

        try {
            $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $hospital = $this->getHospitalProfileForFhir();
            $hospitalName = trim((string) ($metadata['facility_name'] ?? $hospital['name'] ?? '')) ?: 'Healthcare Facility';
            $hospitalAddress = trim((string) ($hospital['address'] ?? ''));
            $hospitalContact = trim(implode(' | ', array_filter([
                trim((string) ($hospital['phone'] ?? '')),
                trim((string) ($hospital['email'] ?? '')),
            ], static fn (string $value): bool => $value !== '')));
            $logoHtml = '';
            $logoName = trim((string) ($hospital['logo'] ?? ''));
            foreach (array_filter([
                $logoName !== '' ? FCPATH . 'assets/images/' . ltrim($logoName, '/\\') : '',
                $logoName !== '' ? FCPATH . 'assets/img/' . ltrim($logoName, '/\\') : '',
                FCPATH . 'assets/img/logo.png',
            ]) as $logoPath) {
                if (is_file($logoPath)) {
                    $logoHtml = '<img src="' . $escape(str_replace('\\', '/', $logoPath)) . '" style="height:52px;max-width:110px">';
                    break;
                }
            }

            $patientName = trim((string) ($metadata['patient_name'] ?? '')) ?: 'Patient';
            $uhid = trim((string) ($metadata['uhid'] ?? ''));
            $abha = trim((string) ($metadata['abha'] ?? ''));
            $recordCode = trim((string) ($metadata['record_code'] ?? ''));
            $footerIdentity = trim(implode(' | ', array_filter([$patientName, $uhid !== '' ? 'UHID: ' . $uhid : ''])));
            $identityFields = [
                ['Patient', $patientName],
                ['UHID', $uhid],
                ['ABHA', $abha],
                [trim((string) ($metadata['record_label'] ?? 'Record')) ?: 'Record', $recordCode],
                ['Reported / Issued', trim((string) ($metadata['reported_at'] ?? date('Y-m-d H:i:s')))],
                ['Practitioner', trim((string) ($metadata['practitioner'] ?? ''))],
            ];
            $identityFields = array_values(array_filter(
                $identityFields,
                static fn (array $field): bool => trim((string) ($field[1] ?? '')) !== ''
            ));
            $identityRows = '';
            for ($index = 0, $count = count($identityFields); $index < $count; $index += 2) {
                $identityRows .= '<tr>';
                for ($column = 0; $column < 2; $column++) {
                    $field = $identityFields[$index + $column] ?? null;
                    if ($field === null) {
                        $identityRows .= '<td></td>';
                        continue;
                    }
                    $identityRows .= '<td><span class="field-label">' . $escape((string) $field[0]) . ':</span> '
                        . '<strong>' . $escape((string) $field[1]) . '</strong></td>';
                }
                $identityRows .= '</tr>';
            }

            $css = 'body{font-family:DejaVu Sans,sans-serif;color:#17212b;font-size:10.5px}'
                . '.brand{width:100%;border-collapse:collapse;border-bottom:2px solid #176b87;margin-bottom:10px}.brand td{vertical-align:middle;padding:0 0 9px 0}'
                . '.brand-logo{width:125px}.facility{font-size:19px;font-weight:bold;color:#123b4a}.facility-meta{font-size:9px;color:#52606d;margin-top:2px}'
                . '.document-title{font-size:14px;font-weight:bold;text-align:right;color:#176b87}.share-identity{width:100%;border-collapse:collapse;margin-bottom:14px}'
                . '.share-identity td{width:50%;border:1px solid #cad7de;background:#f5f9fb;padding:6px;vertical-align:top}.field-label{color:#52606d}'
                . '.share-content-table{width:100%;border-collapse:collapse}'
                . '.share-content-table th,.share-content-table td{border:1px solid #cbd5df;padding:6px}.share-content-table th{background:#eaf2f5;text-align:left}'
                . '.items{width:100%;border-collapse:collapse}.items th,.items td{border:1px solid #cbd5df;padding:7px}.items th{background:#eef2f6;text-align:left}'
                . '.num{text-align:right}.totals{margin-top:12px;text-align:right;font-size:12px;line-height:1.6}'
                . '.share-conclusion{margin-top:14px;padding:9px;border-left:3px solid #176b87;background:#f5f9fb}table{border-collapse:collapse;max-width:100%}img{max-width:100%}'
                . 'h1,h2,h3{margin:8px 0}p{margin:5px 0}';
            $bodyHtml = '<table class="brand"><tr><td class="brand-logo">' . $logoHtml . '</td><td><div class="facility">' . $escape($hospitalName) . '</div>'
                . '<div class="facility-meta">' . $escape($hospitalAddress) . '</div><div class="facility-meta">' . $escape($hospitalContact) . '</div></td>'
                . '<td class="document-title">' . $escape((string) ($metadata['document_title'] ?? 'Digital Health Record')) . '</td></tr></table>'
                . '<table class="share-identity">' . $identityRows . '</table>' . $contentHtml;

            $mpdfTempDir = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'mpdf';
            if (! is_dir($mpdfTempDir)) {
                @mkdir($mpdfTempDir, 0755, true);
            }
            $mpdf = new Mpdf([
                'format' => 'A4',
                'margin_top' => 10,
                'margin_bottom' => 16,
                'margin_left' => 10,
                'margin_right' => 10,
                'tempDir' => $mpdfTempDir,
                'default_font' => 'freeserif',
            ]);
            $mpdf->SetHTMLFooter('<div style="border-top:1px solid #9fb3bd;padding-top:5px;font-size:8px;color:#52606d">'
                . $escape($footerIdentity) . '<span style="float:right">Page {PAGENO} of {nbpg}</span></div>');
            $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($bodyHtml, HTMLParserMode::HTML_BODY);
            $bytes = $mpdf->Output($fileStem . '.pdf', 'S');
            if (! is_string($bytes) || ! str_starts_with($bytes, '%PDF-')) {
                return null;
            }

            return [
                'content_type' => 'application/pdf',
                'data_base64' => base64_encode($bytes),
                'title' => $fileStem . '.pdf',
                'size' => strlen($bytes),
                'hash' => base64_encode(sha1($bytes, true)),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveStoredFileAbsolutePath(string $fullPath): string
    {
        $normalized = str_replace('\\', '/', trim($fullPath));
        if ($normalized === '') {
            return '';
        }

        if (is_file($normalized)) {
            return $normalized;
        }

        if (preg_match('/^[A-Za-z]:\//', $normalized) === 1 || str_starts_with($normalized, '/')) {
            return is_file($normalized) ? $normalized : '';
        }

        $candidates = [
            ROOTPATH . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $normalized), DIRECTORY_SEPARATOR),
            ROOTPATH . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $normalized), DIRECTORY_SEPARATOR),
            WRITEPATH . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $normalized), DIRECTORY_SEPARATOR),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return '';
    }

    private function getScanShareResultPayload(int $queueId): ?array
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return null;
        }

        $likeToken = '"queue_id":' . $queueId;

        $resultLog = $this->db->table('abdm_api_logs')
            ->select('response_json, request_json')
            ->whereIn('event_type', ['abdm.scan_share.lookup.result', 'abdm.scan_share.lookup'])
            ->groupStart()
                ->like('request_json', $likeToken)
                ->orLike('response_json', $likeToken)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (! $resultLog) {
            return null;
        }

        $responsePayload = json_decode((string) ($resultLog['response_json'] ?? ''), true);
        if (is_array($responsePayload) && ! empty($responsePayload)) {
            return $responsePayload;
        }

        $requestPayload = json_decode((string) ($resultLog['request_json'] ?? ''), true);
        return is_array($requestPayload) && ! empty($requestPayload) ? $requestPayload : null;
    }

    private function extractScanShareIdentity(array $payload): array
    {
        $identity = [
            'abha_number' => '',
            'abha_address' => '',
            'patient_name' => '',
            'phone' => '',
            'gender' => '',
            'dob' => '',
        ];

        $abhaNumber = (string) ($this->findFirstByKeys($payload, ['abha_number', 'abhaNumber', 'abha_id', 'abhaId', 'healthIdNumber']) ?? '');
        $abhaDigits = preg_replace('/\D/', '', $abhaNumber);
        if (strlen($abhaDigits) === 14) {
            $identity['abha_number'] = $abhaDigits;
        }

        $abhaAddress = trim((string) ($this->findFirstByKeys($payload, ['abha_address', 'abhaAddress', 'phrAddress', 'healthId']) ?? ''));
        if ($abhaAddress !== '') {
            $identity['abha_address'] = $abhaAddress;
        }

        $patientName = trim((string) ($this->findFirstByKeys($payload, ['patient_name', 'patientName', 'name', 'fullName']) ?? ''));
        if ($patientName !== '') {
            $identity['patient_name'] = strtoupper($patientName);
        }

        $phone = preg_replace('/\D/', '', (string) ($this->findFirstByKeys($payload, ['phone', 'mobile', 'mobileNumber']) ?? ''));
        if ($phone !== '') {
            $identity['phone'] = $phone;
        }

        $gender = strtoupper(substr(trim((string) ($this->findFirstByKeys($payload, ['gender', 'sex']) ?? '')), 0, 1));
        if (in_array($gender, ['M', 'F', 'O'], true)) {
            $identity['gender'] = $gender;
        }

        $dob = trim((string) ($this->findFirstByKeys($payload, ['dob', 'dateOfBirth', 'birthDate']) ?? ''));
        if ($dob !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $identity['dob'] = $dob;
        }

        $age = preg_replace('/\D/', '', (string) ($this->findFirstByKeys($payload, ['age', 'patient_age', 'patientAge']) ?? ''));
        if ($age !== '' && preg_match('/^\d{1,3}$/', $age)) {
            $identity['age'] = $age;
        }

        $birthYear = preg_replace('/\D/', '', (string) ($this->findFirstByKeys($payload, ['birth_year', 'birthYear', 'year_of_birth', 'yearOfBirth']) ?? ''));
        if ($birthYear !== '' && preg_match('/^(19|20)\d{2}$/', $birthYear)) {
            $identity['birth_year'] = $birthYear;
        }

        return $identity;
    }

    private function findFirstByKeys(array $data, array $keys): ?string
    {
        $normalized = array_map(static fn ($k) => strtolower((string) $k), $keys);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $found = $this->findFirstByKeys($value, $keys);
                if ($found !== null && trim($found) !== '') {
                    return $found;
                }
                continue;
            }

            $k = strtolower((string) $key);
            if (in_array($k, $normalized, true)) {
                $v = trim((string) $value);
                if ($v !== '') {
                    return $v;
                }
            }
        }

        return null;
    }

    private function findScanSharePatientMatches(array $identity): array
    {
        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveFirstExistingColumn($fields, ['abha_id', 'abha_no', 'abha', 'abha_address']);

        $select = 'id,p_code,p_fname,mphone1,dob,gender';
        if ($abhaField) {
            $select .= ',' . $abhaField . ' AS patient_abha';
        }

        $bucket = [];
        $append = static function (array $rows, string $reason, int $score) use (&$bucket): void {
            foreach ($rows as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                if (! isset($bucket[$id])) {
                    $row['match_reasons'] = [];
                    $row['match_score']   = 0;
                    $bucket[$id] = $row;
                }
                if (! in_array($reason, $bucket[$id]['match_reasons'], true)) {
                    $bucket[$id]['match_reasons'][] = $reason;
                }
                if ($score > ($bucket[$id]['match_score'] ?? 0)) {
                    $bucket[$id]['match_score'] = $score;
                }
            }
        };

        // Definitive identifier matches (score 4)
        if ($abhaField && ($identity['abha_number'] !== '' || $identity['abha_address'] !== '')) {
            $abha = $identity['abha_number'] !== '' ? $identity['abha_number'] : $identity['abha_address'];
            $rows = $this->db->table('patient_master')->select($select)->where($abhaField, $abha)->limit(10)->get()->getResultArray();
            $append($rows, 'ABHA', 4);
        }

        // Demographic matches — primary criteria
        $nameUpper = strtoupper(trim((string) ($identity['patient_name'] ?? '')));
        $rawGender = strtoupper(trim((string) ($identity['gender'] ?? '')));
        $genderDb  = ($rawGender === 'F' || $rawGender === '2') ? 2 : ($rawGender !== '' ? 1 : null);
        $dob       = trim((string) ($identity['dob'] ?? ''));
        $dobValid  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ? $dob : '';
        $birthYear = trim((string) ($identity['birth_year'] ?? ''));
        $yearOnly  = $dobValid !== '' ? (int) substr($dobValid, 0, 4) : (preg_match('/^(19|20)\d{2}$/', $birthYear) ? (int) $birthYear : 0);
        $age       = trim((string) ($identity['age'] ?? ''));
        $ageYears  = preg_match('/^\d{1,3}$/', $age) ? (int) $age : null;

        if ($nameUpper !== '' && $dobValid !== '') {
            // Name + exact DOB [+ gender] — high confidence (score 3)
            $q = $this->db->table('patient_master')->select($select)
                ->where('UPPER(p_fname)', $nameUpper)
                ->where('dob', $dobValid);
            if ($genderDb !== null) {
                $q->where('gender', $genderDb);
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + DOB + Gender', 3);
            } else {
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + DOB', 3);
            }
        }

        if ($nameUpper !== '' && $yearOnly > 0) {
            // Name + year of birth [+ gender] — medium confidence (score 2)
            $q = $this->db->table('patient_master')->select($select)
                ->where('UPPER(p_fname)', $nameUpper)
                ->where('YEAR(dob)', $yearOnly);
            if ($genderDb !== null) {
                $q->where('gender', $genderDb);
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + Year of Birth + Gender', 2);
            } else {
                $rows = $q->limit(10)->get()->getResultArray();
                $append($rows, 'Name + Year of Birth', 2);
            }
        }

        foreach ($this->findFuzzyDemographicMatches($this->db, $select, $nameUpper, $genderDb, $dobValid, $yearOnly, $ageYears) as $row) {
            $reason = (string) ($row['_match_reason'] ?? 'Similar Name + Demographics');
            $score  = (int) ($row['_match_score'] ?? 2);
            unset($row['_match_reason'], $row['_match_score']);
            $append([$row], $reason, $score);
        }

        // Phone match — supplementary only (score 1), ambiguous for families
        if (($identity['phone'] ?? '') !== '') {
            $rows = $this->db->table('patient_master')->select($select)->where('mphone1', $identity['phone'])->limit(10)->get()->getResultArray();
            $append($rows, 'Phone', 1);
        }

        // Assign confidence label and sort by score descending
        $confidenceLabel = static function (int $score): string {
            if ($score >= 4) { return 'definitive'; }
            if ($score === 3) { return 'high'; }
            if ($score === 2) { return 'medium'; }
            return 'low';
        };

        $result = array_values($bucket);
        usort($result, static function ($a, $b) {
            return ($b['match_score'] ?? 0) <=> ($a['match_score'] ?? 0);
        });

        foreach ($result as &$row) {
            $row['match_confidence'] = $confidenceLabel((int) ($row['match_score'] ?? 0));
        }
        unset($row);

        return $result;
    }

    private function findFuzzyDemographicMatches(
        \CodeIgniter\Database\BaseConnection $db,
        string $select,
        string $nameUpper,
        ?int $genderDb,
        string $dobValid,
        int $yearOnly,
        ?int $ageYears
    ): array {
        if ($nameUpper === '' || strlen($this->normalizeMatchName($nameUpper)) < 4) {
            return [];
        }

        $fields = $db->getFieldNames('patient_master') ?? [];
        $queries = [];

        if ($dobValid !== '') {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + DOB + Gender' : 'Similar Name + DOB', 'score' => 3, 'dob' => $dobValid];
        }
        if ($yearOnly > 0) {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + Year of Birth + Gender' : 'Similar Name + Year of Birth', 'score' => 2, 'year' => $yearOnly];
        }
        if ($ageYears !== null && in_array('age', $fields, true)) {
            $queries[] = ['reason' => $genderDb !== null ? 'Similar Name + Age + Gender' : 'Similar Name + Age', 'score' => 2, 'age' => $ageYears];
        }

        $matches = [];
        foreach ($queries as $query) {
            $builder = $db->table('patient_master')->select($select);
            if (isset($query['dob'])) {
                $builder->where('dob', $query['dob']);
            }
            if (isset($query['year'])) {
                $builder->where('YEAR(dob)', (int) $query['year']);
            }
            if (isset($query['age'])) {
                $builder->where('age', (int) $query['age']);
            }
            if ($genderDb !== null) {
                $builder->where('gender', $genderDb);
            }

            foreach ($builder->limit(100)->get()->getResultArray() as $row) {
                if (! $this->isSimilarPatientName($nameUpper, (string) ($row['p_fname'] ?? ''))) {
                    continue;
                }
                $id = (int) ($row['id'] ?? 0);
                if ($id <= 0 || isset($matches[$id])) {
                    continue;
                }
                $row['_match_reason'] = (string) $query['reason'];
                $row['_match_score']  = (int) $query['score'];
                $matches[$id] = $row;
            }
        }

        return array_values($matches);
    }

    private function normalizeMatchName(string $name): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper($name)) ?? '';
    }

    private function isSimilarPatientName(string $incomingName, string $storedName): bool
    {
        $incoming = $this->normalizeMatchName($incomingName);
        $stored   = $this->normalizeMatchName($storedName);
        if ($incoming === '' || $stored === '' || $incoming === $stored) {
            return false;
        }

        $maxDistance = strlen($incoming) <= 8 ? 1 : (strlen($incoming) <= 14 ? 2 : 3);
        similar_text($incoming, $stored, $percent);
        if (levenshtein($incoming, $stored) <= $maxDistance || $percent >= 86.0) {
            return true;
        }

        $incomingFirst = $this->normalizeMatchName(strtok($incomingName, ' ') ?: $incomingName);
        $storedFirst   = $this->normalizeMatchName(strtok($storedName, ' ') ?: $storedName);
        if (strlen($incomingFirst) < 4 || strlen($storedFirst) < 4) {
            return false;
        }

        $firstDistance = strlen($incomingFirst) <= 8 ? 1 : 2;
        similar_text($incomingFirst, $storedFirst, $firstPercent);

        return levenshtein($incomingFirst, $storedFirst) <= $firstDistance || $firstPercent >= 84.0;
    }

    private function resolveFirstExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }
        return null;
    }

    private function createPatientFromScanIdentity(array $identity, ?string $abhaField): array
    {
        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $genderDb = ($identity['gender'] ?? '') === 'F' ? 2 : 1;
        $dob = (string) ($identity['dob'] ?? '');

        $insertData = [
            'p_fname' => strtoupper((string) ($identity['patient_name'] ?? 'ABHA PATIENT')),
            'mphone1' => (string) ($identity['phone'] ?? ''),
            'gender' => $genderDb,
            'blood_group' => 'Not Define',
            'estimate_dob' => $dob !== '' ? 0 : 1,
        ];
        if ($dob !== '') {
            $insertData['dob'] = $dob;
        } else {
            $insertData['age'] = 0;
            $insertData['age_in_month'] = 0;
        }

        if ($abhaField && (($identity['abha_number'] ?? '') !== '' || ($identity['abha_address'] ?? '') !== '')) {
            $insertData[$abhaField] = (string) (($identity['abha_number'] ?? '') !== '' ? $identity['abha_number'] : $identity['abha_address']);
        }

        $today = date('y') . date('m');
        $countRow = $this->db->query("SELECT COUNT(*) as cnt FROM patient_master WHERE p_code LIKE 'P{$today}%'")->getRow();
        $seq = str_pad(((int) ($countRow->cnt ?? 0)) + 1, 4, '0', STR_PAD_LEFT);
        $insertData['p_code'] = 'P' . $today . $seq;

        $this->db->table('patient_master')->insert($insertData);

        return [
            'patient_id' => (int) $this->db->insertID(),
            'p_code' => (string) $insertData['p_code'],
        ];
    }

    private function validateWebhookSignature()
    {
        $secret = $this->readRuntimeSetting('EKA_WEBHOOK_SECRET');
        if ($secret === '') {
            $secret = $this->readRuntimeSetting('HMS_WEBHOOK_SECRET');
        }
        if ($secret === '') {
            return null;
        }

        $signature = trim((string) ($this->request->getHeaderLine('X-Eka-Signature') ?: ''));
        if ($signature === '') {
            $signature = trim((string) ($this->request->getHeaderLine('X-Signature') ?: ''));
        }
        if ($signature === '') {
            $signature = trim((string) ($this->request->getHeaderLine('X-Hub-Signature-256') ?: ''));
        }
        if ($signature === '') {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error_text' => 'Missing signature header',
            ]);
        }

        $signature = strtolower($signature);
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $rawBody = (string) $this->request->getBody();
        $expected = hash_hmac('sha256', $rawBody, $secret);
        if (! hash_equals($expected, $signature)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error_text' => 'Invalid signature',
            ]);
        }

        return null;
    }

    private function validateGatewayToHmsAuth()
    {
        $token = $this->resolveGatewayToHmsToken();
        $authHeader = trim((string) $this->request->getHeaderLine('Authorization'));
        $incomingBearer = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m) === 1) {
            $incomingBearer = trim((string) ($m[1] ?? ''));
        }

        if ($token === '' || $incomingBearer === '' || ! hash_equals($token, $incomingBearer)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid Authorization bearer token',
                'request_id' => trim((string) $this->request->getHeaderLine('X-Request-Id')),
            ]);
        }

        $signatureFailure = $this->validateGatewayToHmsSignature();
        if ($signatureFailure !== null) {
            return $signatureFailure;
        }

        $metaFailure = $this->validateGatewayToHmsRequestMeta();
        if ($metaFailure !== null) {
            return $metaFailure;
        }

        return null;
    }

    private function validateGatewayToHmsSignature()
    {
        $secret = trim((string) ($this->readRuntimeSetting('GATEWAY_TO_HMS_HMAC_SECRET') ?: $this->readRuntimeSetting('EKA_WEBHOOK_SECRET')));
        if ($secret === '') {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => 'SERVER_MISCONFIGURED',
                'message' => 'Missing gateway webhook HMAC secret',
                'request_id' => trim((string) $this->request->getHeaderLine('X-Request-Id')),
            ]);
        }

        $signature = trim((string) ($this->request->getHeaderLine('X-Eka-Signature') ?: ''));
        if ($signature === '') {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Missing X-Eka-Signature header',
                'request_id' => trim((string) $this->request->getHeaderLine('X-Request-Id')),
            ]);
        }

        $signature = strtolower($signature);
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $rawBody = (string) $this->request->getBody();
        $expected = hash_hmac('sha256', $rawBody, $secret);
        if (! hash_equals($expected, $signature)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid request signature',
                'request_id' => trim((string) $this->request->getHeaderLine('X-Request-Id')),
            ]);
        }

        return null;
    }

    private function validateGatewayToHmsRequestMeta()
    {
        $requestId = trim((string) $this->request->getHeaderLine('X-Request-Id'));
        if ($requestId === '') {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Missing X-Request-Id header',
                'request_id' => '',
            ]);
        }

        $timestamp = trim((string) $this->request->getHeaderLine('X-Timestamp'));
        if ($timestamp === '') {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Missing X-Timestamp header',
                'request_id' => $requestId,
            ]);
        }

        try {
            $ts = new \DateTimeImmutable($timestamp);
            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $diff = abs($now->getTimestamp() - $ts->setTimezone(new \DateTimeZone('UTC'))->getTimestamp());
            if ($diff > 300) {
                return $this->response->setStatusCode(401)->setJSON([
                    'ok' => 0,
                    'error' => 'UNAUTHORIZED',
                    'message' => 'Request timestamp outside allowed skew',
                    'request_id' => $requestId,
                ]);
            }
        } catch (\Throwable) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid X-Timestamp value',
                'request_id' => $requestId,
            ]);
        }

        $cache = \Config\Services::cache();
        $cacheKey = 'abdm_gateway_reqid_' . sha1($requestId);
        try {
            $existing = $cache->get($cacheKey);
            if ($existing !== null) {
                return $this->response->setStatusCode(409)->setJSON([
                    'ok' => 0,
                    'error' => 'REPLAY_REQUEST',
                    'message' => 'Duplicate X-Request-Id detected',
                    'request_id' => $requestId,
                ]);
            }
            $cache->save($cacheKey, 1, 600);
        } catch (\Throwable $e) {
            log_message('warning', '[AbdmGateway] replay-guard cache failed: ' . $e->getMessage());
        }

        return null;
    }

    private function resolveGatewayToHmsToken(): string
    {
        $candidates = [
            'GATEWAY_TO_HMS_TOKEN',
            'ABDM_GATEWAY_TO_HMS_TOKEN',
            'EKA_GATEWAY_TOKEN',
            'EATRIA_BRIDGE_TOKEN',
            'ABDM_BRIDGE_TOKEN',
        ];

        foreach ($candidates as $name) {
            $value = trim((string) $this->readRuntimeSetting($name));
            if ($value !== '') {
                return preg_replace('/^Bearer\s+/i', '', $value);
            }
        }

        return '';
    }

    private function mapPatientRecordTypeToHiType(string $recordType): string
    {
        return match (strtoupper(trim($recordType))) {
            'OPD' => 'OPConsultRecord',
            'IPD' => 'HealthDocumentRecord',
            'LAB' => 'DiagnosticReportRecord',
            'DISCHARGE' => 'DischargeSummaryRecord',
            'IMMUNIZATION' => 'ImmunizationRecord',
            'MLC' => 'HealthDocumentRecord',
            default => 'HealthDocumentRecord',
        };
    }

    /**
     * HMS sits behind NAT, so ABDM/bridge discovery callbacks can never reach it —
     * records must be pushed to the gateway to become discoverable in the PHR.
     * Set ABDM_GATEWAY_PUSH_MODE=0 only for a callback-reachable deployment.
     */
    private function resolveGatewayPushMode(): bool
    {
        $requested = $this->request->getPost('push_to_gateway');
        if ($requested !== null && trim((string) $requested) !== '') {
            return (int) $requested === 1;
        }

        $setting = strtolower(trim($this->readRuntimeSetting('ABDM_GATEWAY_PUSH_MODE')));

        return ! in_array($setting, ['0', 'off', 'false', 'no'], true);
    }

    private function readRuntimeSetting(string $name): string
    {
        $envValue = getenv($name);
        if ($envValue !== false) {
            $value = trim((string) $envValue);
            if ($value !== '') {
                return $value;
            }
        }

        if ($this->db->tableExists('hospital_setting')) {
            $fields = $this->db->getFieldNames('hospital_setting') ?? [];
            $orderCol = in_array('id', $fields, true)
                ? 'id'
                : (in_array('s_id', $fields, true) ? 's_id' : null);

            $builder = $this->db->table('hospital_setting')
                ->select('s_value')
                ->where('s_name', $name);
            if ($orderCol !== null) {
                $builder->orderBy($orderCol, 'DESC');
            }
            $row = $builder->get(1)->getRowArray();
            $value = trim((string) ($row['s_value'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function isStaleConsentTransition(string $currentStatus, string $incomingStatus): bool
    {
        if ($incomingStatus === '' || $currentStatus === '') {
            return false;
        }

        if ($incomingStatus === 'requested' && $currentStatus !== 'requested') {
            return true;
        }

        return false;
    }

    private function isStaleClaimTransition(string $currentStatus, string $incomingStatus): bool
    {
        if ($incomingStatus === '' || $currentStatus === '' || $incomingStatus === 'unknown') {
            return false;
        }

        $rank = [
            'draft' => 10,
            'submitted' => 20,
            'in_review' => 30,
            'approved' => 40,
            'rejected' => 40,
            'failed' => 40,
            'settled' => 50,
            'paid' => 50,
        ];

        $currentRank = (int) ($rank[$currentStatus] ?? 0);
        $incomingRank = (int) ($rank[$incomingStatus] ?? 0);

        return $currentRank > 0 && $incomingRank > 0 && $incomingRank < $currentRank;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Persist a FHIR bundle to health_records and return the new row id.
     * Stores both a plain-text copy in record_data and an encrypted copy in fhir_bundle_enc.
     * Returns 0 when the table is absent or on any DB error (fail-open).
     *
    * @param array{patient_id: int, abha_id: string, hi_type: string, entity_type: string,
    *              entity_id: string, fhir_bundle: string, care_context_reference?: string, consent_handle?: string,
    *              attachment_path?: string, reuse_existing?: bool} $data
     */
    private function storeHealthRecord(array $data): int
    {
        try {
            if (! $this->db->tableExists('health_records')) {
                return 0;
            }

            $enc = new FhirEncryptionService();
            $encPayload = '';
            $rawBundle = (string) ($data['fhir_bundle'] ?? '');
            if ($rawBundle !== '') {
                $encPayload = $enc->encrypt($rawBundle);
            }

            $session = \Config\Services::session();
            $now     = Time::now('Asia/Kolkata')->toDateTimeString();

            $insert = [
                'patient_id'          => (int) ($data['patient_id'] ?? 0) > 0 ? (int) $data['patient_id'] : null,
                'abha_id'             => trim((string) ($data['abha_id'] ?? '')) ?: null,
                'hi_type'             => (string) ($data['hi_type'] ?? 'unknown'),
                'entity_type'         => (string) ($data['entity_type'] ?? ''),
                'entity_id'           => (string) ($data['entity_id'] ?? ''),
                'record_data'         => $rawBundle !== '' ? $rawBundle : null,
                'fhir_bundle_enc'     => $encPayload !== '' ? $encPayload : null,
                'push_status'         => 'queued',
                'push_at'             => $now,
                'consent_handle'      => trim((string) ($data['consent_handle'] ?? '')) ?: null,
                'created_by_user_id'  => (int) ($session->get('user_id') ?? 0) > 0 ? (int) $session->get('user_id') : null,
                'created_by_name'     => trim((string) ($session->get('full_name') ?? $session->get('name') ?? '')) ?: null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            $hrFields = $this->db->getFieldNames('health_records') ?? [];
            if (in_array('care_context_reference', $hrFields, true)) {
                $insert['care_context_reference'] = trim((string) ($data['care_context_reference'] ?? '')) ?: null;
            }
            if (in_array('attachment_path', $hrFields, true)) {
                $insert['attachment_path'] = trim((string) ($data['attachment_path'] ?? '')) ?: null;
            }

            $careContextReference = trim((string) ($data['care_context_reference'] ?? ''));
            if (($data['reuse_existing'] ?? false) === true && $careContextReference !== '') {
                $existing = $this->db->table('health_records')
                    ->select('id')
                    ->where('hi_type', (string) ($data['hi_type'] ?? 'unknown'))
                    ->where('entity_type', (string) ($data['entity_type'] ?? ''))
                    ->where('entity_id', (string) ($data['entity_id'] ?? ''))
                    ->where('care_context_reference', $careContextReference)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
                if (! empty($existing)) {
                    $existingId = (int) ($existing['id'] ?? 0);
                    $update = $insert;
                    unset($update['push_status'], $update['push_at'], $update['created_at']);
                    $this->db->table('health_records')->where('id', $existingId)->update($update);
                    return $existingId;
                }
            }

            $this->db->table('health_records')->insert($insert);
            $insertId = (int) $this->db->insertID();

            $this->mirrorHealthRecordToPatientRecords($data);

            return $insertId;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Mirror an HMS health record into patient_records for M2 source-of-truth APIs.
     * Fail-open by design.
     *
     * @param array<string, mixed> $data
     */
    private function mirrorHealthRecordToPatientRecords(array $data): void
    {
        try {
            if (! $this->db->tableExists('patient_records')) {
                return;
            }

            $patientId = (int) ($data['patient_id'] ?? 0);
            $abhaId = trim((string) ($data['abha_id'] ?? ''));
            $fhirBundle = trim((string) ($data['fhir_bundle'] ?? ''));

            if ($patientId <= 0 || $abhaId === '' || $fhirBundle === '') {
                return;
            }

            $decoded = json_decode($fhirBundle, true);
            if (! is_array($decoded)) {
                return;
            }

            $recordType = $this->resolvePatientRecordType(
                (string) ($data['hi_type'] ?? ''),
                (string) ($data['entity_type'] ?? '')
            );

            $expiryDate = $this->resolvePatientRecordExpiryDate(
                $recordType,
                (string) ($data['entity_type'] ?? ''),
                (string) ($data['entity_id'] ?? '')
            );

            $this->db->table('patient_records')->insert([
                'patient_id'    => $patientId,
                'abha_id'       => $abhaId,
                'consent_id'    => trim((string) ($data['consent_handle'] ?? '')) ?: null,
                'record_type'   => $recordType,
                'fhir_resource' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at'    => Time::now('Asia/Kolkata')->toDateTimeString(),
                'updated_at'    => Time::now('Asia/Kolkata')->toDateTimeString(),
                'expiry_date'   => $expiryDate,
                'status'        => 'ACTIVE',
            ]);
        } catch (\Throwable) {
            // Fail-open
        }
    }

    private function resolvePatientRecordType(string $hiType, string $entityType = ''): string
    {
        $v = strtolower(trim($hiType));
        if (str_contains($v, 'opconsult') || str_contains($v, 'prescription')) {
            return 'OPD';
        }
        if (str_contains($v, 'diagnosticreport') || $entityType === 'lab') {
            return 'LAB';
        }
        if (str_contains($v, 'discharge')) {
            return 'DISCHARGE';
        }
        if (str_contains($v, 'immunization')) {
            return 'IMMUNIZATION';
        }
        if ($entityType === 'ipd') {
            return 'IPD';
        }

        return 'OTHER';
    }

    private function resolvePatientRecordExpiryDate(string $recordType, string $entityType = '', string $entityId = ''): ?string
    {
        if ($recordType === 'MLC') {
            return null;
        }

        // OPD and LAB default to 3 years, IPD/DISCHARGE to 7 years.
        $years = in_array($recordType, ['IPD', 'DISCHARGE'], true) ? 7 : 3;
        if ($recordType === 'LAB' && strtolower(trim($entityType)) === 'ipd') {
            $years = 7;
        }

        return date('Y-m-d', strtotime('+' . $years . ' years'));
    }

    private function syncM2ConsentLog(
        int $patientId,
        string $abhaId,
        string $consentId,
        string $purpose,
        string $status,
        string $expiresAt = ''
    ): void {
        try {
            if (! $this->db->tableExists('consent_logs')) {
                return;
            }

            $mapped = match (strtolower(trim($status))) {
                'approved', 'granted' => 'GRANTED',
                'revoked' => 'REVOKED',
                'expired' => 'EXPIRED',
                default => '',
            };

            if ($mapped === '' || $patientId <= 0 || trim($abhaId) === '' || trim($consentId) === '') {
                return;
            }

            $insert = [
                'patient_id' => $patientId,
                'abha_id' => trim($abhaId),
                'consent_id' => trim($consentId),
                'purpose' => trim($purpose) !== '' ? trim($purpose) : null,
                'status' => $mapped,
                'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                'expires_at' => trim($expiresAt) !== '' ? trim($expiresAt) : null,
            ];

            $this->db->table('consent_logs')->insert($insert);
        } catch (\Throwable) {
            // Fail-open
        }
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushRecordMeta(array $result): array
    {
        $candidates = [
            'record_id' => $result['record_id'] ?? null,
            'existing_record_id' => $result['existing_record_id'] ?? null,
            'id' => $result['id'] ?? null,
            'response.record_id' => $result['response']['record_id'] ?? null,
            'response.existing_record_id' => $result['response']['existing_record_id'] ?? null,
            'response.id' => $result['response']['id'] ?? null,
            'data.record_id' => $result['data']['record_id'] ?? null,
            'data.existing_record_id' => $result['data']['existing_record_id'] ?? null,
            'data.id' => $result['data']['id'] ?? null,
        ];

        foreach ($candidates as $source => $value) {
            $id = (int) $value;
            if ($id > 0) {
                return ['id' => $id, 'source' => $source];
            }
        }

        return ['id' => 0, 'source' => 'none'];
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushRecordId(array $result): int
    {
        $meta = $this->extractGatewayPushRecordMeta($result);
        return (int) ($meta['id'] ?? 0);
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushQueueMeta(array $result): array
    {
        $candidates = [
            'queue_id' => $result['queue_id'] ?? null,
            'existing_queue_id' => $result['existing_queue_id'] ?? null,
            'response.queue_id' => $result['response']['queue_id'] ?? null,
            'response.existing_queue_id' => $result['response']['existing_queue_id'] ?? null,
            'data.queue_id' => $result['data']['queue_id'] ?? null,
            'data.existing_queue_id' => $result['data']['existing_queue_id'] ?? null,
        ];

        foreach ($candidates as $source => $value) {
            $queueId = trim((string) $value);
            if ($queueId !== '') {
                return ['id' => $queueId, 'source' => $source];
            }
        }

        return ['id' => '', 'source' => 'none'];
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushQueueId(array $result): string
    {
        $meta = $this->extractGatewayPushQueueMeta($result);
        return (string) ($meta['id'] ?? '');
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushHttpCode(array $result): int
    {
        $candidates = [
            $result['http_code'] ?? null,
            $result['response']['http_code'] ?? null,
            $result['data']['http_code'] ?? null,
        ];

        foreach ($candidates as $value) {
            $httpCode = (int) $value;
            if ($httpCode > 0) {
                return $httpCode;
            }
        }

        return 0;
    }

    /**
     * @param array<string,mixed> $result
     */
    private function isGatewayPushDuplicate(array $result): bool
    {
        $httpCode = $this->extractGatewayPushHttpCode($result);
        $codes = [
            $result['error_code'] ?? null,
            is_array($result['error'] ?? null) ? ($result['error']['code'] ?? null) : ($result['error'] ?? null),
            $result['response']['error_code'] ?? null,
            is_array($result['response']['error'] ?? null) ? ($result['response']['error']['code'] ?? null) : ($result['response']['error'] ?? null),
            $result['data']['error_code'] ?? null,
            is_array($result['data']['error'] ?? null) ? ($result['data']['error']['code'] ?? null) : ($result['data']['error'] ?? null),
        ];
        foreach ($codes as $code) {
            if ($httpCode === 409 && strtoupper(trim((string) $code)) === 'DUPLICATE_RECORD') {
                return true;
            }
        }

        return false;
    }

    /**
     * HTTP 201 and HTTP 409 (duplicate) are treated as submitted success states.
     *
     * @param array<string,mixed> $result
     */
    private function isGatewayPushSubmitted(array $result): bool
    {
        $httpCode = $this->extractGatewayPushHttpCode($result);
        if ($httpCode === 201) {
            return true;
        }

        if ($this->isGatewayPushDuplicate($result)) {
            return true;
        }

        $okValues = [
            $result['ok'] ?? null,
            $result['response']['ok'] ?? null,
            $result['data']['ok'] ?? null,
        ];

        foreach ($okValues as $ok) {
            if ((int) $ok === 1 && $httpCode >= 200 && $httpCode < 300) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushErrorText(array $result): string
    {
        $messages = [
            $result['message'] ?? null,
            $result['error_text'] ?? null,
            $result['response']['message'] ?? null,
            $result['response']['error_text'] ?? null,
            $result['data']['message'] ?? null,
            $result['data']['error_text'] ?? null,
            $result['error_code'] ?? null,
            $result['response']['error_code'] ?? null,
            $result['data']['error_code'] ?? null,
        ];

        foreach ($messages as $message) {
            $text = trim((string) $message);
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $result
     */
    private function extractGatewayPushFirstPushedAt(array $result): string
    {
        foreach ([
            $result['first_pushed_at'] ?? null,
            $result['response']['first_pushed_at'] ?? null,
            $result['data']['first_pushed_at'] ?? null,
        ] as $value) {
            $timestamp = trim((string) $value);
            if ($timestamp !== '') {
                return $timestamp;
            }
        }

        return '';
    }

    /**
     * Emit one diagnostic line to show exactly which gateway response fields
     * supplied queue/record identifiers for records/push integration.
     *
     * @param array<string,mixed> $result
     */
    private function logGatewayPushResolution(string $context, array $result): void
    {
        try {
            $recordMeta = $this->extractGatewayPushRecordMeta($result);
            $queueMeta = $this->extractGatewayPushQueueMeta($result);
            $httpCode = $this->extractGatewayPushHttpCode($result);
            $submitted = $this->isGatewayPushSubmitted($result) ? 1 : 0;
            $duplicate = $this->isGatewayPushDuplicate($result) ? 1 : 0;

            log_message(
                'info',
                '[abdm.records.push.resolve][' . $context . '] '
                . 'http_code=' . $httpCode
                . ' submitted=' . $submitted
                . ' duplicate=' . $duplicate
                . ' record_id=' . (int) ($recordMeta['id'] ?? 0)
                . ' record_source=' . (string) ($recordMeta['source'] ?? 'none')
                . ' queue_id=' . (string) ($queueMeta['id'] ?? '')
                . ' queue_source=' . (string) ($queueMeta['source'] ?? 'none')
            );
        } catch (\Throwable) {
            // Fail-open diagnostics
        }
    }

    /**
     * Update a health_record's push status and txn_id after getting a connector response.
     * Also creates a matching record_links row.
     *
     * @param int         $healthRecordId  Local health_records.id
     * @param string      $queueId         Bridge queue_id (abdm_txn_id)
     * @param string|null $error           Connector error message (null = success)
     * @param int         $bridgeRecordId  Bridge record_id (from POST /v3/records/push response)
     */
    private function updateHealthRecordTxn(int $healthRecordId, string $queueId, ?string $error, int $bridgeRecordId = 0, string $firstPushedAt = ''): void
    {
        try {
            $now = Time::now('Asia/Kolkata')->toDateTimeString();

            if ($this->db->tableExists('health_records')) {
                $hrUpdate = [
                    'abdm_txn_id'  => $queueId !== '' ? $queueId : null,
                    'push_status'  => $error === null ? 'queued' : 'failed',
                    'updated_at'   => $now,
                ];
                $hrFields = $this->db->getFieldNames('health_records') ?? [];
                if ($bridgeRecordId > 0 && in_array('bridge_record_id', $hrFields, true)) {
                    $hrUpdate['bridge_record_id'] = $bridgeRecordId;
                }
                if ($firstPushedAt !== '' && in_array('first_pushed_at', $hrFields, true)) {
                    $hrUpdate['first_pushed_at'] = $firstPushedAt;
                }
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update($hrUpdate);
            }

            if ($queueId !== '' && $this->db->tableExists('record_links')) {
                // Only insert if not already present
                $existing = $this->db->table('record_links')
                    ->where('health_record_id', $healthRecordId)
                    ->where('abdm_txn_id', $queueId)
                    ->countAllResults();

                if ($existing === 0) {
                    $hr = $this->db->tableExists('health_records')
                        ? ($this->db->table('health_records')->select('abha_id')->where('id', $healthRecordId)->get(1)->getRowArray() ?? [])
                        : [];

                    $this->db->table('record_links')->insert([
                        'health_record_id' => $healthRecordId,
                        'abdm_txn_id'      => $queueId,
                        'abha_id'          => trim((string) ($hr['abha_id'] ?? '')) ?: null,
                        'link_status'      => 'pending',
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        } catch (\Throwable) {
            // Fail-open
        }
    }

    /**
     * Query hospital_setting for FHIR-required hospital profile fields.
     *
    * @return array{name:string,hfr_id:string,address:string,phone:string,email:string,logo:string}
     */
    private function getHospitalProfileForFhir(): array
    {
        try {
            $rows = $this->db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', [
                    'ABDM_HMS_NAME', 'ABDM_HFR_ID', 'H_Name', 'H_address_1', 'H_address_2',
                    'H_phone_No', 'H_Email', 'H_logo',
                ])
                ->get()->getResultArray();
            $map   = array_column($rows, 's_value', 's_name');
            $name  = trim((string) ($map['ABDM_HMS_NAME'] ?? $map['H_Name'] ?? ''));
            $hfrId = trim((string) ($map['ABDM_HFR_ID'] ?? ''));
            $address = trim(implode(', ', array_filter([
                trim((string) ($map['H_address_1'] ?? '')),
                trim((string) ($map['H_address_2'] ?? '')),
            ], static fn (string $value): bool => $value !== '')));
            $phone = trim((string) ($map['H_phone_No'] ?? ''));
            $email = trim((string) ($map['H_Email'] ?? ''));
            $logo = trim((string) ($map['H_logo'] ?? ''));
        } catch (\Throwable $e) {
            $name  = '';
            $hfrId = '';
            $address = '';
            $phone = '';
            $email = '';
            $logo = '';
        }
        return [
            'name' => $name,
            'hfr_id' => $hfrId,
            'address' => $address,
            'phone' => $phone,
            'email' => $email,
            'logo' => $logo,
        ];
    }

    /**
     * Resolve practitioner details for lab/radiology DiagnosticReportRecord.
     *
     * Best-effort strategy:
     * 1) read doctor id/name/registration from lab_request (when present)
     * 2) enrich from doctor_master using doctor id
     *
     * @param int                     $labReqId
     * @param object|array<string,mixed>|null $labReqRow
     * @return array<string,string>|null
     */
    private function resolveLabPractitionerForFhir(int $labReqId, $labReqRow = null): ?array
    {
        if ($labReqId <= 0 || ! $this->db->tableExists('lab_request')) {
            return null;
        }

        $doctorId = 0;
        $doctorName = '';
        $doctorRegNo = '';
        $labType = 0;
        $chargeId = 0;

        $candidateIdCols = ['doctor_id', 'dr_id', 'doc_id', 'r_doc_id', 'consultant_id', 'reported_by_doctor_id'];
        $candidateNameCols = ['doctor_name', 'dr_name', 'doc_name', 'r_doc_name', 'consultant_name', 'reported_by_name', 'reported_by'];
        $candidateRegCols = ['doctor_reg_no', 'registration_no', 'reg_no', 'doctor_registration_no'];

        // Try immediate row first (if caller passed a richer row object/array).
        if (is_object($labReqRow)) {
            $labType = (int) ($labReqRow->lab_type ?? 0);
            $chargeId = (int) ($labReqRow->charge_id ?? 0);
            foreach ($candidateIdCols as $c) {
                if (isset($labReqRow->{$c}) && (int) $labReqRow->{$c} > 0) {
                    $doctorId = (int) $labReqRow->{$c};
                    break;
                }
            }
            foreach ($candidateNameCols as $c) {
                $v = isset($labReqRow->{$c}) ? trim((string) $labReqRow->{$c}) : '';
                if ($v !== '') {
                    $doctorName = $v;
                    break;
                }
            }
            foreach ($candidateRegCols as $c) {
                $v = isset($labReqRow->{$c}) ? trim((string) $labReqRow->{$c}) : '';
                if ($v !== '') {
                    $doctorRegNo = $v;
                    break;
                }
            }
        } elseif (is_array($labReqRow)) {
            $labType = (int) ($labReqRow['lab_type'] ?? 0);
            $chargeId = (int) ($labReqRow['charge_id'] ?? 0);
            foreach ($candidateIdCols as $c) {
                if (isset($labReqRow[$c]) && (int) $labReqRow[$c] > 0) {
                    $doctorId = (int) $labReqRow[$c];
                    break;
                }
            }
            foreach ($candidateNameCols as $c) {
                $v = isset($labReqRow[$c]) ? trim((string) $labReqRow[$c]) : '';
                if ($v !== '') {
                    $doctorName = $v;
                    break;
                }
            }
            foreach ($candidateRegCols as $c) {
                $v = isset($labReqRow[$c]) ? trim((string) $labReqRow[$c]) : '';
                if ($v !== '') {
                    $doctorRegNo = $v;
                    break;
                }
            }
        }

        // Priority 1: explicit admin mapping by modality (Pathology/X-Ray/CT/USG/etc.).
        $testTitle = '';
        if ($chargeId > 0 && $this->db->tableExists('charge_master')) {
            try {
                $chargeRow = $this->db->table('charge_master')
                    ->select('charge_name')
                    ->where('id', $chargeId)
                    ->get(1)
                    ->getRowArray() ?? [];
                $testTitle = trim((string) ($chargeRow['charge_name'] ?? ''));
            } catch (\Throwable $e) {
                $testTitle = '';
            }
        }

        $mappedDoctorId = $this->resolveMappedDoctorIdForLabContext($labType, $testTitle);
        if ($mappedDoctorId > 0) {
            $mappedDoctor = $this->loadDoctorIdentityById($mappedDoctorId);
            if ($mappedDoctor !== null) {
                $doctorId = $mappedDoctorId;
                $doctorName = trim((string) ($mappedDoctor['name'] ?? ''));
                $doctorRegNo = trim((string) ($mappedDoctor['registration_number'] ?? ''));
            }
        }

        try {
            $labFields = $this->db->getFieldNames('lab_request') ?? [];
            $selectCols = ['id'];
            foreach (array_merge($candidateIdCols, $candidateNameCols, $candidateRegCols) as $c) {
                if (in_array($c, $labFields, true)) {
                    $selectCols[] = $c;
                }
            }

            if (count($selectCols) > 1) {
                $labDbRow = $this->db->table('lab_request')
                    ->select(implode(',', array_unique($selectCols)))
                    ->where('id', $labReqId)
                    ->get(1)
                    ->getRowArray() ?? [];

                if (! empty($labDbRow)) {
                    if ($doctorId <= 0) {
                        foreach ($candidateIdCols as $c) {
                            if (isset($labDbRow[$c]) && (int) $labDbRow[$c] > 0) {
                                $doctorId = (int) $labDbRow[$c];
                                break;
                            }
                        }
                    }
                    if ($doctorName === '') {
                        foreach ($candidateNameCols as $c) {
                            $v = isset($labDbRow[$c]) ? trim((string) $labDbRow[$c]) : '';
                            if ($v !== '') {
                                $doctorName = $v;
                                break;
                            }
                        }
                    }
                    if ($doctorRegNo === '') {
                        foreach ($candidateRegCols as $c) {
                            $v = isset($labDbRow[$c]) ? trim((string) $labDbRow[$c]) : '';
                            if ($v !== '') {
                                $doctorRegNo = $v;
                                break;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // fail-open; continue with what we already have
        }

        if ($doctorId > 0 && $this->db->tableExists('doctor_master')) {
            try {
                $dFields = $this->db->getFieldNames('doctor_master') ?? [];
                $dSelect = ['id'];
                foreach (['p_fname', 'p_lname', 'doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                    if (in_array($f, $dFields, true)) {
                        $dSelect[] = $f;
                    }
                }

                $dRow = $this->db->table('doctor_master')
                    ->select(implode(',', array_unique($dSelect)))
                    ->where('id', $doctorId)
                    ->get(1)
                    ->getRowArray() ?? [];

                if ($doctorName === '' && ! empty($dRow)) {
                    $doctorName = trim(trim((string) ($dRow['p_fname'] ?? '')) . ' ' . trim((string) ($dRow['p_lname'] ?? '')));
                }
                if ($doctorRegNo === '' && ! empty($dRow)) {
                    foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                        if (! empty($dRow[$f])) {
                            $doctorRegNo = trim((string) $dRow[$f]);
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fail-open
            }
        }

        if ($doctorName === '') {
            return null;
        }

        return [
            'name' => $doctorName,
            'registration_number' => $doctorRegNo,
        ];
    }

    private function resolveMappedDoctorIdForLabContext(int $labType, string $testTitle): int
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return 0;
        }

        $title = strtolower(trim($testTitle));
        if ($labType === 6) {
            if ($title !== '') {
                if (preg_match('/\bx\s*-?\s*ray\b|\bxray\b|\bradiograph\b/', $title) === 1) {
                    return $this->readMappedDoctorId('ABDM_DOC_XRAY');
                }
                if (preg_match('/\bct\b|\bct\s*scan\b|\bhrct\b|\bcect\b/', $title) === 1) {
                    return $this->readMappedDoctorId('ABDM_DOC_CTSCAN');
                }
                if (preg_match('/\busg\b|\bultra\s*sound\b|\bultrasound\b|\bsonograph\b/', $title) === 1) {
                    return $this->readMappedDoctorId('ABDM_DOC_ULTRASOUND');
                }
                if (preg_match('/\bmri\b|\bmr\b/', $title) === 1) {
                    return $this->readMappedDoctorId('ABDM_DOC_MRI');
                }
            }

            $radDefault = $this->readMappedDoctorId('ABDM_DOC_RADIOLOGY');
            if ($radDefault > 0) {
                return $radDefault;
            }
        }

        return $this->readMappedDoctorId('ABDM_DOC_PATHOLOGY');
    }

    private function readMappedDoctorId(string $settingKey): int
    {
        if ($settingKey === '' || ! $this->db->tableExists('hospital_setting')) {
            return 0;
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $settingKey)
            ->get(1)
            ->getRowArray();

        return (int) ($row['s_value'] ?? 0);
    }

    /**
     * @return array{name:string,registration_number:string}|null
     */
    private function loadDoctorIdentityById(int $doctorId): ?array
    {
        if ($doctorId <= 0 || ! $this->db->tableExists('doctor_master')) {
            return null;
        }

        try {
            $dFields = $this->db->getFieldNames('doctor_master') ?? [];
            $dSelect = ['id'];
            foreach (['p_title', 'p_fname', 'p_lname', 'doctor_reg_no', 'registration_no', 'reg_no', 'doc_reg_no', 'nmc_reg_no', 'mci_reg_no'] as $f) {
                if (in_array($f, $dFields, true)) {
                    $dSelect[] = $f;
                }
            }

            $dRow = $this->db->table('doctor_master')
                ->select(implode(',', array_unique($dSelect)))
                ->where('id', $doctorId)
                ->get(1)
                ->getRowArray() ?? [];

            if (empty($dRow)) {
                return null;
            }

            $name = trim(
                trim((string) ($dRow['p_title'] ?? '')) . ' ' .
                trim((string) ($dRow['p_fname'] ?? '')) . ' ' .
                trim((string) ($dRow['p_lname'] ?? ''))
            );
            if ($name === '') {
                return null;
            }

            $reg = '';
            foreach (['doctor_reg_no', 'registration_no', 'reg_no', 'doc_reg_no', 'nmc_reg_no', 'mci_reg_no'] as $f) {
                if (! empty($dRow[$f])) {
                    $reg = trim((string) $dRow[$f]);
                    break;
                }
            }

            return [
                'name' => $name,
                'registration_number' => $reg,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Map numeric lab_type code to a human-readable title.
     */
    private function mapLabTypeToTitle(int $labType): string
    {
        return match ($labType) {
            1 => 'Haematology',
            2 => 'Biochemistry',
            3 => 'Serology',
            4 => 'Microbiology',
            5 => 'Pathology / Cytology',
            6 => 'Radiology',
            7 => 'Urology',
            8 => 'Molecular Diagnostics',
            default => '',
        };
    }

    /**
     * Load lab_request_item rows for a lab request and build the structured
     * observations array expected by FhirR4Builder::buildLabReportBundle().
     *
     * Each element contains:
     *   test_name, loinc_code, value_type (quantity|string), value,
     *   unit, ucum_code, ref_low, ref_high, interpretation, status
     *
     * @param int    $labReqId  lab_request.id
     * @param string $status    raw status from lab_request.status
     * @return array<int, array<string, mixed>>
     */
    private function buildLabObservations(int $labReqId, string $status): array
    {
        if (! $this->db->tableExists('lab_request_item') || ! $this->db->tableExists('lab_tests')) {
            return [];
        }

        $rows = $this->db->table('lab_request_item lri')
            ->select(
                'lri.lab_test_id, lri.lab_test_value, lri.lab_test_remark,' .
                ' lt.Test, lt.Unit, lt.FixedNormals, lt.FixedNormalsWomen,' .
                ' lt.loinc_code, lt.loinc_scale, lt.loinc_system, lt.loinc_property,' .
                ' lt.isGenderSpecific'
            )
            ->join('lab_tests lt', 'lt.mstTestKey = lri.lab_test_id', 'left')
            ->where('lri.lab_request_id', $labReqId)
            ->orderBy('lri.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        $obsStatus = ($status == '1') ? 'final' : 'preliminary';
        $observations = [];

        foreach ($rows as $row) {
            $rawValue  = trim((string) ($row['lab_test_value'] ?? ''));
            if ($rawValue === '' || strtolower($rawValue) === 'n/a') {
                continue;
            }

            $testName  = trim((string) ($row['Test'] ?? ''));
            $loincCode = trim((string) ($row['loinc_code'] ?? ''));
            $unit      = trim((string) ($row['Unit'] ?? ''));
            $scale     = strtolower(trim((string) ($row['loinc_scale'] ?? '')));

            // Determine value type: Quantitative = numeric value
            $isNumeric = is_numeric($rawValue) && $scale !== 'nom' && $scale !== 'ord';
            $valueType = $isNumeric ? 'quantity' : 'string';

            // Parse reference range from "low-high" format (e.g. "12.00-16.00")
            $refLow = $refHigh = '';
            $normals = trim((string) ($row['FixedNormals'] ?? ''));
            if ($normals !== '' && str_contains($normals, '-')) {
                $parts = explode('-', $normals, 2);
                if (count($parts) === 2 && is_numeric(trim($parts[0])) && is_numeric(trim($parts[1]))) {
                    $refLow  = trim($parts[0]);
                    $refHigh = trim($parts[1]);
                }
            }

            // Interpretation: H/L/N based on value vs reference range
            $interpretation = 'N';
            if ($isNumeric && $refLow !== '' && $refHigh !== '') {
                $fVal = (float) $rawValue;
                if ($fVal < (float) $refLow) {
                    $interpretation = 'L';
                } elseif ($fVal > (float) $refHigh) {
                    $interpretation = 'H';
                }
            }

            $obs = [
                'test_name'      => $testName !== '' ? $testName : ('Test-' . $row['lab_test_id']),
                'loinc_code'     => $loincCode,
                'value_type'     => $valueType,
                'value'          => $rawValue,
                'unit'           => $unit,
                'ucum_code'      => $this->unitToUcum($unit),
                'ref_low'        => $refLow,
                'ref_high'       => $refHigh,
                'interpretation' => $interpretation,
                'status'         => $obsStatus,
            ];

            if ($row['lab_test_remark'] !== '' && $row['lab_test_remark'] !== null) {
                $obs['remark'] = trim((string) $row['lab_test_remark']);
            }

            $observations[] = $obs;
        }

        return $observations;
    }

    /**
     * Best-effort map of common lab units to UCUM codes.
     * Returns the original unit string when no UCUM equivalent is known.
     */
    private function unitToUcum(string $unit): string
    {
        $unit = trim($unit);
        if ($unit === '') {
            return '';
        }

        $map = [
            'g/dl'      => 'g/dL',
            'g/dL'      => 'g/dL',
            'mg/dl'     => 'mg/dL',
            'mg/dL'     => 'mg/dL',
            'mg/l'      => 'mg/L',
            'mmol/l'    => 'mmol/L',
            'umol/l'    => 'umol/L',
            'u/l'       => 'U/L',
            'iu/l'      => 'IU/L',
            'iu/ml'     => 'IU/mL',
            'cells/cumm'=> '10*3/uL',
            'cells/mm3' => '10*3/uL',
            '10^9/l'    => '10*9/L',
            '10^3/ul'   => '10*3/uL',
            'fl'        => 'fL',
            'pg'        => 'pg',
            '%'         => '%',
            'sec'       => 's',
            'min'       => 'min',
            'mmhg'      => 'mm[Hg]',
            'meq/l'     => 'meq/L',
        ];

        return $map[strtolower($unit)] ?? $unit;
    }

    /**
     * Returns a lazily-constructed AbdmAuditService instance.
     */
    private function getAuditService(): AbdmAuditService
    {
        static $svc = null;
        if ($svc === null) {
            $svc = new AbdmAuditService();
        }
        return $svc;
    }

    /**
     * Maps HMS inputs to official ABDM HI record types.
     */
    private function mapHiTypeToRecordType(string $hiType): string
    {
        return match (strtolower($hiType)) {
            'opconsultrecord', 'opconsultation' => 'OPConsultRecord',
            'prescriptionrecord', 'prescription' => 'PrescriptionRecord',
            'diagnosticreportrecord', 'lab_report', 'diagnosticreport' => 'DiagnosticReportRecord',
            'dischargesummaryrecord', 'discharge_summary', 'dischargesummary' => 'DischargeSummaryRecord',
            'wellnessrecord' => 'WellnessRecord',
            'immunizationrecord' => 'ImmunizationRecord',
            'invoicerecord' => 'InvoiceRecord',
            'healthdocumentrecord', 'health_document' => 'HealthDocumentRecord',
            default => 'HealthDocumentRecord',
        };
    }

    private function pushAdditionalHiRecord(array $payload, int $patientId, string $abhaId, string $consentHandle)
    {
        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId);
        $abhaNumber = $abhaIdentity['abha_id'];
        $abhaAddress = $abhaIdentity['abha_address'];
        $abhaId = $abhaAddress !== '' ? $abhaAddress : $abhaNumber;
        $hiType = (string) ($payload['hi_type'] ?? 'HealthDocumentRecord');
        $entityType = (string) ($payload['entity_type'] ?? strtolower($hiType));
        $entityId = (string) ($payload['entity_id'] ?? $patientId);
        $ccRef = (string) ($payload['care_context_reference'] ?? ($hiType . '-' . $patientId . '-' . date('Y-m-d')));
        $ccDisplay = (string) ($payload['care_context_display'] ?? $hiType);
        $bundle = (array) ($payload['bundle'] ?? []);
        $visitDate = (string) ($payload['visit_date'] ?? date('Y-m-d'));
        $patientName = (string) ($payload['patient_name'] ?? ('PATIENT-' . $patientId));
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $consent = $abhaId !== '' ? $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle) : null;
        $consentWarning = '';
        if ($abhaId !== '' && $consent === null) {
            $consentWarning = 'No active consent found. Proceeding with care-context push only.';
            $this->logMissingConsentShareWarning($entityType, (int) $entityId, $patientId, $abhaId, $consentHandle, 'abdm.' . $entityType . '.share.consent_missing', $consentWarning);
        }
        $effectiveConsent = (string) ($consent['consent_handle'] ?? $consentHandle);

        $healthRecordId = $this->storeHealthRecord([
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'hi_type' => $hiType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'fhir_bundle' => $bundleJson,
            'care_context_reference' => $ccRef,
            'consent_handle' => $effectiveConsent,
        ]);

        if ($abhaId === '') {
            if ($healthRecordId > 0 && $this->db->tableExists('health_records')) {
                $this->db->table('health_records')->where('id', $healthRecordId)->update([
                    'push_status' => 'local_only',
                    'updated_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
                ]);
            }

            return $this->response->setJSON([
                'ok' => 1,
                'status' => 'local_stored',
                'health_record_id' => $healthRecordId,
                'care_context_reference' => $ccRef,
                'message' => 'ABHA not available. Record stored locally for later discovery.',
            ]);
        }

        $queueId = null;
        $bridgeRecordId = 0;
        $connectorError = null;
        try {
            $patientRowForPush = $this->loadPatientRow($patientId);
            $patientBirthYear = $this->resolvePatientBirthYear(
                $patientRowForPush,
                str_contains($abhaId, '@') ? $abhaId : '',
                str_contains($abhaId, '@') ? '' : $abhaId
            );
            $result = $this->connector->pushRecord([
                'patient_id' => (string) $patientId,
                'patient_name' => $patientName,
                'abha_id' => $abhaNumber,
                'abha_address' => $abhaAddress,
                'year_of_birth' => $patientBirthYear,
                'hi_type' => $hiType,
                'record_type' => $hiType,
                'visit_date' => $visitDate,
                'care_context_reference' => $ccRef,
                'care_context_display' => $ccDisplay,
                'notes' => $ccDisplay,
                'queue_id' => $ccRef,
                'record_data' => $bundle,
            ]);
            $this->logGatewayPushResolution('additional_hi_record', $result);
            $queueId = $this->extractGatewayPushQueueId($result);
            $bridgeRecordId = $this->extractGatewayPushRecordId($result);
            if (! $this->isGatewayPushSubmitted($result)) {
                $connectorError = $this->extractGatewayPushErrorText($result);
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
            }
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError, $bridgeRecordId);
        }

        $this->getAuditService()->log([
            'action' => 'push_record',
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'abha_id' => $abhaId,
            'patient_id' => $patientId,
            'request' => ['hi_type' => $hiType, 'care_context_reference' => $ccRef, 'consent_handle' => $effectiveConsent],
            'response' => ['queue_id' => $queueId, 'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null, 'warning' => $consentWarning],
            'outcome' => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok' => $connectorError === null ? 1 : 0,
            'queue_id' => $queueId,
            'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            'health_record_id' => $healthRecordId,
            'care_context_reference' => $ccRef,
            'consent_handle' => $effectiveConsent,
            'status' => $connectorError === null ? 'queued' : 'failed',
            'message' => $connectorError === null && $consentWarning !== '' ? 'Record pushed to gateway without active consent. Recheck link status in M2 ABDM Gateway.' : null,
            'warning' => $consentWarning !== '' ? $consentWarning : null,
            'error' => $connectorError,
        ]);
    }

    private function buildWellnessRecordPayload(int $patientId, int $opdId, string $abhaId): ?array
    {
        $patientRow = $this->loadPatientRow($patientId);
        if (empty($patientRow)) {
            return null;
        }

        $row = [];
        if ($this->db->tableExists('opd_prescription')) {
            $builder = $this->db->table('opd_prescription')->where('p_id', $patientId);
            if ($opdId > 0) {
                $builder->where('opd_id', $opdId);
            }
            $builder->groupStart()
                ->where("COALESCE(NULLIF(TRIM(bp), ''), NULLIF(TRIM(diastolic), ''), NULLIF(TRIM(pulse), ''), NULLIF(TRIM(height), ''), NULLIF(TRIM(weight), ''), NULLIF(TRIM(temp), ''), NULLIF(TRIM(rr_min), ''), NULLIF(TRIM(spo2), ''), NULLIF(TRIM(glucose), '')) IS NOT NULL", null, false)
                ->groupEnd();
            $row = $builder->orderBy('id', 'DESC')->get(1)->getRowArray() ?? [];
        }

        $vitals = $this->extractWellnessVitals($row);
        $lifestyle = $this->extractPatientLifestyleObservations($patientRow);
        if (empty($vitals) && empty($lifestyle)) {
            return null;
        }

        $patient = $this->buildAbdmPatientResource($patientRow, $patientId, $abhaId);
        $bundle = $this->buildSimpleWellnessBundle($patient, $vitals, $lifestyle);
        $visitDate = (string) ($row['date_opd_visit'] ?? date('Y-m-d'));
        $entityId = (string) ($opdId > 0 ? $opdId : ($row['opd_id'] ?? $patientId));

        return [
            'hi_type' => 'WellnessRecord',
            'entity_type' => 'wellness',
            'entity_id' => $entityId,
            'patient_id' => $patientId,
            'patient_name' => $this->patientDisplayName($patientRow),
            'visit_date' => $visitDate,
            'care_context_reference' => 'WELLNESS-' . $entityId . '-' . date('Y-m-d', strtotime($visitDate)),
            'care_context_display' => 'Wellness Record - ' . date('d/m/Y', strtotime($visitDate)),
            'bundle' => $bundle,
        ];
    }

    private function buildHealthDocumentRecordPayload(int $patientId, string $abhaId): ?array
    {
        $patientRow = $this->loadPatientRow($patientId);
        if (empty($patientRow)) {
            return null;
        }

        $title = trim((string) ($this->request->getPost('document_title') ?? $this->request->getPost('title') ?? ''));
        $documentText = trim((string) ($this->request->getPost('document_text') ?? $this->request->getPost('notes') ?? ''));
        $contentType = trim((string) ($this->request->getPost('content_type') ?? ''));
        $base64 = trim((string) ($this->request->getPost('document_base64') ?? ''));
        $filePath = trim((string) ($this->request->getPost('file_path') ?? $this->request->getPost('attachment_path') ?? ''));
        if ($title === '') {
            $title = 'Health Document';
        }
        if ($base64 === '' && $documentText !== '') {
            $contentType = 'text/html';
            $base64 = base64_encode('<div xmlns="http://www.w3.org/1999/xhtml">' . esc($documentText) . '</div>');
        }
        if ($base64 === '' && $filePath !== '') {
            $file = $this->resolveReadableAttachmentPath($filePath);
            if ($file !== '') {
                $base64 = base64_encode((string) file_get_contents($file));
                $contentType = $contentType !== '' ? $contentType : ((string) (mime_content_type($file) ?: 'application/octet-stream'));
            }
        }
        if ($base64 === '') {
            return null;
        }
        $contentType = $contentType !== '' ? $contentType : 'application/octet-stream';

        $visitDate = date('Y-m-d');
        $entityId = trim((string) ($this->request->getPost('document_id') ?? $this->request->getPost('entity_id') ?? '')) ?: (string) $patientId;
        $patient = $this->buildAbdmPatientResource($patientRow, $patientId, $abhaId);
        $bundle = $this->buildSimpleHealthDocumentBundle($patient, $title, $contentType, $base64);

        return [
            'hi_type' => 'HealthDocumentRecord',
            'entity_type' => 'health_document',
            'entity_id' => $entityId,
            'patient_id' => $patientId,
            'patient_name' => $this->patientDisplayName($patientRow),
            'visit_date' => $visitDate,
            'care_context_reference' => 'HDOC-' . $patientId . '-' . $entityId . '-' . $visitDate,
            'care_context_display' => $title,
            'bundle' => $bundle,
        ];
    }

    private function buildInvoiceRecordPayload(int $invoiceId, int $patientId, string $abhaId): ?array
    {
        if (! $this->db->tableExists('invoice_master')) {
            return null;
        }
        $invoice = $this->db->table('invoice_master')->where('id', $invoiceId)->get(1)->getRowArray() ?? [];
        if (empty($invoice)) {
            return null;
        }
        if ($patientId <= 0) {
            $patientId = (int) ($invoice['attach_id'] ?? 0);
        }
        $patientRow = $this->loadPatientRow($patientId);
        if ($patientId <= 0 || empty($patientRow)) {
            return null;
        }
        $items = $this->db->tableExists('invoice_item')
            ? $this->db->table('invoice_item')->where('inv_master_id', $invoiceId)->orderBy('id', 'ASC')->get()->getResultArray()
            : [];

        $visitDate = (string) ($invoice['inv_date'] ?? date('Y-m-d'));
        $patient = $this->buildAbdmPatientResource($patientRow, $patientId, $abhaId);
        $bundle = $this->buildSimpleInvoiceBundle($patient, $invoice, $items);

        return [
            'hi_type' => 'InvoiceRecord',
            'entity_type' => 'invoice',
            'entity_id' => (string) $invoiceId,
            'patient_id' => $patientId,
            'patient_name' => $this->patientDisplayName($patientRow),
            'visit_date' => date('Y-m-d', strtotime($visitDate)),
            'care_context_reference' => 'INV-' . $invoiceId . '-' . date('Y-m-d', strtotime($visitDate)),
            'care_context_display' => 'Invoice ' . (string) ($invoice['invoice_code'] ?? $invoiceId),
            'bundle' => $bundle,
        ];
    }

    private function buildInvoiceSourceRecordPayload(string $source, int $billId, int $patientId, string $abhaId): ?array
    {
        $invoice = [];
        $items = [];
        $sourcePrefix = '';

        if ($source === 'opd_invoice' && $this->db->tableExists('opd_master')) {
            $row = $this->db->table('opd_master')->where('opd_id', $billId)->get(1)->getRowArray() ?? [];
            if ($row !== []) {
                $amount = (float) ($row['opd_fee_amount'] ?? 0);
                $invoice = [
                    'id' => $billId,
                    'invoice_code' => (string) ($row['opd_code'] ?? $billId),
                    'invoice_type_code' => '03',
                    'invoice_type_display' => 'OPD',
                    'encounter_class' => 'AMB',
                    'practitioner_id' => (int) ($row['doc_id'] ?? 0),
                    'practitioner_name' => trim((string) ($row['doc_name'] ?? '')),
                    'encounter_start' => (string) ($row['apointment_date'] ?? $row['opd_book_date'] ?? ''),
                    'inv_date' => (string) ($row['apointment_date'] ?? $row['opd_book_date'] ?? date('Y-m-d')),
                    'net_amount' => $amount,
                    'total_amount' => (float) ($row['opd_fee_gross_amount'] ?? $amount),
                    'patient_id' => (int) ($row['p_id'] ?? 0),
                ];
                $items[] = [
                    'item_name' => trim((string) ($row['opd_fee_desc'] ?? '')) ?: 'OPD Consultation Fee',
                    'item_qty' => 1,
                    'item_rate' => $amount,
                    'item_amount' => $amount,
                ];
                $sourcePrefix = 'OPD';
            }
        } elseif ($source === 'charges_invoice' && $this->db->tableExists('invoice_master')) {
            $row = $this->db->table('invoice_master')->where('id', $billId)->get(1)->getRowArray() ?? [];
            if ($row !== []) {
                $contextRow = [];
                $opdCode = trim((string) ($row['opd_code'] ?? ''));
                if ($opdCode !== '' && $opdCode !== '0' && $this->db->tableExists('opd_master')) {
                    $contextRow = $this->db->table('opd_master')->where('opd_code', $opdCode)->get(1)->getRowArray() ?? [];
                } elseif ((int) ($row['ipd_id'] ?? 0) > 0 && $this->db->tableExists('ipd_master')) {
                    $contextRow = $this->db->table('ipd_master')->where('id', (int) $row['ipd_id'])->get(1)->getRowArray() ?? [];
                }
                $isIpdContext = isset($contextRow['ipd_code']);
                $invoice = [
                    'id' => $billId,
                    'invoice_code' => (string) ($row['invoice_code'] ?? $billId),
                    'invoice_type_code' => '99',
                    'invoice_type_display' => 'Others',
                    'encounter_class' => $isIpdContext ? 'IMP' : 'AMB',
                    'practitioner_id' => (int) ($contextRow[$isIpdContext ? 'r_doc_id' : 'doc_id'] ?? 0),
                    'practitioner_name' => trim((string) ($contextRow[$isIpdContext ? 'r_doc_name' : 'doc_name'] ?? '')),
                    'encounter_start' => (string) ($contextRow[$isIpdContext ? 'register_date' : 'apointment_date'] ?? $row['inv_date'] ?? ''),
                    'encounter_end' => (string) ($contextRow['discharge_date'] ?? $row['inv_date'] ?? ''),
                    'inv_date' => (string) ($row['inv_date'] ?? date('Y-m-d')),
                    'net_amount' => (float) ($row['net_amount'] ?? 0),
                    'total_amount' => (float) ($row['total_amount'] ?? $row['net_amount'] ?? 0),
                    'patient_id' => (int) ($row['attach_id'] ?? 0),
                ];
                $items = $this->db->tableExists('invoice_item')
                    ? $this->db->table('invoice_item')->where('inv_master_id', $billId)->orderBy('id', 'ASC')->get()->getResultArray()
                    : [];
                $sourcePrefix = 'CHG';
            }
        } elseif ($source === 'ipd_invoice' && $this->db->tableExists('ipd_master')) {
            $row = $this->db->table('ipd_master')->where('id', $billId)->get(1)->getRowArray() ?? [];
            if ($row !== []) {
                $invoice = [
                    'id' => $billId,
                    'invoice_code' => (string) ($row['ipd_code'] ?? $billId),
                    'invoice_type_code' => '02',
                    'invoice_type_display' => 'IPD',
                    'encounter_class' => 'IMP',
                    'practitioner_id' => (int) ($row['r_doc_id'] ?? 0),
                    'practitioner_name' => trim((string) ($row['r_doc_name'] ?? '')),
                    'encounter_start' => (string) ($row['register_date'] ?? ''),
                    'encounter_end' => (string) ($row['discharge_date'] ?? ''),
                    'inv_date' => (string) ($row['discharge_date'] ?? $row['register_date'] ?? date('Y-m-d')),
                    'net_amount' => (float) ($row['net_amount'] ?? 0),
                    'total_amount' => (float) ($row['gross_amount'] ?? $row['net_amount'] ?? 0),
                    'patient_id' => (int) ($row['p_id'] ?? 0),
                ];

                if ($this->db->tableExists('ipd_invoice_item')) {
                    $items = $this->db->table('ipd_invoice_item')
                        ->select('item_name, item_qty, item_rate, item_amount')
                        ->where('ipd_id', $billId)
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();
                }
                if ($this->db->tableExists('invoice_master') && $this->db->tableExists('invoice_item')) {
                    $chargeItems = $this->db->table('invoice_master i')
                        ->select('t.item_name, t.item_qty, t.item_rate, t.item_amount')
                        ->join('invoice_item t', 't.inv_master_id = i.id')
                        ->where('i.ipd_id', $billId)
                        ->where('i.ipd_include', 1)
                        ->orderBy('t.id', 'ASC')
                        ->get()
                        ->getResultArray();
                    $items = array_merge($items, $chargeItems);
                }
                if ($this->db->tableExists('invoice_med_master')) {
                    $medicineBills = $this->db->table('invoice_med_master')
                        ->select('inv_med_code, net_amount')
                        ->where('ipd_id', $billId)
                        ->where('ipd_credit', 1)
                        ->where('ipd_credit_type', 1)
                        ->orderBy('id', 'ASC')
                        ->get()
                        ->getResultArray();
                    foreach ($medicineBills as $medicineBill) {
                        $amount = (float) ($medicineBill['net_amount'] ?? 0);
                        $items[] = [
                            'item_name' => 'Pharmacy ' . (string) ($medicineBill['inv_med_code'] ?? ''),
                            'item_qty' => 1,
                            'item_rate' => $amount,
                            'item_amount' => $amount,
                        ];
                    }
                }
                $sourcePrefix = 'IPD';
            }
        }

        $resolvedPatientId = (int) ($invoice['patient_id'] ?? 0);
        if ($invoice === [] || $resolvedPatientId <= 0 || ($patientId > 0 && $patientId !== $resolvedPatientId)) {
            return null;
        }

        $patientRow = $this->loadPatientRow($resolvedPatientId);
        if ($patientRow === []) {
            return null;
        }

        if ($abhaId === '') {
            $abhaId = $this->resolvePatientAbhaIdentifier($resolvedPatientId);
        }
        $visitTimestamp = strtotime((string) ($invoice['inv_date'] ?? ''));
        $visitDate = $visitTimestamp !== false ? date('Y-m-d', $visitTimestamp) : date('Y-m-d');
        $patient = $this->buildAbdmPatientResource($patientRow, $resolvedPatientId, $abhaId);

        return [
            'hi_type' => 'InvoiceRecord',
            'entity_type' => $source,
            'entity_id' => (string) $billId,
            'patient_id' => $resolvedPatientId,
            'patient_name' => $this->patientDisplayName($patientRow),
            'visit_date' => $visitDate,
            'care_context_reference' => 'INVOICE-' . $sourcePrefix . '-' . $billId . '-' . $visitDate,
            'care_context_display' => 'Invoice ' . (string) ($invoice['invoice_code'] ?? $billId),
            'bundle' => $this->buildSimpleInvoiceBundle($patient, $invoice, $items),
        ];
    }

    /**
     * Share endpoints are triggered from screens that do not carry the ABHA, and an
     * empty identifier silently downgrades the record to local-only instead of pushing.
     */
    private function resolvePatientAbhaIdentifier(int $patientId): string
    {
        $identity = $this->resolvePatientAbhaIdentity($patientId);

        return $identity['abha_address'] !== '' ? $identity['abha_address'] : $identity['abha_id'];
    }

    /** @return array{abha_id:string,abha_address:string} */
    private function resolvePatientAbhaIdentity(int $patientId, string $abhaId = '', string $abhaAddress = ''): array
    {
        $number = '';
        $address = '';
        $row = $this->loadPatientRow($patientId);
        $candidates = [
            $abhaAddress,
            $abhaId,
            (string) ($row['abha_address'] ?? ''),
            (string) ($row['abha_id'] ?? ''),
            (string) ($row['abha_no'] ?? ''),
            (string) ($row['abha'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '') {
                continue;
            }
            if ($address === '' && str_contains($candidate, '@')) {
                $address = $candidate;
                continue;
            }
            $digits = preg_replace('/\D/', '', $candidate);
            if ($number === '' && is_string($digits) && strlen($digits) === 14) {
                $number = $digits;
            }
        }

        return ['abha_id' => $number, 'abha_address' => $address];
    }

    private function loadPatientRow(int $patientId): array
    {
        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return [];
        }
        return $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
    }

    private function patientDisplayName(array $patientRow): string
    {
        $first = trim((string) ($patientRow['p_fname'] ?? ''));
        $last = trim((string) ($patientRow['p_lname'] ?? ''));

        if (in_array(strtolower($last), ['0', '00', 'na', 'n/a', 'null', 'nil', '-'], true)) {
            $last = '';
        }

        $full = trim($first . ' ' . $last);
        return $full !== '' ? $full : 'Patient';
    }

    /**
     * Resolve patient birth year required by ABDM link-token flow.
     */
    private function resolvePatientBirthYear(array $patientRow, string $abhaAddress = '', string $abhaNumber = ''): int
    {
        $year = 0;

        foreach (['birth_year', 'year_of_birth'] as $field) {
            $candidate = preg_replace('/\D/', '', (string) ($patientRow[$field] ?? ''));
            $candidate = is_string($candidate) ? (int) $candidate : 0;
            if ($candidate >= 1900 && $candidate <= 2200) {
                return $candidate;
            }
        }

        foreach (['dob', 'p_dob', 'birth_date', 'date_of_birth'] as $field) {
            $raw = trim((string) ($patientRow[$field] ?? ''));
            if ($raw === '') {
                continue;
            }
            $ts = strtotime($raw);
            if ($ts !== false) {
                $candidate = (int) date('Y', $ts);
                if ($candidate >= 1900 && $candidate <= 2200) {
                    return $candidate;
                }
            }
        }

        foreach (['age', 'p_age', 'patient_age'] as $field) {
            $ageDigits = preg_replace('/\D/', '', (string) ($patientRow[$field] ?? ''));
            $age = is_string($ageDigits) ? (int) $ageDigits : 0;
            if ($age > 0 && $age < 130) {
                $candidate = (int) date('Y') - $age;
                if ($candidate >= 1900 && $candidate <= 2200) {
                    return $candidate;
                }
            }
        }

        $source = trim($abhaAddress !== '' ? $abhaAddress : $abhaNumber);
        if ($source !== '' && preg_match('/(19\d{2}|20\d{2}|21\d{2}|2200)/', $source, $m) === 1) {
            $year = (int) ($m[1] ?? 0);
            if ($year >= 1900 && $year <= 2200) {
                return $year;
            }
        }

        return 0;
    }

    private function buildAbdmPatientResource(array $patientRow, int $patientId, string $abhaId): array
    {
        $genderRaw = trim((string) ($patientRow['gender'] ?? ''));
        $gender = match ($genderRaw) {
            '1', 'M', 'm', 'male', 'Male' => 'male',
            '2', 'F', 'f', 'female', 'Female' => 'female',
            default => 'unknown',
        };
        $patient = [
            'resourceType' => 'Patient',
            'id' => 'patient-' . $patientId,
            'name' => [['text' => $this->patientDisplayName($patientRow)]],
            'gender' => $gender,
        ];
        if (! empty($patientRow['dob'])) {
            $patient['birthDate'] = date('Y-m-d', strtotime((string) $patientRow['dob']));
        }
        $patient['identifier'] = [];
        $uhid = trim((string) ($patientRow['p_code'] ?? ''));
        if ($uhid !== '') {
            $patient['identifier'][] = [
                'system' => 'https://hms.local/fhir/uhid',
                'value' => $uhid,
            ];
        }
        $abhaIdentity = $this->resolvePatientAbhaIdentity($patientId, $abhaId);
        if ($abhaIdentity['abha_id'] !== '') {
            $patient['identifier'][] = [
                'system' => 'https://healthid.ndhm.gov.in',
                'value' => $abhaIdentity['abha_id'],
            ];
        }
        if ($abhaIdentity['abha_address'] !== '') {
            $patient['identifier'][] = [
                'system' => 'https://healthid.ndhm.gov.in/abha-address',
                'value' => $abhaIdentity['abha_address'],
            ];
        }
        if ($patient['identifier'] === []) {
            unset($patient['identifier']);
        }
        return $patient;
    }

    private function extractWellnessVitals(array $row): array
    {
        if (empty($row)) {
            return [];
        }
        $vitals = [];
        $add = static function (string $code, string $display, mixed $value, string $unit, string $ucum) use (&$vitals): void {
            if ($value === null || trim((string) $value) === '' || ! is_numeric($value)) {
                return;
            }
            $vitals[] = ['loinc_code' => $code, 'display' => $display, 'value' => (float) $value, 'unit' => $unit, 'ucum_code' => $ucum];
        };
        $add('8480-6', 'Systolic blood pressure', $row['bp'] ?? null, 'mmHg', 'mm[Hg]');
        $add('8462-4', 'Diastolic blood pressure', $row['diastolic'] ?? null, 'mmHg', 'mm[Hg]');
        $add('8867-4', 'Heart rate', $row['pulse'] ?? null, '/min', '/min');
        $add('8302-2', 'Body height', $row['height'] ?? null, 'cm', 'cm');
        $add('29463-7', 'Body weight', $row['weight'] ?? null, 'kg', 'kg');
        $temp = $row['temp'] ?? null;
        if ($temp !== null && is_numeric($temp) && (float) $temp > 45) {
            $temp = (((float) $temp - 32) * 5) / 9;
        }
        $add('8310-5', 'Body temperature', $temp, 'Cel', 'Cel');
        $add('9279-1', 'Respiratory rate', $row['rr_min'] ?? null, '/min', '/min');
        $add('59408-5', 'Oxygen saturation in Arterial blood by Pulse oximetry', $row['spo2'] ?? null, '%', '%');
        $add('2339-0', 'Glucose', $row['glucose'] ?? null, 'mg/dL', 'mg/dL');
        return $vitals;
    }

    private function extractPatientLifestyleObservations(array $patientRow): array
    {
        $map = [
            'is_smoking' => 'Smoking status',
            'is_alcohol' => 'Alcohol use',
            'is_drug_abuse' => 'Drug abuse status',
            'is_tobacoo' => 'Tobacco use',
            'is_hypertesion' => 'Hypertension history',
            'is_niddm' => 'Diabetes history',
        ];
        $items = [];
        foreach ($map as $field => $label) {
            $value = trim((string) ($patientRow[$field] ?? ''));
            if ($value !== '' && $value !== '0') {
                $items[] = ['display' => $label, 'value' => 'Yes'];
            }
        }
        if (trim((string) ($patientRow['Others'] ?? '')) !== '') {
            $items[] = ['display' => 'Other wellness notes', 'value' => trim((string) $patientRow['Others'])];
        }
        return $items;
    }

    private function buildSimpleWellnessBundle(array $patient, array $vitals, array $lifestyle): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->format(DATE_ATOM);
        $patientRef = 'Patient/' . (string) ($patient['id'] ?? 'patient-unknown');
        $entries = [];
        $sectionEntries = [];
        $index = 1;
        foreach ($vitals as $vital) {
            $id = 'wellness-obs-' . $index++;
            $sectionEntries[] = ['reference' => 'Observation/' . $id];
            $entries[] = ['resource' => [
                'resourceType' => 'Observation',
                'id' => $id,
                'status' => 'final',
                'category' => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/observation-category', 'code' => 'vital-signs', 'display' => 'Vital Signs']]]],
                'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => (string) $vital['loinc_code'], 'display' => (string) $vital['display']]], 'text' => (string) $vital['display']],
                'subject' => ['reference' => $patientRef],
                'effectiveDateTime' => $issuedAt,
                'valueQuantity' => ['value' => (float) $vital['value'], 'unit' => (string) $vital['unit'], 'system' => 'http://unitsofmeasure.org', 'code' => (string) $vital['ucum_code']],
            ]];
        }
        foreach ($lifestyle as $item) {
            $id = 'wellness-social-' . $index++;
            $sectionEntries[] = ['reference' => 'Observation/' . $id];
            $entries[] = ['resource' => [
                'resourceType' => 'Observation',
                'id' => $id,
                'status' => 'final',
                'category' => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/observation-category', 'code' => 'social-history', 'display' => 'Social History']]]],
                'code' => ['text' => (string) ($item['display'] ?? 'Lifestyle')],
                'subject' => ['reference' => $patientRef],
                'effectiveDateTime' => $issuedAt,
                'valueString' => (string) ($item['value'] ?? ''),
            ]];
        }
        $composition = ['resource' => [
            'resourceType' => 'Composition',
            'id' => 'composition-wellness-' . date('YmdHis'),
            'status' => 'final',
            'type' => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '371529009', 'display' => 'Health maintenance report']]],
            'subject' => ['reference' => $patientRef],
            'date' => $issuedAt,
            'title' => 'Wellness Record',
            'section' => [['title' => 'Vital Signs and Wellness', 'entry' => $sectionEntries]],
        ]];
        return ['resourceType' => 'Bundle', 'type' => 'document', 'timestamp' => $issuedAt, 'entry' => array_merge([$composition, ['resource' => $patient]], $entries)];
    }

    private function buildSimpleHealthDocumentBundle(array $patient, string $title, string $contentType, string $base64): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->format(DATE_ATOM);
        $seed = $title . '|' . $issuedAt . '|' . $base64;
        $uuid = static function (string $value): string {
            $hash = md5($value);
            return sprintf('%s-%s-4%s-a%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 13, 3), substr($hash, 17, 3), substr($hash, 20, 12));
        };
        $patientUrl = 'urn:uuid:' . $uuid($seed . '|patient');
        $binaryUrl = 'urn:uuid:' . $uuid($seed . '|binary');
        $organizationUrl = 'urn:uuid:' . $uuid($seed . '|organization');
        $patient['meta']['profile'] = array_values(array_unique(array_merge((array) ($patient['meta']['profile'] ?? []), ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient'])));
        $hospital = $this->getHospitalProfileForFhir();
        $organization = ['resourceType' => 'Organization', 'id' => substr($organizationUrl, 9), 'name' => trim((string) ($hospital['name'] ?? '')) ?: 'Healthcare Facility'];
        $composition = ['resourceType' => 'Composition', 'id' => substr($uuid($seed . '|composition'), 0), 'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/HealthDocumentRecord']], 'status' => 'final', 'type' => ['text' => 'Health Document Record'], 'subject' => ['reference' => $patientUrl], 'author' => [['reference' => $organizationUrl]], 'date' => $issuedAt, 'title' => $title, 'section' => [['title' => 'Invoice PDF', 'entry' => [['reference' => $binaryUrl, 'type' => 'Binary']]]]];
        $entries = [
            ['fullUrl' => 'urn:uuid:' . $uuid($seed . '|composition'), 'resource' => $composition],
            ['fullUrl' => $patientUrl, 'resource' => $patient],
            ['fullUrl' => $organizationUrl, 'resource' => $organization],
        ];
        $entries[] = ['fullUrl' => $binaryUrl, 'resource' => ['resourceType' => 'Binary', 'id' => substr($binaryUrl, 9), 'contentType' => $contentType, 'data' => $base64]];
        return ['resourceType' => 'Bundle', 'type' => 'document', 'timestamp' => $issuedAt, 'meta' => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle']], 'entry' => $entries];
    }

    private function buildSimpleInvoiceBundle(array $patient, array $invoice, array $items): array
    {
        $issuedAt = Time::now('Asia/Kolkata')->format(DATE_ATOM);
        $invoiceNumber = trim((string) ($invoice['invoice_code'] ?? $invoice['id'] ?? '')) ?: 'unknown';
        $invoiceId = 'invoice-' . (int) ($invoice['id'] ?? 0);
        $compositionId = 'composition-' . $invoiceId;
        $patientId = (string) ($patient['id'] ?? 'patient-unknown');
        $organizationId = 'organization-invoice-issuer';
        $encounterId = $invoiceId . '-encounter';
        $patientRef = 'urn:uuid:' . $patientId;
        $invoiceRef = 'urn:uuid:' . $invoiceId;
        $organizationRef = 'urn:uuid:' . $organizationId;
        $encounterRef = 'urn:uuid:' . $encounterId;
        $identifierSystem = 'https://hms.local/fhir/invoice';
        $hospital = $this->getHospitalProfileForFhir();
        $hospitalName = trim((string) ($hospital['name'] ?? '')) ?: 'Healthcare Facility';
        $hospitalIdentifier = trim((string) ($hospital['hfr_id'] ?? '')) ?: 'local-hospital';
        $invoiceDateTimestamp = strtotime((string) ($invoice['inv_date'] ?? ''));
        $invoiceDate = $invoiceDateTimestamp !== false ? date('Y-m-d', $invoiceDateTimestamp) : date('Y-m-d');
        $encounterStartTimestamp = strtotime((string) ($invoice['encounter_start'] ?? ''));
        $encounterEndTimestamp = strtotime((string) ($invoice['encounter_end'] ?? ''));
        $encounterStart = $encounterStartTimestamp !== false ? date('Y-m-d', $encounterStartTimestamp) : $invoiceDate;
        $encounterEnd = $encounterEndTimestamp !== false ? date('Y-m-d', $encounterEndTimestamp) : $encounterStart;
        $netAmount = (float) ($invoice['net_amount'] ?? $invoice['total_amount'] ?? 0);
        $grossAmount = (float) ($invoice['total_amount'] ?? $invoice['net_amount'] ?? 0);
        $invoiceTypeCode = trim((string) ($invoice['invoice_type_code'] ?? '99')) ?: '99';
        $invoiceTypeDisplay = trim((string) ($invoice['invoice_type_display'] ?? 'Others')) ?: 'Others';
        $encounterClass = trim((string) ($invoice['encounter_class'] ?? 'AMB')) ?: 'AMB';
        $practitionerName = trim((string) ($invoice['practitioner_name'] ?? ''));
        $practitionerSourceId = (int) ($invoice['practitioner_id'] ?? 0);
        $practitionerId = $practitionerName !== ''
            ? 'practitioner-' . ($practitionerSourceId > 0 ? $practitionerSourceId : substr(md5($practitionerName), 0, 12))
            : '';
        $practitionerRef = $practitionerId !== '' ? 'urn:uuid:' . $practitionerId : '';
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $documentReferenceId = $invoiceId . '-pdf';
        $documentReferenceRef = 'urn:uuid:' . $documentReferenceId;
        $pdfBinaryId = $invoiceId . '-pdf-content';
        $pdfFileStem = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', 'Invoice-' . $invoiceNumber), '-');
        $pdfFilename = ($pdfFileStem !== '' ? $pdfFileStem : 'Invoice') . '.pdf';

        $lineItems = [];
        $chargeItems = [];
        foreach ($items as $idx => $item) {
            $itemName = trim((string) ($item['item_name'] ?? '')) ?: 'Item';
            $quantity = (float) ($item['item_qty'] ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $unit = trim((string) ($item['item_unit'] ?? $item['unit'] ?? '')) ?: 'unit';
            $rate = (float) ($item['item_rate'] ?? $item['item_amount'] ?? 0);
            $chargeItemId = $invoiceId . '-charge-' . ($idx + 1);
            $chargeItemRef = 'urn:uuid:' . $chargeItemId;
            $chargeItems[] = [
                'resourceType' => 'ChargeItem',
                'id' => $chargeItemId,
                'meta' => [
                    'versionId' => '1',
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/ChargeItem'],
                ],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>' . $escape($itemName)
                        . ': ' . $escape((string) $quantity) . ' ' . $escape($unit) . '</p></div>',
                ],
                'status' => 'billed',
                'code' => [
                    'coding' => [[
                        'system' => 'https://nrces.in/ndhm/fhir/r4/CodeSystem/ndhm-billing-codes',
                        'code' => $invoiceTypeCode,
                        'display' => $invoiceTypeDisplay,
                    ]],
                    'text' => $itemName,
                ],
                'subject' => ['reference' => $patientRef, 'display' => 'Patient'],
                'occurrenceDateTime' => $invoiceDate,
                'quantity' => ['value' => $quantity, 'unit' => $unit],
                'productCodeableConcept' => ['text' => $itemName],
            ];
            $chargeItemActor = $practitionerRef !== '' ? $practitionerRef : $organizationRef;
            $chargeItems[array_key_last($chargeItems)]['performer'] = [[
                'actor' => ['reference' => $chargeItemActor],
            ]];
            $lineItems[] = [
                'sequence' => $idx + 1,
                'chargeItemReference' => [
                    'reference' => $chargeItemRef,
                    'display' => $itemName,
                ],
                'priceComponent' => [[
                    'type' => 'base',
                    'code' => ['coding' => [[
                        'system' => 'https://nrces.in/ndhm/fhir/r4/CodeSystem/ndhm-price-components',
                        'code' => '01',
                        'display' => 'Rate',
                    ]]],
                    'amount' => [
                        'value' => $rate,
                        'currency' => 'INR',
                    ],
                ]],
            ];
        }

        $patient['meta'] = array_replace((array) ($patient['meta'] ?? []), [
            'versionId' => '1',
            'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient'],
        ]);
        $patient['text'] = [
            'status' => 'generated',
            'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>Patient: '
                . $escape((string) ($patient['name'][0]['text'] ?? $patientId)) . '</p></div>',
        ];
        foreach ((array) ($patient['identifier'] ?? []) as &$identifier) {
            $identifier['assigner'] = ['reference' => $organizationRef];
        }
        unset($identifier);

        $organization = [
            'resourceType' => 'Organization',
            'id' => $organizationId,
            'meta' => [
                'versionId' => '1',
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization'],
            ],
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>' . $escape($hospitalName) . '</p></div>',
            ],
            'identifier' => [[
                'system' => 'https://facility.abdm.gov.in',
                'value' => $hospitalIdentifier,
            ]],
            'active' => true,
            'name' => $hospitalName,
        ];

        $invoiceResource = [
            'resourceType' => 'Invoice',
            'id' => $invoiceId,
            'meta' => [
                'versionId' => '1',
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Invoice'],
            ],
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>Invoice '
                    . $escape($invoiceNumber) . ' for INR ' . $escape(number_format($netAmount, 2, '.', '')) . '</p></div>',
            ],
            'identifier' => [['system' => $identifierSystem, 'value' => $invoiceNumber]],
            'status' => 'issued',
            'type' => ['coding' => [[
                'system' => 'https://nrces.in/ndhm/fhir/r4/CodeSystem/ndhm-billing-codes',
                'code' => $invoiceTypeCode,
                'display' => $invoiceTypeDisplay,
            ]], 'text' => 'Healthcare invoice'],
            'subject' => ['reference' => $patientRef],
            'date' => $invoiceDate,
            'issuer' => ['reference' => $organizationRef],
            'totalPriceComponent' => [[
                'type' => 'base',
                'code' => ['coding' => [[
                    'system' => 'https://nrces.in/ndhm/fhir/r4/CodeSystem/ndhm-price-components',
                    'code' => '01',
                    'display' => 'Rate',
                ]]],
                'amount' => ['value' => $netAmount, 'currency' => 'INR'],
            ]],
            'totalNet' => ['value' => $netAmount, 'currency' => 'INR'],
            'totalGross' => ['value' => $grossAmount, 'currency' => 'INR'],
        ];
        if (! empty($lineItems)) {
            $invoiceResource['lineItem'] = $lineItems;
        }
        if ($practitionerRef !== '') {
            $invoiceResource['participant'] = [[
                'actor' => ['reference' => $practitionerRef, 'display' => $practitionerName],
            ]];
        }

        $patientName = trim((string) ($patient['name'][0]['text'] ?? $patientId)) ?: 'Patient';
        $invoiceRows = '';
        foreach ($items as $idx => $item) {
            $itemName = trim((string) ($item['item_name'] ?? '')) ?: 'Item';
            $quantity = (float) ($item['item_qty'] ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            $rate = (float) ($item['item_rate'] ?? $item['item_amount'] ?? 0);
            $amount = (float) ($item['item_amount'] ?? ($quantity * $rate));
            $invoiceRows .= '<tr><td>' . ($idx + 1) . '</td><td>' . $escape($itemName) . '</td><td class="num">'
                . $escape((string) $quantity) . '</td><td class="num">' . number_format($rate, 2) . '</td><td class="num">'
                . number_format($amount, 2) . '</td></tr>';
        }
        if ($invoiceRows === '') {
            $invoiceRows = '<tr><td colspan="5">No line items</td></tr>';
        }
        $invoicePdfHtml = '<table class="items"><thead><tr><th>#</th><th>Item</th><th class="num">Qty</th><th class="num">Rate (INR)</th>'
            . '<th class="num">Amount (INR)</th></tr></thead><tbody>' . $invoiceRows . '</tbody></table>'
            . '<div class="totals"><div>Gross: INR ' . number_format($grossAmount, 2) . '</div><div><strong>Net: INR '
            . number_format($netAmount, 2) . '</strong></div></div>';
        $patientUhid = '';
        $patientAbhaValues = [];
        foreach ((array) ($patient['identifier'] ?? []) as $identifier) {
            $system = (string) ($identifier['system'] ?? '');
            if (str_contains($system, '/uhid')) {
                $patientUhid = trim((string) ($identifier['value'] ?? ''));
            } elseif (str_contains($system, 'healthid.ndhm.gov.in')) {
                $value = trim((string) ($identifier['value'] ?? ''));
                if ($value !== '') {
                    $patientAbhaValues[] = $value;
                }
            }
        }
        $patientAbha = implode(' / ', array_values(array_unique($patientAbhaValues)));
        $invoicePdf = $this->renderDigitalSharePdf(
            $invoicePdfHtml,
            $pdfFileStem,
            [
                'document_title' => 'Invoice ' . $invoiceNumber,
                'patient_name' => $patientName,
                'uhid' => $patientUhid,
                'abha' => $patientAbha,
                'record_label' => $invoiceTypeDisplay . ' / Invoice',
                'record_code' => $invoiceNumber,
                'reported_at' => $invoiceDate,
                'practitioner' => $practitionerName,
            ]
        );
        $documentReference = null;
        $pdfBinary = null;
        if ($invoicePdf !== null) {
            $invoicePdfBytes = base64_decode((string) $invoicePdf['data_base64'], true);
            if (is_string($invoicePdfBytes) && str_starts_with($invoicePdfBytes, '%PDF-')) {
                $pdfBinary = [
                    'resourceType' => 'Binary',
                    'id' => $pdfBinaryId,
                    'contentType' => 'application/pdf',
                    'data' => (string) $invoicePdf['data_base64'],
                ];
                $documentReference = [
            'resourceType' => 'DocumentReference',
            'id' => $documentReferenceId,
            'meta' => [
                'versionId' => '1',
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentReference'],
            ],
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>PDF copy of invoice '
                    . $escape($invoiceNumber) . '</p></div>',
            ],
            'status' => 'current',
            'docStatus' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '725458005',
                    'display' => 'Receipt',
                ]],
                'text' => 'Invoice Record',
            ],
            'subject' => ['reference' => $patientRef, 'display' => $patientName],
            'date' => $issuedAt,
            'author' => [[
                'reference' => $practitionerRef !== '' ? $practitionerRef : $organizationRef,
                'display' => $practitionerRef !== '' ? $practitionerName : $hospitalName,
            ]],
            'custodian' => ['reference' => $organizationRef, 'display' => $hospitalName],
            'content' => [[
                'attachment' => [
                    'contentType' => 'application/pdf',
                    'language' => 'en-IN',
                    'data' => $invoicePdf['data_base64'],
                    'size' => strlen($invoicePdfBytes),
                    'hash' => base64_encode(sha1($invoicePdfBytes, true)),
                    'title' => $pdfFilename,
                    'creation' => $issuedAt,
                ],
            ]],
            'context' => [
                'encounter' => [['reference' => $encounterRef]],
                'related' => [['reference' => $invoiceRef, 'display' => 'Invoice ' . $invoiceNumber]],
            ],
                ];
            }
        }

        $encounter = [
            'resourceType' => 'Encounter',
            'id' => $encounterId,
            'meta' => [
                'versionId' => '1',
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Encounter'],
            ],
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>' . $escape($invoiceTypeDisplay)
                    . ' visit on ' . $escape($invoiceDate) . ' at ' . $escape($hospitalName) . '</p></div>',
            ],
            'identifier' => [['system' => $identifierSystem . '/encounter', 'value' => $invoiceNumber]],
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $encounterClass,
                'display' => $encounterClass === 'IMP' ? 'inpatient encounter' : 'ambulatory',
            ],
            'subject' => ['reference' => $patientRef, 'display' => (string) ($patient['name'][0]['text'] ?? 'Patient')],
            'period' => ['start' => $encounterStart, 'end' => $encounterEnd],
            'serviceProvider' => ['reference' => $organizationRef, 'display' => $hospitalName],
        ];
        if ($practitionerRef !== '') {
            $encounter['participant'] = [[
                'individual' => ['reference' => $practitionerRef, 'display' => $practitionerName],
            ]];
        }

        $practitioner = null;
        if ($practitionerRef !== '') {
            $practitioner = [
                'resourceType' => 'Practitioner',
                'id' => $practitionerId,
                'meta' => [
                    'versionId' => '1',
                    'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner'],
                ],
                'identifier' => [[
                    'type' => [
                        'coding' => [[
                            'system' => 'http://terminology.hl7.org/CodeSystem/v2-0203',
                            'code' => 'MD',
                            'display' => 'Medical License number',
                        ]],
                    ],
                    'system' => $identifierSystem . '/practitioner',
                    'value' => $practitionerSourceId > 0 ? (string) $practitionerSourceId : $practitionerId,
                ]],
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>' . $escape($practitionerName) . '</p></div>',
                ],
                'active' => true,
                'name' => [['text' => $practitionerName]],
            ];
        }

        $composition = [
            'resourceType' => 'Composition',
            'id' => $compositionId,
            'meta' => [
                'versionId' => '1',
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/InvoiceRecord'],
            ],
            'text' => [
                'status' => 'generated',
                'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>Invoice Record '
                    . $escape($invoiceNumber) . '</p></div>',
            ],
            'identifier' => ['system' => $identifierSystem, 'value' => $invoiceNumber],
            'status' => 'final',
            'type' => [
                'coding' => [[
                    'system' => 'http://loinc.org',
                    'code' => '34775-2',
                    'display' => 'Hospital Invoice',
                ]],
                'text' => 'Invoice Record',
            ],
            'subject' => ['reference' => 'Patient/' . $patientId],
            'encounter' => ['reference' => $encounterRef],
            'date' => $issuedAt,
            'author' => [[
                'reference' => 'Organization/' . $organizationId,
                'display' => $hospitalName,
            ]],
            'title' => 'Invoice ' . $invoiceNumber,
            'custodian' => ['reference' => 'Organization/' . $organizationId],
            'section' => [[
                'title' => 'Invoice details',
                'text' => [
                    'status' => 'generated',
                    'div' => '<div xmlns="http://www.w3.org/1999/xhtml"><p>Billing details for invoice '
                        . $escape($invoiceNumber) . '</p></div>',
                ],
                'entry' => [['reference' => $invoiceRef, 'type' => 'Invoice']],
            ]],
        ];
        if ($documentReference !== null) {
            $composition['section'][] = [
                'title' => 'Document Reference',
                'code' => ['coding' => [[
                    'system' => 'http://snomed.info/sct',
                    'code' => '725458005',
                    'display' => 'Receipt',
                ]]],
                'entry' => [[
                    'reference' => $documentReferenceRef,
                    'type' => 'DocumentReference',
                ]],
            ];
        }
        if ($pdfBinary !== null) {
            $composition['section'][] = [
                'title' => 'Invoice PDF',
                'entry' => [[
                    'reference' => 'Binary/' . $pdfBinaryId,
                    'type' => 'Binary',
                ]],
            ];
        }

        $resources = [$composition, $patient, $organization, $encounter, $invoiceResource];
        if ($documentReference !== null) {
            $resources[] = $documentReference;
        }
        if ($pdfBinary !== null) {
            $resources[] = $pdfBinary;
        }
        if ($practitioner !== null) {
            $resources[] = $practitioner;
        }
        $resources = array_merge($resources, $chargeItems);
        $referenceMap = [];
        foreach ($resources as $resource) {
            $id = (string) ($resource['id'] ?? '');
            $hex = md5((string) ($resource['resourceType'] ?? 'Resource') . '/' . $id);
            $uuid = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-4' . substr($hex, 13, 3)
                . '-a' . substr($hex, 17, 3) . '-' . substr($hex, 20, 12);
            $referenceMap['urn:uuid:' . $id] = 'urn:uuid:' . $uuid;
        }
        $rewriteReferences = static function ($value) use (&$rewriteReferences, $referenceMap) {
            if (is_string($value)) {
                return $referenceMap[$value] ?? $value;
            }
            if (! is_array($value)) {
                return $value;
            }
            foreach ($value as $key => $nested) {
                $value[$key] = $rewriteReferences($nested);
            }
            return $value;
        };
        $entries = [];
        foreach ($resources as $resource) {
            $entries[] = [
                'fullUrl' => $referenceMap['urn:uuid:' . (string) $resource['id']],
                'resource' => $rewriteReferences($resource),
            ];
        }

        return [
            'resourceType' => 'Bundle',
            'id' => 'bundle-' . $invoiceId,
            'meta' => [
                'versionId' => '1',
                'lastUpdated' => $issuedAt,
                'profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
            ],
            'identifier' => ['system' => $identifierSystem, 'value' => $invoiceNumber],
            'type' => 'document',
            'timestamp' => $issuedAt,
            'entry' => $entries,
        ];
    }

    private function resolveReadableAttachmentPath(string $path): string
    {
        $candidate = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (! str_starts_with($candidate, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $candidate)) {
            $candidate = FCPATH . ltrim($candidate, DIRECTORY_SEPARATOR);
        }
        $real = realpath($candidate);
        if ($real === false || ! is_file($real) || ! is_readable($real)) {
            return '';
        }
        $allowed = array_filter([realpath(FCPATH), realpath(WRITEPATH)]);
        foreach ($allowed as $root) {
            if (str_starts_with($real, (string) $root)) {
                return $real;
            }
        }
        return '';
    }

    private function getActiveConsentRecord(int $patientId, string $abhaId, string $consentHandle = ''): ?array
    {
        $hasAbdmConsentTable = $this->db->tableExists('abdm_consent_records');
        $hasConsentAliasTable = $this->db->tableExists('consents');
        if (! $hasAbdmConsentTable && ! $hasConsentAliasTable) {
            return null;
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        if ($hasAbdmConsentTable) {
            $consentFields = $this->db->getFieldNames('abdm_consent_records') ?? [];

            $builder = $this->db->table('abdm_consent_records')
                ->where('patient_id', $patientId)
                ->where('abha_id', $abhaId)
                ->whereIn('consent_status', ['approved', 'granted'])
                ->groupStart()
                    ->where('expires_at IS NULL', null, false)
                    ->orWhere('expires_at >=', $now)
                ->groupEnd();

            if ($consentHandle !== '') {
                $builder->groupStart()->where('consent_handle', $consentHandle);
                if (in_array('consent_id', $consentFields, true)) {
                    $builder->orWhere('consent_id', $consentHandle);
                }
                if (in_array('gateway_consent_id', $consentFields, true)) {
                    $builder->orWhere('gateway_consent_id', $consentHandle);
                }
                $builder->groupEnd();
            }

            $row = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (! empty($row)) {
                return $row;
            }
        }

        if ($hasConsentAliasTable) {
            $consentFields = $this->db->getFieldNames('consents') ?? [];
            $builder = $this->db->table('consents');
            if (in_array('patient_id', $consentFields, true)) {
                $builder->where('patient_id', $patientId);
            }
            if ($consentHandle !== '' && in_array('token', $consentFields, true)) {
                $builder->where('token', $consentHandle);
            }
            if (in_array('expiry', $consentFields, true)) {
                $builder->groupStart()
                    ->where('expiry IS NULL', null, false)
                    ->orWhere('expiry >=', $now)
                ->groupEnd();
            }

            $row = $builder->orderBy(in_array('consent_id', $consentFields, true) ? 'consent_id' : 'patient_id', 'DESC')->get(1)->getRowArray();
            if (! empty($row)) {
                return [
                    'patient_id' => (int) ($row['patient_id'] ?? $patientId),
                    'abha_id' => $abhaId,
                    'consent_handle' => (string) ($row['token'] ?? $consentHandle),
                    'consent_status' => 'approved',
                    'expires_at' => (string) ($row['expiry'] ?? ''),
                    'purpose_code' => (string) ($row['scope'] ?? 'TREATMENT'),
                ];
            }
        }

        return null;
    }

    private function logMissingConsentShareBlock(string $entityType, int $entityId, int $patientId, string $abhaId, string $consentHandle, string $eventType): void
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        $message = 'No active consent found. Share blocked due to expiry/not-approved consent.';
        $this->db->table('abdm_api_logs')->insert([
            'channel' => 'bridge',
            'event_type' => $eventType,
            'endpoint' => '/AbdmGateway/share_' . $entityType,
            'http_method' => 'POST',
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'request_json' => (string) json_encode([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'patient_id' => $patientId,
                'abha_id' => $abhaId,
                'consent_handle' => $consentHandle,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => 200,
            'response_json' => (string) json_encode([
                'ok' => 0,
                'error_text' => $message,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'error',
            'error_message' => $message,
            'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ]);
    }

    private function logMissingConsentShareWarning(string $entityType, int $entityId, int $patientId, string $abhaId, string $consentHandle, string $eventType, string $message): void
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        $this->db->table('abdm_api_logs')->insert([
            'channel' => 'bridge',
            'event_type' => $eventType,
            'endpoint' => '/AbdmGateway/share_' . $entityType,
            'http_method' => 'POST',
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'request_json' => json_encode([
                'patient_id' => $patientId,
                'abha_id' => $abhaId,
                'consent_handle' => $consentHandle,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_json' => json_encode([
                'ok' => 1,
                'note' => $message,
                'care_context_push_only' => 1,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => 200,
            'status' => 'warning',
            'error_message' => $message,
            'created_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ]);
    }

    /**
     * Pick the consent id expected by gateway: consent_id/gateway_consent_id fallback to consent_handle.
     *
     * @param array<string, mixed> $consent
     */
    private function resolveConsentExternalId(array $consent): string
    {
        $consentId = trim((string) ($consent['consent_id'] ?? ''));
        if ($consentId !== '') {
            return $consentId;
        }

        $gatewayConsentId = trim((string) ($consent['gateway_consent_id'] ?? ''));
        if ($gatewayConsentId !== '') {
            return $gatewayConsentId;
        }

        return trim((string) ($consent['consent_handle'] ?? ''));
    }

    // =========================================================================
    // M1 ABHA OTP Flows
    // Calls EAtriaBridgeConnector synchronously — requires eatria_bridge connector.
    // =========================================================================

    /**
     * POST /abdm/abha/aadhaar/generate-otp
     * Initiates ABHA creation/linking flow using Aadhaar OTP.
     * Body: { aadhaar: "123456789012" }
     */
    public function abhaAadhaarGenerateOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body    = $this->request->getJSON(true) ?? [];
        // Accept loginId (API native) or aadhaar (HMS form shorthand)
        $loginId = trim((string) ($body['loginId'] ?? $body['aadhaar'] ?? $this->request->getPost('loginId') ?? $this->request->getPost('aadhaar') ?? ''));

        if ($loginId === '' || ! preg_match('/^\d{12}$/', $loginId)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 12-digit Aadhaar number is required']);
        }

        try {
            $result = $this->connector->abhaAadhaarGenerateOtp(['aadhaar' => $loginId]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
    }

    /**
     * POST /abdm/abha/aadhaar/verify-otp
     * Verifies Aadhaar OTP and returns ABHA profile.
     * Body: { txn_id: "...", otp: "123456" }
     */
    public function abhaAadhaarVerifyOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body  = $this->request->getJSON(true) ?? [];
        // Accept txnId (API native) or txn_id (HMS form shorthand)
        $txnId = trim((string) ($body['txnId'] ?? $body['txn_id'] ?? $this->request->getPost('txnId') ?? $this->request->getPost('txn_id') ?? ''));
        $otp   = trim((string) ($body['otp']   ?? $this->request->getPost('otp') ?? ''));
        $requestedPid = (int) ($body['p_id'] ?? $this->request->getPost('p_id') ?? 0);

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
        }

        try {
            $result = $this->connector->abhaAadhaarVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (! empty($result['ok']) && (int) $result['ok'] === 1) {
            $result = $this->enrichGatewayOtpVerifyResult($result, $requestedPid);
        }

        return $this->response->setJSON($this->sanitizeGatewayOtpVerifyResponse($result));
    }

    /**
     * POST /abdm/abha/mobile/generate-otp
     * Sends OTP to mobile for ABHA linking.
     * Body: { mobile: "9876543210" }
     */
    public function abhaMobileGenerateOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body    = $this->request->getJSON(true) ?? [];
        // Accept loginId (API native) or mobile (HMS form shorthand)
        $loginId = trim((string) ($body['loginId'] ?? $body['mobile'] ?? $this->request->getPost('loginId') ?? $this->request->getPost('mobile') ?? ''));

        if ($loginId === '' || ! preg_match('/^\d{10}$/', $loginId)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 10-digit mobile number is required']);
        }

        try {
            $result = $this->connector->abhaMobileGenerateOtp(['mobile' => $loginId]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
    }

    /**
     * POST /abdm/abha/mobile/verify-otp
     * Verifies mobile OTP and returns ABHA profile.
     * Body: { txn_id: "...", otp: "123456" }
     */
    public function abhaMobileVerifyOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body  = $this->request->getJSON(true) ?? [];
        // Accept txnId (API native) or txn_id (HMS form shorthand)
        $txnId = trim((string) ($body['txnId'] ?? $body['txn_id'] ?? $this->request->getPost('txnId') ?? $this->request->getPost('txn_id') ?? ''));
        $otp   = trim((string) ($body['otp']   ?? $this->request->getPost('otp') ?? ''));
        $requestedPid = (int) ($body['p_id'] ?? $this->request->getPost('p_id') ?? 0);

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
        }

        try {
            $result = $this->connector->abhaMobileVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (! empty($result['ok']) && (int) $result['ok'] === 1) {
            $result = $this->enrichGatewayOtpVerifyResult($result, $requestedPid);
        }

        return $this->response->setJSON($this->sanitizeGatewayOtpVerifyResponse($result));
    }

    /**
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function enrichGatewayOtpVerifyResult(array $result, int $requestedPid): array
    {
        $payload = [];
        if (isset($result['data']) && is_array($result['data'])) {
            $payload = $result['data'];
        } elseif (is_array($result)) {
            $payload = $result;
        }

        $profile = $this->pickGatewayOtpProfile($payload);
        $gatewayPatientPayload = is_array($payload['gateway_patient'] ?? null) ? $payload['gateway_patient'] : [];

        $abhaRaw = trim((string) (
            $profile['ABHANumber']
            ?? $profile['abha_number']
            ?? $profile['abha_id']
            ?? $payload['ABHANumber']
            ?? $payload['abha_number']
            ?? $payload['abha_id']
            ?? ''
        ));
        $abhaDigits = preg_replace('/\D/', '', $abhaRaw);
        $abhaAddress = trim((string) (
            $profile['preferredAddress']
            ?? $profile['preferredAbhaAddress']
            ?? $profile['abha_address']
            ?? $payload['preferredAddress']
            ?? $payload['preferredAbhaAddress']
            ?? $payload['abha_address']
            ?? ''
        ));
        $gatewayMobile = preg_replace('/\D/', '', (string) (
            $profile['mobile']
            ?? $payload['mobile']
            ?? $payload['mobileNumber']
            ?? ($gatewayPatientPayload['mobile'] ?? '')
        ));

        $gatewayAddress = trim((string) (
            ($profile['address'] ?? '')
            ?: ($profile['address_line'] ?? '')
            ?: ($payload['address'] ?? '')
            ?: ($payload['address_line'] ?? '')
            ?: ($payload['gateway_abha_profile']['address'] ?? '')
            ?: ($gatewayPatientPayload['address_line'] ?? '')
            ?: ''
        ));
        $gatewayCity = trim((string) (
            ($profile['city'] ?? '')
            ?: ($payload['city'] ?? '')
            ?: ($payload['gateway_abha_profile']['city'] ?? '')
            ?: ($gatewayPatientPayload['city'] ?? '')
            ?: ''
        ));
        $gatewayDistrict = trim((string) (
            ($profile['districtName'] ?? '')
            ?: ($profile['district_name'] ?? '')
            ?: ($payload['districtName'] ?? '')
            ?: ($payload['district_name'] ?? '')
            ?: ($payload['gateway_abha_profile']['district_name'] ?? '')
            ?: ($gatewayPatientPayload['district'] ?? '')
            ?: ''
        ));
        $gatewayState = trim((string) (
            ($profile['stateName'] ?? '')
            ?: ($profile['state_name'] ?? '')
            ?: ($payload['stateName'] ?? '')
            ?: ($payload['state_name'] ?? '')
            ?: ($payload['gateway_abha_profile']['state_name'] ?? '')
            ?: ($gatewayPatientPayload['state_name'] ?? '')
            ?: ''
        ));
        $gatewayZip = trim((string) (
            ($profile['pinCode'] ?? '')
            ?: ($profile['pin_code'] ?? '')
            ?: ($payload['pinCode'] ?? '')
            ?: ($payload['pin_code'] ?? '')
            ?: ($payload['pincode'] ?? '')
            ?: ($payload['gateway_abha_profile']['pin_code'] ?? '')
            ?: ($gatewayPatientPayload['pincode'] ?? '')
            ?: ''
        ));
        $gatewayEmail = trim((string) (
            ($profile['email'] ?? '')
            ?: ($payload['email'] ?? '')
            ?: ($payload['gateway_abha_profile']['email'] ?? '')
            ?: ($gatewayPatientPayload['email'] ?? '')
            ?: ''
        ));

        $verifiedStatus = trim((string) (
            ($payload['gateway_abha_profile']['status'] ?? '')
            ?: ($profile['verifiedStatus'] ?? '')
            ?: ($profile['status'] ?? '')
            ?: ($payload['verifiedStatus'] ?? '')
            ?: ($payload['status'] ?? '')
            ?: ''
        ));
        $verificationType = trim((string) (
            ($payload['gateway_abha_profile']['abha_type'] ?? '')
            ?: ($profile['verificationType'] ?? '')
            ?: ($profile['abhaType'] ?? '')
            ?: ($payload['verificationType'] ?? '')
            ?: ($payload['abhaType'] ?? '')
            ?: ''
        ));
        $kycVerified = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        if ($kycVerified === null) {
            $kycVerified = strtolower(trim((string) ($payload['gateway_abha_profile']['status'] ?? ''))) === 'verified' ? 1 : 0;
        }
        $mobileVerified = $profile['mobileVerified']
            ?? $payload['mobileVerified']
            ?? ($payload['gateway_abha_profile']['mobile_verified'] ?? null);

        $patientId = $this->resolveGatewayOtpPatientId($requestedPid, $payload, $abhaDigits, $gatewayMobile);
        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return $result;
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $updates = [];

        $abhaField = $this->resolveFirstExistingColumn($fields, ['abha_id', 'abha_no', 'abha']);
        if ($abhaField !== null && $abhaDigits !== '') {
            $updates[$abhaField] = $abhaDigits;
        }
        if ($abhaAddress !== '' && in_array('abha_address', $fields, true)) {
            $updates['abha_address'] = $abhaAddress;
        }

        if ($gatewayMobile !== '' && in_array('mphone1', $fields, true)) {
            $updates['mphone1'] = $gatewayMobile;
        }
        if ($gatewayAddress !== '' && in_array('add1', $fields, true)) {
            $updates['add1'] = $gatewayAddress;
        }
        if ($gatewayCity !== '' && in_array('city', $fields, true)) {
            $updates['city'] = $gatewayCity;
        }
        if ($gatewayDistrict !== '' && in_array('district', $fields, true)) {
            $updates['district'] = $gatewayDistrict;
        }
        if ($gatewayState !== '' && in_array('state', $fields, true)) {
            $updates['state'] = $gatewayState;
        }
        if ($gatewayZip !== '' && in_array('zip', $fields, true)) {
            $updates['zip'] = $gatewayZip;
        }
        if ($gatewayEmail !== '' && in_array('email1', $fields, true)) {
            $updates['email1'] = $gatewayEmail;
        }

        if ($verifiedStatus !== '' && in_array('abha_verified_status', $fields, true)) {
            $updates['abha_verified_status'] = strtoupper($verifiedStatus);
        }
        if ($verificationType !== '' && in_array('abha_verification_type', $fields, true)) {
            $updates['abha_verification_type'] = strtoupper($verificationType);
        }
        if ($kycVerified !== null && in_array('abha_kyc_verified', $fields, true)) {
            $updates['abha_kyc_verified'] = $this->toDbBool($kycVerified);
        }
        if ($mobileVerified !== null && in_array('abha_mobile_verified', $fields, true)) {
            $updates['abha_mobile_verified'] = $this->toDbBool($mobileVerified);
        }

        if (in_array('abdm_linked_at', $fields, true) && ($abhaDigits !== '' || $abhaAddress !== '')) {
            $updates['abdm_linked_at'] = date('Y-m-d H:i:s');
        }

        if ($updates !== []) {
            $this->db->table('patient_master')->where('id', $patientId)->update($updates);
        }

        $result['patient_id'] = $patientId;
        if (isset($result['data']) && is_array($result['data'])) {
            $result['data']['patient_id'] = $patientId;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveGatewayOtpPatientId(int $requestedPid, array $payload, string $abhaDigits, string $mobile): int
    {
        if (! $this->db->tableExists('patient_master')) {
            return 0;
        }

        if ($requestedPid > 0) {
            $row = $this->db->table('patient_master')->select('id')->where('id', $requestedPid)->get(1)->getRowArray();
            if ($row !== null) {
                return (int) ($row['id'] ?? 0);
            }
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveFirstExistingColumn($fields, ['abha_id', 'abha_no', 'abha']);
        if ($abhaField !== null && $abhaDigits !== '') {
            $row = $this->db->table('patient_master')->select('id')->where($abhaField, $abhaDigits)->get(1)->getRowArray();
            if ($row !== null) {
                return (int) ($row['id'] ?? 0);
            }
        }

        if ($mobile !== '' && in_array('mphone1', $fields, true)) {
            $row = $this->db->table('patient_master')->select('id')->where('mphone1', $mobile)->get(1)->getRowArray();
            if ($row !== null) {
                return (int) ($row['id'] ?? 0);
            }
        }

        $fallbackIds = [
            (int) ($payload['patient_id'] ?? 0),
            (int) ($payload['gateway_patient']['id'] ?? 0),
        ];
        foreach ($fallbackIds as $pid) {
            if ($pid <= 0) {
                continue;
            }
            $row = $this->db->table('patient_master')->select('id')->where('id', $pid)->get(1)->getRowArray();
            if ($row !== null) {
                return (int) ($row['id'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function pickGatewayOtpProfile(array $payload): array
    {
        $candidates = [
            $payload['ABHAProfile'] ?? null,
            $payload['gateway_abha_profile'] ?? null,
            $payload['profile'] ?? null,
            $payload['accounts'][0] ?? null,
            $payload['gateway_patient'] ?? null,
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (! is_array($candidate)) {
                continue;
            }

            foreach (['ABHANumber', 'abha_number', 'abha_id', 'preferredAddress', 'preferredAbhaAddress', 'abha_address', 'fullName', 'full_name', 'firstName', 'dob', 'date_of_birth'] as $key) {
                if (array_key_exists($key, $candidate) && trim((string) $candidate[$key]) !== '') {
                    return $candidate;
                }
            }
        }

        return [];
    }

    private function toDbBool($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $v = strtolower(trim((string) $value));
        if ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'y') {
            return 1;
        }

        return 0;
    }

    /**
     * Return only safe/required fields for gateway ABHA OTP verification APIs.
     *
     * @param array<string, mixed> $result
     * @return array<string, mixed>
     */
    private function sanitizeGatewayOtpVerifyResponse(array $result): array
    {
        $ok = ! empty($result['ok']) && (int) $result['ok'] === 1;
        if (! $ok) {
            return [
                'ok' => 0,
                'error_text' => (string) ($result['error_text'] ?? $result['message'] ?? $result['data']['message'] ?? 'OTP verification failed'),
                'request_id' => (string) ($result['request_id'] ?? ''),
            ];
        }

        $payload = [];
        if (isset($result['data']) && is_array($result['data'])) {
            $payload = $result['data'];
        } elseif (is_array($result)) {
            $payload = $result;
        }

        $profile = $this->pickGatewayOtpProfile($payload);
        $gatewayPatientPayload = is_array($payload['gateway_patient'] ?? null) ? $payload['gateway_patient'] : [];

        $txnId = (string) ($payload['txnId'] ?? $payload['txn_id'] ?? $result['txnId'] ?? $result['txn_id'] ?? '');
        $abhaRaw = (string) ($payload['ABHANumber'] ?? $payload['abha_number'] ?? $payload['abha_id'] ?? $profile['ABHANumber'] ?? $profile['abha_number'] ?? $profile['abha_id'] ?? '');
        $abhaDigits = preg_replace('/\D/', '', $abhaRaw);
        $abhaAddress = (string) ($payload['preferredAbhaAddress'] ?? $payload['abha_address'] ?? $profile['preferredAbhaAddress'] ?? $profile['abha_address'] ?? $profile['preferredAddress'] ?? '');
        $name = trim((string) (
            $payload['name']
            ?? $payload['full_name']
            ?? $profile['name']
            ?? $profile['fullName']
            ?? $profile['full_name']
            ?? trim(implode(' ', array_filter([
                (string) ($profile['firstName'] ?? ''),
                (string) ($profile['middleName'] ?? ''),
                (string) ($profile['lastName'] ?? ''),
            ])))
        ));
        $mobile = (string) ($payload['mobile'] ?? $payload['mobileNumber'] ?? $profile['mobile'] ?? ($gatewayPatientPayload['mobile'] ?? ''));
        $gender = (string) ($payload['gender'] ?? $profile['gender'] ?? ($gatewayPatientPayload['gender'] ?? ''));
        $dob = (string) ($payload['dob'] ?? $payload['date_of_birth'] ?? $profile['dob'] ?? $profile['date_of_birth'] ?? ($gatewayPatientPayload['date_of_birth'] ?? ''));
        $address = (string) (
            ($payload['address'] ?? '')
            ?: ($payload['address_line'] ?? '')
            ?: ($profile['address'] ?? '')
            ?: ($profile['address_line'] ?? '')
            ?: ($payload['gateway_abha_profile']['address'] ?? '')
            ?: ($gatewayPatientPayload['address_line'] ?? '')
            ?: ''
        );
        $pinCode = (string) (
            ($payload['pinCode'] ?? '')
            ?: ($payload['pin_code'] ?? '')
            ?: ($payload['pincode'] ?? '')
            ?: ($profile['pinCode'] ?? '')
            ?: ($profile['pin_code'] ?? '')
            ?: ($payload['gateway_abha_profile']['pin_code'] ?? '')
            ?: ($gatewayPatientPayload['pincode'] ?? '')
            ?: ''
        );
        $stateCode = (string) (
            ($payload['stateCode'] ?? '')
            ?: ($payload['state_code'] ?? '')
            ?: ($profile['stateCode'] ?? '')
            ?: ($profile['state_code'] ?? '')
            ?: ($payload['gateway_abha_profile']['state_code'] ?? '')
            ?: ($gatewayPatientPayload['state_code'] ?? '')
            ?: ''
        );
        $stateName = (string) (
            ($payload['stateName'] ?? '')
            ?: ($payload['state_name'] ?? '')
            ?: ($profile['stateName'] ?? '')
            ?: ($profile['state_name'] ?? '')
            ?: ($payload['gateway_abha_profile']['state_name'] ?? '')
            ?: ($gatewayPatientPayload['state_name'] ?? '')
            ?: ''
        );
        $districtCode = (string) (
            ($payload['districtCode'] ?? '')
            ?: ($payload['district_code'] ?? '')
            ?: ($profile['districtCode'] ?? '')
            ?: ($profile['district_code'] ?? '')
            ?: ($payload['gateway_abha_profile']['district_code'] ?? '')
            ?: ''
        );
        $districtName = (string) (
            ($payload['districtName'] ?? '')
            ?: ($payload['district_name'] ?? '')
            ?: ($profile['districtName'] ?? '')
            ?: ($profile['district_name'] ?? '')
            ?: ($payload['gateway_abha_profile']['district_name'] ?? '')
            ?: ($gatewayPatientPayload['district'] ?? '')
            ?: ''
        );

        $matchedPatientId = (int) ($result['patient_id'] ?? $payload['patient_id'] ?? 0);

        return [
            'ok' => 1,
            'request_id' => (string) ($result['request_id'] ?? ''),
            'message' => (string) ($payload['message'] ?? $result['message'] ?? 'ABHA OTP verified successfully'),
            'txn_id' => $txnId,
            'abha_number' => $abhaDigits,
            'abha_address' => $abhaAddress,
            'name' => $name,
            'mobile' => $mobile,
            'gender' => $gender,
            'dob' => $dob,
            'address' => $address,
            'pin_code' => $pinCode,
            'state_code' => $stateCode,
            'state_name' => $stateName,
            'district_code' => $districtCode,
            'district_name' => $districtName,
            'patient_id' => $matchedPatientId > 0 ? $matchedPatientId : null,
            'data' => [
                'txnId' => $txnId,
                'abha_number' => $abhaDigits,
                'name' => $name,
                'mobile' => $mobile,
                'gender' => $gender,
                'dob' => $dob,
                'address' => $address,
                'pin_code' => $pinCode,
                'state_code' => $stateCode,
                'state_name' => $stateName,
                'district_code' => $districtCode,
                'district_name' => $districtName,
                'patient_id' => $matchedPatientId > 0 ? $matchedPatientId : null,
                'profile' => [
                    'ABHANumber' => $abhaDigits,
                    'abha_number' => $abhaDigits,
                    'abha_id' => $abhaDigits,
                    'preferredAbhaAddress' => $abhaAddress,
                    'preferredAddress' => $abhaAddress,
                    'name' => $name,
                    'full_name' => $name,
                    'gender' => $gender,
                    'dob' => $dob,
                    'mobile' => $mobile,
                    'address' => $address,
                    'pinCode' => $pinCode,
                    'stateCode' => $stateCode,
                    'stateName' => $stateName,
                    'districtCode' => $districtCode,
                    'districtName' => $districtName,
                    'firstName' => (string) ($profile['firstName'] ?? ''),
                    'middleName' => (string) ($profile['middleName'] ?? ''),
                    'lastName' => (string) ($profile['lastName'] ?? ''),
                ],
            ],
        ];
    }

    // =========================================================================
    // Gateway status — hospital info + ABDM connectivity
    // GET /AbdmGateway/gateway_status
    // =========================================================================

    public function gatewayStatus()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $result = $this->connector->gatewayStatus();
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // Bridge records — list stored records from bridge
    // GET /AbdmGateway/bridge_records_list
    // =========================================================================

    public function bridgeRecordsList()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $filters = array_filter([
            'abha_id'      => $this->request->getGet('abha_id'),
            'abha_address' => $this->request->getGet('abha_address'),
            'status'       => $this->request->getGet('status'),
            'record_type'  => $this->request->getGet('record_type'),
            'page'         => $this->request->getGet('page'),
            'per_page'     => $this->request->getGet('per_page'),
        ]);

        $result = $this->connector->getRecords($filters);
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // HIP-Initiated Linking — Step 1: request link token
    // POST /AbdmGateway/hip_link_token
    // Body: { abha_address, name, gender, year_of_birth [, abha_number] }
    // =========================================================================

    public function hipLinkToken()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body = $this->request->getJSON(true) ?? [];

        $required = ['abha_address', 'name', 'gender', 'year_of_birth'];
        foreach ($required as $key) {
            if (empty($body[$key])) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => $key . ' is required']);
            }
        }

        $result = $this->connector->hipLinkToken($body);
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // HIP-Initiated Linking — Step 2: link care contexts
    // POST /AbdmGateway/hip_link_carecontext
    // Body: { abha_address, link_token_id, patient_ref, display, hi_type, care_contexts[] }
    // =========================================================================

    public function hipLinkCareContext()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body = $this->request->getJSON(true) ?? [];

        $required = ['abha_address', 'link_token_id', 'patient_ref', 'display', 'hi_type', 'care_contexts'];
        foreach ($required as $key) {
            if (empty($body[$key])) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => $key . ' is required']);
            }
        }

        $result = $this->connector->hipLinkCareContext($body);
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // HIP-Initiated Linking — fetch all linked care contexts for a patient
    // GET /AbdmGateway/hip_patient_links?abha_address=xxx&limit=20
    // =========================================================================

    public function hipPatientLinks()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $filters = array_filter([
            'abha_address' => $this->request->getGet('abha_address'),
            'limit'        => $this->request->getGet('limit'),
        ]);

        if (empty($filters['abha_address'])) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'abha_address is required']);
        }

        $result = $this->connector->hipGetPatientLinks($filters);
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // HIP-Initiated Linking — notify ABDM of care-context update
    // POST /AbdmGateway/hip_link_notify
    // Body: { abha_address, care_context_reference, hi_type, date_of_record }
    // =========================================================================

    public function hipLinkNotify()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body = $this->request->getJSON(true) ?? [];

        $required = ['abha_address', 'care_context_reference', 'hi_type', 'date_of_record'];
        foreach ($required as $key) {
            if (empty($body[$key])) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => $key . ' is required']);
            }
        }

        $result = $this->connector->hipLinkNotify($body);
        return $this->response->setJSON($result);
    }

    // =========================================================================
    // HIP-Initiated Linking — send ABDM deep-link SMS to patient
    // POST /AbdmGateway/hip_sms_notify
    // Body: { phone_number [, hip_name] }
    // =========================================================================

    public function hipSmsNotify()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $body = $this->request->getJSON(true) ?? [];

        if (empty($body['phone_number'])) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'phone_number is required']);
        }

        $result = $this->connector->hipSmsNotify($body);
        return $this->response->setJSON($result);
    }
}

