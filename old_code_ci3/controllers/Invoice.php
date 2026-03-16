<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
	
	public function opdlist()
	{
		$this->load->view('Invoice/Invoice_opd');
	}
	
	public function chargeslist()
	{
		$this->load->view('Invoice/Invoice_charges');
	}
	
	public function search_opd()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
        $sql = "select  o.opd_id, o.doc_name,o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
		p.p_code,p.mphone1,p.email1 ,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,
		if(o.payment_status=1,m.mode_desc,'Pending') as PaymentMode,o.payment_mode
		from (opd_master o join patient_master p on o.p_id=p.id ) join payment_mode m on o.payment_mode=m.id
		WHERE opd_code like '%".$sdata."' or p_code like '%".$sdata."' or P_name like '%".$sdata."%' or
		mphone1 = '".$sdata."' or email1 = '".$sdata."' order by o.opd_id desc  limit 100";

		$query = $this->db->query($sql);
        $data['opd_list']= $query->result();

        $this->load->view('Invoice/Invoice_opd_list',$data);
	}

	public function search_charges()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_\-]/', '', $sdata);
		
        $sql = "select i.id as inv_id, i.refer_by_other,i.invoice_code,p.p_fname,i.attach_id,
		Date_Format(i.inv_date,'%d-%m-%Y') as Inv_Date,p.p_code,p.mphone1,p.email1,
		if(i.insurance_id>1,'Org.','Direct') as Inv_Type,i.invoice_status,
		m.mode_desc as PaymentMode,i.payment_mode,i.payment_part_balance,i.net_amount
		from invoice_master i join patient_master p join payment_mode m 
		on i.attach_id=p.id and i.attach_type=0 and i.payment_mode=m.id
		WHERE invoice_code like '%".$sdata."' or p_code like '%".$sdata."' or p_fname like '%".$sdata."%' or
		mphone1 = '".$sdata."' or email1 = '".$sdata."' order by i.id desc limit 100";
				
		$query = $this->db->query($sql);
        $data['charges_list']= $query->result();

        $this->load->view('Invoice/Invoice_charges_list',$data);
	}
	
	public function opdinvoice($opdid)
	{
		$sql="select *,(case payment_status when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
	
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";
  
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$data['opd_master'][0]->p_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from  organization_case_master  where status=0 and p_id=".$data['patient_master'][0]->id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();
		
		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();


		$this->load->view('Invoice/Invoice_opd_correction',$data);
	}
	
	
	public function showinvoice($invoice_id)
	{
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select * from invoice_master where id=".$invoice_id;
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
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
		$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$this->load->view('Invoice/Invoice_Charges_correction',$data);
	}
	
	
	public function update_correction()
	{
		$oid=$this->input->post('oid');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$opd_correction=$this->input->post('input_corr_amt');
		
		$sql="select *  from  opd_master  where opd_id=".$oid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();
		
		$flag=true;
		
		if($this->input->post('optionsRadios_crdr')==1)
		{
			if($opd_correction>0 && $data['opd_master'][0]->opd_fee_gross_amount>=$opd_correction)
			{
				$opd_fee_amount=$data['opd_master'][0]->opd_fee_gross_amount-$opd_correction;
				$opd_fee_amount=$opd_fee_amount-$data['opd_master'][0]->opd_discount;
			}else{
				$flag=false;
			}
		}else{
			$opd_fee_amount=$data['opd_master'][0]->opd_fee_gross_amount+$opd_correction;
			$opd_fee_amount=$opd_fee_amount-$data['opd_master'][0]->opd_discount;
		}
		
		if($flag)
		{
			$dataupdate = array( 
				'opd_correction_amount' => $this->input->post('input_corr_amt'),
				'opd_correction_remark' => $this->input->post('input_corr_desc'),
				'opd_correction_user' => $user_name,
				'opd_correction_crdr' => $this->input->post('optionsRadios_crdr'),
				'opd_correction_datetime' => date('Y-m-d H:i:s'),
				'opd_fee_amount' => $opd_fee_amount
				);

			$this->load->model('Opd_M');
			$this->Opd_M->update($dataupdate,$oid);
			
			$this->load->model('Payment_M');
	
			if($this->input->post('optionsRadios_crdr')==1)
			{
				$credit_debit=1;
			}
			else{
				$credit_debit=0;
			}
	
			$paydata = array( 
					'payment_mode'=> '1',
					'payof_type'=>'1',
					'payof_id'=>$oid,
					'payof_code'=>$data['opd_master'][0]->opd_code,
					'credit_debit'=>$credit_debit,
					'amount'=>$this->input->post('input_corr_amt'),
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$this->input->post('input_corr_desc'),
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);
		
			$insert_id=$this->Payment_M->insert($paydata);
		}else{

		}
	}
	
	
	public function update_correction_charges()
	{
		$invoice_id=$this->input->post('invoice_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$correction=$this->input->post('input_corr_amt');
		
		$sql="select *  from  invoice_master  where id=".$invoice_id;
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
		
		$flag=true;
		
		if($correction>0 && $data['invoice_master'][0]->net_amount>=$correction)
		{
			$net_amount=$data['invoice_master'][0]->net_amount-$correction;
		}else{
			$flag=false;
		}

		if($flag)
		{
			$dataupdate = array( 
				'correction_amount' => $this->input->post('input_corr_amt'),
				'correction_remark' => $this->input->post('input_corr_desc'),
				'correction_user' => $user_name,
				'correction_crdr' => $this->input->post('optionsRadios_crdr'),
				'correction_datetime' => date('Y-m-d H:i:s'),
				'correction_net_amount' => $fee_amount,
				'invoice_status' => '3'
				);

			$this->load->model('Invoice_M');
			$this->Invoice_M->update($dataupdate,$invoice_id);

			$this->load->model('Payment_M');

			$paydata = array( 
					'payment_mode'=> '3',
					'payof_type'=>'2',
					'payof_id'=>$invoice_id,
					'payof_code'=>$data['invoice_master'][0]->invoice_code,
					'credit_debit'=>1,
					'amount'=>$this->input->post('input_corr_amt'),
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$this->input->post('input_corr_desc'),
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
				);
			$insert_id=$this->Payment_M->insert($paydata);
		}

	}
	
	
	
	public function list_refund()
	{
		$this->load->view('Invoice/refund_invoice');
	}

	

	public function getRequestTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'id',
			1 => 'org_code',
			2 => 'patient_name',
			3 => 'org_date_str',
			4=> 'payment_amount',
			5=> 'payment_process_str'
			);

		// getting total number records without any search
		$sql_f_all = "select *,
			date_format(payment_request_datetime,'%d-%m-%Y %h:%i:%s') as org_date_str,
			if(payment_process=0,'Pending','Complete') as payment_process_str "	;
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from org_payment_request ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //CaseCode-InsuranceCard-ClaimNo
			$sql_where.=" AND id = '".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}

		// getting records as per search parameters
		if( !empty($requestData['columns'][1]['search']['value']) ){   //CaseCode-InsuranceCard-ClaimNo
			$sql_where.=" AND org_code LIKE '%".$requestData['columns'][1]['search']['value']."' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][2]['search']['value']) ){  //p_code-P-Name
			$sql_where.=" AND ( patient_name LIKE '".$requestData['columns'][2]['search']['value']."%') ";
			
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		
		$sql_order="  ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
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
	
	public function getRefundTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'id',
			1 => 'refund_type_code',
			2 => 'refund_type_str',
			3 => 'patient_name',
			4=> 'approved_datetime',
			5=> 'refund_amount',
			6=> 'refund_process_str'
			);

		// getting total number records without any search
		$sql_f_all = "select *,
		(case refund_type when 1 then 'OPD' when 2 then 'Charge' when 3 then 'Org.Inv.' else 'Other' end ) as refund_type_str ,
		if(refund_process<1,'Pending','Complete') as refund_process_str";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from refund_order ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //CaseCode-InsuranceCard-ClaimNo
			$sql_where.=" AND refund_type_code LIKE '%".$requestData['columns'][0]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //p_code-P-Name
			$sql_where.=" AND ( patient_name LIKE '%".$requestData['columns'][1]['search']['value']."%') ";
			
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		
		$sql_order="  ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
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
	
	public function refund_form($rid)
	{
		$sql="select *  from  refund_order  where id=".$rid;
        $query = $this->db->query($sql);
        $data['refund_order']= $query->result();
		
		$this->load->view('Invoice/refund_amount_panel',$data);
	}

	
	
	public function refund_process()
	{
		$r_id=$this->input->post('r_id');
		$r_name=$this->input->post('r_name');
		$r_phone=$this->input->post('r_phone');
		
		$this->load->model('Invoice_M');
		$this->load->model('Payment_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name;
		
		$sql="select *  from  refund_order  where id=".$r_id;
        $query = $this->db->query($sql);
        $refund_order= $query->result();
		
		if(count($refund_order)>0)
		{
			if($refund_order[0]->refund_process>0)
			{
				$showcontent='Already Done ';
				$rvar=array(
					'update' =>0,
					'showcontent'=>$showcontent
					);
				$encode_data = json_encode($rvar);
			}else{

				if($refund_order[0]->refund_type==2)
				{
					$paydata = array( 
						'payment_mode'=> '1',
						'payof_type'=>'2',
						'payof_id'=>$refund_order[0]->refund_type_id,
						'payof_code'=>$refund_order[0]->refund_type_code,
						'credit_debit'=>'1',
						'amount'=>$refund_order[0]->refund_amount,
						'payment_date'=>date('Y-m-d H:i:s'),
						'remark'=>$refund_order[0]->refund_type_reason,
						'update_by'=>$user_name.'['.$user_id.']',
						'update_by_id'=>$user_id
					);
				
					$insert_id=$this->Payment_M->insert($paydata);
				
					$inv_id=$refund_order[0]->refund_type_id;

					$data = array( 
						'refund_process'=> '1',
						'refund_by'=>$user_name.'['.$user_id.']',
						'refund_by_id'=>$user_id,
						'refund_datetime'=>date('Y-m-d H:i:s'),
						'pay_id'=>$insert_id
					);
					
					$this->Payment_M->update_refundorder($data,$r_id);

					$showcontent='Refund Update';
					
					$rvar=array(
					'update' =>1,
					'showcontent'=>$showcontent
					);
					$encode_data = json_encode($rvar);
					
					$this->Invoice_M->update_invoice_final($refund_order[0]->refund_type_id);
				}
				
				if($refund_order[0]->refund_type==1)
				{
					$paydata = array( 
						'payment_mode'=> '1',
						'payof_type'=>'1',
						'payof_id'=>$refund_order[0]->refund_type_id,
						'payof_code'=>$refund_order[0]->refund_type_code,
						'credit_debit'=>'1',
						'amount'=>$refund_order[0]->refund_amount,
						'payment_date'=>date('Y-m-d H:i:s'),
						'remark'=>$refund_order[0]->refund_type_reason,
						'update_by'=>$user_name.'['.$user_id.']',
						'update_by_id'=>$user_id
					);
				
					$insert_id=$this->Payment_M->insert($paydata);
				
					$inv_id=$refund_order[0]->refund_type_id;
					
					$data = array( 
						'refund_process'=> '1',
						'refund_by'=>$user_name.'['.$user_id.']',
						'refund_by_id'=>$user_id,
						'refund_datetime'=>date('Y-m-d H:i:s'),
						'pay_id'=>$insert_id
					);
					
					$this->Payment_M->update_refundorder($data,$r_id);

					$showcontent='Refund Update';
					
					$rvar=array(
					'update' =>1,
					'showcontent'=>$showcontent
					);
					$encode_data = json_encode($rvar);
				}

				if($refund_order[0]->refund_type==3)
				{
					$paydata = array( 
						'payment_mode'=> '1',
						'payof_type'=>'3',
						'payof_id'=>$refund_order[0]->refund_type_id,
						'payof_code'=>$refund_order[0]->refund_type_code,
						'credit_debit'=>'1',
						'amount'=>$refund_order[0]->refund_amount,
						'payment_date'=>date('Y-m-d H:i:s'),
						'remark'=>$refund_order[0]->refund_type_reason,
						'update_by'=>$user_name.'['.$user_id.']',
						'update_by_id'=>$user_id
					);
				
					$insert_id=$this->Payment_M->insert($paydata);
				
					$inv_id=$refund_order[0]->refund_type_id;
					
					$data = array( 
						'refund_process'=> '1',
						'refund_by'=>$user_name.'['.$user_id.']',
						'refund_by_id'=>$user_id,
						'refund_datetime'=>date('Y-m-d H:i:s'),
						'pay_id'=>$insert_id
					);
					
					$this->Payment_M->update_refundorder($data,$r_id);

					$showcontent='Refund Update';
					
					$rvar=array(
					'update' =>1,
					'showcontent'=>$showcontent
					);
					$encode_data = json_encode($rvar);
				}
			}
		}else{
			$showcontent='No Record Found ';
				
			$rvar=array(
                'update' =>0,
				'showcontent'=>$showcontent
                );
			$encode_data = json_encode($rvar);
		}
		
		echo $encode_data;
	}

	public function list_req_payment()
	{
		$this->load->view('Invoice/payment_request_list');
	}

	public function req_payment_process()
	{
		$mode=$this->input->post('mode');
		$amount=$this->input->post('amount');
		$req_payment_id=$this->input->post('req_payment_id');
		$cash_remark=$this->input->post('cash_remark');

		$input_card_mac=$this->input->post('input_card_mac');
		$input_card_bank=$this->input->post('input_card_bank');
		$input_card_digit=$this->input->post('input_card_digit');
		$input_card_tran=$this->input->post('input_card_tran');
		
		$this->load->model('Invoice_M');
		$this->load->model('Payment_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name;
		
		$sql="select *  from  org_payment_request  where id=".$req_payment_id;
        $query = $this->db->query($sql);
        $org_payment_request= $query->result();
		
		if(count($org_payment_request)>0)
		{
			if($org_payment_request[0]->payment_process>0)
			{
				$showcontent='Already Done : <a href="/Invoice/print_org_payment_invoice/"'.$req_payment_id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a> ';
				$rvar=array(
					'update' =>0,
					'showcontent'=>$showcontent
					);
				
			}else{

					$paydata = array( 
						'payment_mode'=> $mode,
						'payof_type'=>'3',
						'payof_id'=>$org_payment_request[0]->org_id,
						'payof_code'=>$org_payment_request[0]->org_code,
						'credit_debit'=>'0',
						'amount'=>$org_payment_request[0]->payment_amount,
						'payment_date'=>date('Y-m-d H:i:s'),
						'remark'=>$cash_remark,
						'update_by'=>$user_name.'['.$user_id.']',
						'update_by_id'=>$user_id,
						'card_bank'=>$input_card_mac,
						'card_remark'=>$input_card_bank,
						'cust_card'=>$input_card_digit,
						'card_tran_id'=>$input_card_tran,
					);
				
					$insert_id=$this->Payment_M->insert($paydata);
				
					if($insert_id>0)
					{
						$data = array( 
							'payment_process'=> '1',
							'payment_accept_by'=>$user_name.'['.$user_id.']',
							'payment_accept_by_id'=>$user_id,
							'payment_datetime'=>date('Y-m-d H:i:s'),
							'pay_id'=>$insert_id
						);
						
						$this->Payment_M->update_org_payment($data,$req_payment_id);
	
						$showcontent='Amount Receipt : <a href="/Invoice/print_org_payment_invoice/'.$req_payment_id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Invoice</a> ';

						$rvar=array(
							'update' =>1,
							'showcontent'=>$showcontent,
							'payid'=>$insert_id,
							);
					}
									
					
					
			}
		}else{
			$showcontent='No Record Found ';
				
			$rvar=array(
                'update' =>0,
				'showcontent'=>$showcontent,
				'payid'=>0,
                );
			
		}

		$encode_data = json_encode($rvar);
		
		echo $encode_data;
	}

	public function payment_form($req_id)
	{
		$sql="select *  from  org_payment_request  where id=".$req_id;
        $query = $this->db->query($sql);
        $data['req_payment_order']= $query->result();

        if(count($data['req_payment_order'])>0)
        {
			$sql="select * from organization_case_master 
			where id='".$data['req_payment_order'][0]->org_id."' ";
			$query = $this->db->query($sql);
			$data['org_info']= $query->result();
			
			$sql="select *,if(gender=1,'Male','Female') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age from patient_master where id='".$data['req_payment_order'][0]->patient_id."' ";
			$query = $this->db->query($sql);
			$data['person_info']= $query->result();

			if($data['req_payment_order'][0]->payment_process>0){
				$showcontent='Amount Receipt : <a href="/Invoice/print_org_payment_invoice/'.$data['req_payment_order'][0]->id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print Payment Reciept</a> ';
				echo $showcontent;

			}else{
				$this->load->view('Invoice/payment_req_panel',$data);
			}
        }else{
			echo 'No Record Found';
		}
	}

	public function getReqPaymentTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'id',
			1 => 'org_code',
			2 => 'payment_type_str',
			3 => 'patient_name',
			4=> 'payment_request_datetime',
			5=> 'payment_amount',
			6=> 'payment_process_str'
			);

		// getting total number records without any search
		$sql_f_all = "select *,
		(case payment_type when 1 then 'ORG'  else 'Other' end ) as payment_type_str ,
		if(payment_process<1,'Pending','Complete') as payment_process_str";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from org_payment_request ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //CaseCode-InsuranceCard-ClaimNo
			$sql_where.=" AND org_code LIKE '%".$requestData['columns'][0]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //p_code-P-Name
			$sql_where.=" AND ( patient_name LIKE '%".$requestData['columns'][1]['search']['value']."%') ";
			
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		
		$sql_order="  ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
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

	public function print_org_payment_invoice($req_id)
	{
		$sql="select *,
		date_format(payment_datetime,'%d-%m-%Y %h:%i:%s') as payment_date_str  
		from  org_payment_request  where id=".$req_id;
        $query = $this->db->query($sql);
        $data['req_payment_order']= $query->result();

        if(count($data['req_payment_order'])>0)
        {
        	$sql="select * from organization_case_master where id='".$data['req_payment_order'][0]->org_id."' ";
	        $query = $this->db->query($sql);
	        $data['org_info']= $query->result();
			
			$sql="select *,if(gender=1,'Male','Female') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age from patient_master where id='".$data['req_payment_order'][0]->patient_id."' ";
	        $query = $this->db->query($sql);
			$data['patient_master']= $query->result();
			
			
			//load mPDF library
			$this->load->library('m_pdf');
			$this->m_pdf->pdf->SetWatermarkText(H_Name);
			$this->m_pdf->pdf->showWatermarkText = true;
		
			$file_name='Invoice-Org-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
			$content=$this->load->view('Invoice/Invoice_org_payment',$data,TRUE);

			//echo $content;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");

        }else{
			echo 'No Record Found';
		}
				
		
	}


}