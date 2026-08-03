<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\SystemOperations;

class SystemOps extends BaseController
{
    public function index(): string
    {
        $ops = new SystemOperations();
        $data = [
            'status' => $ops->collectStatus(),
            'history' => $ops->getHistory(),
        ];

        return view('Setting/Admin/system_ops', $data);
    }

    public function panel(): string
    {
        $ops = new SystemOperations();
        $data = [
            'status' => $ops->collectStatus(),
            'history' => $ops->getHistory(),
        ];

        return view('Setting/Admin/system_ops_panel', $data);
    }

    public function updateDirect()
    {
        $ops = new SystemOperations();
        $result = $ops->runUpdateDirect();

        return $this->response->setJSON($result);
    }

    public function action()
    {
        $action = (string) $this->request->getPost('action');
        $ops = new SystemOperations();
        $result = $ops->runServerAction($action);

        return $this->response->setJSON($result);
    }

    public function diagnose()
    {
        $ops = new SystemOperations();
        $diag = $ops->getGitDiagnosticsForWeb();

        return $this->response->setJSON($diag);
    }
}
