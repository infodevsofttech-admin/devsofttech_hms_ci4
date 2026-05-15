<?php

namespace App\Controllers;

use App\Libraries\Abdm\AbdmConnectorFactory;

/**
 * Abha Controller
 *
 * Handles ABHA number creation wizard endpoints called from billing/patient ABHA Create tab.
 * All methods proxy calls to the ABDM gateway via AbdmConnectorFactory.
 */
class Abha extends BaseController
{
    // -------------------------------------------------------------------------
    // Step 1 — Initiate: send Aadhaar OTP
    // POST abha/create/initiate
    // -------------------------------------------------------------------------
    public function initiate()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $aadhaar = preg_replace('/\D/', '', trim((string) ($this->request->getPost('aadhaar') ?? '')));
        $authType = trim((string) ($this->request->getPost('auth_type') ?? 'aadhaar_otp'));

        if (strlen($aadhaar) !== 12) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 12-digit Aadhaar number is required']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaAadhaarGenerateOtp(['aadhaar' => $aadhaar]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        // Normalise for the wizard: expose txn_id at top level
        if (! empty($result['ok']) && $result['ok'] == 1) {
            $txnId = $result['txn_id'] ?? $result['data']['txnId'] ?? $result['data']['txn_id'] ?? null;
            return $this->response->setJSON(['ok' => 1, 'txn_id' => $txnId]);
        }

        return $this->response->setJSON([
            'ok'         => 0,
            'error_text' => $result['error_text'] ?? $result['message']
                            ?? $result['data']['message'] ?? 'Failed to send OTP',
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 2 — Verify Aadhaar OTP
    // POST abha/create/verify_otp
    // Response includes skip_mobile=true and abha_number if ABHA already linked.
    // -------------------------------------------------------------------------
    public function verifyOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $txnId  = trim((string) ($this->request->getPost('txn_id') ?? $this->request->getPost('txnId') ?? ''));
        $otp    = trim((string) ($this->request->getPost('otp') ?? ''));
        $mobile = trim((string) ($this->request->getPost('mobile') ?? ''));

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txn_id and otp are required']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaAadhaarVerifyOtp(['txnId' => $txnId, 'otp' => $otp, 'mobile' => $mobile]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || $result['ok'] != 1) {
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $result['error_text'] ?? $result['message']
                                ?? $result['data']['message'] ?? 'OTP verification failed',
            ]);
        }
        $payload  = $result['data'] ?? $result;
        $newTxnId = $payload['txnId'] ?? $payload['txn_id'] ?? $txnId;

        // aadhaar/verify-otp returns ABHAProfile when ABHA is created/found
        $profile = $payload['ABHAProfile'] ?? [];
        if (empty($profile) && ! empty($payload['accounts'][0])) {
            $profile = $payload['accounts'][0]; // legacy fallback
        }

        $abhaNum      = (string) ($profile['ABHANumber'] ?? $payload['ABHANumber'] ?? '');
        $name         = (string) ($profile['name'] ?? $profile['fullName'] ?? '');
        $photo        = (string) ($profile['profilePhoto'] ?? '');
        $mobile       = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? '');
        $profileGender = (string) ($profile['gender'] ?? '');
        $profileDob    = (string) ($profile['dob'] ?? '');

        $patientInfo = $this->autoCreateOrFindPatient($abhaNum, $name, $mobile, $profileGender, $profileDob);

        return $this->response->setJSON([
            'ok'             => 1,
            'txn_id'         => $newTxnId,
            'skip_mobile'    => true,
            'abha_number'    => $abhaNum,
            'name'           => $name,
            'photo'          => $photo,
            'mobile'         => $mobile,
            'patient_id'     => $patientInfo['patient_id'],
            'p_code'         => $patientInfo['p_code'],
            'is_new_patient' => $patientInfo['is_new'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 3a — Send mobile OTP
    // POST abha/create/communication
    // -------------------------------------------------------------------------
    public function communication()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $mobile = preg_replace('/\D/', '', trim((string) ($this->request->getPost('mobile') ?? '')));
        $txnId  = trim((string) ($this->request->getPost('txn_id') ?? $this->request->getPost('txnId') ?? ''));

        if (strlen($mobile) !== 10) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 10-digit mobile number is required']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaMobileGenerateOtp(['mobile' => $mobile, 'txnId' => $txnId]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (! empty($result['ok']) && $result['ok'] == 1) {
            $newTxnId = $result['txn_id'] ?? $result['data']['txnId'] ?? $result['data']['txn_id'] ?? $txnId;
            return $this->response->setJSON(['ok' => 1, 'txn_id' => $newTxnId]);
        }

        return $this->response->setJSON([
            'ok'         => 0,
            'error_text' => $result['error_text'] ?? $result['message']
                            ?? $result['data']['message'] ?? 'Failed to send mobile OTP',
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 3b — Verify mobile OTP
    // POST abha/create/verify_comm_otp
    // -------------------------------------------------------------------------
    public function verifyCommOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $txnId = trim((string) ($this->request->getPost('txn_id') ?? $this->request->getPost('txnId') ?? ''));
        $otp   = trim((string) ($this->request->getPost('otp') ?? ''));

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txn_id and otp are required']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaMobileVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || $result['ok'] != 1) {
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $result['error_text'] ?? $result['message']
                                ?? $result['data']['message'] ?? 'Mobile OTP verification failed',
            ]);
        }

        $payload = $result['data'] ?? $result;

        // mobile/verify-otp returns data.profile (new format) or data.ABHAProfile / accounts[0] (legacy)
        $profile = $payload['profile'] ?? $payload['ABHAProfile'] ?? [];
        if (empty($profile) && ! empty($payload['accounts'][0])) {
            $profile = $payload['accounts'][0];
        }

        $abhaNum = (string) ($profile['ABHANumber'] ?? $payload['ABHANumber'] ?? '');
        $name    = (string) ($profile['name'] ?? $profile['fullName'] ?? $payload['name'] ?? '');
        $photo   = (string) ($profile['profilePhoto'] ?? $payload['profilePhoto'] ?? '');
        $gender  = (string) ($profile['gender'] ?? $payload['gender'] ?? '');
        $dob     = (string) ($profile['dob'] ?? $payload['dob'] ?? '');
        $mobile  = (string) ($profile['mobile'] ?? $payload['mobile'] ?? '');

        $patientInfo = $this->autoCreateOrFindPatient($abhaNum, $name, $mobile, $gender, $dob);

        return $this->response->setJSON([
            'ok'             => 1,
            'abha_number'    => $abhaNum,
            'name'           => $name,
            'photo'          => $photo,
            'gender'         => $gender,
            'dob'            => $dob,
            'patient_id'     => $patientInfo['patient_id'],
            'p_code'         => $patientInfo['p_code'],
            'is_new_patient' => $patientInfo['is_new'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 4 — Save/finalise ABHA address
    // POST abha/create/address
    // Gateway has no address-assignment endpoint; return ok=1 as confirmation.
    // -------------------------------------------------------------------------
    public function address()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        // Address assignment is noted for the staff. No gateway call available.
        return $this->response->setJSON(['ok' => 1, 'message' => 'ABHA created successfully.']);
    }

    // -------------------------------------------------------------------------
    // ABHA Card view — GET abha/card/{abha_number}
    // Renders a printable card page for a patient whose ABHA is stored in HMS.
    // -------------------------------------------------------------------------
    public function card(string $abhaNumber = '')
    {
        $abhaNumClean = preg_replace('/\D/', '', $abhaNumber);

        if (strlen($abhaNumClean) !== 14) {
            return $this->response->setStatusCode(400)
                ->setBody('<h3 style="font-family:sans-serif;color:red;">Invalid ABHA number.</h3>');
        }

        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];

        $abhaField = null;
        foreach (['abha_id', 'abha_no', 'abha'] as $f) {
            if (in_array($f, $fields, true)) { $abhaField = $f; break; }
        }
        if ($abhaField === null && in_array('abha_address', $fields, true)) {
            $abhaField = 'abha_address';
        }

        $patient = null;
        if ($abhaField) {
            $patient = $db->table('patient_master')
                ->where($abhaField, $abhaNumClean)->get()->getRowArray();
            if (! $patient) {
                $patient = $db->table('patient_master')
                    ->where($abhaField, $abhaNumber)->get()->getRowArray();
            }
        }

        if (! $patient) {
            return $this->response->setStatusCode(404)
                ->setBody('<h3 style="font-family:sans-serif;color:red;">Patient with ABHA number ' . esc($abhaNumber) . ' not found.</h3>');
        }

        $abhaDisp = preg_replace('/^(\d{2})(\d{4})(\d{4})(\d{4})$/', '$1-$2-$3-$4', $abhaNumClean) ?: $abhaNumber;
        $genderRaw = (string) ($patient['gender'] ?? '');
        $genderLabel = $genderRaw === '1' ? 'Male' : ($genderRaw === '2' ? 'Female' : $genderRaw);
        $dobRaw = (string) ($patient['dob'] ?? '');
        $dobLabel = '';
        if ($dobRaw && $dobRaw !== '0000-00-00') {
            try { $dobLabel = (new \DateTime($dobRaw))->format('d M Y'); } catch (\Exception $e) { $dobLabel = $dobRaw; }
        }

        return view('abha/card', [
            'patient'    => $patient,
            'abha_num'   => $abhaDisp,
            'gender'     => $genderLabel,
            'dob'        => $dobLabel,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helper — find or auto-create patient from ABHA profile data
    // -------------------------------------------------------------------------
    private function autoCreateOrFindPatient(
        string $abhaNum,
        string $name,
        string $mobile,
        string $gender,
        string $dob
    ): array {
        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];

        // Detect ABHA column name
        $abhaField = null;
        foreach (['abha_id', 'abha_no', 'abha'] as $f) {
            if (in_array($f, $fields, true)) { $abhaField = $f; break; }
        }
        if ($abhaField === null && in_array('abha_address', $fields, true)) {
            $abhaField = 'abha_address';
        }

        $abhaNumClean = preg_replace('/\D/', '', $abhaNum); // strip dashes → 14 digits

        // 1. Search by ABHA number
        $existing = null;
        if ($abhaField && $abhaNumClean !== '') {
            $existing = $db->table('patient_master')
                ->where($abhaField, $abhaNumClean)->get()->getRowArray();
            if (! $existing && $abhaNum !== $abhaNumClean) {
                $existing = $db->table('patient_master')
                    ->where($abhaField, $abhaNum)->get()->getRowArray();
            }
        }

        // 2. Fallback: search by mobile
        if (! $existing && $mobile !== '') {
            $existing = $db->table('patient_master')
                ->where('mphone1', $mobile)->get()->getRowArray();
        }

        if ($existing) {
            $patientId = (int) ($existing['id'] ?? 0);
            $pCode     = (string) ($existing['p_code'] ?? '');
            // Backfill ABHA field if it was empty
            if ($abhaField && empty($existing[$abhaField]) && $abhaNumClean !== '') {
                $db->table('patient_master')
                    ->where('id', $patientId)
                    ->update([$abhaField => $abhaNumClean]);
            }
            return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => false];
        }

        // 3. Create new patient row from ABHA profile data
        $genderDb  = 1; // default Male
        $genderAbs = strtoupper($gender);
        if ($genderAbs === 'F' || $genderAbs === '2') { $genderDb = 2; }

        // Convert DOB to MySQL YYYY-MM-DD
        $dobDb = '';
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dob, $m)) {
            $dobDb = $m[3] . '-' . $m[2] . '-' . $m[1]; // DD-MM-YYYY → YYYY-MM-DD
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $dobDb = $dob;
        }

        $insertData = [
            'p_fname'      => strtoupper($name !== '' ? $name : 'ABHA PATIENT'),
            'mphone1'      => $mobile,
            'gender'       => $genderDb,
            'blood_group'  => 'Not Define',
            'estimate_dob' => $dobDb !== '' ? 0 : 1,
        ];
        if ($dobDb !== '') {
            $insertData['dob'] = $dobDb;
        } else {
            $insertData['age']          = 0;
            $insertData['age_in_month'] = 0;
        }
        if ($abhaField && $abhaNumClean !== '') {
            $insertData[$abhaField] = $abhaNumClean;
        }

        $patientModel = new \App\Models\PatientModel();
        $patientId    = $patientModel->insertPatient($insertData);
        $pCode        = '';
        if ($patientId > 0) {
            $row   = $db->table('patient_master')->select('p_code')->where('id', $patientId)->get()->getRowArray();
            $pCode = (string) ($row['p_code'] ?? '');
        }

        return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => true];
    }

    // -------------------------------------------------------------------------
    // Validate ABHA number or address
    // POST abha/register/validate
    // -------------------------------------------------------------------------
    public function validateAbha()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $input = trim((string) ($this->request->getPost('abha_id') ?? $this->request->getPost('abha_address') ?? ''));
        if ($input === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID or address required']);
        }

        $isAddress = str_contains($input, '@');
        $payload   = $isAddress
            ? ['abha_address' => $input]
            : ['abha_id' => preg_replace('/\D/', '', $input)];

        try {
            $result = AbdmConnectorFactory::make()->validateAbha('', $payload);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || $result['ok'] != 1) {
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $result['error_text'] ?? $result['message']
                                ?? $result['data']['message'] ?? 'ABHA validation failed',
            ]);
        }

        return $this->response->setJSON(['ok' => 1, 'status' => (string) ($result['data']['status'] ?? 'UNKNOWN')]);
    }
}
