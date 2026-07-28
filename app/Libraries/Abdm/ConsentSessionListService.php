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
        if (! $this->db->tableExists('abdm_hiu_workflows') || ! $this->db->tableExists('patient_master')) {
            return ['ok' => 1, 'requests' => []];
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
        $select = ['id', 'operation', 'workflow_state', 'status', 'request_id', 'consent_id', 'hfr_id', 'abha_address', 'created_at', 'updated_at', 'completed_at', 'expired_at', 'revoked_at', 'last_error', 'http_code', 'request_json', 'response_json'];
        foreach (['abdm_consent_request_id', 'abdm_consent_artifact_id', 'gateway_request_id'] as $optionalField) {
            if (in_array($optionalField, $fields, true)) {
                $select[] = $optionalField;
            }
        }

        $rows = $this->db->table('abdm_hiu_workflows')
            ->select(implode(', ', $select))
            ->whereIn('operation', [
                'consent_request',
                'consent_status',
                'consent_reconcile',
                'data_fetch',
                'consent_callback',
                'hi_on_request_callback',
                'hi_data_push_callback',
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

                if ($statusFilter !== '' && strtoupper((string) $consent['status']) !== $statusFilter) {
                    continue;
                }
                if ($q !== '') {
                    $haystack = strtolower(
                        $patientInfo['patient_name'] . ' ' . $patientInfo['patient_code'] . ' '
                        . $patientInfo['mobile'] . ' ' . $addr
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
    }

    /**
     * @param array<int, string> $abhaAddresses
     * @return array<string, array<string, mixed>> keyed by abha_address
     */
    private function lookupPatientsByAbhaAddress(array $abhaAddresses): array
    {
        $fields = $this->db->getFieldNames('patient_master') ?? [];
        $idCol = $this->resolveExistingColumn($fields, ['id']);
        $uhidCol = $this->resolveExistingColumn($fields, ['p_code', 'uhid', 'uhid_no', 'patient_code', 'patient_id']);
        $nameCol = $this->resolveExistingColumn($fields, ['p_fname', 'patient_name', 'name']);
        $mobileCol = $this->resolveExistingColumn($fields, ['p_mobile', 'mobile', 'phone', 'contact_no']);
        $abhaAddressCol = $this->resolveExistingColumn($fields, ['abha_address', 'abha_addr']);

        if ($idCol === null || $abhaAddressCol === null || $abhaAddresses === []) {
            return [];
        }

        $builder = $this->db->table('patient_master')
            ->select('' . $idCol . ' AS id, ' . $abhaAddressCol . ' AS abha_address', false);
        if ($uhidCol !== null) {
            $builder->select($uhidCol . ' AS uhid', false);
        }
        if ($nameCol !== null) {
            $builder->select($nameCol . ' AS name', false);
        }
        if ($mobileCol !== null) {
            $builder->select($mobileCol . ' AS mobile', false);
        }
        $builder->whereIn($abhaAddressCol, $abhaAddresses);

        $out = [];
        foreach ($builder->get()->getResultArray() as $p) {
            $addr = trim((string) ($p['abha_address'] ?? ''));
            if ($addr === '') {
                continue;
            }
            $out[$addr] = [
                'patient_id' => (int) ($p['id'] ?? 0),
                'patient_code' => trim((string) ($p['uhid'] ?? '')),
                'patient_name' => trim((string) ($p['name'] ?? '')),
                'mobile' => trim((string) ($p['mobile'] ?? '')),
            ];
        }

        return $out;
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
     * Splits a DESC-by-id set of workflow rows (all belonging to one ABHA
     * address) into distinct consent request "sessions" — each
     * CONSENT_REQUEST row starts a new session, and every subsequent row
     * belongs to that session until the next CONSENT_REQUEST row appears.
     * Returns sessions in chronological order (oldest session first).
     *
     * @param array<int, array<string, mixed>> $rows rows ordered DESC by id
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function groupWorkflowRowsIntoSessions(array $rows): array
    {
        $chronological = array_reverse($rows);

        $sessions = [];
        $current = [];
        foreach ($chronological as $row) {
            $operation = strtoupper(trim((string) ($row['operation'] ?? '')));
            if ($operation === 'CONSENT_REQUEST' && $current !== []) {
                $sessions[] = $current;
                $current = [];
            }
            $current[] = $row;
        }
        if ($current !== []) {
            $sessions[] = $current;
        }

        return $sessions;
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

            $rawConsentStatus = strtoupper(trim((string) (
                $decoded['consent']['status']
                ?? $decoded['consent_status']
                ?? $decoded['status']
                ?? $decoded['data']['consent']['status']
                ?? ''
            )));
            $operation = strtoupper(trim((string) ($row['operation'] ?? '')));
            $status = strtoupper(trim((string) ($row['status'] ?? '')));
            $state = strtoupper(trim((string) ($row['workflow_state'] ?? '')));

            $phase = 'REQUESTED';
            $priority = 120;

            if (($operation === 'DATA_FETCH' || $operation === 'HI_DATA_PUSH_CALLBACK') && $status === 'SUCCESS') {
                $phase = 'COMPLETED';
                $priority = 500;
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
        $consentId = trim((string) ($best['abdm_consent_artifact_id'] ?? $best['consent_id'] ?? $bestDecoded['consent_id'] ?? $bestDecoded['consentId'] ?? ''));
        $consentRequestId = trim((string) ($best['abdm_consent_request_id'] ?? $bestDecoded['abdm_consent_request_id'] ?? $bestDecoded['consent_request_id'] ?? $bestDecoded['consentRequestId'] ?? ''));

        // The session should start with its own CONSENT_REQUEST row.
        $consentRequestRow = null;
        foreach ($rows as $row) {
            if (strtoupper((string) ($row['operation'] ?? '')) === 'CONSENT_REQUEST') {
                $consentRequestRow = $row;
                break;
            }
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
            $purpose = trim((string) ($consentBlock['purpose']['text'] ?? $consentBlock['purpose']['code'] ?? ''));
            $validFrom = trim((string) ($consentBlock['permission']['dateRange']['from'] ?? ''));
            $validTo = trim((string) ($consentBlock['permission']['dateRange']['to'] ?? ''));
            $eraseAt = trim((string) ($consentBlock['permission']['dataEraseAt'] ?? ''));
            $requestedBy = trim((string) ($consentBlock['requester']['name'] ?? ''));
            if ($hfrId === '') {
                $hfrId = trim((string) ($consentRequestRow['hfr_id'] ?? ''));
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
                $grantedOn = trim((string) ($decoded['granted_at'] ?? $row['updated_at'] ?? ''));
                break;
            }
        }

        $revokedOn = trim((string) ($best['revoked_at'] ?? ''));
        $expiredOn = trim((string) ($best['expired_at'] ?? ''));
        if (in_array($phase, ['GRANTED', 'COMPLETED'], true) && $grantedOn === '') {
            $grantedOn = trim((string) ($bestDecoded['granted_at'] ?? $best['updated_at'] ?? ''));
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
            $v = trim((string) $v);
            if ($v !== '' && ! in_array($v, $out, true)) {
                $out[] = $v;
            }
        }

        return $out;
    }
}
