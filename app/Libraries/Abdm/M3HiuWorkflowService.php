<?php

namespace App\Libraries\Abdm;

use CodeIgniter\I18n\Time;
use App\Libraries\Abdm\M3HiuGatewayClient;

class M3HiuWorkflowService
{
    private \CodeIgniter\Database\BaseConnection $db;
    private M3HiuGatewayClient $client;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->client = new M3HiuGatewayClient();
    }

    public function runOperation(string $operation, array $payload): array
    {
        $hasWorkflowTable = $this->db->tableExists('abdm_hiu_workflows');

        $normalized = $this->normalizePayload($payload);
        $prepared = $this->preparePayloadForOperation($operation, $normalized);
        if (($prepared['ok'] ?? 0) !== 1) {
            $failedResult = [
                'ok' => 0,
                'http_code' => (int) ($prepared['http_code'] ?? 422),
                'error_text' => (string) ($prepared['error_text'] ?? 'Validation failed'),
                'retryable' => 0,
                'request_id' => (string) ($normalized['request_id'] ?? ''),
                'transaction_id' => (string) ($normalized['transaction_id'] ?? ''),
                'consent_id' => (string) ($normalized['consent_id'] ?? ''),
                'abha_address' => (string) ($normalized['abha_address'] ?? ''),
            ];
            if ($hasWorkflowTable) {
                $this->upsertWorkflow(
                    $operation,
                    $normalized,
                    $failedResult,
                    'FAILED',
                    'failed',
                    (int) $failedResult['http_code'],
                    false
                );
                $failedResult['workflow_persisted'] = 1;
            }

            return $failedResult;
        }
        $dispatchPayload = (array) ($prepared['payload'] ?? []);

        if ($hasWorkflowTable && $operation !== 'consent_status') {
            $duplicate = $this->findDuplicate($operation, $normalized);
            if ($duplicate !== null) {
                $dupResponse = json_decode((string) ($duplicate['response_json'] ?? ''), true);
                if (is_array($dupResponse) && ! empty($dupResponse)) {
                    $dupResponse['duplicate'] = 1;
                    $dupResponse['workflow_state'] = (string) ($duplicate['workflow_state'] ?? '');
                    return $dupResponse;
                }
            }
        }

        $result = $this->dispatch($operation, $dispatchPayload);
        $httpCode = (int) ($result['http_code'] ?? 0);
        $ok = (int) ($result['ok'] ?? 0) === 1;

        $state = $this->resolveState($operation, $result);
        $status = $ok ? 'success' : 'failed';
        $retryable = (int) ($result['retryable'] ?? 0) === 1;

        if ($hasWorkflowTable) {
            $this->upsertWorkflow($operation, $dispatchPayload, $result, $state, $status, $httpCode, $retryable);
            $result['workflow_persisted'] = 1;
        } else {
            $result['workflow_persisted'] = 0;
            $result['workflow_warning'] = 'abdm_hiu_workflows table not found; operation executed without timeline persistence';
        }

        return $result;
    }

    public function retryDue(int $limit = 20): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $rows = $this->db->table('abdm_hiu_workflows')
            ->select('*')
            ->where('status', 'failed')
            ->where('is_retryable', 1)
            ->where('retry_count <', 3)
            ->groupStart()
                ->where('next_retry_at IS NULL', null, false)
                ->orWhere('next_retry_at <=', Time::now('Asia/Kolkata')->toDateTimeString())
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get($limit)
            ->getResultArray();

        $summary = ['processed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $summary['processed']++;
            $operation = trim((string) ($row['operation'] ?? ''));
            $payload = json_decode((string) ($row['request_json'] ?? ''), true);
            if (! is_array($payload) || $operation === '') {
                $summary['skipped']++;
                continue;
            }

            $result = $this->dispatch($operation, $payload);
            $ok = (int) ($result['ok'] ?? 0) === 1;
            $state = $this->resolveState($operation, $result);
            $status = $ok ? 'success' : 'failed';
            $retryable = (int) ($result['retryable'] ?? 0) === 1;

            $this->upsertWorkflow($operation, $payload, $result, $state, $status, (int) ($result['http_code'] ?? 0), $retryable, (int) ($row['id'] ?? 0));

            if ($ok) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    public function listTimeline(array $filters, int $limit = 200): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return [];
        }

        $builder = $this->db->table('abdm_hiu_workflows')->select('*')->orderBy('id', 'DESC');

        $hfrId = trim((string) ($filters['hfr_id'] ?? ''));
        if ($hfrId !== '') {
            $builder->where('hfr_id', $hfrId);
        }
        $consentId = trim((string) ($filters['consent_id'] ?? ''));
        if ($consentId !== '') {
            $builder->where('consent_id', $consentId);
        }
        $transactionId = trim((string) ($filters['transaction_id'] ?? ''));
        if ($transactionId !== '') {
            $builder->where('transaction_id', $transactionId);
        }
        $abhaAddress = trim((string) ($filters['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $builder->where('abha_address', $abhaAddress);
        }
        $from = trim((string) ($filters['date_from'] ?? ''));
        if ($from !== '') {
            $builder->where('created_at >=', $from . ' 00:00:00');
        }
        $to = trim((string) ($filters['date_to'] ?? ''));
        if ($to !== '') {
            $builder->where('created_at <=', $to . ' 23:59:59');
        }

        return $builder->get($limit)->getResultArray();
    }

    private function dispatch(string $operation, array $payload): array
    {
        return match ($operation) {
            'consent_request' => $this->client->consentRequest($payload),
            'consent_status' => $this->client->consentRequestStatus($payload),
            'consent_fetch' => $this->client->consentRequestFetch($payload),
            'hi_request' => $this->client->healthInformationRequest($payload),
            default => ['ok' => 0, 'http_code' => 400, 'error_text' => 'Unsupported operation: ' . $operation, 'retryable' => 0],
        };
    }

    private function normalizePayload(array $payload): array
    {
        $normalized = $payload;

        // Accept both legacy snake_case and guide-defined camelCase keys.
        if (! isset($normalized['request_id']) && isset($normalized['requestId'])) {
            $normalized['request_id'] = (string) $normalized['requestId'];
        }
        if (! isset($normalized['transaction_id']) && isset($normalized['transactionId'])) {
            $normalized['transaction_id'] = (string) $normalized['transactionId'];
        }
        if (! isset($normalized['consent_id']) && isset($normalized['consentId'])) {
            $normalized['consent_id'] = (string) $normalized['consentId'];
        }
        if (! isset($normalized['consent_id']) && isset($normalized['consentRequestId'])) {
            $normalized['consent_id'] = (string) $normalized['consentRequestId'];
        }
        if (! isset($normalized['abdm_consent_request_id']) && isset($normalized['consentRequestId'])) {
            $normalized['abdm_consent_request_id'] = (string) $normalized['consentRequestId'];
        }
        if (! isset($normalized['abdm_consent_artifact_id']) && isset($normalized['consentId'])) {
            $normalized['abdm_consent_artifact_id'] = (string) $normalized['consentId'];
        }

        if (isset($normalized['abha_id']) && ! isset($normalized['abha_address'])) {
            $normalized['abha_address'] = (string) $normalized['abha_id'];
        }

        foreach (['request_id', 'transaction_id', 'consent_id', 'abha_address', 'hfr_id'] as $k) {
            if (! isset($normalized[$k])) {
                $normalized[$k] = '';
            }
            $normalized[$k] = trim((string) $normalized[$k]);
        }

        $normalized['abha_address'] = $this->canonicalizeAbhaAddress(
            (string) ($normalized['abha_address'] ?? ''),
            (string) ($normalized['abha_id'] ?? '')
        );

        if ($normalized['request_id'] === '') {
            $normalized['request_id'] = 'REQ-HIU-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }

        if (! isset($normalized['requestId']) || trim((string) $normalized['requestId']) === '') {
            $normalized['requestId'] = $normalized['request_id'];
        }
        if (! isset($normalized['timestamp']) || trim((string) $normalized['timestamp']) === '') {
            $normalized['timestamp'] = gmdate('Y-m-d\TH:i:s.000\Z');
        }
        if ($normalized['transaction_id'] !== '' && (! isset($normalized['transactionId']) || trim((string) $normalized['transactionId']) === '')) {
            $normalized['transactionId'] = $normalized['transaction_id'];
        }
        if ($normalized['consent_id'] !== '' && (! isset($normalized['consentId']) || trim((string) $normalized['consentId']) === '')) {
            $normalized['consentId'] = $normalized['consent_id'];
        }

        return $normalized;
    }

    private function preparePayloadForOperation(string $operation, array $normalized): array
    {
        $clean = $this->stripInternalNoise($normalized);
        $requestId = trim((string) ($clean['requestId'] ?? $clean['request_id'] ?? ''));
        if ($requestId === '') {
            $requestId = 'REQ-HIU-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }
        $timestamp = trim((string) ($clean['timestamp'] ?? ''));
        if ($timestamp === '') {
            $timestamp = gmdate('Y-m-d\TH:i:s.000\Z');
        }

        $base = [
            'requestId' => $requestId,
            'timestamp' => $timestamp,
        ];
        $hfr = trim((string) ($clean['hfr_id'] ?? ''));
        if ($hfr !== '') {
            $base['hfr_id'] = $hfr;
        }

        if ($operation === 'consent_request') {
            $consent = $clean['consent'] ?? null;
            if (! is_array($consent) || empty($consent)) {
                $consent = $this->buildDefaultConsent($clean);
            }
            if (! is_array($consent) || empty($consent)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'Consent object is required for HIU consent request.',
                ];
            }

            $patientId = trim((string) ($consent['patient']['id'] ?? ''));
            if ($patientId === '') {
                $patientId = trim((string) ($clean['abha_address'] ?? ''));
            }
            if (! $this->isValidAbhaAddress($patientId)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'Patient ABHA address must be in address format like 91510165305101@sbx.',
                ];
            }
            if (! isset($consent['patient']) || ! is_array($consent['patient'])) {
                $consent['patient'] = [];
            }
            $consent['patient']['id'] = $patientId;

            $requesterName = trim((string) ($consent['requester']['name'] ?? ''));
            if ($requesterName === '' || strcasecmp($requesterName, 'Hospital HMS') === 0) {
                $resolvedRequesterName = $this->resolveRequesterName();
                if ($resolvedRequesterName !== '') {
                    if (! isset($consent['requester']) || ! is_array($consent['requester'])) {
                        $consent['requester'] = [];
                    }
                    $consent['requester']['name'] = $resolvedRequesterName;
                }
            }

            $hiuId = trim((string) ($consent['hiu']['id'] ?? ''));
            if ($hiuId === '') {
                $hiuId = $this->resolveHiuServiceId();
                if ($hiuId === '') {
                    $hiuId = trim((string) ($clean['hfr_id'] ?? ''));
                }
                if ($hiuId !== '') {
                    if (! isset($consent['hiu']) || ! is_array($consent['hiu'])) {
                        $consent['hiu'] = [];
                    }
                    $consent['hiu']['id'] = $hiuId;
                }
            }

            $requesterIdentifierValue = trim((string) ($consent['requester']['identifier']['value'] ?? ''));
            if ($requesterIdentifierValue === '') {
                $requesterIdentifierValue = $hiuId !== '' ? $hiuId : trim((string) ($clean['hfr_id'] ?? ''));
            }
            if ($requesterIdentifierValue !== '') {
                if (! isset($consent['requester']) || ! is_array($consent['requester'])) {
                    $consent['requester'] = [];
                }
                if (! isset($consent['requester']['identifier']) || ! is_array($consent['requester']['identifier'])) {
                    $consent['requester']['identifier'] = [];
                }
                $consent['requester']['identifier']['system'] = 'https://abdm.gov.in/facility';
                $consent['requester']['identifier']['type'] = 'HIU';
                $consent['requester']['identifier']['value'] = $requesterIdentifierValue;
            }

            return [
                'ok' => 1,
                'payload' => $base + ['consent' => $consent],
            ];
        }

        if ($operation === 'consent_status') {
            $consentRequestId = trim((string) ($clean['abdm_consent_request_id'] ?? $clean['consentRequestId'] ?? $clean['consent_id'] ?? ''));
            if ($consentRequestId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'consentRequestId is required for consent status.',
                ];
            }
            if ($this->isGatewayRequestIdPattern($consentRequestId)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'mapping_error: consentRequestId appears to be gateway request_id; use stored ABDM consentRequestId.',
                ];
            }

            return [
                'ok' => 1,
                'payload' => $base + ['consentRequestId' => $consentRequestId],
            ];
        }

        if ($operation === 'consent_fetch') {
            $consentId = trim((string) ($clean['abdm_consent_artifact_id'] ?? $clean['consentId'] ?? $clean['consent_id'] ?? ''));
            if ($consentId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'consentId is required for consent fetch.',
                ];
            }
            if ($this->isGatewayRequestIdPattern($consentId)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'mapping_error: consentId appears to be gateway request_id; use ABDM consent artifact id only.',
                ];
            }

            return [
                'ok' => 1,
                'payload' => $base + ['consentId' => $consentId],
            ];
        }

        if ($operation === 'hi_request') {
            $hiRequest = $clean['hiRequest'] ?? null;
            if (! is_array($hiRequest)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'hiRequest object is required for health information request.',
                ];
            }

            $consentId = trim((string) ($hiRequest['consent']['id'] ?? $clean['consentId'] ?? $clean['consent_id'] ?? ''));
            if ($consentId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'hiRequest.consent.id is required for health information request.',
                ];
            }

            if (! isset($hiRequest['consent']) || ! is_array($hiRequest['consent'])) {
                $hiRequest['consent'] = [];
            }
            $hiRequest['consent']['id'] = $consentId;

            return [
                'ok' => 1,
                'payload' => $base + ['hiRequest' => $hiRequest],
            ];
        }

        return [
            'ok' => 0,
            'http_code' => 400,
            'error_text' => 'Unsupported operation: ' . $operation,
        ];
    }

    private function stripInternalNoise(array $payload): array
    {
        unset(
            $payload['csrf_test_name'],
            $payload['patient_id'],
            $payload['abha_id'],
            $payload['abhaId']
        );

        if (isset($payload['requestId'])) {
            unset($payload['request_id']);
        }
        if (isset($payload['transactionId'])) {
            unset($payload['transaction_id']);
        }

        return $payload;
    }

    private function buildDefaultConsent(array $payload): array
    {
        $patientId = trim((string) ($payload['abha_address'] ?? ''));
        if (! $this->isValidAbhaAddress($patientId)) {
            return [];
        }

        $requesterName = $this->resolveRequesterName();
        if ($requesterName === '') {
            $requesterName = 'Hospital HMS';
        }

        $hiuId = $this->resolveHiuServiceId();
        if ($hiuId === '') {
            $hiuId = trim((string) ($payload['hfr_id'] ?? ''));
        }

        $consent = [
            'purpose' => [
                'code' => 'CAREMGT',
                'text' => 'Care Management',
                'refUri' => 'https://abdm.gov.in',
            ],
            'patient' => [
                'id' => $patientId,
            ],
            'requester' => [
                'name' => $requesterName,
            ],
            'hiTypes' => ['OPConsultation', 'DiagnosticReport'],
            'permission' => [
                'accessMode' => 'VIEW',
                'dateRange' => [
                    'from' => gmdate('Y-m-d\T00:00:00.000\Z', strtotime('-30 days')),
                    'to' => gmdate('Y-m-d\T00:00:00.000\Z'),
                ],
                'dataEraseAt' => gmdate('Y-m-d\T00:00:00.000\Z', strtotime('+365 days')),
                'frequency' => [
                    'unit' => 'HOUR',
                    'value' => 1,
                    'repeats' => 0,
                ],
            ],
        ];

        if ($hiuId !== '') {
            $consent['hiu'] = ['id' => $hiuId];
            $consent['requester']['identifier'] = [
                'system' => 'https://abdm.gov.in/facility',
                'type' => 'HIU',
                'value' => $hiuId,
            ];
        } else {
            $fallbackId = trim((string) ($payload['hfr_id'] ?? ''));
            if ($fallbackId !== '') {
                $consent['requester']['identifier'] = [
                    'system' => 'https://abdm.gov.in/facility',
                    'type' => 'HIU',
                    'value' => $fallbackId,
                ];
            }
        }

        return $consent;
    }

    private function readSettingValue(string $key): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $key)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function resolveRequesterName(): string
    {
        foreach (['ABDM_HMS_NAME', 'H_Name', 'HOSPITAL_NAME'] as $key) {
            $value = $this->readSettingValue($key);
            if ($value !== '') {
                return $value;
            }
        }

        $envName = trim((string) (getenv('HOSPITAL_NAME') ?: getenv('app.name') ?: ''));
        return $envName;
    }

    private function resolveHiuServiceId(): string
    {
        if (! $this->db->tableExists('hospital_setting')) {
            $envHiu = trim((string) (getenv('ABDM_HIU_ID') ?: ''));
            if ($envHiu !== '') {
                return $envHiu;
            }
            return trim((string) (getenv('ABDM_HFR_ID') ?: ''));
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->whereIn('s_name', ['ABDM_HIU_ID', 'EATRIA_HIU_ID'])
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $value = trim((string) ($row['s_value'] ?? ''));
        if ($value !== '') {
            return $value;
        }

        $hfr = $this->readSettingValue('ABDM_HFR_ID');
        if ($hfr !== '') {
            return $hfr;
        }

        $envHiu = trim((string) (getenv('ABDM_HIU_ID') ?: ''));
        if ($envHiu !== '') {
            return $envHiu;
        }

        return trim((string) (getenv('ABDM_HFR_ID') ?: ''));
    }

    private function canonicalizeAbhaAddress(string $abhaAddress, string $abhaId): string
    {
        $candidate = trim($abhaAddress);
        if ($candidate !== '' && $this->isValidAbhaAddress($candidate)) {
            return $candidate;
        }

        if ($candidate !== '') {
            $digits = preg_replace('/\D+/', '', $candidate) ?? '';
            if (preg_match('/^\d{14}$/', $digits) === 1) {
                $domain = trim((string) (getenv('ABDM_ABHA_ADDRESS_DEFAULT_DOMAIN') ?: 'sbx'));
                return $digits . '@' . $domain;
            }
        }

        $abhaDigits = preg_replace('/\D+/', '', trim($abhaId)) ?? '';
        if (preg_match('/^\d{14}$/', $abhaDigits) === 1) {
            $domain = trim((string) (getenv('ABDM_ABHA_ADDRESS_DEFAULT_DOMAIN') ?: 'sbx'));
            return $abhaDigits . '@' . $domain;
        }

        return '';
    }

    private function isValidAbhaAddress(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+@[A-Za-z0-9.-]+$/', trim($value)) === 1;
    }

    private function resolveState(string $operation, array $result): string
    {
        $ok = (int) ($result['ok'] ?? 0) === 1;
        if (! $ok) {
            if (str_contains(strtolower((string) ($result['error_text'] ?? '')), 'mapping_error')) {
                return 'MAPPING_ERROR';
            }
            return 'FAILED';
        }

        $statusStr = strtolower(trim((string) ($result['status'] ?? $result['consent_status'] ?? '')));
        if (in_array($statusStr, ['revoked', 'denied'], true)) {
            return 'REVOKED';
        }
        if (in_array($statusStr, ['expired'], true)) {
            return 'EXPIRED';
        }

        return match ($operation) {
            'consent_request' => 'REQUESTED',
            'consent_status' => 'STATUS_CHECKED',
            'consent_fetch' => 'CONSENT_FETCHED',
            'hi_request' => ($statusStr === 'completed' ? 'COMPLETED' : 'DATA_REQUESTED'),
            default => 'COMPLETED',
        };
    }

    private function findDuplicate(string $operation, array $payload): ?array
    {
        $requestId = trim((string) ($payload['request_id'] ?? ''));
        $transactionId = trim((string) ($payload['transaction_id'] ?? ''));

        $builder = $this->db->table('abdm_hiu_workflows')->select('*')->where('operation', $operation);
        if ($requestId !== '') {
            $row = $builder->where('request_id', $requestId)->orderBy('id', 'DESC')->get(1)->getRowArray();
            if (! empty($row)) {
                return $row;
            }
        }

        if ($transactionId !== '') {
            $row = $this->db->table('abdm_hiu_workflows')
                ->select('*')
                ->where('operation', $operation)
                ->where('transaction_id', $transactionId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray();
            if (! empty($row)) {
                return $row;
            }
        }

        return null;
    }

    private function upsertWorkflow(
        string $operation,
        array $payload,
        array $result,
        string $state,
        string $status,
        int $httpCode,
        bool $retryable,
        int $forceId = 0
    ): void {
        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $hmsRequestId = trim((string) ($payload['request_id'] ?? $payload['requestId'] ?? ''));
        if ($hmsRequestId === '') {
            $hmsRequestId = trim((string) ($result['hms_request_id'] ?? ''));
        }
        $gatewayRequestId = trim((string) ($result['gateway_request_id'] ?? $result['request_id'] ?? $result['requestId'] ?? ''));
        $abdmConsentRequestId = trim((string) ($result['abdm_consent_request_id'] ?? $result['consent_request_id'] ?? $payload['consentRequestId'] ?? ''));
        $transactionId = trim((string) ($payload['transaction_id'] ?? $payload['transactionId'] ?? $result['transaction_id'] ?? ''));
        $consentId = trim((string) ($payload['abdm_consent_artifact_id'] ?? $payload['consent_id'] ?? $payload['consentId'] ?? $result['abdm_consent_artifact_id'] ?? $result['consent_id'] ?? ''));
        if ($consentId === '' && $operation !== 'consent_fetch' && $operation !== 'hi_request') {
            $consentId = $abdmConsentRequestId;
        }
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abha_id'] ?? $result['abha_address'] ?? ''));
        $hfrId = trim((string) ($payload['hfr_id'] ?? $result['hfr_id'] ?? ''));
        $hospitalId = trim((string) ($payload['hospital_id'] ?? $result['hospital_id'] ?? ''));
        $errorText = trim((string) ($result['error_text'] ?? $result['message'] ?? ''));

        $retryCount = 0;
        $id = $forceId;
        if ($id <= 0) {
            $existing = $this->findDuplicate($operation, ['request_id' => $hmsRequestId, 'transaction_id' => $transactionId]);
            if (! empty($existing)) {
                $id = (int) ($existing['id'] ?? 0);
                $retryCount = (int) ($existing['retry_count'] ?? 0);
            }
        } else {
            $existing = $this->db->table('abdm_hiu_workflows')->where('id', $id)->get(1)->getRowArray();
            $retryCount = (int) ($existing['retry_count'] ?? 0);
        }

        if ($status === 'failed' && $retryable) {
            $retryCount++;
        }

        $nextRetryAt = null;
        if ($status === 'failed' && $retryable && $retryCount < 4) {
            $backoffMinutes = min(30, 1 * (2 ** max(0, $retryCount - 1)));
            $nextRetryAt = date('Y-m-d H:i:s', strtotime('+' . $backoffMinutes . ' minutes'));
        }

        $row = [
            'hospital_id' => $hospitalId !== '' ? $hospitalId : null,
            'hfr_id' => $hfrId !== '' ? $hfrId : 'UNKNOWN',
            'abha_address' => $abhaAddress !== '' ? $abhaAddress : null,
            'consent_id' => $consentId !== '' ? $consentId : null,
            'request_id' => $hmsRequestId !== '' ? $hmsRequestId : null,
            'transaction_id' => $transactionId !== '' ? $transactionId : null,
            'operation' => $operation,
            'workflow_state' => $state,
            'status' => $status,
            'http_code' => $httpCode > 0 ? $httpCode : null,
            'is_retryable' => $retryable ? 1 : 0,
            'retry_count' => $retryCount,
            'next_retry_at' => $nextRetryAt,
            'last_error' => $errorText !== '' ? mb_substr($errorText, 0, 2000) : null,
            'request_json' => (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_json' => (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => $now,
        ];

        if (in_array('hms_request_id', $this->db->getFieldNames('abdm_hiu_workflows') ?? [], true)) {
            $row['hms_request_id'] = $hmsRequestId !== '' ? $hmsRequestId : null;
        }
        if (in_array('gateway_request_id', $this->db->getFieldNames('abdm_hiu_workflows') ?? [], true)) {
            $row['gateway_request_id'] = $gatewayRequestId !== '' ? $gatewayRequestId : null;
        }
        if (in_array('abdm_consent_request_id', $this->db->getFieldNames('abdm_hiu_workflows') ?? [], true)) {
            $row['abdm_consent_request_id'] = $abdmConsentRequestId !== '' ? $abdmConsentRequestId : null;
        }
        if (in_array('abdm_consent_artifact_id', $this->db->getFieldNames('abdm_hiu_workflows') ?? [], true)) {
            $row['abdm_consent_artifact_id'] = $consentId !== '' ? $consentId : null;
        }

        if (in_array($state, ['COMPLETED', 'CONSENT_FETCHED'], true)) {
            $row['completed_at'] = $now;
        }
        if ($state === 'EXPIRED') {
            $row['expired_at'] = $now;
        }
        if ($state === 'REVOKED') {
            $row['revoked_at'] = $now;
        }

        if ($id > 0) {
            $this->db->table('abdm_hiu_workflows')->where('id', $id)->update($row);
            return;
        }

        $row['created_at'] = $now;
        $this->db->table('abdm_hiu_workflows')->insert($row);
    }

    private function isGatewayRequestIdPattern(string $value): bool
    {
        return preg_match('/^REQ-\d{14}-[A-Za-z0-9]{6,}$/', trim($value)) === 1;
    }
}
