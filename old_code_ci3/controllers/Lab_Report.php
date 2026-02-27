<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lab_Report extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function lab_master()
	{
		$this->load->view('Lab_Panel/lab_main');
	}

	public function lab_path($lab_type)
	{
		$data['lab_type'] = $lab_type;
		$this->load->view('Lab_Panel/pathlab_request_list', $data);
	}

	public function search_lab_4($lab_type)
	{
		$sdata = $this->input->post('txtsearch');

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		if (trim($sdata) == '') {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,
			Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.item_name,t.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			Left JOIN lab_invoice_request r ON m.id=r.invoice_id  
			
			where m.payment_status=1 and y.itype_id=" . $lab_type . "  
			
			group by m.id order by m.id desc limit 200";
		} else {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.id,m.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id) 
			Left JOIN lab_invoice_request r ON m.id=r.invoice_id 
			
			where m.payment_status=1 and y.itype_id= " . $lab_type . " 	AND (m.invoice_code LIKE '%" . $sdata . "' 
			OR p.p_fname LIKE '%" . $sdata . "%' 
			OR p.p_code LIKE '%" . $sdata . "' )
			
			group by m.id order by m.id desc limit 50";
		}

		$query = $this->db->query($sql);
		$data['labreport_preprocess'] = $query->result();

		if ($lab_type == 5 or $lab_type == 6) // Pathology or Biopsy
		{
			$this->load->view('Lab_Panel/lab_report_tab_path', $data);
		} else {
			$this->load->view('Lab_Panel/lab_report_tab_1', $data);
		}
	}

	public function search_lab_4_labno($lab_type)
	{
		$sdata = $this->input->post('txtsearch_labno');

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		if (trim($sdata) == '') {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,
			Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.item_name,t.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			JOIN lab_invoice_request r ON m.id=r.invoice_id  And r.lab_test_no>0
			where m.payment_status=1 and y.itype_id=$lab_type and m.inv_date >=date_add(curdate(),interval -3 day) 
			group by m.id order by m.id desc limit 50";
		} else {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,
			Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.item_name,t.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			JOIN lab_invoice_request r ON m.id=r.invoice_id And r.lab_test_no>0
			
			where m.payment_status=1 and y.itype_id=$lab_type	AND r.lab_test_no=$sdata
			
			group by m.id order by m.id desc limit 50";
		}

		$query = $this->db->query($sql);
		$data['labreport_preprocess'] = $query->result();

		$this->load->view('Lab_Panel/lab_report_tab_1', $data);
	}


	public function search_lab_4_srno($lab_type)
	{
		$sdata = $this->input->post('txtsearch_srno');

		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);

		if (trim($sdata) == '') {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,
			Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.item_name,t.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			JOIN lab_invoice_request r ON m.id=r.invoice_id And r.daily_sr_no>0 
			where m.payment_status=1 and y.itype_id=$lab_type and m.inv_date >=date_add(curdate(),interval -3 day) 
			group by m.id order by m.id desc limit 50";
		} else {
			$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,
			Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age,
			group_concat(concat_ws(';',t.item_name,t.item_name,t.id,check_item_request(m.id,t.id)) SEPARATOR '#') as data_array,
			r.daily_sr_no,r.lab_test_no
			from ( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			JOIN lab_invoice_request r ON m.id=r.invoice_id And r.daily_sr_no>0
			
			where m.payment_status=1 and y.itype_id=$lab_type	AND r.daily_sr_no=$sdata
			
			group by m.id order by m.id desc limit 50";
		}

		$query = $this->db->query($sql);
		$data['labreport_preprocess'] = $query->result();

		$this->load->view('Lab_Panel/lab_report_tab_1', $data);
	}

	public function lab_tab_1_process($test_id, $lab_type)
	{
		$sql = "select m.invoice_code,p.p_fname as inv_name,t.item_name,t.id as test_id,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age,
			if(l.mstRepoKey is null,0,l.mstRepoKey) as mstRepoKey,if(l.mstRepoKey is null,item_name,l.Title) as Title,m.inv_date,
			r.lab_repo_id,y.group_desc as item_type_desc,y.itype_id,m.insurance_case_id,m.ipd_id,
			m.id as inv_id,m.attach_id,m.insurance_case_id,m.net_amount
			from (( invoice_item t join invoice_master m  join hc_item_type y join patient_master p
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id) 
			left join lab_request r on t.id=r.charge_item_id)
			left join lab_repo l  on t.item_id=l.charge_id
			where m.payment_status=1 and y.itype_id=" . $lab_type . " and r.id is null and t.id=" . $test_id;

		$query = $this->db->query($sql);
		$data['labreport_preprocess'] = $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$print_allowed = 0;
		$insert_id = 0;

		if (count($data['labreport_preprocess']) > 0) {
			if ($data['labreport_preprocess'][0]->insurance_case_id > 0 || $data['labreport_preprocess'][0]->ipd_id > 0) {
				$print_allowed = 1;
			} else {
				$sql = "select sum(if(credit_debit=0,amount,amount*-1)) as paid_amount from payment_history where payof_type=2 and payof_id=" . $data['labreport_preprocess'][0]->inv_id;
				$query = $this->db->query($sql);
				$data['inv_amount'] = $query->result();

				if (count($data['inv_amount']) < 1) {
					$paid_Amount = 0;
				} else {
					$paid_Amount = $data['inv_amount'][0]->paid_amount;
					$inv_amount = $data['labreport_preprocess'][0]->net_amount;

					if ($inv_amount <= $paid_Amount) {
						$print_allowed = 1;
					}
				}
			}

			$this->load->model('PathLab_M');

			$udata = array(
				'lab_repo_id' => $data['labreport_preprocess'][0]->mstRepoKey,
				'charge_id' => $data['labreport_preprocess'][0]->inv_id,
				'lab_type' => $data['labreport_preprocess'][0]->itype_id,
				'charge_item_id' => $data['labreport_preprocess'][0]->test_id,
				'invoice_code' => $data['labreport_preprocess'][0]->invoice_code,
				'patient_name' => $data['labreport_preprocess'][0]->inv_name,
				'patient_id' => $data['labreport_preprocess'][0]->attach_id,
				'ipd_id' => $data['labreport_preprocess'][0]->ipd_id,
				'org_id' => $data['labreport_preprocess'][0]->insurance_case_id,
				'Request_Date' => $data['labreport_preprocess'][0]->inv_date,
				'report_name' => $data['labreport_preprocess'][0]->Title,
				'insert_by' => $user_name,
				'print_allowed' => $print_allowed,
				'collected_time' => date('Y-m-d H:i:s')
			);

			//print_r ($udata);
			$insert_id = $this->PathLab_M->insert_test_request($udata);

			$sql = "select id from lab_invoice_request 
			where lab_type=$lab_type and invoice_id=" . $data['labreport_preprocess'][0]->inv_id;
			$query = $this->db->query($sql);
			$lab_invoice_request = $query->result();

			if (count($lab_invoice_request) < 1) {
				$sql = "select count(*) as no_rec from lab_invoice_request 
				where lab_type=$lab_type and date(report_insert)=curdate()";
				$query = $this->db->query($sql);
				$lab_invoice_count = $query->result();

				$count_rec = $lab_invoice_count[0]->no_rec;

				$count_rec = $count_rec + 1;

				$udata = array(
					'invoice_id' => $data['labreport_preprocess'][0]->inv_id,
					'invoice_code' => $data['labreport_preprocess'][0]->invoice_code,
					'lab_type' => $data['labreport_preprocess'][0]->itype_id,
					'print_allowed' => $print_allowed,
					'collected_time' => date('Y-m-d H:i:s'),
					'daily_sr_no' => $count_rec
				);
				$insert_invoice_request = $this->PathLab_M->insert_invoice_report($udata);
			} else {
				$udata = array(
					'print_allowed' => $print_allowed,
					'collected_time' => date('Y-m-d H:i:s')
				);
				$insert_invoice_request = $this->PathLab_M->update_invoice_report($udata, $lab_invoice_request[0]->id);
			}
		}

		//Add Second Step


		echo $insert_id;
	}

	public function create_report_xray($req_id)
	{

		$this->load->model('PathLab_M');

		$sql = "select * from lab_request where id=" . $req_id;
		$query = $this->db->query($sql);
		$data_lab_request = $query->result();

		$p_id = $data_lab_request[0]->patient_id;

		$sql = "select * from patient_master_exten where id=$p_id";
		$query = $this->db->query($sql);
		$data_patient_data = $query->result();


		$sql = "select mstRepoKey,Title,HTMLData,GrpKey,charge_id,RepoGrp 
		from lab_repo  join lab_rgroups on lab_repo.GrpKey=lab_rgroups.mstRGrpKey
		where mstRepoKey=" . $data_lab_request[0]->lab_repo_id;

		$query = $this->db->query($sql);
		$data_report_format = $query->result();

		$sql = "select * from invoice_master where attach_type=0 and id=" . $data_lab_request[0]->charge_id;
		$query = $this->db->query($sql);
		$data_invoice_master = $query->result();

		$sql = "select id from lab_invoice_request where invoice_id=" . $data_lab_request[0]->charge_id . " and lab_type=" . $data_lab_request[0]->lab_type;
		$query = $this->db->query($sql);
		$lab_invoice_request = $query->result();

		$sql = "select * from invoice_item where id=" . $data_lab_request[0]->charge_item_id;;
		$query = $this->db->query($sql);
		$invoice_item = $query->result();





		if (count($data_report_format) > 0) {

			$Report_Head_H3 = '<h3>' . $data_report_format[0]->Title . '</h3>';
			$Report_string = $data_report_format[0]->HTMLData;
		} else {

			$Report_Head_H3 = '<h3>' . $data_lab_request[0]->report_name . '</h3>';
			$Report_string = "";
		}

		$Report_Header = '<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
							<tbody>
								<tr>
									<td >' . $Report_Head_H3 . '</td>
								</tr>
							</tbody>
						</table>
						';

		$Report_Footer = $data_lab_request[0]->Remark;

		//update report_string with test parameter

		$complete_report = $Report_Header . $Report_string . $Report_Footer;

		$udata = array(
			'Report_Data' => $complete_report,
			'status' => 1,
			'reported_time' => date('Y-m-d H:i:s')
		);

		$this->PathLab_M->update_test_request($udata, $req_id);

		$udata = array(
			'reported_time' => date('Y-m-d H:i:s')
		);
		$this->PathLab_M->update_invoice_report($udata, $lab_invoice_request[0]->id);

		$sql = "select * from lab_request where id=" . $req_id;
		$query = $this->db->query($sql);
		$data['report_format'] = $query->result();

		$sql = "select * from radiology_ultrasound_template where charge_id=" . $invoice_item[0]->item_id;
		$sql = "select * from radiology_ultrasound_template order by Template_Name";
		$query = $this->db->query($sql);
		$data['radiology_ultrasound_template'] = $query->result();


		$this->load->view('PathLab_Report/lab_final_report_show_xray', $data);

		//redirect('Lab_Admin/show_report_final/'.$req_id);
	}

	public function show_report_final($req_id)
	{
		$sql = "select * from lab_request where id=" . $req_id;
		$query = $this->db->query($sql);
		$data['report_format'] = $query->result();

		$sql = "select * from invoice_item where id=" . $data['report_format'][0]->charge_item_id;
		$query = $this->db->query($sql);
		$data['invoice_item'] = $query->result();

		$sql = "select * from radiology_ultrasound_template where charge_id=" . $data['invoice_item'][0]->item_id;
		$sql = "select * from radiology_ultrasound_template order by Template_Name";
		$query = $this->db->query($sql);
		$data['radiology_ultrasound_template'] = $query->result();

		$this->load->view('PathLab_Report/lab_final_report_show_xray', $data);
	}

	public function get_template_xray($req_id)
	{
		$sql = "select * from radiology_ultrasound_template where id=$req_id";
		$query = $this->db->query($sql);
		$radiology_ultrasound_template = $query->result();

		$Findings = "";
		$Impression = "";

		if (count($radiology_ultrasound_template) > 0) {
			$Findings = $radiology_ultrasound_template[0]->Findings;
			$Impression = $radiology_ultrasound_template[0]->Impression;
		}

		$rvar = array(
			'Findings' => $Findings,
			'Impression' => $Impression,
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}


	public function Final_Update_xray($req_id)
	{
		$this->load->model('PathLab_M');

		$udata = array(
			'Report_Data' => $this->input->post('HTMLData'),
			'report_data_Impression' => $this->input->post('report_data_Impression')
		);

		$this->PathLab_M->update_test_request($udata, $req_id);

		echo "Saved";
	}

	public function confirm_report_xray($req_id)
	{
		$this->load->model('PathLab_M');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name . '' . $user->last_name;

		$udata = array(
			'confirm_by' => $user_name,
			'status' => 2
		);

		$this->PathLab_M->update_test_request($udata, $req_id);

		echo "Verified and Ready for Print";
	}



	public function select_lab_invoice_path($inv_id, $lab_type)
	{
		$sql = "select * from invoice_master where id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['inv_master'] = $query->result();

		$pno = $data['inv_master'][0]->attach_id;

		$sql = "select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=" . $pno;
		$query = $this->db->query($sql);
		$data['data'] = $query->result();

		$sql = "select * from lab_invoice_request 
		where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$data['inv_id'] = $inv_id;
		$data['lab_type'] = $lab_type;
		$data['inv_code'] = $data['inv_master'][0]->invoice_code;;

		$this->load->view('Lab_Panel/lab_invoice_main', $data);
	}

	//Other then XRay

	public function select_lab_invoice($inv_id, $lab_type)
	{
		$sql = "select * from invoice_master where id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['inv_master'] = $query->result();

		$pno = $data['inv_master'][0]->attach_id;

		$sql = "select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=" . $pno;
		$query = $this->db->query($sql);
		$data['data'] = $query->result();

		$sql = "select * from lab_invoice_request 
		where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$data['inv_id'] = $inv_id;
		$data['lab_type'] = $lab_type;
		$data['inv_code'] = $data['inv_master'][0]->invoice_code;;

		$this->load->view('Lab_Panel/lab_invoice_xray', $data);
	}

	public function lab_date_show($inv_id, $lab_type)
	{
		$sql = "select * from lab_invoice_request where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$this->load->view('Lab_Panel/lab_date_show', $data);
	}

	public function test_list($inv_id, $lab_type)
	{
		$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			t.item_name,t.item_name,t.id as test_id,check_item_request(m.id,t.id) as check_sample,
			r.lab_repo_id,y.group_desc as item_type_desc,r.`status`,
			r.id as req_id,r.charge_item_id,r.print_combine
			from (( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			left join lab_request r on t.id=r.charge_item_id)
			left join lab_repo l  on t.item_id=l.charge_id
			where y.itype_id=" . $lab_type . " and m.id=" . $inv_id . "
			order by  t.item_name";
		$query = $this->db->query($sql);
		$data['testlist'] = $query->result();

		$sql = "select * from lab_invoice_request where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$this->load->view('Lab_Panel/lab_invoice_main_test_list', $data);
	}

	// Report Test List
	public function xray_test_list($inv_id, $lab_type)
	{
		$sql = "select m.id as inv_id,m.invoice_code,m.inv_date,Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_rname) as inv_name,
			t.item_name,t.item_name,t.id as test_id,check_item_request(m.id,t.id) as check_sample,
			r.lab_repo_id,y.group_desc as item_type_desc,r.`status`,
			r.id as req_id,r.charge_item_id,r.print_combine
			from (( invoice_item t join invoice_master m  join hc_item_type y join patient_master p 
			on m.id=t.inv_master_id  and t.item_type=y.itype_id and p.id=m.attach_id)
			left join lab_request r on t.id=r.charge_item_id)
			left join lab_repo l  on t.item_id=l.charge_id
			where y.itype_id=" . $lab_type . " and m.id=" . $inv_id . "
			order by  t.item_name";
		$query = $this->db->query($sql);
		$data['testlist'] = $query->result();

		$sql = "select * from lab_invoice_request where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$this->load->view('Lab_Panel/lab_invoice_main_xray_test_list', $data);
	}

	public function report_file_list($inv_id, $lab_type, $delete = 0)
	{
		$sql = "select * from file_upload_data where isdelete=" . $delete . " and charge_id='" . $inv_id . "' and charge_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_file_list'] = $query->result();

		$this->load->view('Lab_Panel/lab_report_file_list', $data);
	}

	// Other Then Pathology like Xray , MRI , Ultra sound

	public function select_lab_radiology($inv_id, $lab_type)
	{
		$sql = "select * from invoice_master where id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['inv_master'] = $query->result();

		$pno = $data['inv_master'][0]->attach_id;

		$sql = "select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=" . $pno;
		$query = $this->db->query($sql);
		$data['data'] = $query->result();

		$sql = "select * from lab_invoice_request 
		where invoice_id='" . $inv_id . "' and lab_type='" . $lab_type . "'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request'] = $query->result();

		$data['inv_id'] = $inv_id;
		$data['lab_type'] = $lab_type;
		$data['inv_code'] = $data['inv_master'][0]->invoice_code;;

		$this->load->view('Lab_Panel/lab_invoice_XRay', $data);
	}
}
