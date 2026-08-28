<?php

namespace App\Controllers\Api\v1;

use App\Controllers\BaseController;
use App\Models\DoctorModel;
use App\Models\IpdNursingEntryModel;
use App\Models\OpdModel;

class DoctorApi extends BaseController
{
    protected DoctorModel $doctorModel;
    protected IpdNursingEntryModel $ipdNursingEntryModel;
    protected OpdModel $opdModel;

    public function __construct()
    {
        $this->doctorModel          = new DoctorModel();
        $this->ipdNursingEntryModel = new IpdNursingEntryModel();
        $this->opdModel             = new OpdModel();
    }

    /**
     * Serves the React PWA app entry point at /app/doctor
     */
    public function pwaIndex()
    {
        $pwaPath = FCPATH . 'App/Doctor/index.html';
        if (file_exists($pwaPath)) {
            return $this->response->setBody(file_get_contents($pwaPath));
        }

        return $this->response->setJSON([
            'app_name' => 'Doctor EMR & Bedside Care PWA Gateway',
            'status' => 'ready',
            'api_base_url' => base_url('api/v1/doctor/'),
            'message' => 'React PWA build target directory /App/Doctor is active. API endpoints are ready.',
            'endpoints' => [
                'doctors' => base_url('api/v1/doctor/list'),
                'verify_pin' => base_url('api/v1/doctor/auth/verify-pin'),
                'set_pin' => base_url('api/v1/doctor/auth/set-pin'),
                'opd_list' => base_url('api/v1/doctor/opd/list/{docId}'),
                'ipd_list' => base_url('api/v1/doctor/ipd/list/{docId}'),
                'patient_workspace' => base_url('api/v1/doctor/ipd/workspace/{ipdId}'),
                'save_treatment' => base_url('api/v1/doctor/ipd/treatment/save/{ipdId}'),
            ]
        ]);
    }

    /**
     * GET api/v1/doctor/list
     */
    public function doctors()
    {
        $doctors = $this->doctorModel->getDoctors();
        $list = [];
        foreach ($doctors as $d) {
            $list[] = [
                'id' => (int) $d->id,
                'name' => trim(($d->p_title ?? '') . ' ' . ($d->p_fname ?? '')),
                'email' => $d->email1 ?? '',
                'phone' => $d->mphone1 ?? '',
                'doc_sign' => $d->doc_sign ?? '',
                'has_pin' => ! empty($d->app_pin),
            ];
        }

        return $this->response->setJSON([
            'status' => 1,
            'data' => $list,
        ]);
    }

    /**
     * POST api/v1/doctor/auth/verify-pin
     */
    public function verifyPin()
    {
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $docId = (int) ($post['doctor_id'] ?? 0);
        $pin = trim((string) ($post['pin'] ?? ''));

        if ($docId <= 0) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Doctor ID is required']);
        }

        $doctorRows = $this->doctorModel->getDoctorById($docId);
        $doctorRow = $doctorRows[0] ?? null;

        if (! $doctorRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Doctor profile not found']);
        }

        $hashedPin = (string) ($doctorRow->app_pin ?? '');

        if ($hashedPin === '') {
            return $this->response->setJSON([
                'status' => 2,
                'message' => 'PIN not set yet. Please set a 4-6 digit PIN.',
                'doctor' => [
                    'id' => (int) $doctorRow->id,
                    'name' => trim(($doctorRow->p_title ?? '') . ' ' . ($doctorRow->p_fname ?? '')),
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
            'doctor' => [
                'id' => (int) $doctorRow->id,
                'name' => trim(($doctorRow->p_title ?? '') . ' ' . ($doctorRow->p_fname ?? '')),
            ],
        ]);
    }

    /**
     * POST api/v1/doctor/auth/set-pin
     */
    public function setPin()
    {
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $docId = (int) ($post['doctor_id'] ?? 0);
        $newPin = trim((string) ($post['new_pin'] ?? ''));
        $oldPin = trim((string) ($post['old_pin'] ?? ''));

        if ($docId <= 0 || strlen($newPin) < 4) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Valid Doctor ID and 4-6 digit PIN are required']);
        }

        $doctorRows = $this->doctorModel->getDoctorById($docId);
        $doctorRow = $doctorRows[0] ?? null;

        if (! $doctorRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'Doctor profile not found']);
        }

        $hashedPin = (string) ($doctorRow->app_pin ?? '');
        if ($hashedPin !== '' && $oldPin !== '') {
            $isValid = password_verify($oldPin, $hashedPin) || ($oldPin === $hashedPin);
            if (! $isValid) {
                return $this->response->setStatusCode(401)->setJSON(['status' => 0, 'message' => 'Current PIN is incorrect']);
            }
        }

        $this->doctorModel->updateDoctor([
            'app_pin' => password_hash($newPin, PASSWORD_DEFAULT),
        ], $docId);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Security PIN updated successfully',
        ]);
    }

    /**
     * GET api/v1/doctor/opd/list/(:num)
     */
    public function opdList(int $docId)
    {
        $db = db_connect();
        helper('common');

        $dateMode = $this->request->getGet('date_mode') ?? 'today'; // 'today' or 'all'

        $baseSql = "SELECT o.opd_id, o.opd_code, o.opd_no, o.opd_status, o.apointment_date,
                           o.opd_fee_desc, o.opd_fee_amount, o.payment_status, o.opd_fee_type,
                           coalesce(o.running_opd, 0) as running_opd,
                           p.id as p_id, p.p_code, p.p_fname as P_name, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob, p.mphone1,
                           (CASE
                               WHEN (coalesce(o.running_opd,0)=1 OR o.opd_fee_type=3 OR UPPER(coalesce(o.opd_fee_desc,'')) LIKE '%RUNNING%') THEN 'Running'
                               WHEN o.payment_mode=4 THEN 'Credit to ECHS'
                               WHEN o.payment_status=1 AND o.payment_mode=0 THEN 'No Cost'
                               WHEN o.payment_mode=1 THEN 'Cash'
                               WHEN o.payment_mode=2 THEN 'Bank Card'
                               ELSE 'Pending'
                           END) as opd_type,
                           IF(pr.id IS NULL, 0, 1) as has_prescription,
                           coalesce(pr.queue_no, 0) as queue_no
                    FROM opd_master o
                    JOIN patient_master p ON o.p_id = p.id
                    LEFT JOIN opd_prescription pr ON o.opd_id = pr.opd_id
                    WHERE 1=1";

        if ($docId > 0) {
            $baseSql .= " AND o.doc_id = " . (int) $docId;
        }

        $sqlToday = $baseSql . " AND DATE(o.apointment_date) = CURDATE() ORDER BY o.opd_id DESC LIMIT 100";
        $sqlAll   = $baseSql . " ORDER BY o.opd_id DESC LIMIT 100";

        $queryToday = $db->query($sqlToday);
        $todayRows = $queryToday->getResultArray();

        $queryAll = $db->query($sqlAll);
        $allRows = $queryAll->getResultArray();

        $targetRows = ($dateMode === 'today') ? $todayRows : $allRows;

        $appointments = [];
        $counts = [
            'all' => count($targetRows),
            'booked' => 0,
            'waiting' => 0,
            'visited' => 0,
            'cancelled' => 0,
            'today_total' => count($todayRows),
            'all_total' => count($allRows),
        ];

        foreach ($targetRows as $r) {
            $statusInt = (int) ($r['opd_status'] ?? 0);
            $statusText = 'waiting';
            if ($statusInt === 0) {
                $statusText = 'booked';
                $counts['booked']++;
            } elseif ($statusInt === 1) {
                $statusText = 'waiting';
                $counts['waiting']++;
            } elseif ($statusInt === 2) {
                $statusText = 'visited';
                $counts['visited']++;
            } elseif ($statusInt === 3) {
                $statusText = 'cancelled';
                $counts['cancelled']++;
            } else {
                $counts['waiting']++;
            }

            $ageDisplay = 'N/A';
            if (function_exists('get_age_1')) {
                $ageDisplay = get_age_1($r['dob'] ?? null, $r['age'] ?? '', $r['age_in_month'] ?? '', $r['estimate_dob'] ?? '', $r['apointment_date'] ?? null);
            } elseif (! empty($r['age'])) {
                $ageDisplay = $r['age'] . ' Year';
            } elseif (! empty($r['dob']) && $r['dob'] !== '0000-00-00') {
                try {
                    $ageDisplay = date_diff(date_create($r['dob']), date_create('today'))->y . ' Year';
                } catch (\Throwable $e) {}
            }

            $r['status_label'] = ucfirst($statusText);
            $r['status_key'] = $statusText;
            $r['gender_label'] = ((int)($r['gender'] ?? 1) === 1) ? 'Male' : 'Female';
            $r['patient_display_name'] = $r['P_name'] ?? 'Patient';
            $r['uhid'] = $r['p_code'] ?? '';
            $r['age_display'] = $ageDisplay;
            $appointments[] = $r;
        }

        return $this->response->setJSON([
            'status' => 1,
            'doctor_id' => $docId,
            'date_mode' => $dateMode,
            'counts' => $counts,
            'appointments' => $appointments,
        ]);
    }

    /**
     * GET api/v1/doctor/opd/prescription/(:num)
     */
    public function getOpdPrescription(int $opdId)
    {
        $db = db_connect();

        $opdRow = $db->table('opd_master o')
            ->select('o.*, p.p_code, p.p_fname, p.gender, p.age, p.mphone1, d.p_fname as doc_name')
            ->join('patient_master p', 'p.id = o.p_id', 'left')
            ->join('doctor_master d', 'd.id = o.doc_id', 'left')
            ->where('o.opd_id', $opdId)
            ->get()
            ->getRowArray();

        if (! $opdRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'OPD record not found']);
        }

        $prescRow = $db->table('opd_prescription')
            ->where('opd_id', $opdId)
            ->get()
            ->getRowArray() ?? [];

        $medicines = [];
        if (! empty($prescRow['id'])) {
            $medRows = $db->table('opd_prescrption_prescribed')
                ->where('opd_pre_id', $prescRow['id'])
                ->get()
                ->getResultArray();

            foreach ($medRows as $mr) {
                $medicines[] = [
                    'drug_name' => $mr['med_name'] ?? '',
                    'dosage' => $mr['dosage'] ?? '1 Tab',
                    'frequency' => $mr['dosage_freq_str'] ?? '1-0-1',
                    'duration' => $mr['no_of_days'] ?? '5 Days',
                    'instructions' => $mr['remark'] ?? 'After Food',
                ];
            }
        }

        if (! empty($prescRow)) {
            $prescRow['chief_complaints'] = $prescRow['complaints'] ?? '';
            $prescRow['finding_examinations'] = $prescRow['Finding_Examinations'] ?? '';
            $prescRow['provisional_diagnosis'] = $prescRow['diagnosis'] ?? $prescRow['Provisional_diagnosis'] ?? '';
            $prescRow['bp_systolic'] = $prescRow['bp'] ?? '';
            $prescRow['bp_diastolic'] = $prescRow['diastolic'] ?? '';
            $prescRow['height'] = $prescRow['height'] ?? '';
            $prescRow['rr_min'] = $prescRow['rr_min'] ?? '';
        }

        return $this->response->setJSON([
            'status' => 1,
            'opd' => [
                'opd_id' => (int) $opdRow['opd_id'],
                'opd_code' => $opdRow['opd_code'],
                'patient_name' => $opdRow['p_fname'],
                'uhid' => $opdRow['p_code'],
                'gender' => ((int)$opdRow['gender'] === 1) ? 'Male' : 'Female',
                'age' => $opdRow['age'],
                'doctor_name' => $opdRow['doc_name'],
                'opd_status' => (int) $opdRow['opd_status'],
            ],
            'prescription' => $prescRow,
            'medicines' => $medicines,
        ]);
    }

    /**
     * POST api/v1/doctor/opd/prescription/save/(:num)
     */
    public function saveOpdPrescription(int $opdId)
    {
        $db = db_connect();
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);

        $opdRow = $db->table('opd_master')->where('opd_id', $opdId)->get()->getRowArray();
        if (! $opdRow) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 0, 'message' => 'OPD record not found']);
        }

        $docId = (int) ($post['doctor_id'] ?? $opdRow['doc_id']);
        $pId = (int) ($opdRow['p_id'] ?? 0);

        $imageBase64 = $post['image_base64'] ?? null;
        $imageUrl = '';
        if (! empty($imageBase64)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
                $imgData = substr($imageBase64, strpos($imageBase64, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $binary = base64_decode($imgData);
                if ($binary !== false) {
                    $uploadDir = FCPATH . 'uploads/doctor_notes/';
                    if (! is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    $filename = 'opd_rx_' . $opdId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    file_put_contents($uploadDir . $filename, $binary);
                    $imageUrl = '/uploads/doctor_notes/' . $filename;
                }
            }
        }

        $adviceText = trim((string) ($post['advice'] ?? ''));
        if (! empty($imageUrl)) {
            $adviceText .= ($adviceText ? "\n" : '') . '[Handwritten Rx Photo: ' . $imageUrl . ']';
        }

        $prescData = [
            'opd_id' => $opdId,
            'doc_id' => $docId,
            'p_id' => $pId,
            'date_opd_visit' => date('Y-m-d'),
            'temp' => trim((string) ($post['temp'] ?? '')),
            'pulse' => trim((string) ($post['pulse'] ?? '')),
            'bp' => trim((string) ($post['bp_systolic'] ?? '')),
            'diastolic' => trim((string) ($post['bp_diastolic'] ?? '')),
            'spo2' => trim((string) ($post['spo2'] ?? '')),
            'weight' => trim((string) ($post['weight'] ?? '')),
            'height' => trim((string) ($post['height'] ?? '')),
            'rr_min' => trim((string) ($post['rr_min'] ?? '')),
            'complaints' => trim((string) ($post['chief_complaints'] ?? '')),
            'Finding_Examinations' => trim((string) ($post['finding_examinations'] ?? '')),
            'diagnosis' => trim((string) ($post['provisional_diagnosis'] ?? '')),
            'advice' => $adviceText,
            'investigation' => trim((string) ($post['investigation'] ?? '')),
            'next_visit' => trim((string) ($post['next_visit'] ?? '')),
        ];

        $existing = $db->table('opd_prescription')->where('opd_id', $opdId)->get()->getRowArray();
        $opdPreId = 0;
        if ($existing) {
            $db->table('opd_prescription')->where('opd_id', $opdId)->update($prescData);
            $opdPreId = (int) $existing['id'];
        } else {
            $db->table('opd_prescription')->insert($prescData);
            $opdPreId = (int) $db->insertID();
        }

        $medRaw = $post['medicines'] ?? null;
        $medArray = is_array($medRaw) ? $medRaw : json_decode((string)$medRaw, true);
        if ($opdPreId > 0 && is_array($medArray)) {
            $db->table('opd_prescrption_prescribed')->where('opd_pre_id', $opdPreId)->delete();
            foreach ($medArray as $m) {
                $drugName = trim((string) ($m['drug_name'] ?? ''));
                if ($drugName !== '') {
                    $db->table('opd_prescrption_prescribed')->insert([
                        'opd_pre_id' => $opdPreId,
                        'med_name' => $drugName,
                        'dosage' => trim((string) ($m['dosage'] ?? '1 Tab')),
                        'dosage_freq_str' => trim((string) ($m['frequency'] ?? '1-0-1')),
                        'no_of_days' => trim((string) ($m['duration'] ?? '5 Days')),
                        'remark' => trim((string) ($m['instructions'] ?? 'After Food')),
                    ]);
                }
            }
        }

        // Update OPD status to Visited (2)
        $db->table('opd_master')->where('opd_id', $opdId)->update(['opd_status' => 2]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Prescription saved & OPD Visit completed successfully',
        ]);
    }

    /**
     * GET api/v1/doctor/ipd/list/(:num)
     */
    public function ipdList(int $docId)
    {
        $db = db_connect();
        helper('common');

        $sql = "SELECT i.id as ipd_id, i.ipd_code, i.p_id, i.r_doc_id, i.r_doc_name, i.register_date, i.problem,
                       p.p_fname as fname, p.p_rname as rname, p.p_code as uhid, p.mphone1, p.gender, p.age, p.age_in_month, p.estimate_dob, p.dob,
                       concat('Bed No :', coalesce(b.bed_number, b.bed_code, 'N/A'), ' [', coalesce(w.ward_name, 'General Ward'), ']') as Bed_Desc,
                       b.bed_code, b.bed_number, w.ward_name,
                       coalesce(ipd_doc_list.doc_name, concat('Dr. ', i.r_doc_name)) as assigned_doctors,
                       coalesce(ipd_doc_list.doc_list, cast(i.r_doc_id as char)) as doc_list
                FROM ipd_master i
                JOIN patient_master p ON i.p_id = p.id
                LEFT JOIN (
                    select max(id) as id, ipd_id from bed_assignment_history group by ipd_id
                ) bah_latest ON bah_latest.ipd_id = i.id
                LEFT JOIN bed_assignment_history bah ON bah.id = bah_latest.id
                LEFT JOIN bed_master b ON b.id = bah.bed_id
                LEFT JOIN ward_master w ON w.id = bah.ward_id
                LEFT JOIN (
                    select i.ipd_id,
                        group_concat(distinct concat_ws(' ', 'Dr.', d.p_fname, d.p_mname, d.p_lname)) as doc_name,
                        group_concat(distinct d.id) as doc_list
                    from ipd_master_doc_list i
                    join doctor_master d on i.doc_id = d.id
                    group by i.ipd_id
                ) ipd_doc_list ON i.id = ipd_doc_list.ipd_id
                WHERE i.ipd_status = 0
                ORDER BY i.id DESC";

        $query = $db->query($sql);
        $allPatients = $query->getResultArray();

        $myPatients = [];
        foreach ($allPatients as &$r) {
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

            $r['age_display'] = $ageDisplay;
            $r['gender_label'] = ((int)($r['gender'] ?? 1) === 1) ? 'Male' : 'Female';
            $r['patient_display_name'] = $r['fname'] ?? 'Patient';
            $r['bed_label'] = ! empty($r['Bed_Desc']) ? $r['Bed_Desc'] : (! empty($r['ward_name']) ? ($r['ward_name'] . ' - Bed ' . ($r['bed_number'] ?? '')) : 'General Ward');

            $docListArray = array_filter(explode(',', (string) ($r['doc_list'] ?? '')));
            $isMine = ($docId <= 0)
                || (int)($r['r_doc_id'] ?? 0) === $docId
                || in_array((string)$docId, $docListArray, true);

            $r['is_mine'] = $isMine ? 1 : 0;
            if ($isMine) {
                $myPatients[] = $r;
            }
        }

        return $this->response->setJSON([
            'status' => 1,
            'doctor_id' => $docId,
            'patients' => (count($myPatients) > 0) ? $myPatients : $allPatients,
            'all_patients' => $allPatients,
        ]);
    }

    /**
     * GET api/v1/doctor/ipd/workspace/(:num)
     */
    public function patientWorkspace(int $ipdId)
    {
        $entries = $this->ipdNursingEntryModel->getByIpd($ipdId);

        return $this->response->setJSON([
            'status' => 1,
            'ipd_id' => $ipdId,
            'entries' => $entries,
        ]);
    }

    /**
     * POST api/v1/doctor/ipd/treatment/save/(:num)
     */
    public function saveTreatmentNote(int $ipdId)
    {
        $post = $this->request->getPost() ?: ($this->request->getJSON(true) ?? []);
        $treatmentText = trim((string) ($post['treatment_text'] ?? ''));
        $imageBase64 = $post['image_base64'] ?? null;

        $imageUrl = '';
        if (! empty($imageBase64)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $imageBase64, $type)) {
                $imgData = substr($imageBase64, strpos($imageBase64, ',') + 1);
                $ext = strtolower($type[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $binary = base64_decode($imgData);
                if ($binary !== false) {
                    $uploadDir = FCPATH . 'uploads/doctor_notes/';
                    if (! is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0777, true);
                    }
                    $filename = 'note_ipd_' . $ipdId . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
                    file_put_contents($uploadDir . $filename, $binary);
                    $imageUrl = '/uploads/doctor_notes/' . $filename;
                }
            }
        }

        if ($treatmentText === '' && empty($imageUrl)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Please enter clinical notes or attach a handwritten note photo']);
        }

        $docId = (int) ($post['doctor_id'] ?? 0);
        $doctorName = trim((string) ($post['doctor_name'] ?? 'Doctor'));

        $fullNoteText = '[Doctor Note] ' . $treatmentText;
        if (! empty($imageUrl)) {
            $fullNoteText .= ' [IMAGE_ATTACHMENT:' . $imageUrl . ']';
        }

        $data = [
            'ipd_id' => $ipdId,
            'entry_type' => 'treatment',
            'recorded_at' => date('Y-m-d H:i:s'),
            'treatment_text' => $fullNoteText,
            'general_note' => $imageUrl ? ('Attachment: ' . $imageUrl) : (string) ($post['general_note'] ?? ''),
            'recorded_by' => 'Dr. ' . $doctorName,
            'recorded_by_id' => $docId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->ipdNursingEntryModel->insert($data);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Doctor clinical note / handwritten photo saved successfully',
            'image_url' => $imageUrl,
        ]);
    }
}
