<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orgcase extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
	
	public function search()
	{
		$sdata=$this->input->post('txtsearch');

		$sdata = preg_replace('/[^A-Za-z0-9]/', '', $sdata);
		
		$sql = "select c.id,c.p_id,c.case_id_code,sum(o.opd_fee_amount) as OPD_Amount,sum(i.net_amount) as Invoice_Amount,
		c.insurance_company_name,c.p_name,p.p_code,c.date_registration,insurance_card_name,
		(case c.status when 0 then 'Pending' 
		when 1 then 'Ready for submission' 
		when 2 then 'Submitted' 
		when 3 then 'Payment Done'
		else 'Other' end) as str_status,
		Date_Format(c.date_registration,'%d-%m-%Y') as date_registration_in
		from (organization_case_master c join patient_master p  on c.p_id=p.id ) 
		left join opd_master o   on c.id=o.insurance_case_id  
		left join invoice_master i on c.id=i.insurance_case_id
		group by c.id ";
		
		if($sdata!='')
		{
			$sql.=" WHERE case_id_code = '".$sdata."' or p_name like '%".$sdata."%' or
					p_code = '".$sdata." order by  c.id desc   limit 20";
		}else
		{
			$sql.=" order by  c.id desc   limit 20";
		}
		
		$query = $this->db->query($sql);
        $data['searchdata']= $query->result();

        $this->load->view('Invoice/Case_List',$data);
	}
		
	public function search_all()
	{
		$this->load->view('Invoice/case_list_all');
	}
	
	public function case_invoice($casecode,$print=0,$caseid=0,$med_include=1)
	{
		if($caseid<1)
		{
			$sql="select * from organization_case_master where case_id_code='".$casecode."'";
		}else{
			$sql="select * from organization_case_master where id='".$caseid."'";
		}
		
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();

		$pno=$data['orgcase'][0]->p_id;
		$caseid=$data['orgcase'][0]->id;

		$this->db->query("CALL p_org_update(".$caseid.")");
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$ins_comp_id=$data['orgcase'][0]->insurance_id;
		$inc_card_id=$data['orgcase'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$inc_card_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();

		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		//$sql="select * from v_case_invoice v
		//		where v.id=".$caseid." order by Charge_type,Adate ";
		
		//$query = $this->db->query($sql);
        //$data['showinvoice']= $query->result();

        $sql="select * from v_case_invoice_1 v
				where v.id=".$caseid." order by Charge_type,Adate ";
		$query = $this->db->query($sql);
        $data['showinvoice1']= $query->result();

        $sql="select * from v_case_invoice_2 v
				where v.id=".$caseid." order by Charge_type,Adate ";
		$query = $this->db->query($sql);
        $data['showinvoice2']= $query->result();
		
		//$sql="select sum(amount) as GTotal from v_case_invoice v
		//		where v.id=".$caseid."  ";
		//$query = $this->db->query($sql);
        //$data['invoiceGtotal']= $query->result();
		
		$sql="select m.*,m.id as med_id
				from invoice_med_master m join ipd_master p join organization_case_master o
				on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
				where o.id=".$caseid."  ";

		$query = $this->db->query($sql);
		$data['MedInvoice_data']= $query->result();

		$sql="select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv 
				from invoice_med_master m join ipd_master p join organization_case_master o
				on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
				where o.id=".$caseid." group by o.id ";

		$query = $this->db->query($sql);
		$data['MedInvoice']= $query->result();
		
		if(count($data['MedInvoice'])==0)
		{
			$sql="select m.* ,m.id as med_id
				from invoice_med_master m join organization_case_master o
				on m.case_id=o.id and m.case_credit=1
				where o.id=".$caseid."  ";

			$query = $this->db->query($sql);
			$data['MedInvoice_data']= $query->result();

			$sql="select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv 
				from invoice_med_master m join organization_case_master o
				on m.case_id=o.id and m.case_credit=1
				where o.id=".$caseid." group by o.id ";

			$query = $this->db->query($sql);
			$data['MedInvoice']= $query->result();
		}
		
		$data['med_include']=$med_include;

		$sql="select p.* ,date_format(payment_date,'%d-%m-%Y') as str_date,
				if(credit_debit=0,if(payment_mode=1,'CASH','BANK'),if(payment_mode=1,'CASH RETURN','BANK RETURN')) as Pay_mode
				from payment_history p
				where p.payof_type=3 and  p.payof_id=$caseid";
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();


        if($print>0)
		{
			$this->load->library('m_pdf');
			$file_name='Org_Invoice_-'.$casecode.'-'.date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->curlAllowUnsafeSslRequests = true;
			$this->m_pdf->useSubstitutions = false;
			$print_content=$this->load->view('Invoice/caseinvoice_print_V',$data,TRUE);
			//echo $print_content;
			$this->m_pdf->pdf->WriteHTML($print_content);
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");

		}else{
			$this->load->view('Invoice/caseinvoice_V',$data);
		}
	
	}

	public function case_invoice_ipd($caseid)
	{
		$sql="select * from organization_case_master where id='".$caseid."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();

		$pno=$data['orgcase'][0]->p_id;
		$caseid=$data['orgcase'][0]->id;

		$this->db->query("CALL p_org_update(".$caseid.")");
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$ins_comp_id=$data['orgcase'][0]->insurance_id;
		$inc_card_id=$data['orgcase'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$inc_card_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();

		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		//$sql="select * from v_case_invoice v
		//		where v.id=".$caseid." order by Charge_type,Adate ";
		
		//$query = $this->db->query($sql);
        //$data['showinvoice']= $query->result();

        $sql="select * from v_case_invoice_1 v
				where v.id=".$caseid." order by Charge_type,Adate ";
		$query = $this->db->query($sql);
        $data['showinvoice1']= $query->result();

        $sql="select * from v_case_invoice_2 v
				where v.id=".$caseid." order by Charge_type,Adate ";
		$query = $this->db->query($sql);
        $data['showinvoice2']= $query->result();
		
        $this->load->view('Invoice/caseinvoice_ipd_V',$data);
	
	}

	public function ORG_info(){
		
		$org_no = $this->input->post('input_org_no');
		
		$sql="select * from organization_case_master 
		where case_type=0 and case_id_code='$org_no' ";
        $query = $this->db->query($sql);
        $data_org= $query->result();
		
		if(count($data_org)>0)
		{
			$UHID=$data_org[0]->p_id;

			$sql="select * from patient_master_exten where id='$UHID' ";
        	$query = $this->db->query($sql);
			$data_person= $query->result();
			
			$Patient_id=$data_person[0]->id;
			$Patient_info=$data_person[0]->p_code.'/'.$data_person[0]->p_fname.' '.$data_person[0]->p_relative.' '.$data_person[0]->p_rname.' /Age :'.$data_person[0]->str_age;
			
			$org_info=$Patient_info.'/'.$data_org[0]->insurance_company_name.
			'/Reg.:'.$data_org[0]->insurance_no_1.'/Reg.Dt:'.$data_org[0]->date_registration;
			
			$org_id=$data_org[0]->id;
			
		}else{
			$org_id=0;
			$org_info='';
			
		}

		$rvar=array(
			'org_id' =>$org_id,
			'org_info'=>$org_info
			);

		$encode_data = json_encode($rvar);
		echo $encode_data;

	  }
	
	public function update_itemrateqty()
	{
		$item_type_id=$this->input->post('item_type_id');
		$item_id=$this->input->post('item_id');
		
		$rate=$this->input->post('rate');
		$orgcode=$this->input->post('orgcode');
		$insurance_id=$this->input->post('insurance_id');
		$master_item_id=$this->input->post('master_item_id');

		$sql="select * from invoice_item 
			where id=".$item_id." ";
		$query = $this->db->query($sql);
		$invoice_item= $query->result();

		$qty=0;

		if(count($invoice_item)>0)
		{
			$qty=$invoice_item[0]->item_qty;
		}

		$this->load->model('invoice_M');
		
		$amount_value=$qty * $rate;

			$data['update_invoice_item'] = array( 
				'item_qty' => $qty,
				'item_rate' => $rate,
				'item_amount' => $amount_value,
				'org_code' => $orgcode
			);
			
			$inser_id=$this->invoice_M->update_item( $data['update_invoice_item'],$item_id);
		
		$this->load->model('item_M');
		
		$data['update_insurance_item'] = array( 
				'hc_items_id' => $master_item_id,
				'hc_insurance_id' => $insurance_id,
				'amount1' => $amount_value,
				'code' => $orgcode
			);
		
		
		$sql="select * from hc_items_insurance where  hc_items_id=".$master_item_id." and hc_insurance_id=".$insurance_id;
		$query = $this->db->query($sql);
		$chkitem= $query->result();
		
		if($insurance_id==2)
		{
			if(count($chkitem)>0 )
			{
				$insDone=$this->item_M->update_in_item( $data['update_insurance_item'],$chkitem[0]->id);
			}else{
				$insDone=$this->item_M->insert_in_item( $data['update_insurance_item']);
			}
		}
		
		
		$sql="select * from invoice_item where id=".$item_id;
		$query = $this->db->query($sql);
		$data['invoiceitem']= $query->result();

		$invoice_id=0;
		
		if(count($data['invoiceitem'])>0)
		{
			$invoice_id=$data['invoiceitem'][0]->inv_master_id;
		}
		
		$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
	
		$sql="select *,(case payment_mode when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
				
		$discount_amount=$data['invoice_master'][0]->discount_amount ;
		$correction_amount=$data['invoice_master'][0]->correction_amount ;
		
		$total_amount=$data['invoiceGtotal'][0]->Gtotal;
		$net_amount=$total_amount-$discount_amount;
		$net_amount=$net_amount-$correction_amount;
		$balance_amount=$net_amount;
				
		$dataupdate = array( 
				'total_amount' => $total_amount,				
				'net_amount' => $net_amount
				);

		$this->load->model('Invoice_M');
		$this->Invoice_M->update($dataupdate,$invoice_id);
		
	}
	
	public function update_status()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Ocasemaster_M');
		
		$case_id=$this->input->post('caseid');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$sql="select * from organization_case_master where id='".$case_id."'";
		$query = $this->db->query($sql);
        $case_master= $query->result();
		
		$log=$case_master[0]->log.'\nUpdate Status:'.$this->input->post('status').'-Upd By:'.$user_name.'['.$user_id.']'.date('d/m/Y H:m:s');
		
		$status=$this->input->post('status');

		$org_submit_date= str_to_MysqlDate($this->input->post('org_submit_date'));

		$data = array( 
					'status'=> $status,
					'log'=>$log,
		);

		if($status==1)
		{
			$data['org_submit_date']=$org_submit_date;
		}
			
		$this->Ocasemaster_M->update( $data,$case_id);
			
	}
	
	public function org_contingent_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$htmldata=$this->input->post('HTMLData');
		$case_id=$this->input->post('case_id');
		
		$this->load->model('Ocasemaster_M');
		$data['master'] = array( 
                    'contingent_bill' => $htmldata,
                    'contingent_update_by' => $user_name
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
	
	public function case_invoice_print($caseid)
	{
			
		$sql="select * from organization_case_master where id=".$caseid;
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$pno=$data['orgcase'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$ins_comp_id=$data['orgcase'][0]->insurance_id;
		$inc_card_id=$data['orgcase'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$inc_card_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();
			
		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
			
		$sql="select * from v_case_invoice v
				where v.id=".$caseid." order by s_Date ";
		
		$query = $this->db->query($sql);
        $data['showinvoice']= $query->result();
		
		$sql="select sum(amount) as GTotal from v_case_invoice v
				where v.id=".$caseid." ";
		$query = $this->db->query($sql);
        $data['invoiceGtotal']= $query->result();

        $this->load->view('Invoice/caseinvoice_V',$data);
	
	}
	
	public function getCaseTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'case_id_code',
			1 => 'Reg_No',
			2 => 'p_f_info',
			3 => 'date_registration',
			4=> 'insurance_card_name',
			5=> 'insurance_company_name',
			6=> 'str_status'
			);

		// getting total number records without any search
		$sql_f_all = "select c.id,c.p_id,c.case_id_code,Concat(c.insurance_no,'/',c.insurance_no_1) as Reg_No,
		c.insurance_company_name,Concat(c.p_name,'<br/>','[',p_code,if(i.id is null,'',concat('/',i.ipd_code)),']') as p_f_info,p.p_code,
		c.date_registration,c.insurance_card_name,c.status,
		(case c.status when 0 then 'Pending' 
		when 1 then 'Ready for submission' 
		when 2 then 'submitted' else 'Other' end) as str_status,c.insurance_id,
		Date_Format(c.date_registration,'%d-%m-%Y') as date_registration_in ,i.ipd_code ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from ((organization_case_master c join patient_master p  on c.p_id=p.id ) 
					left join ipd_master i on c.id=i.case_id  and   p.id=i.p_id) ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE c.case_type=0 ";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //CaseCode-InsuranceCard-ClaimNo
			$sql_where.=" AND case_id_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //p_code-P-Name
			$sql_where.=" AND ( p.p_fname LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			$sql_where.=" OR p.mphone1 LIKE '".$requestData['columns'][1]['search']['value']."' ";
			$sql_where.=" OR i.ipd_code LIKE '%".$requestData['columns'][1]['search']['value']."' ";
			$sql_where.=" OR p_code LIKE '%".$requestData['columns'][1]['search']['value']."' )";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][2]['search']['value']) ){  //insurance_card_name
			$sql_where.=" AND c.insurance_card_name LIKE '%".$requestData['columns'][2]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		//if( !empty($requestData['columns'][3]['search']['value']) ){  //insurance_company_name
		//	$sql_where.=" AND insurance_company_name LIKE '%".$requestData['columns'][3]['search']['value']."%' ";
		//	$sql_where_flag=1;
		//}
		
		if( !empty($requestData['columns'][3]['search']['value'])){  //insurance_card_name
			
			if($requestData['columns'][3]['search']['value'] < 0){
			$sql_where.=" AND c.insurance_id not in (1,2,53,63) ";
			$sql_where_flag=1;}
			
			if($requestData['columns'][3]['search']['value']>0){
			$sql_where.=" AND c.insurance_id = '".$requestData['columns'][3]['search']['value']."' ";
			$sql_where_flag=1;}
		}
		
		
		if( !empty($requestData['columns'][4]['search']['value']) ){  //insurance_company_name
			$sql_where.=" AND ( c.insurance_no LIKE '".$requestData['columns'][4]['search']['value']."%' ";
			$sql_where.=" OR c.insurance_no_1 LIKE '".$requestData['columns'][4]['search']['value']."%' ) ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][5]['search']['value'])){  //insurance_card_name
			$sql_where.=" AND c.status = '".$requestData['columns'][5]['search']['value']."' ";
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		
		$sql_order=" group by c.id ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
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
	
	public function contingent_bill($caseid,$print=0)
	{
		$sql="select * from organization_case_master where id='".$caseid."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$pno=$data['orgcase'][0]->p_id;
		$caseid=$data['orgcase'][0]->id;
		$claim_id=$data['orgcase'][0]->insurance_no_1;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$ins_comp_id=$data['orgcase'][0]->insurance_id;
		$inc_card_id=$data['orgcase'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$inc_card_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();

		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		$content=$data['orgcase'][0]->contingent_bill;

		if($print==1)
		{
			//echo $content;
			$page_head="<style>@page {
										margin-top: 0.5cm;
										margin-bottom: 0.5cm;
										margin-left: 0.5cm;
										margin-right: 0.5cm;
								}
						</style>";

			$content=$page_head.$content;
			//create_report_pdf($content,'Report_1.pdf');
			$this->load->library('m_pdf');
			$file_name="Report-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			$this->load->view('Invoice/Org_Contingent_bill',$data);
		}
		
	}

	
	public function create_contingent_bill($caseid)
	{
		$sql="select * from organization_case_master where id='".$caseid."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$pno=$data['orgcase'][0]->p_id;
		$caseid=$data['orgcase'][0]->id;
		$claim_id=$data['orgcase'][0]->insurance_no_1;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$ins_comp_id=$data['orgcase'][0]->insurance_id;
		$inc_card_id=$data['orgcase'][0]->insurance_card_id;
		
		$sql="select * from hc_insurance_card where id=".$inc_card_id;
		$query = $this->db->query($sql);
		$data['hc_insurance_card']= $query->result();

		$sql="select * from hc_insurance where id=".$ins_comp_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		$sql="select * from v_case_invoice v
				where v.id=".$caseid." order by Charge_type,Adate ";
		
		$query = $this->db->query($sql);
        $data['showinvoice']= $query->result();
		
		$sql="select sum(amount) as GTotal from v_case_invoice v
				where v.id=".$caseid."  ";
		$query = $this->db->query($sql);
        $data['invoiceGtotal']= $query->result();
		
		$content="<style>@page {

			margin-top: 0.5cm;
			margin-bottom: 0.5cm;
			margin-left: 0.5cm;
			margin-right: 0.5cm;
			
			margin-header:0.5cm;
			margin-footer:0.5cm;
			header: html_myHeader;
			footer: html_myFooter;
			}
			
			</style>";


		$content.='<h3 style="text-align:center; vertical-align:middle">CLAIM ID : '.$claim_id.'</h3>';
		$content.="<p><b>Name of Hospital : </b>".H_Name."</p>";
			
		$content.='<h3 style="text-align:center; vertical-align:middle">CONTINGENT BILL</h3>';
		
		$content.='<table border="0" cellpadding="1" cellspacing="1" width="100%"  >';
		$content.='<tr><td colspan="2" rowspan="1">Voucher No. ______________________________</td><td>Date : ………………………</td></tr>';
		$content.='<tr><td colspan="2" rowspan="1">Amount of allotment : </td><td>Rs.……………………………</td></tr>';
		$content.='<tr><td colspan="2" rowspan="1">Amount expended and for which bills have already been submitted for payment :</td><td>Rs.……………………………</td></tr>';
		$content.='<tr><td colspan="2" rowspan="1" >Balance of allotment excliding the amount of this bills :</td><td>Rs.……………………………</td></tr>';
		$content.='</table>';
		$content.='<p>Expenditure an account of : Treatment/ Super Treatment/ Special investigation / Consultant fee</p>';
		
		$content.='<table border="0" cellpadding="1" cellspacing="1" width="100%" >';
		$content.='<tr>';
		$content.='<td style="width:200px;" >In respect of<br><b>Name of Patient: '.strtoupper($data['orgcase'][0]->p_name).'</b></td>';
		$content.='<td>Relation : '.strtoupper($data['orgcase'][0]->relation_with_cardholder).'</td>';
		$content.='<td>Service No.: <b>'.strtoupper($data['orgcase'][0]->insurance_no_2).'</b></td>';
		$content.='</tr>';
		
		$content.='<tr>';
		$content.="<td>ESM Name : <b>".strtoupper($data['orgcase'][0]->insurance_card_name).'</b></td>';
		$content.="<td>ECHS CARD No. : <b>".strtoupper($data['orgcase'][0]->insurance_no).'</b></td>';
		$content.="<td>Rank : ".strtoupper($data['orgcase'][0]->card_remark).'</td>';
		$content.='</tr>';
		$content.='</table>';
		
		$content.='<p><B>Authority:</B><br/>';
		$content.='Government of India letter No. 24 (3)/03/US/(WE)/D (Res) (i) dated 08 Sep 2003 <br/>';
		$content.='Government of India letter No. 24 (8)/03/US/(WE)/D (Res) dated 19 Dec 2003 <br/>';
		$content.='Central Organization ECHS Letter NoB/49773/AG/ECHS dated 25 May 2004.</p>';
		
		$sql='select c.id,c.case_id_code,c.p_id,sum(o.opd_fee_amount) as tamount,
			count(o.opd_id) as item_qty,Min(o.opd_fee_amount) as rate
			from opd_master o join organization_case_master c on o.insurance_case_id=c.id
			where opd_status in (1,2) and  id='.$caseid.' group by c.id';
		
		$query = $this->db->query($sql);
		$opd= $query->result();

		$sql="select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
				from invoice_master m join invoice_item i join hc_item_type t  
				on m.id=i.inv_master_id and i.item_type=t.itype_id
				where item_type=34 and m.ipd_include=1 and invoice_status=1 and  
				insurance_case_id=$caseid
				group by i.item_type";
		$query = $this->db->query($sql);
        $data['invoice_list_1']= $query->result();

		$sql="select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
				from invoice_master m join invoice_item i join hc_item_type t  
				on m.id=i.inv_master_id and i.item_type=t.itype_id
				where item_type not in (34,35) and m.ipd_include=1 and invoice_status=1 and  
				insurance_case_id=$caseid
				group by i.item_type";
		$query = $this->db->query($sql);
        $data['invoice_list_2']= $query->result();

		$sql="select i.item_type,t.`desc` AS item_name,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,i.item_rate
				from invoice_master m join invoice_item i join hc_item_type t  
				on m.id=i.inv_master_id and i.item_type=t.itype_id
				where item_type=35 and m.ipd_include=1 and invoice_status=1 and  
				insurance_case_id=$caseid
				group by i.item_type";
		$query = $this->db->query($sql);
        $data['invoice_list_3']= $query->result();

		$sql="select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
				i.item_rate
					FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
						 join ipd_item_type t  ON i.item_type=t.itype_id
					 and i.item_type=t.itype_id
					WHERE  i.package_id=0 and t.itype_id=2 AND  m.case_id=$caseid
					group by i.item_type";
		$query = $this->db->query($sql);
		$data['ipd_invoice_list_1']= $query->result();

		$sql="select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
				i.item_rate
					FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
						 join ipd_item_type t  ON i.item_type=t.itype_id
					 and i.item_type=t.itype_id
					WHERE  i.package_id=0 and t.itype_id not in (2,6) AND  m.case_id=$caseid
					group by i.item_type";
		$query = $this->db->query($sql);
		$data['ipd_invoice_list_2']= $query->result();
		
		$sql="select i.item_type,i.item_name,t.group_desc,sum(i.item_amount) as tamount,sum(i.item_qty) as unit,
				i.item_rate
					FROM (ipd_master m join ipd_invoice_item i on m.id=i.ipd_id)
						 join ipd_item_type t  ON i.item_type=t.itype_id
					 and i.item_type=t.itype_id
					WHERE  i.package_id=0 and t.itype_id=6 AND  m.case_id=$caseid
					group by i.item_type";
		$query = $this->db->query($sql);
		$data['ipd_invoice_list_3']= $query->result();
		
		$sql="select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv 
				from invoice_med_master m join ipd_master p join organization_case_master o
				on m.ipd_id=p.id and p.case_id=o.id and m.ipd_credit=1 and m.ipd_credit_type=1
				where o.id=".$caseid." group by o.id ";
		$query = $this->db->query($sql);
		$data['MedInvoice']= $query->result();

		$sql="SELECT p.* 
			FROM  ipd_package p JOIN ipd_master m ON p.ipd_id=m.id
			WHERE m.case_id=$caseid";
		$query = $this->db->query($sql);
		$data['ipd_package']= $query->result();

		
		if(count($data['MedInvoice'])==0)
		{
			$sql="select sum(m.net_amount) as Med_Amount,o.id ,count(m.id) as no_inv 
				from invoice_med_master m join organization_case_master o
				on m.case_id=o.id and m.case_credit=1
				where o.id=".$caseid." group by o.id ";

			$query = $this->db->query($sql);
			$data['MedInvoice']= $query->result();
		}
				
		$sql="select p.* from ipd_master p join organization_case_master o
			on  p.case_id=o.id where o.id=". $caseid;
		$query = $this->db->query($sql);
		$IPDMaster= $query->result();
		
		$tableHead='<table border="1" cellpadding="1" cellspacing="0" style="width:100%">
						<tr>
							<th style="width:50px">Sr.No.</th>
							<th style="width:100px">Date</th>
							<th >Details of Expenditure</th>
							<th colspan="2" >Number or Quantity</th>
							<th  style="width:100px;align:right;">Rate</th>
							<th style="width:100px;align:right;">Per</th>
							<th style="width:100px;align:right;">Amount</th>
						</tr>';
		$tbody="";
		$srno=0;
		$total_gross_bill=0.00;
		
		if(count($opd)>0)
		{ 
			$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>OPD</td>
				<td style="text-align:right">'.$opd[0]->rate.'</td><td style="text-align:right">'.$opd[0]->item_qty.'</td>
				<td style="text-align:right">'.$opd[0]->rate.'</td><td>CGHS</td><td style="text-align:right">'.$opd[0]->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$opd[0]->tamount;
		}

		foreach($data['ipd_package'] as $row)
		{ 
			$srno=$srno+1;
			$tbody.='<tr>';
			$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>Package :'.$row->package_name.'</td>
			<td style="text-align:right">'.$row->package_Amount.'</td><td style="text-align:right">1</td><td style="text-align:right">'.$row->package_Amount.'</td><td>CGHS</td><td style="text-align:right">'.$row->package_Amount.'</td>';
			$tbody.="</tr>";
			$total_gross_bill=$total_gross_bill+$row->package_Amount;
		}
		
		foreach($data['invoice_list_1'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		foreach($data['ipd_invoice_list_1'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		foreach($data['invoice_list_2'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		foreach($data['ipd_invoice_list_2'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		foreach($data['invoice_list_3'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		foreach($data['ipd_invoice_list_3'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}

		

		foreach($data['ipd_invoice_list_2'] as $row)
		{ 
			if($row->item_type<>'')
			{
				$srno=$srno+1;
				$tbody.='<tr>';
				$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>'.$row->item_name.'</td>
				<td style="text-align:right">'.$row->item_rate.'</td><td style="text-align:right">'.$row->unit.'</td><td style="text-align:right">'.$row->item_rate.'</td><td>CGHS</td><td style="text-align:right">'.$row->tamount.'</td>';
				$tbody.="</tr>";
				$total_gross_bill=$total_gross_bill+$row->tamount;
			}
		}
		
		if(count($data['MedInvoice'])>0)
		{
			$srno=$srno+1;
			$tbody.='<tr>';
			$tbody.='<td>'.$srno.'</td><td>'.date('Y-m-d').'</td><td>Medicine</td>
			<td style="text-align:right">'.$data['MedInvoice'][0]->Med_Amount.'</td><td style="text-align:right">'.$data['MedInvoice'][0]->no_inv.'</td>
			<td style="text-align:right">'.$data['MedInvoice'][0]->Med_Amount.'</td><td></td><td style="text-align:right">'.$data['MedInvoice'][0]->Med_Amount.'</td>';
			$tbody.='</tr>';
			$total_gross_bill=$total_gross_bill+$data['MedInvoice'][0]->Med_Amount;
		}
		
		$tbody.='<tr><td colspan="5"></td><td colspan="2">Gross Total </td><td style="text-align:right">'.Round($total_gross_bill,0).'</td></tr>';
		
		if(count($IPDMaster)>0)
		{
			if($IPDMaster[0]->chargeamount1>0)
			{
				$tbody.='<tr>';
				$tbody.='<td colspan="5"></td><td colspan="2">'.$IPDMaster[0]->charge1.'</td>
				<td style="text-align:right">'.$IPDMaster[0]->chargeamount1.'</td>';
				$tbody.='</tr>';
				$total_gross_bill=$total_gross_bill+$IPDMaster[0]->chargeamount1;
			}
			
			if($IPDMaster[0]->chargeamount2>0)
			{
				$tbody.='<tr>';
				$tbody.='<td colspan="5"></td><td colspan="2">'.$IPDMaster[0]->charge2.'</td>
				<td style="text-align:right">'.$IPDMaster[0]->chargeamount2.'</td>';
				$tbody.='</tr>';
				$total_gross_bill=$total_gross_bill+$IPDMaster[0]->chargeamount2;
			}
			
			if($IPDMaster[0]->Discount>0)
			{
				$tbody.='<tr>';
				$tbody.='<td colspan="5"></td><td colspan="2">'.$IPDMaster[0]->Discount_Remark.'</td>
				<td style="text-align:right">'.$IPDMaster[0]->Discount.'</td>';
				$tbody.='</tr>';
				$total_gross_bill=$total_gross_bill-$IPDMaster[0]->Discount;
			}
			
			if($IPDMaster[0]->Discount2>0)
			{
				$tbody.='<tr>';
				$tbody.='<td colspan="5"></td><td colspan="2">'.$IPDMaster[0]->Discount_Remark2.'</td>
				<td style="text-align:right">'.$IPDMaster[0]->Discount3.'</td>';
				$tbody.='</tr>';
				$total_gross_bill=$total_gross_bill-$IPDMaster[0]->Discount2;
			}
			
			if($IPDMaster[0]->Discount3>0)
			{
				$tbody.='<tr>';
				$tbody.='<td colspan="5"></td><td colspan="2">'.$IPDMaster[0]->Discount_Remark3.'</td>
				<td style="text-align:right">'.$IPDMaster[0]->Discount3.'</td>';
				$tbody.='</tr>';
				$total_gross_bill=$total_gross_bill-$IPDMaster[0]->Discount3;
			}
			
		}

		$tableFooter="";
		$tableFooter.='<tr><td colspan="5"></td><td colspan="2"><b>Net Total </b></td><td style="text-align:right"><b>'.Round($total_gross_bill,0).'</b></td></tr>';
		
		$tableFooter.="</table>";
		
		$content.=$tableHead.$tbody.$tableFooter;
		
		$content.="<p>Net Amount due(In words) : Rs. ".number_to_word(Round($total_gross_bill,0))." </p>";
		
		$content.="<table border=0 width='100%'><tr><td> </td><td>Date : ......................</td><td></td><td>Pre Received Payment</td></tr>";
		$content.="<tr><td> </td><td>From : ......................</td><td></td><td>.....................</td></tr></table>";
		
		$content.='<p><B>Certified that</B><br/>';
		$content.='<ol><li>Certified that above have been claimed on the basis of approved charges as noted in MOA signed for the treatment of ECHS member. </li>';
		$content.='<li>The rate is/are fair and reasonable and less than CGHS rates</li>';
		$content.='<li>The expenditure incurred is debatable to major Head 2076 Minor head 107, Sub Head E-Medical treatment related expenditure code  head 365/00.</li>';
		$content.='<li>The expenditure has been incurred the interest of Ex-Serviceman</li></ol></p>';
		
		$content.='<table border=0 width="100%"><tr><td width="150px">Vetted and Recommended / Not Recommended</td><td></td><td>Sig of OIC ECHS Polyclinic</td></tr>';
		$content.='<tr><td><br>Date : ......................<br><br>Place: ......................</td><td></td><td>.....................</td></tr></table>';

		$dataupdate = array( 
				'contingent_bill' => $content			
		);

		$this->load->model('Ocasemaster_M');
		$this->Ocasemaster_M->update($dataupdate,$caseid);
		
		$sql="select * from organization_case_master where id='".$caseid."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();

		echo 'Created';
		
		//create_report_pdf($content,'Report_1.pdf');
		
	}

	public function org_confirm_payment()
	{
		$amount_r=$this->input->post('amount_r');
		$amount_d=$this->input->post('amount_d');
		$amount_claim=$this->input->post('amount_claim');
		$amount_tds=$this->input->post('amount_tds');
		$date_payment=str_to_MysqlDate($this->input->post('date_payment'));
		$pay_info=$this->input->post('pay_info');
		$org_id=$this->input->post('org_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');

		$this->load->model('Ocasemaster_M');

		$data= array(
			'pay_date_received' => $date_payment,
			'amount_recived' => $amount_r,
			'amount_deduction' => $amount_d,
			'amount_claim' => $amount_claim,
			'amount_tds' => $amount_tds,
			'amount_payment_info' => $pay_info,
			'status' => '3',
			'amount_update_by' => $user_name
		);

		$this->Ocasemaster_M->update( $data,$org_id);
		
		$rvar=array(
					'update' =>1,
					'showcontent'=>'Update'
					);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	

	public function org_payment_request()
	{
		$sql="select * from org_payment_request ";
        $query = $this->db->query($sql);
        $data['org_info']= $query->result();
		
		$sql="select * from v_ipd_list where org_id='".$data['org_info'][0]->id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) as str_age from patient_master where id='".$data['org_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$this->load->view('Invoice/org_case_payment',$data);
	}

	public function load_model_box($caseidcode)
	{
		$sql="select * from organization_case_master where case_id_code='".$caseidcode."' ";
        $query = $this->db->query($sql);
        $data['org_info']= $query->result();
		
		$sql="select * from v_ipd_list where org_id='".$data['org_info'][0]->id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) as str_age from patient_master where id='".$data['org_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$this->load->view('Invoice/org_case_payment',$data);
	}

	public function payment_request()
	{
		$input_pay_amount=$this->input->post('input_pay_amount');
		$caseidcode=$this->input->post('caseid');
		$input_remark=$this->input->post('input_remark');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');

		$sql="select * from organization_case_master where id='".$caseidcode."' ";
        $query = $this->db->query($sql);
        $org_info= $query->result();

        $sql="select * from org_payment_request where payment_process=0 and org_id='".$caseidcode."' ";
        $query = $this->db->query($sql);
        $org_payment_request= $query->result();

        $patient_id=0;
        $org_date=date('Y-m-d H:i:s');

        if(count($org_info)>0)
        {
			$patient_id=$org_info[0]->p_id;

        	$sql="select *,if(gender=1,'Male','Female') as xgender,
			IFNULL(GET_AGE_BY_DOB(dob),age) as str_age from patient_master where id='".$patient_id."' ";
			$query = $this->db->query($sql);
			$person_info= $query->result();
		
			$org_date=$org_info[0]->insert_datetime;
			$patient_name=$person_info[0]->p_fname;

			$org_code=$org_info[0]->case_id_code;
        }

		$paydata = array( 
			'org_id'=> $caseidcode,
			'org_code'=>$org_code,
			'patient_id'=>$patient_id,
			'org_date'=>$org_date,
			'patient_name'=>$patient_name,
			'payment_amount'=>$input_pay_amount,
			'payment_request_datetime'=>date('Y-m-d H:i:s'),
			'request_by_id'=>$user_id,
			'request_by'=>$user_name,
			'remark'=>$input_remark,
		);

		$this->load->model('Payment_M');
		
		if(count($org_payment_request)<1)
		{
			$insert_id=$this->Payment_M->insert_org_payment($paydata);
		}else{
			$this->Payment_M->update_org_payment($paydata,$org_payment_request[0]->id);
			$insert_id=$org_payment_request[0]->id;
		}
		
		echo 'Request has been Created : Req. ID -> '.$insert_id;
	}


	public function refund_request()
	{
		$input_refund_amount=$this->input->post('input_refund_amount');
		$caseidcode=$this->input->post('caseid');
		$input_remark=$this->input->post('input_refund_remark');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']'.date('d-m-Y H:i:s');

		$sql="select * from organization_case_master where id='".$caseidcode."' ";
        $query = $this->db->query($sql);
        $org_info= $query->result();

		$sql="select * from refund_order where refund_process=0 and refund_type=3 and refund_type_id=$caseidcode";
		$query = $this->db->query($sql);
        $refund_order= $query->result();

        $patient_id=0;
        $org_date=date('Y-m-d H:i:s');

        if(count($org_info)>0)
        {
			$patient_id=$org_info[0]->p_id;

        	$sql="select *,if(gender=1,'Male','Female') as xgender,
			IFNULL(GET_AGE_BY_DOB(dob),age) as str_age from patient_master where id='".$patient_id."' ";
			$query = $this->db->query($sql);
			$person_info= $query->result();
		
			$org_date=$org_info[0]->insert_datetime;
			$patient_name=$person_info[0]->p_fname;

			$org_code=$org_info[0]->case_id_code;
        }

		$RefundRequest = array( 
			'refund_type' => 3,
			'refund_type_id' => $caseidcode,
			'refund_type_code' => $org_code,
			'refund_type_reason' => $input_remark,
			'approved_by_id' => $user_id,
			'approved_by' => $user_name,
			'refund_amount' => $input_refund_amount,
			'patient_id' => $patient_id,
			'patient_name' =>$patient_name
			);

		$this->load->model('Payment_M');
		
		if(count($refund_order)<1)
		{
			$insert_id=$this->Payment_M->insert_refundorder($RefundRequest);
			echo 'Refund Request has been Created : Req. ID -> '.$insert_id;
		}else{
			echo 'Refund Request Pending, Please Refund or Cancel Last Refund Request';
		}
		
	}



}