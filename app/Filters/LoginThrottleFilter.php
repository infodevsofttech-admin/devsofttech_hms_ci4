<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication Endpoint Rate Limiting Filter (WASA CWE-307 Compliant)
 *
 * Enforces server-side rate limiting on authentication attempts:
 * - Restricts requests to a maximum of 5 attempts per 60 seconds per IP address.
 * - When threshold is exceeded, returns HTTP 429 Too Many Requests.
 * - Emits standard Retry-After and X-RateLimit-* headers.
 * - Logs violations for security monitoring and incident response.
 */
class LoginThrottleFilter implements FilterInterface
{
    /**
     * Maximum allowed login POST requests within the time window.
     */
    protected int $maxAttempts = 5;

    /**
     * Time window in seconds (60 seconds = 1 minute).
     */
    protected int $decaySeconds = 60;

    /**
     * @param array|null $arguments
     * @return ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return;
        }

        // Only throttle state-changing authentication attempts (POST)
        if (strtoupper($request->getMethod()) !== 'POST') {
            return;
        }

        $throttler = service('throttler');
        $ip = $request->getIPAddress();
        $throttleKey = 'auth_throttle_' . md5($ip);

        // Check if the request exceeds capacity
        if ($throttler->check($throttleKey, $this->maxAttempts, $this->decaySeconds, 1) === false) {
            $retryAfter = max(1, $throttler->getTokenTime());
            $resetTime = time() + $retryAfter;

            // Log security warning for SIEM / audit monitoring
            log_message(
                'warning',
                "[WASA-CWE-307] Authentication rate limit exceeded. IP: {$ip}, Path: {$request->getUri()->getPath()}, Retry-After: {$retryAfter}s"
            );

            /** @var ResponseInterface $response */
            $response = service('response');
            $response->setStatusCode(429, 'Too Many Requests');
            $response->setHeader('Retry-After', (string) $retryAfter);
            $response->setHeader('X-RateLimit-Limit', (string) $this->maxAttempts);
            $response->setHeader('X-RateLimit-Remaining', '0');
            $response->setHeader('X-RateLimit-Reset', (string) $resetTime);

            $message = "Too many login attempts. Please wait {$retryAfter} seconds before trying again.";

            // Return JSON for API / AJAX or JSON-accepting clients
            if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
                return $response->setJSON([
                    'status'      => 429,
                    'error'       => 'Too Many Requests',
                    'message'     => $message,
                    'retry_after' => $retryAfter,
                ]);
            }

            // Return HTML response for standard browser form submissions
            $body = '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
                . '<title>429 Too Many Requests</title>'
                . '<meta name="viewport" content="width=device-width, initial-scale=1">'
                . '<style>'
                . 'body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f8fafc;color:#1e293b;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem;}'
                . '.card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:2.5rem 2rem;max-width:440px;width:100%;box-shadow:0 10px 15px -3px rgba(0,0,0,0.07);text-align:center;}'
                . '.badge{display:inline-block;padding:0.35rem 0.75rem;border-radius:9999px;background:#fee2e2;color:#991b1b;font-weight:600;font-size:0.875rem;margin-bottom:1rem;}'
                . 'h1{font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.75rem 0;}'
                . 'p{color:#64748b;font-size:0.95rem;line-height:1.5;margin:0 0 1.5rem 0;}'
                . '.btn{display:inline-block;background:#0284c7;color:#ffffff;text-decoration:none;padding:0.6rem 1.25rem;border-radius:6px;font-weight:500;font-size:0.875rem;transition:background 0.15s;}'
                . '.btn:hover{background:#0369a1;}'
                . '</style>'
                . '</head><body>'
                . '<div class="card">'
                . '<span class="badge">Security Protection</span>'
                . '<h1>Too Many Attempts</h1>'
                . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<a href="/login" class="btn">Return to Login</a>'
                . '</div>'
                . '</body></html>';

            return $response->setBody($body);
        }
    }

    /**
     * @param array|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // No action needed after request
    }
}
