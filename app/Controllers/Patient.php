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
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata ?? ''));

		$data['search_query'] = $sdata;
		return view('billing/Patient_Search_V', $data);
	}

	public function search_ajax()
	{
		$request = $this->request->getGet();
		
		// Get search value from DataTables search box or from initial search_query
		$dtSearch = trim((string) ($request['search']['value'] ?? ''));
		$initialSearch = trim((string) ($request['search_query'] ?? ''));
		
		// Use DataTables search if provided, otherwise use initial search_query
		$sdata = $dtSearch !== '' ? $dtSearch : $initialSearch;
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', $sdata);

		// Detect ABHA column name in patient_master
		$abhaField = null;
		$pmFields  = $this->db->getFieldNames('patient_master') ?? [];
		foreach (['abha_id', 'abha_no', 'abha', 'abha_address'] as $f) {
			if (in_array($f, $pmFields, true)) { $abhaField = $f; break; }
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
					$abhaClause = $abhaField ? " or p.{$abhaField} = " . $this->db->escape($rowData) : '';
					$searchString .= " and (p.p_code like " . $this->db->escape('%' . $rowData) . " 
									or p.mphone1 = " . $this->db->escape($rowData) . " 
									or p.udai=" . $this->db->escape($rowData) . $abhaClause . ")";
				} elseif (ctype_alpha($rowData)) {
					$searchString .= " and (p.p_fname like " . $this->db->escape('%' . $rowData . '%') . " 
						or p.email1 = " . $this->db->escape($rowData) . " 
						or SUBSTRING_INDEX(p.p_fname,' ',1) sounds like " . $this->db->escape($rowData) . ")";
				} else {
					// Handle dashed ABHA format: XX-XXXX-XXXX-XXXX
					$rawDigits = preg_replace('/\D/', '', $rowData);
					$abhaElse  = ($abhaField && strlen($rawDigits) === 14)
						? " or p.{$abhaField} = " . $this->db->escape($rawDigits) . " or p.{$abhaField} = " . $this->db->escape($rowData)
						: '';
					$searchString .= " and (p.p_code like " . $this->db->escape($rowData) . " 
						or p.email1 = " . $this->db->escape($rowData) . $abhaElse . ")";
				}
			}
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
			
			$data[] = [
				$start + $index + 1,
				'<a href="javascript:load_form(\'' . base_url('billing/patient/person_record/' . $patientId) . '\');">' . esc($row->p_code ?? '') . '</a>',
				esc(($row->p_fname ?? '') . ' {' . ($row->p_rname ?? '') . '}'),
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

		return view('billing/Patient_Profile_Image_V', [
			'patient' => $patient,
			'profileFilePath' => $profileFilePath,
		]);
	}

	public function show_profile_opd(int $pno, int $edit = 0)
	{
		$patient = $this->db->table('patient_master')->where('id', $pno)->get()->getRow();
		if (!$patient) {
			return $this->response->setStatusCode(404)->setBody('Patient not found');
		}

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
			'opd_code',
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
			'opdGroups' => $opdGroups,
			'backUrl' => $backUrl,
			'backTitle' => $backTitle,
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


}
