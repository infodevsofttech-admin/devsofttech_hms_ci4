<?php

namespace App\Libraries;

/**
 * FHIR Payload Encryption Service
 *
 * Provides AES-256-GCM authenticated encryption for FHIR bundle JSON
 * payloads stored at rest in the health_records table.
 *
 * Key configuration — add to .env:
 *   ABDM_FHIR_ENCRYPTION_KEY = <64-char lowercase hex string — 32 random bytes>
 *
 * Generate a key with:
 *   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
 *
 * If ABDM_FHIR_ENCRYPTION_KEY is absent the service falls back to an HMAC
 * of the application's existing encryption.key with a fixed domain label.
 * This guarantees the service always works, but a dedicated key is strongly
 * recommended for production.
 */
class FhirEncryptionService
{
    private const CIPHER  = 'aes-256-gcm';
    private const IV_LEN  = 12;  // 96-bit IV (recommended for GCM)
    private const TAG_LEN = 16;  // 128-bit authentication tag

    private string $key;

    public function __construct()
    {
        // Prefer getenv() so the value is available before CI4 bootstraps .env
        $keyHex = trim((string) getenv('ABDM_FHIR_ENCRYPTION_KEY'));
        if ($keyHex === '') {
            $keyHex = trim((string) env('ABDM_FHIR_ENCRYPTION_KEY', ''));
        }

        if ($keyHex !== '' && ctype_xdigit($keyHex) && strlen($keyHex) >= 64) {
            // Decode exactly 32 bytes from the first 64 hex chars
            $this->key = (string) hex2bin(substr($keyHex, 0, 64));
        } else {
            // Fallback: derive a 32-byte key from the CI4 application key
            $appKey    = trim((string) env('encryption.key', (string) env('app.key', '')));
            $this->key = hash_hmac('sha256', 'abdm-fhir-records-v1', $appKey, true);
        }
    }

    /**
     * Encrypt plaintext with AES-256-GCM.
     *
     * Output format (base64-encoded): IV (12 B) | TAG (16 B) | CIPHERTEXT
     *
     * @throws \RuntimeException on OpenSSL failure
     */
    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $iv  = random_bytes(self::IV_LEN);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($ciphertext === false) {
            throw new \RuntimeException(
                'FHIR payload encryption failed: ' . (openssl_error_string() ?: 'unknown OpenSSL error')
            );
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a payload produced by encrypt().
     *
     * The GCM authentication tag is verified automatically; a mismatch
     * (indicating tampering or a wrong key) throws a RuntimeException.
     *
     * @throws \RuntimeException on decryption or authentication failure
     */
    public function decrypt(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }

        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) < self::IV_LEN + self::TAG_LEN + 1) {
            throw new \RuntimeException('Invalid encrypted FHIR payload: data is truncated or corrupt');
        }

        $iv         = substr($raw, 0, self::IV_LEN);
        $tag        = substr($raw, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($raw, self::IV_LEN + self::TAG_LEN);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException(
                'FHIR payload decryption failed: authentication tag mismatch or corrupt data'
            );
        }

        return $plaintext;
    }

    /**
     * Returns true when the current PHP build supports AES-256-GCM.
     */
    public static function isSupported(): bool
    {
        return in_array(self::CIPHER, openssl_get_cipher_methods(), true);
    }
}
