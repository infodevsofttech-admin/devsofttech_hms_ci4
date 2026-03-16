<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opd extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
		//$this->load->library("Pdf");
	}

    public function addopd($pno,$org_case_id=0)
    {
        $sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id ";
        $query = $this->db->query($sql);
		$data['doc_spec_l']= $query->result();

		$data['org_case_id']=$org_case_id;
		
		if($org_case_id>0)
		{
			$sql="SELECT *
            from organization_case_master 
            where id= $org_case_id";
			$query = $this->db->query($sql);
			$data['org_case_master']= $query->result();
		}

        $this->load->view('opd_appointment_V',$data);
	}
	
	
    
    public function showfee()
    {
		$doc_id=$this->input->post('doc_id');
		$p_id=$this->input->post('pid');
		$org_case_id=$this->input->post('org_case_id');
		
		$sql="select *
            from doctor_master 
            where id=$doc_id";
        $query = $this->db->query($sql);
		$doctor_master= $query->result();

		$no_opd_days=5-1;

		if(count($doctor_master)>0)
		{
			$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
		}

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$doc_id." group by d.id ";
        $query = $this->db->query($sql);
        $doc_info= $query->result();

        $sql="SELECT * from opd_master where p_id=".$p_id." and doc_id=".$doc_id." 
		and  apointment_date >= date_add(curdate(),interval -$no_opd_days day) and opd_fee_type<>3";
		$query = $this->db->query($sql);
		$opd_running= $query->result();

		$sql="SELECT * from opd_master where p_id=".$p_id." ";
		$query = $this->db->query($sql);
		$opd_old= $query->result();

		$fee_type_list="4,0";

		$fee_type_select=1;

		if(count($opd_old)>0)
		{
			if(count($opd_running)>0)
			{
				$fee_type_select=3;
				$fee_type_list.=",3";
			}else{
				$fee_type_select=2;
				$fee_type_list.=",2";
			}

		}else{
			$fee_type_select=1;
			$fee_type_list.=",1";
		}

        $content='';

        $content.= '<H4><strong>Doctor Name :</strong> '.$doc_info[0]->p_fname.' <strong>/ Specialization:</strong> '.$doc_info[0]->SpecName.' <strong>/ 
        Gender :</strong> '.$doc_info[0]->xGender.' <strong></H4>';
        $content.= '<input type="hidden" name="doc_id" id="doc_id" value="'.$doc_info[0]->id.'" />';

        
        $sql="SELECT d.*,if(d.doc_fee_desc='',t.fee_type,d.doc_fee_desc) AS fee_desc
			FROM doc_opd_fee d JOIN doc_fee_type t ON d.doc_fee_type=t.id  
			WHERE  doc_id=".$doc_id." and t.id in (".$fee_type_list.")";
        
        $query = $this->db->query($sql);
        $data['doc_fee_a']= $query->result();

        $sql="SELECT d.*,if(d.doc_fee_desc='',t.fee_type,d.doc_fee_desc) AS fee_desc
			FROM doc_opd_fee d JOIN doc_fee_type t ON d.doc_fee_type=t.id  
			WHERE  doc_id=".$doc_id." and t.id not in (".$fee_type_list.")";
        
        $query = $this->db->query($sql);
        $data['doc_fee_b']= $query->result();



        $checked='';

		foreach($data['doc_fee_a'] as $row)
			{
				if($row->id==$fee_type_select)
				{
					$checked='checked';
				}

				$content.= '<label>';
				$content.= '<input type="radio" name="fee_id" id="fee_id" class="flat-red" '.$checked.' value='.$row->id.'> ';
				$content.= ' Rs. '.$row->amount.' [<i>'.$row->fee_desc.'</i>]';
				$content.= '</label><br/>';
			}

		$content.="<hr/>";

		$other_content='';

		foreach($data['doc_fee_b'] as $row)
			{
				if($row->id==$fee_type_select)
				{
					$checked='checked';
				}

				$other_content.= '<label>';
				$other_content.= '<input type="radio" name="fee_id" id="fee_id" class="flat-red" '.$checked.' value='.$row->id.'> ';
				$other_content.= ' Rs. '.$row->amount.' [<i>'.$row->fee_desc.'</i>]';
				$other_content.= '</label><br/>';
			}

		$content.='<div class="panel-group" id="accordion">';
		$content.='<div class="panel panel-default">
						<div class="panel-heading">
						<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
							Other Option</a>
						</h4>
						</div>
						<div id="collapse1" class="panel-collapse collapse">
						<div class="panel-body">
							'.$other_content.'
						</div>
						</div>
					</div>
					</div>';

        $rvar=array(
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
            'content'=>$content
        );
        
        $encode_data = json_encode($rvar);

        echo $encode_data ;

    }
	
	public function invoice($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'ECHS Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
	
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";

        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select * from  organization_case_master  
		where status=0 and case_type=0 and p_id=".$data['patient_master'][0]->id;
        $query = $this->db->query($sql);
		$data['case_master']= $query->result();
			
		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
				
		$sql="select * from  refund_order  where refund_process=0 and refund_type=1 and  refund_type_id=".$opdid;
		$query = $this->db->query($sql);
		$data['refund_order']= $query->result();

		if(count($data['refund_order'])>0)
		{
			$data['refund_status']=1; // Refund Pending
		}else{
			$data['refund_status']=0 ; //No Pending
		}
		
		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as paid_amount from  payment_history  where payof_type=1 and  payof_id=".$opdid;
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();

		$data['paid_amount']=0;

		if($data['payment_history'][0]->paid_amount=='' || $data['payment_history'][0]->paid_amount==0 )
		{
			$data['paid_amount']=0;
		}else{
			$data['paid_amount']=$data['payment_history'][0]->paid_amount;
		}

		$opd_fee_gross_amount=$data['opd_master'][0]->opd_fee_amount;

		if($data['opd_master'][0]->payment_mode==4)
		{
			$data['pending_amount']=0;
		}else{
			$data['pending_amount']=$opd_fee_gross_amount-$data['paid_amount'];
		}

		$sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id ";
        $query = $this->db->query($sql);
		$data['doc_spec_l']= $query->result();

		$sql="select s.id,s.pay_type,m.bank_name
				From hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
		$query = $this->db->query($sql);
		$data['bank_data']= $query->result();
				
		$this->load->view('opd_invoice_V',$data);
		
	}
	
	public function invoice_print($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['opd_master'][0]->apointment_date."')  AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";

        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		if($data['opd_master'][0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$data['opd_master'][0]->insurance_case_id;
			$query = $this->db->query($sql);
			$data['case_master']= $query->result();
		}
		
		$this->load->view('invoice_print_v',$data);
	}

	

	public function opd_lettre_print($opdid)
	{
		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select *
            from doctor_master 
            where id=$doc_id";
			$query = $this->db->query($sql);
			$doctor_master= $query->result();

			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}

		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['opd_master'][0]->apointment_date."')  AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

        $sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date from opd_master 
        where p_id=".$data['opd_master'][0]->p_id." and opd_id < ".$opdid." 
        order by opd_id desc limit 1";
        $query = $this->db->query($sql);
		$data['old_opd']= $query->result();

		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		if($data['opd_master'][0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$data['opd_master'][0]->insurance_case_id;
			$query = $this->db->query($sql);
			$data['case_master']= $query->result();
		}
		
		$this->load->view('opd_letter_head',$data);
	}
	
	
    public function confirm_opd()
    {
        $this->load->model('Opd_M');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.' '. $user->last_name.'['.date('d-m-Y H:i:s').']';

        $sql="select * from  patient_master  where id=".$this->input->post('pid');
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
        
        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$this->input->post('doc_id')." group by d.id ";
        $query = $this->db->query($sql);
        $data['doc_info']= $query->result();

		$sql="select * from  doc_opd_fee  where id=".$this->input->post('fee_id');
		$query = $this->db->query($sql);
		$data['doc_fee']= $query->result();

		$sql="select * from  opd_master  where p_id=".$this->input->post('pid')." order by opd_id desc";
        $query = $this->db->query($sql);
		$data['opd_master']= $query->result();

		$last_Vist_date='';

		if(count($data['opd_master'])>0)
		{
			$last_Vist_date=$data['opd_master'][0]->apointment_date;
		}

		$opd_fee_amount=$data['doc_fee'][0]->amount;
		$opd_fee_desc=$data['doc_fee'][0]->doc_fee_desc;
		$echs_credit=0;
		
		$doc_id=$this->input->post('doc_id');

        $data['insert'] = array( 
            'p_id' => $this->input->post('pid'),
			'P_name' => strtoupper($data['person_info'][0]->p_fname),			
			'doc_id' => $this->input->post('doc_id'),
			'insurance_id' => '0',
			'opd_fee_id' => $this->input->post('fee_id'),
            'opd_fee_amount' => $opd_fee_amount,
			'opd_fee_gross_amount' => $opd_fee_amount,
            'opd_fee_desc' => $opd_fee_desc,
			'doc_name' => $data['doc_info'][0]->p_fname,
			'opd_fee_type' =>$data['doc_fee'][0]->doc_fee_type,
			'apointment_date' =>str_to_MysqlDate($this->input->post('datepicker_appointment')),
            'doc_spec' => $data['doc_info'][0]->SpecName,
			'prepared_by' => $user_name_info,
		); 

		$data['patient_master'] = array( 
            'last_visit' => str_to_MysqlDate($this->input->post('datepicker_appointment')),
		); 
		
		if(count($data['opd_master'])>0)
		{
			$data['insert']=array_merge($data['insert'],array('last_opdvisit_date'=>$data['opd_master'][0]->apointment_date));
		}

        $inser_id=$this->Opd_M->insert( $data['insert']);

		if($inser_id>0)
		{
			$this->load->model('Patient_M');
			$this->Patient_M->update($data['patient_master'],$this->input->post('pid'));
		}

        $rvar=array(
			'insertid' =>$inser_id,
			'error_text'=>'OPD Register',
			'csrfName' => $this->security->get_csrf_token_name(),
			'csrfHash' => $this->security->get_csrf_hash(),
        );
        
        $encode_data = json_encode($rvar);
        echo $encode_data;
		
    }
	
	public function confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Opd_M');
		$this->load->model('Payment_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name.'['.date('d-m-Y H:i:s').']';
		
		$submit_page=$this->input->post('spid');
		$oid=$this->input->post('oid');
		
		$sql="select * from opd_master where opd_id='".$oid."'";
		$query = $this->db->query($sql);
		$opd_master= $query->result();
		
		$sql="select COALESCE(SUM(if(credit_debit=0,amount,amount*-1)),0) AS opd_pay  
			from payment_history where payof_id='$oid' and payof_type=1";
		$query = $this->db->query($sql);
		$chk_payment= $query->result();

		$pending_amt=$opd_master[0]->opd_fee_amount-$chk_payment[0]->opd_pay;

		$pay_remark='';
		if($opd_master[0]->opd_discount>0)
		{
			$pay_remark='Dis.Amt.:'.$opd_master[0]->opd_disc_remark.' /Amount: '. $opd_master[0]->opd_discount.'/Update:'.$opd_master[0]->opd_disc_update_by;
		}

		if($this->input->post('mode')==0)                                 
		{
			$paydata = array( 
					'payment_mode'=> '0',
					'payof_type'=>'1',
					'payof_id'=>$this->input->post('oid'),
					'payof_code'=>$opd_master[0]->opd_code,
					'credit_debit'=>'0',
					'amount'=>$opd_master[0]->opd_fee_amount,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name_info.' ['.$user_id.']',
					'update_by_id'=>$user_id,
					'insert_code'=>$submit_page
			);
			
			$insert_id=$this->Payment_M->insert($paydata);
			
			$data = array( 
					'payment_mode'=> '0',
					'payment_status'=>'1',
					'payment_mode_desc'=>'No Cost',
					'payment_id'=>$insert_id,
					'confirm_pay_opd'=>date('Y-m-d H:i:s'),
					'prepared_by_id'=>$user_id
			);
			
			$this->Opd_M->update( $data,$this->input->post('oid'));
			
				$status='Zero Cost';

				
				$rvar=array(
                'update' =>1,
		        'showcontent'=>$status,
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
	
		if($this->input->post('mode')==1 && $pending_amt>0)                                 
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'payof_type'=>'1',
					'payof_id'=>$this->input->post('oid'),
					'payof_code'=>$opd_master[0]->opd_code,
					'credit_debit'=>'0',
					'amount'=>$opd_master[0]->opd_fee_amount,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name_info.' ['.$user_id.']',
					'update_by_id'=>$user_id,
					'insert_code'=>$submit_page
			);
			
			$insert_id=$this->Payment_M->insert($paydata);
			
			$data = array( 
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'payment_mode_desc'=>'Cash',
					'payment_id'=>$insert_id,
					'confirm_pay_opd'=>date('Y-m-d H:i:s'),
					'prepared_by_id'=>$user_id
			);
			
			$this->Opd_M->update( $data,$this->input->post('oid'));
			
				$status='CASH';
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$status,		       
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==2 && $pending_amt>0 )
		{
			$FormRules = array(
                array(
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[3]|max_length[15]'
                )
            );
			
			$this->form_validation->set_rules($FormRules);
			if ($this->form_validation->run() == TRUE)
            {
				$paydata = array( 
					'payment_mode'=> '2',
					'payof_type'=>'1',
					'payof_id'=>$this->input->post('oid'),
					'payof_code'=>$opd_master[0]->opd_code,
					'credit_debit'=>'0',
					'amount'=>$opd_master[0]->opd_fee_amount,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name_info.' ['.$user_id.']',
					'pay_bank_id'=>$this->input->post('cbo_pay_type'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'payment_mode_desc'=>'Bank/Online',
						'confirm_pay_opd'=>date('Y-m-d H:i:s'),
						'payment_id'=>$insert_id,
						'prepared_by_id'=>$user_id
				);
				
				$this->Opd_M->update( $data,$this->input->post('oid'));
				
				$status='Bank Card';
								
				$rvar=array(
                'update' =>1,
				'showcontent'=>$status,
				
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error,
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}
		
		if($this->input->post('mode')==4)
		{
			$data = array( 
                    
					'payment_mode'=> '4',
					'payment_status'=>'1',
					'payment_mode_desc'=>'Org.Case Credit',
					'confirm_pay_opd'=>date('d/m/Y H:m:s'),
					'insurance_case_id'=>$this->input->post('case_id'),
					'prepared_by_id'=>$user_id
			);
			
			$this->Opd_M->update( $data,$this->input->post('oid'));
				
				$status='Org. Case Credit';
				
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$status,
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
	}
	
	public function update_discount()
	{
		$oid=$this->input->post('oid');
		
		$opd_discount=$this->input->post('input_dis_amt');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$sql="select *  from  opd_master  where opd_id=".$oid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		$opd_fee_amount=$data['opd_master'][0]->opd_fee_gross_amount-$opd_discount;
		
		$dataupdate = array( 
				'opd_discount' => $this->input->post('input_dis_amt'),
				'opd_disc_remark' => $this->input->post('input_dis_desc'),
				'opd_fee_amount' => $opd_fee_amount,
				'opd_disc_update_by'=>$user_name.'['.$user_id.']'
				);

		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$oid);

	}
	
	public function update_doc_date()
	{
		$oid=$this->input->post('oid');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		
		$doc_name_id=$this->input->post('doc_name_id');
		$datepicker_opddate=str_to_MysqlDate($this->input->post('datepicker_opddate'));
		$doc_opd_fee=$this->input->post('opd_fee_amt');
			
		
		$sql="select *  from  opd_master  where opd_id=".$oid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		$sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$doc_name_id." group by d.id ";
        $query = $this->db->query($sql);
        $data['doc_info']= $query->result();
		
		$opd_fee_amount=$doc_opd_fee-$data['opd_master'][0]->opd_discount;
		
		$dataupdate = array( 
				'doc_id' => $doc_name_id,
				'doc_name' => $data['doc_info'][0]->p_fname,
				'doc_spec' => $data['doc_info'][0]->SpecName,
				'apointment_date' => $datepicker_opddate,
				'opd_fee_gross_amount'=>$doc_opd_fee,
				'opd_fee_amount'=>$opd_fee_amount
				);
		
		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$oid);
	
		$sql="select *  from  opd_master  where opd_id=".$oid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
	
		$sql="select * from payment_history where payof_type=1 and payof_id=".$oid." and id=".$data['opd_master'][0]->payment_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		if(count($payment_history)>0)
		{
			$amount_diff=$payment_history[0]->amount-$opd_fee_amount;
			if($amount_diff<>0)
			{
				$pay_remark=$payment_history[0]->remark." /Update OPD Rate Difference ".$amount_diff." :By ".$user_name;
		
				$paydata = array( 
							'amount'=>$data['opd_master'][0]->opd_fee_amount,
							'remark'=>$pay_remark,
					);
				$this->load->model('Payment_M');
				$this->Payment_M->update($paydata,$payment_history[0]->id);
			}
			
		}
			
	}

	public function opd_load_doc($opdid)
	{
		$data['opdid']=$opdid;

		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
			GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['opd_master'][0]->apointment_date."')  AS age 
			from patient_master where id='".$data['opd_master'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$this->load->view('Person/OPD_file_upload',$data);
	}
	
	
	public function save_image($opdid)
	{
		$filename =  'OPD-'.$opdid.'-'.time() . '.jpg';

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
			
			$sql="select * from opd_master  where opd_id=".$opdid;
			$query = $this->db->query($sql);
			$opd_master= $query->result();

			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			if(count($opd_master)>0)
			{
				$pid=$opd_master[0]->p_id;
				$org_id=$opd_master[0]->insurance_case_id;
			}

			$udata = array( 
					'upload_by'=> $user_name,
					'pid'=>$pid,
					'opd_id'=>$opdid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'upload_by_id'=>$user_id
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
					
				$user_name = $user->first_name.''. $user->last_name.':T-'.date('d-m-Y h:m:s');
		
				$opdstatus=2;
				
				if($opdstatus=='2')
				{
					$status_str='Visit Done';
				}elseif($opdstatus=='3')
				{
					$status_str='Visit Cancel';
				}
	
				$opd_status_remark=$status_str.' Update By:'.$user_name;
				
				$dataupdate = array( 
				'opd_status' => $opdstatus,
				'opd_status_remark' => $opd_status_remark
				);

				$this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$opdid);
			}
			
			echo $filename;
		}
	}
	public function save_image_mobile($opdid)
	{
		$filename =  'OPD-'.$opdid.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
		
		
		$user_id = '0';
		$user_name = 'Mobile';
				
		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		if (!$this->upload->do_upload('userfile')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
			
			//print_r($data);

			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
			
			$sql="select * from opd_master  where opd_id=".$opdid;
			$query = $this->db->query($sql);
			$opd_master= $query->result();

			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			if(count($opd_master)>0)
			{
				$pid=$opd_master[0]->p_id;
				$org_id=$opd_master[0]->insurance_case_id;
			}

			$udata = array( 
					'upload_by'=> $user_name,
					'pid'=>$pid,
					'opd_id'=>$opdid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'upload_by_id'=>$user_id
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
				
				$opdstatus=2;
				
				if($opdstatus=='2')
				{
					$status_str='Visit Done';
				}elseif($opdstatus=='3')
				{
					$status_str='Visit Cancel';
				}
	
				$opd_status_remark=$status_str.' Update By:'.$user_name;
				
				$dataupdate = array( 
				'opd_status' => $opdstatus,
				'opd_status_remark' => $opd_status_remark
				);

				$this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$opdid);
			}
			
			echo 'File Upload successfully';
		}
	}

	public function save_image_mobile_bak($opdid)
	{
		$filename =  'OPD-'.$opdid.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
		
		
		$user_id = '0';
		$user_name = 'Mobile';
				
		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		$img = $_POST['userfile'];   
		$img = str_replace('data:image/jpeg;base64,', '', $img);   
		$img = str_replace(' ', '+', $img);   
		$data = base64_decode($img);

		$file = $folder_name . '.jpeg';

		$success = file_put_contents($file, $data);   

		print $success ? $file : 'Unable to save the file.'; 
		
		if (!$success) {
			$data = array('upload_data' => $this->upload->data()); 
			print_r($data);

			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
					
			print_r($data);

			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
			
			$sql="select * from opd_master  where opd_id=".$opdid;
			$query = $this->db->query($sql);
			$opd_master= $query->result();

			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			if(count($opd_master)>0)
			{
				$pid=$opd_master[0]->p_id;
				$org_id=$opd_master[0]->insurance_case_id;
			}

			$udata = array( 
					'upload_by'=> $user_name,
					'pid'=>$pid,
					'opd_id'=>$opdid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'upload_by_id'=>$user_id
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
				
				$opdstatus=2;
				
				if($opdstatus=='2')
				{
					$status_str='Visit Done';
				}elseif($opdstatus=='3')
				{
					$status_str='Visit Cancel';
				}
	
				$opd_status_remark=$status_str.' Update By:'.$user_name;
				
				$dataupdate = array( 
				'opd_status' => $opdstatus,
				'opd_status_remark' => $opd_status_remark
				);

				$this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$opdid);
			}
			
			echo 'File Upload successfully';
		}
	}
	
	public function opd_file_hide($file_id)
	{
		$user = $this->ion_auth->user()->row();
		$upload_by_id = $user->id;
		
		$udata = array( 
				'show_type'=> '1',
				'upload_by_id'=>$upload_by_id
				);
		$this->load->model('File_M');
		
		$this->File_M->update($udata,$file_id);

		redirect('/Opd/opd_file_last_list');
	}
	
	public function opd_file_last_list($opdid='')
	{
		$user = $this->ion_auth->user()->row();
		$upload_by_id = $user->id;
		
		if($opdid=='')
		{
			$sql="select f.id,f.full_path,date_format(f.insert_date,'%d/%m/%Y-%H:%i') as strinsert_date,
			f.file_ext,p.p_fname,p.str_age from file_upload_data f join patient_master_exten p on f.pid=p.id 
			where show_type=0 and upload_by_id=".$upload_by_id." order by id desc limit 5";
		}else{
			$sql="select f.id,f.full_path,date_format(f.insert_date,'%d/%m/%Y-%H:%i') as strinsert_date,
			f.file_ext,p.p_fname,p.str_age from file_upload_data f join patient_master_exten p on f.pid=p.id 
			where show_type=0 and opd_id=$opdid and upload_by_id=$upload_by_id order by id desc limit 5";
		}
		
		$query = $this->db->query($sql);
		$data['opd_file_list']= $query->result();
		
		$this->load->view('Person/scan_opd_list',$data);
	}
	
	public function opd_file_list($opdid)
	{
		$sql="select * from file_upload_data where show_type=0 and opd_id=".$opdid;
		$query = $this->db->query($sql);
		$data['opd_file_list']= $query->result();

		$sql="select * from file_opd_rec where opd_id=".$opdid;
		$query = $this->db->query($sql);
		$file_opd_rec= $query->result();

		foreach($file_opd_rec as $row)
		{
			$full_path=$row->full_path;
			$file_v_no=$row->v_no;

            $start_no=stripos($full_path,$file_v_no);
			$end_no=strlen($full_path);
			
			$folder_name=substr($full_path,0,$start_no);

			$files_info = get_dir_file_info($folder_name);
			
			foreach($files_info as $key => $value) {
				$find_file=number_format(substr_count($value['name'],$file_v_no),0);
				if($find_file==1)
				{
					$index_key=str_replace($file_v_no.'_','',$value['name']);
					$index_key=str_replace('.webm','',$index_key);

					$file_list[$index_key] = $value['name'];
				}
			}

			ksort($file_list);

			$prev_file='';
			$current_file='';
			foreach($file_list as $key => $value) {
				$current_file=$folder_name.'/'.$value;
				if(strlen($prev_file)>0)
				{
					//First File
					$objFH = fopen( $prev_file, "rb" );
					$strBuffer1 = fread( $objFH, filesize( $prev_file) );
					fclose( $objFH );
		
					//Second File
					$objFH = fopen( $current_file, "rb" );
					$strBuffer2 = fread( $objFH, filesize( $current_file) );
					fclose( $objFH );

					// manipulate buffers here...
					$strBuffer3 = $strBuffer1 . $strBuffer2;

					// open for write/binary-safe
					$objFH = fopen( $current_file, "wb" );
					fwrite( $objFH, $strBuffer3 );
					fclose( $objFH );

					//Delete prev File
					unlink($prev_file);

					$prev_file=$current_file;

					$data_upload = array( 
						'opd_id' => $opd_id, 
						'full_path' => $current_file,
						); 
				
					$this->load->model('FileOPDRec_M');
				
					$this->FileOPDRec_M->insert($data_upload,$file_v_no);
				}
			 
			}



		}

        $sql="select * from file_opd_rec where opd_id=".$opdid;
		$query = $this->db->query($sql);
		$data['file_opd_rec']= $query->result();

		$this->load->view('Person/opd_file_show',$data);

	}
	
	public function opd_file_upload($opdid)
	{
		$data['opdid']=$opdid;
				
		$this->load->view('Person/opd_scanfile_upload',$data);
	}
	
	public function opd_scanfiles_upload($opdid)
	{
		
		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$output_dir = $folder_name;

		if(isset($_FILES["myfile"]))
		{
			$ret = array();

			$error = $_FILES["myfile"]["error"];

			// upload single file
			if(!is_array($_FILES["myfile"]["name"])) //single file
			{
				$filename =  'OPD-'.$opdid.'-'.time() .$_FILES["myfile"]["name"];
				$config['upload_path'] = $folder_name;
				$config['file_name'] = $filename;
				$config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc';
				
				$this->load->library('upload', $config);
				echo $filename;
				//move_uploaded_file($_FILES["myfile"]["tmp_name"],$output_dir.$fileName);
				
				if (!$this->upload->do_upload('myfile')) {
					$error = array('error' => $this->upload->display_errors());
					echo $error['error'];
				}else{
					$data = array('upload_data' => $this->upload->data()); 
					$this->load->model('File_M');
					$file_insert_id=$this->File_M->insert($data['upload_data']);
					
					$sql="select * from opd_master  where opd_id=".$opdid;
					$query = $this->db->query($sql);
					$opd_master= $query->result();
								
					$pid=0;
					$ipd_id=0;
					$org_id=0;
					
					if(count($opd_master)>0)
					{
						$pid=$opd_master[0]->p_id;
						$org_id=$opd_master[0]->insurance_case_id;
					}

					$udata = array( 
							'opd_id'=>$opdid,
							'pid'=>$pid,
							'ipd_id'=>$ipd_id,
							'case_id'=>$org_id
							);

					if($file_insert_id>0)
					{
						$this->File_M->update($udata,$file_insert_id);
					}
					
				}
				
				$ret[]= $_FILES["myfile"]["name"];
			}
			else
			{
				// Handle Multiple files
				$fileCount = count($_FILES["myfile"]["name"]);
				for($i=0; $i<$fileCount; $i++)
				{
					$filename =  'OPD-'.$opdid.'-'.$i.'-'.time() .$_FILES["myfile"]["name"];
				
					//$fileName = $_FILES["myfile"]["name"][$i];
					//move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$output_dir.$fileName);
					
					if (!$this->upload->do_upload($_FILES["myfile"]["tmp_name"][$i])) {
						$error = array('error' => $this->upload->display_errors());
						echo $error['error'];
					}else{
						$data = array('upload_data' => $this->upload->data()); 
						$this->load->model('File_M');
						$file_insert_id=$this->File_M->insert($data['upload_data']);
						
						$sql="select * from opd_master  where opd_id=".$opdid;
						$query = $this->db->query($sql);
						$opd_master= $query->result();
									
						$pid=0;
						$ipd_id=0;
						$org_id=0;
						
						if(count($opd_master)>0)
						{
							$pid=$opd_master[0]->p_id;
							$ipd_id=$opd_master[0]->ipd_id;
							$org_id=$opd_master[0]->insurance_case_id;
						}

						$udata = array( 
							'upload_by'=> $user_name,
							'pid'=>$pid,
							'opd_id'=>$opdid,
							'ipd_id'=>$ipd_id,
							'case_id'=>$org_id
							);

						if($file_insert_id>0)
						{
							$this->File_M->update($udata,$file_insert_id);
						}
					}
					
					$ret[]= $_FILES["myfile"]["name"][$i];
				}
			}
			// output file names as comma seperated strings to display status
			echo json_encode($ret);
		}
	}

	public function opd_status($opdid,$opdstatus)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.':T-'.date('d-m-Y h:m:s');
		
		$status_str="";
		
		if($opdstatus=='2')
		{
			$status_str='Visit Done';
		}elseif($opdstatus=='3')
		{
			$status_str='Visit Cancel';
		}
		
		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$dataupdate = array( 
				'opd_status' => $opdstatus,
				'opd_status_remark' => $opd_status_remark
				);

		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$opdid);
	}
	
	public function get_appointment($opd_date='')
	{
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');

		}
		
		$data["opd_date"]=$opd_date;
		
		$sql="select group_concat(DISTINCT o.doc_id ) as doc_list_opd
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id and m.id=s.med_spec_id)
			join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."'";
		$query = $this->db->query($sql);
        $doc_opd= $query->result();
		
		if(count($doc_opd)>0)
		{
			$doc_opd_list=$doc_opd[0]->doc_list_opd;
		}else{
			$doc_opd_list="0";
		}
		
		if($doc_opd_list=='')
		{
			$doc_opd_list="0";
		}
				
		$sql="select d.id as doc_id, d.p_fname,count(DISTINCT o.opd_id) as No_opd, 
			group_concat(DISTINCT m.SpecName) as Spec, 
			count( DISTINCT CASE o.opd_status WHEN   1 THEN  o.opd_id END) as count_wait,
			count( DISTINCT CASE o.opd_status WHEN   2 THEN  o.opd_id END) as count_visit,
			count( DISTINCT CASE o.opd_status WHEN   3 THEN  o.opd_id END) as count_cancel,
			MOD(d.id,5) as color_code
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id and m.id=s.med_spec_id)
			left join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."' 
			where o.doc_id in (".$doc_opd_list.")
			group by d.p_fname having count(DISTINCT o.opd_id)>0 ";
        $query = $this->db->query($sql);
        $data['doc_master']= $query->result();
				

		$data['color']=array( 
				'0' => 'bg-yellow',
				'1' => 'bg-red',
				'2' => 'bg-green',
				'3' => 'bg-gray',
				'4' => 'bg-orange',
				'5' => 'bg-blue'
			);
		$data['content']=$this->load->view('dashboard/opd_appointment_panel',$data,True);

		$this->load->view('dashboard/appointment',$data);
	}

	public function get_appointment_data($opd_date='')
	{
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');

		}
		
		$data["opd_date"]=$opd_date;
		
		$sql="select group_concat(DISTINCT o.doc_id ) as doc_list_opd
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id and m.id=s.med_spec_id)
			join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."'";
		$query = $this->db->query($sql);
        $doc_opd= $query->result();
		
		if(count($doc_opd)>0)
		{
			$doc_opd_list=$doc_opd[0]->doc_list_opd;
		}else{
			$doc_opd_list="0";
		}
		
		if($doc_opd_list=='')
		{
			$doc_opd_list="0";
		}
				
		$sql="select d.id as doc_id, d.p_fname,count(DISTINCT o.opd_id) as No_opd, 
			group_concat(DISTINCT m.SpecName) as Spec, 
			count( DISTINCT CASE o.opd_status WHEN   1 THEN  o.opd_id END) as count_wait,
			count( DISTINCT CASE o.opd_status WHEN   2 THEN  o.opd_id END) as count_visit,
			count( DISTINCT CASE o.opd_status WHEN   3 THEN  o.opd_id END) as count_cancel,
			MOD(d.id,5) as color_code
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id and m.id=s.med_spec_id)
			left join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."' 
			where o.doc_id in (".$doc_opd_list.")
			group by d.p_fname having count(DISTINCT o.opd_id)>0 ";
        $query = $this->db->query($sql);
        $data['doc_master']= $query->result();
				

		$data['color']=array( 
				'0' => 'bg-yellow',
				'1' => 'bg-red',
				'2' => 'bg-green',
				'3' => 'bg-gray',
				'4' => 'bg-orange',
				'5' => 'bg-blue'
				);
		$this->load->view('dashboard/opd_appointment_panel',$data);
	}
	
	
	public function opd_display($doc_id=1)
	{
		$opd_date=date('Y-m-d');
		
		$data["opd_date"]=$opd_date;

		$sql="	SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,
				group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
				FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
				 JOIN opd_prescription m ON o.opd_id=m.opd_id)
				LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
				WHERE  ( o.apointment_date =  '".$opd_date."' )  
				and o.opd_status=1 and  o.doc_id ='".$doc_id."'
				GROUP BY o.opd_id
				ORDER BY opd_id  ";
		
		$query = $this->db->query($sql);
        $data['opd_list_1']= $query->result();

		$doc_id=2;

		$sql="	SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,
				group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
				FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
				 JOIN opd_prescription m ON o.opd_id=m.opd_id)
				LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
				WHERE  ( o.apointment_date =  '".$opd_date."' )  
				and o.opd_status=1 and  o.doc_id ='".$doc_id."'
				GROUP BY o.opd_id
				ORDER BY opd_id  ";
		
		$query = $this->db->query($sql);
        $data['opd_list_2']= $query->result();

		$data['color']=array();

		$sql="select id,color_name,code_code from color";
		$query = $this->db->query($sql);
		$color= $query->result();
		
		foreach($color as $aRow)
		{
			array_push($data['color'],$aRow->code_code);
		}
		
		$data['color1']=array( 
				'0' => 'bg-yellow',
				'1' => 'bg-red',
				'2' => 'bg-green',
				'3' => 'bg-gray',
				'4' => 'bg-orange',
				'5' => 'bg-blue'
				);

		$this->load->view('dashboard/opd_display',$data);
	}

	public function opd_cancel($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str=$this->input->post('input_remark');

		$opd_status_remark=$status_str.' [Update By:'.$user_name.']';
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount 
		from payment_history where payof_type=1 and payof_id=".$opdid;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from opd_master 
		where opd_id=".$opdid;
		$query = $this->db->query($sql);
        $opd_master= $query->result();
		
		$sql="select * from refund_order 
		where refund_process=0 and refund_type=1 and refund_type_id=".$opdid;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		$insert_id=0;

		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $opd_master[0]->opd_status==1)
			{
				$RefundRequest = array( 
				'refund_type' => 1,
				'refund_type_id' => $opdid,
				'refund_type_code' => $opd_master[0]->opd_code,
				'refund_type_reason' => 'Cancel OPD',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount
				);
				
				$this->load->model('Payment_M');
				$insert_id=$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}else{
			$insert_id=1;
		}
		
		$dataupdate = array( 
				'opd_status' => '3',
				'opd_status_remark' => $opd_status_remark
			);

		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$opdid);

		echo $insert_id;
		
	}

	public function opd_crorg($opdid,$org_code_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=1 and payof_id=".$opdid;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from opd_master where opd_id=".$opdid;
		$query = $this->db->query($sql);
        $opd_master= $query->result();
		
		$sql="select * from refund_order where refund_process=0 and refund_type=1 and refund_type_id=".$opdid;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $opd_master[0]->opd_status==1)
			{
				$RefundRequest = array( 
				'refund_type' => 1,
				'refund_type_id' => $opdid,
				'refund_type_code' => $opd_master[0]->opd_code,
				'refund_type_reason' => 'Cr. to Org.',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}
		
		$dataupdate = array( 
				'payment_mode' => 3,
				'payment_id' => 0,
				'insurance_case_id' => $org_code_id,
				);

		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$opdid);
	}

	public function opd_discount_update($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_discount=$this->input->post('input_dis_amt');
		$opd_disc_remark=$this->input->post('input_dis_desc');

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=1 and payof_id=".$opdid;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from opd_master where opd_id=".$opdid;
		$query = $this->db->query($sql);
        $opd_master= $query->result();
		
		$sql="select * from refund_order where refund_process=0 and refund_type=1 and refund_type_id=".$opdid;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			$opd_fee_amount=$opd_master[0]->opd_fee_gross_amount-$opd_discount;
			
			if($payment_history[0]->paid_amount>0 && $opd_master[0]->opd_status==1 && $opd_fee_amount>=0 && $opd_discount>0)
			{
				$RefundRequest = array( 
				'refund_type' => 1,
				'refund_type_id' => $opdid,
				'refund_type_code' => $opd_master[0]->opd_code,
				'refund_type_reason' => 'Discount',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $opd_discount,
				'patient_id' => $opd_master[0]->p_id,
				'patient_name' =>$opd_master[0]->P_name
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
			
			if($opd_fee_amount>=0)
			{
				$dataupdate = array( 
					'opd_discount' => $opd_discount,
					'opd_disc_remark' => $opd_disc_remark,
					'opd_fee_amount' => $opd_fee_amount,
					'opd_disc_update_by'=>$user_name.'['.$user_id.']'
					);

				$this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$opdid);
			}
		}
	}
	
	public function scan_opd_desktop()
	{
		$this->load->view('Person/opd_scan_desktop');
	}
	
	public function search_scan_opd()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9 _\-]/', '', trim($sdata));

		if(is_numeric($sdata))
		{
			$where="   (mphone1 = '".$sdata."' or udai='".$sdata."' or p_code like '%".$sdata."')" ;
		}elseif (filter_var($sdata, FILTER_VALIDATE_EMAIL)) {
			$where="   email1 = '".$sdata."'" ;
		}else{
			$where="  (p_fname  like '%".$sdata."%' or
					SUBSTRING_INDEX(p_fname,' ',1) sounds like '".$sdata."')";
		}
            
		$sql = "SELECT p.*,o.opd_id,
				o.doc_name,o.opd_code,Date_Format(o.opd_book_date,'%d-%m-%Y') as App_Date, 
				if(MAX(date(o.opd_book_date))=curdate(),Date_Format(MAX(o.opd_book_date),'%d-%m-%Y %H:%i'),0) as opd_today 
				FROM (patient_master_exten p JOIN opd_master o ON p.id=o.p_id) 
				WHERE ".$where."   Group by  o.opd_id
				order by  o.opd_id desc ";
		

		$query = $this->db->query($sql);
        $data['opd_list']= $query->result();

        $this->load->view('Person/opd_scan_search',$data);
	}
	
	
	
	public function SelectOPD($opdid)
	{
				
        $sql = "select  o.opd_id, o.doc_name,o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
		p.p_code,p.mphone1,p.email1 ,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,
		m.mode_desc as PaymentMode,o.payment_mode
		from (opd_master o join patient_master p on o.p_id=p.id ) join payment_mode m on o.payment_mode=m.id
		WHERE o.opd_id=".$opdid;

		$query = $this->db->query($sql);
        $opd_data= $query->result();

        if(count($opd_data)>0)
		{
			echo '<B>OPD Code :</b>'.$opd_data[0]->opd_code ;
			echo '  <B>Doctor Name :</b> Dr.'.$opd_data[0]->doc_name ;
			echo '  <B>OPD Date :</b>'.$opd_data[0]->App_Date ;
			echo '  <B>Patient Name :</b>'.$opd_data[0]->P_name ;
			echo '  <B>Patient Code :</b>'.$opd_data[0]->p_code ;
		}
	}

	public function get_appointment_list($doc_id,$opd_date='')
	{
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');
		}
		
		$data["opd_date"]=$opd_date;

		$data["doc_id"]=$doc_id;
		
        $sql="select d.p_fname,count(DISTINCT o.opd_id) as No_opd, d.id as doc_id, 
				group_concat(DISTINCT m.SpecName) as Spec, 
				count( DISTINCT CASE o.opd_status WHEN   1 THEN  o.opd_id END) as count_wait,
				count( DISTINCT CASE o.opd_status WHEN   2 THEN  o.opd_id END) as count_visit,
				count( DISTINCT CASE o.opd_status WHEN   3 THEN  o.opd_id END) as count_cancel,
				MOD(d.id,5) as color_code
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id 
			and m.id=s.med_spec_id)left join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."'
			where  d.id ='".$doc_id."' group by d.id";
		
        $query = $this->db->query($sql);
        $data['doc_master']= $query->result();

		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,p.p_fname as P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			LEFT JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1 
			WHERE  m.id is null  and ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id ORDER BY opd_code desc ";

		$query = $this->db->query($sql);
        $data['opd_list_0']= $query->result();
		
		$sql="	SELECT o.opd_id,o.opd_code,p.id,p.p_code,p.p_fname as P_name,m.queue_no,o.opd_fee_desc,p.p_rname,
			m.current_queue_no,m.id,o.opd_fee_amount			
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_id  ";
		
		$query = $this->db->query($sql);
        $data['opd_list_1']= $query->result();
				
		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,p.p_fname as P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,o.opd_fee_amount,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )   
			and o.opd_status=2 and o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_code  ";
					
		$query = $this->db->query($sql);
        $data['opd_list_2']= $query->result();
		
		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,p.p_fname as P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,o.opd_fee_amount,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=3 and o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_code  ";
				
		$query = $this->db->query($sql);
        $data['opd_list_3']= $query->result();

		$sql="select id,color_name,code_code from color";
		$query = $this->db->query($sql);
		$color= $query->result();
		
		$data['color']=array();
		
		foreach($color as $aRow)
		{
			array_push($data['color'],$aRow->code_code);
		}
		
		$data['color1']=array( 
				'0' => 'bg-yellow',
				'1' => 'bg-red',
				'2' => 'bg-green',
				'3' => 'bg-gray',
				'4' => 'bg-orange',
				'5' => 'bg-blue'
			);

		$data['doc_master_panel']=$this->load->view('dashboard/opd_queue_status_total',$data,true);
		$data['tab0']=$this->load->view('dashboard/opd_queue_status_tab_0',$data,true);
		$data['tab1']=$this->load->view('dashboard/opd_queue_status_tab_1',$data,true);
		$data['tab2']=$this->load->view('dashboard/opd_queue_status_tab_2',$data,true);
		$data['tab3']=$this->load->view('dashboard/opd_queue_status_tab_3',$data,true);
	

		$this->load->view('dashboard/opd_list_doc',$data);
	}

	
	public function update_appointment_panel_bak()
	{
		$doc_id=$this->input->post('doc_id');
        $opd_date=$this->input->post('opd_date');
        
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');
		}
			
		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			LEFT JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1 
			WHERE  m.id is null  and ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id ORDER BY opd_code desc ";

		$query = $this->db->query($sql);
        $opd_list_0= $query->result();
		
		$sql="	SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,p.p_rname,
			group_concat('ID:',h.id,'/M:',(case h.payment_mode when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_id desc ";
		
		$query = $this->db->query($sql);
        $opd_list_1= $query->result();
				
		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			 JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )   
			and o.opd_status=2 and o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_code  desc";
					
		$query = $this->db->query($sql);
        $opd_list_2= $query->result();
		
		$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,
			group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending' else 'Other' end) separator ',') AS `Paymode`
			FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
			JOIN opd_prescription m ON o.opd_id=m.opd_id)
			LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=3 and o.doc_id ='".$doc_id."'
			GROUP BY o.opd_id
			ORDER BY opd_code desc ";
				
		$query = $this->db->query($sql);
        $opd_list_3= $query->result();

        $sql="select d.p_fname,count(DISTINCT o.opd_id) as No_opd, d.id as doc_id, 
				group_concat(DISTINCT m.SpecName) as Spec, 
				count( DISTINCT CASE o.opd_status WHEN   1 THEN  o.opd_id END) as count_wait,
				count( DISTINCT CASE o.opd_status WHEN   2 THEN  o.opd_id END) as count_visit,
				count( DISTINCT CASE o.opd_status WHEN   3 THEN  o.opd_id END) as count_cancel,
				MOD(d.id,5) as color_code
			from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id 
			and m.id=s.med_spec_id)left join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."'
			where  d.id ='".$doc_id."' group by d.id";
		
        $query = $this->db->query($sql);
        $doc_master= $query->result();

		$sql="select id,color_name,code_code from color";
		$query = $this->db->query($sql);
		$color= $query->result();
		
		$data['color']=array();
		
		foreach($color as $aRow)
		{
			array_push($data['color'],$aRow->code_code);
		}
		
		$data['color1']=array( 
				'0' => 'bg-yellow',
				'1' => 'bg-red',
				'2' => 'bg-green',
				'3' => 'bg-gray',
				'4' => 'bg-orange',
				'5' => 'bg-blue'
				);

		//Doctor Panel
		$doc_master_panel='<ul class="list-group list-group-unbordered" >
							<li class="list-group-item">
						  		<b>On Waiting </b> <a class="pull-right">'.$doc_master[0]->count_wait.'</a>
							</li>
							<li class="list-group-item">
						  		<b>No. of Visited</b> <a class="pull-right">'.$doc_master[0]->count_visit.'</a>
							</li>';
		if($doc_master[0]->count_cancel>0) { 
		$doc_master_panel.='<li class="list-group-item">Canceled 
								<span class="pull-right badge bg-red">'.$doc_master[0]->count_cancel.'</span>
							</li>';
		}
			
		$doc_master_panel.='<li class="list-group-item">
					  			<b>Total</b> <a class="pull-right">'.$doc_master[0]->No_opd.'</a>
							</li>
						</ul>';
		
		//Master View
		$Master_View='<div class="box">
							<div class="box-header">
							  <h3 class="box-title">##HEAD_NAME##</h3>
							</div>
							<!-- /.box-header -->
							<div class="box-body no-padding">
								<table class="table table-condensed">
									##CONTENT##
								</table>
							</div>
						</div>';

		//On Booking List
		$tab0='';
			$srno=0;
			foreach($opd_list_0 as $row)
			{
				$tab0.='<tr>
						  <td style="width: 10px">'.$row->opd_id.'</td>
							<td style="width: 10px">'.$row->opd_code.'</td>
							<td>'.$row->P_name.'</td>
							<td>'.$row->Paymode.'</td>
							<td>';
							if($row->payment_status==1) {
								$tab0.='<button  type="button" 	class="btn btn-primary"  
										onclick="Opd_create_queue('.$row->opd_id .')">Queue</button>
										<button  type="button" 	class="btn btn-primary" data-toggle="modal"
											data-target="#tallModal" 
											data-opdid="'.$row->opd_id.'" data-etype="3" >Cancel OPD</button>';
							}else{
								$tab0.='<a href="javascript:load_form(\'/Opd/invoice/'.$row->opd_id.'/0\');">
										<i class="fa fa-dashboard"></i> Go For Payment</a></p>';
							}
				$tab0.='</td>
							<td>
							</td>
						</tr>';
			}


		$Master_View_head=str_replace("##HEAD_NAME##","On Booking List",$Master_View);
		$tab0=str_replace("##CONTENT##",$tab0,$Master_View_head);

		//OnWaiting List

		$tab1='';
			$srno=0;
			foreach($opd_list_1 as $row)
			{
			$tab1.='<tr>
						<td style="width: 10px">'.$row->queue_no.'</td>
						<td style="width: 10px">'.$row->opd_code.'</td>
						<td>'.$row->P_name.'</td>
						<td>'.$row->opd_fee_desc.'/'.$row->Paymode.'</td>
						<td>
						<button  type="button" 	class="btn btn-default" 
						Onclick="Opd_Prescription('.$row->opd_id .')" ><img src="/assets/images/icon/prescription.png" class="img_icon"  /></button>
						<button  type="button" 	class="btn btn-default" data-toggle="modal"
						data-target="#tallModal" 
						data-opdid="'.$row->opd_id .'" data-etype="1" ><img src="/assets/images/icon/iball_scan.png" class="img_icon"  /></button>
						<button  type="button" 	class="btn btn-default" data-toggle="modal"
						data-target="#tallModal" 
						data-opdid="'.$row->opd_id .'" data-etype="3" ><img src="/assets/images/icon/upload_scan_img.png" class="img_icon"  /></button>
						<button  type="button" 	class="btn btn-default" data-toggle="modal"
							data-target="#tallModal" 
							data-opdid="'.$row->opd_id .'" data-etype="2" >
							<img src="/assets/images/icon/medical_profile.png" class="img_icon"  />
						</button>
						<button  type="button" class="btn btn-default" onclick="update_status(<?=$row->opd_id?>,2)" >
<img src="/assets/images/icon/update_status.png" class="img_icon" /></button>
</td>
</tr>';
}

$Master_View_head=str_replace("##HEAD_NAME##","On Waiting List",$Master_View);
$tab1=str_replace("##CONTENT##",$tab1,$Master_View_head);

//Visited List
$tab2='';

$srno=0;
foreach($opd_list_2 as $row)
{
$tab2.='<tr>
    <td style="width: 10px">'.$row->opd_id.'</td>
    <td style="width: 10px">'.$row->opd_code.'</td>
    <td>'.$row->P_name.'</td>
    <td>'.$row->opd_fee_desc.'/'.$row->Paymode.'</td>
    <td>
        <button type="button" class="btn btn-default" Onclick="Opd_Prescription('.$row->opd_id .')"><img
                src="/assets/images/icon/prescription.png" class="img_icon" /></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="1"><img src="/assets/images/icon/iball_scan.png"
                class="img_icon" /></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="3"><img src="/assets/images/icon/upload_scan_img.png"
                class="img_icon" /></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="2">
            <img src="/assets/images/icon/medical_profile.png" class="img_icon" />
        </button>
    </td>
</tr>';
}

$Master_View_head=str_replace("##HEAD_NAME##","Visited List",$Master_View);
$tab2=str_replace("##CONTENT##",$tab2,$Master_View_head);

//Cancel List
$tab3='';
$srno=0;
foreach($opd_list_3 as $row)
{
$tab3.='<tr>
    <td style="width: 10px">'.$row->opd_id.'</td>
    <td style="width: 10px">'.$row->opd_code.'</td>
    <td>'.$row->P_name.'</td>
    <td>'.$row->opd_fee_desc.'/'.$row->Paymode.'</td>
    <td><button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="1"><img src="/assets/images/icon/iball_scan.png"
                class="img_icon" /></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="3"><img src="/assets/images/icon/upload_scan_img.png"
                class="img_icon" /></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#tallModal"
            data-opdid="'.$row->opd_id.'" data-etype="2"><img src="/assets/images/icon/medical_profile.png"
                class="img_icon" />
            << /button>
    </td>
</tr>';
}

$Master_View_head=str_replace("##HEAD_NAME##","Visited List",$Master_View);
$tab3=str_replace("##CONTENT##",$tab3,$Master_View_head);

$rvar=array(
'update' =>1,
'doc_master_panel'=> $doc_master_panel,
'tab0' => $tab0,
'tab1' => $tab1,
'tab2' => $tab2,
'tab3' => $tab3,
);

$encode_data = json_encode($rvar);
echo $encode_data;

}

public function update_appointment_panel()
{
$doc_id=$this->input->post('doc_id');
$opd_date=$this->input->post('opd_date');

if($opd_date=='')
{
$opd_date=date('Y-m-d');
}

$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,
group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending'
else 'Other' end) separator ',') AS `Paymode`
FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
LEFT JOIN opd_prescription m ON o.opd_id=m.opd_id)
LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
WHERE m.id is null and ( o.apointment_date = '".$opd_date."' )
and o.opd_status=1 and o.doc_id ='".$doc_id."'
GROUP BY o.opd_id ORDER BY opd_code desc ";

$query = $this->db->query($sql);
$data['opd_list_0']= $query->result();

$sql=" SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,p.p_rname,
m.current_queue_no,m.id,o.opd_fee_amount
FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
JOIN opd_prescription m ON o.opd_id=m.opd_id)
LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
WHERE ( o.apointment_date = '".$opd_date."' )
and o.opd_status=1 and o.doc_id ='".$doc_id."'
GROUP BY o.opd_id
ORDER BY opd_id desc ";

$query = $this->db->query($sql);
$data['opd_list_1']= $query->result();

$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,o.opd_fee_amount,
group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending'
else 'Other' end) separator ',') AS `Paymode`
FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
JOIN opd_prescription m ON o.opd_id=m.opd_id)
LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
WHERE ( o.apointment_date = '".$opd_date."' )
and o.opd_status=2 and o.doc_id ='".$doc_id."'
GROUP BY o.opd_id
ORDER BY opd_code desc";

$query = $this->db->query($sql);
$data['opd_list_2']= $query->result();

$sql="SELECT o.opd_id,o.opd_code,p.id,p.p_code,o.P_name,m.queue_no,o.opd_fee_desc,o.payment_status,p.p_rname,o.opd_fee_amount,
group_concat('ID:',`h`.`id`,'/M:',(case `h`.`payment_mode` when 1 then 'Cash' when 2 then 'BANK ' when 0 then 'Pending'
else 'Other' end) separator ',') AS `Paymode`
FROM ((opd_master o JOIN patient_master p ON o.p_id=p.id)
JOIN opd_prescription m ON o.opd_id=m.opd_id)
LEFT JOIN payment_history h ON o.opd_id=h.payof_id AND h.payof_type=1
WHERE ( o.apointment_date = '".$opd_date."' )
and o.opd_status=3 and o.doc_id ='".$doc_id."'
GROUP BY o.opd_id
ORDER BY opd_code desc ";

$query = $this->db->query($sql);
$data['opd_list_3']= $query->result();

$sql="select d.p_fname,count(DISTINCT o.opd_id) as No_opd, d.id as doc_id,
group_concat(DISTINCT m.SpecName) as Spec,
count( DISTINCT CASE o.opd_status WHEN 1 THEN o.opd_id END) as count_wait,
count( DISTINCT CASE o.opd_status WHEN 2 THEN o.opd_id END) as count_visit,
count( DISTINCT CASE o.opd_status WHEN 3 THEN o.opd_id END) as count_cancel,
MOD(d.id,5) as color_code
from (doctor_master d join doc_spec s join med_spec m on d.id=s.doc_id
and m.id=s.med_spec_id)left join opd_master o on d.id=o.doc_id and o.apointment_date='".$opd_date."'
where d.id ='".$doc_id."' group by d.id";

$query = $this->db->query($sql);
$data['doc_master']= $query->result();

$sql="select id,color_name,code_code from color";
$query = $this->db->query($sql);
$color= $query->result();

$data['color']=array();

foreach($color as $aRow)
{
array_push($data['color'],$aRow->code_code);
}

$data['color1']=array(
'0' => 'bg-yellow',
'1' => 'bg-red',
'2' => 'bg-green',
'3' => 'bg-gray',
'4' => 'bg-orange',
'5' => 'bg-blue'
);

$doc_master_panel=$this->load->view('dashboard/opd_queue_status_total',$data,true);
$tab0=$this->load->view('dashboard/opd_queue_status_tab_0',$data,true);
$tab1=$this->load->view('dashboard/opd_queue_status_tab_1',$data,true);
$tab2=$this->load->view('dashboard/opd_queue_status_tab_2',$data,true);
$tab3=$this->load->view('dashboard/opd_queue_status_tab_3',$data,true);

$rvar=array(
'update' =>1,
'doc_master_panel'=> $doc_master_panel,
'tab0' => $tab0,
'tab1' => $tab1,
'tab2' => $tab2,
'tab3' => $tab3,
);

$encode_data = json_encode($rvar);
echo $encode_data;

}




}