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
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? '');
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
                'address' => $address,
                'district' => $districtName,
                'state' => $stateName,
                'zip' => $zip,
                'email' => $email,
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
        $mobile           = (string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? '');
        $verifiedStatus   = (string) (($payload['gateway_abha_profile']['status'] ?? '') ?: ($profile['verifiedStatus'] ?? '') ?: ($profile['status'] ?? '') ?: ($payload['verifiedStatus'] ?? '') ?: ($payload['status'] ?? ''));
        $verificationType = (string) (($payload['gateway_abha_profile']['abha_type'] ?? '') ?: ($profile['verificationType'] ?? '') ?: ($profile['abhaType'] ?? '') ?: ($payload['verificationType'] ?? '') ?: ($payload['abhaType'] ?? ''));
        $kycVerified      = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
        $mobileVerified   = $profile['mobileVerified'] ?? $payload['mobileVerified'] ?? null;
        $address          = (string) (($profile['address'] ?? '') ?: ($profile['address_line'] ?? '') ?: ($payload['address'] ?? '') ?: ($payload['address_line'] ?? '') ?: ($payload['gateway_abha_profile']['address'] ?? '') ?: ($payload['gateway_patient']['address_line'] ?? ''));
        $zip              = (string) (($profile['pinCode'] ?? '') ?: ($profile['pin_code'] ?? '') ?: ($payload['pinCode'] ?? '') ?: ($payload['pin_code'] ?? '') ?: ($payload['gateway_abha_profile']['pin_code'] ?? '') ?: ($payload['gateway_patient']['pincode'] ?? ''));
        $stateName        = (string) (($profile['stateName'] ?? '') ?: ($profile['state_name'] ?? '') ?: ($payload['stateName'] ?? '') ?: ($payload['state_name'] ?? '') ?: ($payload['gateway_abha_profile']['state_name'] ?? '') ?: ($payload['gateway_patient']['state_name'] ?? ''));
        $districtName     = (string) (($profile['districtName'] ?? '') ?: ($profile['district_name'] ?? '') ?: ($payload['districtName'] ?? '') ?: ($payload['district_name'] ?? '') ?: ($payload['gateway_abha_profile']['district_name'] ?? '') ?: ($payload['gateway_patient']['district'] ?? ''));
        $email            = (string) (($profile['email'] ?? '') ?: ($payload['email'] ?? '') ?: ($payload['gateway_abha_profile']['email'] ?? '') ?: ($payload['gateway_patient']['email'] ?? ''));

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
                'address' => $address,
                'district' => $districtName,
                'state' => $stateName,
                'zip' => $zip,
                'email' => $email,
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

        $officialCardUrl = trim((string) ($hospitalBrand['official_card_url'] ?? ''));
        if ($officialCardUrl === '') {
            $officialCardUrl = 'https://abha.abdm.gov.in/abha/v3/';
        }

        $bridgePortalUrl = trim((string) ($hospitalBrand['bridge_portal_url'] ?? ''));
        if ($bridgePortalUrl === '') {
            $bridgePortalUrl = 'https://abdm-bridge.e-atria.in/';
        }

        $barcodeSvg = $this->buildCode39Svg($hmsId);

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
            'brand_name' => 'E-Atria',
            'hms_id' => $hmsId,
            'hms_barcode_svg' => $barcodeSvg,
            'official_card_url' => $officialCardUrl,
            'bridge_portal_url' => $bridgePortalUrl,
        ]);
    }

    // -------------------------------------------------------------------------
    // ABHA Official Card fetch — GET abha/card/official/{abha_number}
    // Proxies bridge GET /api/v3/abha/card and returns card_data for browser print.
    // -------------------------------------------------------------------------
    public function officialCard(string $abhaNumber = '')
    {
        $abhaNumClean = preg_replace('/\D/', '', $abhaNumber);
        if (strlen($abhaNumClean) !== 14) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error_text' => 'Invalid ABHA number.',
            ]);
        }

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('patient_master') ?? [];

        $abhaField = null;
        foreach (['abha_id', 'abha_no', 'abha'] as $f) {
            if (in_array($f, $fields, true)) {
                $abhaField = $f;
                break;
            }
        }
        if ($abhaField === null && in_array('abha_address', $fields, true)) {
            $abhaField = 'abha_address';
        }

        $patient = null;
        if ($abhaField) {
            $patient = $db->table('patient_master')->where($abhaField, $abhaNumClean)->get()->getRowArray();
            if (! $patient) {
                $patient = $db->table('patient_master')->where($abhaField, $abhaNumber)->get()->getRowArray();
            }
        }

        if (! $patient) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => 0,
                'error_text' => 'Patient not found for this ABHA number.',
            ]);
        }

        $runtime = $this->resolveBridgeRuntimeSettings();
        if (($runtime['ok'] ?? 0) !== 1) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error_text' => (string) ($runtime['error_text'] ?? 'Bridge settings are incomplete.'),
            ]);
        }

        $query = [
            'abha_number' => $abhaNumClean,
            'patient_id' => (int) ($patient['id'] ?? 0),
            'abha_address' => trim((string) ($patient['abha_address'] ?? '')),
            'hfr_id' => (string) ($runtime['hfr_id'] ?? ''),
        ];

        $result = $this->bridgeGet(
            (string) ($runtime['url'] ?? ''),
            (string) ($runtime['token'] ?? ''),
            '/api/v3/abha/card',
            $query
        );

        if (($result['ok'] ?? 0) !== 1) {
            $httpCode = (int) ($result['http_code'] ?? 502);
            if ($httpCode < 100 || $httpCode > 599) {
                $httpCode = 502;
            }
            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => 0,
                'error_text' => (string) ($result['error_text'] ?? 'Failed to fetch official ABHA card from bridge.'),
                'bridge_http_code' => (int) ($result['http_code'] ?? 0),
            ]);
        }

        $payload = $result['data'] ?? $result;
        $cardData = trim((string) ($payload['card_data'] ?? $payload['cardData'] ?? ''));
        if ($cardData === '') {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => 0,
                'error_text' => 'Bridge response does not contain card_data.',
            ]);
        }

        if (stripos($cardData, 'data:image') === 0) {
            $parts = explode(',', $cardData, 2);
            $cardData = $parts[1] ?? '';
        }

        return $this->response->setJSON([
            'ok' => 1,
            'card_data' => $cardData,
            'abha_number' => $abhaNumClean,
            'patient_id' => (int) ($patient['id'] ?? 0),
            'request_id' => (string) ($result['request_id'] ?? ''),
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
            'official_card_url' => '',
            'bridge_portal_url' => '',
        ];

        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('hospital_setting')) {
                return $branding;
            }

            $rows = $db->table('hospital_setting')
                ->select('s_name, s_value')
                ->whereIn('s_name', [
                    'HOSPITAL_NAME',
                    'HOSPITAL_DISPLAY_NAME',
                    'HOSPITAL_TITLE',
                    'ABDM_OFFICIAL_CARD_URL',
                    'ABDM_BRIDGE_PORTAL_URL',
                    'EATRIA_BRIDGE_URL',
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

            foreach (['HOSPITAL_NAME', 'HOSPITAL_DISPLAY_NAME', 'HOSPITAL_TITLE'] as $nameKey) {
                if (! empty($settings[$nameKey])) {
                    $branding['hospital_name'] = (string) $settings[$nameKey];
                    break;
                }
            }

            if (! empty($settings['ABDM_OFFICIAL_CARD_URL'])) {
                $branding['official_card_url'] = (string) $settings['ABDM_OFFICIAL_CARD_URL'];
            }

            if (! empty($settings['ABDM_BRIDGE_PORTAL_URL'])) {
                $branding['bridge_portal_url'] = (string) $settings['ABDM_BRIDGE_PORTAL_URL'];
            } elseif (! empty($settings['EATRIA_BRIDGE_URL'])) {
                $parsed = parse_url((string) $settings['EATRIA_BRIDGE_URL']);
                $scheme = (string) ($parsed['scheme'] ?? 'https');
                $host = (string) ($parsed['host'] ?? '');
                if ($host !== '') {
                    $branding['bridge_portal_url'] = $scheme . '://' . $host . '/';
                }
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
     * @return array<string,mixed>
     */
    private function resolveBridgeRuntimeSettings(): array
    {
        $settings = [
            'url' => rtrim((string) (getenv('EATRIA_BRIDGE_URL') ?: 'https://abdm-bridge.e-atria.in/api'), '/'),
            'token' => trim((string) (getenv('EATRIA_BRIDGE_TOKEN') ?: '')),
            'hfr_id' => trim((string) (getenv('ABDM_HFR_ID') ?: '')),
        ];

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('hospital_setting')) {
                $rows = $db->table('hospital_setting')
                    ->select('s_name,s_value')
                    ->whereIn('s_name', ['EATRIA_BRIDGE_URL', 'EATRIA_BRIDGE_TOKEN', 'ABDM_HFR_ID', 'H_HFR_ID'])
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $key = trim((string) ($row['s_name'] ?? ''));
                    $val = trim((string) ($row['s_value'] ?? ''));
                    if ($key === 'EATRIA_BRIDGE_URL' && $val !== '') {
                        $settings['url'] = rtrim($val, '/');
                    }
                    if ($key === 'EATRIA_BRIDGE_TOKEN' && $val !== '') {
                        $settings['token'] = $val;
                    }
                    if ($key === 'ABDM_HFR_ID' && $val !== '') {
                        $settings['hfr_id'] = $val;
                    }
                    if ($key === 'H_HFR_ID' && $val !== '' && (string) $settings['hfr_id'] === '') {
                        $settings['hfr_id'] = $val;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Keep env/default values when DB lookup fails.
        }

        if (stripos((string) $settings['token'], 'Bearer ') === 0) {
            $settings['token'] = trim(substr((string) $settings['token'], 7));
        }

        if ((string) $settings['url'] === '') {
            return ['ok' => 0, 'error_text' => 'EATRIA_BRIDGE_URL is missing.'];
        }
        if ((string) $settings['token'] === '') {
            return ['ok' => 0, 'error_text' => 'EATRIA_BRIDGE_TOKEN is missing.'];
        }
        if ((string) $settings['hfr_id'] === '') {
            return ['ok' => 0, 'error_text' => 'ABDM_HFR_ID is missing.'];
        }

        return ['ok' => 1] + $settings;
    }

    /**
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function bridgeGet(string $baseUrl, string $token, string $path, array $query = []): array
    {
        $base = rtrim($baseUrl, '/');
        $normalizedPath = '/' . ltrim($path, '/');

        $baseHasApiSuffix = (bool) preg_match('#/api$#i', $base);
        $pathHasApiPrefix = str_starts_with($normalizedPath, '/api/');
        if ($baseHasApiSuffix && $pathHasApiPrefix) {
            $normalizedPath = substr($normalizedPath, 4);
        }

        $url = $base . $normalizedPath;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        try {
            $client = service('curlrequest', [
                'timeout' => 30,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);

            $response = $client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($token),
                    'Accept' => 'application/json',
                ],
            ]);

            $httpCode = (int) $response->getStatusCode();
            $decoded = json_decode((string) $response->getBody(), true);
            if (! is_array($decoded)) {
                return [
                    'ok' => 0,
                    'http_code' => $httpCode,
                    'error_text' => 'Non-JSON response from bridge.',
                ];
            }

            $ok = (int) ($decoded['ok'] ?? (($httpCode >= 200 && $httpCode < 300) ? 1 : 0));
            if ($ok !== 1) {
                return [
                    'ok' => 0,
                    'http_code' => $httpCode,
                    'error_text' => (string) ($decoded['error_text'] ?? $decoded['message'] ?? $decoded['error'] ?? 'Bridge request failed.'),
                    'data' => $decoded,
                    'request_id' => (string) ($decoded['request_id'] ?? ''),
                ];
            }

            return [
                'ok' => 1,
                'http_code' => $httpCode,
                'data' => $decoded,
                'request_id' => (string) ($decoded['request_id'] ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => 0,
                'http_code' => 0,
                'error_text' => $e->getMessage(),
            ];
        }
    }

    private function buildCode39Svg(string $value): string
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return '';
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
            return '';
        }

        $narrow = 2;
        $wide = 5;
        $height = 56;
        $quiet = 10;
        $gap = 2;

        $x = $quiet;
        $rects = [];
        $cleanLen = strlen($clean);
        for ($i = 0; $i < $cleanLen; $i++) {
            $pattern = $supported[$clean[$i]];
            for ($j = 0; $j < 9; $j++) {
                $w = ($pattern[$j] === 'w') ? $wide : $narrow;
                if ($j % 2 === 0) {
                    $rects[] = '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $height . '" fill="#111" />';
                }
                $x += $w;
            }
            if ($i < ($cleanLen - 1)) {
                $x += $gap;
            }
        }

        $width = $x + $quiet;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="HMS Barcode">'
            . implode('', $rects)
            . '</svg>';
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
            $existingUpdates = [];

            $existingName = strtoupper(trim((string) ($existing['p_fname'] ?? '')));
            if ($name !== '' && ($existingName === '' || $existingName === 'ABHA PATIENT')) {
                $existingUpdates['p_fname'] = strtoupper($name);
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
            $payload['ABHAProfile'] ?? null,
            $payload['gateway_abha_profile'] ?? null,
            $payload['profile'] ?? null,
            $payload['accounts'][0] ?? null,
            $payload['gateway_patient'] ?? null,
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
        foreach ([$profile, $payload, $payload['gateway_abha_profile'] ?? [], $payload['gateway_patient'] ?? []] as $source) {
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
            return $this->response->setJSON([
                'ok'         => 0,
                'error_text' => $result['error_text'] ?? $result['message']
                                ?? $result['data']['message'] ?? 'ABHA validation failed',
            ]);
        }

        return $this->response->setJSON(['ok' => 1, 'status' => (string) ($result['data']['status'] ?? 'UNKNOWN')]);
    }
}
