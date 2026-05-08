<?php

namespace App\Libraries\Abdm;

/**
 * DirectAbdmConnector
 *
 * Future adapter — calls ABDM / NHA APIs directly without any middleware.
 * All methods currently throw RuntimeException with TODO notes.
 *
 * To activate: set env abdm.connector = direct_abdm and implement each method.
 *
 * Required env / Config\AbdmConnector keys:
 *   directAbdmBaseUrl      e.g. 'https://dev.abdm.gov.in/api/v3'
 *   directAbdmClientId     ABDM M3 client ID
 *   directAbdmClientSecret ABDM M3 client secret
 *   directAbdmTimeoutSec   HTTP timeout in seconds (default 30)
 *
 * Reference ABDM IG: https://nrces.in/ndhm/fhir/r4/index.html
 */
class DirectAbdmConnector implements AbdmConnectorInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private int    $timeoutSec;

    public function __construct()
    {
        $config              = config('AbdmConnector');
        $this->baseUrl       = rtrim((string) ($config->directAbdmBaseUrl ?? ''), '/');
        $this->clientId      = (string) ($config->directAbdmClientId ?? '');
        $this->clientSecret  = (string) ($config->directAbdmClientSecret ?? '');
        $this->timeoutSec    = (int) ($config->directAbdmTimeoutSec ?? 30);
    }

    public function getConnectorName(): string
    {
        return 'direct_abdm';
    }

    // -------------------------------------------------------------------------
    // ABHA
    // -------------------------------------------------------------------------

    public function validateAbha(string $abhaId, array $fullPayload = []): array
    {
        // TODO: Obtain M3 access token, then call:
        //   POST {baseUrl}/v1/search/existsByHealthId
        //   Body: { "healthId": $abhaId }
        // Reference: ABDM M3 ABHA search API
        throw new \RuntimeException(
            'DirectAbdmConnector::validateAbha() not implemented. '
            . 'TODO: POST /v1/search/existsByHealthId with ABHA ID.'
        );
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
        // TODO: Obtain M3 access token, then call:
        //   POST {baseUrl}/v0.5/consent-requests/init
        //   Body: ConsentRequest FHIR resource per ABDM IG
        throw new \RuntimeException(
            'DirectAbdmConnector::requestConsent() not implemented. '
            . 'TODO: POST /v0.5/consent-requests/init.'
        );
    }

    // -------------------------------------------------------------------------
    // Health Record Sharing
    // -------------------------------------------------------------------------

    public function sharePrescriptionBundle(array $payload, string $entityId = ''): array
    {
        // TODO: Encrypt FHIR bundle using HIP public key, then call:
        //   POST {baseUrl}/v0.5/health-information/hip/on-request
        // Reference: ABDM IG — HIP-initiated record push
        throw new \RuntimeException(
            'DirectAbdmConnector::sharePrescriptionBundle() not implemented. '
            . 'TODO: Encrypt bundle and POST /v0.5/health-information/hip/on-request.'
        );
    }

    public function shareIpdDischargeBundle(array $payload, string $entityId = ''): array
    {
        // TODO: Same flow as sharePrescriptionBundle but bundle_type = DischargeSummaryBundle
        throw new \RuntimeException(
            'DirectAbdmConnector::shareIpdDischargeBundle() not implemented. '
            . 'TODO: Encrypt bundle and POST /v0.5/health-information/hip/on-request.'
        );
    }

    public function shareDiagnosisReportBundle(array $payload, string $entityId = ''): array
    {
        // TODO: Same flow as sharePrescriptionBundle but bundle_type = DiagnosticReportBundle
        throw new \RuntimeException(
            'DirectAbdmConnector::shareDiagnosisReportBundle() not implemented. '
            . 'TODO: Encrypt bundle and POST /v0.5/health-information/hip/on-request.'
        );
    }

    // -------------------------------------------------------------------------
    // Scan & Share
    // -------------------------------------------------------------------------

    public function scanShareLookup(string $qrPayload, string $abhaIdHint = '', array $fullPayload = []): array
    {
        // TODO: Decode QR token, then call:
        //   POST {baseUrl}/v1.0/hip/patient/scan-share/profile
        //   Body: { "token": $qrPayload }
        throw new \RuntimeException(
            'DirectAbdmConnector::scanShareLookup() not implemented. '
            . 'TODO: POST /v1.0/hip/patient/scan-share/profile with decoded QR token.'
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
        // TODO: Obtain NHCX access token (separate from ABDM), then call:
        //   POST {nhcxBaseUrl}/v0.5/nhcx/claim
        //   Body: FHIR Claim bundle
        throw new \RuntimeException(
            'DirectAbdmConnector::nhcxClaimCreate() not implemented. '
            . 'TODO: POST NHCX /v0.5/nhcx/claim with FHIR Claim bundle.'
        );
    }

    public function nhcxClaimStatusRequest(
        int    $documentId,
        string $externalRef,
        string $currentStatus,
        array  $fullPayload = []
    ): array {
        // TODO: POST {nhcxBaseUrl}/v0.5/nhcx/claim/status with external_ref
        throw new \RuntimeException(
            'DirectAbdmConnector::nhcxClaimStatusRequest() not implemented. '
            . 'TODO: POST NHCX /v0.5/nhcx/claim/status.'
        );
    }
}
