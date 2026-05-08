<?php

namespace App\Libraries\Abdm;

use App\Libraries\BridgeSyncService;

/**
 * DreamsoftConnector
 *
 * Routes all ABDM operations through the Dreamsoft bridge middleware
 * by enqueuing events to bridge_sync_queue via BridgeSyncService.
 *
 * This is the default (and currently only production) adapter.
 * Switch to 'direct_abdm' in Config\AbdmConnector when DirectAbdmConnector is ready.
 */
class DreamsoftConnector implements AbdmConnectorInterface
{
    private BridgeSyncService $bridge;

    public function __construct()
    {
        $this->bridge = new BridgeSyncService();
    }

    public function getConnectorName(): string
    {
        return 'dreamsoft';
    }

    // -------------------------------------------------------------------------
    // ABHA
    // -------------------------------------------------------------------------

    public function validateAbha(string $abhaId, array $fullPayload = []): array
    {
        $payload = $fullPayload !== [] ? $fullPayload : [
            'abha_id'      => $abhaId,
            'requested_at' => date('Y-m-d H:i:s'),
        ];
        $queueId = $this->bridge->enqueue('abdm.abha.validate', $payload, 'abha', $abhaId);
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    // -------------------------------------------------------------------------
    // Consent
    // -------------------------------------------------------------------------

    public function requestConsent(
        int    $patientId,
        string $abhaId,
        string $purposeCode,
        string $expiresAt,
        string $consentHandle,
        array  $rawPayload = []
    ): array {
        $payload = $rawPayload !== [] ? $rawPayload : [
            'patient_id'     => $patientId,
            'abha_id'        => $abhaId,
            'purpose_code'   => $purposeCode,
            'expires_at'     => $expiresAt,
            'consent_handle' => $consentHandle,
        ];
        $queueId = $this->bridge->enqueue('abdm.consent.requested', $payload, 'consent', $consentHandle);
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    // -------------------------------------------------------------------------
    // Health Record Sharing
    // -------------------------------------------------------------------------

    public function sharePrescriptionBundle(array $payload, string $entityId = ''): array
    {
        $queueId = $this->bridge->enqueue(
            'abdm.fhir.share.requested',
            $payload,
            'opd_fhir_document',
            $entityId
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    public function shareIpdDischargeBundle(array $payload, string $entityId = ''): array
    {
        $queueId = $this->bridge->enqueue(
            'abdm.ipd.discharge.share.requested',
            $payload,
            'ipd_discharge',
            $entityId
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    public function shareDiagnosisReportBundle(array $payload, string $entityId = ''): array
    {
        $queueId = $this->bridge->enqueue(
            'abdm.diagnosis.report.share.requested',
            $payload,
            'lab_request',
            $entityId
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    // -------------------------------------------------------------------------
    // Scan & Share
    // -------------------------------------------------------------------------

    public function scanShareLookup(string $qrPayload, string $abhaIdHint = '', array $fullPayload = []): array
    {
        $payload = $fullPayload !== [] ? $fullPayload : [
            'qr_payload'   => $qrPayload,
            'abha_id_hint' => $abhaIdHint,
            'requested_at' => date('Y-m-d H:i:s'),
        ];
        $queueId = $this->bridge->enqueue(
            'abdm.scan_share.lookup',
            $payload,
            'abha_scan',
            $abhaIdHint !== '' ? $abhaIdHint : null
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    // -------------------------------------------------------------------------
    // NHCX Claims
    // -------------------------------------------------------------------------

    public function nhcxClaimCreate(
        array $bundle,
        int   $documentId,
        int   $patientId,
        int   $encounterId
    ): array {
        $payload = [
            'nhcx_claim_document_id' => $documentId,
            'patient_id'             => $patientId,
            'encounter_id'           => $encounterId,
            'bundle'                 => $bundle,
        ];
        $queueId = $this->bridge->enqueue(
            'nhcx.claim.created',
            $payload,
            'nhcx_claim_document',
            (string) $documentId
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }

    public function nhcxClaimStatusRequest(
        int    $documentId,
        string $externalRef,
        string $currentStatus,
        array  $fullPayload = []
    ): array {
        $payload = $fullPayload !== [] ? $fullPayload : [
            'nhcx_claim_document_id' => $documentId,
            'external_ref'           => $externalRef,
            'claim_status'           => $currentStatus,
            'requested_at'           => date('Y-m-d H:i:s'),
        ];
        $queueId = $this->bridge->enqueue(
            'nhcx.claim.status.requested',
            $payload,
            'nhcx_claim_document',
            (string) $documentId
        );
        return ['ok' => 1, 'queue_id' => $queueId, 'status' => 'queued'];
    }
}
