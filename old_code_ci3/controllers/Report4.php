<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report4 extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
    }
  
	function echs_ipd_list_main()
	{
		$sql="select * from h_insurance_group ";
        $query = $this->db->query($sql);
        $data['ins_group']= $query->result();

		$sql="select * from hc_insurance order by short_name ";
        $query = $this->db->query($sql);
        $data['hc_insurance']= $query->result();

		$this->load->view('Report3/ECHS_IPD',$data);
	}
	
	function echs_opd_list_main()
	{
		$sql="select * from h_insurance_group ";
        $query = $this->db->query($sql);
        $data['ins_group']= $query->result();

		$sql="select * from hc_insurance order by short_name ";
        $query = $this->db->query($sql);
        $data['hc_insurance']= $query->result();

		$this->load->view('Report3/ECHS_OPD',$data);
	}
  
	function echs_ipd_list_data($daterange,$org_status,$ipd_type,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;
		
		$where.=" and  p.ipd_status=1";
		
		if($org_status>=0)
		{
			$where.=" and  p.status=".$org_status;
		}
		
		$where.="   and  p.register_date between '".$minRange."' and '".$maxRange."' " ;
		
		if($ipd_type>0){
			$where.=" and group_ins=".$ipd_type." ";
		}elseif($ipd_type<0){
			$where.=" and insurance_id=".($ipd_type*-1)." ";
		}
		
	
		$sql="select p.ipd_code,p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,p_code,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,
			charge_amount,paid_amount,p.org_submit_status,p.status,
			concat(p.insurance_no,'/',p.insurance_no_1) as case_info,
			p.med_amount,p.Discount_all,p.chargeamount_all,p.insurance_id,p.ins_company_name,
			p.amount_recived,p.amount_deduction,p.app_status,p.color,
			(p.charge_amount+p.med_amount+p.chargeamount_all-p.Discount_all) as net_total
			from v_ipd_list p   where ".$where."	group by p.p_id,p.doc_list  order by id";
		
		//echo $sql;
		
		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_Payment=0.00;

		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr  >
					<th width="50px">#</th>
					<th width="100px" align="right">Case Info.</th>
					<th>IPD Code</th>
					<th>P Code</th>
					<th>Person Name</th>
					<th>Org. Status</th>
					<th>Admit Date</th>
					<th>Discharge Date</th>
					<th>Org Name</th>
					<th>Doc. Name</th>
					<th width="100px" align="right">Net Amt.</th>
					<th>Amt Received</th>
					<th>Amt Deduct</th>
				 </tr>';
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr style=";color:'.$row->color.';">
							<td>'.$sr_no.'</td>
							<td align="right">'.$row->case_info.'</td>
							<td>'.$row->ipd_code.'</td>
							<td>'.$row->p_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td>'.$row->org_submit_status.'</td>
							<td>'.$row->str_register_date.'</td>
							<td>'.$row->str_discharge_date.'</td>
							<td>'.$row->ins_company_name.'</td>
							<td>'.$row->doc_name.'</td>
							<td align="right">'.$row->net_total.'</td>
							<td>'.$row->amount_recived.'</td>
							<td>'.$row->amount_deduction.'</td>
					 </tr>';
			$total_Payment+= $row->net_total;
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
							<td>Total</td>
							<td>'.$total_Payment.'</td>
					 </tr>';
	
		$content.="</table>";

		if($output==0)
		{
			//echo $content;
			//create_report_pdf_landscape($content,'ECHS_IPD_List'.date('Yms'));
			$this->load->library('m_pdf');
			
			$file_name="Report-ECHS_IPD_List_".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");


		}else{
			ExportExcel($content,'ECHS_IPD_List'.date('Yms'));
		}
	
	}
	
	function echs_opd_list_data($daterange,$org_status,$ipd_type,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;

		if($org_status>=0)
		{
			$where.=" and  o.status=".$org_status;
		}

		$where.="   and  o.date_registration between '".$minRange."' and '".$maxRange."' " ;
		
		if($ipd_type>0){
			$where.=" and in_master.id=".$ipd_type." ";
		}elseif($ipd_type<0){
			$where.=" and o.insurance_id=".($ipd_type*-1)." ";
		}

		$sql="select o.case_id_code,Concat(o.insurance_no,'/',o.insurance_no_1) as case_info,p.p_fname,
		`o`.`status`,p.p_code,p.p_fname,
		(case `o`.`status` when 0 then 'Pending' when 1 then 'Ready' when 2 then 'Submit' else 'Nothing' end) AS `org_submit_status`,
		(o.inv_opd_amt+o.inv_opd_charge_amt) as Charge_amount,
		o.inv_opd_med_amt as med_net_amount,
		o.date_registration,date_format(o.date_registration,'%d-%m-%Y') as str_date_registration
		from (organization_case_master o join patient_master p on o.p_id=p.id and o.case_type=0)
		join hc_insurance as in_master  on in_master.id = o.insurance_id
		where ".$where." and (o.inv_opd_amt+o.inv_opd_charge_amt+o.inv_opd_med_amt)>0
		group by o.id order by o.id";
		
		echo $sql;
		
		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_Payment=0.00;
		$total_Med_Payment=0.00;

		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr>
					<th width="50px">#</th>
					<th width="100px" align="right">Case Info.</th>
					<th>Org. Code</th>
					<th>UHID</th>
					<th>Person Name</th>
					<th>Date</th>
					<th width="100px" align="right">Charge Amt.</th>
					<th width="100px" align="right">Med. Amt.</th>
				 </tr>';
		
		$sr_no=0;
		foreach($dischargelist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td align="right">'.$row->case_info.'</td>
							<td>'.$row->case_id_code.'</td>
							<td>'.$row->p_code.'</td>
							<td>'.$row->p_fname.'</td>
							<td>'.$row->str_date_registration.'</td>
							<td align="right">'.$row->Charge_amount.'</td>
							<td align="right">'.$row->med_net_amount.'</td>
					 </tr>';
			$total_Payment+= $row->Charge_amount;
			$total_Med_Payment+= $row->med_net_amount;
			
		}
				$content.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td>'.$total_Payment.'</td>
							<td>'.$total_Med_Payment.'</td>
					 </tr>';
		
		$content.="</table>";

		if($output==0)
		{
			//echo $content;
			//create_report_pdf_landscape($content,'ECHS_OPD_List'.date('Yms'));
			$this->load->library('m_pdf');
			
			$file_name="Report-ECHS_OPD_List_".date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'ECHS_OPD_List'.date('Yms'));
		}
	
	}
	
	
	
}
  
/* End of file c_test.php */
/* Location: ./application/controllers/c_test.php */