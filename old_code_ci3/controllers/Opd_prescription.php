<?php

class Opd_prescription extends MY_Controller{
    function __construct()
    {
        parent::__construct();
		$this->load->model('Prescription_M');
		$this->load->model('Patient_M');
    } 

   // Prescription
	public function create_revist_opd($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,if(apointment_date>date_add(CURDATE(),interval -7 day),0,1) as Exp_date from opd_master_exten where  opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		$pno=$data['opd_master'][0]->p_id;
		$doc_id=$data['opd_master'][0]->doc_id;
		$opd_date=$data['opd_master'][0]->apointment_date;
		$Exp_date=$data['opd_master'][0]->Exp_date;
		
		$opd_status=$data['opd_master'][0]->opd_status;
		
		$sql="select max(queue_no) as max_queue from opd_prescription where date_opd_visit=curdate() and doc_id='".$doc_id."'";
		$query = $this->db->query($sql);
        $max_queue= $query->result();
		
		if(count($max_queue)>0)
		{
			$max_queue_no=$max_queue[0]->max_queue;
		}else{
			$max_queue_no=0;
		}
		
		$max_queue_no=$max_queue_no+1;
		
		$sql="select * from opd_prescription where visit_status=0 and HCODE=".HCODE." and opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_prescription']= $query->result();
	
		$msg_send="";
	
		if(count($data['opd_prescription'])==0 )
		{
			if($Exp_date==0 && $opd_status==2)
			{
				$datainsert = array( 
				'HCODE' => HCODE,
				'opd_id' => $opdid,
				'p_id' => $pno,
				'revisit'=>'1',
				'queue_no'=>$max_queue_no,
				'date_opd_visit'=>date('Y-m-d'),
				'update_by'=>$user_name
				);
				
				$this->Prescription_M->insert_opd_prescription($datainsert);

				$msg_send="OPD Created";
				
			}elseif($opd_status==1){
				$msg_send="OPD Pending";
			}else{
				$msg_send="OPD Expired";
			}

		}else{
			$msg_send="Revisit Already Created :";
		}
		
		echo $msg_send;
	}
	
	public function create_opd_queue($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select * 
		from opd_master 
		where opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$pno=$data['opd_master'][0]->p_id;
		$doc_id=$data['opd_master'][0]->doc_id;
		$opd_date=$data['opd_master'][0]->apointment_date;
		
		$opd_status=$data['opd_master'][0]->opd_status;
		
		$sql="select max(queue_no) as max_queue from opd_prescription 
		where date_opd_visit=curdate() and doc_id='".$doc_id."'";
		$query = $this->db->query($sql);
        $max_queue= $query->result();
      	
		if(count($max_queue)>0)
		{
			$max_queue_no=$max_queue[0]->max_queue;
		}else{
			$max_queue_no=0;
		}
		
		$max_queue_no=$max_queue_no+1;
		
		$sql="select * from opd_prescription where visit_status=0 and opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_prescription']= $query->result();
	
		$msg_send="";
		
		if(count($data['opd_prescription'])==0 )
		{
			if($opd_status==1)
			{
				$datainsert = array( 
				'opd_id' => $opdid,
				'p_id' => $pno,
				'revisit'=>'0',
				'queue_no'=>$max_queue_no,
				'doc_id'=>$doc_id,
				'date_opd_visit'=>date('Y-m-d'),
				'update_by'=>$user_name
				);
				
				$this->Prescription_M->insert_opd_prescription($datainsert);

				$msg_send="OPD in Queue Now";
			}else{
				$msg_send="OPD Already Done, Create Revisit";
			}
			
		}else{
			$msg_send="OPD Already in Queue";
		}
		
		echo $msg_send;
	}
	
	public function show_profile_opd($p_id)
	{
		$data['p_id']=$p_id;

        $sql="select * from patient_master_exten where  id=".$p_id;
        $query = $this->db->query($sql);
		$data['data']= $query->result();
		
		$sql="select o.* from opd_master o where o.p_id=$p_id order by o.opd_id desc";
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select o.* from opd_master o where o.p_id=$p_id and date(o.apointment_date)=curdate()";
        $query = $this->db->query($sql);
        $data['opd_master_current']= $query->result();

		//$this->load->view('Person/person_file_show',$data);

		$this->load->view('dashboard/OPD_old_prescription',$data);

	}

	public function show_old_Prescribed($p_id,$currect_opd)
	{

		$sql="select o.* from opd_master o 
		where o.p_id=$p_id and o.opd_id<>$currect_opd  order by o.opd_id desc";
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select * from opd_prescription where opd_id=$currect_opd";
		$query = $this->db->query($sql);
		$current_opd_master= $query->result();

		$data['doc_id']=$current_opd_master[0]->doc_id;
		$data['opd_id']=$current_opd_master[0]->opd_id;
		$data['opd_session_id']=$current_opd_master[0]->id;
		$data['p_id']=$current_opd_master[0]->p_id;
				
		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_patient_old_medicine',$data);

	}


	public function show_medical_item($p_id)
	{
		$data['p_id']=$p_id;

        $sql="SELECT *
			from invoice_med_master 
			WHERE patient_id=$p_id 
			ORDER BY id desc";
		$query = $this->db->query($sql);
        $data['invoice_med_master']= $query->result();

		$this->load->view('dashboard/opd_medical_bill',$data);

	}

	public function show_profile_investigation($p_id)
	{
		$data['p_id']=$p_id;

        $sql="SELECT i.id,i.inv_date,i.invoice_code,GROUP_CONCAT(t.item_name) as Item_list,i.net_amount,
			i.attach_id,
			date_format(i.inv_date,'%d-%m-%Y') as inv_date_str
			FROM invoice_master i JOIN invoice_item t ON i.id=t.inv_master_id
			WHERE i.attach_id=$p_id
			GROUP BY i.id  ";
        $query = $this->db->query($sql);
		$data['invoice_master']= $query->result();

		//$this->load->view('Person/person_file_show',$data);

		$this->load->view('dashboard/Investigation_history',$data);

	}
	
	public function Prescription($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select * from opd_master where opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		$pno=$data['opd_master'][0]->p_id;
		$doc_id=$data['opd_master'][0]->doc_id;
		
		$sql="select d.id,d.p_code,d.p_fname,d.p_title,d.mphone1,d.mphone2, 
				if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age, 
				d.dob,d.zip,d.email1,d.email2,d.register_date,d.add1,d.add2,d.city,
				d.state,d.country,d.active, group_concat(distinct m.SpecName) as SpecName
				from (doctor_master d left join (doc_spec s 
				join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
			where  d.id=".$doc_id." group by d.id";
		
		$query = $this->db->query($sql);
        $data['doc_master']= $query->result();

		$sql="SELECT m.mor_id,m.morbidities,c.p_id
				FROM morbidities_master m left JOIN patient_morbidities c ON m.mor_id=c.morbidities AND c.p_id=$pno 
				Where m.active=1";
		$query = $this->db->query($sql);
        $data['morbidities']= $query->result();


		$sql="select * from opd_prescription where visit_status=0 and opd_id=".$opdid;
		$query = $this->db->query($sql);
        $data['opd_prescription']= $query->result();
		
		$sql="select *
		from patient_master_exten where    id=".$pno;
		$query = $this->db->query($sql);
        $data['data']= $query->result();

		$sql="select *
		from patient_master where    id=".$pno;
		$query = $this->db->query($sql);
        $data['Pdata']= $query->result();
		
		$sql="select * from invprofiles ";
		$query = $this->db->query($sql);
        $data['invprofiles']= $query->result();

		$sql="select * from investigation where short_name<>'' order by short_name,sort_id ";
		$query = $this->db->query($sql);
        $data['short_investigation']= $query->result();

		$sql="select * from complaints_master where show_in_short=1 order by Name ";
		$query = $this->db->query($sql);
        $data['short_complaint']= $query->result();
		
		$data['doc_id']=$doc_id;
				
		if(count($data['opd_prescription'])==0)
		{
			$datainsert = array( 
					'opd_id' => $opdid,
					'p_id' => $pno,
					'doc_id'=>$doc_id,
					'update_by'=>$user_name
			);
			
			$this->Prescription_M->insert_opd_prescription($datainsert);

			$sql="select * from opd_prescription where session_id=0 and opd_id=".$opdid;
			$query = $this->db->query($sql);
			$data['opd_prescription']= $query->result();
		}

		//old Data from Last OPD
		$sql="Select * from opd_prescription where p_id=$pno and opd_id<$opdid order by opd_id desc limit 1";
		$query = $this->db->query($sql);
		$opd_prescription_old= $query->result();

		$data['old_complaint_list']=array();
		$data['old_diagnosis_list']=array();
		$data['old_Provisional_diagnosis_list']=array();

		if(count($opd_prescription_old)>0)
		{
			$str_complaint=trim(trim($opd_prescription_old[0]->complaints,','));
			if(strlen($str_complaint)>1)
			{
				$data['old_complaint_list']=explode(',',$str_complaint);
			}
			
			$str_diagnosis=trim(trim($opd_prescription_old[0]->diagnosis,','));
			if(strlen($str_diagnosis)>1){
				$data['old_diagnosis_list']=explode(',',$str_diagnosis);
			}

			$str_Provisional_diagnosis=trim(trim($opd_prescription_old[0]->Provisional_diagnosis,','));
			if(strlen($str_Provisional_diagnosis)>1){
				$data['old_Provisional_diagnosis_list']=explode(',',$str_Provisional_diagnosis);
			}
		}
		
		$opd_session_id=$data['opd_prescription'][0]->id;
				
		$data['p_id']=$pno;
		$data['opd_id']=$opdid;
		$data['opd_session_id']=$opd_session_id;

        $this->load->view('dashboard/OPDPrescription',$data);
	}

	public function get_finding_exam()
	{
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select distinct  Finding_Examinations from opd_prescription where Finding_Examinations like '%".$q."%'  ";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Finding_Examinations']));
				$new_row['value']=htmlentities(stripslashes($row['Finding_Examinations']));
				
				//$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function show_profile_image($p_id,$opd_id,$doc_id)
	{
		$data['p_id']=$p_id;

		$sql="select *,IFNULL(GET_AGE_BY_DOB(dob),age) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,
		if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=".$p_id;
		$query = $this->db->query($sql);
        $data['data']= $query->result();

		$data['opd_id']=$opd_id;
		$data['doc_id']=$doc_id;
		
		
		$this->load->view('dashboard/person_profile_photo_from_doctor',$data);
	}

	public function prescribed_dose($opd_id,$opd_session_id)
	{
		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
		where pt.opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
		$data['opd_drug']= $query->result();

		$sql="select * from opd_dose_shed ";
		$query = $this->db->query($sql);
		$data['opd_dose_shed']= $query->result();
		
		$sql="select * from opd_dose_when";
		$query = $this->db->query($sql);
		$data['opd_dose_when']= $query->result();

		$sql="select * from opd_dose_frequency";
		$query = $this->db->query($sql);
		$data['opd_dose_frequency']= $query->result();

		$sql="select * from opd_dose_where";
		$query = $this->db->query($sql);
		$data['opd_dose_where']= $query->result();

		$sql="select * from opd_dose_duration";
		$query = $this->db->query($sql);
		$opd_dose_duration= $query->result();

		$sql="select * from med_formulation";
		$query = $this->db->query($sql);
		$med_formulation= $query->result();

		$sql="SELECT o.id,o.next_visit_desc,DATE_ADD(CURDATE(),INTERVAL o.no_of_days DAY) AS next_date,
		Concat(next_visit_desc,' (', Date_Format(DATE_ADD(CURDATE(),INTERVAL o.no_of_days DAY),'%d-%m-%Y') ,')') as next_visit_day
		FROM opd_nextvisit o order by no_of_days";
		$query = $this->db->query($sql);
		$data['opd_nextvisit']= $query->result();

		$data['opd_dose_duration']='';
		foreach($opd_dose_duration as $row)
		{
			$data['opd_dose_duration'].='"'.$row->duration.'",';
		}

		$data['med_formulation']='';
		foreach($med_formulation as $row)
		{
			$data['med_formulation'].='"'.$row->formulation.'",';
		}


		$sql="select * from opd_dose_remark";
		$query = $this->db->query($sql);
		$opd_dose_remark= $query->result();

		$data['opd_dose_remark']='';
		foreach($opd_dose_remark as $row)
		{
			$data['opd_dose_remark'].='"'.$row->dose_remark.'",';
		}


		$sql="select * from opd_advice";
		$query = $this->db->query($sql);
		$data['opd_advise']= $query->result();
		
		$sql="select * from opd_master where opd_id=$opd_id";
		$query = $this->db->query($sql);
		$data['opd_master']= $query->result();

		$sql="select * 
		from opd_prescription_advice where opd_id=$opd_id";
		$query = $this->db->query($sql);
		$data['opd_patient_advice']= $query->result();

		$data['advice_given']=$this->load->view('dashboard/opd_prescribed_advice',$data,true);

		$sql="select * from opd_prescription where opd_id=$opd_id";
		$query = $this->db->query($sql);
		$data['opd_prescription']= $query->result();

		$data['doc_id']=$data['opd_master'][0]->doc_id;
		$data['opd_id']=$opd_id;
		$data['opd_session_id']=$opd_session_id;
		$data['p_id']=$data['opd_master'][0]->p_id;
		
        $this->load->view('dashboard/opd_prescribed_dose',$data);

	}


	function add_patient_advice()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
			
		$opd_id=$this->input->post('opd_id');
		$opd_pre_id=$this->input->post('opd_session_id');
		
		$advice_id=$this->input->post('advice_id');

		$sql="select * from opd_advice where id=$advice_id";
		$query = $this->db->query($sql);
		$opd_advice= $query->result();

		$opd_advice_text="";
		$opd_advice_text_hindi="";

		if(count($opd_advice)>0){
			$opd_advice_text=$opd_advice[0]->advice;
			$opd_advice_text_hindi=$opd_advice[0]->advice_hindi;
		}

		$data_insert=array(
			'opd_id'=>$opd_id,
			'opd_pre_id'=>$opd_pre_id,
			'advice_id'=>$advice_id,
			'advice_txt'=>$opd_advice_text,
			'advice_txt_hindi'=>$opd_advice_text_hindi,
		);

		
		$this->Prescription_M->insert_opd_patient_advice($data_insert);
		
		$sql="select * 
		from opd_prescription_advice where opd_id=$opd_id";
		$query = $this->db->query($sql);
		$data['opd_patient_advice']= $query->result();

		$this->load->view('dashboard/opd_prescribed_advice',$data);


	}
	
	function del_patient_advice($advice_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$sql="select * from opd_prescription_advice where id=$advice_id";
		$query = $this->db->query($sql);
		$opd_advice= $query->result();

		$opd_id=$opd_advice[0]->opd_id;
	
		
		$this->Prescription_M->remove_opd_patient_advice($advice_id);
		
		$sql="select * 
		from opd_prescription_advice where opd_id=$opd_id";
		$query = $this->db->query($sql);
		$data['opd_patient_advice']= $query->result();

		$this->load->view('dashboard/opd_prescribed_advice',$data);

	}

	public function get_invprofiles($invPCode)
	{
		if($invPCode=='')
		{
			$invPCode="0";
		}else{
			$invPCode = str_replace('-',',',$invPCode);
		}
		
		$sql="select p.Code as p_Code,p.Name,i.Name,i.Code as i_Code
			from (invprofiles p join invtprofiles j on p.Code=j.ProfileCode )
			join investigation i on i.Code=j.InvestigationCode
			where p.Code in (".$invPCode.")
			order by p.Name,j.printOrder";
		$query = $this->db->query($sql);
        $invprofiles= $query->result();

		$content="";
		
		foreach($invprofiles as $row)
		{
			$content.= '<div class="checkbox" >
					  <label>
						<input type="checkbox" name="Advised_'.$row->i_Code.'" value="'.$row->i_Code.'" class="chkadvise"> '.$row->Name.'
					  </label>
				</div>';
		}
		
		$content.='<script>$(document).ready(function(){
					$(".chkadvise").on("click",function() {
						getInvestigationAdvised();
					});
				});
				</script>';
		
		echo $content;
	}
	
	public function add_AdviseInvest($invACode,$opdSession)
	{
		$rangeArray = explode("-",$invACode);

		foreach($rangeArray as $value)
		{
			$sql="select * from opd_prescription_investigation 
			where opd_pre_id=".$opdSession." and investigation_code=".$value." order by investigation_name";
			$query = $this->db->query($sql);
			$invChk= $query->result();
			
			if(count($invChk)==0)
			{
				$sql="select * 
					from investigation
					where Code='".$value."' ";
				$query = $this->db->query($sql);
				$invprofiles= $query->result();
				
				$investigation_name="";
				if(count($invprofiles)>0)
				{
					$investigation_name=$invprofiles[0]->Name;
				}
				
				$datainsert = array( 
					'opd_pre_id' => $opdSession,
					'investigation_code' => $value,
					'investigation_name' => $investigation_name
				);

				
				$this->Prescription_M->insert_p_investigation($datainsert);
			}
		}
	
		$sql="select * from opd_prescription_investigation 
			where opd_pre_id=".$opdSession." order by investigation_name";
		$query = $this->db->query($sql);
        $invOPDprofiles= $query->result();

		$content="<table width='100%'>";
		
		foreach($invOPDprofiles as $row)
		{
			$content.= '<tr>
							<td>'.$row->investigation_name.'</td>
							<td> </td>
							<td><a href="javascript:RemoveTest(\''.$row->investigation_code.'\',\''.$opdSession.'\')">Remove</a></td>
						</tr>';
		}
		$content.="</table>";
		echo $content;
	}
	
	public function Remove_AdviseInvest($invACode,$opdSession)
	{
		$sql="select * from opd_prescription_investigation 
			where opd_pre_id=".$opdSession." and investigation_code=".$invACode." ";
		
		$query = $this->db->query($sql);
		$invChk= $query->result();
			
		
		
		if(count($invChk)>0)
		{
			$this->Prescription_M->remove_p_investigation($invChk[0]->id);
		}
	
		$sql="select * from opd_prescription_investigation 
			where opd_pre_id=".$opdSession." order by investigation_name";
		$query = $this->db->query($sql);
        $invOPDprofiles= $query->result();

		$content="<table width='100%'>";
		
		foreach($invOPDprofiles as $row)
		{
			$content.= '<tr>
							<td>'.$row->investigation_name.'</td>
							<td> </td>
							<td><a href="javascript:RemoveTest(\''.$row->investigation_code.'\',\''.$opdSession.'\')">Remove</a></td>
						</tr>';
		}
		$content.="</table>";
		echo $content;
	}
	
	public function get_complaints(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from complaints_master where Name like '%".$q."%'  ";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Name']));
				$new_row['value']=htmlentities(stripslashes($row['Name']));
				
				$new_row['complaints_no']=htmlentities(stripslashes($row['Code']));
				
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	
	public function get_surgery(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from surgery where Name like '%".$q."%'  ";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Name']));
				$new_row['value']=htmlentities(stripslashes($row['Name']));
				
				$new_row['surgery_no']=htmlentities(stripslashes($row['Code']));
				
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	public function get_disease(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from disease_master where  Name like '%".$q."%'  order by Name";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Name']));
				$new_row['value']=htmlentities(stripslashes($row['Name']));
				
				$new_row['complaints_no']=htmlentities(stripslashes($row['Code']));
				
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	public function get_investigation(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from investigation where  Name like '%".$q."%' or short_name like '%".$q."%'  order by Name";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Name']));
				$new_row['value']=htmlentities(stripslashes($row['Name']));
				
				$new_row['investigation_no']=htmlentities(stripslashes($row['Code']));
				
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function vital_data($opd_session_id)
	{
		$sql="select * from opd_prescription where  id=".$opd_session_id;
		$query = $this->db->query($sql);
        $opd_prescription= $query->result();

		$content="<p class='text-primary'>";

		if($opd_prescription[0]->bp<>'')
		{
			$content.="<span class='text-success'>BP :</span>".$opd_prescription[0]->bp." / ".$opd_prescription[0]->diastolic." <br/>";
		}

		if($opd_prescription[0]->pulse<>'')
		{
			$content.="<span class='text-success'>Pulse :</span>".$opd_prescription[0]->pulse." <br/>";
		}

		if($opd_prescription[0]->temp<>'')
		{
			$content.="<span class='text-success'>Temp :</span>".$opd_prescription[0]->temp." C"." <br/>";
		}

		if($opd_prescription[0]->spo2<>'')
		{
			$content.="<span class='text-success'>SpO2 :</span>".$opd_prescription[0]->spo2." %"." <br/>";
		}

		if($opd_prescription[0]->glucose<>'')
		{
			$content.="<span class='text-success'>Blood Sugar :</span>".$opd_prescription[0]->glucose." <br/>";
		}

		$content.="</p>";

		echo $content;

	}
 
	public function get_remark($p_id)
	{
		$sql="select * from patient_remark where  p_id=".$p_id." Order by id desc";
		$query = $this->db->query($sql);
        $patient_remark= $query->result();

		$content="";

		foreach($patient_remark as $row)
		{
			$content.="<p>".nl2br($row->remark)."<br/>";
			$content.="Update By:".$row->insert_by."<br/>";
		}

		$data['content']= $content;


	}

	public function patient_remark($p_id)
	{
		$sql="select *,Date_Format(insert_datetime,'%d/%m/%Y %H:%i') as ins_time from patient_remark where  p_id=".$p_id." Order by id desc";
		$query = $this->db->query($sql);
        $data['patient_remark']= $query->result();

		$sql="select * from tag_master  Order by tag_name";
		$query = $this->db->query($sql);
        $data['tag_master']= $query->result();

		$sql="SELECT a.*,t.tag_name,t.tag_type_id
				FROM patient_tag_assign a JOIN tag_master t ON a.tag_id=t.id
				WHERE isdelete=0 and a.p_id=$p_id";
		$query = $this->db->query($sql);
		$data['patient_tag_list']= $query->result();

		$data['p_id']=$p_id;

		$this->load->view('dashboard/patient_remark',$data);
	}
	
	

	public function get_medical(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from opd_med_master 
				where  item_name like '%".$q."%'  order by item_name";

			$sql=$sql." limit 10";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['item_name'])).' ['.htmlentities(stripslashes($row['formulation'])).']';
				$new_row['value']=htmlentities(stripslashes($row['item_name']));
				$new_row['med_id']=htmlentities(stripslashes($row['id']));
				$new_row['item_name']=htmlentities(stripslashes($row['item_name']));
				$new_row['formulation']=htmlentities(stripslashes($row['formulation']));
				$new_row['genericname']=htmlentities(stripslashes($row['genericname']));

				$new_row['dosage']=htmlentities(stripslashes($row['dosage']));
				$new_row['dosage_when']=htmlentities(stripslashes($row['dosage_when']));
				$new_row['dosage_freq']=htmlentities(stripslashes($row['dosage_freq']));
				$new_row['no_of_days']=htmlentities(stripslashes($row['no_of_days']));
				$new_row['qty']=htmlentities(stripslashes($row['qty']));
				$new_row['remark']=htmlentities(stripslashes($row['remark']));

				$row_set[] = $new_row; //build an array
			  }
			}else{
				$row_set[] ='';
			}

			echo json_encode($row_set); //format the array into json data
		}
	}

	// Enhanced medicine autocomplete API
	public function get_medicine_autocomplete()
	{
		$this->load->helper('url');
		
		// Get search parameters
		$query = $this->input->get('query') ?: $this->input->get('term');
		$category = $this->input->get('category') ?: 'medicine_name';
		$limit = (int)($this->input->get('limit') ?: 10);
		
		if (empty($query) || strlen($query) < 1) {
			echo json_encode(['success' => false, 'data' => []]);
			return;
		}

		$user = $this->ion_auth->user()->row();
		$doc_id = $user->id;
		
		$response_data = [];
		
		switch($category) {
			case 'medicine_name':
				$sql = "SELECT DISTINCT item_name as text, 'global' as scope, id as med_id, formulation, genericname 
						FROM opd_med_master 
						WHERE item_name LIKE ? 
						ORDER BY item_name 
						LIMIT ?";
				$result = $this->db->query($sql, ['%' . $query . '%', $limit]);
				
				foreach($result->result() as $row) {
					$response_data[] = [
						'text' => $row->text,
						'scope' => $row->scope,
						'med_id' => $row->med_id,
						'formulation' => $row->formulation,
						'genericname' => $row->genericname
					];
				}
				break;
				
			case 'dosage':
				$dosage_patterns = [
					'1-0-1', '1-1-1', '0-1-0', '1-0-0', '0-0-1', '2-1-1', '1-1-2',
					'1/2-0-1/2', '1/2-1/2-1/2', '2-0-2', '1-2-1', '3-2-1', '1-1-1-1',
					'2-2-2', '1/4-1/4-1/4', '3/4-3/4-3/4', 'As required', 'SOS'
				];
				foreach($dosage_patterns as $pattern) {
					if (stripos($pattern, $query) !== false) {
						$response_data[] = ['text' => $pattern, 'scope' => 'global'];
					}
				}
				break;
				
			case 'dosage_when':
				$when_options = [
					'Before meal', 'After meal', 'With meal', 'Empty stomach', 
					'At bedtime', 'Morning', 'Evening', 'As directed'
				];
				foreach($when_options as $option) {
					if (stripos($option, $query) !== false) {
						$response_data[] = ['text' => $option, 'scope' => 'global'];
					}
				}
				break;
				
			case 'dosage_frequency':
				$frequency_options = [
					'Daily', 'Twice daily', 'Thrice daily', 'Four times daily',
					'Once weekly', 'Twice weekly', 'Every other day', 'As needed',
					'Every 4 hours', 'Every 6 hours', 'Every 8 hours', 'Every 12 hours'
				];
				foreach($frequency_options as $option) {
					if (stripos($option, $query) !== false) {
						$response_data[] = ['text' => $option, 'scope' => 'global'];
					}
				}
				break;
				
			case 'dosage_duration':
				$duration_options = [
					'3 days', '5 days', '7 days', '10 days', '14 days', '21 days', '30 days',
					'1 week', '2 weeks', '3 weeks', '1 month', '2 months', '3 months',
					'Till symptoms subside', 'Continue as advised'
				];
				foreach($duration_options as $option) {
					if (stripos($option, $query) !== false) {
						$response_data[] = ['text' => $option, 'scope' => 'global'];
					}
				}
				break;
		}
		
		echo json_encode([
			'success' => true, 
			'data' => array_slice($response_data, 0, $limit)
		]);
	}
 
	public function opd_prescription_save()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_update_info = $user->first_name.''. $user->last_name.'['.$user->id.']'.date('d-m-Y H:i:s');

		$opd_session_id=$this->input->post('opd_session_id');
		$opd_id=$this->input->post('opd_id');
		$input_Examinations=$this->input->post('input_Examinations');
		$input_Prescriber=$this->input->post('input_Prescriber');

		$p_id=$this->input->post('p_id');

		$input_complaints=trim($this->input->post('input_complaints'));
		$input_Investigation=trim($this->input->post('input_Investigation'));
		$input_diagnosis=trim($this->input->post('input_diagnosis'));
		$input_Provisional_diagnosis=trim($this->input->post('input_Provisional_diagnosis'));
				
		$input_BP=$this->input->post('input_BP');
		$input_Diastolic=$this->input->post('input_Diastolic');
		$input_Height=$this->input->post('input_Height');
		$input_Weight=$this->input->post('input_Weight');
		$input_Waist=$this->input->post('input_Waist');
		$input_RR=$this->input->post('input_RR');
		
		$input_Tempature=$this->input->post('input_Tempature');
		$input_Pulse=$this->input->post('input_Pulse');

		$input_SPO2=$this->input->post('input_SPO2');
		$input_glucose=$this->input->post('input_glucose');
		
		$opd_prescription_paediatric_chk=$this->input->post('opd_prescription_paediatric_chk');
		$opd_prescription_pregnancy_chk=$this->input->post('opd_prescription_pregnancy_chk');
		$opd_prescription_lactation_chk=$this->input->post('opd_prescription_lactation_chk');
		$opd_prescription_liver_insufficiency_chk=$this->input->post('opd_prescription_liver_insufficiency_chk');
		$opd_prescription_renal_insufficiency_chk=$this->input->post('opd_prescription_renal_insufficiency_chk');
		$opd_prescription_pulmonary_insufficiency_chk=$this->input->post('opd_prescription_pulmonary_insufficiency_chk');
		$opd_prescription_corona_suspected_chk=$this->input->post('opd_prescription_corona_suspected_chk');
		$opd_prescription_dengue_chk=$this->input->post('opd_prescription_dengue_chk');

		$opd_prescription_smoking_chk=$this->input->post('opd_prescription_smoking_chk');
		$opd_prescription_alcohol_chk=$this->input->post('opd_prescription_alcohol_chk');
		$opd_prescription_drug_chk=$this->input->post('opd_prescription_drug_chk');


		$input_pallor=$this->input->post('input_pallor');
		$input_Icterus=$this->input->post('input_Icterus');
		$input_cyanosis=$this->input->post('input_cyanosis');
		$input_clubbing=$this->input->post('input_clubbing');
		$input_edema=$this->input->post('input_edema');

		$pain_value=$this->input->post('pain_value');

		$morbidities =  $this->input->post('morbidities_list');

		$morbidities_list = explode("-", $morbidities);

		$sql="select * from opd_prescription where opd_id=".$opd_id." and id=".$opd_session_id;
		$query = $this->db->query($sql);
        $dataRec= $query->result();
		
		$content="Nothing Done";
		
		if(count($dataRec)>0)
		{
			$dataupdate = array( 
			'Finding_Examinations' => strtoupper($input_Examinations),
			'Prescriber_Remarks' => strtoupper($input_Prescriber),
			'bp' => $input_BP,
			'diastolic' => $input_Diastolic,
			'pulse' => $input_Pulse,
			'height' => $input_Height,
			'weight' => $input_Weight,
			'waist' => $input_Waist,
			'temp' => $input_Tempature,
			'rr_min' => $input_RR,
			'spo2' => $input_SPO2,
			'glucose' => $input_glucose,
			'update_by' => $user_update_info,

			'complaints' => strtoupper($input_complaints),
			'Investigation' => strtoupper($input_Investigation),
			'diagnosis' => strtoupper($input_diagnosis),
			'Provisional_diagnosis' => strtoupper($input_Provisional_diagnosis),
			
			'paediatric' => $opd_prescription_paediatric_chk,
			'pregnancy' => $opd_prescription_pregnancy_chk,
			'lactation' => $opd_prescription_lactation_chk,
			'liver_insufficiency' => $opd_prescription_liver_insufficiency_chk,
			'renal_insufficiency' => $opd_prescription_renal_insufficiency_chk,
			'pulmonary_insufficiency' => $opd_prescription_pulmonary_insufficiency_chk,
			'corona_suspected'=> $opd_prescription_corona_suspected_chk,
			'dengue'=> $opd_prescription_dengue_chk,
			
			'pallor' => strtoupper($input_pallor),
			'Icterus' => strtoupper($input_Icterus),
			'cyanosis' => strtoupper($input_cyanosis),
			'clubbing' => strtoupper($input_clubbing),
			'edema' => strtoupper($input_edema),

			'pain_value' => $pain_value,

			);

			$dataPatientMaster=array(
				'is_smoking' => $opd_prescription_smoking_chk,
				'is_alcohol'=> $opd_prescription_alcohol_chk,
				'is_drug_abuse'=> $opd_prescription_drug_chk,
			);
			
			$this->Prescription_M->update_opd_prescription($dataupdate,$opd_session_id);

			$this->Patient_M->update($dataPatientMaster,$p_id);

			$sql="SELECT m.mor_id,m.morbidities
				FROM morbidities_master m Where m.active=1";
			$query = $this->db->query($sql);
        	$morbidities_data= $query->result();

			
			foreach($morbidities_data as $row){
				if(in_array($row->mor_id,$morbidities_list)){
					$this->Prescription_M->update_patient_morbidities($p_id,$row->mor_id,0);
				}else{
					$this->Prescription_M->update_patient_morbidities($p_id,$row->mor_id,1);
				}
			}
			
			$content="Update Done";
		}
		
		echo $content;
	}
 
	public function save_prescribed_extra()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
			
		$opd_session_id=$this->input->post('opd_session_id');
		
		$advice=$this->input->post('input_advice');
		$next_visit=$this->input->post('input_next_visit');
		$refer_to=$this->input->post('input_refer_to');

		$sql="select * from opd_prescription where id=".$opd_session_id;
		$query = $this->db->query($sql);
        $dataRec= $query->result();
		
		$content="Nothing Done";
		
		if(count($dataRec)>0)
		{
			$dataupdate = array( 
				'advice' => $advice,
				'next_visit' => $next_visit,
				'refer_to'=> $refer_to,
			);

			
			$this->Prescription_M->update_opd_prescription($dataupdate,$opd_session_id);
			
			$content="Update Done";
		}
		
		echo $content;
	}
 
	
 
	
	
 
	//Medical
 
	public function add_medical()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$input_med_name=strtoupper($this->input->post('input_med_name'));
		$input_med_type=$this->input->post('input_med_type');
		$input_dosage=$this->input->post('input_dosage');
		$input_dosage_when=$this->input->post('input_dosage_when');
		$input_dosage_freq=$this->input->post('input_dosage_freq');
		$input_dose_where=$this->input->post('input_dose_where');
		$input_no_of_days=$this->input->post('input_no_of_days');
		$input_qty=$this->input->post('input_qty');
		$input_remark=strtoupper($this->input->post('input_remark'));
		$hid_med_id=$this->input->post('hid_med_id');
		$opd_session_id=$this->input->post('opd_session_id');
		$opd_med_item_id=$this->input->post('opd_med_item_id');
		
		$insert_master = array( 
			'item_name' => $input_med_name,
			'formulation' => $input_med_type,
		);

		if($hid_med_id==0)
		{
			$sql="Select * from opd_med_master Where Trim(item_name)='$input_med_name'";
			$query = $this->db->query($sql);
			$opd_med_master= $query->result();

			if(count($opd_med_master)>0){
				$hid_med_id=$opd_med_master[0]->id;
			}else{
				$hid_med_id=$this->Prescription_M->insert_med_master($insert_master);
			}
		}
		
		$datainsert = array( 
			'opd_pre_id' => $opd_session_id,
			'med_name' => $input_med_name,
			'med_id' => $hid_med_id,
			'med_type' => $input_med_type,
			'dosage' => $input_dosage,
			'dosage_when' => $input_dosage_when,
			'dosage_freq' => $input_dosage_freq,
			'dosage_where' => $input_dose_where,
			'qty' => $input_qty,
			'no_of_days' => $input_no_of_days,
			'remark' => $input_remark,
			'update_by' => $user_name
		);
		
		$update_master = array( 
			'formulation' => $input_med_type,
			'dosage' => $input_dosage,
			'dosage_when' => $input_dosage_when,
			'dosage_freq' => $input_dosage_freq,
			'dosage_where' => $input_dose_where,
			'qty' => $input_qty,
			'no_of_days' => $input_no_of_days,
			'remark' => $input_remark,
		);

		$this->Prescription_M->update_med_master($update_master,$hid_med_id);
		
		if($opd_med_item_id>0)
		{
			$this->Prescription_M->update_p_medical($datainsert,$opd_med_item_id);
		}else{
			$this->Prescription_M->insert_p_medical($datainsert);
		}

		$content="";
		
		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
        $data['opd_drug']= $query->result();

		$content=$this->load->view('dashboard/opd_prescription_med_list',$data,TRUE);
	
		echo $content;

	}

	public function medical_Select()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
				
		$opd_session_id=$this->input->post('opd_session_id');
		$rec_id=$this->input->post('med_prec_id');
		
		$sql="select * from opd_prescrption_prescribed 
		where id=".$rec_id." and opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
        $dataRec= $query->result();
		
		$rvar=array(
			"id" => $dataRec[0]->id,
			"opd_session_id" => $opd_session_id,
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

	public function medical_prescribed_Remove($p_med_id,$opd_session_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		

		$sql="select * from opd_prescrption_prescribed 
		where id=$p_med_id";
		$query = $this->db->query($sql);
        $dataRec= $query->result();
			
		if(count($dataRec)>0)
		{
			$this->load->model('Doctor_M');
			$this->Prescription_M->remove_p_medical($p_med_id);
		}
		
		$content="";
		
		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
		$data['opd_drug']= $query->result();

		$content=$this->load->view('dashboard/opd_prescription_med_list',$data,TRUE);
	
		echo $content;
	}

	public function update_medical()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$medical_remarks=$this->input->post('medical_remarks');
		$opd_session_id=$this->input->post('opd_session_id');
		$rec_id=$this->input->post('invACode');
		
		$sql="select * from opd_prescrption_prescribed 
			where id=".$rec_id." and opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
        $dataRec= $query->result();
		
		$content="Nothing Done";
		
		if(count($dataRec)>0)
		{
			$dataupdate = array( 
			'remark' => $medical_remarks,
			'update_by' => $user_name
			);

			
			$this->Prescription_M->update_p_medical($dataupdate,$rec_id);
			
			$content="Update Done";
		}
		
		echo $content;
	}

	
	public function remove_medical($p_med_id,$rx_group_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql="select * from opd_prescrption_prescribed_template 
		where id=$p_med_id";
		$query = $this->db->query($sql);
        $dataRec= $query->result();
			
		if(count($dataRec)>0)
		{
			$this->load->model('Doctor_M');
			$this->Doctor_M->remove_p_medical($p_med_id);
		}
		
		$sql="select * from opd_prescription_template 
			Where  id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['opd_prescription_template']= $query->result();

		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed_template pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
		Where  rx_group_id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['opd_prescrption_prescribed_template']= $query->result();

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_medicine_list',$data);
	}

	
	public function opd_prescription_print($opdid,$opd_session_id,$print_type=0)
	{
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();
		
		$pno=$opd_master[0]->p_id;

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select d.* ,group_concat(m.SpecName) as SpecName 
			FROM (doctor_master d left JOIN   doc_spec s on d.id =s.doc_id)
			join med_spec m  on s.med_spec_id =m.id 
			WHERE d.id=$doc_id ";
        	$query = $this->db->query($sql);
			$doctor_master= $query->result();
			
			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}

		$sql="SELECT o.*,DATE_FORMAT(o.apointment_date,'%d-%m-%Y') as str_apointment_date,

		date_format(date_add(o.apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date,
		date_format(opd_book_date,'%d-%m-%Y %H:%i') as  str_opd_book_date ,
		(case o.payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str ,
		d.doc_sign,DATE_FORMAT(o.last_opdvisit_date,'%d-%m-%Y') as str_last_opdvisit_date,
		d.opd_print_format
		from  opd_master o JOIN doctor_master d ON o.doc_id=d.id  where opd_id=".$opdid;
        $query = $this->db->query($sql);
		$opd_master= $query->result();

		$data['opd_master']=$opd_master;
		
		$sql="select * from opd_prescription where  opd_id=".$opdid." and id=".$opd_session_id;
		$query = $this->db->query($sql);
        $opd_prescription= $query->result();

		$sql="select * from opd_prescription_advice where  opd_id=".$opdid." and opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
        $opd_prescription_advice= $query->result();
		
		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS str_age,
		date_format(last_visit,'%d-%m-%Y  %H:%i') as last_visit_str
		from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
		$pdata= $query->result();

		$sql="Select count(*) as rec_opd from opd_master where p_id=$pno";
		$query = $this->db->query($sql);
		$no_opd= $query->result();

		$total_no_visit=$no_opd[0]->rec_opd;

		$data['patient_master']=$pdata;
		$patient_master=$pdata;

		$total_no_visit=$no_opd[0]->rec_opd+$patient_master[0]->no_of_visit;

		$sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date 
		from opd_master where p_id=".$opd_master[0]->p_id." and doc_id=".$opd_master[0]->doc_id." 
		and  apointment_date >= date_add(sysdate(),interval -$no_opd_days day) and opd_fee_type<>3";
		$query = $this->db->query($sql);
		$old_opd= $query->result();

		$data['old_opd']=$old_opd;

		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();

		$data['insurance']=$insurance;
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();

			$data['case_master']=$case_master;
		}

		$old_uhid='';
		if($patient_master[0]->udai<>"")
		{
			$old_uhid= ' /'.$patient_master[0]->udai;
		}
		$data['old_uhid']=$old_uhid;
		
		$exp_date="";

		if($opd_master[0]->opd_fee_type=='3'){
                if(count($old_opd)>0){
					$exp_date= 'OPD Start Date : '.$old_opd[0]->s_opd_date.'<br>Valid Upto :'.$old_opd[0]->opd_Exp_Date.'<br>';
                }
		}else{
			$exp_date="<b>Valid Upto : </b>".$opd_master[0]->opd_Exp_Date ."";
		}

		$last_opdvisit_date="";

		if($opd_master[0]->last_opdvisit_date=='')
		{
			if($patient_master[0]->last_visit=='')
			{
				$last_opdvisit_date="";
			}else{
				$last_opdvisit_date='<br>Last Visit :'.$patient_master[0]->last_visit_str;
			}
			
			
		}else{
			$last_opdvisit_date='<br>Last Visit :'.$opd_master[0]->str_last_opdvisit_date;
		}

		$sql="SELECT group_concat(m.short_name) AS m_name
		FROM morbidities_master m  JOIN patient_morbidities c ON m.mor_id=c.morbidities AND c.p_id=".$pno;
		$query = $this->db->query($sql);
		$patient_morbidities= $query->result();

		$data['morbidities']="";

		if($patient_morbidities[0]->m_name<>'')
		{
			$data['morbidities']=$patient_morbidities[0]->m_name;
		}

		$data['Complication']='';
		if($opd_prescription[0]->pregnancy==1)
		{
			$data['Complication'].='Pregnancy,';
		}

		if($opd_prescription[0]->lactation==1)
		{
			$data['Complication'].='Lactation,';
		}

		if($opd_prescription[0]->liver_insufficiency==1)
		{
			$data['Complication'].='Liver insufficiency,';
		}

		if($opd_prescription[0]->renal_insufficiency==1)
		{
			$data['Complication'].='Renal Insufficiency,';
		}

		if($opd_prescription[0]->pulmonary_insufficiency==1)
		{
			$data['Complication'].='Pulmonary Insufficiency,';
		}

		if($opd_prescription[0]->corona_suspected==1)
		{
			$data['Complication'].='Corona Suspected,';
		}

		if($opd_prescription[0]->dengue==1)
		{
			$data['Complication'].='Dengue,';
		}

		$data['Addiction']='';

		if($patient_master[0]->is_smoking==1)
		{
			$data['Addiction'].='Smoking,';
		}

		if($patient_master[0]->is_alcohol==1)
		{
			$data['Addiction'].='Alcoholic,';
		}

		if($patient_master[0]->is_drug_abuse==1)
		{
			$data['Addiction'].='Drug Abuse,';
		}


		
		$data['exp_date']=$exp_date;
		$data['last_opdvisit_date']=$last_opdvisit_date;

		$data['doc_name']='Dr. '.$opd_master[0]->doc_name;
		$data['doc_sign']=nl2br($opd_master[0]->doc_sign);
		$data['SpecName']=$doctor_master[0]->SpecName;


		$data['pName']=$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname) ;
		$data['pRelative']=$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname) ;
		$data['age_sex']=$patient_master[0]->str_age.' / '.$patient_master[0]->xgender;
		$data['opd_no']=$opd_master[0]->opd_code;
		$data['opd_date']=$opd_master[0]->str_apointment_date;
		
		$data['opd_fee']= 'Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc ;
		$data['opd_fee_desc']= $opd_master[0]->opd_fee_desc ;
		
		$data['opd_sr_no']=$opd_master[0]->opd_no;

		$data['str_opd_book_date']=$opd_master[0]->str_opd_book_date;


		$data['short_info']='Sr No.: <b>'.$opd_master[0]->opd_no .'</b> 
							<br/>OPD Fee : Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc .' <br>
							UHID : '.$patient_master[0]->p_code.'<br/>
							P Add. :'.$patient_master[0]->add1.','.$patient_master[0]->city.'';

		$data['short_info_1']='Sr No.: <b>'.$opd_master[0]->opd_no .'</b> 
		/ OPD Fee : Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc .'  / 
		UHID : '.$patient_master[0]->p_code.' / <b>No. of Visit</b> : '.$total_no_visit.' <br/>'.$last_opdvisit_date;
		
		$data['uhid_no']=$patient_master[0]->p_code;
		$data['phoneno']=$patient_master[0]->mphone1;
		$data['p_address']=$patient_master[0]->add1.','.$patient_master[0]->city.'';
		$data['total_no_visit']=$total_no_visit;
		$data['exp_date']=$exp_date;

		$data['opd_book_date']=$opd_master[0]->opd_book_date;
		$sql="SELECT pt.* ,d.dose_show_sign AS dose_shed,d.dose_show_desc AS dose_shed_hindi,
		dw.dose_sign_desc AS dose_when,dw.dose_sign_hindi AS dose_when_hindi,
		df.dose_sign_desc AS dose_frequency,df.dose_sign_hindi AS dose_frequency_hindi,
		d_on.dose_sign_desc AS dose_where,d_on.dose_sign_hindi AS dose_where_hindi,
		m.genericname
		FROM ((((opd_prescrption_prescribed pt
		LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
		LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
		LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
		LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id)
		LEFT JOIN  opd_med_master m ON pt.med_id =m.id
		where opd_pre_id=".$opd_session_id;
		$query = $this->db->query($sql);
        $opd_p_prescribed= $query->result();

		$top_content="";
		$barContent="";
		
		$top_content.="<p style='font-family:verdana;font-size:100%;'>OPD No.:<b>".$opd_master[0]->opd_code."</b> / Date : <b>".$opd_master[0]->str_apointment_date."</b></p>";
		$top_content.="<p style='font-family:verdana;font-size:100%;'>Patient ID :<b>".$pdata[0]->p_code."</b> <br/>
						Patient  Name : <b>".$pdata[0]->p_fname."</b> <br/>
						Gender: <b>".$pdata[0]->xgender."</b> <br/>
						Age:<b>".$pdata[0]->str_age."</b></p>";

		$barContent.="OPD No:".$opd_master[0]->opd_code. PHP_EOL ."OD:".$opd_master[0]->str_apointment_date. PHP_EOL ."Doc.:".$opd_master[0]->doc_name."[".$opd_master[0]->doc_spec."]". PHP_EOL ;
		$barContent.="PID:".$pdata[0]->p_code. PHP_EOL ."PN:".$pdata[0]->p_fname."/".$pdata[0]->xgender."/A:".$pdata[0]->str_age. PHP_EOL;

		$vital_content="";

		if($opd_prescription[0]->pulse<>'')
		{
			$vital_content.=" <b>Pulse : </b>".$opd_prescription[0]->pulse." bpm";
		}

		if($opd_prescription[0]->diastolic<>'')
		{
			
			if($opd_prescription[0]->bp<>'')
			{
				$vital_content.=" <b>BP :</b> ".$opd_prescription[0]->bp;
			}

			$vital_content.="/".$opd_prescription[0]->diastolic;

			$vital_content.=" mmHg";
		}
	
		if($opd_prescription[0]->spo2<>'')
		{
			$vital_content.=" / <b>SpO2: </b>".$opd_prescription[0]->spo2." %";
		}
		
		if($opd_prescription[0]->temp<>'')
		{
			$vital_content.=" / <b>Temp: </b>".$opd_prescription[0]->temp." F";
		}
			

		if($opd_prescription[0]->weight<>'')
		{
			$vital_content.=" / <b>Weight : </b>".$opd_prescription[0]->weight." KG";
		}

		if($opd_prescription[0]->height<>'')
		{
			$vital_content.=" / <b>Height : </b>".$opd_prescription[0]->height." cm";
		}

		$vital_content="<p>".$vital_content."</p>";

		$Complaint="";
		
		if($opd_prescription[0]->complaints!='')
		{
			$Complaint="<b>Complaint : </b>";
			$Complaint.=''.nl2br(trim(trim($opd_prescription[0]->complaints),",")).'<br/>';
		}

		$painscale="";
		
		if($opd_prescription[0]->pain_value>0)
		{
			$painscale="<b>Pain Scale : </b>";
			$pain_value=$opd_prescription[0]->pain_value;
			if($pain_value==1){
				$painscale.='Mild Pain';
			}elseif($pain_value==2){
				$painscale.='Moderate';
			}elseif($pain_value==3){
				$painscale.='Intence';
			}elseif($pain_value==4){
				$painscale.='Worst Pain Possible';
			}
		}


		$data['painscale_img']='<img src="assets/images/pains_scale.png" style="width:300px;height:40px;" />';
		if($opd_prescription[0]->pain_value>=0)
		{
			$data['painscale_img']='<img src="assets/images/pains_scale_'.$opd_prescription[0]->pain_value.'.png" style="width: 300px;height: 40px;" />';
		}

		$Provisional_diagnosis="";

		if($opd_prescription[0]->Provisional_diagnosis!='')
		{
			$Provisional_diagnosis="<b>Provisional Diagnosis : </b>";
			$Provisional_diagnosis.=' '.nl2br(trim(trim($opd_prescription[0]->Provisional_diagnosis),",")).'<br/>';

		}
		
		$diagnosis="";
		
		if($opd_prescription[0]->diagnosis!='')
		{
			$diagnosis="<b>Diagnosis : </b>";
			$diagnosis.=' '.nl2br(trim(trim($opd_prescription[0]->diagnosis),",")).'<br/>';

		}
		
		$investigation="";
		if($opd_prescription[0]->investigation!='')
		{
			$investigation="<b>Investigation Advised : </b>";
			$investigation.=' '.nl2br(trim(trim($opd_prescription[0]->investigation),",")).'<br/>';
		}


		$Finding_Examinations="";

		if($opd_prescription[0]->Finding_Examinations!='')
		{
			$Finding_Examinations="<b>Findings : </b><br/>";
			$Finding_Examinations.=' '.nl2br(trim(trim($opd_prescription[0]->Finding_Examinations),",")).'<br/>';

		}

		$Prescriber_Remarks="";

		if($opd_prescription[0]->Prescriber_Remarks!='')
		{
			$Prescriber_Remarks="<b>Prescriber Diagnosis : </b><br/>";
			$Prescriber_Remarks.=' '.nl2br(trim(trim($opd_prescription[0]->Prescriber_Remarks),",")).'<br/>';

		}


		$advice="";

		if(($opd_prescription[0]->advice!='') || count($opd_prescription_advice)>0)
		{
			$advice="<b>Advice : </b>";
		}
		
		if($opd_prescription[0]->advice!='')
		{
			$advice.=''.nl2br($opd_prescription[0]->advice).'<br/>';
		}

		foreach($opd_prescription_advice as $row){
			$advice.=''.$row->advice_txt_hindi.'<br/>';
		}

		$next_visit="";
		
		if($opd_prescription[0]->next_visit!='')
		{
			$next_visit="<b>Next Visit : </b>";
			$next_visit.=''.$opd_prescription[0]->next_visit.'<br/>';
		}

		$refer_to="";
		
		if($opd_prescription[0]->refer_to!='')
		{
			$refer_to="<b>Refer To : </b>";
			$refer_to.=''.$opd_prescription[0]->refer_to.'<br/>';
		}
				
		$medical='';
		$sr_no=1;
		if(count($opd_p_prescribed)>0) 
		{ 
			$medical="<H2>Rx : </H2>";
			$medical.= '
			<table width="100%" style="padding: left 5px;px;border:0px;" >
				<tr>
					<th >Medicine Name</th>
					<th >Dosage</th>
					<th >Qty</th>
					<th >Day</th>
				</tr>'; 

			foreach($opd_p_prescribed as $row)
			{
				$medical_extend="";
			
				$medical.='<tr>
								<td>'.$sr_no.' - '.$row->med_type.' '.$row->med_name.'</td>
								<td>'.$row->dose_shed.' / '.$row->dose_shed_hindi.'  '.$row->dose_when_hindi.'  '.$row->dose_frequency_hindi.'</td>
								<td>'.$row->qty.'</td>
								<td>'.$row->no_of_days.'</td>
							</tr>';
				$medical.='<tr style="border-bottom: 1px solid black;">
								<td colspan="4">';
				if(strlen($row->genericname)>0 || strlen($row->remark)>0 || strlen($row->dose_shed_hindi)>0)
				{
					if(strlen($row->genericname)>1)
					{
						$medical.='<p style="font-size: 8px;">Composition : <i>'.$row->genericname.'</i></p>';
					}
					
					if(strlen($row->remark)>0)
					{
						$medical.='<p>Notes : '.$row->remark.'</p>';
					}
				}

				$medical.='<hr style="margin: 0px 0px 0px 0px;"></td>
							</tr>';
				
				$sr_no=$sr_no+1;

			}
			$medical.="</table>";
		}
		
		
		$doctor="";
		$doctor.='<p style="text-align:right;">Dr. '.$opd_master[0]->doc_name.' <br/> ['.$opd_master[0]->doc_spec.']</p>';
		
		$data['Complaint']= trim(trim($Complaint),",") ;
		$data['diagnosis']= trim(trim($diagnosis),",");
		$data['Provisional_diagnosis']= trim(trim($Provisional_diagnosis),",");
		$data['investigation']= trim(trim($investigation),",");
		$data['medical']= trim(trim($medical),",");
		$data['doctor']= trim(trim($doctor),",");
		$data['top_content']= trim(trim($top_content),",");
		$data['vital_content']= trim(trim($vital_content),",");

		$data['Finding_Examinations']= trim(trim($Finding_Examinations),",");
		$data['Prescriber_Remarks']= trim(trim($Prescriber_Remarks),",");

		$data['advice']= trim(trim($advice),",");
		$data['next_visit']= trim(trim($next_visit),",");
		$data['refer_to']= trim(trim($refer_to),",");

		$data['painscale']=$painscale;

		
		$this->load->library('M_pdf');
       
        $file_name='Prescription-Rx-'.$opd_master[0]->opd_code."-".date('Ymdhis').".pdf";

        //$filepath=$folder_name.'/'.$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);

		if($print_type==0)
		{
			$print_format=$doctor_master[0]->rx_plain_paper;
			$content=$this->load->view('/dashboard/'.$print_format,$data,TRUE);
		}elseif($print_type==1){
			$print_format=$doctor_master[0]->rx_blank_letter_head;
			$content=$this->load->view('/dashboard/'.$print_format,$data,TRUE);
		}else{
			$print_format=$doctor_master[0]->rx_pre_print_letter_head_format;
			$content=$this->load->view('/dashboard/'.$print_format,$data,TRUE);
		}
        
		// OPD Visit Done
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.':T-'.date('d-m-Y h:m:s');

		$status_str='Visit Done : Auto';
		$opd_status_remark=$status_str.' Update By:'.$user_name;

		$dataupdate = array( 
			'opd_status' => 2,
			'opd_status_remark' => $opd_status_remark
			);

		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$opdid);

		// OPD Visit Done End Here

		$this->m_pdf->pdf->shrink_tables_to_fit = 1;
		//echo $content;
        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($file_name,"I");
				
	}

	public function upload_video()
	{
		$folder_name='uploads/opdvideo/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
		

		if(isset($_FILES["audiovideo"])){
			// Define a name for the file
			$fileName = "myaudiovideo.webm";
		
			// In this case the current directory of the PHP script
			$uploadDirectory = $folder_name.'/'. $fileName;
			
			// Move the file to your server
			if (!move_uploaded_file($_FILES["audiovideo"]["tmp_name"], $uploadDirectory)) {
				echo("Couldn't upload video !");
			}
			else{
				echo("File Moved");
			}
		}
		elseif(isset($_FILES["audio"])){
			// Define a name for the file
			$fileName = "myaudio.webm";
		
			// In this case the current directory of the PHP script
			$uploadDirectory = $folder_name.'/'. $fileName;
			
			// Move the file to your server
			if (!move_uploaded_file($_FILES["audio"]["tmp_name"], $uploadDirectory)) {
				echo("Couldn't upload video !");
			}
			else{
				echo("File Moved");
			}
		}
		elseif(isset($_FILES["video"])){
			// Define a name for the file
			$fileName = "myvideo.webm";
		
			// In this case the current directory of the PHP script
			$uploadDirectory = $folder_name.'/'. $fileName;
			
			// Move the file to your server
			if (!move_uploaded_file($_FILES["video"]["tmp_name"], $uploadDirectory)) {
				echo("Couldn't upload video !");
			}
			else{
				echo("File Moved");
			}
		}
		elseif(isset($_FILES["stream"])){
			// Define a name for the file
			$fileName = $_POST["name"];
			$file_number = $_POST["file_number"];
			$opd_id=$_POST["opd_id"];
			$file_name_id=$_POST["file_name_id"];

			$folder_name=$folder_name.'/'.$opd_id;
			
			if (!file_exists($folder_name)) {
				mkdir($folder_name, 0777, true);
			}
			// In this case the current directory of the PHP script
			$uploadDirectory = $folder_name.'/'. $fileName.'.webm';
			
			// Move the file to your server
			if (!move_uploaded_file($_FILES["stream"]["tmp_name"], $uploadDirectory)) {
				echo("Couldn't upload video !");
			}
			else{
				//update last file
				$last_file_number=$file_number-1;
				$strFile2 = $folder_name.'/'."Main_video_".$file_number.'.webm';
				$strFile1 = $folder_name.'/'."Main_video_".$last_file_number.'.webm';
		
				$final_file=$folder_name.'/'.$file_name_id.'_'.$file_number.'.webm';
		
				$last_final_file=$folder_name.'/'.$file_name_id.'_'.$last_file_number.'.webm';

				if($last_file_number>0)
				{
					$last_final_file=$folder_name.'/'.$file_name_id.'_'.$last_file_number.'.webm';
				}
		
				$chk_file=file_exists($last_final_file);
				if($chk_file==1)
				{
					//First File
					$objFH = fopen( $last_final_file, "rb" );
					$strBuffer1 = fread( $objFH, filesize( $last_final_file) );
					fclose( $objFH );
		
					//Second File
					$objFH = fopen( $strFile2, "rb" );
					$strBuffer2 = fread( $objFH, filesize( $strFile2) );
					fclose( $objFH );
		
					// manipulate buffers here...
					$strBuffer3 = $strBuffer1 . $strBuffer2;
		
					// open for write/binary-safe
					$objFH = fopen( $final_file, "wb" );
					fwrite( $objFH, $strBuffer3 );
					fclose( $objFH );
		
					unlink($last_final_file);
					unlink($strFile2);
					//unlink($strFile1);
				}else{
		
					//Second File
					$objFH = fopen( $strFile2, "rb" );
					$strBuffer2 = fread( $objFH, filesize( $strFile2) );
					fclose($objFH);
		
					// manipulate buffers here...
					$strBuffer3 = $strBuffer2;
		
					// open for write/binary-safe
					$objFH = fopen( $final_file, "wb" );
					fwrite( $objFH, $strBuffer3 );
					fclose( $objFH );
		
					echo 'File Not Found';
					unlink($strFile2);
				}
		
				$data_upload = array( 
					'opd_id' => $opd_id, 
					'full_path' => $final_file,
				); 
                
                $this->load->model('FileOPDRec_M');
                
                $this->FileOPDRec_M->insert($data_upload,$file_name_id); 
                
				echo("Success");


			}
		}
		else{
			echo "No file uploaded";
		}
	}

	//Suggest Panel

	function new_rx_group($doc_id=0)
	{
		$data['doc_id']=$doc_id;
		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_new',$data);
	}

	function rx_group($opd_id,$doc_id)
	{
		$data['opd_id']=$opd_id;
		$data['doc_id']=$doc_id;

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_save',$data);
	}

	function rx_group_select($rx_group_id)
	{
		$opd_session_id = $this->input->post('opd_session_id');
		$rx_group_id=$this->input->post('rx_group_id');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');
		
		

		$sql="select * from opd_prescrption_prescribed_template 
                Where  rx_group_id=$rx_group_id";
		$query = $this->db->query($sql);
		$prescrption_prescribed= $query->result();

            if(count($prescrption_prescribed)>0 && $rx_group_id>0)
            {
				foreach($prescrption_prescribed as $row)
				{
					$prescrption_prescribed_data=array(
						'opd_pre_id' => $opd_session_id,
						'med_id' => $row->med_id,
						'med_name' => $row->med_name,
						'med_type' => $row->med_type,
						'dosage' => $row->dosage,
						'dosage_when' => $row->dosage_when,
						'dosage_freq' => $row->dosage_freq,
						'qty' => $row->qty,
						'no_of_days' => $row->no_of_days,
						'remark' => $row->remark,
						'update_by' => $user_name, 
					);
	
					$this->Prescription_M->insert_p_medical($prescrption_prescribed_data);
					 
				}
            }

			$content="";
		
			$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.opd_pre_id=".$opd_session_id;
			$query = $this->db->query($sql);
			$data['opd_drug']= $query->result();
	
			$content=$this->load->view('dashboard/opd_prescription_med_list',$data,TRUE);
		
			echo $content;
	}

	function add_medicince_opd($opd_pre_prescribed_id,$opd_session_id)
	{
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');
		
		

		$sql="select * from opd_prescrption_prescribed 
                Where  id=$opd_pre_prescribed_id";
		$query = $this->db->query($sql);
		$prescrption_prescribed= $query->result();

		foreach($prescrption_prescribed as $row)
		{
			$prescrption_prescribed_data=array(
				'opd_pre_id' => $opd_session_id,
				'med_id' => $row->med_id,
				'med_name' => $row->med_name,
				'med_type' => $row->med_type,
				'dosage' => $row->dosage,
				'dosage_when' => $row->dosage_when,
				'dosage_freq' => $row->dosage_freq,
				'dosage_where' => $row->dosage_where,
				'qty' => $row->qty,
				'no_of_days' => $row->no_of_days,
				'remark' => $row->remark,
				'update_by' => $user_name, 
			);

			$this->Prescription_M->insert_p_medical($prescrption_prescribed_data);

		}
         
			$content="";
		
			$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.opd_pre_id=".$opd_session_id;
			$query = $this->db->query($sql);
			$data['opd_drug']= $query->result();
	
			$content=$this->load->view('dashboard/opd_prescription_med_list',$data,TRUE);
		
			echo $content;
	}

	function add_medicine_opd_all($current_opd_id,$opd_session_id)
	{
				
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');
		
		

		$sql="select * from opd_prescrption_prescribed 
                Where  opd_pre_id=$opd_session_id";
		$query = $this->db->query($sql);
		$prescrption_prescribed= $query->result();

            if(count($prescrption_prescribed)>0)
            {
				foreach($prescrption_prescribed as $row)
				{
					$prescrption_prescribed_data=array(
						'opd_pre_id' => $current_opd_id,
						'med_id' => $row->med_id,
						'med_name' => $row->med_name,
						'med_type' => $row->med_type,
						'dosage' => $row->dosage,
						'dosage_when' => $row->dosage_when,
						'dosage_freq' => $row->dosage_freq,
						'qty' => $row->qty,
						'no_of_days' => $row->no_of_days,
						'remark' => $row->remark,
						'update_by' => $user_name, 
					);
	
					$this->Prescription_M->insert_p_medical($prescrption_prescribed_data);

				}
            }

			$content="";
		
			$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id
			where pt.opd_pre_id=".$opd_session_id;
			$query = $this->db->query($sql);
			$data['opd_drug']= $query->result();
	
			$content=$this->load->view('dashboard/opd_prescription_med_list',$data,TRUE);
		
			echo $content;
	}


	
	function save_rx_group_list($doc_id=0)
	{
		$sql="SELECT p.*,group_concat(m.med_name SEPARATOR '<br/>' ) AS med_name_list 
		from opd_prescription_template p LEFT JOIN opd_prescrption_prescribed_template m
		ON p.id=m.rx_group_id
		WHERE p.doc_id=$doc_id or p.doc_id=0
		GROUP BY p.id";
		$query = $this->db->query($sql);
		$data['rx_group_list']= $query->result();

		$data['Color']=array(
			'bg-primary text-white',
			'bg-secondary text-white',
			'bg-success text-white',
			'bg-danger text-white',
			'bg-warning text-dark',
			'bg-info text-white',
			'bg-light text-dark',
			'bg-dark text-white',
			'bg-white text-dark',
		);

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_list',$data);
	}
	
	function save_rx_group_show($rx_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select * from opd_prescription_template where  id=$rx_id";
		$query = $this->db->query($sql);
		$rx_master= $query->result();
		
		$sql="select * from opd_prescrption_prescribed_template where  rx_group_id=$rx_id";
		$query = $this->db->query($sql);
		$rx_prescribed= $query->result();

		$this->load->view('dashboard/opd_prescription_suggest_panel/opd_prev',$data);
		
	}


	function update_opd_queue($opd_session_id)
	{
		$sql="select * from opd_prescription where id=$opd_session_id";
		$query = $this->db->query($sql);
		$opd_prescription= $query->result();

		if(count($opd_prescription)>0){
			$doc_id=$opd_prescription[0]->doc_id;
			$date_opd_visit=$opd_prescription[0]->date_opd_visit;

			$this->load->model('Opd_M');
			$this->Opd_M->update_opd_queue_no($opd_session_id,$doc_id,$date_opd_visit);
		}

		echo 'Queue Update';
		
	}

	function rx_group_panel()
	{
		$sql="select * from opd_prescription_template order by rx_group_name";
		$query = $this->db->query($sql);
		$data['rx_master']= $query->result();

		$this->load->view('dashboard/rx_group',$data);
		
	}


	function rx_group_list()
	{
		$sql="select * from opd_prescription_template order by rx_group_name";
		$query = $this->db->query($sql);
		$data['rx_master']= $query->result();

		$this->load->view('dashboard/rx_group_list',$data);
		
	}

	function save_rx_group_edit($rx_id)
	{
		
		$sql="select * from opd_prescription_template where  id=$rx_id";
		$query = $this->db->query($sql);
		$data['rx_master']= $query->result();
		
		$sql="select * from opd_prescrption_prescribed_template where  rx_group_id=$rx_id";
		$query = $this->db->query($sql);
		$data['rx_prescribed']= $query->result();

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_show',$data);

	}



	function save_rx_group_from_opd_prescription()
    {
		$opd_id = $this->input->post('opd_session_id');
		$rx_group_name=$this->input->post('rx_group_name');
		$doc_id=$this->input->post('doc_id');
		
		$this->load->model('Doctor_M');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
        $sql="select * from opd_prescription 
                Where  id=$opd_id";
        $query = $this->db->query($sql);
        $opd_prescription= $query->result();
	   
		echo $sql;

		$rx_group_id=0;

        if(count($opd_prescription)>0)
        {
            $opd_prescription_data=array(
                'rx_group_name' => $rx_group_name,
                'doc_id' => $doc_id,
                'update_by' => $user_name,
                'Finding_Examinations' => $opd_prescription[0]->Finding_Examinations,
                'Prescriber_Remarks' => $opd_prescription[0]->Prescriber_Remarks,
                'complaints' => $opd_prescription[0]->complaints,
                'diagnosis' => $opd_prescription[0]->diagnosis,
                'investigation' => $opd_prescription[0]->investigation, 
			);
			
			$opd_pre_id=$opd_prescription[0]->id;
		
            $rx_group_id=$this->Doctor_M->insert_opd_prescription_template($opd_prescription_data); 

            $sql="select * from opd_prescrption_prescribed 
                Where  opd_pre_id=$opd_pre_id";
            $query = $this->db->query($sql);
            $prescrption_prescribed= $query->result();

            if(count($prescrption_prescribed)>0 && $rx_group_id>0)
            {
				foreach($prescrption_prescribed as $row)
				{
					$prescrption_prescribed_data=array(
						'rx_group_id' => $rx_group_id,
						'med_id' => $row->med_id,
						'med_name' => $row->med_name,
						'med_type' => $row->med_type,
						'dosage' => $row->dosage,
						'dosage_when' => $row->dosage_when,
						'dosage_freq' => $row->dosage_freq,
						'qty' => $row->qty,
						'no_of_days' => $row->no_of_days,
						'remark' => $row->remark,
						'update_by' => $user_name, 
					);
	
					$rx_group_sub=$this->Doctor_M->insert_opd_prescrption_prescribed_template($prescrption_prescribed_data); 
				}
            }
        }
		
		echo $rx_group_id;
	}

	function save_rx_group()
    {
		$rx_group_name = $this->input->post('input_rx_group_name');
		$complaints=$this->input->post('input_complaints');
		$diagnosis=$this->input->post('input_diagnosis');
		$investigation=$this->input->post('input_investigation');
		$Finding_Examinations=$this->input->post('input_Finding_Examinations');
		$id=$this->input->post('hid_rx_id');
				
		$this->load->model('Doctor_M');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');

        $opd_prescription_data=array(
                'rx_group_name' => $rx_group_name,
                'update_by' => $user_name,
                'Finding_Examinations' => $Finding_Examinations,
                'complaints' => $complaints,
                'diagnosis' => $diagnosis,
                'investigation' => $investigation, 
		);
			
		if($id>0)
		{
			$this->Doctor_M->update_opd_prescription_template($opd_prescription_data,$id);
			$rx_group_id=$id;
			$show_text="Data Saved"; 
		}else{
			$rx_group_id=$this->Doctor_M->insert_opd_prescription_template($opd_prescription_data); 
			$show_text="Data Added"; 
		}

		$rvar=array(
			'insertid' =>$rx_group_id,
			'show_text'=>$show_text
		);
        

		echo json_encode($rvar);
		
	}

	function rx_group_medicine($rx_group_id)
	{
		$sql="select * from opd_prescription_template 
			Where  id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['opd_prescription_template']= $query->result();

		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
				df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
				FROM (((opd_prescrption_prescribed_template pt
				LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
				LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
				LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
				LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
		Where  rx_group_id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['opd_prescrption_prescribed_template']= $query->result();

		$sql="select * from opd_dose_shed ";
		$query = $this->db->query($sql);
		$data['opd_dose_shed']= $query->result();
		
		$sql="select * from opd_dose_when";
		$query = $this->db->query($sql);
		$data['opd_dose_when']= $query->result();

		$sql="select * from opd_dose_frequency";
		$query = $this->db->query($sql);
		$data['opd_dose_frequency']= $query->result();

		$sql="select * from opd_dose_where";
		$query = $this->db->query($sql);
		$data['opd_dose_where']= $query->result();

		$data['rx_group_id']=$rx_group_id;

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_medicine',$data);

	}


	function rx_opd_medicine()
	{
		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
				df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
				FROM (((opd_med_master pt
				LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
				LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
				LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
				LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
				Order by pt.item_name";
		$query = $this->db->query($sql);
		$data['opd_med_master']= $query->result();

		$sql="select * from opd_dose_shed ";
		$query = $this->db->query($sql);
		$data['opd_dose_shed']= $query->result();
		
		$sql="select * from opd_dose_when";
		$query = $this->db->query($sql);
		$data['opd_dose_when']= $query->result();

		$sql="select * from opd_dose_frequency";
		$query = $this->db->query($sql);
		$data['opd_dose_frequency']= $query->result();

		$sql="select * from opd_dose_where";
		$query = $this->db->query($sql);
		$data['opd_dose_where']= $query->result();

		

		$this->load->view('dashboard/opd_prescription_suggest_panel/opd_medicine_panel',$data);

	}


	function rx_group_medicine_save($rx_group_id)
	{
		$this->load->model('Doctor_M');
		

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');

		$med_name = $this->input->post('input_med_name');
		$med_type=$this->input->post('input_med_type');
		$dosage=$this->input->post('input_dosage');
		$dosage_when=$this->input->post('input_dosage_when');
		$dosage_freq=$this->input->post('input_dosage_freq');
		$no_of_days=$this->input->post('input_no_of_days');
		$qty=$this->input->post('input_qty');
		$remark=$this->input->post('input_remark');
		$med_id=$this->input->post('hid_med_id');
		$dosage_where=$this->input->post('input_dose_where');
		$genericname=$this->input->post('input_genericname');
		
		$rx_group_id=$rx_group_id;
		$hid_p_med_id=$this->input->post('hid_p_med_id');

		$insert_master = array( 
			'item_name' => $med_name,
			'formulation' => $med_type,
			'dosage' => $dosage,
			'dosage_when' => $dosage_when,
			'dosage_freq' => $dosage_freq,
			'dosage_where' => $dosage_where,
			'genericname' => $genericname,
			'qty' => $qty,
			'no_of_days' => $no_of_days,
			'remark' => $remark,
			'genericname' => $genericname
		);

		if($med_id==0)
		{
			$med_id=$this->Prescription_M->insert_med_master($insert_master);
		}else{
			$update_master = array( 
				'dosage' => $dosage,
				'dosage_when' => $dosage_when,
				'dosage_freq' => $dosage_freq,
				'dosage_where' => $dosage_where,
				'qty' => $qty,
				'no_of_days' => $no_of_days,
				'remark' => $remark,
				'genericname' => $genericname
			);

			$this->Prescription_M->update_med_master($update_master,$med_id);
		}

		$prescrption_prescribed_data=array(
			'rx_group_id' => $rx_group_id,
			'med_id' => $med_id,
			'med_name' => $med_name,
			'med_type' => $med_type,
			'dosage' => $dosage,
			'dosage_when' => $dosage_when,
			'dosage_freq' => $dosage_freq,
			'qty' => $qty,
			'no_of_days' => $no_of_days,
			'remark' => $remark,
			'dosage_where'=>$dosage_where,
			'update_by' => $user_name, 
		);

		if($hid_p_med_id>0)
		{
			$rx_group_id_INSERT=$this->Doctor_M->update_opd_prescrption_prescribed_template($prescrption_prescribed_data,$hid_p_med_id); 
			$show_text="Data Update"; 
		}else{
			$rx_group_id_INSERT=$this->Doctor_M->insert_opd_prescrption_prescribed_template($prescrption_prescribed_data); 
			$show_text="Data Added"; 
		}
		
		$sql="select * from opd_prescrption_prescribed_template 
			Where  id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['prescrption_prescribed']= $query->result();

		$sql="SELECT * ,d.dose_show_sign AS dose_shed,dw.dose_sign_desc AS dose_when,
			df.dose_sign_desc AS dose_frequency,d_on.dose_sign_desc AS dose_where
			FROM (((opd_prescrption_prescribed_template pt
			LEFT JOIN  opd_dose_shed d ON pt.dosage=d.dose_shed_id)
			LEFT JOIN opd_dose_when dw ON pt.dosage_when=dw.dose_when_id)
			LEFT JOIN opd_dose_frequency df ON pt.dosage_freq=df.dose_freq_id)
			LEFT JOIN opd_dose_where d_on ON pt.dosage_where=d_on.dose_where_id 
		Where  rx_group_id=$rx_group_id";
		$query = $this->db->query($sql);
		$data['opd_prescrption_prescribed_template']= $query->result();

		$this->load->view('dashboard/opd_prescription_suggest_panel/rx_group_medicine_list',$data);

	}


	public function medical_Select_prescription_template()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$p_med_id=$this->input->post('p_med_id');

		$sql="select * from opd_prescrption_prescribed_template 
		where id=$p_med_id";
		$query = $this->db->query($sql);
        $prescribed_template= $query->result();
		
		$rvar=array(
			"med_id" => $prescribed_template[0]->med_id,
			"med_name" => $prescribed_template[0]->med_name,
			"med_type" => $prescribed_template[0]->med_type,
			"dosage" => $prescribed_template[0]->dosage,
			"dosage_when" => $prescribed_template[0]->dosage_when,
			"dosage_freq" => $prescribed_template[0]->dosage_freq,
			"qty" => $prescribed_template[0]->qty,
			"no_of_days" => $prescribed_template[0]->no_of_days,
			"remark" => $prescribed_template[0]->remark,
			"p_med_id"=>$p_med_id,
			'genericname'=>$prescribed_template[0]->genericname,
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	function patient_glucose_data($p_id)
	{
		$sql="Select p.id,Date_Format(p.test_datetime,'%d-%m-%Y') AS test_date,
			if(p.test_type=0,p.test_value,'') AS r_sugar,
			if(p.test_type=1,p.test_value,'') AS f_sugar,
			if(p.test_type=2,p.test_value,'') AS a_sugar
			from patient_glucose_chart p
			where p.p_id=$p_id order by p.test_datetime desc"; 
		$query = $this->db->query($sql);
        $data['patient_glucose_data']= $query->result();

		$data['p_id']=$p_id;

		$this->load->view('dashboard/patient_glucose_data',$data);
	}

	function add_glucose_value($p_id)
	{
		

		$patient_glucose_data=array(
			'p_id' => $p_id,
			'test_datetime'=> str_to_MysqlDate($this->input->post('datepicker_dot')),
			'test_value' => $this->input->post('input_glucose_value'),
			'test_type' => $this->input->post('input_test_type'),
		);

		$rx_group_id_INSERT=$this->Prescription_M->insert_patient_glucose_chart($patient_glucose_data); 
		
		$send_error=validation_errors();
		$rvar=array(
			'insertid' =>$rx_group_id_INSERT,
			'msg'=>$send_error
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	function patient_glucose_data_del($p_id,$g_id)
	{
		
		$this->Prescription_M->remove_patient_glucose_chart($g_id); 
		
		$sql="Select p.id,Date_Format(p.test_datetime,'%d-%m-%Y') AS test_date,
			if(p.test_type=0,p.test_value,'') AS r_sugar,
			if(p.test_type=1,p.test_value,'') AS f_sugar,
			if(p.test_type=2,p.test_value,'') AS a_sugar
			from patient_glucose_chart p
			where p.p_id=$p_id order by p.test_datetime desc"; 
		$query = $this->db->query($sql);
        $data['patient_glucose_data']= $query->result();

		$data['p_id']=$p_id;

		$content=$this->load->view('dashboard/patient_glucose_data',$data,true);

		echo $content;
	}

	function patient_investigation_data($p_id)
	{
		$sql="Select p.*
		from patient_investigation p 
			where p.p_id=$p_id order by p.date_investigation desc"; 
		$query = $this->db->query($sql);
        $data['patient_investigation_data']= $query->result();

		$data['p_id']=$p_id;

		$this->load->view('dashboard/patient_investigation',$data);
	}

	function add_investigation_value($p_id)
	{
		

		$normal_range='';
		$isbold='';
		$out_range='';

		$patient_investigation_data=array(
			'p_id' => $p_id,
			'date_investigation'=> str_to_MysqlDate($this->input->post('datepicker_investigation')),
			'investigation_id' => $this->input->post('input_investigation_ID'),
			'investigation_name' => $this->input->post('input_investigation'),
			'result' => $this->input->post('input_investigation_value'),
			'result_text' => $this->input->post('input_investigation_report'),
			'normal_range' => $normal_range,
			'isbold' => $isbold,
			'out_range' => $out_range,
		);

		$investigation_INSERT=$this->Prescription_M->insert_patient_investigation($patient_investigation_data); 
		
		$send_error=validation_errors();
		$rvar=array(
			'insertid' =>$investigation_INSERT,
			'msg'=>$send_error
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}


	function patient_investigation_value_del($p_id,$inv_id)
	{
		
		$this->Prescription_M->remove_patient_investigation($inv_id); 

		$sql="Select p.*
		from patient_investigation p 
			where p.p_id=$p_id order by p.date_investigation desc"; 
		$query = $this->db->query($sql);
        $data['patient_investigation_data']= $query->result();

		$data['p_id']=$p_id;

		$content=$this->load->view('dashboard/patient_investigation',$data,true);

		echo $content;

	}

	function doc_online($doc_id)
	{
		$sql="Select * from doctor_master where id=$doc_id"; 
		$query = $this->db->query($sql);
        $doctor_master= $query->result();

		$data['doc_name']=$doctor_master[0]->p_fname;
		$data['doc_id']=$doc_id;

		$this->load->view('center_room/center_room',$data);

	}

	// OPD Medicince 

	function opd_medicince()
	{
		$sql="Select id,item_name,formulation,genericname,company_name from opd_med_master order by item_name"; 
		$query = $this->db->query($sql);
        $data['opd_med_master']= $query->result();

		$this->load->view('OPD/opd_medicince',$data);
	}

	function opd_medicince_remove($med_id)
	{
		$this->Prescription_M->remove_med_master($med_id);
		echo 'Item Deleted';
	}

	function opd_medicince_show($med_id)
	{
		$sql="Select * from opd_med_master where id=$med_id"; 
		$query = $this->db->query($sql);
        $opd_med_master= $query->result();

		if(count($opd_med_master)>0)
		{
			echo '<td>'.$opd_med_master[0]->id.'</td>
                                <td>'.$opd_med_master[0]->formulation.' '.$opd_med_master[0]->item_name.'</td>
                                <td>'.$opd_med_master[0]->genericname.'</td>
                                <td>'.$opd_med_master[0]->company_name.'</td>
                                <td><a href="javascript:edit_medicince('.$opd_med_master[0]->id.')">Edit</a></td>
                                <td><a href="javascript:remove_medicince('.$opd_med_master[0]->id.')">Remove</a></td>';
		}else{
			echo '<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>';
		}

	}

	function opd_medicince_edit($med_id)
	{
		$sql="Select * from opd_med_master where id=$med_id"; 
		$query = $this->db->query($sql);
        $data['opd_med_master']= $query->result();

		if (isset($data['opd_med_master'][0]->id)) {
            if (isset($_POST) && count($_POST) > 0) {

				$company_id=$this->input->post('company_id');
				$formulation = $this->input->post('formulation');

				$sql="Select * from med_company where id=$company_id"; 
				$query = $this->db->query($sql);
				$med_company= $query->result();

				if(count($med_company)>0){
					$company_name=$med_company[0]->company_name;
				}else{
					$company_name='';
				}

				$sql="Select * from med_formulation where formulation='$formulation'"; 
				$query = $this->db->query($sql);
				$med_formulation= $query->result();

				if(count($med_formulation)>0){
					$med_formulation_id=$med_formulation[0]->id;
				}else{
					$med_formulation_id=0;
				}

				$params = array(
					'item_name' => $this->input->post('item_name'),
					'formulation' => $this->input->post('formulation'),
					'formulation_id' => $med_formulation_id,
					'company_id' => $company_id,
					'company_name' => $company_name,
					'genericname' => $this->input->post('genericname'),
				);

				$this->Prescription_M->update_med_master($params,$med_id);
		
				echo 'Data Updated';
			} else {
				$sql="Select * from med_company order by company_name"; 
				$query = $this->db->query($sql);
				$data['med_company']= $query->result();

				$sql="Select * from med_formulation order by formulation_length"; 
				$query = $this->db->query($sql);
				$data['med_formulation']= $query->result();

                $this->load->view('OPD/opd_medicince_edit',$data);
            }
        } else {
            echo 'The Medicince you are trying to edit does not exist.';
        }

		
	}

	function opd_medicince_add()
	{
		if (isset($_POST) && count($_POST) > 0) {

			$company_id=$this->input->post('company_id');
			$formulation = $this->input->post('formulation');


			$sql="Select * from med_company where id=$company_id"; 
			$query = $this->db->query($sql);
			$med_company= $query->result();

			if(count($med_company)>0){
				$company_name=$med_company[0]->company_name;
			}else{
				$company_name='';
			}

			$sql="Select * from med_formulation where formulation='$formulation'"; 
			$query = $this->db->query($sql);
			$med_formulation= $query->result();

			if(count($med_formulation)>0){
				$med_formulation_id=$med_formulation[0]->id;
			}else{
				$med_formulation_id=0;
			}

			$params = array(
				'item_name' => $this->input->post('item_name'),
				'formulation' => $this->input->post('formulation'),
				'formulation_id' => $med_formulation_id,
				'company_id' => $company_id,
				'company_name' => $company_name,
				'genericname' => $this->input->post('genericname'),
			);

			$this->Prescription_M->insert_med_master($params);
	
			echo 'Data Insert';
		} else {
			$sql="Select * from med_company order by company_name"; 
			$query = $this->db->query($sql);
			$data['med_company']= $query->result();

			$sql="Select * from med_formulation order by formulation_length"; 
			$query = $this->db->query($sql);
			$data['med_formulation']= $query->result();

			$this->load->view('OPD/opd_medicince_add',$data);
		}

		
	}


    
}