<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\RefferModel;

class Reffer extends BaseController
{
    public function index(): string
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_index', [
            'refer_master' => $model->getAll(),
        ]);
    }

    public function create(): string
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_add', [
            'refer_type' => $model->getTypes(),
        ]);
    }

    public function edit(int $id): string
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_edit', [
            'data' => $model->getById($id),
            'refer_type' => $model->getTypes(),
        ]);
    }

    public function store()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $rules = [
            'input_name' => 'required|min_length[1]|max_length[30]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'insertid' => 0,
                'error_text' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $user = auth()->user();
        $userLabel = $user ? trim(($user->username ?? '') . '[' . ($user->id ?? '') . ']') : 'System';

        $model = new RefferModel();
        $data = [
            'title' => $this->request->getPost('cbo_title'),
            'f_name' => strtoupper((string) $this->request->getPost('input_name')),
            'refer_type' => $this->request->getPost('cbo_refer_type'),
            'date_of_add' => date('Y-m-d H:i:s'),
            'insert_by' => $userLabel,
            'phone_number' => $this->request->getPost('input_phone_number'),
            'active' => 1,
        ];

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
        ]);
    }

    public function update(int $id)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $rules = [
            'input_name' => 'required|min_length[1]|max_length[30]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => implode(' ', $this->validator->getErrors()),
            ]);
        }

        $user = auth()->user();
        $userLabel = $user ? trim(($user->username ?? '') . '[' . ($user->id ?? '') . ']') : 'System';

        $model = new RefferModel();
        $data = [
            'title' => $this->request->getPost('cbo_title'),
            'f_name' => strtoupper((string) $this->request->getPost('input_name')),
            'refer_type' => $this->request->getPost('cbo_refer_type'),
            'date_of_add' => date('Y-m-d H:i:s'),
            'insert_by' => $userLabel,
            'phone_number' => $this->request->getPost('input_phone_number'),
            'active' => 1,
        ];

        $updated = $model->updateReffer($data, $id);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'error_text' => $updated ? '' : 'Please Check',
        ]);
    }

    public function activate(int $id, int $active)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new RefferModel();
        $updated = $model->updateStatus($id, $active ? 1 : 0);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'error_text' => $updated ? '' : 'Please Check',
        ]);
    }

    public function typeList(): string
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_type_list', [
            'types' => $model->getTypes(),
        ]);
    }

    public function storeType()
    {
        $name = trim((string) $this->request->getPost('type_desc'));
        if ($name === '') {
            return $this->renderTypeListWithErrors(['type_desc' => 'Type is required.']);
        }

        $model = new RefferModel();
        $model->insertType($name);

        return $this->renderTypeListWithMessage('Type added successfully.');
    }

    public function updateType()
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('type_desc'));
        if ($id <= 0 || $name === '') {
            return $this->renderTypeListWithErrors(['type_desc' => 'Type is required.']);
        }

        $model = new RefferModel();
        $model->updateType($id, $name);

        return $this->renderTypeListWithMessage('Type updated successfully.');
    }

    public function deleteType()
    {
        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->renderTypeListWithErrors(['type_desc' => 'Invalid type.']);
        }

        $model = new RefferModel();
        $model->deleteType($id);

        return $this->renderTypeListWithMessage('Type deleted successfully.');
    }

    private function renderTypeListWithMessage(string $message)
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_type_list', [
            'types' => $model->getTypes(),
            'message' => $message,
        ]);
    }

    private function renderTypeListWithErrors(array $errors)
    {
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_type_list', [
            'types' => $model->getTypes(),
            'errors' => $errors,
        ]);
    }
}
