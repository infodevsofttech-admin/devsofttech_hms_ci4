<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\RefferModel;

class Reffer extends BaseController
{
    public function index(): string
    {
        $this->ensureReferMasterAddressColumns();
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_index', [
            'refer_master' => $model->getAll(),
        ]);
    }

    public function create(): string
    {
        $this->ensureReferMasterAddressColumns();
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_add', [
            'refer_type' => $model->getTypes(),
        ]);
    }

    public function edit(int $id): string
    {
        $this->ensureReferMasterAddressColumns();
        $model = new RefferModel();

        return view('Setting/Reffer/reffer_edit', [
            'data' => $model->getById($id),
            'refer_type' => $model->getTypes(),
        ]);
    }

    public function store()
    {
        $this->ensureReferMasterAddressColumns();
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

        $data = array_merge($data, $this->getReferAddressData());

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
        ]);
    }

    public function update(int $id)
    {
        $this->ensureReferMasterAddressColumns();
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

        $data = array_merge($data, $this->getReferAddressData());

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

    private function getReferAddressData(): array
    {
        $db = db_connect();
        $data = [];

        if ($db->fieldExists('place', 'refer_master')) {
            $data['place'] = trim((string) $this->request->getPost('input_place'));
        }
        if ($db->fieldExists('city', 'refer_master')) {
            $data['city'] = trim((string) $this->request->getPost('input_city'));
        }
        if ($db->fieldExists('district', 'refer_master')) {
            $data['district'] = trim((string) $this->request->getPost('input_district'));
        }
        if ($db->fieldExists('state', 'refer_master')) {
            $data['state'] = trim((string) $this->request->getPost('input_state'));
        }

        return $data;
    }

    private function ensureReferMasterAddressColumns(): void
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('refer_master')) {
                return;
            }

            $columnsToEnsure = [
                'place' => "ALTER TABLE refer_master ADD COLUMN place varchar(150) DEFAULT NULL AFTER phone_number",
                'city' => "ALTER TABLE refer_master ADD COLUMN city varchar(100) DEFAULT NULL AFTER place",
                'district' => "ALTER TABLE refer_master ADD COLUMN district varchar(100) DEFAULT NULL AFTER city",
                'state' => "ALTER TABLE refer_master ADD COLUMN state varchar(100) DEFAULT NULL AFTER district",
            ];

            foreach ($columnsToEnsure as $column => $sql) {
                if (! $db->fieldExists($column, 'refer_master')) {
                    $db->query($sql);
                }
            }
        } catch (\Throwable $e) {
            // Fail-open so referral pages still load if schema update is blocked.
        }
    }
}
