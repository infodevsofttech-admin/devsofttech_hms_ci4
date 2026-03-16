<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PathLab extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Invoice_M');
		$this->load->model('Payment_M');
		
		$invoice_id=$this->input->post('invoice_id');
		
		$sql="select * from invoice_master where id='".$invoice_id."'";
		$query = $this->db->query($sql);
		$inv_master= $query->result();

		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as tPayment 
		from payment_history where payof_type=2 and payof_id='".$invoice_id."'";
		$query = $this->db->query($sql);
		$payment_history= $query->result();

		$TotPaymentR=0;
		
		if(count($payment_history)>0)
		{
			$TotPaymentR=$payment_history[0]->tPayment;
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$pay_remark='';
		
		if($inv_master[0]->discount_amount>0)
		{
			$pay_remark='Dis.Amt.:'.$inv_master[0]->discount_desc.' /Amount: '. $inv_master[0]->discount_amount.'/Update:'.$inv_master[0]->disc_update_by;
		}		

		$amountpaid=$this->input->post('input_amount_paid');
		
		if($amountpaid<0){
			$amountpaid=$amountpaid*-1;
		}
		
		$amountbalanace=0.00;
		
		if($amountpaid < $inv_master[0]->net_amount );
		{
			$amountbalanace=$inv_master[0]->net_amount-$TotPaymentR;
		}
		
		if($this->input->post('mode')==1)
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'payof_type'=>'2',
					'payof_id'=>$invoice_id,
					'payof_code'=>$inv_master[0]->invoice_code,
					'credit_debit'=>'0',
					'amount'=>$amountpaid,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);
			
			if($amountpaid<=$amountbalanace)
			{
				$insert_id=$this->Payment_M->insert($paydata);
				
				$data = array( 
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Cash',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
				);

				$this->Invoice_M->update( $data,$invoice_id);
			}

				$status='CASH';
				$showcontent=$status;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
		}
		
		if($this->input->post('mode')==2)
		{
			$FormRules = array(
                array(
                    'field' => 'input_card_mac',
                    'label' => 'Card Bank Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
                )
            );

			$this->form_validation->set_rules($FormRules);

			if ($this->form_validation->run() == TRUE)
            {
				$paydata = array( 
					'payment_mode'=> '2',
					'payof_type'=>'2',
					'payof_id'=>$invoice_id,
					'payof_code'=>$inv_master[0]->invoice_code,
					'credit_debit'=>'0',
					'amount'=>$amountpaid,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					'update_by_id'=>$user_id
				);

				if($amountpaid<=$amountbalanace)
				{
					$insert_id=$this->Payment_M->insert($paydata);
					
					$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'invoice_status'=>'1',
						'payment_mode_desc'=>'Bank Card',
						'confirm_invoice'=>date('Y-m-d H:i:s'),
						'payment_id'=>$insert_id,
						'card_bank'=>$this->input->post('input_card_mac'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_card_bank'),
						'card_tran_id'=>$this->input->post('input_card_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']',
						'prepared_by_id'=>$user_id
					);
				
					$this->Invoice_M->update( $data,$invoice_id);
				}

				
				
				$status='Bank Card';
				$showcontent=$status;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				$encode_data = json_encode($rvar);
              
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                
				$encode_data = json_encode($rvar);
			}
		}
		
		if($this->input->post('mode')==3)
		{
			$data = array( 
					'payment_mode'=> '3',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'payment_mode_desc'=>'IPD Credit',
					'ipd_id'=>$this->input->post('ipd_id'),
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);
			
			$this->Invoice_M->update( $data,$invoice_id);
		
			$sql="select * from  ipd_master  where id=".$this->input->post('ipd_id');
			$query = $this->db->query($sql);
			$ipd_master= $query->result();
			
			if(count($ipd_master)>0)
			{
				$data = array( 
					'insurance_case_id'=>$ipd_master[0]->case_id
				);
				
				$this->Invoice_M->update( $data,$invoice_id);
				
			}
				$status='IPD Credit';
				
				$rvar=array(
                'update' =>1
                );
				
				$encode_data = json_encode($rvar);
		}
		
		if($this->input->post('mode')==4)
		{
			$data = array( 
					'payment_mode'=> '4',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_mode_desc'=>'Org.Case Credit',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'insurance_case_id'=>$this->input->post('case_id'),
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);
			
			$this->Invoice_M->update($data,$invoice_id);
				
				$status='Org. Credit';
				
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$status
                );
				
				$encode_data = json_encode($rvar);
               
		}
		
		if($this->input->post('mode')==5)
		{
			$data = array( 
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_id'=>'0',
					'payment_mode_desc'=>'Zero Amount',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'prepared_by'=>$user_name.'['.$user_id.']',
					'prepared_by_id'=>$user_id
			);

			$this->Invoice_M->update( $data,$invoice_id);
				
				$status='Zero Amount';
				$showcontent=$status;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );

				$encode_data = json_encode($rvar);

		}
		
		$this->load->model('Invoice_M');
		$this->Invoice_M->update_invoice_final($invoice_id);

		
	 echo $encode_data;
	}

	public function IPD_Invoice_Edit($Invoice_ID)
	{
		$sql="select * from invoice_master where id=".$Invoice_ID;
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		
		$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$Invoice_ID;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$Invoice_ID;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$pno=$data['invoiceMaster'][0]->attach_id;
		$ipd_no=$data['invoiceMaster'][0]->ipd_id;
		$ins_id=$data['invoiceMaster'][0]->insurance_card_id;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		if($ins_id>0)
		{
			$sql="select * from hc_item_type order by group_desc";
		}else{
			$sql="select * from hc_item_type where itype_id<>33 order by group_desc ";
		}
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
		
		$path_cash=1;
		$path_credit=1;
		
		$ins_comp_id=0;
		if($ins_id>0)
		{
			$sql="select * from hc_insurance_card where id=".$ins_id;
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();
			
			$ins_comp_id=$data['hc_insurance_card'][0]->insurance_id;
			
			$sql="select * from hc_insurance where id=".$ins_comp_id;
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
			
			$path_cash=$data['insurance'][0]->path_cash;
			$path_credit=$data['insurance'][0]->path_credit;
		}
		
		$data['pdata']=$ins_comp_id;
		
		$sql="select * from doctor_master where active=1  order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		if($ins_comp_id<2)
		{
			$sql="select id,Concat(idesc,' : {',amount,'}') as sdesc from hc_items where itype=1  order by idesc ";
		}else{
			$sql="select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
				i.hc_insurance_id ,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1, 
				Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
				from (hc_items m join hc_item_type t on m.itype=t.itype_id)
				left join 
				(select * from hc_items_insurance where hc_insurance_id=".$ins_comp_id." and isdelete=0  ) as i on m.id=i.hc_items_id
				where itype_id=1 order by m.idesc";
		}
		
		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		$this->load->model('invoice_M');

		$today = date("Y-m-d");
		$expire = $data['invoiceMaster'][0]->inv_date; //from db

		$today_dt = new DateTime($today);
		$inv_dt = new DateTime($expire);
		
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days 
		from  ipd_master  where  id=".$data['invoiceMaster'][0]->ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		if(count($data['ipd_master'])>0)
		{
			if($data['ipd_master'][0]->ipd_status==0)
			{
				$ipd_status=0;
			}else{
				$ipd_status=1;
			}
		}else{
			$ipd_status=1;
		}

		if($this->ion_auth->in_group('ChargeInvoiceUpdate') || ($ipd_status==0 && $this->ion_auth->in_group('IPDPayment') ))
		{
			$this->load->view('Invoice_PathLab',$data);
		}else{
			
		}
		
		//$this->load->view('Invoice_ipd_credit',$data);
    }
	
	
	public function addPathTest($pno,$ins_id=0,$opd_id=0)
	{
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		if($opd_id>0)
		{
			$sql="select * from opd_master where p_id='".$pno."' and opd_id=".$opd_id." order by  opd_id desc limit 1";
		}else{
			$sql="select * from opd_master where p_id='".$pno."' order by  opd_id desc limit 1";
		}
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		if(count($data['opd_master'])>0)
		{
			$select_doc=$data['opd_master'][0]->doc_id;
			$opd_code=$data['opd_master'][0]->opd_code;
		}else{
			$select_doc=0;
			$opd_code='';
		}
		
		$sql="select l.doc_id from ipd_master p join ipd_master_doc_list l on p.id=l.ipd_id 
		where p.ipd_status=0 and  p.p_id=".$pno." ";
        $query = $this->db->query($sql);
        $data['ipd_master_docid']= $query->result();
		
		if(count($data['ipd_master_docid'])>0)
		{
			$select_doc=$data['ipd_master_docid'][0]->doc_id;
		}
		
		if($ins_id>0)
		{
			$sql="select * from hc_item_type order by group_desc";
		}else{
			$sql="select * from hc_item_type where itype_id<>33 order by group_desc";
		}
		
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
		
		$path_cash=1;
		$path_credit=1;

		
		$ins_comp_id=0;
		if($ins_id>0)
		{
			$sql="select * from hc_insurance_card where id=".$ins_id;
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();
			
			$ins_comp_id=$data['hc_insurance_card'][0]->insurance_id;
			
			$sql="select * from hc_insurance where id=".$ins_comp_id;
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
			
			$path_cash=$data['insurance'][0]->path_cash;
			$path_credit=$data['insurance'][0]->path_credit;
		}
		
		$data['pdata']=$ins_comp_id;
		
		$sql="select * from doctor_master where active=1  order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

       


		if($ins_comp_id<2)
		{
			$sql="select id,Concat(idesc,' : {',amount,'}') as sdesc from hc_items where itype=1  order by idesc ";
		}else{
			$sql="select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
				i.hc_insurance_id ,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1, 
				Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
				from (hc_items m join hc_item_type t on m.itype=t.itype_id)
				left join 
				(select * from hc_items_insurance where hc_insurance_id=".$ins_comp_id." and isdelete=0  ) as i on m.id=i.hc_items_id
				where itype_id=1 order by m.idesc";
		}

		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		$this->load->model('invoice_M');

		if($ins_id>0)
		{
			$sql="select * from invoice_master where invoice_status=0 and insurance_card_id>0 and attach_id=".$pno;
		}else{
			$sql="select * from invoice_master where invoice_status=0 and insurance_card_id=0 and attach_id=".$pno;
		}
		
		$query = $this->db->query($sql);
        $data['chk_draft_invoice']= $query->result();

		if(count($data['chk_draft_invoice'])<1)
		{
			$data['insert_invoice'] = array( 
            'attach_type' => '0',
			'opd_code' => $opd_code,
			'attach_id' => $pno,
			'refer_by_id' => $select_doc,	
        	'inv_date' => date('Y-m-d H:i:s'),
			'inv_name' => $data['person_info'][0]->p_fname,
			'insurance_id' => $ins_comp_id,
			'insurance_card_id' => $ins_id,
			'insurance_cash' => $path_cash,
			'insurance_credit' => $path_credit,
			'inv_a_code' => $data['person_info'][0]->p_code
			);
			$inser_id=$this->invoice_M->create_invoice( $data['insert_invoice']);
		}else{
			$today = date("Y-m-d");
			$expire = $data['chk_draft_invoice'][0]->inv_date; //from db

			$today_dt = new DateTime($today);
			$inv_dt = new DateTime($expire);

			if ($inv_dt < $today_dt) {
				
				//$this->db->delete("invoice_master", "id=".$data['chk_draft_invoice'][0]->id);
				//$this->db->delete("invoice_item", "inv_master_id=".$data['chk_draft_invoice'][0]->id);
				
				$data['insert_invoice'] = array( 
				'attach_type' => '0',
				'attach_id' => $pno,
				'opd_code' => $opd_code,
				'refer_by_id' => $select_doc,	
				'inv_date' => date('Y-m-d H:i:s'),
				'inv_name' => $data['person_info'][0]->p_fname,
				'insurance_id' => $ins_comp_id,
				'insurance_card_id' => $ins_id,
				'insurance_cash' => $path_cash,
				'insurance_credit' => $path_credit,
				'inv_a_code' => $data['person_info'][0]->p_code
				);
				//$inser_id=$data['chk_draft_invoice'][0]->id;
				$inser_id=$this->invoice_M->create_invoice( $data['insert_invoice']);
			}else{
				$inser_id=$data['chk_draft_invoice'][0]->id;
			}
		}

		if($inser_id)
		{
			$sql="select * from invoice_master where id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();
			
			$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceDetails']= $query->result();
			
			$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();
		}
		
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days from  ipd_master  where ipd_status=0 and p_id=".$data['invoiceMaster'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$this->load->view('Invoice_PathLab',$data);
		//$this->load->view('Invoice_ipd_credit',$data);
    }
	
	public function list_pathtest_bytype()
	{
		$tid=$this->input->post('itype_idv');
		$ins_id=$this->input->post('ins_id');
		
		$sql="select id,Concat(idesc,' : {',amount,'}') as sdesc from hc_items where itype=".$tid;
        
		if($ins_id>1)
		{
			$sql="select m.id,m.itype,m.idesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,
				i.hc_insurance_id ,if(i.hc_insurance_id is null,m.amount,i.amount1) as amount1, 
								Concat(m.idesc,'{',if(i.hc_insurance_id is null,m.amount,i.amount1),'}',if(i.id is null,'',concat('[',i.code,']'))) as sdesc
				from (hc_items m join hc_item_type t on m.itype=t.itype_id)
				left join 
				(select * from hc_items_insurance where hc_insurance_id=".$ins_id." and isdelete=0  ) as i on m.id=i.hc_items_id
				where itype_id=".$tid."  order by m.idesc ";
		}else{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=".$tid."   order by idesc ";
		}

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
	
	public function update_refer_doc()
	{
		$invoice_id=$this->input->post('inv_id');
		$doc_id=$this->input->post('doc_id');
		$inv_date=$this->input->post('inv_date');
		
		if($doc_id<1)
		{
			$refername=$this->input->post('refername');
		}else{
			$sql="select * from doctor_master where id=".$doc_id;
			$query = $this->db->query($sql);
			$data['doc_master']= $query->result();
			$refername=$data['doc_master'][0]->p_fname;
		}
		
		if($this->ion_auth->in_group('InvoiceDateChanged'))
		{
			$inv_date=str_to_MysqlDate($inv_date);
		}else{
			$inv_date=$data['invoice_master'][0]->inv_date;
		}
		
		$dataupdate = array( 
				'refer_by_id' => $doc_id,
				'inv_date' => $inv_date,				
				'refer_by_other' => $refername,
				'invoice_status' =>'1',
		);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);
		
	}
	
	public function get_echs_id()
	{
		$itype_name_id_srno=$this->input->post('input_charge_code');
		$tid=$this->input->post('itype_idv');
		
		$sql="select * from hc_items where itype=".$tid." and echs_sr_no='".$itype_name_id_srno."'";
		//echo $sql;
		$query = $this->db->query($sql);
		$data['echs_data']= $query->result();
		
		if($query->num_rows()>0)
		{
			echo $data['echs_data'][0]->id;
		}else{
			echo '0';
		}			
	}
	
	
	
	public function showinvoice($invoice_id)
	{
		$this->load->model('Invoice_M');
		$this->Invoice_M->update_invoice_final($invoice_id);

		$sql="select i.*,t.group_desc 
			from invoice_item i join hc_item_type t on i.item_type=t.itype_id 
			where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
				
		$sql="select *,
		(case payment_mode 
			when  1 Then 'Cash' 
			when  2 then 'Bank Card' 
			when 3 then 'IPD Credit' 
			when 4 then 'Org. Credit' 
			else 'Pending' end) as Payment_type_str 
		from invoice_master 
		where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$ins_comp_id=$data['invoice_master'][0]->insurance_id;
		$ins_id=$data['invoice_master'][0]->insurance_card_id;

		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		$sql="select * from patient_master_exten 
			where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select * from  ipd_master  
			where ipd_status=0 and p_id=".$data['invoice_master'][0]->attach_id;
		
		if($data['invoice_master'][0]->ipd_id >0)
		{
			$sql="select * from  ipd_master  where id=".$data['invoice_master'][0]->ipd_id;
		}
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from invoice_item i join hc_item_type t on i.item_type=t.itype_id 
			where t.is_ipd_opd=2 and i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$IPD_Credit= $query->result();
				
		if(count($IPD_Credit)>0)
		{
			$data['IPD_Credit']="1";
		}else{
			$data['IPD_Credit']="0";
		}
		
		$sql="select * 
			from  organization_case_master  
			where case_type=0 and status=0 and p_id=".$data['invoice_master'][0]->attach_id;
				
		if($data['invoice_master'][0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master 
			 where  id=".$data['invoice_master'][0]->insurance_case_id;
		}elseif(count($data['ipd_master'])>0){
			if($data['ipd_master'][0]->case_id>0)
			{
				$sql="select * from  organization_case_master  where  id=".$data['ipd_master'][0]->case_id;
			}
		}
		
		$query = $this->db->query($sql);
		$data['case_master']= $query->result();
	
		$this->load->view('Invoice_PathLab_final_V',$data);
	}
	
	public function update_discount()
	{
		$invoice_id=$this->input->post('invoice_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$dataupdate = array( 
				'discount_amount' => $this->input->post('input_dis_amt'),
				'discount_desc' => $this->input->post('input_dis_desc'),
				'disc_update_by'=>$user_name.'['.$user_id.']'				
				);
		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);

		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
		
		$sql="select * from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$discount_amount=$data['invoice_master'][0]->discount_amount ;
		$correction_amount=$data['invoice_master'][0]->correction_amount ;

		$total_amount=$data['invoiceGtotal'][0]->Gtotal;
		$net_amount=$total_amount-$discount_amount;
		$net_amount=$net_amount-$correction_amount;
		$paid_amount=$data['payment_history'][0]->paid_amount;
		$balance_amount=$net_amount-$paid_amount;
		
		if($balance_amount>0 && $paid_amount>0)
		{
			$payment_part=1;
		}else{
			$payment_part=0;
		}
				
		$dataupdate = array( 
				'total_amount' => $total_amount,				
				'net_amount' => $net_amount,
				'payment_part_received' => $paid_amount,
				'payment_part_balance' => $balance_amount,
				'payment_part' => $payment_part,
		);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);

	}
	
	
	public function showitem($type)
	{
		$this->load->model('invoice_M');
		
		if($type==1)
		{
			$ins_id=$this->input->post('ins_id');
			
			$sql="select * from hc_items where id=".$this->input->post('itype_name_id');
			if($ins_id>1)
			{
				$sql="select m.id,m.itype,m.idesc as sdesc,m.amount,t.itype_id,t.is_ipd_opd,t.group_desc,i.isdelete,
					if(i.hc_insurance_id is null,0,i.hc_insurance_id) as hc_insurance_id ,if(i.hc_insurance_id is null,
					m.amount,i.amount1) as amount1,
					if(i.hc_insurance_id is null,'',i.code) as org_code
					from (hc_items m left join hc_items_insurance i on m.id=i.hc_items_id and i.isdelete=0 and i.hc_insurance_id=".$ins_id." ) 
					join hc_item_type t on m.itype=t.itype_id where m.id=".$this->input->post('itype_name_id');
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
			
				$item_rate=$data['itemlist'][0]->amount1;
				$org_code=$data['itemlist'][0]->org_code;
				
				$item_custom_rate=$this->input->post('input_rate');
				
				if($item_custom_rate>0)
				{
					$item_rate=$item_custom_rate;
				}
				
				$amount_value=$this->input->post('input_qty')*$item_rate;
				
			}
			else
			{
				$sql="select id,idesc as sdesc,amount from hc_items where id=".$this->input->post('itype_name_id');
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
				
				$item_rate=$data['itemlist'][0]->amount;
				
				$org_code='';
				
				$item_custom_rate=$this->input->post('input_rate');
				
				if($item_custom_rate>0)
				{
					$item_rate=$item_custom_rate;
				}
				$amount_value=$this->input->post('input_qty')*$item_rate;
			}

			$data['insert_invoice_item'] = array( 
				'inv_master_id' => $this->input->post('lab_invoice_id'),
				'item_type' => $this->input->post('itype_idv'),
				'item_id' => $this->input->post('itype_name_id'),				
				'item_name' => $data['itemlist'][0]->sdesc,
				'item_rate' => $item_rate,
				'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
				'item_qty' => $this->input->post('input_qty'),
				'item_amount' => $amount_value,
				'ins_id' => $ins_id,
				'org_code' => $org_code
				);
				
			$sql="select * from invoice_item where inv_master_id=".$this->input->post('lab_invoice_id')." and item_id=".$this->input->post('itype_name_id');
			$query = $this->db->query($sql);
			
			$itemcheck= $query->result();
			
			if(count($itemcheck)<1)
			{
				$inser_id=$this->invoice_M->addinvoiceitem( $data['insert_invoice_item']);
			}
			
		}if($type==2)
		{
			$amount_value=$this->input->post('update_qty') * $this->input->post('item_rate');

			$data['update_invoice_item'] = array( 
				'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
				'item_qty' => $this->input->post('update_qty'),
				'item_amount' => $amount_value
			);
			
			$inser_id=$this->invoice_M->update_item( $data['update_invoice_item'],$this->input->post('itemid'));
			
		}else{
			$this->invoice_M->deleteinvoiceitem($this->input->post('itemid'));
			echo 'DataDelete '.$this->input->post('itemid');
			//$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
		
		$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$this->input->post('lab_invoice_id')." Order by i.id";
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$invoice_id=$this->input->post('lab_invoice_id');
		
		$sql="select * from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$total_amount=$data['invoiceGtotal'][0]->Gtotal;
		$discount_amount=$data['invoice_master'][0]->discount_amount ;
		$total_amount=$data['invoiceGtotal'][0]->Gtotal;
		$net_amount=$total_amount-$discount_amount;
		
		$dataupdate = array( 
				'total_amount' => $total_amount,				
				'net_amount' => $net_amount
				);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);
		

		echo '<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Charge Type</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Updated Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>';

		$srno=0;

		foreach($data['invoiceDetails'] as $row)
		{ 
			$srno=$srno+1;
			echo '<tr>';
			echo '<td>'.$srno.'</td>';
			echo '<td>'.$row->group_desc.'</td>';
			echo '<td>'.$row->item_name.'</td>';
			if(in_array($row->item_type, array(1,2,3,4,5)))
			{
				echo '<td>'.$row->item_rate.'</td>';
				echo '<td>'.$row->item_qty.'</td>';
				echo '<td>'.$row->item_amount.'</td>';
				echo '<td>';
			}else{
				echo '<td><input type=hidden name="hidden_rate_'.$row->id.'" id="hidden_rate_'.$row->id.'"  value="'.$row->item_rate.'" >'.$row->item_rate.'</td>';
				echo '<td><input class="form-control" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->item_qty.'" type="number" /></td>';
				echo '<td>'.$row->item_amount.'</td>';
				echo '<td>';
				echo '<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')">Update</button>';
			}

			$sql="select * from lab_request where charge_item_id=".$row->id;
			$query = $this->db->query($sql);
			$lab_request= $query->result();

			if(count($lab_request)<1)
			{
				echo '<td><button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')">-Remove</button>';
			}

			echo '</tr>';
		}	
		
		echo '<input type="hidden" id="srno" name="srno" value="'.count($data['invoiceDetails']).'" />';
		
		echo '<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th>'.$data['invoiceGtotal'][0]->Gtotal.'</th>
				<th></th>
			</tr>
		</table>';
	}
	
	public function invoice_print($invoice_id)
	{
		$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id." order by i.id";
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select *,(case invoice_status when 0 then 'Pending' when 1 then '' when 2 then 'Cancelled Invoice' else 'Other' end) as Invoice_status_str,
		(case payment_mode when  1 Then 'Cash' when  2 then 'Bank Card' 
		when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str  
		from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['invoice_master'][0]->inv_date."')   AS age from patient_master 
		where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select *,get_payment_type(payment_mode,credit_debit) as Payment_type_str from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
		
		$ins_comp_id=$data['invoice_master'][0]->insurance_id;
		$ins_id=$data['invoice_master'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		
		
		$sql="select * from  organization_case_master  where  id=".$data['invoice_master'][0]->insurance_case_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$this->load->view('invoice_pathlab_print_V',$data);
		
	}
	
	public function charge_crorg($inv_id,$org_code_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$inv_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
        $invoice_master= $query->result();
		
		$sql="select * from refund_order where refund_process=0 and refund_type=2 and refund_type_id=".$inv_id;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $invoice_master[0]->invoice_status==1)
			{
				$RefundRequest = array( 
				'refund_type' => 2,
				'refund_type_id' => $inv_id,
				'refund_type_code' => $invoice_master[0]->invoice_code,
				'refund_type_reason' => 'Cr. to Org.',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount,
				'patient_id' => $invoice_master[0]->attach_id,
				'patient_name' =>strtoupper($invoice_master[0]->inv_name)
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}

		$dataupdate = array( 
				'payment_mode' => 4,
				'payment_mode_desc' => 'Org. Credit',
				'payment_id' => 0,
				'insurance_case_id' => $org_code_id,
				'payment_status'=>1
				);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$inv_id);
	}

	public function invoice_PDF_print($invoice_id,$page_size=0)
	{
		$this->load->model('Invoice_M');
		$this->Invoice_M->update_invoice_final($invoice_id);


		$sql="select i.*,t.group_desc 
		from invoice_item i join hc_item_type t on i.item_type=t.itype_id 
		where i.inv_master_id=".$invoice_id." order by t.group_desc,i.id";
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		

		$sql="select *,(case invoice_status when 0 then 'Pending' when 1 then '' when 2 then 'Cancelled Invoice' else 'Other' end) as Invoice_status_str,
		(case payment_mode when  1 Then 'Cash' when  2 then 'Bank Card' 
		when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str ,
		date_format(confirm_invoice,'%d-%m-%Y %H:%i:%s') as confirm_invoice_datetime
		from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$data['invoice_master'][0]->inv_date."')  AS age from patient_master 
		where id='".$data['invoice_master'][0]->attach_id."' ";
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

        $sql="select *,get_payment_type(payment_mode,credit_debit) as Payment_type_str 
        from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
		
		$ins_comp_id=$data['invoice_master'][0]->insurance_id;
		$ins_id=$data['invoice_master'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$hc_insurance_card= $query->result();
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();
		
		$sql="select *,i.short_name 
		from  organization_case_master  o join hc_insurance i on o.insurance_id=i.id
		where  o.id=".$data['invoice_master'][0]->insurance_case_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();

		//load mPDF library
				
		$this->load->library('m_pdf');
		
		$this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = true;

        $file_name='Charge_Invoice-'.date('Ymdhis').".pdf";

        $filepath=$file_name;

		if($page_size==0)
		{
			$content=$this->load->view('Invoice/invoice_print',$data,TRUE);
		}elseif($page_size==1){
			$content=$this->load->view('Invoice/invoice_print_a5',$data,TRUE);
		}elseif($page_size==2){
			$content=$this->load->view('Invoice/invoice_print_a4_double',$data,TRUE);
		}
		
       	//echo $content;
		
		//generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output("TEST.pdf","I");
	
	}
	

 
	public function charge_crIPD($inv_id,$ipd_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount 
		from payment_history where payof_type=2 and payof_id=".$inv_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
        $invoice_master= $query->result();
		
		$sql="select * from ipd_master where id=".$ipd_id;
		$query = $this->db->query($sql);
        $ipd_master= $query->result();
		
		$sql="select * from refund_order where refund_process=0 and refund_type=2 and refund_type_id=".$inv_id;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $invoice_master[0]->invoice_status==1)
			{
				$RefundRequest = array( 
				'refund_type' => 2,
				'refund_type_id' => $inv_id,
				'refund_type_code' => $invoice_master[0]->invoice_code,
				'refund_type_reason' => 'Cr. to IPD.',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount,
				'patient_id' => $invoice_master[0]->attach_id,
				'patient_name' =>strtoupper($invoice_master[0]->inv_name)
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}

		$dataupdate = array( 
				'payment_mode' => 3,
				'payment_mode_desc' => 'IPD Credit',
				'payment_id' => 0,
				'insurance_case_id' => $ipd_master[0]->case_id,
				'ipd_id' => $ipd_id,
				'payment_status' => 1,
				);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$inv_id);
	}
	
	
	public function cancel_inv($inv_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$inv_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
        $invoice_master= $query->result();

		$sql="select * from refund_order 
		where refund_process=0 and refund_type=2 and refund_type_id=".$inv_id;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $invoice_master[0]->invoice_status==1)
			{
				$RefundRequest = array( 
				'refund_type' => 2,
				'refund_type_id' => $inv_id,
				'refund_type_code' => $invoice_master[0]->invoice_code,
				'refund_type_reason' => 'Cancel Invoice',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $payment_history[0]->paid_amount,
				'patient_id' => $invoice_master[0]->attach_id,
				'patient_name' =>strtoupper($invoice_master[0]->inv_name)
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}

			$dataupdate = array( 
				'payment_id' => 0,
				'invoice_status' => 2
			);

			$this->load->model('Invoice_M');
			$this->Invoice_M->update($dataupdate,$inv_id);
		}else{

		}

		
	}
	
	public function charge_refund($inv_id,$inv_refund_amount)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user->id.']:T-'.date('d-m-Y h:m:s');
		
		$status_str="";

		$opd_status_remark=$status_str.' Update By:'.$user_name;
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$inv_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
        $invoice_master= $query->result();

		$sql="select * from refund_order where refund_process=0 and refund_type=2 and refund_type_id=".$inv_id;
		$query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($payment_history)>0 && count($refund_order)<1)
		{
			if($payment_history[0]->paid_amount>0 && $invoice_master[0]->invoice_status==1 && $inv_refund_amount<=$payment_history[0]->paid_amount)
			{
				if($inv_refund_amount<0)
				{
					$inv_refund_amount=$inv_refund_amount*-1;
				}
				
				$RefundRequest = array( 
				'refund_type' => 2,
				'refund_type_id' => $inv_id,
				'refund_type_code' => $invoice_master[0]->invoice_code,
				'refund_type_reason' => 'Return Extra Charge',
				'approved_by_id' => $user_id,
				'approved_by' => $user_name,
				'refund_amount' => $inv_refund_amount,
				'patient_id' => $invoice_master[0]->attach_id,
				'patient_name' =>strtoupper($invoice_master[0]->inv_name)
				);
				
				$this->load->model('Payment_M');
				$this->Payment_M->insert_refundorder($RefundRequest);
			}
		}
	
	}
	
	public function deleteinvoice()
	{
		$inv_id=$this->input->post('inv_id');
		$this->db->delete("invoice_master", "id=".$inv_id);
		$this->db->delete("invoice_item", "inv_master_id=".$inv_id);
	}
 
}