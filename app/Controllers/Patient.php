<?php

namespace App\Controllers;

use App\Controllers\BaseController;
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
		$abhaId = trim((string) $this->request->getPost('input_abha_id'));

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

		if ($abhaId !== '' && ! $this->isValidAbhaId($abhaId)) {
			if ($isAjax) {
				return $this->response->setJSON([
					'insertid' => 0,
					'error_text' => 'ABHA ID must be a 14-digit number.',
				]);
			}

			return redirect()->to(base_url('billing/patient'))
				->withInput()
				->with('error', 'ABHA ID must be a 14-digit number.');
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
		$this->applyPatientAbhaFieldValues($data, $abhaId);

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
		$inputRelativeName = trim((string) $this->request->getPost('input_relative_name'));
		$inputName = trim((string) $this->request->getPost('input_name'));

		$builder = $this->db->table('patient_master');
		$builder->select("id, CONCAT(p_fname,'/',IF(gender=1,'M','F'),'/',p_relative,' ',p_rname) AS Sresult");

		$hasCondition = false;
		if ($inputMphone1 !== '') {
			$builder->orWhere('mphone1', $inputMphone1);
			$hasCondition = true;
		}

		if ($inputAadhar !== '') {
			$builder->orWhere('udai', $inputAadhar);
			$hasCondition = true;
		}

		if ($inputRelativeName !== '') {
			$builder->groupStart()
				->where('p_rname', $inputRelativeName)
				->where('p_fname', $inputName)
				->groupEnd();
			$hasCondition = true;
		}

		$searchResult = [];
		if ($hasCondition) {
			$searchResult = $builder->orderBy('id', 'DESC')->get()->getResult();
		}

		return view('billing/search_adv_data', ['search_result' => $searchResult]);
	}

	public function search()
	{
		$sdata = (string) $this->request->getPost('txtsearch');
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata ?? ''));

		if (strlen($sdata) === 0) {
			$sql = "SELECT p.*, 
					Date_Format(p.last_visit,'%d-%m-%Y')  AS Last_Visit
					FROM patient_master p 
					GROUP BY p.id 
					ORDER BY p.last_visit DESC
					LIMIT 100 ";
		} else {
			$sdateArray = explode(' ', $sdata);
			$searchString = ' 1=1 ';

			foreach ($sdateArray as $rowData) {
				if ($rowData === '') {
					continue;
				}

				if (is_numeric($rowData)) {
					$searchString .= " and (p.p_code like '%$rowData' 
									or p.mphone1 = '$rowData' 
									or p.udai='$rowData' )";
				} elseif (ctype_alpha($rowData)) {
					$searchString .= " and (p.p_fname like '%$rowData%' 
						or p.email1 = '$rowData' 
						or SUBSTRING_INDEX(p.p_fname,' ',1) sounds like '$rowData')";
				} else {
					$searchString .= " and (p.p_code like '$rowData' 
						or p.email1 = '$rowData' )";
				}
			}

			$sql = "SELECT p.*, 
					Date_Format(p.last_visit,'%d-%m-%Y') AS Last_Visit
					FROM patient_master p 
					WHERE  $searchString
					GROUP BY p.id 
					ORDER BY p.last_visit DESC";
		}

		$query = $this->db->query($sql);
		$data['data'] = $query->getResult();

		return view('billing/Patient_Search_V', $data);
	}

	public function person_record(int $pno, int $edit = 0)
	{
		$sql = "select *,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,
		if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
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

        if ($this->db->fieldExists('profile_picture', 'patient_master')) {
            $profile_picture_path = (string) ($data['data'][0]->profile_picture ?? '');
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
		$abhaId = trim((string) $this->request->getPost('input_abha_id'));
		$age_month = (string) $this->request->getPost('input_age_month');
		$age_year = (string) $this->request->getPost('input_age_year');

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

		if ($abhaId !== '' && ! $this->isValidAbhaId($abhaId)) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => 'ABHA ID must be a 14-digit number.',
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
		$this->applyPatientAbhaFieldValues($data, $abhaId);

		if ($chk_age === 'on') {
			$data['age'] = $age_year;
			$data['age_in_month'] = $age_month;
		} else {
			$data['dob'] = $this->parseDate($this->request->getPost('datepicker_dob'));
		}

		$pid = (int) $this->request->getPost('p_id');
		$patientModel = new PatientModel();
		$patientModel->updatePatient($data, $pid);
		$this->saveNamesToNameList([
			(string) $this->request->getPost('input_name'),
			(string) $this->request->getPost('input_relative_name'),
		]);

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

		$pid = (int) $this->request->getPost('p_id');
		$abhaId = trim((string) $this->request->getPost('abha_id'));

		if ($abhaId !== '' && ! $this->isValidAbhaId($abhaId)) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => 'ABHA ID must be a 14-digit number.',
			]);
		}

		$data = [];
		$this->applyPatientAbhaFieldValues($data, $abhaId);
		if ($data === []) {
			return $this->response->setJSON([
				'update' => 0,
				'error_text' => 'ABHA field not found in patient master.',
			]);
		}

		$patientModel = new PatientModel();
		$patientModel->updatePatient($data, $pid);

		return $this->response->setJSON([
			'update' => 1,
			'showcontent' => 'Data Saved successfully',
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

		$opdList = $this->db->table('opd_master')
			->select('opd_id, opd_code, doc_name, apointment_date, queue_no')
			->where('p_id', $pno)
			->orderBy('opd_id', 'DESC')
			->get()
			->getResultArray();

		$opdIds = array_column($opdList, 'opd_id');
		$filesByOpd = [];

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

		$opdGroups = [];
		foreach ($opdList as $row) {
			$opdId = (int) $row['opd_id'];
			$opdGroups[] = [
				'opd_id' => $opdId,
				'opd_code' => $row['opd_code'] ?? '',
				'doc_name' => $row['doc_name'] ?? '',
				'opd_date' => !empty($row['apointment_date']) ? date('d-m-Y', strtotime($row['apointment_date'])) : '',
				'queue_no' => !empty($row['queue_no']) ? ('Q:' . $row['queue_no']) : '',
				'files' => $filesByOpd[$opdId] ?? [],
			];
		}

		return view('billing/Patient_Profile_Opd_V', [
			'patient' => $patient,
			'opdGroups' => $opdGroups,
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

	private function applyPatientAbhaFieldValues(array &$data, string $abhaId): void
	{
		$targetField = $this->resolvePatientAbhaIdField();
		if ($targetField !== null) {
			$data[$targetField] = $abhaId;
		}
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


}
