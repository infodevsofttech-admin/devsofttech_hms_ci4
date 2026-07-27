<?php

namespace App\Libraries\Abdm;

use CodeIgniter\I18n\Time;
use App\Libraries\Abdm\M3HiuGatewayClient;
use App\Libraries\Abdm\M3HiuDocumentRepository;

class M3HiuWorkflowService
{
    private \CodeIgniter\Database\BaseConnection $db;
    private M3HiuGatewayClient $client;
    private M3HiuDocumentRepository $documentRepository;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->client = new M3HiuGatewayClient();
        $this->documentRepository = new M3HiuDocumentRepository();
    }

    public function runOperation(string $operation, array $payload): array
    {
        $hasWorkflowTable = $this->db->tableExists('abdm_hiu_workflows');

        $normalized = $this->normalizePayload($payload);
        $retiredError = $this->retiredOperationError($operation, $normalized);
        if ($retiredError !== null) {
            if ($hasWorkflowTable) {
                $this->upsertWorkflow(
                    $operation,
                    $normalized,
                    $retiredError,
                    'RETIRED',
                    'failed',
                    (int) ($retiredError['http_code'] ?? 410),
                    false
                );
                $retiredError['workflow_persisted'] = 1;
            }

            return $retiredError;
        }

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

        if ($hasWorkflowTable && ! in_array($operation, ['consent_status', 'consent_reconcile', 'data_fetch'], true)) {
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

        $persistPayload = $dispatchPayload;
        foreach ([
            'request_id', 'requestId', 'transaction_id', 'transactionId',
            'abha_address', 'consent_id', 'consentId',
            'abdm_consent_request_id', 'abdm_consent_artifact_id', 'hfr_id',
        ] as $key) {
            if (! array_key_exists($key, $persistPayload) && array_key_exists($key, $normalized)) {
                $persistPayload[$key] = $normalized[$key];
            }
        }

        if ($hasWorkflowTable) {
            $workflowId = $this->upsertWorkflow($operation, $persistPayload, $result, $state, $status, $httpCode, $retryable);
            $result['workflow_persisted'] = 1;

            if ($operation === 'data_fetch' && $ok) {
                $docPersist = $this->documentRepository->persistFromDataFetch($persistPayload, $result, $workflowId);
                $result['documents_persisted'] = (int) ($docPersist['saved'] ?? 0);
                $result['documents_updated'] = (int) ($docPersist['updated'] ?? 0);
            }
        } else {
            $result['workflow_persisted'] = 0;
            $result['workflow_warning'] = 'abdm_hiu_workflows table not found; operation executed without timeline persistence';
        }

        return $result;
    }

    private function retiredOperationError(string $operation, array $payload): ?array
    {
        if (! in_array($operation, ['consent_fetch', 'hi_request'], true)) {
            return null;
        }

        return [
            'ok' => 0,
            'http_code' => 410,
            'error_text' => 'Operation ' . $operation . ' is retired. Use consent_reconcile and data_fetch polling only.',
            'retryable' => 0,
            'request_id' => (string) ($payload['request_id'] ?? ''),
            'transaction_id' => (string) ($payload['transaction_id'] ?? ''),
            'consent_id' => (string) ($payload['consent_id'] ?? ''),
            'abha_address' => (string) ($payload['abha_address'] ?? ''),
            'workflow_state' => 'RETIRED',
            'replacement_ops' => ['consent_reconcile', 'data_fetch'],
        ];
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

            if (in_array($operation, ['consent_fetch', 'hi_request'], true)) {
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

    public function pollNatGateway(int $limit = 30): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return ['processed' => 0, 'consent_updates' => 0, 'data_updates' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $summary = ['processed' => 0, 'consent_updates' => 0, 'data_updates' => 0, 'failed' => 0, 'skipped' => 0];
        $consentRows = $this->db->table('abdm_hiu_workflows')
            ->select('*')
            ->where('operation', 'consent_request')
            ->where('status', 'success')
            ->orderBy('id', 'DESC')
            ->get($limit)
            ->getResultArray();

        $seenConsentRequests = [];
        foreach ($consentRows as $row) {
            $requestId = trim((string) ($row['request_id'] ?? ''));
            if ($requestId === '' || isset($seenConsentRequests[$requestId])) {
                $summary['skipped']++;
                continue;
            }
            $seenConsentRequests[$requestId] = true;
            $summary['processed']++;

            $payload = [
                'request_id' => $requestId,
                'transaction_id' => trim((string) ($row['transaction_id'] ?? '')),
                'consent_id' => trim((string) ($row['consent_id'] ?? '')),
                'abdm_consent_request_id' => trim((string) ($row['abdm_consent_request_id'] ?? '')),
                'abdm_consent_artifact_id' => trim((string) ($row['abdm_consent_artifact_id'] ?? '')),
                'abha_address' => trim((string) ($row['abha_address'] ?? '')),
                'hfr_id' => $this->normalizeStoredHfrId((string) ($row['hfr_id'] ?? '')),
            ];

            $consentResult = $this->runOperation('consent_reconcile', $payload);
            if ((int) ($consentResult['ok'] ?? 0) === 1) {
                $summary['consent_updates']++;

                $consentState = strtolower(trim((string) (
                    $consentResult['workflow_state']
                    ?? $consentResult['status']
                    ?? $consentResult['consent_status']
                    ?? ''
                )));
                if ($consentState !== 'granted') {
                    $summary['skipped']++;
                    continue;
                }

                $resolvedConsentId = trim((string) (
                    $consentResult['abdm_consent_artifact_id']
                    ?? $consentResult['consent_id']
                    ?? $consentResult['abdm_consent_request_id']
                    ?? $consentResult['consent_request_id']
                    ?? ''
                ));
                if ($resolvedConsentId === '' || $this->isGatewayRequestIdPattern($resolvedConsentId)) {
                    $summary['skipped']++;
                    continue;
                }

                $dataPayload = $payload;
                $dataPayload['consent_id'] = $resolvedConsentId;
                $dataPayload['consent_request_id'] = $resolvedConsentId;
                $dataPayload['abdm_consent_artifact_id'] = $resolvedConsentId;
                $dataPayload['abdm_consent_request_id'] = $resolvedConsentId;

                $dataResult = $this->runOperation('data_fetch', $dataPayload);
                if ((int) ($dataResult['ok'] ?? 0) === 1) {
                    $summary['data_updates']++;
                } else {
                    $summary['failed']++;
                }
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
        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];

        $hfrId = trim((string) ($filters['hfr_id'] ?? ''));
        if ($hfrId !== '') {
            $builder->where('hfr_id', $hfrId);
        }
        $consentId = trim((string) ($filters['consent_id'] ?? ''));
        if ($consentId !== '') {
            $builder->groupStart();
            $builder->where('consent_id', $consentId);
            if (in_array('abdm_consent_request_id', $fields, true)) {
                $builder->orWhere('abdm_consent_request_id', $consentId);
            }
            if (in_array('abdm_consent_artifact_id', $fields, true)) {
                $builder->orWhere('abdm_consent_artifact_id', $consentId);
            }
            $builder->orWhere('request_id', $consentId);
            $builder->groupEnd();
        }
        $transactionId = trim((string) ($filters['transaction_id'] ?? ''));
        if ($transactionId !== '') {
            $builder->where('transaction_id', $transactionId);
        }
        $abhaAddress = trim((string) ($filters['abha_address'] ?? ''));
        if ($abhaAddress !== '') {
            $builder->groupStart();
            $builder->where('abha_address', $abhaAddress);
            // Older rows may store ABHA address only inside request_json consent.patient.id.
            $builder->orLike('request_json', '"abha_address":"' . str_replace('"', '\\"', $abhaAddress) . '"');
            $builder->orLike('request_json', '"id":"' . str_replace('"', '\\"', $abhaAddress) . '"');
            $builder->groupEnd();
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

    public function natPollSummary(int $lookbackMinutes = 180): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return [
                'ok' => 0,
                'message' => 'abdm_hiu_workflows table not found',
                'last_polled_at' => null,
                'consent_updates' => 0,
                'data_updates' => 0,
                'failed' => 0,
                'pending' => 0,
                'recent_errors' => [],
            ];
        }

        $fromTime = date('Y-m-d H:i:s', strtotime('-' . max(1, $lookbackMinutes) . ' minutes'));

        $lastRow = $this->db->table('abdm_hiu_workflows')
            ->select('updated_at')
            ->whereIn('operation', ['consent_reconcile', 'data_fetch'])
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray();

        $rows = $this->db->table('abdm_hiu_workflows')
            ->select('operation, status, workflow_state')
            ->whereIn('operation', ['consent_reconcile', 'data_fetch'])
            ->where('updated_at >=', $fromTime)
            ->get()
            ->getResultArray();

        $errorRows = $this->db->table('abdm_hiu_workflows')
            ->select('updated_at, operation, request_id, transaction_id, last_error')
            ->whereIn('operation', ['consent_reconcile', 'data_fetch'])
            ->where('status', 'failed')
            ->where('updated_at >=', $fromTime)
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get(8)
            ->getResultArray();

        $consentUpdates = 0;
        $dataUpdates = 0;
        $failed = 0;
        $pending = 0;

        foreach ($rows as $row) {
            $operation = trim((string) ($row['operation'] ?? ''));
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            $state = strtolower(trim((string) ($row['workflow_state'] ?? '')));

            if ($status === 'failed') {
                $failed++;
                continue;
            }

            if ($operation === 'consent_reconcile' && in_array($state, ['granted', 'status_checked'], true)) {
                $consentUpdates++;
                continue;
            }

            if ($operation === 'data_fetch' && in_array($state, ['data_received', 'completed'], true)) {
                $dataUpdates++;
                continue;
            }

            $pending++;
        }

        return [
            'ok' => 1,
            'last_polled_at' => $lastRow['updated_at'] ?? null,
            'lookback_minutes' => $lookbackMinutes,
            'consent_updates' => $consentUpdates,
            'data_updates' => $dataUpdates,
            'failed' => $failed,
            'pending' => $pending,
            'recent_errors' => $errorRows,
        ];
    }

    public function ingestConsentUpdateWebhook(array $payload): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return [
                'ok' => 1,
                'persisted' => 0,
                'message' => 'abdm_hiu_workflows table not found; callback accepted without persistence',
            ];
        }

        $consent = is_array($payload['consent'] ?? null) ? (array) $payload['consent'] : [];
        $consentDetail = is_array($payload['consentDetail'] ?? null) ? (array) $payload['consentDetail'] : [];
        $consentObj = ! empty($consent) ? $consent : $consentDetail;

        $status = strtolower(trim((string) (
            $consentObj['status']
            ?? $payload['consent_status']
            ?? $payload['status']
            ?? ''
        )));
        if ($status === '') {
            $status = 'status_checked';
        }

        $consentId = trim((string) (
            $consentObj['id']
            ?? $payload['consent_id']
            ?? $payload['consentId']
            ?? $payload['abdm_consent_artifact_id']
            ?? ''
        ));

        $requestId = trim((string) (
            $payload['request_id']
            ?? $payload['requestId']
            ?? ($payload['response']['requestId'] ?? '')
            ?? ''
        ));
        $transactionId = trim((string) (
            $payload['transaction_id']
            ?? $payload['transactionId']
            ?? ($consentObj['transactionId'] ?? '')
            ?? ''
        ));

        $abhaAddress = trim((string) (
            ($consentObj['patient']['id'] ?? '')
            ?: ($payload['abha_address'] ?? '')
            ?: ($payload['abha_id'] ?? '')
        ));
        $abhaAddress = $this->canonicalizeAbhaAddress($abhaAddress, (string) ($payload['abha_id'] ?? ''));

        $persistPayload = [
            'request_id' => $requestId,
            'requestId' => $requestId,
            'transaction_id' => $transactionId,
            'transactionId' => $transactionId,
            'consent_id' => $consentId,
            'consentId' => $consentId,
            'abdm_consent_artifact_id' => $consentId,
            'abha_address' => $abhaAddress,
            'hfr_id' => trim((string) ($payload['hfr_id'] ?? '')),
            'consent' => $consentObj,
            'callback' => $payload,
        ];

        $result = [
            'ok' => 1,
            'http_code' => 202,
            'status' => $status,
            'consent_id' => $consentId,
            'consentId' => $consentId,
            'abdm_consent_artifact_id' => $consentId,
            'request_id' => $requestId,
            'transaction_id' => $transactionId,
            'abha_address' => $abhaAddress,
            'consent' => $consentObj,
            'data' => $payload,
            'retryable' => 0,
        ];

        $state = match ($status) {
            'granted', 'approved', 'active' => 'GRANTED',
            'revoked', 'denied' => 'REVOKED',
            'expired' => 'EXPIRED',
            default => 'STATUS_CHECKED',
        };

        $this->upsertWorkflow('consent_callback', $persistPayload, $result, $state, 'success', 202, false);

        return [
            'ok' => 1,
            'persisted' => 1,
            'workflow_state' => $state,
            'consent_id' => $consentId,
            'request_id' => $requestId,
        ];
    }

    public function ingestHealthInformationCallback(array $payload, string $operation = 'hi_on_request_callback'): array
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return [
                'ok' => 1,
                'persisted' => 0,
                'message' => 'abdm_hiu_workflows table not found; callback accepted without persistence',
            ];
        }

        $requestId = trim((string) (
            $payload['request_id']
            ?? $payload['requestId']
            ?? ($payload['response']['requestId'] ?? '')
            ?? ''
        ));
        $transactionId = trim((string) (
            $payload['transaction_id']
            ?? $payload['transactionId']
            ?? ($payload['response']['transactionId'] ?? '')
            ?? ''
        ));
        $consentId = trim((string) (
            ($payload['hiRequest']['consent']['id'] ?? '')
            ?: ($payload['consent']['id'] ?? '')
            ?: ($payload['consent_id'] ?? '')
            ?: ($payload['consentId'] ?? '')
        ));
        $abhaAddress = $this->canonicalizeAbhaAddress(trim((string) (
            ($payload['patient']['id'] ?? '')
            ?: ($payload['abha_address'] ?? '')
            ?: ($payload['abha_id'] ?? '')
        )), (string) ($payload['abha_id'] ?? ''));

        $errorCode = trim((string) ($payload['error']['code'] ?? ''));
        $errorMessage = trim((string) ($payload['error']['message'] ?? ''));
        $errorText = trim(($errorCode !== '' ? $errorCode . ' ' : '') . $errorMessage);

        $hasData = isset($payload['fhir_bundle'])
            || isset($payload['fhirBundle'])
            || isset($payload['bundles'])
            || isset($payload['entries'])
            || isset($payload['data']);

        $ok = $errorText === '' ? 1 : 0;
        $state = $hasData ? 'DATA_RECEIVED' : ($ok === 1 ? 'DATA_PENDING' : 'FAILED');

        $persistPayload = [
            'request_id' => $requestId,
            'requestId' => $requestId,
            'transaction_id' => $transactionId,
            'transactionId' => $transactionId,
            'consent_id' => $consentId,
            'consentId' => $consentId,
            'abdm_consent_artifact_id' => $consentId,
            'abha_address' => $abhaAddress,
            'callback' => $payload,
        ];
        $result = [
            'ok' => $ok,
            'http_code' => 202,
            'status' => strtolower($state),
            'error_text' => $errorText,
            'request_id' => $requestId,
            'transaction_id' => $transactionId,
            'consent_id' => $consentId,
            'abha_address' => $abhaAddress,
            'retryable' => 0,
            'data' => $payload,
        ];

        $this->upsertWorkflow($operation, $persistPayload, $result, $state, $ok === 1 ? 'success' : 'failed', 202, false);

        return [
            'ok' => 1,
            'persisted' => 1,
            'workflow_state' => $state,
            'request_id' => $requestId,
            'transaction_id' => $transactionId,
            'error_text' => $errorText,
        ];
    }

    private function dispatch(string $operation, array $payload): array
    {
        return match ($operation) {
            'consent_request' => $this->client->consentRequest($payload),
            'consent_status' => $this->client->consentRequestStatus($payload),
            'consent_reconcile' => $this->client->reconcileConsentStatus($payload),
            'data_fetch' => $this->client->fetchDecryptedData($payload),
            default => ['ok' => 0, 'http_code' => 400, 'error_text' => 'Unsupported operation: ' . $operation, 'retryable' => 0],
        };
    }

    private function normalizePayload(array $payload): array
    {
        $normalized = $payload;
        $generatedRequestId = false;

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
            $generatedRequestId = true;
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

        $normalized['__generated_request_id'] = $generatedRequestId ? 1 : 0;

        return $normalized;
    }

    private function preparePayloadForOperation(string $operation, array $normalized): array
    {
        $requestIdGenerated = (int) ($normalized['__generated_request_id'] ?? 0) === 1;
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

            // Preserve requester.identifier for bridge deployments that validate strict requester schema.
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

            $consent['hiTypes'] = $this->normalizeConsentHiTypes($consent['hiTypes'] ?? []);
            if (empty($consent['hiTypes'])) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'consent.hiTypes is required for consent request.',
                ];
            }

            return [
                'ok' => 1,
                'payload' => $base + ['consent' => $consent],
            ];
        }

        if ($operation === 'consent_status') {
            $consentRequestId = trim((string) ($clean['abdm_consent_request_id'] ?? $clean['consentRequestId'] ?? $clean['consent_id'] ?? ''));
            if ($consentRequestId === '') {
                $consentRequestId = $this->resolveAbdmConsentRequestIdFromWorkflow($clean);
            }
            if ($consentRequestId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'consentRequestId is required for consent status. Run consent request first or provide the ABDM consentRequestId from init response.',
                ];
            }
            if ($this->isGatewayRequestIdPattern($consentRequestId)) {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'mapping_error: consentRequestId appears to be a local temporary request id; use consentRequestId returned by consent request/status.',
                ];
            }

            return [
                'ok' => 1,
                'payload' => $base + ['consentRequestId' => $consentRequestId],
            ];
        }

        if ($operation === 'consent_reconcile') {
            $requestIdRef = trim((string) ($clean['request_id'] ?? $clean['requestId'] ?? ''));
            if ($requestIdGenerated) {
                $requestIdRef = '';
            }
            $consentRequestId = trim((string) ($clean['abdm_consent_request_id'] ?? $clean['consentRequestId'] ?? ''));
            $consentId = trim((string) ($clean['abdm_consent_artifact_id'] ?? $clean['consentId'] ?? $clean['consent_id'] ?? ''));

            if ($requestIdRef === '' && $consentRequestId === '' && $consentId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'One of request_id, consent_request_id, or consent_id is required for consent reconciliation.',
                ];
            }

            $query = [];
            if ($requestIdRef !== '') {
                $query['request_id'] = $requestIdRef;
            }
            if ($consentRequestId !== '' && ! $this->isGatewayRequestIdPattern($consentRequestId)) {
                $query['consent_request_id'] = $consentRequestId;
            }
            if ($consentId !== '' && ! $this->isGatewayRequestIdPattern($consentId)) {
                $query['consent_id'] = $consentId;
            }

            return [
                'ok' => 1,
                'payload' => $query,
            ];
        }

        if ($operation === 'data_fetch') {
            $requestFallback = '';
            if (! $requestIdGenerated) {
                $requestFallback = trim((string) (
                    $clean['request_id']
                    ?? $clean['requestId']
                    ?? ''
                ));
            }

            $consentId = trim((string) (
                $clean['abdm_consent_artifact_id']
                ?? $clean['consentId']
                ?? $clean['consent_id']
                ?? $clean['abdm_consent_request_id']
                ?? $clean['consentRequestId']
                ?? $clean['consent_request_id']
                ?? $requestFallback
            ));
            if ($consentId === '') {
                $consentId = $this->resolveAbdmConsentArtifactIdFromWorkflow($clean);
            }
            if ($consentId === '') {
                $consentId = $this->resolveAbdmConsentRequestIdFromWorkflow($clean);
            }

            if ($consentId === '') {
                return [
                    'ok' => 0,
                    'http_code' => 422,
                    'error_text' => 'consent_id is required for decrypted data fetch polling. Use consent artifact id, consent request id, or original request_id after consent is GRANTED.',
                ];
            }

            $query = [];
            $query['consent_id'] = $consentId;

            return [
                'ok' => 1,
                'payload' => $query,
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
            $payload['abhaId'],
            $payload['__generated_request_id']
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
                    'from' => gmdate('Y-m-d\T00:00:00.000\Z', strtotime('-365 days')),
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

    /**
     * Normalize legacy/record hiTypes to HIU consent hiTypes accepted by bridge.
     *
     * @param mixed $hiTypes
     * @return array<int, string>
     */
    private function normalizeConsentHiTypes($hiTypes): array
    {
        if (is_string($hiTypes)) {
            $hiTypes = [$hiTypes];
        }
        if (! is_array($hiTypes)) {
            return [];
        }

        $map = [
            'opconsultrecord' => 'OPConsultation',
            'opconsultation' => 'OPConsultation',
            'diagnosticreportrecord' => 'DiagnosticReport',
            'diagnosticreport' => 'DiagnosticReport',
            'prescriptionrecord' => 'Prescription',
            'prescription' => 'Prescription',
            'dischargesummaryrecord' => 'DischargeSummary',
            'dischargesummary' => 'DischargeSummary',
            'healthdocumentrecord' => 'HealthDocument',
            'healthdocument' => 'HealthDocument',
            'immunization' => 'ImmunizationRecord',
            'immunizationrecord' => 'ImmunizationRecord',
            'wellness' => 'Wellness',
            'wellnessrecord' => 'Wellness',
            'invoice' => 'Invoice',
            'invoicerecord' => 'Invoice',
        ];

        $normalized = [];
        foreach ($hiTypes as $type) {
            $raw = trim((string) $type);
            if ($raw === '') {
                continue;
            }

            $key = strtolower($raw);
            $resolved = $map[$key] ?? $raw;
            if (! in_array($resolved, $normalized, true)) {
                $normalized[] = $resolved;
            }
        }

        return $normalized;
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

    private function resolveHospitalHfrId(): string
    {
        $value = $this->readSettingValue('ABDM_HFR_ID');
        if ($value !== '') {
            return $value;
        }

        return trim((string) (getenv('ABDM_HFR_ID') ?: ''));
    }

    private function normalizeStoredHfrId(string $value): string
    {
        $candidate = trim($value);
        if ($candidate === '' || strcasecmp($candidate, 'UNKNOWN') === 0) {
            return $this->resolveHospitalHfrId();
        }

        return $candidate;
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
            'consent_callback' => ($statusStr === 'granted' || $statusStr === 'approved' ? 'GRANTED' : ($statusStr === 'revoked' ? 'REVOKED' : ($statusStr === 'expired' ? 'EXPIRED' : 'STATUS_CHECKED'))),
            'hi_request' => ($statusStr === 'completed' ? 'COMPLETED' : 'DATA_REQUESTED'),
            'hi_on_request_callback' => (($statusStr === 'data_received' || isset($result['fhir_bundle']) || isset($result['bundles']) || isset($result['entries'])) ? 'DATA_RECEIVED' : ($ok ? 'DATA_PENDING' : 'FAILED')),
            'hi_data_push_callback' => (($statusStr === 'data_received' || isset($result['fhir_bundle']) || isset($result['bundles']) || isset($result['entries'])) ? 'DATA_RECEIVED' : ($ok ? 'DATA_PENDING' : 'FAILED')),
            'consent_reconcile' => ($statusStr === 'granted' ? 'GRANTED' : ($statusStr === 'revoked' ? 'REVOKED' : ($statusStr === 'expired' ? 'EXPIRED' : 'STATUS_CHECKED'))),
            'data_fetch' => (($statusStr === 'completed' || isset($result['fhir_bundle']) || isset($result['bundles'])) ? 'DATA_RECEIVED' : 'DATA_PENDING'),
            default => 'COMPLETED',
        };
    }

    private function findDuplicate(string $operation, array $payload): ?array
    {
        $requestId = trim((string) ($payload['request_id'] ?? ''));
        $transactionId = trim((string) ($payload['transaction_id'] ?? ''));

        // HI operations should dedupe by transaction id so retries/new requests
        // keep separate timeline rows instead of overwriting prior transactions.
        if (in_array($operation, ['hi_request', 'data_fetch'], true)) {
            if ($transactionId === '') {
                return null;
            }

            return $this->db->table('abdm_hiu_workflows')
                ->select('*')
                ->where('operation', $operation)
                ->where('transaction_id', $transactionId)
                ->orderBy('id', 'DESC')
                ->get(1)
                ->getRowArray() ?: null;
        }

        if (in_array($operation, ['hi_on_request_callback', 'hi_data_push_callback'], true)) {
            $builder = $this->db->table('abdm_hiu_workflows')->select('*')->where('operation', $operation);
            if ($transactionId !== '') {
                $row = $builder->where('transaction_id', $transactionId)->orderBy('id', 'DESC')->get(1)->getRowArray();
                if (! empty($row)) {
                    return $row;
                }
            }

            if ($requestId !== '') {
                $row = $this->db->table('abdm_hiu_workflows')
                    ->select('*')
                    ->where('operation', $operation)
                    ->where('request_id', $requestId)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
                if (! empty($row)) {
                    return $row;
                }
            }

            return null;
        }

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

    private function generateUuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
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
    ): int {
        $now = Time::now('Asia/Kolkata')->toDateTimeString();
        $hmsRequestId = $this->firstNonEmptyString([
            $payload['request_id'] ?? null,
            $payload['requestId'] ?? null,
        ]);
        if ($hmsRequestId === '') {
            $hmsRequestId = $this->firstNonEmptyString([
                $result['hms_request_id'] ?? null,
            ]);
        }
        $gatewayRequestId = $this->firstNonEmptyString([
            $result['gateway_request_id'] ?? null,
            $result['request_id'] ?? null,
            $result['requestId'] ?? null,
        ]);
        $abdmConsentRequestId = $this->firstNonEmptyString([
            $result['abdm_consent_request_id'] ?? null,
            $result['consent_request_id'] ?? null,
            $payload['consentRequestId'] ?? null,
            $payload['consent_request_id'] ?? null,
        ]);
        $transactionId = $this->firstNonEmptyString([
            $payload['transaction_id'] ?? null,
            $payload['transactionId'] ?? null,
            $result['transaction_id'] ?? null,
            $result['transactionId'] ?? null,
        ]);
        $consentId = $this->firstNonEmptyString([
            $payload['abdm_consent_artifact_id'] ?? null,
            $payload['consent_id'] ?? null,
            $payload['consentId'] ?? null,
            $payload['hiRequest']['consent']['id'] ?? null,
            $result['abdm_consent_artifact_id'] ?? null,
            $result['consent_id'] ?? null,
            $result['consentId'] ?? null,
            $result['hiRequest']['consent']['id'] ?? null,
        ]);
        if ($consentId === '' && $operation !== 'consent_fetch' && $operation !== 'hi_request') {
            $consentId = $abdmConsentRequestId;
        }
        $abhaAddress = $this->firstNonEmptyString([
            $payload['abha_address'] ?? null,
            $payload['abha_id'] ?? null,
            $result['abha_address'] ?? null,
        ]);
        if ($abhaAddress === '') {
            $abhaAddress = $this->firstNonEmptyString([
                $payload['consent']['patient']['id'] ?? null,
            ]);
        }
        $hfrId = $this->normalizeStoredHfrId($this->firstNonEmptyString([
            $payload['hfr_id'] ?? null,
            $result['hfr_id'] ?? null,
        ]));
        $hospitalId = $this->firstNonEmptyString([
            $payload['hospital_id'] ?? null,
            $result['hospital_id'] ?? null,
        ]);
        $errorText = $this->firstNonEmptyString([
            $result['error_text'] ?? null,
            $result['message'] ?? null,
        ]);

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
            'hfr_id' => $hfrId !== '' ? $hfrId : null,
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
            return $id;
        }

        $row['created_at'] = $now;
        $this->db->table('abdm_hiu_workflows')->insert($row);
        return (int) ($this->db->insertID() ?: 0);
    }

    private function isGatewayRequestIdPattern(string $value): bool
    {
        return preg_match('/^REQ-/i', trim($value)) === 1;
    }

    /**
     * @param array<int, mixed> $values
     */
    private function firstNonEmptyString(array $values): string
    {
        foreach ($values as $value) {
            $text = trim((string) ($value ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function resolveAbdmConsentRequestIdFromWorkflow(array $payload): string
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return '';
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
        $hasAbdmConsentRequestId = in_array('abdm_consent_request_id', $fields, true);

        $requestId = trim((string) ($payload['requestId'] ?? $payload['request_id'] ?? ''));
        $transactionId = trim((string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abha_id'] ?? ''));

        $tryBuilder = function (?string $whereCol, string $whereVal) use ($hasAbdmConsentRequestId): string {
            if ($whereCol === null || $whereVal === '') {
                return '';
            }

            $select = ['consent_id', 'response_json'];
            if ($hasAbdmConsentRequestId) {
                $select[] = 'abdm_consent_request_id';
            }

            $rows = $this->db->table('abdm_hiu_workflows')
                ->select(implode(', ', $select))
                ->where($whereCol, $whereVal)
                ->whereIn('operation', ['consent_request', 'consent_status'])
                ->orderBy('id', 'DESC')
                ->get(25)
                ->getResultArray();

            foreach ($rows as $row) {
                $candidate = '';
                if ($hasAbdmConsentRequestId) {
                    $candidate = trim((string) ($row['abdm_consent_request_id'] ?? ''));
                }
                if ($candidate === '') {
                    $candidate = trim((string) ($row['consent_id'] ?? ''));
                }
                if ($candidate !== '' && ! $this->isGatewayRequestIdPattern($candidate)) {
                    return $candidate;
                }

                $decoded = json_decode((string) ($row['response_json'] ?? ''), true);
                if (is_array($decoded)) {
                    $candidate = trim((string) (
                        $decoded['abdm_consent_request_id']
                        ?? $decoded['consent_request_id']
                        ?? $decoded['consentRequestId']
                        ?? ''
                    ));
                    if ($candidate !== '' && ! $this->isGatewayRequestIdPattern($candidate)) {
                        return $candidate;
                    }
                }
            }

            return '';
        };

        $resolved = $tryBuilder('request_id', $requestId);
        if ($resolved !== '') {
            return $resolved;
        }

        $resolved = $tryBuilder('transaction_id', $transactionId);
        if ($resolved !== '') {
            return $resolved;
        }

        $resolved = $tryBuilder('abha_address', $abhaAddress);
        if ($resolved !== '') {
            return $resolved;
        }

        return '';
    }

    private function resolveAbdmConsentArtifactIdFromWorkflow(array $payload): string
    {
        if (! $this->db->tableExists('abdm_hiu_workflows')) {
            return '';
        }

        $fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
        $hasAbdmConsentArtifactId = in_array('abdm_consent_artifact_id', $fields, true);

        $requestId = trim((string) ($payload['requestId'] ?? $payload['request_id'] ?? ''));
        $transactionId = trim((string) ($payload['transactionId'] ?? $payload['transaction_id'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abha_id'] ?? ''));

        $tryBuilder = function (?string $whereCol, string $whereVal) use ($hasAbdmConsentArtifactId): string {
            if ($whereCol === null || $whereVal === '') {
                return '';
            }

            $select = ['consent_id', 'response_json'];
            if ($hasAbdmConsentArtifactId) {
                $select[] = 'abdm_consent_artifact_id';
            }

            $rows = $this->db->table('abdm_hiu_workflows')
                ->select(implode(', ', $select))
                ->where($whereCol, $whereVal)
                ->whereIn('operation', ['consent_fetch', 'consent_reconcile', 'consent_status', 'hi_request'])
                ->orderBy('id', 'DESC')
                ->get(25)
                ->getResultArray();

            foreach ($rows as $row) {
                $candidate = '';
                if ($hasAbdmConsentArtifactId) {
                    $candidate = trim((string) ($row['abdm_consent_artifact_id'] ?? ''));
                }
                if ($candidate === '') {
                    $candidate = trim((string) ($row['consent_id'] ?? ''));
                }
                if ($candidate !== '' && ! $this->isGatewayRequestIdPattern($candidate)) {
                    return $candidate;
                }

                $decoded = json_decode((string) ($row['response_json'] ?? ''), true);
                if (is_array($decoded)) {
                    $candidate = trim((string) (
                        $decoded['abdm_consent_artifact_id']
                        ?? $decoded['consent_id']
                        ?? $decoded['consentId']
                        ?? ''
                    ));
                    if ($candidate !== '' && ! $this->isGatewayRequestIdPattern($candidate)) {
                        return $candidate;
                    }
                }
            }

            return '';
        };

        $resolved = $tryBuilder('request_id', $requestId);
        if ($resolved !== '') {
            return $resolved;
        }

        $resolved = $tryBuilder('transaction_id', $transactionId);
        if ($resolved !== '') {
            return $resolved;
        }

        $resolved = $tryBuilder('abha_address', $abhaAddress);
        if ($resolved !== '') {
            return $resolved;
        }

        return '';
    }
}
