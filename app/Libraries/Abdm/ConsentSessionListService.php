<?php

namespace App\Libraries\Abdm;

/**
 * Builds the global "ABHA Patient Request List" (all patients' ABDM consent
 * request history, most recent first) shown from the left-nav ABDM panel.
 *
 * The session-grouping / detail-computation logic here mirrors
 * App\Controllers\Patient::getAbdmConsentRequestsList() (Phase 3, per-patient
 * Consent Request History), just applied across every patient's ABHA address
 * instead of a single one.
 */
class ConsentSessionListService
{
    private \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * @param array<string, mixed> $filters optional keys: 'q' (search text), 'status'
     */
    public function getGlobalConsentRequestsList(array $filters = [], int $limit = 300): array
    {
        @ini_set('memory_limit', '256M');

        try {
            if (! $this->db->tableExists('abdm_hiu_workflows') || ! $this->db->tableExists('patient_master')) {
                return ['ok' => 1, 'requests' => []];
            }

            $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
            if ($fields === []) {
                return ['ok' => 1, 'requests' => []];
            }

            $candidateColumns = [
                'id', 'operation', 'workflow_state', 'status', 'request_id', 'consent_id',
                'hfr_id', 'abha_address', 'created_at', 'updated_at', 'completed_at',
                'expired_at', 'revoked_at', 'last_error', 'http_code',
                'abdm_consent_request_id', 'abdm_consent_artifact_id', 'gateway_request_id',
            ];
            $select = [];
            foreach ($candidateColumns as $col) {
                if (in_array($col, $fields, true)) {
                    $select[] = $col;
                }
            }
            if ($select === []) {
                $select[] = 'id';
            }

            // Exclude huge FHIR bundles and decrypted base64 document attachments
            // in data_fetch and hi_data_push_callback rows from being buffered into PHP memory.
            if (in_array('request_json', $fields, true)) {
                $select[] = "(CASE WHEN operation IN ('data_fetch', 'hi_data_push_callback', 'DATA_FETCH', 'HI_DATA_PUSH_CALLBACK') THEN NULL ELSE request_json END) AS request_json";
            }
            if (in_array('response_json', $fields, true)) {
                $select[] = "(CASE WHEN operation IN ('data_fetch', 'hi_data_push_callback', 'DATA_FETCH', 'HI_DATA_PUSH_CALLBACK') THEN NULL ELSE response_json END) AS response_json";
            }

            $selectSql = implode(', ', $select);

            $rows = $this->db->table('abdm_hiu_workflows')
                ->select($selectSql)
                ->whereIn('operation', [
                    'consent_request',
                    'consent_status',
                    'consent_reconcile',
                    'data_fetch',
                    'consent_callback',
                    'hi_on_request_callback',
                    'hi_data_push_callback',
                    'CONSENT_REQUEST',
                    'CONSENT_STATUS',
                    'CONSENT_RECONCILE',
                    'DATA_FETCH',
                    'CONSENT_CALLBACK',
                    'HI_ON_REQUEST_CALLBACK',
                    'HI_DATA_PUSH_CALLBACK',
                ])
                ->orderBy('id', 'DESC')
                ->get(2000)
                ->getResultArray();

            if ($rows === []) {
                return ['ok' => 1, 'requests' => []];
            }

            // Group rows by abha_address, preserving the DESC-by-id order within each group.
            $byAddress = [];
            foreach ($rows as $row) {
                $addr = trim((string) ($row['abha_address'] ?? ''));
                if ($addr === '') {
                    continue;
                }
                $byAddress[$addr][] = $row;
            }

            if ($byAddress === []) {
                return ['ok' => 1, 'requests' => []];
            }

            $patientsByAddress = $this->lookupPatientsByAbhaAddress(array_keys($byAddress));

            $statusFilter = strtoupper(trim((string) ($filters['status'] ?? '')));
            $q = strtolower(trim((string) ($filters['q'] ?? '')));

            $out = [];
            foreach ($byAddress as $addr => $addrRows) {
                $patientInfo = $patientsByAddress[$addr] ?? [
                    'patient_id' => 0,
                    'patient_code' => '',
                    'patient_name' => '',
                    'mobile' => '',
                ];

                $sessions = $this->groupWorkflowRowsIntoSessions($addrRows);
                foreach ($sessions as $sessionRows) {
                    $detail = $this->computeConsentSessionDetail($sessionRows, $addr);
                    if ((int) ($detail['ok'] ?? 0) !== 1) {
                        continue;
                    }
                    $consent = $detail['consent'];

                    if ($statusFilter !== '' && strtoupper((string) ($consent['status'] ?? '')) !== $statusFilter) {
                        continue;
                    }
                    if ($q !== '') {
                        $haystack = strtolower(
                            ($patientInfo['patient_name'] ?? '') . ' ' . ($patientInfo['patient_code'] ?? '') . ' '
                            . ($patientInfo['mobile'] ?? '') . ' ' . $addr
                        );
                        if (strpos($haystack, $q) === false) {
                            continue;
                        }
                    }

                    $out[] = array_merge($patientInfo, $consent);
                }
            }

            usort($out, function ($a, $b) {
                return strcmp((string) ($b['requested_on'] ?? ''), (string) ($a['requested_on'] ?? ''));
            });

            if ($limit > 0 && count($out) > $limit) {
                $out = array_slice($out, 0, $limit);
            }

            return ['ok' => 1, 'requests' => $out];
        } catch (\Throwable $e) {
            log_message('error', 'ConsentSessionListService::getGlobalConsentRequestsList failure: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'ok' => 0,
                'error' => 'Failed to load consent requests: ' . $e->getMessage(),
                'requests' => [],
            ];
        }
    }

    /**
     * @param array<int, string> $abhaAddresses
     * @return array<string, array<string, mixed>> keyed by abha_address
     */
    private function lookupPatientsByAbhaAddress(array $abhaAddresses): array
    {
        try {
            if ($abhaAddresses === [] || ! $this->db->tableExists('patient_master')) {
                return [];
            }

            $fields = $this->db->getFieldNames('patient_master') ?? [];
            $idCol = $this->resolveExistingColumn($fields, ['id']);
            $uhidCol = $this->resolveExistingColumn($fields, ['p_code', 'uhid', 'uhid_no', 'patient_code', 'patient_id']);
            $nameCol = $this->resolveExistingColumn($fields, ['p_fname', 'patient_name', 'name']);
            $lastNameCol = $this->resolveExistingColumn($fields, ['p_lname', 'last_name']);
            $mobileCol = $this->resolveExistingColumn($fields, ['mphone1', 'mphone2', 'p_mobile', 'mobile', 'phone', 'contact_no', 'phone1']);
            $abhaAddressCol = $this->resolveExistingColumn($fields, ['abha_address', 'abha_addr']);

            if ($idCol === null || $abhaAddressCol === null) {
                return [];
            }

            $selectParts = [$idCol . ' AS id', $abhaAddressCol . ' AS abha_address'];
            if ($uhidCol !== null) {
                $selectParts[] = $uhidCol . ' AS uhid';
            }
            if ($nameCol !== null) {
                $selectParts[] = $nameCol . ' AS name';
            }
            if ($lastNameCol !== null) {
                $selectParts[] = $lastNameCol . ' AS last_name';
            }
            if ($mobileCol !== null) {
                $selectParts[] = $mobileCol . ' AS mobile';
            }

            $rows = $this->db->table('patient_master')
                ->select(implode(', ', $selectParts), false)
                ->whereIn($abhaAddressCol, $abhaAddresses)
                ->get()
                ->getResultArray();

            $out = [];
            foreach ($rows as $p) {
                $addr = trim((string) ($p['abha_address'] ?? ''));
                if ($addr === '') {
                    continue;
                }
                $firstName = trim((string) ($p['name'] ?? ''));
                $lastName = trim((string) ($p['last_name'] ?? ''));
                if ($lastName === '0') {
                    $lastName = '';
                }
                $fullName = trim($firstName . ' ' . $lastName);
                $out[$addr] = [
                    'patient_id' => (int) ($p['id'] ?? 0),
                    'patient_code' => trim((string) ($p['uhid'] ?? '')),
                    'patient_name' => $fullName !== '' ? $fullName : $firstName,
                    'mobile' => trim((string) ($p['mobile'] ?? '')),
                ];
            }

            return $out;
        } catch (\Throwable $e) {
            log_message('warning', 'ConsentSessionListService::lookupPatientsByAbhaAddress error: ' . $e->getMessage());
            return [];
        }
    }

    private function resolveExistingColumn(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Splits a DESC-by-id set of workflow rows into distinct consent request
     * "sessions", correlating rows by their stable identifiers (request_id /
     * abdm_consent_request_id / consent_id) rather than simple chronological proximity.
     *
     * @param array<int, array<string, mixed>> $rows rows ordered DESC by id
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupWorkflowRowsIntoSessions(array $rows): array
    {
        $chronological = array_reverse($rows);

        $sessions = [];
        $order = [];
        $abdmIdToAnchor = [];
        $requestIdToAnchor = [];
        $anchorHasAbdmId = [];
        $openAnchor = null;

        foreach ($chronological as $row) {
            $operation = strtoupper(trim((string) ($row['operation'] ?? '')));
            $rowRequestId = trim((string) ($row['request_id'] ?? ''));
            $rowAbdmConsentRequestId = trim((string) ($row['abdm_consent_request_id'] ?? ''));
            $rowConsentId = trim((string) ($row['consent_id'] ?? ''));

            if ($operation === 'CONSENT_REQUEST') {
                $anchorKey = 'anchor_' . count($order);
                $order[] = $anchorKey;
                $sessions[$anchorKey] = [$row];
                if ($rowRequestId !== '') {
                    $requestIdToAnchor[$rowRequestId] = $anchorKey;
                }
                if ($rowAbdmConsentRequestId !== '') {
                    $abdmIdToAnchor[$rowAbdmConsentRequestId] = $anchorKey;
                    $requestIdToAnchor[$rowAbdmConsentRequestId] = $anchorKey;
                    $anchorHasAbdmId[$anchorKey] = true;
                }
                if ($rowConsentId !== '') {
                    $abdmIdToAnchor[$rowConsentId] = $anchorKey;
                    $requestIdToAnchor[$rowConsentId] = $anchorKey;
                }
                $openAnchor = $anchorKey;
                continue;
            }

            $anchorKey = null;
            if ($rowAbdmConsentRequestId !== '' && isset($abdmIdToAnchor[$rowAbdmConsentRequestId])) {
                $anchorKey = $abdmIdToAnchor[$rowAbdmConsentRequestId];
            } elseif ($rowRequestId !== '' && isset($requestIdToAnchor[$rowRequestId])) {
                $anchorKey = $requestIdToAnchor[$rowRequestId];
            } elseif ($rowConsentId !== '' && isset($requestIdToAnchor[$rowConsentId])) {
                $anchorKey = $requestIdToAnchor[$rowConsentId];
            } elseif ($openAnchor !== null && ($rowAbdmConsentRequestId === '' || empty($anchorHasAbdmId[$openAnchor]))) {
                $anchorKey = $openAnchor;
            }

            if ($anchorKey === null) {
                $anchorKey = 'orphan_' . count($order);
                $order[] = $anchorKey;
                $sessions[$anchorKey] = [];
            }

            $sessions[$anchorKey][] = $row;

            if ($anchorKey === $openAnchor) {
                $openAnchor = null;
            }
            if ($rowAbdmConsentRequestId !== '') {
                $abdmIdToAnchor[$rowAbdmConsentRequestId] = $anchorKey;
                $anchorHasAbdmId[$anchorKey] = true;
            }
            if ($rowRequestId !== '') {
                $requestIdToAnchor[$rowRequestId] = $anchorKey;
            }
            if ($rowConsentId !== '') {
                $requestIdToAnchor[$rowConsentId] = $anchorKey;
            }
        }

        $result = [];
        foreach ($order as $anchorKey) {
            if (! empty($sessions[$anchorKey])) {
                $result[] = $sessions[$anchorKey];
            }
        }

        return $result;
    }

    /**
     * Extracts a clean scalar string from mixed input (preventing Array-to-string conversion notices).
     *
     * @param mixed $val
     */
    private function extractScalarString($val): string
    {
        if (is_scalar($val)) {
            return trim((string) $val);
        }

        return '';
    }

    /**
     * Computes the requested-vs-granted Health Information Type breakdown for
     * a single consent request "session" (one CONSENT_REQUEST row plus its
     * subsequent status/reconcile/data_fetch/callback rows).
     *
     * @param array<int, array<string, mixed>> $rows the session's rows (any order)
     */
    private function computeConsentSessionDetail(array $rows, string $abhaAddress): array
    {
        if ($rows === []) {
            return ['ok' => 0, 'error' => 'No ABDM consent activity found.'];
        }

        $best = null;
        $bestPriority = -1;
        $bestDecoded = [];

        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['response_json'] ?? ''), true);
            if (! is_array($decoded)) {
                $decoded = [];
            }

            $rawConsentStatus = '';
            if (isset($decoded['consent']) && is_array($decoded['consent'])) {
                $rawConsentStatus = $this->extractScalarString(
                    $decoded['consent']['status']
                    ?? $decoded['consent']['consent_status']
                    ?? $decoded['consent']['consentStatus']
                    ?? null
                );
            }
            if ($rawConsentStatus === '' && isset($decoded['consentDetail']) && is_array($decoded['consentDetail'])) {
                $rawConsentStatus = $this->extractScalarString($decoded['consentDetail']['status'] ?? null);
            }
            if ($rawConsentStatus === '' && isset($decoded['data']['consent']) && is_array($decoded['data']['consent'])) {
                $rawConsentStatus = $this->extractScalarString($decoded['data']['consent']['status'] ?? null);
            }
            if ($rawConsentStatus === '') {
                $rawConsentStatus = $this->extractScalarString(
                    $decoded['consent_status'] ?? $decoded['consentStatus'] ?? null
                );
            }
            if ($rawConsentStatus === '' || in_array(strtolower($rawConsentStatus), ['success', 'ok', 'failed', 'error', 'status_checked'], true)) {
                $topStatus = $this->extractScalarString($decoded['status'] ?? null);
                if (! in_array(strtolower($topStatus), ['success', 'ok', 'failed', 'error', 'status_checked', '1', '0', ''], true)) {
                    $rawConsentStatus = $topStatus;
                }
            }
            $rawConsentStatus = strtoupper($rawConsentStatus);
            $operation = strtoupper(trim((string) ($row['operation'] ?? '')));
            $status = strtoupper(trim((string) ($row['status'] ?? '')));
            $state = strtoupper(trim((string) ($row['workflow_state'] ?? '')));

            $phase = 'REQUESTED';
            $priority = 120;

            if (($operation === 'DATA_FETCH' || $operation === 'HI_DATA_PUSH_CALLBACK') && $status === 'SUCCESS' && $state === 'DATA_RECEIVED') {
                $phase = 'COMPLETED';
                $priority = 500;
            } elseif (($operation === 'DATA_FETCH' || $operation === 'HI_DATA_PUSH_CALLBACK') && $status === 'SUCCESS') {
                $phase = 'COMPLETED';
                $priority = 490;
            } elseif (in_array($rawConsentStatus, ['GRANTED', 'APPROVED', 'ACTIVE'], true)) {
                $phase = 'GRANTED';
                $priority = 430;
            } elseif ($rawConsentStatus === 'REVOKED') {
                $phase = 'REVOKED';
                $priority = 320;
            } elseif ($rawConsentStatus === 'EXPIRED') {
                $phase = 'EXPIRED';
                $priority = 310;
            } elseif ($rawConsentStatus === 'DENIED') {
                $phase = 'DENIED';
                $priority = 300;
            } elseif ($state === 'DATA_RECEIVED') {
                $phase = 'COMPLETED';
                $priority = 480;
            } elseif ($state === 'GRANTED') {
                $phase = 'GRANTED';
                $priority = 420;
            } elseif ($state === 'REVOKED') {
                $phase = 'REVOKED';
                $priority = 300;
            } elseif ($state === 'EXPIRED') {
                $phase = 'EXPIRED';
                $priority = 290;
            } elseif ($status === 'FAILED' && $operation === 'CONSENT_REQUEST') {
                $phase = 'FAILED';
                $priority = 260;
            } elseif ($status === 'FAILED') {
                $phase = 'REQUESTED';
                $priority = 190;
            } elseif (in_array($state, ['REQUESTED', 'PENDING', 'STATUS_CHECKED'], true)) {
                $phase = 'REQUESTED';
                $priority = 180;
            }

            if ($best === null || $priority > $bestPriority) {
                $best = $row;
                $best['_phase'] = $phase;
                $bestPriority = $priority;
                $bestDecoded = $decoded;
            }
        }

        if ($best === null) {
            return ['ok' => 0, 'error' => 'No ABDM consent activity found.'];
        }

        $phase = (string) $best['_phase'];

        $terminalPhase = '';
        $terminalRowId = 0;
        foreach ($rows as $row) {
            $rowDecoded = json_decode((string) ($row['response_json'] ?? ''), true);
            if (! is_array($rowDecoded)) {
                $rowDecoded = [];
            }
            $rowStatus = '';
            if (isset($rowDecoded['consent']) && is_array($rowDecoded['consent'])) {
                $rowStatus = $this->extractScalarString(
                    $rowDecoded['consent']['status']
                    ?? $rowDecoded['consent']['consent_status']
                    ?? null
                );
            }
            if ($rowStatus === '' && isset($rowDecoded['consentDetail']) && is_array($rowDecoded['consentDetail'])) {
                $rowStatus = $this->extractScalarString($rowDecoded['consentDetail']['status'] ?? null);
            }
            if ($rowStatus === '' && isset($rowDecoded['data']['consent']) && is_array($rowDecoded['data']['consent'])) {
                $rowStatus = $this->extractScalarString($rowDecoded['data']['consent']['status'] ?? null);
            }
            if ($rowStatus === '') {
                $rowStatus = $this->extractScalarString(
                    $rowDecoded['consent_status'] ?? $rowDecoded['consentStatus'] ?? null
                );
            }
            if ($rowStatus === '' || in_array(strtolower($rowStatus), ['success', 'ok', 'failed', 'error', 'status_checked'], true)) {
                $rowState = strtoupper(trim((string) ($row['workflow_state'] ?? '')));
                if (in_array($rowState, ['REVOKED', 'EXPIRED', 'DENIED'], true)) {
                    $rowStatus = $rowState;
                }
            }
            $rowStatus = strtoupper($rowStatus);
            if (! in_array($rowStatus, ['REVOKED', 'EXPIRED', 'DENIED'], true)) {
                continue;
            }
            $rowId = (int) ($row['id'] ?? 0);
            if ($rowId >= $terminalRowId) {
                $terminalPhase = $rowStatus;
                $terminalRowId = $rowId;
            }
        }
        if ($terminalPhase !== '') {
            $phase = $terminalPhase;
        }

        $consentId = '';
        $consentRequestId = '';
        foreach ($rows as $row) {
            $rowDecoded = json_decode((string) ($row['response_json'] ?? ''), true);
            if (! is_array($rowDecoded)) {
                $rowDecoded = [];
            }

            $rowConsentId = trim((string) (
                $row['abdm_consent_artifact_id']
                ?? $row['consent_id']
                ?? $this->extractScalarString($rowDecoded['consent_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent']['id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent']['consent_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent']['consentId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentDetail']['id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentDetail']['consentId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent_artifact_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent_artefact_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentArtifactId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentArtefactId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['data']['consent']['id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['data']['consent_id'] ?? null)
                ?? ''
            ));
            if ($rowConsentId !== '' && ! preg_match('/^REQ-/i', $rowConsentId)) {
                $consentId = $rowConsentId;
            }

            $rowConsentRequestId = trim((string) (
                $row['abdm_consent_request_id']
                ?? $this->extractScalarString($rowDecoded['abdm_consent_request_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent_request_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentRequestId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent']['consent_request_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consent']['consentRequestId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentDetail']['consent_request_id'] ?? null)
                ?? $this->extractScalarString($rowDecoded['consentDetail']['consentRequestId'] ?? null)
                ?? $this->extractScalarString($rowDecoded['data']['consent_request_id'] ?? null)
                ?? ''
            ));
            if ($rowConsentRequestId !== '' && ! preg_match('/^REQ-/i', $rowConsentRequestId)) {
                $consentRequestId = $rowConsentRequestId;
            }
        }
        if ($consentId === '' && $consentRequestId !== '') {
            $consentId = $consentRequestId;
        }

        // The session should start with its own CONSENT_REQUEST row.
        $consentRequestRow = null;
        foreach ($rows as $row) {
            if (strtoupper((string) ($row['operation'] ?? '')) === 'CONSENT_REQUEST') {
                $consentRequestRow = $row;
                break;
            }
        }

        $anchorState = strtoupper(trim((string) (is_array($consentRequestRow) ? ($consentRequestRow['workflow_state'] ?? '') : '')));
        if (in_array($anchorState, ['EXPIRED', 'DENIED', 'REVOKED'], true)) {
            $phase = $anchorState;
        }

        $requestedHiTypes = [];
        $requestedOn = '';
        $purpose = '';
        $validFrom = '';
        $validTo = '';
        $eraseAt = '';
        $requestedBy = '';
        $hfrId = trim((string) ($best['hfr_id'] ?? ''));

        if (is_array($consentRequestRow)) {
            $reqPayload = json_decode((string) ($consentRequestRow['request_json'] ?? ''), true);
            if (! is_array($reqPayload)) {
                $reqPayload = [];
            }
            $consentBlock = (array) ($reqPayload['consent'] ?? []);
            $requestedHiTypes = $this->normalizeHiTypesList($consentBlock['hiTypes'] ?? $consentBlock['hi_types'] ?? []);
            $requestedOn = trim((string) ($consentRequestRow['created_at'] ?? ''));
            $purpose = $this->extractScalarString($consentBlock['purpose']['text'] ?? $consentBlock['purpose']['code'] ?? null);
            $validFrom = $this->extractScalarString(
                $consentBlock['permission']['dateRange']['from']
                ?? $consentBlock['permission']['date_range']['from']
                ?? null
            );
            $validTo = $this->extractScalarString(
                $consentBlock['permission']['dateRange']['to']
                ?? $consentBlock['permission']['date_range']['to']
                ?? null
            );
            $eraseAt = $this->extractScalarString(
                $consentBlock['permission']['dataEraseAt']
                ?? $consentBlock['permission']['data_erase_at']
                ?? null
            );
            $requestedBy = $this->extractScalarString($consentBlock['requester']['name'] ?? null);
            if ($hfrId === '') {
                $hfrId = trim((string) ($consentRequestRow['hfr_id'] ?? ''));
            }
        }

        // Fallback scanning across all session rows for missing metadata (dates, purpose, requestedBy)
        foreach ($rows as $row) {
            $rowReq = json_decode((string) ($row['request_json'] ?? ''), true);
            $rowResp = json_decode((string) ($row['response_json'] ?? ''), true);
            $containers = [
                is_array($rowReq) ? ($rowReq['consent'] ?? null) : null,
                is_array($rowReq) ? ($rowReq['consentDetail'] ?? null) : null,
                is_array($rowReq) ? $rowReq : null,
                is_array($rowResp) ? ($rowResp['consent'] ?? null) : null,
                is_array($rowResp) ? ($rowResp['consentDetail'] ?? null) : null,
                is_array($rowResp) ? $rowResp : null,
            ];

            foreach ($containers as $c) {
                if (! is_array($c)) {
                    continue;
                }
                if ($validFrom === '') {
                    $validFrom = $this->extractScalarString(
                        $c['permission']['dateRange']['from']
                        ?? $c['permission']['date_range']['from']
                        ?? $c['date_range']['from']
                        ?? $c['dateRange']['from']
                        ?? null
                    );
                }
                if ($validTo === '') {
                    $validTo = $this->extractScalarString(
                        $c['permission']['dateRange']['to']
                        ?? $c['permission']['date_range']['to']
                        ?? $c['date_range']['to']
                        ?? $c['dateRange']['to']
                        ?? null
                    );
                }
                if ($eraseAt === '') {
                    $eraseAt = $this->extractScalarString(
                        $c['permission']['dataEraseAt']
                        ?? $c['permission']['data_erase_at']
                        ?? (is_array($c['expiry'] ?? null) ? ($c['expiry']['date'] ?? null) : ($c['expiry'] ?? null))
                        ?? $c['dataEraseAt']
                        ?? $c['data_erase_at']
                        ?? null
                    );
                }
                if ($purpose === '') {
                    $purpose = $this->extractScalarString(
                        $c['purpose']['text']
                        ?? $c['purpose']['code']
                        ?? $c['purpose']
                        ?? null
                    );
                }
                if ($requestedBy === '') {
                    $requestedBy = $this->extractScalarString(
                        $c['requester']['name']
                        ?? $c['requester']
                        ?? null
                    );
                }
                if ($requestedOn === '') {
                    $requestedOn = trim((string) ($row['created_at'] ?? ''));
                }
            }
        }

        $grantedHiTypes = [];
        $grantedOn = '';
        foreach ($rows as $row) {
            $decoded = json_decode((string) ($row['response_json'] ?? ''), true);
            if (! is_array($decoded)) {
                continue;
            }
            $rowHiTypes = $this->normalizeHiTypesList($decoded['hi_types'] ?? $decoded['consent']['hi_types'] ?? []);
            if ($rowHiTypes !== []) {
                $grantedHiTypes = $rowHiTypes;
                $grantedOn = trim((string) (
                    $this->extractScalarString($decoded['granted_at'] ?? null)
                    ?: ($row['updated_at'] ?? '')
                ));
                break;
            }
        }

        $revokedOn = trim((string) ($best['revoked_at'] ?? ''));
        $expiredOn = trim((string) ($best['expired_at'] ?? ''));
        if (in_array($phase, ['GRANTED', 'COMPLETED'], true) && $grantedOn === '') {
            $grantedOn = trim((string) (
                $this->extractScalarString($bestDecoded['granted_at'] ?? null)
                ?: ($best['updated_at'] ?? '')
            ));
        }

        $items = [];
        $typesForItems = $requestedHiTypes !== [] ? $requestedHiTypes : $grantedHiTypes;
        foreach ($typesForItems as $hiType) {
            $itemStatus = 'REQUESTED';
            $itemTimestamp = $requestedOn;

            if ($phase === 'REVOKED') {
                $itemStatus = 'REVOKED';
                $itemTimestamp = $revokedOn;
            } elseif ($phase === 'EXPIRED') {
                $itemStatus = 'EXPIRED';
                $itemTimestamp = $expiredOn;
            } elseif (in_array($phase, ['GRANTED', 'COMPLETED'], true)) {
                if (in_array($hiType, $grantedHiTypes, true)) {
                    $itemStatus = 'GRANTED';
                    $itemTimestamp = $grantedOn;
                } else {
                    $itemStatus = 'DENIED';
                    $itemTimestamp = $grantedOn;
                }
            } elseif ($phase === 'FAILED') {
                $itemStatus = 'FAILED';
                $itemTimestamp = trim((string) ($best['updated_at'] ?? ''));
            }

            $items[] = [
                'document_name' => $hiType,
                'permission' => 'VIEW',
                'status' => $itemStatus,
                'timestamp' => $itemTimestamp,
            ];
        }

        return [
            'ok' => 1,
            'consent' => [
                'consent_id' => $consentId,
                'consent_request_id' => $consentRequestId,
                'abha_address' => $abhaAddress,
                'status' => $phase,
                'purpose' => $purpose !== '' ? $purpose : 'Care Management',
                'requested_hi_types' => $requestedHiTypes,
                'granted_hi_types' => $grantedHiTypes,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'erase_at' => $eraseAt,
                'requested_on' => $requestedOn,
                'granted_on' => $grantedOn,
                'revoked_on' => $revokedOn,
                'expired_on' => $expiredOn,
                'hfr_id' => $hfrId,
                'requested_by' => $requestedBy !== '' ? $requestedBy : 'HMS',
                'items' => $items,
            ],
        ];
    }

    /**
     * Normalizes a hiTypes value (JSON string, array, or single string) into a
     * clean, de-duplicated string array.
     *
     * @param mixed $value
     */
    private function normalizeHiTypesList($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [$value];
        }
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $v) {
            if (is_array($v)) {
                $v = $v['type'] ?? $v['hiType'] ?? $v['name'] ?? $v['code'] ?? null;
            }
            if (! is_scalar($v)) {
                continue;
            }
            $s = trim((string) $v);
            if ($s !== '' && ! in_array($s, $out, true)) {
                $out[] = $s;
            }
        }

        return $out;
    }
}
