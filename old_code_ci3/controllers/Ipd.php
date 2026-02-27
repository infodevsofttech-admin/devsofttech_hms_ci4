<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ipd extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function IpdDashboard()
	{
		$this->load->view('IPD/ipd_dashboard');
	}
	
	public function ipd_panel_payment($ipdno)
    {
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
		
		$sql="select *,Date_Format(pay_date,'%d-%M') as pay_date_str,
		case payment_mode when 1 then 'Cash' 
		when 2 then 'Bank Card' 
		when 3 then 'Cash Return' 
		when 4 then 'Bank Return' 
		else 'Other' end  as pay_mode from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment']= $query->result();
		
		$sql="select * from invoice_med_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_master']= $query->result();

		$sql="select i.id as inv_id,i.invoice_code,sum(t.item_amount) as amount,i.refer_by_other,
			date_format(i.inv_date,'%d-%m-%Y') as str_date,i.ipd_include,
			group_concat(distinct c.group_desc) as charge_list
			from invoice_master i left join
			(invoice_item t join hc_item_type c on t.item_type=c.itype_id)
			on i.id =t.inv_master_id   
			where  payment_status=1  and ipd_id = ".$ipdno."	group by i.id ";

		$query = $this->db->query($sql);
		$data['inv_master']= $query->result();

		$total_med=0.00;
		$total_charges=0.00;

		$sql="select sum(net_amount) as tnet_amount from invoice_med_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total']= $query->result();
		
		$total_med=$data['inv_med_total'][0]->tnet_amount;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_include=0 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_not_total']= $query->result();
		
		$sql="select sum(net_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_include=1  and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_total']= $query->result();
		
		$total_charges=$data['inv_charge_total'][0]->tnet_amount;
		
		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as t_ipd_pay from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
		$data['inv_payment_total']= $query->result();
		
		$total_payment=$data['inv_payment_total'][0]->t_ipd_pay;
		
		$balance=$total_payment-($total_charges+$tcharge-$tdiscount);
		
		$data['inv_total']=array(
		'total_med' => $total_med,
		'total_charges' => $total_charges,
		'total_payment' => $total_payment,
		'ipd_id' => $ipdno,
		'discount' => $tdiscount,
		'balance' => $balance,
		'charge' => $tcharge
		);
		
		$this->load->view('IPD/ipd_payment',$data);
	}
	
	public function ipd_panel_invoice_show()
    {
		$inv_id=$this->input->post('invid');
		$inv_type=$this->input->post('invtype');
		
		if($inv_type == 0)
		{
				redirect('InvoiceShow/invoice_med/'.$inv_id);
		}else{
				redirect('InvoiceShow/invoice_charges/'.$inv_id);
		}
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

		$this->load->view('IPD/ipd_account_panel',$data);
		
	}
	
	public function ipd_complete_invoice($ipdno,$print=0)
	{
		$this->db->query("CALL p_cal_ipd_master(".$ipdno.")");	
		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipdmaster']= $query->result();
		
		$sql="select * from v_ipd_list where id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
		
		$sql="select b.ipd_id,b.bed_id,b.TDate,m.room_name,m.bed_no
				from ipd_bed_assign b left join hc_bed_master m on b.bed_id=m.id
				where  b.ipd_id=".$ipdno." order by TDate desc";
		$query = $this->db->query($sql);
        $data['bed_list']= $query->result();
		
		$pno=$data['ipdmaster'][0]->p_id;
		$case_id=$data['ipdmaster'][0]->case_id;
		
		$discount=$data['ipdmaster'][0]->Discount;
		$discount2=$data['ipdmaster'][0]->Discount2;
		$discount3=$data['ipdmaster'][0]->Discount3;

		$charge1=$data['ipdmaster'][0]->chargeamount1;
		$charge2=$data['ipdmaster'][0]->chargeamount2;
		$tcharge=$charge1+$charge2;
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$total_med=0.00;
		$total_charges=0.00;
		
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
		
		$sql="select * from v_ipd_bill_invoice v
				where v.ipd_id=".$ipdno."  order by  v.Charge_type ";

		$query = $this->db->query($sql);
        $data['showinvoice']= $query->result();
		
		$sql="select * from invoice_med_master where  ipd_credit=1 and ipd_credit_type=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_list']= $query->result();
		
		$sql="select sum(net_amount) as tnet_amount from invoice_med_master 
		where  ipd_credit=1 and ipd_credit_type=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total']= $query->result();
		
		$total_med=$data['inv_med_total'][0]->tnet_amount;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master 
		where  payment_status=1 and ipd_include=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_total']= $query->result();
			
		$sql="select *,h.id as pay_id,Date_Format(pay_date,'%d-%M') as pay_date_str,
		case p.payment_mode when 1 then 'Cash' 
		when 2 then 'Bank Card' 
		when 3 then 'Return Cash ' 
		when 4 then 'Bank Return' else 'Other' end  as pay_mode 
		from ipd_payment p join payment_history h on p.payment_id=h.id and h.payof_type=4  where  p.ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment']= $query->result();

		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as t_ipd_pay 
		from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment_total']= $query->result();
		
		$total_charges=$data['inv_charge_total'][0]->tnet_amount;
		
		$total_payment=$data['ipd_payment_total'][0]->t_ipd_pay;
		
		$total_gross_amount=($total_charges+round($total_med,0));
		
		$tdiscount=$discount+$discount2+$discount3;
		
		$net_amount=($total_gross_amount+$tcharge)-$tdiscount;
		
		$balance=$net_amount-$total_payment;
		
		$data['inv_total']=array(
		'total_med' => $total_med,
		'total_charges' => $total_gross_amount,
		'total_payment' => $total_payment,
		'ipd_id' => $ipdno,
		'discount' => $tdiscount,
		'charge'=>$tcharge,
		'balance' => $balance,
		'net_amount' => $net_amount
		);
		
		
		$Check_Discount=1;
		
		if($print==1)
		{
			$this->load->view('IPD/Ipd_invoice_print_V',$data);

		}elseif($print==2){
			$this->load->view('IPD/ipd_invoice_print_wo_payment',$data);
		}else{
			$this->load->view('IPD/Ipd_invoice_V',$data);
		}
		
	}
	
	public function ipd_short_invoice($ipdno,$print=0)
	{
		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
        $data['ipdmaster']= $query->result();
		
		$pno=$data['ipdmaster'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)  AS age from patient_master where id='".$pno."' ";

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
			$this->load->view('IPD/Ipd_invoice_print_V',$data);
			
		}else{
			$this->load->view('IPD/Ipd_invoice_V',$data);
		}
	}
	
	public function ipd_confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Ipd_M');
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

			$ipdpaymentdata = array( 
					'payment_mode'=> '1',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Cash',
					'pay_date'=>$pay_datetime,
					'enter_pay_date' => date('Y-m-d H:i:s'),
					'ipd_id' => $ipd_id,
					'amount' => $amount,
					'remark'=>$pay_remark,
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);

			$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $ipdpaymentdata);

			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_ipdpay_id,
			'pay_date' =>$pay_datetime
			);

			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
		if($this->input->post('mode')==2)
		{
			
			$FormRules = array(
                array(
                    'field' => 'input_card_mac',
                    'label' => 'Card Bank Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
				/*,
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
                array(
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[1]|max_length[15]'
                )*/
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
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_mode_desc'=>'Bank Card',
						'payment_id'=>$insert_id,
						'pay_date'=>$pay_datetime,
						'enter_pay_date' => date('Y-m-d H:i:s'),
						'ipd_id' => $ipd_id,
						'amount' => $amount,
						'card_bank'=>$this->input->post('input_card_mac'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_card_bank'),
						'card_tran_id'=>$this->input->post('input_card_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']',
						'prepared_by_id'=>$user_id
				);
				
				$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $data);
				
				$status='Bank Card';
				
								
				$rvar=array(
				'update' =>1,
				'ipd_id'=> $ipd_id,
				'payid' => $insert_ipdpay_id
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
		
		
		if($this->input->post('mode')==5)
		{
			$FormRules = array(
                array(
                    'field' => 'input_person_name',
                    'label' => 'Name on Chq.',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_bank_tran',
					'label' => 'Chq. Number',
                    'rules' => 'required|min_length[1]|max_length[15]'
                )
				/*,
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
                */
            );
			
			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
				$pay_remark='Chq Payment';
				
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
					'card_bank'=>$this->input->post('input_bank_hospital'),
					'cust_card'=>$this->input->post('input_bank_name'),
					'card_remark'=>$this->input->post('input_person_name'),
					'card_tran_id'=>$this->input->post('input_bank_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_mode_desc'=>'Bank Chq.-Draft',
						'payment_id'=>$insert_id,
						'pay_date'=>$pay_datetime,
						'enter_pay_date' => date('Y-m-d H:i:s'),
						'ipd_id' => $ipd_id,
						'amount' => $amount,
						'card_bank'=>$this->input->post('input_bank_hospital'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_bank_name'),
						'card_tran_id'=>$this->input->post('input_bank_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']',
						'prepared_by_id'=>$user_id
				);
				
				$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $data);
				
				$status='Bank Card';
				
								
				$rvar=array(
				'update' =>1,
				'ipd_id'=> $ipd_id,
				'payid' => $insert_ipdpay_id
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
		
		$this->load->model('Ipd_M');
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

			$ipdpaymentdata = array( 
					'payment_mode'=> '3',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Cash Return',
					'pay_date'=>date('Y-m-d H:i:s'),
					'ipd_id' => $ipd_id,
					'credit_debit'=>'1',
					'amount' => $amount,
					'remark'=>$pay_remark,
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);

			$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $ipdpaymentdata);

			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_ipdpay_id
			);

			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
		
		if($this->input->post('mode')==4)
		{
			$paydata = array( 
					'payment_mode'=> '4',
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

			$ipdpaymentdata = array( 
					'payment_mode'=> '4',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Bank Return',
					'pay_date'=>date('Y-m-d H:i:s'),
					'ipd_id' => $ipd_id,
					'credit_debit'=>'1',
					'amount' => $amount,
					'remark'=>$pay_remark,
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);

			$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $ipdpaymentdata);

			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_ipdpay_id
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
		IFNULL(GET_AGE_BY_DOB(dob),age)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select i.id,d.p_fname,d.id as doc_id from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['ipd_info'][0]->p_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from org_approved_status ";
        $query = $this->db->query($sql);
        $data['org_approved_status']= $query->result();

		$this->load->view('IPD/ipd_main_panel',$data);
	}
	
	public function ipd_account_panel($ipdno)
	{
		
		$sql="select * from ipd_master where id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();
		
		$discount=$data['ipd_info'][0]->Discount;
		$discount2=$data['ipd_info'][0]->Discount2;
		$discount3=$data['ipd_info'][0]->Discount3;
		
		$charge1=$data['ipd_info'][0]->chargeamount1;
		$charge2=$data['ipd_info'][0]->chargeamount2;
		$tcharge=$charge1+$charge2;

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select * from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['ipd_info'][0]->p_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$total_med=0.00;
		$total_charges=0.00;
		
		$sql="select sum(net_amount) as tnet_amount from invoice_med_master where  ipd_credit=1 and ipd_credit_type>0 and  ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total_credit']= $query->result();
		
		$sql="select sum(net_amount) as tnet_amount,sum(payment_received) as t_payment_received from invoice_med_master where  ipd_credit=0 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total_cash']= $query->result();
		
		$total_med_credit=$data['inv_med_total_credit'][0]->tnet_amount;
		$total_med_cash=$data['inv_med_total_cash'][0]->tnet_amount;
		$total_med_cash_paid=$data['inv_med_total_cash'][0]->t_payment_received;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_include=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_total']= $query->result();
		
		$total_charges=$data['inv_charge_total'][0]->tnet_amount;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as tnet_amount from ipd_payment where   ipd_id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$data['inv_payment_total']= $query->result();
		
		$total_payment=$data['inv_payment_total'][0]->tnet_amount;
		$tdiscount=$discount+$discount2+$discount3;
		
		$total_balance_amount=$total_payment-($total_charges+$total_med_credit+$tcharge-$tdiscount);

		$data['inv_total']=array(
		'total_med_credit' => $total_med_credit,
		'total_med_cash' => $total_med_cash,
		'total_med_cash_paid' => $total_med_cash_paid,
		'total_charges' => $total_charges,
		'total_payment' => $total_payment,
		'total_balance_amount' => $total_balance_amount,
		'discount' => $tdiscount,
		'charge' => $tcharge,
		'ipd_id' => $ipdno
		);
		

		$this->load->view('IPD/ipd_account_panel',$data);
	}
	
	public function ipd_panel($ipdcode,$stype=0)
    {
		if($stype > 0)
		{
			$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days from ipd_master where ipd_code='".$ipdcode."' ";
		}else{
			$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days from ipd_master where id='".$ipdcode."' ";
		}

        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();
		
		if($stype > 0)
		{
			$sql="select * from v_ipd_list where ipd_code='".$ipdcode."' ";
			
		}else{
			$sql="select * from v_ipd_list where id='".$ipdcode."' ";
		}
		
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
				
		$discount=$data['ipd_info'][0]->Discount;
		$discount2=$data['ipd_info'][0]->Discount2;
		$discount3=$data['ipd_info'][0]->Discount3;
		
		$charge1=$data['ipd_info'][0]->chargeamount1;
		$charge2=$data['ipd_info'][0]->chargeamount2;
		$tcharge=$charge1+$charge2;
				
		$ipdno=$data['ipd_info'][0]->id;

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select * from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['ipd_info'][0]->p_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$total_med=0.00;
		$total_charges=0.00;
		
		$sql="select sum(net_amount) as tnet_amount from invoice_med_master where  ipd_credit=1 and ipd_credit_type>0 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total_credit']= $query->result();
		
		$sql="select sum(net_amount) as tnet_amount,sum(payment_received) as t_payment_received from invoice_med_master where  ipd_credit=0 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_med_total_cash']= $query->result();

		$sql="select * from invoice_med_master where ipd_credit=1 and  ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_master_credit']= $query->result();
		
		$total_med_credit=$data['inv_med_total_credit'][0]->tnet_amount;
		$total_med_cash=$data['inv_med_total_cash'][0]->tnet_amount;
		$total_med_cash_paid=$data['inv_med_total_cash'][0]->t_payment_received;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_include=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$data['inv_charge_total']= $query->result();
		
		$total_charges=$data['inv_charge_total'][0]->tnet_amount;
		
		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as tnet_amount from ipd_payment where   ipd_id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$data['inv_payment_total']= $query->result();
		
		$total_payment=$data['inv_payment_total'][0]->tnet_amount;
		
		$tdiscount=$discount+$discount2+$discount3;
		
		$total_balance_amount=$total_payment-($total_charges+$total_med_credit+$tcharge-($tdiscount));

		$data['inv_total']=array(
		'total_med_credit' => $total_med_credit,
		'total_med_cash' => $total_med_cash,
		'total_med_cash_paid' => $total_med_cash_paid,
		'total_charges' => $total_charges,
		'total_payment' => $total_payment,
		'total_balance_amount' => $total_balance_amount,
		'discount' => $tdiscount,
		'charge' => $tcharge,
		'ipd_id' => $ipdno
		);
		
		$sql="select *,Date_Format(pay_date,'%d-%M') as pay_date_str,case payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Cash Return' else 'Other' end  as pay_mode from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment']= $query->result();

		if ($this->ion_auth->in_group('admin1') || $this->ion_auth->in_group('IPDAdmit') ) { 
			if($data['ipd_info'][0]->ipd_status==0)
			{
				$this->load->view('IPD/Ipdpanel_V',$data);
			}else{
				if($this->ion_auth->in_group('IPDReAdmit'))
				{
					$this->load->view('IPD/Ipdpanel_V',$data);
				}else{
					$this->load->view('IPD/IPD_View',$data);
				}
			}
		}else{
			$this->load->view('IPD/IPD_View',$data);
		}
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
		
		$this->load->view('IPD/ipd_bed_status',$data);
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
                
				$this->load->model('Ipd_M');
                
				$data = array( 
                    'ipd_id' => $ipd_id, 
                	'doc_id' => $doc_id
                 ); 
				$inser_id=$this->Ipd_M->add_ipd_doc($data);
		}
	}
	
	public function show_ipd_form1($ipdno)
	{
		$sql="select *,if(case_type=1,'MLC','Non MLC') as case_type_s, 
		Date_format(register_date,'%d-%m-%Y') as str_register_date ,
		Date_format(discharge_date,'%d-%m-%Y') as str_discharge_date
		from ipd_master where id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
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
		$content=$this->load->view('IPD/print_ipd_form1',$data,TRUE);
       	
        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");

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

			redirect('Ipd/ipd_panel/'.$data['ipd_info'][0]->id);
		}else{
			$sql="select *,if(gender=1,'Male','FeMale') as xgender,
			IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";
            
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
			where status=0 and ipd_id=0  and case_type=1 and p_id=".$pno;
			$query = $this->db->query($sql);
			$data['case_master']= $query->result();

			$this->load->view('IPD/Ipd_Registration_V',$data);
		}
	
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

		
		$this->load->view('IPD/ipd_bed_assign',$data);
		
	}
	
	public function ipd_cash_print($ipd_id,$payid,$print=0)
	{
		$sql="select * from ipd_payment where id=".$payid;
		$query = $this->db->query($sql);
		$data['ipd_payment']= $query->result();

		$payment_id=$data['ipd_payment'][0]->payment_id;
		
		$sql="select * from ipd_master where id =".$data['ipd_payment'][0]->ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$pno=$data['ipd_master'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		if($print==0)
		{
			$this->load->view('IPD/print_payment_receipt_ipd',$data);
		}else{
			redirect('IpdNew/ipd_cash_print_pdf/'.$ipd_id.'/'.$payment_id);
			//$this->load->view('IPD/ipd_payment_receipt_print',$data);
		}
	}
	
	public function load_model_box($ipd_id)
	{
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days,if(case_type=1,'MLC','Non MLC') as case_type_s, Date_format(register_date,'%d-%m-%Y') as str_register_date from ipd_master where id='".$ipd_id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) as str_age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$this->load->view('IPD/ipd_model_payment_box',$data);
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
		$this->load->view('IPD/ipd_panel_deduction_payment');
	}
		
    public function AddNew() { 
         if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                $this->load->model('Ipd_M');

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
					'reg_time' => $this->input->post('res_time')
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
			
                $rvar=array(
                'insertid' =>$inser_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
   }
   
    public function discharge_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
				$this->load->model('Ipd_M');
				$this->load->model('Ocasemaster_M');
				
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
				
				//Update Org. Status Update
				$sql="select * from ipd_master where id='".$ipd_id."' ";
				$query = $this->db->query($sql);
				$ipd_info= $query->result();
				
				if($ipd_info[0]->case_id>0)
				{
					$case_id=$ipd_info[0]->case_id;
					
					$sql="select * from organization_case_master where id='".$case_id."'";
					$query = $this->db->query($sql);
					$case_master= $query->result();
					
					if(count($case_master)>0)
					{
						$log=$case_master[0]->log.'\nUpdate Status:'.$this->input->post('status').'-Upd By:'.$user_name.'['.$user_id.']'.date('d/m/Y H:m:s');
					
						$data = array( 
							'status'=> 1,
							'log'=>$log,
						);
						
						$this->Ocasemaster_M->update( $data,$case_id);
					}
					
				}
			
			//Update Org. Stop
				
				$inser_id=1;
                
                $rvar=array(
                'insertid' =>$inser_id,
				'showcontent' => "Discharge Status Updated."
                );
				
                $encode_data = json_encode($rvar);
                echo $encode_data;
	}
	
	public function discharge_request()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                $this->load->model('Ipd_M');
				
				$user = $this->ion_auth->user()->row();
				$user_id = $user->id;
				$user_name = $user->first_name.''. $user->last_name;
				
				$discharge_by=$user_name.'['.$user_id.']';
				
                $data['master'] = array( 
					'ipd_status' => '0',
					'discharge_date' => str_to_MysqlDate($this->input->post('dis_date')),
					'discharge_time' => $this->input->post('dis_time'),
					'discarge_patient_status' => $this->input->post('p_status'),
					'dept_id' => $this->input->post('dept_id')
                ); 
				
				$ipd_id=$this->input->post('Ipd_ID');
				$this->Ipd_M->update($data['master'],$ipd_id);
				
                echo "Discharge Status Updated";
	}

	public function update_surgery()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		 $this->load->model('Ipd_M');
				

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$ipd_id=$this->input->post('ipd_id');
		$issurgery=$this->input->post('issurgery');
		$surgery_name=$this->input->post('surgery_name');
		$surgery_date=$this->input->post('surgery_date');

		

		$content="";

		$dataupdate = array( 
			'issurgery' => $issurgery,
			'surgery_name' => $surgery_name,
			'surgery_date' => str_to_MysqlDate($surgery_date)
		);
	
		$this->Ipd_M->update($dataupdate,$ipd_id);
		
		$content="Update Done";
	
		
		echo $content;
	}


	public function update_delivery()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		 $this->load->model('Ipd_M');
				
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$ipd_id=$this->input->post('ipd_id');
		$isdelivery=$this->input->post('isdelivery');
		$delivery_date=$this->input->post('delivery_date');
		$delivery_time=$this->input->post('delivery_time');
		$delivery_sex_of_baby=$this->input->post('delivery_sex_of_baby');

				
		$dataupdate = array( 
			'isdelivery' => $isdelivery,
			'delivery_time' => $delivery_time,
			'delivery_date' => str_to_MysqlDate($delivery_date),
			'delivery_sex_of_baby' => $delivery_sex_of_baby
		);
			
		$this->Ipd_M->update($dataupdate,$ipd_id);
		$content="Update Done";
		
		echo $content;
	}
	
	
	 public function update_ipdadmit_time()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                $this->load->model('Ipd_M');
				
				$user = $this->ion_auth->user()->row();
				$user_id = $user->id;
				$user_name = $user->first_name.''. $user->last_name;

				$discharge_by=$user_name.'['.$user_id.']';
				
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
		IFNULL(GET_AGE_BY_DOB(dob),age) AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
	

		if($data['ipd_info'][0]->ipd_status == 0 )
		{
			$this->load->view('IPD/ipd_discharge',$data);
		}else{
			$this->load->view('IPD/ipd_discharged_status',$data);
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
			3=> 'P_mobile1',
			4=> 'doc_name',
			5=> 'Disstatus',
			6=> 'str_register_date',
			7=> 'str_discharge_date',
			8=> 'Disstatus'
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
			$sql_where.=" AND P_mobile1 LIKE '%".$requestData['columns'][3]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][4]['search']['value']) ){  //P_mobile1
			$sql_where.=" AND doc_name LIKE '%".$requestData['columns'][4]['search']['value']."%' ";
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
		
		$this->load->model('Ipd_M');
		
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
		
		$this->load->model('Ipd_M');
		
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
		
		$this->load->model('Ipd_M');
		
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
		
		$this->load->model('Ipd_M');
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
		
		$this->load->view('IPD/IPD_Medical_Invoice_Panel',$data);
	}
	
	public function list_med_inv_details($ipd_id,$inv_med_id)
	{
		$sql="select * from invoice_med_master where ipd_credit=1 and id='".$inv_med_id."' and  ipd_id = ".$ipd_id;
		$query = $this->db->query($sql);
		$data['inv_master_credit']= $query->result();
		
		$sql="select * from inv_med_item where inv_med_id='".$inv_med_id."'";
		$query = $this->db->query($sql);
		$data['inv_master_credit_details']= $query->result();
		
		$this->load->view('IPD/ipd_medical_invoice_short',$data);
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

		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=40   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_1']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=34   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_2']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=27   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_5']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=26   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_3']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=35   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_6']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=43   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_7']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc 
		from hc_items where itype=3   order by idesc ";
		$query = $this->db->query($sql);
        $data['item_list_8']= $query->result();

		$this->load->view('IPD/ipd_invoice_items',$data);
    }
	
	public function ipd_package($ipd_id)
	{
		$sql="select * from  package where id>1 and active=1";
		$query = $this->db->query($sql);
		$data['package_list']= $query->result();
	
		$sql="select d.id ,d.p_fname ,concat(d.p_fname,' [',group_concat(m.SpecName),']') as DocSpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id order by d.p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$this->load->view('IPD/ipd_package',$data);
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
		$this->load->model('Ipd_M');
		
		$ipd_id=$this->input->post('ipd_id');
		$pack_id=$this->input->post('package_id');
		$package_name=$this->input->post('package_name');
		$comment=$this->input->post('comment');
		$package_desc=$this->input->post('comment');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.' D:'.date('d-m-Y h:i:s');
			
		if($type==1)
		{
			$sql="select * from package where id=".$pack_id;
			$query = $this->db->query($sql);
			$package= $query->result();

			$item_custom_amount=$this->input->post('input_amount');
			$date_item=$this->input->post('date_item');
		
			if($pack_id>1)
			{
				$package_desc=$package[0]->Pakage_description;
				$package_name=$package[0]->ipd_pakage_name;
				$item_amount=$package[0]->Pakage_Min_Amount;
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
				'update_by' => $user_name
				);
			$inser_id=$this->Ipd_M->insert_package( $data['insert_package']);

		}elseif($type==2)
		{
			$amount_value=$this->input->post('input_amount');

			$data['update_package'] = array( 
				'item_amount' => $amount_value
			);
			
			$inser_id=$this->Ipd_M->update_package( $data['update_package'],$this->input->post('itemid'));
			
		}else{
			
			echo $this->Ipd_M->deletepackage($this->input->post('itemid'));
			//$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
			
	}
	
	public function doc_ipd_fee($doc_id)
	{
		$sql="select * from doc_ipd_fee where isdelete=0 and   doc_id= ".$doc_id." order by doc_fee_desc ";
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
		$this->load->model('Ipd_M');
		
		$ipd_id=$this->input->post('ipd_id');
		
		if($type==1)
		{
			
			$sql="select * from ipd_items where id=".$this->input->post('itype_name_id');
			$query = $this->db->query($sql);
			$data['itemlist']= $query->result();

			$item_rate=$data['itemlist'][0]->amount;

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
		
			$data['insert_invoice_item'] = array( 
				'ipd_id' => $ipd_id,
				'item_type' => $this->input->post('itype_idv'),
				'item_id' => $this->input->post('itype_name_id'),				
				'item_name' => $data['itemlist'][0]->idesc,
				'item_rate' => $item_rate,
				'item_added_date' => date('Y-m-d h:i:s'),
				'item_qty' => $this->input->post('input_qty'),
				'item_amount' => $amount_value,
				'doc_id' => $this->input->post('doc_id'),
				'doc_name' => $refername,
				'date_item' => $date_item,
				'comment' => $this->input->post('comment')
				);
			$inser_id=$this->Ipd_M->insert_ipd_item( $data['insert_invoice_item']);

		}elseif($type==2)
		{
			$amount_value=$this->input->post('update_qty') * $this->input->post('item_rate');

			$data['update_invoice_item'] = array( 
				'item_added_date' => date('Y-m-d h:i:s'),
				'item_qty' => $this->input->post('update_qty'),
				'item_amount' => $amount_value
			);
			
			$inser_id=$this->Ipd_M->update_ipd_item( $data['update_invoice_item'],$this->input->post('itemid'));
			
		}else{
			
			echo $this->Ipd_M->deleteIpdinvoiceitem($this->input->post('itemid'));
			//$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
			
	}
	
	public function show_ipd_items($ipd_id)
	{
		$sql="select * from  v_ipd_list where  id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();

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
		
		echo '<table class="table table-striped ">
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
				echo '<th></th>';
				echo '</tr>';
				$head=0;
			}
			
			if($row->item_type==null || $row->item_type=='')
				{
					echo '<tr>';
					echo '<th>#</th>';
					echo '<th colspan="3">Total Amount</th>';
					echo '<th>'.$row->xAmount.'</th>';
					echo '<td></td>';
					echo '</tr>';
					
				}elseif($row->id==null || $row->id==''){
					echo '<tr>';
					echo '<td>#</td>';
					echo '<td colspan="3">Sub Total</td>';
					echo '<td>'.$row->xAmount.'</td>';
					echo '<td></td>';
					echo '</tr>';
					$head=1;
				}else{
					$srno=$srno+1;
					echo '<tr>';
					echo '<td>'.$srno.'</td>';
					echo '<td>'.$row->item_name.'<br><i>'.$row->comment.'</i></td>';
					echo '<td><input type=hidden name="hidden_rate_'.$row->id.'" id="hidden_rate_'.$row->id.'"  value="'.$row->item_rate.'" >'.$row->item_rate.'</td>';
					echo '<td><input class="form-control" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->item_qty.'" type="number" /></td>';
					echo '<td>'.$row->item_amount.'</td>';
					echo '<td>';
					echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')">Update</button>';
					echo '<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')">-Remove</button></td>';
					echo '</tr>';
				}
			
		
			
		}	
		echo '</table>';
		echo '<input type="hidden" id="srno" name="srno" value="'.count($data['invoiceDetails']).'" />';
		
		
	}
	
   
   public function add_ipd_org($ipd_id,$org_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                
				$this->load->model('Ipd_M');
				
				$update_ipd=array(
					'case_id'=>$org_id
				);
				
				$this->Ipd_M->update($update_ipd,$ipd_id);
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