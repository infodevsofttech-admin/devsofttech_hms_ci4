<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Auth as AuthConfig;

/**
 * AuthRedirectTest
 *
 * Validates post-login redirection sanitization in AuthConfig::loginRedirect().
 * Prevents API, AJAX, polling, and background routes (e.g. AbdmOpdQueue/fetch)
 * from hijacking post-login landing pages.
 *
 * @internal
 */
final class AuthRedirectTest extends CIUnitTestCase
{
    protected AuthConfig $authConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authConfig = config('Auth');
    }

    protected function tearDown(): void
    {
        session()->removeTempdata('beforeLoginUrl');
        parent::tearDown();
    }

    public function testDefaultLoginRedirectWhenNoBeforeUrl(): void
    {
        session()->removeTempdata('beforeLoginUrl');
        $redirectUrl = $this->authConfig->loginRedirect();

        // Default redirect should point to '/' or home/dashboard
        $this->assertNotEmpty($redirectUrl);
        $this->assertStringNotContainsString('AbdmOpdQueue', $redirectUrl);
    }

    public function testAbdmOpdQueueFetchIsRejected(): void
    {
        $pollUrl = 'https://demo.e-atria.net/AbdmOpdQueue/fetch?date=2026-10-28&status=';
        session()->setTempdata('beforeLoginUrl', $pollUrl, 300);

        $redirectUrl = $this->authConfig->loginRedirect();

        $this->assertStringNotContainsString('AbdmOpdQueue/fetch', $redirectUrl);
        $this->assertNull(session()->getTempdata('beforeLoginUrl'), 'beforeLoginUrl must be consumed and cleared');
    }

    public function testAbdmGatewayEndpointsAreRejected(): void
    {
        $urls = [
            'https://demo.e-atria.net/AbdmGateway/health_records_list',
            'https://demo.e-atria.net/AbdmGateway/opd_running_token_status',
            'https://demo.e-atria.net/AbdmHiu/status',
            'https://demo.e-atria.net/AbdmSync/summary',
            'https://demo.e-atria.net/api/v1/opd/queue',
            'https://demo.e-atria.net/auth/session-status',
            'https://demo.e-atria.net/captcha',
            'https://demo.e-atria.net/doctor_work/document_workspace',
            'https://demo.e-atria.net/opd/appointment',
        ];

        foreach ($urls as $url) {
            session()->setTempdata('beforeLoginUrl', $url, 300);
            $redirectUrl = $this->authConfig->loginRedirect();

            $path = parse_url($url, PHP_URL_PATH);
            $this->assertStringNotContainsString(
                $path,
                $redirectUrl,
                "URL {$url} should have been rejected by loginRedirect()"
            );
        }
    }

    public function testLegitimatePageIsPreserved(): void
    {
        $legitUrl = base_url('billing/patient');
        session()->setTempdata('beforeLoginUrl', $legitUrl, 300);

        $redirectUrl = $this->authConfig->loginRedirect();

        $this->assertStringContainsString('billing/patient', $redirectUrl);
        $this->assertNull(session()->getTempdata('beforeLoginUrl'), 'beforeLoginUrl must be consumed after redirect');
    }
}
