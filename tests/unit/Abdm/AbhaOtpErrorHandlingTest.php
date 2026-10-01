<?php

namespace Tests\Unit\Abdm;

use App\Controllers\Abha;
use CodeIgniter\Test\CIUnitTestCase;
use ReflectionClass;

final class AbhaOtpErrorHandlingTest extends CIUnitTestCase
{
    private Abha $controller;
    private \ReflectionMethod $extractMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Abha();
        $ref = new ReflectionClass($this->controller);
        $this->extractMethod = $ref->getMethod('extractBridgeErrorText');
        $this->extractMethod->setAccessible(true);
    }

    public function testExtractBridgeErrorTextMapsUidai400ToIncorrectOtp(): void
    {
        $payload = [
            'error' => [
                'code' => 'ABDM-1204',
                'message' => 'UIDAI Error code : 400 : Invalid Aadhaar OTP value.',
            ],
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'Please enter a valid OTP.');
        $this->assertSame('Incorrect OTP', $result);
    }

    public function testExtractBridgeErrorTextMapsUidai403ToAttemptsExceeded(): void
    {
        $payload = [
            'error_text' => 'UIDAI Error code : 403 : Maximum number of attempts for OTP match is exceeded or OTP is not generated. Please generate a fresh OTP and try to authenticate again',
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'Please enter a valid OTP.');
        $this->assertStringContainsString('Incorrect OTP', $result);
        $this->assertStringContainsString('Maximum number of attempts exceeded', $result);
        $this->assertStringNotContainsString('403', $result);
        $this->assertStringNotContainsString('UIDAI Error code', $result);
    }

    public function testExtractBridgeErrorTextStripsRawErrorCodesFromGenericOtpError(): void
    {
        $payload = [
            'message' => 'ABDM-1204 : Invalid OTP entered by user',
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'Please enter a valid OTP.');
        $this->assertSame('Incorrect OTP', $result);
    }

    public function testExtractBridgeErrorTextFallbackNormalizesOtpText(): void
    {
        $payload = [];
        $result = $this->extractMethod->invoke($this->controller, $payload, 'Please enter a valid OTP. Entered OTP is either expired or incorrect.');
        $this->assertSame('Incorrect OTP', $result);
    }

    public function testExtractBridgeErrorTextSkipsGenericUpstreamErrorAndExtractsLoginHint(): void
    {
        $payload = [
            'ok' => 0,
            'error' => [
                'source' => 'abdm_gateway',
                'code' => 'ABDM_UPSTREAM_ERROR',
                'message' => 'ABDM gateway returned an error.',
                'http_status' => 400,
            ],
            'data' => [
                'loginHint' => 'Invalid Login Hint',
                'timestamp' => '2026-10-01 16:20:23',
            ],
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'The ABDM Bridge could not send the OTP.');
        $this->assertStringContainsString('ABDM gateway rejected login hint', $result);
        $this->assertStringContainsString('14-digit ABHA Number', $result);
        $this->assertStringNotContainsString('ABDM gateway returned an error.', $result);
    }

    public function testExtractBridgeErrorTextNormalizesUnknownColumnPreferredAbhaAddress(): void
    {
        $payload = [
            'ok' => 0,
            'error' => "Unknown column 'preferredAbhaAddress' in 'where clause'",
            'request_id' => 'REQ-20261001170423-8270515b',
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'ABHA validation failed');
        $this->assertStringContainsString('ABHA Address does not exist or was not found in the ABDM registry', $result);
    }

    public function testExtractBridgeErrorTextNormalizesUnknownColumnForAbhaNumber(): void
    {
        $payload = [
            'ok' => 0,
            'type' => 'abha-number',
            'value' => '91987654321032',
            'error' => "Unknown column 'preferredAbhaAddress' in 'where clause'",
            'request_id' => 'REQ-20261001181024-934fd7ae',
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'ABHA validation failed');
        $this->assertStringContainsString('ABHA Number does not exist or was not found in the ABDM registry', $result);
    }

    public function testExtractBridgeErrorTextPreservesGenericWhenNoFieldErrors(): void
    {
        $payload = [
            'ok' => 0,
            'error' => [
                'source' => 'abdm_gateway',
                'code' => 'ABDM_UPSTREAM_ERROR',
                'message' => 'ABDM gateway returned an error.',
                'http_status' => 400,
            ],
        ];

        $result = $this->extractMethod->invoke($this->controller, $payload, 'The ABDM Bridge could not send the OTP.');
        $this->assertSame('ABDM gateway returned an error.', $result);
    }
}

