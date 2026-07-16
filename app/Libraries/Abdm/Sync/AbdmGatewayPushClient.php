<?php

namespace App\Libraries\Abdm\Sync;

use CodeIgniter\Database\BaseConnection;

class AbdmGatewayPushClient
{
    private BaseConnection $db;

    /** @var string[] */
    private array $allowedHiTypes = [
        'OPConsultRecord',
        'DiagnosticReportRecord',
        'DischargeSummaryRecord',
        'ImmunizationRecord',
        'InvoiceRecord',
        'PrescriptionRecord',
        'HealthDocumentRecord',
        'WellnessRecord',
    ];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function pushRecord(array $payload, string $idempotencyKey = ''): array
    {
        $requestId = $this->createRequestId();
        $validation = $this->validatePayload($payload);
        if (($validation['valid'] ?? false) !== true) {
            return [
                'ok' => false,
                'status' => 'failed',
                'http_status' => 400,
                'request_id' => $requestId,
                'error' => 'validation_failed',
                'message' => implode('; ', $validation['errors'] ?? ['Invalid payload']),
                'retryable' => false,
            ];
        }

        $baseUrl = rtrim($this->readSetting('ABDM_BRIDGE_URL', 'EATRIA_BRIDGE_URL'), '/');
        if ($baseUrl === '') {
            return [
                'ok' => false,
                'status' => 'failed',
                'http_status' => 500,
                'request_id' => $requestId,
                'error' => 'bridge_url_missing',
                'message' => 'ABDM bridge URL is not configured.',
                'retryable' => false,
            ];
        }

        $token = trim($this->readSetting('EATRIA_BRIDGE_TOKEN', 'ABDM_BRIDGE_TOKEN'));
        if ($token === '') {
            return [
                'ok' => false,
                'status' => 'failed',
                'http_status' => 500,
                'request_id' => $requestId,
                'error' => 'token_missing',
                'message' => 'ABDM bridge token is not configured.',
                'retryable' => false,
            ];
        }

        $url = $baseUrl . '/api/v3/records/push';
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'X-Request-Id' => $requestId,
        ];
        if ($idempotencyKey !== '') {
            $headers['X-Idempotency-Key'] = $idempotencyKey;
        }

        $startedAt = microtime(true);
        try {
            $client = service('curlrequest', [
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payload,
            ]);

            $httpStatus = (int) $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);
            if (! is_array($body)) {
                $body = ['raw' => (string) $response->getBody()];
            }

            $mapped = $this->mapResponse($httpStatus, $body, $requestId);
            $mapped['latency_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
            $mapped['gateway_request_id'] = (string) ($response->getHeaderLine('X-Request-Id') ?: ($body['request_id'] ?? ''));

            $this->logPush($payload, $mapped, $requestId);
            return $mapped;
        } catch (\Throwable $e) {
            $result = [
                'ok' => false,
                'status' => 'retry',
                'http_status' => 0,
                'request_id' => $requestId,
                'error' => 'network_error',
                'message' => $e->getMessage(),
                'retryable' => true,
                'gateway_request_id' => '',
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ];
            $this->logPush($payload, $result, $requestId);
            return $result;
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function validatePayload(array $payload): array
    {
        $errors = [];

        $hfrId = trim((string) ($payload['hfr_id'] ?? ''));
        if ($hfrId === '') {
            $errors[] = 'hfr_id is required';
        }

        $hiType = trim((string) ($payload['hi_type'] ?? ''));
        if (! in_array($hiType, $this->allowedHiTypes, true)) {
            $errors[] = 'hi_type is invalid';
        }

        $careContextRef = trim((string) ($payload['care_context_reference'] ?? ''));
        if ($careContextRef === '') {
            $errors[] = 'care_context_reference is required';
        }

        $bundle = $payload['fhir_bundle'] ?? null;
        if (! is_array($bundle)) {
            $errors[] = 'fhir_bundle must be an object';
        } else {
            if (($bundle['resourceType'] ?? '') !== 'Bundle') {
                $errors[] = 'fhir_bundle.resourceType must be Bundle';
            }
            if (($bundle['type'] ?? '') !== 'document') {
                $errors[] = 'fhir_bundle.type must be document';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $result
     */
    private function logPush(array $payload, array $result, string $requestId): void
    {
        if (! $this->db->tableExists('abdm_api_logs')) {
            return;
        }

        $maskedPayload = $payload;
        if (isset($maskedPayload['abha_id'])) {
            $maskedPayload['abha_id'] = $this->maskAbha((string) $maskedPayload['abha_id']);
        }
        if (isset($maskedPayload['abha_address'])) {
            $maskedPayload['abha_address'] = $this->maskAbhaAddress((string) $maskedPayload['abha_address']);
        }
        unset($maskedPayload['fhir_bundle']);

        $this->db->table('abdm_api_logs')->insert([
            'channel' => 'eatria_bridge',
            'event_type' => 'abdm.sync.outbox.records_push',
            'endpoint' => '/api/v3/records/push',
            'http_method' => 'POST',
            'entity_type' => 'record',
            'entity_id' => (string) ($payload['local_record_id'] ?? ''),
            'request_json' => (string) json_encode($maskedPayload + ['request_id' => $requestId], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_code' => (int) ($result['http_status'] ?? 0),
            'response_json' => (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => (bool) ($result['ok'] ?? false) ? 'success' : 'error',
            'error_message' => (string) (($result['ok'] ?? false) ? '' : ($result['message'] ?? '')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function mapResponse(int $httpStatus, array $body, string $requestId): array
    {
        $queueId = (string) ($body['queue_id'] ?? $body['existing_queue_id'] ?? '');
        $recordId = (int) ($body['record_id'] ?? $body['id'] ?? $body['existing_record_id'] ?? 0);
        $message = (string) ($body['message'] ?? $body['error_text'] ?? ('HTTP ' . $httpStatus));

        if ($httpStatus === 201 || ((int) ($body['ok'] ?? 0) === 1 && $httpStatus >= 200 && $httpStatus < 300)) {
            return [
                'ok' => true,
                'status' => 'done',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'gateway_record_id' => $recordId > 0 ? $recordId : null,
                'gateway_queue_id' => $queueId !== '' ? $queueId : null,
                'message' => $message,
                'retryable' => false,
            ];
        }

        if ($httpStatus === 409 || strtoupper((string) ($body['error_code'] ?? '')) === 'DUPLICATE_RECORD') {
            return [
                'ok' => true,
                'status' => 'done',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'gateway_record_id' => $recordId > 0 ? $recordId : null,
                'gateway_queue_id' => $queueId !== '' ? $queueId : null,
                'message' => $message !== '' ? $message : 'Duplicate record handled as success.',
                'retryable' => false,
            ];
        }

        if ($httpStatus >= 500 || $httpStatus === 0) {
            return [
                'ok' => false,
                'status' => 'retry',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'error' => 'gateway_unavailable',
                'message' => $message,
                'retryable' => true,
                'gateway_record_id' => null,
                'gateway_queue_id' => null,
            ];
        }

        if ($httpStatus === 400 || $httpStatus === 401 || $httpStatus === 403 || $httpStatus === 422) {
            return [
                'ok' => false,
                'status' => 'failed',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'error' => 'validation_or_auth_error',
                'message' => $message,
                'retryable' => false,
                'gateway_record_id' => null,
                'gateway_queue_id' => null,
            ];
        }

        return [
            'ok' => false,
            'status' => 'failed',
            'http_status' => $httpStatus,
            'request_id' => $requestId,
            'error' => 'unknown_error',
            'message' => $message,
            'retryable' => false,
            'gateway_record_id' => null,
            'gateway_queue_id' => null,
        ];
    }

    private function createRequestId(): string
    {
        $bytes = random_bytes(16);
        $hex = bin2hex($bytes);
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }

    private function readSetting(string ...$names): string
    {
        foreach ($names as $name) {
            $env = getenv($name);
            if ($env !== false && trim((string) $env) !== '') {
                return trim((string) $env);
            }

            if ($this->db->tableExists('hospital_setting')) {
                $row = $this->db->table('hospital_setting')
                    ->select('s_value')
                    ->where('s_name', $name)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();
                $value = trim((string) ($row['s_value'] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function maskAbha(string $abha): string
    {
        $digits = preg_replace('/\D/', '', $abha);
        if (! is_string($digits) || $digits === '') {
            return '';
        }

        if (strlen($digits) <= 4) {
            return str_repeat('*', strlen($digits));
        }

        return str_repeat('*', strlen($digits) - 4) . substr($digits, -4);
    }

    private function maskAbhaAddress(string $abhaAddress): string
    {
        $abhaAddress = trim($abhaAddress);
        if ($abhaAddress === '') {
            return '';
        }

        $parts = explode('@', $abhaAddress, 2);
        $left = $parts[0] ?? '';
        $right = $parts[1] ?? '';
        if (strlen($left) <= 4) {
            return str_repeat('*', max(1, strlen($left))) . ($right !== '' ? '@' . $right : '');
        }

        return str_repeat('*', strlen($left) - 4) . substr($left, -4) . ($right !== '' ? '@' . $right : '');
    }
}
