<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Libraries\HealthplixService;

class HealthplixSettings extends BaseController
{
    public function index()
    {
        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        return view('Setting/Admin/healthplix_settings', [
            'healthplix_enabled' => $this->readSettingValue('HEALTHPLIX_ENABLED') === '1' ? '1' : '0',
            'healthplix_base_url' => $this->readSettingValue('HEALTHPLIX_BASE_URL') ?: 'https://consultation-edge-dev.healthplix.com',
            'healthplix_fetch_secret_masked' => $this->maskKey($this->readSettingValue('HEALTHPLIX_FETCH_SECRET')),
            'healthplix_fetch_secret_exists' => $this->readSettingValue('HEALTHPLIX_FETCH_SECRET') !== '',
            'healthplix_tenant_id' => $this->readSettingValue('HEALTHPLIX_TENANT_ID'),
            'healthplix_tenant_key_masked' => $this->maskKey($this->readSettingValue('HEALTHPLIX_TENANT_KEY')),
            'healthplix_tenant_key_exists' => $this->readSettingValue('HEALTHPLIX_TENANT_KEY') !== '',
            'healthplix_doctor_identifier' => $this->readSettingValue('HEALTHPLIX_DOCTOR_IDENTIFIER'),
            'healthplix_doctor_email' => $this->readSettingValue('HEALTHPLIX_DOCTOR_EMAIL'),
            'healthplix_service_identifier' => $this->readSettingValue('HEALTHPLIX_SERVICE_IDENTIFIER'),
            'healthplix_service_name' => $this->readSettingValue('HEALTHPLIX_SERVICE_NAME'),
            'healthplix_tenant_header_name' => $this->readSettingValue('HEALTHPLIX_TENANT_HEADER_NAME') ?: 'tenant_id',
            'healthplix_token_path' => $this->readSettingValue('HEALTHPLIX_TOKEN_PATH') ?: 'v1/generate/token',
            'healthplix_patient_path' => $this->readSettingValue('HEALTHPLIX_PATIENT_PATH') ?: 'v1/patient/register',
            'healthplix_appointment_path' => $this->readSettingValue('HEALTHPLIX_APPOINTMENT_PATH') ?: 'v1/appointment/register',
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        if (! $this->db->tableExists('hospital_setting')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'hospital_setting table not found']);
        }

        $fields = [
            'HEALTHPLIX_ENABLED' => $this->request->getPost('healthplix_enabled') === '1' ? '1' : '0',
            'HEALTHPLIX_BASE_URL' => trim((string) $this->request->getPost('healthplix_base_url')),
            'HEALTHPLIX_FETCH_SECRET' => trim((string) $this->request->getPost('healthplix_fetch_secret')),
            'HEALTHPLIX_TENANT_ID' => trim((string) $this->request->getPost('healthplix_tenant_id')),
            'HEALTHPLIX_TENANT_KEY' => trim((string) $this->request->getPost('healthplix_tenant_key')),
            'HEALTHPLIX_DOCTOR_IDENTIFIER' => trim((string) $this->request->getPost('healthplix_doctor_identifier')),
            'HEALTHPLIX_DOCTOR_EMAIL' => trim((string) $this->request->getPost('healthplix_doctor_email')),
            'HEALTHPLIX_SERVICE_IDENTIFIER' => trim((string) $this->request->getPost('healthplix_service_identifier')),
            'HEALTHPLIX_SERVICE_NAME' => trim((string) $this->request->getPost('healthplix_service_name')),
            'HEALTHPLIX_TENANT_HEADER_NAME' => trim((string) $this->request->getPost('healthplix_tenant_header_name')),
            'HEALTHPLIX_TOKEN_PATH' => trim((string) $this->request->getPost('healthplix_token_path')),
            'HEALTHPLIX_PATIENT_PATH' => trim((string) $this->request->getPost('healthplix_patient_path')),
            'HEALTHPLIX_APPOINTMENT_PATH' => trim((string) $this->request->getPost('healthplix_appointment_path')),
        ];

        $savedCount = 0;
        foreach ($fields as $name => $value) {
            if ($value === '' && $name !== 'HEALTHPLIX_ENABLED') {
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

        $storedFetchSecret = $this->readSettingValue('HEALTHPLIX_FETCH_SECRET');

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Saved ' . $savedCount . ' setting(s)',
            'healthplixFetchSecretConfigured' => $storedFetchSecret !== '',
            'healthplixFetchSecretMasked' => $this->maskKey($storedFetchSecret),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function test()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        $overrides = [
            'HEALTHPLIX_ENABLED' => $this->request->getPost('healthplix_enabled') === '1' ? '1' : '0',
        ];

        $overrideFields = [
            'HEALTHPLIX_BASE_URL' => 'healthplix_base_url',
            'HEALTHPLIX_TENANT_HEADER_NAME' => 'healthplix_tenant_header_name',
            'HEALTHPLIX_TENANT_ID' => 'healthplix_tenant_id',
            'HEALTHPLIX_TENANT_KEY' => 'healthplix_tenant_key',
            'HEALTHPLIX_TOKEN_PATH' => 'healthplix_token_path',
            'HEALTHPLIX_PATIENT_PATH' => 'healthplix_patient_path',
            'HEALTHPLIX_APPOINTMENT_PATH' => 'healthplix_appointment_path',
        ];

        foreach ($overrideFields as $settingKey => $postKey) {
            $value = trim((string) $this->request->getPost($postKey));
            if ($value !== '') {
                $overrides[$settingKey] = $value;
            }
        }

        $service = new HealthplixService();
        $result = $service->generateToken($overrides);
        if (($result['ok'] ?? false) !== true) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => (string) ($result['error'] ?? 'HealthPlix token test failed'),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'HealthPlix token generated successfully',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function canManageSettings(): bool
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
