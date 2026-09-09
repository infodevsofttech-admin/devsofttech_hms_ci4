<?php

namespace App\Libraries\Abdm;

/**
 * AbdmV3DirectHelper
 *
 * Direct ABDM V3 Client for Find ABHA via Mobile & Account Login.
 * Compliant with ABDM M1 Guidelines:
 *   MOBILE NUMBER -> PROFILES -> OTP -> COMPLETE PROFILE FETCHED
 *
 * Uses ABDM Sandbox / Production endpoints with RSA-OAEP encryption.
 */
class AbdmV3DirectHelper
{
    protected string $clientId     = 'SBXID_033661';
    protected string $clientSecret = '656f79f1-ef99-495f-9f37-713219ecbbcf';
    protected string $sessionsUrl  = 'https://dev.abdm.gov.in/api/hiecm/gateway/v3/sessions';
    protected string $certUrl      = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/public/certificate';
    protected string $searchUrl    = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/account/abha/search';
    protected string $otpUrl       = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/login/request/otp';
    protected string $verifyUrl    = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/login/verify';
    protected string $cardUrl      = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/account/abha-card';
    protected string $profileUrl   = 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/account';

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings(): void
    {
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('hospital_setting')) {
                $rows = $db->table('hospital_setting')
                    ->whereIn('s_name', ['ABDM_CLIENT_ID', 'ABDM_CLIENT_SECRET'])
                    ->get()->getResultArray();
                foreach ($rows as $r) {
                    $name = trim((string) ($r['s_name'] ?? ''));
                    $val  = trim((string) ($r['s_value'] ?? ''));
                    if ($name === 'ABDM_CLIENT_ID' && $val !== '') {
                        $this->clientId = $val;
                    }
                    if ($name === 'ABDM_CLIENT_SECRET' && $val !== '') {
                        $this->clientSecret = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
            if (function_exists('log_message')) {
                log_message('warning', '[AbdmV3DirectHelper] loadSettings failed: ' . $e->getMessage());
            }
        }
    }

    protected function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    protected function curlCall(string $url, string $method, array $body = [], array $headers = []): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        } elseif ($method !== 'GET') {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            if (! empty($body)) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        }

        curl_setopt_array($ch, $opts);
        $raw  = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        $json = json_decode((string) $raw, true);
        return [
            'code' => $code,
            'raw'  => (string) $raw,
            'err'  => $err,
            'json' => is_array($json) ? $json : [],
        ];
    }

    /**
     * Get or refresh gateway session Bearer token.
     */
    public function getSessionToken(): string
    {
        $cacheKey = 'abdm_v3_direct_session_token';
        if (function_exists('cache')) {
            try {
                $cached = cache()->get($cacheKey);
                if (is_string($cached) && trim($cached) !== '') {
                    return trim($cached);
                }
            } catch (\Throwable $e) {}
        }

        $resp = $this->curlCall($this->sessionsUrl, 'POST', [
            'clientId'     => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'grantType'    => 'client_credentials',
        ], [
            'REQUEST-ID: ' . $this->generateUuid(),
            'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
            'X-CM-ID: sbx',
        ]);

        $token = (string) ($resp['json']['accessToken'] ?? '');
        $expiresIn = (int) ($resp['json']['expiresIn'] ?? 1200);

        if ($token !== '' && function_exists('cache')) {
            try {
                cache()->save($cacheKey, $token, max(300, $expiresIn - 120));
            } catch (\Throwable $e) {}
        }

        return $token;
    }

    /**
     * Get or refresh ABDM RSA Public Certificate PEM.
     */
    public function getPublicCertificatePem(): string
    {
        $cacheKey = 'abdm_v3_direct_public_cert_pem';
        if (function_exists('cache')) {
            try {
                $cached = cache()->get($cacheKey);
                if (is_string($cached) && trim($cached) !== '') {
                    return trim($cached);
                }
            } catch (\Throwable $e) {}
        }

        $token = $this->getSessionToken();
        if ($token === '') {
            throw new \RuntimeException('Failed to obtain ABDM session token for certificate fetch.');
        }

        $resp = $this->curlCall($this->certUrl, 'GET', [], [
            'Authorization: Bearer ' . $token,
            'REQUEST-ID: ' . $this->generateUuid(),
            'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
        ]);

        $rawKey = (string) ($resp['json']['publicKey'] ?? $resp['json']['Certificate'] ?? $resp['raw'] ?? '');
        if (trim($rawKey) === '') {
            throw new \RuntimeException('ABDM public certificate is empty.');
        }

        if (! str_contains($rawKey, '-----BEGIN')) {
            $pemKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(trim($rawKey), 64, "\n") . "-----END PUBLIC KEY-----\n";
        } else {
            $pemKey = $rawKey;
        }

        if (function_exists('cache')) {
            try {
                cache()->save($cacheKey, $pemKey, 86400);
            } catch (\Throwable $e) {}
        }

        return $pemKey;
    }

    /**
     * RSA-OAEP encrypt text using ABDM public key.
     */
    public function encrypt(string $plainText): string
    {
        $pem = $this->getPublicCertificatePem();
        $pubKey = openssl_pkey_get_public($pem);
        if ($pubKey === false) {
            throw new \RuntimeException('Invalid ABDM public key resource: ' . openssl_error_string());
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($plainText, $encrypted, $pubKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $ok) {
            throw new \RuntimeException('RSA-OAEP encryption failed: ' . openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Step 1: Search ABHA profiles by 10-digit mobile number.
     * Upstream: POST /abha/api/v3/profile/account/abha/search
     *
     * @return array{ok: int, txn_id?: string, profiles?: array, error_text?: string}
     */
    public function searchByMobile(string $mobile): array
    {
        $digits = preg_replace('/\D/', '', $mobile);
        if (strlen($digits) !== 10) {
            return ['ok' => 0, 'error_text' => 'Valid 10-digit mobile number is required.'];
        }

        try {
            $token = $this->getSessionToken();
            if ($token === '') {
                return ['ok' => 0, 'error_text' => 'Could not obtain ABDM session token.'];
            }

            $encMobile = $this->encrypt($digits);
            $resp = $this->curlCall($this->searchUrl, 'POST', [
                'scope'  => ['search-abha'],
                'mobile' => $encMobile,
            ], [
                'Authorization: Bearer ' . $token,
                'REQUEST-ID: ' . $this->generateUuid(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-Client-ID: ' . $this->clientId,
            ]);

            if ($resp['code'] >= 200 && $resp['code'] < 300 && ! empty($resp['json'])) {
                $firstBlock = is_array($resp['json']) && isset($resp['json'][0]) ? $resp['json'][0] : $resp['json'];
                $txnId = (string) ($firstBlock['txnId'] ?? '');
                $rawProfiles = (array) ($firstBlock['ABHA'] ?? $firstBlock['accounts'] ?? []);

                $profiles = [];
                foreach ($rawProfiles as $p) {
                    $methods = [];
                    foreach ((array) ($p['authMethods'] ?? []) as $m) {
                        $mUpper = strtoupper(trim((string) $m));
                        if ($mUpper !== '' && ! in_array($mUpper, $methods, true)) {
                            $methods[] = $mUpper;
                        }
                    }
                    if (empty($methods)) {
                        $methods = ['MOBILE_OTP', 'AADHAAR_OTP'];
                    }

                    $profiles[] = [
                        'index'         => (string) ($p['index'] ?? (count($profiles) + 1)),
                        'abha_number'   => (string) ($p['ABHANumber'] ?? $p['abhaNumber'] ?? $p['abha_id'] ?? ''),
                        'name'          => (string) ($p['name'] ?? ''),
                        'gender'        => (string) ($p['gender'] ?? ''),
                        'dob'           => (string) ($p['dob'] ?? $p['dateOfBirth'] ?? ''),
                        'kyc_verified'  => (string) ($p['kycVerified'] ?? 'true') === 'true',
                        'auth_methods'  => $methods,
                        'profile_photo' => (string) ($p['profilePhoto'] ?? ''),
                    ];
                }

                return [
                    'ok'       => 1,
                    'txn_id'   => $txnId,
                    'profiles' => $profiles,
                ];
            }

            $errMsg = (string) ($resp['json']['message'] ?? $resp['json']['error']['message'] ?? 'ABDM search returned no accounts for this mobile.');
            return ['ok' => 0, 'error_text' => $errMsg, 'raw' => $resp['raw']];
        } catch (\Throwable $e) {
            log_message('error', '[AbdmV3DirectHelper] searchByMobile exception: ' . $e->getMessage());
            return ['ok' => 0, 'error_text' => $e->getMessage()];
        }
    }

    /**
     * Step 2: Request OTP for a selected profile index.
     * Upstream: POST /abha/api/v3/profile/login/request/otp
     *
     * @return array{ok: int, txn_id?: string, message?: string, error_text?: string}
     */
    public function requestProfileOtp(string $searchTxnId, string $index, string $authMethod = 'MOBILE_OTP'): array
    {
        $authMethod = strtoupper(trim($authMethod));
        $isAadhaar  = in_array($authMethod, ['AADHAAR_OTP', 'AADHAAR', 'AADHAAR-VERIFY'], true);

        try {
            $token = $this->getSessionToken();
            if ($token === '') {
                return ['ok' => 0, 'error_text' => 'Could not obtain ABDM session token.'];
            }

            $encIndex = $this->encrypt((string) $index);
            $scope = $isAadhaar
                ? ['abha-login', 'search-abha', 'aadhaar-verify']
                : ['abha-login', 'search-abha', 'mobile-verify'];
            $otpSystem = $isAadhaar ? 'aadhaar' : 'abdm';

            $payload = [
                'scope'     => $scope,
                'loginHint' => 'index',
                'loginId'   => $encIndex,
                'otpSystem' => $otpSystem,
                'txnId'     => $searchTxnId,
            ];

            $resp = $this->curlCall($this->otpUrl, 'POST', $payload, [
                'Authorization: Bearer ' . $token,
                'REQUEST-ID: ' . $this->generateUuid(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-Client-ID: ' . $this->clientId,
            ]);

            if ($resp['code'] >= 200 && $resp['code'] < 300 && ! empty($resp['json'])) {
                $newTxnId = (string) ($resp['json']['txnId'] ?? $searchTxnId);
                $msg = (string) ($resp['json']['message'] ?? 'OTP sent successfully.');
                return [
                    'ok'      => 1,
                    'txn_id'  => $newTxnId,
                    'message' => $msg,
                ];
            }

            $errMsg = (string) ($resp['json']['message'] ?? $resp['json']['error']['message'] ?? 'Failed to request OTP from ABDM.');
            return ['ok' => 0, 'error_text' => $errMsg, 'raw' => $resp['raw']];
        } catch (\Throwable $e) {
            log_message('error', '[AbdmV3DirectHelper] requestProfileOtp exception: ' . $e->getMessage());
            return ['ok' => 0, 'error_text' => $e->getMessage()];
        }
    }

    /**
     * Step 3: Verify the OTP and obtain full profile + tokens.
     * Upstream: POST /abha/api/v3/profile/login/verify
     *
     * @return array{ok: int, token?: string, accounts?: array, profile?: array, error_text?: string}
     */
    public function verifyProfileOtp(string $otpTxnId, string $otp, string $authMethod = 'MOBILE_OTP'): array
    {
        $digits = preg_replace('/\D/', '', $otp);
        if (strlen($digits) !== 6) {
            return ['ok' => 0, 'error_text' => 'Enter a valid 6-digit OTP.'];
        }

        $authMethod = strtoupper(trim($authMethod));
        $isAadhaar  = in_array($authMethod, ['AADHAAR_OTP', 'AADHAAR', 'AADHAAR-VERIFY'], true);

        try {
            $token = $this->getSessionToken();
            if ($token === '') {
                return ['ok' => 0, 'error_text' => 'Could not obtain ABDM session token.'];
            }

            $encOtp = $this->encrypt($digits);
            $scope = $isAadhaar
                ? ['abha-login', 'aadhaar-verify']
                : ['abha-login', 'mobile-verify'];

            $payload = [
                'scope'    => $scope,
                'authData' => [
                    'authMethods' => ['otp'],
                    'otp'         => [
                        'txnId'    => $otpTxnId,
                        'otpValue' => $encOtp,
                    ],
                ],
            ];

            $resp = $this->curlCall($this->verifyUrl, 'POST', $payload, [
                'Authorization: Bearer ' . $token,
                'REQUEST-ID: ' . $this->generateUuid(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-Client-ID: ' . $this->clientId,
            ]);

            if ($resp['code'] >= 200 && $resp['code'] < 300 && ! empty($resp['json'])) {
                $authResult = strtolower((string) ($resp['json']['authResult'] ?? ''));
                if ($authResult === 'failed') {
                    $failMsg = (string) ($resp['json']['message'] ?? 'Invalid OTP entered.');
                    return ['ok' => 0, 'error_text' => $failMsg];
                }

                $userToken = (string) ($resp['json']['token'] ?? '');
                $accounts  = (array) ($resp['json']['accounts'] ?? []);
                $firstAcc  = isset($accounts[0]) && is_array($accounts[0]) ? $accounts[0] : [];

                // Attempt to fetch full profile details and ABHA card using the userToken
                $fullProfile = [];
                $cardBase64  = '';
                if ($userToken !== '') {
                    $fullProfile = $this->fetchProfileDetails($userToken, $token);
                    $cardBase64  = $this->fetchCardPng($userToken, $token);
                }

                return [
                    'ok'           => 1,
                    'txn_id'       => (string) ($resp['json']['txnId'] ?? $otpTxnId),
                    'token'        => $userToken,
                    'accounts'     => $accounts,
                    'account'      => $firstAcc,
                    'full_profile' => $fullProfile,
                    'card_base64'  => $cardBase64,
                ];
            }

            $errMsg = (string) ($resp['json']['message'] ?? $resp['json']['error']['message'] ?? 'OTP verification failed.');
            return ['ok' => 0, 'error_text' => $errMsg, 'raw' => $resp['raw']];
        } catch (\Throwable $e) {
            log_message('error', '[AbdmV3DirectHelper] verifyProfileOtp exception: ' . $e->getMessage());
            return ['ok' => 0, 'error_text' => $e->getMessage()];
        }
    }

    /**
     * Fetch full profile details using the profile X-Token.
     * Upstream: GET /abha/api/v3/profile/account
     */
    public function fetchProfileDetails(string $xToken, string $gatewayToken = ''): array
    {
        try {
            $token = $gatewayToken !== '' ? $gatewayToken : $this->getSessionToken();
            $headers = [
                'X-Token: Bearer ' . $xToken,
                'REQUEST-ID: ' . $this->generateUuid(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-Client-ID: ' . $this->clientId,
            ];
            if ($token !== '') {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
            $resp = $this->curlCall($this->profileUrl, 'GET', [], $headers);
            return is_array($resp['json']) ? $resp['json'] : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fetch official ABHA card base64 image using profile X-Token.
     * Upstream: GET /abha/api/v3/profile/account/abha-card
     */
    public function fetchCardPng(string $xToken, string $gatewayToken = ''): string
    {
        try {
            $token = $gatewayToken !== '' ? $gatewayToken : $this->getSessionToken();
            $headers = [
                'X-Token: Bearer ' . $xToken,
                'REQUEST-ID: ' . $this->generateUuid(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-Client-ID: ' . $this->clientId,
            ];
            if ($token !== '') {
                $headers[] = 'Authorization: Bearer ' . $token;
            }
            $ch = curl_init($this->cardUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code >= 200 && $code < 300 && ! empty($raw)) {
                // If returned as raw image binary, base64 encode it
                if (! str_contains($raw, '{')) {
                    return base64_encode($raw);
                }
                // If returned as JSON with base64
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return (string) ($decoded['card'] ?? $decoded['card_base64'] ?? '');
                }
            }
        } catch (\Throwable $e) {}
        return '';
    }
}
