<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
        $this->load->library("Pdf");
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

	//echo $table_show;
    // create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
  
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('IHC:D.S.Bisht');
    $pdf->SetTitle('IHC:OPD List');
    $pdf->SetSubject('IHC:OPD List');
    $pdf->SetKeywords('IHC:OPD List, PDF, OPD, List, guide');   
  
    // set default header data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
    $pdf->setFooterData(array(0,64,0), array(0,64,128)); 
  
    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
  
    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); 
  
    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);    
  
    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 
  
    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  
  
    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }   
  
    // ---------------------------------------------------------    
  
    // set default font subsetting mode
    $pdf->setFontSubsetting(true);   
  
    // Set font
    // dejavusans is a UTF-8 Unicode font, if you only need to
    // print standard ASCII chars, you can use core fonts like
    // helvetica or times to reduce file size.
    $pdf->SetFont('dejavusans', '', 14, '', true);   
  
    // Add a page
    // This method has several options, check the source code documentation for more information.
    $pdf->AddPage(); 
  
    // set text shadow effect
    $pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));    
  
	$test= '<h1>Report Sample Page TEsted</h1>';
    // Set some content to print
    $html = <<<EOD
	$table_show 
     
EOD;
  
    // Print text using writeHTMLCell()
    $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
    // ---------------------------------------------------------    
  
    // Close and output PDF document
    // This method has several options, check the source code documentation for more information.
    $pdf->Output('OPD_001.pdf', 'I');    
  
    //============================================================+
    // END OF FILE
    //============================================================+
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
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by)
	{
		$sql_f_all = "select o.opd_code,
		o.P_name ,p_code ,Date_Format(o.apointment_date,'%d-%m-%Y') as 'App_Date',
		o.doc_name ,if(o.insurance_id>1,'Org.','Direct') as 'Type',
		m.mode_desc as 'PayMode',o.opd_fee_gross_amount as 'GrossAmt',
		o.opd_discount as Discount,	o.opd_fee_amount as 'FeeAmt',
		o.prepared_by,GET_AGE_1(dob,age,age_in_month,estimate_dob)  as Age ";
		
		$sql_from=" from opd_master o join payment_mode m join patient_master p on o.payment_mode=m.id and p.id=o.p_id";
		
		$sql_where=" Where 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
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
		
		$sql_order=" Order by o.apointment_date";
		
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
		
		if($order_2==1)
		{
			$sql_order.=' ,o.doc_name';
		}
		
		if($order_2==2)
		{
			$sql_order.=' ,o.prepared_by';
		}
		
		//echo $table_show;
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
	  
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('IHC:D.S.Bisht');
		$pdf->SetTitle('IHC:OPD List');
		$pdf->SetSubject('IHC:OPD List');
		$pdf->SetKeywords('IHC:OPD List, PDF, OPD, List, guide');   
	  
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
	  
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
	  
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); 
	  
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);    
	  
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 
	  
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  
	  
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	  
		// ---------------------------------------------------------    
	  
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	  
		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 8, '', true);   
	  
		// Add a page
		// This method has several options, check the source code documentation for more information.
		
	  		// set text shadow effect
		//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));    
		
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
			from opd_master o where Date(o.apointment_date) between '".$minRange."' and '".$maxRange. "'
			group by Date(o.apointment_date)";
			
			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>OPD Date : </b>'.$row->strDate.'<br/>';
								
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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
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
			from opd_master o where Date(o.apointment_date) between '".$minRange."' and '".$maxRange. "'	group by o.doc_id";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();	
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Doctor Name : Dr.</b>'.$row->doc_name.'<br/>';
								
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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
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
			from opd_master o where Date(o.apointment_date) between'".$minRange."' and '".$maxRange. "'	group by o.prepared_by";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';
								
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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
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
			from opd_master o join payment_mode m on o.payment_mode=m.id where Date(o.apointment_date) between'".$minRange."' and '".$maxRange. "'	group by o.payment_mode";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Payment Mode : </b>'.$row->PayMode.'<br/>';
								
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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
    // ---------------------------------------------------------    
  
    // Close and output PDF document
    // This method has several options, check the source code documentation for more information.
    // $pdf->Output('OPD_001.pdf', 'I');  
	$folder_name='uploads/'.date('Ymd');
				
	if (!file_exists($folder_name)) {
		mkdir($folder_name, 0777, true);
		chmod($folder_name, 0777);
	}
	
	$filename="OPD_0001".date('Ymdhis').".pdf";
	$filepath=$folder_name.'/'.$filename;
	
		
		$pdf->Output($filepath, 'F');    
		$pdf->Output($filepath);	
  
    //============================================================+
    // END OF FILE
    //============================================================+
	}
	

	public function report_charge_app_show($opd_date_range,
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by)
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
		
		//echo $table_show;
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
	  
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('IHC:D.S.Bisht');
		$pdf->SetTitle('IHC:OPD List');
		$pdf->SetSubject('IHC:OPD List');
		$pdf->SetKeywords('IHC:OPD List, PDF, OPD, List, guide');   
	  
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
	  
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
	  
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); 
	  
		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);    
	  
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 
	  
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  
	  
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	  
		// ---------------------------------------------------------    
	  
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	  
		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 8, '', true);   
	  
		// Add a page
		// This method has several options, check the source code documentation for more information.
		
	  		// set text shadow effect
		//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));    
		
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
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Invoice Date : </b>'.$row->strDate.'<br/>';

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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
		
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
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Doctor Name : Dr.</b>'.$row->refer_by_other.'<br/>';

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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
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
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';

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
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
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
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Payment Mode : </b>'.$row->PayMode.'<br/>';

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
								
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
    
		
	// ---------------------------------------------------------    
  
    // Close and output PDF document
    // This method has several options, check the source code documentation for more information.
    $pdf->Output('INV_001.pdf', 'I');    
  
    //============================================================+
    // END OF FILE
    //============================================================+
	}
	
	
	public function report_ipdpayment_app_show($opd_date_range,
	$doc_name_id,$emp_name_id,$paymode_id,$order_1,$order_2,$group_by)
	{
		$sql_f_all = "select i.id,i.ipd_code,i.ipd_status,m.p_fname,m.p_code,contact_person_Name,
				i.register_date,p.pay_date,p.enter_pay_date,p.payment_id,
				if(Date(p.pay_date)=date(p.enter_pay_date),p.pay_date,Concat(p.pay_date,'<BR>',p.enter_pay_date)) as strPayDate,
				if(i.ipd_status=0,'Admit','Discharged') as IPD_Status,
				if(credit_debit=0,p.amount,0) as C_Amount,
				if(credit_debit=1,p.amount,0) as D_Amount,p.payment_mode_desc,
				doc.doc_name,doc_list,p.prepared_by ";
							
		$sql_from=" from ipd_payment p  join ipd_master i join patient_master m join 
			(select l.ipd_id,group_concat(d.p_fname) as doc_name,group_concat(d.id) as doc_list from ipd_master_doc_list l 
			join doctor_master d on l.doc_id=d.id group by l.ipd_id ) as doc
			on i.id=p.ipd_id and i.p_id=m.id and i.id=doc.ipd_id";
		
		$sql_where=" Where 1=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		$sql_where.=" and Date(p.pay_date) between '".$minRange."' and '".$maxRange. "'";

		if($doc_name_id>0)
		{
			$sql_where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
		}
		
		if($emp_name_id>0)
		{
			$sql_where.=" and p.prepared_by like '%[".$emp_name_id."]' ";
		}
		
		if($paymode_id>0)
		{
			$sql_where.=" and p.payment_mode = '".$paymode_id."'";
		}
		
		$sql_order=" Order by p.pay_date";
		
		if($order_1==1)
		{
			$sql_order.=' ,doc.doc_name';
		}
		
		if($order_1==2)
		{
			$sql_order.=' ,p.prepared_by';
		}
		
		if($order_1==3)
		{
			$sql_order.=' ,p.payment_mode_desc';
		}
		
		if($order_2==1)
		{
			$sql_order.=' ,doc.doc_name';
		}
		
		if($order_2==2)
		{
			$sql_order.=' ,p.prepared_by';
		}
		
		//echo $table_show;
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
	  
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('IHC:D.S.Bisht');
		$pdf->SetTitle('IHC:OPD List');
		$pdf->SetSubject('IHC:OPD List');
		$pdf->SetKeywords('IHC:OPD List, PDF, OPD, List, guide');   
	  
		// set default header data
		$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
		$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
	  
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
	  
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); 
	  
		// set margins
		//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetMargins(5, PDF_MARGIN_TOP, 0.25);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);    
	  
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 
	  
		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  
	  
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	  
		// ---------------------------------------------------------    
	  
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	  
		// Set font
		// dejavusans is a UTF-8 Unicode font, if you only need to
		// print standard ASCII chars, you can use core fonts like
		// helvetica or times to reduce file size.
		$pdf->SetFont('dejavusans', '', 8, '', true);   
	  
		// Add a page
		// This method has several options, check the source code documentation for more information.
		
	  		// set text shadow effect
		//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));    
		
		$table_start='<table border="1" cellpadding="2" cellspacing="2">';
		$table_end='</table>';
		
		$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
		$table_head.='<th width="90">IPD No </th>';
		$table_head.='<th width="90">Patient Name / Patient Code</th>';
		$table_head.='<th>Doctor Name</th>';
		$table_head.='<th>Pay Date</th>';
		$table_head.='<th>Pay Mode</th>';
		$table_head.='<th width="70" align="right">Amount (Cr.)</th>';
		$table_head.='<th width="70" align="right">Amount (Dr.)</th>';
		$table_head.='<th>Employee</th></tr>';
		
		if($group_by==0)
		{
			
			
			$GroupSQL="select Date(p.pay_date) as sdate,Date_Format(Date(p.pay_date),'%d-%m-%Y') as strDate
			from ipd_payment p  join ipd_master i join patient_master m  on i.id=p.ipd_id and i.p_id=m.id 
			where  Date(p.pay_date) between'".$minRange."' and '".$maxRange. "'
			group by Date(p.pay_date) ";
			
			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Payment Date : </b>'.$row->strDate.'<br/>';

				$gwhere=" and Date(p.pay_date)='".$row->sdate."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>['.$rowdata->p_code.']<br><b>'.$rowdata->p_fname.'</b><br>{'.$rowdata->contact_person_Name.'}</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
		if($group_by==1)
		{
			
			
			$GroupSQL="select doc.doc_name
					from ipd_payment p  join ipd_master i join patient_master m join 
					(select l.ipd_id,group_concat(d.p_fname) as doc_name,group_concat(d.id) as doc_list from ipd_master_doc_list l 
					join doctor_master d on l.doc_id=d.id group by l.ipd_id ) as doc
					on i.id=p.ipd_id and i.p_id=m.id and i.id=doc.ipd_id
					where  Date(p.pay_date) between'".$minRange."' and '".$maxRange. "'	group by doc.doc_name";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Doctor Name : Dr.</b>'.$row->doc_name.'<br/>';

				$gwhere=" and doc.doc_name='".$row->doc_name."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>['.$rowdata->p_code.']<br><b>'.$rowdata->p_fname.'</b><br>{'.$rowdata->contact_person_Name.'}</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
					
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
		if($group_by==2)
		{
			
					
			$GroupSQL="select p.prepared_by
					from ipd_payment p  join ipd_master i join patient_master m  
					on i.id=p.ipd_id and i.p_id=m.id
					Where Date(p.pay_date) between'".$minRange."' and '".$maxRange. "'	group by p.prepared_by";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Prepared By : </b>'.$row->prepared_by.'<br/>';

				$gwhere=" and p.prepared_by='".$row->prepared_by."'";

				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order);
				$data['gsub_rec']= $query->result();

				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>['.$rowdata->p_code.']<br><b>'.$rowdata->p_fname.'</b><br>{'.$rowdata->contact_person_Name.'}</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
    
		if($group_by==3)
		{
	
			$GroupSQL="select p.payment_mode,p.payment_mode_desc
						from ipd_payment p  join ipd_master i join patient_master m  
						on i.id=p.ipd_id and i.p_id=m.id 
						Where Date(p.pay_date) between'".$minRange."' and '".$maxRange. "'	group by p.payment_mode ";

			$query = $this->db->query($GroupSQL);
			$data['group_rec']= $query->result();
			
			foreach($data['group_rec'] as $row)
			{ 
				// Add a page
				// This method has several options, check the source code documentation for more information.
				$pdf->AddPage();
				
				$data_show='<b>Payment Mode : </b>'.$row->payment_mode_desc.'<br/>';

				$gwhere=" and p.payment_mode='".$row->payment_mode."'";
				
				$query = $this->db->query($sql_f_all.$sql_from.$sql_where.$gwhere.' '.$sql_order);
				$data['gsub_rec']= $query->result();
				
				$table_body='';
				$table_total_1=0.00;
				$table_Dr_total_1=0.00;
				
				
				foreach($data['gsub_rec'] as $rowdata)
				{
					$table_body.='<tr>';
					$table_body.='<td>'.$rowdata->ipd_code.'</td>';
					$table_body.='<td>['.$rowdata->p_code.']<br><b>'.$rowdata->p_fname.'</b><br>{'.$rowdata->contact_person_Name.'}</td>';
					$table_body.='<td>'.$rowdata->doc_name.'</td>';
					$table_body.='<td>'.$rowdata->strPayDate.'</td>';
					$table_body.='<td>'.$rowdata->payment_mode_desc.'</td>';
					$table_body.='<td align="right">'.$rowdata->C_Amount.'</td>';
					$table_body.='<td align="right">'.$rowdata->D_Amount.'</td>';
					$table_body.='<td>'.$rowdata->prepared_by.'</td>';
					$table_body.='</tr>';
					
					$table_total_1=$table_total_1+$rowdata->C_Amount;
					$table_Dr_total_1=$table_Dr_total_1+$rowdata->D_Amount;
				}
				
				$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
				$table_footer.='<td colspan="5"><b>Total</b></td>';
				$table_footer.='<td align="right">'.$table_total_1.'</td>';
				$table_footer.='<td align="right">'.$table_Dr_total_1.'</td>';
				$table_footer.='<td ></td></tr>';
				
				$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
				
				// Set some content to print
				$html = <<<EOD
				$data_show 
EOD;
				// Print text using writeHTMLCell()
				$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
  
				
			}
		}
		
	// ---------------------------------------------------------    
  
    // Close and output PDF document
    // This method has several options, check the source code documentation for more information.
    $pdf->Output('ipd_payment_list.pdf', 'I');    
  
    //============================================================+
    // END OF FILE
    //============================================================+
	}
	
	public function report_total_payment_app_show($opd_date_range,$emp_name_id,$paymode_id,$order_1,$output=0)
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
					//echo $data_show;
					create_report_pdf($data_show,'Payment.pdf');
				}else{
					
					ExportExcel($data_show,'Payment');
				}
	}
	
	public function report_charge_type($opd_date_range)
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
			
			//echo $table_show;
			// create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
		  
			// set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor('IHC:D.S.Bisht');
			$pdf->SetTitle('IHC:OPD List');
			$pdf->SetSubject('IHC:OPD List');
			$pdf->SetKeywords('IHC:OPD List, PDF, OPD, List, guide');   
		  
			// set default header data
			$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING, array(0,64,255), array(0,64,128));
			$pdf->setFooterData(array(0,64,0), array(0,64,128)); 
		  
			// set header and footer fonts
			$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
			$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));  
		  
			// set default monospaced font
			$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED); 
		  
			// set margins
			//$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
			$pdf->SetMargins(5, PDF_MARGIN_TOP, 0.25);
			$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
			$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);    
		  
			// set auto page breaks
			$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); 
		  
			// set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  
		  
			// set some language-dependent strings (optional)
			if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
				require_once(dirname(__FILE__).'/lang/eng.php');
				$pdf->setLanguageArray($l);
			}   
		  
			// ---------------------------------------------------------    
		  
			// set default font subsetting mode
			$pdf->setFontSubsetting(true);   
		  
			// Set font
			// dejavusans is a UTF-8 Unicode font, if you only need to
			// print standard ASCII chars, you can use core fonts like
			// helvetica or times to reduce file size.
			$pdf->SetFont('dejavusans', '', 9, '', true);   
		  
			// Add a page
			// This method has several options, check the source code documentation for more information.
			
				// set text shadow effect
			//$pdf->setTextShadow(array('enabled'=>true, 'depth_w'=>0.2, 'depth_h'=>0.2, 'color'=>array(196,196,196), 'opacity'=>1, 'blend_mode'=>'Normal'));    
			
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
					
					// Set some content to print
					$html = <<<EOD
					$data_show 
EOD;
					// Print text using writeHTMLCell()
					$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   

					// ---------------------------------------------------------    
	  
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('Total_payment_list.pdf', 'I');    
	  
		//============================================================+
		// END OF FILE
		//============================================================+
		}
	

	public function report_emp_total($opd_date_range,$emp_name_id,$paymode_id,$order_1,$output=0)
		{
			$sql_f_all = "select p.update_by,
			sum(if(p.payof_type=1,if(p.credit_debit=0,p.amount,0),0)) as OPD_Amount,
			sum(if(p.payof_type=2,if(p.credit_debit=0,p.amount,0),0)) as Charge_Amount,
			sum(if(p.payof_type=4,if(p.credit_debit=0,p.amount,0),0)) as IPD_Amount,
			sum(if(p.credit_debit=1,p.amount,0)) as Return_Amount,
			sum(if(p.credit_debit=0,p.amount,p.amount*-1)) as total_Amount	";

			$sql_from=" from payment_history p ";
			
			$sql_where=" Where 1=1 ";
			
			$sql_group_by=" group by p.update_by_id ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
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
			$table_head.='<th align="center" width="100">Return Amount</th>';
			$table_head.='<th align="center" width="100">Total</th>';
			$table_head.='</tr>';
						
			$table_footer='';
			
					$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order);
					$data['gsub_rec']= $query->result();
										
					$table_body='';
					$table_total_opd=0.00;
					$table_total_ipd=0.00;
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
						$table_body.='<td align="right">'.$rowdata->Return_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata->total_Amount.'</td>';
						$table_body.='</tr>';
						
						
						$table_total_opd=$table_total_opd+$rowdata->OPD_Amount;
						$table_total_ipd=$table_total_ipd+$rowdata->IPD_Amount;
						$table_total_charge=$table_total_charge+$rowdata->Charge_Amount;
						$table_total_return=$table_total_return+$rowdata->Return_Amount;
						$table_total_all=$table_total_all+$rowdata->total_Amount;
						
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right">'.$table_total_opd.'</td>';
					$table_footer.='<td align="right">'.$table_total_charge.'</td>';
					$table_footer.='<td align="right">'.$table_total_ipd.'</td>';
					$table_footer.='<td align="right">'.$table_total_return.'</td>';
					$table_footer.='<td align="right">'.$table_total_all.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					if($output==0)
					{
						create_report_pdf($data_show,'Payment_Complie');
					}else{
						
						ExportExcel($data_show,'Payment_Complie');
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
		
		$sql_where.=" and o.apointment_date between '".$minRange."' and '".$maxRange. "'";
		
		if($doc_name_id>0)
		{
			$sql_where.=" and o.doc_id='".$doc_name_id."'";
		}
		
		
		$sql="select o.doc_id,o.doc_name,o.apointment_date,count(o.opd_id) as no_opd,sum(o.opd_fee_amount) as OPD_Amount,

			sum(if(o.payment_mode in (1,2),'1',0)) as Cash_no,
			sum(if(o.payment_mode in (1,2),o.opd_fee_amount,0)) as Cash_Amount,

			sum(if(o.payment_mode=4,'1',0)) as Org_Credit_no,
			sum(if(o.payment_mode=4,o.opd_fee_amount,0)) as Org_Credit_Amount,

			sum(if(o.opd_correction_amount>0,1,0)) as No_Cancel,
			sum(if(o.opd_correction_amount>0,o.opd_correction_amount,0)) as No_Cancel_Amount

			from opd_master o  
			where ".$sql_where." and o.payment_status=1  
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
								<th align="center">No. Cancel</th>
								<th align="center">Cancel Amt.</th>
								<th align="center">Net OPD</th>
								<th align="center">Net Amount</th>
							</tr>
						</thead>';
						
		$line='<tbody>';
		$tablefooter='</tbody></table>';
		$FooterHead='';
		foreach($opdlist as $row)
		{ 
			$net_opd=$row->no_opd - $row->No_Cancel;
			$net_amount=$row->OPD_Amount + $row->Org_Credit_Amount - $row->No_Cancel_Amount ;
				
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
				$line.='<th align="right">'.$row->No_Cancel.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$row->No_Cancel_Amount.'</th>'.PHP_EOL;

				$line.='<th align="right">'.$net_opd.'</th>'.PHP_EOL;
				$line.='<th align="right">'.$net_amount.'</th>'.PHP_EOL;
				
				$line.="</tr>";
				
				$FooterHead.='<h2>Doctor :Dr. '.$row->doc_name.' / Total Net OPD : '.$net_opd.' / Total Amount : Rs.'.number_format($net_amount,2).' </h2>';
				
			
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
				$line.='<td align="right">'.$row->No_Cancel.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$row->No_Cancel_Amount.'</td>'.PHP_EOL;
				
				$line.='<td align="right">'.$net_opd.'</td>'.PHP_EOL;
				$line.='<td align="right">'.$net_amount.'</td>'.PHP_EOL;
				
				$line.="</tr>";
			}
		}
	
		$print_data= $tableHead.$line.$tablefooter.'<br>'.$FooterHead;
		if($output==0)
		{
			create_report_pdf_landscape($print_data,'OPDList');
		}else{
			
			ExportExcel($print_data,'OPDList');
		}
		
	}
}
  
/* End of file c_test.php */
/* Location: ./application/controllers/c_test.php */