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
                LEFT JOIN ipd_master i ON (b.current_ipd_id = i.id AND i.ipd_status = 0)
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
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
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
            $whereClause .= " AND DATE(o.apointment_date) = CURDATE()";
        }
        if ($docId > 0) {
            $whereClause .= " AND o.doc_id = " . $docId;
        }

        $sql = "SELECT o.opd_id, o.opd_code, o.opd_no, o.p_id, o.doc_id, o.apointment_date, o.opd_status, coalesce(o.opd_fee_type, 'Cash') as opd_type,
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

        $todayTotalCount = (int) $db->query("SELECT count(*) as c FROM opd_master WHERE DATE(apointment_date) = CURDATE()")->getRow()->c;
        $allTotalCount = (int) $db->query("SELECT count(*) as c FROM opd_master")->getRow()->c;

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
    }
}

