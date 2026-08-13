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

    /**
     * Request an OTP for an ABHA account returned by validateAbha().
     *
     * @param array<string, mixed> $payload Must contain the search txn_id and
     *                                      selected MOBILE_OTP or AADHAAR_OTP method.
     */
    public function abhaLoginRequestOtp(array $payload): array;

    /**
     * Verify an account-bound ABHA login OTP and return the normalized profile
     * plus the official ABHA card payload when available.
     *
     * @param array<string, mixed> $payload Must contain txn_id, auth_method, and otp.
     */
    public function abhaLoginVerifyOtp(array $payload): array;

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
    // M1 ABHA OTP Flows (ABHA creation & linking via Aadhaar / Mobile)
    // These require synchronous HTTP calls — not suitable for async queue.
    // -------------------------------------------------------------------------

    /**
     * ABDM M1: Initiate ABHA creation/link via Aadhaar OTP.
     * Calls gateway POST /api/v3/abha/aadhaar/generate-otp
     *
     * @param array<string, mixed> $payload  Must contain 'aadhaar' key (12-digit)
     */
    public function abhaAadhaarGenerateOtp(array $payload): array;

    /**
     * ABDM M1: Verify Aadhaar OTP and complete ABHA flow.
     * Calls gateway POST /api/v3/abha/aadhaar/verify-otp
     *
     * @param array<string, mixed> $payload  Must contain 'txn_id' and 'otp' keys
     */
    public function abhaAadhaarVerifyOtp(array $payload): array;

    /**
     * ABDM M1: Generate mobile OTP for ABHA verification.
     * Calls gateway POST /api/v3/abha/mobile/generate-otp
     *
     * @param array<string, mixed> $payload  Must contain 'mobile' key (10-digit)
     */
    public function abhaMobileGenerateOtp(array $payload): array;

    /**
     * ABDM M1: Verify mobile OTP and complete ABHA flow.
     * Calls gateway POST /api/v3/abha/mobile/verify-otp
     *
     * @param array<string, mixed> $payload  Must contain 'txn_id' and 'otp' keys
     */
    public function abhaMobileVerifyOtp(array $payload): array;

    /**
     * ABDM M1: List ABHA address suggestions for an in-progress enrolment.
     *
     * @param array<string, mixed> $payload  Must contain 'txn_id'
     */
    public function abhaAddressSuggestions(array $payload): array;

    /**
     * ABDM M1: Set the chosen (suggested or custom) ABHA address.
     *
     * @param array<string, mixed> $payload  Must contain 'txn_id' and 'abha_address'
     */
    public function abhaSetAddress(array $payload): array;

    /**
     * Fetch hospital facility details and official ABDM Scan & Share QR image.
     * Gateway response includes hospital_name, hfr_id, and qr_data data URI/base64.
     */
    public function facilityQrCode(): array;

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

    // -------------------------------------------------------------------------
    // Health Records — store-and-link flow (POST /api/v3/records/push)
    // -------------------------------------------------------------------------

    /**
     * Push a health record to the bridge using the store-and-link flow.
     * The bridge stores the record; ABDM links it when the patient uses their PHR app
     * (user-initiated) or when HMS calls shareRecord().
     * Returns ['ok'=>1, 'queue_id'=>'REC-...', 'id'=>42, 'status'=>'pending'] on success.
     *
     * Required keys in $data:
     *   patient_id, patient_name, hi_type, visit_date, record_data (FHIR bundle or raw JSON)
     * Optional:
     *   abha_id, abha_address, doctor_name, department, care_context_reference, notes
     *
     * @param array<string, mixed> $data
     */
    public function pushRecord(array $data): array;

    /**
     * GET /api/v3/records/{id} — fetch stored bridge record + abdm_status.
     */
    public function getRecord(int $bridgeId): array;

    /**
     * POST /api/v3/records/{id}/share — trigger HIP-initiated ABDM care-context linking.
     */
    public function triggerShare(int $bridgeId): array;

    /**
     * POST /api/v3/records/{id}/link-and-share — orchestrated care-context link + share.
     */
    public function linkAndShare(int $bridgeId): array;

    /**
     * GET /api/v3/records/{id}/workflow-status — end-to-end workflow status.
     */
    public function workflowStatus(int $bridgeId): array;

    /**
     * GET /api/v3/records — list stored bridge records with optional filters.
     *
     * Filters: abha_id, abha_address, status (pending|shared|linked|failed|revoked),
     *          record_type, page, per_page
     *
     * @param array<string, mixed> $filters
     */
    public function getRecords(array $filters = []): array;

    // -------------------------------------------------------------------------
    // System / Hospital Info
    // -------------------------------------------------------------------------

    /**
     * GET /api/v3/gateway/status — hospital info + ABDM upstream connectivity check.
     * Returns HIP ID, hospital name, ABDM connection state, test mode flag.
     */
    public function gatewayStatus(): array;

    // -------------------------------------------------------------------------
    // HIP-Initiated Linking (HMS → Bridge → ABDM, 2-step async flow)
    // -------------------------------------------------------------------------

    /**
     * Step 1 — POST /api/v3/hip/link-token
     * Request a one-time JWT link token for a patient from ABDM (async).
     * Returns immediately with ['ok'=>1, 'link_token_id'=>N, 'request_id'=>'...']
     * Token arrives asynchronously — use link_token_id in step 2 once delivered.
     *
     * Required keys: abha_address, name (First|Last), gender (M|F|O), year_of_birth
     * Optional: abha_number
     *
     * @param array<string, mixed> $payload
     */
    public function hipLinkToken(array $payload): array;

    /**
     * Step 2 — POST /api/v3/hip/link/carecontext
     * Link care contexts to patient's ABHA using link_token_id from step 1.
     *
     * Required keys: abha_address, link_token_id, patient_ref, display, hi_type
     * care_contexts: [['ref' => '...', 'display' => '...']]
     *
     * @param array<string, mixed> $payload
     */
    public function hipLinkCareContext(array $payload): array;

    /**
     * GET /api/v3/hip/link/patient/links — fetch all ABDM-linked care contexts for a patient.
     *
     * @param array<string, mixed> $filters  e.g. ['abha_address' => 'p@abdm', 'limit' => 20]
     */
    public function hipGetPatientLinks(array $filters = []): array;

    /**
     * POST /api/v3/hip/link/notify — push care-context update notification to ABDM.
     *
     * Required keys: abha_address, care_context_reference, hi_type, date_of_record
     *
     * @param array<string, mixed> $payload
     */
    public function hipLinkNotify(array $payload): array;

    /**
     * POST /api/v3/hip/link/sms-notify — send ABDM deep-link SMS to patient's mobile.
     *
     * Required keys: phone_number
     * Optional: hip_name
     *
     * @param array<string, mixed> $payload
     */
    public function hipSmsNotify(array $payload): array;

    // OPD Queue
    public function opdQueueFetch(string $date = '', string $status = '', int $page = 1, int $limit = 100): array;
    public function opdTokenCreate(array $payload): array;
    public function opdTokenUpdateStatus(int $tokenId, string $status): array;

    /**
     * GET /api/v3/opd/running-token-status — current token being served at this HIP.
     */
    public function opdRunningTokenStatus(): array;
}
