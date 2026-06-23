<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorInterface;
use App\Libraries\Abdm\AbdmConnectorFactory;
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

        return $this->response->setJSON([
            'ok' => 1,
            'consent_handle' => $handle,
            'status' => $incomingStatus,
            'gateway_consent_id' => $incomingConsentId !== '' ? $incomingConsentId : null,
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

        if ($abhaId === '' && $abhaAddressPost !== '') {
            $abhaId = $abhaAddressPost;
        }

        if ($opdId <= 0 || $patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_id and patient_id are required']);
        }

        if (! $this->db->tableExists('opd_fhir_documents')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_fhir_documents table not found']);
        }

        $hasAbha = $abhaId !== '' || $abhaAddressPost !== '';
        $consent = null;
        if ($hasAbha) {
            $consent = $this->getActiveConsentRecord($patientId, $abhaId !== '' ? $abhaId : $abhaAddressPost, $consentHandle);
            if ($consent === null) {
                return $this->response->setJSON([
                    'ok' => 0,
                    'error_text' => 'No active consent found. Share blocked due to expiry/not-approved consent.',
                ]);
            }
        }

        $builder = $this->db->table('opd_fhir_documents')
            ->where('opd_id', $opdId)
            ->whereIn('bundle_type', ['OPConsultRecord', 'MedicationRequestBundle']);
        if ($sessionId > 0) {
            $builder->where('opd_session_id', $sessionId);
        }
        $bundleRow = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();

        if (empty($bundleRow)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'No FHIR bundle found for selected OPD/session']);
        }

        $bundleJson = (string) ($bundleRow['bundle_json'] ?? '{}');
        $bundle = json_decode($bundleJson, true);
        if (! is_array($bundle)) {
            $bundle = ['raw' => $bundleJson];
        }

        // Resolve ABHA identifiers from request + patient profile.
        $abhaAddress = str_contains($abhaId, '@') ? $abhaId : '';
        $abhaNumber = '';
        if ($abhaAddressPost !== '' && str_contains($abhaAddressPost, '@')) {
            $abhaAddress = $abhaAddressPost;
        }
        if ($abhaAddress === '') {
            $digits = preg_replace('/\D/', '', $abhaId);
            if (strlen((string) $digits) === 14) {
                $abhaNumber = (string) $digits;
            }
        }

        $patientName = '';
        if ($this->db->tableExists('patient_master')) {
            $pmFields = $this->db->getFieldNames('patient_master') ?? [];
            $pmSelect = ['id'];
            foreach (['p_fname', 'p_lname', 'abha_address', 'abha_id', 'abha_no', 'abha'] as $f) {
                if (in_array($f, $pmFields, true)) {
                    $pmSelect[] = $f;
                }
            }

            $patientRow = $this->db->table('patient_master')
                ->select(implode(',', array_unique($pmSelect)))
                ->where('id', $patientId)
                ->get(1)
                ->getRowArray() ?? [];

            $patientName = trim(
                trim((string) ($patientRow['p_fname'] ?? '')) . ' ' .
                trim((string) ($patientRow['p_lname'] ?? ''))
            );

            if ($abhaAddress === '') {
                foreach (['abha_address', 'abha', 'abha_id', 'abha_no'] as $field) {
                    $value = trim((string) ($patientRow[$field] ?? ''));
                    if ($value !== '' && str_contains($value, '@')) {
                        $abhaAddress = $value;
                        break;
                    }
                }
            }

            if ($abhaNumber === '') {
                foreach (['abha_id', 'abha_no', 'abha'] as $field) {
                    $value = trim((string) ($patientRow[$field] ?? ''));
                    $digits = preg_replace('/\D/', '', $value);
                    if (strlen((string) $digits) === 14) {
                        $abhaNumber = (string) $digits;
                        break;
                    }
                }
            }
        }

        if ($abhaAddress === '' && $abhaNumber === '') {
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
        $sessionForRef = $sessionId > 0 ? $sessionId : (int) ($bundleRow['opd_session_id'] ?? 0);
        $visitDateRaw = trim((string) ($bundleRow['created_at'] ?? ''));
        $visitDate = $visitDateRaw !== '' ? date('Y-m-d', strtotime($visitDateRaw)) : date('Y-m-d');
        $careContextRef = trim((string) ($this->request->getPost('careContextId') ?? $this->request->getPost('care_context_reference') ?? ''));
        if ($careContextRef === '') {
            $careContextRef = 'OPD-' . $opdId . '-S' . ($sessionForRef > 0 ? $sessionForRef : 0) . '-' . $visitDate;
        }
        $careContextDisplay = 'OPD Visit - ' . $visitDate;

        $payload = [
            'opd_id' => $opdId,
            'opd_session_id' => (int) ($bundleRow['opd_session_id'] ?? 0),
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'consent_id' => $this->resolveConsentExternalId($consent),
            'bundle_type' => (string) ($bundleRow['bundle_type'] ?? 'MedicationRequestBundle'),
            'bundle' => $bundle,
        ];

        // Store FHIR payload in health_records before pushing
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $hasAbha ? $abhaId : '',
            'hi_type'        => 'OPConsultRecord',
            'entity_type'    => 'opd',
            'entity_id'      => (string) $opdId,
            'fhir_bundle'    => $bundleJson,
            'care_context_reference' => $careContextRef,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
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
                'hi_type'                => $hiType,
                'record_type'            => $hiType,
                'visit_date'             => $visitDate,
                'care_context_reference' => $careContextRef,
                'care_context_display'   => $careContextDisplay,
                'notes'                  => $careContextDisplay,
                'queue_id'               => $careContextRef,
                'record_data'            => $bundle,
            ]);

            $queueId = (string) ($result['queue_id'] ?? '');
            $bridgeRecordId = (int) ($result['id'] ?? 0);

            $httpCode = (int) ($result['http_code'] ?? 0);
            $resultOk = (int) ($result['ok'] ?? 0) === 1;
            $errorCode = strtoupper(trim((string) ($result['error_code'] ?? '')));
            $isDuplicate = $httpCode === 409 || $errorCode === 'DUPLICATE_RECORD';

            if ($isDuplicate) {
                $bridgeRecordId = (int) ($result['existing_record_id'] ?? $bridgeRecordId);
                $resultOk = true;
                $status = 'duplicate';
            }

            if (! $resultOk) {
                $connectorError = trim((string) (
                    $result['message']
                    ?? $result['error_text']
                    ?? ($result['error_code'] ?? '')
                ));
                if ($connectorError === '') {
                    $connectorError = 'Bridge push failed';
                }
                $status = 'failed';
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
            'request'     => ['opd_id' => $opdId, 'hi_type' => 'OPConsultRecord', 'consent_handle' => (string) ($consent['consent_handle'] ?? '')],
            'response'    => ['queue_id' => $queueId],
            'outcome'     => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok' => $connectorError === null ? 1 : 0,
            'queue_id' => $queueId,
            'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'gateway_consent_id' => $this->resolveConsentExternalId($consent) !== '' ? $this->resolveConsentExternalId($consent) : null,
            'status' => $status,
            'error' => $connectorError,
        ]);
    }

    public function shareIpdDischargeBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $ipdId         = (int) $this->request->getPost('ipd_id');
        $patientId     = (int) $this->request->getPost('patient_id');
        $abhaId        = trim((string) $this->request->getPost('abha_id'));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($ipdId <= 0 || $patientId <= 0) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ipd_id and patient_id are required']);
        }

        $hasAbha = $abhaId !== '';
        $consent = null;
        if ($hasAbha) {
            $consent = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
            if ($consent === null) {
                return $this->response->setJSON([
                    'ok'         => 0,
                    'error_text' => 'No active consent found. Share blocked due to expiry/not-approved consent.',
                ]);
            }
        }

        // ── Load IPD data ──────────────────────────────────────────────────────
        $ipdRow = $this->db->tableExists('ipd_master')
            ? ($this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [])
            : [];

        $summaryHtml = '';
        if ($this->db->tableExists('ipd_discharge') && $this->db->fieldExists('content', 'ipd_discharge')) {
            $summaryRow = $this->db->table('ipd_discharge')
                ->select('content')
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
            $summaryHtml = trim((string) ($summaryRow['content'] ?? ''));
        }

        // ── Load patient data ──────────────────────────────────────────────────
        $patientRow = [];
        if ($this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }
        $patName = trim(
            trim((string) ($patientRow['p_fname'] ?? '')) . ' ' .
            trim((string) ($patientRow['p_lname'] ?? ''))
        ) ?: trim((string) ($ipdRow['P_name'] ?? ''));

        // ── Load hospital profile (HFR ID, name) ──────────────────────────────
        $hospitalProfile = $this->getHospitalProfileForFhir();

        // ── Load attending doctor ─────────────────────────────────────────────
        $doctorId   = (int) ($ipdRow['r_doc_id'] ?? 0);
        $doctorName = trim((string) ($ipdRow['r_doc_name'] ?? ''));
        $doctorRegNo = '';
        if ($doctorId > 0 && $this->db->tableExists('doctor_master')) {
            $dFields = $this->db->getFieldNames('doctor_master') ?? [];
            $dSelect = ['id'];
            foreach (['p_fname', 'p_lname', 'doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                if (in_array($f, $dFields, true)) {
                    $dSelect[] = $f;
                }
            }
            $dRow = $this->db->table('doctor_master')->select(implode(',', $dSelect))->where('id', $doctorId)->get(1)->getRowArray() ?? [];
            if ($dRow !== [] && $doctorName === '') {
                $doctorName = trim(trim((string) ($dRow['p_fname'] ?? '')) . ' ' . trim((string) ($dRow['p_lname'] ?? '')));
            }
            foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                if (! empty($dRow[$f])) {
                    $doctorRegNo = trim((string) $dRow[$f]);
                    break;
                }
            }
        }

        // ── Parse dates ───────────────────────────────────────────────────────
        $admissionRaw  = trim((string) ($ipdRow['register_date'] ?? ''));
        $dischargeRaw  = trim((string) ($ipdRow['discharge_date'] ?? ''));
        $admissionDate = $admissionRaw !== '' ? (new \DateTime($admissionRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';
        $dischargeDate = $dischargeRaw !== '' ? (new \DateTime($dischargeRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';
        $visitDate     = $dischargeRaw !== '' ? date('Y-m-d', strtotime($dischargeRaw)) : ($admissionRaw !== '' ? date('Y-m-d', strtotime($admissionRaw)) : date('Y-m-d'));

        // ── Build FHIR DischargeSummaryRecord bundle ────────────────────────────
        $fhir = new FhirR4Builder();

        $patient = [
            'id'          => (string) $patientId,
            'name'        => $patName,
            'gender'      => trim((string) ($patientRow['gender'] ?? '')),
            'birthDate'   => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
            'abhaAddress' => $abhaId,
        ];

        $encounter = [
            'id'             => (string) $ipdId,
            'admission_date' => $admissionDate,
            'discharge_date' => $dischargeDate,
            'ipd_code'       => trim((string) ($ipdRow['ipd_code'] ?? '')),
        ];

        // Parse summary HTML into structured sections where possible
        $chiefComplaints = trim((string) ($ipdRow['problem'] ?? ''));
        $summary = [
            'title'                 => 'Discharge Summary',
            'chief_complaints'      => $chiefComplaints,
            'clinical_summary_html' => $summaryHtml,
        ];

        $practitioner = $doctorName !== ''
            ? ['name' => $doctorName, 'registration_number' => $doctorRegNo]
            : null;
        $organization = $hospitalProfile['name'] !== ''
            ? ['name' => $hospitalProfile['name'], 'hfr_id' => $hospitalProfile['hfr_id']]
            : null;

        $bundle     = $fhir->buildDischargeSummaryBundle($patient, $encounter, $summary, $practitioner, $organization);
        $bundleJson = (string) json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // ── Store health_record ───────────────────────────────────────────────
        $ipdCode = trim((string) ($ipdRow['ipd_code'] ?? ''));
        $ccRef   = 'IPD-' . $ipdId . ($ipdCode !== '' ? '-' . $ipdCode : '') . '-' . $visitDate;
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'DischargeSummaryRecord',
            'entity_type'    => 'ipd',
            'entity_id'      => (string) $ipdId,
            'fhir_bundle'    => $bundleJson,
            'care_context_reference' => $ccRef,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
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
                'patient_name'           => $patName,
                'abha_id'                => $abhaId,
                'hi_type'                => 'DischargeSummaryRecord',
                'record_type'            => 'DischargeSummaryRecord',
                'visit_date'             => $visitDate,
                'doctor_name'            => $doctorName,
                'care_context_reference' => $ccRef,
                'care_context_display'    => 'Discharge Summary - ' . ($ipdCode !== '' ? 'IPD#' . $ipdCode : 'IPD#' . $ipdId),
                'notes'                  => 'Discharge Summary - ' . ($ipdCode !== '' ? 'IPD#' . $ipdCode : 'IPD#' . $ipdId),
                'queue_id'               => 'IPD-' . $ipdId . '-' . $visitDate,
                'record_data'            => $bundle,
            ]);
            $queueId        = (string) ($result['queue_id'] ?? '');
            $bridgeRecordId = (int) ($result['id'] ?? 0);
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
            'request'       => ['ipd_id' => $ipdId, 'hi_type' => 'DischargeSummaryRecord', 'consent_handle' => (string) ($consent['consent_handle'] ?? '')],
            'response'      => ['queue_id' => $queueId],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'             => $connectorError === null ? 1 : 0,
            'queue_id'       => $queueId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'status'         => $connectorError === null ? 'queued' : 'failed',
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

        $hasAbha = $abhaId !== '';
        $consentRecord = null;
        if ($hasAbha) {
            $consentRecord = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
            if ($consentRecord === null) {
                return $this->response->setJSON([
                    'ok' => 0,
                    'error' => 'No active consent found. Share blocked due to expiry/not-approved consent.',
                ]);
            }
        }
        $effectiveConsent = (string) ($consentRecord['consent_handle'] ?? $consentHandle);

        // ── Load patient ──────────────────────────────────────────────────────
        $patientRow = [];
        if ($this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }
        $patName = trim(
            trim((string) ($patientRow['p_fname'] ?? '')) . ' ' .
            trim((string) ($patientRow['p_lname'] ?? ''))
        ) ?: trim((string) ($labReq->patient_name ?? ''));

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

        $pdfAttachment = $this->loadLatestLabPdfAttachment(
            (int) ($labReq->charge_id ?? 0),
            (int) ($labReq->lab_type ?? 0),
            $labReqId,
            $testTitle !== '' ? ($testTitle . ' PDF Report') : 'Lab Report PDF'
        );

        if ($pdfAttachment === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'error' => 'PDF attachment missing for this lab request. Compile and store the report first, then retry ABDM submit.',
            ]);
        }

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
                'abha_id'                => $abhaId,
                'hi_type'                => 'DiagnosticReportRecord',
                'record_type'            => 'DiagnosticReportRecord',
                'visit_date'             => $visitDate,
                'care_context_reference' => $ccRef,
                'care_context_display'    => $testTitle !== '' ? $testTitle : 'Lab Report',
                'notes'                  => $testTitle !== '' ? $testTitle : 'Lab Report',
                'queue_id'               => 'LAB-' . $labReqId . '-' . $visitDate,
                'record_data'            => $bundle,
            ]);
            $queueId        = (string) ($result['queue_id'] ?? '');
            $bridgeRecordId = (int) ($result['id'] ?? 0);
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
            'response'      => ['queue_id' => $queueId],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'             => $connectorError === null ? 1 : 0,
            'queue_id'       => $queueId,
            'consent_handle' => $effectiveConsent,
            'status'         => $connectorError === null ? 'queued' : 'failed',
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
        $patName = trim(
            trim((string) ($patientRow['p_fname'] ?? '')) . ' ' .
            trim((string) ($patientRow['p_lname'] ?? ''))
        ) ?: trim((string) ($labReq->patient_name ?? ''));

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
        $pdfAttachment = $this->loadLatestLabPdfAttachment(
            (int) ($labReq->charge_id ?? 0),
            (int) ($labReq->lab_type ?? 0),
            $labReqId,
            $testTitle !== '' ? ($testTitle . ' PDF Report') : 'Lab Report PDF'
        );

        if ($pdfAttachment === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'PDF attachment missing for this lab request. Compile and store the report first, then retry preview.',
            ]);
        }

        $bundle = $fhir->buildLabReportBundle($patient, $diagnosticReport, $observations, $practitioner, $organization, $encounter, $pdfAttachment);

        return $this->response->setJSON([
            'status' => 'ok',
            'lab_req_id' => $labReqId,
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'bundle' => $bundle,
        ]);
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

        $ipdRow = $this->db->tableExists('ipd_master')
            ? ($this->db->table('ipd_master')->where('id', $ipdId)->get(1)->getRowArray() ?? [])
            : [];

        if (empty($ipdRow)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'IPD record not found.',
            ]);
        }

        $summaryHtml = '';
        if ($this->db->tableExists('ipd_discharge') && $this->db->fieldExists('content', 'ipd_discharge')) {
            $summaryRow = $this->db->table('ipd_discharge')
                ->select('content')
                ->where('ipd_id', $ipdId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
            $summaryHtml = trim((string) ($summaryRow['content'] ?? ''));
        }

        $patientRow = [];
        if ($this->db->tableExists('patient_master')) {
            $patientRow = $this->db->table('patient_master')->where('id', $patientId)->get(1)->getRowArray() ?? [];
        }
        $patName = trim(
            trim((string) ($patientRow['p_fname'] ?? '')) . ' ' .
            trim((string) ($patientRow['p_lname'] ?? ''))
        ) ?: trim((string) ($ipdRow['P_name'] ?? ''));

        $hospitalProfile = $this->getHospitalProfileForFhir();

        $doctorId   = (int) ($ipdRow['r_doc_id'] ?? 0);
        $doctorName = trim((string) ($ipdRow['r_doc_name'] ?? ''));
        $doctorRegNo = '';
        if ($doctorId > 0 && $this->db->tableExists('doctor_master')) {
            $dFields = $this->db->getFieldNames('doctor_master') ?? [];
            $dSelect = ['id'];
            foreach (['p_fname', 'p_lname', 'doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                if (in_array($f, $dFields, true)) {
                    $dSelect[] = $f;
                }
            }
            $dRow = $this->db->table('doctor_master')->select(implode(',', $dSelect))->where('id', $doctorId)->get(1)->getRowArray() ?? [];
            if ($dRow !== [] && $doctorName === '') {
                $doctorName = trim(trim((string) ($dRow['p_fname'] ?? '')) . ' ' . trim((string) ($dRow['p_lname'] ?? '')));
            }
            foreach (['doctor_reg_no', 'registration_no', 'reg_no'] as $f) {
                if (! empty($dRow[$f])) {
                    $doctorRegNo = trim((string) $dRow[$f]);
                    break;
                }
            }
        }

        $admissionRaw  = trim((string) ($ipdRow['register_date'] ?? ''));
        $dischargeRaw  = trim((string) ($ipdRow['discharge_date'] ?? ''));
        $admissionDate = $admissionRaw !== '' ? (new \DateTime($admissionRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';
        $dischargeDate = $dischargeRaw !== '' ? (new \DateTime($dischargeRaw, new \DateTimeZone('Asia/Kolkata')))->format('Y-m-d\TH:i:sP') : '';

        $fhir = new FhirR4Builder();

        $patient = [
            'id'          => (string) $patientId,
            'name'        => $patName,
            'gender'      => trim((string) ($patientRow['gender'] ?? '')),
            'birthDate'   => ! empty($patientRow['dob']) ? date('Y-m-d', strtotime((string) $patientRow['dob'])) : '',
            'abhaAddress' => $abhaId,
        ];

        $encounter = [
            'id'             => (string) $ipdId,
            'admission_date' => $admissionDate,
            'discharge_date' => $dischargeDate,
            'ipd_code'       => trim((string) ($ipdRow['ipd_code'] ?? '')),
        ];

        $summary = [
            'title'                 => 'Discharge Summary',
            'chief_complaints'      => trim((string) ($ipdRow['problem'] ?? '')),
            'clinical_summary_html' => $summaryHtml,
        ];

        $practitioner = $doctorName !== ''
            ? ['name' => $doctorName, 'registration_number' => $doctorRegNo]
            : null;
        $organization = $hospitalProfile['name'] !== ''
            ? ['name' => $hospitalProfile['name'], 'hfr_id' => $hospitalProfile['hfr_id']]
            : null;

        $bundle = $fhir->buildDischargeSummaryBundle($patient, $encounter, $summary, $practitioner, $organization);

        return $this->response->setJSON([
            'status' => 'ok',
            'ipd_id' => $ipdId,
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'bundle' => $bundle,
        ]);
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
            // Use the new store-and-link flow (POST /v3/records/push) for re-push:
            // No consent needed — bridge stores record and serves it when ABDM requests data.
            $result  = $this->connector->pushRecord([
                'patient_id'             => (string) $patientId,
                'patient_name'           => $patientName,
                'abha_id'                => $abhaId,
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
            $queueId        = $result['queue_id'] ?? null;
            $bridgeRecordId = (int) ($result['id'] ?? 0);
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
        $yearOnly  = $dobValid !== '' ? (int) substr($dobValid, 0, 4) : 0;

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
            $row = $this->db->table('hospital_setting')
                ->select('s_value')
                ->where('s_name', $name)
                ->get(1)
                ->getRowArray();
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
    *              entity_id: string, fhir_bundle: string, care_context_reference?: string, consent_handle?: string} $data
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

            $this->db->table('health_records')->insert($insert);

            return (int) $this->db->insertID();
        } catch (\Throwable) {
            return 0;
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
    private function updateHealthRecordTxn(int $healthRecordId, string $queueId, ?string $error, int $bridgeRecordId = 0): void
    {
        try {
            $now = Time::now('Asia/Kolkata')->toDateTimeString();

            if ($this->db->tableExists('health_records')) {
                $hrUpdate = [
                    'abdm_txn_id'  => $queueId !== '' ? $queueId : null,
                    'push_status'  => $error === null ? 'queued' : 'failed',
                    'updated_at'   => $now,
                ];
                // Store bridge record_id when the column exists (migration adds it)
                if ($bridgeRecordId > 0) {
                    $hrFields = $this->db->getFieldNames('health_records') ?? [];
                    if (in_array('bridge_record_id', $hrFields, true)) {
                        $hrUpdate['bridge_record_id'] = $bridgeRecordId;
                    }
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
     * @return array{name: string, hfr_id: string}
     */
    private function getHospitalProfileForFhir(): array
    {
        try {
            $rows = $this->db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', ['ABDM_HMS_NAME', 'ABDM_HFR_ID', 'H_Name'])
                ->get()->getResultArray();
            $map   = array_column($rows, 's_value', 's_name');
            $name  = trim((string) ($map['ABDM_HMS_NAME'] ?? $map['H_Name'] ?? ''));
            $hfrId = trim((string) ($map['ABDM_HFR_ID'] ?? ''));
        } catch (\Throwable $e) {
            $name  = '';
            $hfrId = '';
        }
        return ['name' => $name, 'hfr_id' => $hfrId];
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

