<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorInterface;
use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\FhirR4Builder;
use App\Libraries\FhirEncryptionService;
use App\Libraries\AbdmAuditService;
use CodeIgniter\I18n\Time;

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

        if (! $this->db->tableExists('abdm_consent_records')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'abdm_consent_records table not found']);
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

        $queueId = null;
        try {
            $result  = $this->connector->requestConsent($patientId, $abhaId, $purposeCode, $expiresAt, $consentHandle, $rawPayload);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'consent_handle' => $consentHandle,
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

        if (! $this->db->tableExists('abdm_consent_records')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'abdm_consent_records table not found']);
        }

        $handle = trim((string) $payload['consent_handle']);
        $status = trim((string) ($payload['consent_status'] ?? 'approved'));
        $expiresAt = trim((string) ($payload['expires_at'] ?? ''));
        $now = Time::now('Asia/Kolkata')->toDateTimeString();

        $existing = $this->db->table('abdm_consent_records')
            ->where('consent_handle', $handle)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

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

        $this->db->table('abdm_consent_records')
            ->where('consent_handle', $handle)
            ->update($update);

        return $this->response->setJSON(['ok' => 1, 'consent_handle' => $handle, 'status' => $incomingStatus]);
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
        try {
            $result  = $this->connector->scanShareLookup($qrPayload, $abhaId, $payload);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => 'queued',
            'queue_id' => $queueId,
            'abha_id_hint' => $abhaId,
            'message' => 'Scan & Share lookup queued to center server.',
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
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($opdId <= 0 || $patientId <= 0 || $abhaId === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_id, patient_id and abha_id are required']);
        }

        if (! $this->db->tableExists('opd_fhir_documents')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'opd_fhir_documents table not found']);
        }

        $consent = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
        if ($consent === null) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'No active consent found. Share blocked due to expiry/not-approved consent.',
            ]);
        }

        $builder = $this->db->table('opd_fhir_documents')
            ->where('opd_id', $opdId)
            ->where('bundle_type', 'MedicationRequestBundle');
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

        $payload = [
            'opd_id' => $opdId,
            'opd_session_id' => (int) ($bundleRow['opd_session_id'] ?? 0),
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'bundle_type' => (string) ($bundleRow['bundle_type'] ?? 'MedicationRequestBundle'),
            'bundle' => $bundle,
        ];

        // Store FHIR payload in health_records before pushing
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'OPConsultRecord',
            'entity_type'    => 'opd',
            'entity_id'      => (string) $opdId,
            'fhir_bundle'    => $bundleJson,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
        ]);

        $queueId = null;
        $connectorError = null;
        try {
            $result  = $this->connector->sharePrescriptionBundle($payload, (string) ($bundleRow['id'] ?? ''));
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        // Update health_record with txn_id and create record_link
        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError);
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
            'ok' => 1,
            'queue_id' => $queueId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'status' => 'queued',
        ]);
    }

    public function shareIpdDischargeBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $ipdId = (int) $this->request->getPost('ipd_id');
        $patientId = (int) $this->request->getPost('patient_id');
        $abhaId = trim((string) $this->request->getPost('abha_id'));
        $consentHandle = trim((string) $this->request->getPost('consent_handle'));

        if ($ipdId <= 0 || $patientId <= 0 || $abhaId === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ipd_id, patient_id and abha_id are required']);
        }

        $consent = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);
        if ($consent === null) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'No active consent found. Share blocked due to expiry/not-approved consent.',
            ]);
        }

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

        $payload = [
            'ipd_id' => $ipdId,
            'patient_id' => $patientId,
            'abha_id' => $abhaId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'ipd_code' => trim((string) ($ipdRow['ipd_code'] ?? '')),
            'register_date' => trim((string) ($ipdRow['register_date'] ?? '')),
            'discharge_date' => trim((string) ($ipdRow['discharge_date'] ?? '')),
            'discharge_time' => trim((string) ($ipdRow['discharge_time'] ?? '')),
            'summary_html' => $summaryHtml,
        ];

        // Store in health_records
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'DischargeSummary',
            'entity_type'    => 'ipd',
            'entity_id'      => (string) $ipdId,
            'fhir_bundle'    => json_encode($payload),
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
        ]);

        $queueId = null;
        $connectorError = null;
        try {
            $result  = $this->connector->shareIpdDischargeBundle($payload, (string) $ipdId);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError);
        }

        $this->getAuditService()->log([
            'action'        => 'push_record',
            'entity_type'   => 'ipd',
            'entity_id'     => (string) $ipdId,
            'abha_id'       => $abhaId,
            'patient_id'    => $patientId,
            'request'       => ['ipd_id' => $ipdId, 'hi_type' => 'DischargeSummary', 'consent_handle' => (string) ($consent['consent_handle'] ?? '')],
            'response'      => ['queue_id' => $queueId],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'queue_id' => $queueId,
            'consent_handle' => (string) ($consent['consent_handle'] ?? ''),
            'status' => 'queued',
        ]);
    }

    public function shareDiagnosisReportBundle()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'AJAX only']);
        }

        $labReqId  = (int) ($this->request->getPost('lab_req_id') ?? 0);
        $patientId = (int) ($this->request->getPost('patient_id') ?? 0);
        $abhaId    = trim((string) ($this->request->getPost('abha_id') ?? ''));
        $consentHandle = trim((string) ($this->request->getPost('consent_handle') ?? ''));

        if ($labReqId <= 0 || $patientId <= 0 || $abhaId === '') {
            return $this->response->setJSON(['ok' => 0, 'error' => 'lab_req_id, patient_id and abha_id are required']);
        }

        $db = \Config\Database::connect();

        $labReq = $db->table('lab_request')
            ->select('id, patient_name, lab_type, charge_id, Report_Data, report_data_Impression, status, reported_time')
            ->where('id', $labReqId)
            ->get(1)
            ->getRow();

        if (! $labReq) {
            return $this->response->setJSON(['ok' => 0, 'error' => 'Lab request not found']);
        }

        $consentRecord = $this->getActiveConsentRecord($patientId, $abhaId, $consentHandle);

        $payload = [
            'lab_req_id'       => $labReqId,
            'patient_id'       => $patientId,
            'patient_name'     => (string) ($labReq->patient_name ?? ''),
            'abha_id'          => $abhaId,
            'lab_type'         => (int) ($labReq->lab_type ?? 0),
            'invoice_id'       => (int) ($labReq->charge_id ?? 0),
            'report_html'      => (string) ($labReq->Report_Data ?? ''),
            'impression'       => (string) ($labReq->report_data_Impression ?? ''),
            'report_status'    => (int) ($labReq->status ?? 0),
            'reported_time'    => (string) ($labReq->reported_time ?? ''),
            'consent_handle'   => $consentRecord['consent_handle'] ?? $consentHandle,
        ];

        // Store in health_records (report HTML as the FHIR payload placeholder)
        $healthRecordId = $this->storeHealthRecord([
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'hi_type'        => 'DiagnosticReport',
            'entity_type'    => 'lab',
            'entity_id'      => (string) $labReqId,
            'fhir_bundle'    => json_encode($payload),
            'consent_handle' => $payload['consent_handle'],
        ]);

        $queueId = null;
        $connectorError = null;
        try {
            $result  = $this->connector->shareDiagnosisReportBundle($payload, (string) $labReqId);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
            $connectorError = $e->getMessage();
        }

        if ($healthRecordId > 0) {
            $this->updateHealthRecordTxn($healthRecordId, (string) ($queueId ?? ''), $connectorError);
        }

        $this->getAuditService()->log([
            'action'        => 'push_record',
            'entity_type'   => 'lab',
            'entity_id'     => (string) $labReqId,
            'abha_id'       => $abhaId,
            'patient_id'    => $patientId,
            'request'       => ['lab_req_id' => $labReqId, 'hi_type' => 'DiagnosticReport', 'consent_handle' => $payload['consent_handle']],
            'response'      => ['queue_id' => $queueId],
            'outcome'       => $connectorError === null ? 'success' : 'failure',
            'error_message' => (string) ($connectorError ?? ''),
        ]);

        return $this->response->setJSON([
            'ok'             => 1,
            'queue_id'       => $queueId,
            'consent_handle' => $payload['consent_handle'],
            'status'         => 'queued',
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

        if (! $this->db->tableExists('abdm_consent_records')) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'abdm_consent_records table not found']);
        }

        $existing = $this->db->table('abdm_consent_records')
            ->where('consent_handle', $consentHandle)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        if (empty($existing)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error_text' => 'consent_handle not found']);
        }

        // Idempotency — already revoked
        if (strtolower((string) ($existing['consent_status'] ?? '')) === 'revoked') {
            return $this->response->setJSON(['ok' => 1, 'consent_handle' => $consentHandle, 'status' => 'revoked', 'duplicate' => 1]);
        }

        $this->db->table('abdm_consent_records')
            ->where('consent_handle', $consentHandle)
            ->update([
                'consent_status'  => 'revoked',
                'raw_payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at'      => $now,
            ]);

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

        // Decrypt the stored payload (JSON encoded full push payload, may contain 'bundle' key)
        $storedPayload = [];
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
                'visit_date'             => $visitDate !== '' ? $visitDate : date('Y-m-d'),
                'doctor_name'            => $doctorName,
                'department'             => $department,
                'entity_type'            => $entityType,
                'entity_id'              => $entityId,
                'care_context_reference' => trim((string) ($hr['care_context_reference'] ?? '')),
                'record_data'            => $fhirBundle,
            ]);
            $queueId = $result['queue_id'] ?? null;
        } catch (\Throwable $e) {
            $connectorErr = $e->getMessage();
        }

        $this->updateHealthRecordTxn($hrId, (string) ($queueId ?? ''), $connectorErr);

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

    private function validateWebhookSignature()
    {
        $secret = $this->readRuntimeSetting('EKA_WEBHOOK_SECRET');
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
     * Persist an encrypted FHIR bundle to health_records and return the new row id.
     * Returns 0 when the table is absent or on any DB error (fail-open).
     *
     * @param array{patient_id: int, abha_id: string, hi_type: string, entity_type: string,
     *              entity_id: string, fhir_bundle: string, consent_handle?: string} $data
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

            $this->db->table('health_records')->insert([
                'patient_id'          => (int) ($data['patient_id'] ?? 0) > 0 ? (int) $data['patient_id'] : null,
                'abha_id'             => trim((string) ($data['abha_id'] ?? '')) ?: null,
                'hi_type'             => (string) ($data['hi_type'] ?? 'unknown'),
                'entity_type'         => (string) ($data['entity_type'] ?? ''),
                'entity_id'           => (string) ($data['entity_id'] ?? ''),
                'fhir_bundle_enc'     => $encPayload !== '' ? $encPayload : null,
                'push_status'         => 'queued',
                'push_at'             => $now,
                'consent_handle'      => trim((string) ($data['consent_handle'] ?? '')) ?: null,
                'created_by_user_id'  => (int) ($session->get('user_id') ?? 0) > 0 ? (int) $session->get('user_id') : null,
                'created_by_name'     => trim((string) ($session->get('full_name') ?? $session->get('name') ?? '')) ?: null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            return (int) $this->db->insertID();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Update a health_record's push status and txn_id after getting a connector response.
     * Also creates a matching record_links row.
     */
    private function updateHealthRecordTxn(int $healthRecordId, string $queueId, ?string $error): void
    {
        try {
            $now = Time::now('Asia/Kolkata')->toDateTimeString();

            if ($this->db->tableExists('health_records')) {
                $this->db->table('health_records')
                    ->where('id', $healthRecordId)
                    ->update([
                        'abdm_txn_id'  => $queueId !== '' ? $queueId : null,
                        'push_status'  => $error === null ? 'queued' : 'failed',
                        'updated_at'   => $now,
                    ]);
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

    private function getActiveConsentRecord(int $patientId, string $abhaId, string $consentHandle = ''): ?array
    {
        if (! $this->db->tableExists('abdm_consent_records')) {
            return null;
        }

        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $builder = $this->db->table('abdm_consent_records')
            ->where('patient_id', $patientId)
            ->where('abha_id', $abhaId)
            ->where('consent_status', 'approved')
            ->groupStart()
                ->where('expires_at IS NULL', null, false)
                ->orWhere('expires_at >', $now)
            ->groupEnd();

        if ($consentHandle !== '') {
            $builder->where('consent_handle', $consentHandle);
        }

        $row = $builder->orderBy('id', 'DESC')->get(1)->getRowArray();
        return ! empty($row) ? $row : null;
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

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
        }

        try {
            $result = $this->connector->abhaAadhaarVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
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

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
        }

        try {
            $result = $this->connector->abhaMobileVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        return $this->response->setJSON($result);
    }
}

