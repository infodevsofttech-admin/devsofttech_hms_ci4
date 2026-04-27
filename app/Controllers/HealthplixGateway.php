<?php

namespace App\Controllers;

use App\Libraries\HealthplixService;

class HealthplixGateway extends BaseController
{
    public function generateToken()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $service = new HealthplixService();
        $result = $service->generateToken();
        if (($result['ok'] ?? false) !== true) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => (string) ($result['error'] ?? 'HealthPlix token generation failed'),
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => (int) ($result['status'] ?? 200),
            'message' => 'HealthPlix token generated',
        ]);
    }

    public function registerPatient()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $payload = $this->resolvePayload();
        if (empty($payload)) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'Patient payload is required',
            ]);
        }

        $service = new HealthplixService();
        $result = $service->registerPatient($payload);
        if (($result['ok'] ?? false) !== true) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => (string) ($result['error'] ?? 'HealthPlix patient registration failed'),
                'status' => (int) ($result['status'] ?? 0),
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => (int) ($result['status'] ?? 200),
            'data' => $result['data'] ?? [],
        ]);
    }

    public function bookAppointment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $payload = $this->resolvePayload();
        if (empty($payload)) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => 'Appointment payload is required',
            ]);
        }

        $service = new HealthplixService();
        $result = $service->bookAppointment($payload);
        if (($result['ok'] ?? false) !== true) {
            return $this->response->setJSON([
                'ok' => 0,
                'error_text' => (string) ($result['error'] ?? 'HealthPlix appointment booking failed'),
                'status' => (int) ($result['status'] ?? 0),
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'status' => (int) ($result['status'] ?? 200),
            'data' => $result['data'] ?? [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePayload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json) && ! empty($json)) {
            return $json;
        }

        $payloadJson = trim((string) $this->request->getPost('payload_json'));
        if ($payloadJson !== '') {
            $decoded = json_decode($payloadJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
