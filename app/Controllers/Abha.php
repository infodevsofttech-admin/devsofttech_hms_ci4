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
            // Cache the Aadhaar number against this txn_id so verifyOtp() can use it
            // later for local-patient name/age/gender/aadhaar matching (never sent to browser).
            if ($txnId) {
                session()->set('abha_aadhaar_txn_' . $txnId, $aadhaar);
            }
            return $this->response->setJSON(['ok' => 1, 'txn_id' => $txnId]);
        }

        return $this->response->setJSON([
            'ok'         => 0,
            'error_text' => $this->extractBridgeErrorText($result, 'Failed to send OTP'),
            'request_id' => (string) ($result['request_id'] ?? ''),
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
        $requestMobile = trim((string) ($this->request->getPost('mobile') ?? ''));

        if ($txnId === '' || $otp === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'txn_id and otp are required']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaAadhaarVerifyOtp(['txnId' => $txnId, 'otp' => $otp, 'mobile' => $requestMobile]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || $result['ok'] != 1) {
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $this->extractBridgeErrorText($result, 'OTP verification failed'),
                'request_id' => (string) ($result['request_id'] ?? ''),
            ]);
        }
        $payload  = $result['data'] ?? $result;
        $newTxnId = $payload['txnId'] ?? $payload['txn_id'] ?? $txnId;

        $profile = $this->pickGatewayAbhaProfile($payload);

        $abhaNum          = (string) ($profile['ABHANumber'] ?? $profile['abha_id'] ?? $payload['ABHANumber'] ?? $payload['abha_id'] ?? '');
        $abhaAddress      = (string) (
            ($profile['preferredAddress'] ?? '')
            ?: ($profile['preferredAbhaAddress'] ?? '')
            ?: ($profile['abha_address'] ?? '')
            ?: ($payload['preferredAddress'] ?? '')
            ?: ($payload['preferredAbhaAddress'] ?? '')
            ?: ($payload['abha_address'] ?? '')
            ?: ''
        );
        $name             = $this->extractAbhaProfileName($profile, $payload);
        $photo            = (string) ($profile['profilePhoto'] ?? $profile['profile_photo'] ?? $payload['profilePhoto'] ?? $payload['profile_photo'] ?? '');
        // Bridge/gateway verify-otp responses don't always echo the mobile back;
        // fall back to the mobile the user actually typed and OTP-verified.
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? $requestMobile ?? '');
        $profileGender    = (string) ($profile['gender'] ?? $payload['gender'] ?? '');
        $profileDob       = (string) ($profile['dob'] ?? $profile['date_of_birth'] ?? $payload['dob'] ?? $payload['date_of_birth'] ?? '');
        $verifiedStatus   = (string) (($payload['gateway_abha_profile']['status'] ?? '') ?: ($profile['verifiedStatus'] ?? '') ?: ($profile['status'] ?? '') ?: ($payload['verifiedStatus'] ?? '') ?: ($payload['status'] ?? ''));
        $verificationType = (string) (($payload['gateway_abha_profile']['abha_type'] ?? '') ?: ($profile['verificationType'] ?? '') ?: ($profile['abhaType'] ?? '') ?: ($payload['verificationType'] ?? '') ?: ($payload['abhaType'] ?? ''));
        $kycVerified      = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        $mobileVerified   = $profile['mobileVerified'] ?? $payload['mobileVerified'] ?? null;
        $address          = (string) (($profile['address'] ?? '') ?: ($profile['address_line'] ?? '') ?: ($payload['address'] ?? '') ?: ($payload['address_line'] ?? '') ?: ($payload['gateway_abha_profile']['address'] ?? '') ?: ($payload['gateway_patient']['address_line'] ?? ''));
        $zip              = (string) (($profile['pinCode'] ?? '') ?: ($profile['pin_code'] ?? '') ?: ($payload['pinCode'] ?? '') ?: ($payload['pin_code'] ?? '') ?: ($payload['gateway_abha_profile']['pin_code'] ?? '') ?: ($payload['gateway_patient']['pincode'] ?? ''));
        $stateName        = (string) (($profile['stateName'] ?? '') ?: ($profile['state_name'] ?? '') ?: ($payload['stateName'] ?? '') ?: ($payload['state_name'] ?? '') ?: ($payload['gateway_abha_profile']['state_name'] ?? '') ?: ($payload['gateway_patient']['state_name'] ?? ''));
        $districtName     = (string) (($profile['districtName'] ?? '') ?: ($profile['district_name'] ?? '') ?: ($payload['districtName'] ?? '') ?: ($payload['district_name'] ?? '') ?: ($payload['gateway_abha_profile']['district_name'] ?? '') ?: ($payload['gateway_patient']['district'] ?? ''));
        $email            = (string) (($profile['email'] ?? '') ?: ($payload['email'] ?? '') ?: ($payload['gateway_abha_profile']['email'] ?? '') ?: ($payload['gateway_patient']['email'] ?? ''));

        $abhaMeta = [
            'abha_address' => $abhaAddress,
            'profile_photo' => $photo,
            'verified_status' => $verifiedStatus,
            'verification_type' => $verificationType,
            'kyc_verified' => $kycVerified,
            'mobile_verified' => $mobileVerified,
            'address' => $address,
            'district' => $districtName,
            'state' => $stateName,
            'zip' => $zip,
            'email' => $email,
        ];

        // Aadhaar cached against this txn_id in initiate(), used only for local
        // duplicate-patient matching below (never persisted/echoed to the browser).
        $aadhaarForMatch = trim((string) (session()->get('abha_aadhaar_txn_' . $txnId) ?? ''));
        session()->remove('abha_aadhaar_txn_' . $txnId);

        $patientInfo = $this->tryAutoLinkByDirectMatch($abhaNum, $name, $mobile, $profileGender, $profileDob, $abhaMeta);

        $responseBase = [
            'ok'                => 1,
            'txn_id'            => $newTxnId,
            'skip_mobile'       => true,
            'abha_number'       => $abhaNum,
            'name'              => $name,
            'photo'             => $photo,
            'mobile'            => $mobile,
            'gender'            => $profileGender,
            'dob'               => $profileDob,
            'abha_address'      => $abhaAddress,
            'verified_status'   => $verifiedStatus,
            'verification_type' => $verificationType,
            'kyc_verified'      => $kycVerified,
            'mobile_verified'   => $mobileVerified,
            'address'           => $address,
            'district'          => $districtName,
            'state'             => $stateName,
            'zip'               => $zip,
            'email'             => $email,
        ];

        if ($patientInfo !== null) {
            return $this->response->setJSON($responseBase + [
                'need_confirmation' => false,
                'patient_id'     => $patientInfo['patient_id'],
                'p_code'         => $patientInfo['p_code'],
                'is_new_patient' => $patientInfo['is_new'],
            ]);
        }

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveAbhaFieldName($fields);
        $abhaNumClean = preg_replace('/\D/', '', $abhaNum);
        $candidates = $this->findMatchingCandidates($db, $fields, $name, $mobile, $profileGender, $profileDob, $aadhaarForMatch, $abhaField, $abhaNumClean);

        return $this->response->setJSON($responseBase + [
            'need_confirmation' => true,
            'patient_id'        => 0,
            'p_code'            => '',
            'is_new_patient'    => null,
            'candidates'        => $candidates,
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
            'error_text' => $this->extractBridgeErrorText($result, 'Failed to send mobile OTP'),
            'request_id' => (string) ($result['request_id'] ?? ''),
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
        // The mobile itself isn't part of the OTP-verify payload (it's tied to txn_id
        // on the bridge side), but the caller may resend it so we can persist it even
        // if the gateway response doesn't echo it back.
        $requestMobile = trim((string) ($this->request->getPost('mobile') ?? ''));

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
                'error_text' => $this->extractBridgeErrorText($result, 'Mobile OTP verification failed'),
                'request_id' => (string) ($result['request_id'] ?? ''),
            ]);
        }

        $payload = $result['data'] ?? $result;

        $profile = $this->pickGatewayAbhaProfile($payload);

        $abhaNum          = (string) ($profile['ABHANumber'] ?? $profile['abha_id'] ?? $payload['ABHANumber'] ?? $payload['abha_id'] ?? '');
        $abhaAddress      = (string) (
            ($profile['preferredAddress'] ?? '')
            ?: ($profile['preferredAbhaAddress'] ?? '')
            ?: ($profile['abha_address'] ?? '')
            ?: ($payload['preferredAddress'] ?? '')
            ?: ($payload['preferredAbhaAddress'] ?? '')
            ?: ($payload['abha_address'] ?? '')
            ?: ''
        );
        $name             = $this->extractAbhaProfileName($profile, $payload);
        $photo            = (string) ($profile['profilePhoto'] ?? $profile['profile_photo'] ?? $payload['profilePhoto'] ?? $payload['profile_photo'] ?? '');
        $gender           = (string) ($profile['gender'] ?? $payload['gender'] ?? '');
        $dob              = (string) ($profile['dob'] ?? $profile['date_of_birth'] ?? $payload['dob'] ?? $payload['date_of_birth'] ?? '');
        // Bridge/gateway verify-otp responses don't always echo the mobile back;
        // fall back to the mobile the user actually typed and OTP-verified.
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? $requestMobile ?? '');
        $verifiedStatus   = (string) (($payload['gateway_abha_profile']['status'] ?? '') ?: ($profile['verifiedStatus'] ?? '') ?: ($profile['status'] ?? '') ?: ($payload['verifiedStatus'] ?? '') ?: ($payload['status'] ?? ''));
        $verificationType = (string) (($payload['gateway_abha_profile']['abha_type'] ?? '') ?: ($profile['verificationType'] ?? '') ?: ($profile['abhaType'] ?? '') ?: ($payload['verificationType'] ?? '') ?: ($payload['abhaType'] ?? ''));
        $kycVerified      = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        $mobileVerified   = $profile['mobileVerified'] ?? $payload['mobileVerified'] ?? null;
        $address          = (string) (($profile['address'] ?? '') ?: ($profile['address_line'] ?? '') ?: ($payload['address'] ?? '') ?: ($payload['address_line'] ?? '') ?: ($payload['gateway_abha_profile']['address'] ?? '') ?: ($payload['gateway_patient']['address_line'] ?? ''));
        $zip              = (string) (($profile['pinCode'] ?? '') ?: ($profile['pin_code'] ?? '') ?: ($payload['pinCode'] ?? '') ?: ($payload['pin_code'] ?? '') ?: ($payload['gateway_abha_profile']['pin_code'] ?? '') ?: ($payload['gateway_patient']['pincode'] ?? ''));
        $stateName        = (string) (($profile['stateName'] ?? '') ?: ($profile['state_name'] ?? '') ?: ($payload['stateName'] ?? '') ?: ($payload['state_name'] ?? '') ?: ($payload['gateway_abha_profile']['state_name'] ?? '') ?: ($payload['gateway_patient']['state_name'] ?? ''));
        $districtName     = (string) (($profile['districtName'] ?? '') ?: ($profile['district_name'] ?? '') ?: ($payload['districtName'] ?? '') ?: ($payload['district_name'] ?? '') ?: ($payload['gateway_abha_profile']['district_name'] ?? '') ?: ($payload['gateway_patient']['district'] ?? ''));
        $email            = (string) (($profile['email'] ?? '') ?: ($payload['email'] ?? '') ?: ($payload['gateway_abha_profile']['email'] ?? '') ?: ($payload['gateway_patient']['email'] ?? ''));

        $abhaMeta = [
            'abha_address' => $abhaAddress,
            'profile_photo' => $photo,
            'verified_status' => $verifiedStatus,
            'verification_type' => $verificationType,
            'kyc_verified' => $kycVerified,
            'mobile_verified' => $mobileVerified,
            'address' => $address,
            'district' => $districtName,
            'state' => $stateName,
            'zip' => $zip,
            'email' => $email,
        ];

        $patientInfo = $this->tryAutoLinkByDirectMatch($abhaNum, $name, $mobile, $gender, $dob, $abhaMeta);

        $responseBase = [
            'ok'                => 1,
            'abha_number'       => $abhaNum,
            'name'              => $name,
            'photo'             => $photo,
            'mobile'            => $mobile,
            'gender'            => $gender,
            'dob'               => $dob,
            'abha_address'      => $abhaAddress,
            'verified_status'   => $verifiedStatus,
            'verification_type' => $verificationType,
            'kyc_verified'      => $kycVerified,
            'mobile_verified'   => $mobileVerified,
            'address'           => $address,
            'district'          => $districtName,
            'state'             => $stateName,
            'zip'               => $zip,
            'email'             => $email,
        ];

        if ($patientInfo !== null) {
            return $this->response->setJSON($responseBase + [
                'need_confirmation' => false,
                'patient_id'     => $patientInfo['patient_id'],
                'p_code'         => $patientInfo['p_code'],
                'is_new_patient' => $patientInfo['is_new'],
            ]);
        }

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveAbhaFieldName($fields);
        $abhaNumClean = preg_replace('/\D/', '', $abhaNum);
        // No Aadhaar available on the pure mobile-OTP path.
        $candidates = $this->findMatchingCandidates($db, $fields, $name, $mobile, $gender, $dob, '', $abhaField, $abhaNumClean);

        return $this->response->setJSON($responseBase + [
            'need_confirmation' => true,
            'patient_id'        => 0,
            'p_code'            => '',
            'is_new_patient'    => null,
            'candidates'        => $candidates,
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
    // Facility Scan & Share QR — GET abha/register/facility_qr
    // -------------------------------------------------------------------------
    public function facilityQr()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        try {
            /** @var \App\Libraries\Abdm\AbdmConnectorInterface $connector */
            $connector = AbdmConnectorFactory::make();
            $result = $connector->facilityQrCode();
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            return $this->response->setStatusCode((int) ($result['http_code'] ?? 502) ?: 502)->setJSON([
                'ok' => 0,
                'error_text' => $result['error_text'] ?? $result['message'] ?? $result['error'] ?? 'Unable to fetch facility QR code',
                'bridge_http_code' => (int) ($result['http_code'] ?? 0),
            ]);
        }

        return $this->response->setJSON([
            'ok' => 1,
            'hospital_id' => $result['hospital_id'] ?? $result['data']['hospital_id'] ?? '',
            'hospital_name' => $result['hospital_name'] ?? $result['data']['hospital_name'] ?? '',
            'hfr_id' => $result['hfr_id'] ?? $result['data']['hfr_id'] ?? '',
            'qr_data' => $result['qr_data'] ?? $result['data']['qr_data'] ?? '',
            'request_id' => $result['request_id'] ?? '',
        ]);
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

        $hospitalBrand = $this->loadHospitalBranding();
        $hmsId = trim((string) ($patient['p_code'] ?? ''));
        if ($hmsId === '') {
            $hmsId = 'PID-' . (string) ($patient['id'] ?? '');
        }
        $profilePhotoUrl = $this->resolvePatientPhotoUrl($patient);
        $mobileNo = trim((string) ($patient['mphone1'] ?? ''));
        $mobileVerified = (int) ($patient['abha_mobile_verified'] ?? 0) === 1;
        $abhaQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($abhaNumClean);
        $barcodeSvg = $this->buildCode39Svg($hmsId);
        $barcodeImage = $this->buildCode39ImageDataUri($hmsId, $barcodeSvg);

        return view('abha/card', [
            'patient'    => $patient,
            'abha_num'   => $abhaDisp,
            'abha_raw'   => $abhaNumClean,
            'patient_id' => (int) ($patient['id'] ?? 0),
            'abha_address' => (string) ($patient['abha_address'] ?? ''),
            'gender'     => $genderLabel,
            'dob'        => $dobLabel,
            'hospital_name' => (string) ($hospitalBrand['hospital_name'] ?? 'E-Atria Hospital'),
            'hospital_logo_url' => (string) ($hospitalBrand['hospital_logo_url'] ?? base_url('assets/img/logo.png')),
            'brand_name' => (string) ($hospitalBrand['brand_name'] ?? 'HMS'),
            'hms_id' => $hmsId,
            'hms_barcode_svg' => $barcodeSvg,
            'hms_barcode_image' => $barcodeImage,
            'profile_photo_url' => $profilePhotoUrl,
            'abha_qr_url' => $abhaQrUrl,
            'patient_mobile' => $mobileNo,
            'mobile_verified' => $mobileVerified,
            'stored_abha_card' => trim((string) ($patient['abha_card_base64'] ?? '')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function loadHospitalBranding(): array
    {
        $branding = [
            'hospital_name' => '',
            'hospital_logo_url' => base_url('assets/img/logo.png'),
            'brand_name' => 'HMS',
        ];

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('hospital_setting')) {
                return $branding;
            }

            $rows = $db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', [
                    'H_Name',
                    'H_logo',
                    'HOSPITAL_NAME',
                    'HOSPITAL_DISPLAY_NAME',
                    'HOSPITAL_TITLE',
                ])
                ->orderBy('id', 'DESC')
                ->get()
                ->getResultArray();

            $settings = [];
            foreach ($rows as $row) {
                $key = trim((string) ($row['s_name'] ?? ''));
                if ($key === '' || array_key_exists($key, $settings)) {
                    continue;
                }
                $settings[$key] = trim((string) ($row['s_value'] ?? ''));
            }

            foreach (['H_Name', 'HOSPITAL_NAME', 'HOSPITAL_DISPLAY_NAME', 'HOSPITAL_TITLE'] as $nameKey) {
                if (! empty($settings[$nameKey])) {
                    $branding['hospital_name'] = (string) $settings[$nameKey];
                    break;
                }
            }

            $logoName = trim((string) ($settings['H_logo'] ?? ''));
            if ($logoName !== '') {
                $branding['hospital_logo_url'] = base_url('assets/images/' . rawurlencode($logoName));
            }

            if ($branding['hospital_name'] !== '') {
                $branding['brand_name'] = $branding['hospital_name'];
            }
        } catch (\Throwable $e) {
            // Keep default branding if settings lookup fails.
        }

        if ($branding['hospital_name'] === '') {
            $branding['hospital_name'] = 'E-Atria Hospital';
        }

        return $branding;
    }

    /**
     * @param array<string,mixed> $patient
     */
    private function resolvePatientPhotoUrl(array $patient): string
    {
        $photoPath = '/assets/images/no_image.svg';

        $profileFileId = (int) ($patient['profile_file_id'] ?? 0);
        if ($profileFileId > 0) {
            try {
                $db = \Config\Database::connect();
                if ($db->tableExists('file_upload_data')) {
                    $fileRow = $db->table('file_upload_data')
                        ->select('full_path')
                        ->where('id', $profileFileId)
                        ->get()
                        ->getRowArray();
                    $fullPath = trim((string) ($fileRow['full_path'] ?? ''));
                    if ($fullPath !== '') {
                        $pos = strpos($fullPath, '/uploads/', 1);
                        if ($pos !== false) {
                            return substr($fullPath, $pos);
                        }
                        return $fullPath;
                    }
                }
            } catch (\Throwable $e) {
                // Fall through to next source.
            }
        }

        $profilePicture = trim((string) ($patient['profile_picture'] ?? ''));
        if ($profilePicture !== '') {
            $pos = strpos($profilePicture, '/uploads/', 1);
            if ($pos !== false) {
                return substr($profilePicture, $pos);
            }
            return $profilePicture;
        }

        $abhaPhotoBase64 = trim((string) ($patient['abha_profile_photo_base64'] ?? ''));
        if ($abhaPhotoBase64 !== '') {
            return str_starts_with($abhaPhotoBase64, 'data:image')
                ? $abhaPhotoBase64
                : 'data:image/jpeg;base64,' . $abhaPhotoBase64;
        }

        return $photoPath;
    }

    private function buildCode39Svg(string $value): string
    {
        $layout = $this->buildCode39Layout($value);
        if ($layout === null) {
            return '';
        }

        $rects = [];
        foreach ($layout['bars'] as $bar) {
            $rects[] = '<rect x="' . $bar['x'] . '" y="0" width="' . $bar['w'] . '" height="' . $layout['height'] . '" fill="#111" />';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $layout['width'] . ' ' . $layout['height'] . '" role="img" aria-label="HMS Barcode">'
            . implode('', $rects)
            . '</svg>';
    }

    private function buildCode39ImageDataUri(string $value, string $svgFallback = ''): string
    {
        $layout = $this->buildCode39Layout($value);
        if ($layout === null) {
            return '';
        }

        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagepng')) {
            $svg = $svgFallback !== '' ? $svgFallback : $this->buildCode39Svg($value);
            return $svg !== '' ? 'data:image/svg+xml;base64,' . base64_encode($svg) : '';
        }

        $img = imagecreatetruecolor($layout['width'], $layout['height']);
        if ($img === false) {
            $svg = $svgFallback !== '' ? $svgFallback : $this->buildCode39Svg($value);
            return $svg !== '' ? 'data:image/svg+xml;base64,' . base64_encode($svg) : '';
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 17, 17, 17);
        imagefilledrectangle($img, 0, 0, $layout['width'], $layout['height'], $white);

        foreach ($layout['bars'] as $bar) {
            imagefilledrectangle(
                $img,
                $bar['x'],
                0,
                $bar['x'] + $bar['w'] - 1,
                $layout['height'],
                $black
            );
        }

        ob_start();
        imagepng($img);
        $pngBinary = ob_get_clean();
        imagedestroy($img);

        if (! is_string($pngBinary) || $pngBinary === '') {
            $svg = $svgFallback !== '' ? $svgFallback : $this->buildCode39Svg($value);
            return $svg !== '' ? 'data:image/svg+xml;base64,' . base64_encode($svg) : '';
        }

        return 'data:image/png;base64,' . base64_encode($pngBinary);
    }

    /**
     * @return array{bars: list<array{x:int,w:int}>, width:int, height:int}|null
     */
    private function buildCode39Layout(string $value): ?array
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return null;
        }

        $supported = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];

        $payload = '*' . $normalized . '*';
        $clean = '';
        $len = strlen($payload);
        for ($i = 0; $i < $len; $i++) {
            $ch = $payload[$i];
            if (! isset($supported[$ch])) {
                continue;
            }
            $clean .= $ch;
        }

        if ($clean === '') {
            return null;
        }

        $narrow = 2;
        $wide = 5;
        $height = 56;
        $quiet = 10;
        $gap = 2;

        $x = $quiet;
        $bars = [];
        $cleanLen = strlen($clean);
        for ($i = 0; $i < $cleanLen; $i++) {
            $pattern = $supported[$clean[$i]];
            for ($j = 0; $j < 9; $j++) {
                $w = ($pattern[$j] === 'w') ? $wide : $narrow;
                if ($j % 2 === 0) {
                    $bars[] = ['x' => $x, 'w' => $w];
                }
                $x += $w;
            }
            if ($i < ($cleanLen - 1)) {
                $x += $gap;
            }
        }

        return [
            'bars' => $bars,
            'width' => $x + $quiet,
            'height' => $height,
        ];
    }

    /**
     * Normalize nested bridge error payloads into a single readable message.
     *
     * @param array<string, mixed> $result
     */
    private function extractBridgeErrorText(array $result, string $fallback): string
    {
        $fieldErrors = [];
        if (is_array($result['data'] ?? null)) {
            foreach ($result['data'] as $field => $message) {
                if ($field === 'timestamp' || ! is_string($message) || trim($message) === '') {
                    continue;
                }
                $fieldErrors[] = $field . ': ' . trim($message);
            }
        }
        if ($fieldErrors !== []) {
            return implode('; ', $fieldErrors);
        }

        $candidates = [
            $result['error_text'] ?? null,
            $result['message'] ?? null,
            $result['error']['message'] ?? null,
            $result['error']['description'] ?? null,
            $result['error']['code'] ?? null,
            $result['error']['error'] ?? null,
            $result['error']['error_description'] ?? null,
            $result['data']['message'] ?? null,
            $result['data']['error']['message'] ?? null,
            $result['data']['error']['description'] ?? null,
            $result['data']['error']['code'] ?? null,
            $result['data']['error']['error'] ?? null,
            $result['data']['error']['error_description'] ?? null,
            $result['details']['message'] ?? null,
            $result['details']['error'] ?? null,
            $result['description'] ?? null,
            $result['data']['description'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return $fallback;
    }

    // -------------------------------------------------------------------------
    // Step 5 — Confirm patient link/create after a "matching patients found"
    // review by the operator (see findMatchingCandidates()).
    // POST abha/create/confirm_patient
    // -------------------------------------------------------------------------
    public function confirmPatient()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $action    = trim((string) ($this->request->getPost('action') ?? ''));
        $updateMode = trim((string) ($this->request->getPost('update_mode') ?? 'update'));
        $patientId = (int) ($this->request->getPost('patient_id') ?? 0);
        $abhaNum   = (string) ($this->request->getPost('abha_number') ?? '');
        $name      = trim((string) ($this->request->getPost('name') ?? ''));
        $mobile    = preg_replace('/\D/', '', (string) ($this->request->getPost('mobile') ?? ''));
        $gender    = (string) ($this->request->getPost('gender') ?? '');
        $dob       = (string) ($this->request->getPost('dob') ?? '');

        if (! in_array($action, ['new', 'existing'], true)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid action. Must be "new" or "existing".']);
        }
        if (! in_array($updateMode, ['keep', 'update'], true)) {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Invalid patient update mode.']);
        }

        $abhaMeta = [
            'abha_address'      => (string) ($this->request->getPost('abha_address') ?? ''),
            'profile_photo'     => (string) ($this->request->getPost('photo') ?? ''),
            'card_base64'       => (string) ($this->request->getPost('card_base64') ?? ''),
            'verified_status'   => (string) ($this->request->getPost('verified_status') ?? ''),
            'verification_type' => (string) ($this->request->getPost('verification_type') ?? ''),
            'kyc_verified'      => $this->request->getPost('kyc_verified'),
            'mobile_verified'   => $this->request->getPost('mobile_verified'),
            'address'           => (string) ($this->request->getPost('address') ?? ''),
            'district'          => (string) ($this->request->getPost('district') ?? ''),
            'state'             => (string) ($this->request->getPost('state') ?? ''),
            'zip'               => (string) ($this->request->getPost('zip') ?? ''),
            'email'             => (string) ($this->request->getPost('email') ?? ''),
        ];

        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField    = $this->resolveAbhaFieldName($fields);
        $abhaNumClean = preg_replace('/\D/', '', $abhaNum);
        $abhaAddress  = trim((string) ($abhaMeta['abha_address'] ?? ''));

        if ($abhaNumClean === '' && $abhaAddress === '') {
            return $this->response->setJSON(['ok' => 0, 'error_text' => 'Missing ABHA identifier.']);
        }

        // ABHA number must be unique across patient_master — never let two
        // patient rows carry the same ABHA.
        if ($abhaField && $abhaNumClean !== '') {
            $conflict = $db->table('patient_master')
                ->select('id,p_code,p_fname')
                ->where($abhaField, $abhaNumClean)
                ->get()->getRowArray();
            if ($conflict) {
                $conflictId = (int) ($conflict['id'] ?? 0);
                if ($action === 'new' || $conflictId !== $patientId) {
                    return $this->response->setJSON([
                        'ok' => 0,
                        'error_text' => 'This ABHA number is already linked to patient '
                            . ($conflict['p_code'] ?? '') . ' (' . ($conflict['p_fname'] ?? '') . '). '
                            . 'Please select that patient instead.',
                        'conflict_patient_id' => $conflictId,
                        'conflict_p_code' => (string) ($conflict['p_code'] ?? ''),
                    ]);
                }
            }
        }

        if ($action === 'existing') {
            if ($patientId <= 0) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'patient_id is required to link an existing patient.']);
            }
            $row = $db->table('patient_master')->where('id', $patientId)->get()->getRowArray();
            if (! $row) {
                return $this->response->setJSON(['ok' => 0, 'error_text' => 'Selected patient not found.']);
            }

            // Guard: this patient must not already carry a *different* ABHA number.
            if ($abhaField && ! empty($row[$abhaField]) && $abhaNumClean !== ''
                && preg_replace('/\D/', '', (string) $row[$abhaField]) !== $abhaNumClean) {
                return $this->response->setJSON([
                    'ok' => 0,
                    'error_text' => 'Selected patient already has a different ABHA linked (' . $row[$abhaField] . '). Cannot link a second ABHA to the same patient.',
                ]);
            }

            $patientInfo = $this->linkAbhaToPatient(
                $db,
                $patientId,
                $updateMode === 'update' ? $name : '',
                $updateMode === 'update' ? $mobile : '',
                $updateMode === 'update' ? $gender : '',
                $updateMode === 'update' ? $dob : '',
                $updateMode === 'update' ? $abhaMeta : array_diff_key($abhaMeta, array_flip(['address', 'district', 'state', 'zip', 'email'])),
                $abhaNumClean,
                $abhaAddress,
                $fields,
                $abhaField,
                $updateMode === 'update'
            );
        } else {
            $patientInfo = $this->createPatientFromAbha($db, $name, $mobile, $gender, $dob, $abhaMeta, $abhaNumClean, $abhaAddress, $fields, $abhaField);
        }

        return $this->response->setJSON([
            'ok'             => 1,
            'patient_id'     => $patientInfo['patient_id'],
            'p_code'         => $patientInfo['p_code'],
            'is_new_patient' => $patientInfo['is_new'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helper — resolve the column name used to store the ABHA number
    // in patient_master (varies by installation/migration history).
    // -------------------------------------------------------------------------
    private function resolveAbhaFieldName(array $fields): ?string
    {
        foreach (['abha_id', 'abha_no', 'abha'] as $f) {
            if (in_array($f, $fields, true)) {
                return $f;
            }
        }
        if (in_array('abha_address', $fields, true)) {
            return 'abha_address';
        }

        return null;
    }

    /**
     * Try to safely auto-resolve the local patient WITHOUT operator confirmation.
     * Only used when there's no real ambiguity:
     *  - exact ABHA-number match (this exact digital identity was already linked here before), or
     *  - an unambiguous orphan "ABHA PATIENT" placeholder row (HMS's own incomplete record, not a
     *    different real patient).
     * Returns null when neither applies — caller should then run findMatchingCandidates()
     * and ask the operator to confirm "Create New" vs "Update Existing".
     *
     * @return array{patient_id:int,p_code:string,is_new:bool}|null
     */
    private function tryAutoLinkByDirectMatch(
        string $abhaNum,
        string $name,
        string $mobile,
        string $gender,
        string $dob,
        array $abhaMeta = []
    ): ?array {
        $db     = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField    = $this->resolveAbhaFieldName($fields);
        $abhaNumClean = preg_replace('/\D/', '', $abhaNum);
        $abhaAddress  = trim((string) ($abhaMeta['abha_address'] ?? ''));

        $existing = null;
        if ($abhaField && $abhaNumClean !== '') {
            $existing = $db->table('patient_master')
                ->where($abhaField, $abhaNumClean)->get()->getRowArray();
            if (! $existing && $abhaNum !== $abhaNumClean) {
                $existing = $db->table('patient_master')
                    ->where($abhaField, $abhaNum)->get()->getRowArray();
            }
        }

        if ($existing) {
            $patientId = (int) ($existing['id'] ?? 0);
            return $this->linkAbhaToPatient($db, $patientId, $name, $mobile, $gender, $dob, $abhaMeta, $abhaNumClean, $abhaAddress, $fields, $abhaField);
        }

        // Safe repair path for orphan placeholder rows created during prior failed mapping.
        // Reuse only when there is exactly one unambiguous candidate with no clinical linkage.
        $placeholder = $this->findRepairablePlaceholderPatient($db, $fields);
        if ($placeholder !== null) {
            $patientId = (int) ($placeholder['id'] ?? 0);
            if ($patientId > 0) {
                return $this->repairPlaceholderPatient($db, $patientId, $name, $mobile, $gender, $dob, $abhaMeta, $abhaNumClean, $abhaAddress, $fields, $abhaField);
            }
        }

        return null;
    }

    /**
     * Compute whole-years age from a dob string ('DD-MM-YYYY' or 'YYYY-MM-DD').
     */
    private function computeAgeYears(string $dob): ?int
    {
        $dobDb = $this->normalizeDobToDb($dob);
        if ($dobDb === '') {
            return null;
        }
        try {
            return (int) (new \DateTime($dobDb))->diff(new \DateTime())->y;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function genderLabel(int $g): string
    {
        if ($g === 2) {
            return 'Female';
        }
        if ($g === 3) {
            return 'Other';
        }
        if ($g === 1) {
            return 'Male';
        }

        return '';
    }

    /**
     * Search patient_master for possible existing local records matching the
     * ABHA profile by name/age/gender (and mobile/aadhaar when available), so
     * the operator can confirm "this is the same person" before linking, or
     * choose to register as a brand-new patient. HMS never auto-creates a new
     * patient record on its own once this search has run — the operator always
     * makes the final call.
     *
     * @return array<int, array<string, mixed>>
     */
    private function findMatchingCandidates(
        $db,
        array $fields,
        string $name,
        string $mobile,
        string $genderRaw,
        string $dob,
        string $aadhaar,
        ?string $abhaField,
        string $abhaNumClean
    ): array {
        $genderDb   = $this->toPatientGenderValue($genderRaw);
        $ageYears   = $this->computeAgeYears($dob);
        $birthYear  = preg_match('/^(\d{4})/', trim($dob), $birthYearMatch) === 1 ? $birthYearMatch[1] : '';
        $nameUp     = strtoupper(trim($name));
        $nameTokens = array_values(array_filter(preg_split('/\s+/', $nameUp) ?: [], fn ($t) => strlen($t) >= 3));
        $aadhaar    = preg_replace('/\D/', '', $aadhaar);

        if ($nameTokens === [] && $mobile === '' && $aadhaar === '' && $abhaNumClean === '' && $birthYear === '') {
            return [];
        }

        $selectFields = array_values(array_intersect(
            ['id', 'p_code', 'p_fname', 'p_lname', 'gender', 'dob', 'age', 'mphone1', 'udai', 'add1', 'district', 'state', 'zip', 'profile_file_id', 'profile_picture', 'abha_profile_photo_base64'],
            $fields
        ));
        $select = implode(',', $selectFields);
        if ($abhaField !== null && ! in_array($abhaField, ['id', 'p_code', 'p_fname', 'p_lname', 'gender', 'dob', 'age', 'mphone1', 'udai'], true)) {
            $select .= ',' . $abhaField;
        }

        $builder = $db->table('patient_master')->select($select);
        $builder->groupStart();
        $any = false;
        foreach ($nameTokens as $tok) {
            $builder->orLike('p_fname', $tok);
            $any = true;
        }
        if ($mobile !== '') {
            $builder->orWhere('mphone1', $mobile);
            $any = true;
        }
        if ($aadhaar !== '') {
            $builder->orWhere('udai', $aadhaar);
            $any = true;
        }
        if ($ageYears !== null && $genderDb > 0) {
            $builder->orWhere('gender', $genderDb);
            $any = true;
        }
        $builder->groupEnd();

        if (! $any) {
            return [];
        }

        $rows = $builder->orderBy('insert_date', 'DESC')->limit(40)->get()->getResultArray();

        $candidates = [];
        foreach ($rows as $row) {
            $rowName    = strtoupper(trim((string) ($row['p_fname'] ?? '') . ' ' . (string) ($row['p_lname'] ?? '')));
            $rowTokens  = array_values(array_filter(preg_split('/\s+/', $rowName) ?: [], fn ($t) => strlen($t) >= 3));
            $nameOverlap = $nameTokens !== [] && $rowTokens !== [] && $nameTokens[0] === $rowTokens[0];

            $rowAgeYears = null;
            $rowDob = trim((string) ($row['dob'] ?? ''));
            if ($rowDob !== '' && $rowDob !== '0000-00-00') {
                try {
                    $rowAgeYears = (int) (new \DateTime($rowDob))->diff(new \DateTime())->y;
                } catch (\Throwable $e) {
                    $rowAgeYears = null;
                }
            }
            if ($rowAgeYears === null && (int) ($row['age'] ?? 0) > 0) {
                $rowAgeYears = (int) $row['age'];
            }

            $rowBirthYear = preg_match('/^(\d{4})/', $rowDob, $rowBirthYearMatch) === 1 ? $rowBirthYearMatch[1] : '';
            $birthYearMatch = $birthYear !== '' && $rowBirthYear !== '' && $rowBirthYear === $birthYear;
            $ageMatch       = $birthYearMatch;
            $genderMatch    = $genderDb > 0 && (int) ($row['gender'] ?? 0) === $genderDb;
            $mobileMatch    = $mobile !== '' && preg_replace('/\D/', '', (string) ($row['mphone1'] ?? '')) === $mobile;
            $aadhaarMatch   = $aadhaar !== '' && preg_replace('/\D/', '', (string) ($row['udai'] ?? '')) === $aadhaar;

            $rowAbha      = $abhaField !== null ? trim((string) ($row[$abhaField] ?? '')) : '';
            $abhaConflict = $rowAbha !== '' && preg_replace('/\D/', '', $rowAbha) !== $abhaNumClean;

            $abhaMatch = $abhaNumClean !== '' && $rowAbha !== ''
                && preg_replace('/\D/', '', $rowAbha) === $abhaNumClean;
            $minimumDemographicMatch = $nameOverlap && $birthYearMatch && $genderMatch;
            if (! $abhaMatch && ! $aadhaarMatch && ! $minimumDemographicMatch) {
                continue;
            }

            $score = ($abhaMatch ? 12 : 0) + ($aadhaarMatch ? 10 : 0)
                + ($minimumDemographicMatch ? 5 : 0) + ($nameOverlap ? 2 : 0)
                + ($birthYearMatch ? 2 : 0) + ($genderMatch ? 1 : 0) + ($mobileMatch ? 4 : 0);

            $candidates[] = [
                'id'            => (int) ($row['id'] ?? 0),
                'p_code'        => (string) ($row['p_code'] ?? ''),
                'name'          => trim((string) ($row['p_fname'] ?? '') . ' ' . (string) ($row['p_lname'] ?? '')),
                'gender'        => (int) ($row['gender'] ?? 0),
                'gender_label'  => $this->genderLabel((int) ($row['gender'] ?? 0)),
                'dob'           => $rowDob,
                'age'           => $rowAgeYears,
                'mobile'        => (string) ($row['mphone1'] ?? ''),
                'photo_url'     => $this->resolvePatientPhotoUrl($row),
                'address'       => trim(implode(', ', array_filter([
                    (string) ($row['add1'] ?? ''),
                    (string) ($row['district'] ?? ''),
                    (string) ($row['state'] ?? ''),
                    (string) ($row['zip'] ?? ''),
                ]))),
                'aadhaar'       => (string) ($row['udai'] ?? ''),
                'abha'          => $rowAbha,
                'abha_conflict' => $abhaConflict,
                'match'         => [
                    'name'    => $nameOverlap,
                    'age'     => $ageMatch,
                    'gender'  => $genderMatch,
                    'mobile'  => $mobileMatch,
                    'aadhaar' => $aadhaarMatch,
                    'abha'    => $abhaMatch,
                ],
                'score' => $score,
            ];
        }

        usort($candidates, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($candidates, 0, 8);
    }

    /**
     * Link an ABHA profile to an already-identified existing patient row
     * (either auto-matched by exact ABHA number, or explicitly chosen by the
     * operator from the "matching patients" confirmation modal). Only
     * backfills fields that are currently blank so verified/manually-entered
     * HMS data is never clobbered.
     *
     * @return array{patient_id:int,p_code:string,is_new:bool}
     */
    private function linkAbhaToPatient(
        $db,
        int $patientId,
        string $name,
        string $mobile,
        string $gender,
        string $dob,
        array $abhaMeta,
        string $abhaNumClean,
        string $abhaAddress,
        array $fields,
        ?string $abhaField,
        bool $updateDetails = false
    ): array {
        $existing = $db->table('patient_master')->where('id', $patientId)->get()->getRowArray() ?? [];
        $pCode    = (string) ($existing['p_code'] ?? '');
        $existingUpdates = [];

        $existingName = strtoupper(trim((string) ($existing['p_fname'] ?? '')));
        if ($name !== '' && ($updateDetails || $existingName === '' || $existingName === 'ABHA PATIENT')) {
            $existingUpdates['p_fname'] = strtoupper($name);
        }

        // Backfill DOB/gender/mobile from ABHA profile only if not already recorded,
        // so we never clobber a manually-corrected value.
        $existingDob = trim((string) ($existing['dob'] ?? ''));
        if (($updateDetails || $existingDob === '' || $existingDob === '0000-00-00') && $dob !== '') {
            $dobDb = $this->normalizeDobToDb($dob);
            if ($dobDb !== '') {
                $existingUpdates['dob'] = $dobDb;
                if (in_array('estimate_dob', $fields, true)) {
                    $existingUpdates['estimate_dob'] = 0;
                }
            }
        }
        $existingGenderRaw = (int) ($existing['gender'] ?? 0);
        if (($updateDetails || $existingGenderRaw <= 0) && $gender !== '') {
            $existingUpdates['gender'] = $this->toPatientGenderValue($gender);
        }
        if ($mobile !== '' && ($updateDetails || empty($existing['mphone1']))) {
            $existingUpdates['mphone1'] = $mobile;
        }

        // Backfill ABHA field if it was empty
        if ($abhaField && empty($existing[$abhaField]) && $abhaNumClean !== '') {
            $existingUpdates[$abhaField] = $abhaNumClean;
        }

        if ($existingUpdates !== []) {
            $db->table('patient_master')->where('id', $patientId)->update($existingUpdates);
        }

        $this->syncAbhaMetaToPatient($patientId, $abhaMeta, $abhaNumClean, $abhaAddress, $fields);

        return ['patient_id' => $patientId, 'p_code' => $pCode, 'is_new' => false];
    }

    /**
     * Repair a single unambiguous orphan "ABHA PATIENT" placeholder row created
     * during a prior failed mapping. Not a "new patient" from the operator's
     * perspective — this is HMS fixing its own incomplete record.
     *
     * @return array{patient_id:int,p_code:string,is_new:bool}
     */
    private function repairPlaceholderPatient(
        $db,
        int $patientId,
        string $name,
        string $mobile,
        string $gender,
        string $dob,
        array $abhaMeta,
        string $abhaNumClean,
        string $abhaAddress,
        array $fields,
        ?string $abhaField
    ): array {
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

    /**
     * Create a brand-new patient_master row from an ABHA profile. Only ever
     * called when the operator has explicitly chosen "Create New" from the
     * matching-patients confirmation modal (or there was nothing to match).
     *
     * @return array{patient_id:int,p_code:string,is_new:bool}
     */
    private function createPatientFromAbha(
        $db,
        string $name,
        string $mobile,
        string $gender,
        string $dob,
        array $abhaMeta,
        string $abhaNumClean,
        string $abhaAddress,
        array $fields,
        ?string $abhaField
    ): array {
        $genderDb = $this->toPatientGenderValue($gender);
        $dobDb    = $this->normalizeDobToDb($dob);

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

        $address = trim((string) ($abhaMeta['address'] ?? ''));
        if ($address !== '' && in_array('add1', $fields, true)) {
            $target['add1'] = $address;
        }

        $district = trim((string) ($abhaMeta['district'] ?? ''));
        if ($district !== '' && in_array('district', $fields, true)) {
            $target['district'] = strtoupper($district);
        }

        $state = trim((string) ($abhaMeta['state'] ?? ''));
        if ($state !== '' && in_array('state', $fields, true)) {
            $target['state'] = strtoupper($state);
        }

        $zip = trim((string) ($abhaMeta['zip'] ?? ''));
        if ($zip !== '' && in_array('zip', $fields, true)) {
            $target['zip'] = $zip;
        }

        $email = trim((string) ($abhaMeta['email'] ?? ''));
        if ($email !== '' && in_array('email1', $fields, true)) {
            $target['email1'] = $email;
        }

        $profilePhoto = trim((string) ($abhaMeta['profile_photo'] ?? ''));
        if ($profilePhoto !== '' && in_array('abha_profile_photo_base64', $fields, true)) {
            $target['abha_profile_photo_base64'] = $profilePhoto;
        }

        $cardBase64 = trim((string) ($abhaMeta['card_base64'] ?? ''));
        if ($cardBase64 !== '' && in_array('abha_card_base64', $fields, true)) {
            $target['abha_card_base64'] = $cardBase64;
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
        $account = is_array($payload['account'] ?? null) ? $payload['account'] : [];
        $dataAccount = is_array($payload['data']['account'] ?? null) ? $payload['data']['account'] : [];
        $loginAccount = is_array($payload['accounts'][0] ?? null) ? $payload['accounts'][0] : [];
        $mergedAccount = $loginAccount;
        foreach ([$dataAccount, $account] as $accountSource) {
            foreach ($accountSource as $key => $value) {
                if (! is_scalar($value) || trim((string) $value) === '') {
                    continue;
                }
                if (in_array(strtoupper(trim((string) $value)), ['PATIENT', 'PATIENT NAME', 'N/A'], true)) {
                    continue;
                }
                $mergedAccount[$key] = $value;
            }
        }

        $candidates = [
            $payload['ABHAProfile'] ?? null,
            $payload['gateway_abha_profile'] ?? null,
            $payload['data']['gateway_abha_profile'] ?? null,
            $payload['gateway_patient'] ?? null,
            $payload['data']['gateway_patient'] ?? null,
            $payload['profile'] ?? null,
            $mergedAccount,
            $account,
            $loginAccount,
            $payload['data']['account'] ?? null,
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
     * @param array<string, mixed> $payload
     */
    private function extractAbhaProfileName(array $profile, array $payload): string
    {
        foreach ([$profile, $payload, $payload['account'] ?? [], $payload['data']['account'] ?? [], $payload['gateway_abha_profile'] ?? [], $payload['gateway_patient'] ?? [], $payload['data']['gateway_patient'] ?? []] as $source) {
            if (! is_array($source)) {
                continue;
            }

            foreach (['name', 'fullName', 'full_name', 'patient_name'] as $key) {
                $name = trim((string) ($source[$key] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }

            $parts = [];
            foreach (['firstName', 'first_name', 'middleName', 'middle_name', 'lastName', 'last_name'] as $key) {
                $part = trim((string) ($source[$key] ?? ''));
                if ($part !== '') {
                    $parts[] = $part;
                }
            }

            if ($parts !== []) {
                return implode(' ', $parts);
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $profile
     */
    private function looksLikeGatewayAbhaProfile(array $profile): bool
    {
        foreach (['ABHANumber', 'abha_id', 'preferredAddress', 'preferredAbhaAddress', 'abha_address', 'name', 'fullName', 'full_name', 'patient_name', 'firstName', 'first_name', 'lastName', 'last_name', 'gender', 'dob', 'date_of_birth', 'profilePhoto', 'profile_photo'] as $key) {
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
            $errorText = '';
            foreach ([
                $result['error_text'] ?? null,
                $result['message'] ?? null,
                $result['description'] ?? null,
                $result['error'] ?? null,
                $result['error']['message'] ?? null,
                $result['error']['description'] ?? null,
                $result['error']['error'] ?? null,
                $result['error']['error_description'] ?? null,
                $result['data']['message'] ?? null,
                $result['data']['description'] ?? null,
                $result['data']['error'] ?? null,
                $result['data']['error']['message'] ?? null,
                $result['data']['error']['description'] ?? null,
                $result['data']['error']['error'] ?? null,
                $result['data']['error']['error_description'] ?? null,
                $result['details']['message'] ?? null,
                $result['details']['error'] ?? null,
                $result['data']['raw_response'] ?? null,
            ] as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $errorText = trim($candidate);
                    break;
                }
            }

            $requestId = '';
            foreach ([
                $result['request_id'] ?? null,
                $result['data']['request_id'] ?? null,
                $result['result']['request_id'] ?? null,
            ] as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $requestId = trim($candidate);
                    break;
                }
            }

            if ($errorText === '') {
                $errorText = 'ABHA validation failed';
            }
            if ($requestId !== '' && stripos($errorText, $requestId) === false) {
                $errorText .= ' (Ref: ' . $requestId . ')';
            }

            if (stripos($errorText, 'Please make a valid request.') !== false) {
                $errorText = 'Bridge validation rejected this request. Please contact bridge support with the reference shown. '
                    . $errorText;
            }

            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $errorText,
            ]);
        }

        $statusRaw = '';
        foreach ([
            $result['status'] ?? null,
            $result['validation_status'] ?? null,
            $result['data']['status'] ?? null,
            $result['data']['validation_status'] ?? null,
            $result['result']['status'] ?? null,
            $result['data']['result']['status'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $statusRaw = strtoupper(trim($candidate));
                break;
            }
        }

        // Bridge adapters may return ACTIVE/VERIFIED/EXISTS for successful ABHA checks.
        $validStates = ['VALID', 'ACTIVE', 'VERIFIED', 'EXISTS', 'FOUND', 'SUCCESS'];
        $status = in_array($statusRaw, $validStates, true) || $statusRaw === '' ? 'VALID' : $statusRaw;

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $accounts = is_array($data['accounts'] ?? null) ? $data['accounts'] : [];
        $account = isset($accounts[0]) && is_array($accounts[0])
            ? $accounts[0]
            : (is_array($data['account'] ?? null) ? $data['account'] : []);
        $txnId = trim((string) (
            $result['txn_id']
            ?? $result['txnId']
            ?? $data['txn_id']
            ?? $data['txnId']
            ?? $data['transaction_id']
            ?? $data['transactionId']
            ?? $account['txn_id']
            ?? $account['txnId']
            ?? ''
        ));
        $authMethods = is_array($result['auth_methods'] ?? null)
            ? $result['auth_methods']
            : (is_array($result['authMethods'] ?? null)
                ? $result['authMethods']
                : (is_array($data['authMethods'] ?? null) ? $data['authMethods'] : ($data['auth_methods'] ?? [])));
        $blockedMethods = $data['blockedAuthMethods'] ?? $data['blocked_auth_methods'] ?? $account['blockedAuthMethods'] ?? $account['blocked_auth_methods'] ?? [];
        $maskedMobile = $this->maskAbhaMobile((string) (
            $data['mobile']
            ?? $data['mobileNumber']
            ?? $data['maskedMobile']
            ?? $data['masked_mobile']
            ?? $data['phone']
            ?? $account['masked_mobile']
            ?? $account['mobile']
            ?? $account['mobileNumber']
            ?? $account['maskedMobile']
            ?? $account['phone']
            ?? ''
        ));
        return $this->response->setJSON([
            'ok' => 1,
            'status' => $status,
            'raw_status' => $statusRaw,
            'txn_id' => $txnId,
            'auth_methods' => array_values(array_unique(array_map(static fn($method): string => strtoupper(trim((string) $method)), $authMethods))),
            'blocked_auth_methods' => is_array($blockedMethods) ? array_values($blockedMethods) : [],
            'masked_mobile' => $maskedMobile,
            'account' => [
                'name' => trim((string) ($account['name'] ?? $account['fullName'] ?? $account['full_name'] ?? $data['name'] ?? $data['fullName'] ?? $data['full_name'] ?? '')),
                'abha_number' => trim((string) ($account['ABHANumber'] ?? $account['abhaNumber'] ?? $account['abha_id'] ?? $account['healthIdNumber'] ?? $data['ABHANumber'] ?? $data['healthIdNumber'] ?? $data['abha_id'] ?? '')),
                'abha_address' => trim((string) ($account['abhaAddress'] ?? $account['preferredAddress'] ?? $account['preferredAbhaAddress'] ?? $account['abha_address'] ?? $data['abhaAddress'] ?? $data['preferredAddress'] ?? $data['preferredAbhaAddress'] ?? $data['abha_address'] ?? '')),
                'masked_mobile' => $maskedMobile,
            ],
        ]);
    }

    private function maskAbhaMobile(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/\*/', $value) === 1) {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) === 10) {
            return substr($digits, 0, 2) . '*****' . substr($digits, -3);
        }

        return $value;
    }

    public function loginRequestOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $txnId = trim((string) ($this->request->getPost('txn_id') ?? ''));
        $authMethod = strtoupper(trim((string) ($this->request->getPost('auth_method') ?? '')));
        if ($txnId === '' || ! in_array($authMethod, ['MOBILE_OTP', 'AADHAAR_OTP'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => 0, 'error_text' => 'Valid transaction and OTP method are required.']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaLoginRequestOtp([
                'txn_id' => $txnId,
                'auth_method' => $authMethod,
                'abha_id' => trim((string) ($this->request->getPost('abha_id') ?? '')),
                'abha_address' => trim((string) ($this->request->getPost('abha_address') ?? '')),
                'scope' => trim((string) ($this->request->getPost('scope') ?? 'abha-login')),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            $httpCode = (int) ($result['http_code'] ?? 0);
            if ($httpCode < 400 || $httpCode > 599) {
                $httpCode = 502;
            }
            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => 0,
                'error_text' => $this->extractBridgeErrorText($result, 'The ABDM Bridge could not send the OTP.'),
                'request_id' => (string) ($result['request_id'] ?? ''),
            ]);
        }

        $data = is_array($result['data'] ?? null) ? $result['data'] : [];
        $maskedMobile = trim((string) ($data['masked_mobile'] ?? $data['maskedMobile'] ?? $data['mobile'] ?? ''));
        $deliveryMessage = trim((string) ($result['message'] ?? $data['message'] ?? ''));
        if ($maskedMobile === '' && preg_match('/(\d{4})(?!.*\d)/', $deliveryMessage, $mobileEnding) === 1) {
            $maskedMobile = '******' . $mobileEnding[1];
        }
        return $this->response->setJSON([
            'ok' => 1,
            'txn_id' => (string) ($result['txn_id'] ?? $result['txnId'] ?? $data['txn_id'] ?? $data['txnId'] ?? $txnId),
            'auth_method' => $authMethod,
            'masked_mobile' => $this->maskAbhaMobile($maskedMobile),
            'resend_after' => max(60, (int) ($data['resend_after'] ?? $data['resendAfter'] ?? 60)),
        ]);
    }

    public function loginVerifyOtp()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
        }

        $txnId = trim((string) ($this->request->getPost('txn_id') ?? ''));
        $authMethod = strtoupper(trim((string) ($this->request->getPost('auth_method') ?? '')));
        $otp = preg_replace('/\D/', '', trim((string) ($this->request->getPost('otp') ?? '')));
        if ($txnId === '' || ! in_array($authMethod, ['MOBILE_OTP', 'AADHAAR_OTP'], true) || strlen($otp) !== 6) {
            return $this->response->setStatusCode(422)->setJSON(['ok' => 0, 'error_text' => 'Transaction, OTP method, and 6-digit OTP are required.']);
        }

        try {
            $result = AbdmConnectorFactory::make()->abhaLoginVerifyOtp([
                'txn_id' => $txnId,
                'auth_method' => $authMethod,
                'otp' => $otp,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(502)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
        }

        if (empty($result['ok']) || (int) $result['ok'] !== 1) {
            $httpCode = (int) ($result['http_code'] ?? 0);
            if ($httpCode < 400 || $httpCode > 599) {
                $httpCode = 502;
            }
            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => 0,
                'error_text' => $this->extractBridgeErrorText($result, 'OTP verification failed.'),
                'request_id' => (string) ($result['request_id'] ?? ''),
            ]);
        }

        $payload = $result;
        if (is_array($result['data'] ?? null)) {
            foreach ($result['data'] as $key => $value) {
                if (! array_key_exists($key, $payload)) {
                    $payload[$key] = $value;
                }
            }
        }
        $profile = $this->pickGatewayAbhaProfile($payload);
        $firstNonEmpty = static function (array $values): string {
            foreach ($values as $value) {
                if (is_scalar($value) && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }
            return '';
        };
        $abhaNumber = preg_replace('/\D/', '', $firstNonEmpty([$profile['ABHANumber'] ?? null, $profile['abhaNumber'] ?? null, $profile['abha_number'] ?? null, $profile['abha_id'] ?? null, $payload['ABHANumber'] ?? null, $payload['abhaNumber'] ?? null, $payload['abha_id'] ?? null]));
        $abhaAddress = $firstNonEmpty([$profile['preferredAddress'] ?? null, $profile['preferredAbhaAddress'] ?? null, $profile['abhaAddress'] ?? null, $profile['abha_address'] ?? null, $payload['preferredAddress'] ?? null, $payload['abhaAddress'] ?? null, $payload['abha_address'] ?? null]);
        $name = $this->extractAbhaProfileName($profile, $payload);
        $mobile = $firstNonEmpty([$profile['mobile'] ?? null, $profile['mobileNumber'] ?? null, $profile['mobile_number'] ?? null, $payload['mobile'] ?? null, $payload['mobileNumber'] ?? null, $payload['mobile_number'] ?? null]);
        $gender = $firstNonEmpty([$profile['gender'] ?? null, $payload['gender'] ?? null]);
        $dob = $firstNonEmpty([$profile['dob'] ?? null, $profile['dateOfBirth'] ?? null, $profile['date_of_birth'] ?? null, $payload['dob'] ?? null, $payload['dateOfBirth'] ?? null, $payload['date_of_birth'] ?? null]);
        if ($dob === '') {
            $day = $firstNonEmpty([$profile['dayOfBirth'] ?? null, $profile['day_of_birth'] ?? null]);
            $month = $firstNonEmpty([$profile['monthOfBirth'] ?? null, $profile['month_of_birth'] ?? null]);
            $year = $firstNonEmpty([$profile['yearOfBirth'] ?? null, $profile['year_of_birth'] ?? null]);
            if ($day !== '' && $month !== '' && $year !== '') {
                $dob = sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
            }
        }
        $address = $firstNonEmpty([$profile['address'] ?? null, $profile['addressLine'] ?? null, $profile['address_line'] ?? null, $payload['address'] ?? null, $payload['addressLine'] ?? null, $payload['address_line'] ?? null]);
        $district = $firstNonEmpty([$profile['districtName'] ?? null, $profile['district_name'] ?? null, $profile['district'] ?? null, $payload['districtName'] ?? null, $payload['district_name'] ?? null, $payload['district'] ?? null]);
        $state = $firstNonEmpty([$profile['stateName'] ?? null, $profile['state_name'] ?? null, $profile['state'] ?? null, $payload['stateName'] ?? null, $payload['state_name'] ?? null, $payload['state'] ?? null]);
        $zip = $firstNonEmpty([$profile['pinCode'] ?? null, $profile['pincode'] ?? null, $profile['pin'] ?? null, $payload['pinCode'] ?? null, $payload['pincode'] ?? null, $payload['pin'] ?? null]);
        $photo = $firstNonEmpty([$profile['profilePhoto'] ?? null, $profile['profile_photo'] ?? null, $profile['photo'] ?? null, $payload['patient_photo'] ?? null, $payload['profilePhoto'] ?? null, $payload['profile_photo'] ?? null, $payload['photo'] ?? null, $payload['data']['profilePhoto'] ?? null, $payload['data']['patient_photo'] ?? null]);
        $cardData = $this->extractAbhaCardData($payload);

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];
        $abhaField = $this->resolveAbhaFieldName($fields);
        $candidates = $this->findMatchingCandidates($db, $fields, $name, $mobile, $gender, $dob, '', $abhaField, $abhaNumber);

        return $this->response->setJSON([
            'ok' => 1,
            'need_confirmation' => true,
            'abha_number' => $abhaNumber,
            'abha_address' => $abhaAddress,
            'name' => $name,
            'mobile' => $mobile,
            'gender' => $gender,
            'dob' => $dob,
            'address' => $address,
            'district' => $district,
            'state' => $state,
            'zip' => $zip,
            'photo' => $photo,
            'verified_status' => 'VERIFIED',
            'verification_type' => $authMethod,
            'mobile_verified' => $authMethod === 'MOBILE_OTP' ? 1 : null,
            'card_base64' => $cardData,
            'card_content_type' => $this->resolveAbhaCardContentType($payload),
            'candidates' => $candidates,
        ]);
    }

    private function extractAbhaCardData(array $payload): string
    {
        $sources = [$payload, $payload['data'] ?? [], $payload['account'] ?? [], $payload['profile'] ?? [], $payload['gateway_patient'] ?? [], $payload['data']['gateway_patient'] ?? []];
        foreach ($sources as $source) {
            if (! is_array($source)) { continue; }
            foreach (['card_base64', 'card_data', 'abhaCard', 'abha_card', 'official_card', 'cardData', 'card'] as $key) {
                $value = $source[$key] ?? '';
                if (is_string($value) && trim($value) !== '') { return trim($value); }
                if (is_array($value)) {
                    foreach (['base64', 'data', 'card_base64', 'card_data'] as $nestedKey) {
                        if (is_string($value[$nestedKey] ?? null) && trim($value[$nestedKey]) !== '') {
                            return trim($value[$nestedKey]);
                        }
                    }
                }
            }
        }
        return '';
    }

    private function resolveAbhaCardContentType(array $payload): string
    {
        $format = strtolower(trim((string) ($payload['card_content_type'] ?? $payload['cardContentType'] ?? $payload['card_format'] ?? ($payload['account']['card_format'] ?? ''))));
        if ($format === 'png') { return 'image/png'; }
        if ($format === 'jpg' || $format === 'jpeg') { return 'image/jpeg'; }
        if ($format === 'pdf') { return 'application/pdf'; }
        return $format !== '' && str_contains($format, '/') ? $format : 'image/png';
    }
}
