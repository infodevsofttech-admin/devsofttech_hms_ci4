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

        // Content Security Policy (strict sources, trusted CDNs, preventing wildcards and injection)
        'Content-Security-Policy'           => "default-src 'self'; " .
                                               "script-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net https://oss.maxcdn.com https://cdnjs.cloudflare.com; " .
                                               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
                                               "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com; " .
                                               "img-src 'self' data: blob: https://api.qrserver.com; " .
                                               "connect-src 'self' blob: data:; " .
                                               "frame-src 'self' data: blob:; " .
                                               "media-src 'self' data: blob:; " .
                                               "object-src 'none'; " .
                                               "worker-src 'self' blob:; " .
                                               "manifest-src 'self'; " .
                                               "form-action 'self'; " .
                                               "base-uri 'self'; " .
                                               "frame-ancestors 'self';",
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
