<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
        //$this->load->library("Pdf");
    }
  
    public function opd() {
	
		$this->load->library('table');
		
		$template = array(
			'table_open'            => '<table class="table table-striped" style="border=1;">',
			'thead_open'            => '<thead>',
			'thead_close'           => '</thead>',

			'heading_row_start'     => '<tr>',
			'heading_row_end'       => '</tr>',
			'heading_cell_start'    => '<th>',
			'heading_cell_end'      => '</th>',

			'tbody_open'            => '<tbody>',
			'tbody_close'           => '</tbody>',

			'row_start'             => '<tr>',
			'row_end'               => '</tr>',
			'cell_start'            => '<td>',
			'cell_end'              => '</td>',

			'row_alt_start'         => '<tr>',
			'row_alt_end'           => '</tr>',
			'cell_alt_start'        => '<td>',
			'cell_alt_end'          => '</td>',

			'table_close'           => '</table>'
			);

		$this->table->set_template($template);

		$query = $this->db->query("select o.opd_code,o.P_name,o.apointment_date,o.doc_name
		from opd_master o");

		$table_show = $this->table->generate($query); 

		$content=$table_show;
		$this->load->library('m_pdf');
		
		$file_name="Report-OPD-".date('Ymdhis').".pdf";
		$filepath=$file_name;
		$this->m_pdf->pdf->WriteHTML($content);
		$this->m_pdf->pdf->Output($filepath,"I");

    }

	public function index()
	{
		$this->load->view('Report/ReportMain');
	}
	public function report_opd_app()
	{
		
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/rep_opd_p',$data);
	}
	
	
	public function report_charge_app()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/rep_other_charge',$data);
	}
	
	public function report_ipdpayment_app()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode where id in (1,2) ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/ipd_payment_report',$data);
	}
	
	public function report_totalpayment_app()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode where id in (1,2) ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/rep_total_payment',$data);
	}
	
	public function report_emp_total_app()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode where id in (1,2) ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/rep_collocation_emp',$data);
	}
	
	public function report_opd_app_show($opd_date_range,
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by,$output=0)
	{
		$sql_f_all = "select o.opd_code,
		o.P_name ,p_code ,Date_Format(o.apointment_date,'%d-%m-%Y') as 'App_Date',
		o.doc_name ,if(o.insurance_id>1,'Org.','Direct') as 'Type',
		m.mode_desc as 'PayMode',o.opd_fee_gross_amount as 'GrossAmt',
		o.opd_discount as Discount,	o.opd_fee_amount as 'FeeAmt',
		o.prepared_by,IFNULL(GET_AGE_BY_DOB(dob),age)  as Age ";
		
		$sql_from=" from opd_master o join payment_mode m join patient_master p on o.payment_mode=m.id and p.id=o.p_id";
		
		$sql_where=" Where  (payment_mode>0 or running_opd=1) ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$data_show="";

		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
				
		$sql_where.=" and Date(o.apointment_date) between '".$minRange."' and '".$maxRange. "'";

		if($doc_name_id>0)
		{
			$sql_where.=" and o.doc_id='".$doc_name_id."'";
		}
		
		if($emp_name_id>0)
		{
			$sql_where.=" and o.prepared_by_id = '".$emp_name_id."'";
		}
		
		if($paymode_id>0)
		{
			$sql_where.=" and o.payment_mode = '".$paymode_id."'";
		}
		
		$sql_order=" Order by o.apointment_date";
		
		if($order_1==0)
		{
			$sql_order.=' ,o.opd_id';
		}

		if($order_1==1)
		{
			$sql_order.=' ,o.doc_name';
		}
		
		if($order_1==2)
		{
			$sql_order.=' ,o.prepared_by';
		}
		
		if($order_1==3)
		{
			$sql_order.=' ,m.mode_desc';
		}

		if($order_2==0)
		{
			$sql_order.=' ,o.opd_id';
		}
		
		if($order_2==1)
		{
			$sql_order.=' ,o.doc_name';
		}
		
		if($order_2==2)
		{
			$sql_order.=' ,o.prepared_by';
		}
		
		
		$table_start='<table border="1" cellpadding="2" cellspacing="2">';
		$table_end='</table>';
		
		if($group_by==0)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>OPD ID</th>';
			$table_head.='<th width="150" align="left">Patient Name / Age</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Fee Amt.</th></tr>';
			
			$GroupSQL="select Date(o.apointment_date) as sdate,Date_Format(Date(o.apointment_date),'%d-%m-%Y') as strDate
			from opd_master o   ".$sql_where."
			group by Date(o.apointment_date)";
			
			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			$index = 0;
			$data_show="";
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
								
				$data_show.='<b>OPD Date : </b>'.$row->strDate.'<br/>';
	
				$gwhere=" and Date(o.apointment_date)='".$row->sdate."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->opd_code.'</td>';
					$table_body.='<td>'.$rowdata->P_name.'/'.$rowdata->Age.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->PayMode.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->FeeAmt.'</td>';
					$table_body.='</tr>';
					$table_total_1=$table_total_1+$rowdata->FeeAmt;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$index++;
			}
		}
		
		if($group_by==1)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>OPD ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Date</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Fee Amt.</th></tr>';
			
					
			$GroupSQL="select o.doc_id,doc_name
			from opd_master  o ".$sql_where."	group by o.doc_id";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();	
			
			$index = 0;
			$data_show="";
			foreach($data['group_rec'] as $row)
			{ 
								
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Doctor Name : Dr.</b>'.$row->doc_name.'<br/>';
								
				$gwhere=" and doc_id='".$row->doc_id."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->opd_code.'</td>';
					$table_body.='<td>'.$rowdata->P_name.'/'.$rowdata->Age.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->App_Date.'</td>';
					$table_body.='<td>'.$rowdata->PayMode.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->FeeAmt.'</td>';
					$table_body.='</tr>';
					$table_total_1=$table_total_1+$rowdata->FeeAmt;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$index++;
				
			}
		}
		
		if($group_by==2)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>OPD ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>App. Date</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th width="50" align="right">Fee Amt.</th></tr>';
					
			$GroupSQL="select o.prepared_by
			from opd_master o   ".$sql_where."	group by o.prepared_by";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			$index = 0;
			$data_show="";
			foreach($data['group_rec'] as $row)
			{ 
				
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}

				$data_show.='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';
				
				$gwhere=" and o.prepared_by='".$row->prepared_by."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->opd_code.'</td>';
					$table_body.='<td>'.$rowdata->P_name.'/'.$rowdata->Age.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->App_Date.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->PayMode.'</td>';
					$table_body.='<td align="right">'.$rowdata->FeeAmt.'</td>';
					$table_body.='</tr>';
					$table_total_1=$table_total_1+$rowdata->FeeAmt;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$index++;

			}
		}
		
    
		if($group_by==3)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>OPD ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Date</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Fee Amt.</th></tr>';
					
			$GroupSQL="select o.payment_mode,m.mode_desc as 'PayMode'
			from opd_master o join payment_mode m on o.payment_mode=m.id 
			".$sql_where."	group by o.payment_mode";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			$index = 0;
			$data_show="";

			foreach($data['group_rec'] as $row)
			{ 
				
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}

				$data_show.='<b>Payment Mode : </b>'.$row->PayMode.'<br/>';
				
				$gwhere=" and o.payment_mode='".$row->payment_mode."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->opd_code.'</td>';
					$table_body.='<td>'.$rowdata->P_name.'/'.$rowdata->Age.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->App_Date.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->FeeAmt.'</td>';
					$table_body.='</tr>';
					$table_total_1=$table_total_1+$rowdata->FeeAmt;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$index++;

			}
		}

		$content=$data_show;
		$file_name="Report-OPD".date('Ymdhis').".pdf";
		

		if($output==0)
		{
			
			$this->load->library('m_pdf');
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			
			ExportExcel($content,$file_name);
		}


    
	}
	

	public function report_charge_app_show($opd_date_range,
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by,$output=0)
	{
		$sql_f_all = "select i.id,i.invoice_code,p.p_fname,p.p_code,i.refer_by_other,
		i.inv_date,Date_Format(i.inv_date,'%d-%m-%Y') as str_inv_date,i.invoice_status,
		if(i.insurance_id>1,'Org.','Direct') as PType,i.prepared_by,
		if(i.correction_amount>0,i.correction_net_amount,i.net_amount) as Amount,m.mode_desc ,
		group_concat(t.item_name) as test_list";
		
		$sql_from=" from invoice_master i join patient_master p join payment_mode m join invoice_item t
		on i.attach_id=p.id and i.attach_type=0 and i.payment_mode=m.id and i.id=t.inv_master_id";
		
		$sql_where=" Where i.invoice_status>0 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
		
		
		
		$sql_where.=" and Date(i.inv_date) between Date('".$minRange."') and Date('".$maxRange. "') ";

		if($doc_name_id>0)
		{
			$sql_where.=" and i.refer_by_id='".$doc_name_id."'";
		}
		
		if($emp_name_id>0)
		{
			$sql_where.=" and i.prepared_by like '%[".$emp_name_id."]' ";
		}
		
		if($paymode_id>0)
		{
			$sql_where.=" and i.payment_mode = '".$paymode_id."'";
		}
		
		$sql_order=" Order by i.inv_date";
		
		if($order_1==1)
		{
			$sql_order.=' ,i.refer_by_other';
		}
		
		if($order_1==2)
		{
			$sql_order.=' ,i.prepared_by';
		}
		
		if($order_1==3)
		{
			$sql_order.=' ,m.mode_desc';
		}
		
		if($order_2==1)
		{
			$sql_order.=' ,i.refer_by_other';
		}
		
		if($order_2==2)
		{
			$sql_order.=' ,i.prepared_by';
		}
		
			
		$table_start='<table border="1" cellpadding="2" cellspacing="2">';
		$table_end='</table>';
		
		if($group_by==0)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>Invoice ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Inv. Amt.</th></tr>';
			
			$GroupSQL="select Date(i.inv_date) as sdate,Date_Format(Date(i.inv_date),'%d-%m-%Y') as strDate
			from invoice_master i where i.invoice_status>0 and  Date(i.inv_date) between'".$minRange."' and '".$maxRange. "'
			group by Date(i.inv_date)";
			
			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Invoice Date : </b>'.$row->strDate.'<br/>';

				$gwhere=" and Date(i.inv_date)='".$row->sdate."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' group by i.id '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td colspan="1" rowspan="2" >'.$rowdata->invoice_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->refer_by_other.'</td>';
					$table_body.='<td>'.$rowdata->mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->Amount.'</td>';
					$table_body.='</tr>';
					$table_body.='<tr><td colspan="5" rowspan="1">'.$rowdata->test_list.'</td><td></td></tr>';
					$table_total_1=$table_total_1+$rowdata->Amount;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
		
			}
		}
		
		if($group_by==1)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>Invoice ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Amount</th></tr>';
			
			$GroupSQL="select i.refer_by_id,refer_by_other
			from invoice_master i where i.invoice_status>0 and  Date(i.inv_date) between'".$minRange."' and '".$maxRange. "'	group by i.refer_by_id";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Doctor Name : Dr.</b>'.$row->refer_by_other.'<br/>';

				$gwhere=" and i.refer_by_id='".$row->refer_by_id."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' group by i.id '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td colspan="1" rowspan="2">'.$rowdata->invoice_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->inv_date.'</td>';
					$table_body.='<td>'.$rowdata->mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->Amount.'</td>';
					$table_body.='</tr>';
					$table_body.='<tr><td colspan="5" rowspan="1">'.$rowdata->test_list.'</td><td></td></tr>';
					$table_total_1=$table_total_1+$rowdata->Amount;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
								
			}
		}
		
		if($group_by==2)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>Invoice ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Invoice Date</th>';
			$table_head.='<th>Doctor Name</th>';
			$table_head.='<th>Pay Mode</th>';
			$table_head.='<th width="50" align="right">Amount</th></tr>';
					
			$GroupSQL="select i.prepared_by
			from opd_master i where  i.invoice_status>0 and   Date(i.apointment_date) between'".$minRange."' and '".$maxRange. "'	group by i.prepared_by";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';

				$gwhere=" and i.prepared_by='".$row->prepared_by."'";

				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' group by i.id '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td colspan="1" rowspan="2" >'.$rowdata->invoice_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->inv_date.'</td>';
					$table_body.='<td>'.$rowdata->refer_by_other.'</td>';
					$table_body.='<td>'.$rowdata->mode_desc.'</td>';
					$table_body.='<td align="right">'.$rowdata->Amount.'</td>';
					$table_body.='</tr>';
					$table_body.='<tr><td colspan="5" rowspan="1">'.$rowdata->test_list.'</td><td></td></tr>';
					$table_total_1=$table_total_1+$rowdata->Amount;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
								
			}
		}
		
    
		if($group_by==3)
		{
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>Invoice ID</th>';
			$table_head.='<th width="150" align="left">Patient Name</th>';
			$table_head.='<th>Patient Code</th>';
			$table_head.='<th>Inv.Date</th>';
			$table_head.='<th>Ref. Doc. Name</th>';
			$table_head.='<th>Employee</th>';
			$table_head.='<th width="50" align="right">Amount</th></tr>';
			
			$GroupSQL="select i.payment_mode,m.mode_desc as 'PayMode'
			from invoice_master i join payment_mode m on i.payment_mode=m.id 
			where  i.invoice_status>0 and  Date(i.inv_date) between'".$minRange."' and '".$maxRange. "'	group by i.payment_mode";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Payment Mode : </b>'.$row->PayMode.'<br/>';

				$gwhere=" and i.payment_mode='".$row->payment_mode."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' group by i.id '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td colspan="1" rowspan="2" >'.$rowdata->invoice_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->p_code.'</td>';
					$table_body.='<td>'.$rowdata->inv_date.'</td>';
					$table_body.='<td>'.$rowdata->refer_by_other.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->Amount.'</td>';
					$table_body.='</tr>';
					$table_body.='<tr><td colspan="5" rowspan="1">'.$rowdata->test_list.'</td><td></td></tr>';
					$table_total_1=$table_total_1+$rowdata->Amount;
				}
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
								
				
				
			}
		}
		
		$content=$data_show;
		$file_name="Report-OPD-".date('Ymdhis').".pdf";

		
		if($output==0)
		{
			$this->load->library('m_pdf');
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");

		
		}else{
			
			ExportExcel($content,$file_name);
		}
		
	
	}
	
	
	public function report_ipdpayment_app_show($opd_date_range,
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by,$output=0)
	{
		$sql_f_all = "select i.id,i.ipd_code,i.ipd_status,m.p_fname,m.p_code,contact_person_Name,
						i.register_date,p.payment_date,p.id as pay_id,
						Date_format(p.payment_date,'%d-%m-%Y') as strPayDate,
						if(i.ipd_status=0,'Admit','Discharged') as IPD_Status,
						if(p.credit_debit=0,p.amount,0) as C_Amount,
						if(p.credit_debit=1,p.amount,0) as D_Amount,
						if(p.payment_mode=1,'CASH','BANK') AS payment_mode_desc,
						doc.doc_name,doc_list,p.update_by AS prepared_by,p.remark,
						if(i.case_id=0,'','INSURED') as insurance_type";
							
		$sql_from=" FROM ((payment_history p  join ipd_master i on  i.id=p.payof_id AND p.payof_type=4)
					join patient_master m   ON i.p_id=m.id)
					JOIN (select l.ipd_id,group_concat(d.p_fname) as doc_name,group_concat(d.id) as doc_list from ipd_master_doc_list l 
					join doctor_master d on l.doc_id=d.id group by l.ipd_id ) as doc
					on i.id=doc.ipd_id";
		
		$sql_where=" Where 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
		
		$sql_where.=" and Date(p.payment_date) between '".$minRange."' and '".$maxRange. "'";

		if($doc_name_id>0)
		{
			$sql_where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
		}
		
		if($emp_name_id>0)
		{
			$sql_where.=" and p.update_by_id in (".$emp_name_id.") ";
		}
		
		if($paymode_id>0)
		{
			$sql_where.=" and p.payment_mode = '".$paymode_id."'";
		}
		
		$sql_order=" Order by p.payment_date";
		
		if($order_1==1)
		{
			$sql_order.=' ,doc.doc_name';
		}
		
		if($order_1==2)
		{
			$sql_order.=' ,p.update_by';
		}
		
		if($order_1==3)
		{
			$sql_order.=' ,p.payment_mode';
		}
		
		if($order_2==1)
		{
			$sql_order.=' ,doc.doc_name';
		}
		
		if($order_2==2)
		{
			$sql_order.=' ,p.update_by';
		}
		
		
		$table_start='<table border="1" cellpadding="2" cellspacing="1">';
		$table_end='</table>';
		
		$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
		$table_head.='<th width="90">Pay ID </th>';
		$table_head.='<th width="90">IPD No </th>';
		$table_head.='<th width="90">Patient Name</th>';
		$table_head.='<th>Pay Date</th>';
		$table_head.='<th>Pay Mode</th>';
		$table_head.='<th>TPA/Direct</th>';
		$table_head.='<th width="70" align="right">Amount (Cr.)</th>';
		$table_head.='<th width="70" align="right">Amount (Dr.)</th>';
		$table_head.='<th  align="left">Remark</th>';
		$table_head.='<th  align="left">Dr.Name</th>';
		$table_head.='<th>Employee</th></tr>';

		$data_show="";
		
		if($group_by==-1)
		{
				$data_show.='<b>Payment Date between: </b>'.$minRange.' and '.$maxRange. '<br/>';

		
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->pay_id.'</td>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->insurance_type.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$TOTAL_AMT=$table_total_1-$table_Dr_total_1;
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="6"><b>Net Amt. (Cr-Dr) :</b> '.$TOTAL_AMT.'</td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td align="right"></td>';
				$table_footer.='<td ></td><td ></td></tr>';
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
		
				
		}



		if($group_by==0)
		{
			
			
			$GroupSQL="select Date(p.payment_date) as sdate,Date_Format(Date(p.payment_date),'%d-%m-%Y') as strDate
			From ((payment_history p  join ipd_master i on  i.id=p.payof_id AND p.payof_type=4)
 			join patient_master m   ON i.p_id=m.id) 
			where  Date(p.payment_date) between'".$minRange."' and '".$maxRange. "'
			group by Date(p.payment_date) ";
			
			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}

				$data_show.='<b>Payment Date : </b>'.$row->strDate.'<br/>';

				$gwhere=" and Date(p.payment_date)='".$row->sdate."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->pay_id.'</td>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->insurance_type.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="8"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
		
				
			}
		}
		
		if($group_by==1)
		{
			
			
			$GroupSQL="select doc.doc_name
					from ((payment_history p  join ipd_master i on  i.id=p.payof_id AND p.payof_type=4)
					join patient_master m   ON i.p_id=m.id)
					JOIN 
						(select l.ipd_id,group_concat(d.p_fname) as doc_name,group_concat(d.id) as doc_list from ipd_master_doc_list l 
						join doctor_master d on l.doc_id=d.id group by l.ipd_id ) as doc
						on i.id=doc.ipd_id
					where  Date(p.payment_date) between'".$minRange."' and '".$maxRange. "'	group by doc.doc_name";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				$index = 0;
				$data_show="";

				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Doctor Name : Dr.</b>'.$row->doc_name.'<br/>';

				$gwhere=" and doc.doc_name='".$row->doc_name."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->pay_id.'</td>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->insurance_type.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
					
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="8"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				
			}
		}
		
		if($group_by==2)
		{
			
			$GroupSQL="select p.update_by as prepared_by
					from ((payment_history p  join ipd_master i on  i.id=p.payof_id AND p.payof_type=4)
					join patient_master m   ON i.p_id=m.id)
					Where Date(p.payment_date) between'".$minRange."' and '".$maxRange. "'	group by p.prepared_by";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";
			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}
				
				$data_show.='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';

				$gwhere=" and p.update_by='".$row->prepared_by."'";

				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->pay_id.'</td>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->insurance_type.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="8"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
			}
		}
		
    
		if($group_by==3)
		{
	
			$GroupSQL="select p.payment_mode,p.payment_mode_desc
						from ((payment_history p  join ipd_master i on  i.id=p.payof_id AND p.payof_type=4)
						join patient_master m   ON i.p_id=m.id) 
						Where Date(p.payment_date) between'".$minRange."' and '".$maxRange. "'	group by p.payment_mode ";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();

			$index = 0;
			$data_show="";

			
			foreach($data['group_rec'] as $row)
			{ 
				if($index>0)
				{
					$data_show.="<pagebreak>";
				}

				$data_show.='<b>Payment Mode : </b>'.$row->payment_mode_desc.'<br/>';

				$gwhere=" and p.payment_mode='".$row->payment_mode."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->pay_id.'</td>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>'.$rowdata->p_fname.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td>'.$rowdata->insurance_type.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="8"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
			}
		}
		
		$content=$data_show;
		

		if($output==0)
		{
			
			$this->load->library('m_pdf');
		
			// $content;
			$file_name="Report-opd-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		
		}else{
			
			ExportExcel($content,'IPD_List_payment');
		}

	}
	
	public function report_total_payment_app_show($opd_date_range,$emp_name_id,$paymode_id,$order_1,$output=0)
	{
		$sql_f_all = "select p.id,p.payment_date,date_format(p.payment_date,'%d-%m-%Y %H:%m') as str_payment_date,
				case p.payof_type when 1 then 'OPD' 
				when 2 then 'Charges' when 3 then 'ORG' when 4 then 'IPD' else 'Other' end as PayType,
				payof_id,p.payof_code,
				if(credit_debit=0,amount,0) as Cr_Amount,
				if(credit_debit=1,amount,0) as Dr_Amount,
				p.remark,p.update_by,get_patientinfo(p.payof_type,payof_id) as P_Name,
				case p.payment_mode when 1 then 'Cash' when 2 then 'Bank' end as Pay_Mode";

		$sql_from=" from payment_history p ";
		
		$sql_where=" Where 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = str_replace('T',' ',$rangeArray[0]);
		$maxRange = str_replace('T',' ',$rangeArray[1]);
		
		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
		
		if($minRange==$maxRange)
		{
			$sql_where.=" and Date(p.payment_date) between Date('".$minRange."') and Date('".$maxRange. "')";
		}else{
			$sql_where.=" and p.payment_date between '".$minRange."' and '".$maxRange. "'";
		}
		
		$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
				
		if($emp_name_id<>0)
		{
			$emp_name_id=str_replace('S',',',$emp_name_id);
			
			$sql_where.=" and p.update_by_id  in (".$emp_name_id.")";
		}
		
		if($paymode_id<1)
		{
			$sql_where.=" and p.payment_mode in (1,2) ";
		}else{
			$sql_where.=" and p.payment_mode = '".$paymode_id."'";
		}

		$sql_order=" Order by p.id";
		
		if($order_1==1)
		{
			$sql_order.=' ,p.update_by';
		}
		
		if($order_1==2)
		{
			$sql_order.=' ,p.payment_mode';
		}
		
		
		$table_start='<table border="1" cellpadding="2" cellspacing="0" width="100%">';
		$table_end='</table>';
		
		$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
		$table_head.='<th  width="40">PayID </th>';
		$table_head.='<th >Date</th>';
		$table_head.='<th>Type of Payment</th>';
		$table_head.='<th width="100px">OPD/IPD/In. Code </th>';
		$table_head.='<th width="40">Pay Mode</th>';
		$table_head.='<th width="60" align="right">Amt (Cr.)</th>';
		$table_head.='<th width="60" align="right">Amt (Dr.)</th>';
		$table_head.='<th>Remark</th>';
		$table_head.='<th width="90px">Employee</th></tr>';
		
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				//echo $sql_f_all.$sql_from.$sql_where.'  '.$sql_order;
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				// $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->id.'</td>';
					$table_body.='<td>'.$rowdata->str_payment_date.'</td>';
					$table_body.='<td>'.$rowdata->PayType.'</td>';
					$table_body.='<td >'.$rowdata->payof_code.'<br>'.$rowdata->P_Name.'</td>';
					$table_body.='<td>'.$rowdata->Pay_Mode.'</td>';
					$table_body.='<td align="right">'.$rowdata->Cr_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->Dr_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->update_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->Cr_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->Dr_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td></td><td></td></tr>';
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$Total_Amount=$table_total_1-$table_Dr_total_1;
				
				$data_show.='<p>Total Amount : '.$Total_Amount.'</p>';
				
				if($output==0)
				{
					$content= $data_show;
					
					$this->load->library('m_pdf');
			
					$file_name="Report-".date('Ymdhis').".pdf";
			
					$filepath=$file_name;
			
					$this->m_pdf->pdf->WriteHTML($content);
			
					$this->m_pdf->pdf->Output($filepath,"I");

				}else{
					
					ExportExcel($data_show,'Payment');
				}
	}
	
	public function report_charge_type($opd_date_range,$output=0)
		{
			$sql_f_all = "select p.id,p.payment_date,date_format(p.payment_date,'%d-%m-%Y %H:%m') as str_payment_date,
					case p.payof_type when 1 then 'OPD' 
					when 2 then 'Charges' when 4 then 'IPD' else 'Other' end as PayType,
					payof_id,p.payof_code,
					if(credit_debit=0,amount,0) as Cr_Amount,
					if(credit_debit=1,amount,0) as Dr_Amount,
					p.remark,p.update_by,get_patientinfo(p.payof_type,payof_id) as P_Name,
					case p.payment_mode when 1 then 'Cash' when 2 then 'Bank' end as Pay_Mode";

			$sql_from=" from payment_history p ";
			
			$sql_where=" Where 1=1 ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if (!$this->ion_auth->in_group('ReportOLDREC')) {
				$date1=date_create($minRange);
				$date2=date_create(date('Y-m-d'));

				$diff=date_diff($date1,$date2);
				
				//echo $diff->format('%a');
				
				$int_diff=$diff->format('%a');
				
				if($int_diff>2)
				{
					date_sub($date2,date_interval_create_from_date_string("1 days"));
					// date_format($date2,"Y-m-d");
					$minRange=date_format($date2,"Y-m-d");;
				}
			}
			
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(p.payment_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and p.payment_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
			
			
			if($emp_name_id<>0)
			{
				$emp_name_id=str_replace('S',',',$emp_name_id);
				
				$sql_where.=" and p.update_by_id  in (".$emp_name_id.")";
			}
			
			if($paymode_id<1)
			{
				$sql_where.=" and p.payment_mode in (1,2) ";
			}else{
				$sql_where.=" and p.payment_mode = '".$paymode_id."'";
			}
			
			$sql_order=" Order by p.id";
			
			if($order_1==1)
			{
				$sql_order.=' ,p.update_by';
			}
			
			if($order_1==2)
			{
				$sql_order.=' ,p.payment_mode';
			}
			
			
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="880">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th  width="40">PayID </th>';
			$table_head.='<th >Date</th>';
			$table_head.='<th>Type of Payment</th>';
			$table_head.='<th width="100px">OPD/IPD/In. Code </th>';
			$table_head.='<th width="40">Pay Mode</th>';
			$table_head.='<th width="60" align="right">Amt (Cr.)</th>';
			$table_head.='<th width="60" align="right">Amt (Dr.)</th>';
			$table_head.='<th>Remark</th>';
			$table_head.='<th width="90px">Employee</th></tr>';
			
			// Add a page
			// This method has several options, check the source code documentation for more information.
					$pdf->AddPage();
					
					$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_order);
					$data['gsub_rec']= $query->result();
					
					//echo $sql_f_all.$sql_from.$sql_where.'  '.$sql_order;
					
					$table_body='';
					$table_total_1=0.00;
					$table_Dr_total_1=0.00;
					
					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					
					foreach($data['gsub_rec'] as $rowdata)
					{
						$table_body.='<tr>';
						$table_body.='<td>'.$rowdata->id.'</td>';
						$table_body.='<td>'.$rowdata->str_payment_date.'</td>';
						$table_body.='<td>'.$rowdata->PayType.'</td>';
						$table_body.='<td >'.$rowdata->payof_code.'<br>'.$rowdata->P_Name.'</td>';
						$table_body.='<td>'.$rowdata->Pay_Mode.'</td>';
						$table_body.='<td align="right">'.$rowdata->Cr_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->Dr_Amount.'</td>';
						$table_body.='<td>'.$rowdata->remark.'</td>';
						$table_body.='<td>'.$rowdata->update_by.'</td>';
						$table_body.='</tr>';
						
						$table_total_1=$table_total_1+$rowdata->Cr_Amount;
						$table_Dr_total_1=$table_Dr_total_1+$rowdata->Dr_Amount;
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td colspan="5"><b>Total</b></td>';
					$table_footer.='<td align="right">'.$table_total_1.'</td>';
					$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
					$table_footer.='<td ></td><td></td><td></td></tr>';
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					$Total_Amount=$table_total_1-$table_Dr_total_1;
					
					$data_show.='<p>Total Amount : '.$Total_Amount.'</p>';
					
					$content=$data_show;
					$this->load->library('m_pdf');
					
					

					if($output==0)
					{
						$file_name="Report-opd-".date('Ymdhis').".pdf";
						$filepath=$file_name;
						$this->m_pdf->pdf->WriteHTML($content);
						$this->m_pdf->pdf->Output($filepath,"I");
					
					}else{
						
						ExportExcel($content,'IPD_List_payment');
					}
		}
	

	public function report_emp_total($opd_date_range,$emp_name_id,$paymode_id,$order_1,$output=0)
		{
			$sql_f_all = "select p.update_by,
			sum(if(p.payof_type=1,if(p.credit_debit=0,p.amount,0),0)) as OPD_Amount,
			sum(if(p.payof_type=2,if(p.credit_debit=0,p.amount,0),0)) as Charge_Amount,
			sum(if(p.payof_type=4,if(p.credit_debit=0,p.amount,0),0)) as IPD_Amount,
			sum(if(p.payof_type=3,if(p.credit_debit=0,p.amount,0),0)) as ORG_Amount,
			sum(if(p.credit_debit=1,p.amount,0)) as Return_Amount,
			sum(if(p.credit_debit=0,p.amount,p.amount*-1)) as total_Amount	";

			$sql_from=" from payment_history p ";
			
			$sql_where=" Where 1=1 ";
			
			$sql_group_by=" group by p.update_by_id ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if (!$this->ion_auth->in_group('ReportOLDREC')) {
				$date1=date_create($minRange);
				$date2=date_create(date('Y-m-d'));

				$diff=date_diff($date1,$date2);
				
				//echo $diff->format('%a');
				
				$int_diff=$diff->format('%a');
				
				if($int_diff>2)
				{
					date_sub($date2,date_interval_create_from_date_string("1 days"));
					// date_format($date2,"Y-m-d");
					$minRange=date_format($date2,"Y-m-d");;
				}
			}
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(p.payment_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and p.payment_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
						
			if($emp_name_id<>0)
			{
				$emp_name_id=str_replace('S',',',$emp_name_id);
				
				$sql_where.=" and p.update_by_id  in (".$emp_name_id.")";
			}
			
			if($paymode_id<1)
			{
				$sql_where.=" and p.payment_mode in (1,2) ";
			}else{
				$sql_where.=" and p.payment_mode = '".$paymode_id."'";
			}
			
			$sql_order=" Order by p.update_by";
			
			if($order_1==1)
			{
				$sql_order.=' ,p.update_by';
			}
			
			if($order_1==2)
			{
				$sql_order.=' ,p.payment_mode';
			}
						
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="800">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>User ID</th>';
			$table_head.='<th align="center" width="100">OPD Amount</th>';
			$table_head.='<th align="center" width="100">Charge Amount</th>';
			$table_head.='<th align="center" width="100">IPD Amount</th>';
			$table_head.='<th align="center" width="100">ORG Amount</th>';
			$table_head.='<th align="center" width="100">Return Amount</th>';
			$table_head.='<th align="center" width="100">Total</th>';
			$table_head.='</tr>';
						
			$table_footer='';
			
					$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order);
					$data['gsub_rec']= $query->result();
										
					$table_body='';
					$table_total_opd=0.00;
					$table_total_ipd=0.00;
					$table_total_org=0.00;
					$table_total_charge=0.00;
					$table_total_return=0.00;
					$table_total_all=0.00;
					
					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					
					foreach($data['gsub_rec'] as $rowdata)
					{
						$table_body.='<tr>';
						$table_body.='<td>'.$rowdata->update_by.'</td>';
						$table_body.='<td align="right">'.$rowdata->OPD_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->Charge_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->IPD_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->ORG_Amount.'</td>';
						
						$table_body.='<td align="right">'.$rowdata->Return_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->total_Amount.'</td>';
						$table_body.='</tr>';
						
						
						$table_total_opd=$table_total_opd+$rowdata->OPD_Amount;
						$table_total_ipd=$table_total_ipd+$rowdata->IPD_Amount;
						$table_total_org=$table_total_org+$rowdata->ORG_Amount;
						$table_total_charge=$table_total_charge+$rowdata->Charge_Amount;
						$table_total_return=$table_total_return+$rowdata->Return_Amount;
						$table_total_all=$table_total_all+$rowdata->total_Amount;
						
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right">'.$table_total_opd.'</td>';
					$table_footer.='<td align="right">'.$table_total_charge.'</td>';
					$table_footer.='<td align="right">'.$table_total_ipd.'</td>';
					$table_footer.='<td align="right">'.$table_total_org.'</td>';
					$table_footer.='<td align="right">'.$table_total_return.'</td>';
					$table_footer.='<td align="right">'.$table_total_all.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					if($output==0)
					{
						$content=$data_show;
						$this->load->library('m_pdf');
						
						$file_name="Report-opd-".date('Ymdhis').".pdf";
						$filepath=$file_name;
						$this->m_pdf->pdf->WriteHTML($content);
						$this->m_pdf->pdf->Output($filepath,"I");
					}else{
						
						ExportExcel($data_show,'Payment_Complie');
					}
		}
	
		public function report_emp_total_daywise($opd_date_range,$emp_name_id,$paymode_id,$order_1,$output=0)
		{
			$sql_f_all = "select p.update_by_id,date_format(p.payment_date,'%d-%m-%Y') as g_payment_date,p.update_by,
			sum(if(p.payof_type=1,if(p.credit_debit=0,p.amount,0),0)) as OPD_Amount,
			sum(if(p.payof_type=2,if(p.credit_debit=0,p.amount,0),0)) as Charge_Amount,
			sum(if(p.payof_type=4,if(p.credit_debit=0,p.amount,0),0)) as IPD_Amount,
			sum(if(p.payof_type=3,if(p.credit_debit=0,p.amount,0),0)) as ORG_Amount,
			sum(if(p.credit_debit=1,p.amount,0)) as Return_Amount,
			sum(if(p.credit_debit=0,p.amount,p.amount*-1)) as total_Amount	";

			$sql_from=" from payment_history p ";
			
			$sql_where=" Where 1=1 ";
			
			$sql_group_by=" group by date(p.payment_date), p.update_by_id ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if (!$this->ion_auth->in_group('ReportOLDREC')) {
				$date1=date_create($minRange);
				$date2=date_create(date('Y-m-d'));

				$diff=date_diff($date1,$date2);
				
				//echo $diff->format('%a');
				
				$int_diff=$diff->format('%a');
				
				if($int_diff>2)
				{
					date_sub($date2,date_interval_create_from_date_string("1 days"));
					// date_format($date2,"Y-m-d");
					$minRange=date_format($date2,"Y-m-d");;
				}
			}
			
			$sql_where.=" and Date(p.payment_date) between Date('".$minRange."') and Date('".$maxRange. "')";
				
						
			if($emp_name_id<>0)
			{
				$emp_name_id=str_replace('S',',',$emp_name_id);
				
				$sql_where.=" and p.update_by_id  in (".$emp_name_id.")";
			}
			
			if($paymode_id<1)
			{
				$sql_where.=" and p.payment_mode in (1,2) ";
			}else{
				$sql_where.=" and p.payment_mode = '".$paymode_id."'";
			}
			
			$sql_order=" Order by date(p.payment_date)";
			
						
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="800">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th>User ID</th>';
			$table_head.='<th align="center" width="100">OPD Amount</th>';
			$table_head.='<th align="center" width="100">Charge Amount</th>';
			$table_head.='<th align="center" width="100">IPD Amount</th>';
			$table_head.='<th align="center" width="100">ORG Amount</th>';
			$table_head.='<th align="center" width="100">Return Amount</th>';
			$table_head.='<th align="center" width="100">Total</th>';
			$table_head.='</tr>';

			$table_footer='';
			
			$sql=$sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.' WITH rollup';

			$query = $this->db->query($sql);
			$data['gsub_rec']= $query->result();
									
			$table_body='';
			$data_show='';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					if($rowdata->g_payment_date=='' && $rowdata->update_by_id==''){
						
						$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
						$table_footer.='<td ><b>Grand Total</b></td>';
						$table_footer.='<td align="right">'.$rowdata->OPD_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->Charge_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->IPD_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->ORG_Amount.'</td>';
						
						$table_footer.='<td align="right">'.$rowdata->Return_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->total_Amount.'</td>';
						$table_footer.='</tr>';
						
						$data_show.='<p>Date(YYYY-MM-DD h:m)  '.$rowdata->g_payment_date.'</p>';
						$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;

					}else if($rowdata->update_by_id=='')
					{

						$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
						$table_footer.='<td ><b>Total</b></td>';
						$table_footer.='<td align="right">'.$rowdata->OPD_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->Charge_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->IPD_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->ORG_Amount.'</td>';
						
						$table_footer.='<td align="right">'.$rowdata->Return_Amount.'</td>';
						$table_footer.='<td align="right">'.$rowdata->total_Amount.'</td>';
						$table_footer.='</tr>';
						
						$data_show.='<p>Date(YYYY-MM-DD h:m)  '.$rowdata->g_payment_date.'</p>';
						$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
						$data_show.='<page_break>';
						$table_body='';
						$table_footer='';
						
					}else{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->update_by.'</td>';
					$table_body.='<td align="right">'.$rowdata->OPD_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->Charge_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->IPD_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->ORG_Amount.'</td>';
					
					$table_body.='<td align="right">'.$rowdata->Return_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->total_Amount.'</td>';
					$table_body.='</tr>';
			
					}
				}
				
				$content=$data_show;
					if($output==0)
					{
						
						$this->load->library('m_pdf');
						
						$file_name="Report-opd-".date('Ymdhis').".pdf";
						$filepath=$file_name;
						$this->m_pdf->pdf->WriteHTML($content);
						$this->m_pdf->pdf->Output($filepath,"I");
						//echo $content;
					}else{
						
						ExportExcel($content,'Payment_Complie');
					}
		}
	

	public function report_opd_page()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/OPD_Report_Doctor',$data);
	}
	
	public function report_opd($opd_date_range,$doc_name_id,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
		
		$sql_where.=" and o.apointment_date between '".$minRange."' and '".$maxRange. "'";
		
		if($doc_name_id>0)
		{
			$sql_where.=" and o.doc_id='".$doc_name_id."'";
		}
		
		
		$sql="select o.doc_id,o.doc_name,o.apointment_date,count(o.opd_id) as no_opd,
			sum(o.opd_fee_amount) as OPD_Amount,

			sum(if(o.payment_mode in (1,2),'1',0)) as Cash_no,
			sum(if(o.payment_mode in (1,2),o.opd_fee_amount,0)) as Cash_Amount,

			sum(if(o.payment_mode=4,'1',0)) as Org_Credit_no,
			sum(if(o.payment_mode=4,o.opd_fee_amount,0)) as Org_Credit_Amount
			
			from opd_master o  
			where ".$sql_where." and o.payment_status=1  AND o.opd_status IN (1,2)
			group by o.doc_id,o.apointment_date with ROLLUP";
		
		$query = $this->db->query($sql);
        $opdlist= $query->result();
		
		$srno=1;
		
		$tableHead='<table border="1" cellpadding="2" cellspacing="0" >
						<thead>
							<tr>
								<th align="center" width="120px">Doctor Name</th>
								<th align="center">Date</th>
								<th align="center" width="50px">Total</th>
								<th align="center">Total.Amt.</th>
								<th align="center">No.Cash OPD</th>
								<th align="center">Cash Amt.</th>
								<th align="center">Org.OPD Credit</th>
								<th align="center">Org.Cr.Amt.</th>
								<th align="center">Net OPD</th>
								<th align="center">Net Amount</th>
							</tr>
						</thead>';
						
		$line='<tbody>';
		$tablefooter='</tbody></table>';
		$FooterHead='';
		foreach($opdlist as $row)
		{ 
				
			if($row->doc_id=='')
			{
				
			}elseif($row->apointment_date=='')
			{
				$line.='<tr style="background-color:yellow;">';
				$line.='<th colspan="2">Total of Dr.'.$row->doc_name.'</th>'.PHP_EOL;
				$line.='<th align="right" width="50px">'.$row->no_opd.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->OPD_Amount.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->Cash_no.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->Cash_Amount.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->Org_Credit_no.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->Org_Credit_Amount.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->no_opd.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->OPD_Amount.'</th>'.PHP_EOL;
				
				$line.="</tr>";
				
				$FooterHead.='<h2>Doctor :Dr. '.$row->doc_name.' / Total Net OPD : '.$row->no_opd.' / Total Amount : Rs.'.number_format($row->OPD_Amount,2).' </h2>';
				
			
			}else
			{
				$line.='<tr>';
				$line.='<td width="120px">'.$row->doc_name.'</td>'.PHP_EOL;
				$line.='<td>'.$row->apointment_date.'</td>'.PHP_EOL;
				$line.='<td align="right" width="50px">'.$row->no_opd.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->OPD_Amount.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->Cash_no.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->Cash_Amount.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->Org_Credit_no.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->Org_Credit_Amount.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->no_opd.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->OPD_Amount.'</td>'.PHP_EOL;
				
				$line.="</tr>";
			}
		}
	
		$print_data= $tableHead.$line.$tablefooter.'<br>'.$FooterHead;
		$content=$print_data;

		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-opd-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			
			ExportExcel($content,'OPDList');
		}
		
	}

	function report_opd_total()
	{
		
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode ";
        $query = $this->db->query($sql);
        $data['paymodelist']= $query->result();
		
		$this->load->view('Report/opd_report_total',$data);
	}
	

	function opd_total_data($opd_date_range,$doc_name_id,$emp_name_id,$paymode_id,$output=0)
	{
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where =" 1=1 " ;
		
		$sql_where.=" and o.apointment_date between '".$minRange."' and '".$maxRange. "'";

		if($doc_name_id>0)
		{
			$sql_where.=" and o.doc_id='".$doc_name_id."'";
		}
		
		if($emp_name_id>0)
		{
			$sql_where.=" and o.prepared_by like '%[".$emp_name_id."]'";
		}
		
		if($paymode_id>0)
		{
			$sql_where.=" and o.payment_mode = '".$paymode_id."'";
		}
			
		$sql="SELECT o.doc_name,
				o.opd_fee_desc,o.opd_fee_amount,
				sum(if(o.opd_status IN (1,2),1,0)) AS No_opd,
				sum(if(o.opd_status IN (1,2),o.opd_fee_amount,0)) AS T_Amt
				FROM opd_master o 
				where ".$sql_where." 
				AND o.opd_status IN (1,2) and
				o.payment_status=1 AND (payment_mode>0 or running_opd=1)
				GROUP BY o.opd_fee_id,o.opd_fee_type,o.opd_fee_amount";
		
		//echo $sql;
		
		$query = $this->db->query($sql);
        $OPD_list= $query->result();

		$sql="SELECT o.doc_name,
				o.opd_fee_desc,o.opd_fee_amount,
				sum(if(o.opd_status IN (3),1,0)) AS No_opd,
				sum(if(o.opd_status IN (3),o.opd_fee_amount,0)) AS T_Amt
				FROM opd_master o 
				where ".$sql_where." 
				AND o.opd_status IN (3) and
				o.payment_status=1 AND (payment_mode>0 or running_opd=1)
				GROUP BY o.opd_fee_id,o.opd_fee_type,o.opd_fee_amount";
		
		//echo $sql;
		
		$query = $this->db->query($sql);
        $OPD_list_cancel= $query->result();
				
		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr  >
					<th width="50px">#</th>
					<th>Dr Name</th>
					<th>OPD Type</th>
					<th align="right">OPD Fee</th>
					<th align="right">No. of OPD</th>
					<th align="right">Tot.</th>
				 </tr>';
		$sr_no=0;
		$total_Payment=0;
		$total_opd=0;

		foreach($OPD_list as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td>Dr. '.$row->doc_name.'</td>
							<td>'.$row->opd_fee_desc.'</td>
							<td align="right">'.$row->opd_fee_amount.'</td>
							<td align="right">'.$row->No_opd.'</td>
							<td align="right">'.$row->T_Amt.'</td>
					 	</tr>';
			$total_Payment+= $row->T_Amt;
			$total_opd+= $row->No_opd;
		}
				
				$content.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td>Total</td>
							<td align="right">'.$total_opd.'</td>
							<td align="right">'.$total_Payment.'</td>
					 	</tr>';
	
		$content.="</table>";

		//Cancel OPD

		$content_cancel='
			<h3>Cancel OPD</h3>	
			<table border="1" width="100%" cellpadding="3">';

		$content_cancel.='<tr  >
					<th width="50px">#</th>
					<th>Dr Name</th>
					<th>OPD Type</th>
					<th align="right">OPD Fee</th>
					<th align="right">No. of OPD</th>
					<th align="right">Tot.</th>
				 </tr>';
		$sr_no=0;
		$total_Payment=0;
		$total_opd=0;

		foreach($OPD_list_cancel as $row)
		{
			$sr_no=$sr_no+1;
			$content_cancel.='<tr>
							<td>'.$sr_no.'</td>
							<td>Dr. '.$row->doc_name.'</td>
							<td>'.$row->opd_fee_desc.'</td>
							<td align="right">'.$row->opd_fee_amount.'</td>
							<td align="right">'.$row->No_opd.'</td>
							<td align="right">'.$row->T_Amt.'</td>
					 	</tr>';
			$total_Payment+= $row->T_Amt;
			$total_opd+= $row->No_opd;
		}
				
				$content_cancel.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td>Total</td>
							<td align="right">'.$total_opd.'</td>
							<td align="right">'.$total_Payment.'</td>
					 	</tr>';
	
		$content_cancel.="</table>";

		if(count($OPD_list_cancel)>0){
			$content.=$content_cancel;
		}

		if($output==0)
		{
			$this->load->library('m_pdf');
			
			$file_name='Report-OPD-'.date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'OPD_List'.date('Yms'));
		}
	
	}

	public function report_totalpayment_bank_app()
	{
		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="SELECT b.id,b.bank_name,a.pay_type,a.id AS ac_id
				FROM hospital_bank b JOIN hospital_bank_payment_source a ON b.id=a.bank_id";
        $query = $this->db->query($sql);
        $data['hospital_bank_payment_source']= $query->result();
		
		$this->load->view('Report/rep_total_bank_payment',$data);
	}


	public function report_total_payment_bank_show($opd_date_range,$emp_name_id,$bank_id,$output=0)
	{
		$sql_f_all = "Select p.id,p.payment_date,date_format(p.payment_date,'%d-%m-%Y %H:%m') as str_payment_date,
		case p.payof_type when 1 then 'OPD' 
		when 2 then 'Charges' when 3 then 'ORG' when 4 then 'IPD' else 'Other' end as PayType,
		payof_id,p.payof_code,
		if(credit_debit=0,amount,0) as Cr_Amount,
		if(credit_debit=1,amount,0) as Dr_Amount,
		p.remark,p.update_by,get_patientinfo(p.payof_type,payof_id) as P_Name,
		case p.payment_mode when 1 then 'Cash' when 2 then 'Bank' end as Pay_Mode,
		CONCAT(h.bank_name,':',b.pay_type) AS bank_pay_source";

		$sql_from=" FROM (payment_history p LEFT JOIN hospital_bank_payment_source b ON p.pay_bank_id=b.id)
		JOIN hospital_bank h ON b.bank_id=h.id ";
		
		$sql_where=" Where 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = str_replace('T',' ',$rangeArray[0]);
		$maxRange = str_replace('T',' ',$rangeArray[1]);
		
		if (!$this->ion_auth->in_group('ReportOLDREC')) {
			$date1=date_create($minRange);
			$date2=date_create(date('Y-m-d'));

			$diff=date_diff($date1,$date2);
			
			//echo $diff->format('%a');
			
			$int_diff=$diff->format('%a');
			
			if($int_diff>2)
			{
				date_sub($date2,date_interval_create_from_date_string("1 days"));
				// date_format($date2,"Y-m-d");
				$minRange=date_format($date2,"Y-m-d");;
			}
		}
		
		if($minRange==$maxRange)
		{
			$sql_where.=" and Date(p.payment_date) between Date('".$minRange."') and Date('".$maxRange. "')";
		}else{
			$sql_where.=" and p.payment_date between '".$minRange."' and '".$maxRange. "'";
		}
		
		$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
				
		if($emp_name_id<>0)
		{
			$emp_name_id=str_replace('S',',',$emp_name_id);
			
			$sql_where.=" and p.update_by_id  in (".$emp_name_id.")";
		}
		
		if($bank_id<1)
		{
			$sql_where.=" and p.payment_mode =2 ";
		}else{
			$sql_where.=" and p.payment_mode =2 and p.pay_bank_id = '".$paymode_id."'";
		}

		$sql_order=" Order by p.id";
		
		
		$table_start='<table border="1" cellpadding="2" cellspacing="0" width="100%">';
		$table_end='</table>';
		
		$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
		$table_head.='<th  width="40">PayID </th>';
		$table_head.='<th >Date</th>';
		$table_head.='<th>Bank</th>';
		$table_head.='<th width="100px">OPD/IPD/In. Code </th>';
		$table_head.='<th width="40">Bank Source</th>';
		$table_head.='<th width="60" align="right">Amt (Cr.)</th>';
		$table_head.='<th width="60" align="right">Amt (Dr.)</th>';
		$table_head.='<th>Remark</th>';
		$table_head.='<th width="90px">Employee</th></tr>';
		
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$sql= $sql_f_all.$sql_from.$sql_where.'  '.$sql_order;
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				// $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->id.'</td>';
					$table_body.='<td>'.$rowdata->str_payment_date.'</td>';
					$table_body.='<td>'.$rowdata->PayType.'</td>';
					$table_body.='<td >'.$rowdata->payof_code.'<br>'.$rowdata->P_Name.'</td>';
					$table_body.='<td>'.$rowdata->bank_pay_source.'</td>';
					$table_body.='<td align="right">'.$rowdata->Cr_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->Dr_Amount.'</td>';
					$table_body.='<td>'.$rowdata->remark.'</td>';
					$table_body.='<td>'.$rowdata->update_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->Cr_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->Dr_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td></td><td></td></tr>';
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				$Total_Amount=$table_total_1-$table_Dr_total_1;
				
				$data_show.='<p>Total Amount : '.$Total_Amount.'</p>';

				
				
				if($output==0)
				{
					$content= $data_show;
					
					$this->load->library('m_pdf');
			
					$file_name="Report-".date('Ymdhis').".pdf";
			
					$filepath=$file_name;
			
					$this->m_pdf->pdf->WriteHTML($content);
			
					$this->m_pdf->pdf->Output($filepath,"I");

				}else{
					
					ExportExcel($data_show.$sql,'Payment');
				}
	}



}

	
  
/* End of file c_test.php */
/* Location: ./application/controllers/c_test.php */