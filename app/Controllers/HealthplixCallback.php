<?php

namespace App\Controllers;

class HealthplixCallback extends BaseController
{
    public function fetch()
    {
        $rawBody = (string) $this->request->getBody();
        $decoded = $this->request->getJSON(true);
        $payload = is_array($decoded) ? $decoded : [];

        $expectedSecret = $this->resolveExpectedSecret();
        $providedSecret = trim((string) $this->request->getHeaderLine('X-Healthplix-Secret'));

        if ($expectedSecret !== '' && ! hash_equals($expectedSecret, $providedSecret)) {
            $this->logFetchEvent('error', 401, [
                'message' => 'Invalid callback secret',
                'payload_bytes' => strlen($rawBody),
                'payload_keys' => array_keys($payload),
            ], 'Unauthorized callback request');

            return $this->response->setStatusCode(401)->setJSON([
                'ok' => 0,
                'error_text' => 'Unauthorized callback request',
            ]);
        }

        $this->logFetchEvent('success', 200, [
            'message' => 'Callback payload received',
            'payload_bytes' => strlen($rawBody),
            'payload_keys' => array_keys($payload),
        ]);

        return $this->response->setJSON([
            'ok' => 1,
            'message' => 'HealthPlix fetch callback received',
        ]);
    }

    private function resolveExpectedSecret(): string
    {
        $secret = trim((string) hospital_setting_value('HEALTHPLIX_FETCH_SECRET', ''));
        if ($secret !== '') {
            return $secret;
        }

        return trim((string) env('HEALTHPLIX_FETCH_SECRET', ''));
    }

    /**
     * @param array<string, mixed> $response
     */
    private function logFetchEvent(string $status, int $responseCode, array $response, string $errorMessage = ''): void
    {
        try {
            if (! $this->db || ! method_exists($this->db, 'tableExists') || ! $this->db->tableExists('abdm_api_logs')) {
                return;
            }

            $this->db->table('abdm_api_logs')->insert([
                'channel' => 'healthplix',
                'event_type' => 'healthplix.fetch.callback',
                'endpoint' => (string) $this->request->getUri()->getPath(),
                'http_method' => strtoupper($this->request->getMethod()),
                'entity_type' => 'callback',
                'entity_id' => trim((string) ($this->request->getHeaderLine('X-Request-Id') ?: 'n/a')),
                'request_json' => json_encode([
                    'ip' => $this->request->getIPAddress(),
                    'content_type' => (string) $this->request->getHeaderLine('Content-Type'),
                ], JSON_UNESCAPED_SLASHES),
                'response_code' => $responseCode,
                'response_json' => json_encode($response, JSON_UNESCAPED_SLASHES),
                'status' => $status,
                'error_message' => $errorMessage,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
        }
    }
}