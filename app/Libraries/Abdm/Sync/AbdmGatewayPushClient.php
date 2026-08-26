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

    /** @var string[] */
    private array $allowedRecordTypes = [
        'OPConsultRecord',
        'PrescriptionRecord',
        'DiagnosticReportRecord',
        'DischargeSummaryRecord',
        'ImmunizationRecord',
        'InvoiceRecord',
        'WellnessRecord',
        'HealthDocumentRecord',
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

        $baseUrl = $this->resolveBridgeBaseUrl();
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

        $url = $this->buildGatewayUrl($baseUrl, '/api/v3/records/push');
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

            $this->logPush($payload, $mapped, $requestId, '/api/v3/records/push', 'abdm.sync.outbox.records_push', 'record');
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
            $this->logPush($payload, $result, $requestId, '/api/v3/records/push', 'abdm.sync.outbox.records_push', 'record');
            return $result;
        }
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function pushCareContextLink(array $payload, string $idempotencyKey = ''): array
    {
        $requestId = $this->createRequestId();
        $validation = $this->validateCareContextPayload($payload);
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

        $baseUrl = $this->resolveBridgeBaseUrl();
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

        $url = $this->buildGatewayUrl($baseUrl, '/api/v1/abdm/gateway/care-context/link');
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

            $mapped = $this->mapCareContextResponse($httpStatus, $body, $requestId);
            $mapped['latency_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
            $mapped['gateway_request_id'] = (string) ($response->getHeaderLine('X-Request-Id') ?: ($body['request_id'] ?? ''));

            $this->logPush($payload, $mapped, $requestId, '/api/v1/abdm/gateway/care-context/link', 'abdm.sync.outbox.care_context_link', 'care_context');
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
            $this->logPush($payload, $result, $requestId, '/api/v1/abdm/gateway/care-context/link', 'abdm.sync.outbox.care_context_link', 'care_context');
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
     * @return array<string,mixed>
     */
    public function validateCareContextPayload(array $payload): array
    {
        $errors = [];

        if ((int) ($payload['hospital_id'] ?? 0) <= 0) {
            $errors[] = 'hospital_id is required';
        }

        $hfrId = trim((string) ($payload['hfr_id'] ?? ''));
        if ($hfrId === '') {
            $errors[] = 'hfr_id is required';
        }

        $patient = $payload['patient'] ?? null;
        if (! is_array($patient)) {
            $errors[] = 'patient object is required';
        } else {
            if (trim((string) ($patient['patient_id'] ?? '')) === '') {
                $errors[] = 'patient.patient_id is required';
            }
            if (trim((string) ($patient['name'] ?? '')) === '') {
                $errors[] = 'patient.name is required';
            }

            $mobileDigits = preg_replace('/\D/', '', (string) ($patient['mobile'] ?? ''));
            if (! is_string($mobileDigits) || strlen($mobileDigits) !== 10) {
                $errors[] = 'patient.mobile must be a 10-digit number';
            }

            $gender = strtoupper(trim((string) ($patient['gender'] ?? '')));
            if (! in_array($gender, ['M', 'F', 'O'], true)) {
                $errors[] = 'patient.gender must be M, F, or O';
            }

            $yob = (int) ($patient['year_of_birth'] ?? 0);
            $currentYear = (int) date('Y');
            if ($yob < 1900 || $yob > $currentYear) {
                $errors[] = 'patient.year_of_birth must be a valid 4-digit year';
            }
        }

        $contexts = $payload['care_contexts'] ?? null;
        if (! is_array($contexts) || $contexts === []) {
            $errors[] = 'care_contexts must contain at least one item';
        } else {
            foreach ($contexts as $index => $context) {
                if (! is_array($context)) {
                    $errors[] = 'care_contexts[' . $index . '] must be an object';
                    continue;
                }

                if (trim((string) ($context['reference_number'] ?? '')) === '') {
                    $errors[] = 'care_contexts[' . $index . '].reference_number is required';
                }
                if (trim((string) ($context['display'] ?? '')) === '') {
                    $errors[] = 'care_contexts[' . $index . '].display is required';
                }

                $recordType = trim((string) ($context['record_type'] ?? ''));
                if (! in_array($recordType, $this->allowedRecordTypes, true)) {
                    $errors[] = 'care_contexts[' . $index . '].record_type is invalid';
                }

                $visitDate = trim((string) ($context['visit_date'] ?? ''));
                if ($visitDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $visitDate)) {
                    $errors[] = 'care_contexts[' . $index . '].visit_date must be YYYY-MM-DD HH:MM:SS';
                }
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
    private function logPush(array $payload, array $result, string $requestId, string $endpoint, string $eventType, string $entityType): void
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
            'event_type' => $eventType,
            'endpoint' => $endpoint,
            'http_method' => 'POST',
            'entity_type' => $entityType,
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
        $queueMeta = [
            'queue_id' => $body['queue_id'] ?? null,
            'existing_queue_id' => $body['existing_queue_id'] ?? null,
            'response.queue_id' => $body['response']['queue_id'] ?? null,
            'response.existing_queue_id' => $body['response']['existing_queue_id'] ?? null,
            'data.queue_id' => $body['data']['queue_id'] ?? null,
            'data.existing_queue_id' => $body['data']['existing_queue_id'] ?? null,
        ];
        $queueId = '';
        $queueSource = 'none';
        foreach ($queueMeta as $source => $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                $queueId = $candidate;
                $queueSource = $source;
                break;
            }
        }

        $recordMeta = [
            'record_id' => $body['record_id'] ?? null,
            'existing_record_id' => $body['existing_record_id'] ?? null,
            'id' => $body['id'] ?? null,
            'response.record_id' => $body['response']['record_id'] ?? null,
            'response.existing_record_id' => $body['response']['existing_record_id'] ?? null,
            'response.id' => $body['response']['id'] ?? null,
            'data.record_id' => $body['data']['record_id'] ?? null,
            'data.existing_record_id' => $body['data']['existing_record_id'] ?? null,
            'data.id' => $body['data']['id'] ?? null,
        ];
        $recordId = 0;
        $recordSource = 'none';
        foreach ($recordMeta as $source => $value) {
            $candidate = (int) $value;
            if ($candidate > 0) {
                $recordId = $candidate;
                $recordSource = $source;
                break;
            }
        }

        $error = $body['error'] ?? null;
        $responseError = $body['response']['error'] ?? null;
        $dataError = $body['data']['error'] ?? null;
        $errorCode = strtoupper(trim((string) (
            $body['error_code']
            ?? (is_array($error) ? ($error['code'] ?? null) : $error)
            ?? ($body['response']['error_code'] ?? null)
            ?? (is_array($responseError) ? ($responseError['code'] ?? null) : $responseError)
            ?? ($body['data']['error_code'] ?? null)
            ?? (is_array($dataError) ? ($dataError['code'] ?? null) : $dataError)
            ?? ''
        )));
        $isDuplicate = $httpStatus === 409 && $errorCode === 'DUPLICATE_RECORD';
        $isSubmitted = $httpStatus === 201 || $isDuplicate;
        $message = (string) ($body['message'] ?? $body['error_text'] ?? ('HTTP ' . $httpStatus));
        $firstPushedAt = trim((string) (
            $body['first_pushed_at']
            ?? ($body['response']['first_pushed_at'] ?? null)
            ?? ($body['data']['first_pushed_at'] ?? '')
        ));

        // The bridge returns field-level FHIR errors here; without them a 422 is undiagnosable.
        $validationErrors = is_array($body['errors'] ?? null) ? $body['errors'] : [];
        if ($validationErrors !== []) {
            $details = [];
            foreach ($validationErrors as $error) {
                if (! is_array($error)) {
                    $details[] = trim((string) $error);
                    continue;
                }
                $details[] = trim(implode(' ', array_filter([
                    trim((string) ($error['code'] ?? '')),
                    trim((string) ($error['field'] ?? '')),
                    trim((string) ($error['message'] ?? '')),
                ])));
            }
            $details = array_values(array_filter($details));
            if ($details !== []) {
                $message .= ' | ' . implode(' ; ', $details);
            }
        }

        if ($isSubmitted || ((int) ($body['ok'] ?? 0) === 1 && $httpStatus >= 200 && $httpStatus < 300)) {
            return [
                'ok' => true,
                'status' => 'done',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'gateway_record_id' => $recordId > 0 ? $recordId : null,
                'gateway_queue_id' => $queueId !== '' ? $queueId : null,
                'first_pushed_at' => $firstPushedAt !== '' ? $firstPushedAt : null,
                'gateway_record_source' => $recordSource,
                'gateway_queue_source' => $queueSource,
                'submitted' => $isSubmitted ? 1 : 0,
                'duplicate' => $isDuplicate ? 1 : 0,
                'message' => $message,
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
                'gateway_record_source' => $recordSource,
                'gateway_queue_source' => $queueSource,
                'submitted' => 0,
                'duplicate' => $isDuplicate ? 1 : 0,
            ];
        }

        if ($httpStatus === 400 || $httpStatus === 401 || $httpStatus === 403 || $httpStatus === 409 || $httpStatus === 422) {
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
                'gateway_record_source' => $recordSource,
                'gateway_queue_source' => $queueSource,
                'submitted' => 0,
                'duplicate' => $isDuplicate ? 1 : 0,
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
            'gateway_record_source' => $recordSource,
            'gateway_queue_source' => $queueSource,
            'submitted' => 0,
            'duplicate' => $isDuplicate ? 1 : 0,
        ];
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    private function mapCareContextResponse(int $httpStatus, array $body, string $requestId): array
    {
        $message = (string) ($body['message'] ?? $body['error_text'] ?? ('HTTP ' . $httpStatus));
        $ok = (int) ($body['ok'] ?? 0) === 1;

        if (($httpStatus >= 200 && $httpStatus < 300) && $ok) {
            return [
                'ok' => true,
                'status' => 'done',
                'http_status' => $httpStatus,
                'request_id' => $requestId,
                'gateway_record_id' => null,
                'gateway_queue_id' => null,
                'submitted' => 1,
                'duplicate' => 0,
                'message' => $message !== '' ? $message : 'Care context linked successfully',
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
                'submitted' => 0,
                'duplicate' => 0,
            ];
        }

        if (in_array($httpStatus, [400, 401, 403, 422], true)) {
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
                'submitted' => 0,
                'duplicate' => 0,
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
            'submitted' => 0,
            'duplicate' => 0,
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

    /**
     * ABDM_BRIDGE_URL still points at the retired /api/v1/bridge root in older .env files,
     * so normalise every source down to the bridge API root that serves /api/v3/*.
     */
    private function resolveBridgeBaseUrl(): string
    {
        $baseUrl = trim($this->readSetting('EATRIA_BRIDGE_URL', 'ABDM_BRIDGE_URL'));
        if ($baseUrl === '') {
            return '';
        }

        $baseUrl = rtrim($baseUrl, '/');
        $baseUrl = (string) preg_replace('#/api/v\d+/bridge$#i', '/api', $baseUrl);
        $baseUrl = (string) preg_replace('#/v\d+/bridge$#i', '', $baseUrl);
        $baseUrl = (string) preg_replace('#/api/v\d+$#i', '/api', $baseUrl);

        return rtrim($baseUrl, '/');
    }

    private function buildGatewayUrl(string $baseUrl, string $path): string
    {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        return (string) preg_replace('#/api/api/#', '/api/', $url);
    }
}
