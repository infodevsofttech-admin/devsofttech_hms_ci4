<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\NurseModel;

class Nurse extends BaseController
{
    private NurseModel $nurseModel;

    public function __construct()
    {
        $this->nurseModel = new NurseModel();
    }

    public function index(): string
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $nurses = $this->nurseModel->getNurses($q);

        return view('Setting/Nurse/Nurse_Search_V', [
            'nurses' => $nurses,
            'searchQuery' => $q,
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'error' => 'Invalid request']);
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        $nurseCode = trim((string) ($this->request->getPost('nurse_code') ?? ''));
        $name = trim((string) ($this->request->getPost('name') ?? ''));
        $hprId = trim((string) ($this->request->getPost('hpr_id') ?? ''));
        $registrationNo = trim((string) ($this->request->getPost('registration_no') ?? ''));
        $gender = trim((string) ($this->request->getPost('gender') ?? 'Female'));
        $designation = trim((string) ($this->request->getPost('designation') ?? 'Staff Nurse'));
        $qualification = trim((string) ($this->request->getPost('qualification') ?? ''));
        $contactNo = trim((string) ($this->request->getPost('contact_no') ?? ''));
        $email = trim((string) ($this->request->getPost('email') ?? ''));
        $department = trim((string) ($this->request->getPost('department') ?? 'Nursing'));
        $isActive = (int) ($this->request->getPost('is_active') ?? 1);

        if ($name === '') {
            return $this->response->setJSON(['ok' => false, 'error' => 'Nurse Name is required.']);
        }

        if ($nurseCode === '') {
            // Auto generate code if empty
            $nurseCode = 'NUR-' . str_pad((string) (rand(100, 999)), 3, '0', STR_PAD_LEFT);
        }

        $payload = [
            'nurse_code' => $nurseCode,
            'name' => $name,
            'hpr_id' => $hprId,
            'registration_no' => $registrationNo,
            'gender' => $gender,
            'designation' => $designation,
            'qualification' => $qualification,
            'contact_no' => $contactNo,
            'email' => $email,
            'department' => $department,
            'is_active' => $isActive,
        ];

        if ($id > 0) {
            $this->nurseModel->updateNurse($id, $payload);
            $savedId = $id;
        } else {
            $savedId = $this->nurseModel->insertNurse($payload);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => $id > 0 ? 'Nurse updated successfully.' : 'Nurse created successfully.',
            'id' => $savedId,
            'nurse' => $this->nurseModel->getNurseById($savedId),
        ]);
    }

    public function delete($id = null)
    {
        $id = (int) ($id ?? $this->request->getPost('id') ?? 0);
        if ($id <= 0) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Invalid nurse ID.']);
        }

        $this->nurseModel->deleteNurse($id);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Nurse record deleted successfully.',
        ]);
    }

    public function list_json()
    {
        $nurses = $this->nurseModel->getActiveNurses();
        return $this->response->setJSON([
            'ok' => true,
            'nurses' => $nurses,
        ]);
    }
}
