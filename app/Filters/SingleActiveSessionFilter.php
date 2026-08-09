<?php

namespace App\Filters;

use App\Libraries\UserSessionRegistry;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SingleActiveSessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            return null;
        }

        $userId = (int) (auth()->user()->id ?? 0);
        if ($userId <= 0) {
            return null;
        }

        $registry = new UserSessionRegistry();
        $status = $registry->ensureCurrent($userId);
        if (($status['active'] ?? false) === true) {
            return null;
        }

        $message = ($status['reason'] ?? '') === 'signed_in_elsewhere'
            ? 'Your account was signed in on another system. This session has been logged out.'
            : 'Your login session is no longer active. Please sign in again.';

        auth()->logout();

        $isAjax = strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
        if ($isAjax || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
            return service('response')->setStatusCode(409)->setJSON([
                'session_terminated' => true,
                'message' => $message,
                'login_url' => base_url('login'),
            ]);
        }

        return redirect()->to(base_url('login'))->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}