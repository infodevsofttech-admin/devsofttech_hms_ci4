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

        $sql = "SELECT o.opd_id, o.opd_code, o.opd_no, o.opd_status, o.apointment_date,
                       o.opd_fee_desc, o.opd_fee_amount, o.payment_status, o.opd_fee_type,
                       coalesce(o.running_opd, 0) as running_opd,
                       p.id as p_id, p.p_code, p.p_fname as P_name, p.gender, p.age, p.dob, p.mphone1,
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
                LEFT JOIN opd_prescription pr ON o.opd_id = pr.opd_id";

        if ($docId > 0) {
            $sql .= " WHERE o.doc_id = " . (int) $docId;
        }

        $sql .= " ORDER BY o.opd_id DESC LIMIT 100";

        $query = $db->query($sql);
        $allRows = $query->getResultArray();

        $appointments = [];
        $counts = [
            'all' => count($allRows),
            'booked' => 0,
            'waiting' => 0,
            'visited' => 0,
            'cancelled' => 0,
        ];

        foreach ($allRows as $r) {
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

            $r['status_label'] = ucfirst($statusText);
            $r['status_key'] = $statusText;
            $r['gender_label'] = ((int)($r['gender'] ?? 1) === 1) ? 'Male' : 'Female';
            $r['patient_display_name'] = $r['P_name'] ?? 'Patient';
            $r['uhid'] = $r['p_code'] ?? '';
            $appointments[] = $r;
        }

        return $this->response->setJSON([
            'status' => 1,
            'doctor_id' => $docId,
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

        $vitals = [
            'temp' => trim((string) ($post['temp'] ?? '')),
            'pulse' => trim((string) ($post['pulse'] ?? '')),
            'bp' => trim((string) ($post['bp_systolic'] ?? '')),
            'diastolic' => trim((string) ($post['bp_diastolic'] ?? '')),
            'spo2' => trim((string) ($post['spo2'] ?? '')),
            'weight' => trim((string) ($post['weight'] ?? '')),
        ];

        $chiefComplaints = trim((string) ($post['chief_complaints'] ?? ''));
        $provisionalDiagnosis = trim((string) ($post['provisional_diagnosis'] ?? ''));
        $advice = trim((string) ($post['advice'] ?? ''));
        $investigation = trim((string) ($post['investigation'] ?? ''));
        $nextVisit = trim((string) ($post['next_visit'] ?? ''));
        $jsonMedicines = is_array($post['medicines'] ?? null) ? json_encode($post['medicines']) : (string) ($post['medicines'] ?? '[]');

        $prescData = array_merge($vitals, [
            'opd_id' => $opdId,
            'doc_id' => $docId,
            'p_id' => $pId,
            'date' => date('Y-m-d'),
            'chief_complaints' => $chiefComplaints,
            'provisional_diagnosis' => $provisionalDiagnosis,
            'advice' => $advice,
            'investigation' => $investigation,
            'next_visit' => $nextVisit,
            'json_medicines' => $jsonMedicines,
        ]);

        $existing = $db->table('opd_prescription')->where('opd_id', $opdId)->get()->getRowArray();
        if ($existing) {
            $db->table('opd_prescription')->where('opd_id', $opdId)->update($prescData);
        } else {
            $db->table('opd_prescription')->insert($prescData);
        }

        // Update OPD status to Visited (2)
        $db->table('opd_master')->where('opd_id', $opdId)->update(['opd_status' => 2]);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Prescription saved & OPD Visit marked completed successfully',
        ]);
    }

    /**
     * GET api/v1/doctor/ipd/list/(:num)
     */
    public function ipdList(int $docId)
    {
        $db = db_connect();
        $builder = $db->table('ipd_master ipd')
            ->select('ipd.*, p.p_fname as fname, p.p_code as uhid, p.mphone1, p.gender, p.age, b.bed_code, b.bed_number, w.ward_name')
            ->join('patient_master p', 'p.id = ipd.p_id', 'left')
            ->join('bed_master b', 'b.id = ipd.bed_id', 'left')
            ->join('ward_master w', 'w.id = b.ward_id', 'left')
            ->where('ipd.status', 'admitted')
            ->orderBy('ipd.id', 'DESC');

        if ($docId > 0 && $db->fieldExists('doc_id', 'ipd_master')) {
            $builder->where('ipd.doc_id', $docId);
        }

        $patients = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'doctor_id' => $docId,
            'patients' => $patients,
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

        if ($treatmentText === '') {
            return $this->response->setStatusCode(422)->setJSON(['status' => 0, 'message' => 'Doctor treatment note / order is required']);
        }

        $docId = (int) ($post['doctor_id'] ?? 0);
        $doctorName = trim((string) ($post['doctor_name'] ?? 'Doctor'));

        $data = [
            'ipd_id' => $ipdId,
            'entry_type' => 'treatment',
            'recorded_at' => date('Y-m-d H:i:s'),
            'treatment_text' => '[Doctor Note] ' . $treatmentText,
            'general_note' => (string) ($post['general_note'] ?? ''),
            'recorded_by' => 'Dr. ' . $doctorName,
            'recorded_by_id' => $docId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->ipdNursingEntryModel->insert($data);

        return $this->response->setJSON([
            'status' => 1,
            'message' => 'Doctor clinical note / treatment saved successfully',
        ]);
    }
}
