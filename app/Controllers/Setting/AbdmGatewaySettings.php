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
        if ($hfrId === '') {
            $hfrId = $this->readSettingValue('H_HFR_ID');
        }
        $hmsName  = $this->readSettingValue('ABDM_HMS_NAME');
        $gwUrl    = $this->readSettingValue('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api';
        $bridgeHospitalId = $this->readSettingValue('ABDM_BRIDGE_HOSPITAL_ID');
        $sslVerifyRaw = strtolower(trim($this->readSettingValue('ABDM_BRIDGE_SSL_VERIFY')));
        $sslVerify = ! in_array($sslVerifyRaw, ['0', 'false', 'no', 'off'], true);

        return view('Setting/Admin/abdm_gateway_settings', [
            'gateway_url'          => $gwUrl,
            'hfr_id'               => $hfrId,
            'hms_name'             => $hmsName,
            'bridge_hospital_id'   => $bridgeHospitalId,
            'token_masked'         => $this->maskKey($token),
            'token_exists'         => $token !== '',
            'connector'            => $this->readSettingValue('ABDM_CONNECTOR') ?: 'eatria_bridge',
            'abdm_sync_provider'   => $this->readSettingValue('ABDM_SYNC_PROVIDER') ?: 'eatria',
            'ssl_verify'           => $sslVerify,
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
        $token  = $this->sanitizeBearerToken((string) $this->request->getPost('api_token'));
        $hfrId  = trim((string) $this->request->getPost('hfr_id'));
        $hmsName = trim((string) $this->request->getPost('hms_name'));
        $sslVerifyPosted = $this->request->getPost('ssl_verify');
        $sslVerify = $sslVerifyPosted === null ? true : (bool) (int) $sslVerifyPosted;

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
        $this->upsertSettingValue('ABDM_BRIDGE_SSL_VERIFY', $sslVerify ? '1' : '0');

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
            'ssl_verify'    => $sslVerify,
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
        $token = $this->sanitizeBearerToken((string) $this->request->getPost('api_token'));
        $hfrId = trim((string) $this->request->getPost('hfr_id'));

        if ($gwUrl === '') {
            $gwUrl = $this->readSettingValue('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api';
        }
        if ($token === '') {
            $token = $this->readSettingValue('EATRIA_BRIDGE_TOKEN');
        }
        if ($hfrId === '') {
            $hfrId = $this->readSettingValue('ABDM_HFR_ID');
            if ($hfrId === '') {
                $hfrId = $this->readSettingValue('H_HFR_ID');
            }
        }

        $gwUrl = rtrim($gwUrl, '/');

        // ssl_verify: prefer explicitly posted value (from the unsaved form state),
        // else fall back to the stored setting. Defaults to secure (verify ON).
        $sslVerifyPosted = $this->request->getPost('ssl_verify');
        if ($sslVerifyPosted !== null) {
            $sslVerify = (bool) (int) $sslVerifyPosted;
        } else {
            $sslVerifyRaw = strtolower(trim($this->readSettingValue('ABDM_BRIDGE_SSL_VERIFY')));
            $sslVerify = ! in_array($sslVerifyRaw, ['0', 'false', 'no', 'off'], true);
        }

        // ── Step 1: Health check — include Bearer if available (gateway may require auth even on /health) ──
        $healthUrl     = $gwUrl . '/v3/health' . ($hfrId !== '' ? '?hfr_id=' . urlencode($hfrId) : '');
        $healthHeaders = ['Accept: application/json'];
        if ($token !== '') {
            $healthHeaders[] = 'Authorization: Bearer ' . $token;
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $healthUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER     => $healthHeaders,
        ]);
        $rawHealth     = (string) curl_exec($ch);
        $codeHealth    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrHealth = curl_error($ch);
        curl_close($ch);

        if ($curlErrHealth !== '') {
            $this->writeTestLog($healthUrl, 'GET', [], 0, '', 'error', 'cURL error: ' . $curlErrHealth);
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Cannot reach gateway: ' . $curlErrHealth,
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $healthBody = json_decode($rawHealth, true);
        $healthOk   = $codeHealth === 200 && is_array($healthBody) && ($healthBody['status'] ?? '') === 'ok';

        // If health returned 401/403 with no token, report "no token" not "unreachable"
        if (! $healthOk && in_array($codeHealth, [401, 403], true) && $token === '') {
            $this->writeTestLog($healthUrl, 'GET', [], $codeHealth, $rawHealth, 'error', 'Health check HTTP ' . $codeHealth . ' — no token configured');
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Gateway reachable but API token required. Save your token first then re-test.',
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $hfrIdOk  = (bool) ($healthBody['hfr_id_ok'] ?? false);
        $hfrIdMsg = (string) ($healthBody['hfr_id_msg'] ?? '');
        $mode     = (string) ($healthBody['mode'] ?? 'unknown');
        $version  = (string) ($healthBody['version'] ?? '');

        $this->writeTestLog(
            $healthUrl, 'GET', [], $codeHealth, $rawHealth,
            $healthOk ? 'success' : 'error',
            $healthOk ? '' : ('Health check HTTP ' . $codeHealth)
        );

        if (! $healthOk) {
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Gateway unreachable or health check failed (HTTP ' . $codeHealth . ')',
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        // ── Step 2: Bearer auth check via /v3/gateway/status ───────────────
        if ($token === '') {
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Gateway is reachable' . ($hfrIdMsg !== '' ? ' — ' . $hfrIdMsg : '') . '. But no API token configured — save your token first.',
                'mode'       => $mode,
                'version'    => $version,
                'hfr_id_ok'  => $hfrIdOk,
                'hfr_id_msg' => $hfrIdMsg,
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $statusUrl = $gwUrl . '/v3/gateway/status' . ($hfrId !== '' ? '?hfr_id=' . urlencode($hfrId) : '');
        $ch2 = curl_init();
        curl_setopt_array($ch2, [
            CURLOPT_URL            => $statusUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => $sslVerify,
            CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
        ]);
        $rawStatus     = (string) curl_exec($ch2);
        $codeStatus    = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        $curlErrStatus = curl_error($ch2);
        curl_close($ch2);

        if ($curlErrStatus !== '') {
            $this->writeTestLog($statusUrl, 'GET', [], 0, '', 'error', 'cURL error: ' . $curlErrStatus);
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Auth check failed: ' . $curlErrStatus,
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $statusBody = json_decode($rawStatus, true);
        // Token is valid when authenticated_as is present (ok:0 means ABDM upstream unreachable, not bad token)
        // Per API docs: ok:1 = full success, ok:0+authenticated_as = token ok but ABDM upstream down
        $abdmUpstreamOk = $codeStatus === 200 && is_array($statusBody) && (
            (int) ($statusBody['ok'] ?? 0) === 1 ||
            ($statusBody['status'] ?? '') === 'ok'
        );
        $authOk = $abdmUpstreamOk || (
            $codeStatus === 200 && is_array($statusBody) && !empty($statusBody['authenticated_as'])
        );
        $authInfo = null;

        if ($authOk && isset($statusBody['authenticated_as']) && is_array($statusBody['authenticated_as'])) {
            $a = $statusBody['authenticated_as'];
            $authInfo = [
                'principal'   => (string) ($a['principal']   ?? ''),
                'hospital_id' => (string) ($a['hospital_id'] ?? ''),
                'hfr_id'      => (string) ($a['hfr_id']      ?? ''),
                'type'        => (string) ($a['type']         ?? ''),
            ];

            // Auto-persist hfr_id and principal name if not already stored
            if ($this->db->tableExists('hospital_setting')) {
                if ($authInfo['hfr_id'] !== '' && $this->readSettingValue('ABDM_HFR_ID') === '') {
                    $this->upsertSettingValue('ABDM_HFR_ID', $authInfo['hfr_id']);
                }
                if ($authInfo['hospital_id'] !== '') {
                    $this->upsertSettingValue('ABDM_BRIDGE_HOSPITAL_ID', $authInfo['hospital_id']);
                }
                if ($authInfo['principal'] !== '' && $this->readSettingValue('ABDM_HMS_NAME') === '') {
                    $this->upsertSettingValue('ABDM_HMS_NAME', $authInfo['principal']);
                }
            }
        }

        $authErrMsg = $authOk ? '' : ('HTTP ' . $codeStatus . ($codeStatus === 401 ? ' — invalid API key' : ($codeStatus === 403 ? ' — access denied' : ($codeStatus === 200 ? ' — unexpected response format: ' . mb_substr($rawStatus, 0, 120) : ''))));
        $this->writeTestLog($statusUrl, 'GET', [], $codeStatus, $rawStatus, $authOk ? 'success' : 'error', $authErrMsg);

        if (! $authOk) {
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => 'Gateway reachable' . ($hfrIdMsg !== '' ? ' (' . $hfrIdMsg . ')' : '') . ' but auth failed: ' . $authErrMsg,
                'mode'       => $mode,
                'version'    => $version,
                'hfr_id_ok'  => $hfrIdOk,
                'hfr_id_msg' => $hfrIdMsg,
                'auth_ok'    => false,
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $abdmUpstreamNote = $abdmUpstreamOk ? '' : ' | ⚠ ABDM upstream: unreachable (gateway ok:0)';
        return $this->response->setJSON([
            'update'     => 1,
            'error_text' => 'Gateway OK — ' . ($hfrIdMsg ?: 'connected') . ' | Token authenticated ✓' . $abdmUpstreamNote . ($mode !== 'unknown' ? ' | mode: ' . $mode : '') . ($version !== '' ? ', v' . $version : ''),
            'mode'       => $mode,
            'version'    => $version,
            'hfr_id_ok'  => $hfrIdOk,
            'hfr_id_msg' => $hfrIdMsg,
            'auth_ok'    => true,
            'abdm_upstream_ok' => $abdmUpstreamOk,
            'auth_msg'   => 'API key authenticated ✓',
            'auth_info'  => $authInfo,
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ]);
    }

    /**
     * Write a test-connection entry to abdm_api_logs (fail-open).
     */
    private function writeTestLog(
        string $endpoint,
        string $method,
        array  $requestBody,
        int    $httpCode,
        string $rawResponse,
        string $status,
        string $errorMessage
    ): void {
        try {
            if (! $this->db->tableExists('abdm_api_logs')) {
                return;
            }
            $decoded      = json_decode($rawResponse, true);
            $responseJson = is_array($decoded)
                ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (trim($rawResponse) !== '' ? $rawResponse : null);

            $this->db->table('abdm_api_logs')->insert([
                'channel'       => 'eatria_bridge',
                'event_type'    => 'gateway.test_connection',
                'endpoint'      => $endpoint,
                'http_method'   => strtoupper($method),
                'entity_type'   => null,
                'entity_id'     => null,
                'request_json'  => $requestBody !== [] ? json_encode($requestBody, JSON_UNESCAPED_UNICODE) : null,
                'response_code' => $httpCode > 0 ? $httpCode : null,
                'response_json' => $responseJson,
                'status'        => $status,
                'error_message' => $errorMessage !== '' ? mb_substr($errorMessage, 0, 1000) : null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('warning', '[AbdmGatewaySettings] writeTestLog failed: ' . $e->getMessage());
        }
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

    private function sanitizeBearerToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }
        if (stripos($token, 'Bearer ') === 0) {
            $token = trim(substr($token, 7));
        }
        return trim($token, " \t\n\r\0\x0B\"'");
    }
}
