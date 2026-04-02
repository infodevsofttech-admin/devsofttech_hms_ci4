?php

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

    private function wardCodeExists(string $wardCode, int $excludeId = 0): bool
    {
        $builder = $this->db->table('ward_master')->where('ward_code', $wardCode);
        if ($excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function wards(): string
    {
        $departments = [(object) [
            'iId' => 0,
            'vName' => 'All Department',
        ]];

        if ($this->db->tableExists('hc_department')) {
            $departmentRows = $this->db->table('hc_department')
                ->orderBy('vName', 'ASC')
                ->get()
                ->getResult();

            $departments = array_merge($departments, $departmentRows);
        }

        $wardBuilder = $this->db->table('ward_master w')
            ->orderBy('w.ward_name', 'ASC');

        if ($this->db->tableExists('hc_department')) {
            $wardBuilder->select('w.*, COALESCE(d.vName, "All Department") AS department_name', false)
                ->join('hc_department d', 'd.iId = w.department_id', 'left');
        } else {
            $wardBuilder->select('w.*, "All Department" AS department_name', false);
        }

        $wardRows = $wardBuilder->get()->getResult();

        return view('Setting/Bed/ward_list', [
            'wards' => $wardRows,
            'departments' => $departments,
        ]);
    }

    public function saveWard()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        $wardCode = trim((string) $this->request->getPost('ward_code'));
        $wardName = trim((string) $this->request->getPost('ward_name'));
        $departmentIdRaw = $this->request->getPost('department_id');
        $departmentId = $departmentIdRaw === null || $departmentIdRaw === '' ? 0 : (int) $departmentIdRaw;

        if ($wardCode === '' || $wardName === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Ward code and ward name are required.']);
        }

        if ($this->wardCodeExists($wardCode, $id)) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Ward code already exists.']);
        }

        if ($departmentId > 0 && $this->db->tableExists('hc_department')) {
            $departmentExists = $this->db->table('hc_department')
                ->where('iId', $departmentId)
                ->countAllResults();

            if ($departmentExists <= 0) {
                return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid department selected.']);
            }
        }

        $user = auth()->user();
        $data = [
            'ward_code' => $wardCode,
            'ward_name' => $wardName,
            'department_id' => $departmentId,
            'building_id' => $this->request->getPost('building_id') ?: null,
            'floor_number' => $this->request->getPost('floor_number') ?: null,
            'ward_type' => $this->request->getPost('ward_type') ?: 'General',
            'gender_type' => $this->request->getPost('gender_type') ?: 'unisex',
            'ward_category' => $this->request->getPost('ward_category') ?: 'adult',
            'total_capacity' => $this->request->getPost('total_capacity') ?: 0,
            'nurse_station_location' => $this->request->getPost('nurse_station_location'),
            'has_oxygen' => $this->request->getPost('has_oxygen') ? 1 : 0,
            'has_suction' => $this->request->getPost('has_suction') ? 1 : 0,
            'has_monitor' => $this->request->getPost('has_monitor') ? 1 : 0,
            'status' => $this->request->getPost('status') ?: 'active',
            'remarks' => $this->request->getPost('remarks'),
        ];

        $model = new WardModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);

            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $data['created_by'] = $user->id ?? null;
        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteWard()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid ward ID']);
        }

        $bedCount = $this->db->table('bed_master')
            ->where('ward_id', $id)
            ->countAllResults();

        if ($bedCount > 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $bedCount . ' bed(s) are mapped with this ward. Cannot delete.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $model = new WardModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Delete failed. Please try again.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function departments(): string
    {
        if (! $this->db->tableExists('hc_department')) {
            return view('Setting/Bed/department_list', [
                'departments' => [],
                'tableMissing' => true,
            ]);
        }

        $departmentRows = $this->db->table('hc_department')
            ->orderBy('vName', 'ASC')
            ->get()
            ->getResult();

        return view('Setting/Bed/department_list', [
            'departments' => $departmentRows,
            'tableMissing' => false,
        ]);
    }

    public function saveDepartment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('hc_department')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Department table not found.']);
        }

        $id = (int) $this->request->getPost('id');
        $name = trim((string) $this->request->getPost('department_name'));
        $typeId = $this->request->getPost('hc_type_id');
        $typeId = $typeId === null || $typeId === '' ? null : (int) $typeId;

        if ($name === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Department name is required.']);
        }

        $duplicate = $this->db->table('hc_department')
            ->where('vName', $name);
        if ($id > 0) {
            $duplicate->where('iId !=', $id);
        }

        if ($duplicate->countAllResults() > 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Department name already exists.']);
        }

        $payload = [
            'vName' => $name,
            'hc_type_id' => $typeId,
        ];

        if ($id > 0) {
            $updated = $this->db->table('hc_department')
                ->where('iId', $id)
                ->update($payload);

            return $this->response->setJSON([
                'update' => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $inserted = $this->db->table('hc_department')->insert($payload);
        $insertId = $inserted ? (int) $this->db->insertID() : 0;

        return $this->response->setJSON([
            'insertid' => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function deleteDepartment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        if (! $this->db->tableExists('hc_department')) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Department table not found.']);
        }

        $id = (int) $this->request->getPost('id');
        if ($id <= 0) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid department ID']);
        }

        $wardCount = $this->db->table('ward_master')
            ->where('department_id', $id)
            ->countAllResults();

        if ($wardCount > 0) {
            return $this->response->setJSON([
                'update' => 0,
                'error_text' => $wardCount . ' ward(s) use this department. Cannot delete.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
            ]);
        }

        $this->db->table('hc_department')->where('iId', $id)->delete();
        $deleted = $this->db->affectedRows() > 0;

        return $this->response->setJSON([
            'update' => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Delete failed. Please try again.',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function bedStatus(): string
    {
        // ── Stats counters ────────────────────────────────────────────────
        $stats = [
            'total'       => 0,
            'available'   => 0,
            'occupied'    => 0,
            'reserved'    => 0,
            'maintenance' => 0,
            'cleaning'    => 0,
            'blocked'     => 0,
        ];
        $floors = [];   // [floorNo => [wardId => ['ward_name'=>…,'beds'=>[…],'count'=>[…]]]]
        $wards  = [];   // [wardId => wardName]  for the filter dropdown

        if ($this->db->tableExists('bed_master') && $this->db->tableExists('ward_master')) {
            $builder = $this->db->table('bed_master b')
                ->select('b.id as bed_id, b.bed_number, b.bed_code, b.bed_status, b.current_ipd_id')
                ->select('b.has_oxygen, b.has_monitor, b.has_ventilator, b.is_isolation_bed')
                ->select('w.id as ward_id, w.ward_name, w.floor_number, w.ward_type')
                ->join('ward_master w', 'w.id = b.ward_id', 'left')
                ->where('b.status', 'active')
                ->orderBy('w.floor_number', 'ASC')
                ->orderBy('w.ward_name', 'ASC')
                ->orderBy('b.bed_number', 'ASC');

            if ($this->db->tableExists('ipd_master')) {
                $builder
                    ->select('i.id as ipd_id, i.ipd_code, i.register_date, i.r_doc_name')
                    ->join('ipd_master i', 'i.id = b.current_ipd_id AND i.ipd_status = 0', 'left', false);

                if ($this->db->tableExists('patient_master')) {
                    $builder
                        ->select('p.p_code, p.p_fname, p.p_lname')
                        ->join('patient_master p', 'p.id = i.p_id', 'left');
                }
            }

            $today = new \DateTime();

            foreach ($builder->get()->getResult() as $row) {
                $floorNo = (int) ($row->floor_number ?? 0);
                $wardId  = (int) ($row->ward_id ?? 0);
                $status  = (string) ($row->bed_status ?? 'available');

                $stats['total']++;
                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                } elseif (strpos($status, 'maintenance') !== false) {
                    $stats['maintenance']++;
                }

                // Days since admission
                $daysAdmitted = null;
                if (! empty($row->register_date)) {
                    try {
                        $admitDate    = new \DateTime((string) $row->register_date);
                        $daysAdmitted = (int) $today->diff($admitDate)->days;
                    } catch (\Throwable $e) {
                        $daysAdmitted = null;
                    }
                }

                $patientName = trim(((string) ($row->p_fname ?? '')) . ' ' . ((string) ($row->p_lname ?? '')));

                // Initialise floor / ward buckets
                if (! isset($floors[$floorNo])) {
                    $floors[$floorNo] = [];
                }
                if (! isset($floors[$floorNo][$wardId])) {
                    $floors[$floorNo][$wardId] = [
                        'ward_name' => (string) ($row->ward_name ?? 'Unknown Ward'),
                        'ward_type' => (string) ($row->ward_type ?? ''),
                        'beds'      => [],
                        'count'     => ['total' => 0, 'available' => 0, 'occupied' => 0],
                    ];
                    $wards[$wardId] = (string) ($row->ward_name ?? 'Unknown Ward');
                }

                $floors[$floorNo][$wardId]['beds'][] = [
                    'bed_id'         => (int) ($row->bed_id ?? 0),
                    'bed_number'     => (string) ($row->bed_number !== '' && $row->bed_number !== null
                                            ? $row->bed_number : ($row->bed_code ?? '-')),
                    'bed_code'       => (string) ($row->bed_code ?? ''),
                    'bed_status'     => $status,
                    'patient_name'   => $patientName,
                    'patient_code'   => (string) ($row->p_code ?? ''),
                    'doctor_name'    => trim((string) ($row->r_doc_name ?? '')),
                    'ipd_code'       => (string) ($row->ipd_code ?? ''),
                    'admit_date'     => (string) ($row->register_date ?? ''),
                    'days_admitted'  => $daysAdmitted,
                    'has_oxygen'     => (bool) ($row->has_oxygen ?? false),
                    'has_monitor'    => (bool) ($row->has_monitor ?? false),
                    'has_ventilator' => (bool) ($row->has_ventilator ?? false),
                    'is_isolation'   => (bool) ($row->is_isolation_bed ?? false),
                ];

                $floors[$floorNo][$wardId]['count']['total']++;
                if ($status === 'available') {
                    $floors[$floorNo][$wardId]['count']['available']++;
                } elseif ($status === 'occupied') {
                    $floors[$floorNo][$wardId]['count']['occupied']++;
                }
            }

            ksort($floors);
        }

        return view('Setting/Bed/bed_status_list', [
            'floors' => $floors,
            'stats'  => $stats,
            'wards'  => $wards,
        ]);
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
            'category_code'        => $code,
            'category_name'        => $name,
            'category_type'        => $this->request->getPost('category_type'),
            'base_charge_per_day'  => $this->request->getPost('base_charge_per_day') ?? 0,
            'nursing_charge_per_day' => $this->request->getPost('nursing_charge_per_day') ?? 0,
            'amenities'            => $this->request->getPost('amenities'),
            'description'          => $this->request->getPost('description'),
            'status'               => $this->request->getPost('status') ?? 'active',
        ];

        $model = new BedCategoryModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);

            return $this->response->setJSON([
                'update'      => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName'    => csrf_token(),
                'csrfHash'    => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid'   => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
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

        // Block deletion if any beds in this category are currently occupied by IPD patients
        $inUseCount = $this->db->table('bed_master')
            ->where('bed_category_id', $id)
            ->where('current_ipd_id IS NOT NULL', null, false)
            ->countAllResults();

        if ($inUseCount > 0) {
            return $this->response->setJSON([
                'update'     => 0,
                'error_text' => "{$inUseCount} bed(s) in this category are currently occupied by IPD patient(s). Cannot delete.",
                'csrfName'   => csrf_token(),
                'csrfHash'   => csrf_hash(),
            ]);
        }

        $model   = new BedCategoryModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update'     => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Delete failed. Please try again.',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ]);
    }

    public function beds(): string
    {
        $bedModel      = new BedMasterModel();
        $wardModel     = new WardModel();
        $categoryModel = new BedCategoryModel();

        return view('Setting/Bed/bed_list', [
            'beds'       => $bedModel->getAllWithRelations(),
            'wards'      => $wardModel->getAllActive(),
            'categories' => $categoryModel->getAll(),
        ]);
    }

    public function saveBed()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id         = (int) $this->request->getPost('id');
        $bedCode    = trim((string) $this->request->getPost('bed_code'));
        $bedNumber  = trim((string) $this->request->getPost('bed_number'));
        $wardId     = (int) $this->request->getPost('ward_id');
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
            'bed_code'              => $bedCode,
            'bed_number'            => $bedNumber,
            'ward_id'               => $wardId,
            'bed_category_id'       => $categoryId,
            'bed_status'            => $this->request->getPost('bed_status') ?? 'available',
            'bed_position'          => $this->request->getPost('bed_position'),
            'has_oxygen'            => $this->request->getPost('has_oxygen') ? 1 : 0,
            'has_suction'           => $this->request->getPost('has_suction') ? 1 : 0,
            'has_monitor'           => $this->request->getPost('has_monitor') ? 1 : 0,
            'has_ventilator'        => $this->request->getPost('has_ventilator') ? 1 : 0,
            'is_isolation_bed'      => $this->request->getPost('is_isolation_bed') ? 1 : 0,
            'base_charge_override'  => $this->request->getPost('base_charge_override') ?: null,
            'nursing_charge_override' => $this->request->getPost('nursing_charge_override') ?: null,
            'status'                => $this->request->getPost('status') ?? 'active',
            'remarks'               => $this->request->getPost('remarks'),
        ];

        $model = new BedMasterModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);

            return $this->response->setJSON([
                'update'      => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName'    => csrf_token(),
                'csrfHash'    => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid'   => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
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

        $model   = new BedMasterModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update'     => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ]);
    }

    public function maintenance(): string
    {
        $bedModel         = new BedMasterModel();
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

        $id    = (int) $this->request->getPost('id');
        $bedId = (int) $this->request->getPost('bed_id');
        $type  = trim((string) $this->request->getPost('maintenance_type'));

        if ($bedId <= 0 || $type === '') {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed and maintenance type are required.']);
        }

        $data = [
            'bed_id'               => $bedId,
            'maintenance_type'     => $type,
            'scheduled_date'       => $this->request->getPost('scheduled_date') ?: null,
            'completed_date'       => $this->normalizeDateTime($this->request->getPost('completed_date')),
            'performed_by'         => $this->request->getPost('performed_by'),
            'issue_description'    => $this->request->getPost('issue_description'),
            'action_taken'         => $this->request->getPost('action_taken'),
            'next_maintenance_date' => $this->request->getPost('next_maintenance_date') ?: null,
            'cost'                 => $this->request->getPost('cost') ?: 0,
            'status'               => $this->request->getPost('status') ?? 'pending',
        ];

        $model = new BedMaintenanceLogModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);

            return $this->response->setJSON([
                'update'      => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName'    => csrf_token(),
                'csrfHash'    => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid'   => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
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

        $model   = new BedMaintenanceLogModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update'     => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ]);
    }

    public function assignmentHistory(): string
    {
        $model     = new BedAssignmentHistoryModel();
        $bedModel  = new BedMasterModel();
        $wardModel = new WardModel();

        return view('Setting/Bed/assignment_history', [
            'assignments' => $model->getAllWithRelations(),
            'beds'        => $bedModel->getAllWithRelations(),
            'wards'       => $wardModel->getAllActive(),
        ]);
    }

    public function saveAssignment()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
        }

        $id           = (int) $this->request->getPost('id');
        $bedId        = (int) $this->request->getPost('bed_id');
        $wardId       = (int) $this->request->getPost('ward_id');
        $type         = trim((string) $this->request->getPost('assignment_type'));
        $assignedDate = $this->normalizeDateTime($this->request->getPost('assigned_date'));

        if ($bedId <= 0 || $wardId <= 0 || $type === '' || $assignedDate === null) {
            return $this->response->setJSON(['update' => 0, 'error_text' => 'Bed, ward, assignment type and assigned date are required.']);
        }

        $data = [
            'ipd_id'              => $this->request->getPost('ipd_id') ?: null,
            'bed_id'              => $bedId,
            'assignment_type'     => $type,
            'assigned_date'       => $assignedDate,
            'discharged_date'     => $this->normalizeDateTime($this->request->getPost('discharged_date')),
            'assigned_by'         => $this->request->getPost('assigned_by') ?: null,
            'transfer_reason'     => $this->request->getPost('transfer_reason'),
            'transfer_from_bed_id' => $this->request->getPost('transfer_from_bed_id') ?: null,
            'remarks'             => $this->request->getPost('remarks'),
            'ward_id'             => $wardId,
            'released_date'       => $this->normalizeDateTime($this->request->getPost('released_date')),
            'total_days'          => $this->request->getPost('total_days') ?: 0,
            'release_reason'      => $this->request->getPost('release_reason'),
        ];

        $model = new BedAssignmentHistoryModel();
        if ($id > 0) {
            $updated = $model->update($id, $data);

            return $this->response->setJSON([
                'update'      => $updated ? 1 : 0,
                'showcontent' => $updated ? 'Data Saved successfully' : 'Please check',
                'csrfName'    => csrf_token(),
                'csrfHash'    => csrf_hash(),
            ]);
        }

        $insertId = $model->insert($data);

        return $this->response->setJSON([
            'insertid'   => $insertId,
            'error_text' => $insertId > 0 ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
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

        $model   = new BedAssignmentHistoryModel();
        $deleted = $model->delete($id);

        return $this->response->setJSON([
            'update'     => $deleted ? 1 : 0,
            'error_text' => $deleted ? '' : 'Please check',
            'csrfName'   => csrf_token(),
            'csrfHash'   => csrf_hash(),
        ]);
    }
}