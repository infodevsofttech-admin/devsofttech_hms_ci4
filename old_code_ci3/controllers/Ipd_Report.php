<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ipd_Report extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function Ipd_bal_panel()
	{
		$this->load->view('IPD_Report/ipd_balance_report');
	}
	
	public function Ipd_bal_panel_report($daterange,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" p.balance_amount>0 " ;
		
		$where.=" and Direct_Cust=1 and p.ipd_status=1  and  p.register_date between '".$minRange."' and '".$maxRange."' " ;
		
		if(isset($doc_name))
		{
			if($doc_name_id>0)
			{
				$where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
			}
		}

		$sql="select p.ipd_code,p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,p_code,p.Contact_info,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,p.Direct_Cust,
			p.net_amount,p.balance_amount,(p.org_amount_recived+p.total_paid_amount) as sum_of_paid
			from v_ipd_list p  where ".$where."	group by p.p_id,p.doc_list  order by id";

		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$total_Payment=0.00;
		$total_balance=0.00;

		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr>
					<th width="50px">#</th>
					<th>IPD Code</th>
					<th>P Code</th>
					<th>Person Name</th>
					<th>Phone No.</th>
					<th>Admit Date</th>
					<th>Discharge Date</th>
					<th>Doc. Name</th>
					<th width="100px" align="right">Net Amount</th>
					<th width="100px" align="right">Paid Amt.</th>
					<th width="100px" align="right">Balance Amt.</th>
					<th>TYPE</th>
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
							<td>'.$row->Contact_info.'</td>
							<td>'.$row->str_register_date.'</td>
							<td>'.$row->str_discharge_date.'</td>
							<td>'.$row->doc_name.'</td>
							<td align="right">'.$row->net_amount.'</td>
							<td align="right">'.$row->sum_of_paid.'</td>
							<td align="right">'.$row->balance_amount.'</td>
							<td>'.$row->admit_type.'</td>
					 </tr>';
			$total_Payment+= $row->sum_of_paid;
			$total_balance+= $row->balance_amount;
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
							<td>'.$total_balance.'</td>
							<td></td>
					 </tr>';
		$content.="</table>";
				
		if($output==0)
		{
			$this->load->library('m_pdf');
			 $file_name="IPD_Balance_List-".date('Ymdhis').".pdf";
			 $filepath=$file_name;
			 $this->m_pdf->pdf->WriteHTML($content);
			 $this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			
			ExportExcel($content,'IPD_Balance_List_'.date('dmYHis'));
		}

		
		
	}
	
 }