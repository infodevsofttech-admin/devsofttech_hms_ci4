<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opd extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
		$this->load->library("Pdf");
	}

    public function addopd($pno)
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
       
        $this->load->view('opd_appointment_V',$data);
    }
    
    public function showfee()
    {
    	$doc_id=$this->input->post('doc_id');
    	$p_id=$this->input->post('pid');
		
        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$doc_id." group by d.id ";
        $query = $this->db->query($sql);
        $doc_info= $query->result();

        $sql="SELECT * from opd_master where p_id=".$p_id." and doc_id=".$doc_id." 
        	 and  apointment_date > date_add(sysdate(),interval -5 day) and opd_fee_type<>3";
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
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['patient_master'][0]->id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
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

	public function invoice_print_pdf($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $opd_master= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age 
		from patient_master where id='".$opd_master[0]->p_id."' ";

        $query = $this->db->query($sql);
        $patient_master= $query->result();
		
		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();
		}

		// Content Start  From here

		$content='<h3 align="center">OPD Charges Invoice</h3>';

        $itemlist='Items:';

		$content.='	<table width="100%">
						<tr>
							<td width="50%">
								To <br/> 
								UHID : '.$patient_master[0]->p_code.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
								Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b> P.No. :'.$patient_master[0]->mphone1 .'<br>
								Address : '.$patient_master[0]->add1.','.$patient_master[0]->city.'<br>
							</td>
							<td width="30%">
								Invoice No.  : '.$opd_master[0]->opd_code.'<br>
								Date : <strong>'.$opd_master[0]->str_apointment_date.'</strong><br>
							</td>
							<td width="20%">
								
							</td>
						</tr>
					<table>	';
		$content.='<table border="0.5" cellspacing="1" cellpadding="1.5">
			<tr>
				<th >Date </th>
				<th  align="center">Charge Name</th>
				<th  align="center">Doctor Name</th>
				<th  align="center">Description</th>
				<th  align="center">Amount</th>
			</tr>';
			
		$content.= '<tr>';
					$content.= '<td>'.$opd_master[0]->str_apointment_date.'</td>';
					$content.= '<td>Consultation  Fee</td>';
					$content.= '<td>Dr.'.$opd_master[0]->doc_name.'</td>';
					$content.= '<td>'.$opd_master[0]->opd_fee_desc.'</td>';
					$content.= '<td align="right">Rs.'.$opd_master[0]->opd_fee_gross_amount.'</td>';
					$content.= '</tr>';

		if($opd_master[0]->opd_discount>0 )
		{
			$content.= '<tr>';
					$content.= '<td>#</td>';
					$content.= '<td> Deduction </td>';
					$content.= '<td colspan="2">'.$opd_master[0]->opd_disc_remark.'</td>';
					$content.= '<td align="right"> Rs.'.$opd_master[0]->opd_discount.'</td>';
					$content.= '</tr>';

		}

		$content.= '<tr>';
					$content.= '<td>#</td>';
					$content.= '<td> </td>';
					$content.= '<td colspan="2">Net Amount</td>';
					$content.= '<td align="right"> Rs.'.$opd_master[0]->opd_fee_amount.'</td>';
					$content.= '</tr>';
		
		$content.='</table>';

		// Content End Here

		if($opd_master[0]->payment_id>0 )
		{
				$content.='<strong>Payment No. :</strong>'.$opd_master[0]->payment_id.'<br>';
		}

		$content.='	
		<br/>
		<table width="100%">
			<tr>
				<td width="66.3%">
					<b>Amount in Words : </b>Rs. '.number_to_word($opd_master[0]->opd_fee_amount).'
				</td>
				<td width="33.3%">
													
				</td>
			</tr>
			<tr>
				<td width="66.3%">
					<b>Prepared By :</b>'.$opd_master[0]->prepared_by.'
				</td>
				<td width="33.3%" align="center">
					<b>Signature</b>								
				</td>
			</tr>
		<table>	';

		
		$bar_content=$patient_master[0]->p_code.':OPD-'.$opd_master[0]->opd_code.':T'.date('dmYhms');
		//echo $content;
		$this->load->library("Pdf");
		create_Invoice_pdf($content,$bar_content,'opd_invoice-'.$opdid.'-'.date('Yms'));
	}

	public function opd_lettre_print($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['opd_master'][0]->apointment_date."')  AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

        $sql="select *,date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date from opd_master 
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

        $inser_id=$this->Opd_M->insert( $data['insert']);
 
        $rvar=array(
	        'insertid' =>$inser_id,
	        'error_text'=>'Some Thing Wrong',
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
		
		$sql="select * from payment_history where payof_id='".$oid."' and insert_code='".$submit_page."'";
		$query = $this->db->query($sql);
		$chk_payment= $query->result();

		$showcontent1='<div class="row no-print">
						<div class="col-xs-6">
							<a href="/index.php/Opd/invoice_print/'.$this->input->post('oid').'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
							 <a href="/Opd/opd_PDF_print/'.$this->input->post('oid').'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Letter Head</a>
						</div>
						<div class="col-xs-6">
							Payment Method by : ';          
		$showcontent2='</div></div>';

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
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent,
		        'csrfName' => $this->security->get_csrf_token_name(),
		        'csrfHash' => $this->security->get_csrf_hash(),
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}else{
				if(count($chk_payment)>0)
				{
					$rvar=array(
					'update' =>0,
					'showcontent'=>'Already Paid',
					'csrfName' => $this->security->get_csrf_token_name(),
		        	'csrfHash' => $this->security->get_csrf_hash(),
					);
					
					$encode_data = json_encode($rvar);
					echo $encode_data;
				}
		}

	
		if($this->input->post('mode')==1 && count($chk_payment)<1 )                                 
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
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent,
		        'csrfName' => $this->security->get_csrf_token_name(),
		        'csrfHash' => $this->security->get_csrf_hash(),
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}else{
				if(count($chk_payment)>0)
				{
					$rvar=array(
					'update' =>0,
					'showcontent'=>'Already Paid',
					'csrfName' => $this->security->get_csrf_token_name(),
		        	'csrfHash' => $this->security->get_csrf_hash(),
					);
					
					$encode_data = json_encode($rvar);
					echo $encode_data;
				}
		}
		
		if($this->input->post('mode')==2 && count($chk_payment)<1 )
		{
			$FormRules = array(
                array(
                    'field' => 'input_card_mac',
                    'label' => 'Card Bank Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[4]|max_length[4]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
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
					'update_by'=>$user_name.'['.$user_id.']',
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'payment_mode_desc'=>'Bank Card',
						'confirm_pay_opd'=>date('Y-m-d H:i:s'),
						'payment_id'=>$insert_id,
						'card_bank'=>$this->input->post('input_card_mac'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_card_bank'),
						'card_tran_id'=>$this->input->post('input_card_tran'),
						'prepared_by_id'=>$user_id
				);
				
				$this->Opd_M->update( $data,$this->input->post('oid'));
							
				
				$status='Bank Card';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent,
				'csrfName' => $this->security->get_csrf_token_name(),
		        'csrfHash' => $this->security->get_csrf_hash(),
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error,
                'csrfName' => $this->security->get_csrf_token_name(),
		        'csrfHash' => $this->security->get_csrf_hash(),
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}else{
				if(count($chk_payment)>0)
				{
					$rvar=array(
					'update' =>0,
					'showcontent'=>'Already Paid',
					'csrfName' => $this->security->get_csrf_token_name(),
		        	'csrfHash' => $this->security->get_csrf_hash(),
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
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent,
				'csrfName' => $this->security->get_csrf_token_name(),
		        'csrfHash' => $this->security->get_csrf_hash(),
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
				'upload_by_id'=>$user_id
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
		$sql="select * from file_upload_data where opd_id=".$opdid;
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
			join opd_master o on d.id=o.doc_id and o.apointment_date between date_add(curdate(),interval -5 day) and curdate()";
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
		$this->load->view('dashboard/appointment',$data);
	}
	
	public function get_appointment_list($doc_id,$opd_date='')
	{
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');
		}
		
		$data["opd_date"]=$opd_date;
		
		$sql="select o.* from opd_master_exten o left join opd_prescription_list p on o.opd_id=p.opd_id 
			WHERE  p.id is null  and ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";

		$query = $this->db->query($sql);
        $data['opd_list_0']= $query->result();
        
		$sql="select o.*,p.queue_no from opd_master_exten o join opd_prescription_list p on o.opd_id=p.opd_id 
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";
		
		$query = $this->db->query($sql);
        $data['opd_list_1']= $query->result();
				
		$sql="select * from opd_master_exten o
			WHERE  ( o.apointment_date =  '".$opd_date."' )   
			and o.opd_status=2 and o.doc_id ='".$doc_id."' ORDER BY opd_code  desc";
			
		$query = $this->db->query($sql);
        $data['opd_list_2']= $query->result();
		
		$sql="select * from opd_master_exten o
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=3 and o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";
			
		$query = $this->db->query($sql);
        $data['opd_list_3']= $query->result();

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

		$this->load->view('dashboard/opd_list_doc',$data);
	}
 
	public function opd_cancel($opdid)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str=$this->input->post('input_remark');

		$opd_status_remark=$status_str.' [Update By:'.$user_name.']';
		
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
				'refund_type_reason' => 'Cancel OPD',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}
		
		$dataupdate = array( 
				'opd_status' => '3',
				'opd_status_remark' => $opd_status_remark
				);


		$this->load->model('Opd_M');
		$this->Opd_M->update($dataupdate,$opdid);
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
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
        $sql = "select  o.opd_id, o.doc_name,o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
		p.p_code,p.mphone1,p.email1 ,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,
		m.mode_desc as PaymentMode,o.payment_mode
		from (opd_master o join patient_master p on o.p_id=p.id ) join payment_mode m on o.payment_mode=m.id
		WHERE (opd_code like '%".$sdata."' or p_code like '%".$sdata."' or P_name like '%".$sdata."%' or
		mphone1 = '".$sdata."' or email1 = '".$sdata."') and o.apointment_date>Date_Add(sysdate(),interval -7 day) order by o.opd_id desc  limit 100";

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


	public function opd_PDF_print($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $opd_master= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
        $patient_master= $query->result();

        $sql="select *,date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date
        from opd_master where p_id=".$opd_master[0]->p_id." and opd_id < ".$opdid." 
        order by opd_id desc limit 1";
        $query = $this->db->query($sql);
		$old_opd= $query->result();

		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();
		}

		$old_uhid='';
		if($patient_master[0]->udai<>"")
	    {
	    	$old_uhid= ' /'.$patient_master[0]->udai;
	    }

	    $exp_date="";

	    if($opd_master[0]->opd_fee_type=='3'){
                if(count($old_opd)>0){
                  $exp_date= 'OPD Start Date : '.$old_opd[0]->s_opd_date.'<br>Valid Upto :'.$old_opd[0]->opd_Exp_Date.'<br>';
                }
         }else{
        		$exp_date="<b>Valid Upto : ".$opd_master[0]->opd_Exp_Date ."</b>";
        } 

		$content='<hr/>
				<table width="100%" border="1">
						<tr>
							<td width="33.3%">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								P.No. :'.$patient_master[0]->mphone1 .'
							</td>
							<td width="33.3%">
								Sr No.: '.$opd_master[0]->opd_no .' <br/>	
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'
							</td>
							<td width="33.3%">
								Consultant Name <br><b>Dr. '.$opd_master[0]->doc_name.'<br>['.$opd_master[0]->doc_spec.']</b>
							</td>
						</tr>
					<table>	
					<hr/>';
		//echo $content;

		create_OPD_Letter_pdf($content,'opd_parchi-'.$opdid.'-'.date('Yms'));
		
	}


	public function update_appointment_panel()
	{
		$doc_id=$this->input->post('doc_id');
        $opd_date=$this->input->post('opd_date');
        
		if($opd_date=='')
		{
			$opd_date=date('Y-m-d');
		}
				
		$sql="select o.* from opd_master_exten o left join opd_prescription_list p on o.opd_id=p.opd_id 
			WHERE  p.id is null  and ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";
		$query = $this->db->query($sql);
        $opd_list_0= $query->result();
        
		$sql="select o.*,p.queue_no from opd_master_exten o join opd_prescription_list p on o.opd_id=p.opd_id 
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=1 and  o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";
		$query = $this->db->query($sql);
        $opd_list_1= $query->result();
				
		$sql="select * from opd_master_exten o
			WHERE  ( o.apointment_date =  '".$opd_date."' )   
			and o.opd_status=2 and o.doc_id ='".$doc_id."' ORDER BY opd_code  desc";
			
		$query = $this->db->query($sql);
        $opd_list_2= $query->result();
		
		$sql="select * from opd_master_exten o
			WHERE  ( o.apointment_date =  '".$opd_date."' )  
			and o.opd_status=3 and o.doc_id ='".$doc_id."' ORDER BY opd_code desc ";
			
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
						<button  type="button" class="btn btn-default" onclick="update_status('.$row->opd_id.',2)" >Visit Done</button></td>
						
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
				<td><button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" data-etype="1" ><img src="/assets/images/icon/iball_scan.png" class="img_icon"  /></button>
				<button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" data-etype="3" ><img src="/assets/images/icon/upload_scan_img.png" class="img_icon"  /></button>
				<button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" data-etype="2" >
					<img src="/assets/images/icon/medical_profile.png" class="img_icon"  />
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
				<td><button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" data-etype="1" ><img src="/assets/images/icon/iball_scan.png" class="img_icon"  /></button>
				<button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" data-etype="3" ><img src="/assets/images/icon/upload_scan_img.png" class="img_icon"  /></button>
				<button  type="button" 	class="btn btn-default" data-toggle="modal"
				data-target="#tallModal" 
				data-opdid="'.$row->opd_id.'" 
				data-etype="2" ><img src="/assets/images/icon/medical_profile.png" class="img_icon"  /><</button>
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

	
 
 }