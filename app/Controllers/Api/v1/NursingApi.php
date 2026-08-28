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
        $records = $this->bedMasterModel->getAllWithRelations();
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
}
