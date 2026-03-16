<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Patient extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
		$this->load->model('Patient_M');
	}

    public function index()
	{
		$sql="select * from hc_insurance";
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();

		$sql="select * from blood_group order by id";
        $query = $this->db->query($sql);
        $data['blood_group']= $query->result();
		
		$this->load->view('Patient_V',$data);
	}

    public function search_adv()
	{	
		$sdata="";
		
		$input_mphone1=$this->input->post('input_mphone1');
		$input_Aadhar=$this->input->post('input_udai');
		$input_relative_name=$this->input->post('input_relative_name');
		$input_name=$this->input->post('input_name');
		
		if(strlen($input_mphone1)>0)
		{
			$sdata=$sdata." or mphone1 = '".$input_mphone1."'";
		}
		
		if(strlen($input_Aadhar)>0)
		{
			$sdata=$sdata." or udai = '".$input_Aadhar."'";
		}

		if(strlen($input_relative_name)>0)
		{
			$sdata=$sdata." or (trim(p_rname) = '".trim($input_relative_name)."' and Trim(p_fname)='".trim($input_name)."') ";
		}
		
        $sql = "SELECT id,Concat(p_fname,'/',if(gender=1,'M','F'),'/',p_relative,' ',p_rname) as Sresult 
		FROM patient_master WHERE 1<>1 ".$sdata."  order by  id desc";
		$query = $this->db->query($sql);
        $search_result= $query->result();

		if(count($search_result)>0)
		{
			echo '<table class="table table-bordered table-striped TableData"><tr><th>Name of Person/Sex/Relative Name</th></tr>';
		}
        
		for($i=0;$i<count($search_result);++$i)
		{
			echo '<tr>
					<td>
						<a href="javascript:load_form(\'/Patient/person_record/'.$search_result[$i]->id.'\');">'.$search_result[$i]->Sresult.'</a>
					</td>
				</tr>';
		}
		
		if(count($search_result)>0)
		{
			echo '</table>';
		}
	}
	
	public function search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata));

		if(strlen($sdata)==0)
        {
			$sql = "SELECT p.*,GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age , 
					Date_Format(p.last_visit,'%d-%m-%Y')  AS Last_Visit
					FROM patient_master p 
					group BY p.id 
					order BY p.last_visit DESC
					LIMIT 100 ";
        }else{

			$sdate_array=explode(" ",$sdata);
			$search_string=" 1=1 " ;

			foreach($sdate_array as $row_data)
			{
				if(is_numeric($row_data))
				{
					$search_string.=" and (p.p_code like '%$row_data' 
									or p.mphone1 = '$row_data' 
									or p.udai='$row_data' )";
				}elseif(ctype_alpha($row_data)){
					$search_string.=" and (p.p_fname like '%$row_data%' 
						or p.email1 = '$row_data' 
						or SUBSTRING_INDEX(p.p_fname,' ',1) sounds like '".$row_data."')";
				}else{
					$search_string.=" and (p.p_code like '$row_data' 
						or p.email1 = '$row_data' )";
				}
			}

			$sql = "SELECT p.*,GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age , 
					Date_Format(p.last_visit,'%d-%m-%Y') AS Last_Visit
					FROM patient_master p 
					WHERE  $search_string
				group BY p.id 
				order BY p.last_visit DESC  ";
		}
					
		$query = $this->db->query($sql);
		$data['data']= $query->result();
	
        $this->load->view('Patient_Search_V',$data);
	}
	

	public function search_opd()
	{
		$sql = "SELECT p.*,GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age , 
					Date_Format(o.apointment_date,'%d-%m-%Y')  AS opd_Visit,o.doc_name,o.doc_spec,o.opd_code,i.short_name
					FROM (patient_master p join opd_master o on p.id =o.p_id)
					Left join hc_insurance i on i.id = o.insurance_id
					order BY o.opd_id DESC 	LIMIT 100 ";

		$query = $this->db->query($sql);
		$data['data']= $query->result();
	
        $content=$this->load->view('Patient_Search_opd',$data,true);

		echo $content;
	}
	
    function person_record($pno,$edit=0)
    {
       	$sql="select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,
		if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=".$pno;
		$query = $this->db->query($sql);
        $data['data']= $query->result();

		$required_age=$data['data'][0]->age;
		$required_age_in_month=$data['data'][0]->age_in_month;
        $required_dob=$data['data'][0]->dob;
        $estimate_dob=$data['data'][0]->estimate_dob;
        
        if($required_age=='' && $estimate_dob==1)
        {
			if($required_age=='0')
			{
				if($required_age_in_month==0 || $required_age_in_month=='')
				{
					$edit=1;
				}
			}
        }

        if($required_dob=='' && $estimate_dob==0 )
        {
			$edit=1;
        }


		$sql="select *,p.p_fname,if(apointment_date=curdate(),1,0) as new_opd,
		date_format(apointment_date,'%d-%m-%Y') as str_apointment_date 
		from opd_master o join patient_master p on o.p_id=p.id where p_id=$pno Order by opd_code desc";
		$query = $this->db->query($sql);
        $data['opd_List']= $query->result();
		
		$sql="select m.id,m.invoice_code,Date_Format(m.inv_date,'%d-%m-%y') as str_inv_date,m.inv_name,
				if(count(t.id)>0,group_concat(t.item_name SEPARATOR ' / '),'No-Item') as Item_List,	m.net_amount,m.invoice_status
				from invoice_master m left join invoice_item t on m.id=t.inv_master_id 
				where attach_type=0 and  attach_id=".$pno." group by m.id order by m.id desc";
        $query = $this->db->query($sql);
        $data['invoice_list']= $query->result();
		
		$sql="select i.*,m.ins_company_name,m.opd_allowed,m.charge_cash 
		from hc_insurance_card i join hc_insurance m on i.insurance_id=m.id 
		where   i.p_id=".$pno;
        $query = $this->db->query($sql);
        $data['data_insurance_card']= $query->result();
	
		$sql="select *,Date_Format(date_registration,'%d-%m-%Y') as str_date_registration 
			from  organization_case_master  where status=0 and case_type=0 and p_id=".$pno;
        $query = $this->db->query($sql);
        $data['case_master_opd']= $query->result();

        $sql="SELECT o.*,DATE_FORMAT(o.date_registration,'%d-%m-%Y') as str_date_registration ,
			i.ipd_code,i.register_date
			from  organization_case_master  o left JOIN ipd_master i ON o.ipd_id=i.id
			WHERE o.status=0 AND o.case_type=1 and o.p_id=".$pno;
        $query = $this->db->query($sql);
        $data['case_master_ipd']= $query->result();

        $sql="select * from  file_upload_data  where id=".$data['data'][0]->profile_file_id;
        $query = $this->db->query($sql);
        $file_data= $query->result();

        $profile_file_path="/assets/images/no_image.jpg";

		$sql="SELECT * from  blood_group order by id";
        $query = $this->db->query($sql);
        $data['blood_group']= $query->result();

		$sql="select * from tag_master  Order by tag_name";
		$query = $this->db->query($sql);
        $data['tag_master']= $query->result();

		$sql="SELECT a.*,t.tag_name,t.tag_type_id
				FROM patient_tag_assign a JOIN tag_master t ON a.tag_id=t.id
				WHERE isdelete=0 and a.p_id=$pno";
		$query = $this->db->query($sql);
		$data['patient_tag_list']= $query->result();

        if(count($file_data)>0)
        {
			$pos=strpos($file_data[0]->full_path,'/uploads/',1) ;
			$profile_file_path=substr($file_data[0]->full_path,$pos);
        }

        $data['profile_file_path']=$profile_file_path;

		if($edit==0)
		{
			$this->load->view('Person_profile_V',$data);
		}else{
			$this->load->view('Person_Edit_V',$data);
		}
		

    }
	
	public function update_1()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$FormRules = array(
                array(
                    'field' => 'input_name',
                    'label' => 'Name',
                    'rules' => 'required|min_length[2]|max_length[30]'
                )
            );

			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {

                $data = array( 
                    'mphone1' => $this->input->post('input_mphone1'), 
					'p_fname' => strtoupper($this->input->post('input_name')),
					'gender' => $this->input->post('optionsRadios_gender'),
					'dob' => str_to_MysqlDate($this->input->post('datepicker_dob')),
					'zip' => $this->input->post('input_zip'),
					'add1' => $this->input->post('input_address'),
					'city' => $this->input->post('input_city'),
					'district' => $this->input->post('input_district'),
					'state' => $this->input->post('input_state'),
					'age' => $this->input->post('input_age'),
					'title' => $this->input->post('cbo_title'),
					'p_relative' => strtoupper($this->input->post('cbo_relation')),
					'p_rname' => $this->input->post('input_relative_name'),
					'email1' => $this->input->post('input_email'),
					'age' => $this->input->post('input_age'),
					'age_in' => $this->input->post('cbo_age')
                ); 

				$pid= $this->input->post('p_id');
                $this->Patient_M->update($data,$pid); 
                $rvar=array(
                'update' =>1,
				'showcontent'=> 'Data Saved successfully'
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
                
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}
	

	public function update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$chk_age=$this->input->post('chk_age');
         
		 $FormRules = array(
                array(
                    'field' => 'input_name',
                    'label' => 'Name of Patient',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
                
            ); 
		 
		$age_month=$this->input->post('input_age_month');
		$age_year=$this->input->post('input_age_year');

		
		if($chk_age=='on')
		 {
			$estimate_dob=1;
			
			if($age_year=='' && $age_month=='')
			{
				array_push($FormRules,
				array(
                    'field' => 'input_age_year',
					'label' => 'Patient Age',
                    'rules' => 'required|min_length[1]|max_length[4]'
                )
				);
			}
		 }else{
			$estimate_dob=0;
			array_push($FormRules,
				array(
                    'field' => 'datepicker_dob',
					'label' => 'Date of Birth',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
			 );
		 }

			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
                
                $data = array( 
                    'mphone1' => $this->input->post('input_mphone1'), 
                	'p_fname' => strtoupper($this->input->post('input_name')),
                	'gender' => $this->input->post('optionsRadios_gender'),
                	'zip' => $this->input->post('input_zip'),
					'add1' => strtoupper($this->input->post('input_address')),
					'city' => strtoupper($this->input->post('input_city')),
					'district' => strtoupper($this->input->post('input_district')),
					'state' => strtoupper($this->input->post('input_state')),
					'title' => $this->input->post('cbo_title'),
					'p_relative' => strtoupper($this->input->post('cbo_relation')),
					'p_rname' => strtoupper($this->input->post('input_relative_name')),
					'email1' => strtoupper($this->input->post('input_email')),
					'udai' => strtoupper($this->input->post('input_Aadhar')),
					'estimate_dob' => $estimate_dob,
					'blood_group' => $this->input->post('input_blood_group')
                ); 
				
				if($chk_age=='on')
				{
					$data['age'] = $age_year;
					$data['age_in_month'] = $age_month;
					
				}else{
					$data['dob'] = str_to_MysqlDate($this->input->post('datepicker_dob'));
					
				}
				
				$pid= $this->input->post('p_id');
                $this->Patient_M->update($data,$pid); 
                $rvar=array(
                'update' =>1,
				'showcontent'=> 'Data Saved successfully'
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
                
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }

	}
	
	public function update_aadhar()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$data = array( 
			'udai' => $this->input->post('udai'),
		); 
		
		$pid= $this->input->post('p_id');
		$this->Patient_M->update($data,$pid); 
		$rvar=array(
		'update' =>1,
		'showcontent'=> 'Data Saved successfully'
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	public function create() { 
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$chk_age=$this->input->post('chk_age');

		$FormRules = array(
                array(
                    'field' => 'input_name',
                    'label' => 'Name of Patient',
                    'rules' => 'required|min_length[1]|max_length[100]'
                ),
				array(
                    'field' => 'input_relative_name',
                    'rules' => 'required|min_length[1]|max_length[100]'
                ),
                array(
                    'field' => 'input_mphone1',
                    'rules' => 'required|min_length[10]|max_length[10]'
                )
                
            ); 

		$age_month=$this->input->post('input_age_month');
		$age_year=$this->input->post('input_age_year');

		
		if($chk_age=='on')
		{
			$estimate_dob=1;
			
			if($age_year=='' && $age_month=='')
			{
				array_push($FormRules,
				array(
                    'field' => 'input_age_year',
					'label' => 'Patient Age',
                    'rules' => 'required|min_length[1]|max_length[4]'
                )
				);
			}
		}else{
			$estimate_dob=0;
			array_push($FormRules,
				array(
                    'field' => 'datepicker_dob',
					'label' => 'Date of Birth',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
			);
		}

		$this->form_validation->set_rules($FormRules);

		if ($this->form_validation->run() == TRUE)
            {

                $data = array( 
                    'mphone1' => $this->input->post('input_mphone1'), 
					'p_fname' => strtoupper($this->input->post('input_name')),
					'gender' => $this->input->post('optionsRadios_gender'),
					'zip' => $this->input->post('input_zip'),
					'add1' => strtoupper($this->input->post('input_address')),
					'city' => strtoupper($this->input->post('input_city')),
					'district' => strtoupper($this->input->post('input_district')),
					'state' => strtoupper($this->input->post('input_state')),
					'title' => $this->input->post('cbo_title'),
					'p_relative' => $this->input->post('cbo_relation'),
					'p_rname' => strtoupper($this->input->post('input_relative_name')),
					'blood_group' => strtoupper($this->input->post('input_blood_group')),
					'udai' => strtoupper($this->input->post('input_udai')),
					'estimate_dob' => $estimate_dob
                );
				
				if($chk_age=='on')
				{
					$data['age'] = $age_year;
					$data['age_in_month'] = $age_month;
					
				}else{
					$data['dob'] = str_to_MysqlDate($this->input->post('datepicker_dob'));
					
				}
				
                $inser_id=$this->Patient_M->insert($data); 
                $rvar=array(
                'insertid' =>$inser_id
                );

				// Check Multiple UHID
				$sdata='';
				//$sdata=$sdata." or udai = '".$this->input->post('input_udai')."'";
				$sdata=$sdata." or (trim(p_rname) = '".trim(strtoupper($this->input->post('input_relative_name')))."' and Trim(p_fname)='".trim(strtoupper($this->input->post('input_name')))."') ";
				
				$sql = "SELECT id,Concat(p_code,'/',p_fname,'/',if(gender=1,'M','F'),'/',p_relative,' ',p_rname) as Sresult 
				FROM patient_master WHERE 1<>1 ".$sdata." and id<>$inser_id  order by  id desc";
				$query = $this->db->query($sql);
				$search_result= $query->result();

				$user = $this->ion_auth->user()->row();
				$user_name = $user->first_name.''. $user->last_name.'[Date:'.date('d-m-Y h:i:s').']-'.$user->id;

				$log_text='';

				foreach($search_result as $row)
				{
					$log_text.=$row->Sresult.PHP_EOL;
				}

				if(count($search_result)>0)
				{
					$data = array( 
						'new_uhid' => $inser_id, 
						'name_of_person' => strtoupper($this->input->post('input_name')),
						'new_patient_code' => '',
						'date_of_registration' => date('Y-m-d h:i:s'),
						'update_by' => $user_name,
						'remark_duplicate' => $log_text,
					);

					$inser_id=$this->Patient_M->insert_log_patient_multiple($data); 
				}

				//End of Chking Multiple UHID
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
			
	}
		
	public function show_cards($pno,$card_no=0)
	{
			
		$sql="select * from patient_master where id=".$pno;		
        $query = $this->db->query($sql);
        $data['pdata']= $query->result();
		
		$inc_card_no=$data['pdata'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id>1 and id=".$inc_card_no;
        $query = $this->db->query($sql);
        $data['data_insurance_card']= $query->result();

		$sql="select * from hc_insurance where id>1 and active=1 order by ins_company_name ";
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();

		$this->load->view('insurance/insurance_card',$data);
	}
	
	public function city(){
		$this->load->model('Patient_M');
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);
			$this->Patient_M->get_city($q);
		}
	}

	public function P_info(){
		
		$UHID = $this->input->post('input_uhid');
		
		$sql="select * from patient_master_exten where p_code='$UHID' ";
        $query = $this->db->query($sql);
        $data= $query->result();
		
		if(count($data)>0)
		{
			$Patient_id=$data[0]->id;
			$Patient_info=$data[0]->p_fname.' '.$data[0]->p_relative.' '.$data[0]->p_rname.' /Age :'.$data[0]->str_age;

		}else{
			$Patient_id=0;
			$Patient_info='';
		}

		$rvar=array(
			'Patient_id' =>$Patient_id,
			'Patient_info'=>$Patient_info
			);

		$encode_data = json_encode($rvar);
		echo $encode_data;

	  }
	
	public function update_card()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$FormRules = array(
                array(
                    'field' => 'input_insurance_id',
                    'label' => 'Insurance No',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
				array(
                    'field' => 'input_card_holder_name',
                    'label' => 'Insurance Card Holder Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
            );

		$this->form_validation->set_rules($FormRules);

		if ($this->form_validation->run() == TRUE)
            {
                
                $data = array( 
                    'insurance_id' => $this->input->post('Insurance_id'), 
					'p_id' => $this->input->post('p_id'),
					'insurance_no' => $this->input->post('input_insurance_id'),
					'card_holder_name' => strtoupper($this->input->post('input_card_holder_name')),
					'issue_date' => str_to_MysqlDate($this->input->post('datepicker_issue_date')),
					'expiry_date' => str_to_MysqlDate($this->input->post('datepicker_expiry_date')),
					'relation_patient_cardholder' => strtoupper($this->input->post('input_Relation'))
                ); 
					
				$card_no=$this->input->post('inscard_id');
                $inser_id=$card_no;
				
				if($card_no=="0")
				{
					$inser_id=$this->Patient_M->insert_card($data);
				}else{
					$this->Patient_M->update_card($data,$card_no);
				}

				$data = array( 
					'insurance_card_id' => $inser_id,
					'insurance_id' => $this->input->post('Insurance_id'), 
					'insurance_no' => $this->input->post('input_insurance_id'),
					'card_holder_name' => strtoupper($this->input->post('input_card_holder_name')),
					'issue_date' => str_to_MysqlDate($this->input->post('datepicker_issue_date')),
					'expiry_date' => str_to_MysqlDate($this->input->post('datepicker_expiry_date')),
					'relation_patient_cardholder' => strtoupper($this->input->post('input_Relation'))
                );
				
				$this->Patient_M->update($data,$this->input->post('p_id')); 

                $rvar=array(
                'insertid' =>$inser_id
                );
				
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
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

		$this->load->view('Person/Person_Profile',$data);

	}

	public function show_profile_image($p_id)
	{
		$data['p_id']=$p_id;

		$sql="select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,
		if(gender=1,'Male','Female') as xgender ,
		if(date_add(insert_date,interval 12 hour)>sysdate(),1,0) as p_edit
		from patient_master where  id=".$p_id;
		$query = $this->db->query($sql);
        $data['data']= $query->result();
		
		
		$this->load->view('Person/person_profile_photo',$data);
	}


	public function save_profile_image($p_id)
	{
		$filename =  'P-'.$p_id.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
				
		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		if (!$this->upload->do_upload('webcam')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
			
			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
						
			$udata = array( 
					'upload_by'=> $user_name,
					'pid'=>$p_id,
					'opd_id'=>'0',
					'ipd_id'=>'0',
					'case_id'=>'0',
					'upload_by_id'=>$user_id
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
					
				$user_name = $user->first_name.''. $user->last_name.':T-'.date('d-m-Y h:m:s');
		
				$dataupdate = array( 
				'profile_file_id' => $file_insert_id,
				'profile_picture_update_by' => $user_name
				);

				$this->Patient_M->update($dataupdate,$p_id); 
			}
			
			echo $filename;
		}
	}

	//Patient Tag

	function add_comments($p_id)
    {   
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' [Date:'.date('d-m-Y h:i').']';
		
		$comments_data = array( 
                    'p_id' => $p_id,
                    'remark' => $this->input->post('add_notes'),
					'insert_by_id' => $user_id,
					'insert_by' => $user_name
			);

		$insert_id=$this->Patient_M->insert_remark($comments_data);

		if($insert_id>0)
		{
			$rvar=array(
				'update' =>$insert_id,
				'error_text' => 'Update Done '.$insert_id
			);
		}else{
			$rvar=array(
				'update' =>0,
				'error_text' => 'Some Error',
			);
		}
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
    }
	
	function patient_tag()
	{
		$this->load->model('Patienttag_M');

		$tag_id=$this->input->post('tag_id');
		$p_id=$this->input->post('p_id');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		if($this->input->post('isadd')==1)
        {
		
            $sql="Select * 
					from patient_tag_assign 
					where isdelete=0 and p_id='$p_id' and tag_id='$tag_id'";
            $query = $this->db->query($sql);
            $no_rec= $query->num_rows();
			
            if($no_rec==0)
            {
                $data = array( 
					'p_id' => $p_id, 
					'tag_id' => $tag_id,
					'assign_by_id' => $user_id,
					'assign_by' => $user_name.'['.$user_id.']'.date('d-m-Y H:i'),
    	       ); 
                $inser_id=$this->Patienttag_M->insert_patient_tag_assign($data); 
                
            }
        }else{
			
			$cust_tag_assign=$this->input->post('cust_tag_assign');
			
            $data = array( 
				'isdelete' => 1, 
				'delete_by' => $this->session->userdata('username').'['.$this->session->userdata('agent_id').']'.date('d-m-Y H:i'),
			); 
            
			$this->Patienttag_M->update_patient_tag_assign($data,$cust_tag_assign); 
        }
				
		echo '1';

	}



	//AutoList of Person Names
	
	public function get_name(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from name_list where name like '".$q."%'  ";

			$sql=$sql."order by name limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
				foreach ($query->result_array() as $row){
					$new_row['label']=htmlentities(stripslashes($row['name']));
					$new_row['value']=htmlentities(stripslashes($row['name']));
					
					$row_set[] = $new_row; //build an array
				}
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
}

