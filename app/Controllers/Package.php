<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PackageModel;

class Package extends BaseController
{
    public function searchItemType(): string
    {
        $model = new PackageModel();

        return view('Setting/Charges/Package/item_type_search_v', [
            'data' => $model->getPackageGroups(),
        ]);
    }

    public function searchAdv(int $groupId = 1): string
    {
        $model = new PackageModel();

        return view('Setting/Charges/Package/item_search_adv', [
            'data' => $model->getPackagesByGroup($groupId),
            'groupId' => $groupId,
        ]);
    }

    public function search(): string
    {
        $model = new PackageModel();
        $groupId = 1;

        return view('Setting/Charges/Package/item_search_V', [
            'package_group' => $model->getPackageGroups(),
            'data' => $model->getPackagesByGroup($groupId),
            'groupId' => $groupId,
            'insurance_list' => $model->getInsuranceList(),
        ]);
    }

    public function searchPrint(int $groupId = 1): string
    {
        $model = new PackageModel();
        $insuranceId = (int) $this->request->getGet('insurance');
        $insurance = $insuranceId > 0 ? $model->getInsuranceById($insuranceId) : null;
        $data = $insuranceId > 0
            ? $model->getPackagesByGroupWithInsurance($groupId, $insuranceId)
            : $model->getPackagesByGroup($groupId);

        return view('Setting/Charges/Package/item_item_list', [
            'data' => $data,
            'insuranceId' => $insuranceId,
            'insuranceName' => $insurance->ins_company_name ?? '',
        ]);
    }

    public function exportExcel(int $groupId = 1)
    {
        $model = new PackageModel();
        $insuranceId = (int) $this->request->getGet('insurance');
        $insurance = $insuranceId > 0 ? $model->getInsuranceById($insuranceId) : null;
        $data = $insuranceId > 0
            ? $model->getPackagesByGroupWithInsurance($groupId, $insuranceId)
            : $model->getPackagesByGroup($groupId);

        $table = view('Setting/Charges/Package/item_item_excel', [
            'data' => $data,
            'insuranceId' => $insuranceId,
            'insuranceName' => $insurance->ins_company_name ?? '',
        ]);

        $filename = 'IPD_Package_' . date('dMy') . '.xls';

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
        $model = new PackageModel();

        return view('Setting/Charges/Package/item_type_profile', [
            'data_item' => $model->getPackageGroupById($id),
        ]);
    }

    public function itemRecord(int $id): string
    {
        $model = new PackageModel();

        return view('Setting/Charges/Package/item_profile_V', [
            'data_item' => $model->getPackageById($id),
            'data_insurance_item' => $model->getPackageInsuranceList($id),
            'data_insurance' => $model->getInsuranceList(),
        ]);
    }

    public function updateRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new PackageModel();
        $itemId = (int) $this->request->getPost('p_id');

        $data = [
            'ipd_pakage_name' => $this->request->getPost('input_Package_name'),
            'Pakage_description' => $this->request->getPost('input_Pakage_description'),
            'Pakage_Min_Amount' => $this->request->getPost('input_Pakage_Min_Amount'),
            'update_date' => date('Y-m-d H:i:s'),
        ];

        $updated = $model->updatePackage($data, $itemId);

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
        $model = new PackageModel();

        return view('Setting/Charges/Package/item_create_V', [
            'data_item_type' => $model->getPackageGroups(),
        ]);
    }

    public function addItemTypeRecord(): string
    {
        return view('Setting/Charges/Package/item_type_create');
    }

    public function createRecord()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
        }

        $model = new PackageModel();
        $data = [
            'pakage_group_id' => $this->request->getPost('Item_Type'),
            'ipd_pakage_name' => $this->request->getPost('input_Item_name'),
            'Pakage_Min_Amount' => $this->request->getPost('input_amount'),
            'update_date' => date('Y-m-d H:i:s'),
        ];

        $insertId = $model->insertPackage($data);

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

        $model = new PackageModel();
        $itemId = (int) $this->request->getPost('p_id');

        $data = [
            'hc_items_id' => $itemId,
            'hc_insurance_id' => $this->request->getPost('ins_company_name'),
            'i_amount' => $this->request->getPost('input_amount'),
            'code' => $this->request->getPost('input_item_code'),
        ];

        $insertId = $model->insertPackageInsurance($data);
        $list = $model->getPackageInsuranceList($itemId);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please Check',
            'html' => view('Setting/Charges/Package/item_insurance_list', [
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

        $model = new PackageModel();
        $itemId = (int) $this->request->getPost('p_id');
        $removeId = (int) $this->request->getPost('in_remove_id');

        $deleted = $model->deletePackageInsurance($removeId);
        $list = $model->getPackageInsuranceList($itemId);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please Check',
            'html' => view('Setting/Charges/Package/item_insurance_list', [
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

        $model = new PackageModel();
        $data = [
            'pakage_group_name' => $this->request->getPost('input_Item_type'),
        ];

        $insertId = $model->insertPackageGroup($data);

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

        $model = new PackageModel();
        $groupId = (int) $this->request->getPost('itemtype_id');

        $data = [
            'pakage_group_name' => $this->request->getPost('input_Item_type'),
        ];

        $updated = $model->updatePackageGroup($data, $groupId);

        return $this->response->setJSON([
            'update' => $updated ? 1 : 0,
            'showcontent' => $updated ? 'Data Saved successfully' : '',
            'error_text' => $updated ? '' : 'Please Check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
