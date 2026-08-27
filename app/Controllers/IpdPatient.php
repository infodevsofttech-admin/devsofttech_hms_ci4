<?php

namespace App\Controllers;

use App\Libraries\NursingScanExtractionService;
use App\Models\BedAssignmentHistoryModel;
use App\Models\BedMasterModel;
use App\Models\IpdBillingModel;
use App\Models\IpdModel;
use App\Models\IpdNursingEntryModel;
use App\Models\NursingBedsideItemModel;

class IpdPatient extends BaseController
{
    protected IpdBillingModel $ipdBillingModel;
    protected IpdNursingEntryModel $ipdNursingEntryModel;
    protected NursingBedsideItemModel $nursingBedsideItemModel;
    protected NursingScanExtractionService $nursingScanExtractionService;
    protected BedMasterModel $bedMasterModel;
    protected BedAssignmentHistoryModel $bedAssignmentModel;
    protected IpdModel $ipdModel;

    public function __construct()
    {
        $this->ipdBillingModel = new IpdBillingModel();
        $this->ipdNursingEntryModel = new IpdNursingEntryModel();
        $this->nursingBedsideItemModel = new NursingBedsideItemModel();
        $this->nursingScanExtractionService = new NursingScanExtractionService();
        $this->bedMasterModel = new BedMasterModel();
        $this->bedAssignmentModel = new BedAssignmentHistoryModel();
        $this->ipdModel = new IpdModel();
        helper(['common', 'form', 'age']);
    }

    public function index()
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.view',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $records = $this->ipdBillingModel->getCurrentAdmissions();

        $stats = [
            'total_beds'       => 0,
            'available'        => 0,
            'occupied'         => 0,
            'reserved'         => 0,
            'maintenance'      => 0,
            'cleaning'         => 0,
            'total_admitted'   => count($records),
            'nursing_stations' => 0,
        ];
        $floors = [];
        $wards = [];

        $stationModel = new \App\Models\NursingStationModel();
        $nursingStations = $stationModel->getActiveStations();
        $stats['nursing_stations'] = count($nursingStations);

        if ($this->db->tableExists('bed_master') && $this->db->tableExists('ward_master')) {
            $builder = $this->db->table('bed_master b')
                ->select('b.id as bed_id, b.bed_number, b.bed_code, b.bed_status, b.current_ipd_id')
                ->select('b.has_oxygen, b.has_monitor, b.has_ventilator, b.is_isolation_bed')
                ->select('w.id as ward_id, w.ward_name, w.floor_number, w.ward_type, w.nursing_station_id')
                ->join('ward_master w', 'w.id = b.ward_id', 'left')
                ->where('b.status', 'active')
                ->orderBy('w.floor_number', 'ASC')
                ->orderBy('w.ward_name', 'ASC')
                ->orderBy('b.bed_number', 'ASC');

            if ($this->db->tableExists('nursing_station_master')) {
                $builder->select('ns.station_name, ns.station_code')
                    ->join('nursing_station_master ns', 'ns.id = w.nursing_station_id', 'left');
            } else {
                $builder->select('NULL as station_name, NULL as station_code');
            }

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
                $floorNo = (string) ($row->floor_number !== null && $row->floor_number !== '' ? $row->floor_number : 'Ground Floor');
                $wardId  = (int) ($row->ward_id ?? 0);
                $status  = (string) ($row->bed_status ?? 'available');

                $stats['total_beds']++;
                if (array_key_exists($status, $stats)) {
                    $stats[$status]++;
                } elseif (strpos($status, 'maintenance') !== false) {
                    $stats['maintenance']++;
                }

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

                if (! isset($floors[$floorNo])) {
                    $floors[$floorNo] = [];
                }
                if (! isset($floors[$floorNo][$wardId])) {
                    $floors[$floorNo][$wardId] = [
                        'ward_name'    => (string) ($row->ward_name ?? 'Unknown Ward'),
                        'ward_type'    => (string) ($row->ward_type ?? ''),
                        'station_name' => (string) ($row->station_name ?? 'Unassigned Station'),
                        'station_code' => (string) ($row->station_code ?? ''),
                        'beds'         => [],
                        'count'        => ['total' => 0, 'available' => 0, 'occupied' => 0],
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
                    'ipd_id'         => (int) ($row->ipd_id ?? 0),
                    'admit_date'     => (string) ($row->register_date ?? ''),
                    'days_admitted'  => $daysAdmitted,
                    'has_oxygen'     => (bool) ($row->has_oxygen ?? false),
                    'has_monitor'    => (bool) ($row->has_monitor ?? false),
                    'has_ventilator' => (bool) ($row->has_ventilator ?? false),
                    'is_isolation'   => (bool) ($row->is_isolation_bed ?? false),
                    'station_name'   => (string) ($row->station_name ?? ''),
                ];

                $floors[$floorNo][$wardId]['count']['total']++;
                if ($status === 'available') {
                    $floors[$floorNo][$wardId]['count']['available']++;
                } elseif ($status === 'occupied') {
                    $floors[$floorNo][$wardId]['count']['occupied']++;
                }
            }
        }

        return view('ipd_nursing/patient_list', [
            'records'         => $records,
            'stats'           => $stats,
            'floors'          => $floors,
            'wards'           => $wards,
            'nursingStations' => $nursingStations,
        ]);
    }

    public function workspace(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.view',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $caseMeta = $this->ipdBillingModel->getIpdCaseMeta($ipdId);
        $insCompId = (int) ($caseMeta['insurance_id'] ?? 0);
        if ($insCompId <= 0) {
            $insCompId = 1;
        }

        $docList = $this->ipdBillingModel->getIpdDoctorList();
        $doctorIds = array_map(static fn ($doc) => (int) ($doc->id ?? 0), $docList);
        $doctorIds = array_values(array_filter($doctorIds, static fn ($id) => $id > 0));
        $person = $panelData['person_info'] ?? null;
        $patientId = max(0, (int) ($person->id ?? 0));
        if ($patientId <= 0) {
            $patientId = $this->getPatientIdFromIpd($ipdId);
        }

        $historyFields = [
            'is_smoking' => 'Smoking',
            'is_alcohol' => 'Alcohol',
            'is_tobacoo' => 'Tobacco',
            'is_drug_abuse' => 'Drug abuse',
            'is_hypertesion' => 'Hypertension',
            'is_niddm' => 'Type 2 diabetes mellitus (DM)',
            'is_hbsag' => 'HBsAg',
            'is_hcv' => 'HCV',
            'is_hiv_I_II' => 'HIV I & II',
        ];
        $patientHistoryRow = $this->getPatientHistoryFlags($patientId, array_keys($historyFields));
        $opdHistorySnapshot = $this->getLatestOpdHistorySnapshot($patientId);
        $isFemalePatient = strtolower((string) ($person->xgender ?? '')) === 'female';

        // Get current bed assignment with ward info
        $currentBed = $this->bedMasterModel
            ->select('bed_master.*, ward_master.ward_name')
            ->join('ward_master', 'ward_master.id = bed_master.ward_id', 'left')
            ->where('bed_master.current_ipd_id', $ipdId)
            ->first();
        
        // Get available beds for transfer
        $availableBeds = $this->bedMasterModel->getAvailableBedsGroupedByWard();
        // Get bed assignment history
        $bedHistory = $this->bedAssignmentModel->getByIpd($ipdId);

        $nurseModel = new \App\Models\NurseModel();
        $nurseList = $nurseModel->getActiveNurses();

        return view('ipd_nursing/workspace', [
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $person,
            'nursing_entries' => $this->ipdNursingEntryModel->getByIpd($ipdId),
            'nursing_charge_history' => $this->ipdBillingModel->getNursingChargeHistory($ipdId),
            'bedside_items_by_category' => $this->nursingBedsideItemModel->getBillableGroupedByCategory($insCompId),
            'doctor_visit_fee_types' => $this->ipdBillingModel->getDoctorVisitFeeTypes(),
            'doctor_visit_fee_map' => $this->ipdBillingModel->getDoctorVisitFeeMap($doctorIds),
            'doc_list' => $docList,
            'nurse_list' => $nurseList,
            'history_fields' => $historyFields,
            'patient_history_row' => $patientHistoryRow,
            'opd_history_snapshot' => $opdHistorySnapshot,
            'is_female_patient' => $isFemalePatient,
            'current_bed' => $currentBed,
            'available_beds' => $availableBeds,
            'bed_history' => $bedHistory,
        ]);
    }

    public function saveAdmissionHistory(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.record.manage',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid request']);
        }

        $patientId = $this->getPatientIdFromIpd($ipdId);
        if ($patientId <= 0) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Patient not found']);
        }

        $historyFieldKeys = [
            'is_smoking',
            'is_alcohol',
            'is_tobacoo',
            'is_drug_abuse',
            'is_hypertesion',
            'is_niddm',
            'is_hbsag',
            'is_hcv',
            'is_hiv_I_II',
        ];

        $saved = false;
        if ($this->db->tableExists('patient_master')) {
            $patientFields = $this->db->getFieldNames('patient_master') ?? [];
            if (in_array('id', $patientFields, true)) {
                $historyUpdate = [];
                foreach ($historyFieldKeys as $field) {
                    if (in_array($field, $patientFields, true)) {
                        $historyUpdate[$field] = (int) ($this->request->getPost($field) ?? 0) === 1 ? 1 : 0;
                    }
                }

                if (! empty($historyUpdate)) {
                    $saved = (bool) $this->db->table('patient_master')
                        ->where('id', $patientId)
                        ->update($historyUpdate) || $saved;
                }
            }
        }

        $opdPayload = [
            'drug_allergy_status' => trim((string) ($this->request->getPost('drug_allergy_status') ?? '')),
            'drug_allergy_details' => trim((string) ($this->request->getPost('drug_allergy_details') ?? '')),
            'adr_history' => trim((string) ($this->request->getPost('adr_history') ?? '')),
            'current_medications' => trim((string) ($this->request->getPost('current_medications') ?? '')),
            'co_morbidities' => trim((string) ($this->request->getPost('co_morbidities') ?? '')),
            'women_lmp' => trim((string) ($this->request->getPost('women_lmp') ?? '')),
            'women_last_baby' => trim((string) ($this->request->getPost('women_last_baby') ?? '')),
            'women_pregnancy_related' => trim((string) ($this->request->getPost('women_pregnancy_related') ?? '')),
            'women_related_problems' => trim((string) ($this->request->getPost('women_related_problems') ?? '')),
            'hpi_note' => trim((string) ($this->request->getPost('hpi_note') ?? '')),
        ];

        if ($this->db->tableExists('opd_prescription')) {
            $rxFields = $this->db->getFieldNames('opd_prescription') ?? [];
            if (in_array('p_id', $rxFields, true) && in_array('id', $rxFields, true)) {
                $latest = $this->db->table('opd_prescription')
                    ->select('id,Prescriber_Remarks')
                    ->where('p_id', $patientId)
                    ->orderBy('id', 'DESC')
                    ->get(1)
                    ->getRowArray();

                if (is_array($latest) && ! empty($latest['id'])) {
                    $update = [];
                    foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $field) {
                        if (in_array($field, $rxFields, true)) {
                            $update[$field] = (string) ($opdPayload[$field] ?? '');
                        }
                    }

                    if (in_array('Prescriber_Remarks', $rxFields, true)) {
                        $remarks = (string) ($latest['Prescriber_Remarks'] ?? '');
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Drug Allergy Status', (string) ($opdPayload['drug_allergy_status'] ?? ''));
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Drug Allergy Details', (string) ($opdPayload['drug_allergy_details'] ?? ''));
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'ADR History', (string) ($opdPayload['adr_history'] ?? ''));
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Current Medications', (string) ($opdPayload['current_medications'] ?? ''));
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'Co-Morbidities', (string) ($opdPayload['co_morbidities'] ?? ''));
                        $remarks = $this->upsertLabeledLineInRemarks($remarks, 'HPI Note', (string) ($opdPayload['hpi_note'] ?? ''));
                        $update['Prescriber_Remarks'] = $remarks;
                    }

                    if (! empty($update)) {
                        $saved = (bool) $this->db->table('opd_prescription')
                            ->where('id', (int) $latest['id'])
                            ->update($update) || $saved;
                    }
                }
            }
        }

        return $this->response->setJSON([
            'status' => $saved ? 1 : 0,
            'message' => $saved ? 'Admission history updated' : 'No data updated',
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function saveNursingEntry(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.record.manage',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid request']);
        }

        $entryType = (string) ($this->request->getPost('entry_type') ?? '');
        if (! in_array($entryType, ['vitals', 'fluid', 'treatment', 'admission'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Invalid nursing entry type']);
        }

        $recordedAtInput = (string) ($this->request->getPost('recorded_at') ?? '');
        $recordedAt = $recordedAtInput !== ''
            ? str_replace('T', ' ', $recordedAtInput) . (strlen($recordedAtInput) === 16 ? ':00' : '')
            : date('Y-m-d H:i:s');

        $user = auth()->user();
        $recordedBy = $user->username ?? $user->email ?? 'User';
        $recordedById = $user->id ?? null;

        $nurseIdPost = (int) ($this->request->getPost('recorded_by_nurse_id') ?? 0);
        if ($nurseIdPost > 0) {
            $nurseModel = new \App\Models\NurseModel();
            $nurseRow = $nurseModel->getNurseById($nurseIdPost);
            if ($nurseRow) {
                $recordedBy = ($nurseRow['nurse_code'] ? '[' . $nurseRow['nurse_code'] . '] ' : '') . $nurseRow['name'];
                $recordedById = $nurseRow['id'];
            }
        }

        $data = [
            'ipd_id' => $ipdId,
            'entry_type' => $entryType,
            'recorded_at' => $recordedAt,
            'shift_name' => '',
            'temperature_c' => $this->resolveTemperatureCFromPost(),
            'pulse_rate' => $this->request->getPost('pulse_rate') !== null && $this->request->getPost('pulse_rate') !== '' ? (int) $this->request->getPost('pulse_rate') : null,
            'resp_rate' => $this->request->getPost('resp_rate') !== null && $this->request->getPost('resp_rate') !== '' ? (int) $this->request->getPost('resp_rate') : null,
            'bp_systolic' => $this->request->getPost('bp_systolic') !== null && $this->request->getPost('bp_systolic') !== '' ? (int) $this->request->getPost('bp_systolic') : null,
            'bp_diastolic' => $this->request->getPost('bp_diastolic') !== null && $this->request->getPost('bp_diastolic') !== '' ? (int) $this->request->getPost('bp_diastolic') : null,
            'spo2' => $this->request->getPost('spo2') !== null && $this->request->getPost('spo2') !== '' ? (int) $this->request->getPost('spo2') : null,
            'weight_kg' => $this->request->getPost('weight_kg') !== null && $this->request->getPost('weight_kg') !== '' ? (float) $this->request->getPost('weight_kg') : null,
            'fluid_direction' => (string) ($this->request->getPost('fluid_direction') ?? ''),
            'fluid_route' => (string) ($this->request->getPost('fluid_route') ?? ''),
            'fluid_amount_ml' => $this->request->getPost('fluid_amount_ml') !== null && $this->request->getPost('fluid_amount_ml') !== '' ? (int) $this->request->getPost('fluid_amount_ml') : null,
            'treatment_text' => (string) ($this->request->getPost('treatment_text') ?? ''),
            'general_note' => (string) ($this->request->getPost('general_note') ?? ''),
            'recorded_by' => $recordedBy,
            'recorded_by_id' => $recordedById,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($entryType === 'vitals') {
            $hasVitals = $data['temperature_c'] !== null
                || $data['pulse_rate'] !== null
                || $data['resp_rate'] !== null
                || $data['bp_systolic'] !== null
                || $data['bp_diastolic'] !== null
                || $data['spo2'] !== null
                || $data['weight_kg'] !== null;
            if (! $hasVitals) {
                return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Enter at least one vital value']);
            }
        }

        if ($entryType === 'fluid' && $data['fluid_amount_ml'] === null) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Fluid amount is required']);
        }

        if ($entryType === 'treatment' && trim((string) $data['treatment_text']) === '') {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Treatment note is required']);
        }

        if ($entryType === 'admission') {
            $hasVitals = $data['temperature_c'] !== null
                || $data['pulse_rate'] !== null
                || $data['resp_rate'] !== null
                || $data['bp_systolic'] !== null
                || $data['bp_diastolic'] !== null
                || $data['spo2'] !== null
                || $data['weight_kg'] !== null;
            $hasComplaint = trim((string) $data['treatment_text']) !== '';

            if (! $hasVitals && ! $hasComplaint) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 0,
                    'message' => 'Enter admission complaints or at least one vital value',
                ]);
            }
        }

        $entryId = (int) ($this->request->getPost('entry_id') ?? 0);
        if ($entryId > 0) {
            unset($data['created_at']);
            $this->ipdNursingEntryModel->update($entryId, $data);
            $msg = 'Nursing entry updated';
        } else {
            $this->ipdNursingEntryModel->insert($data);
            $msg = 'Nursing entry saved';
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => $msg,
        ]);
    }

    public function scanNursingPaper(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.record.manage',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid request']);
        }

        $docType = strtolower(trim((string) ($this->request->getPost('document_type') ?? 'auto')));
        if (! in_array($docType, ['auto', 'vitals', 'fluid', 'treatment'], true)) {
            $docType = 'auto';
        }

        $manualText = trim((string) ($this->request->getPost('ocr_text') ?? ''));
        $file = $this->request->getFile('scan_file');
        $uploadedMeta = null;

        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt'];
            $ext = strtolower((string) $file->getExtension());
            if (! in_array($ext, $allowed, true)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 0,
                    'message' => 'Only PDF, image, or text files are allowed',
                ]);
            }

            $scanRef = 'NSCAN-' . date('YmdHis') . '-' . substr(md5((string) mt_rand()), 0, 6);
            $targetDir = WRITEPATH . 'uploads/nursing_scans/' . $ipdId;
            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            $storedName = $scanRef . '.' . $ext;
            $file->move($targetDir, $storedName, true);

            $uploadedMeta = [
                'scan_ref' => $scanRef,
                'path' => rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $storedName,
                'mime' => (string) $file->getClientMimeType(),
            ];
        }

        if ($manualText === '' && $uploadedMeta === null) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'Upload a scan file or paste OCR text',
            ]);
        }

        $parsed = $this->nursingScanExtractionService->extract($docType, $manualText, $uploadedMeta);
        $rows = is_array($parsed['rows'] ?? null) ? $parsed['rows'] : [];

        return $this->response->setJSON([
            'status' => 1,
            'message' => empty($rows)
                ? 'Scan processed. No structured rows detected; please review and edit manually.'
                : 'Scan processed. Review extracted rows before saving.',
            'scan_ref' => (string) ($parsed['scan_ref'] ?? ''),
            'document_type' => $docType,
            'rows' => $rows,
            'warnings' => $parsed['warnings'] ?? [],
            'nabh_requirements' => [
                'Each row must have recorded date/time.',
                'Each row must include nursing note or measurable values.',
                'Vitals rows require at least one vital parameter.',
                'Fluid rows require direction and amount in ml.',
                'All AI/OCR rows must be nurse-reviewed before save.',
            ],
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function saveScannedNursingEntries(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.record.manage',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid request']);
        }

        $rowsJson = (string) ($this->request->getPost('rows_json') ?? '[]');
        $rows = json_decode($rowsJson, true);
        if (! is_array($rows) || empty($rows)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 0,
                'message' => 'No reviewed rows to save',
            ]);
        }

        $scanRef = trim((string) ($this->request->getPost('scan_ref') ?? ''));

        $user = auth()->user();
        $recordedBy = $user->username ?? $user->email ?? 'User';
        $recordedById = $user->id ?? null;

        $savedCount = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            if (! is_array($row)) {
                $errors[] = 'Row ' . ($idx + 1) . ': Invalid payload.';
                continue;
            }

            $entryType = strtolower(trim((string) ($row['entry_type'] ?? '')));
            if (! in_array($entryType, ['vitals', 'fluid', 'treatment'], true)) {
                $errors[] = 'Row ' . ($idx + 1) . ': Unsupported entry type.';
                continue;
            }

            $recordedAt = $this->normalizeScannedRecordedAt((string) ($row['recorded_at'] ?? ''));
            if ($recordedAt === '') {
                $errors[] = 'Row ' . ($idx + 1) . ': Recorded At is required.';
                continue;
            }

            $temperatureF = $this->toNullableFloat($row['temperature_f'] ?? null);
            $temperatureC = $temperatureF !== null ? round(($temperatureF - 32) * 5 / 9, 2) : null;
            $pulse = $this->toNullableInt($row['pulse_rate'] ?? null);
            $resp = $this->toNullableInt($row['resp_rate'] ?? null);
            $sbp = $this->toNullableInt($row['bp_systolic'] ?? null);
            $dbp = $this->toNullableInt($row['bp_diastolic'] ?? null);
            $spo2 = $this->toNullableInt($row['spo2'] ?? null);
            $weight = $this->toNullableFloat($row['weight_kg'] ?? null);
            $fluidAmount = $this->toNullableInt($row['fluid_amount_ml'] ?? null);
            $fluidDirection = strtolower(trim((string) ($row['fluid_direction'] ?? '')));
            $fluidRoute = trim((string) ($row['fluid_route'] ?? ''));
            $treatmentText = trim((string) ($row['treatment_text'] ?? ''));
            $generalNote = trim((string) ($row['general_note'] ?? ''));
            $confidence = $this->toNullableFloat($row['confidence'] ?? null);
            $isCorrected = (int) ($row['is_corrected'] ?? 0) === 1;

            $sourceParts = ['Source: Scanned Paper'];
            if ($scanRef !== '') {
                $sourceParts[] = 'ScanRef: ' . $scanRef;
            }
            $sourceParts[] = 'ReviewedBy: ' . $recordedBy;
            $sourceParts[] = 'ReviewedAt: ' . date('Y-m-d H:i:s');
            if ($confidence !== null) {
                $sourceParts[] = 'AIConfidence: ' . max(0, min(100, (int) round($confidence * 100))) . '%';
            }
            $sourceParts[] = 'Corrected: ' . ($isCorrected ? 'Yes' : 'No');
            $sourceTag = ' [' . implode(' | ', $sourceParts) . ']';
            $generalNote = trim($generalNote . $sourceTag);

            if ($entryType === 'vitals') {
                $hasVitals = $temperatureC !== null || $pulse !== null || $resp !== null || $sbp !== null || $dbp !== null || $spo2 !== null || $weight !== null;
                if (! $hasVitals) {
                    $errors[] = 'Row ' . ($idx + 1) . ': Vitals row requires at least one vital parameter.';
                    continue;
                }
            }

            if ($entryType === 'fluid') {
                if (! in_array($fluidDirection, ['intake', 'output'], true)) {
                    $errors[] = 'Row ' . ($idx + 1) . ': Fluid direction must be intake/output.';
                    continue;
                }
                if ($fluidAmount === null || $fluidAmount <= 0) {
                    $errors[] = 'Row ' . ($idx + 1) . ': Fluid amount is required.';
                    continue;
                }
            }

            if ($entryType === 'treatment' && $treatmentText === '' && $generalNote === $sourceTag) {
                $errors[] = 'Row ' . ($idx + 1) . ': Treatment text or note is required.';
                continue;
            }

            $insert = [
                'ipd_id' => $ipdId,
                'entry_type' => $entryType,
                'recorded_at' => $recordedAt,
                'shift_name' => $this->resolveShiftName($recordedAt),
                'temperature_c' => $temperatureC,
                'pulse_rate' => $pulse,
                'resp_rate' => $resp,
                'bp_systolic' => $sbp,
                'bp_diastolic' => $dbp,
                'spo2' => $spo2,
                'weight_kg' => $weight,
                'fluid_direction' => $entryType === 'fluid' ? $fluidDirection : '',
                'fluid_route' => $entryType === 'fluid' ? $fluidRoute : '',
                'fluid_amount_ml' => $entryType === 'fluid' ? $fluidAmount : null,
                'treatment_text' => $entryType === 'treatment' ? $treatmentText : '',
                'general_note' => $generalNote,
                'recorded_by' => $recordedBy,
                'recorded_by_id' => $recordedById,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $this->ipdNursingEntryModel->insert($insert);
            $savedCount++;
        }

        return $this->response->setJSON([
            'status' => $savedCount > 0 ? 1 : 0,
            'message' => $savedCount > 0
                ? ('Saved ' . $savedCount . ' scanned nursing row(s).')
                : 'No rows saved. Please fix validation errors.',
            'saved_count' => $savedCount,
            'errors' => $errors,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function nursingChartPrint(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.view',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        $panelData = $this->ipdBillingModel->getIpdPanelInfo($ipdId);
        if (empty($panelData)) {
            return $this->response->setStatusCode(404)->setBody('IPD not found');
        }

        $chartDate = (string) ($this->request->getGet('date') ?? date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $chartDate)) {
            $chartDate = date('Y-m-d');
        }

        $nurse = trim((string) ($this->request->getGet('nurse') ?? ''));
        $startDateTime = $chartDate . ' 00:00:00';
        $endDateTime = $chartDate . ' 23:59:59';

        $entries = $this->ipdNursingEntryModel->getByIpdPeriod($ipdId, $startDateTime, $endDateTime, $nurse);

        return view('billing/ipd/nursing_chart_print', [
            'ipd_info' => $panelData['ipd_info'] ?? null,
            'person_info' => $panelData['person_info'] ?? null,
            'nursing_entries' => $entries,
            'chart_date' => $chartDate,
            'chart_nurse' => $nurse,
        ]);
    }

    private function requireAnyPermission(array $permissions)
    {
        if (! function_exists('auth')) {
            return null;
        }

        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return null;
            }
        }

        return $this->response->setStatusCode(403)->setBody('Access denied');
    }

    private function resolveShiftName(string $recordedAt): string
    {
        $timePart = substr($recordedAt, 11, 5);
        if (! preg_match('/^\d{2}:\d{2}$/', $timePart)) {
            return 'Morning';
        }

        [$hour, $minute] = array_map('intval', explode(':', $timePart));
        $minutesFromMidnight = ($hour * 60) + $minute;

        if ($minutesFromMidnight >= 360 && $minutesFromMidnight < 840) {
            return 'Morning';
        }

        if ($minutesFromMidnight >= 840 && $minutesFromMidnight < 1320) {
            return 'Evening';
        }

        return 'Night';
    }

    private function resolveTemperatureCFromPost(): ?float
    {
        $temperatureF = $this->request->getPost('temperature_f');
        if ($temperatureF !== null && $temperatureF !== '') {
            return round((((float) $temperatureF) - 32) * 5 / 9, 2);
        }

        $temperatureC = $this->request->getPost('temperature_c');
        if ($temperatureC !== null && $temperatureC !== '') {
            return (float) $temperatureC;
        }

        return null;
    }

    private function normalizeScannedRecordedAt(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $candidate = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}(:\d{2})?$/', $candidate) === 1) {
            return strlen($candidate) === 16 ? $candidate . ':00' : $candidate;
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function toNullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function getPatientIdFromIpd(int $ipdId): int
    {
        if ($ipdId <= 0 || ! $this->db->tableExists('ipd_master')) {
            return 0;
        }

        $fields = $this->db->getFieldNames('ipd_master') ?? [];
        if (! in_array('id', $fields, true) || ! in_array('p_id', $fields, true)) {
            return 0;
        }

        $row = $this->db->table('ipd_master')
            ->select('p_id')
            ->where('id', $ipdId)
            ->get(1)
            ->getRowArray();

        return max(0, (int) ($row['p_id'] ?? 0));
    }

    private function getPatientHistoryFlags(int $patientId, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = 0;
        }

        if ($patientId <= 0 || ! $this->db->tableExists('patient_master')) {
            return $result;
        }

        $fields = $this->db->getFieldNames('patient_master') ?? [];
        if (! in_array('id', $fields, true)) {
            return $result;
        }

        $select = ['id'];
        foreach ($keys as $key) {
            if (in_array($key, $fields, true)) {
                $select[] = $key;
            }
        }

        $row = $this->db->table('patient_master')
            ->select(implode(',', $select))
            ->where('id', $patientId)
            ->get(1)
            ->getRowArray() ?? [];

        foreach ($keys as $key) {
            if (isset($row[$key])) {
                $result[$key] = (int) $row[$key] === 1 ? 1 : 0;
            }
        }

        return $result;
    }

    private function extractLabeledField(string $remarks, string $label): string
    {
        if (preg_match('/^\s*' . preg_quote($label, '/') . '\s*:\s*(.+)$/im', $remarks, $m) !== 1) {
            return '';
        }

        return trim((string) ($m[1] ?? ''));
    }

    private function getLatestOpdHistorySnapshot(int $patientId): array
    {
        $empty = [
            'drug_allergy_status' => '',
            'drug_allergy_details' => '',
            'adr_history' => '',
            'current_medications' => '',
            'co_morbidities' => '',
            'women_lmp' => '',
            'women_last_baby' => '',
            'women_pregnancy_related' => '',
            'women_related_problems' => '',
            'hpi_note' => '',
        ];

        if ($patientId <= 0 || ! $this->db->tableExists('opd_prescription')) {
            return $empty;
        }

        $fields = $this->db->getFieldNames('opd_prescription') ?? [];
        if (! in_array('p_id', $fields, true)) {
            return $empty;
        }

        $row = $this->db->table('opd_prescription')
            ->where('p_id', $patientId)
            ->orderBy('id', 'DESC')
            ->get(1)
            ->getRowArray() ?? [];

        if (empty($row)) {
            return $empty;
        }

        $remarks = trim((string) ($row['Prescriber_Remarks'] ?? ''));
        foreach (['drug_allergy_status', 'drug_allergy_details', 'adr_history', 'current_medications', 'women_lmp', 'women_last_baby', 'women_pregnancy_related', 'women_related_problems'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                $empty[$field] = $value;
            }
        }

        if ($empty['co_morbidities'] === '') {
            $empty['co_morbidities'] = $this->extractLabeledField($remarks, 'Co-Morbidities');
        }
        if ($empty['hpi_note'] === '') {
            $empty['hpi_note'] = $this->extractLabeledField($remarks, 'HPI Note');
        }

        if ($empty['drug_allergy_status'] === '') {
            $empty['drug_allergy_status'] = $this->extractLabeledField($remarks, 'Drug Allergy Status');
        }
        if ($empty['drug_allergy_details'] === '') {
            $empty['drug_allergy_details'] = $this->extractLabeledField($remarks, 'Drug Allergy Details');
        }
        if ($empty['adr_history'] === '') {
            $empty['adr_history'] = $this->extractLabeledField($remarks, 'ADR History');
        }
        if ($empty['current_medications'] === '') {
            $empty['current_medications'] = $this->extractLabeledField($remarks, 'Current Medications');
        }

        return $empty;
    }

    private function upsertLabeledLineInRemarks(string $remarks, string $label, string $value): string
    {
        $remarks = trim($remarks);
        $pattern = '/^\s*' . preg_quote($label, '/') . '\s*:\s*.*$/im';
        $remarks = preg_replace($pattern, '', $remarks) ?? $remarks;

        $lines = array_filter(array_map(static function (string $line): string {
            return trim($line);
        }, preg_split('/\R/', $remarks) ?: []), static function (string $line): bool {
            return $line !== '';
        });

        $value = trim($value);
        if ($value !== '') {
            $lines[] = $label . ': ' . $value;
        }

        return trim(implode(PHP_EOL, $lines));
    }

    public function bedTransfer(int $ipdId)
    {
        $permission = $this->requireAnyPermission([
            'ipd_nursing.bed.transfer',
            'billing.access',
            'billing.ipd.current-admission',
            'billing.ipd.invoice',
        ]);
        if ($permission) {
            return $permission;
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 0, 'message' => 'Invalid request']);
        }

        $newBedId = (int) $this->request->getPost('bed_id');
        $transferReason = trim((string) $this->request->getPost('transfer_reason'));
        $remarks = trim((string) $this->request->getPost('remarks'));
        $assignmentDateTime = trim((string) $this->request->getPost('assignment_datetime'));

        if ($newBedId <= 0) {
            return $this->response->setJSON(['status' => 0, 'message' => 'Please select a bed']);
        }

        // Validate and format assignment date/time
        if (empty($assignmentDateTime)) {
            $assignmentDateTime = date('Y-m-d H:i:s');
        } else {
            // Convert from HTML5 datetime-local format to MySQL datetime
            $assignmentDateTime = date('Y-m-d H:i:s', strtotime($assignmentDateTime));
        }

        try {
            // Get current bed assignment
            $currentBed = $this->bedMasterModel->where('current_ipd_id', $ipdId)->first();

            // Check if new bed is available
            $newBed = $this->bedMasterModel->find($newBedId);
            if (!$newBed || $newBed->bed_status !== 'available' || $newBed->current_ipd_id !== null) {
                return $this->response->setJSON(['status' => 0, 'message' => 'Selected bed is not available']);
            }

            // Transfer bed
            $this->ipdModel->bedAssign([
                'ipd_id' => $ipdId,
                'bed_id' => $newBedId,
                'Fdate' => $assignmentDateTime,
                'TDate' => $assignmentDateTime,
                'transfer_reason' => $transferReason,
                'transfer_from_bed_id' => $currentBed ? $currentBed->id : null,
                'remarks' => $remarks,
                'assignment_type' => 'transfer',
            ]);

            return $this->response->setJSON([
                'status' => 1,
                'message' => 'Bed transferred successfully',
                'reload' => true,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Bed transfer failed for IPD #' . $ipdId . ': ' . $e->getMessage());
            return $this->response->setJSON(['status' => 0, 'message' => 'Bed transfer failed: ' . $e->getMessage()]);
        }
    }
}
