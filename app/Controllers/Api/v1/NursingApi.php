<?php

namespace App\Controllers\Api\v1;

use App\Controllers\BaseController;
use App\Models\BedAssignmentHistoryModel;
use App\Models\BedMasterModel;
use App\Models\IpdNursingEntryModel;
use App\Models\NurseModel;
use App\Models\NursingStationModel;

class NursingApi extends BaseController
{
    protected BedMasterModel $bedMasterModel;
    protected BedAssignmentHistoryModel $bedAssignmentModel;
    protected IpdNursingEntryModel $ipdNursingEntryModel;
    protected NursingStationModel $nursingStationModel;
    protected NurseModel $nurseModel;

    public function __construct()
    {
        $this->bedMasterModel       = new BedMasterModel();
        $this->bedAssignmentModel  = new BedAssignmentHistoryModel();
        $this->ipdNursingEntryModel = new IpdNursingEntryModel();
        $this->nursingStationModel  = new NursingStationModel();
        $this->nurseModel           = new NurseModel();
    }

    /**
     * Serves the central Mobile Apps Hub entry point at /app
     */
    public function hubIndex()
    {
        $hubPath = FCPATH . 'App/index.html';
        if (file_exists($hubPath)) {
            return $this->response->setBody(file_get_contents($hubPath));
        }

        return redirect()->to(base_url('app/nursing'));
    }

    /**
     * Serves the React PWA app entry point at /app/nursing
     */
    public function pwaIndex()
    {
        $pwaPath = FCPATH . 'App/Nursing/index.html';
        if (file_exists($pwaPath)) {
            return $this->response->setBody(file_get_contents($pwaPath));
        }

        return $this->response->setJSON([
            'app_name' => 'Nursing Care PWA App Gateway',
            'status' => 'ready',
            'api_base_url' => base_url('api/v1/nursing/'),
            'message' => 'React PWA build target directory /App/Nursing is active. API endpoints are ready for integration.',
            'endpoints' => [
                'beds' => base_url('api/v1/nursing/beds'),
                'workspace' => base_url('api/v1/nursing/workspace/{ipdId}'),
                'save_entry' => base_url('api/v1/nursing/entry/save/{ipdId}'),
                'nurses' => base_url('api/v1/nursing/nurses'),
            ]
        ]);
    }

    /**
     * GET api/v1/nursing/beds
     */
    public function beds()
    {
        $db = db_connect();
        $sql = "SELECT b.id, b.bed_number, b.bed_code, b.bed_category_id, b.ward_id, b.current_ipd_id,
                       b.status, coalesce(b.bed_status, 'available') as bed_status,
                       w.ward_name, c.category_name,
                       i.id as ipd_id, i.ipd_code, i.p_id, i.r_doc_id, i.r_doc_name,
                       p.p_fname as patient_name, p.p_code as uhid, p.mphone1, p.gender, p.age, p.dob,
                       coalesce(ipd_doc_list.doc_name, concat('Dr. ', i.r_doc_name)) as doctor_name
                FROM bed_master b
                LEFT JOIN ward_master w ON b.ward_id = w.id
                LEFT JOIN bed_category_master c ON b.bed_category_id = c.id
                LEFT JOIN ipd_master i ON ((b.current_ipd_id = i.id OR (b.bed_number = i.bed_no AND i.bed_no != '')) AND i.ipd_status = 0)
                LEFT JOIN patient_master p ON i.p_id = p.id
                LEFT JOIN (
                    select i.ipd_id, group_concat(distinct concat_ws(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname)) as doc_name
                    from ipd_master_doc_list i
                    join doctor_master d on i.doc_id = d.id
                    group by i.ipd_id
                ) ipd_doc_list ON i.id = ipd_doc_list.ipd_id
                ORDER BY w.ward_name ASC, b.bed_number ASC";

        $records = $db->query($sql)->getResultArray();
        foreach ($records as &$r) {
            $r['status'] = (!empty($r['patient_name']) || !empty($r['ipd_id'])) ? 'occupied' : 'available';
        }

        $nursingStations = $this->nursingStationModel->getActiveStations();

        return $this->response->setJSON([
            'status' => 1,
            'data' => [
                'beds' => $records,
                'nursing_stations' => $nursingStations,
            ]
        ]);
    }

    /**
     * GET api/v1/nursing/nurses
     */
    public function nurses()
    {
        return $this->response->setJSON([
            'status' => 1,
            'data' => $this->nurseModel->getActiveNurses()
        ]);
    }

    /**
     * POST api/v1/nursing/auth/verify-pin
     */
    public function verifyPin()
    {
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $nurseId = (int) ($post['nurse_id'] ?? 0);
        $nurseCode = trim((string) ($post['nurse_code'] ?? ''));
        $pin = trim((string) ($post['pin'] ?? ''));

        if ($nurseId <= 0 && $nurseCode !== '') {
            $nurseRow = db_connect()->table('nurse_master')->where('nurse_code', $nurseCode)->get()->getRowArray();
        } else {
            $nurseRow = $this->nurseModel->getNurseById($nurseId);
        }

        if (! $nurseRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Nurse profile not found']);
        }

        $hashedPin = (string) ($nurseRow['app_pin'] ?? '');

        if ($hashedPin === '') {
            return $this->response->setJSON([
                'status' => 2,
                'message' => 'PIN not set yet. Please set a 4-6 digit PIN.',
                'nurse' => [
                    'id' => (int) $nurseRow['id'],
                    'nurse_code' => $nurseRow['nurse_code'],
                    'name' => $nurseRow['name'],
                    'designation' => $nurseRow['designation'] ?? 'Staff Nurse',
                ],
            ]);
        }

        $isValid = password_verify($pin, $hashedPin) || ($pin === $hashedPin);

        if (! $isValid) {
            return $this->response->setStatusCode(401)->setJSON(['status' => 0, 'message' => 'Incorrect Security PIN']);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Authentication successful',
            'nurse' => [
                'id' => (int) $nurseRow['id'],
                'nurse_code' => $nurseRow['nurse_code'],
                'name' => $nurseRow['name'],
                'designation' => $nurseRow['designation'] ?? 'Staff Nurse',
            ],
        ]);
    }

    /**
     * POST api/v1/nursing/auth/set-pin
     */
    public function setPin()
    {
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $nurseId = (int) ($post['nurse_id'] ?? 0);
        $newPin = trim((string) ($post['new_pin'] ?? ''));
        $oldPin = trim((string) ($post['old_pin'] ?? ''));

        if ($nurseId <= 0 || strlen($newPin) < 4) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Valid Nurse ID and 4-6 digit PIN are required']);
        }

        $nurseRow = $this->nurseModel->getNurseById($nurseId);
        if (! $nurseRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Nurse profile not found']);
        }

        $hashedPin = (string) ($nurseRow['app_pin'] ?? '');
        if ($hashedPin !== '' && $oldPin !== '') {
            $isValid = password_verify($oldPin, $hashedPin) || ($oldPin === $hashedPin);
            if (! $isValid) {
                return $this->response->setStatusCode(401)->setJSON(['status' => 0, 'message' => 'Current PIN is incorrect']);
            }
        }

        $this->nurseModel->updateNurse($nurseId, [
            'app_pin' => password_hash($newPin, PASSWORD_DEFAULT),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Security PIN updated successfully',
        ]);
    }

    /**
     * GET api/v1/nursing/workspace/(:num)
     */
    public function workspace(int $ipdId)
    {
        $entries = $this->ipdNursingEntryModel->getByIpd($ipdId);

        return $this->response->setJSON([
            'status' => 1,
            'ipd_id' => $ipdId,
            'entries' => $entries,
        ]);
    }

    /**
     * POST api/v1/nursing/entry/save/(:num)
     */
    public function saveEntry(int $ipdId)
    {
        $post = $this->request->getPost();
        if (empty($post)) {
            try {
                $post = $this->request->getJSON(true) ?? [];
            } catch (\Throwable $e) {
                $raw = $this->request->getBody();
                $post = is_string($raw) ? (json_decode($raw, true) ?? []) : [];
            }
        }
        $entryType = (string) ($post['entry_type'] ?? '');
        if (! in_array($entryType, ['vitals', 'fluid', 'treatment', 'admission'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Invalid nursing entry type']);
        }

        $recordedAtInput = (string) ($post['recorded_at'] ?? '');
        $recordedAt = $recordedAtInput !== ''
            ? str_replace('T', ' ', $recordedAtInput) . (strlen($recordedAtInput) === 16 ? ':00' : '')
            : date('Y-m-d H:i:s');

        $data = [
            'ipd_id' => $ipdId,
            'entry_type' => $entryType,
            'recorded_at' => $recordedAt,
            'shift_name' => '',
            'temperature_c' => isset($post['temperature_f']) && $post['temperature_f'] !== '' ? (((float)$post['temperature_f'] - 32) * 5 / 9) : null,
            'pulse_rate' => isset($post['pulse_rate']) && $post['pulse_rate'] !== '' ? (int) $post['pulse_rate'] : null,
            'resp_rate' => isset($post['resp_rate']) && $post['resp_rate'] !== '' ? (int) $post['resp_rate'] : null,
            'bp_systolic' => isset($post['bp_systolic']) && $post['bp_systolic'] !== '' ? (int) $post['bp_systolic'] : null,
            'bp_diastolic' => isset($post['bp_diastolic']) && $post['bp_diastolic'] !== '' ? (int) $post['bp_diastolic'] : null,
            'spo2' => isset($post['spo2']) && $post['spo2'] !== '' ? (int) $post['spo2'] : null,
            'weight_kg' => isset($post['weight_kg']) && $post['weight_kg'] !== '' ? (float) $post['weight_kg'] : null,
            'fluid_direction' => (string) ($post['fluid_direction'] ?? ''),
            'fluid_route' => (string) ($post['fluid_route'] ?? ''),
            'fluid_amount_ml' => isset($post['fluid_amount_ml']) && $post['fluid_amount_ml'] !== '' ? (int) $post['fluid_amount_ml'] : null,
            'treatment_text' => (string) ($post['treatment_text'] ?? ''),
            'general_note' => (string) ($post['general_note'] ?? ''),
            'recorded_by' => (string) ($post['recorded_by'] ?? 'Staff'),
            'recorded_by_id' => isset($post['recorded_by_id']) ? (int) $post['recorded_by_id'] : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $entryId = (int) ($post['entry_id'] ?? 0);
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

    /**
     * GET api/v1/nursing/opd/list
     */
    public function opdList()
    {
        $db = db_connect();
        helper('common');

        $dateMode = $this->request->getGet('date_mode') ?? 'today';
        $docId = (int) ($this->request->getGet('doctor_id') ?? 0);
        $vitalsFilter = $this->request->getGet('vitals_status') ?? 'all'; // 'all', 'pending', 'done'

        $whereClause = "WHERE 1=1";
        if ($dateMode === 'today') {
            $whereClause .= " AND (DATE(o.apointment_date) = CURDATE() OR (o.apointment_date IS NULL AND DATE(o.opd_book_date) = CURDATE()))";
        }
        if ($docId > 0) {
            $whereClause .= " AND o.doc_id = " . $docId;
        }

        $sql = "SELECT o.opd_id, o.opd_code, o.opd_no, o.p_id, o.doc_id, o.apointment_date, o.opd_book_date, o.opd_status, coalesce(o.opd_fee_type, 'Cash') as opd_type,
                       p.p_fname as fname, p.p_rname as rname, p.p_code as uhid, p.mphone1, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob,
                       concat('Dr. ', d.p_fname, ' ', coalesce(d.p_lname, '')) as doctor_name,
                       pr.temp, pr.pulse, pr.bp, pr.diastolic, pr.spo2, pr.weight, pr.height, pr.rr_min
                FROM opd_master o
                JOIN patient_master p ON o.p_id = p.id
                LEFT JOIN doctor_master d ON o.doc_id = d.id
                LEFT JOIN opd_prescription pr ON o.opd_id = pr.opd_id
                {$whereClause}
                ORDER BY o.opd_id DESC";

        $query = $db->query($sql);
        $records = $query->getResultArray();

        $docWhere = $docId > 0 ? " AND doc_id = " . $docId : "";
        $todayTotalCount = (int) $db->query("SELECT count(*) as c FROM opd_master WHERE (DATE(apointment_date) = CURDATE() OR (apointment_date IS NULL AND DATE(opd_book_date) = CURDATE()))" . $docWhere)->getRow()->c;
        $allTotalCount = (int) $db->query("SELECT count(*) as c FROM opd_master WHERE 1=1" . $docWhere)->getRow()->c;

        $counts = [
            'all' => 0, 'waiting' => 0, 'visited' => 0, 'booked' => 0, 'cancelled' => 0,
            'today_total' => $todayTotalCount, 'all_total' => $allTotalCount,
            'pending_vitals' => 0, 'done_vitals' => 0
        ];
        $appointments = [];

        foreach ($records as $r) {
            $statusKey = 'waiting';
            $statusLabel = 'Waiting';
            $st = (int) ($r['opd_status'] ?? 0);
            if ($st === 2) {
                $statusKey = 'visited';
                $statusLabel = 'Visited';
            } elseif ($st === 3) {
                $statusKey = 'cancelled';
                $statusLabel = 'Cancelled';
            } elseif ($st === 1) {
                $statusKey = 'booked';
                $statusLabel = 'Booked';
            }

            $hasVitals = !empty($r['temp']) || !empty($r['pulse']) || !empty($r['bp']) || !empty($r['spo2']) || !empty($r['weight']);

            if ($hasVitals) {
                $counts['done_vitals']++;
            } else {
                $counts['pending_vitals']++;
            }

            if ($vitalsFilter === 'pending' && $hasVitals) {
                continue;
            }
            if ($vitalsFilter === 'done' && !$hasVitals) {
                continue;
            }

            $counts['all']++;
            if (isset($counts[$statusKey])) {
                $counts[$statusKey]++;
            }

            $ageDisplay = 'N/A';
            if (function_exists('get_age_1')) {
                $ageDisplay = get_age_1($r['dob'] ?? null, $r['age'] ?? '', $r['age_in_month'] ?? '', $r['estimate_dob'] ?? '');
            } elseif (! empty($r['age'])) {
                $ageDisplay = $r['age'] . ' Year';
            } elseif (! empty($r['dob']) && $r['dob'] !== '0000-00-00') {
                try {
                    $ageDisplay = date_diff(date_create($r['dob']), date_create('today'))->y . ' Year';
                } catch (\Throwable $e) {}
            }

            $r['status_key'] = $statusKey;
            $r['status_label'] = $statusLabel;
            $r['age_display'] = $ageDisplay;
            $r['gender_label'] = ((int)($r['gender'] ?? 1) === 1) ? 'Male' : 'Female';
            $r['patient_display_name'] = $r['fname'] ?? 'Patient';
            $r['has_vitals'] = $hasVitals;

            $appointments[] = $r;
        }

        return $this->response->setJSON([
            'status' => 1,
            'counts' => $counts,
            'date_mode' => $dateMode,
            'appointments' => $appointments,
        ]);
    }

    /**
     * POST api/v1/nursing/opd/vitals/save/(:num)
     */
    public function saveOpdVitals(int $opdId)
    {
        $db = db_connect();
        $json = $this->request->getJSON(true);
        $post = $this->request->getPost() ?: [];
        $dataInput = ! empty($json) ? $json : $post;

        $opdRow = $db->table('opd_master')->where('opd_id', $opdId)->get()->getRowArray();
        if (! $opdRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'OPD record not found']);
        }

        $vitalsData = [
            'opd_id' => $opdId,
            'doc_id' => (int) ($opdRow['doc_id'] ?? 0),
            'p_id' => (int) ($opdRow['p_id'] ?? 0),
            'date_opd_visit' => date('Y-m-d'),
            'temp' => trim((string) ($dataInput['temp'] ?? '')),
            'pulse' => trim((string) ($dataInput['pulse'] ?? '')),
            'bp' => trim((string) ($dataInput['bp_systolic'] ?? '')),
            'diastolic' => trim((string) ($dataInput['bp_diastolic'] ?? '')),
            'spo2' => trim((string) ($dataInput['spo2'] ?? '')),
            'weight' => trim((string) ($dataInput['weight'] ?? '')),
            'height' => trim((string) ($dataInput['height'] ?? '')),
            'rr_min' => trim((string) ($dataInput['rr_min'] ?? '')),
        ];

        $existing = $db->table('opd_prescription')->where('opd_id', $opdId)->get()->getRowArray();
        if ($existing) {
            $db->table('opd_prescription')->where('opd_id', $opdId)->update($vitalsData);
        } else {
            $db->table('opd_prescription')->insert($vitalsData);
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Patient Vitals saved successfully by Nursing Staff',
        ]);
    }

    /**
     * POST api/v1/nursing/opd/scan/save/(:num)
     */
    public function saveOpdScan(int $opdId)
    {
        $db = db_connect();
        $json = $this->request->getJSON(true);
        $post = $this->request->getPost() ?: [];
        $dataInput = ! empty($json) ? $json : $post;

        $imageBase64 = $dataInput['image_base64'] ?? null;
        if (empty($imageBase64)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Scan photo required']);
        }

        $docType = trim((string) ($dataInput['document_type'] ?? 'Paper Document'));
        $nurseName = trim((string) ($dataInput['nurse_name'] ?? 'Nursing Staff'));

        $imgData = $imageBase64;
        $ext = 'jpg';
        if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
            $imgData = substr($imageBase64, strpos($imageBase64, ',') + 1);
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
        }
        $imgData = str_replace(' ', '+', $imgData);
        $binary = base64_decode($imgData);

        if ($binary === false || strlen($binary) === 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Failed to process document image']);
        }

        $uploadDir = FCPATH . 'uploads/nursing_scans/';
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $filename = 'nursing_opd_doc_' . $opdId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        file_put_contents($uploadDir . $filename, $binary);
        $imageUrl = '/uploads/nursing_scans/' . $filename;

        // Append attachment to OPD prescription advice/investigation
        $opdRow = $db->table('opd_master')->where('opd_id', $opdId)->get()->getRowArray();
        $pId = (int) ($opdRow['p_id'] ?? 0);

        $existing = $db->table('opd_prescription')->where('opd_id', $opdId)->get()->getRowArray();
        $attachmentText = '[Nursing Scan: ' . $docType . ' by ' . $nurseName . '] [IMAGE_ATTACHMENT:' . $imageUrl . ']';

        if ($existing) {
            $newAdvice = trim(($existing['advice'] ?? '') . "\n" . $attachmentText);
            $db->table('opd_prescription')->where('opd_id', $opdId)->update(['advice' => $newAdvice]);
        } else {
            $db->table('opd_prescription')->insert([
                'opd_id' => $opdId,
                'doc_id' => (int) ($opdRow['doc_id'] ?? 0),
                'p_id' => $pId,
                'date_opd_visit' => date('Y-m-d'),
                'advice' => $attachmentText,
            ]);
        }

        // Register in file_upload_data for HMS Scan Doc List popup
        $this->registerFileUploadData([
            'filename' => $filename,
            'public_path' => $imageUrl,
            'opd_id' => $opdId,
            'p_id' => $pId,
            'upload_by' => $nurseName,
            'doc_type' => $docType,
            'file_size_kb' => round(strlen($binary) / 1024, 2),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'OPD Scanned Document saved successfully',
            'image_url' => $imageUrl,
        ]);
    }

    /**
     * POST api/v1/nursing/ipd/scan/save/(:num)
     */
    public function saveIpdScan(int $ipdId)
    {
        $db = db_connect();
        $json = $this->request->getJSON(true);
        $post = $this->request->getPost() ?: [];
        $dataInput = ! empty($json) ? $json : $post;

        $imageBase64 = $dataInput['image_base64'] ?? null;
        if (empty($imageBase64)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Scan photo required']);
        }

        $docType = trim((string) ($dataInput['document_type'] ?? 'Paper Document'));
        $nurseId = (int) ($dataInput['nurse_id'] ?? 0);
        $nurseName = trim((string) ($dataInput['nurse_name'] ?? 'Nursing Staff'));

        $imgData = $imageBase64;
        $ext = 'jpg';
        if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
            $imgData = substr($imageBase64, strpos($imageBase64, ',') + 1);
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
        }
        $imgData = str_replace(' ', '+', $imgData);
        $binary = base64_decode($imgData);

        if ($binary === false || strlen($binary) === 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Failed to process document image']);
        }

        $uploadDir = FCPATH . 'uploads/nursing_scans/';
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $filename = 'nursing_ipd_doc_' . $ipdId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        file_put_contents($uploadDir . $filename, $binary);
        $imageUrl = '/uploads/nursing_scans/' . $filename;

        $fullNoteText = '[Nursing Scanned Document: ' . $docType . '] [IMAGE_ATTACHMENT:' . $imageUrl . ']';

        $ipdRow = $db->table('ipd_master')->where('id', $ipdId)->get()->getRowArray();
        $pId = (int) ($ipdRow['p_id'] ?? 0);

        $data = [
            'ipd_id' => $ipdId,
            'entry_type' => 'treatment',
            'recorded_at' => date('Y-m-d H:i:s'),
            'treatment_text' => $fullNoteText,
            'general_note' => 'Scanned Document: ' . $docType . ' (' . $imageUrl . ')',
            'recorded_by' => '[Nurse] ' . $nurseName,
            'recorded_by_id' => $nurseId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->ipdNursingEntryModel->insert($data);

        // Register in file_upload_data for HMS Scan Doc List popup
        $this->registerFileUploadData([
            'filename' => $filename,
            'public_path' => $imageUrl,
            'ipd_id' => $ipdId,
            'p_id' => $pId,
            'upload_by' => $nurseName,
            'doc_type' => $docType,
            'file_size_kb' => round(strlen($binary) / 1024, 2),
        ]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'IPD Scanned Document uploaded successfully to Patient Chart',
            'image_url' => $imageUrl,
        ]);
    }

    protected function registerFileUploadData(array $info)
    {
        $db = db_connect();
        if (! $db->tableExists('file_upload_data')) {
            return;
        }

        $filename = $info['filename'];
        $publicPath = $info['public_path'];
        $fullPath = FCPATH . ltrim($publicPath, '/');
        $opdId = (int) ($info['opd_id'] ?? 0);
        $ipdId = (int) ($info['ipd_id'] ?? 0);
        $pId = (int) ($info['p_id'] ?? 0);
        $uploadBy = $info['upload_by'] ?? 'App User';
        $docCategory = $info['doc_type'] ?? 'Scanned Document';
        $binarySizeKb = (float) ($info['file_size_kb'] ?? 0);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        $db->table('file_upload_data')->insert([
            'file_name' => $filename,
            'file_type' => 'image/' . ($ext === 'jpg' ? 'jpeg' : $ext),
            'file_path' => str_replace('\\', '/', dirname($fullPath)) . '/',
            'full_path' => str_replace('\\', '/', $fullPath),
            'raw_name' => pathinfo($filename, PATHINFO_FILENAME),
            'orig_name' => $filename,
            'client_name' => $filename,
            'file_ext' => '.' . $ext,
            'file_size' => $binarySizeKb,
            'is_image' => 1,
            'image_type' => $ext,
            'insert_date' => date('Y-m-d H:i:s'),
            'insert_time' => date('Y-m-d H:i:s'),
            'pid' => $pId,
            'opd_id' => $opdId,
            'ipd_id' => $ipdId,
            'upload_by' => $uploadBy,
            'show_type' => 0,
            'isdelete' => 0,
            'document_type' => $docCategory,
            'content_description' => 'Scanned via Mobile PWA App (' . $docCategory . ')',
            'ai_status' => 'pending',
            'ai_alert_flag' => 0,
        ]);

        return (int) $db->insertID();
    }

    /**
     * Search patient by Barcode, QR Code payload, UHID, ABHA ID/Address, Mobile, or Name
     * GET api/v1/nursing/patient/search?q=...
     */
    public function searchPatient()
    {
        $db = db_connect();
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '') {
            return $this->response->setJSON(['status' => 1, 'data' => []]);
        }

        // Check if $q is a JSON payload from ABHA QR or Hospital QR
        $abhaHidn = null;
        $abhaAddress = null;
        $abhaMobile = null;
        $abhaName = null;

        if (str_starts_with($q, '{') && str_ends_with($q, '}')) {
            $jsonParsed = json_decode($q, true);
            if (is_array($jsonParsed)) {
                $abhaHidn = $jsonParsed['hidn'] ?? $jsonParsed['abha_number'] ?? $jsonParsed['hid_number'] ?? null;
                $abhaAddress = $jsonParsed['hid'] ?? $jsonParsed['abha_address'] ?? null;
                $abhaMobile = $jsonParsed['mobile'] ?? $jsonParsed['phone'] ?? null;
                $abhaName = $jsonParsed['name'] ?? null;
                $uhidFromJson = $jsonParsed['uhid'] ?? $jsonParsed['p_code'] ?? null;
                if ($uhidFromJson) {
                    $q = $uhidFromJson;
                }
            }
        }

        // Clean query (e.g. remove hyphens if looks like ABHA number)
        $cleanAbha = str_replace('-', '', $q);

        $builder = $db->table('patient_master p');
        $builder->select('p.id, p.p_code, p.p_fname, p.title, p.gender, p.dob, p.age, p.mphone1, p.mphone2, p.email1, p.city, p.district, p.state, p.abha_id, p.abha_address, p.abha_verified_status, p.abha_kyc_verified, p.abha_mobile_verified, p.abha_profile_photo_base64');

        $builder->groupStart();
        $builder->where('p.p_code', $q);
        $builder->orWhere('p.old_uhid', $q);
        $builder->orWhere('p.mphone1', $q);
        $builder->orWhere('p.mphone2', $q);
        $builder->orWhere('p.abha_id', $q);
        $builder->orWhere('p.abha_id', $cleanAbha);
        $builder->orWhere('p.abha_address', $q);

        if ($abhaHidn) {
            $builder->orWhere('p.abha_id', $abhaHidn);
            $builder->orWhere('p.abha_id', str_replace('-', '', $abhaHidn));
        }
        if ($abhaAddress) {
            $builder->orWhere('p.abha_address', $abhaAddress);
        }
        if ($abhaMobile) {
            $builder->orWhere('p.mphone1', $abhaMobile);
            $builder->orWhere('p.mphone2', $abhaMobile);
        }

        if (strlen($q) >= 2) {
            $builder->orLike('p.p_fname', $q);
            $builder->orLike('p.mphone1', $q);
            $builder->orLike('p.p_code', $q);
        }
        $builder->groupEnd();

        $patients = $builder->limit(15)->get()->getResultArray();

        // Enrich with current IPD bed admission and today's OPD appointment status
        $today = date('Y-m-d');
        foreach ($patients as &$pat) {
            $pId = (int) $pat['id'];
            $pat['fullName'] = trim(($pat['title'] ? $pat['title'] . ' ' : '') . $pat['p_fname']);

            // Check IPD admission
            $ipdRow = $db->table('ipd_master i')
                ->select('i.id as ipd_id, i.ipd_code, i.bed_no, i.register_date, i.r_doc_name, b.bed_number, w.ward_name')
                ->join('bed_master b', 'b.id = i.bed_no OR b.bed_number = i.bed_no', 'left')
                ->join('ward_master w', 'w.id = b.ward_id', 'left')
                ->where('i.p_id', $pId)
                ->groupStart()
                    ->where('i.discharge_date IS NULL')
                    ->orWhere('i.discharge_date', '0000-00-00')
                ->groupEnd()
                ->orderBy('i.id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $pat['ipd_admission'] = $ipdRow ?: null;

            // Check Today OPD appointment
            $opdRow = $db->table('opd_master o')
                ->select('o.opd_id, o.opd_code, o.opd_no as token_no, o.doc_id, o.doc_name as doctor_name, o.apointment_date, o.opd_book_date, pr.temp, pr.pulse, pr.bp, pr.diastolic, pr.spo2, pr.weight, pr.height, pr.rr_min, pr.glucose, pr.waist')
                ->join('opd_prescription pr', 'pr.opd_id = o.opd_id', 'left')
                ->where('o.p_id', $pId)
                ->groupStart()
                    ->where('DATE(o.apointment_date)', $today)
                    ->orGroupStart()
                        ->where('o.apointment_date IS NULL')
                        ->where('DATE(o.opd_book_date)', $today)
                    ->groupEnd()
                ->groupEnd()
                ->orderBy('o.opd_id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $pat['opd_today'] = $opdRow ?: null;
        }

        return $this->response->setJSON([
            'status' => 1,
            'data' => $patients,
            'count' => count($patients),
        ]);
    }

    /**
     * Save ABDM M2 Wellness Vitals for any registered patient
     * POST api/v1/nursing/patient/wellness/save
     */
    public function savePatientWellness()
    {
        $db = db_connect();
        $post = $this->request->getPost();
        if (empty($post)) {
            try {
                $post = $this->request->getJSON(true) ?? [];
            } catch (\Throwable $e) {
                $raw = $this->request->getBody();
                $post = is_string($raw) ? (json_decode($raw, true) ?? []) : [];
            }
        }

        $patientId = (int) ($post['patient_id'] ?? 0);
        if ($patientId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Patient ID is required']);
        }

        $patient = $db->table('patient_master')->where('id', $patientId)->get()->getRowArray();
        if (! $patient) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Patient record not found']);
        }

        $recordedAtInput = (string) ($post['recorded_at'] ?? '');
        $recordedAt = $recordedAtInput !== ''
            ? str_replace('T', ' ', $recordedAtInput) . (strlen($recordedAtInput) === 16 ? ':00' : '')
            : date('Y-m-d H:i:s');

        $tempF = isset($post['temperature_f']) && $post['temperature_f'] !== '' ? (float) $post['temperature_f'] : null;
        $tempC = $tempF !== null ? (($tempF - 32) * 5 / 9) : null;
        $heightCm = isset($post['height_cm']) && $post['height_cm'] !== '' ? (float) $post['height_cm'] : null;
        $weightKg = isset($post['weight_kg']) && $post['weight_kg'] !== '' ? (float) $post['weight_kg'] : null;
        $bmi = null;
        if ($heightCm && $heightCm > 0 && $weightKg && $weightKg > 0) {
            $heightM = $heightCm / 100;
            $bmi = round($weightKg / ($heightM * $heightM), 2);
        }

        // Anthropometry & Body Measurements
        $waistCm = isset($post['waist_circumference_cm']) && $post['waist_circumference_cm'] !== '' ? (float) $post['waist_circumference_cm'] : null;
        $hipCm = isset($post['hip_circumference_cm']) && $post['hip_circumference_cm'] !== '' ? (float) $post['hip_circumference_cm'] : null;
        $waistHipRatio = null;
        if ($waistCm && $waistCm > 0 && $hipCm && $hipCm > 0) {
            $waistHipRatio = round($waistCm / $hipCm, 2);
        } elseif (isset($post['waist_hip_ratio']) && $post['waist_hip_ratio'] !== '') {
            $waistHipRatio = (float) $post['waist_hip_ratio'];
        }

        // Blood Glucose & POC Lab tests
        $sugarRandom = isset($post['sugar_random']) && $post['sugar_random'] !== '' ? (float) $post['sugar_random'] : null;
        $sugarFasting = isset($post['sugar_fasting']) && $post['sugar_fasting'] !== '' ? (float) $post['sugar_fasting'] : null;
        $sugarPp = isset($post['sugar_pp']) && $post['sugar_pp'] !== '' ? (float) $post['sugar_pp'] : null;
        $hba1c = isset($post['hba1c']) && $post['hba1c'] !== '' ? (float) $post['hba1c'] : null;
        $hemoglobin = isset($post['hemoglobin']) && $post['hemoglobin'] !== '' ? (float) $post['hemoglobin'] : null;

        // Pain score & Physical Activity
        $painScore = isset($post['pain_score']) && $post['pain_score'] !== '' ? (int) $post['pain_score'] : null;
        $dailySteps = isset($post['daily_steps']) && $post['daily_steps'] !== '' ? (int) $post['daily_steps'] : null;
        $sleepHours = isset($post['sleep_hours']) && $post['sleep_hours'] !== '' ? (float) $post['sleep_hours'] : null;
        $exerciseMin = isset($post['exercise_min_per_day']) && $post['exercise_min_per_day'] !== '' ? (int) $post['exercise_min_per_day'] : null;

        // Lifestyle & Social History
        $dietType = ! empty($post['diet_type']) ? trim((string) $post['diet_type']) : null;
        $tobaccoStatus = ! empty($post['tobacco_status']) ? trim((string) $post['tobacco_status']) : null;
        $alcoholStatus = ! empty($post['alcohol_status']) ? trim((string) $post['alcohol_status']) : null;

        // Women's Health
        $womenLmp = ! empty($post['women_lmp']) ? trim((string) $post['women_lmp']) : null;
        $womenPregnancy = ! empty($post['women_pregnancy_status']) ? trim((string) $post['women_pregnancy_status']) : null;

        $wellnessData = [
            'patient_id' => $patientId,
            'uhid' => $patient['p_code'] ?? '',
            'abha_id' => $patient['abha_id'] ?? null,
            'abha_address' => $patient['abha_address'] ?? null,
            'recorded_at' => $recordedAt,
            'temperature_f' => $tempF,
            'temperature_c' => $tempC !== null ? round($tempC, 2) : null,
            'pulse_rate' => isset($post['pulse_rate']) && $post['pulse_rate'] !== '' ? (int) $post['pulse_rate'] : null,
            'resp_rate' => isset($post['resp_rate']) && $post['resp_rate'] !== '' ? (int) $post['resp_rate'] : null,
            'bp_systolic' => isset($post['bp_systolic']) && $post['bp_systolic'] !== '' ? (int) $post['bp_systolic'] : null,
            'bp_diastolic' => isset($post['bp_diastolic']) && $post['bp_diastolic'] !== '' ? (int) $post['bp_diastolic'] : null,
            'spo2' => isset($post['spo2']) && $post['spo2'] !== '' ? (int) $post['spo2'] : null,
            'weight_kg' => $weightKg,
            'height_cm' => $heightCm,
            'bmi' => $bmi,
            'sugar_random' => $sugarRandom,
            'sugar_fasting' => $sugarFasting,
            'sugar_pp' => $sugarPp,
            'hba1c' => $hba1c,
            'hemoglobin' => $hemoglobin,
            'waist_circumference_cm' => $waistCm,
            'hip_circumference_cm' => $hipCm,
            'waist_hip_ratio' => $waistHipRatio,
            'daily_steps' => $dailySteps,
            'sleep_hours' => $sleepHours,
            'exercise_min_per_day' => $exerciseMin,
            'diet_type' => $dietType,
            'tobacco_status' => $tobaccoStatus,
            'alcohol_status' => $alcoholStatus,
            'women_lmp' => $womenLmp,
            'women_pregnancy_status' => $womenPregnancy,
            'pain_score' => $painScore,
            'general_advice' => (string) ($post['general_advice'] ?? ''),
            'diet_lifestyle_note' => (string) ($post['diet_lifestyle_note'] ?? ''),
            'recorded_by' => (string) ($post['recorded_by'] ?? 'Staff Nurse'),
            'recorded_by_id' => isset($post['recorded_by_id']) ? (int) $post['recorded_by_id'] : null,
            'abdm_status' => 'ready',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (! $db->tableExists('patient_wellness_records')) {
            $db->query("CREATE TABLE IF NOT EXISTS `patient_wellness_records` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `patient_id` int unsigned NOT NULL,
              `uhid` varchar(50) DEFAULT NULL,
              `abha_id` varchar(30) DEFAULT NULL,
              `abha_address` varchar(120) DEFAULT NULL,
              `recorded_at` datetime NOT NULL,
              `temperature_f` decimal(5,2) DEFAULT NULL,
              `temperature_c` decimal(5,2) DEFAULT NULL,
              `pulse_rate` int DEFAULT NULL,
              `resp_rate` int DEFAULT NULL,
              `bp_systolic` int DEFAULT NULL,
              `bp_diastolic` int DEFAULT NULL,
              `spo2` int DEFAULT NULL,
              `weight_kg` decimal(5,2) DEFAULT NULL,
              `height_cm` decimal(5,2) DEFAULT NULL,
              `bmi` decimal(5,2) DEFAULT NULL,
              `sugar_random` decimal(6,2) DEFAULT NULL,
              `sugar_fasting` decimal(6,2) DEFAULT NULL,
              `sugar_pp` decimal(6,2) DEFAULT NULL,
              `hba1c` decimal(4,2) DEFAULT NULL,
              `hemoglobin` decimal(4,2) DEFAULT NULL,
              `waist_circumference_cm` decimal(5,2) DEFAULT NULL,
              `hip_circumference_cm` decimal(5,2) DEFAULT NULL,
              `waist_hip_ratio` decimal(4,2) DEFAULT NULL,
              `daily_steps` int DEFAULT NULL,
              `sleep_hours` decimal(4,2) DEFAULT NULL,
              `exercise_min_per_day` int DEFAULT NULL,
              `diet_type` varchar(100) DEFAULT NULL,
              `tobacco_status` varchar(100) DEFAULT NULL,
              `alcohol_status` varchar(100) DEFAULT NULL,
              `women_lmp` date DEFAULT NULL,
              `women_pregnancy_status` varchar(100) DEFAULT NULL,
              `pain_score` tinyint DEFAULT NULL,
              `general_advice` text,
              `diet_lifestyle_note` text,
              `recorded_by` varchar(120) DEFAULT 'Staff Nurse',
              `recorded_by_id` int DEFAULT NULL,
              `fhir_bundle_json` longtext,
              `abdm_status` varchar(30) DEFAULT 'ready',
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_patient_id` (`patient_id`),
              KEY `idx_uhid` (`uhid`),
              KEY `idx_recorded_at` (`recorded_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            // Defensive check for newly added Option B columns
            if (! $db->fieldExists('sugar_random', 'patient_wellness_records')) {
                @$db->query("ALTER TABLE `patient_wellness_records`
                  ADD COLUMN `sugar_random` decimal(6,2) DEFAULT NULL AFTER `bmi`,
                  ADD COLUMN `sugar_fasting` decimal(6,2) DEFAULT NULL AFTER `sugar_random`,
                  ADD COLUMN `sugar_pp` decimal(6,2) DEFAULT NULL AFTER `sugar_fasting`,
                  ADD COLUMN `hba1c` decimal(4,2) DEFAULT NULL AFTER `sugar_pp`,
                  ADD COLUMN `hemoglobin` decimal(4,2) DEFAULT NULL AFTER `hba1c`,
                  ADD COLUMN `waist_circumference_cm` decimal(5,2) DEFAULT NULL AFTER `hemoglobin`,
                  ADD COLUMN `hip_circumference_cm` decimal(5,2) DEFAULT NULL AFTER `waist_circumference_cm`,
                  ADD COLUMN `waist_hip_ratio` decimal(4,2) DEFAULT NULL AFTER `hip_circumference_cm`,
                  ADD COLUMN `daily_steps` int DEFAULT NULL AFTER `waist_hip_ratio`,
                  ADD COLUMN `sleep_hours` decimal(4,2) DEFAULT NULL AFTER `daily_steps`,
                  ADD COLUMN `exercise_min_per_day` int DEFAULT NULL AFTER `sleep_hours`,
                  ADD COLUMN `diet_type` varchar(100) DEFAULT NULL AFTER `exercise_min_per_day`,
                  ADD COLUMN `tobacco_status` varchar(100) DEFAULT NULL AFTER `diet_type`,
                  ADD COLUMN `alcohol_status` varchar(100) DEFAULT NULL AFTER `tobacco_status`,
                  ADD COLUMN `women_lmp` date DEFAULT NULL AFTER `alcohol_status`,
                  ADD COLUMN `women_pregnancy_status` varchar(100) DEFAULT NULL AFTER `women_lmp`,
                  ADD COLUMN `pain_score` tinyint DEFAULT NULL AFTER `women_pregnancy_status`");
            }
        }

        $db->table('patient_wellness_records')->insert($wellnessData);
        $wellnessId = $db->insertID();

        // Build compliant ABDM NRCES FHIR R4 WellnessRecord Document Bundle
        $cleanDate = date('Ymd', strtotime($recordedAt));
        $careContextRef = 'WELLNESS-' . $patientId . '-W' . $wellnessId . '-' . $cleanDate;
        $careContextDisplay = 'Wellness & Vitals Record - ' . date('d M Y', strtotime($recordedAt));
        $fhirBundleJson = null;

        try {
            $sourceVitals = [];
            $addVital = static function (string $code, string $display, mixed $val, string $unit, string $ucum) use (&$sourceVitals): void {
                if ($val !== null && trim((string) $val) !== '') {
                    $sourceVitals[] = [
                        'loinc_code' => $code,
                        'code' => $code,
                        'display' => $display,
                        'value' => (float) $val,
                        'unit' => $unit,
                        'ucum_code' => $ucum,
                    ];
                }
            };

            // Standard Vital Signs
            $addVital('8480-6', 'Systolic blood pressure', $wellnessData['bp_systolic'], 'mmHg', 'mm[Hg]');
            $addVital('8462-4', 'Diastolic blood pressure', $wellnessData['bp_diastolic'], 'mmHg', 'mm[Hg]');
            $addVital('8867-4', 'Heart rate', $wellnessData['pulse_rate'], '/min', '/min');
            $addVital('8302-2', 'Body height', $wellnessData['height_cm'], 'cm', 'cm');
            $addVital('29463-7', 'Body weight', $wellnessData['weight_kg'], 'kg', 'kg');
            $addVital('39156-5', 'Body Mass Index', $wellnessData['bmi'], 'kg/m2', 'kg/m2');
            $addVital('8310-5', 'Body temperature', $wellnessData['temperature_c'] ?? $wellnessData['temperature_f'], 'Cel', 'Cel');
            $addVital('9279-1', 'Respiratory rate', $wellnessData['resp_rate'], '/min', '/min');
            $addVital('59408-5', 'Oxygen saturation in Arterial blood by Pulse oximetry', $wellnessData['spo2'], '%', '%');
            $addVital('72514-3', 'Pain severity - 0-10 verbal numeric rating', $wellnessData['pain_score'], '{score}', '{score}');

            // Body Measurements / Anthropometry
            $addVital('56115-9', 'Waist circumference', $wellnessData['waist_circumference_cm'], 'cm', 'cm');
            $addVital('56114-2', 'Hip circumference', $wellnessData['hip_circumference_cm'], 'cm', 'cm');
            $addVital('8280-0', 'Waist to hip ratio', $wellnessData['waist_hip_ratio'], 'ratio', '{ratio}');

            // Blood Sugar & POC Laboratory Measurements
            $addVital('2339-0', 'Glucose [Mass/volume] in Blood', $wellnessData['sugar_random'], 'mg/dL', 'mg/dL');
            $addVital('1558-6', 'Fasting glucose [Mass/volume] in Blood', $wellnessData['sugar_fasting'], 'mg/dL', 'mg/dL');
            $addVital('1521-4', 'Glucose [Mass/volume] in Blood 2 hours post meal', $wellnessData['sugar_pp'], 'mg/dL', 'mg/dL');
            $addVital('4548-4', 'Hemoglobin A1c/Hemoglobin.total in Blood', $wellnessData['hba1c'], '%', '%');
            $addVital('718-7', 'Hemoglobin [Mass/volume] in Blood', $wellnessData['hemoglobin'], 'g/dL', 'g/dL');

            // Physical Activity & Sleep
            $addVital('55423-8', 'Number of steps in 24 hour Measured', $wellnessData['daily_steps'], '{steps}', '{steps}');
            $addVital('93832-4', 'Sleep duration', $wellnessData['sleep_hours'], 'h', 'h');
            $addVital('55411-3', 'Exercise duration', $wellnessData['exercise_min_per_day'], 'min/d', 'min/d');

            $lifestyle = [];
            if (! empty($wellnessData['diet_type'])) {
                $lifestyle[] = [
                    'code' => '81663-7',
                    'display' => 'Diet Type',
                    'value' => $wellnessData['diet_type'],
                ];
            }
            if (! empty($wellnessData['tobacco_status'])) {
                $lifestyle[] = [
                    'code' => '365981007',
                    'display' => 'Tobacco Smoking Status',
                    'value' => $wellnessData['tobacco_status'],
                ];
            }
            if (! empty($wellnessData['alcohol_status'])) {
                $lifestyle[] = [
                    'code' => '228273003',
                    'display' => 'Alcohol Consumption Status',
                    'value' => $wellnessData['alcohol_status'],
                ];
            }
            if (! empty($wellnessData['diet_lifestyle_note'])) {
                $lifestyle[] = [
                    'code' => 'diet-lifestyle',
                    'display' => 'Diet & Lifestyle Guidance',
                    'value' => $wellnessData['diet_lifestyle_note'],
                ];
            }
            if (! empty($wellnessData['general_advice'])) {
                $lifestyle[] = [
                    'code' => 'general-nursing',
                    'display' => 'General Nursing Observations',
                    'value' => $wellnessData['general_advice'],
                ];
            }

            $womenWellness = [];
            if (! empty($wellnessData['women_lmp'])) {
                $womenWellness['lmp'] = $wellnessData['women_lmp'];
            }
            if (! empty($wellnessData['women_pregnancy_status'])) {
                $womenWellness['pregnancy_status'] = $wellnessData['women_pregnancy_status'];
            }

            $source = [
                'record_id' => (string) $wellnessId,
                'visit_date' => date('Y-m-d', strtotime($recordedAt)),
                'completed_at' => date(DATE_ATOM, strtotime($recordedAt)),
                'hfr_id' => 'IN0510000828',
                'patient' => [
                    'id' => $patientId,
                    'name' => trim(($patient['p_fname'] ?? '') . ' ' . ($patient['p_lname'] ?? '')),
                    'gender' => strtolower((string) ($patient['gender'] ?? 'male')),
                    'dob' => ! empty($patient['dob']) ? date('Y-m-d', strtotime((string) $patient['dob'])) : null,
                    'mobile' => (string) ($patient['mphone1'] ?? ''),
                    'abha_id' => (string) ($patient['abha_id'] ?? ''),
                    'abha_address' => (string) ($patient['abha_address'] ?? ''),
                    'p_code' => (string) ($patient['p_code'] ?? ''),
                ],
                'practitioner' => [
                    'id' => (string) ($wellnessData['recorded_by_id'] ?? '1'),
                    'name' => (string) ($wellnessData['recorded_by'] ?? 'Staff Nurse'),
                ],
                'vitals' => $sourceVitals,
                'women_wellness' => $womenWellness,
                'lifestyle' => $lifestyle,
            ];

            $generator = new \App\Libraries\Abdm\Fhir\Generators\WellnessFhirGenerator();
            $genResult = $generator->generate($source);
            $fhirBundle = $genResult['fhir_bundle'] ?? [];
            if (! empty($fhirBundle)) {
                $fhirBundleJson = json_encode($fhirBundle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $db->table('patient_wellness_records')->where('id', $wellnessId)->update([
                    'fhir_bundle_json' => $fhirBundleJson,
                    'care_context_reference' => $careContextRef,
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', '[savePatientWellness] FHIR generation error: ' . $e->getMessage());
        }

        // Register in health_records for ABDM discovery & push
        $healthRecordId = 0;
        if ($db->tableExists('health_records')) {
            $effectiveAbha = trim((string) ($patient['abha_address'] ?? $patient['abha_id'] ?? ''));
            $db->table('health_records')->insert([
                'patient_id' => $patientId,
                'abha_id' => $effectiveAbha !== '' ? $effectiveAbha : null,
                'hi_type' => 'WellnessRecord',
                'entity_type' => 'wellness',
                'entity_id' => (string) $wellnessId,
                'push_status' => 'local_discovery_ready',
                'care_context_reference' => $careContextRef,
                'record_data' => $fhirBundleJson ?? json_encode($wellnessData),
                'created_by_name' => (string) ($post['recorded_by'] ?? 'Staff Nurse'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $healthRecordId = (int) $db->insertID();
        }

        // Proactive push to ABDM Bridge when patient has ABHA ID or Address
        $pushStatus = 'local_discovery_ready';
        $bridgeRecordId = null;
        $queueId = null;
        $pushMsg = '';
        $effectiveAbha = trim((string) ($patient['abha_address'] ?? $patient['abha_id'] ?? ''));

        if ($effectiveAbha !== '') {
            try {
                $connector = \App\Libraries\Abdm\AbdmConnectorFactory::make();
                $pushPayload = [
                    'patient_id' => (string) $patientId,
                    'patient_ref' => (string) ($patient['p_code'] ?? ('P' . $patientId)),
                    'local_patient_id' => (string) ($patient['p_code'] ?? ('P' . $patientId)),
                    'patient_name' => trim(($patient['p_fname'] ?? '') . ' ' . ($patient['p_lname'] ?? '')),
                    'abha_id' => (string) ($patient['abha_id'] ?? ''),
                    'abha_address' => (string) ($patient['abha_address'] ?? ''),
                    'year_of_birth' => ! empty($patient['dob']) ? date('Y', strtotime((string) $patient['dob'])) : null,
                    'hi_type' => 'WellnessRecord',
                    'record_type' => 'WellnessRecord',
                    'visit_date' => date('Y-m-d', strtotime($recordedAt)),
                    'care_context_reference' => $careContextRef,
                    'care_context_display' => 'Wellness Record - ' . date('d/m/Y', strtotime($recordedAt)),
                    'notes' => 'Wellness & Vitals Record',
                    'queue_id' => $careContextRef,
                    'record_data' => ! empty($fhirBundle) ? $fhirBundle : json_decode((string) ($fhirBundleJson ?? '{}'), true),
                ];
                $pushRes = $connector->pushRecord($pushPayload);
                $pushOk = (int) ($pushRes['ok'] ?? 0);
                $httpCode = (int) ($pushRes['http_code'] ?? 0);
                $statusVal = strtolower((string) ($pushRes['status'] ?? ''));

                if ($pushOk === 1 || in_array($httpCode, [200, 201, 202, 409], true) || in_array($statusVal, ['queued', 'pushed', 'linked'], true)) {
                    $pushStatus = 'queued';
                    $bridgeRecordId = (int) ($pushRes['record_id'] ?? 0);
                    $queueId = (string) ($pushRes['queue_id'] ?? '');
                    $pushMsg = ' & pushed to ABDM Bridge' . ($bridgeRecordId > 0 ? " (Record #{$bridgeRecordId})" : '');
                } else {
                    $pushStatus = 'failed';
                    $pushMsg = ' (ABDM Bridge push pending: ' . ($pushRes['error_text'] ?? 'Connection error') . ')';
                }
            } catch (\Throwable $pe) {
                log_message('warning', '[savePatientWellness] Bridge push error: ' . $pe->getMessage());
                $pushStatus = 'failed';
            }
        }

        // Update patient_wellness_records with push status & references
        $db->table('patient_wellness_records')->where('id', $wellnessId)->update([
            'care_context_reference' => $careContextRef,
            'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
            'queue_id' => $queueId !== '' ? $queueId : null,
            'abdm_status' => $pushStatus,
        ]);

        // Update health_records
        if ($healthRecordId > 0) {
            $db->table('health_records')->where('id', $healthRecordId)->update([
                'push_status' => $pushStatus,
                'bridge_record_id' => $bridgeRecordId > 0 ? $bridgeRecordId : null,
                'abdm_txn_id' => $queueId !== '' ? $queueId : null,
                'push_at' => $pushStatus === 'queued' ? date('Y-m-d H:i:s') : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Create or refresh task in abdm_work_tasks
        if ($db->tableExists('abdm_work_tasks')) {
            try {
                $taskService = new \App\Libraries\AbdmWorkTaskService();
                $tId = $taskService->createOrRefreshTask(
                    'wellness_record_publish',
                    'patient_wellness_records',
                    'wellness',
                    (string) $wellnessId,
                    $patientId,
                    trim(($patient['p_fname'] ?? '') . ' ' . ($patient['p_lname'] ?? '')),
                    $effectiveAbha,
                    'submit',
                    [
                        'wellness_id' => $wellnessId,
                        'care_context_reference' => $careContextRef,
                        'bridge_record_id' => $bridgeRecordId,
                        'clinical_timestamp' => $recordedAt,
                        'trigger' => 'nursing.wellness_saved',
                    ]
                );
                if ($pushStatus === 'queued' && $tId > 0) {
                    $db->table('abdm_work_tasks')->where('id', $tId)->update([
                        'status' => 'completed',
                        'last_action_result' => 'Pushed to ABDM Bridge (ID #' . $bridgeRecordId . ', Queue: ' . $queueId . ')',
                        'completed_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } catch (\Throwable $te) {
                log_message('warning', '[savePatientWellness] Task create error: ' . $te->getMessage());
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'ABDM M2 Wellness & Vitals recorded' . $pushMsg,
            'wellness_id' => $wellnessId,
            'care_context_reference' => $careContextRef,
            'abdm_push_status' => $pushStatus,
            'bridge_record_id' => $bridgeRecordId,
            'bmi' => $bmi,
        ]);
    }

    /**
     * Get patient wellness & vitals history
     * GET api/v1/nursing/patient/wellness/history/(:num)
     */
    public function getPatientWellnessHistory(int $patientId)
    {
        $db = db_connect();
        $records = $db->table('patient_wellness_records')
            ->where('patient_id', $patientId)
            ->orderBy('recorded_at', 'DESC')
            ->limit(30)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'patient_id' => $patientId,
            'records' => $records,
        ]);
    }

    /**
     * Save ABDM M2 Health Document from scan/camera for any registered patient
     * POST api/v1/nursing/patient/document/save
     */
    public function savePatientDocument()
    {
        $db = db_connect();
        $post = $this->request->getPost();
        if (empty($post)) {
            try {
                $post = $this->request->getJSON(true) ?? [];
            } catch (\Throwable $e) {
                $raw = $this->request->getBody();
                $post = is_string($raw) ? (json_decode($raw, true) ?? []) : [];
            }
        }

        $patientId = (int) ($post['patient_id'] ?? 0);
        if ($patientId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Patient ID required']);
        }

        $patient = $db->table('patient_master')->where('id', $patientId)->get()->getRowArray();
        if (! $patient) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Patient record not found']);
        }

        $imageBase64 = $post['image_base64'] ?? null;
        if (empty($imageBase64)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Scanned document image required']);
        }

        $docCategory = trim((string) ($post['document_type'] ?? 'Health Document'));
        $nurseName = trim((string) ($post['nurse_name'] ?? 'Staff Nurse'));
        $nurseId = (int) ($post['nurse_id'] ?? 0);
        $docDate = trim((string) ($post['document_date'] ?? date('Y-m-d')));
        $remarks = trim((string) ($post['remarks'] ?? ''));

        $imgData = $imageBase64;
        $ext = 'jpg';
        if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
            $imgData = substr($imageBase64, strpos($imageBase64, ',') + 1);
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') $ext = 'jpg';
        }
        $imgData = str_replace(' ', '+', $imgData);
        $binary = base64_decode($imgData);

        if ($binary === false || strlen($binary) === 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Failed to process document image']);
        }

        $uploadDir = FCPATH . 'uploads/health_documents/';
        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $filename = 'abdm_m2_doc_p' . $patientId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
        file_put_contents($uploadDir . $filename, $binary);
        $imageUrl = '/uploads/health_documents/' . $filename;

        // Map to ABDM HI Type
        $hiType = 'HealthDocumentRecord';
        $catLower = strtolower($docCategory);
        if (str_contains($catLower, 'presc')) {
            $hiType = 'PrescriptionRecord';
        } elseif (str_contains($catLower, 'diag') || str_contains($catLower, 'lab') || str_contains($catLower, 'report')) {
            $hiType = 'DiagnosticReport';
        } elseif (str_contains($catLower, 'discharg')) {
            $hiType = 'DischargeSummary';
        } elseif (str_contains($catLower, 'immun')) {
            $hiType = 'ImmunizationRecord';
        }

        // Register in file_upload_data for HMS Scan Doc List popup first
        $fileUploadId = $this->registerFileUploadData([
            'filename' => $filename,
            'public_path' => $imageUrl,
            'p_id' => $patientId,
            'upload_by' => $nurseName,
            'doc_type' => $docCategory,
            'file_size_kb' => round(strlen($binary) / 1024, 2),
        ]);

        $cleanDate = date('Ymd', strtotime($docDate));
        $careContextRef = 'DOC-file-' . ($fileUploadId > 0 ? $fileUploadId : time()) . '-' . $cleanDate;

        $healthRecordId = null;
        if ($db->tableExists('health_records')) {
            $db->table('health_records')->insert([
                'patient_id' => $patientId,
                'abha_id' => $patient['abha_id'] ?? null,
                'hi_type' => $hiType,
                'entity_type' => 'patient_document',
                'entity_id' => (string) ($fileUploadId > 0 ? $fileUploadId : time()),
                'attachment_path' => $imageUrl,
                'push_status' => 'pending',
                'care_context_reference' => $careContextRef,
                'record_data' => json_encode([
                    'category' => $docCategory,
                    'document_date' => $docDate,
                    'remarks' => $remarks,
                    'uploaded_by' => $nurseName,
                    'image_url' => $imageUrl,
                    'file_upload_id' => $fileUploadId,
                ]),
                'created_by_name' => $nurseName,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $healthRecordId = $db->insertID();
        }

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'ABDM M2 Health Document uploaded successfully',
            'image_url' => $imageUrl,
            'health_record_id' => $healthRecordId,
            'hi_type' => $hiType,
            'care_context_reference' => $careContextRef,
        ]);
    }
}


