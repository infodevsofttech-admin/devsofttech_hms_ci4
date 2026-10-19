<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Enforces Server-Side Authentication & Authorization for API v1 Endpoints (WASA CWE-306 Remediation)
 */
class ApiAuthFilter implements FilterInterface
{
    /**
     * Endpoints that do not require prior authentication (e.g. login/verify-pin, live TV queue display)
     */
    protected array $exemptPaths = [
        'api/v1/nursing/auth/verify-pin',
        'api/v1/nursing/auth/set-pin',
        'api/v1/doctor/auth/verify-pin',
        'api/v1/doctor/auth/set-pin',
        'api/v1/opd/queue/live',
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow CORS preflight OPTIONS requests
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        $uriPath = trim((string) ($request->getUri()->getPath() ?? ''), '/');

        // Check if path is explicitly exempt
        foreach ($this->exemptPaths as $exempt) {
            if ($uriPath === $exempt || str_ends_with($uriPath, $exempt)) {
                return null;
            }
        }

        // 1. Check active user session (CodeIgniter Shield / HMS Admin)
        if (function_exists('auth') && auth()->loggedIn()) {
            return null;
        }

        $session = session();
        if ($session->has('user_id') && (int) $session->get('user_id') > 0) {
            return null;
        }

        // 2. Check active PWA role-based sessions
        if ($session->has('pwa_auth_nurse_id') && (int) $session->get('pwa_auth_nurse_id') > 0) {
            return null;
        }

        if ($session->has('pwa_auth_doctor_id') && (int) $session->get('pwa_auth_doctor_id') > 0) {
            return null;
        }

        if ($session->has('pwa_auth_store_id') && (int) $session->get('pwa_auth_store_id') > 0) {
            return null;
        }

        // 3. Check token from header or request parameters
        $token = trim((string) $request->getHeaderLine('X-Device-Token'));
        if ($token === '') {
            $authHeader = trim((string) $request->getHeaderLine('Authorization'));
            if (preg_match('/Bearer\s+([a-f0-9]{64})/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if ($token === '') {
            $token = trim((string) ($request->getGet('device_token') ?? $request->getPost('device_token') ?? ''));
        }

        if ($token !== '' && strlen($token) === 64) {
            if ($this->validateDeviceToken($token)) {
                return null;
            }
        }

        // Authentication failed - return 401 Unauthorized
        return service('response')
            ->setStatusCode(401)
            ->setJSON([
                'status'  => 0,
                'error'   => 'Unauthorized',
                'message' => 'Authentication required. Access to this endpoint requires an active session or valid authorization token.',
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    /**
     * Validates device token against cache or database
     */
    protected function validateDeviceToken(string $token): bool
    {
        $cache = service('cache');
        $cacheKey = 'api_device_token_' . $token;

        if ($cache->get($cacheKey)) {
            return true;
        }

        $db = \Config\Database::connect();

        // Check against active nurses
        $nurses = $db->table('nurse_master')
            ->select('id, nurse_code, device_code')
            ->where('is_active', 1)
            ->where('device_code IS NOT NULL')
            ->where('device_code !=', '')
            ->get()->getResultArray();

        foreach ($nurses as $nurse) {
            $expected = hash_hmac('sha256', $nurse['id'] . '_' . ($nurse['nurse_code'] ?? ''), (string) $nurse['device_code']);
            if (hash_equals($expected, $token)) {
                $cache->save($cacheKey, ['role' => 'nurse', 'id' => (int) $nurse['id']], 86400 * 30);
                return true;
            }
        }

        // Check against active doctors
        $doctors = $db->table('doctor_master')
            ->select('id, device_code')
            ->where('active', 1)
            ->where('device_code IS NOT NULL')
            ->where('device_code !=', '')
            ->get()->getResultArray();

        foreach ($doctors as $doctor) {
            $expected = hash_hmac('sha256', (string) $doctor['id'], (string) $doctor['device_code']);
            if (hash_equals($expected, $token)) {
                $cache->save($cacheKey, ['role' => 'doctor', 'id' => (int) $doctor['id']], 86400 * 30);
                return true;
            }
        }

        return false;
    }
}
