<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Abdm\AbdmConnectorFactory;
use App\Libraries\AbdmWorkTaskService;
use App\Libraries\BridgeSyncService;
use App\Models\BloodGroupModel;
use App\Models\PatientModel;

class Patient extends BaseController
{
    // A consent request left in REQUESTED/PENDING for longer than this (no GRANTED/DENIED
    // callback from the ABDM bridge) is treated as abandoned/stale so "Fetch ABDM Records"
    // starts a fresh consent request instead of resuming a request that will never resolve.
    private const ABDM_PENDING_STALE_SECONDS = 3600;

    public function index()
    {
        $user = auth()->user();

        $bloodGroupModel = new BloodGroupModel();
        $data['blood_group'] = $bloodGroupModel
            ->orderBy('id', 'ASC')
            ->findAll();
        
        $data['user'] = $user;

        $data['india_states'] = [];
        if ($this->db->tableExists('india_state')) {
            $data['india_states'] = $this->db->table('india_state')
                ->select('id, state_name')
                ->orderBy('state_name', 'ASC')
                ->get()
                ->getResultArray();
        }

		$data['refer_master'] = $this->getActiveReferMasters();

        return view('billing/Patient_V', $data);
    }

    public function search_opd()
	{
		$sql = "SELECT p.*, 
					Date_Format(o.apointment_date,'%d-%m-%Y')  AS opd_Visit,o.doc_name,o.doc_spec,o.opd_code,i.short_name
					FROM (patient_master p join opd_master o on p.id =o.p_id)
					Left join hc_insurance i on i.id = o.insurance_id
					order BY o.opd_id DESC 	LIMIT 100 ";

        $query = $this->db->query($sql);
		$data['data'] = $query->getResult();

		return view('billing/Patient_Search_opd', $data);
	}

    public function create()
	{
		$isAjax = $this->request->isAJAX();
		$abhaIdInput = trim((string) $this->request->getPost('input_abha_id'));
		$abhaAddressInput = trim((string) $this->request->getPost('input_abha_address'));
		[$abhaId, $abhaAddress, $abhaError] = $this->normalizeAbhaInputs($abhaIdInput, $abhaAddressInput);

		$chk_age = $this->request->getPost('chk_age');
		$age_month = (string) $this->request->getPost('input_age_month');
		$age_year = (string) $this->request->getPost('input_age_year');

		$rules = [
			'input_name' => 'required|min_length[1]|max_length[100]',
			'input_relative_name' => 'required|min_length[1]|max_length[100]',
			'input_mphone1' => 'required|min_length[10]|max_length[10]',
		];

		if ($chk_age === 'on') {
			$estimate_dob = 1;
			if ($age_year === '' && $age_month === '') {
				$rules['input_age_year'] = 'required|min_length[1]|max_length[4]';
			}
		} else {
			$estimate_dob = 0;
			$rules['datepicker_dob'] = 'required|min_length[1]|max_length[10]';
		}

		$validation = service('validation');
		$validation->setRules($rules);
		if (!$validation->withRequest($this->request)->run()) {
			$errorText = implode("\n", $validation->getErrors());
			if ($isAjax) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => $errorText,
				]);
			}

			return redirect()->to(base_url('billing/patient'))
				->withInput()
				->with('error', $errorText);
		}

		if ($abhaError !== null) {
			if ($isAjax) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => $abhaError,
				]);
			}

			return redirect()->to(base_url('billing/patient'))
				->withInput()
				->with('error', $abhaError);
		}

		$referByName = trim((string) $this->request->getPost('refer_by_name'));
		$referByError = $this->validatePatientReferByInput($referByName);
		if ($referByError !== null) {
			if ($isAjax) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => $referByError,
				]);
			}

			return redirect()->to(base_url('billing/patient'))
				->withInput()
				->with('error', $referByError);
		}

		$bloodGroup = trim((string) $this->request->getPost('input_blood_group'));
		if ($bloodGroup === '') {
			$bloodGroup = 'Not Define';
		}

		$data = [
			'mphone1' => $this->request->getPost('input_mphone1'),
			'p_fname' => strtoupper((string) $this->request->getPost('input_name')),
			'gender' => $this->request->getPost('optionsRadios_gender'),
			'zip' => $this->request->getPost('input_zip'),
			'add1' => strtoupper((string) $this->request->getPost('input_address')),
			'city' => strtoupper((string) $this->request->getPost('input_city')),
			'district' => strtoupper((string) $this->request->getPost('input_district')),
			'state' => strtoupper((string) $this->request->getPost('input_state')),
			'title' => $this->request->getPost('cbo_title'),
			'p_relative' => $this->request->getPost('cbo_relation'),
			'p_rname' => strtoupper((string) $this->request->getPost('input_relative_name')),
			'blood_group' => strtoupper($bloodGroup),
			'udai' => strtoupper((string) $this->request->getPost('input_udai')),
			'estimate_dob' => $estimate_dob,
		];
		$this->applyPatientReferbyField($data, $referByName);
		$this->applyPatientAbhaFieldValues($data, $abhaId, $abhaAddress);

		if ($chk_age === 'on') {
			$data['age'] = $age_year;
			$data['age_in_month'] = $age_month;
		} else {
			$data['dob'] = $this->parseDate($this->request->getPost('datepicker_dob'));
		}

		$patientModel = new PatientModel();
		$insertId = $patientModel->insertPatient($data);
		if ($insertId <= 0) {
			if ($isAjax) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => 'Unable to save patient.',
				]);
			}

			return redirect()->to(base_url('billing/patient'))
				->withInput()
				->with('error', 'Unable to save patient.');
		}

		$this->saveNamesToNameList([
			(string) $this->request->getPost('input_name'),
			(string) $this->request->getPost('input_relative_name'),
		]);

		$this->enqueuePatientAbhaSync($insertId, $data, 'patient.created');
		$this->createPatientAbdmWorkTask($insertId, $data, $abhaId);

		// Check Multiple UHID
		$relativeName = trim(strtoupper((string) $this->request->getPost('input_relative_name')));
		$patientName = trim(strtoupper((string) $this->request->getPost('input_name')));

		$builder = $this->db->table('patient_master');
		$builder->select("id, CONCAT(p_code,'/',p_fname,'/',IF(gender=1,'M','F'),'/',p_relative,' ',p_rname) as Sresult");
		$builder->groupStart()
			->where('p_rname', $relativeName)
			->where('p_fname', $patientName)
			->groupEnd();
		$builder->where('id <>', $insertId);
		$builder->orderBy('id', 'DESC');
		$search_result = $builder->get()->getResult();

		$logText = '';
		foreach ($search_result as $row) {
			$logText .= $row->Sresult . PHP_EOL;
		}

		if (count($search_result) > 0) {
			$user = auth()->user();
			$userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
			$userId = $user->id ?? '';
			$userName = $userLabel . '[Date:' . date('d-m-Y H:i:s') . ']-' . $userId;

			$dupData = [
				'new_uhid' => $insertId,
				'name_of_person' => $patientName,
				'new_patient_code' => '',
				'date_of_registration' => date('Y-m-d H:i:s'),
				'update_by' => $userName,
				'remark_duplicate' => $logText,
			];

			$patientModel->insertDuplicateLog($dupData);
		}

		if ($isAjax) {
			return $this->response->setJSON(['insertid' => $insertId]);
		}

		return redirect()->to(base_url('billing/patient/person_record/' . $insertId));
	}

	public function search_adv()
	{
		$inputMphone1 = trim((string) $this->request->getPost('input_mphone1'));
		$inputAadhar = trim((string) $this->request->getPost('input_udai'));
		$inputAbhaId = trim((string) $this->request->getPost('input_abha_id'));
		$inputRelativeName = trim((string) $this->request->getPost('input_relative_name'));
		$inputName = trim((string) $this->request->getPost('input_name'));
		$abhaField = $this->resolvePatientAbhaIdField();

		$builder = $this->db->table('patient_master');
		$builder->select("id,p_fname,p_relative,p_rname,gender,add1,city,district,state,zip,mphone1,udai,last_visit,dob,age,age_in_month,estimate_dob");
		if ($this->db->fieldExists('referby', 'patient_master')) {
			$builder->select('referby');
		}
		if ($abhaField !== null) {
			$builder->select($abhaField . ' AS abha_id');
		}

		$hasCondition = false;
		if ($inputMphone1 !== '' || $inputAadhar !== '' || $inputAbhaId !== '' || ($inputRelativeName !== '' && $inputName !== '')) {
			$builder->groupStart();

			if ($inputMphone1 !== '') {
				$builder->where('mphone1', $inputMphone1);
				$hasCondition = true;
			}

			if ($inputAadhar !== '') {
				$builder->orWhere('udai', $inputAadhar);
				$hasCondition = true;
			}

			if ($inputAbhaId !== '' && $abhaField !== null) {
				$builder->orWhere($abhaField, $inputAbhaId);
				$hasCondition = true;
			}

			if ($inputRelativeName !== '' && $inputName !== '') {
				$builder->orGroupStart()
					->where('p_rname', trim(strtoupper($inputRelativeName)))
					->where('p_fname', trim(strtoupper($inputName)))
					->groupEnd();
				$hasCondition = true;
			}

			$builder->groupEnd();
		}

		$searchResult = [];
		if ($hasCondition) {
			$searchResult = $builder->orderBy('id', 'DESC')->limit(25)->get()->getResult();
		}

		return view('billing/search_adv_data', [
			'search_result' => $searchResult,
			'filters' => [
				'input_mphone1' => $inputMphone1,
				'input_udai' => strtoupper($inputAadhar),
				'input_abha_id' => $inputAbhaId,
				'input_relative_name' => strtoupper($inputRelativeName),
				'input_name' => strtoupper($inputName),
			],
		]);
	}

	public function abha_fetch_profile()
	{
		if (! $this->request->isAJAX()) {
			return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
		}

		$abhaId = trim((string) $this->request->getPost('abha_id'));
		if (! $this->isValidAbhaId($abhaId)) {
			return $this->response->setJSON(['ok' => 0, 'error_text' => 'ABHA ID must be a 14-digit number']);
		}

		$profile = [];
		$abhaField = $this->resolvePatientAbhaIdField();
		if ($abhaField !== null) {
			$row = $this->db->table('patient_master')
				->select('id,p_fname,gender,dob,mphone1,city,state')
				->where($abhaField, $abhaId)
				->get(1)
				->getRowArray();

			if (! empty($row)) {
				$profile = [
					'name' => (string) ($row['p_fname'] ?? ''),
					'gender' => (string) ($row['gender'] ?? ''),
					'dob' => (string) ($row['dob'] ?? ''),
					'mobile' => (string) ($row['mphone1'] ?? ''),
					'city' => (string) ($row['city'] ?? ''),
					'state' => (string) ($row['state'] ?? ''),
				];
			}
		}

		$queueId = null;
		try {
			$bridge = new BridgeSyncService();
			$queueId = $bridge->enqueue('abdm.abha.profile.fetch.requested', [
				'abha_id' => $abhaId,
				'requested_at' => date('Y-m-d H:i:s'),
			], 'abha', $abhaId);
		} catch (\Throwable $e) {
		}

		return $this->response->setJSON([
			'ok' => 1,
			'queue_id' => $queueId,
			'profile' => $profile,
			'source' => ! empty($profile) ? 'local_cache' : 'abdm_queue',
		]);
	}

	// -------------------------------------------------------------------------
	// ABHA M1 OTP Flows — accessible to billing staff (no abdm.* permission needed)
	// -------------------------------------------------------------------------

	public function abhaAadhaarGenerateOtp()
	{
		if (! $this->request->isAJAX()) {
			return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
		}

		$isJson  = str_contains($this->request->getHeaderLine('Content-Type'), 'application/json');
		$body    = $isJson ? ($this->request->getJSON(true) ?? []) : [];
		$loginId = trim((string) ($body['loginId'] ?? $body['aadhaar'] ?? $this->request->getPost('loginId') ?? $this->request->getPost('aadhaar') ?? ''));

		if ($loginId === '' || ! preg_match('/^\d{12}$/', $loginId)) {
			return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 12-digit Aadhaar number is required']);
		}

		try {
			$result = AbdmConnectorFactory::make()->abhaAadhaarGenerateOtp(['aadhaar' => $loginId]);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
		}

		return $this->response->setJSON($result);
	}

	public function abhaAadhaarVerifyOtp()
	{
		if (! $this->request->isAJAX()) {
			return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
		}

		$isJson = str_contains($this->request->getHeaderLine('Content-Type'), 'application/json');
		$body   = $isJson ? ($this->request->getJSON(true) ?? []) : [];
		$txnId  = trim((string) ($body['txnId'] ?? $body['txn_id'] ?? $this->request->getPost('txnId') ?? $this->request->getPost('txn_id') ?? ''));
		$otp    = trim((string) ($body['otp'] ?? $this->request->getPost('otp') ?? ''));
		$mobile = trim((string) ($body['mobile'] ?? $this->request->getPost('mobile') ?? ''));
		$requestedPid = (int) ($body['p_id'] ?? $this->request->getPost('p_id') ?? 0);

		if ($txnId === '' || $otp === '') {
			return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
		}

		try {
			$result = AbdmConnectorFactory::make()->abhaAadhaarVerifyOtp(['txnId' => $txnId, 'otp' => $otp, 'mobile' => $mobile]);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
		}

		if (! empty($result['ok']) && (int) $result['ok'] === 1) {
			$result = $this->enrichAbhaVerifyResult($result, $requestedPid);
		}

		return $this->response->setJSON($this->sanitizeAbhaVerifyApiResponse($result));
	}

	public function abhaMobileGenerateOtp()
	{
		if (! $this->request->isAJAX()) {
			return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
		}

		$isJson  = str_contains($this->request->getHeaderLine('Content-Type'), 'application/json');
		$body    = $isJson ? ($this->request->getJSON(true) ?? []) : [];
		$loginId = trim((string) ($body['loginId'] ?? $body['mobile'] ?? $this->request->getPost('loginId') ?? $this->request->getPost('mobile') ?? ''));

		if ($loginId === '' || ! preg_match('/^\d{10}$/', $loginId)) {
			return $this->response->setJSON(['ok' => 0, 'error_text' => 'Valid 10-digit mobile number is required']);
		}

		try {
			$result = AbdmConnectorFactory::make()->abhaMobileGenerateOtp(['mobile' => $loginId]);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
		}

		return $this->response->setJSON($result);
	}

	public function abhaMobileVerifyOtp()
	{
		if (! $this->request->isAJAX()) {
			return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error_text' => 'Invalid request']);
		}

		$isJson = str_contains($this->request->getHeaderLine('Content-Type'), 'application/json');
		$body   = $isJson ? ($this->request->getJSON(true) ?? []) : [];
		$txnId  = trim((string) ($body['txnId'] ?? $body['txn_id'] ?? $this->request->getPost('txnId') ?? $this->request->getPost('txn_id') ?? ''));
		$otp    = trim((string) ($body['otp'] ?? $this->request->getPost('otp') ?? ''));
		$requestedPid = (int) ($body['p_id'] ?? $this->request->getPost('p_id') ?? 0);

		if ($txnId === '' || $otp === '') {
			return $this->response->setJSON(['ok' => 0, 'error_text' => 'txnId and otp are required']);
		}

		try {
			$result = AbdmConnectorFactory::make()->abhaMobileVerifyOtp(['txnId' => $txnId, 'otp' => $otp]);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error_text' => $e->getMessage()]);
		}

		if (! empty($result['ok']) && (int) $result['ok'] === 1) {
			$result = $this->enrichAbhaVerifyResult($result, $requestedPid);
		}

		return $this->response->setJSON($this->sanitizeAbhaVerifyApiResponse($result));
	}


	public function search()
	{
		$sdata = (string) $this->request->getPost('txtsearch');
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\/-]/', '', trim($sdata ?? ''));

		$advSearchBy = trim((string) $this->request->getPost('adv_search_by'));
		$advSearchValue = preg_replace('/[^A-Za-z0-9 _.@\/-]/', '', trim((string) $this->request->getPost('adv_search_value')));
		$advAgeMode = trim((string) $this->request->getPost('adv_age_mode'));
		$advAgeValue = preg_replace('/[^0-9]/', '', trim((string) $this->request->getPost('adv_age_value')));
		$advAgeFrom = preg_replace('/[^0-9]/', '', trim((string) $this->request->getPost('adv_age_from')));
		$advAgeTo = preg_replace('/[^0-9]/', '', trim((string) $this->request->getPost('adv_age_to')));
		$advAgeTolerance = preg_replace('/[^0-9]/', '', trim((string) $this->request->getPost('adv_age_tolerance')));
		$advAgeToleranceInt = $advAgeTolerance === '' ? 2 : (int) $advAgeTolerance;
		if ($advAgeToleranceInt < 0) {
			$advAgeToleranceInt = 0;
		}
		if ($advAgeToleranceInt > 20) {
			$advAgeToleranceInt = 20;
		}

		$data['search_query'] = $sdata;
		$data['advanced_filters'] = [
			'adv_search_by' => $advSearchBy,
			'adv_search_value' => $advSearchValue,
			'adv_age_mode' => $advAgeMode,
			'adv_age_value' => $advAgeValue,
			'adv_age_from' => $advAgeFrom,
			'adv_age_to' => $advAgeTo,
			'adv_age_tolerance' => (string) $advAgeToleranceInt,
		];
		return view('billing/Patient_Search_V', $data);
	}

	protected function buildOldUhidSearchClause(string $rowData, ?string $oldUhidField): string
	{
		if ($oldUhidField === null || $oldUhidField === '') {
			return '';
		}

		$escapedValue = $this->db->escape($rowData);
		$escapedLikeValue = $this->db->escape('%' . $rowData . '%');
		$escapedSuffixLikeValue = $this->db->escape('%' . $rowData);
		$normalizedTerm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $rowData));

		$clauses = [
			"p.{$oldUhidField} = " . $escapedValue,
			'p.' . $oldUhidField . ' LIKE ' . $escapedLikeValue,
			'p.' . $oldUhidField . ' LIKE ' . $escapedSuffixLikeValue,
		];

		if ($normalizedTerm !== '') {
			$escapedNormalizedTerm = $this->db->escape($normalizedTerm);
			$normalizedField = "UPPER(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.{$oldUhidField}, '')), '/', ''), '-', ''), ' ', ''))";
			$clauses[] = $normalizedField . ' = ' . $escapedNormalizedTerm;
			$clauses[] = $normalizedField . ' LIKE ' . $this->db->escape('%' . $normalizedTerm . '%');
			$clauses[] = $normalizedField . ' LIKE ' . $this->db->escape('%' . $normalizedTerm);
		}

		return "TRIM(COALESCE(p.{$oldUhidField}, '')) != '' AND (" . implode(' OR ', $clauses) . ')';
	}

	protected function formatPatientCodeCell(int $patientId, string $patientCode, string $oldUhid = ''): string
	{
		$link = '<a href="javascript:load_form(\'' . base_url('billing/patient/person_record/' . $patientId) . '\');">' . esc($patientCode) . '</a>';
		$oldUhid = trim($oldUhid);

		if ($oldUhid === '') {
			return $link;
		}

		return $link . '<br><small><i>' . esc($oldUhid) . '</i></small>';
	}

	protected function buildPatientSearchCondition(string $rowData, ?string $abhaField = null, ?string $oldUhidField = null): string
	{
		$escapedValue = $this->db->escape($rowData);
		$clauses = [
			'p.p_code like ' . $this->db->escape('%' . $rowData),
			'p.mphone1 = ' . $escapedValue,
			'p.udai = ' . $escapedValue,
		];

		if ($abhaField !== null && $abhaField !== '') {
			$clauses[] = 'p.' . $abhaField . ' = ' . $escapedValue;
		}

		$oldUhidClause = $this->buildOldUhidSearchClause($rowData, $oldUhidField);
		if ($oldUhidClause !== '') {
			$clauses[] = $oldUhidClause;
		}

		return ' and (' . implode(' or ', $clauses) . ')';
	}

	public function search_ajax()
	{
		$request = $this->request->getGet();
		
		// Get search value from DataTables search box or from initial search_query
		$dtSearch = trim((string) ($request['search']['value'] ?? ''));
		$initialSearch = trim((string) ($request['search_query'] ?? ''));
		$advSearchBy = trim((string) ($request['adv_search_by'] ?? ''));
		$advSearchValue = trim((string) ($request['adv_search_value'] ?? ''));
		$advAgeMode = trim((string) ($request['adv_age_mode'] ?? ''));
		$advAgeValue = trim((string) ($request['adv_age_value'] ?? ''));
		$advAgeFrom = trim((string) ($request['adv_age_from'] ?? ''));
		$advAgeTo = trim((string) ($request['adv_age_to'] ?? ''));
		$advAgeTolerance = trim((string) ($request['adv_age_tolerance'] ?? ''));
		
		// Use DataTables search if provided, otherwise use initial search_query
		$sdata = $dtSearch !== '' ? $dtSearch : $initialSearch;
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\/-]/', '', $sdata);
		$advSearchValue = preg_replace('/[^A-Za-z0-9 _.@\/-]/', '', $advSearchValue);
		$advAgeValue = preg_replace('/[^0-9]/', '', $advAgeValue);
		$advAgeFrom = preg_replace('/[^0-9]/', '', $advAgeFrom);
		$advAgeTo = preg_replace('/[^0-9]/', '', $advAgeTo);
		$advAgeTolerance = preg_replace('/[^0-9]/', '', $advAgeTolerance);
		$advAgeToleranceInt = $advAgeTolerance === '' ? 2 : (int) $advAgeTolerance;
		if ($advAgeToleranceInt < 0) {
			$advAgeToleranceInt = 0;
		}
		if ($advAgeToleranceInt > 20) {
			$advAgeToleranceInt = 20;
		}

// Detect ABHA and legacy UHID columns in patient_master
		$abhaField = null;
		$oldUhidField = null;
		$pmFields  = $this->db->getFieldNames('patient_master') ?? [];
		foreach (['abha_id', 'abha_no', 'abha', 'abha_address'] as $f) {
			if (in_array($f, $pmFields, true)) { $abhaField = $f; break; }
		}
		foreach (['old_uhid', 'legacy_uhid', 'old_patient_code'] as $f) {
			if (in_array($f, $pmFields, true)) { $oldUhidField = $f; break; }
		}

		$hasAdvancedFilters = false;
		$advancedClause = '';

		if ($advSearchBy === 'age') {
			$ageExpr = "(CASE
				WHEN p.dob IS NOT NULL AND p.dob != '0000-00-00' THEN TIMESTAMPDIFF(YEAR, p.dob, CURDATE())
				WHEN p.age REGEXP '^[0-9]+$' THEN CAST(p.age AS UNSIGNED)
				ELSE NULL
			END)";

			if ($advAgeMode === 'between' && $advAgeFrom !== '' && $advAgeTo !== '') {
				$from = (int) $advAgeFrom;
				$to = (int) $advAgeTo;
				if ($from > $to) {
					[$from, $to] = [$to, $from];
				}
				$advancedClause = " and {$ageExpr} between {$from} and {$to}";
				$hasAdvancedFilters = true;
			} elseif ($advAgeMode === 'approx' && $advAgeValue !== '') {
				$age = (int) $advAgeValue;
				$min = max(0, $age - $advAgeToleranceInt);
				$max = $age + $advAgeToleranceInt;
				$advancedClause = " and {$ageExpr} between {$min} and {$max}";
				$hasAdvancedFilters = true;
			}
		} elseif ($advSearchValue !== '') {
			$escapedLike = $this->db->escape('%' . $advSearchValue . '%');
			$escapedExact = $this->db->escape($advSearchValue);

			switch ($advSearchBy) {
				case 'patient_uhid':
					$advancedClause = " and (p.p_code LIKE {$escapedLike}";
					if (ctype_digit($advSearchValue)) {
						$advancedClause .= ' or p.id = ' . (int) $advSearchValue;
					}
					$advancedClause .= ')';
					$hasAdvancedFilters = true;
					break;
				case 'patient_name':
					$advancedClause = " and p.p_fname LIKE {$escapedLike}";
					$hasAdvancedFilters = true;
					break;
				case 'relative_name':
					$advancedClause = " and p.p_rname LIKE {$escapedLike}";
					$hasAdvancedFilters = true;
					break;
				case 'phone':
					$advancedClause = " and (p.mphone1 = {$escapedExact} or p.mphone1 LIKE {$escapedLike})";
					$hasAdvancedFilters = true;
					break;
				case 'refer_by':
					if (in_array('referby', $pmFields, true)) {
						$advancedClause = " and p.referby LIKE {$escapedLike}";
						$hasAdvancedFilters = true;
					}
					break;
				case 'old_uhid':
					$oldUhidClause = $this->buildOldUhidSearchClause($advSearchValue, $oldUhidField);
					if ($oldUhidClause !== '') {
						$advancedClause = ' and (' . $oldUhidClause . ')';
						$hasAdvancedFilters = true;
					}
					break;
			}
		}

		// Build WHERE clause based on search query
		$searchString = ' 1=1 ';
		if (strlen($sdata) > 0) {
			$sdateArray = explode(' ', $sdata);

			foreach ($sdateArray as $rowData) {
				if ($rowData === '') {
					continue;
				}

				if (is_numeric($rowData)) {
					$searchString .= $this->buildPatientSearchCondition($rowData, $abhaField, $oldUhidField);
				} elseif (ctype_alpha($rowData)) {
					$escapedValue = $this->db->escape($rowData);
					$legacyUhidClause = '';
					$oldUhidClause = $this->buildOldUhidSearchClause($rowData, $oldUhidField);
					if ($oldUhidClause !== '') {
						$legacyUhidClause = ' or (' . $oldUhidClause . ')';
					}
					$searchString .= " and (p.p_fname like " . $this->db->escape('%' . $rowData . '%') . " 
						or p.email1 = " . $this->db->escape($rowData) . " 
						or SUBSTRING_INDEX(p.p_fname,' ',1) sounds like " . $this->db->escape($rowData) . $legacyUhidClause . ")";
				} else {
					// Handle dashed ABHA format: XX-XXXX-XXXX-XXXX
					$rawDigits = preg_replace('/\D/', '', $rowData);
					$abhaElse  = ($abhaField && strlen($rawDigits) === 14)
						? " or p.{$abhaField} = " . $this->db->escape($rawDigits) . " or p.{$abhaField} = " . $this->db->escape($rowData)
						: '';
					$legacyUhidClause = '';
					$oldUhidClause = $this->buildOldUhidSearchClause($rowData, $oldUhidField);
					if ($oldUhidClause !== '') {
						$legacyUhidClause = ' or (' . $oldUhidClause . ')';
					}
					$searchString .= " and (p.p_code like " . $this->db->escape($rowData) . " 
						or p.email1 = " . $this->db->escape($rowData) . $abhaElse . $legacyUhidClause . ")";
				}
			}
		}

		$searchString .= $advancedClause;

		$useLatestThirtyOnly = (strlen($sdata) === 0 && ! $hasAdvancedFilters);
		if ($useLatestThirtyOnly) {
			$searchString .= " and (
				(
					p.last_visit IS NOT NULL
					AND p.last_visit != '0000-00-00'
					AND DATE(p.last_visit) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
				)
				OR
				(
					p.insert_date IS NOT NULL
					AND p.insert_date != '0000-00-00'
					AND DATE(p.insert_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
				)
			)";
		}

		// Count total records (without filtering)
		$totalSql = "SELECT COUNT(DISTINCT p.id) as total FROM patient_master p";
		$totalData = (int) $this->db->query($totalSql)->getRow()->total;

		// Count filtered records
		$filteredSql = "SELECT COUNT(DISTINCT p.id) as total FROM patient_master p WHERE $searchString";
		$totalFiltered = (int) $this->db->query($filteredSql)->getRow()->total;

		// Get pagination and sorting parameters
		$start = isset($request['start']) ? (int) $request['start'] : 0;
		$length = isset($request['length']) ? (int) $request['length'] : 10;
		
		// Columns for ordering
		$columns = ['', 'p.p_code', 'p.p_fname', 'p.age', 'p.last_visit', 'p.insurance_id', ''];
		$orderColumn = 'p.last_visit';
		$orderDir = 'DESC';
		
		if (!empty($request['order'][0]['column']) && is_numeric($request['order'][0]['column'])) {
			$orderIndex = (int) $request['order'][0]['column'];
			if (isset($columns[$orderIndex]) && $columns[$orderIndex] !== '') {
				$orderColumn = $columns[$orderIndex];
			}
			$orderDir = strtoupper($request['order'][0]['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
		}

		// Get data
		$sql = "SELECT p.*, 
				Date_Format(p.last_visit,'%d-%m-%Y') AS Last_Visit,
				IF(p.insurance_id = 0 OR p.insurance_id IS NULL, 'Self', 'Insuranced') as insurance_status
				FROM patient_master p 
				WHERE $searchString
				GROUP BY p.id 
				ORDER BY $orderColumn $orderDir
				LIMIT $start, $length";

		$records = $this->db->query($sql)->getResult();

		// Format data for DataTables
		$data = [];
		foreach ($records as $index => $row) {
			$patientId = (int) ($row->id ?? 0);
			$age = esc(get_age_1($row->dob ?? null, $row->age ?? '', $row->age_in_month ?? '', $row->estimate_dob ?? '', $row->Last_Visit ?? null));
			$oldUhid = '';
			$patientNameCell = esc(($row->p_fname ?? '') . ' {' . ($row->p_rname ?? '') . '}');
			$referBy = trim((string) ($row->referby ?? ''));
			if ($referBy !== '') {
				$patientNameCell .= '<br><small><i>Refer By: ' . esc(ucwords(strtolower($referBy))) . '</i></small>';
			}
			if ($oldUhidField !== null && $oldUhidField !== '' && isset($row->{$oldUhidField})) {
				$oldUhid = (string) $row->{$oldUhidField};
			}
			
			$data[] = [
				$start + $index + 1,
				$this->formatPatientCodeCell($patientId, (string) ($row->p_code ?? ''), $oldUhid),
				$patientNameCell,
				$age,
				esc($row->Last_Visit ?? ''),
				esc($row->insurance_status ?? 'Self'),
				'<a href="javascript:load_form(\'' . base_url('billing/patient/show_profile_opd/' . $patientId . '/1') . '\');" class="btn btn-info btn-xs"><span class="fa fa-history"></span> Patient History</a>'
			];
		}

		return $this->response->setJSON([
			'draw' => isset($request['draw']) ? (int) $request['draw'] : 0,
			'recordsTotal' => $totalData,
			'recordsFiltered' => $totalFiltered,
			'data' => $data
		]);
	}

	public function person_record(int $pno, int $edit = 0)
	{
		$sql = "select *,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,
		if(date_add(insert_date,interval 24 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=" . $pno;
		$query = $this->db->query($sql);
		$data['data'] = $query->getResult();

		if (count($data['data']) === 0) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

		$required_age = $data['data'][0]->age;
		$required_age_in_month = $data['data'][0]->age_in_month;
		$required_dob = $data['data'][0]->dob;
		$estimate_dob = $data['data'][0]->estimate_dob;

		if ($required_age == '' && $estimate_dob == 1) {
			if ($required_age == '0') {
				if ($required_age_in_month == 0 || $required_age_in_month == '') {
					$edit = 1;
				}
			}
		}

		if ($required_dob == '' && $estimate_dob == 0) {
			$edit = 1;
		}

		$sql = "select o.opd_id,o.opd_code,o.doc_name,o.apointment_date,o.p_id,
		p.p_fname,if(o.apointment_date=curdate(),1,0) as new_opd,
		date_format(o.apointment_date,'%d-%m-%Y') as str_apointment_date
		from opd_master o join patient_master p on o.p_id=p.id
		where o.p_id=$pno
		order by o.opd_id desc
		limit 200";
		$query = $this->db->query($sql);
		$data['opd_List'] = $query->getResult();

		$sql = "select m.id,m.invoice_code,Date_Format(m.inv_date,'%d-%m-%y') as str_inv_date,m.inv_name,
				m.attach_id,m.insurance_card_id,
				if(count(t.id)>0,group_concat(t.item_name SEPARATOR ' / '),'No-Item') as Item_List, m.net_amount,m.invoice_status
				from invoice_master m left join invoice_item t on m.id=t.inv_master_id
				where attach_type=0 and attach_id=$pno
				group by m.id
				order by m.id desc
				limit 200";
		$query = $this->db->query($sql);
		$data['invoice_list'] = $query->getResult();

		$sql = "select i.*,m.ins_company_name,m.opd_allowed,m.charge_cash 
		from hc_insurance_card i join hc_insurance m on i.insurance_id=m.id 
		where   i.p_id=$pno";
		$query = $this->db->query($sql);
		$data['data_insurance_card'] = $query->getResult();

		$sql = "select *,Date_Format(date_registration,'%d-%m-%Y') as str_date_registration 
			from  organization_case_master  where status=0 and case_type=0 and p_id=$pno";
		$query = $this->db->query($sql);
		$data['case_master_opd'] = $query->getResult();

		$sql = "SELECT o.*,DATE_FORMAT(o.date_registration,'%d-%m-%Y') as str_date_registration ,
			i.ipd_code,i.register_date
			from  organization_case_master  o left JOIN ipd_master i ON o.ipd_id=i.id
			WHERE o.status=0 AND o.case_type=1 and o.p_id=$pno";
		$query = $this->db->query($sql);
		$data['case_master_ipd'] = $query->getResult();

		$sql = "select * from  file_upload_data  where id=" . $data['data'][0]->profile_file_id;
		$query = $this->db->query($sql);
		$file_data = $query->getResult();

		$profile_file_path = '/assets/images/no_image.svg';
        $profile_picture_path = '';
		$abha_profile_photo_base64 = '';

        if ($this->db->fieldExists('profile_picture', 'patient_master')) {
            $profile_picture_path = (string) ($data['data'][0]->profile_picture ?? '');
        }
		if ($this->db->fieldExists('abha_profile_photo_base64', 'patient_master')) {
			$abha_profile_photo_base64 = trim((string) ($data['data'][0]->abha_profile_photo_base64 ?? ''));
		}

		$sql = "SELECT * from  blood_group order by id";
		$query = $this->db->query($sql);
		$data['blood_group'] = $query->getResult();

		$data['refer_master'] = $this->getActiveReferMasters();

		$sql = "select * from tag_master  Order by tag_name";
		$query = $this->db->query($sql);
		$data['tag_master'] = $query->getResult();

		$sql = "SELECT a.*,t.tag_name,t.tag_type_id
				FROM patient_tag_assign a JOIN tag_master t ON a.tag_id=t.id
				WHERE isdelete=0 and a.p_id=$pno";
		$query = $this->db->query($sql);
		$data['patient_tag_list'] = $query->getResult();

		if (count($file_data) > 0) {
			$pos = strpos($file_data[0]->full_path, '/uploads/', 1);
			if ($pos !== false) {
				$profile_file_path = substr($file_data[0]->full_path, $pos);
			} elseif (!empty($file_data[0]->full_path)) {
				$profile_file_path = $file_data[0]->full_path;
			}
		} elseif ($profile_picture_path !== '') {
            $pos = strpos($profile_picture_path, '/uploads/', 1);
            if ($pos !== false) {
                $profile_file_path = substr($profile_picture_path, $pos);
            } else {
                $profile_file_path = $profile_picture_path;
            }
		} elseif ($abha_profile_photo_base64 !== '') {
			$profile_file_path = str_starts_with($abha_profile_photo_base64, 'data:image')
				? $abha_profile_photo_base64
				: 'data:image/jpeg;base64,' . $abha_profile_photo_base64;
        }

		$data['profile_file_path'] = $profile_file_path;

		if ($edit == 0) {
			return view('billing/Person_profile_V', $data);
		}

		return view('billing/Person_Edit_V', $data);
	}

	public function update()
	{
		if (!$this->request->isAJAX()) {
			return $this->response
				->setStatusCode(400)
				->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
		}

		$chk_age = $this->request->getPost('chk_age');
		$pid = (int) $this->request->getPost('p_id');
		$abhaIdInput = trim((string) $this->request->getPost('input_abha_id'));
		$abhaAddressInput = trim((string) $this->request->getPost('input_abha_address'));
		[$abhaId, $abhaAddress, $abhaError] = $this->normalizeAbhaInputs($abhaIdInput, $abhaAddressInput);
		$age_month = (string) $this->request->getPost('input_age_month');
		$age_year = (string) $this->request->getPost('input_age_year');
		$patientModel = new PatientModel();
		$existingPatient = $pid > 0 ? $patientModel->find($pid) : null;
		$isAbhaVerifiedLocked = is_array($existingPatient)
			&& trim((string) ($existingPatient['abha_id'] ?? $existingPatient['abha_no'] ?? $existingPatient['abha'] ?? $existingPatient['abha_address'] ?? '')) !== ''
			&& strtoupper(trim((string) ($existingPatient['abha_verified_status'] ?? ''))) === 'VERIFIED';

		$rules = [
			'input_name' => 'required|min_length[1]|max_length[30]',
		];

		if ($chk_age === 'on') {
			$estimate_dob = 1;
			if ($age_year === '' && $age_month === '') {
				$rules['input_age_year'] = 'required|min_length[1]|max_length[4]';
			}
		} else {
			$estimate_dob = 0;
			$rules['datepicker_dob'] = 'required|min_length[1]|max_length[10]';
		}

		$validation = service('validation');
		$validation->setRules($rules);
		if (!$validation->withRequest($this->request)->run()) {
			$errorText = implode("\n", $validation->getErrors());
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => $errorText,
			]);
		}

		if ($abhaError !== null) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => $abhaError,
			]);
		}

		$referByName = trim((string) $this->request->getPost('refer_by_name'));
		$existingReferBy = is_array($existingPatient) ? (string) ($existingPatient['referby'] ?? '') : null;
		$referByError = $this->validatePatientReferByInput($referByName, $existingReferBy);
		if ($referByError !== null) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => $referByError,
			]);
		}

		$data = [
			'mphone1' => $this->request->getPost('input_mphone1'),
			'p_fname' => strtoupper((string) $this->request->getPost('input_name')),
			'gender' => $this->request->getPost('optionsRadios_gender'),
			'zip' => $this->request->getPost('input_zip'),
			'add1' => strtoupper((string) $this->request->getPost('input_address')),
			'city' => strtoupper((string) $this->request->getPost('input_city')),
			'district' => strtoupper((string) $this->request->getPost('input_district')),
			'state' => strtoupper((string) $this->request->getPost('input_state')),
			'title' => $this->request->getPost('cbo_title'),
			'p_relative' => strtoupper((string) $this->request->getPost('cbo_relation')),
			'p_rname' => strtoupper((string) $this->request->getPost('input_relative_name')),
			'email1' => strtoupper((string) $this->request->getPost('input_email')),
			'udai' => strtoupper((string) $this->request->getPost('input_Aadhar')),
			'estimate_dob' => $estimate_dob,
			'blood_group' => $this->request->getPost('input_blood_group'),
		];
		$this->applyPatientReferbyField($data, $referByName);

		if ($isAbhaVerifiedLocked) {
			$data['p_fname'] = (string) ($existingPatient['p_fname'] ?? $data['p_fname']);
			$data['gender'] = (string) ($existingPatient['gender'] ?? $data['gender']);
			$data['dob'] = $existingPatient['dob'] ?? ($data['dob'] ?? null);
			if (array_key_exists('age', $existingPatient)) {
				$data['age'] = $existingPatient['age'];
			}
			if (array_key_exists('age_in_month', $existingPatient)) {
				$data['age_in_month'] = $existingPatient['age_in_month'];
			}
			if (array_key_exists('estimate_dob', $existingPatient)) {
				$data['estimate_dob'] = $existingPatient['estimate_dob'];
			}
			$abhaId = trim((string) ($existingPatient['abha_id'] ?? $existingPatient['abha_no'] ?? $existingPatient['abha'] ?? $existingPatient['abha_address'] ?? $abhaId));
		}

		$this->applyPatientAbhaFieldValues($data, $abhaId, $abhaAddress);

		if ($chk_age === 'on') {
			$data['age'] = $age_year;
			$data['age_in_month'] = $age_month;
		} else {
			$data['dob'] = $this->parseDate($this->request->getPost('datepicker_dob'));
		}

		$patientModel->updatePatient($data, $pid);
		$this->saveNamesToNameList([
			(string) $this->request->getPost('input_name'),
			(string) $this->request->getPost('input_relative_name'),
		]);

		$this->enqueuePatientAbhaSync($pid, $data, 'patient.updated');

		return $this->response->setJSON([
			'update' => 1,
			'showcontent' => 'Data Saved successfully',
		]);
	}

	private function getActiveReferMasters(): array
	{
		if (! $this->db->tableExists('refer_master')) {
			return [];
		}

		return $this->db->table('refer_master')
			->where('active', 1)
			->orderBy('f_name', 'ASC')
			->get()
			->getResult();
	}

	private function applyPatientReferbyField(array &$data, string $referByName): void
	{
		if (! $this->db->fieldExists('referby', 'patient_master')) {
			return;
		}

		$data['referby'] = trim(strtoupper($referByName));
	}

	private function validatePatientReferByInput(string $referByName, ?string $existingReferBy = null): ?string
	{
		$referByName = trim($referByName);
		if ($referByName === '') {
			return null;
		}

		$normalize = static fn (string $v): string => strtoupper(trim(preg_replace('/\s+/', ' ', $v)));
		$typed = $normalize($referByName);
		if ($typed === '') {
			return null;
		}

		if ($existingReferBy !== null && $normalize($existingReferBy) === $typed) {
			return null;
		}

		$activeRefs = $this->getActiveReferMasters();
		foreach ($activeRefs as $row) {
			$label = trim((string) (($row->title ?? '') . ' ' . ($row->f_name ?? '')));
			if ($label !== '' && $normalize($label) === $typed) {
				return null;
			}
		}

		return 'Refer By must be selected from Refer Master list.';
	}

	public function update_aadhar()
	{
		if (!$this->request->isAJAX()) {
			return $this->response
				->setStatusCode(400)
				->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
		}

		$pid = (int) $this->request->getPost('p_id');
		$udai = (string) $this->request->getPost('udai');

		$patientModel = new PatientModel();
		$patientModel->updatePatient(['udai' => $udai], $pid);

		return $this->response->setJSON([
			'update' => 1,
			'showcontent' => 'Data Saved successfully',
		]);
	}

	public function update_abha()
	{
		if (!$this->request->isAJAX()) {
			return $this->response
				->setStatusCode(400)
				->setJSON(['update' => 0, 'error_text' => 'Invalid request']);
		}

		$pid      = (int) $this->request->getPost('p_id');
		$abhaIdInput = trim((string) $this->request->getPost('abha_id'));
		$abhaAddressInput = trim((string) $this->request->getPost('abha_address'));
		[$abhaId, $abhaAddress, $abhaError] = $this->normalizeAbhaInputs($abhaIdInput, $abhaAddressInput);
		$verified = (int) $this->request->getPost('verified'); // 1 = came from OTP flow

		if ($abhaError !== null) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => $abhaError,
			]);
		}

		$data = [];
		$this->applyPatientAbhaFieldValues($data, $abhaId, $abhaAddress);
		if ($data === []) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => 'ABHA field not found in patient master.',
			]);
		}

		// ABHA number must be unique across patient_master — block if another
		// patient already carries this exact ABHA id.
		if ($abhaId !== '') {
			$abhaField = $this->resolvePatientAbhaIdField();
			if ($abhaField !== null) {
				$conflict = $this->db->table('patient_master')
					->select('id,p_code,p_fname')
					->where($abhaField, $abhaId)
					->where('id !=', $pid)
					->get()->getRowArray();
				if ($conflict) {
					return $this->response->setJSON([
						'update' => 0,
						'error_text' => 'This ABHA number is already linked to patient '
							. ($conflict['p_code'] ?? '') . ' (' . ($conflict['p_fname'] ?? '') . '). '
							. 'An ABHA number can only be linked to one patient.',
					]);
				}
			}
		}

		// Set abdm_linked_at when ABHA is verified via OTP; clear when manually entered
		$pmFields = $this->db->getFieldNames('patient_master') ?? [];
		if (in_array('abdm_linked_at', $pmFields, true)) {
			$data['abdm_linked_at'] = ($verified === 1 && $abhaId !== '') ? date('Y-m-d H:i:s') : null;
		}

		$patientModel = new PatientModel();
		$patientModel->updatePatient($data, $pid);
		$this->enqueuePatientAbhaSync($pid, $data, 'patient.abha.updated');

		return $this->response->setJSON([
			'update'       => 1,
			'showcontent'  => 'Data Saved successfully',
			'verified'     => $verified === 1 && $abhaId !== '' ? 1 : 0,
		]);
	}

	public function update_card()
	{
		if (!$this->request->isAJAX()) {
			return $this->response
				->setStatusCode(400)
				->setJSON(['insertid' => 0, 'error_text' => 'Invalid request']);
		}

		$rules = [
			'input_insurance_id' => 'required|min_length[2]|max_length[30]',
			'input_card_holder_name' => 'required|min_length[1]|max_length[30]',
		];

		$validation = service('validation');
		$validation->setRules($rules);
		if (!$validation->withRequest($this->request)->run()) {
			$errorText = implode("\n", $validation->getErrors());
			return $this->response->setJSON([
				'insertid' => 0,
				'error_text' => $errorText,
			]);
		}

		$pid = (int) $this->request->getPost('p_id');
		$insCompanyId = (int) $this->request->getPost('Insurance_id');
		$cardId = (int) $this->request->getPost('inscard_id');
		$issueDate = $this->parseDate($this->request->getPost('datepicker_issue_date'));
		$expiryDate = $this->parseDate($this->request->getPost('datepicker_expiry_date'));

		$cardData = [
			'insurance_id' => $insCompanyId,
			'p_id' => $pid,
			'insurance_no' => (string) $this->request->getPost('input_insurance_id'),
			'card_holder_name' => strtoupper((string) $this->request->getPost('input_card_holder_name')),
			'issue_date' => $issueDate,
			'expiry_date' => $expiryDate,
			'relation_patient_cardholder' => strtoupper((string) $this->request->getPost('input_Relation')),
		];

		$patientModel = new PatientModel();
		$insertId = $cardId;
		if ($cardId <= 0) {
			$insertId = $patientModel->insertCard($cardData);
			if ($insertId <= 0) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => 'Unable to save insurance card.',
				]);
			}
		} else {
			$patientModel->updateCard($cardData, $cardId);
		}

		$patientUpdate = [
			'insurance_card_id' => $insertId,
			'insurance_id' => $insCompanyId,
			'insurance_no' => $cardData['insurance_no'],
			'card_holder_name' => $cardData['card_holder_name'],
			'issue_date' => $issueDate,
			'expiry_date' => $expiryDate,
			'relation_patient_cardholder' => $cardData['relation_patient_cardholder'],
		];
		$patientModel->updatePatient($patientUpdate, $pid);

		return $this->response->setJSON([
			'insertid' => $insertId,
		]);
	}

	public function show_cards(int $pno, int $insId = 0)
	{
		$patient = $this->db->table('patient_master')->where('id', $pno)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

		$cards = $this->db->table('hc_insurance_card c')
			->select('c.*, i.ins_company_name')
			->join('hc_insurance i', 'i.id = c.insurance_id', 'left')
			->where('c.p_id', $pno)
			->orderBy('c.id', 'DESC')
			->get()
			->getResult();

		$insList = $this->db->table('hc_insurance')
			->orderBy('ins_company_name', 'ASC')
			->get()
			->getResult();

		return view('billing/Patient_Cards_V', [
			'patient' => $patient,
			'cards' => $cards,
			'insList' => $insList,
			'selectedInsId' => $insId,
		]);
	}

	public function show_profile_image(int $pno, int $edit = 0)
	{
		$patient = $this->db->table('patient_master')->where('id', $pno)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

		$profileFilePath = $this->getProfileFilePath((int) ($patient->profile_file_id ?? 0));
		$hospitalEnabled = hospital_setting_value('ALLOW_IMAGE_PREUPLOAD_EDIT', '0') === '1';
		$user = function_exists('auth') ? auth()->user() : null;
		$userAllowed = $user && method_exists($user, 'can') && $user->can('media.image.preupload-edit');

		return view('billing/Patient_Profile_Image_V', [
			'patient' => $patient,
			'profileFilePath' => $profileFilePath,
			'allow_image_preupload_edit' => ($hospitalEnabled && $userAllowed) ? 1 : 0,
		]);
	}

	public function show_profile_opd(int $pno, int $edit = 0)
	{
		$patient = $this->db->table('patient_master')->where('id', $pno)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

		$profileFilePath = $this->getProfileFilePath((int) ($patient->profile_file_id ?? 0));
		$hospitalEnabled = hospital_setting_value('ALLOW_IMAGE_PREUPLOAD_EDIT', '0') === '1';
		$user = function_exists('auth') ? auth()->user() : null;
		$userAllowed = $user && method_exists($user, 'can') && $user->can('media.image.preupload-edit');

		$request = service('request');
		$backUrl = trim((string) $request->getGet('back_url'));
		$backTitle = trim((string) $request->getGet('back_title'));
		if ($backUrl === '') {
			$backUrl = base_url('billing/patient/person_record') . '/' . $pno . '/0';
		}
		if ($backTitle === '') {
			$backTitle = 'Profile';
		}

		$opdFields = $this->db->getFieldNames('opd_master') ?? [];
		$opdSelect = [
			'opd_id',
			'opd_no',
			'opd_code',
			'doc_id',
			'doc_name',
			'apointment_date',
		];
		$opdSelect[] = in_array('queue_no', $opdFields, true) ? 'queue_no' : "'' AS queue_no";

		$opdList = $this->db->table('opd_master')
			->select(implode(', ', $opdSelect), false)
			->where('p_id', $pno)
			->orderBy('opd_id', 'DESC')
			->get()
			->getResultArray();

		$totalOpdVisits = count($opdList);
		$lastVisitDate = '';
		$lastVisitOpdNo = '';
		if ($totalOpdVisits > 0 && !empty($opdList[0]['apointment_date'])) {
			$lastVisitDate = (string) $opdList[0]['apointment_date'];
		}
		if ($totalOpdVisits > 0) {
			$lastVisitOpdNo = trim((string) (($opdList[0]['opd_no'] ?? '') !== '' ? $opdList[0]['opd_no'] : ($opdList[0]['opd_code'] ?? '')));
		}

		$opdIds = array_column($opdList, 'opd_id');
		$filesByOpd = [];
		$rxByOpd = [];

		if ($opdIds && $this->db->tableExists('file_upload_data')) {
			$fields = $this->db->getFieldNames('file_upload_data') ?? [];
			$builder = $this->db->table('file_upload_data');
			if (in_array('opd_id', $fields, true)) {
				$builder->whereIn('opd_id', $opdIds);
			} elseif (in_array('attach_id', $fields, true)) {
				$builder->whereIn('attach_id', $opdIds);
			}
			if (in_array('show_type', $fields, true)) {
				$builder->where('show_type', 0);
			}

			$rows = $builder->orderBy('id', 'ASC')->get()->getResultArray();
			foreach ($rows as $row) {
				$opdId = (int) ($row['opd_id'] ?? ($row['attach_id'] ?? 0));
				if ($opdId <= 0) {
					continue;
				}

				$path = (string) ($row['full_path'] ?? '');
				if ($path !== '') {
					$pos = strpos($path, '/uploads/', 1);
					if ($pos !== false) {
						$path = substr($path, $pos);
					}
				}

				$ext = strtolower((string) ($row['file_ext'] ?? pathinfo($path, PATHINFO_EXTENSION)));
				$ext = $ext && $ext[0] !== '.' ? '.' . $ext : $ext;
				$isPdf = $ext === '.pdf';

				$filesByOpd[$opdId][] = [
					'path' => $path,
					'isPdf' => $isPdf,
				];
			}
		}

		if ($opdIds && $this->db->tableExists('opd_prescription')) {
			$rxFields = $this->db->getFieldNames('opd_prescription') ?? [];
			$selectParts = [];

			if (in_array('id', $rxFields, true)) {
				$selectParts[] = 'id';
			}
			if (in_array('opd_id', $rxFields, true)) {
				$selectParts[] = 'opd_id';
			}
			foreach (['date_opd_visit', 'queue_no', 'bp', 'diastolic', 'pulse', 'temp', 'spo2', 'complaints', 'diagnosis', 'investigation', 'advice'] as $col) {
				if (in_array($col, $rxFields, true)) {
					$selectParts[] = $col;
				}
			}

			if ($selectParts !== [] && in_array('opd_id', $rxFields, true)) {
				$rxRows = $this->db->table('opd_prescription')
					->select(implode(',', $selectParts), false)
					->whereIn('opd_id', $opdIds)
					->orderBy('id', 'DESC')
					->get()
					->getResultArray();

				foreach ($rxRows as $rxRow) {
					$opdId = (int) ($rxRow['opd_id'] ?? 0);
					if ($opdId <= 0 || isset($rxByOpd[$opdId])) {
						continue;
					}
					$rxByOpd[$opdId] = $rxRow;
				}
			}
		}

		$opdGroups = [];
		foreach ($opdList as $row) {
			$opdId = (int) $row['opd_id'];
			$rx = $rxByOpd[$opdId] ?? null;
			$rxSessionId = (int) (($rx['id'] ?? 0));

			$opdGroups[] = [
				'opd_id' => $opdId,
				'opd_code' => $row['opd_code'] ?? '',
				'doc_name' => $row['doc_name'] ?? '',
				'opd_date' => !empty($row['apointment_date']) ? date('d-m-Y', strtotime($row['apointment_date'])) : '',
				'queue_no' => !empty($row['queue_no']) ? ('Q:' . $row['queue_no']) : '',
				'rx_session_id' => $rxSessionId,
				'rx_date' => !empty($rx['date_opd_visit']) ? date('d-m-Y', strtotime((string) $rx['date_opd_visit'])) : '',
				'rx_queue_no' => !empty($rx['queue_no']) ? ('Q:' . $rx['queue_no']) : '',
				'bp' => (string) ($rx['bp'] ?? ''),
				'diastolic' => (string) ($rx['diastolic'] ?? ''),
				'pulse' => (string) ($rx['pulse'] ?? ''),
				'temp' => (string) ($rx['temp'] ?? ''),
				'spo2' => (string) ($rx['spo2'] ?? ''),
				'complaints' => (string) ($rx['complaints'] ?? ''),
				'diagnosis' => (string) ($rx['diagnosis'] ?? ''),
				'investigation' => (string) ($rx['investigation'] ?? ''),
				'advice' => (string) ($rx['advice'] ?? ''),
				'files' => $filesByOpd[$opdId] ?? [],
			];
		}

		return view('billing/Patient_Profile_Opd_V', [
			'patient' => $patient,
			'profileFilePath' => $profileFilePath,
			'allow_image_preupload_edit' => 1,
			'opdGroups' => $opdGroups,
			'totalOpdVisits' => $totalOpdVisits,
			'lastVisitDate' => $lastVisitDate,
			'lastVisitOpdNo' => $lastVisitOpdNo,
			'backUrl' => $backUrl,
			'backTitle' => $backTitle,
		]);
	}

	public function abdm_documents(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		$lastSync = $this->getLatestAbdmSyncSnapshot((string) ($abhaContext['abha_address'] ?? ''));

		if (! $this->db->tableExists('abdm_hiu_documents')) {
			return $this->response->setJSON([
				'ok' => 1,
				'count' => 0,
				'items' => [],
				'abha' => $abhaContext,
				'last_sync' => $lastSync,
			]);
		}

		$limit = (int) ($this->request->getGet('limit') ?? 100);
		if ($limit <= 0 || $limit > 300) {
			$limit = 100;
		}

		$q = trim((string) ($this->request->getGet('q') ?? ''));
		$filterConsentRequestId = trim((string) ($this->request->getGet('consent_request_id') ?? ''));
		$includeSummary = ((int) ($this->request->getGet('include_summary') ?? 0)) === 1;

		$docFields = $this->db->getFieldNames('abdm_hiu_documents') ?? [];
		$selectCols = ['d.id', 'd.patient_id', 'd.abha_address', 'd.document_title', 'd.document_date', 'd.care_context_reference', 'd.practitioner_name', 'd.organization_name', 'd.bundle_type', 'd.created_at'];
		if (in_array('consent_request_id', $docFields, true)) {
			$selectCols[] = 'd.consent_request_id';
		}
		if ($includeSummary) {
			$selectCols[] = 'd.summary_json';
		}

		$builder = $this->db->table('abdm_hiu_documents d')
			->select(implode(', ', $selectCols))
			->orderBy('d.document_date', 'DESC')
			->orderBy('d.id', 'DESC')
			->limit($limit);

		$builder->groupStart()
			->where('d.patient_id', $pno);
		if ($abhaContext['abha_address'] !== '') {
			$builder->orWhere('d.abha_address', $abhaContext['abha_address']);
		}
		$builder->groupEnd();

		if ($filterConsentRequestId !== '' && in_array('consent_request_id', $docFields, true)) {
			// A single M3 HIU consent request (umbrella consent_request_id) can
			// produce ONE consent artifact PER linked HIP facility, and each
			// fetched document is tagged with its OWN facility-specific artifact
			// id in this column -- never the umbrella request id itself. So a
			// naive exact-match filter here would only ever surface ONE facility
			// (whichever artifact happened to be passed in) and silently hide
			// every sibling facility's documents from the same consent session.
			// Expand the filter to include every known artifact id recorded for
			// this consent_request_id (see abdm_hiu_consent_artifacts), plus the
			// filter value itself, so "Show Data" for a session returns ALL of
			// its facilities' documents (verified 2026-07-30).
			$sessionIds = [$filterConsentRequestId];
			if ($this->db->tableExists('abdm_hiu_consent_artifacts')) {
				$artifactRows = $this->db->table('abdm_hiu_consent_artifacts')
					->select('artifact_id')
					->where('consent_request_id', $filterConsentRequestId)
					->get()
					->getResultArray();
				foreach ($artifactRows as $artifactRow) {
					$artifactId = trim((string) ($artifactRow['artifact_id'] ?? ''));
					if ($artifactId !== '') {
						$sessionIds[] = $artifactId;
					}
				}
			}
			$builder->whereIn('d.consent_request_id', array_values(array_unique($sessionIds)));
		}

		if ($q !== '') {
			$builder->groupStart()
				->like('d.document_title', $q)
				->orLike('d.care_context_reference', $q)
				->orLike('d.practitioner_name', $q)
				->orLike('d.organization_name', $q)
				->groupEnd();
		}

		$rows = $builder->get()->getResultArray();

		if ($includeSummary) {
			foreach ($rows as &$rowRef) {
				$summary = json_decode((string) ($rowRef['summary_json'] ?? ''), true);
				if (! is_array($summary)) {
					$summary = [];
				}
				// Attachments carry base64 image/PDF data — strip them from this
				// list payload to keep it light; use abdm_document_detail for the
				// full attachment content of a single document.
				unset($summary['attachments']);
				$rowRef['summary'] = $summary;
				unset($rowRef['summary_json']);
			}
			unset($rowRef);
		}

		return $this->response->setJSON([
			'ok' => 1,
			'count' => count($rows),
			'items' => $rows,
			'abha' => $abhaContext,
			'last_sync' => $lastSync,
		]);
	}


	public function abdm_document_detail(int $pno, int $docId)
	{
		if ($docId <= 0 || ! $this->db->tableExists('abdm_hiu_documents')) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Document not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);

		$builder = $this->db->table('abdm_hiu_documents d')
			->select('*')
			->where('d.id', $docId)
			->groupStart()
				->where('d.patient_id', $pno);
		if ($abhaContext['abha_address'] !== '') {
			$builder->orWhere('d.abha_address', $abhaContext['abha_address']);
		}
		$builder->groupEnd();

		$row = $builder->get(1)->getRowArray();
		if (! is_array($row) || empty($row)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Document not found for patient',
			]);
		}

		$row['summary'] = json_decode((string) ($row['summary_json'] ?? ''), true) ?: [];

		return $this->response->setJSON([
			'ok' => 1,
			'item' => $row,
		]);
	}

	public function abdm_content_request(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		if ($abhaContext['abha_address'] === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
				'abha' => $abhaContext,
			]);
		}

		if ((int) ($abhaContext['is_verified'] ?? 0) !== 1) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA is not marked as verified in HMS. Please verify ABHA first, then request content.',
				'abha' => $abhaContext,
			]);
		}

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		$payload = [
			'patient_id' => $pno,
			'abha_address' => $abhaContext['abha_address'],
			'hiTypes' => ['OPConsultation', 'Prescription', 'DiagnosticReport', 'HealthDocumentRecord'],
		];

		try {
			$result = $service->runOperation('consent_request', $payload);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'Unable to start content request: ' . $e->getMessage(),
			]);
		}

		$ok = (int) ($result['ok'] ?? 0) === 1;
		$httpCode = (int) ($result['http_code'] ?? ($ok ? 200 : 422));
		if ($httpCode < 100 || $httpCode > 599) {
			$httpCode = $ok ? 200 : 422;
		}

		if (! $ok) {
			return $this->response->setStatusCode($httpCode)->setJSON([
				'ok' => 0,
				'error' => (string) ($result['error_text'] ?? 'Content request failed.'),
				'data' => $result,
			]);
		}

		return $this->response->setStatusCode($httpCode)->setJSON([
			'ok' => 1,
			'message' => 'Consent/content request created successfully.',
			'workflow_state' => 'REQUESTED',
			'request_id' => (string) ($result['request_id'] ?? ''),
			'data' => $result,
		]);
	}

	/**
	 * Manual consent request form submission — lets the user pick specific Health
	 * Information Types and an editable validity date range instead of always
	 * using the hardcoded defaults from M3HiuWorkflowService::buildDefaultConsent().
	 * Returns the same shape as abdm_content_request() so the existing JS auto-flow
	 * polling (runAutoFlowStep) can pick up the request_id and continue tracking
	 * consent status / data fetch to completion.
	 */
	public function abdm_content_request_custom(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		if ($abhaContext['abha_address'] === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
				'abha' => $abhaContext,
			]);
		}

		if ((int) ($abhaContext['is_verified'] ?? 0) !== 1) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA is not marked as verified in HMS. Please verify ABHA first, then request content.',
				'abha' => $abhaContext,
			]);
		}

		$hiTypesInput = $this->request->getPost('hi_types');
		if (! is_array($hiTypesInput)) {
			$decoded = json_decode((string) $hiTypesInput, true);
			$hiTypesInput = is_array($decoded) ? $decoded : [];
		}
		$hiTypes = $this->normalizeHiTypesList($hiTypesInput);
		if ($hiTypes === []) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'Select at least one Health Information Type.',
			]);
		}

		$dateFromInput = trim((string) $this->request->getPost('date_from'));
		$dateToInput = trim((string) $this->request->getPost('date_to'));
		$eraseDateInput = trim((string) $this->request->getPost('erase_date'));

		// Purpose is a fixed ABDM vocabulary, selected from a dropdown (not free
		// text) so it can never be mistyped into an invalid purpose code.
		$purposeTextByCode = [
			'CAREMGT' => 'Care Management',
			'BTG' => 'Break The Glass',
			'PUBHLTH' => 'Public Health',
			'HPAYMT' => 'Healthcare Payment',
			'DSRCH' => 'Disease Specific Healthcare Research',
			'PATRQT' => 'Self Requested',
		];
		$purposeCode = strtoupper(trim((string) $this->request->getPost('purpose_code')));
		if (! array_key_exists($purposeCode, $purposeTextByCode)) {
			$purposeCode = 'CAREMGT';
		}

		$istTz = new \DateTimeZone('Asia/Kolkata');
		$utcTz = new \DateTimeZone('UTC');
		$nowIst = new \DateTimeImmutable('now', $istTz);

		try {
			$fromIst = $dateFromInput !== '' ? new \DateTimeImmutable($dateFromInput, $istTz) : $nowIst->modify('-365 days');
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'Invalid "Date From" date.',
			]);
		}
		try {
			$toIst = $dateToInput !== '' ? new \DateTimeImmutable($dateToInput, $istTz) : $nowIst;
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'Invalid "Date To" date.',
			]);
		}
		if ($fromIst->getTimestamp() >= $toIst->getTimestamp()) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => '"Date From" must be earlier than "Date To".',
			]);
		}

		try {
			$eraseIst = $eraseDateInput !== '' ? new \DateTimeImmutable($eraseDateInput, $istTz) : $nowIst->modify('+1 year');
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'Invalid "Expiry Date" date.',
			]);
		}
		if ($eraseIst->setTime(23, 59, 59)->getTimestamp() < $toIst->getTimestamp()) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => '"Expiry Date" must be on or after "Date To".',
			]);
		}

		$safeNowIst = $nowIst->modify('-120 seconds');
		if ($toIst->getTimestamp() > $safeNowIst->getTimestamp()) {
			$toIst = $safeNowIst;
		}

		$fromUtc = $fromIst->setTime(0, 0, 0)->setTimezone($utcTz)->format('Y-m-d\TH:i:s.000\Z');
		$toUtc = $toIst->setTimezone($utcTz)->format('Y-m-d\TH:i:s.000\Z');
		$eraseAtUtc = $eraseIst->setTime(23, 59, 59)->setTimezone($utcTz)->format('Y-m-d\TH:i:s.000\Z');

		$consent = [
			'purpose' => [
				'code' => $purposeCode,
				'text' => $purposeTextByCode[$purposeCode],
				'refUri' => 'https://abdm.gov.in',
			],
			'patient' => [
				'id' => $abhaContext['abha_address'],
			],
			'hiTypes' => $hiTypes,
			'permission' => [
				'accessMode' => 'VIEW',
				'dateRange' => [
					'from' => $fromUtc,
					'to' => $toUtc,
				],
				'dataEraseAt' => $eraseAtUtc,
				'frequency' => [
					'unit' => 'HOUR',
					'value' => 1,
					'repeats' => 0,
				],
			],
		];

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		$payload = [
			'patient_id' => $pno,
			'abha_address' => $abhaContext['abha_address'],
			'consent' => $consent,
		];

		try {
			$result = $service->runOperation('consent_request', $payload);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'Unable to start content request: ' . $e->getMessage(),
			]);
		}

		$ok = (int) ($result['ok'] ?? 0) === 1;
		$httpCode = (int) ($result['http_code'] ?? ($ok ? 200 : 422));
		if ($httpCode < 100 || $httpCode > 599) {
			$httpCode = $ok ? 200 : 422;
		}

		if (! $ok) {
			return $this->response->setStatusCode($httpCode)->setJSON([
				'ok' => 0,
				'error' => (string) ($result['error_text'] ?? 'Content request failed.'),
				'data' => $result,
			]);
		}

		return $this->response->setStatusCode($httpCode)->setJSON([
			'ok' => 1,
			'message' => 'Consent/content request created successfully.',
			'workflow_state' => 'REQUESTED',
			'request_id' => (string) ($result['request_id'] ?? ''),
			'data' => $result,
		]);
	}

	public function abdm_content_auto_flow(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		if ($abhaContext['abha_address'] === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
				'abha' => $abhaContext,
			]);
		}

		if ((int) ($abhaContext['is_verified'] ?? 0) !== 1) {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA is not marked as verified in HMS. Please verify ABHA first, then request content.',
				'abha' => $abhaContext,
			]);
		}

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		$flowRefId = trim((string) (
			$this->request->getGet('request_id')
			?? $this->request->getPost('request_id')
			?? ''
		));
		$consentRequestRef = '';
		$consentArtifactRef = '';

		$consentResult = null;
		$reconcileResult = null;
		$fetchResult = null;
		$lastSync = $this->getLatestAbdmSyncSnapshot((string) ($abhaContext['abha_address'] ?? ''));
		$resumedFromLastSync = false;

		if ($flowRefId === '') {
			$lastRequestId = trim((string) ($lastSync['request_id'] ?? ''));
			$lastPhase = strtoupper(trim((string) ($lastSync['phase'] ?? '')));
			$lastSyncStale = (bool) ($lastSync['restart_required'] ?? false);
			// Do not resume a stale pending request (older than 1 hour with no bridge
			// callback) — fall through so a fresh consent_request is created instead.
			if ($lastRequestId !== '' && ! $lastSyncStale && in_array($lastPhase, ['REQUESTED', 'PENDING', 'GRANTED'], true)) {
				$flowRefId = $lastRequestId;
				$consentRequestRef = trim((string) ($lastSync['consent_request_id'] ?? ''));
				$consentArtifactRef = trim((string) ($lastSync['consent_id'] ?? ''));
				$resumedFromLastSync = true;
				$consentResult = [
					'ok' => 1,
					'http_code' => 200,
					'request_id' => $flowRefId,
					'workflow_state' => $lastPhase,
					'reused_existing_request' => 1,
					'message' => 'Resuming previous ABDM consent flow from saved status.',
				];
			}
		}

		if ($flowRefId === '') {
			$consentPayload = [
				'patient_id' => $pno,
				'abha_address' => $abhaContext['abha_address'],
				'hiTypes' => ['OPConsultation', 'Prescription', 'DiagnosticReport', 'HealthDocumentRecord'],
			];

			try {
				$consentResult = $service->runOperation('consent_request', $consentPayload);
			} catch (\Throwable $e) {
				return $this->response->setStatusCode(500)->setJSON([
					'ok' => 0,
					'error' => 'Unable to start consent request: ' . $e->getMessage(),
				]);
			}

			if ((int) ($consentResult['ok'] ?? 0) !== 1) {
				$errorText = trim((string) ($consentResult['error_text'] ?? 'Consent request failed.'));
				$isDuplicateConsent = stripos($errorText, 'ABDM-1070') !== false
					|| stripos($errorText, 'Duplicate consent request') !== false;

				if ($isDuplicateConsent) {
					$existingRequestId = $this->findLatestAbdmGatewayRequestId($abhaContext['abha_address']);
					if ($existingRequestId !== '') {
						$consentResult['ok'] = 1;
						$consentResult['http_code'] = 200;
						$consentResult['request_id'] = $existingRequestId;
						$consentResult['workflow_state'] = 'REQUESTED';
						$consentResult['reused_existing_request'] = 1;
						$consentResult['message'] = 'Duplicate consent detected. Reusing latest request for reconciliation.';
					}
				}

				if ((int) ($consentResult['ok'] ?? 0) !== 1) {
					$httpCode = (int) ($consentResult['http_code'] ?? 422);
					if ($httpCode < 100 || $httpCode > 599) {
						$httpCode = 422;
					}

					return $this->response->setStatusCode($httpCode)->setJSON([
						'ok' => 0,
						'phase' => 'REQUEST_FAILED',
						'error' => (string) ($consentResult['error_text'] ?? 'Consent request failed.'),
						'request_id' => (string) ($consentResult['request_id'] ?? ''),
						'data' => $consentResult,
					]);
				}
			}

			$flowRefId = trim((string) (
				$consentResult['consent_request_id']
				?? $consentResult['abdm_consent_request_id']
				?? $consentResult['consent_id']
				?? $consentResult['request_id']
				?? ''
			));
			$consentRequestRef = trim((string) (
				$consentResult['consent_request_id']
				?? $consentResult['abdm_consent_request_id']
				?? ''
			));
			$consentArtifactRef = trim((string) (
				$consentResult['consent_id']
				?? $consentResult['abdm_consent_artifact_id']
				?? ''
			));
			if ($consentArtifactRef === '' && $consentRequestRef !== '' && preg_match('/^REQ-/i', $consentRequestRef) !== 1) {
				$consentArtifactRef = $consentRequestRef;
			}
		}

		if ($flowRefId === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'phase' => 'REQUEST_MISSING',
				'error' => 'Unable to determine consent reference for auto flow.',
			]);
		}

		if ($consentRequestRef === '' && preg_match('/^REQ-HIU-/i', $flowRefId) === 1) {
			$resolvedRequestId = $this->findLatestAbdmGatewayRequestId($abhaContext['abha_address']);
			if ($resolvedRequestId !== '') {
				$flowRefId = $resolvedRequestId;
			}
		}

		// If we already have a granted snapshot with a concrete consent reference,
		// continue directly with fetch instead of re-reconciling a stale request_id.
		if ($resumedFromLastSync && strtoupper(trim((string) ($lastSync['phase'] ?? ''))) === 'GRANTED') {
			if ($consentArtifactRef === '' && $consentRequestRef === '') {
				$consentRequestRef = trim((string) ($lastSync['consent_request_id'] ?? ''));
				$consentArtifactRef = trim((string) ($lastSync['consent_id'] ?? ''));
			}

			$canDirectFetch = ($consentArtifactRef !== '' && preg_match('/^REQ-/i', $consentArtifactRef) !== 1)
				|| ($consentRequestRef !== '' && preg_match('/^REQ-/i', $consentRequestRef) !== 1);

			if ($canDirectFetch) {
				$fetchPayload = [
					'abha_address' => $abhaContext['abha_address'],
				];
				if ($consentArtifactRef !== '' && preg_match('/^REQ-/i', $consentArtifactRef) !== 1) {
					$fetchPayload['consentId'] = $consentArtifactRef;
				} elseif ($consentRequestRef !== '' && preg_match('/^REQ-/i', $consentRequestRef) !== 1) {
					$fetchPayload['consentRequestId'] = $consentRequestRef;
				}

				try {
					$fetchResult = $service->runOperation('data_fetch', $fetchPayload);
				} catch (\Throwable $e) {
					return $this->response->setStatusCode(500)->setJSON([
						'ok' => 0,
						'phase' => 'FETCH_FAILED',
						'error' => 'Unable to fetch ABDM content: ' . $e->getMessage(),
						'request_id' => $flowRefId,
					]);
				}

				if ((int) ($fetchResult['ok'] ?? 0) === 1) {
					return $this->response->setJSON([
						'ok' => 1,
						'phase' => 'COMPLETED',
						'poll_again' => 0,
						'request_id' => $flowRefId,
						'message' => 'Resumed previous granted consent flow and fetched data successfully.',
						'data' => [
							'consent_request' => $consentResult,
							'consent_reconcile' => $reconcileResult,
							'data_fetch' => $fetchResult,
						],
					]);
				}
			}
		}

		$reconcilePayload = [
			'abha_address' => $abhaContext['abha_address'],
		];
		if ($flowRefId !== '') {
			$reconcilePayload['request_id'] = $flowRefId;
		}
		if ($consentRequestRef !== '') {
			$reconcilePayload['consentRequestId'] = $consentRequestRef;
		}

		try {
			$reconcileResult = $service->runOperation('consent_reconcile', $reconcilePayload);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'phase' => 'STATUS_FAILED',
				'error' => 'Unable to reconcile consent status: ' . $e->getMessage(),
				'request_id' => $flowRefId,
			]);
		}

		if ((int) ($reconcileResult['ok'] ?? 0) !== 1) {
			$errorText = trim((string) ($reconcileResult['error_text'] ?? 'Consent status check failed.'));
			$httpCode = (int) ($reconcileResult['http_code'] ?? 422);
			// Bridge uses several different wordings for "this reference doesn't
			// resolve to a session yet" depending on which lookup key was sent
			// (request_id vs consentRequestId vs consentId) -- e.g. "Consent
			// record not found", "NOT_FOUND", or "No HIU sessions found matching
			// the criteria". Treat all of these as non-fatal/still-pending
			// (bridge just hasn't indexed the session under that key yet) rather
			// than surfacing a hard "Auto flow failed" error to the user, which
			// was happening repeatedly because only the first two wordings were
			// recognized. Also accept 400 alongside 404 since the bridge doesn't
			// consistently use 404 for this class of response.
			$isNonFatalPending = in_array($httpCode, [400, 404], true)
				&& (stripos($errorText, 'Consent record not found') !== false
					|| stripos($errorText, 'NOT_FOUND') !== false
					|| stripos($errorText, 'No HIU sessions found') !== false
					|| stripos($errorText, 'no sessions found') !== false
					|| stripos($errorText, 'matching the criteria') !== false);

			if ($isNonFatalPending) {
				$fallbackLookup = [
					'abha_address' => $abhaContext['abha_address'],
				];
				if ($consentRequestRef !== '') {
					$fallbackLookup['consentRequestId'] = $consentRequestRef;
				}
				if ($consentArtifactRef !== '') {
					$fallbackLookup['consentId'] = $consentArtifactRef;
				}
				if ($flowRefId !== '') {
					$fallbackLookup['request_id'] = $flowRefId;
				}

				if ($consentRequestRef !== '' || $consentArtifactRef !== '') {
					try {
						$fallbackReconcile = $service->runOperation('consent_reconcile', $fallbackLookup);
					} catch (\Throwable $e) {
						$fallbackReconcile = [
							'ok' => 0,
							'http_code' => 500,
							'error_text' => 'Unable to reconcile consent status using fallback reference: ' . $e->getMessage(),
						];
					}

					if ((int) ($fallbackReconcile['ok'] ?? 0) === 1) {
						$reconcileResult = $fallbackReconcile;
						$state = $this->deriveAutoFlowState($reconcileResult);
						if ($state === 'GRANTED') {
							$fetchPayload = [
								'abha_address' => $abhaContext['abha_address'],
								'consentId' => $consentArtifactRef,
							];
							if ($fetchPayload['consentId'] === '') {
								unset($fetchPayload['consentId']);
								if ($consentRequestRef !== '') {
									$fetchPayload['consentRequestId'] = $consentRequestRef;
								}
							}

							try {
								$fetchResult = $service->runOperation('data_fetch', $fetchPayload);
							} catch (\Throwable $e) {
								return $this->response->setStatusCode(500)->setJSON([
									'ok' => 0,
									'phase' => 'FETCH_FAILED',
									'error' => 'Unable to fetch ABDM content after fallback reconcile: ' . $e->getMessage(),
									'request_id' => $flowRefId,
								]);
							}

							if ((int) ($fetchResult['ok'] ?? 0) === 1) {
								return $this->response->setJSON([
									'ok' => 1,
									'phase' => 'COMPLETED',
									'poll_again' => 0,
									'request_id' => $flowRefId,
									'message' => 'Consent status was resolved using fallback bridge reference and data fetched successfully.',
									'data' => [
										'consent_request' => $consentResult,
										'consent_reconcile' => $reconcileResult,
										'data_fetch' => $fetchResult,
									],
								]);
							}
						}
					}
				}

				$altConsentRequestRef = trim((string) ($consentRequestRef !== '' ? $consentRequestRef : ($lastSync['consent_request_id'] ?? '')));
				$altConsentArtifactRef = trim((string) ($consentArtifactRef !== '' ? $consentArtifactRef : ($lastSync['consent_id'] ?? '')));
				$canDirectFetch = ($altConsentArtifactRef !== '' && preg_match('/^REQ-/i', $altConsentArtifactRef) !== 1)
					|| ($altConsentRequestRef !== '' && preg_match('/^REQ-/i', $altConsentRequestRef) !== 1);

				if ($canDirectFetch) {
					$fetchPayload = [
						'abha_address' => $abhaContext['abha_address'],
					];
					if ($altConsentArtifactRef !== '' && preg_match('/^REQ-/i', $altConsentArtifactRef) !== 1) {
						$fetchPayload['consentId'] = $altConsentArtifactRef;
					} elseif ($altConsentRequestRef !== '' && preg_match('/^REQ-/i', $altConsentRequestRef) !== 1) {
						$fetchPayload['consentRequestId'] = $altConsentRequestRef;
					}

					try {
						$fetchResult = $service->runOperation('data_fetch', $fetchPayload);
					} catch (\Throwable $e) {
						return $this->response->setStatusCode(500)->setJSON([
							'ok' => 0,
							'phase' => 'FETCH_FAILED',
							'error' => 'Unable to fetch ABDM content: ' . $e->getMessage(),
							'request_id' => $flowRefId,
						]);
					}

					if ((int) ($fetchResult['ok'] ?? 0) === 1) {
						return $this->response->setJSON([
							'ok' => 1,
							'phase' => 'COMPLETED',
							'poll_again' => 0,
							'request_id' => $flowRefId,
							'message' => 'Consent status lookup returned not found for old request id, but data fetch succeeded using saved consent reference.',
							'data' => [
								'consent_request' => $consentResult,
								'consent_reconcile' => $reconcileResult,
								'data_fetch' => $fetchResult,
							],
						]);
					}
				}

				return $this->response->setJSON([
					'ok' => 1,
					'phase' => 'REQUESTED',
					// Keep tracking the same first request; bridge may still be processing callbacks.
					'poll_again' => 1,
					'request_id' => $flowRefId,
					'message' => 'Consent request is still being processed. Waiting for bridge callback update.',
					'data' => [
						'consent_request' => $consentResult,
						'consent_reconcile' => $reconcileResult,
					],
				]);
			}

			if ($httpCode < 100 || $httpCode > 599) {
				$httpCode = 422;
			}

			return $this->response->setStatusCode($httpCode)->setJSON([
				'ok' => 0,
				'phase' => 'STATUS_FAILED',
				'error' => (string) ($reconcileResult['error_text'] ?? 'Consent status check failed.'),
				'request_id' => $flowRefId,
				'data' => [
					'consent_request' => $consentResult,
					'consent_reconcile' => $reconcileResult,
				],
			]);
		}

		$state = $this->deriveAutoFlowState($reconcileResult);
		$pollAgain = in_array($state, ['REQUESTED', 'PENDING'], true);

		if ($state === 'GRANTED') {
			$fetchPayload = [
				'abha_address' => $abhaContext['abha_address'],
			];
			if ($consentArtifactRef !== '') {
				$fetchPayload['consentId'] = $consentArtifactRef;
			} elseif ($consentRequestRef !== '') {
				$fetchPayload['consentRequestId'] = $consentRequestRef;
			} else {
				$fetchPayload['request_id'] = $flowRefId;
			}
			try {
				$fetchResult = $service->runOperation('data_fetch', $fetchPayload);
			} catch (\Throwable $e) {
				return $this->response->setStatusCode(500)->setJSON([
					'ok' => 0,
					'phase' => 'FETCH_FAILED',
					'error' => 'Unable to fetch ABDM content: ' . $e->getMessage(),
					'request_id' => $flowRefId,
				]);
			}

			if ((int) ($fetchResult['ok'] ?? 0) !== 1) {
				$httpCode = (int) ($fetchResult['http_code'] ?? 422);
				if ($httpCode < 100 || $httpCode > 599) {
					$httpCode = 422;
				}

				return $this->response->setStatusCode($httpCode)->setJSON([
					'ok' => 0,
					'phase' => 'FETCH_FAILED',
					'error' => (string) ($fetchResult['error_text'] ?? 'Data fetch failed.'),
					'request_id' => $flowRefId,
					'data' => [
						'consent_request' => $consentResult,
						'consent_reconcile' => $reconcileResult,
						'data_fetch' => $fetchResult,
					],
				]);
			}

			$state = 'COMPLETED';
			$pollAgain = false;
		}

		$message = match ($state) {
			'COMPLETED' => 'Data fetch completed and documents should now be available.',
			'GRANTED' => 'Consent granted. Proceeding to fetch data.',
			'DENIED' => 'Consent denied/revoked/expired by patient.',
			default => 'Consent request created. Waiting for patient approval.',
		};

		return $this->response->setJSON([
			'ok' => 1,
			'phase' => $state,
			'poll_again' => $pollAgain ? 1 : 0,
			'request_id' => $flowRefId,
			'message' => $message,
			'data' => [
				'consent_request' => $consentResult,
				'consent_reconcile' => $reconcileResult,
				'data_fetch' => $fetchResult,
			],
		]);
	}

	/**
	 * Fetch-only action for a patient whose consent is already GRANTED.
	 * Unlike abdm_content_auto_flow(), this does NOT create a new consent
	 * request and does NOT re-run consent reconcile polling — it directly
	 * pulls health data using the already-saved consent reference. This
	 * avoids spamming the bridge with duplicate consent_request/reconcile
	 * calls just to re-fetch already-granted records.
	 */
	public function abdm_content_fetch_only(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		if ($abhaContext['abha_address'] === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
			]);
		}

		// Optional overrides let the "Fetch Records" action in the Consent Request
		// History list target a SPECIFIC past session's consent reference, instead
		// of always re-pulling using the globally-latest granted consent.
		$overrideConsentId = trim((string) (
			$this->request->getGet('consent_id')
			?? $this->request->getPost('consent_id')
			?? ''
		));
		$overrideConsentRequestId = trim((string) (
			$this->request->getGet('consent_request_id')
			?? $this->request->getPost('consent_request_id')
			?? ''
		));

		if ($overrideConsentId !== '' || $overrideConsentRequestId !== '') {
			$phase = 'GRANTED';
			$consentArtifactRef = $overrideConsentId;
			$consentRequestRef = $overrideConsentRequestId;
		} else {
			$lastSync = $this->getLatestAbdmSyncSnapshot((string) ($abhaContext['abha_address'] ?? ''));
			$phase = strtoupper(trim((string) ($lastSync['phase'] ?? '')));
			if (! in_array($phase, ['GRANTED', 'COMPLETED'], true)) {
				return $this->response->setStatusCode(422)->setJSON([
					'ok' => 0,
					'phase' => $phase !== '' ? $phase : 'IDLE',
					'error' => 'Consent is not granted yet. Use "+ New Request" to send/track a consent request first.',
				]);
			}

			$consentRequestRef = trim((string) ($lastSync['consent_request_id'] ?? ''));
			$consentArtifactRef = trim((string) ($lastSync['consent_id'] ?? ''));
		}

		if ($consentArtifactRef === '' && $consentRequestRef === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'phase' => $phase,
				'error' => 'No saved consent reference found. Use "+ New Request" to re-establish consent.',
			]);
		}

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		$fetchPayload = [
			'abha_address' => $abhaContext['abha_address'],
		];
		if ($consentArtifactRef !== '') {
			$fetchPayload['consentId'] = $consentArtifactRef;
		}
		// Always include the request id too (when known), purely so any documents
		// persisted from this fetch can record which consent request they came
		// from. This does not change the actual bridge query for Method 2, which
		// is driven only by consentId when both are present.
		if ($consentRequestRef !== '') {
			$fetchPayload['consentRequestId'] = $consentRequestRef;
		}

		try {
			$fetchResult = $service->runOperation('data_fetch', $fetchPayload);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'phase' => 'FETCH_FAILED',
				'error' => 'Unable to fetch ABDM content: ' . $e->getMessage(),
			]);
		}

		if ((int) ($fetchResult['ok'] ?? 0) !== 1) {
			$httpCode = (int) ($fetchResult['http_code'] ?? 422);
			if ($httpCode < 100 || $httpCode > 599) {
				$httpCode = 422;
			}

			return $this->response->setStatusCode($httpCode)->setJSON([
				'ok' => 0,
				'phase' => 'FETCH_FAILED',
				'error' => (string) ($fetchResult['error_text'] ?? 'Data fetch failed.'),
				'data' => ['data_fetch' => $fetchResult],
			]);
		}

		// `$fetchResult['ok'] === 1` only means the API call to the bridge
		// succeeded (no HTTP/transport error) — it does NOT mean the HIP has
		// actually sent any health records yet (the bridge may legitimately
		// respond with an empty decrypted_data set while still processing).
		// Only report success to the user once documents were actually saved;
		// otherwise say plainly that the fetch is still pending.
		$documentsPersisted = (int) ($fetchResult['documents_persisted'] ?? 0);
		$documentsUpdated = (int) ($fetchResult['documents_updated'] ?? 0);

		// The consent-artifact-scoped fetch above (bridge "Method 2") may resolve
		// to a stale/different session sharing the same consent artifact id. Per
		// the bridge gateway team, GET /v1/hiu/data/fetch?request_id=... ("Method
		// 1") targets the ONE specific consent request instead -- this is the
		// "self"/current session's own data. Try it before falling back to the
		// broader by-ABHA-address historical lookup (Method 3).
		if ($documentsPersisted + $documentsUpdated === 0 && $consentRequestRef !== '') {
			try {
				$requestIdFetchResult = $service->runOperation('data_fetch', [
					'abha_address' => $abhaContext['abha_address'],
					'consentRequestId' => $consentRequestRef,
					'fetch_by_request_id' => 1,
				]);
			} catch (\Throwable $e) {
				$requestIdFetchResult = null;
			}

			if (is_array($requestIdFetchResult) && (int) ($requestIdFetchResult['ok'] ?? 0) === 1) {
				$reqDocumentsPersisted = (int) ($requestIdFetchResult['documents_persisted'] ?? 0);
				$reqDocumentsUpdated = (int) ($requestIdFetchResult['documents_updated'] ?? 0);
				if ($reqDocumentsPersisted + $reqDocumentsUpdated > 0) {
					return $this->response->setJSON([
						'ok' => 1,
						'phase' => 'COMPLETED',
						'message' => 'Records fetched successfully using existing granted consent.',
						'documents_persisted' => $reqDocumentsPersisted,
						'documents_updated' => $reqDocumentsUpdated,
						'data' => ['data_fetch' => $fetchResult, 'data_fetch_by_request_id' => $requestIdFetchResult],
					]);
				}
			}
		}

		// The consent-artifact-scoped fetch above (bridge "Method 1/2") only
		// returns data for the ONE consent/request we targeted. Per the bridge
		// gateway team, GET /v1/hiu/data/fetch?abha_address=... ("Method 3")
		// separately returns ALL historical decrypted records already available
		// at the bridge across every past consent for this patient. If the
		// consent-scoped call above found nothing new, fall back to this
		// broader by-ABHA-address lookup before telling the user there is
		// truly nothing to fetch yet.
		if ($documentsPersisted + $documentsUpdated === 0) {
			try {
				$abhaFetchResult = $service->runOperation('data_fetch', [
					'abha_address' => $abhaContext['abha_address'],
					'fetch_by_abha_address' => 1,
				]);
			} catch (\Throwable $e) {
				$abhaFetchResult = null;
			}

			if (is_array($abhaFetchResult) && (int) ($abhaFetchResult['ok'] ?? 0) === 1) {
				$abhaDocumentsPersisted = (int) ($abhaFetchResult['documents_persisted'] ?? 0);
				$abhaDocumentsUpdated = (int) ($abhaFetchResult['documents_updated'] ?? 0);
				if ($abhaDocumentsPersisted + $abhaDocumentsUpdated > 0) {
					return $this->response->setJSON([
						'ok' => 1,
						'phase' => 'COMPLETED',
						'message' => 'Records fetched successfully from historical ABDM records for this ABHA address.',
						'documents_persisted' => $abhaDocumentsPersisted,
						'documents_updated' => $abhaDocumentsUpdated,
						'data' => ['data_fetch' => $fetchResult, 'data_fetch_by_abha_address' => $abhaFetchResult],
					]);
				}
			}
		}

		if ($documentsPersisted + $documentsUpdated === 0) {
			return $this->response->setJSON([
				'ok' => 1,
				'phase' => 'GRANTED',
				'message' => 'Consent is granted, but the health information provider has not sent any records yet. Please try again in a few minutes.',
				'documents_persisted' => 0,
				'documents_updated' => 0,
				'data' => ['data_fetch' => $fetchResult],
			]);
		}

		return $this->response->setJSON([
			'ok' => 1,
			'phase' => 'COMPLETED',
			'message' => 'Records fetched successfully using existing granted consent.',
			'documents_persisted' => $documentsPersisted,
			'documents_updated' => $documentsUpdated,
			'data' => ['data_fetch' => $fetchResult],
		]);
	}

	/**
	 * Consent detail breakdown for the "View Consent" modal — shows the requested vs
	 * granted Health Information Types for the current consent artifact, plus the
	 * Requested -> Granted -> Revoked/Expired lifecycle timestamps for each type.
	 */
	public function abdm_consent_detail(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		$abhaAddress = trim((string) ($abhaContext['abha_address'] ?? ''));
		if ($abhaAddress === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
			]);
		}

		$detail = $this->getAbdmConsentArtifactDetail($abhaAddress);
		if ((int) ($detail['ok'] ?? 0) !== 1) {
			return $this->response->setStatusCode(404)->setJSON($detail);
		}

		return $this->response->setJSON($detail);
	}

	/**
	 * Full history of this patient's ABDM consent requests (one entry per distinct
	 * consent request "session"), most recent first — used by the "Consent Request
	 * History" table on the OPD ABDM tab.
	 */
	public function abdm_consent_requests(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		$abhaAddress = trim((string) ($abhaContext['abha_address'] ?? ''));
		if ($abhaAddress === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
			]);
		}

		return $this->response->setJSON($this->getAbdmConsentRequestsList($abhaAddress));
	}

	/**
	 * Live status check — actively calls the ABDM bridge's
	 * GET /v1/hiu/consent/status (via M3HiuWorkflowService's consent_reconcile
	 * operation) for this patient's most recent consent request instead of
	 * only re-reading whatever was last saved in abdm_hiu_workflows. Needed
	 * because HMS otherwise only learns about a GRANTED consent when a cron
	 * (`php spark abdm:hiu-poll`) or a webhook callback updates the DB — if
	 * neither has run yet, the UI can keep showing a stale REQUESTED status
	 * even though the bridge/ABDM sandbox already shows GRANTED. If the
	 * reconcile confirms GRANTED, this also cascades into fetching decrypted
	 * data for EVERY known consent artifact under this consent request (one
	 * per linked HIP facility) via M3HiuWorkflowService::fetchAllArtifactsAfterGrant(),
	 * so a manual click surfaces ALL facilities' records instead of just one.
	 */
	public function abdm_check_live_status(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		$abhaAddress = trim((string) ($abhaContext['abha_address'] ?? ''));
		if ($abhaAddress === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'ABHA Address not available for this patient.',
			]);
		}

		$snapshot = $this->getLatestAbdmSyncSnapshot($abhaAddress);
		$requestId = trim((string) ($snapshot['request_id'] ?? ''));
		$consentRequestId = trim((string) ($snapshot['consent_request_id'] ?? ''));
		$consentId = trim((string) ($snapshot['consent_id'] ?? ''));
		if ($requestId === '' && $consentRequestId === '' && $consentId === '') {
			return $this->response->setStatusCode(422)->setJSON([
				'ok' => 0,
				'error' => 'No consent request found for this patient to check.',
			]);
		}

		$reconcilePayload = [
			'abha_address' => $abhaAddress,
			'request_id' => $requestId,
			'abdm_consent_request_id' => $consentRequestId,
			'abdm_consent_artifact_id' => $consentId,
			'consent_id' => $consentId,
		];

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		try {
			$reconcile = $service->runOperation('consent_reconcile', $reconcilePayload);
		} catch (\Throwable $e) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'Unable to check live status: ' . $e->getMessage(),
			]);
		}

		$reconcileOk = (int) ($reconcile['ok'] ?? 0) === 1;

		$fetchSummary = ['granted' => false, 'data_updates' => 0, 'failed' => 0, 'artifact_ids' => []];
		if ($reconcileOk) {
			try {
				$fetchSummary = $service->fetchAllArtifactsAfterGrant($reconcilePayload, $reconcile);
			} catch (\Throwable $e) {
				// Status was successfully reconciled even if the data-fetch
				// cascade below fails transiently; don't hide that from the UI.
				$fetchSummary['fetch_error'] = $e->getMessage();
			}
		}

		return $this->response->setJSON([
			'ok' => 1,
			'reconcile_ok' => $reconcileOk ? 1 : 0,
			'reconcile_error' => $reconcileOk ? '' : (string) ($reconcile['error_text'] ?? 'Live status check failed.'),
			'granted' => $fetchSummary['granted'] ? 1 : 0,
			'artifacts_fetched' => count($fetchSummary['artifact_ids'] ?? []) + ($fetchSummary['granted'] ? 1 : 0),
			'data_fetch_updates' => (int) ($fetchSummary['data_updates'] ?? 0),
			'data_fetch_failed' => (int) ($fetchSummary['failed'] ?? 0),
		] + $this->getAbdmConsentRequestsList($abhaAddress));
	}

	public function abdm_timeline(int $pno)
	{
		if (! $this->db->tableExists('patient_master')) {
			return $this->response->setStatusCode(500)->setJSON([
				'ok' => 0,
				'error' => 'patient_master table not found',
			]);
		}

		$patientRow = $this->db->table('patient_master')->where('id', $pno)->get()->getRowArray();
		if (! is_array($patientRow)) {
			return $this->response->setStatusCode(404)->setJSON([
				'ok' => 0,
				'error' => 'Patient not found',
			]);
		}

		$abhaContext = $this->buildAbhaProfileContext($patientRow);
		if ($abhaContext['abha_address'] === '') {
			return $this->response->setJSON([
				'ok' => 1,
				'count' => 0,
				'items' => [],
				'note' => 'ABHA address not available for timeline filter.',
			]);
		}

		$limit = (int) ($this->request->getGet('limit') ?? 15);
		if ($limit <= 0 || $limit > 100) {
			$limit = 15;
		}

		$service = new \App\Libraries\Abdm\M3HiuWorkflowService();
		$rows = $service->listTimeline([
			'abha_address' => (string) $abhaContext['abha_address'],
		], $limit);

		return $this->response->setJSON([
			'ok' => 1,
			'count' => count($rows),
			'items' => $rows,
		]);
	}

	public function save_profile_image(int $pid)
	{
		$patient = $this->db->table('patient_master')->where('id', $pid)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setJSON([
				'success' => false,
				'message' => 'Patient not found',
			]);
		}

		$dataUri = (string) ($this->request->getPost('image') ?? $this->request->getPost('webcam') ?? $this->request->getPost('data_uri'));
		if ($dataUri === '') {
			$raw = (string) $this->request->getBody();
			if (str_starts_with($raw, 'data:image')) {
				$dataUri = $raw;
			}
		}

		if ($dataUri === '' || !str_contains($dataUri, 'base64,')) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Invalid image data.',
			]);
		}

		[$meta, $encoded] = explode('base64,', $dataUri, 2);
		$mime = 'image/jpeg';
		if (preg_match('/data:(image\/[a-zA-Z0-9.+-]+);/i', $meta, $match)) {
			$mime = strtolower($match[1]);
		}

		$binary = base64_decode($encoded);
		if ($binary === false) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Unable to decode image.',
			]);
		}

		$extension = match ($mime) {
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			default => 'jpg',
		};

		$uploadPath = rtrim(FCPATH, '\\/') . '/uploads/patient';
		if (!is_dir($uploadPath)) {
			mkdir($uploadPath, 0755, true);
		}

		$filename = 'profile_' . $pid . '_' . time() . '.' . $extension;
		$fullPath = $uploadPath . '/' . $filename;
		if (file_put_contents($fullPath, $binary) === false) {
			return $this->response->setJSON([
				'success' => false,
				'message' => 'Unable to save image.',
			]);
		}

		$publicPath = '/uploads/patient/' . $filename;
		$insertId = $this->insertFileUploadRecordFromData($pid, 'profile', $publicPath, $mime, strlen($binary));

		$updated = false;
		if ($insertId > 0) {
			$this->db->table('patient_master')
				->where('id', $pid)
				->update(['profile_file_id' => $insertId]);
			$updated = true;
		}

		if ($this->db->fieldExists('profile_picture', 'patient_master')) {
			$this->db->table('patient_master')
				->where('id', $pid)
				->update(['profile_picture' => $publicPath]);
			$updated = true;
		}

		return $this->response->setJSON([
			'success' => $updated,
			'path' => $publicPath,
			'message' => $updated ? 'Profile image saved.' : 'Image saved, record not updated.',
			'csrf' => csrf_hash(),
		]);
	}

	public function patient_file_upload(int $pid)
	{
		$patient = $this->db->table('patient_master')->where('id', $pid)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

		$docType = (string) $this->request->getPost('doc_type');
		$updateProfile = $this->request->getPost('update_profile') === '1';
		$message = '';

		$file = $this->request->getFile('upload_file');
		if ($file && $file->isValid() && !$file->hasMoved()) {
			$uploadPath = rtrim(FCPATH, '\\/') . '/uploads/patient';
			if (!is_dir($uploadPath)) {
				mkdir($uploadPath, 0755, true);
			}

			$newName = $file->getRandomName();
			if ($file->move($uploadPath, $newName)) {
				$publicPath = '/uploads/patient/' . $newName;
				$insertId = $this->insertFileUploadRecord($pid, $docType, $publicPath, $file);

				if ($updateProfile && $insertId > 0) {
					$this->db->table('patient_master')
						->where('id', $pid)
						->update(['profile_file_id' => $insertId]);
				}

				$message = $insertId > 0 ? 'File uploaded successfully.' : 'File uploaded, but record was not saved.';
			} else {
				$message = 'Unable to save the file.';
			}
		} elseif ($file && !$file->isValid()) {
			$message = $file->getErrorString();
		}

		return view('billing/Patient_File_Upload_V', [
			'patient' => $patient,
			'docType' => $docType,
			'updateProfile' => $updateProfile,
			'message' => $message,
		]);
	}

	public function city()
	{
		$q = strtolower((string) $this->request->getGet('term'));
		if ($q === '') {
			return $this->response->setJSON([]);
		}

		$patientModel = new PatientModel();
		return $this->response->setJSON($patientModel->getCitySuggestions($q));
	}

	public function district_list()
	{
		$q = trim((string) $this->request->getGet('term'));
		if ($q === '' || strlen($q) < 2) {
			return $this->response->setJSON([]);
		}

		if (! $this->db->tableExists('abdm_district_master')) {
			return $this->response->setJSON([]);
		}

		$rows = $this->db->table('abdm_district_master')
			->select('district_name AS value, district_name AS label, state_code')
			->like('district_name', $q)
			->orderBy('district_name', 'ASC')
			->limit(15)
			->get()
			->getResultArray();

		return $this->response->setJSON($rows);
	}

	public function get_name()
	{
		$q = strtolower((string) $this->request->getGet('term'));
		if ($q === '') {
			return $this->response->setJSON([]);
		}

		$patientModel = new PatientModel();
		return $this->response->setJSON($patientModel->getNameSuggestions($q));
	}

	private function getProfileFilePath(int $fileId): string
	{
		$profileFilePath = '/assets/images/no_image.svg';
		if ($fileId <= 0) {
			return $profileFilePath;
		}

		$query = $this->db->table('file_upload_data')->where('id', $fileId)->get();
		$row = $query->getRow();
		if ($row && isset($row->full_path)) {
			$path = (string) $row->full_path;
			if ($path !== '') {
				$profileFilePath = $path;
			}
		}

		return $profileFilePath;
	}

	private function insertFileUploadRecord(int $pid, string $docType, string $publicPath, $file): int
	{
		if (!$this->db->tableExists('file_upload_data')) {
			return 0;
		}

		$fields = $this->db->getFieldNames('file_upload_data');
		if (!$fields) {
			return 0;
		}

		$data = [];
		if (in_array('p_id', $fields, true)) {
			$data['p_id'] = $pid;
		}
		if (in_array('attach_id', $fields, true)) {
			$data['attach_id'] = $pid;
		}
		if (in_array('doc_type', $fields, true)) {
			$data['doc_type'] = $docType;
		}
		if (in_array('file_name', $fields, true)) {
			$data['file_name'] = $file->getClientName();
		}
		if (in_array('file_type', $fields, true)) {
			$data['file_type'] = $file->getClientMimeType();
		}
		if (in_array('file_size', $fields, true)) {
			$data['file_size'] = $file->getSize();
		}
		if (in_array('full_path', $fields, true)) {
			$data['full_path'] = $publicPath;
		}
		if (in_array('insert_date', $fields, true)) {
			$data['insert_date'] = date('Y-m-d H:i:s');
		}
		if (in_array('created_at', $fields, true)) {
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		if (!$data) {
			return 0;
		}

		$builder = $this->db->table('file_upload_data');
		if (!$builder->insert($data)) {
			return 0;
		}

		return (int) $this->db->insertID();
	}

	private function insertFileUploadRecordFromData(int $pid, string $docType, string $publicPath, string $mime, int $size): int
	{
		if (!$this->db->tableExists('file_upload_data')) {
			return 0;
		}

		$fields = $this->db->getFieldNames('file_upload_data');
		if (!$fields) {
			return 0;
		}

		$data = [];
		if (in_array('p_id', $fields, true)) {
			$data['p_id'] = $pid;
		}
		if (in_array('attach_id', $fields, true)) {
			$data['attach_id'] = $pid;
		}
		if (in_array('doc_type', $fields, true)) {
			$data['doc_type'] = $docType;
		}
		if (in_array('file_name', $fields, true)) {
			$data['file_name'] = basename($publicPath);
		}
		if (in_array('file_type', $fields, true)) {
			$data['file_type'] = $mime;
		}
		if (in_array('file_size', $fields, true)) {
			$data['file_size'] = $size;
		}
		if (in_array('full_path', $fields, true)) {
			$data['full_path'] = $publicPath;
		}
		if (in_array('insert_date', $fields, true)) {
			$data['insert_date'] = date('Y-m-d H:i:s');
		}
		if (in_array('created_at', $fields, true)) {
			$data['created_at'] = date('Y-m-d H:i:s');
		}

		if (!$data) {
			return 0;
		}

		$builder = $this->db->table('file_upload_data');
		if (!$builder->insert($data)) {
			return 0;
		}

		return (int) $this->db->insertID();
	}

	/**
	 * @param array<string, mixed> $result
	 * @return array<string, mixed>
	 */
	private function enrichAbhaVerifyResult(array $result, int $requestedPid): array
	{
		$payload = [];
		if (isset($result['data']) && is_array($result['data'])) {
			$payload = $result['data'];
		} elseif (is_array($result)) {
			$payload = $result;
		}

		$profile = $this->pickGatewayAbhaProfile($payload);
		$gatewayPatientPayload = is_array($payload['gateway_patient'] ?? null) ? $payload['gateway_patient'] : [];
		$abhaNumberRaw = trim((string) ($profile['ABHANumber'] ?? $profile['abha_id'] ?? $payload['ABHANumber'] ?? $payload['abha_id'] ?? ''));
		$abhaDigits = preg_replace('/\D/', '', $abhaNumberRaw);
		$abhaAddress = trim((string) (
			$profile['preferredAddress']
			?? $profile['preferredAbhaAddress']
			?? $profile['abha_address']
			?? $payload['abha_address']
			?? $payload['preferredAddress']
			?? $payload['preferredAbhaAddress']
			?? ''
		));
		$gatewayName = trim((string) ($profile['name'] ?? $profile['fullName'] ?? $profile['full_name'] ?? $payload['name'] ?? $payload['full_name'] ?? ''));
		$gatewayMobile = trim((string) ($profile['mobile'] ?? $payload['mobile'] ?? $payload['mobileNumber'] ?? ''));
		$gatewayDob = trim((string) ($profile['dob'] ?? $profile['date_of_birth'] ?? $payload['dob'] ?? $payload['date_of_birth'] ?? ''));
		$gatewayGender = trim((string) ($profile['gender'] ?? $payload['gender'] ?? ''));
		$gatewayPhoto = trim((string) ($profile['profilePhoto'] ?? $profile['profile_photo'] ?? $payload['profilePhoto'] ?? $payload['profile_photo'] ?? ''));
		$gatewayEmail = trim((string) ($profile['email'] ?? $payload['email'] ?? ''));
		$gatewayAddress = trim((string) (
			$profile['address']
			?? $profile['address_line']
			?? $payload['address']
			?? $payload['address_line']
			?? ($gatewayPatientPayload['address_line'] ?? '')
			?? ''
		));
		$gatewayZip = trim((string) (
			$profile['pinCode']
			?? $profile['pin_code']
			?? $payload['pinCode']
			?? $payload['pin_code']
			?? $payload['pincode']
			?? ($gatewayPatientPayload['pincode'] ?? '')
			?? ''
		));
		$gatewayStateCode = trim((string) (
			$profile['stateCode']
			?? $profile['state_code']
			?? $payload['stateCode']
			?? $payload['state_code']
			?? ($gatewayPatientPayload['state_code'] ?? '')
			?? ''
		));
		$gatewayStateName = trim((string) (
			$profile['stateName']
			?? $profile['state_name']
			?? $payload['stateName']
			?? $payload['state_name']
			?? ($gatewayPatientPayload['state_name'] ?? '')
			?? ''
		));
		$gatewayDistrictCode = trim((string) (
			$profile['districtCode']
			?? $profile['district_code']
			?? $payload['districtCode']
			?? $payload['district_code']
			?? ($payload['gateway_abha_profile']['district_code'] ?? '')
			?? ''
		));
		$gatewayDistrictName = trim((string) (
			$profile['districtName']
			?? $profile['district_name']
			?? $payload['districtName']
			?? $payload['district_name']
			?? ($payload['gateway_abha_profile']['district_name'] ?? '')
			?? ($gatewayPatientPayload['district'] ?? '')
			?? ''
		));
		$verifiedStatus = trim((string) (
			($payload['gateway_abha_profile']['status'] ?? '')
			?: ($profile['verifiedStatus'] ?? '')
			?: ($profile['status'] ?? '')
			?: ($profile['abhaStatus'] ?? '')
			?: ($payload['verifiedStatus'] ?? '')
			?: ($payload['status'] ?? '')
			?: ''
		));
		$verificationType = trim((string) (
			($payload['gateway_abha_profile']['abha_type'] ?? '')
			?: ($profile['verificationType'] ?? '')
			?: ($profile['abhaType'] ?? '')
			?: ($payload['verificationType'] ?? '')
			?: ($payload['abhaType'] ?? '')
			?: ''
		));
		$kycVerified = $profile['kycVerified'] ?? $payload['kycVerified'] ?? null;
		if ($kycVerified === null) {
			$kycVerified = strtolower(trim((string) ($payload['gateway_abha_profile']['status'] ?? ''))) === 'verified' ? 1 : 0;
		}
		$mobileVerified = $profile['mobileVerified']
			?? $payload['mobileVerified']
			?? ($payload['gateway_abha_profile']['mobile_verified'] ?? null);
		$gatewayCity = trim((string) ($profile['city'] ?? $payload['city'] ?? ($gatewayPatientPayload['city'] ?? '')));
		if ($gatewayCity === '') {
			$gatewayCity = $this->extractCityFromAddress($gatewayAddress, $gatewayDistrictName, $gatewayStateName);
		}

		$this->upsertAbdmLocationMasters($gatewayStateCode, $gatewayStateName, $gatewayDistrictCode, $gatewayDistrictName);

		$patient = $this->findPatientForAbhaProfile($requestedPid, $abhaDigits, $gatewayMobile);
		$patientId = (int) ($patient['id'] ?? 0);
		$before = $patient !== null ? $this->buildPatientSnapshot($patient) : null;

		$photoMeta = [
			'saved' => false,
			'path' => '',
			'error' => '',
		];

		if ($patientId > 0 && $this->db->tableExists('patient_master')) {
			$updates = [];

			if ($abhaDigits !== '') {
				$abhaField = $this->resolvePatientAbhaIdField();
				if ($abhaField !== null) {
					$updates[$abhaField] = $abhaDigits;
				}
			}

			$pmFields = $this->db->getFieldNames('patient_master') ?? [];
			if ($gatewayMobile !== '' && in_array('mphone1', $pmFields, true)) {
				$updates['mphone1'] = preg_replace('/\D/', '', $gatewayMobile);
			}
			$dobDb = $this->normalizeGatewayDobToDb($gatewayDob);
			if ($dobDb !== '' && in_array('dob', $pmFields, true)) {
				$updates['dob'] = $dobDb;
				if (in_array('estimate_dob', $pmFields, true)) {
					$updates['estimate_dob'] = 0;
				}
			}
			$genderDb = $this->toPatientGenderDbValue($gatewayGender);
			if ($genderDb !== null && in_array('gender', $pmFields, true)) {
				$updates['gender'] = $genderDb;
			}
			if ($gatewayAddress !== '' && in_array('add1', $pmFields, true)) {
				$updates['add1'] = $gatewayAddress;
			}
			if ($gatewayCity !== '' && in_array('city', $pmFields, true)) {
				$updates['city'] = $gatewayCity;
			}
			if ($gatewayDistrictName !== '' && in_array('district', $pmFields, true)) {
				$updates['district'] = $gatewayDistrictName;
			}
			if ($gatewayStateName !== '' && in_array('state', $pmFields, true)) {
				$updates['state'] = $gatewayStateName;
			}
			if ($gatewayZip !== '' && in_array('zip', $pmFields, true)) {
				$updates['zip'] = $gatewayZip;
			}
			if ($gatewayEmail !== '' && in_array('email1', $pmFields, true)) {
				$updates['email1'] = $gatewayEmail;
			}
			if ($verifiedStatus !== '' && in_array('abha_verified_status', $pmFields, true)) {
				$updates['abha_verified_status'] = $verifiedStatus;
			}
			if ($verificationType !== '' && in_array('abha_verification_type', $pmFields, true)) {
				$updates['abha_verification_type'] = $verificationType;
			}
			if ($kycVerified !== null && in_array('abha_kyc_verified', $pmFields, true)) {
				$updates['abha_kyc_verified'] = (int) ((string) $kycVerified === '1' || (string) $kycVerified === 'true' || (string) $kycVerified === 'yes');
			}
			if ($mobileVerified !== null && in_array('abha_mobile_verified', $pmFields, true)) {
				$updates['abha_mobile_verified'] = (int) ((string) $mobileVerified === '1' || (string) $mobileVerified === 'true' || (string) $mobileVerified === 'yes');
			}
			if ($abhaAddress !== '' && in_array('abha_address', $pmFields, true)) {
				$updates['abha_address'] = $abhaAddress;
			}
			if (in_array('abdm_linked_at', $pmFields, true) && ($abhaDigits !== '' || $abhaAddress !== '')) {
				$updates['abdm_linked_at'] = date('Y-m-d H:i:s');
			}

			if ($updates !== []) {
				$this->db->table('patient_master')->where('id', $patientId)->update($updates);
			}

			if ($gatewayPhoto !== '') {
				$photoMeta = $this->saveGatewayProfilePhotoToPatient($patientId, $gatewayPhoto);
			}
		}

		$afterPatient = null;
		if ($patientId > 0 && $this->db->tableExists('patient_master')) {
			$afterPatient = $this->db->table('patient_master')->where('id', $patientId)->get()->getRowArray();
		}
		$after = $afterPatient !== null ? $this->buildPatientSnapshot($afterPatient) : $before;

		$result['gateway_user'] = [
			'name' => $gatewayName,
			'mobile' => $gatewayMobile,
			'dob' => $gatewayDob,
			'gender' => $gatewayGender,
			'address' => $gatewayAddress,
			'city' => $gatewayCity,
			'zip' => $gatewayZip,
			'state_code' => $gatewayStateCode,
			'state_name' => $gatewayStateName,
			'district_code' => $gatewayDistrictCode,
			'district_name' => $gatewayDistrictName,
			'abha_number' => $abhaDigits,
			'abha_address' => $abhaAddress,
			'photo_base64' => $gatewayPhoto,
		];

		$result['hms_patient'] = [
			'requested_patient_id' => $requestedPid > 0 ? $requestedPid : null,
			'matched_patient_id' => $patientId > 0 ? $patientId : null,
			'before' => $before,
			'after' => $after,
			'comparison' => [
				'name_match_before' => $before !== null
					? mb_strtolower(trim((string) ($before['name'] ?? ''))) === mb_strtolower($gatewayName)
					: null,
				'name_match_after' => $after !== null
					? mb_strtolower(trim((string) ($after['name'] ?? ''))) === mb_strtolower($gatewayName)
					: null,
				'mobile_match_before' => $before !== null
					? preg_replace('/\D/', '', (string) ($before['mobile'] ?? '')) === preg_replace('/\D/', '', $gatewayMobile)
					: null,
				'mobile_match_after' => $after !== null
					? preg_replace('/\D/', '', (string) ($after['mobile'] ?? '')) === preg_replace('/\D/', '', $gatewayMobile)
					: null,
				'abha_match_before' => $before !== null
					? preg_replace('/\D/', '', (string) ($before['abha_id'] ?? '')) === $abhaDigits
					: null,
				'abha_match_after' => $after !== null
					? preg_replace('/\D/', '', (string) ($after['abha_id'] ?? '')) === $abhaDigits
					: null,
				'dob_match_before' => $before !== null
					? trim((string) ($before['dob'] ?? '')) === trim($gatewayDob)
					: null,
				'dob_match_after' => $after !== null
					? trim((string) ($after['dob'] ?? '')) === trim($gatewayDob)
					: null,
				'gender_match_before' => $before !== null
					? $this->normalizeGenderForCompare((string) ($before['gender'] ?? '')) === $this->normalizeGenderForCompare($gatewayGender)
					: null,
				'gender_match_after' => $after !== null
					? $this->normalizeGenderForCompare((string) ($after['gender'] ?? '')) === $this->normalizeGenderForCompare($gatewayGender)
					: null,
			],
			'photo_update' => $photoMeta,
		];

		return $result;
	}

	/**
	 * Return only safe/required fields for ABHA OTP verification APIs.
	 * Prevents leaking connector tokens, full bridge profile dumps, and base64 photo blobs.
	 *
	 * @param array<string, mixed> $result
	 * @return array<string, mixed>
	 */
	private function sanitizeAbhaVerifyApiResponse(array $result): array
	{
		$ok = ! empty($result['ok']) && (int) $result['ok'] === 1;
		if (! $ok) {
			return [
				'ok' => 0,
				'error_text' => (string) ($result['error_text'] ?? $result['message'] ?? $result['data']['message'] ?? 'OTP verification failed'),
				'request_id' => (string) ($result['request_id'] ?? ''),
			];
		}

		$payload = [];
		if (isset($result['data']) && is_array($result['data'])) {
			$payload = $result['data'];
		} elseif (is_array($result)) {
			$payload = $result;
		}

		$profile = $this->pickGatewayAbhaProfile($payload);

		$txnId = (string) ($payload['txnId'] ?? $payload['txn_id'] ?? $result['txnId'] ?? $result['txn_id'] ?? '');
		$abhaRaw = (string) ($payload['ABHANumber'] ?? $payload['abha_number'] ?? $payload['abha_id'] ?? $profile['ABHANumber'] ?? $profile['abha_number'] ?? $profile['abha_id'] ?? '');
		$abhaDigits = preg_replace('/\D/', '', $abhaRaw);
		$abhaAddress = (string) ($payload['preferredAbhaAddress'] ?? $payload['abha_address'] ?? $profile['preferredAbhaAddress'] ?? $profile['abha_address'] ?? $profile['preferredAddress'] ?? '');
		$name = trim((string) (
			$payload['name']
			?? $payload['full_name']
			?? $profile['name']
			?? $profile['fullName']
			?? $profile['full_name']
			?? trim(implode(' ', array_filter([
				(string) ($profile['firstName'] ?? ''),
				(string) ($profile['middleName'] ?? ''),
				(string) ($profile['lastName'] ?? ''),
			])))
		));
		$mobile = (string) ($payload['mobile'] ?? $payload['mobileNumber'] ?? $profile['mobile'] ?? '');
		$gender = (string) ($payload['gender'] ?? $profile['gender'] ?? '');
		$dob = (string) ($payload['dob'] ?? $payload['date_of_birth'] ?? $profile['dob'] ?? $profile['date_of_birth'] ?? '');
		$address = (string) ($payload['address'] ?? $payload['address_line'] ?? $profile['address'] ?? $profile['address_line'] ?? '');
		$pinCode = (string) ($payload['pinCode'] ?? $payload['pin_code'] ?? $payload['pincode'] ?? $profile['pinCode'] ?? $profile['pin_code'] ?? '');
		$stateCode = (string) ($payload['stateCode'] ?? $payload['state_code'] ?? $profile['stateCode'] ?? $profile['state_code'] ?? '');
		$stateName = (string) ($payload['stateName'] ?? $payload['state_name'] ?? $profile['stateName'] ?? $profile['state_name'] ?? '');
		$districtCode = (string) ($payload['districtCode'] ?? $payload['district_code'] ?? $profile['districtCode'] ?? $profile['district_code'] ?? '');
		$districtName = (string) ($payload['districtName'] ?? $payload['district_name'] ?? $profile['districtName'] ?? $profile['district_name'] ?? '');

		$matchedPatientId = (int) ($result['hms_patient']['matched_patient_id'] ?? 0);
		$patientId = (int) ($result['patient_id'] ?? $payload['patient_id'] ?? $matchedPatientId);
		$pCode = (string) ($result['p_code'] ?? $payload['p_code'] ?? '');
		$isNew = $result['is_new_patient'] ?? $payload['is_new_patient'] ?? null;

		$safeProfile = [
			'ABHANumber' => $abhaDigits,
			'abha_number' => $abhaDigits,
			'abha_id' => $abhaDigits,
			'preferredAbhaAddress' => $abhaAddress,
			'preferredAddress' => $abhaAddress,
			'name' => $name,
			'full_name' => $name,
			'gender' => $gender,
			'dob' => $dob,
			'mobile' => $mobile,
			'address' => $address,
			'pinCode' => $pinCode,
			'stateCode' => $stateCode,
			'stateName' => $stateName,
			'districtCode' => $districtCode,
			'districtName' => $districtName,
			'firstName' => (string) ($profile['firstName'] ?? ''),
			'middleName' => (string) ($profile['middleName'] ?? ''),
			'lastName' => (string) ($profile['lastName'] ?? ''),
		];

		return [
			'ok' => 1,
			'request_id' => (string) ($result['request_id'] ?? ''),
			'message' => (string) ($payload['message'] ?? $result['message'] ?? 'ABHA OTP verified successfully'),
			'txn_id' => $txnId,
			'abha_number' => $abhaDigits,
			'abha_address' => $abhaAddress,
			'name' => $name,
			'mobile' => $mobile,
			'gender' => $gender,
			'dob' => $dob,
			'address' => $address,
			'pin_code' => $pinCode,
			'state_code' => $stateCode,
			'state_name' => $stateName,
			'district_code' => $districtCode,
			'district_name' => $districtName,
			'patient_id' => $patientId > 0 ? $patientId : null,
			'p_code' => $pCode !== '' ? $pCode : null,
			'is_new_patient' => $isNew,
			'data' => [
				'txnId' => $txnId,
				'abha_number' => $abhaDigits,
				'name' => $name,
				'mobile' => $mobile,
				'gender' => $gender,
				'dob' => $dob,
				'address' => $address,
				'pin_code' => $pinCode,
				'state_code' => $stateCode,
				'state_name' => $stateName,
				'district_code' => $districtCode,
				'district_name' => $districtName,
				'profile' => $safeProfile,
			],
		];
	}

	private function extractCityFromAddress(string $address, string $districtName = '', string $stateName = ''): string
	{
		$address = trim($address);
		if ($address === '') {
			return '';
		}

		$districtNorm = mb_strtoupper(trim($districtName));
		$stateNorm = mb_strtoupper(trim($stateName));
		$parts = array_values(array_filter(array_map('trim', explode(',', $address)), static fn (string $v): bool => $v !== ''));
		if ($parts === []) {
			return '';
		}

		for ($i = count($parts) - 1; $i >= 0; $i--) {
			$candidate = trim((string) ($parts[$i] ?? ''));
			if ($candidate === '' || preg_match('/\d/', $candidate) === 1) {
				continue;
			}

			$norm = mb_strtoupper($candidate);
			if (($districtNorm !== '' && $norm === $districtNorm) || ($stateNorm !== '' && $norm === $stateNorm)) {
				continue;
			}

			return $candidate;
		}

		return '';
	}

	private function upsertAbdmLocationMasters(string $stateCode, string $stateName, string $districtCode, string $districtName): void
	{
		// State data lives in india_state (read-only reference — no writes needed from ABHA flow).

		if ($districtCode !== '' && $districtName !== '' && $this->db->tableExists('abdm_district_master')) {
			$districtBuilder = $this->db->table('abdm_district_master')
				->select('id')
				->where('district_code', $districtCode);
			if ($stateCode !== '') {
				$districtBuilder->where('state_code', $stateCode);
			}
			$districtRow = $districtBuilder->limit(1)->get()->getRowArray();

			if ($districtRow === null) {
				$this->db->table('abdm_district_master')->insert([
					'district_code' => $districtCode,
					'district_name' => $districtName,
					'state_code' => $stateCode,
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s'),
				]);
			} else {
				$this->db->table('abdm_district_master')
					->where('id', (int) ($districtRow['id'] ?? 0))
					->update([
						'district_name' => $districtName,
						'state_code' => $stateCode,
						'updated_at' => date('Y-m-d H:i:s'),
					]);
			}
		}
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

	/**
	 * @return array<string, mixed>|null
	 */
	private function findPatientForAbhaProfile(int $requestedPid, string $abhaDigits, string $mobile): ?array
	{
		if (! $this->db->tableExists('patient_master')) {
			return null;
		}

		if ($requestedPid > 0) {
			$row = $this->db->table('patient_master')->where('id', $requestedPid)->get()->getRowArray();
			if ($row !== null) {
				return $row;
			}
		}

		$abhaField = $this->resolvePatientAbhaIdField();
		if ($abhaDigits !== '' && $abhaField !== null) {
			$row = $this->db->table('patient_master')->where($abhaField, $abhaDigits)->get()->getRowArray();
			if ($row !== null) {
				return $row;
			}
		}

		$mobileDigits = preg_replace('/\D/', '', $mobile);
		if ($mobileDigits !== '') {
			$row = $this->db->table('patient_master')->where('mphone1', $mobileDigits)->get()->getRowArray();
			if ($row !== null) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function buildPatientSnapshot(array $row): array
	{
		$abhaField = $this->resolvePatientAbhaIdField();
		$abhaId = $abhaField !== null ? trim((string) ($row[$abhaField] ?? '')) : '';
		$abhaAddress = trim((string) ($row['abha_address'] ?? ''));

		return [
			'id' => (int) ($row['id'] ?? 0),
			'p_code' => trim((string) ($row['p_code'] ?? '')),
			'name' => trim((string) ($row['p_fname'] ?? '')),
			'mobile' => trim((string) ($row['mphone1'] ?? '')),
			'dob' => trim((string) ($row['dob'] ?? '')),
			'gender' => trim((string) ($row['gender'] ?? '')),
			'abha_id' => $abhaId,
			'abha_address' => $abhaAddress,
			'profile_picture' => trim((string) ($row['profile_picture'] ?? '')),
			'profile_file_id' => (int) ($row['profile_file_id'] ?? 0),
		];
	}

	/**
	 * ABDM verify-otp/eKYC responses return DOB as "DD-MM-YYYY". Convert to
	 * MySQL "YYYY-MM-DD" for storage. Returns '' if unparseable/empty.
	 */
	private function normalizeGatewayDobToDb(string $dob): string
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
	 * Map ABDM gateway gender string (M/F/O, MALE/FEMALE/OTHER, or already 1/2/3)
	 * to patient_master's numeric gender code. Returns null if unrecognised.
	 */
	private function toPatientGenderDbValue(string $gender): ?int
	{
		$g = strtoupper(trim($gender));
		if ($g === '') {
			return null;
		}
		if ($g === 'M' || $g === '1' || $g === 'MALE') {
			return 1;
		}
		if ($g === 'F' || $g === '2' || $g === 'FEMALE') {
			return 2;
		}
		if ($g === 'O' || $g === '3' || $g === 'OTHER') {
			return 3;
		}

		return null;
	}

	/**
	 * @return array{saved:bool,path:string,error:string}
	 */
	private function saveGatewayProfilePhotoToPatient(int $pid, string $photoBase64): array
	{
		$raw = trim($photoBase64);
		if ($pid <= 0 || $raw === '') {
			return ['saved' => false, 'path' => '', 'error' => 'Missing patient id or photo.'];
		}

		$mime = 'image/jpeg';
		$encoded = $raw;
		if (str_starts_with($raw, 'data:image')) {
			$parts = explode('base64,', $raw, 2);
			if (count($parts) !== 2) {
				return ['saved' => false, 'path' => '', 'error' => 'Invalid image data URI.'];
			}
			if (preg_match('/data:(image\/[a-zA-Z0-9.+-]+);/i', $parts[0], $m) === 1) {
				$mime = strtolower((string) ($m[1] ?? 'image/jpeg'));
			}
			$encoded = $parts[1];
		}

		$binary = base64_decode($encoded, true);
		if ($binary === false) {
			return ['saved' => false, 'path' => '', 'error' => 'Unable to decode photo base64.'];
		}

		$ext = match ($mime) {
			'image/png' => 'png',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			default => 'jpg',
		};

		$uploadPath = rtrim(FCPATH, '\\/') . '/uploads/patient';
		if (! is_dir($uploadPath) && ! mkdir($uploadPath, 0755, true) && ! is_dir($uploadPath)) {
			return ['saved' => false, 'path' => '', 'error' => 'Unable to create upload directory.'];
		}

		$fileName = 'abha_profile_' . $pid . '_' . time() . '.' . $ext;
		$absolutePath = $uploadPath . '/' . $fileName;
		if (file_put_contents($absolutePath, $binary) === false) {
			return ['saved' => false, 'path' => '', 'error' => 'Unable to write photo file.'];
		}

		$publicPath = '/uploads/patient/' . $fileName;
		$insertId = $this->insertFileUploadRecordFromData($pid, 'profile', $publicPath, $mime, strlen($binary));

		$updates = [];
		if ($insertId > 0) {
			$updates['profile_file_id'] = $insertId;
		}
		if ($this->db->fieldExists('profile_picture', 'patient_master')) {
			$updates['profile_picture'] = $publicPath;
		}

		if ($updates !== []) {
			$this->db->table('patient_master')->where('id', $pid)->update($updates);
		}

		return ['saved' => true, 'path' => $publicPath, 'error' => ''];
	}

	private function normalizeGenderForCompare(string $value): string
	{
		$v = strtoupper(trim($value));
		if ($v === '1' || $v === 'M' || $v === 'MALE') {
			return 'M';
		}
		if ($v === '2' || $v === 'F' || $v === 'FEMALE') {
			return 'F';
		}
		if ($v === '3' || $v === 'O' || $v === 'OTHER') {
			return 'O';
		}

		return $v;
	}

    private function parseDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

		$date = trim($date);

		$dt = \DateTime::createFromFormat('Y-m-d', $date);
		if (!$dt) {
			$dt = \DateTime::createFromFormat('d/m/Y', $date);
		}

        return $dt ? $dt->format('Y-m-d') : null;
    }

	private function saveNamesToNameList(array $names): void
	{
		if (! $this->db->tableExists('name_list')) {
			return;
		}

		$builder = $this->db->table('name_list');
		foreach ($names as $rawName) {
			$name = preg_replace('/\s+/', ' ', trim((string) $rawName));
			if ($name === '' || strlen($name) < 2) {
				continue;
			}

			$name = ucwords(strtolower($name));
			$exists = $builder->select('id')->where('name', $name)->limit(1)->get()->getRowArray();
			if ($exists) {
				continue;
			}

			$builder->insert(['name' => $name]);
		}
	}

	private function applyPatientAbhaFieldValues(array &$data, string $abhaId, string $abhaAddress = ''): void
	{
		$fields = $this->db->tableExists('patient_master') ? ($this->db->getFieldNames('patient_master') ?? []) : [];

		if (in_array('abha_id', $fields, true)) {
			$data['abha_id'] = $abhaId;
		} else {
			$targetField = $this->resolvePatientAbhaIdField();
			if ($targetField !== null) {
				$data[$targetField] = $abhaId;
			}
		}

		if (in_array('abha_address', $fields, true) && $abhaAddress !== '') {
			$data['abha_address'] = $abhaAddress;
		}
	}

	/**
	 * @return array{0:string,1:string,2:?string}
	 */
	private function normalizeAbhaInputs(string $abhaIdInput, string $abhaAddressInput): array
	{
		$abhaId = trim($abhaIdInput);
		$abhaAddress = trim($abhaAddressInput);

		if ($abhaAddress === '' && $this->isLikelyAbhaAddress($abhaId)) {
			$abhaAddress = $abhaId;
			$abhaId = '';
		}

		if ($abhaId === '' && $abhaAddress !== '' && $this->isValidAbhaId($abhaAddress)) {
			$abhaId = $abhaAddress;
			$abhaAddress = '';
		}

		if ($abhaId !== '' && ! $this->isValidAbhaId($abhaId)) {
			return ['', '', 'ABHA ID must be a 14-digit number.'];
		}

		if ($abhaAddress !== '' && ! $this->isLikelyAbhaAddress($abhaAddress)) {
			return ['', '', 'ABHA Address format looks invalid.'];
		}

		return [$abhaId, $abhaAddress, null];
	}

	private function isLikelyAbhaAddress(string $value): bool
	{
		$value = trim($value);
		if ($value === '') {
			return false;
		}

		return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,}@[A-Za-z0-9.-]+$/', $value) === 1;
	}

	private function resolvePatientAbhaIdField(): ?string
	{
		if (! $this->db->tableExists('patient_master')) {
			return null;
		}

		$fields = $this->db->getFieldNames('patient_master') ?? [];
		foreach (['abha_id', 'abha_no', 'abha'] as $field) {
			if (in_array($field, $fields, true)) {
				return $field;
			}
		}

		if (in_array('abha_address', $fields, true)) {
			return 'abha_address';
		}

		return null;
	}

	private function isValidAbhaId(string $value): bool
	{
		return preg_match('/^\d{14}$/', $value) === 1;
	}

	/**
	 * @param array<string, mixed> $patientData
	 */
	private function enqueuePatientAbhaSync(int $patientId, array $patientData, string $eventType): void
	{
		if ($patientId <= 0) {
			return;
		}

		$abhaField = $this->resolvePatientAbhaIdField();
		$abhaId = $abhaField !== null ? trim((string) ($patientData[$abhaField] ?? '')) : '';

		$payload = [
			'patient_id' => $patientId,
			'abha_id' => $abhaId,
			'name' => trim((string) ($patientData['p_fname'] ?? '')),
			'mobile' => trim((string) ($patientData['mphone1'] ?? '')),
			'gender' => trim((string) ($patientData['gender'] ?? '')),
			'dob' => trim((string) ($patientData['dob'] ?? '')),
			'city' => trim((string) ($patientData['city'] ?? '')),
			'state' => trim((string) ($patientData['state'] ?? '')),
		];

		try {
			$bridgeSync = new BridgeSyncService();
			$bridgeSync->enqueue(
				$eventType,
				$payload,
				'patient',
				(string) $patientId
			);
		} catch (\Throwable $e) {
			// Do not block patient workflows if queue service is unavailable.
		}
	}

	/**
	 * @param array<string, mixed> $patientData
	 */
	private function createPatientAbdmWorkTask(int $patientId, array $patientData, string $abhaId): void
	{
		if ($patientId <= 0) {
			return;
		}

		$taskService = new AbdmWorkTaskService();
		$patientName = trim((string) ($patientData['p_fname'] ?? ''));
		$actionMode = $abhaId !== '' ? 'update_abha' : 'create_abha';
		$taskType = $abhaId !== '' ? 'patient_abha_update' : 'patient_abha_create';

		$taskService->createOrRefreshTask(
			$taskType,
			'patient_registration',
			'patient',
			(string) $patientId,
			$patientId,
			$patientName,
			$abhaId,
			$actionMode,
			[
				'trigger' => 'patient.created',
			]
		);
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	private function buildAbhaProfileContext(array $row): array
	{
		$abhaField = $this->resolvePatientAbhaIdField();
		$abhaIdRaw = $abhaField !== null ? trim((string) ($row[$abhaField] ?? '')) : '';
		$abhaAddress = trim((string) ($row['abha_address'] ?? ''));

		if ($abhaAddress === '' && $abhaIdRaw !== '' && $this->isLikelyAbhaAddress($abhaIdRaw)) {
			$abhaAddress = $abhaIdRaw;
		}

		if ($abhaAddress === '' && preg_match('/abha_address\s*:\s*([A-Za-z0-9._-]+@[A-Za-z0-9.-]+)/i', (string) ($row['log'] ?? ''), $m) === 1) {
			$abhaAddress = trim((string) ($m[1] ?? ''));
		}

		$verifiedStatus = strtoupper(trim((string) ($row['abha_verified_status'] ?? '')));
		$kycVerified = (int) ($row['abha_kyc_verified'] ?? 0) === 1;
		$mobileVerified = (int) ($row['abha_mobile_verified'] ?? 0) === 1;
		$hasStatusColumn = array_key_exists('abha_verified_status', $row);
		$isVerified = in_array($verifiedStatus, ['1', 'VERIFIED', 'YES', 'Y', 'TRUE'], true) || ($kycVerified && $mobileVerified);
		if (! $hasStatusColumn && $abhaAddress !== '') {
			$isVerified = true;
		}

		return [
			'abha_id' => $abhaIdRaw,
			'abha_address' => $abhaAddress,
			'verified_status' => $verifiedStatus,
			'is_verified' => $isVerified ? 1 : 0,
		];
	}

	private function deriveAutoFlowState(array $result): string
	{
		$blob = strtolower(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

		if (str_contains($blob, 'granted') || str_contains($blob, 'approved') || str_contains($blob, 'consent_granted')) {
			return 'GRANTED';
		}

		if (str_contains($blob, 'denied') || str_contains($blob, 'revoked') || str_contains($blob, 'expired') || str_contains($blob, 'rejected')) {
			return 'DENIED';
		}

		if (str_contains($blob, 'requested') || str_contains($blob, 'pending') || str_contains($blob, 'await')) {
			return 'PENDING';
		}

		$explicit = strtoupper(trim((string) ($result['workflow_state'] ?? $result['state'] ?? $result['status'] ?? '')));
		if ($explicit !== '') {
			if (in_array($explicit, ['GRANTED', 'APPROVED', 'COMPLETED'], true)) {
				return 'GRANTED';
			}
			if (in_array($explicit, ['REVOKED', 'EXPIRED', 'DENIED', 'REJECTED'], true)) {
				return 'DENIED';
			}
		}

		return 'REQUESTED';
	}

	private function findLatestAbdmGatewayRequestId(string $abhaAddress): string
	{
		$abhaAddress = trim($abhaAddress);
		if ($abhaAddress === '' || ! $this->db->tableExists('abdm_hiu_workflows')) {
			return '';
		}

		$fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
		$select = ['request_id', 'response_json'];
		if (in_array('gateway_request_id', $fields, true)) {
			$select[] = 'gateway_request_id';
		}
		if (in_array('hms_request_id', $fields, true)) {
			$select[] = 'hms_request_id';
		}

		$rows = $this->db->table('abdm_hiu_workflows')
			->select(implode(', ', $select))
			->where('operation', 'consent_request')
			->where('abha_address', $abhaAddress)
			->where('status', 'success')
			->orderBy('id', 'DESC')
			->get(20)
			->getResultArray();

		foreach ($rows as $row) {
			$candidates = [
				trim((string) ($row['gateway_request_id'] ?? '')),
				trim((string) ($row['request_id'] ?? '')),
			];

			$decoded = json_decode((string) ($row['response_json'] ?? ''), true);
			if (is_array($decoded)) {
				$candidates[] = trim((string) ($decoded['gateway_request_id'] ?? ''));
				$candidates[] = trim((string) ($decoded['request_id'] ?? ''));
				$candidates[] = trim((string) ($decoded['requestId'] ?? ''));
			}

			foreach ($candidates as $candidate) {
				if ($candidate !== '' && preg_match('/^REQ-/i', $candidate) === 1) {
					return $candidate;
				}
			}
		}

		return '';
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getLatestAbdmSyncSnapshot(string $abhaAddress): array
	{
		$snapshot = [
			'phase' => 'IDLE',
			'request_id' => '',
			'consent_request_id' => '',
			'consent_id' => '',
			'message' => 'No previous ABDM sync activity found.',
			'updated_at' => '',
			'operation' => '',
			'status' => '',
			'restart_required' => false,
		];

		$abhaAddress = trim($abhaAddress);
		if ($abhaAddress === '' || ! $this->db->tableExists('abdm_hiu_workflows')) {
			return $snapshot;
		}

		$fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];
		// See fetchAbdmWorkflowRows() for why response_json is capped to NULL
		// for data_fetch/hi_data_push_callback rows -- those can carry
		// multi-MB decrypted bundles with base64 attachments that this method
		// never reads fields out of anyway (only consent status/hi_types).
		$responseJsonExpr = "(CASE WHEN operation IN ('data_fetch', 'hi_data_push_callback') THEN NULL ELSE response_json END) AS response_json";
		$select = ['id', 'operation', 'workflow_state', 'status', 'request_id', 'consent_id', 'updated_at', 'last_error', 'http_code', $responseJsonExpr];
		if (in_array('gateway_request_id', $fields, true)) {
			$select[] = 'gateway_request_id';
		}
		if (in_array('abdm_consent_request_id', $fields, true)) {
			$select[] = 'abdm_consent_request_id';
		}
		if (in_array('abdm_consent_artifact_id', $fields, true)) {
			$select[] = 'abdm_consent_artifact_id';
		}

		$rows = $this->db->table('abdm_hiu_workflows')
			->select(implode(', ', $select))
			->where('abha_address', $abhaAddress)
			->whereIn('operation', [
				'consent_request',
				'consent_status',
				'consent_reconcile',
				'data_fetch',
				'consent_callback',
				'hi_on_request_callback',
				'hi_data_push_callback',
			])
			->orderBy('id', 'DESC')
			->get(40)
			->getResultArray();

		if ($rows === []) {
			return $snapshot;
		}

		// Scope this snapshot to the CURRENT (most recent) consent request
		// "session" only. Without this, the phase-priority scan below picks
		// whichever row ANYWHERE in the last 40 rows reached the highest
		// lifecycle stage (e.g. an older, already-GRANTED/COMPLETED consent),
		// even when the patient has since started a brand-new consent_request
		// that is still just REQUESTED -- silently resurrecting a stale/
		// unrelated session's consent_id and causing "Fetch Records"/live
		// status checks to operate on the wrong consent (confirmed 2026-07-30:
		// a new 4-facility consent request stayed masked behind a prior
		// single-facility consent that had already reached GRANTED earlier).
		$latestConsentRequestRow = $this->db->table('abdm_hiu_workflows')
			->select('id')
			->where('abha_address', $abhaAddress)
			->where('operation', 'consent_request')
			->orderBy('id', 'DESC')
			->get(1)
			->getRowArray();
		$latestSessionStartId = (int) ($latestConsentRequestRow['id'] ?? 0);
		if ($latestSessionStartId > 0) {
			$rows = array_values(array_filter(
				$rows,
				static fn (array $row): bool => (int) ($row['id'] ?? 0) >= $latestSessionStartId
			));
			if ($rows === []) {
				return $snapshot;
			}
		}

		$best = null;
		$bestPriority = -1;
		$bestGrantedOrCompleted = null;
		$bestGrantedOrCompletedId = -1;

		foreach ($rows as $row) {
			$decoded = json_decode((string) ($row['response_json'] ?? ''), true);
			if (! is_array($decoded)) {
				$decoded = [];
			}

			$rawConsentStatus = strtoupper(trim((string) (
				$decoded['consent']['status']
				?? $decoded['consent_status']
				?? $decoded['status']
				?? $decoded['data']['consent']['status']
				?? ''
			)));

			$requestId = trim((string) (
				$row['gateway_request_id']
				?? $row['request_id']
				?? $decoded['gateway_request_id']
				?? $decoded['request_id']
				?? $decoded['requestId']
				?? ''
			));
			$consentRequestId = trim((string) (
				$row['abdm_consent_request_id']
				?? $decoded['abdm_consent_request_id']
				?? $decoded['consent_request_id']
				?? $decoded['consentRequestId']
				?? ''
			));
			$consentId = trim((string) (
				$row['abdm_consent_artifact_id']
				?? $row['consent_id']
				?? $decoded['abdm_consent_artifact_id']
				?? $decoded['consent_id']
				?? $decoded['consentId']
				?? ''
			));

			$operation = strtoupper(trim((string) ($row['operation'] ?? '')));
			$status = strtoupper(trim((string) ($row['status'] ?? '')));
			$state = strtoupper(trim((string) ($row['workflow_state'] ?? '')));
			$errorText = trim((string) ($row['last_error'] ?? $decoded['error_text'] ?? ''));
			$httpCode = (int) ($row['http_code'] ?? $decoded['http_code'] ?? 0);

			$phase = 'REQUESTED';
			$priority = 120;

			// NOTE: `status` only reflects whether the bridge API call itself
			// succeeded (no HTTP/transport error) -- it does NOT mean the HIP
			// actually sent any health records (see M3HiuWorkflowService::
			// resolveState()/runOperation(), which always set status=success for
			// any error-free response, even one reporting decrypted_data=[]).
			// Whether data truly arrived is tracked separately via workflow_state
			// (DATA_PENDING vs DATA_RECEIVED); require that here too, otherwise a
			// broad/historical data_fetch attempt with empty decrypted_data can
			// wrongly hijack "latest consent" resolution with a stale consent_id.
			if (($operation === 'DATA_FETCH' || $operation === 'HI_DATA_PUSH_CALLBACK') && $status === 'SUCCESS' && $state === 'DATA_RECEIVED') {
				$phase = 'COMPLETED';
				$priority = 500;
			} elseif (in_array($rawConsentStatus, ['GRANTED', 'APPROVED', 'ACTIVE'], true)) {
				$phase = 'GRANTED';
				$priority = 430;
			} elseif (in_array($rawConsentStatus, ['REVOKED', 'DENIED', 'EXPIRED'], true)) {
				$phase = 'DENIED';
				$priority = 310;
			} elseif (in_array($state, ['DATA_RECEIVED'], true)) {
				$phase = 'COMPLETED';
				$priority = 480;
			} elseif (in_array($state, ['GRANTED'], true)) {
				$phase = 'GRANTED';
				$priority = 420;
			} elseif (in_array($state, ['REVOKED', 'EXPIRED'], true)) {
				$phase = 'DENIED';
				$priority = 300;
			} elseif ($status === 'FAILED' && $httpCode === 404 && stripos($errorText, 'consent record not found') !== false) {
				$phase = 'REQUESTED';
				$priority = 200;
			} elseif ($status === 'FAILED' && $operation === 'CONSENT_REQUEST') {
				// Only a failed *submission* of the consent request itself is terminal.
				// A failed status-check/reconcile/data-fetch attempt is transient bridge
				// noise and must NOT force HMS to create a brand-new consent request
				// (which would show up as a duplicate entry in the patient's PHR app).
				$phase = 'FAILED';
				$priority = 260;
			} elseif ($status === 'FAILED') {
				$phase = 'REQUESTED';
				$priority = 190;
			} elseif (in_array($state, ['REQUESTED', 'PENDING', 'STATUS_CHECKED'], true)) {
				$phase = 'REQUESTED';
				$priority = 180;
			}

			$message = 'Last status loaded from previous ABDM sync.';
			if ($phase === 'COMPLETED') {
				$message = 'Last sync completed successfully.';
			} elseif ($phase === 'GRANTED') {
				$message = 'Consent was granted. Continue sync to fetch records.';
			} elseif ($phase === 'DENIED') {
				$message = 'Consent was denied/revoked/expired in previous attempt.';
			} elseif ($status === 'FAILED' && $httpCode === 404) {
				$message = 'Last consent request is still being processed by bridge. Please wait and refresh.';
			} elseif ($phase === 'FAILED') {
				$message = 'Last consent request failed. Start a new consent request.';
			}

			if ($best === null || $priority > $bestPriority) {
				$best = [
					'id' => (int) ($row['id'] ?? 0),
					'phase' => $phase,
					'request_id' => $requestId,
					'consent_request_id' => $consentRequestId,
					'consent_id' => $consentId,
					'message' => $message,
					'updated_at' => trim((string) ($row['updated_at'] ?? '')),
					'operation' => strtolower(trim((string) ($row['operation'] ?? ''))),
					'status' => strtolower(trim((string) ($row['status'] ?? ''))),
				];
				$bestPriority = $priority;
			}

			if (in_array($phase, ['GRANTED', 'COMPLETED'], true)) {
				$currentId = (int) ($row['id'] ?? 0);
				if ($currentId > $bestGrantedOrCompletedId) {
					$bestGrantedOrCompleted = [
						'id' => $currentId,
						'phase' => $phase,
						'request_id' => $requestId,
						'consent_request_id' => $consentRequestId,
						'consent_id' => $consentId,
						'message' => $message,
						'updated_at' => trim((string) ($row['updated_at'] ?? '')),
						'operation' => strtolower(trim((string) ($row['operation'] ?? ''))),
						'status' => strtolower(trim((string) ($row['status'] ?? ''))),
					];
					$bestGrantedOrCompletedId = $currentId;
				}
			}
		}

		if (! is_array($best)) {
			return $snapshot;
		}

		// Guardrail: if top-ranked row is transient pending/not-found, but we already
		// have a more recent granted/completed record, prefer the success state.
		if (
			is_array($bestGrantedOrCompleted)
			&& in_array((string) ($best['phase'] ?? ''), ['REQUESTED', 'PENDING'], true)
			&& (string) ($best['status'] ?? '') === 'failed'
			&& stripos((string) ($best['message'] ?? ''), 'processed by bridge') !== false
		) {
			$best = $bestGrantedOrCompleted;
		}

		if (($best['request_id'] ?? '') === '') {
			foreach ($rows as $row) {
				$decoded = json_decode((string) ($row['response_json'] ?? ''), true);
				if (! is_array($decoded)) {
					$decoded = [];
				}
				$fallbackRequestId = trim((string) (
					$row['gateway_request_id']
					?? $row['request_id']
					?? $decoded['gateway_request_id']
					?? $decoded['request_id']
					?? $decoded['requestId']
					?? ''
				));
				if ($fallbackRequestId !== '') {
					$best['request_id'] = $fallbackRequestId;
					break;
				}
			}
		}

		$snapshot['phase'] = (string) ($best['phase'] ?? 'REQUESTED');
		$snapshot['request_id'] = (string) ($best['request_id'] ?? '');
		$snapshot['consent_request_id'] = (string) ($best['consent_request_id'] ?? '');
		$snapshot['consent_id'] = (string) ($best['consent_id'] ?? '');
		$snapshot['message'] = (string) ($best['message'] ?? 'Last status loaded from previous ABDM sync.');
		$snapshot['updated_at'] = (string) ($best['updated_at'] ?? '');
		$snapshot['operation'] = (string) ($best['operation'] ?? '');
		$snapshot['status'] = (string) ($best['status'] ?? '');

		// A consent request stuck in REQUESTED/PENDING for more than ABDM_PENDING_STALE_SECONDS
		// (1 hour) with no GRANTED/DENIED callback from the bridge is considered abandoned by
		// PHR/ABHA — the patient likely never saw/approved it, or the bridge silently dropped
		// it. Flag it so the UI/auto-flow can start a brand-new consent request instead of
		// resuming (and thus indefinitely polling) a request that will never resolve.
		$isStalePending = false;
		if (in_array($snapshot['phase'], ['REQUESTED', 'PENDING'], true) && $snapshot['updated_at'] !== '') {
			try {
				$updatedAt = new \DateTimeImmutable($snapshot['updated_at'], new \DateTimeZone('Asia/Kolkata'));
				$nowIst = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Kolkata'));
				$pendingAgeSeconds = $nowIst->getTimestamp() - $updatedAt->getTimestamp();
				$isStalePending = $pendingAgeSeconds > self::ABDM_PENDING_STALE_SECONDS;
			} catch (\Throwable $e) {
				$isStalePending = false;
			}
		}

		if ($isStalePending) {
			$snapshot['message'] = 'Previous consent request has been pending for over 1 hour with no response from ABDM/PHR. Click "Fetch ABDM Records" to start a fresh request.';
		}

		$snapshot['restart_required'] = (bool) (
			($snapshot['phase'] === 'FAILED')
			|| (($snapshot['phase'] === 'REQUESTED')
				&& ($snapshot['status'] === 'failed')
				&& stripos((string) ($snapshot['message'] ?? ''), 'not found on bridge') !== false)
			|| $isStalePending
		);

		return $snapshot;
	}

	/**
	 * Builds the requested-vs-granted Health Information Type breakdown for a patient's
	 * current ABDM consent artifact, for the "View Consent" detail modal.
	 *
	 * Per ABDM M3 spec (confirmed with bridge team 2026-07-28): revocation is
	 * whole-artifact only (no partial per-HI-type revoke), so a single overall
	 * `status` + the granted `hi_types` array is sufficient to compute each
	 * requested type's effective status:
	 *   - GRANTED  : type is in the granted hi_types array and artifact status is GRANTED
	 *   - DENIED   : type was requested but is NOT in the granted hi_types array
	 *   - REVOKED  : artifact status is REVOKED (applies to the whole artifact)
	 *   - EXPIRED  : artifact status is EXPIRED
	 *   - REQUESTED: artifact is still pending (no GRANTED/REVOKED/EXPIRED yet)
	 */
	private function getAbdmConsentArtifactDetail(string $abhaAddress): array
	{
		$abhaAddress = trim($abhaAddress);
		if ($abhaAddress === '' || ! $this->db->tableExists('abdm_hiu_workflows')) {
			return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
		}

		$rows = $this->fetchAbdmWorkflowRows($abhaAddress);
		if ($rows === []) {
			return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
		}

		$sessions = $this->groupWorkflowRowsIntoSessions($rows);
		if ($sessions === []) {
			return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
		}

		// Sessions are chronological (oldest first). Skip synthetic "orphan"
		// sessions with no owning CONSENT_REQUEST row (stray per-artifact
		// re-poll rows that never correlated back to a real request, e.g. a
		// one-off manual test call) so the most recent REAL request is used.
		for ($i = count($sessions) - 1; $i >= 0; $i--) {
			$hasAnchor = false;
			foreach ($sessions[$i] as $sessionRow) {
				if (strtoupper(trim((string) ($sessionRow['operation'] ?? ''))) === 'CONSENT_REQUEST') {
					$hasAnchor = true;
					break;
				}
			}
			if ($hasAnchor) {
				return $this->computeConsentSessionDetail($sessions[$i], $abhaAddress);
			}
		}

		return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
	}

	/**
	 * Builds one detail entry (same shape as getAbdmConsentArtifactDetail) per
	 * distinct consent request the patient has had over time, for the "Consent
	 * Request History" list — most recent first.
	 */
	private function getAbdmConsentRequestsList(string $abhaAddress): array
	{
		$abhaAddress = trim($abhaAddress);
		if ($abhaAddress === '' || ! $this->db->tableExists('abdm_hiu_workflows')) {
			return ['ok' => 1, 'requests' => []];
		}

		$rows = $this->fetchAbdmWorkflowRows($abhaAddress);
		if ($rows === []) {
			return ['ok' => 1, 'requests' => []];
		}

		$sessions = $this->groupWorkflowRowsIntoSessions($rows);
		$requests = [];
		foreach ($sessions as $sessionRows) {
			// Skip synthetic "orphan" sessions with no owning CONSENT_REQUEST
			// row (stray per-artifact re-poll rows whose internal request_id
			// never correlated back to a real request -- e.g. a one-off manual
			// test call). These don't represent an actual request the user
			// initiated and would otherwise show up as extra incomplete rows
			// in the Consent Request History table.
			$hasAnchor = false;
			foreach ($sessionRows as $sessionRow) {
				if (strtoupper(trim((string) ($sessionRow['operation'] ?? ''))) === 'CONSENT_REQUEST') {
					$hasAnchor = true;
					break;
				}
			}
			if (! $hasAnchor) {
				continue;
			}

			$detail = $this->computeConsentSessionDetail($sessionRows, $abhaAddress);
			if ((int) ($detail['ok'] ?? 0) === 1) {
				$requests[] = $detail['consent'];
			}
		}

		return ['ok' => 1, 'requests' => array_reverse($requests)];
	}

	/**
	 * Fetches the raw consent-related workflow rows for a patient's ABHA address,
	 * most recent first. Shared by getAbdmConsentArtifactDetail() and
	 * getAbdmConsentRequestsList().
	 */
	private function fetchAbdmWorkflowRows(string $abhaAddress): array
	{
		$fields = $this->db->getFieldNames('abdm_hiu_workflows') ?? [];

		// `data_fetch` / `hi_data_push_callback` rows can carry the ENTIRE
		// decrypted FHIR bundle in response_json -- including base64 scanned
		// document attachments that are sometimes multiple MB per row. None of
		// the fields this list actually reads out of response_json (consent
		// status, hi_types, granted_at) ever appear in those bulk-data
		// payloads, so cap it out at the SQL level for those two operations to
		// avoid buffering tens/hundreds of MB into PHP memory just to build a
		// consent-history list (this previously caused a fatal "Allowed memory
		// size exhausted" error once enough data_fetch rows accumulated).
		$responseJsonExpr = "(CASE WHEN operation IN ('data_fetch', 'hi_data_push_callback') THEN NULL ELSE response_json END) AS response_json";
		$select = ['id', 'operation', 'workflow_state', 'status', 'request_id', 'consent_id', 'hfr_id', 'created_at', 'updated_at', 'completed_at', 'expired_at', 'revoked_at', 'last_error', 'http_code', 'request_json', $responseJsonExpr];
		foreach (['abdm_consent_request_id', 'abdm_consent_artifact_id', 'gateway_request_id'] as $optionalField) {
			if (in_array($optionalField, $fields, true)) {
				$select[] = $optionalField;
			}
		}
		$selectSql = implode(', ', $select);

		// The per-artifact re-poll loop (M3HiuWorkflowService::pollNatGateway())
		// can generate many `data_fetch` rows per consent per cron cycle. A flat
		// "most recent 200 rows" window can therefore push an older but still-
		// relevant CONSENT_REQUEST anchor row out of range, which breaks
		// groupWorkflowRowsIntoSessions() (it requires every session to start
		// with its own CONSENT_REQUEST row) and produces a blank/orphaned
		// session in the UI. Always include every CONSENT_REQUEST row for this
		// ABHA (there are only ever a handful) regardless of the recent-activity
		// window, then merge with the most recent 200 rows of any operation.
		$anchorRows = $this->db->table('abdm_hiu_workflows')
			->select($selectSql)
			->where('abha_address', $abhaAddress)
			->where('operation', 'consent_request')
			->orderBy('id', 'DESC')
			->get(100)
			->getResultArray();

		$recentRows = $this->db->table('abdm_hiu_workflows')
			->select($selectSql)
			->where('abha_address', $abhaAddress)
			->whereIn('operation', [
				'consent_request',
				'consent_status',
				'consent_reconcile',
				'data_fetch',
				'consent_callback',
				'hi_on_request_callback',
				'hi_data_push_callback',
			])
			->orderBy('id', 'DESC')
			->get(200)
			->getResultArray();

		$merged = [];
		foreach (array_merge($anchorRows, $recentRows) as $row) {
			$merged[(int) $row['id']] = $row;
		}
		krsort($merged);

		return array_values($merged);
	}

	/**
	 * Splits a DESC-by-id set of workflow rows into distinct consent request
	 * "sessions", correlating rows by their STABLE identifiers (request_id /
	 * abdm_consent_request_id / consent_id) rather than simple chronological
	 * proximity to the most recent CONSENT_REQUEST row.
	 *
	 * IMPORTANT: a naive "everything after the newest CONSENT_REQUEST row
	 * belongs to it" split (the original implementation) breaks as soon as
	 * background polling (M3HiuWorkflowService::pollNatGateway()) reconciles
	 * EVERY historical consent_request in a single batch -- those older
	 * sessions' consent_reconcile/data_fetch rows all land chronologically
	 * AFTER a brand new consent_request row and would be misattributed to it,
	 * silently merging an unrelated older session's facility documents into
	 * the newest session's "Show Data" view (verified 2026-07-30: a 4-facility
	 * session displayed a completely different, older single-facility
	 * session's documents because of this).
	 *
	 * Each CONSENT_REQUEST row starts a new session keyed by its own
	 * `request_id`. Every other row is attributed to a session by (in order
	 * of preference): (1) a previously-learned abdm_consent_request_id match,
	 * (2) a previously-learned request_id/consent_id match, (3) falling back
	 * to the most recently opened session that has not yet resolved its own
	 * abdm_consent_request_id (covers the very first reconcile call right
	 * after creation, which sometimes uses the gateway's own freshly-minted
	 * request_id instead of preserving HMS's tracking id). Once a session's
	 * abdm_consent_request_id/request_id is known, that binding persists for
	 * every later row regardless of how much later it arrives.
	 *
	 * @param array<int, array<string, mixed>> $rows rows ordered DESC by id
	 * @return array<int, array<int, array<string, mixed>>>
	 */
	private function groupWorkflowRowsIntoSessions(array $rows): array
	{
		$chronological = array_reverse($rows);

		$sessions = [];
		$order = [];
		$abdmIdToAnchor = [];
		$requestIdToAnchor = [];
		$openAnchor = null;

		foreach ($chronological as $row) {
			$operation = strtoupper(trim((string) ($row['operation'] ?? '')));
			$rowRequestId = trim((string) ($row['request_id'] ?? ''));
			$rowAbdmConsentRequestId = trim((string) ($row['abdm_consent_request_id'] ?? ''));
			$rowConsentId = trim((string) ($row['consent_id'] ?? ''));

			if ($operation === 'CONSENT_REQUEST') {
				$anchorKey = 'anchor_' . count($order);
				$order[] = $anchorKey;
				$sessions[$anchorKey] = [$row];
				if ($rowRequestId !== '') {
					$requestIdToAnchor[$rowRequestId] = $anchorKey;
				}
				$openAnchor = $anchorKey;
				continue;
			}

			$anchorKey = null;
			if ($rowAbdmConsentRequestId !== '' && isset($abdmIdToAnchor[$rowAbdmConsentRequestId])) {
				$anchorKey = $abdmIdToAnchor[$rowAbdmConsentRequestId];
			} elseif ($rowRequestId !== '' && isset($requestIdToAnchor[$rowRequestId])) {
				$anchorKey = $requestIdToAnchor[$rowRequestId];
			} elseif ($rowConsentId !== '' && isset($requestIdToAnchor[$rowConsentId])) {
				$anchorKey = $requestIdToAnchor[$rowConsentId];
			} elseif ($openAnchor !== null) {
				$anchorKey = $openAnchor;
			}

			if ($anchorKey === null) {
				// No matching anchor at all (should not normally happen) --
				// start a synthetic session so the row is not silently dropped.
				$anchorKey = 'orphan_' . count($order);
				$order[] = $anchorKey;
				$sessions[$anchorKey] = [];
			}

			$sessions[$anchorKey][] = $row;

			if ($anchorKey === $openAnchor) {
				$openAnchor = null;
			}
			if ($rowAbdmConsentRequestId !== '') {
				$abdmIdToAnchor[$rowAbdmConsentRequestId] = $anchorKey;
			}
			if ($rowRequestId !== '') {
				$requestIdToAnchor[$rowRequestId] = $anchorKey;
			}
			if ($rowConsentId !== '') {
				$requestIdToAnchor[$rowConsentId] = $anchorKey;
			}
		}

		$result = [];
		foreach ($order as $anchorKey) {
			if (! empty($sessions[$anchorKey])) {
				$result[] = $sessions[$anchorKey];
			}
		}

		return $result;
	}

	/**
	 * Computes the requested-vs-granted Health Information Type breakdown for a
	 * single consent request "session" (one CONSENT_REQUEST row plus its
	 * subsequent status/reconcile/data_fetch/callback rows).
	 *
	 * @param array<int, array<string, mixed>> $rows the session's rows (any order)
	 */
	private function computeConsentSessionDetail(array $rows, string $abhaAddress): array
	{
		if ($rows === []) {
			return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
		}

		$best = null;
		$bestPriority = -1;
		$bestDecoded = [];

		foreach ($rows as $row) {
			$decoded = json_decode((string) ($row['response_json'] ?? ''), true);
			if (! is_array($decoded)) {
				$decoded = [];
			}

			$rawConsentStatus = strtoupper(trim((string) (
				$decoded['consent']['status']
				?? $decoded['consent_status']
				?? $decoded['status']
				?? $decoded['data']['consent']['status']
				?? ''
			)));
			$operation = strtoupper(trim((string) ($row['operation'] ?? '')));
			$status = strtoupper(trim((string) ($row['status'] ?? '')));
			$state = strtoupper(trim((string) ($row['workflow_state'] ?? '')));

			$phase = 'REQUESTED';
			$priority = 120;

			// NOTE: the `status` column only reflects whether the underlying API
			// call/poll succeeded (no HTTP/transport error) — it does NOT mean
			// data was actually received (see M3HiuWorkflowService::runOperation()
			// / ingestHealthInformationCallback(), which always set status=success
			// for any error-free response, even one reporting decrypted_data=[]
			// and consent/session status "requested"). Whether data truly arrived
			// is tracked separately in workflow_state (DATA_PENDING vs
			// DATA_RECEIVED), so require that here instead of the generic status.
			if (($operation === 'DATA_FETCH' || $operation === 'HI_DATA_PUSH_CALLBACK') && $status === 'SUCCESS' && $state === 'DATA_RECEIVED') {
				$phase = 'COMPLETED';
				$priority = 500;
			} elseif (in_array($rawConsentStatus, ['GRANTED', 'APPROVED', 'ACTIVE'], true)) {
				$phase = 'GRANTED';
				$priority = 430;
			} elseif ($rawConsentStatus === 'REVOKED') {
				$phase = 'REVOKED';
				$priority = 320;
			} elseif ($rawConsentStatus === 'EXPIRED') {
				$phase = 'EXPIRED';
				$priority = 310;
			} elseif ($rawConsentStatus === 'DENIED') {
				$phase = 'DENIED';
				$priority = 300;
			} elseif ($state === 'DATA_RECEIVED') {
				$phase = 'COMPLETED';
				$priority = 480;
			} elseif ($state === 'GRANTED') {
				$phase = 'GRANTED';
				$priority = 420;
			} elseif ($state === 'REVOKED') {
				$phase = 'REVOKED';
				$priority = 300;
			} elseif ($state === 'EXPIRED') {
				$phase = 'EXPIRED';
				$priority = 290;
			} elseif ($status === 'FAILED' && $operation === 'CONSENT_REQUEST') {
				$phase = 'FAILED';
				$priority = 260;
			} elseif ($status === 'FAILED') {
				$phase = 'REQUESTED';
				$priority = 190;
			} elseif (in_array($state, ['REQUESTED', 'PENDING', 'STATUS_CHECKED'], true)) {
				$phase = 'REQUESTED';
				$priority = 180;
			}

			if ($best === null || $priority > $bestPriority) {
				$best = $row;
				$best['_phase'] = $phase;
				$bestPriority = $priority;
				$bestDecoded = $decoded;
			}
		}

		if ($best === null) {
			return ['ok' => 0, 'error' => 'No ABDM consent activity found for this patient.'];
		}

		$phase = (string) $best['_phase'];

		// IMPORTANT: do NOT read consent_id/consent_request_id only from the
		// "best" (highest-priority phase) row. The initial consent_request row
		// and its immediate consent_reconcile/consent_status follow-up often
		// tie on priority (both still "REQUESTED" phase) while only the LATER
		// row actually carries the real consent_id assigned by the bridge —
		// and the priority tie-break above keeps whichever row was seen FIRST
		// (chronologically oldest), so the blank consent_id from the original
		// consent_request row was winning and hiding the real id (confirmed
		// via abdm_hiu_workflows: consent_request row had consent_id='' while
		// its own consent_reconcile row moments later had a real UUID). Scan
		// every row in the session (already chronological, oldest first) and
		// keep the LAST non-empty value found so the most recent known id wins.
		$consentId = '';
		$consentRequestId = '';
		foreach ($rows as $row) {
			$rowDecoded = json_decode((string) ($row['response_json'] ?? ''), true);
			if (! is_array($rowDecoded)) {
				$rowDecoded = [];
			}

			$rowConsentId = trim((string) (
				$row['abdm_consent_artifact_id']
				?? $row['consent_id']
				?? $rowDecoded['consent_id']
				?? $rowDecoded['consentId']
				?? ''
			));
			if ($rowConsentId !== '') {
				$consentId = $rowConsentId;
			}

			$rowConsentRequestId = trim((string) (
				$row['abdm_consent_request_id']
				?? $rowDecoded['abdm_consent_request_id']
				?? $rowDecoded['consent_request_id']
				?? $rowDecoded['consentRequestId']
				?? ''
			));
			if ($rowConsentRequestId !== '') {
				$consentRequestId = $rowConsentRequestId;
			}
		}

		// The session should start with its own CONSENT_REQUEST row.
		$consentRequestRow = null;
		foreach ($rows as $row) {
			if (strtoupper((string) ($row['operation'] ?? '')) === 'CONSENT_REQUEST') {
				$consentRequestRow = $row;
				break;
			}
		}

		// A short, human-friendly identifier for this session (the "Request ID"
		// guid is too long to display in a table column) -- use the underlying
		// abdm_hiu_workflows row id of this session's own CONSENT_REQUEST row
		// (falling back to whichever row scored best) since it's already a
		// small, unique, DB-backed integer.
		$displayId = (int) (is_array($consentRequestRow) ? ($consentRequestRow['id'] ?? 0) : 0);
		if ($displayId <= 0) {
			$displayId = (int) ($best['id'] ?? 0);
		}


		$requestedHiTypes = [];
		$requestedOn = '';
		$purpose = '';
		$validFrom = '';
		$validTo = '';
		$eraseAt = '';
		$requestedBy = '';
		$hfrId = trim((string) ($best['hfr_id'] ?? ''));

		if (is_array($consentRequestRow)) {
			$reqPayload = json_decode((string) ($consentRequestRow['request_json'] ?? ''), true);
			if (! is_array($reqPayload)) {
				$reqPayload = [];
			}
			$consentBlock = (array) ($reqPayload['consent'] ?? []);
			$requestedHiTypes = $this->normalizeHiTypesList($consentBlock['hiTypes'] ?? $consentBlock['hi_types'] ?? []);
			$requestedOn = trim((string) ($consentRequestRow['created_at'] ?? ''));
			$purpose = trim((string) ($consentBlock['purpose']['text'] ?? $consentBlock['purpose']['code'] ?? ''));
			$validFrom = trim((string) ($consentBlock['permission']['dateRange']['from'] ?? ''));
			$validTo = trim((string) ($consentBlock['permission']['dateRange']['to'] ?? ''));
			$eraseAt = trim((string) ($consentBlock['permission']['dataEraseAt'] ?? ''));
			$requestedBy = trim((string) ($consentBlock['requester']['name'] ?? ''));
			if ($hfrId === '') {
				$hfrId = trim((string) ($consentRequestRow['hfr_id'] ?? ''));
			}
		}

		// Bridge flattens consent.hi_types -> top-level hi_types on consent_status /
		// consent_reconcile / consent_callback responses (see M3HiuGatewayClient
		// flatten logic). The "best" row for overall phase can be a data_fetch /
		// hi_data_push_callback row (once records are COMPLETED) whose response is
		// a FHIR data bundle with no hi_types field at all — so look separately for
		// the most recent row (within this session) that actually carries the
		// granted hi_types payload.
		$grantedHiTypes = [];
		$grantedOn = '';
		foreach ($rows as $row) {
			$decoded = json_decode((string) ($row['response_json'] ?? ''), true);
			if (! is_array($decoded)) {
				continue;
			}
			$rowHiTypes = $this->normalizeHiTypesList($decoded['hi_types'] ?? $decoded['consent']['hi_types'] ?? []);
			if ($rowHiTypes !== []) {
				$grantedHiTypes = $rowHiTypes;
				$grantedOn = trim((string) ($decoded['granted_at'] ?? $row['updated_at'] ?? ''));
				break;
			}
		}

		// If this session's overall phase is GRANTED/COMPLETED but no row ever
		// recorded WHICH hi_types were granted (e.g. data arrived via an async
		// hi_data_push_callback before any consent_reconcile/consent_status poll
		// ran and stored a hi_types-bearing response), do not mark every
		// requested type as DENIED for lack of evidence — a COMPLETED/GRANTED
		// phase is itself proof the consent covers at least the requested
		// types, so fall back to treating the full requested set as granted.
		if ($grantedHiTypes === [] && $requestedHiTypes !== [] && in_array($phase, ['GRANTED', 'COMPLETED'], true)) {
			$grantedHiTypes = $requestedHiTypes;
		}

		$revokedOn = trim((string) ($best['revoked_at'] ?? ''));
		$expiredOn = trim((string) ($best['expired_at'] ?? ''));
		if (in_array($phase, ['GRANTED', 'COMPLETED'], true) && $grantedOn === '') {
			$grantedOn = trim((string) ($bestDecoded['granted_at'] ?? $best['updated_at'] ?? ''));
		}

		$items = [];
		$typesForItems = $requestedHiTypes !== [] ? $requestedHiTypes : $grantedHiTypes;
		foreach ($typesForItems as $hiType) {
			$itemStatus = 'REQUESTED';
			$itemTimestamp = $requestedOn;

			if ($phase === 'REVOKED') {
				$itemStatus = 'REVOKED';
				$itemTimestamp = $revokedOn;
			} elseif ($phase === 'EXPIRED') {
				$itemStatus = 'EXPIRED';
				$itemTimestamp = $expiredOn;
			} elseif (in_array($phase, ['GRANTED', 'COMPLETED'], true)) {
				if (in_array($hiType, $grantedHiTypes, true)) {
					$itemStatus = 'GRANTED';
					$itemTimestamp = $grantedOn;
				} else {
					$itemStatus = 'DENIED';
					$itemTimestamp = $grantedOn;
				}
			} elseif ($phase === 'FAILED') {
				$itemStatus = 'FAILED';
				$itemTimestamp = trim((string) ($best['updated_at'] ?? ''));
			}

			$items[] = [
				'document_name' => $hiType,
				'permission' => 'VIEW',
				'status' => $itemStatus,
				'timestamp' => $itemTimestamp,
			];
		}

		return [
			'ok' => 1,
			'consent' => [
				'id' => $displayId,
				'consent_id' => $consentId,
				'consent_request_id' => $consentRequestId,
				'abha_address' => $abhaAddress,
				'status' => $phase,
				'purpose' => $purpose !== '' ? $purpose : 'Care Management',
				'requested_hi_types' => $requestedHiTypes,
				'granted_hi_types' => $grantedHiTypes,
				'valid_from' => $validFrom,
				'valid_to' => $validTo,
				'erase_at' => $eraseAt,
				'requested_on' => $requestedOn,
				'granted_on' => $grantedOn,
				'revoked_on' => $revokedOn,
				'expired_on' => $expiredOn,
				'hfr_id' => $hfrId,
				'requested_by' => $requestedBy !== '' ? $requestedBy : 'HMS',
				'items' => $items,
			],
		];
	}

	/**
	 * Normalizes a hiTypes value (JSON string, array, or single string) into a clean,
	 * de-duplicated string array.
	 *
	 * @param mixed $value
	 */
	private function normalizeHiTypesList($value): array
	{
		if (is_string($value)) {
			$decoded = json_decode($value, true);
			$value = is_array($decoded) ? $decoded : [$value];
		}
		if (! is_array($value)) {
			return [];
		}

		$out = [];
		foreach ($value as $v) {
			$v = trim((string) $v);
			if ($v !== '' && ! in_array($v, $out, true)) {
				$out[] = $v;
			}
		}

		return $out;
	}


}
