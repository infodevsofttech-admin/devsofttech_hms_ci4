<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class IpdNew extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
		$this->load->model('Ipd_M');
	}

	public function IpdDashboard()
	{
		$this->load->view('IPD2/ipd_dashboard');
	}
	
	public function ipd_panel_payment($ipdno)
    {
				
		$sql="select *,Date_Format(payment_date,'%d-%M-%Y') as pay_date_str,
		Concat(if(payment_mode=1,'Cash','BANK'),if(credit_debit=0,'','-Return')) as pay_mode 
		from payment_history 
		where  payof_type=4 and payof_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment_history']= $query->result();
		
		$sql="select * from invoice_med_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_master']= $query->result();

		$this->load->view('IPD2/ipd_payment',$data);
	}

	public function IPD_info(){
		
		$IPDNO = $this->input->post('input_ipd_no');
		
		$sql="select * from v_ipd_list where ipd_code='$IPDNO' ";
        $query = $this->db->query($sql);
        $data= $query->result();
		
		if(count($data)>0)
		{
			$Patient_id=$data[0]->p_id;
			$IPD_id=$data[0]->id;
			$ipd_info=$data[0]->p_code.'/'.$data[0]->p_fname.' '.$data[0]->p_rname.' /Age :'.$data[0]->str_age.' /AdmitDate:'.
			$data[0]->str_register_date.'/TPA:'.$data[0]->admit_type;
			
		}else{
			$Patient_id=0;
			$ipd_info='';
			$IPD_id=0;
		}

		$rvar=array(
			'IPD_id' =>$IPD_id,
			'ipd_info'=>$ipd_info
			);

		$encode_data = json_encode($rvar);
		echo $encode_data;

	  }
	
	public function ipd_Diagnosis($ipdno)
	{
		
		$sql="select i.id as inv_id,i.invoice_code,sum(t.item_amount) as amount,i.refer_by_other,
			date_format(i.inv_date,'%d-%m-%Y') as str_date,i.ipd_include,
			group_concat(distinct c.group_desc) as charge_list
			from invoice_master i left join
			(invoice_item t join hc_item_type c on t.item_type=c.itype_id)
			on i.id =t.inv_master_id   
			where  payment_status=1  and ipd_id = ".$ipdno."	group by i.id ";
 
		$query = $this->db->query($sql);
		$data['inv_master']= $query->result();
		
		$this->load->view('IPD2/ipd_diagnosis_charges',$data);
	}

	public function ipd_panel_invoice_show($inv_id,$inv_type)
    {
		//$inv_id=$this->input->post('invid');
		//$inv_type=$this->input->post('invtype');
		
		if($inv_type == 0)
		{
				redirect('InvoiceShow/invoice_med/'.$inv_id);
		}else{
				redirect('InvoiceShow/invoice_charges/'.$inv_id);
		}
	}

	public function ipd_Diagnosis_invoice_show($invoice_id)
	{
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select *,(case payment_status when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'Org Credit' else 'Pending' end) as Payment_type_str  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

		$this->load->view('IPD2/ipd_diagnosis_invoice_items',$data);
	
	}

	public function Update_Invoice_ipd_itmes_package()
	{
		$ipd_item_package_type= $this->input->post('ipd_item_package_type');
		$ipd_item_id = $this->input->post('ipd_item_id');
		
		$dataupdate = array( 
				'package_id' => $ipd_item_package_type			
				);
		
		
		$this->Ipd_M->update_ipd_item($dataupdate,$ipd_item_id);
		echo 'Value of '.$ipd_item_package_type.' is '.$ipd_item_id;
	}
		
	public function show_account($ipdno)
	{
		$total_med=0.00;
		$total_charges=0.00;
		
		$sql="select * from ipd_master where  id='".$ipdno."' ";
        $query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$discount=$data['ipd_master'][0]->Discount;
		$discount2=$data['ipd_master'][0]->Discount2;
		$discount3=$data['ipd_master'][0]->Discount3;
		
		$tdiscount=$discount+$discount2+$discount3;
		
		$charge1=$data['ipd_master'][0]->chargeamount1;
		$charge2=$data['ipd_master'][0]->chargeamount2;
		$tcharge=$charge1+$charge2;
		
		$sql="select sum(net_amount) as tnet_amount from invoice_med_master where  ipd_credit=1 and ipd_credit_type<2 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total']= $query->result();
		
		$total_med=$data['inv_med_total'][0]->tnet_amount;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_total']= $query->result();
		
		$total_charges=$data['inv_charge_total'][0]->tnet_amount;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as tnet_amount from ipd_payment where   ipd_id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$data['inv_payment_total']= $query->result();
		
		$total_payment=$data['inv_payment_total'][0]->tnet_amount;
		
		$total_balance_amount=$total_payment-($total_charges+$total_med+$tcharge-$tdiscount);
		
		$data['inv_total']=array(
		'total_med' => $total_med,
		'total_charges' => $total_charges,
		'total_payment' => $total_payment,
		'total_balance_amount' => $total_balance_amount,
		'discount' => $tdiscount,
		'charge' => $tcharge,
		'ipd_id' => $ipdno
		);

		$this->load->view('IPD2/ipd_account_panel',$data);
		
	}
	
	public function ipd_complete_invoice($ipdno,$print=0)
	{
		//calculate_IPD

		

		$this->Ipd_M->calculate_IPD($ipdno);

		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
		$data['ipdmaster']= $query->result();

		$pno=$data['ipdmaster'][0]->p_id;
	
		$sql="select * from  ipd_package where  ipd_id=".$ipdno;
		$query = $this->db->query($sql);
		$data['ipd_package']= $query->result();

		//Check Items in Package
		$No_Pack_items=0;

		$sql1=	'Select count(*) as no_rec from ipd_invoice_item i 
				where i.package_id>0 and i.ipd_id='.$ipdno;
		
		$sql2=	'Select count(*) as no_rec from invoice_med_master 
				Where ipd_credit=1 and ipd_credit_type=0 and ipd_id = '.$ipdno;
		
		$sql3=	'Select count(*) as no_rec from v_ipd_invoice b 
				where b.ipd_id='.$ipdno.'  and ipd_include=0';
		
		$query = $this->db->query($sql1);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		$query = $this->db->query($sql2);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		$query = $this->db->query($sql3);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		$data['No_Pack_items']=$No_Pack_items;

		if(count($data['ipd_package'])>0)
		{
			$sql="select t.group_desc,i.*,sum(i.item_amount) as xAmount
			from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
			where i.package_id=0 and i.ipd_id=".$ipdno."
			group by i.item_type,i.id order by i.item_type";
			$query = $this->db->query($sql);
			$data['ipd_invoice_item']= $query->result();
		}else{
			$sql="select t.group_desc,i.*,sum(i.item_amount) as xAmount
			from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
			where  i.ipd_id=".$ipdno."
			group by i.item_type,i.id order by i.item_type";
			$query = $this->db->query($sql);
			$data['ipd_invoice_item']= $query->result();
		}

		$sql="select * from invoice_med_master 
		where  ipd_credit=1 and ipd_credit_type=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_list']= $query->result();

		$sql="select b.ipd_id AS ipd_id,b.Charge_type AS Charge_type,b.idesc AS idesc,
			b.item_rate AS item_rate,b.orgcode AS orgcode,sum(b.item_qty) AS no_qty,sum(b.Amount) AS amount 
		from v_ipd_invoice b where b.ipd_id=".$ipdno."  and ipd_include=1
		group by b.ipd_id,b.item_id,b.item_rate order by b.Charge_type ";
		
		$query = $this->db->query($sql);
		$data['showinvoice']= $query->result();
		
		$sql="select * from v_ipd_list where id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
		
		$sql="select b.ipd_id,b.bed_id,b.TDate,m.room_name,m.bed_no
				from ipd_bed_assign b left join hc_bed_master m on b.bed_id=m.id
				where  b.ipd_id=".$ipdno." order by TDate desc";
		$query = $this->db->query($sql);
        $data['bed_list']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		if($data['ipdmaster'][0]->case_id>0)
		{
			$sql="select * from organization_case_master where id='".$data['ipdmaster'][0]->case_id."'";
			$query = $this->db->query($sql);
			$data['orgcase']= $query->result();

			$ins_comp_id=$data['orgcase'][0]->insurance_id;
			$inc_card_id=$data['orgcase'][0]->insurance_card_id;

			$sql="select * from hc_insurance_card where id='".$inc_card_id."'";
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();

			$sql="select * from hc_insurance where id='".$ins_comp_id."'";
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
		}

		$sql="SELECT h.*,DATE_FORMAT(h.payment_date,'%d-%m-%Y') as pay_date_str,
		concat(if(h.credit_debit=0,'','Return '),if(h.payment_mode=1,'CASH','BANK')) as pay_mode
		from payment_history h    where h.payof_type=4 AND h.payof_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment']= $query->result();
	
		if($print>0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			//$this->m_pdf->pdf->SetWatermarkText(H_Name);
			//$this->m_pdf->pdf->showWatermarkText = true;
	
			$file_name='Report-IPDBill-'.$ipdno."-".date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//$this->load->view('Medical/medical_bill_print_format',$data);

			if($print==1)
			{
				$content=$this->load->view('IPD2/Ipd_invoice_print_V',$data,TRUE);
			}else if($print==2)
			{
				$content=$this->load->view('IPD2/ipd_invoice_print_wo_payment',$data,TRUE);
			}else if($print==3)
			{
				$content=$this->load->view('IPD2/ipd_invoice_doc_wise',$data,TRUE);
			}else if($print==4)
			{
				$content=$this->load->view('IPD2/ipd_invoice_print_TPA_V',$data,TRUE);
			}else if($print==5){
				$content=$this->load->view('IPD2/ipd_invoice_print_on_letterhead',$data,TRUE);
			}else{
				$content='Blank';
			}
			

			//echo $content;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I"); 



		}else{
			$this->load->view('IPD2/Ipd_invoice_V',$data);
		}

		
		
	}
	
	public function ipd_short_invoice($ipdno,$print=0)
	{
		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipdmaster']= $query->result();
		
		$pno=$data['ipdmaster'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		if($data['ipdmaster'][0]->case_id>0)
		{
			$sql="select * from organization_case_master where id='".$data['ipdmaster'][0]->case_id."'";
			$query = $this->db->query($sql);
			$data['orgcase']= $query->result();
							
			$ins_comp_id=$data['orgcase'][0]->insurance_id;
			$inc_card_id=$data['orgcase'][0]->insurance_card_id;
			
			$sql="select * from hc_insurance_card where id='".$inc_card_id."'";
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();

			$sql="select * from hc_insurance where id='".$ins_comp_id."'";
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
		}

		$sql="select * from v_ipd_invoice v
				where v.ipd_id=".$ipdno." order by Charge_type,inv_date ";
		$query = $this->db->query($sql);
        $data['showinvoice']= $query->result();
		
		$sql="select sum(amount) as GTotal from v_ipd_invoice v
				where v.ipd_id=".$ipdno."  ";
		$query = $this->db->query($sql);
        $data['invoiceGtotal']= $query->result();
		
		$sql="select *,case payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end  as pay_mode from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment']= $query->result();
		

        if($print>0)
		{
			$this->load->view('IPD2/Ipd_invoice_print_V',$data);
			
		}else{
			$this->load->view('IPD2/Ipd_invoice_V',$data);
		}
	}
	
	public function ipd_confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		$this->load->model('Payment_M');
		
		$ipd_id=$this->input->post('ipd_id');
		$amount=$this->input->post('amount');
		
		$sql="select * from ipd_master where id=".$ipd_id;
		$query = $this->db->query($sql);
        $ipd_master= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$pay_remark=$this->input->post('cash_remark');
		
		$pay_datetime=str_to_MysqlDate($this->input->post('date_payment')).' '.date('H:i:s');
		
		if($this->input->post('mode')==1)
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'payof_type'=>'4',
					'payof_id'=>$ipd_id,
					'payof_code'=>$ipd_master[0]->ipd_code,
					'credit_debit'=>'0',
					'amount'=>$amount,
					'payment_date'=>$pay_datetime,
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Payment_M->insert($paydata);
			
			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_id,
			'pay_date' =>$pay_datetime
			);

			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
		if($this->input->post('mode')==2)
		{
			
			$FormRules = array(
                array(
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[1]|max_length[15]'
                )
            );
			
			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
				$paydata = array( 
					'payment_mode'=> '2',
					'payof_type'=>'4',
					'payof_id'=>$ipd_master[0]->id,
					'payof_code'=>$ipd_master[0]->ipd_code,
					'credit_debit'=>'0',
					'amount' => $amount,
					'payment_date'=>$pay_datetime,
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'pay_bank_id'=>$this->input->post('cbo_pay_type'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);
				
				$status='Bank Card';
				
								
				$rvar=array(
				'update' =>1,
				'ipd_id'=> $ipd_id,
				'payid' => $insert_id
				);
								
				$encode_data = json_encode($rvar);
                echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}
		
	}
	
	public function ipd_ded_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		$this->load->model('Payment_M');
		
		$ipd_id=$this->input->post('ipd_id');
		$amount=$this->input->post('amount');
		
		$sql="select * from ipd_master where id=".$ipd_id;
		$query = $this->db->query($sql);
        $ipd_master= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$pay_remark=$this->input->post('cash_remark');

		if($this->input->post('mode')==3)
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'payof_type'=>'4',
					'payof_id'=>$ipd_id,
					'payof_code'=>$ipd_master[0]->ipd_code,
					'credit_debit'=>'1',
					'amount'=>$amount,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Payment_M->insert($paydata);

			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_id
			);

			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
		if($this->input->post('mode')==4)
		{
			$paydata = array( 
					'payment_mode'=> '2',
					'payof_type'=>'4',
					'payof_id'=>$ipd_id,
					'payof_code'=>$ipd_master[0]->ipd_code,
					'credit_debit'=>'1',
					'amount'=>$amount,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Payment_M->insert($paydata);
			
			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_id
			);

			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
	}
	
	public function ipd_org_status_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$this->load->model('Ocasemaster_M');
		$data['master'] = array( 
                    'org_approved_amount' => $this->input->post('amount'),
                    'org_approved_status_id' => $this->input->post('org_status'),
					'org_status_update_by' => $user_name
                 );

		$case_id=$this->input->post('case_id');
		$this->Ocasemaster_M->update($data['master'],$case_id);

		$rvar=array(
                'update' =>'1',
				'msg' => 'Update Done '.$case_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		
	}
	
	public function ipd_main_panel($ipdno)
	{
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days from ipd_master where id='".$ipdno."' ";
		$query = $this->db->query($sql);
        $data['ipd_info']= $query->result();
		
		$sql="select * from v_ipd_list where id='".$ipdno."' ";
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select i.id,d.p_fname,d.id as doc_id from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from  organization_case_master  
			where id=".$data['ipd_info'][0]->case_id;
        $query = $this->db->query($sql);
		$data['case_master']= $query->result();
		
		$sql="select * from  organization_case_master  
		where status=0 and case_type=1 and  ipd_id=0 and p_id=".$data['ipd_info'][0]->p_id;
        $query = $this->db->query($sql);
        $data['case_master_open']= $query->result();
		
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from org_approved_status ";
        $query = $this->db->query($sql);
        $data['org_approved_status']= $query->result();

		$sql="SELECT m.id,CONCAT(m.title,' ',m.f_name,' [',t.type_desc,']') AS refer_name, t.type_desc
		FROM refer_master m JOIN refer_type t ON m.refer_type=t.id order by t.type_desc,m.f_name";
        $query = $this->db->query($sql);
        $data['refer_master']= $query->result();

		$sql="SELECT i.id,CONCAT(m.title,' ',m.f_name,' [',t.type_desc,']') AS refer_name, t.type_desc
		FROM (refer_master m JOIN refer_type t ON m.refer_type=t.id)
			Join ipd_refer i on i.refer_by=m.id and i.ipd_id=$ipdno 
			order by t.type_desc,m.f_name";
        $query = $this->db->query($sql);
        $data['refer_list']= $query->result();


		$this->load->view('IPD2/ipd_main_panel',$data);
	}
	
	public function ipd_account_panel($ipdno)
	{
		//calculate_IPD

		
		$this->Ipd_M->calculate_IPD($ipdno);

		$sql="select * from ipd_master where id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$ipd_master= $query->result();

		$data['ipd_master']=$ipd_master;

		$data['inv_total']=array(
			'total_med_credit' => $ipd_master[0]->med_amount+$ipd_master[0]->package_med_amount,
			'total_med_cash' => $ipd_master[0]->cash_med_amount,
			'total_med_cash_paid' => $ipd_master[0]->med_paid,
			'total_charge_amount' => $ipd_master[0]->charge_amount,
			'total_payment' => $ipd_master[0]->total_paid_amount,
			'total_balance_amount' => $ipd_master[0]->balance_amount,
			'net_amount' => $ipd_master[0]->net_amount,
			'ipd_id' => $ipdno
		);
		
		$this->load->view('IPD2/ipd_account_panel',$data);
	}
	
	public function ipd_panel($ipdcode,$stype=0)
    {
			
		if($stype > 0)
		{
			$sql="select id from ipd_master where ipd_code='".$ipdcode."' ";
			$query = $this->db->query($sql);
			$get_ipd_no= $query->result();
			
			$ipdno=$get_ipd_no[0]->id;
		}else{
			$ipdno=$ipdcode;
		}

		//calculate_IPD

		
		$this->Ipd_M->calculate_IPD($ipdno);

		$sql="select * from v_ipd_list where id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$data['ipd_info']= $query->result();
		
		$p_id=$data['ipd_info'][0]->p_id;

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age 
		from patient_master where id='".$p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
				
		$sql="select * 
		from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id 
		where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();

		$sql="select * 
		from  organization_case_master  
		where status=0 and case_type=1 and ipd_id=$ipdno and p_id=".$p_id;
        $query = $this->db->query($sql);
		$data['case_master']= $query->result();
				
		$sql="select * from ipd_master where id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$ipd_master= $query->result();

		$data['ipd_master']=$ipd_master;

		$sql="select *,Date_Format(payment_date,'%d-%M-%Y') as pay_date_str,
		Concat(if(payment_mode=1,'Cash','BANK'),if(credit_debit=0,'','-Return')) as pay_mode 
		from payment_history 
		where  payof_type=4 and payof_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment_history']= $query->result();

		if ($this->ion_auth->in_group('admin1') || $this->ion_auth->in_group('IPDAdmit') ) { 
			if($data['ipd_info'][0]->ipd_status==0)
			{
				$this->load->view('IPD2/Ipdpanel_V',$data);
			}else{
				if($this->ion_auth->in_group('IPDReAdmit'))
				{
					$this->load->view('IPD2/Ipdpanel_V',$data);
				}else{
					$this->load->view('IPD2/IPD_View',$data);
				}
			}
		}else{
			$this->load->view('IPD2/IPD_View',$data);
		}
	}

	public function load_model_TPA_Payment($ipd_id)
	{
		$sql="select *,if(case_type=1,'MLC','Non MLC') as case_type_s, 
		Date_format(register_date,'%d-%m-%Y') as str_register_date ,
		Date_format(discharge_date,'%d-%m-%Y') as str_discharge_date
		from ipd_master where id='".$ipd_id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master 
		where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$data['ipd_id']=$ipd_id;

		$this->load->view('IPD2/TPA_Payment',$data);
	}

	public function tpa_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		
		$ipd_id=$this->input->post('hid_ipd_id');
		$payable_by_tpa=$this->input->post('input_payable_by_tpa');
		$discount_for_tpa=$this->input->post('input_discount_for_tpa');
		$discount_by_hospital=$this->input->post('input_discount_by_hospital');
		$discount_by_hospital_2=$this->input->post('input_discount_by_hospital_2');
		$discount_by_hospital_remark=$this->input->post('input_discount_by_hospital_remark');
		$discount_by_hospital_2_remark=$this->input->post('input_discount_by_hospital_2_remark');
		
		$paydata = array( 
					'payable_by_tpa'=> $payable_by_tpa,
					'discount_by_hospital'=>$discount_by_hospital,
					'discount_by_hospital_2'=>$discount_by_hospital_2,
					'discount_for_tpa'=>$discount_for_tpa,
					'discount_by_hospital_remark'=>$discount_by_hospital_remark,
					'discount_by_hospital_2_remark'=>$discount_by_hospital_2_remark,
			);

		$update=$this->Ipd_M->update($paydata,$ipd_id);
		
		$rvar=array(
		'update' =>1,
		'ipd_id'=> $ipd_id,
		);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	
	}
		
	public function IpdList()
	{
		$sql = "select *,(paid_amount-(charge_amount+med_amount)) as balance from v_ipd_list where ipd_status=0 ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
        $this->load->view('IPD2/IPDList_V',$data);
    }
	
	public function ipd_room_status()
	{
		$sql="select b.room_name,b.room_rent,count(b.id) as Total_Bed,sum(if(b.bed_used_p_id=0,1,0)) as FreeBed
		from hc_bed_master b group by b.room_name";
		$query = $this->db->query($sql);
        $data['roomdata']= $query->result();
		
		$this->load->view('IPD2/ipd_bed_status',$data);
	}
	
	public function add_remove_doc($ipd_doc_id)
	{
		if ($this->ion_auth->in_group('IPDAdmit')) { 
			$this->db->delete("ipd_master_doc_list", "id=".$ipd_doc_id);
		}
	}
	
	public function add_add_doc($ipd_id,$doc_id)
	{
		if ($this->ion_auth->in_group('IPDAdmit')) { 
			
			if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
				
                
				$data = array( 
                    'ipd_id' => $ipd_id, 
                	'doc_id' => $doc_id
                 ); 
				$inser_id=$this->Ipd_M->add_ipd_doc($data);
		}
	}
	
	public function show_ipd_form($ipdno,$form_no=1)
	{
		$sql="select *,if(case_type=1,'MLC','Non MLC') as case_type_s, 
		Date_format(register_date,'%d-%m-%Y') as str_register_date ,
		Date_format(discharge_date,'%d-%m-%Y') as str_discharge_date
		from ipd_master where id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select * From v_ipd_list Where  id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_info_2']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select i.id,d.p_fname,d.id as doc_id from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from organization_case_master where id='".$data['ipd_info'][0]->case_id."' ";
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
				
		$d= $this->ion_auth->user()->row();
		
		$data['user'] = $d;

		//load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = true;
	  
		$file_name='Report-FaceForm-'.date('Ymdhis').".pdf";

        $filepath=$file_name;

		//$this->load->view('Medical/medical_bill_print_format',$data);

		if($form_no==1)
		{
			$content=$this->load->view('IPD2/print_ipd_form1',$data,TRUE);
		}elseif($form_no==2){
			$content=$this->load->view('IPD2/print_ipd_form2',$data,TRUE);
		}elseif($form_no==3){
			$content=$this->load->view('IPD2/print_ipd_form3',$data,TRUE);
		}elseif($form_no==4){
			$content=$this->load->view('IPD2/ipd_dues_form',$data,TRUE);
		}elseif($form_no==5){
			$this->m_pdf->pdf->showWatermarkText = false;
			$content=$this->load->view('IPD_Format/form_admission_histroy_phusical_assessment',$data,TRUE);
		}elseif($form_no==6){
			$this->m_pdf->pdf->showWatermarkText = false;
			$content=$this->load->view('IPD_Format/form_treatment_chart',$data,TRUE);
		}elseif($form_no==7){
			$this->m_pdf->pdf->showWatermarkText = false;
			$content=$this->load->view('IPD_Format/form_vitals_chart',$data,TRUE);
		}elseif($form_no==8){
			$this->m_pdf->pdf->showWatermarkText = false;
			$content=$this->load->view('IPD_Format/form_progress_notes',$data,TRUE);
		}elseif($form_no==9){
			$this->m_pdf->pdf->showWatermarkText = false;
			$content=$this->load->view('IPD_Format/form_fluid_in_out',$data,TRUE);
		}elseif($form_no==13){
			$content=$this->load->view('IPD2/print_ipd_form4',$data,TRUE);
		}else{
			$content="";
		}
		
       	
        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
	}

	public function ipd_list()
	{
		$this->load->view('IPD2/ipd_all_list');
	}
	
    public function addipd($pno)
    {
		$sql="select  count(*) as no_ipd from ipd_master where ipd_status=0 and p_id=".$pno;
        $query = $this->db->query($sql);
        $data['ipd_master_rec']= $query->result();
		
		if ($data['ipd_master_rec'][0]->no_ipd > 0)
		{
			$sql="select * from ipd_master where ipd_status=0 and p_id=".$pno;
			$query = $this->db->query($sql);
			$data['ipd_info']= $query->result();

			redirect('IpdNew/ipd_panel/'.$data['ipd_info'][0]->id);
		}else{
			$sql="select *,if(gender=1,'Male','FeMale') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";
            
			$query = $this->db->query($sql);
			$data['person_info']= $query->result();

			$sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
				from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
				where d.active=1 group by d.id ";
			$query = $this->db->query($sql);
			$data['doc_spec_l']= $query->result();

			$sql="select id,concat('Bed No:',bed_no,'/','[',room_name,']') as Bed_Desc from hc_bed_master  where bed_used_p_id=0";
			$query = $this->db->query($sql);
			$data['ipd_bed_list']= $query->result();

			$sql="select * from hc_insurance ";
			$query = $this->db->query($sql);
			$data['insur_list']= $query->result();
			
			$sql="select * from  organization_case_master  
			where status=0 and case_type=1 and ipd_id=0 and p_id=".$pno;
			$query = $this->db->query($sql);
			$data['case_master']= $query->result();

			$sql="select * from  refer_master  where active=1";
			$query = $this->db->query($sql);
			$data['refer_master']= $query->result();


			$this->load->view('IPD2/Ipd_Registration_V',$data);
		}
	
	}
	
	public function AddNew() { 
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
			

			$data['master'] = array( 
				'p_id' => $this->input->post('pid'), 
				'P_name' => $this->input->post('pname'),
				'relation' => $this->input->post('r_relation'),
				'contact_person_Name' => $this->input->post('rp_name'),
				'insurance_id' => $this->input->post('insur_list'),
				'policy_no' => $this->input->post('insur_no'),
				'register_date' => str_to_MysqlDate($this->input->post('res_date')),
				'problem' => $this->input->post('problem'),
				'remark' => $this->input->post('remark'),
				'P_mobile1' => $this->input->post('phone1'),
				'P_mobile2' => $this->input->post('phone2'),
				'case_type' => $this->input->post('optionsRadios_mlc'),
				'case_id' => $this->input->post('optionsRadios_org'),
				'reg_time' => $this->input->post('res_time'),
				'refer_by' => $this->input->post('refer_by_list')
			); 

			$groupData = $this->input->post('doc_id');

			if (isset($groupData) && !empty($groupData)) {
				$data['doc_list']=$groupData;

				$inser_id=$this->Ipd_M->insert($data);

				$data['BedData'] = array( 
				'ipd_id' => $inser_id,
				'Fdate' => date('Y-m-d h:i:s'),
				'TDate' => date('Y-m-d h:i:s'),
				'bed_id' => $this->input->post('room_list')
				);

				if($this->input->post('room_list')<>"")
				{
				$inser_bed_id=$this->Ipd_M->bedassign($data['BedData']);
				}
				}	
				else
				{
				$inser_id=0;
				}

				if($this->input->post('optionsRadios_org')>0 && $inser_id>0)
				{
					$org_data = array( 
						'ipd_id' => $inser_id,
					);

						$this->load->model('Ocasemaster_M');
						$this->Ocasemaster_M->update($org_data,$this->input->post('optionsRadios_org'));
				}

$rvar=array(
'insertid' =>$inser_id
);
$encode_data = json_encode($rvar);
echo $encode_data;
}
	
	public function ipd_bed_assign_list($ipdno)
	{
		$sql="select m.bed_no,m.room_name,b.Fdate,b.TDate,b.Remark
		from ipd_bed_assign b join hc_bed_master m on b.bed_id=m.id where b.ipd_id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipd_bed_assign']= $query->result();
		
		$sql="select id,concat('Bed No:',bed_no,'/','[',room_name,']') as Bed_Desc from hc_bed_master  where bed_used_p_id=0";
			$query = $this->db->query($sql);
			$data['ipd_bed_list']= $query->result();

		
		$this->load->view('IPD2/ipd_bed_assign',$data);
		
	}
	
	
	public function ipd_cash_print_pdf($ipd_id,$payid)
	{
		$sql="select *,date_format(payment_date,'%d-%m-%Y %h:%i %p') as payment_date_str ,
		if(credit_debit=0,'','Return') as Amount_str
		from payment_history where payof_type=4 and payof_id=$ipd_id and id=$payid";
		$query = $this->db->query($sql);
		$data['ipd_payment']= $query->result();
		
		$sql="select * from ipd_master where id =".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$pno=$data['ipd_master'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
		$data['patient_master']= $query->result();
		
		$data['ipd_id']=$ipd_id;
		$data['payid']=$payid;

		//load mPDF library
				
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = false;
        
        $file_name='Charge_Invoice-'.date('Ymdhis').".pdf";

        $filepath=$file_name;

		$content=$this->load->view('IPD2/ipd_payment_receipt_print',$data,TRUE);
		
       	//echo $content;
		
		//$this->m_pdf->pdf->debug = true;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output("TEST.pdf","I");
	}
	
	public function load_model_box($ipd_id)
	{
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days,if(case_type=1,'MLC','Non MLC') as case_type_s, Date_format(register_date,'%d-%m-%Y') as str_register_date from ipd_master where id='".$ipd_id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$sql="select s.id,s.pay_type,m.bank_name
				From hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
		$query = $this->db->query($sql);
		$data['bank_data']= $query->result();

		$this->load->view('IPD2/ipd_model_payment_box',$data);
	}

	
	
	public function ipd_bed_list()
	{
		$sql="select id,concat('Bed No:',bed_no,'/','[',room_name,']') as Bed_Desc from hc_bed_master  where bed_used_p_id=0";
		
		$query = $this->db->query($sql);
        $ipd_bed_list= $query->result();
		
		echo '<select class="form-control" name="room_list" id="room_list" >';
		
		foreach($ipd_bed_list as $row)
		{ 
			echo '<option value="'.$row->id.'">'.$row->Bed_Desc.'</option>';
		}
		echo '</select>';
		echo '<input type="hidden" id="start_datetime"  value="'.date('Y-m-d H:i:s').'" />';
		echo '<input type="hidden" id="end_datetime"  value="'.date('Y-m-d H:i:s').'" />';
	}
	
	public function load_model_ded_box()
	{
		$this->load->view('IPD2/ipd_panel_deduction_payment');
	}
		
    
   
    public function discharge_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
				
				$user = $this->ion_auth->user()->row();
				$user_id = $user->id;
				$user_name = $user->first_name.''. $user->last_name;
				
				$discharge_by=$user_name.'['.$user_id.']';
				
                $data['master'] = array( 
                    'discharge_by' => $discharge_by, 
                	'discharge_remark' => $this->input->post('discharge_remark'),
					'discharge_balance_user' => $this->input->post('input_bal_user'),
					'discharge_balance_remark' => $this->input->post('input_bal_remark'),
					'ipd_status' => '1',
					'discharge_date' => str_to_MysqlDate($this->input->post('dis_date')),
					'discharge_time' => $this->input->post('dis_time'),
					'discarge_patient_status' => $this->input->post('p_status')
                ); 
				
				$ipd_id=$this->input->post('Ipd_ID');
				$this->Ipd_M->update($data['master'],$ipd_id);
				
				$inser_id=1;
                
                $rvar=array(
                'insertid' =>$inser_id,
				'showcontent' => "Discharge Status Updated."
                );
				
                $encode_data = json_encode($rvar);
                echo $encode_data;
	}
	
	public function update_ipdadmit_time()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
				
				$ipd_id=$this->input->post('Ipd_ID');

                $data['master'] = array( 
                    'register_date' => str_to_MysqlDate($this->input->post('strdate')), 
                	'reg_time' => $this->input->post('strtime')
                ); 

				$ipd_id=$this->input->post('Ipd_ID');
				$this->Ipd_M->update($data['master'],$ipd_id);

				$inser_id=1;

                $rvar=array(
                'insertid' =>$inser_id,
				'showcontent' => "Admit Date Updated"
                );

                $encode_data = json_encode($rvar);
                echo $encode_data;
	}

	public function update_relative_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
							
				$Ipd_ID=$this->input->post('Ipd_ID');
				$P_mobile1=$this->input->post('P_mobile1');
				$P_mobile2=$this->input->post('P_mobile2');
				$contact_person_Name=$this->input->post('contact_person_Name');
				$case_type=$this->input->post('case_type');

                $data['master'] = array( 
					'contact_person_Name' => $contact_person_Name,
					'P_mobile1' => $P_mobile1,  
                	'P_mobile2' => $P_mobile2,
					'case_type' => $case_type
				); 
				
				$this->Ipd_M->update($data['master'],$Ipd_ID);

				$inser_id=1;

                $rvar=array(
                'insertid' =>$inser_id,
				'showcontent' => "Admit Date Updated"
                );

                $encode_data = json_encode($rvar);
                echo $encode_data;
	}
	
	public function discharge_ipd($ipdno)
	{
		$sql="select * from ipd_master where id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();
		
		$sql="select * from ipd_discharg_status ";
        $query = $this->db->query($sql);
        $data['ipd_discharge_status']= $query->result();
		
		$discount=$data['ipd_info'][0]->Discount;

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
	

		if($data['ipd_info'][0]->ipd_status == 0 )
		{
			$this->load->view('IPD2/ipd_discharge',$data);
		}else{
			$this->load->view('IPD2/ipd_discharged_status',$data);
		}
		
		
	}
   
	public function getIpdTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'ipd_code', 
			1 => 'p_code',
			2 => 'p_fname',
			3=> 'admit_type',
			4=> 'insurance_no_1',
			5=> 'doc_name',
			6=> 'Disstatus',
			7=> 'str_register_date',
			8=> 'str_discharge_date',
			9=> 'status_desc'
			);

		// getting total number records without any search
		$sql_f_all = "select * ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from v_ipd_list ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //ipd_code
			$sql_where.=" AND ipd_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //p_code
			$sql_where.=" AND p_code LIKE '".$requestData['columns'][1]['search']['value']."%' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][2]['search']['value']) ){  //p_fname
			$sql_where.=" AND p_fname LIKE '%".$requestData['columns'][2]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][3]['search']['value']) ){  //P_mobile1
			$sql_where.=" AND (admit_type LIKE '%".$requestData['columns'][3]['search']['value']."%' 
						or ins_company_name LIKE '%".$requestData['columns'][3]['search']['value']."%')";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][4]['search']['value']) ){  //P_mobile1
			$sql_where.=" AND insurance_no_1 LIKE '%".$requestData['columns'][4]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][5]['search']['value']) ){  //P_mobile1
			$sql_where.=" AND doc_name LIKE '%".$requestData['columns'][5]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][7]['search']['value'])  )
		{ //IPD Admit Date Range
			$rangeArray = explode("/",$requestData['columns'][7]['search']['value']);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];
			$admitType = $rangeArray[2];
			if($admitType==0){
				$sql_where.=" AND ( Date(register_date) between '".$minRange."' AND '".$maxRange."' ) ";
			}elseif($admitType==1){
				$sql_where.=" AND ( Date(discharge_date) between '".$minRange."' AND '".$maxRange."' ) ";
			}else{
				$sql_where.=" AND (( Date(register_date) between '".$minRange."' AND '".$maxRange."' ) ";
				$sql_where.=" OR ( Date(discharge_date) between '".$minRange."' AND '".$maxRange."' )) ";
			}
			
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		
		$sql_order=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format

	}
	
	public function discount_update($dis_no)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$Discount_By=$user_name.'['.$user_id.']'.date('d-m-Y');

		if($dis_no==1)
		{
			$data['master'] = array( 
			'Discount_By' => $Discount_By, 
			'Discount_Remark' => $this->input->post('Discount_Remark'),
			'Discount' => $this->input->post('Discount')
			); 
		}elseif($dis_no==2){
			$data['master'] = array( 
			'Discount_By2' => $Discount_By, 
			'Discount_Remark2' => $this->input->post('Discount_Remark'),
			'Discount2' => $this->input->post('Discount')
			); 
		}elseif($dis_no==3){
			$data['master'] = array( 
			'Discount_By3' => $Discount_By, 
			'Discount_Remark3' => $this->input->post('Discount_Remark'),
			'Discount3' => $this->input->post('Discount')
			); 
		}

		$ipd_id=$this->input->post('Ipd_ID');
		$this->Ipd_M->update($data['master'],$ipd_id);
		
		$inser_id=1;
		
		$rvar=array(
		'insertid' =>$inser_id,
		'showcontent' => "Discount Status Updated."
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
 
	public function charge_update($dis_no)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$charge_by=$user_name.'['.$user_id.']'.date('d-m-Y');

		if($dis_no==1)
		{
			$data['master'] = array( 
			'charge_by1' => $charge_by, 
			'charge1' => $this->input->post('input_charge_remark1'),
			'chargeamount1' => $this->input->post('input_charge_Amount1')
			); 
		}elseif($dis_no==2){
			$data['master'] = array( 
			'charge_by2' => $charge_by, 
			'charge2' => $this->input->post('input_charge_remark2'),
			'chargeamount2' => $this->input->post('input_charge_Amount2')
			); 
		}
		
		$ipd_id=$this->input->post('Ipd_ID');
		$this->Ipd_M->update($data['master'],$ipd_id);
		
		$inser_id=1;
		
		$rvar=array(
		'insertid' =>$inser_id,
		'showcontent' => "Charge Status Updated."
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
 
	public function readmit($dis_no)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$charge_by=$user_name.'['.$user_id.']'.date('d-m-Y');

		if($dis_no==2)
		{
			$data['master'] = array( 
			'ipd_status' => '0'
			); 
		}
		
		$ipd_id=$this->input->post('Ipd_ID');
		$this->Ipd_M->update($data['master'],$ipd_id);
		
		$inser_id=1;
		
		$rvar=array(
		'insertid' =>$inser_id,
		'showcontent' => "Status Updated."
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
 
	public function add_bed_room()
	{
		$room_list=$this->input->post('room_list');
		$Ipd_ID=$this->input->post('Ipd_ID');
		
		
		$data = array( 
			'ipd_id' => $this->input->post('Ipd_ID'),
			'Fdate' => $this->input->post('start_datetime'),
			'TDate' => $this->input->post('end_datetime'),
            'bed_id' => $this->input->post('room_list')
		);

		$inser_id=$this->Ipd_M->bedassign($data); 
        
		$sql="select m.bed_no,m.room_name,b.Fdate,b.TDate,b.Remark
		from ipd_bed_assign b join hc_bed_master m on b.bed_id=m.id where b.ipd_id=".$Ipd_ID;
		
		$query = $this->db->query($sql);
        $room_assign_info= $query->result();
		
		$r_data='<div class="box-body">
							  <table id="example1" class="table table-bordered table-striped TableData">
								<thead>
								<tr>
								  <th>Bed No.</th>
								  <th>Description</th>
								  <th>Date</th>
								  <th>Vacant Date</th>
								</tr>
								</thead>
								<tbody>';

		for ($i = 0; $i < count($room_assign_info); ++$i) { 
			$r_data=$r_data.'<tr>
			  <td>'.$room_assign_info[$i]->bed_no.'</td>
			  <td>'.$room_assign_info[$i]->room_name.'</td>
			  <td>'.$room_assign_info[$i]->Fdate.'</td>
			  <td>'.$room_assign_info[$i]->TDate.'</td>
			 </tr>';
		}
		
		$r_data=$r_data.'</tbody></table></div>';
		
		$rvar=array(
		'insertid' =>$inser_id
		);
		$encode_data = json_encode($rvar);
		//echo $encode_data;
		echo $r_data;
		
	}
	
	public function list_med_inv($ipd_id)
	{
		$sql="select * from invoice_med_master where ipd_credit=1 and  ipd_id = ".$ipd_id;
		$query = $this->db->query($sql);
		$data['inv_master_credit']= $query->result();

		$data['ipd_id']=$ipd_id;
		
		$this->load->view('IPD2/IPD_Medical_Invoice_Panel',$data);
	}
	
	public function list_med_inv_details($ipd_id,$inv_med_id)
	{
		$sql="select * from invoice_med_master where ipd_credit=1 and id='".$inv_med_id."' and  ipd_id = ".$ipd_id;
		$query = $this->db->query($sql);
		$data['inv_master_credit']= $query->result();
		
		$sql="select * from inv_med_item where inv_med_id='".$inv_med_id."'";
		$query = $this->db->query($sql);
		$data['inv_master_credit_details']= $query->result();
		
		$this->load->view('IPD2/ipd_medical_invoice_short',$data);
	}
   
 
	public function Update_Invoice_ipd_credit_type()
	{
		$ipd_credit_type= $this->input->post('ipd_credit_type');
		$inv_id = $this->input->post('inv_id');
		
		$dataupdate = array( 
				'ipd_include' => $ipd_credit_type			
				);
		
		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$inv_id);
	}
 
 
 //Org. Update
 
	public function IPD_Invoice_Edit($IPD_ID)
	{
		$sql="select * from  v_ipd_list  
		where  id=".$IPD_ID;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();

		$ins_comp_id=$data['ipd_master'][0]->insurance_id;

		if($ins_comp_id=='' || $ins_comp_id == null)
		{
			$ins_comp_id=0;
		}

		$sql="select i.*,t.group_desc 
		from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
		where i.ipd_id=".$IPD_ID;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from ipd_invoice_item where ipd_id=".$IPD_ID;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$sql="select d.id ,d.p_fname ,concat(d.p_fname,' [',group_concat(m.SpecName),']') as DocSpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id order by d.p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from med_spec order by SpecName";
        $query = $this->db->query($sql);
        $data['med_spec']= $query->result();

		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=1   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_1']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		 where m.itype=2   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_2']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=5   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_5']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		 where m.itype=3   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_3']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=6   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_6']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=7   order by m.idesc ";
		$query = $this->db->query($sql);
        $data['item_list_7']= $query->result();
		
		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=8   order by m.idesc ";
		$query = $this->db->query($sql);
		$data['item_list_8']= $query->result();

		$sql="select m.id,Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',
		if(i.id is null,'',concat('[',i.code,']'))) as sdesc
		from (ipd_items m join ipd_item_type t on m.itype=t.itype_id)
		left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_comp_id and isdelete=0 
		where m.itype=10   order by m.idesc ";
		$query = $this->db->query($sql);
		$data['item_list_10']= $query->result();
		
		$sql="select * from ipd_item_type  ";
		$query = $this->db->query($sql);
        $data['item_list_cat']= $query->result();

		$data['ins_comp_id']=$ins_comp_id;

		$this->load->view('IPD2/ipd_invoice_items',$data);
    }
	
	public function ipd_package($ipd_id)
	{
		
		$sql="select * from  v_ipd_list  
		where  id=$ipd_id";
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();

		$ins_comp_id=$data['ipd_master'][0]->insurance_id;

		if($ins_comp_id=='' || $ins_comp_id == null)
		{
			$ins_comp_id=0;
		}

		
		$sql="SELECT m.id,m.ipd_pakage_name,m.Pakage_Min_Amount,i.hc_insurance_id,
		i.code,if(i.hc_insurance_id is null,m.Pakage_Min_Amount,i.i_amount) as amount1,
		if(i.hc_insurance_id is null,Concat(' Rs. ',m.Pakage_Min_Amount),Concat('[ Rs. ',i.i_amount,' /Org.Code ',i.code,']')) as org_code
		from package m left join package_insurance i
		on m.id=i.hc_items_id and i.hc_insurance_id=$ins_comp_id order by m.ipd_pakage_name" ;
		$query = $this->db->query($sql);
		$data['package_list']= $query->result();
	
		$sql="select d.id ,d.p_fname ,concat(d.p_fname,' [',group_concat(m.SpecName),']') as DocSpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id order by d.p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$this->load->view('IPD2/ipd_package',$data);
	}
	
	public function show_Package($ipd_id)
	{
		$sql="select * from  ipd_package where  ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_package']= $query->result();
		
		echo '<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Package Name</th>
				<th>Amount</th>
				<th></th>
			</tr>';
		$srno=0;
		$head=1;
		foreach($data['ipd_package'] as $row)
		{ 
		$srno=$srno+1;
			echo '<tr>';
			echo '<td>'.$srno.'</td>';
			echo '<td>'.$row->package_name.'<br><i>'.$row->package_desc.'</i></td>';
			echo '<td><input class="form-control" style="width:100px" name="input_amt_'.$row->id.'" id="input_amt_'.$row->id.'"  value="'.$row->package_Amount.'" type="number" /></td>';
			echo '<td>';
			echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')">Update</button>';
			echo '<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')">-Remove</button></td>';
			echo '</tr>';
		}
		
		echo '</table>';

	}
	
   	public function ipd_package_showitem($type)
	{
		
		
		$ipd_id=$this->input->post('ipd_id');
		$pack_id=$this->input->post('package_id');
		$package_name=$this->input->post('package_name');
		$comment=$this->input->post('comment');
		$package_desc=$this->input->post('comment');

		$sql="select * from  v_ipd_list  
		where  id=$ipd_id";
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();

		$ins_comp_id=$data['ipd_master'][0]->insurance_id;

		if($ins_comp_id=='' || $ins_comp_id == null)
		{
			$ins_comp_id=0;
		}
	
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.' D:'.date('d-m-Y h:i:s');

		if($type==1)
		{
			$sql="SELECT m.id,m.ipd_pakage_name,m.Pakage_Min_Amount,i.hc_insurance_id,
			i.code,if(i.hc_insurance_id is null,m.Pakage_Min_Amount,i.i_amount) as amount1,
			if(i.hc_insurance_id is null,Concat(' Rs. ',m.Pakage_Min_Amount),Concat('[ Rs. ',i.i_amount,' /Org.Code ',i.code,']')) as org_code,
			i.code
			from package m left join package_insurance i
			on m.id=i.hc_items_id and i.hc_insurance_id=$ins_comp_id 
			WHERE m.id=$pack_id";

			$query = $this->db->query($sql);
			$package= $query->result();

			$item_custom_amount=$this->input->post('input_amount');
			$date_item=$this->input->post('date_item');
			$org_code='';
		
			if($pack_id>1)
			{
				$package_desc=$package[0]->Pakage_description;
				$package_name=$package[0]->ipd_pakage_name;
				$item_amount=$package[0]->amount1;
				$org_code=$package[0]->code;
			}
			
			if($date_item=='')
			{
				$date_item=date('Y-m-d');
			}
			
			if($item_custom_amount>0)
			{
				$item_amount=$item_custom_amount;
			}
			
			$data['insert_package'] = array( 
				'ipd_id' => $ipd_id,
				'package_id' => $pack_id,
				'package_name' => $package_name,				
				'package_desc' => $package_desc,
				'package_Amount' => $item_amount,
				'comment' => $comment,
				'update_by' => $user_name,
				'ins_id'=> $ins_comp_id,
				'org_code'=> $org_code
				);
			$inser_id=$this->Ipd_M->insert_package( $data['insert_package']);

		}elseif($type==2)
		{
			$amount_value=$this->input->post('input_amount');
			$data['update_package'] = array( 
				'package_Amount' => $amount_value
			);
		
			$inser_id=$this->Ipd_M->update_package( $data['update_package'],$this->input->post('itemid'));
			
		}else{
			echo $this->Ipd_M->deletepackage($this->input->post('itemid'));
			//$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
			
	}
	
	public function doc_ipd_fee($doc_id)
	{
		$sql="select * from doc_ipd_fee where isdelete=0 and doc_fee_type=2 and  doc_id= ".$doc_id." order by doc_fee_desc ";
		$query = $this->db->query($sql);
        $doc_fee= $query->result();
		
		foreach($doc_fee as $row)
		{ 
			echo '<option value='.$row->amount.'>'.$row->doc_fee_desc.'[Rs. '.$row->amount.']</option>';
		}

	}
	
	public function ipd_items_bytype()
	{
		$tid=$this->input->post('itype_idv');

		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from ipd_items where itype=".$tid."   order by idesc ";
		
		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		echo '<div class="form-group">
				<label>Test Name</label>
					<select class="form-control input-sm" id="itype_name_id" name="itype_name_id"  >	';				
		foreach($data['labitem'] as $row)
		{ 
			echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
		}
		echo '</select></div>';
	}
	
	public function ipd_showitem($type)
	{
		
		
		$ipd_id=$this->input->post('ipd_id');
		$item_id=$this->input->post('itype_name_id');

		$ins_id=$this->input->post('ins_id');

		if($ins_id=='')
		{
			$ins_id=0;
		}
		
		if($type==1)
		{
			
			$sql="select m.id,m.idesc,
			if(i.id is null,'',i.code) as org_code, if(i.hc_insurance_id is null,m.amount,i.amount1) AS amount1 
			from (ipd_items m join ipd_item_type t on m.itype=t.itype_id) 
			left join ipd_items_insurance i on m.id=i.hc_items_id AND hc_insurance_id=$ins_id and isdelete=0  
			where m.id=$item_id ";
			$query = $this->db->query($sql);
			$itemlist= $query->result();

			if(count($itemlist)>0)
			{
				$item_rate=$itemlist[0]->amount1;
				$item_name=$itemlist[0]->idesc;
				$org_code=$itemlist[0]->org_code;
			}else{
				$item_rate=$this->input->post('input_rate');
				$item_name=$this->input->post('itype_name');
				$org_code='';
			}
			
			$item_custom_rate=$this->input->post('input_rate');
			$date_item=$this->input->post('date_item');

			if($date_item=='')
			{
				$date_item=date('Y-m-d');
			}
			
			if($item_custom_rate>0)
			{
				$item_rate=$item_custom_rate;
			}
			
			$amount_value=$this->input->post('input_qty')*$item_rate;
			
			$doc_id=$this->input->post('doc_id');
			
			if($doc_id<1)
			{
				$refername=$this->input->post('refername');
			}else{
				$sql="select * from doctor_master where id=".$doc_id;
				$query = $this->db->query($sql);
				$data['doc_master']= $query->result();
				$refername=$data['doc_master'][0]->p_fname;
			}
		
			$item_id=$this->input->post('itype_name_id');

			$data['insert_invoice_item'] = array( 
				'ipd_id' => $ipd_id,
				'item_type' => $this->input->post('itype_idv'),
				'item_id' => $this->input->post('itype_name_id'),				
				'item_name' => $item_name,
				'item_rate' => $item_rate,
				'item_added_date' => date('Y-m-d h:i:s'),
				'item_qty' => $this->input->post('input_qty'),
				'item_amount' => $amount_value,
				'doc_id' => $this->input->post('doc_id'),
				'doc_name' => $refername,
				'date_item' => $date_item,
				'comment' => $this->input->post('comment'),
				'ins_id' => $ins_id,
				'org_code' => $org_code,
			);

			$sql="Select * from ipd_invoice_item 
			where ipd_id=$ipd_id and item_id=$item_id and item_rate=$item_rate and doc_id=$doc_id" ;
			$query = $this->db->query($sql);
			$rec_exist= $query->result();
			
			if(count($rec_exist)>0)
			{
				$inser_id=0;
			}else{
				$inser_id=$this->Ipd_M->insert_ipd_item( $data['insert_invoice_item']);
			}
		

		}elseif($type==2)
		{
			$amount_value=$this->input->post('update_qty') * $this->input->post('item_rate');

			$data['update_invoice_item'] = array( 
				'item_qty' => $this->input->post('update_qty'),
				'item_amount' => $amount_value
			);
			
			$inser_id=$this->Ipd_M->update_ipd_item( $data['update_invoice_item'],$this->input->post('itemid'));
			
		}else{
			
			$inser_id= $this->Ipd_M->deleteIpdinvoiceitem($this->input->post('itemid'));
			//$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}

		echo $inser_id;
			
	}
	
	public function show_ipd_items($ipd_id)
	{
		$sql="select * from  v_ipd_list where  id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();

		$sql="select * from  ipd_package where  ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_package']= $query->result();

		//package_select
		$select_package_content="";
		
		$ins_comp_id=$data['ipd_master'][0]->insurance_id;

		$sql="select t.group_desc,i.*,sum(i.item_amount) as xAmount
		from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
		where i.ipd_id=".$ipd_id."
		group by i.item_type,i.id with rollup";
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from ipd_invoice_item where ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		echo '<table class="table table-striped table-sm table-condensed">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>';
		$srno=0;
		$head=1;
		foreach($data['invoiceDetails'] as $row)
		{ 
			if($head==1 && $row->item_type<>null) 
			{
				echo '<tr style="background-color:orange;">';
				echo '<th>#</th>';
				echo '<th colspan="4">'.$row->group_desc.'</th>';
				echo '</tr>';
				$head=0;
			}
			
			if($row->item_type==null || $row->item_type=='')
				{
					echo '<tr>';
					echo '<th>#</th>';
					echo '<th colspan="3">Total Amount</th>';
					echo '<th>'.$row->xAmount.'</th>';
					echo '</tr>';
					
				}elseif($row->id==null || $row->id==''){
					echo '<tr>';
					echo '<td>#</td>';
					echo '<td colspan="3">Sub Total</td>';
					echo '<td>'.$row->xAmount.'</td>';
					echo '</tr>';
					$head=1;
				}else{
					$srno=$srno+1;
					
					$doc_name_desc="";
					if($row->doc_id>0)
					{
						$doc_name_desc='<br>Dr.'.$row->doc_name.' '.$row->doc_spec;
					}

					$check='';
					if ($row->package_id>0)
					{
						$check='Checked';
					}

					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_name.'</td>';
					echo '<td><input type=hidden name="hidden_rate_'.$row->id.'" id="hidden_rate_'.$row->id.'"  value="'.$row->item_rate.'" >'.$row->item_rate.'</td>';
					echo '<td><input class="form-control input_sm" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->item_qty.'" type="number" /></td>';
					echo '<td>'.$row->item_amount.'</td>';
					echo '</tr>';
					
					echo '<tr>';
					$check_box='';
					if(count($data['ipd_package']))
					{
						$check_box='<input type="checkbox"  onchange="onChangeUpdate(this,'.$row->id.')" '.$check.' > Inc. In Pkg.';
					}

					echo '<td>#</td>';
					echo '<td colspan="2">'.$doc_name_desc.'<br><i>'.$row->comment.'</i></td>';
					echo '<td colspan="2">'.$check_box;
					echo '<div class="btn-group">
							<button type="button" class="btn btn-warning" onclick="update_qty('.$row->id.')"><i class="fa fa-edit"></i></button>
							<button type="button" class="btn btn-danger"><i class="fa fa-remove" onclick="remove_item_invoice('.$row->id.')"></i></button>
							
						</div>';
					echo '</td>';
					echo '</tr>';

				}
		}	
		echo '</table>';
		echo '<input type="hidden" id="srno" name="srno" value="'.count($data['invoiceDetails']).'" />';
				
	}


	public function remove_refer($r_id)
	{
		if ($this->ion_auth->in_group('IPDReferUpdate')) { 
			$this->Ipd_M->remove_refer_ipd($r_id);
		}
	}
	
	public function add_refer($ipd_id,$refer_by)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$Update_user = $user->first_name.' '. $user->last_name.' ['.$user_id.'] '.date('Y-m-d H:i:s');

		if ($this->ion_auth->in_group('IPDAdmit')) { 

			$sql="select *
			from refer_master 
			where id=$refer_by";
			$query = $this->db->query($sql);
			$refer_master= $query->result();

			if(count($refer_master)>0){
				if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
				
                
				$data = array( 
                    'ipd_id' => $ipd_id, 
                	'refer_by' => $refer_by,
					'refer_type' => $refer_master[0]->refer_type,
					'refer_name' => $refer_master[0]->f_name,
					'update_by' => $Update_user,
                 ); 
				$inser_id=$this->Ipd_M->insert_refer_ipd($data);
			}
			
			
		}
	}

	public function Update_Refer($ipd_id,$refer_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		
		
		$update_refer=array(
			'refer_by'=>$refer_id
		);
		
		$this->Ipd_M->update($update_refer,$ipd_id);
		
	}
	
	public function update_org_doc()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
		$org_id=$this->input->post('org_id');
		$feild=$this->input->post('feild');
		$fvalue=$this->input->post('fvalue');
		
		$this->load->model('Ocasemaster_M');
		
		$update_ipd=array(
			$feild=>$fvalue
		);
		
		$this->Ocasemaster_M->update($update_ipd,$org_id);
		
	}
 
 }