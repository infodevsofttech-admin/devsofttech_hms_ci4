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

        $geminiKey = $this->readSettingValue('GEMINI_API_KEY');
        $azureKey = $this->readSettingValue('AZURE_OPENAI_API_KEY');
        $docIntelKey = $this->readSettingValue('AZURE_DOCINTEL_KEY');

        return view('Setting/Admin/ai_settings', [
            'gemini_key_masked' => $this->maskKey($geminiKey),
            'gemini_key_exists' => $geminiKey !== '',
            'azure_openai_endpoint' => $this->readSettingValue('AZURE_OPENAI_ENDPOINT'),
            'azure_openai_deployment' => $this->readSettingValue('AZURE_OPENAI_DEPLOYMENT'),
            'azure_openai_api_version' => $this->readSettingValue('AZURE_OPENAI_API_VERSION') ?: '2024-10-21',
            'azure_openai_key_masked' => $this->maskKey($azureKey),
            'azure_openai_key_exists' => $azureKey !== '',
            'azure_docintel_endpoint' => $this->readSettingValue('AZURE_DOCINTEL_ENDPOINT'),
            'azure_docintel_key_masked' => $this->maskKey($docIntelKey),
            'azure_docintel_key_exists' => $docIntelKey !== '',
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
            'GEMINI_API_KEY' => trim((string) $this->request->getPost('gemini_api_key')),
            'AZURE_OPENAI_ENDPOINT' => trim((string) $this->request->getPost('azure_openai_endpoint')),
            'AZURE_OPENAI_API_KEY' => trim((string) $this->request->getPost('azure_openai_api_key')),
            'AZURE_OPENAI_DEPLOYMENT' => trim((string) $this->request->getPost('azure_openai_deployment')),
            'AZURE_OPENAI_API_VERSION' => trim((string) $this->request->getPost('azure_openai_api_version')),
            'AZURE_DOCINTEL_ENDPOINT' => trim((string) $this->request->getPost('azure_docintel_endpoint')),
            'AZURE_DOCINTEL_KEY' => trim((string) $this->request->getPost('azure_docintel_key')),
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
            $provider = 'gemini';
        }

        if ($provider === 'azure') {
            return $this->testAzureOpenAi();
        }

        $apiKey = trim((string) $this->request->getPost('gemini_api_key'));
        if ($apiKey === '') {
            $apiKey = $this->readSettingValue('GEMINI_API_KEY');
        }

        if ($apiKey === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Gemini API key not configured']);
        }

        try {
            $client = service('curlrequest', $this->geminiHttpOptions());
            $payload = [
                'contents' => [[
                    'parts' => [[
                        'text' => 'Reply with exactly: CONNECTED',
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0,
                    'maxOutputTokens' => 20,
                ],
            ];

            $lastStatus = 0;
            $lastBody = '';

            foreach ($this->geminiModelCandidates() as $modelName) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelName . ':generateContent?key=' . urlencode($apiKey);
                $response = $client->post($url, [
                    'headers' => ['Content-Type' => 'application/json'],
                    'json' => $payload,
                ]);

                $status = $response->getStatusCode();
                $body = (string) $response->getBody();

                if ($status === 429) {
                    usleep(1200000);
                    $retry = $client->post($url, [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json' => $payload,
                    ]);
                    $status = $retry->getStatusCode();
                    $body = (string) $retry->getBody();
                }

                if ($status >= 200 && $status < 300) {
                    return $this->response->setJSON([
                        'update' => 1,
                        'error_text' => 'Gemini connection successful (' . $modelName . ')',
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                    ]);
                }

                $lastStatus = $status;
                $lastBody = $body;

                if ($status !== 404) {
                    break;
                }
            }

            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $this->geminiHttpFailureMessage($lastStatus, $lastBody),
                'detail' => mb_substr($lastBody, 0, 250),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $this->normalizeGeminiError($e->getMessage()),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    private function testAzureOpenAi()
    {
        $endpoint = trim((string) $this->request->getPost('azure_openai_endpoint'));
        $apiKey = trim((string) $this->request->getPost('azure_openai_api_key'));
        $deployment = trim((string) $this->request->getPost('azure_openai_deployment'));
        $apiVersion = trim((string) $this->request->getPost('azure_openai_api_version'));

        if ($endpoint === '') {
            $endpoint = $this->readSettingValue('AZURE_OPENAI_ENDPOINT');
        }
        if ($apiKey === '') {
            $apiKey = $this->readSettingValue('AZURE_OPENAI_API_KEY');
        }
        if ($deployment === '') {
            $deployment = $this->readSettingValue('AZURE_OPENAI_DEPLOYMENT');
        }
        if ($apiVersion === '') {
            $apiVersion = $this->readSettingValue('AZURE_OPENAI_API_VERSION');
        }
        if ($apiVersion === '') {
            $apiVersion = '2024-10-21';
        }

        if ($endpoint === '' || $apiKey === '' || $deployment === '') {
            $missing = [];
            if ($endpoint === '') {
                $missing[] = 'endpoint';
            }
            if ($apiKey === '') {
                $missing[] = 'key';
            }
            if ($deployment === '') {
                $missing[] = 'deployment';
            }

            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Azure OpenAI settings incomplete: missing ' . implode(', ', $missing),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $endpoint = rtrim($endpoint, '/');
        $url = $endpoint . '/openai/deployments/' . rawurlencode($deployment) . '/chat/completions?api-version=' . rawurlencode($apiVersion);

        try {
            $client = service('curlrequest', $this->geminiHttpOptions());
            $response = $client->post($url, [
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'messages' => [[
                        'role' => 'user',
                        'content' => 'Reply with exactly: CONNECTED',
                    ]],
                    'temperature' => 0,
                    'max_tokens' => 20,
                ],
            ]);

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status < 200 || $status >= 300) {
                return $this->response->setJSON([
                    'update' => 0,
                    'error_text' => 'Azure OpenAI test failed (HTTP ' . $status . ')',
                    'detail' => mb_substr($body, 0, 300),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                ]);
            }

            return $this->response->setJSON([
                'update' => 1,
                'error_text' => 'Azure OpenAI connection successful',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Azure OpenAI test failed: ' . $e->getMessage(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function geminiHttpOptions(): array
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

    private function normalizeGeminiError(string $message): string
    {
        $message = trim($message);

        if (stripos($message, 'SSL certificate problem') !== false) {
            return 'Gemini test failed: SSL certificate trust issue in local PHP/cURL. Local use is possible; this build auto-relaxes SSL only in development/testing. If still failing, configure CA bundle in PHP.';
        }

        return 'Gemini test failed: ' . $message;
    }

    private function geminiHttpFailureMessage(int $status, string $body): string
    {
        if ($status === 429) {
            return 'Gemini test failed: Rate limit/quota exceeded (HTTP 429). Please wait 1-2 minutes, then retry. If it continues, check API quota/billing in Google AI Studio.';
        }

        if ($status === 403) {
            return 'Gemini test failed: API key not permitted for this request (HTTP 403). Verify key and project permissions.';
        }

        if ($status === 404) {
            return 'Gemini test failed: Model endpoint not found (HTTP 404). Please verify Gemini API availability for this key/project.';
        }

        if ($status <= 0) {
            return 'Gemini test failed: No HTTP response received.';
        }

        $message = 'Gemini test failed (HTTP ' . $status . ')';
        $decoded = json_decode($body, true);
        $apiMessage = trim((string) ($decoded['error']['message'] ?? ''));
        if ($apiMessage !== '') {
            $message .= ': ' . $apiMessage;
        }

        return $message;
    }

    /**
     * @return array<int, string>
     */
    private function geminiModelCandidates(): array
    {
        return [
            'gemini-2.0-flash',
            'gemini-1.5-flash',
            'gemini-1.5-flash-latest',
        ];
    }

    private function canManageAiSettings(): bool
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return false;
        }

        return true;
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
