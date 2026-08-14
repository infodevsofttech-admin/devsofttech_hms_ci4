<?php

namespace App\Libraries;

/**
 * Aadhaar Vault
 *
 * The Aadhaar Act forbids storing the full Aadhaar number in readable form, so
 * patient_master keeps only a masked value while the number itself is held
 * encrypted (AES-256-GCM) alongside a deterministic HMAC used for lookups.
 *
 * Key configuration — add to .env:
 *   AADHAAR_ENCRYPTION_KEY = <64-char lowercase hex string — 32 random bytes>
 *
 * Generate with:
 *   php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
 */
class AadhaarVaultService
{
    private const CIPHER  = 'aes-256-gcm';
    private const IV_LEN  = 12;
    private const TAG_LEN = 16;

    private string $key;

    public function __construct()
    {
        $keyHex = trim((string) getenv('AADHAAR_ENCRYPTION_KEY'));
        if ($keyHex === '') {
            $keyHex = trim((string) env('AADHAAR_ENCRYPTION_KEY', ''));
        }
        if ($keyHex === '') {
            $keyHex = trim((string) getenv('ABDM_FHIR_ENCRYPTION_KEY'));
        }

        if ($keyHex !== '' && ctype_xdigit($keyHex) && strlen($keyHex) >= 64) {
            $this->key = (string) hex2bin(substr($keyHex, 0, 64));
        } else {
            $appKey    = trim((string) env('encryption.key', (string) env('app.key', '')));
            $this->key = hash_hmac('sha256', 'hms-aadhaar-vault-v1', $appKey, true);
        }
    }

    /**
     * Strip formatting and return the 12 digits, or '' when not a valid Aadhaar.
     */
    public function normalize(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw) ?? '';

        return strlen($digits) === 12 ? $digits : '';
    }

    public function last4(?string $raw): string
    {
        $digits = $this->normalize($raw);

        return $digits === '' ? '' : substr($digits, -4);
    }

    /**
     * Display form kept in patient_master.udai so legacy screens cannot leak the number.
     */
    public function mask(?string $raw): string
    {
        $last4 = $this->last4($raw);

        return $last4 === '' ? '' : 'XXXXXXXX' . $last4;
    }

    /**
     * Deterministic keyed digest so an exact-match search stays possible.
     */
    public function hash(?string $raw): string
    {
        $digits = $this->normalize($raw);

        return $digits === '' ? '' : hash_hmac('sha256', $digits, $this->key);
    }

    public function encrypt(?string $raw): string
    {
        $digits = $this->normalize($raw);
        if ($digits === '') {
            return '';
        }

        $iv  = random_bytes(self::IV_LEN);
        $tag = '';

        $ciphertext = openssl_encrypt($digits, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($ciphertext === false) {
            throw new \RuntimeException('Aadhaar encryption failed.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Returns '' when the payload is empty or cannot be authenticated.
     */
    public function decrypt(?string $payload): string
    {
        $payload = trim((string) $payload);
        if ($payload === '') {
            return '';
        }

        $binary = base64_decode($payload, true);
        if ($binary === false || strlen($binary) <= self::IV_LEN + self::TAG_LEN) {
            return '';
        }

        $iv         = substr($binary, 0, self::IV_LEN);
        $tag        = substr($binary, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($binary, self::IV_LEN + self::TAG_LEN);

        $plain = openssl_decrypt($ciphertext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? '' : $plain;
    }

    /**
     * True when the value is the masked form we render back into edit forms,
     * which must never be treated as a new Aadhaar number.
     */
    public function isMasked(?string $value): bool
    {
        return preg_match('/^X{8}\d{4}$/i', trim((string) $value)) === 1;
    }

    /**
     * Column values for a patient_master insert/update.
     *
     * @return array{udai:string,udai_enc:string,udai_hash:string,udai_last4:string}
     */
    public function buildColumns(?string $raw): array
    {
        $digits = $this->normalize($raw);
        if ($digits === '') {
            return ['udai' => '', 'udai_enc' => '', 'udai_hash' => '', 'udai_last4' => ''];
        }

        return [
            'udai'       => $this->mask($digits),
            'udai_enc'   => $this->encrypt($digits),
            'udai_hash'  => $this->hash($digits),
            'udai_last4' => $this->last4($digits),
        ];
    }
}
