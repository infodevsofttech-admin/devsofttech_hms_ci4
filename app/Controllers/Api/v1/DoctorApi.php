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
        $builder = $db->table('opd_master opd')
            ->select('opd.*, p.fname, p.mname, p.lname, p.uhid, p.mphone1, p.gender, p.age')
            ->join('patient_master p', 'p.id = opd.patient_id', 'left')
            ->orderBy('opd.id', 'DESC')
            ->limit(50);

        if ($docId > 0 && $db->fieldExists('doctor_id', 'opd_master')) {
            $builder->where('opd.doctor_id', $docId);
        }

        $records = $builder->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 1,
            'doctor_id' => $docId,
            'appointments' => $records,
        ]);
    }

    /**
     * GET api/v1/doctor/ipd/list/(:num)
     */
    public function ipdList(int $docId)
    {
        $db = db_connect();
        $builder = $db->table('ipd_master ipd')
            ->select('ipd.*, p.fname, p.mname, p.lname, p.uhid, p.mphone1, p.gender, p.age, b.bed_code, b.bed_number, w.ward_name')
            ->join('patient_master p', 'p.id = ipd.patient_id', 'left')
            ->join('bed_master b', 'b.id = ipd.bed_id', 'left')
            ->join('ward_master w', 'w.id = b.ward_id', 'left')
            ->where('ipd.status', 'admitted')
            ->orderBy('ipd.id', 'DESC');

        if ($docId > 0 && $db->fieldExists('doctor_id', 'ipd_master')) {
            $builder->where('ipd.doctor_id', $docId);
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
