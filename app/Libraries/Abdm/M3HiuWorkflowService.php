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

        $result = $this->dispatch($operation, $normalized);
        $httpCode = (int) ($result['http_code'] ?? 0);
        $ok = (int) ($result['ok'] ?? 0) === 1;

        $state = $this->resolveState($operation, $result);
        $status = $ok ? 'success' : 'failed';
        $retryable = (int) ($result['retryable'] ?? 0) === 1;

        if ($hasWorkflowTable) {
            $this->upsertWorkflow($operation, $normalized, $result, $state, $status, $httpCode, $retryable);
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

        if (isset($normalized['abha_id']) && ! isset($normalized['abha_address'])) {
            $normalized['abha_address'] = (string) $normalized['abha_id'];
        }

        foreach (['request_id', 'transaction_id', 'consent_id', 'abha_address', 'hfr_id'] as $k) {
            if (! isset($normalized[$k])) {
                $normalized[$k] = '';
            }
            $normalized[$k] = trim((string) $normalized[$k]);
        }

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

    private function resolveState(string $operation, array $result): string
    {
        $ok = (int) ($result['ok'] ?? 0) === 1;
        if (! $ok) {
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
        $requestId = trim((string) ($payload['request_id'] ?? $result['request_id'] ?? ''));
        $transactionId = trim((string) ($payload['transaction_id'] ?? $result['transaction_id'] ?? ''));
        $consentId = trim((string) ($payload['consent_id'] ?? $result['consent_id'] ?? ''));
        $abhaAddress = trim((string) ($payload['abha_address'] ?? $payload['abha_id'] ?? $result['abha_address'] ?? ''));
        $hfrId = trim((string) ($payload['hfr_id'] ?? $result['hfr_id'] ?? ''));
        $hospitalId = trim((string) ($payload['hospital_id'] ?? $result['hospital_id'] ?? ''));
        $errorText = trim((string) ($result['error_text'] ?? $result['message'] ?? ''));

        $retryCount = 0;
        $id = $forceId;
        if ($id <= 0) {
            $existing = $this->findDuplicate($operation, ['request_id' => $requestId, 'transaction_id' => $transactionId]);
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
            'request_id' => $requestId !== '' ? $requestId : null,
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
}
