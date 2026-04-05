<?php

namespace App\Controllers;

use App\Libraries\BridgeSyncService;
use App\Libraries\FhirR4Builder;
use CodeIgniter\I18n\Time;

class AbdmGateway extends BaseController
{
    public function abhaValidate()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $abhaId = trim((string) $this->request->getPost('abha_id'));
        if ($abhaId === '' || preg_match('/^\d{14}$/', $abhaId) !== 1) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be a 14-digit number.']);
        }

        $payload = [
            'abha_id' => $abhaId,
            'requested_at' => Time::now('Asia/Kolkata')->toDateTimeString(),
        ];

        $queueId = null;
        try {
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('abdm.abha.validate', $payload, 'abha', $abhaId);
        } catch (\Throwable $e) {
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => 'queued',
            'queue_id' => $queueId,
            'message' => 'ABHA validation request queued to center server.',
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
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('abdm.consent.requested', $rawPayload, 'consent', $consentHandle);
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
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('abdm.scan_share.lookup', $payload, 'abha_scan', $abhaId !== '' ? $abhaId : null);
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

        $queueId = null;
        try {
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('abdm.fhir.share.requested', $payload, 'opd_fhir_document', (string) ($bundleRow['id'] ?? ''));
        } catch (\Throwable $e) {
        }

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

        $queueId = null;
        try {
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('abdm.ipd.discharge.share.requested', $payload, 'ipd_discharge', (string) $ipdId);
        } catch (\Throwable $e) {
        }

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

        $bridge   = new BridgeSyncService();
        $queueId  = $bridge->enqueue('abdm.diagnosis.report.share.requested', $payload, 'lab_request', (string) $labReqId);

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
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue(
                'nhcx.claim.created',
                [
                    'nhcx_claim_document_id' => $documentId,
                    'patient_id' => $patientId,
                    'encounter_id' => $encounterId,
                    'bundle' => $bundle,
                ],
                'nhcx_claim_document',
                (string) $documentId
            );
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
            $bridge = new BridgeSyncService();
            $queueId = $bridge->enqueue('nhcx.claim.status.requested', $payload, 'nhcx_claim_document', (string) $documentId);
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
}
