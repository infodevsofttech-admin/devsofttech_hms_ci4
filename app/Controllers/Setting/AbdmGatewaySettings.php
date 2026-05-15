<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;

class AbdmGatewaySettings extends BaseController
{
    public function index()
    {
        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $token    = $this->readSettingValue('EATRIA_BRIDGE_TOKEN');
        $hfrId    = $this->readSettingValue('ABDM_HFR_ID');
        $hmsName  = $this->readSettingValue('ABDM_HMS_NAME');
        $gwUrl    = $this->readSettingValue('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api';

        return view('Setting/Admin/abdm_gateway_settings', [
            'gateway_url'          => $gwUrl,
            'hfr_id'               => $hfrId,
            'hms_name'             => $hmsName,
            'token_masked'         => $this->maskKey($token),
            'token_exists'         => $token !== '',
            'connector'            => $this->readSettingValue('ABDM_CONNECTOR') ?: 'eatria_bridge',
            'abdm_sync_provider'   => $this->readSettingValue('ABDM_SYNC_PROVIDER') ?: 'eatria',
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

        $gwUrl  = trim((string) $this->request->getPost('gateway_url'));
        $token  = trim((string) $this->request->getPost('api_token'));
        $hfrId  = trim((string) $this->request->getPost('hfr_id'));
        $hmsName = trim((string) $this->request->getPost('hms_name'));

        // Validate gateway URL format
        if ($gwUrl !== '' && ! filter_var($gwUrl, FILTER_VALIDATE_URL)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Gateway URL is not a valid URL.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = 0;

        if ($gwUrl !== '') {
            $this->upsertSettingValue('EATRIA_BRIDGE_URL', rtrim($gwUrl, '/'));
            $saved++;
        }

        if ($token !== '') {
            $this->upsertSettingValue('EATRIA_BRIDGE_TOKEN', $token);
            $saved++;
        }

        if ($hfrId !== '') {
            $this->upsertSettingValue('ABDM_HFR_ID', $hfrId);
            $saved++;
        }

        if ($hmsName !== '') {
            $this->upsertSettingValue('ABDM_HMS_NAME', $hmsName);
            $saved++;
        }

        // Always set connector to eatria_bridge when saving from this page
        $this->upsertSettingValue('ABDM_CONNECTOR', 'eatria_bridge');
        $this->upsertSettingValue('ABDM_SYNC_PROVIDER', 'eatria');

        if ($saved === 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Provide at least one value to save.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $storedToken = $this->readSettingValue('EATRIA_BRIDGE_TOKEN');

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'ABDM Gateway settings saved.',
            'token_exists'  => $storedToken !== '',
            'token_masked'  => $this->maskKey($storedToken),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function testConnection()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->canManageSettings()) {
            return $this->response->setStatusCode(403)->setJSON(['update' => 0, 'error_text' => 'Access denied']);
        }

        // Use posted values first, then stored settings
        $gwUrl = trim((string) $this->request->getPost('gateway_url'));
        $token = trim((string) $this->request->getPost('api_token'));

        if ($gwUrl === '') {
            $gwUrl = $this->readSettingValue('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api';
        }
        if ($token === '') {
            $token = $this->readSettingValue('EATRIA_BRIDGE_TOKEN');
        }

        $gwUrl = rtrim($gwUrl, '/');
        $healthUrl = $gwUrl . '/v3/health';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $healthUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $raw     = curl_exec($ch);
        $code    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Connection failed: ' . $curlErr,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        if ($code !== 200) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Gateway returned HTTP ' . $code,
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $body = json_decode((string) $raw, true);
        $mode = (string) ($body['mode'] ?? 'unknown');
        $ver  = (string) ($body['version'] ?? '');

        // Test Bearer auth with /api/v3/gateway/status
        $authOk = false;
        $authMsg = '';
        if ($token !== '') {
            $ch2 = curl_init();
            curl_setopt_array($ch2, [
                CURLOPT_URL            => $gwUrl . '/v3/gateway/status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token,
                ],
            ]);
            $raw2  = curl_exec($ch2);
            $code2 = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            $body2  = json_decode((string) $raw2, true);
            $authOk = $code2 === 200 && isset($body2['ok']) && $body2['ok'] == 1;
            $authMsg = $authOk
                ? 'API key authenticated ✓'
                : ('Auth check: HTTP ' . $code2 . ($code2 === 403 ? ' — invalid API key' : ''));
        }

        return $this->response->setJSON([
            'update' => 1,
            'error_text' => 'Gateway reachable — mode: ' . $mode . ($ver !== '' ? ', v' . $ver : ''),
            'mode'    => $mode,
            'version' => $ver,
            'auth_ok' => $authOk,
            'auth_msg' => $authMsg,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers (same pattern as HealthplixSettings)
    // -------------------------------------------------------------------------

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
            $v = trim((string) constant($name));
            if ($v !== '') {
                return $v;
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

        return trim((string) ($row['s_value'] ?? ''));
    }

    private function upsertSettingValue(string $name, string $value): bool
    {
        $existing = $this->db->table('hospital_setting')
            ->select('id, s_value')
            ->where('s_name', $name)
            ->get(1)
            ->getRowArray();

        if ($existing) {
            return (bool) $this->db->table('hospital_setting')
                ->where('id', (int) $existing['id'])
                ->update(['s_value' => $value]);
        }

        return (bool) $this->db->table('hospital_setting')->insert([
            's_name'  => $name,
            's_value' => $value,
        ]);
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
