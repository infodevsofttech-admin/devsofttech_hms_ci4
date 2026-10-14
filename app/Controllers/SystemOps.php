<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SystemOperations;

class SystemOps extends BaseController
{
    private function ensureAdminAccess()
    {
        if (! function_exists('auth') || ! auth()->loggedIn()) {
            if ($this->request->isAJAX() || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'Authentication required']);
            }
            return redirect()->to(base_url('login'))->with('error', 'Authentication required.');
        }

        $user = auth()->user();
        $isAllowed = false;
        if ($user && method_exists($user, 'inGroup')) {
            $isAllowed = $user->inGroup('superadmin', 'admin', 'developer');
        }
        if (! $isAllowed && $user && method_exists($user, 'can')) {
            $isAllowed = $user->can('admin.settings');
        }

        if (! $isAllowed) {
            if ($this->request->isAJAX() || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json')) {
                return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'Administrative privileges required']);
            }
            return $this->response->setStatusCode(403)->setBody('Access denied. Administrator privileges required.');
        }

        return null;
    }

    public function index()
    {
        if ($deny = $this->ensureAdminAccess()) {
            return $deny;
        }

        $ops = new SystemOperations();
        $data = [
            'status' => $ops->collectStatus(),
            'history' => $ops->getHistory(),
        ];

        return view('Setting/Admin/system_ops', $data);
    }

    public function panel()
    {
        if ($deny = $this->ensureAdminAccess()) {
            return $deny;
        }

        $ops = new SystemOperations();
        $data = [
            'status' => $ops->collectStatus(),
            'history' => $ops->getHistory(),
        ];

        return view('Setting/Admin/system_ops_panel', $data);
    }

    public function updateDirect()
    {
        if ($deny = $this->ensureAdminAccess()) {
            return $deny;
        }

        $ops = new SystemOperations();
        $result = $ops->runUpdateDirect();

        return $this->response->setJSON($result);
    }

    public function diagnose()
    {
        if ($deny = $this->ensureAdminAccess()) {
            return $deny;
        }

        $ops = new SystemOperations();
        $diag = $ops->getGitDiagnosticsForWeb();

        return $this->response->setJSON($diag);
    }
}
