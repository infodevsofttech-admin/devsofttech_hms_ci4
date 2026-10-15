<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Enterprise Application Security Headers Filter (WASA & OWASP Compliant)
 */
class SecureHeadersFilter implements FilterInterface
{
    /**
     * Recommended security headers matching OWASP Secure Headers Project
     * and WASA (Web Application Security Assessment) requirements.
     *
     * @var array<string, string>
     */
    protected array $headers = [
        // Protect against clickjacking
        'X-Frame-Options'                   => 'SAMEORIGIN',

        // Prevent MIME type sniffing
        'X-Content-Type-Options'            => 'nosniff',

        // Restrict file downloads in legacy IE
        'X-Download-Options'                => 'noopen',

        // Restrict Adobe Flash / PDF cross-domain policies
        'X-Permitted-Cross-Domain-Policies' => 'none',

        // Protect referrer leakage
        'Referrer-Policy'                   => 'strict-origin-when-cross-origin',

        // Enforce HTTPS
        'Strict-Transport-Security'         => 'max-age=31536000; includeSubDomains; preload',

        // Restrict unnecessary browser device capabilities
        'Permissions-Policy'                => 'camera=(), microphone=(), geolocation=(), payment=()',

        // Cross-Origin isolation protections
        'Cross-Origin-Opener-Policy'        => 'same-origin-allow-popups',

        // Content Security Policy (allowing self, inline scripts, styles, data URIs for canvas/charts)
        'Content-Security-Policy'           => "default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https:; frame-ancestors 'self';",
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        foreach ($this->headers as $header => $value) {
            // Only set if not already explicitly defined by a specific controller
            if (! $response->hasHeader($header)) {
                $response->setHeader($header, $value);
            }
        }

        // Provide fresh CSRF token header for AJAX/fetch calls
        if (function_exists('csrf_hash')) {
            $response->setHeader('X-CSRF-TOKEN', csrf_hash());
        }

        return $response;
    }
}
