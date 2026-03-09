<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ItemIpdModel;

class ItemIpd extends BaseController
{
    public function searchItemType(): string
    {
        $model = new ItemIpdModel();

        return view('Setting/Charges/IPD_Charges/item_type_search_v', [
            'data' => $model->getItemTypes(),
        ]);
    }

    public function searchAdv(int $itypeId = 1): string
    {
        $model = new ItemIpdModel();

        return view('Setting/Charges/IPD_Charges/item_search_adv', [
            'data' => $model->getItemsByType($itypeId),
            'typeId' => $itypeId,
        ]);
    }

    public function search(): string
    {
        $model = new ItemIpdModel();
        $typeId = 1;

        return view('Setting/Charges/IPD_Charges/item_search_V', [
            'labitemtype' => $model->getItemTypesList(),
            'data' => $model->getItemsByType($typeId),
            'typeId' => $typeId,
            'insurance_list' => $model->getInsuranceList(),
        ]);
    }

    public function searchPrint(int $itypeId = 1): string
    {
        $model = new ItemIpdModel();
        $insuranceId = (int) $this->request->getGet('insurance');
        $insurance = $insuranceId > 0 ? $model->getInsuranceById($insuranceId) : null;
        $data = $insuranceId > 0
            ? $model->getItemsByTypeWithInsurance($itypeId, $insuranceId)
            : $model->getItemsByType($itypeId);

        return view('Setting/Charges/IPD_Charges/item_item_list', [
            'data' => $data,
            'insuranceId' => $insuranceId,
            'insuranceName' => $insurance->ins_company_name ?? '',
        ]);
    }

    public function exportExcel(int $itypeId = 1)
    {
        $model = new ItemIpdModel();
        $insuranceId = (int) $this->request->getGet('insurance');
        $insurance = $insuranceId > 0 ? $model->getInsuranceById($insuranceId) : null;
        $data = $insuranceId > 0
            ? $model->getItemsByTypeWithInsurance($itypeId, $insuranceId)
            : $model->getItemsByType($itypeId);

        $table = view('Setting/Charges/IPD_Charges/item_item_excel', [
            'data' => $data,
            'insuranceId' => $insuranceId,
            'insuranceName' => $insurance->ins_company_name ?? '',
        ]);

        $filename = 'IPD_Charges_' . date('dMy') . '.xls';

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.ms-excel')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody($table);
    }

    public function itemTypeRecord(int $id): string
    {
        $model = new ItemIpdModel();

        return view('Setting/Charges/IPD_Charges/item_type_profile', [
            'data_item' => $model->getItemTypeById($id),
        ]);
    }

    public function itemRecord(int $id): string
    {
        $model = new ItemIpdModel();

        return view('Setting/Charges/IPD_Charges/item_profile_V', [
            'data_item' => $model->getItemById($id),
            'data_item_type' => $model->getItemTypesList(),
            'data_insurance_item' => $model->getInsuranceItemList($id),
            'data_insurance' => $model->getInsuranceList(),
        ]);
    }

    public function updateRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $itemId = (int) $this->request->getPost('p_id');

        $data = [
            'itype' => $this->request->getPost('Item_Type'),
            'idesc' => $this->request->getPost('input_Item_name'),
            'amount' => $this->request->getPost('input_amount'),
            'idesc_detail' => $this->request->getPost('input_Item_desc'),
            'update_date' => date('Y-m-d H:i:s'),
        ];

        $updated = $model->updateItem($data, $itemId);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data Saved successfully' : '',
            'error_text' => $updated ? '' : 'Please Check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function addRecord(): string
    {
        $model = new ItemIpdModel();

        return view('Setting/Charges/IPD_Charges/item_create_V', [
            'data_item_type' => $model->getItemTypesList(),
        ]);
    }

    public function addItemTypeRecord(): string
    {
        return view('Setting/Charges/IPD_Charges/item_type_create');
    }

    public function createRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $data = [
            'itype' => $this->request->getPost('Item_Type'),
            'idesc' => $this->request->getPost('input_Item_name'),
            'amount' => $this->request->getPost('input_amount'),
            'update_date' => date('Y-m-d H:i:s'),
        ];

        $insertId = $model->insertItem($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function addInsuranceItemRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $itemId = (int) $this->request->getPost('p_id');

        $data = [
            'hc_items_id' => $itemId,
            'hc_insurance_id' => $this->request->getPost('ins_company_name'),
            'amount1' => $this->request->getPost('input_amount'),
            'code' => $this->request->getPost('input_item_code'),
        ];

        $insertId = $model->insertItemInsurance($data);
        $list = $model->getInsuranceItemList($itemId);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'html' => view('Setting/Charges/IPD_Charges/item_insurance_list', [
                'data_insurance_item' => $list,
            ]),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function removeInsuranceItem()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $itemId = (int) $this->request->getPost('p_id');
        $removeId = (int) $this->request->getPost('in_remove_id');

        $deleted = $model->deleteItemInsurance($removeId);
        $list = $model->getInsuranceItemList($itemId);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please Check',
            'html' => view('Setting/Charges/IPD_Charges/item_insurance_list', [
                'data_insurance_item' => $list,
            ]),
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function createItemTypeRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $data = [
            'group_desc' => $this->request->getPost('input_Item_type'),
        ];

        $insertId = $model->insertItemType($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function updateItemTypeRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new ItemIpdModel();
        $itemTypeId = (int) $this->request->getPost('itemtype_id');

        $data = [
            'group_desc' => $this->request->getPost('input_Item_type'),
        ];

        $updated = $model->updateItemType($data, $itemTypeId);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data Saved successfully' : '',
            'error_text' => $updated ? '' : 'Please Check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

}
