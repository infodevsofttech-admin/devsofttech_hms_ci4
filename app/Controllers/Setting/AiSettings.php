<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class AiSettings extends BaseController
{
    public function index()
    {
        if (! $this->canManageAiSettings()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('Setting/Admin/ai_settings', [
            'diagnosis_ai_server_url' => $this->readSettingValue('DIAGNOSIS_AI_SERVER_URL'),
            'diagnosis_ai_ocr_endpoint' => $this->readSettingValue('DIAGNOSIS_AI_OCR_ENDPOINT'),
            'diagnosis_ai_parse_endpoint' => $this->readSettingValue('DIAGNOSIS_AI_PARSE_ENDPOINT'),
            'diagnosis_ai_imaging_prompt' => $this->readSettingValue('DIAGNOSIS_AI_IMAGING_PROMPT') ?: $this->defaultImagingPrompt(),
            'diagnosis_ai_timeout_seconds' => $this->readSettingValue('DIAGNOSIS_AI_TIMEOUT_SECONDS') ?: '45',
            'diagnosis_ai_retry_attempts' => $this->readSettingValue('DIAGNOSIS_AI_RETRY_ATTEMPTS') ?: '2',
            'diagnosis_ai_daily_limit' => $this->readSettingValue('DIAGNOSIS_AI_DAILY_LIMIT') ?: '20',
            'diagnosis_ai_token_masked' => $this->maskKey($this->readSettingValue('DIAGNOSIS_AI_PARSE_TOKEN')),
            'diagnosis_ai_token_exists' => $this->readSettingValue('DIAGNOSIS_AI_PARSE_TOKEN') !== '',
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageAiSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'hospital_setting table not found']);
        }

        $fields = [
            'DIAGNOSIS_AI_SERVER_URL' => trim((string) $this->request->getPost('diagnosis_ai_server_url')),
            'DIAGNOSIS_AI_OCR_ENDPOINT' => trim((string) $this->request->getPost('diagnosis_ai_ocr_endpoint')),
            'DIAGNOSIS_AI_PARSE_ENDPOINT' => trim((string) $this->request->getPost('diagnosis_ai_parse_endpoint')),
            'DIAGNOSIS_AI_IMAGING_PROMPT' => trim((string) $this->request->getPost('diagnosis_ai_imaging_prompt')),
            'DIAGNOSIS_AI_PARSE_TOKEN' => trim((string) $this->request->getPost('diagnosis_ai_parse_token')),
            'DIAGNOSIS_AI_TIMEOUT_SECONDS' => trim((string) $this->request->getPost('diagnosis_ai_timeout_seconds')),
            'DIAGNOSIS_AI_RETRY_ATTEMPTS' => trim((string) $this->request->getPost('diagnosis_ai_retry_attempts')),
            'DIAGNOSIS_AI_DAILY_LIMIT' => trim((string) $this->request->getPost('diagnosis_ai_daily_limit')),
        ];

        $savedCount = 0;
        foreach ($fields as $name => $value) {
            if ($value === '') {
                continue;
            }

            if ($this->upsertSettingValue($name, $value)) {
                $savedCount++;
            }
        }

        if ($savedCount === 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Provide at least one setting value to save',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Saved ' . $savedCount . ' setting(s)',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function test()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageAiSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $provider = strtolower(trim((string) $this->request->getPost('provider')));
        if ($provider === '') {
            $provider = 'ai-server';
        }

        if ($provider === 'diagnosis-external') {
            return $this->testDiagnosisExternalAi();
        }

        if ($provider === 'ai-server') {
            return $this->testAiServer();
        }

        return $this->response->setJSON([
            'update' => 0,
            'error_text' => 'Unsupported provider',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function usage()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageAiSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if (! $this->db->tableExists('lab_ai_extraction_batches')) {
            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'Usage table unavailable',
                'usage' => [
                    'total_today' => 0,
                    'ai_today' => 0,
                    'fallback_today' => 0,
                    'last_hour' => 0,
                    'daily_limit' => 20,
                    'level' => 'ok',
                    'ratio' => 0,
                    'providers' => [],
                ],
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $dailyLimit = (int) ($this->readSettingValue('DIAGNOSIS_AI_DAILY_LIMIT') ?: '20');
        if ($dailyLimit <= 0) {
            $dailyLimit = 20;
        }

        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        $hourStart = date('Y-m-d H:i:s', time() - 3600);

        $table = $this->db->table('lab_ai_extraction_batches');

        $totalToday = (int) $this->db->table('lab_ai_extraction_batches')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->countAllResults();

        $fallbackToday = (int) $this->db->table('lab_ai_extraction_batches')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->where("LOWER(ai_provider) IN ('local-xray-fallback','regex-fallback')", null, false)
            ->countAllResults();

        $aiToday = max(0, $totalToday - $fallbackToday);

        $lastHour = (int) $this->db->table('lab_ai_extraction_batches')
            ->where('created_at >=', $hourStart)
            ->where('created_at <=', $end)
            ->countAllResults();

        $providerRows = $table
            ->select('ai_provider, COUNT(*) AS total', false)
            ->where('created_at >=', $start)
            ->where('created_at <=', $end)
            ->groupBy('ai_provider')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        $providers = [];
        foreach ($providerRows as $row) {
            $key = trim((string) ($row['ai_provider'] ?? ''));
            if ($key === '') {
                $key = 'unknown';
            }
            $providers[] = [
                'provider' => $key,
                'count' => (int) ($row['total'] ?? 0),
            ];
        }

        $ratio = (int) round(($aiToday / max(1, $dailyLimit)) * 100);
        $level = 'ok';
        if ($ratio >= 100) {
            $level = 'critical';
        } elseif ($ratio >= 90) {
            $level = 'danger';
        } elseif ($ratio >= 70) {
            $level = 'warn';
        }

        return $this->response->setJSON([
            'update' => 1,
            'usage' => [
                'total_today' => $totalToday,
                'ai_today' => $aiToday,
                'fallback_today' => $fallbackToday,
                'last_hour' => $lastHour,
                'daily_limit' => $dailyLimit,
                'level' => $level,
                'ratio' => $ratio,
                'providers' => $providers,
            ],
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function testDiagnosisExternalAi()
    {
        $endpoint = trim((string) $this->request->getPost('diagnosis_ai_parse_endpoint'));
        $token = trim((string) $this->request->getPost('diagnosis_ai_parse_token'));

        if ($endpoint === '') {
            $endpoint = $this->readSettingValue('DIAGNOSIS_AI_PARSE_ENDPOINT');
        }
        if ($token === '') {
            $token = $this->readSettingValue('DIAGNOSIS_AI_PARSE_TOKEN');
        }

        if ($endpoint === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Diagnosis AI endpoint is required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $client = service('curlrequest', $this->aiHttpOptions());
            $response = $client->post(rtrim($endpoint, '/'), [
                'headers' => $headers,
                'json' => [
                    'ocr_text' => 'Hemoglobin 13.5 g/dL; Total Bilirubin 1.2 mg/dL',
                    'panel_name' => 'LFT Test',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if ($status < 200 || $status >= 300) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Diagnosis AI endpoint test failed (HTTP ' . $status . ')',
                    'detail' => mb_substr($body, 0, 300),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            $values = $decoded['values'] ?? null;
            if (! is_array($values)) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Diagnosis AI endpoint responded without values[]',
                    'detail' => mb_substr($body, 0, 300),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'Diagnosis AI endpoint connection successful',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Diagnosis AI endpoint test failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    private function testAiServer()
    {
        $baseUrl = trim((string) $this->request->getPost('diagnosis_ai_server_url'));
        if ($baseUrl === '') {
            $baseUrl = $this->readSettingValue('DIAGNOSIS_AI_SERVER_URL');
        }

        if ($baseUrl === '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'AI Server URL is required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $token = trim((string) $this->request->getPost('diagnosis_ai_parse_token'));
        if ($token === '') {
            $token = $this->readSettingValue('DIAGNOSIS_AI_PARSE_TOKEN');
        }

        try {
            $headers = ['Accept' => 'application/json'];
            if ($token !== '') {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $client = service('curlrequest', $this->aiHttpOptions());
            $url = rtrim($baseUrl, '/') . '/translate?text=test&target_lang=en';
            $response = $client->get($url, [
                'headers' => $headers,
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status < 200 || $status >= 300) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'AI Server test failed (HTTP ' . $status . ')',
                    'detail' => mb_substr($body, 0, 300),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'AI Server connection successful',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'AI Server test failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function aiHttpOptions(): array
    {
        $options = [
            'timeout' => 20,
            'http_errors' => false,
        ];

        if (defined('ENVIRONMENT') && in_array((string) ENVIRONMENT, ['development', 'testing'], true)) {
            $options['verify'] = false;
        }

        return $options;
    }

    private function canManageAiSettings(): bool
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return false;
        }

        return true;
    }

    private function defaultImagingPrompt(): string
    {
        return "Generate a radiology report in concise clinical style.\n"
            . "Use this structure exactly:\n"
            . "1) Findings Draft\n"
            . "2) Technique\n"
            . "3) Impression Draft\n\n"
            . "Include checks for lungs/pleura, mediastinum/heart size, diaphragm/costophrenic angles, bones/soft tissue, and lines/tubes/devices if present.\n"
            . "If a structure is normal, explicitly state it as normal.\n"
            . "Do not mention AI. Keep output clinically readable for doctors.\n"
            . "Study Name: {study_name}";
    }

    private function readSettingValue(string $name): string
    {
        if (defined($name)) {
            $definedValue = trim((string) constant($name));
            if ($definedValue !== '') {
                return $definedValue;
            }
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return '';
        }

        $row = $this->db->table('hospital_setting')
            ->select('s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        $value = trim((string) ($row['s_value'] ?? ''));
        if ($value !== '') {
            return $value;
        }

        $rows = $this->db->table('hospital_setting')
            ->select('s_name, s_value')
            ->get()
            ->getResultArray();

        $target = strtoupper(trim($name));
        foreach ($rows as $item) {
            $itemName = strtoupper(trim((string) ($item['s_name'] ?? '')));
            if ($itemName === $target) {
                $itemValue = trim((string) ($item['s_value'] ?? ''));
                if ($itemValue !== '') {
                    return $itemValue;
                }
            }
        }

        return '';
    }

    private function upsertSettingValue(string $name, string $value): bool
    {
        $existing = $this->db->table('hospital_setting')
            ->select('id, s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        $oldValue = trim((string) ($existing['s_value'] ?? ''));
        if ($existing) {
            $ok = $this->db->table('hospital_setting')
                ->where('id', (int) ($existing['id'] ?? 0))
                ->update(['s_value' => $value]);
        } else {
            $ok = $this->db->table('hospital_setting')->insert([
                's_name' => $name,
                's_value' => $value,
            ]);
        }

        if ($ok) {
            $this->auditClinicalUpdate('hospital_setting', $name, $name, $oldValue, $value);
        }

        return (bool) $ok;
    }

    private function maskKey(string $key): string
    {
        $key = trim($key);
        if ($key === '') {
            return '';
        }

        if (mb_strlen($key) <= 8) {
            return str_repeat('*', mb_strlen($key));
        }

        return mb_substr($key, 0, 4) . str_repeat('*', mb_strlen($key) - 8) . mb_substr($key, -4);
    }
}
