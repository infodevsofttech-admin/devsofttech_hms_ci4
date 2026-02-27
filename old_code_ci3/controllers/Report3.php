<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report3 extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
    }
  
	function ipd_report_1()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from h_insurance_group ";
        $query = $this->db->query($sql);
        $data['ins_group']= $query->result();

		$sql="select * from hc_insurance order by short_name";
        $query = $this->db->query($sql);
        $data['hc_insurance']= $query->result();
		
		$this->load->view('Report/Report_IPD',$data);
	}
	
	function ipd_report_2()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from h_insurance_group ";
        $query = $this->db->query($sql);
        $data['ins_group']= $query->result();

		$sql="select * from hc_insurance order by short_name";
        $query = $this->db->query($sql);
        $data['hc_insurance']= $query->result();
		
		$this->load->view('Report/IPD_Current_List',$data);
	}

	function ipd_report_3()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from h_insurance_group ";
        $query = $this->db->query($sql);
        $data['ins_group']= $query->result();
		
		$this->load->view('Report/Report_medical',$data);
	}
  
	function ipd_discharge($daterange,$doc_name_id,$ipd_type,$ipd_status,$ipd_date,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;

		$order_by="";
		
		if($ipd_status>-1)
		{
			$where.=" and  p.ipd_status=".$ipd_status;
		}
		
		if($ipd_date==0)
		{
			$where.="   and  date(p.register_date) between '".$minRange."' and '".$maxRange."' " ;
			$order_by.=" p.id";
		}elseif($ipd_date==1)
		{
			$where.="   and  date(p.discharge_date) between '".$minRange."' and '".$maxRange."' " ;
			$order_by.=" p.discharge_date";
		}
		
		if($doc_name_id<>0)
		{
			$doc_array=explode("S",$doc_name_id);
			$where.="and ( 1<>1 ";
			foreach($doc_array as $row)
			{
				$where.=" OR  FIND_IN_SET('".$row."',doc_list) ";
			}
			
			$where.=")";
		}
		
		if($ipd_type>=1){
			$where.=" and group_ins=".$ipd_type." ";
		}else if($ipd_type<0){
			$where.=" and insurance_id=".($ipd_type*-1)." ";
		}



		$content="";
	
		$sql="select p.ipd_code,p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,p_code,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.status_desc,
			p.charge_amount,p.package_charge_amount,p.med_amount,p.package_med_amount,p.chargeamount_all,p.Discount_all,
			(p.charge_amount+p.med_amount+p.chargeamount_all-p.Discount_all) as net_Amount,p.paid_amount
			from v_ipd_list p  where ".$where."	group by p.id  order by $order_by";

		//$content.= $sql;
		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_paid_amount=0.00;
		$total_net_amount=0.00;

		$content="<style>@page {
				margin-top: 0.5cm;
				margin-bottom: 0.5cm;
				margin-left: 0.5cm;
				margin-right: 0.5cm;
			
			}
			</style>";

		$content.='<table border="1" width="100%" cellpadding="3" autosize="2.4">';

		$content.='<thead>
					<tr>
					<th width="50px">#</th>
					<th>IPD / UHID Code</th>
					<th>Person Name</th>
					<th>Admit /Discharge Date</th>
					<th>Doc. Name</th>
					<th width="100px" align="right">Net Amount</th>
					<th width="100px" align="right">Paid Amt.</th>
					<th>Status</th>
					<th>TYPE</th>
				 </tr>
				 </thead>';
		
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td>'.$row->ipd_code.'<br/>'.$row->p_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td>'.$row->str_register_date.'<br/>'.$row->str_discharge_date.'</td>
							<td>'.$row->doc_name.'</td>
							<td align="right">'.$row->net_Amount.'</td>
							<td align="right">'.$row->paid_amount.'</td>
							<td>'.$row->status_desc.'</td>
							<td>'.$row->admit_type.'</td>
					 </tr>';
			
			$total_paid_amount+= $row->paid_amount;
			$total_net_amount+= $row->net_Amount;
		}

				$content.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td align="right">'.$total_net_amount.'</td>
							<td align="right">'.$total_paid_amount.'</td>
							<td></td>
							<td></td>
					 </tr>';
		
		
		$content.="</table>";
		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		
		}else{
			
			ExportExcel($content,'IPD_List');
		}
		
		
		
	
	}
	
	function ipd_discharge_details($daterange,$doc_name_id,$ipd_type,$ipd_status,$ipd_date,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;
		$order_by="";
		
		if($ipd_status>-1)
		{
			$where.=" and  p.ipd_status=".$ipd_status;
		}
		
		if($ipd_date==0)
		{
			$where.="   and  p.register_date between '".$minRange."' and '".$maxRange."' " ;
			$order_by.=" p.id";
		}elseif($ipd_date==1)
		{
			$where.="   and  p.discharge_date between '".$minRange."' and '".$maxRange."' " ;
			$order_by.=" p.discharge_date";
		}
		
		if($doc_name_id<>0)
		{
			$doc_array=explode("S",$doc_name_id);
			$where.="and ( 1<>1 ";
			foreach($doc_array as $row)
			{
				$where.=" OR  FIND_IN_SET('".$row."',doc_list) ";
			}
			
			$where.=")";
		}
		
		if($ipd_type>=1){
			$where.=" and group_ins=".$ipd_type." ";
		}else if($ipd_type<0){
			$where.=" and insurance_id=".($ipd_type*-1)." ";
		}

	
		$sql="select p.ipd_code,p.P_mobile1,case_id_code,insurance_no,insurance_no_1,insurance_no_2,Org_insurance_comp,xgender,str_age,
			p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,p.reg_time,p_code,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,
			p.charge_amount,p.package_charge_amount,p.med_amount,p.package_med_amount,p.chargeamount_all,p.Discount_all,
			(p.charge_amount+p.med_amount+p.chargeamount_all-p.Discount_all) as net_Amount,p.paid_amount,
			p.amount_recived,amount_deduction,p.org_approved_amount,status_desc
			from v_ipd_list p  where ".$where."	group by p.id  order by  $order_by";

		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_paid_amount=0.00;
		$total_net_amount=0.00;

		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr>
					<th width="50px">#</th>
					<th>IPD Code</th>
					<th>P Code</th>
					<th>Person Name</th>
					<th>Gender</th>
					<th>Age</th>
					
					
					<th>Card No.</th>
					<th>Claim ID/Case ID</th>
					<th>Claim ID/Case ID</th>
					
					<th>Admit Date</th>
					<th>Ad. Time</th>
					<th>Discharge Date</th>
					<th>Doc. Name</th>
					
					<th width="100px" align="right">Charge Amt.</th>
					<th width="100px" align="right">Medicine Amt.</th>
					<th width="100px" align="right">Other Charge</th>
					<th width="100px" align="right">Discount</th>
					<th width="100px" align="right">Net Amount</th>
					<th width="100px" align="right">Paid Amt.</th>
					
					<th>TPA</th>
					<th>Case Code</th>
					<th>Insurance</th>
					
					<th width="100px" align="right">TPA Approved Amt.</th>
					<th width="100px" align="right">TPA Amt. Rec.</th>
					<th width="100px" align="right">TPA Amt. Ded.</th>

					<th width="100px" align="right">Pack. Amt.</th>
					<th width="100px" align="right">Pack. Med. Amt.</th>
					<th width="100px" align="left">Discharge Status</th>

					
				 </tr>';
		
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td>'.$row->ipd_code.'</td>
							<td>'.$row->p_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td>'.$row->xgender.'</td>
							<td>'.$row->str_age.'</td>
														
							<td>'.$row->insurance_no.'</td>
							<td>'.$row->insurance_no_1.'</td>
							<td>'.$row->insurance_no_2.'</td>
							
							<td>'.$row->str_register_date.'</td>
							<td>'.$row->reg_time.'</td>
							<td>'.$row->str_discharge_date.'</td>
							<td>'.$row->doc_name.'</td>
							
							<td align="right">'.$row->charge_amount.'</td>
							<td align="right">'.$row->med_amount.'</td>
							<td align="right">'.$row->chargeamount_all.'</td>
							<td align="right">'.$row->Discount_all.'</td>
							<td align="right">'.$row->net_Amount.'</td>
							<td align="right">'.$row->paid_amount.'</td>
							
							<td>'.$row->admit_type.'</td>
							<td>'.$row->case_id_code.'</td>
							<td>'.$row->Org_insurance_comp.'</td>
							
							<td align="right">'.$row->org_approved_amount.'</td>
							<td align="right">'.$row->amount_recived.'</td>
							<td align="right">'.$row->amount_deduction.'</td>
							
							<td align="right">'.$row->package_charge_amount.'</td>
							<td align="right">'.$row->package_med_amount.'</td>
							<td align="left">'.$row->status_desc.'</td>				
							
					 </tr>';
			$total_paid_amount+= $row->paid_amount;
			$total_net_amount+= $row->net_Amount;
		}

				$content.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>'.$total_net_amount.'</td>
							<td>'.$total_paid_amount.'</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
					 </tr>';
		
		
		$content.="</table>";

		//echo $content;
		ExportExcel($content,'IPD_List');
		
	}
	
	function ipd_discharge_medical($daterange,$doc_name_id,$ipd_type,$ipd_status,$ipd_date,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" (p.med_amount>0 or p.package_med_amount>0 )" ;
		
		if($ipd_status>-1)
		{
			$where.=" and  p.ipd_status=".$ipd_status;
		}
		
		if($ipd_date==0)
		{
			$where.="   and  p.register_date between '".$minRange."' and '".$maxRange."' " ;
		}elseif($ipd_date==1)
		{
			$where.="   and  p.discharge_date between '".$minRange."' and '".$maxRange."' " ;
		}
		
		if($doc_name_id>0)
		{
			$where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
		}
		
		if($ipd_type>=1){
			$where.=" and group_ins=".$ipd_type." ";
		}else if($ipd_type<0){
			$where.=" and insurance_id=".($ipd_type*-1)." ";
		}
	
		$sql="select p.ipd_code,case_id_code,insurance_no,insurance_no_1,insurance_no_2,Org_insurance_comp,
			p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,p_code,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,
			p.charge_amount,p.package_charge_amount,p.med_amount,p.package_med_amount,p.chargeamount_all,p.Discount_all,
			(p.charge_amount+p.med_amount+p.chargeamount_all-p.Discount_all) as net_Amount,p.paid_amount,
			p.amount_recived,amount_deduction,p.org_approved_amount
			from v_ipd_list p  where ".$where."	group by p.id  order by id";

		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_med_amount=0.00;
		$package_med_amount=0.00;
		$total_net_amount=0.00;

		$content="<h3>Date(YYYY-MM-DD) between ".$minRange." and ".$maxRange."</h3> ";

		$content.='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr>
					<th width="50px">#</th>
					<th>IPD Code</th>
					<th>Person Name</th>
					<th>TPA</th>
					<th width="100px" align="right">Net Amount</th>
					<th width="100px" align="right">Medicine Amt.</th>
					<th width="100px" align="right">Medicine Pack. Amt.</th>
					<th>Doc. Name</th>
				 </tr>';
		
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td>'.$row->ipd_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td>'.$row->admit_type.'</td>
							<td align="right">'.$row->net_Amount.'</td>
							<td align="right">'.$row->med_amount.'</td>
							<td align="right">'.$row->package_med_amount.'</td>
							<td>'.$row->doc_name.'</td>
					 </tr>';
			$total_med_amount+= $row->med_amount;
			$package_med_amount+= $row->package_med_amount;
			$total_net_amount+= $row->net_Amount;
		}

			$content.='<tr>
				<td>#</td>
				<td></td>
				<td></td>
				<td></td>
				<td align="right">'.$total_net_amount.'</td>
				<td align="right">'.$total_med_amount.'</td>
				<td align="right">'.$package_med_amount.'</td>
				<td></td>
			</tr>';

			$med_cr_amt=$package_med_amount+$total_med_amount;

			$content.='<tr>
				<td>#</td>
				<td colspan="5" align="right">Total Medical Cr. Amount</td>
				<td align="right">'.$med_cr_amt.'</td>
				<td></td>
			</tr>';

		$content.="</table>";

		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-Medical_".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		
		}else{
			
			ExportExcel($content,'IPD_Medical_List');
		}
		
	}
	

	function ipd_current($doc_name_id,$ipd_type,$sortorder,$output=0)
	{

		$where =" p.ipd_status=0 " ;
		
		if($doc_name_id>0)
		{
			$where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
		}
		
		if($ipd_type>=1){
			$where.=" and group_ins=".$ipd_type." ";
		}else if($ipd_type<0){
			$where.=" and insurance_id=".($ipd_type*-1)." ";
		}
	
		$orderby=" Order By ";
		
		if($sortorder==0)
		{
			$orderby.=" p.id";
		}elseif($sortorder==1)
		{
			$orderby.=" p.register_date";
		}elseif($sortorder==2)
		{
			$orderby.=" p.p_fname";
		}else{
			$orderby.=" p.Bed_Desc";
		}
		
		$sql="select p.ipd_code,p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,
			charge_amount,paid_amount,p.app_status,p.color,p.org_approved_amount,p.net_amount,
			p.balance_amount,no_days
			from v_ipd_list p  where ".$where."	group by p.id ".$orderby;

		$query = $this->db->query($sql);
        $dischargelist= $query->result();

		$content='<table border="1" width="100%" cellpadding="3" style="background-color: white;">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>IPD Code</th>
					<th>Person Name</th>
					<th  width="150px">Bed No</th>
					<th>Admit Date</th>
					<th>No.Days</th>
					<th>Doc. Name</th>
					<th>TYPE</th>
					<th>Org. Status</th>
					<th>App. Amt.</th>
					<th>Bill Amt.</th>
					<th>Bal. Amt.</th>
					<th>Paid Amt.</th>
				 </tr></thead><tbody>';
		
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr style=";color:'.$row->color.';">
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->ipd_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td width="150px">'.$row->Bed_Desc.'</td>
							<td>'.$row->str_register_date.'<br>'.$row->str_discharge_date.'</td>
							<td>'.$row->no_days.'</td>
							<td>'.$row->doc_name.'</td>
							<td>'.$row->admit_type.'</td>
							<td>'.$row->app_status.'</td>
							<td>'.$row->org_approved_amount.'</td>
							<td>'.$row->net_amount.'</td>
							<td>'.$row->balance_amount.'</td>
							<td>'.$row->paid_amount.'</td>
					 </tr>';
		}
		
		$content.="</tbody></table>";

		if($output==0)
		{
			echo $content;
		}else{
			
			ExportExcel($content,'Current_IPD_List'+date('Ymdhis'));
		}
		
	
	}
	function report_Diagnosis_Qty_main()
	{
		$sql="select * from hc_item_type where is_ipd_opd in (0,1)";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();
		
		$this->load->view('Report3/diagnosis_qty',$data);	
	}
	
	
	function report_Diagnosis_Qty_total($opd_date_range,$Diagnosis_id,$output=0)
	{
		$sql_field="select t.item_type,y.group_desc,t.item_name,
				sum(t.item_qty) as t_item_qty,
				sum(t.item_amount) as item_amount,
				sum(Round(t.item_amount*(1-m.discount_amount/m.total_amount))) as item_amount_net
				from (invoice_item t join invoice_master m 
				on m.id=t.inv_master_id) join hc_item_type y on t.item_type=y.itype_id ";
		
		$sql_where=" where m.invoice_status=1 ";
		
		$rangeArray = explode("S",$opd_date_range);
		$minRange = str_replace('T',' ',$rangeArray[0]);
		$maxRange = str_replace('T',' ',$rangeArray[1]);
		
		if($minRange==$maxRange)
		{
			$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
		}else{
			$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
		}
		
		if($Diagnosis_id>0)
		{
			$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
			
			$sql_where.=" and t.item_type in (".$Diagnosis_id.") ";
		}
		
		$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
		
		$sql_group=" group by t.item_type,t.item_id order by t.item_type";
	
		$sql=$sql_field.$sql_where.$sql_group;
		
		$query = $this->db->query($sql);
        $Diagnosis_Data= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Diagnosis Group</th>
					<th>Diagnosis</th>
					<th  width="150px">Qty</th>
					<th>Amount</th>
					<th>Net Amount</th>
				 </tr></thead><tbody>';
		
		$sr_no=0;
		$item_amount=0;
		$item_amount_net=0;

		foreach($Diagnosis_Data as $row)
		{
			$sr_no=$sr_no+1;
			$item_amount+=$row->item_amount;
			$item_amount_net+=$row->item_amount_net;
			$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->group_desc.'</td>
							<td>'.$row->item_name.'</td>
							<td width="150px">'.$row->t_item_qty.'</td>
							<td>'.$row->item_amount.'</td>
							<td>'.$row->item_amount_net.'</td>
					 </tr>';
		}
		$content.='<tr>
							<td  width="50px">#</td>
							<td>Total</td>
							<td></td>
							<td width="150px"></td>
							<td>'.$item_amount.'</td>
							<td>'.$item_amount_net.'</td>
					 </tr>';

		$content.="</tbody></table>";

		if($output==0)
		{
			$this->load->library('m_pdf');

			$file_name="Report-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			
			ExportExcel($content,'Diagnosis_Qty'.date('Ymd'));
		}
		
	}

	function ipd_item_reports()
	{
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$sql="select * from ipd_item_type order by 'desc'";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();

		$this->load->view('Report/Report_medical',$data);
	}

	function ipd_item_reports_data($daterange,$Diagnosis_id,$doc_name_id,$ipd_status,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where =" 1=1 " ;

		$order_by=" i.id";

		if($ipd_status>-1)
		{
			$where.=" and  i.ipd_status=".$ipd_status;
		}
		
		if($Diagnosis_id>0)
		{
			$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
			
			$where.=" and t.item_type in (".$Diagnosis_id.") ";
		}

		if($doc_name_id>0)
		{
			$doc_list=str_replace("S",",",$doc_name_id);
			
			$where.=" and t.doc_id in ($doc_list)";
		}

		$where.="   and  date(i.register_date) between '".$minRange."' and '".$maxRange."' " ;

		$sql="SELECT i.id,i.ipd_code,i.p_fname,date_format(i.register_date,'%d-%m-%Y') AS register_date_str,
			DATE_FORMAT(i.discharge_date,'%d-%m-%Y') AS discharge_date_str,i.doc_name as ipd_doc_name,
			t.item_name,t.item_rate,t.item_qty,t.item_amount,t.doc_name,t.doc_id,
			ROUND(t.item_amount-(t.item_amount*((i.Discount_all)/(i.net_amount+i.Discount_all)))) AS Aft_Amt,
			ROUND(((i.Discount_all*100)/(i.net_amount+i.Discount_all)),2) AS Dis_per
			FROM v_ipd_list i JOIN ipd_invoice_item t ON i.id=t.ipd_id 
			Where $where Order By $order_by";
		$query = $this->db->query($sql);
        $data['ipd_item_data']= $query->result();
		

		$content=$this->load->view('Report/Report_medical_template',$data,true);

		//echo $content;

		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		
		}else{
			
			ExportExcel($content,'IPD_List');
		}


	}


	function ipd_item_refer_reports()
	{
		$sql="select * from refer_master where active=1 order by f_name";
        $query = $this->db->query($sql);
        $data['refer_master']= $query->result();

		$sql="select * from ipd_item_type order by 'desc'";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();

		$this->load->view('Report/Report_refer_ipd',$data);
	}

	function ipd_item_refer_reports_data($daterange,$refer_id,$ipd_status,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where =" 1=1 " ;

		$order_by=" i.id";

		if($ipd_status>-1)
		{
			$where.=" and  i.ipd_status=".$ipd_status;
		}
		
		if($refer_id>0)
		{
			$refer_list=str_replace("S",",",$refer_id);
			
			$where.=" and r.refer_by in ($refer_list)";
		}

		$where.="   and  date(i.register_date) between '".$minRange."' and '".$maxRange."' " ;

		$sql="SELECT i.id,i.ipd_code,i.p_fname,date_format(i.register_date,'%d-%m-%Y') AS register_date_str, 
			DATE_FORMAT(i.discharge_date,'%d-%m-%Y') AS discharge_date_str,
			i.doc_name as ipd_doc_name, i.net_amount,i.balance_amount, m.title,m.f_name,r.refer_type,t.type_desc 
			FROM ((v_ipd_list i JOIN ipd_refer r ON i.id=r.ipd_id) JOIN refer_type t ON r.refer_type=t.id)
				JOIN refer_master m ON r.refer_by=m.id
			Where $where Order By $order_by";
		$query = $this->db->query($sql);
        $data['ipd_refer_data']= $query->result();
		

		$content=$this->load->view('Report/Report_refer_template',$data,true);

		//echo $content;

		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		
		}else{
			
			ExportExcel($content,'IPD_List');
		}


	}
	
}
