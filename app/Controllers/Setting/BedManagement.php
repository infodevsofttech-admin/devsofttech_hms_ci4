<?php

namespace App\Controllers\Setting;

use App\Controllers\BaseController;
use App\Models\BedAssignmentHistoryModel;
use App\Models\BedCategoryModel;
use App\Models\BedMaintenanceLogModel;
use App\Models\BedMasterModel;
use App\Models\WardModel;

class BedManagement extends BaseController
{
    public function index(): string
    {
        return view('Setting/Bed/index');
    }

    private function normalizeDateTime(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return str_replace('T', ' ', $value);
    }

    private function bedCodeExists(string $bedCode, int $excludeId = 0): bool
    {
        $builder = $this->db->table('bed_master')->where('bed_code', $bedCode);
        if ($excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    private function wardBedExists(int $wardId, string $bedNumber, int $excludeId = 0): bool
    {
        $builder = $this->db->table('bed_master')
            ->where('ward_id', $wardId)
            ->where('bed_number', $bedNumber);
        if ($excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function categories(): string
    {
        $model = new BedCategoryModel();

        return view('Setting/Bed/category_list', [
            'categories' => $model->getAll(),
        ]);
    }

    public function saveCategory()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $code = trim((string) $this->request->getPost('category_code'));
        $name = trim((string) $this->request->getPost('category_name'));

        if ($code === '' || $name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Category code and name are required.']);
        }

        $data = [
            'category_code' => $code,
            'category_name' => $name,
            'category_type' => $this->request->getPost('category_type'),
            'base_charge_per_day' => $this->request->getPost('base_charge_per_day') ?? 0,
            'nursing_charge_per_day' => $this->request->getPost('nursing_charge_per_day') ?? 0,
            'amenities' => $this->request->getPost('amenities'),
            'description' => $this->request->getPost('description'),
            'status' => $this->request->getPost('status') ?? 'active',
        ];

        $model = new BedCategoryModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);
            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteCategory()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid category ID']);
        }

        $model = new BedCategoryModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function beds(): string
    {
        $bedModel = new BedMasterModel();
        $wardModel = new WardModel();
        $categoryModel = new BedCategoryModel();

        return view('Setting/Bed/bed_list', [
            'beds' => $bedModel->getAllWithRelations(),
            'wards' => $wardModel->getAllActive(),
            'categories' => $categoryModel->getAll(),
        ]);
    }

    public function saveBed()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $bedCode = trim((string) $this->request->getPost('bed_code'));
        $bedNumber = trim((string) $this->request->getPost('bed_number'));
        $wardId = (int) $this->request->getPost('ward_id');
        $categoryId = (int) $this->request->getPost('bed_category_id');

        if ($bedCode === '' || $bedNumber === '' || $wardId <= 0 || $categoryId <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed code, number, ward and category are required.']);
        }

        if ($this->bedCodeExists($bedCode, $id)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed code already exists.']);
        }

        if ($this->wardBedExists($wardId, $bedNumber, $id)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed number already exists in this ward.']);
        }

        $data = [
            'bed_code' => $bedCode,
            'bed_number' => $bedNumber,
            'ward_id' => $wardId,
            'bed_category_id' => $categoryId,
            'bed_status' => $this->request->getPost('bed_status') ?? 'available',
            'bed_position' => $this->request->getPost('bed_position'),
            'has_oxygen' => $this->request->getPost('has_oxygen') ? 1 : 0,
            'has_suction' => $this->request->getPost('has_suction') ? 1 : 0,
            'has_monitor' => $this->request->getPost('has_monitor') ? 1 : 0,
            'has_ventilator' => $this->request->getPost('has_ventilator') ? 1 : 0,
            'is_isolation_bed' => $this->request->getPost('is_isolation_bed') ? 1 : 0,
            'base_charge_override' => $this->request->getPost('base_charge_override') ?: null,
            'nursing_charge_override' => $this->request->getPost('nursing_charge_override') ?: null,
            'status' => $this->request->getPost('status') ?? 'active',
            'remarks' => $this->request->getPost('remarks'),
        ];

        $model = new BedMasterModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);
            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteBed()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid bed ID']);
        }

        $model = new BedMasterModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function maintenance(): string
    {
        $bedModel = new BedMasterModel();
        $maintenanceModel = new BedMaintenanceLogModel();

        return view('Setting/Bed/maintenance_list', [
            'beds' => $bedModel->getAllWithRelations(),
            'logs' => $maintenanceModel->getAllWithRelations(),
        ]);
    }

    public function saveMaintenance()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $bedId = (int) $this->request->getPost('bed_id');
        $type = trim((string) $this->request->getPost('maintenance_type'));

        if ($bedId <= 0 || $type === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed and maintenance type are required.']);
        }

        $data = [
            'bed_id' => $bedId,
            'maintenance_type' => $type,
            'scheduled_date' => $this->request->getPost('scheduled_date') ?: null,
            'completed_date' => $this->normalizeDateTime($this->request->getPost('completed_date')),
            'performed_by' => $this->request->getPost('performed_by'),
            'issue_description' => $this->request->getPost('issue_description'),
            'action_taken' => $this->request->getPost('action_taken'),
            'next_maintenance_date' => $this->request->getPost('next_maintenance_date') ?: null,
            'cost' => $this->request->getPost('cost') ?: 0,
            'status' => $this->request->getPost('status') ?? 'pending',
        ];

        $model = new BedMaintenanceLogModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);
            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteMaintenance()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid maintenance ID']);
        }

        $model = new BedMaintenanceLogModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function assignmentHistory(): string
    {
        $model = new BedAssignmentHistoryModel();
        $bedModel = new BedMasterModel();
        $wardModel = new WardModel();

        return view('Setting/Bed/assignment_history', [
            'assignments' => $model->getAllWithRelations(),
            'beds' => $bedModel->getAllWithRelations(),
            'wards' => $wardModel->getAllActive(),
        ]);
    }

    public function saveAssignment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $bedId = (int) $this->request->getPost('bed_id');
        $wardId = (int) $this->request->getPost('ward_id');
        $type = trim((string) $this->request->getPost('assignment_type'));
        $assignedDate = $this->normalizeDateTime($this->request->getPost('assigned_date'));

        if ($bedId <= 0 || $wardId <= 0 || $type === '' || $assignedDate === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed, ward, assignment type and assigned date are required.']);
        }

        $data = [
            'ipd_id' => $this->request->getPost('ipd_id') ?: null,
            'bed_id' => $bedId,
            'assignment_type' => $type,
            'assigned_date' => $assignedDate,
            'discharged_date' => $this->normalizeDateTime($this->request->getPost('discharged_date')),
            'assigned_by' => $this->request->getPost('assigned_by') ?: null,
            'transfer_reason' => $this->request->getPost('transfer_reason'),
            'transfer_from_bed_id' => $this->request->getPost('transfer_from_bed_id') ?: null,
            'remarks' => $this->request->getPost('remarks'),
            'ward_id' => $wardId,
            'released_date' => $this->normalizeDateTime($this->request->getPost('released_date')),
            'total_days' => $this->request->getPost('total_days') ?: 0,
            'release_reason' => $this->request->getPost('release_reason'),
        ];

        $model = new BedAssignmentHistoryModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);
            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteAssignment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid assignment ID']);
        }

        $model = new BedAssignmentHistoryModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }
}
