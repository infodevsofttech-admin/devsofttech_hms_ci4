<?php

namespace App\Libraries;

class HealthplixService
{
    /**
     * @param array<string, string> $overrides
     * @return array<string, mixed>
     */
    public function generateToken(array $overrides = []): array
    {
        if (! $this->isEnabled($overrides)) {
            return ['ok' => false, 'error' => 'HealthPlix integration is disabled for this hospital.'];
        }

        $baseUrl = rtrim($this->readSetting('HEALTHPLIX_BASE_URL', $overrides), '/');
        $tokenPath = trim($this->readSetting('HEALTHPLIX_TOKEN_PATH', $overrides));
        $tenantId = trim($this->readSetting('HEALTHPLIX_TENANT_ID', $overrides));
        $tenantKey = trim($this->readSetting('HEALTHPLIX_TENANT_KEY', $overrides));

        if ($baseUrl === '' || $tokenPath === '') {
            return ['ok' => false, 'error' => 'HEALTHPLIX_BASE_URL and HEALTHPLIX_TOKEN_PATH are required.'];
        }
        if ($tenantId === '' || $tenantKey === '') {
            return ['ok' => false, 'error' => 'HEALTHPLIX_TENANT_ID and HEALTHPLIX_TENANT_KEY are required.'];
        }

        $endpoint = $this->buildUrl($baseUrl, $tokenPath);
        // Token endpoint uses Basic Auth only — no request body per HealthPlix API doc v1.7
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($tenantId . ':' . $tenantKey),
            'Accept' => 'application/json',
        ];

        $result = $this->request('POST', $endpoint, $headers, [], 'healthplix.token.generate', 'tenant', $tenantId);
        if (($result['ok'] ?? false) !== true) {
            return $result;
        }

        $data = (array) ($result['data'] ?? []);
        $token = trim((string) ($data['token'] ?? ($data['access_token'] ?? '')));
        if ($token === '') {
            return ['ok' => false, 'error' => 'HealthPlix token response did not include token/access_token.'];
        }

        return [
            'ok' => true,
            'token' => $token,
            'status' => (int) ($result['status'] ?? 200),
            'data' => $data,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $overrides
     * @return array<string, mixed>
     */
    public function registerPatient(array $payload, array $overrides = []): array
    {
        if (! $this->isEnabled($overrides)) {
            return ['ok' => false, 'error' => 'HealthPlix integration is disabled for this hospital.'];
        }

        $baseUrl = rtrim($this->readSetting('HEALTHPLIX_BASE_URL', $overrides), '/');
        $path = trim($this->readSetting('HEALTHPLIX_PATIENT_PATH', $overrides));
        if ($baseUrl === '' || $path === '') {
            return ['ok' => false, 'error' => 'HEALTHPLIX_BASE_URL and HEALTHPLIX_PATIENT_PATH are required.'];
        }

        $tokenResult = $this->generateToken($overrides);
        if (($tokenResult['ok'] ?? false) !== true) {
            return $tokenResult;
        }

        $token = (string) ($tokenResult['token'] ?? '');
        $endpoint = $this->buildUrl($baseUrl, $path);
        $tenantHeaderName = trim($this->readSetting('HEALTHPLIX_TENANT_HEADER_NAME', $overrides));
        if ($tenantHeaderName === '') {
            $tenantHeaderName = 'tenant_id';
        }
        $tenantId = trim($this->readSetting('HEALTHPLIX_TENANT_ID', $overrides));

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            $tenantHeaderName => $tenantId,
        ];

        $entityId = trim((string) ($payload['external_patient_id'] ?? ($payload['patient_id'] ?? '')));

        $apiPayload = $this->buildPatientApiPayload($payload);
        return $this->request('POST', $endpoint, $headers, $apiPayload, 'healthplix.patient.register', 'patient', $entityId);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $overrides
     * @return array<string, mixed>
     */
    public function bookAppointment(array $payload, array $overrides = []): array
    {
        if (! $this->isEnabled($overrides)) {
            return ['ok' => false, 'error' => 'HealthPlix integration is disabled for this hospital.'];
        }

        $baseUrl = rtrim($this->readSetting('HEALTHPLIX_BASE_URL', $overrides), '/');
        $path = trim($this->readSetting('HEALTHPLIX_APPOINTMENT_PATH', $overrides));
        if ($baseUrl === '' || $path === '') {
            return ['ok' => false, 'error' => 'HEALTHPLIX_BASE_URL and HEALTHPLIX_APPOINTMENT_PATH are required.'];
        }

        $tokenResult = $this->generateToken($overrides);
        if (($tokenResult['ok'] ?? false) !== true) {
            return $tokenResult;
        }

        $token = (string) ($tokenResult['token'] ?? '');
        $endpoint = $this->buildUrl($baseUrl, $path);
        $tenantHeaderName = trim($this->readSetting('HEALTHPLIX_TENANT_HEADER_NAME', $overrides));
        if ($tenantHeaderName === '') {
            $tenantHeaderName = 'tenant_id';
        }
        $tenantId = trim($this->readSetting('HEALTHPLIX_TENANT_ID', $overrides));

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            $tenantHeaderName => $tenantId,
        ];

        $entityId = trim((string) ($payload['external_appointment_id'] ?? ($payload['appointment_id'] ?? '')));

        $apiPayload = $this->buildAppointmentApiPayload($payload);
        return $this->request('POST', $endpoint, $headers, $apiPayload, 'healthplix.appointment.book', 'opd', $entityId);
    }

    /**
     * @param array<string, string> $overrides
     */
    private function isEnabled(array $overrides = []): bool
    {
        return $this->readSetting('HEALTHPLIX_ENABLED', $overrides) === '1';
    }

    /**
     * Map internal patient payload to HealthPlix API v1 field names.
     * Ref: API Spec Doc v1.7 - Register Patient (POST v1/patient/register)
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildPatientApiPayload(array $payload): array
    {
        $genderRaw = strtolower(trim((string) ($payload['gender'] ?? '')));
        $genderMap = [
            'male' => 'Male', 'm' => 'Male', '1' => 'Male',
            'female' => 'Female', 'f' => 'Female', '2' => 'Female',
            'other' => 'Others', 'o' => 'Others',
        ];
        $gender = $genderMap[$genderRaw] ?? 'Others';

        $honorific = trim((string) ($payload['honorific'] ?? ''));
        if ($honorific === '') {
            $honorific = ($gender === 'Female') ? 'Mrs.' : 'Mr.';
        }

        $patientCode = trim((string) ($payload['patient_code'] ?? ($payload['external_patient_id'] ?? '')));
        $patientIdentifier = $patientCode !== '' ? $patientCode : (string) ($payload['external_patient_id'] ?? '');

        // Minimum 3 characters required by API
        if (mb_strlen($patientIdentifier) < 3) {
            $patientIdentifier = str_pad($patientIdentifier, 3, '0', STR_PAD_LEFT);
        }

        $api = [
            'patient_identifier' => $patientIdentifier,
            'name'               => trim((string) ($payload['full_name'] ?? '')),
            'phone'              => trim((string) ($payload['mobile'] ?? '')),
            'gender'             => $gender,
            'honorific'          => $honorific,
        ];

        // HealthPlix expects dob; derive from estimate_dob/age when explicit DOB is missing.
        $resolvedDob = $this->resolveDobForApi($payload);
        if ($resolvedDob !== '') {
            $api['dob'] = $resolvedDob;
        }

        // Optional fields — only send if non-empty
        foreach ([
            'email'          => 'email',
            'address_line1'  => 'address',
            'city'           => 'city',
            'zip'            => 'pincode',
            'phone_secondary' => 'phone_secondary',
            'occupation'     => 'occupation',
        ] as $internalKey => $apiKey) {
            $val = trim((string) ($payload[$internalKey] ?? ''));
            if ($val !== '') {
                $api[$apiKey] = $val;
            }
        }

        return $api;
    }

    /**
     * Resolve DOB in YYYY-MM-DD from explicit dob, estimate_dob, or age values.
     *
     * @param array<string, mixed> $payload
     */
    private function resolveDobForApi(array $payload): string
    {
        $dob = $this->normalizeDateForApi((string) ($payload['dob'] ?? ''));
        if ($dob !== '') {
            return $dob;
        }

        $estimateDob = $this->normalizeDateForApi((string) ($payload['estimate_dob'] ?? ''));
        if ($estimateDob !== '') {
            return $estimateDob;
        }

        $ageYears = $this->parseNonNegativeInt($payload['age_years'] ?? ($payload['age'] ?? null));
        $ageMonths = $this->parseNonNegativeInt($payload['age_months'] ?? ($payload['age_in_month'] ?? null));

        if ($ageYears === null && $ageMonths === null) {
            return '';
        }

        try {
            $dobFromAge = new \DateTime('today');
            if ($ageYears !== null && $ageYears > 0) {
                $dobFromAge->sub(new \DateInterval('P' . $ageYears . 'Y'));
            }
            if ($ageMonths !== null && $ageMonths > 0) {
                $dobFromAge->sub(new \DateInterval('P' . $ageMonths . 'M'));
            }

            return $dobFromAge->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalizeDateForApi(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        try {
            return (new \DateTime($value))->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * @param mixed $value
     */
    private function parseNonNegativeInt($value): ?int
    {
        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/\d+/', $text, $m) !== 1) {
            return null;
        }

        return max(0, (int) $m[0]);
    }

    /**
     * Map internal appointment payload to HealthPlix API v1 field names.
     * Ref: API Spec Doc v1.7 - Register Appointment (POST v1/appointment/register)
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildAppointmentApiPayload(array $payload): array
    {
        $patientCode = trim((string) ($payload['patient_identifier'] ?? ''));
        if (mb_strlen($patientCode) < 3) {
            $patientCode = str_pad($patientCode, 3, '0', STR_PAD_LEFT);
        }

        // Parse appointment date/time from stored datetime string
        $datetimeRaw = trim((string) ($payload['appointment_datetime'] ?? ''));
        $appntDate = '';
        $appntTime = '';

        if ($datetimeRaw !== '') {
            try {
                $dt = new \DateTime($datetimeRaw);
                $appntDate = $dt->format('Y-m-d');
                $appntTime = $dt->format('H:i:s');
            } catch (\Throwable $e) {
                // fallback to today below
            }
        }

        if ($appntDate === '') {
            $appntDate = date('Y-m-d');
        }
        if ($appntTime === '') {
            $appntTime = date('H:i:s');
        }

        return [
            'patient_identifier'  => $patientCode,
            'doctor_identifier'   => trim((string) ($payload['doctor_identifier'] ?? '')),
            'appnt_date'          => $appntDate,
            'appnt_time'          => $appntTime,
            'appnt_duration'      => (int) ($payload['appnt_duration'] ?? 30),
            'service_identifier'  => trim((string) ($payload['service_identifier'] ?? '')),
        ];
    }

    /**
     * @param array<string, string> $overrides
     */
    private function readSetting(string $name, array $overrides = []): string
    {
        if (array_key_exists($name, $overrides)) {
            return trim((string) $overrides[$name]);
        }

        $envValue = trim((string) env($name, ''));
        if ($envValue !== '') {
            return $envValue;
        }

        if (defined($name)) {
            $defined = trim((string) constant($name));
            if ($defined !== '') {
                return $defined;
            }
        }

        try {
            $db = db_connect();
            if ($db && method_exists($db, 'tableExists') && $db->tableExists('hospital_setting')) {
                $row = $db->table('hospital_setting')
                    ->select('s_value')
                    ->where('s_name', $name)
                    ->get(1)
                    ->getRowArray();

                $value = trim((string) ($row['s_value'] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable $e) {
        }

        return '';
    }

    private function buildUrl(string $baseUrl, string $path): string
    {
        if (preg_match('~^https?://~i', $path) === 1) {
            return $path;
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $url,
        array $headers,
        array $json,
        string $eventType = '',
        string $entityType = '',
        string $entityId = ''
    ): array
    {
        $options = [
            'timeout' => 20,
            'http_errors' => false,
        ];

        if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
            $options['verify'] = false;
        }

        try {
            $client = service('curlrequest', $options);
            $requestOptions = ['headers' => $headers];
            if (! empty($json)) {
                $requestOptions['json'] = $json;
            }
            $response = $client->request($method, $url, $requestOptions);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);
            if (! is_array($decoded)) {
                $decoded = [];
            }

            if ($status < 200 || $status >= 300) {
                $this->logApiCall($eventType, $url, $method, $entityType, $entityId, $json, $status, $body, 'error', 'HTTP ' . $status);

                return [
                    'ok' => false,
                    'status' => $status,
                    'error' => 'HealthPlix API request failed (HTTP ' . $status . ')',
                    'body' => mb_substr(trim($body), 0, 600),
                    'data' => $decoded,
                ];
            }

            $this->logApiCall($eventType, $url, $method, $entityType, $entityId, $json, $status, $body, 'success', '');

            return [
                'ok' => true,
                'status' => $status,
                'data' => $decoded,
                'body' => mb_substr(trim($body), 0, 600),
            ];
        } catch (\Throwable $e) {
            $this->logApiCall($eventType, $url, $method, $entityType, $entityId, $json, 0, '', 'error', $e->getMessage());

            return [
                'ok' => false,
                'status' => 0,
                'error' => 'HealthPlix API request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $requestPayload
     */
    private function logApiCall(
        string $eventType,
        string $endpoint,
        string $method,
        string $entityType,
        string $entityId,
        array $requestPayload,
        int $responseCode,
        string $responseBody,
        string $status,
        string $errorMessage
    ): void {
        if ($eventType === '') {
            return;
        }

        try {
            $db = db_connect();
            if (! $db || ! method_exists($db, 'tableExists') || ! $db->tableExists('abdm_api_logs')) {
                return;
            }

            $requestJson = (string) json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $decodedBody = json_decode($responseBody, true);
            $responseJson = is_array($decodedBody)
                ? (string) json_encode($decodedBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : trim($responseBody);

            $db->table('abdm_api_logs')->insert([
                'channel' => 'healthplix',
                'event_type' => $eventType,
                'endpoint' => $endpoint,
                'http_method' => strtoupper($method),
                'entity_type' => $entityType !== '' ? $entityType : null,
                'entity_id' => $entityId !== '' ? $entityId : null,
                'request_json' => $requestJson,
                'response_code' => $responseCode > 0 ? $responseCode : null,
                'response_json' => $responseJson !== '' ? mb_substr($responseJson, 0, 4000) : null,
                'status' => $status,
                'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Logging must never block business flow.
        }
    }
}
