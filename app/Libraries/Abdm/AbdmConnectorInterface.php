<?php

namespace App\Libraries\Abdm;

/**
 * AbdmConnectorInterface
 *
 * Contract for all ABDM connector adapters.
 * Switch adapters via Config\AbdmConnector::$connector (or env abdm.connector).
 *
 * Available adapters:
 *   'dreamsoft'   -> DreamsoftConnector   (routes through Dreamsoft bridge — current)
 *   'direct_abdm' -> DirectAbdmConnector  (calls ABDM APIs directly — future)
 *
 * All methods return a uniform response array:
 *   Success:  ['ok' => 1, 'queue_id' => int|null, 'status' => string, ...]
 *   Failure:  ['ok' => 0, 'error_text' => string]
 *
 * Controllers never depend on the adapter implementation:
 *   $connector = AbdmConnectorFactory::make();
 *   $result    = $connector->validateAbha($abhaId);
 */
interface AbdmConnectorInterface
{
    /** Human-readable adapter name: 'dreamsoft' | 'direct_abdm' */
    public function getConnectorName(): string;

    // -------------------------------------------------------------------------
    // ABHA
    // -------------------------------------------------------------------------

    /**
     * Validate / look up a 14-digit ABHA number.
     *
     * @param array<string, mixed> $fullPayload  Full payload built by the controller
     *                                           (must contain at minimum 'abha_id' key)
     */
    public function validateAbha(string $abhaId, array $fullPayload = []): array;

    // -------------------------------------------------------------------------
    // Consent
    // -------------------------------------------------------------------------

    /**
     * Initiate a consent request for a patient.
     *
     * @param array<string, mixed> $rawPayload  Full raw payload to pass to the bridge
     */
    public function requestConsent(
        int    $patientId,
        string $abhaId,
        string $purposeCode,
        string $expiresAt,
        string $consentHandle,
        array  $rawPayload = []
    ): array;

    // -------------------------------------------------------------------------
    // Health Record Sharing
    // -------------------------------------------------------------------------

    /**
     * Share a prescription / OPD FHIR bundle.
     *
     * Expected payload keys:
     *   opd_id, opd_session_id, patient_id, abha_id,
     *   consent_handle, bundle_type, bundle (FHIR array)
     *
     * @param array<string, mixed> $payload
     */
    public function sharePrescriptionBundle(array $payload, string $entityId = ''): array;

    /**
     * Share an IPD discharge summary bundle.
     *
     * Expected payload keys:
     *   ipd_id, patient_id, abha_id, consent_handle,
     *   ipd_code, register_date, discharge_date, discharge_time, summary_html
     *
     * @param array<string, mixed> $payload
     */
    public function shareIpdDischargeBundle(array $payload, string $entityId = ''): array;

    /**
     * Share a diagnostic / lab report bundle.
     *
     * Expected payload keys:
     *   lab_req_id, patient_id, patient_name, abha_id, lab_type,
     *   invoice_id, report_html, impression, report_status, reported_time, consent_handle
     *
     * @param array<string, mixed> $payload
     */
    public function shareDiagnosisReportBundle(array $payload, string $entityId = ''): array;

    // -------------------------------------------------------------------------
    // Scan & Share
    // -------------------------------------------------------------------------

    /**
     * Look up a patient using QR payload from Scan & Share flow.
     *
     * @param array<string, mixed> $fullPayload  Full payload built by the controller
     */
    public function scanShareLookup(string $qrPayload, string $abhaIdHint = '', array $fullPayload = []): array;

    // -------------------------------------------------------------------------
    // NHCX Claims
    // -------------------------------------------------------------------------

    /**
     * Submit a new NHCX claim bundle.
     *
     * @param array<string, mixed> $bundle  FHIR Claim bundle from FhirR4Builder
     */
    public function nhcxClaimCreate(
        array $bundle,
        int   $documentId,
        int   $patientId,
        int   $encounterId
    ): array;

    /**
     * Request a status update on an existing NHCX claim.
     *
     * @param array<string, mixed> $fullPayload  Full payload built by the controller
     */
    public function nhcxClaimStatusRequest(
        int    $documentId,
        string $externalRef,
        string $currentStatus,
        array  $fullPayload = []
    ): array;
}
