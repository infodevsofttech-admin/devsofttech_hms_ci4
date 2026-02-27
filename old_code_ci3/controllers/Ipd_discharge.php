<?php

use Mpdf\Http\Response;

defined('BASEPATH') or exit('No direct script access allowed');

class Ipd_discharge extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		$this->load->model('ipdDischarge_M');
		$this->load->model('Ipd_M');
		$this->load->model('Patient_M');
	}

	public function search_ipd()
	{
		$this->load->view('IPD_Discharge/main');
	}

	public function search_ipd_result()
	{
		$sdata = $this->input->post('txtsearch');

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		$sql_where = "";

		if (trim($sdata) == '') {
			$sql = "select * from v_ipd_list order by id desc limit 200";
		} else {
			if (!$this->ion_auth->in_group('IPDOLD')) {
				$sql_where = " and Date(register_date) > '2019-03-31'";
			}

			$sql = "select * from v_ipd_list 
			where ipd_code like '%" . $sdata . "' or	p_code LIKE '%" . $sdata . "' 
			OR p_fname LIKE '%" . $sdata . "%' 
			OR doc_name LIKE '%" . $sdata . "%'
			" . $sql_where . "
			group by id order by id desc limit 100";
		}

		$query = $this->db->query($sql);
		$data['ipd_master'] = $query->result();

		$this->load->view('IPD_Discharge/search_ipd_result', $data);
	}

	public function ipd_select($ipdno, $re_create = 0)
	{

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$sql = "select * from hc_department order by vName ";
		$query = $this->db->query($sql);
		$data['hc_department'] = $query->result();

		$sql = "select * from ipd_discharg_status ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_status'] = $query->result();

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_master'] = $query->result();

		$ipd_create = $data['ipd_master'][0]->ipd_create;

		if ($ipd_create == 1 && $re_create == 0) {
			redirect('Ipd_discharge/preview_discharge_report/' . $ipdno);
			return true;
		}


		/*$dataupdate = array( 
			'discharge_time'=>date('H:i'),
		);
		
		$this->Ipd_M->update($dataupdate,$ipdno);
		*/


		$p_id = $data['ipd_master'][0]->p_id;
		$ipd_admit_date = $data['ipd_master'][0]->register_date;

		$sql = "select * from patient_master where  id=$p_id ";
		$query = $this->db->query($sql);
		$data['Pdata'] = $query->result();

		if ($data['ipd_master'][0]->discharge_date == null || $data['ipd_master'][0]->discharge_date == '0000-00-00' || $data['ipd_master'][0]->discharge_date == '1901-01-01') {
			$ipd_discharge_date = date('Y-m-d');
		} else {
			$ipd_discharge_date = $data['ipd_master'][0]->discharge_date;
		}

		$sql = "select * from ipd_discharge 
		where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge'] = $query->result();

		//Surgery 
		$sql = "select * from ipd_discharge_surgery where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_surgery'] = $query->result();

		//Procedure
		$sql = "select * from ipd_discharge_procedure where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_procedure'] = $query->result();

		if (count($data['ipd_discharge']) == 0) {
			$datainsert = array(
				'ipd_id' => $ipdno,
				'created_datetime' => $user_name . '[' . $user_id . ']',
			);
			$this->ipdDischarge_M->insert_ipdDischarge($datainsert);
		}

		//General examination

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,
		if(d.rdata is null,col_pre_value,d.rdata) as rdata_text,g.col_type,col_pre_value,d.rdata
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' where cat_group=1";
		$query = $this->db->query($sql);
		$data['ipd_discharge_general_exam_col_1'] = $query->result();

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_type,col_pre_value,
		if(d.rdata is null,col_pre_value,d.rdata) as rdata_text,g.col_type,col_pre_value,d.rdata
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' where cat_group=2";
		$query = $this->db->query($sql);
		$data['ipd_discharge_general_exam_col_2'] = $query->result();

		$sql = "select g.id,g.sys_exam_name,g.sys_format,d.rdata 
		from ipd_discharge_sys_exam g left join ipd_discharge_1_a d on g.id=d.head_id and d.ipd_d_id='" . $ipdno . "' where g.id=9 ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_sys_exam'] = $query->result();

		//ipd_discharge_1_d
		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_type,col_pre_value,
		if(d.rdata is null,col_pre_value,d.rdata) as rdata_text,g.col_type,col_pre_value,d.rdata
		from ipd_discharge_investigation_during_admit g left join ipd_discharge_1_d d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' where cat_group=1";
		$query = $this->db->query($sql);
		$data['ipd_discharge_investigation_during_admit'] = $query->result();

		//ipd_discharge_1_e
		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_type,col_pre_value,
		if(d.rdata is null,col_pre_value,d.rdata) as rdata_text,g.col_type,col_pre_value,d.rdata
		from ipd_discharge_special_investigation g left join ipd_discharge_1_e d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' where cat_group=1";
		$query = $this->db->query($sql);
		$data['ipd_discharge_special_investigation'] = $query->result();

		//Examination at the time of Discharge

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,
		if(d.rdata is null,col_pre_value,d.rdata) as rdata_text
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b_final d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "'  ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_exam_on_discharge_col'] = $query->result();

		//CLINICAL INVESTIGATION REPORTS

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "' and CHAR_LENGTH(lab_investigation_list)>0 ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_2'] = $query->result();

		$lab_list = "0";
		if (count($data['ipd_discharge_2']) > 0) {
			$lab_list = $data['ipd_discharge_2'][0]->lab_investigation_list;
		}

		$sql = "select *,if(id in (" . $lab_list . "),'checked','') as chked from lab_request where patient_id=" . $p_id . " 
		and (ipd_id=" . $ipdno . " or (Request_Date between '" . $ipd_admit_date . "'  and '" . $ipd_discharge_date . "'  ))";

		$sql = " SELECT  QUOTE(m.inv_date) AS sDate, date_format(m.inv_date,'%d-%m-%Y') AS sInd_Date,
			group_CONCAT(t.Test ORDER BY t.Test) AS test_list,if(m.inv_date in (" . $lab_list . "),'checked','') as chked
			FROM (((invoice_master m JOIN lab_request l ON m.id=l.charge_id)
			JOIN lab_request_item i ON l.id=i.lab_request_id)
			JOIN lab_tests t ON i.lab_test_id=t.mstTestKey)
			JOIN ipd_discharge_investigation_template d ON t.TestID=d.test_code
			WHERE m.attach_id=" . $p_id . " and  m.inv_date between '" . $ipd_admit_date . "'  and '" . $ipd_discharge_date . "' 
			GROUP BY m.inv_date";

		echo $sql;

		$query = $this->db->query($sql);
		$data['lab_request'] = $query->result();

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_2'] = $query->result();


		//Presenting Complaints with Duration and Reason for Admission

		$sql = "select * from ipd_discharge_complaint where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_complaint'] = $query->result();

		$sql = "select * from ipd_discharge_complaint_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_complaint_remark'] = $query->result();

		//Final Diagnosis at the time of Discharge

		$sql = "select * from ipd_discharge_diagnosis where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_diagnosis'] = $query->result();

		$sql = "select * from ipd_discharge_diagnosis_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_diagnosis_remark'] = $query->result();

		//Summary of key investigtions during Hospitalization 

		$sql = "select * from ipd_discharge_investigtions_inhos where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_investigtions_inhos'] = $query->result();


		//Course in the hospital 

		$sql = "select * from ipd_discharge_course where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_course'] = $query->result();

		$sql = "select * from ipd_discharge_course_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_course_remark'] = $query->result();

		//Discharge Medications

		$sql = "select * from ipd_discharge_drug where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_drug'] = $query->result();

		//Discharge Medications New Method
		$sql = "SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((ipd_discharge_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.ipd_id='" . $ipdno . "' order by pt.id";
		$query = $this->db->query($sql);
		$data['ipd_discharge_prescrption_prescribed'] = $query->result();
		$data1['ipd_discharge_prescrption_prescribed'] = $data['ipd_discharge_prescrption_prescribed'];

		$data['ipd_discharge_prescrption_prescribed_content'] = $this->load->view('IPD_Discharge/ipd_prescription', $data1, true);

		//Discharge Instructions 

		$sql = "select * from ipd_discharge_instructions where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_instructions'] = $query->result();

		//Discharge FOOD DRUG INTERACTION 
		$sql = "select * from ipd_discharge_drug_food_interaction where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_drug_food_interaction'] = $query->result();

		$food_list = "0";
		if (count($data['ipd_discharge_drug_food_interaction']) > 0) {
			if (strlen($data['ipd_discharge_drug_food_interaction'][0]->food_id_list) > 0) {
				$food_list = $data['ipd_discharge_drug_food_interaction'][0]->food_id_list;
			}
		}

		$sql = "SELECT m.id,m.food_short,if(m.id IN (" . $food_list . "),'Checked','') AS food_exist,food_desc
				FROM ipd_discharge_master_food m ";
		$query = $this->db->query($sql);
		$data['dis_food_interaction'] = $query->result();

		$foot_banner_list = "0";
		if (count($data['ipd_discharge_instructions']) > 0) {
			if (strlen($data['ipd_discharge_instructions'][0]->footer_banner) > 0) {
				$foot_banner_list = $data['ipd_discharge_instructions'][0]->footer_banner;
			}
		}

		$sql = "SELECT m.id,m.banner_name,if(m.id IN (" . $foot_banner_list . "),'Checked','') AS banner_exist
				FROM ipd_discharge_banner m ";
		$query = $this->db->query($sql);
		$data['dis_banner_list'] = $query->result();

		//IPD discharge Medicince prescribed
		$sql = "select * from opd_dose_shed ";
		$query = $this->db->query($sql);
		$data['opd_dose_shed'] = $query->result();

		$sql = "select * from opd_dose_when";
		$query = $this->db->query($sql);
		$data['opd_dose_when'] = $query->result();

		$sql = "select * from opd_dose_frequency";
		$query = $this->db->query($sql);
		$data['opd_dose_frequency'] = $query->result();

		$sql = "select * from opd_dose_where";
		$query = $this->db->query($sql);
		$data['opd_dose_where'] = $query->result();

		$sql = "select * from opd_dose_duration";
		$query = $this->db->query($sql);
		$data['opd_dose_duration'] = $query->result();

		$sql = "select * from med_formulation";
		$query = $this->db->query($sql);
		$data['med_formulation'] = $query->result();

		$sql = "SELECT o.id,o.next_visit_desc,DATE_ADD(CURDATE(),INTERVAL o.no_of_days DAY) AS next_date,
		Concat(next_visit_desc,' (', Date_Format(DATE_ADD(CURDATE(),INTERVAL o.no_of_days DAY),'%d-%m-%Y') ,')') as next_visit_day
		FROM opd_nextvisit o order by no_of_days";
		$query = $this->db->query($sql);
		$data['opd_nextvisit'] = $query->result();

		//IPD discharge Medicince prescribed End


		$this->load->view('IPD_Discharge/ipd_discharge_edit', $data);
	}


	// Get Combo Value

	public function get_complaints()
	{

		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from complaints_master where Name like '%" . $q . "%'  ";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['Name']));
					$new_row['value'] = htmlentities(stripslashes($row['Name']));

					$new_row['complaints_no'] = htmlentities(stripslashes($row['Code']));

					$new_row['sql'] = $sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}

	// Get Combo Value

	public function get_hospital_course_master()
	{

		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from hospital_course_master where Name like '%" . $q . "%'  ";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['Name']));
					$new_row['value'] = htmlentities(stripslashes($row['Name']));

					$new_row['complaints_no'] = htmlentities(stripslashes($row['Code']));

					$new_row['sql'] = $sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}


	public function get_surgery()
	{

		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from surgery where  Name like '%" . $q . "%'  ";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['Name']));
					$new_row['value'] = htmlentities(stripslashes($row['Name']));

					$new_row['surgery_no'] = htmlentities(stripslashes($row['Code']));

					$new_row['sql'] = $sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function get_drug_timing()
	{

		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from drug_timing where dosage_desc like '%" . $q . "%' or dosage_code like '" . $q . "%'  ";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['dosage_desc']));
					$new_row['value'] = htmlentities(stripslashes($row['dosage_desc']));

					$new_row['surgery_no'] = htmlentities(stripslashes($row['id']));

					$new_row['sql'] = $sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function get_disease()
	{
		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from disease_master where  Name like '%" . $q . "%'  order by Name";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['Name']));
					$new_row['value'] = htmlentities(stripslashes($row['Name']));

					$new_row['complaints_no'] = htmlentities(stripslashes($row['Code']));

					$new_row['sql'] = $sql;
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function get_medical()
	{
		if (isset($_GET['term'])) {
			$q = strtolower($_GET['term']);

			$sql = "select * from local_medical where med_name like '%" . $q . "%'  order by med_name";

			$sql = $sql . " limit 20";

			$query = $this->db->query($sql);

			if ($query->num_rows() > 0) {
				foreach ($query->result_array() as $row) {
					$new_row['label'] = htmlentities(stripslashes($row['med_name']));
					$new_row['value'] = htmlentities(stripslashes($row['med_name']));
					$row_set[] = $new_row; //build an array
				}
				echo json_encode($row_set); //format the array into json data
			}
		}
	}

	// Data Saving

	public function update_Sys_Exam()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$IPD_id = $this->input->post('IPD_id');
		$sys_exam_id = $this->input->post('sys_exam_id');

		$sql = "select * from ipd_discharge_sys_exam where id=" . $sys_exam_id;
		$query = $this->db->query($sql);
		$short_head_sys_exam = $query->result();

		$short_head = "";
		if (count($short_head_sys_exam) > 0) {
			$short_head = $short_head_sys_exam[0]->sys_exam_name;
		}

		$datainsert = array(
			'ipd_d_id' => $IPD_id,
			'head_id' => $sys_exam_id,
			'short_head' => $short_head,
			'rdata' => $editor_Sys_Exam,
		);

		$sql = "select * from ipd_discharge_1_a where ipd_d_id=" . $IPD_id . " and head_id=" . $sys_exam_id;
		$query = $this->db->query($sql);
		$chk_exist = $query->result();

		if (count($chk_exist) > 0) {
			$this->ipdDischarge_M->update_ipd_discharge_1_a($datainsert, $chk_exist[0]->id);
		} else {
			$this->ipdDischarge_M->insert_ipd_discharge_1_a($datainsert);
		}

		echo 'Saved';
	}

	public function update_investigation_m_Exam()
	{

		$IPD_id = $this->input->post('gen_exam_ipd_id');

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,
		if(d.id is null,1,0) as insert_data
		from ipd_discharge_investigation_during_admit g 
		left join ipd_discharge_1_d d on g.id=d.col_id and d.ipd_d_id=" . $IPD_id;
		$query = $this->db->query($sql);
		$discharge_general_exam = $query->result();

		foreach ($discharge_general_exam as $row) {
			$rdata = $this->input->post('input_m_exam_' . $row->id);

			$datainsert = array(
				'ipd_d_id' => $IPD_id,
				'col_id' => $row->id,
				'short_head' => $row->col_name,
				'rdata' => $rdata,
			);

			$this->ipdDischarge_M->update_insert_ipd_discharge_1_d($datainsert, $IPD_id, $row->id);
		}

		echo 'Saved';
	}

	public function update_investigation_s_Exam()
	{

		$IPD_id = $this->input->post('gen_exam_ipd_id');

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,
		if(d.id is null,1,0) as insert_data
		from ipd_discharge_special_investigation g 
		left join ipd_discharge_1_e d on g.id=d.col_id and d.ipd_d_id=" . $IPD_id;
		$query = $this->db->query($sql);
		$discharge_general_exam = $query->result();

		foreach ($discharge_general_exam as $row) {
			$rdata = $this->input->post('input_s_exam_' . $row->id);

			$datainsert = array(
				'ipd_d_id' => $IPD_id,
				'col_id' => $row->id,
				'short_head' => $row->col_name,
				'rdata' => $rdata,
			);

			$this->ipdDischarge_M->update_insert_ipd_discharge_1_e($datainsert, $IPD_id, $row->id);
		}

		echo 'Saved';
	}

	public function update_gen_Exam()
	{

		$IPD_id = $this->input->post('gen_exam_ipd_id');

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,
		if(d.id is null,1,0) as insert_data
		from ipd_discharge_general_exam_col g 
		left join ipd_discharge_1_b d on g.id=d.col_id and d.ipd_d_id=" . $IPD_id;
		$query = $this->db->query($sql);
		$discharge_general_exam = $query->result();

		foreach ($discharge_general_exam as $row) {
			$rdata = $this->input->post('input_g_exam_' . $row->id);

			$datainsert = array(
				'ipd_d_id' => $IPD_id,
				'col_id' => $row->id,
				'short_head' => $row->col_name,
				'rdata' => $rdata,
			);

			$this->ipdDischarge_M->update_insert_ipd_discharge_1_b($datainsert, $IPD_id, $row->id);
		}

		echo 'Saved';
	}


	public function update_dis_Exam()
	{

		$IPD_id = $this->input->post('dis_exam_ipd_id');

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,
		if(d.id is null,1,0) as insert_data
		from ipd_discharge_general_exam_col g 
		left join ipd_discharge_1_b_final d on g.id=d.col_id and d.ipd_d_id=" . $IPD_id;
		$query = $this->db->query($sql);
		$discharge_general_exam = $query->result();

		foreach ($discharge_general_exam as $row) {
			$rdata = $this->input->post('input_d_exam_' . $row->id);

			$datainsert = array(
				'ipd_d_id' => $IPD_id,
				'col_id' => $row->id,
				'short_head' => $row->col_name,
				'rdata' => $rdata,
			);

			$this->ipdDischarge_M->update_insert_ipd_discharge_1_b_final($datainsert, $IPD_id, $row->id);
		}

		echo 'Saved';
	}

	public function update_Provisional_Exam()
	{
		$editor_data = $this->input->post('editor_data');
		$ipd_id = $this->input->post('ipd_id');
		$lab_investigation_list = $this->input->post('lab_investigation_list');

		$lab_investigation_list = str_replace(":", ",", $lab_investigation_list);

		$short_head = "Other Examinations or Provisional Diagnosis";

		$datainsert = array(
			'ipd_d_id' => $ipd_id,
			'lab_investigation_list' => $lab_investigation_list,
			'short_head' => $short_head,
			'rdata' => $editor_data,
		);

		$return_id = $this->ipdDischarge_M->update_insert_ipd_discharge_2($datainsert, $ipd_id);

		echo 'Saved : With Return Code ->' . $return_id;
	}

	//Save Personal History

	public function update_personal_history()
	{
		$p_id = $this->input->post('p_id');

		$opd_prescription_smoking_chk = $this->input->post('opd_prescription_smoking_chk');
		$opd_prescription_alcohol_chk = $this->input->post('opd_prescription_alcohol_chk');
		$opd_prescription_tobacoo_chk = $this->input->post('opd_prescription_tobacoo_chk');
		$opd_prescription_drug_chk = $this->input->post('opd_prescription_drug_chk');
		$opd_prescription_hypertesion_chk = $this->input->post('opd_prescription_hypertesion_chk');
		$opd_prescription_niddm_chk = $this->input->post('opd_prescription_niddm_chk');
		$opd_prescription_hbsag_chk = $this->input->post('opd_prescription_hbsag_chk');
		$opd_prescription_hcv_chk = $this->input->post('opd_prescription_hcv_chk');
		$opd_prescription_hiv_I_II_chk = $this->input->post('opd_prescription_hiv_I_II_chk');

		$dataPatientMaster = array(
			'is_smoking' => $opd_prescription_smoking_chk,
			'is_alcohol' => $opd_prescription_alcohol_chk,
			'is_drug_abuse' => $opd_prescription_drug_chk,
			'is_tobacoo' => $opd_prescription_tobacoo_chk,
			'is_hypertesion' => $opd_prescription_hypertesion_chk,
			'is_niddm' => $opd_prescription_niddm_chk,
			'is_hbsag' => $opd_prescription_hbsag_chk,
			'is_hcv' => $opd_prescription_hcv_chk,
			'is_hiv_I_II' => $opd_prescription_hiv_I_II_chk,
		);

		$this->Patient_M->update($dataPatientMaster, $p_id);

		echo 'Saved ';
	}


	//FOOD_DRUG_INTERACTION

	public function update_food_interaction()
	{
		$editor_data = $this->input->post('editor_data');

		$ipd_id = $this->input->post('ipd_id');
		$food_interaction_list = $this->input->post('food_interaction_list');

		$food_interaction_list = str_replace(":", ",", $food_interaction_list);

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'food_id_list' => $food_interaction_list,
			'food_text' => $editor_data,
		);

		$return_id = $this->ipdDischarge_M->update_insert_ipd_discharge_drug_food_interaction($datainsert, $ipd_id);

		echo 'Saved : With Return Code ->' . $return_id;
	}

	//Footer Banner 

	public function update_footer_banner()
	{
		$editor_data = $this->input->post('editor_data');

		$ipd_id = $this->input->post('ipd_id');
		$footer_banner_list = $this->input->post('footer_banner_list');

		$footer_banner_list = str_replace(":", ",", $footer_banner_list);

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'footer_banner' => $footer_banner_list,
		);

		$return_id = $this->ipdDischarge_M->update_insert_ipd_discharge_instructions($datainsert, $ipd_id);

		echo 'Saved : With Return Code ->' . $return_id;
	}



	//Compalint Part Add Update Renove

	public function add_complaints()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$complaint = $this->input->post('input_complaints');
		$complaint_value = $this->input->post('input_complaints_value');
		$complaint_remarks = $this->input->post('input_complaints_remarks');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_code' => $complaint_value,
			'comp_report' => $complaint,
			'comp_remark' => $complaint_remarks,
			'update_by' => $user_name
		);

		$this->ipdDischarge_M->insert_p_complaint($datainsert);

		$content = "";

		$sql = "select * from ipd_discharge_complaint where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";

		if (count($dataRec) > 0) {
			$content .= "<tr><th>Complaint Name</th><td>Remarks</th><th></th><th></th></tr>";
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
							<td>
								<input class="form-control input-sm"
									name="input_complaints_' . $row->id . '"
									id="input_complaints_' . $row->id . '" type="text"
									value="' . $row->comp_report . '">
							</td>
							<td>
								<input class="form-control input-sm"
									name="input_complaint_remarks_' . $row->id . '"
									id="input_complaint_remarks_' . $row->id . '" type="text"
									value="' . $row->comp_remark . '">
							</td>
							<td><a href="javascript:complaintUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a>
							</td>
							<td><a href="javascript:complaintRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a>
							</td>
						</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}


	public function update_complaint()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$complaint_remarks = $this->input->post('complaint_remarks');
		$complaint = $this->input->post('complaint');
		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_complaint where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'comp_remark' => $complaint_remarks,
				'comp_report' => $complaint,
				'update_by' => $user_name
			);


			$this->ipdDischarge_M->update_p_complaint($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}


	public function remove_complaint($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_complaint where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {
			$this->ipdDischarge_M->remove_p_complaint($invRecID);
		}

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";

		if (count($dataRec) > 0) {
			$content .= "<tr><th>Complaint Name</th><td>Remarks</th><th></th><th></th></tr>";
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
							<td>
								<input class="form-control input-sm"
									name="input_complaints_' . $row->id . '"
									id="input_complaints_' . $row->id . '" type="text"
									value="' . $row->comp_report . '">
							</td>
							<td>
								<input class="form-control input-sm"
									name="input_complaint_remarks_' . $row->id . '"
									id="input_complaint_remarks_' . $row->id . '" type="text"
									value="' . $row->comp_remark . '">
							</td>
							<td><a href="javascript:complaintUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a>
							</td>
							<td><a href="javascript:complaintRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a>
							</td>
						</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}

	public function update_complaint_edit()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_remark' => $editor_Sys_Exam,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_complaint_remark($datainsert, $ipd_id);

		echo 'Saved';
	}

	//procedure Update and Add
	public function add_procedure()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$input_procedure_name = $this->input->post('input_procedure_name');
		$input_procedure_id = $this->input->post('input_procedure_id');
		$input_procedure_remark = $this->input->post('input_procedure_remark');
		$procedure_date = $this->input->post('procedure_date');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'procedure_id' => $input_procedure_id,
			'procedure_name' => $input_procedure_name,
			'procedure_remark' => $input_procedure_remark,
			'procedure_date' => str_to_MysqlDate($procedure_date),
			'update_by' => $user_name
		);

		$this->ipdDischarge_M->insert_p_procedure($datainsert);

		$content = "";

		$sql = "select * from ipd_discharge_procedure where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_discharge_procedure'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_procedure', $data, true);

		echo $content;
	}


	public function update_procedure()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$procedure_name = $this->input->post('procedure_name');
		$procedure_remark = $this->input->post('procedure_remark');
		$procedure_date = $this->input->post('procedure_date');

		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_procedure where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'procedure_name' => $procedure_name,
				'procedure_remark' => $procedure_remark,
				'procedure_date' => str_to_MysqlDate($procedure_date),
				'update_by' => $user_name
			);

			$this->ipdDischarge_M->update_p_procedure($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}

	public function remove_procedure($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_procedure where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {
			$this->ipdDischarge_M->remove_p_procedure($invRecID);
		}

		$content = "";

		$sql = "select * from ipd_discharge_procedure where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_discharge_procedure'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_procedure', $data, true);

		echo $content;
	}

	//surgery Update and Add
	public function add_surgery()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$input_surgery_name = $this->input->post('input_surgery_name');
		$input_surgery_id = $this->input->post('input_surgery_id');
		$input_surgery_remark = $this->input->post('input_surgery_remark');
		$surgery_date = $this->input->post('surgery_date');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'surgery_id' => $input_surgery_id,
			'surgery_name' => $input_surgery_name,
			'surgery_remark' => $input_surgery_remark,
			'surgery_date' => str_to_MysqlDate($surgery_date),
			'update_by' => $user_name
		);

		$this->ipdDischarge_M->insert_p_surgery($datainsert);

		$content = "";

		$sql = "select * from ipd_discharge_surgery where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_discharge_surgery'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_surgery', $data, true);

		echo $content;
	}


	public function update_surgery()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$surgery_name = $this->input->post('surgery_name');
		$surgery_remark = $this->input->post('surgery_remark');
		$surgery_date = $this->input->post('surgery_date');

		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_surgery where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'surgery_name' => $surgery_name,
				'surgery_remark' => $surgery_remark,
				'surgery_date' => str_to_MysqlDate($surgery_date),
				'update_by' => $user_name
			);

			$this->ipdDischarge_M->update_p_surgery($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}

	public function remove_surgery($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_surgery where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {
			$this->ipdDischarge_M->remove_p_surgery($invRecID);
		}

		$content = "";

		$sql = "select * from ipd_discharge_surgery where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_discharge_surgery'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_surgery', $data, true);

		echo $content;
	}

	//Delivery Update
	public function update_delivery()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$this->load->model('Ipd_M');


		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$ipd_id = $this->input->post('ipd_id');
		$isdelivery = $this->input->post('isdelivery');
		$delivery_date = $this->input->post('delivery_date');
		$delivery_time = $this->input->post('delivery_time');
		$delivery_sex_of_baby = $this->input->post('delivery_sex_of_baby');
		$delivery_sex_of_baby2 = $this->input->post('delivery_sex_of_baby2');
		$delivery_sex_of_baby3 = $this->input->post('delivery_sex_of_baby3');


		$dataupdate = array(
			'isdelivery' => $isdelivery,
			'delivery_time' => $delivery_time,
			'delivery_date' => str_to_MysqlDate($delivery_date),
			'delivery_sex_of_baby' => $delivery_sex_of_baby,
			'delivery_sex_of_baby2' => $delivery_sex_of_baby2,
			'delivery_sex_of_baby3' => $delivery_sex_of_baby3
		);

		$this->Ipd_M->update($dataupdate, $ipd_id);
		$content = "Update Done";

		echo $content;
	}





	//Delivery End 




	//diagnosis Part Add Update Renove

	public function add_diagnosis()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$diagnosis = $this->input->post('input_diagnosis');
		$diagnosis_value = $this->input->post('input_diagnosis_value');
		$diagnosis_remarks = $this->input->post('input_diagnosis_remarks');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_code' => $diagnosis_value,
			'comp_report' => $diagnosis,
			'comp_remark' => $diagnosis_remarks,
			'update_by' => $user_name
		);

		$this->ipdDischarge_M->insert_p_diagnosis($datainsert);

		$content = "";

		$sql = "select * from ipd_discharge_diagnosis where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";

		if (count($dataRec) > 0) {
			$content .= '<tr><th>Diagnosis </th><td>Remarks</th><th></th><th></th></tr>';
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
							<td>
								<input class="form-control input-sm"
									name="input_diagnosis_' . $row->id . '"
									id="input_diagnosis_' . $row->id . '" type="text"
									value="' . $row->comp_report . '">
							</td>
							<td>
								<input class="form-control input-sm"
									name="input_diagnosis_remarks_' . $row->id . '"
									id="input_diagnosis_remarks_' . $row->id . '" type="text"
									value="' . $row->comp_remark . '">
							</td>
							<td><a href="javascript:diagnosisUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a></td>
							<td><a href="javascript:diagnosisRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a></td>
						</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}


	public function update_diagnosis()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$diagnosis_remarks = $this->input->post('diagnosis_remarks');
		$diagnosis = $this->input->post('diagnosis');
		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_diagnosis where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'comp_remark' => $diagnosis_remarks,
				'comp_report' => $diagnosis,
				'update_by' => $user_name
			);


			$this->ipdDischarge_M->update_p_diagnosis($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}


	public function remove_diagnosis($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_diagnosis where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {

			$this->ipdDischarge_M->remove_p_diagnosis($invRecID);
		}

		$content = "";

		$sql = "select * from ipd_discharge_diagnosis where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";
		if (count($dataRec) > 0) {
			$content .= '<tr><th>Diagnosis </th><td>Remarks</th><th></th><th></th></tr>';
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
							<td>
								<input class="form-control input-sm"
									name="input_diagnosis_' . $row->id . '"
									id="input_diagnosis_' . $row->id . '" type="text"
									value="' . $row->comp_report . '">
							</td>
							<td>
								<input class="form-control input-sm"
									name="input_diagnosis_remarks_' . $row->id . '"
									id="input_diagnosis_remarks_' . $row->id . '" type="text"
									value="' . $row->comp_remark . '">
							</td>
							<td><a href="javascript:diagnosisUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a></td>
							<td><a href="javascript:diagnosisRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a></td>
						</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}

	public function update_diagnosis_edit()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_remark' => $editor_Sys_Exam,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_diagnosis_remark($datainsert, $ipd_id);

		echo 'Saved';
	}

	//Summary of Key investigtion during Hospitalization
	public function update_investigtions_inhos()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_remark' => $editor_Sys_Exam,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_investigtions_inhos($datainsert, $ipd_id);

		echo 'Saved';
	}

	//Course in the Hospitalization


	public function add_course()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$diagnosis = $this->input->post('input_course');
		$diagnosis_value = $this->input->post('input_course_value');
		$diagnosis_remarks = $this->input->post('input_course_remarks');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_code' => $diagnosis_value,
			'comp_report' => $diagnosis,
			'comp_remark' => $diagnosis_remarks,
			'update_by' => $user_name
		);

		$this->ipdDischarge_M->insert_p_course($datainsert);

		$content = "";

		$sql = "select * from ipd_discharge_course where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";

		if (count($dataRec) > 0) {
			$content .=  '<tr><th>Course </th><td>Remarks</th><th></th><th></th></tr>';
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
			<td>
				<input class="form-control input-sm"
					name="input_course_' . $row->id . '"
					id="input_course_' . $row->id . '" type="text"
					value="' . $row->comp_report . '">
			</td>
			<td>
				<input class="form-control input-sm"
					name="input_course_remarks_' . $row->id . '"
					id="input_course_remarks_' . $row->id . '" type="text"
					value="' . $row->comp_remark . '">
			</td>
			<td><a href="javascript:courseUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a></td>
			<td><a href="javascript:courseRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a></td>
		</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}


	public function update_course()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$diagnosis_remarks = $this->input->post('course_remarks');
		$comp_report = $this->input->post('course');
		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_course where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'comp_remark' => $diagnosis_remarks,
				'comp_report' => $comp_report,
				'update_by' => $user_name
			);


			$this->ipdDischarge_M->update_p_course($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}


	public function remove_course($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_course where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {

			$this->ipdDischarge_M->remove_p_course($invRecID);
		}

		$content = "";

		$sql = "select * from ipd_discharge_course where ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "<div class='col-md-12' ><table class='table table-condensed'>";

		if (count($dataRec) > 0) {
			$content .= '<tr><th>Course </th><td>Remarks</th><th></th><th></th></tr>';
		}

		foreach ($dataRec as $row) {
			$content .= '<tr>
			<td>
				<input class="form-control input-sm"
					name="input_course_' . $row->id . '"
					id="input_course_' . $row->id . '" type="text"
					value="' . $row->comp_report . '">
			</td>
			<td>
				<input class="form-control input-sm"
					name="input_course_remarks_' . $row->id . '"
					id="input_course_remarks_' . $row->id . '" type="text"
					value="' . $row->comp_remark . '">
			</td>
			<td><a href="javascript:courseUpdate(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Update</a></td>
			<td><a href="javascript:courseRemove(\'' . $row->id . '\',\'' . $row->ipd_id . '\')">Remove</a></td>
		</tr>';
		}

		$content .= '</table></div>';

		echo $content;
	}

	public function update_course_remark()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_remark' => $editor_Sys_Exam,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_course_remark($datainsert, $ipd_id);

		echo 'Saved';
	}


	//Discharge Instructions 
	public function save_Instructions()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$review_after = $this->input->post('input_next_visit');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'comp_remark' => $editor_Sys_Exam,
			'review_after' => $review_after,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_instructions($datainsert, $ipd_id);

		echo 'Saved';
	}



	public function save_Footer()
	{
		$editor_Sys_Exam = $this->input->post('editor_Sys_Exam');
		$ipd_id = $this->input->post('ipd_id');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'footer_text' => $editor_Sys_Exam,
		);

		$this->ipdDischarge_M->update_insert_ipd_discharge_instructions($datainsert, $ipd_id);

		echo 'Saved';
	}

	//Drugs

	public function add_drug()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$input_med_name = strtoupper($this->input->post('input_med_name'));
		$input_med_type = $this->input->post('input_med_type');
		$input_dosage = $this->input->post('input_dosage');
		$input_dosage_when = $this->input->post('input_dosage_when');
		$input_dosage_freq = $this->input->post('input_dosage_freq');
		$input_dose_where = $this->input->post('input_dose_where');
		$input_no_of_days = $this->input->post('input_no_of_days');
		$input_qty = $this->input->post('input_qty');
		$input_remark = strtoupper($this->input->post('input_remark'));
		$hid_med_id = $this->input->post('hid_med_id');
		$ipd_id = $this->input->post('ipd_id');
		$hid_ipd_med_item_id = $this->input->post('hid_ipd_med_item_id');
		$input_med_salt = $this->input->post('input_med_salt');

		$datainsert = array(
			'ipd_id' => $ipd_id,
			'med_id' => $hid_med_id,
			'med_name' => $input_med_name,
			'med_type' => $input_med_type,
			'med_salt' => $input_med_salt,
			'dosage' => $input_dosage,
			'dosage_when' => $input_dosage_when,
			'dosage_freq' => $input_dosage_freq,
			'dosage_where' => $input_dose_where,
			'qty' => $input_qty,
			'no_of_days' => $input_no_of_days,
			'remark' => $input_remark,
			'update_by' => $user_name
		);

		if ($hid_ipd_med_item_id > 0) {
			$this->ipdDischarge_M->update_p_drug($datainsert, $hid_ipd_med_item_id);
		} else {
			$this->ipdDischarge_M->insert_p_drug($datainsert);
		}



		//Discharge Medications New Method
		$sql = "SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((ipd_discharge_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.ipd_id='" . $ipd_id . "' order by pt.id ";
		$query = $this->db->query($sql);
		$data['ipd_discharge_prescrption_prescribed'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_prescription', $data, true);

		echo $content;
	}


	public function update_drug()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$drug_remarks = $this->input->post('drug_remarks');
		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('invACode');

		$sql = "select * from ipd_discharge_drug where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$content = "Nothing Done";

		if (count($dataRec) > 0) {
			$dataupdate = array(
				'drug_dose' => $drug_remarks,
				'update_by' => $user_name
			);


			$this->ipdDischarge_M->update_p_drug($dataupdate, $rec_id);

			$content = "Update Done";
		}

		echo $content;
	}

	public function remove_drug($invRecID, $ipd_id)
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$sql = "select * from ipd_discharge_prescrption_prescribed where ipd_id=" . $ipd_id . " and id=" . $invRecID;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		if (count($dataRec) > 0) {
			$this->ipdDischarge_M->remove_p_drug($invRecID);
		}

		//Discharge Medications New Method
		$sql = "SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((ipd_discharge_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.ipd_id='" . $ipd_id . "' Order by pt.id";
		$query = $this->db->query($sql);
		$data['ipd_discharge_prescrption_prescribed'] = $query->result();

		$content = $this->load->view('IPD_Discharge/ipd_prescription', $data, true);

		echo $content;
	}

	public function medical_Select()
	{
		if (!$this->input->is_ajax_request()) {
			exit('no valid req.');
		}

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name . '[' . $user->id . ']:T-' . date('d-m-Y h:m:s');

		$ipd_id = $this->input->post('ipd_id');
		$rec_id = $this->input->post('med_prec_id');

		$sql = "select * from ipd_discharge_prescrption_prescribed 
		where id=" . $rec_id . " and ipd_id=" . $ipd_id;
		$query = $this->db->query($sql);
		$dataRec = $query->result();

		$rvar = array(
			"id" => $dataRec[0]->id,
			"ipd_id" => $ipd_id,
			"med_name" => $dataRec[0]->med_name,
			"med_type" => $dataRec[0]->med_type,
			"dosage" => $dataRec[0]->dosage,
			"dosage_when" => $dataRec[0]->dosage_when,
			"dosage_freq" => $dataRec[0]->dosage_freq,
			"no_of_days" => $dataRec[0]->no_of_days,
			"qty" => $dataRec[0]->qty,
			"remark" => $dataRec[0]->remark,
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	//Chamunda Hospital
	public function preview_discharge_report($ipdno)
	{
		$content = "";

		$dataupdate = array(
			'ipd_create' => 1,
		);

		$this->Ipd_M->update($dataupdate, $ipdno);

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();

		$sql = "select * from ipd_master where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master_data = $query->result();

		$p_id = $ipd_master[0]->p_id;
		$ipd_admit_date = $ipd_master[0]->register_date;

		$sql = "select * from patient_master_exten where  id='" . $p_id . "' ";
		$query = $this->db->query($sql);
		$patient_master = $query->result();

		$sql = "select * from patient_master where  id='" . $p_id . "' ";
		$query = $this->db->query($sql);
		$patient_master_data = $query->result();


		if ($ipd_master[0]->discharge_date == null || $ipd_master[0]->discharge_date == '0000-00-00' || $ipd_master[0]->discharge_date == '1901-01-01') {
			$ipd_discharge_date = date('Y-m-d');
		} else {
			$ipd_discharge_date = $ipd_master[0]->discharge_date;
		}

		if ($ipd_master[0]->case_id == 0) {
			$Discharge_time = date('h:i A', strtotime($ipd_master[0]->discharge_time));
			$Reg_time = date('h:i A', strtotime($ipd_master[0]->reg_time));
		} else {
			$Discharge_time = '';
			$Reg_time = '';
		}

		$data['Discharge_time'] = $Discharge_time;
		$data['Reg_time'] = $Reg_time;


		//IPD DEpartment

		$dept_id = $ipd_master_data[0]->dept_id;

		if ($dept_id == '') {
			$dept_id = 1;
		}

		$sql = "select * from hc_department where  iId=$dept_id";
		$query = $this->db->query($sql);
		$ipd_department = $query->result();

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$doc_list = $ipd_master[0]->doc_list;

		$sql = "select d.id,d.p_fname,d.p_title,d.mphone1,d.mphone2,
		if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age,
		d.dob,d.zip,d.email1,d.email2,
		group_concat(distinct  m.SpecName) as SpecName,d.doc_sign
		from (doctor_master d left join  (doc_spec s join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
		where d.id in (" . $doc_list . ") group by d.id";
		$query = $this->db->query($sql);
		$doc_master = $query->result();

		$depart_name = $ipd_department[0]->vName;

		$doc_list_main_sign = '<table border="1" width="100%" cellpadding="2" cellspacing="0">
							<tr>
								<th colspan="2" style="text-align:center;margin:1px; padding:0px;"><h3 >Treating Consultant</h2></th>
							</tr> ';

		$row_num = 0;

		// Doctor Name

		foreach ($doc_master as $row) {
			$row_num = $row_num + 1;

			$mod_r = $row_num % 2;
			if ($mod_r > 0) {
				$doc_list_main_sign .= '<tr>';
			}

			$doc_list_main_sign .= '
				<td style="width:50%"><b>Dr. ' . $row->p_fname . '</b><br/>[' . $row->SpecName . '] <br/>
					' . nl2br($row->doc_sign) . '
				</td>';

			if ($mod_r == 0) {
				$doc_list_main_sign .= '</tr>';
			}
		}

		$row_num = $row_num + 1;
		$mod_r = $row_num % 2;
		if ($mod_r == 0) {

			$doc_list_main_sign .= '<td style="width:50%"></td>
			</tr>';
		}

		$doc_list_main_sign .= "</table>";

		$data['doc_list_main_sign'] = $doc_list_main_sign;

		//Presenting Complaints with Duration and Reason for Admission

		$sql = "select * from ipd_discharge_complaint where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_complaint = $query->result();

		$sql = "select * from ipd_discharge_complaint_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_complaint_remark = $query->result();


		//Surgery
		$sql = "select * from ipd_discharge_surgery where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_surgery = $query->result();

		//Prosedure
		$sql = "select * from ipd_discharge_procedure where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_procedure = $query->result();

		//General examination

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,d.ipd_d_id,g.col_unit
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' 
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_general_exam_col = $query->result();

		$sql = "select g.id,g.sys_exam_name,g.sys_format,d.rdata 
		from ipd_discharge_sys_exam g left join ipd_discharge_1_a d on g.id=d.head_id and d.ipd_d_id='" . $ipdno . "'  ";
		$query = $this->db->query($sql);
		$ipd_discharge_sys_exam = $query->result();


		//CLINICAL INVESTIGATION REPORTS

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "'  and CHAR_LENGTH(lab_investigation_list)>0";
		$query = $this->db->query($sql);
		$ipd_discharge_2 = $query->result();

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_2_rdata = $query->result();

		$lab_list = "";

		if (count($ipd_discharge_2) > 0) {
			$lab_list = $ipd_discharge_2[0]->lab_investigation_list;
		}

		if (strlen($lab_list) > 0) {
			$lab_list_array = explode(",", $lab_list);
			$sql_lab_test = "SELECT t.Test,t.Unit,t.FixedNormals";

			foreach ($lab_list_array as $key => $value) {
				$sql_lab_test .= "," . "group_concat(if(m.inv_date=" . $value . ",i.lab_test_value,null)) AS lab_test_value_" . $key;
			}

			$sql_lab_test .= " FROM ((((invoice_master m JOIN lab_request l ON m.id=l.charge_id)
							JOIN lab_request_item i ON l.id=i.lab_request_id)
							JOIN lab_tests t ON i.lab_test_id=t.mstTestKey)
							JOIN ipd_discharge_investigation_template d ON t.TestID=d.test_code)
							JOIN lab_rgroups g ON d.group_test=g.mstRGrpKey
							WHERE m.attach_id=" . $p_id . " and m.inv_date IN (" . $lab_list . ")
							GROUP BY d.test_code
							ORDER BY g.sort_order,t.mstTestKey";
			$query = $this->db->query($sql_lab_test);
			$lab_request = $query->result_array();
		}

		//Final Diagnosis 

		$sql = "select * from ipd_discharge_diagnosis where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_diagnosis = $query->result();

		$sql = "select * from ipd_discharge_diagnosis_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_diagnosis_remark = $query->result();

		//Summary of key investigtions during Hospitalization 

		$sql = "select * from ipd_discharge_investigtions_inhos where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_investigtions_inhos = $query->result();

		//Course in the hospital 

		$sql = "select * from ipd_discharge_course where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_course = $query->result();

		$sql = "select * from ipd_discharge_course_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_course_remark = $query->result();

		//Exam. in Discharge

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_unit
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b_final d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "'  
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_exam_on_discharge_col = $query->result();

		//investigation Manual
		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_unit
		from ipd_discharge_investigation_during_admit g left join ipd_discharge_1_d d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "'  
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_exam_on_discharge_col_1 = $query->result();

		//investigation 2 Xray
		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_unit
		from ipd_discharge_special_investigation g left join ipd_discharge_1_e d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "'  
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_exam_on_discharge_col_2 = $query->result();

		//Discharge Medications

		$sql = "select * from ipd_discharge_drug where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_drug = $query->result();

		//Discharge Instructions 

		$sql = "select * from ipd_discharge_instructions where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_instructions = $query->result();

		//Discharge FOOD DRUG INTERACTION 
		$sql = "select * from ipd_discharge_drug_food_interaction where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_drug_food_interaction = $query->result();

		$food_list = "0";
		if (count($ipd_discharge_drug_food_interaction) > 0) {
			if (strlen($ipd_discharge_drug_food_interaction[0]->food_id_list) > 0) {
				$food_list = $ipd_discharge_drug_food_interaction[0]->food_id_list;
			}
		}

		$sql = "SELECT m.id,m.food_short,m.food_desc,m.food_desc_lang
				FROM ipd_discharge_master_food m where m.id IN (" . $food_list . ")";
		$query = $this->db->query($sql);
		$dis_food_interaction = $query->result();

		//Diet Advice

		$data['diet_advice'] = "";
		if (count($dis_food_interaction) > 0 || count($ipd_discharge_drug_food_interaction) > 0) {
			$content = '<p><b>Dietary  : </b>';
			$content .= '';

			foreach ($dis_food_interaction as $row) {
				$content .= '<b>' . $row->food_short . '</b>:<span style="font-family:freeserif;"> ' . $row->food_desc_lang . '</span><br/>';
			}
			$content .= '</p>';

			if ($ipd_discharge_drug_food_interaction[0]->food_text != '') {
				$content .= $ipd_discharge_drug_food_interaction[0]->food_text;
			}
			$data['diet_advice'] = $content;
		}



		//Content Start
		$sql = "SELECT * FROM ipd_discharg_status where id =" . $ipd_master[0]->discarge_patient_status;
		$query = $this->db->query($sql);
		$ipd_discharge_status = $query->result();

		//HTML Style



		if (count($ipd_discharge_status) < 1) {
			$h1_head = '';
		} else {
			$h1_head = $ipd_discharge_status[0]->status_details;
		}

		$data['h1_head'] = $h1_head;
		$data['depart_name'] = $depart_name;

		if (D_discharge_doctor_name == 1) {
			$data['Doc_Name_List'] = $doc_list_main_sign;
		}

		if ($ipd_master[0]->case_id == '0') {
			$discharge_date_time = date('h:m A', strtotime($ipd_master[0]->discharge_time));
		} else {
			$discharge_date_time = '';
		}

		$discharge_date_time = $ipd_master[0]->discharge_time;

		$p_address = "";

		if (strlen($patient_master[0]->p_address) > 2) {
			$p_address = "<br/>Address : " . $patient_master[0]->p_address;
		}

		if (strtoupper($patient_master[0]->blood_group) == 'NOT DEFINE') {
			$blood_group_show = '';
		} else {
			$blood_group_show = 'Blood Group : ' . $patient_master[0]->blood_group;
		}

		$data['ipd_master'] = $ipd_master;

		if (count($ipd_discharge_surgery) > 0) {
			$data['Surgery'] = '';
			$content = '<p><b>Surgery : </b>';
			foreach ($ipd_discharge_surgery as $row) {
				$content .= '<br/>' . $row->surgery_name . '&nbsp;&nbsp;&nbsp; / <b>Date of Surgery :</b> ' . MysqlDate_to_str($row->surgery_date);
			}
			$content .= "</p>";
			$data['Surgery'] = $content;
		}

		if (count($ipd_discharge_procedure) > 0) {
			$data['Procedure'] = '';
			$content = '<p><b>Procedure : </b>';

			foreach ($ipd_discharge_procedure as $row) {
				$content .= '<Br/>' . $row->procedure_name . '&nbsp;&nbsp;&nbsp; / Date of Procedure : ' . MysqlDate_to_str($row->procedure_date);
			}

			$content .= "</p>";

			$data['Procedure'] = $content;
		}

		if ($ipd_master[0]->isdelivery == 1) {
			$baby_desc = 'Sex of baby: ' . $ipd_master[0]->delivery_sex_of_baby . '&nbsp;&nbsp;&nbsp;';

			if ($ipd_master[0]->delivery_sex_of_baby2 <> '') {
				$baby_desc .= 'Sex of Second Baby :' . $ipd_master[0]->delivery_sex_of_baby2 . '&nbsp;&nbsp;&nbsp;';
			}

			if ($ipd_master[0]->delivery_sex_of_baby3 == '') {
				$baby_desc .= 'Sex of Third Baby :' . $ipd_master[0]->delivery_sex_of_baby3 . '';
			}

			$content = '';
			$content .= '<b>Child Birth : </b>';
			$content .= 'Delivery Date and Time : ' . MysqlDate_to_str($ipd_master[0]->delivery_date) . ' ' . $ipd_master[0]->delivery_time;
			$content .= '<br/>' . $baby_desc;

			$data['isdelivery'] = $content;
		}

		//Presenting Complaints with Duration and Reason for Admission
		if (count($ipd_discharge_complaint) > 0 || count($ipd_discharge_complaint_remark) > 0) {

			$content = '<p><B>Presenting Complaints and Reason for Admission</B> :<br /> ';

			foreach ($ipd_discharge_complaint as $row) {
				$content .= $row->comp_report . ' ';
				$content .= '<i>' . $row->comp_remark . '</i>';
			}

			if (count($ipd_discharge_complaint_remark) > 0) {
				if ($ipd_discharge_complaint_remark[0]->comp_remark != '') {
					$content .= nl2br($ipd_discharge_complaint_remark[0]->comp_remark);
				}
			}

			$content .= "</p>";


			$data['discharge_complaint'] = $content;
		}

		//Provisional Diagnosis at the time of Admission
		if (count($ipd_discharge_general_exam_col) > 0) {
			$content = '<p><b>General Examination on Admission : </b><br/>';

			foreach ($ipd_discharge_general_exam_col as $row) {
				$content .= $row->col_name . ' :<i>' . $row->rdata . $row->col_unit . '</i>&nbsp;&nbsp;&nbsp;';
			}
			$content .= '</p>';

			$data['discharge_general_exam'] = $content;
		}

		foreach ($ipd_discharge_sys_exam as $row) {
			$content = '';
			if ($row->rdata == null || $row->rdata == '') {
			} else {
				//$content.='<p style="'.$style_tag_P.'" align="center"><b>'.$row->sys_exam_name.'</b></p>';
				$content = $row->rdata;
			}

			$data['discharge_sys_exam'] = $content;
		}


		//Personal History

		$personal_history = '';

		if ($patient_master_data[0]->is_smoking == 1) {
			$personal_history .= 'Smoking ';
		}

		if ($patient_master_data[0]->is_alcohol == 1) {
			$personal_history .= 'Alcoholic ';
		}

		if ($patient_master_data[0]->is_drug_abuse == 1) {
			$personal_history .= 'Drug Abuse ';
		}

		if ($patient_master_data[0]->is_tobacoo == 1) {
			$personal_history .= 'Tobacoo ';
		}

		if ($patient_master_data[0]->is_hypertesion == 1) {
			$personal_history .= 'Hypertension';
		}

		if ($patient_master_data[0]->is_niddm == 1) {
			$personal_history .= 'Type 2 DM';
		}

		if ($patient_master_data[0]->is_hbsag == 1) {
			$personal_history .= 'HBsAg ';
		}

		if ($patient_master_data[0]->is_hcv == 1) {
			$personal_history .= 'HCV ';
		}

		if ($patient_master_data[0]->is_hiv_I_II == 1) {
			$personal_history .= 'HIV I-II ';
		}

		if ($patient_master_data[0]->Others == 1) {
			$personal_history .= 'Others ';
		}

		if (strlen($personal_history) > 0) {
			$content = "<p><b>Personal History : </b> " . $personal_history . "</p>";

			$data['personal_history'] = $content;
		}

		//CLINICAL INVESTIGATION REPORTS
		$data['lab_test_content'] = "<p><b>Clinical Investigation Reports : </b> ";
		$content = '';
		if (isset($lab_request)) {

			$content .= '<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
						<tbody>';
			$content .= '<tr>';
			$content .= '<th>Test Name</th>';
			$content .= '<th>Fixed Normals</th>';
			foreach ($lab_list_array as $key => $value) {
				$content .= '<th>' . MysqlDate_to_str(str_replace("'", "", $value)) . '</th>';
			}
			$content .= '</tr>';

			foreach ($lab_request as $row) {
				$content .= '<tr>';
				$content .= '<td>' . $row['Test'] . '</td>';
				$content .= '<td>' . $row['FixedNormals'] . '</td>';
				foreach ($lab_list_array as $key => $value) {
					$content .= '<td>' . $row['lab_test_value_' . $key] . '</td>';
				}
				$content .= '</tr>';
			}

			$content .= '</tbody></table>';

			if (isset($data['lab_test_content'])) {
				$data['lab_test_content'] .= $content;
			} else {
				$data['lab_test_content'] = $content;
			}
		}

		//investigation Manual
		if (count($ipd_discharge_exam_on_discharge_col_1) > 0) {
			$content = '';
			foreach ($ipd_discharge_exam_on_discharge_col_1 as $row) {
				$content .= '<b>' . $row->col_name . '</b>: ' . $row->rdata . '&nbsp;&nbsp; /&nbsp;';
			}
			$content .= "<br/>";

			if (isset($data['lab_test_content'])) {
				$data['lab_test_content'] .= $content;
			} else {
				$data['lab_test_content'] = $content;
			}
		}


		//Manual X-Ray
		if (count($ipd_discharge_exam_on_discharge_col_2) > 0) {
			$content = '';
			foreach ($ipd_discharge_exam_on_discharge_col_2 as $row) {
				$content .= '<b>' . $row->col_name . ':</b>' . $row->rdata . "<br/>";
			}

			if (isset($data['lab_test_content'])) {
				$data['lab_test_content'] .= $content;
			} else {
				$data['lab_test_content'] = $content;
			}
		}

		if (count($ipd_discharge_2_rdata) > 0) {
			$content = '';
			if ($ipd_discharge_2_rdata[0]->rdata != '') {
				$content .= '<div>' . $ipd_discharge_2_rdata[0]->rdata . '</div>';
			}
			if (isset($data['lab_test_content'])) {
				$data['lab_test_content'] .= $content;
			} else {
				$data['lab_test_content'] = $content;
			}
		}

		$data['lab_test_content'] .= '</p>';

		//Final Diagnosis at the time of Discharge
		$content = '<p><b>Final Diagnosis</b>';
		if (count($ipd_discharge_diagnosis) > 0) {
			foreach ($ipd_discharge_diagnosis as $row) {
				$content .= '<br/>' . $row->comp_report . '  ';
				if (strlen($row->comp_remark) > 0) {
					$content .= '<i>(' . $row->comp_remark . ')</i>';
				}
			}
		}

		if (count($ipd_discharge_diagnosis_remark) > 0) {
			if ($ipd_discharge_diagnosis_remark[0]->comp_remark != '') {
				$content .= '<br/>' . nl2br(trim($ipd_discharge_diagnosis_remark[0]->comp_remark));
			}
		}

		$content .= "</p>";

		$data['FinalDiagnosis'] = $content;

		//Summary of key investigtions during Hospitalization 
		if (count($ipd_discharge_investigtions_inhos) > 0) {
			$content = '';
			if ($ipd_discharge_investigtions_inhos[0]->comp_remark != '') {
				$content .= "<B>Summary of key investigtions during Hospitalization</B>";
				$content .= '<div>' . $ipd_discharge_investigtions_inhos[0]->comp_remark . '</div>';
			}

			$data['discharge_investigtions_inhos'] = $content;
		}

		//Course in the hospital
		if (count($ipd_discharge_course) > 0) {
			$content = '<p><B>Course in the hospital</B>';
			foreach ($ipd_discharge_course as $row) {
				$content .= '<br/>' . $row->comp_report . ' ';
				$content .= '<i>' . $row->comp_remark . '</i>';
			}
			$content .= "</p>";

			$data['Course_in_the_hospital'] = $content;
		}

		if (count($ipd_discharge_course_remark) > 0) {
			$content = '';
			if ($ipd_discharge_course_remark[0]->comp_remark != '') {
				$content .= '<div>' . $ipd_discharge_course_remark[0]->comp_remark . '</div>';
			}
			if (isset($data['Course_in_the_hospital'])) {
				$data['Course_in_the_hospital'] .= $content;
			} else {
				$data['Course_in_the_hospital'] = '<p><B>Course in the hospital</B>' . $content . "</p>";
			}
		}


		//Exam in Discharge
		if (count($ipd_discharge_exam_on_discharge_col) > 0) {
			$content = '<p><b>Examination on Discharge : </b>';
			$content .= '';

			foreach ($ipd_discharge_exam_on_discharge_col as $row) {
				$content .= $row->col_name . '<i>: ' . $row->rdata . ' ' . $row->col_unit . '</i>&nbsp;&nbsp; /&nbsp;';
			}
			$content .= '</p>';

			$data['discharge_exam_on_discharge'] = $content;
		}

		//Discharge Medicince

		$sql = "SELECT pt.* ,d.dose_show_sign AS dose_shed,d.dose_show_desc AS dose_shed_hindi,
		dw.dose_sign_desc AS dose_when,dw.dose_sign_hindi AS dose_when_hindi,
		df.dose_sign_desc AS dose_frequency,df.dose_sign_hindi AS dose_frequency_hindi,
		d_on.dose_sign_desc AS dose_where,d_on.dose_sign_hindi AS dose_where_hindi,
		m.genericname
		FROM ((((ipd_discharge_prescrption_prescribed pt
		LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
		LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
		LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
		LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id)
		LEFT JOIN  opd_med_master m ON pt.med_id =m.id
		where pt.ipd_id=" . $ipdno . " Order by pt.id";
		$query = $this->db->query($sql);
		$ipd_p_prescribed = $query->result();

		$medical = '<p>';
		$sr_no = 1;
		if (count($ipd_p_prescribed) > 0) {
			$medical = "<p><b>Discharge Medications</b>";
			$medical .= '<div style="padding-left:5px;" >
			<table width="100%" style="border:0px;" >
				<tr style="font-size: 10px;">
					<th></th>
					<th style="font-size: 10px;">Medicine Name</th>
					<th style="font-size: 10px;">Dosage</th>
					<th style="font-size: 10px;">Qty</th>
					<th style="font-size: 10px;">Day</th>
				</tr>';

			foreach ($ipd_p_prescribed as $row) {

				$medical .= '<tr >
								<td></td>
								<td style="font-size: 10px;">' . $sr_no . ' - ' . $row->med_type . ' ' . $row->med_name . '</td>
								<td style="font-size: 10px;">' . $row->dose_shed . ' / <span style="font-family:freeserif;">' . $row->dose_shed_hindi . '  ' . $row->dose_when_hindi . '  ' . $row->dose_frequency_hindi . '</span></td>
								<td style="font-size: 10px;">' . $row->qty . '</td>
								<td style="font-size: 10px;">' . $row->no_of_days . '</td>
							</tr>';
				$medical .= '<tr style="border-bottom: 1px solid black;">
								<td></td>
								<td colspan="4">';
				if (strlen($row->genericname) > 0 || strlen($row->remark) > 0 || strlen($row->dose_shed_hindi) > 0) {
					if (strlen($row->genericname) > 1) {
						$medical .= '<span style="font-size: 8px;">Composition : <i>' . $row->genericname . '</i></span>';
					}

					if (strlen($row->remark) > 0) {
						$medical .= '<span style="font-size: 8px;"> Notes : ' . $row->remark . '</span>';
					}
				}

				$medical .= '<hr style="margin: 0px 0px 0px 0px;"></td>
							</tr>';

				$sr_no = $sr_no + 1;
			}
			$medical .= "</table></p></div>";

			$data['Discharge_Medications'] = $medical;
		}


		$foot_banner_list = "0";
		$content = "";
		//Discharge Instructions 1  
		if (count($ipd_discharge_instructions) > 0) {

			$content .= '<div><br/><b>Discharge Advice/Instructions/Summary</b></div>';

			if ($ipd_discharge_instructions[0]->comp_remark <> '') {
				$content .= $ipd_discharge_instructions[0]->comp_remark;
			}

			if (strlen($ipd_discharge_instructions[0]->review_after) > 0) {
				$content .= '<div>Review after ' . $ipd_discharge_instructions[0]->review_after . ' days / as and when required</div>';
			}

			if (strlen($ipd_discharge_instructions[0]->footer_banner) > 0) {
				$foot_banner_list = $ipd_discharge_instructions[0]->footer_banner;
			}

			if ($ipd_discharge_instructions[0]->footer_text != '') {
				$content .= $ipd_discharge_instructions[0]->footer_text;
			}

			if ($foot_banner_list <> "0") {
				$sql = "SELECT m.id,m.banner_name,m.banner_image_name
				FROM ipd_discharge_banner m where m.id IN (" . $foot_banner_list . ") order by id desc";
				$query = $this->db->query($sql);
				$dis_banner_list = $query->result();

				foreach ($dis_banner_list as $row) {
					$content .= '<br/><br/><img width="750"  src="assets/images/' . $row->banner_image_name . '" />';
				}
			}
		}

		$data['Discharge_Instructions'] = $content;

		$content_html = $this->load->view('IPD_Discharge/' . D_discharge_content_format, $data, true);

		// Update in Database
		$dataupdate = array(
			'ipd_id' => $ipdno,
			'content' => $content_html
		);
		$this->ipdDischarge_M->update_ipdDischarge($dataupdate, $ipd_discharge[0]->id);

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge'] = $query->result();


		$data['content'] = $content_html;

		$this->load->view('IPD_Discharge/ipd_discharge_preview', $data);
	}




	//Report Format

	public function create_discharge_report($ipdno)
	{
		$content = "";

		$dataupdate = array(
			'ipd_create' => 1,
		);

		$this->Ipd_M->update($dataupdate, $ipdno);

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();

		$sql = "select * from ipd_master where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master_data = $query->result();

		$p_id = $ipd_master[0]->p_id;
		$ipd_admit_date = $ipd_master[0]->register_date;

		$sql = "select * from patient_master_exten where  id='" . $p_id . "' ";
		$query = $this->db->query($sql);
		$patient_master = $query->result();


		$sql = "select * from patient_master where  id='" . $p_id . "' ";
		$query = $this->db->query($sql);
		$patient_master_data = $query->result();

		//IPD DEpartment

		$dept_id = $ipd_master_data[0]->dept_id;

		$sql = "select * from hc_department where  iId=$dept_id";
		$query = $this->db->query($sql);
		$ipd_department = $query->result();

		if ($ipd_master[0]->discharge_date == null || $ipd_master[0]->discharge_date == '0000-00-00' || $ipd_master[0]->discharge_date == '1901-01-01') {
			$ipd_discharge_date = date('Y-m-d');
		} else {
			$ipd_discharge_date = $ipd_master[0]->discharge_date;
		}

		if ($ipd_master[0]->case_id == 0) {
			$Discharge_time = date('h:m A', strtotime($ipd_master[0]->discharge_time));
			$Reg_time = date('h:m A', strtotime($ipd_master[0]->reg_time));
		} else {
			$Discharge_time = '';
			$Reg_time = '';
		}


		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$doc_list = $ipd_master[0]->doc_list;

		$sql = "select d.id,d.p_fname,d.p_title,d.mphone1,d.mphone2,
		if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age,
		d.dob,d.zip,d.email1,d.email2,
		group_concat(distinct  m.SpecName) as SpecName,d.doc_sign
		from (doctor_master d left join  (doc_spec s join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
		where d.id in (" . $doc_list . ") group by d.id";
		$query = $this->db->query($sql);
		$doc_master = $query->result();


		$depart_name = '<table border="1" width="100%" cellpadding="1" cellspacing="1">
						<tr>
							<td colspan="2" align="center">
								<h3>Department/Specialty : <b>' . $ipd_department[0]->vName . '</b></h3>
							</td>
						</tr> 
						</table>';


		$doc_list = "";
		$doc_list_sign = '<table border="1" width="100%"><tr><td colspan="2" align="center"><h2>Treating Consultant / Authorized Team Doctor</h2></td></tr> ';

		$doc_list_main_sign = '<table border="1" width="100%" cellpadding="1" cellspacing="1"><tr><td colspan="2" align="center"><h3>Treating Consultant/Department/Specialty </h3></td></tr> ';

		$row_num = 0;
		foreach ($doc_master as $row) {
			$row_num = $row_num + 1;

			$doc_list .= "Dr. " . $row->p_fname . " [" . $row->SpecName . "] <br/>";

			$doc_list_sign .= '
				<tr>
					<td ><b>Dr. ' . $row->p_fname . '</b>
					<br /><i>' . $row->SpecName . '</i>
					<br />
					<br />
					<br /></td>
					<td style="text-align:center; vertical-align:top">Signature of Consultant</td>
				</tr>';

			$mod_r = $row_num % 2;
			if ($mod_r > 0) {
				$doc_list_main_sign .= '<tr>';
			}

			$doc_list_main_sign .= '
				<td style="width:50%"><b>Dr. ' . $row->p_fname . '</b><br/>[' . $row->SpecName . '] <br/>
					' . nl2br($row->doc_sign) . '
				</td>';

			if ($mod_r == 0) {
				$doc_list_main_sign .= '</tr>';
			}
		}
		$row_num = $row_num + 1;
		$mod_r = $row_num % 2;
		if ($mod_r == 0) {

			$doc_list_main_sign .= '<td style="width:50%"></td>
			</tr>';
		}


		$doc_list_sign .= "</table>";

		$doc_list_main_sign .= "</table>";

		//Presenting Complaints with Duration and Reason for Admission

		$sql = "select * from ipd_discharge_complaint where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_complaint = $query->result();

		$sql = "select * from ipd_discharge_complaint_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_complaint_remark = $query->result();


		//Surgery
		$sql = "select * from ipd_discharge_surgery where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_surgery = $query->result();

		//Prosedure
		$sql = "select * from ipd_discharge_procedure where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_procedure = $query->result();

		//General examination

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,d.ipd_d_id,g.col_unit
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "' 
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_general_exam_col = $query->result();

		$sql = "select g.id,g.sys_exam_name,g.sys_format,d.rdata 
		from ipd_discharge_sys_exam g left join ipd_discharge_1_a d on g.id=d.head_id and d.ipd_d_id='" . $ipdno . "'  ";
		$query = $this->db->query($sql);
		$ipd_discharge_sys_exam = $query->result();


		//CLINICAL INVESTIGATION REPORTS

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "'  and CHAR_LENGTH(lab_investigation_list)>0";
		$query = $this->db->query($sql);
		$ipd_discharge_2 = $query->result();

		$sql = "select * from ipd_discharge_2 where ipd_d_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_2_rdata = $query->result();

		$lab_list = "";

		if (count($ipd_discharge_2) > 0) {
			$lab_list = $ipd_discharge_2[0]->lab_investigation_list;
		}

		if (strlen($lab_list) > 0) {
			$lab_list_array = explode(",", $lab_list);
			$sql_lab_test = "SELECT t.Test,t.Unit,t.FixedNormals";

			foreach ($lab_list_array as $key => $value) {
				$sql_lab_test .= "," . "group_concat(if(m.inv_date=" . $value . ",i.lab_test_value,null)) AS lab_test_value_" . $key;
			}

			$sql_lab_test .= " FROM ((((invoice_master m JOIN lab_request l ON m.id=l.charge_id)
							JOIN lab_request_item i ON l.id=i.lab_request_id)
							JOIN lab_tests t ON i.lab_test_id=t.mstTestKey)
							JOIN ipd_discharge_investigation_template d ON t.TestID=d.test_code)
							JOIN lab_rgroups g ON d.group_test=g.mstRGrpKey
							WHERE m.attach_id=" . $p_id . " and m.inv_date IN (" . $lab_list . ")
							GROUP BY d.test_code
							ORDER BY g.sort_order,t.mstTestKey";


			$query = $this->db->query($sql_lab_test);
			$lab_request = $query->result_array();
		}

		//Final Diagnosis 

		$sql = "select * from ipd_discharge_diagnosis where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_diagnosis = $query->result();

		$sql = "select * from ipd_discharge_diagnosis_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_diagnosis_remark = $query->result();


		//Summary of key investigtions during Hospitalization 

		$sql = "select * from ipd_discharge_investigtions_inhos where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_investigtions_inhos = $query->result();

		//Course in the hospital 

		$sql = "select * from ipd_discharge_course where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_course = $query->result();

		$sql = "select * from ipd_discharge_course_remark where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_course_remark = $query->result();

		//Exam. in Discharge

		$sql = "select g.id,g.col_name,g.col_description,d.col_id,d.short_head,d.rdata,g.col_unit
		from ipd_discharge_general_exam_col g left join ipd_discharge_1_b_final d on g.id=d.col_id and d.ipd_d_id='" . $ipdno . "'  
		where CHAR_LENGTH(d.rdata)>0 ";
		$query = $this->db->query($sql);
		$ipd_discharge_exam_on_discharge_col = $query->result();


		//Discharge Medications

		$sql = "select * from ipd_discharge_drug where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_drug = $query->result();

		//Discharge Instructions 

		$sql = "select * from ipd_discharge_instructions where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_instructions = $query->result();

		//Discharge FOOD DRUG INTERACTION 
		$sql = "select * from ipd_discharge_drug_food_interaction where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge_drug_food_interaction = $query->result();

		$food_list = "0";
		if (count($ipd_discharge_drug_food_interaction) > 0) {
			if (strlen($ipd_discharge_drug_food_interaction[0]->food_id_list) > 0) {
				$food_list = $ipd_discharge_drug_food_interaction[0]->food_id_list;
			}
		}

		$sql = "SELECT m.id,m.food_short
				FROM ipd_discharge_master_food m where m.id IN (" . $food_list . ")";
		$query = $this->db->query($sql);
		$dis_food_interaction = $query->result();

		//HTML Style

		$style_tag_P = "LINE-HEIGHT:20px;";

		$content_space_line = '<table border="0" cellpadding="0" cellspacing="0" style="width:100%">
				<tbody>
					<tr>
						<td style="text-align:center; vertical-align:middle">
						
						</td>
					</tr>
				</tbody>
				</table>';


		//Content Start
		$sql = "SELECT * FROM ipd_discharg_status where id =" . $ipd_master[0]->discarge_patient_status;
		$query = $this->db->query($sql);
		$ipd_discharge_status = $query->result();

		if (count($ipd_discharge_status) < 1) {
			$h1_head = '';
		} else {
			$h1_head = $ipd_discharge_status[0]->status_details;
		}

		$content .= '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
				<tbody>
					<tr>
						<td style="text-align:center; vertical-align:middle">
						<h1>' . $h1_head . '</h1>
						</td>
					</tr>
					<tr><td></td></tr>
				</tbody>
				</table>';

		$content .= $depart_name;

		$content .= $content_space_line;

		$content .= $doc_list_main_sign;

		$content .= $content_space_line;

		if ($ipd_master[0]->case_id == '0') {
			$discharge_date_time = date('h:m A', strtotime($ipd_master[0]->discharge_time));
		} else {
			$discharge_date_time = '';
		}

		$discharge_date_time = $ipd_master[0]->discharge_time;

		$p_address = "";

		if (strlen($patient_master[0]->p_address) > 2) {
			$p_address = "<br/>Address : " . $patient_master[0]->p_address;
		}

		if (strtoupper($patient_master[0]->blood_group) == 'NOT DEFINE') {
			$blood_group_show = '';
		} else {
			$blood_group_show = 'Blood Group : ' . $patient_master[0]->blood_group;
		}

		$content .= '<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
			<tbody>
				<tr>
					<td colspan="4" style="text-align:center; vertical-align:middle">
						<h3>Patient Information</h3>
					</td>
				</tr>
				<tr>
					<td colspan="2" rowspan="1">Name of Patient&nbsp; : <b>' . ucwords($ipd_master[0]->p_fname) . '</b>
					<br /><i>' . ucwords($ipd_master[0]->p_relative) . ' ' . ucwords($ipd_master[0]->p_rname) . '</i>' . $p_address . '</td>
					<td>UHID:<b>' . $ipd_master[0]->p_code . '</b></td>
					<td>IPD No. : <b>' . $ipd_master[0]->ipd_code . '</b></td>
				</tr>
				<tr>
					<td colspan="2" rowspan="1">Age :&nbsp;<b>' . $ipd_master[0]->str_age . '</b> /&nbsp; Gender : &nbsp;<b>' . $ipd_master[0]->xgender . '</b> </td>
					<td rowspan="1">TPA:<b>' . $ipd_master[0]->admit_type . '</b></td>
					<td rowspan="1">Discharge Status : <b>' . $ipd_master[0]->status_desc . '</b> </td>
				</tr>
				<tr>
					<td colspan="2">Date of Admission : <b>' . $ipd_master[0]->str_register_date . ' ' . $Reg_time . ' </b></td>
					<td colspan="2">Date of Discharge : <b>' . $ipd_master[0]->str_discharge_date . ' ' . $Discharge_time . ' </b></td>
				</tr>
				<tr>
					<td colspan="2">MLC :<b>' . $ipd_master[0]->mlc_type . '</b></td>
					<td colspan="2">' . $blood_group_show . '</td>
				</tr>
			</tbody>
		</table>';

		$content .= $content_space_line;

		if ($ipd_master[0]->issurgery == 1 || $ipd_master[0]->isdelivery == 1 || count($ipd_discharge_surgery) > 0 || count($ipd_discharge_procedure) > 0) {
			$content .= '
			<table border="1" cellpadding="1" cellspacing="1" style="width:100%">
				<tbody>';

			if ($ipd_master[0]->issurgery == 1) {
				$content .= '
					<tr>
						<td>
							<h3>Surgery</h3>
						</td>
						<td >' . $ipd_master[0]->surgery_name . '</td>
						<td >Date of Surgery : ' . MysqlDate_to_str($ipd_master[0]->surgery_date) . '</td>
					</tr>';
			}

			if (count($ipd_discharge_surgery) > 0) {
				foreach ($ipd_discharge_surgery as $row) {
					$content .= '
					<tr>
						<td>
							<h3>Surgery</h3>
						</td>
						<td >' . $row->surgery_name . '</td>
						<td >Date of Surgery : ' . MysqlDate_to_str($row->surgery_date) . '</td>
					</tr>';
				}
			}

			if (count($ipd_discharge_procedure) > 0) {
				foreach ($ipd_discharge_procedure as $row) {
					$content .= '
					<tr>
						<td>
							<h3>Procedure</h3>
						</td>
						<td >' . $row->procedure_name . '</td>
						<td >Date of Procedure : ' . MysqlDate_to_str($row->procedure_date) . '</td>
					</tr>';
				}
			}



			if ($ipd_master[0]->isdelivery == 1) {
				$baby_desc = 'Sex of baby: ' . $ipd_master[0]->delivery_sex_of_baby . '';

				if ($ipd_master[0]->delivery_sex_of_baby2 <> '') {
					$baby_desc .= 'Sex of Second Baby :' . $ipd_master[0]->delivery_sex_of_baby2 . '';
				}

				if ($ipd_master[0]->delivery_sex_of_baby3 <> '') {
					$baby_desc .= 'Sex of Third Baby :' . $ipd_master[0]->delivery_sex_of_baby3 . '';
				}

				$content .= '
					<tr>
						<td>
							<h3>Child Birth</h3>
						</td>
						<td >Delivery Date and Time : ' . MysqlDate_to_str($ipd_master[0]->delivery_date) . ' ' . $ipd_master[0]->delivery_time . '</td>
						<td >' . $baby_desc . ' </td>
					</tr>';
			}

			$content .= '
				</tbody>
			</table>';
		}



		//Presenting Complaints with Duration and Reason for Admission

		$content .= '<h3>Presenting Complaints and Reason for Admission</h3>';

		foreach ($ipd_discharge_complaint as $row) {
			$content .= '' . $row->comp_report . '<br />';
			$content .= '<i>' . $row->comp_remark . '</i>';
		}

		if (count($ipd_discharge_complaint_remark) > 0) {
			if ($ipd_discharge_complaint_remark[0]->comp_remark != '') {
				$content .= '' . nl2br($ipd_discharge_complaint_remark[0]->comp_remark) . ' ';
			}
		}

		//Provisional Diagnosis at the time of Admission

		$content .= '
		<h3>General Examination on Admission</h3>
		<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
			<tbody>';
		$icol = 0;
		$no_col = 4;
		foreach ($ipd_discharge_general_exam_col as $row) {
			if ($icol % $no_col == 0) {
				$content .= '<tr>';
			}
			$content .= '<td><b>' . $row->col_name . ':</b> ' . $row->rdata . ' ' . $row->col_unit . '</td>';

			if ($icol % $no_col == $no_col - 1) {
				$content .= '</tr>';
			}

			$icol = $icol + 1;
		}

		while ($icol % $no_col > 0) {
			$content .= '<td> &nbsp;&nbsp;</td>';

			if ($icol % $no_col == $no_col - 1) {
				$content .= '</tr>';
			}
			$icol = $icol + 1;
		}

		$content .= '</table>';

		foreach ($ipd_discharge_sys_exam as $row) {
			if ($row->rdata == null || $row->rdata == '') {
			} else {
				//$content.='<p style="'.$style_tag_P.'" align="center"><b>'.$row->sys_exam_name.'</b></p>';
				$content .= '<div>' . $row->rdata . '</div>';
			}
		}

		//Personal History

		$personal_history = '';

		if ($patient_master_data[0]->is_smoking == 1) {
			$personal_history .= 'Smoking ';
		}

		if ($patient_master_data[0]->is_alcohol == 1) {
			$personal_history .= 'Alcoholic ';
		}

		if ($patient_master_data[0]->is_drug_abuse == 1) {
			$personal_history .= 'Drug Abuse ';
		}

		if ($patient_master_data[0]->is_tobacoo == 1) {
			$personal_history .= 'Tobacoo ';
		}

		if ($patient_master_data[0]->is_hypertesion == 1) {
			$personal_history .= 'Hypertension ';
		}

		if ($patient_master_data[0]->is_niddm == 1) {
			$personal_history .= 'NIDDM ';
		}

		if ($patient_master_data[0]->is_hbsag == 1) {
			$personal_history .= 'HBsAg ';
		}

		if ($patient_master_data[0]->is_hcv == 1) {
			$personal_history .= 'HCV ';
		}

		if ($patient_master_data[0]->is_hiv_I_II == 1) {
			$personal_history .= 'HIV I-II ';
		}

		if ($patient_master_data[0]->Others == 1) {
			$personal_history .= 'Others ';
		}

		if (strlen($personal_history) > 0) {
			$content .= "<b>Personal History : </b> " . $personal_history;
		}

		//CLINICAL INVESTIGATION REPORTS
		if (isset($lab_request)) {
			$content .= '<h3>Clinical Investigation Reports</h3>';
			$content .= '<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
						<tbody>';

			$content .= '<tr>';
			$content .= '<th> </th>';
			$content .= '<th>Fixed Normals</th>';
			foreach ($lab_list_array as $key => $value) {
				$content .= '<th>' . MysqlDate_to_str(str_replace("'", "", $value)) . '</th>';
			}
			$content .= '</tr>';

			foreach ($lab_request as $row) {
				$content .= '<tr>';
				$content .= '<td>' . $row['Test'] . '</td>';
				$content .= '<td>' . $row['FixedNormals'] . '</td>';
				foreach ($lab_list_array as $key => $value) {
					$content .= '<td>' . $row['lab_test_value_' . $key] . '</td>';
				}
				$content .= '</tr>';
			}

			$content .= '</tbody></table>';
		}

		if (count($ipd_discharge_2_rdata) > 0) {
			if ($ipd_discharge_2_rdata[0]->rdata != '') {
				$content .= '<div style="' . $style_tag_P . '">' . $ipd_discharge_2_rdata[0]->rdata . '</div>';
			}
		}

		//Final Diagnosis at the time of Discharge

		$content .= '<br/><h3>Final Diagnosis</h3>';

		foreach ($ipd_discharge_diagnosis as $row) {
			$content .= '<br/>' . $row->comp_report . '  ';
			$content .= '<i>' . $row->comp_remark . '</i>';
		}

		if (count($ipd_discharge_diagnosis_remark) > 0) {
			if ($ipd_discharge_diagnosis_remark[0]->comp_remark != '') {
				$content .= '<div>' . $ipd_discharge_diagnosis_remark[0]->comp_remark . '</div>';
			}
		}

		//Summary of key investigtions during Hospitalization 
		if (count($ipd_discharge_investigtions_inhos) > 0) {
			if ($ipd_discharge_investigtions_inhos[0]->comp_remark != '') {
				$content .= "<h3>Summary of key investigtions during Hospitalization</h3>";
				$content .= '<div>' . $ipd_discharge_investigtions_inhos[0]->comp_remark . '</div>';
			}
		}

		//Course in the hospital

		$content .= '<br/>
			<h3>Course in the hospital</h3>';

		foreach ($ipd_discharge_course as $row) {
			$content .= '<br/>' . $row->comp_report . ' ';
			$content .= '<i>' . $row->comp_remark . '</i>';
		}

		if (count($ipd_discharge_course_remark) > 0) {
			if ($ipd_discharge_course_remark[0]->comp_remark != '') {
				$content .= '<div>' . $ipd_discharge_course_remark[0]->comp_remark . '</div>';
			}
		}

		//Exam in Discharge

		$content .= '<h3>Examination on Discharge</h3>';

		$content .= '
		<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
			<tbody>';
		$icol = 0;
		$no_col = 4;
		foreach ($ipd_discharge_exam_on_discharge_col as $row) {
			if ($icol % $no_col == 0) {
				$content .= '<tr>';
			}
			$content .= '<td><b>' . $row->col_name . '</b>: ' . $row->rdata . ' ' . $row->col_unit . '</td>';

			if ($icol % $no_col == $no_col - 1) {
				$content .= '</tr>';
			}

			$icol = $icol + 1;
		}

		while ($icol % $no_col > 0) {
			$content .= '<td>&nbsp;&nbsp;</td>';

			if ($icol % $no_col == $no_col - 1) {
				$content .= '</tr>';
			}
			$icol = $icol + 1;
		}

		$content .= '</tbody></table>';

		$content .= '<br/><h3>Discharge Medications</h3>';
		$content .= "<ol>";
		foreach ($ipd_discharge_drug as $row) {
			$content .= '<li>' . $row->drug_name . ' ----------> ';

			$content .= '<i> ' . $row->drug_dose . '</i>';

			if (strlen($row->drug_day) > 0) {
				$content .= ' [ ' . $row->drug_day . " days] ";
			} else {
				$content .= ' ';
			}

			$content .= '</li>';
		}
		$content .= "</ol>";



		//FOOD DRUG INTERACTION 
		if (count($ipd_discharge_drug_food_interaction) > 0) {
			if (strlen($ipd_discharge_drug_food_interaction[0]->food_text) > 0 || count($dis_food_interaction) > 0) {
				$content .= "<h3>Dietary</h3>";
			}
		}

		if (count($ipd_discharge_drug_food_interaction) > 0) {
			if ($ipd_discharge_drug_food_interaction[0]->food_text != '') {
				$content .= $ipd_discharge_drug_food_interaction[0]->food_text;
			}
		}

		foreach ($dis_food_interaction as $row) {
			$content .= $row->food_desc_lang . '<br/> ';
			//$content.='<i>'.$row->food_desc.'</i>';
		}

		//Discharge Instructions  
		if (count($ipd_discharge_instructions) > 0) {
			if ($ipd_discharge_instructions[0]->comp_remark != '') {
				$content .= "<h3>Discharge Advice/Instructions/Summary </h3>";
				$content .= '<div>' . $ipd_discharge_instructions[0]->comp_remark . '</div>';
			}

			if ($ipd_discharge_instructions[0]->footer_text != '') {
				$content .= $ipd_discharge_instructions[0]->footer_text;
			}
		}




		//FOOD DRUG INTERACTION 
		//$content.=$doc_list_sign;

		//Foot Banner
		$foot_banner_list = "0";
		if (count($ipd_discharge_instructions) > 0) {
			if (strlen($ipd_discharge_instructions[0]->footer_banner) > 0) {
				$foot_banner_list = $ipd_discharge_instructions[0]->footer_banner;
			}
		}

		$sql = "SELECT m.id,m.banner_name,m.banner_image_name
				FROM ipd_discharge_banner m where m.id IN (" . $foot_banner_list . ") order by id desc";
		$query = $this->db->query($sql);
		$dis_banner_list = $query->result();

		foreach ($dis_banner_list as $row) {
			$content .= '<br/><br/><img width="750"  src="assets/images/' . $row->banner_image_name . '" />';
		}

		// Update in Database
		$dataupdate = array(
			'ipd_id' => $ipdno,
			'content' => $content
		);
		$this->ipdDischarge_M->update_ipdDischarge($dataupdate, $ipd_discharge[0]->id);

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge'] = $query->result();

		$this->load->view('IPD_Discharge/ipd_discharge_preview', $data);
	}

	//

	public function edit_discharge_report($ipdno)
	{
		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$data['ipd_discharge'] = $query->result();

		$this->load->view('IPD_Discharge/ipd_discharge_preview', $data);
	}


	public function show_file($ipdno)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();

		// Doctor Name

		$docname = '';
		$docedu = '';

		$report_head = 'Patient Name :' . $ipd_master[0]->p_fname . ' / Age :' . $ipd_master[0]->str_age . ' / Gender :' . $ipd_master[0]->xgender . ' / IPD ID :' . $ipd_master[0]->ipd_code;

		$complete_report = $ipd_discharge[0]->content;

		$folder_name = 'uploads/' . date('Ymd');

		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$file_name = 'Report-IPD-Discharge-' . $ipdno . "-" . date('Ymdhis') . ".pdf";

		$filepath = $folder_name . '/' . $file_name;

		$udata = array(
			'file_name' => $file_name,
			'file_type' => 'pdf',
			'file_path' => $folder_name,
			'full_path' => $filepath,
			'orig_name' => $file_name,
			'client_name' => 'system_genrate',
			'file_ext' => '.pdf',
			'upload_by' => $user_name,
			'pid' => $ipd_master[0]->p_id,
			'ipd_id' => $ipd_master[0]->id,
			'case_id' => 0,
			'file_desc' => $report_head,
			'charge_id' => 0
		);

		$this->load->model('File_M');

		$sql = "select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='" . $file_name . "'";
		$query = $this->db->query($sql);
		$check_file_exist = $query->result();

		if (count($check_file_exist) < 1) {
			$this->File_M->insert($udata);
		} else {
			$this->File_M->update($udata, $check_file_exist[0]->id);
		}

		create_Discharge_report_pdf($complete_report, $filepath, '', $report_head, $docname, $docedu);
	}



	public function show_file2($ipdno)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();

		// Doctor Name

		$docname = '';
		$docedu = '';

		$report_head = 'Patient Name :' . $ipd_master[0]->p_fname . ' / Age :' . $ipd_master[0]->str_age . ' / Gender :' . $ipd_master[0]->xgender . ' / IPD ID :' . $ipd_master[0]->ipd_code;

		$complete_report = $ipd_discharge[0]->content;

		$folder_name = 'uploads/' . date('Ymd');

		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$file_name = 'Report-IPD-Discharge-' . $ipdno . "-" . date('Ymdhis') . ".pdf";

		$filepath = $folder_name . '/' . $file_name;

		$udata = array(
			'file_name' => $file_name,
			'file_type' => 'pdf',
			'file_path' => $folder_name,
			'full_path' => $filepath,
			'orig_name' => $file_name,
			'client_name' => 'system_genrate',
			'file_ext' => '.pdf',
			'upload_by' => $user_name,
			'pid' => $ipd_master[0]->p_id,
			'ipd_id' => $ipd_master[0]->id,
			'case_id' => 0,
			'file_desc' => $report_head,
			'charge_id' => 0
		);

		$this->load->model('File_M');

		$sql = "select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='" . $file_name . "'";
		$query = $this->db->query($sql);
		$check_file_exist = $query->result();

		if (count($check_file_exist) < 1) {
			$this->File_M->insert($udata);
		} else {
			$this->File_M->update($udata, $check_file_exist[0]->id);
		}

		create_discharge_logo_pdf($complete_report, $filepath);
	}

	public function show_file3($ipdno)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();

		// Doctor Name

		$docname = '';
		$docedu = '';

		$report_head = 'Patient Name :' . $ipd_master[0]->p_fname . ' / Age :' . $ipd_master[0]->str_age . ' / Gender :' . $ipd_master[0]->xgender . ' / IPD ID :' . $ipd_master[0]->ipd_code;

		$report_foot = 'P:' . $ipd_master[0]->p_fname . ' / A:' . $ipd_master[0]->str_age . ' /G:' . $ipd_master[0]->xgender . ' /' . $ipd_master[0]->ipd_code;

		$complete_report = $ipd_discharge[0]->content;

		$folder_name = 'uploads/' . date('Ymd');

		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$file_name = 'Report-IPD-Discharge-' . $ipdno . "-" . date('Ymdhis') . ".pdf";

		$filepath = $folder_name . '/' . $file_name;

		$udata = array(
			'file_name' => $file_name,
			'file_type' => 'pdf',
			'file_path' => $folder_name,
			'full_path' => $filepath,
			'orig_name' => $file_name,
			'client_name' => 'system_genrate',
			'file_ext' => '.pdf',
			'upload_by' => $user_name,
			'pid' => $ipd_master[0]->p_id,
			'ipd_id' => $ipd_master[0]->id,
			'case_id' => 0,
			'file_desc' => $report_head,
			'charge_id' => 0
		);

		$this->load->model('File_M');

		$sql = "select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='" . $file_name . "'";
		$query = $this->db->query($sql);
		$check_file_exist = $query->result();

		if (count($check_file_exist) < 1) {
			$this->File_M->insert($udata);
		} else {
			$this->File_M->update($udata, $check_file_exist[0]->id);
		}

		//create_Discharge_report_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu);

		$content = '<style>
			body {

				font-family: freeserif;
			}

			table {
			    border-collapse: collapse;
			}
			thead {
			    vertical-align: bottom;
			    text-align: center;
			    font-weight: bold;
			}
			tfoot {
			    text-align: center;
			    font-weight: bold;
			}
			th {
			    text-align: left;
			    padding-left: 0.35em;
			    padding-right: 0.35em;
			    padding-top: 0.35em;
			    padding-bottom: 0.35em;
			    vertical-align: top;
			}
			td {
			    padding-left: 0.35em;
			    padding-right: 0.35em;
			    padding-top: 0.35em;
			    padding-bottom: 0.35em;
			    vertical-align: top;
			}

		p, td { font-family: freeserif;font-size: 11pt }

		</style>';

		$filename = '/uploads/' . time() . "_order.pdf";

		//load mPDF library
		$this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

		$this->m_pdf->pdf->SetWatermarkText(H_Name);
		$this->m_pdf->pdf->showWatermarkText = true;

		$Head_Print = '
			<table width="100%">
			    <tr>
			        <td width="66%">
						<br/>
						<br/>
						<br/>
						<br/>
						<br/>
						<br/>
						<br/>
						<br/>
						<br/>
					</td>
			        <td width="33%" style="text-align: right;"></td>
			    </tr>
			</table>';

		$this->m_pdf->pdf->SetHTMLFooter('
			<table width="100%">
			    <tr>
			        <td width="33%" >Page : {PAGENO}/{nbpg}</td>
					<td width="66%" style="text-align: right;"><i>' . $report_foot . '</i></td>
			        
			    </tr>
			</table>');

		//generate the PDF from the given html
		$this->m_pdf->pdf->WriteHTML($Head_Print . $content . $complete_report);

		//download it.
		$this->m_pdf->pdf->Output($filepath, "I");
	}

	public function show_discharge($ipdno, $print_type = 0)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$sql = "select * from ipd_discharge where  ipd_id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_discharge = $query->result();

		$sql = "select * from v_ipd_list where  id='" . $ipdno . "' ";
		$query = $this->db->query($sql);
		$ipd_master = $query->result();


		$department = '';

		$docname = '';
		$docedu = '';

		$data['report_head'] = 'Patient Name :' . $ipd_master[0]->p_fname . ' / Age :' . $ipd_master[0]->str_age . ' / Gender :' . $ipd_master[0]->xgender . ' / IPD ID :' . $ipd_master[0]->ipd_code;

		$data['report_foot'] = 'P:' . $ipd_master[0]->p_fname . ' / A:' . $ipd_master[0]->str_age . ' /G:' . $ipd_master[0]->xgender . ' /' . $ipd_master[0]->ipd_code;

		$data['complete_report'] = $ipd_discharge[0]->content;

		$data['print_type'] = $print_type;

		$folder_name = 'uploads/' . date('Ymd');

		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$file_name = 'Report-IPD-Discharge-' . $ipdno . "-" . date('Ymdhis') . ".pdf";

		$filepath = $folder_name . '/' . $file_name;

		$udata = array(
			'file_name' => $file_name,
			'file_type' => 'pdf',
			'file_path' => $folder_name,
			'full_path' => $filepath,
			'orig_name' => $file_name,
			'client_name' => 'system_genrate',
			'file_ext' => '.pdf',
			'upload_by' => $user_name,
			'pid' => $ipd_master[0]->p_id,
			'ipd_id' => $ipd_master[0]->id,
			'case_id' => 0,
			'file_desc' => $data['report_head'],
			'charge_id' => 0
		);

		$this->load->model('File_M');

		$sql = "select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='" . $file_name . "'";
		$query = $this->db->query($sql);
		$check_file_exist = $query->result();

		if (count($check_file_exist) < 1) {
			$this->File_M->insert($udata);
		} else {
			$this->File_M->update($udata, $check_file_exist[0]->id);
		}

		//create_Discharge_report_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu);

		$print_content = $this->load->view('IPD_Discharge/' . D_discharge_template, $data, TRUE);

		//$print_content=$this->load->view('IPD_Discharge/ipd_discharge_print_format_1',$data,TRUE);

		$filename = '/uploads/' . time() . "_order.pdf";

		//load mPDF library
		$this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

		$this->m_pdf->pdf->SetWatermarkText(H_Name);
		$this->m_pdf->pdf->showWatermarkText = true;

		//generate the PDF from the given html
		$this->m_pdf->pdf->WriteHTML($print_content);

		//download it.
		$this->m_pdf->pdf->Output($filepath, "I");
	}
}
