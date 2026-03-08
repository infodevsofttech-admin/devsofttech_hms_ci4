<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\NursingBedsideItemModel;

class NursingBedsideItems extends BaseController
{
    protected NursingBedsideItemModel $model;

    public function __construct()
    {
        $this->model = new NursingBedsideItemModel();
    }

    public function index()
    {
        return view('Setting/Charges/Nursing_Bedside/item_search_V', [
            'rows' => $this->model->getMasterList(),
            'insurance_list' => $this->model->getInsuranceList(),
        ]);
    }

    public function get(int $itemId)
    {
        return $this->response->setJSON([
            'ok' => 1,
            'row' => $this->model->find($itemId),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function save()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'message' => 'Invalid request']);
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $itemName = trim((string) ($this->request->getPost('item_name') ?? ''));
        $itemType = trim((string) ($this->request->getPost('item_type') ?? ''));

        if ($itemName === '' || $itemType === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'message' => 'Item Name and Item Type are required',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $userId = null;
        if (function_exists('auth')) {
            $user = auth()->user();
            if ($user) {
                $userId = (int) ($user->id ?? 0);
            }
        }

        $data = [
            'item_code' => trim((string) ($this->request->getPost('item_code') ?? '')) ?: null,
            'item_name' => $itemName,
            'item_type' => $itemType,
            'category' => trim((string) ($this->request->getPost('category') ?? '')) ?: null,
            'default_rate' => (float) ($this->request->getPost('default_rate') ?? 0),
            'unit' => trim((string) ($this->request->getPost('unit') ?? 'Unit')),
            'description' => trim((string) ($this->request->getPost('description') ?? '')) ?: null,
            'is_active' => (int) ($this->request->getPost('is_active') ?? 0) === 1 ? 1 : 0,
            'is_billable' => (int) ($this->request->getPost('is_billable') ?? 0) === 1 ? 1 : 0,
            'updated_by' => $userId,
        ];

        if ($itemId > 0) {
            $saved = $this->model->update($itemId, $data);
            $savedItemId = $itemId;
        } else {
            $data['created_by'] = $userId;
            $savedItemId = (int) $this->model->insert($data);
            $saved = $savedItemId > 0;
        }

        return $this->response->setJSON([
            'ok' => $saved ? 1 : 0,
            'message' => $saved ? 'Saved successfully' : 'Unable to save',
            'item_id' => $saved ? $savedItemId : 0,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function insuranceList(int $itemId)
    {
        return $this->response->setJSON([
            'ok' => 1,
            'html' => view('Setting/Charges/Nursing_Bedside/item_insurance_list', [
                'rows' => $this->model->getInsuranceRatesForItem($itemId),
            ]),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function insuranceAdd()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'message' => 'Invalid request']);
        }

        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        $insuranceId = (int) ($this->request->getPost('insurance_id') ?? 0);
        $amount = (float) ($this->request->getPost('amount') ?? 0);
        $code = trim((string) ($this->request->getPost('code') ?? ''));

        if ($itemId <= 0 || $insuranceId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'message' => 'Select item and insurance company',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $saved = $this->model->upsertInsuranceRate($itemId, $insuranceId, $amount, $code);

        return $this->response->setJSON([
            'ok' => $saved ? 1 : 0,
            'message' => $saved ? 'Insurance rate saved' : 'Unable to save insurance rate',
            'html' => view('Setting/Charges/Nursing_Bedside/item_insurance_list', [
                'rows' => $this->model->getInsuranceRatesForItem($itemId),
            ]),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function insuranceRemove()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'message' => 'Invalid request']);
        }

        $mappingId = (int) ($this->request->getPost('mapping_id') ?? 0);
        $itemId = (int) ($this->request->getPost('item_id') ?? 0);
        if ($mappingId <= 0 || $itemId <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'message' => 'Invalid insurance mapping',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $removed = $this->model->removeInsuranceRate($mappingId);

        return $this->response->setJSON([
            'ok' => $removed ? 1 : 0,
            'message' => $removed ? 'Insurance rate removed' : 'Unable to remove insurance rate',
            'html' => view('Setting/Charges/Nursing_Bedside/item_insurance_list', [
                'rows' => $this->model->getInsuranceRatesForItem($itemId),
            ]),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
