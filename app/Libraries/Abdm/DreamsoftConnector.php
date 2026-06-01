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
    // M1 ABHA OTP Flows — not queue-able; throw so callers fall back to sync connector
    // -------------------------------------------------------------------------

    public function abhaAadhaarGenerateOtp(array $payload): array
    {
        throw new \RuntimeException(
            'DreamsoftConnector does not support synchronous M1 OTP flows. '
            . 'Set abdm.connector = eatria_bridge in .env to use M1 endpoints.'
        );
    }

    public function abhaAadhaarVerifyOtp(array $payload): array
    {
        throw new \RuntimeException(
            'DreamsoftConnector does not support synchronous M1 OTP flows. '
            . 'Set abdm.connector = eatria_bridge in .env to use M1 endpoints.'
        );
    }

    public function abhaMobileGenerateOtp(array $payload): array
    {
        throw new \RuntimeException(
            'DreamsoftConnector does not support synchronous M1 OTP flows. '
            . 'Set abdm.connector = eatria_bridge in .env to use M1 endpoints.'
        );
    }

    public function abhaMobileVerifyOtp(array $payload): array
    {
        throw new \RuntimeException(
            'DreamsoftConnector does not support synchronous M1 OTP flows. '
            . 'Set abdm.connector = eatria_bridge in .env to use M1 endpoints.'
        );
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

    public function pushRecord(array $data): array
    {
        // Dreamsoft bridge does not support store-and-link flow.
        // Delegate to sharePrescriptionBundle as a best-effort fallback.
        return $this->sharePrescriptionBundle($data, (string) ($data['entity_id'] ?? ''));
    }

    public function getRecord(int $bridgeId): array
    {
        // No direct pull API in Dreamsoft async adapter; return queued placeholder.
        return [
            'ok' => 1,
            'id' => $bridgeId,
            'status' => 'queued',
            'message' => 'Dreamsoft connector does not provide synchronous record fetch.',
        ];
    }

    public function triggerShare(int $bridgeId): array
    {
        // Keep compatibility with older UI actions.
        return [
            'ok' => 1,
            'id' => $bridgeId,
            'status' => 'queued',
            'message' => 'Dreamsoft connector does not provide synchronous share trigger.',
        ];
    }

    public function linkAndShare(int $bridgeId): array
    {
        // New e-Atria orchestration API is not available in Dreamsoft adapter.
        return [
            'ok' => 1,
            'id' => $bridgeId,
            'status' => 'queued',
            'message' => 'Dreamsoft connector does not provide synchronous link-and-share orchestration.',
        ];
    }

    public function workflowStatus(int $bridgeId): array
    {
        // No synchronous status API in Dreamsoft adapter.
        return [
            'ok' => 1,
            'id' => $bridgeId,
            'status' => 'queued',
            'next_action' => 'Track status via bridge queue/callback events.',
        ];
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

    public function getRecords(array $filters = []): array
    {
        return [
            'ok' => 1,
            'records' => [],
            'status' => 'queued',
            'message' => 'Dreamsoft connector does not provide synchronous records listing.',
        ];
    }

    public function gatewayStatus(): array
    {
        return [
            'ok' => 1,
            'status' => 'queued',
            'connector' => $this->getConnectorName(),
            'message' => 'Dreamsoft connector does not expose synchronous gateway status.',
        ];
    }

    public function hipLinkToken(array $payload): array
    {
        return [
            'ok' => 0,
            'error_text' => 'HIP link-token flow is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function hipLinkCareContext(array $payload): array
    {
        return [
            'ok' => 0,
            'error_text' => 'HIP link care-context flow is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function hipGetPatientLinks(array $filters = []): array
    {
        return [
            'ok' => 0,
            'error_text' => 'HIP patient links flow is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function hipLinkNotify(array $payload): array
    {
        return [
            'ok' => 0,
            'error_text' => 'HIP link notify flow is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function hipSmsNotify(array $payload): array
    {
        return [
            'ok' => 0,
            'error_text' => 'HIP SMS notify flow is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function opdQueueFetch(string $date = '', string $status = '', int $page = 1, int $limit = 100): array
    {
        return [
            'ok' => 0,
            'error_text' => 'OPD queue API is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function opdTokenCreate(array $payload): array
    {
        return [
            'ok' => 0,
            'error_text' => 'OPD token API is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function opdTokenUpdateStatus(int $tokenId, string $status): array
    {
        return [
            'ok' => 0,
            'error_text' => 'OPD token status API is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }

    public function opdRunningTokenStatus(): array
    {
        return [
            'ok' => 0,
            'error_text' => 'OPD running token status API is not supported by DreamsoftConnector. Use eatria_bridge connector.',
        ];
    }
}
