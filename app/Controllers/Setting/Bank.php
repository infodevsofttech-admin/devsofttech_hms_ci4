<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\BankModel;

class Bank extends BaseController
{
    public function index(): string
    {
        $model = new BankModel();

        return view('Setting/Bank/bank_search_v', [
            'banks' => $model->getBanks(),
            'sources' => $model->getPaymentSources(),
        ]);
    }

    public function createBank()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $name = trim((string) $this->request->getPost('bank_name'));
        if ($name === '') {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Bank name is required.']);
        }

        $model = new BankModel();
        $insertId = $model->insertBank($name);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function createBankWithSource()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $bankName = trim((string) $this->request->getPost('bank_name'));
        $payType = trim((string) $this->request->getPost('pay_type'));

        if ($bankName === '') {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Bank name is required.']);
        }

        $model = new BankModel();
        $bankId = $model->insertBank($bankName);
        if ($bankId <= 0) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Please check']);
        }

        if ($payType !== '') {
            $model->insertPaymentSource($bankId, $payType);
        }

        return $this->response->setJSON([
            'insertid' => $bankId,
            'error_text' => '',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function sources(int $bankId)
    {
        $model = new BankModel();

        return $this->response->setJSON([
            'sources' => $model->getPaymentSourcesByBank($bankId),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateBank()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('bank_id');
        $name = trim((string) $this->request->getPost('bank_name'));

        if ($id <= 0 || $name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid data']);
        }

        $model = new BankModel();
        $updated = $model->updateBank($id, $name);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'error_text' => $updated ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteBank()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('bank_id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid bank ID']);
        }

        $model = new BankModel();
        if ($model->countPaymentSourcesForBank($id) > 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => 'Remove payment sources before deleting the bank.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $deleted = $model->deleteBank($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function createSource()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $bankId = (int) $this->request->getPost('bank_id');
        $payType = trim((string) $this->request->getPost('pay_type'));

        if ($bankId <= 0 || $payType === '') {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Bank and pay type are required.']);
        }

        $model = new BankModel();
        $insertId = $model->insertPaymentSource($bankId, $payType);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateSource()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('source_id');
        $bankId = (int) $this->request->getPost('bank_id');
        $payType = trim((string) $this->request->getPost('pay_type'));

        if ($id <= 0 || $bankId <= 0 || $payType === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid data']);
        }

        $model = new BankModel();
        $updated = $model->updatePaymentSource($id, $bankId, $payType);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'error_text' => $updated ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteSource()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('source_id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid source ID']);
        }

        $model = new BankModel();
        $deleted = $model->deletePaymentSource($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
