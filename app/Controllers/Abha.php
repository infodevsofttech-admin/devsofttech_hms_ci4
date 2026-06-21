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

        $profile = $this->pickGatewayAbhaProfile($payload);

        $abhaNum          = (string) ($profile['ABHANumber'] ?? $profile['abha_id'] ?? $payload['ABHANumber'] ?? $payload['abha_id'] ?? '');
        $abhaAddress      = (string) ($profile['preferredAbhaAddress'] ?? $profile['abha_address'] ?? $payload['preferredAbhaAddress'] ?? $payload['abha_address'] ?? '');
        $name             = (string) ($profile['name'] ?? $profile['fullName'] ?? $profile['full_name'] ?? $payload['name'] ?? $payload['full_name'] ?? '');
        $photo            = (string) ($profile['profilePhoto'] ?? $profile['profile_photo'] ?? $payload['profilePhoto'] ?? $payload['profile_photo'] ?? '');
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? '');
        $profileGender    = (string) ($profile['gender'] ?? $payload['gender'] ?? '');
        $profileDob       = (string) ($profile['dob'] ?? $profile['date_of_birth'] ?? $payload['dob'] ?? $payload['date_of_birth'] ?? '');
        $verifiedStatus   = (string) ($profile['verifiedStatus'] ?? $payload['verifiedStatus'] ?? '');
        $verificationType = (string) ($profile['verificationType'] ?? $payload['verificationType'] ?? '');
        $kycVerified      = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        $mobileVerified   = $profile['mobileVerified'] ?? $payload['mobileVerified'] ?? null;

        $patientInfo = $this->autoCreateOrFindPatient(
            $abhaNum,
            $name,
            $mobile,
            $profileGender,
            $profileDob,
            [
                'abha_address' => $abhaAddress,
                'profile_photo' => $photo,
                'verified_status' => $verifiedStatus,
                'verification_type' => $verificationType,
                'kyc_verified' => $kycVerified,
                'mobile_verified' => $mobileVerified,
            ]
        );

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

        $profile = $this->pickGatewayAbhaProfile($payload);

        $abhaNum          = (string) ($profile['ABHANumber'] ?? $profile['abha_id'] ?? $payload['ABHANumber'] ?? $payload['abha_id'] ?? '');
        $abhaAddress      = (string) ($profile['preferredAbhaAddress'] ?? $profile['abha_address'] ?? $payload['preferredAbhaAddress'] ?? $payload['abha_address'] ?? '');
        $name             = (string) ($profile['name'] ?? $profile['fullName'] ?? $profile['full_name'] ?? $payload['name'] ?? $payload['full_name'] ?? '');
        $photo            = (string) ($profile['profilePhoto'] ?? $profile['profile_photo'] ?? $payload['profilePhoto'] ?? $payload['profile_photo'] ?? '');
        $gender           = (string) ($profile['gender'] ?? $payload['gender'] ?? '');
        $dob              = (string) ($profile['dob'] ?? $profile['date_of_birth'] ?? $payload['dob'] ?? $payload['date_of_birth'] ?? '');
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? '');
        $verifiedStatus   = (string) ($profile['verifiedStatus'] ?? $payload['verifiedStatus'] ?? '');
        $verificationType = (string) ($profile['verificationType'] ?? $payload['verificationType'] ?? '');
        $kycVerified      = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        $mobileVerified   = $profile['mobileVerified'] ?? $payload['mobileVerified'] ?? null;

        $patientInfo = $this->autoCreateOrFindPatient(
            $abhaNum,
            $name,
            $mobile,
            $gender,
            $dob,
            [
                'abha_address' => $abhaAddress,
                'profile_photo' => $photo,
                'verified_status' => $verifiedStatus,
                'verification_type' => $verificationType,
                'kyc_verified' => $kycVerified,
                'mobile_verified' => $mobileVerified,
            ]
        );

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
        string $dob,
        array $abhaMeta = []
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

        $abhaNumClean = preg_replace('/\D/', '', $abhaNum); // strip dashes -> 14 digits
        $abhaAddress  = trim((string) ($abhaMeta['abha_address'] ?? ''));

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

            $this->syncAbhaMetaToPatient($patientId, $abhaMeta, $abhaNumClean, $abhaAddress, $fields);
            return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => false];
        }

        // 2b. Safe repair path for orphan placeholder rows created during prior failed mapping.
        // Reuse only when there is exactly one unambiguous candidate with no clinical linkage.
        $placeholder = $this->findRepairablePlaceholderPatient($db, $fields);
        if ($placeholder !== null) {
            $patientId = (int) ($placeholder['id'] ?? 0);
            if ($patientId > 0) {
                $repairData = [
                    'p_fname'      => strtoupper($name !== '' ? $name : 'ABHA PATIENT'),
                    'mphone1'      => $mobile,
                    'gender'       => $this->toPatientGenderValue($gender),
                    'estimate_dob' => 1,
                ];

                $dobDb = $this->normalizeDobToDb($dob);
                if ($dobDb !== '') {
                    $repairData['dob'] = $dobDb;
                    $repairData['estimate_dob'] = 0;
                }

                if ($abhaField && $abhaNumClean !== '') {
                    $repairData[$abhaField] = $abhaNumClean;
                }
                if ($abhaAddress !== '' && in_array('abha_address', $fields, true)) {
                    $repairData['abha_address'] = $abhaAddress;
                }

                $this->applyAbhaMetaColumns($repairData, $abhaMeta, $fields);
                if (($abhaNumClean !== '' || $abhaAddress !== '') && in_array('abdm_linked_at', $fields, true)) {
                    $repairData['abdm_linked_at'] = date('Y-m-d H:i:s');
                }

                $db->table('patient_master')->where('id', $patientId)->update($repairData);

                $row   = $db->table('patient_master')->select('p_code')->where('id', $patientId)->get()->getRowArray();
                $pCode = (string) ($row['p_code'] ?? '');

                $this->syncAbhaMetaToPatient($patientId, $abhaMeta, $abhaNumClean, $abhaAddress, $fields);

                return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => false];
            }
        }

        // 3. Create new patient row from ABHA profile data
        $genderDb  = $this->toPatientGenderValue($gender);

        // Convert DOB to MySQL YYYY-MM-DD
        $dobDb = $this->normalizeDobToDb($dob);

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
        if ($abhaAddress !== '' && in_array('abha_address', $fields, true)) {
            $insertData['abha_address'] = $abhaAddress;
        }

        $this->applyAbhaMetaColumns($insertData, $abhaMeta, $fields);
        if (($abhaNumClean !== '' || $abhaAddress !== '') && in_array('abdm_linked_at', $fields, true)) {
            $insertData['abdm_linked_at'] = date('Y-m-d H:i:s');
        }

        $patientModel = new \App\Models\PatientModel();
        $patientId    = $patientModel->insertPatient($insertData);
        $pCode        = '';
        if ($patientId > 0) {
            $row   = $db->table('patient_master')->select('p_code')->where('id', $patientId)->get()->getRowArray();
            $pCode = (string) ($row['p_code'] ?? '');
        }

        if ($patientId > 0) {
            $this->syncAbhaMetaToPatient($patientId, $abhaMeta, $abhaNumClean, $abhaAddress, $fields);
        }

        return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => true];
    }

    private function syncAbhaMetaToPatient(int $patientId, array $abhaMeta, string $abhaNumClean, string $abhaAddress, array $fields): void
    {
        if ($patientId <= 0) {
            return;
        }

        $db = \Config\Database::connect();

        $updates = [];
        if ($abhaAddress !== '' && in_array('abha_address', $fields, true)) {
            $updates['abha_address'] = $abhaAddress;
        }

        $this->applyAbhaMetaColumns($updates, $abhaMeta, $fields);

        if (($abhaNumClean !== '' || $abhaAddress !== '') && in_array('abdm_linked_at', $fields, true)) {
            $updates['abdm_linked_at'] = date('Y-m-d H:i:s');
        }

        if ($updates !== []) {
            $db->table('patient_master')->where('id', $patientId)->update($updates);
        }
    }

    private function applyAbhaMetaColumns(array &$target, array $abhaMeta, array $fields): void
    {
        $verifiedStatus = trim((string) ($abhaMeta['verified_status'] ?? ''));
        if ($verifiedStatus !== '' && in_array('abha_verified_status', $fields, true)) {
            $target['abha_verified_status'] = strtoupper($verifiedStatus);
        }

        $verificationType = trim((string) ($abhaMeta['verification_type'] ?? ''));
        if ($verificationType !== '' && in_array('abha_verification_type', $fields, true)) {
            $target['abha_verification_type'] = strtoupper($verificationType);
        }

        if (array_key_exists('kyc_verified', $abhaMeta) && in_array('abha_kyc_verified', $fields, true)) {
            $target['abha_kyc_verified'] = $this->toDbBool($abhaMeta['kyc_verified']);
        }

        if (array_key_exists('mobile_verified', $abhaMeta) && in_array('abha_mobile_verified', $fields, true)) {
            $target['abha_mobile_verified'] = $this->toDbBool($abhaMeta['mobile_verified']);
        }

        $profilePhoto = trim((string) ($abhaMeta['profile_photo'] ?? ''));
        if ($profilePhoto !== '' && in_array('abha_profile_photo_base64', $fields, true)) {
            $target['abha_profile_photo_base64'] = $profilePhoto;
        }
    }

    private function toDbBool($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $v = strtolower(trim((string) $value));
        if ($v === '1' || $v === 'true' || $v === 'yes' || $v === 'y') {
            return 1;
        }

        return 0;
    }

    private function toPatientGenderValue(string $gender): int
    {
        $genderAbs = strtoupper(trim($gender));
        if ($genderAbs === 'F' || $genderAbs === '2' || $genderAbs === 'FEMALE') {
            return 2;
        }
        if ($genderAbs === 'O' || $genderAbs === '3' || $genderAbs === 'OTHER') {
            return 3;
        }

        return 1;
    }

    private function normalizeDobToDb(string $dob): string
    {
        $dob = trim($dob);
        if ($dob === '') {
            return '';
        }

        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dob, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            return $dob;
        }

        return '';
    }

    /**
     * Find one safe placeholder candidate to repair.
     * Conditions:
     * - name is ABHA PATIENT
     * - ABHA id and address are empty
     * - no OPD/invoice linkage
     * - inserted recently (last 24h)
     * - exactly one candidate (to avoid accidental overwrite)
     *
     * @param array<int, string> $fields
     * @return array<string, mixed>|null
     */
    private function findRepairablePlaceholderPatient($db, array $fields): ?array
    {
        if (! $db->tableExists('patient_master')) {
            return null;
        }

        $abhaField = null;
        foreach (['abha_id', 'abha_no', 'abha'] as $f) {
            if (in_array($f, $fields, true)) {
                $abhaField = $f;
                break;
            }
        }

        $builder = $db->table('patient_master p')
            ->select('p.id,p.p_code,p.insert_date')
            ->where('UPPER(TRIM(COALESCE(p.p_fname, "")))', 'ABHA PATIENT')
            ->where('(p.mphone1 IS NULL OR TRIM(p.mphone1) = "")', null, false)
            ->where('p.insert_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)', null, false);

        if ($abhaField !== null) {
            $builder->where('(p.' . $abhaField . ' IS NULL OR TRIM(p.' . $abhaField . ') = "")', null, false);
        }
        if (in_array('abha_address', $fields, true)) {
            $builder->where('(p.abha_address IS NULL OR TRIM(p.abha_address) = "")', null, false);
        }

        if ($db->tableExists('opd_master')) {
            $builder->where('NOT EXISTS (SELECT 1 FROM opd_master o WHERE o.p_id = p.id)', null, false);
        }
        if ($db->tableExists('invoice_master')) {
            $builder->where('NOT EXISTS (SELECT 1 FROM invoice_master i WHERE i.attach_type = 0 AND i.attach_id = p.id)', null, false);
        }

        $rows = $builder->orderBy('p.id', 'DESC')->limit(2)->get()->getResultArray();
        if (count($rows) !== 1) {
            return null;
        }

        return $rows[0];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function pickGatewayAbhaProfile(array $payload): array
    {
        $candidates = [
            $payload['gateway_patient'] ?? null,
            $payload['ABHAProfile'] ?? null,
            $payload['accounts'][0] ?? null,
            $payload['profile'] ?? null,
            $payload,
        ];

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && $this->looksLikeGatewayAbhaProfile($candidate)) {
                return $candidate;
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function looksLikeGatewayAbhaProfile(array $profile): bool
    {
        foreach (['ABHANumber', 'abha_id', 'preferredAbhaAddress', 'abha_address', 'name', 'fullName', 'full_name', 'gender', 'dob', 'date_of_birth', 'profilePhoto', 'profile_photo'] as $key) {
            if (array_key_exists($key, $profile) && trim((string) $profile[$key]) !== '') {
                return true;
            }
        }

        return false;
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
