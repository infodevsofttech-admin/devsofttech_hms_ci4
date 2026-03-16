<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class OcasePathLap extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
		
	public function confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('invoice_M');
		$this->load->model('Payment_M');
		
		$sql="select * from invoice_master where id='".$this->input->post('lab_invoice_id')."'";
		$query = $this->db->query($sql);
		$inv_master= $query->result();
		
		$invoice_id=$this->input->post('lab_invoice_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$showcontent1='<div class="row no-print">
						<div class="col-xs-6">
							<a href="/index.php/OcasePathLap/invoice_print/'.$this->input->post('lab_invoice_id').'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
						</div>
						<div class="col-xs-6">
							Payment Method by : ';          

		$showcontent2='</div></div>';

		$pay_remark='';
		
		if($inv_master[0]->discount_amount>0)
		{
			$pay_remark='Dis.Amt.:'.$inv_master[0]->discount_desc.' /Amount: '. $inv_master[0]->discount_amount.'/Update:'.$inv_master[0]->disc_update_by;
		}

		$amountpaid=$this->input->post('input_amount_paid');
		$amountbalanace=0.00;
		
		if($amountpaid < $inv_master[0]->net_amount )-$amountpaid;
		{
			$amountbalanace=$inv_master[0]->net_amount;
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
					'payment_date'=>date('d/m/Y H:m:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']'
			);
			
			$insert_id=$this->Payment_M->insert($paydata);

			$data = array( 
                    
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_mode_desc'=>'Cash',
					'payment_id'=>$insert_id,
					'confirm_invoice'=>date('d/m/Y H:m:s'),
					'prepared_by'=>$user_name.'['.$user_id.']'
			);

			$this->invoice_M->update( $data,$invoice_id);
				
				$status='CASH';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
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
					'payof_type'=>'2',
					'payof_id'=>$invoice_id,
					'payof_code'=>$inv_master[0]->invoice_code,
					'credit_debit'=>'0',
					'amount'=>$amountpaid,
					'payment_date'=>date('d/m/Y H:m:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran')
				);
				
				$insert_id=$this->Payment_M->insert($paydata);
				
				$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'invoice_status'=>'1',
						'payment_mode_desc'=>'Bank Card',
						'payment_id'=>$insert_id,
						'confirm_invoice'=>date('d/m/Y H:m:s'),
						'card_bank'=>$this->input->post('input_card_mac'),
						'cust_card'=>$this->input->post('input_card_bank'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'card_tran_id'=>$this->input->post('input_card_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']'
				);
				$this->invoice_M->update( $data,$invoice_id);
				
				$status='Bank Card';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
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
		
		if($this->input->post('mode')==3)
		{
			$data = array( 
                    
					'payment_mode'=> '3',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_mode_desc'=>'IPD Credit',
					'confirm_invoice'=>date('d/m/Y H:m:s'),
					'ipd_id'=>$this->input->post('ipd_id'),
					'prepared_by'=>$user_name.'['.$user_id.']'
					
			);
			
			$this->invoice_M->update( $data,$invoice_id);
				
				$status='IPD Credit';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==4)
		{
			$data = array( 
                    
					'payment_mode'=> '3',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_mode_desc'=>'Org.Case Credit',
					'confirm_invoice'=>date('d/m/Y H:m:s'),
					'insurance_case_id'=>$this->input->post('case_id'),
					'prepared_by'=>$user_name.'['.$user_id.']'
					
			);
			
			$this->invoice_M->update( $data,$invoice_id);
				
				$status='Org. Credit';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==0)
		{
			
		}
	}
	
	public function edit_org_invoice($invoice_id)
	{
		$sql="select * from hc_item_type";
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
		
		$sql="select * from doctor_master where active=1";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
				
		$sql="select * from invoice_master where id=".$inser_id;
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$inser_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$inser_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$ins_comp_id=$data['invoiceMaster'][0]->insurance_id;
		
		if($ins_comp_id<2)
		{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=1";
		}else{
			$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc from v_hc_items_with_insurance
					where itype=1 and hc_insurance_id in (0,".$ins_comp_id.")";
		}
		
		$query = $this->db->query($sql);
        $data['labitem']= $query->result();
	
		$this->load->view('Invoice/Organization_Invoice_PathLab',$data);
	}
	
	public function addPathTest($pno,$ins_id=0)
	{
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select * from hc_item_type";
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();

		$ins_comp_id=0;
		
		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
		
		$ins_comp_id=$data['hc_insurance_card'][0]->insurance_id;
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
				
		$sql="select * from doctor_master where active=1";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		if($ins_comp_id<2)
		{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=1   order by idesc  ";
		}else{
			$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc from v_hc_items_with_insurance
					where itype=1 and hc_insurance_id in (0,".$ins_comp_id.")   order by idesc ";
		}
		
		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		$this->load->model('invoice_M');

		$sql="select count(*) as no_invoice from invoice_master where invoice_status=0 and insurance_card_id>0 and attach_id=".$pno;
        $query = $this->db->query($sql);
        $data['chk_draft_invoice']= $query->result();
		
		if($data['chk_draft_invoice'][0]->no_invoice<1)
		{
			$data['insert_invoice'] = array( 
            'attach_type' => '0',
			'attach_id' => $pno,			
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $data['person_info'][0]->p_fname,
			'insurance_id' => $ins_comp_id,
			'insurance_card_id' => $ins_id,
			'insurance_cash' => $data['insurance'][0]->path_cash,
			'insurance_credit' => $data['insurance'][0]->path_credit,
			'inv_a_code' => $data['person_info'][0]->p_code
			);
			$inser_id=$this->invoice_M->create_invoice( $data['insert_invoice']);
		}else{
			$sql="select id from invoice_master where invoice_status=0 and attach_id=".$pno;
			$query = $this->db->query($sql);
			$data['invoice_id']= $query->result();
		
			$inser_id=$data['invoice_id'][0]->id;
		}
		
		if($inser_id)
		{
			$sql="select * from invoice_master where id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();
			
			$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceDetails']= $query->result();
			
			$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();
		}

		$this->load->view('Invoice/Organization_Invoice_PathLab',$data);
    }
	
	public function list_pathtest_bytype()
	{
		$tid=$this->input->post('itype_idv');
		$ins_id=$this->input->post('ins_id');
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=".$tid;
        
		if($ins_id>1)
		{
			$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc from v_hc_items_with_insurance
					where itype=".$tid." and hc_insurance_id in (0,".$ins_id.") order by idesc";
			
		}else{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=".$tid."  order by idesc";
		}

		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		echo '<div class="form-group">
				<label>Charge Code</label>';
	
		echo '<select class="form-control" id="itype_name_id" name="itype_name_id"  >	';				
		foreach($data['labitem'] as $row)
		{ 
			echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
		}
		echo '</select></div>';
		
		
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
	
	public function update_refer_doc()
	{
		$invoice_id=$this->input->post('inv_id');
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
		
		$dataupdate = array( 
				'refer_by_id' => $doc_id,				
				'refer_by_other' => $refername
		);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);
	}
	
	
	public function showinvoice($invoice_id)
	{
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select *  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$ins_comp_id=$data['invoice_master'][0]->insurance_id;
		$ins_id=$data['invoice_master'][0]->insurance_card_id;

		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
				
		$discount_amount=$data['invoice_master'][0]->discount_amount ;
		$total_amount=$data['invoiceGtotal'][0]->Gtotal;
		$net_amount=$total_amount-$discount_amount;
		$paid_amount=$data['payment_history'][0]->paid_amount;
		$balance_amount=$net_amount-$paid_amount;
		
		if($balance_amount>0)
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
				'payment_part' => $payment_part
				);
				
		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);
		
		$sql="select *  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
			
		$this->load->view('Invoice/Organization_Invoice_PathLab_final_V',$data);
	}
	
	public function showitem($type)
	{
		$this->load->model('invoice_M');
		
		if($type>0)
		{
			$ins_id=$this->input->post('ins_id');
			
			$sql="select * from hc_items where id=".$this->input->post('itype_name_id');
			if($ins_id>1)
			{
				$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc,amount1 from v_hc_items_with_insurance
						where id=".$this->input->post('itype_name_id')." and hc_insurance_id in (0,".$ins_id.")";
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
			
				$item_rate=$data['itemlist'][0]->amount1;
				
				$amount_value=$this->input->post('input_qty')*$item_rate;
				
			}
			else
			{
				$sql="select id,Concat(idesc,' : [',amount,']') as sdesc,amount from hc_items where id=".$this->input->post('itype_name_id');
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
				
				$item_rate=$data['itemlist'][0]->amount;
				
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
				'item_amount' => $amount_value
				);
				
			$inser_id=$this->invoice_M->addinvoiceitem( $data['insert_invoice_item']);
		
		}else{
			$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
		
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		echo '<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Item Group</th>
				<th>Charge Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>';

		$srno=0;

		foreach($data['invoiceDetails'] as $row)
		{ 
			$srno=$srno+1;
			echo '<tr>';
			echo '<td>'.$srno.'</td>';
			echo '<td>'.$row->desc.'</td>';
			echo '<td>'.$row->item_name.'</td>';
			echo '<td>'.$row->item_rate.'</td>';
			echo '<td>'.$row->item_qty.'</td>';
			echo '<td>'.$row->item_amount.'</td>';
			echo '<td><button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')">-Remove</button></td>';
			
			echo '</tr>';
		}
		
		echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			
		echo '<tr>
				<th style="width: 10px">#</th>
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
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select *  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$ins_comp_id=$data['invoice_master'][0]->insurance_id;
		$ins_id=$data['invoice_master'][0]->insurance_card_id;

		$sql="select * from hc_insurance_card where id=".$ins_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
		
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		$sql="select * from  organization_case_master  where id=".$data['invoice_master'][0]->insurance_case_id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$sql="select *,(case payment_mode when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'Org Credit' else 'Pending' end) as Payment_type_str from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
		
		$this->load->view('Invoice/Organization_invoice_print_v',$data);
	}
	
}