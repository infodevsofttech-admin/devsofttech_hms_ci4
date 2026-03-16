<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Medical_Report extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
        $this->load->library("Pdf");
    }
	
	//Sale Report 
	
	public function Report_1()
	{
		
		$this->load->view('Medical/Report/Med_Report_1');
	}
	
	public function Report_1_data($sale_date_range,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="select count(DISTINCT m.id) as No_invoice,
		count( DISTINCT  m.patient_id) as No_Patients,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		Sum(i.twdisc_amount) as tnet_amount,
		sum(if(m.ipd_id=0 and m.case_id=0,i.twdisc_amount,0)) as OPD_Cash_Bill_amount,
		sum(if(m.ipd_id=0 and m.case_id>1,i.twdisc_amount,0)) as OPD_Org_Bill_amount,

		sum(if(m.ipd_id>0 and m.ipd_credit=0,i.twdisc_amount,0)) as IPD_Cash_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1,i.twdisc_amount,0)) as IPD_Credit_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0,i.twdisc_amount,0)) as IPD_Package_Bill_amount
		
		from ((invoice_med_master m join inv_med_item i on m.id=i.inv_med_id) 
		left join patient_master p on m.patient_id=p.id)
		left join organization_case_master o on m.case_id=o.id
		
		where ".$sql_where." group by m.inv_date with Rollup";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="2" cellspacing="0">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Date</th>
					<th>No. Patients</th>
					<th>No. Inv.</th>
					<th>Net Amt.</th>
					<th>OPD Cash</th>
					<th>OPD Org.</th>
					<th>IPD Cash Amount</th>
					<th>IPD Cr Hospital</th>
					<th>IPD Cr Pkg. Hospital</th>
					<th>Tot. Cr. Amt.</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		$cr_Amt_row=0;
		$cr_Amt_total=0;

		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			
			$cr_Amt_row=$row->OPD_Org_Bill_amount+$row->IPD_Credit_Bill_amount+$row->IPD_Package_Bill_amount;

			if($row->inv_date=='')
			{
				$content.='<tr>
							<td width="50px">#</td>
							<td>Total</td>
							<td align="right">'.$row->No_Patients.'</td>
							<td align="right">'.$row->No_invoice.'</td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->OPD_Org_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Credit_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Package_Bill_amount.'</td>
							<td align="right">'.$cr_Amt_total.'</td>
						</tr>';
			}else{
				$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->inv_date_str.'</td>
							<td align="right">'.$row->No_Patients.'</td>
							<td align="right">'.$row->No_invoice.'</td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->OPD_Org_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Credit_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Package_Bill_amount.'</td>
							<td align="right">'.$cr_Amt_row.'</td>
						</tr>';
				$cr_Amt_total+=$cr_Amt_row;
			}
		}
		
		$content.='</tbody></table>';
		
		
		$this->load->library('m_pdf');
			
		$file_name="Report-MedicalBill-".date('Ymdhis').".pdf";

		$filepath=$file_name;

		
		$this->m_pdf->pdf->WriteHTML($content);
	
		$this->m_pdf->pdf->Output($filepath,"I");
		
		
	}
  

	//Daily Medicine Sale Report
	public function Report_daily_med_sale()
	{
		
		$this->load->view('Medical/Report/Med_Report_daily_med_sale');
	}

	public function Report_daily_med_sale_data($sale_date_range,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="SELECT i.item_code,i.item_Name,
					SUM(if(m.sale_return=0,i.qty,0)) AS Sale_Qty,
					SUM(if(m.sale_return=0,i.twdisc_amount,0)) AS Sale_Amount,
					SUM(if(m.sale_return=0,i.qty*p.purchase_unit_rate,0)) AS Purchase_Amount,
					SUM(if(m.sale_return=1,i.qty,0)) AS Return_Qty,
					SUM(if(m.sale_return=1,i.twdisc_amount,0)) AS Return_Amount,
					SUM(if(m.sale_return=1,i.qty*p.purchase_unit_rate,0)) AS return_Purchase_Amount,
					q.cur_qty
					FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id)
					LEFT JOIN purchase_invoice_item p ON i.store_stock_id=p.id 
					JOIN (SELECT t.item_code,SUM(t.total_unit-total_sale_unit-total_return_unit-total_lost_unit) AS cur_qty
			FROM purchase_invoice_item t
			GROUP BY t.item_code) AS q ON q.item_code=i.item_code
			Where ".$sql_where." GROUP BY i.item_code ORDER BY i.item_Name";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="2" cellspacing="0">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Item Code</th>
					<th>Item Name</th>
					<th>Sale Qty</th>
					<th>Sale Amt</th>
					<th>Pur. Amt</th>
					<th>Return Qty</th>
					<th>Return Amt</th>
					<th>Return Pur. Amt</th>
					<th>Cur.Qty</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		$tot_sale_amt=0;
		$tot_return_amt=0;
		$tot_pur_amt=0;
		$tot_pur_return_amt=0;

		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;

			$tot_sale_amt=$tot_sale_amt+$row->Sale_Amount;
			$tot_return_amt=$tot_return_amt+$row->Return_Amount;

			$tot_pur_amt+=$row->Purchase_Amount;
			$tot_pur_return_amt+=$row->return_Purchase_Amount;
			
			$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->item_code.'</td>
							<td >'.$row->item_Name.'</td>
							<td align="right">'.$row->Sale_Qty.'</td>
							<td align="right">'.$row->Sale_Amount.'</td>
							<td align="right">'.$row->Purchase_Amount.'</td>
							<td align="right">'.$row->Return_Qty.'</td>
							<td align="right">'.$row->Return_Amount.'</td>
							<td align="right">'.$row->return_Purchase_Amount.'</td>
							<td align="right">'.$row->cur_qty.'</td>
						</tr>';
		}

		$content.='<tr>
							<th  width="50px">#</th>
							<th></th>
							<th ></th>
							<th >Total Sale</th>
							<th align="right">'.$tot_sale_amt.'</th>
							<th align="right">'.$tot_pur_amt.'</th>
							<th >Total  Return</th>
							<th align="right">'.$tot_return_amt.'</th>
							<th align="right">'.$tot_pur_return_amt.'</th>
							<th></th>
						</tr>';
		
		$content.='</tbody></table>';

		$Tot_Margin=($tot_sale_amt-$tot_pur_amt)-($tot_return_amt-$tot_pur_return_amt);

		$content.="Total Margin : Rs.".$Tot_Margin;
				
		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-MedicalSale-".date('Ymdhis').".pdf";
	
			$filepath=$file_name;
				
			$this->m_pdf->pdf->WriteHTML($content);
	 
			$this->m_pdf->pdf->Output($filepath,"I");

			
		}else{
			$file_name="Report-MedicalSale-".date('Ymdhis').".pdf";

			ExportExcel($content,$file_name);
		}
	
	}

	//Company Wise Medicine Sale Report
	public function Report_company_med_sale()
	{
		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();

		$this->load->view('Medical/Report/Med_Report_company_med_sale',$data);
	}

	public function Report_company_med_sale_data($sale_date_range,$comp_id,$output=0)
	{
		$sql_where="  c.company_id= ".$comp_id;
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
	
		$sql="SELECT i.item_code,i.item_Name,c.company_name,
			SUM(if(m.sale_return=0,i.qty,0)) AS Sale_Qty,
			SUM(if(m.sale_return=0,i.twdisc_amount,0)) AS Sale_Amount,
			SUM(if(m.sale_return=0,i.qty*p.purchase_unit_rate,0)) AS Purchase_Amount,
			SUM(if(m.sale_return=1,i.qty,0)) AS Return_Qty,
			SUM(if(m.sale_return=1,i.twdisc_amount,0)) AS Return_Amount,
			SUM(if(m.sale_return=1,i.qty*p.purchase_unit_rate,0)) AS return_Purchase_Amount
			FROM ((invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id)
			JOIN purchase_invoice_item p ON i.store_stock_id=p.id )
			JOIN med_product_master c ON i.item_code=c.id
			Where ".$sql_where." GROUP BY i.item_code ORDER BY i.item_Name";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="2" cellspacing="0">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Item Code</th>
					<th>Item Name</th>
					<th>Sale Qty</th>
					<th>Sale Amt</th>
					<th>Pur. Amt</th>
					<th>Return Qty</th>
					<th>Return Amt</th>
					<th>Return Pur. Amt</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		$tot_sale_amt=0;
		$tot_return_amt=0;
		$tot_pur_amt=0;
		$tot_pur_return_amt=0;

		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;

			$tot_sale_amt=$tot_sale_amt+$row->Sale_Amount;
			$tot_return_amt=$tot_return_amt+$row->Return_Amount;

			$tot_pur_amt+=$row->Purchase_Amount;
			$tot_pur_return_amt+=$row->return_Purchase_Amount;
			
			$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->item_code.'</td>
							<td >'.$row->item_Name.'</td>
							<td align="right">'.$row->Sale_Qty.'</td>
							<td align="right">'.$row->Sale_Amount.'</td>
							<td align="right">'.$row->Purchase_Amount.'</td>
							<td align="right">'.$row->Return_Qty.'</td>
							<td align="right">'.$row->Return_Amount.'</td>
							<td align="right">'.$row->return_Purchase_Amount.'</td>
						</tr>';
		}

		$content.='<tr>
							<th  width="50px">#</th>
							<th></th>
							<th ></th>
							<th >Total Sale</th>
							<th align="right">'.$tot_sale_amt.'</th>
							<th align="right">'.$tot_pur_amt.'</th>
							<th >Total  Return</th>
							<th align="right">'.$tot_return_amt.'</th>
							<th align="right">'.$tot_pur_return_amt.'</th>
						</tr>';
		
		$content.='</tbody></table>';

		$Tot_Margin=($tot_sale_amt-$tot_pur_amt)-($tot_return_amt-$tot_pur_return_amt);

		$content.="Total Margin : Rs.".$Tot_Margin;
				
		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-MedicalSale-".date('Ymdhis').".pdf";
	
			$filepath=$file_name;
				
			$this->m_pdf->pdf->WriteHTML($content);
	 
			$this->m_pdf->pdf->Output($filepath,"I");

			
		}else{
			$file_name="Report-MedicalSale-".date('Ymdhis').".pdf";

			ExportExcel($content,$file_name);
		}
	
	}
	
	public function Report_company_med_purchase_data($sale_date_range,$comp_id,$output=0)
	{
		$sql_where="  p.company_id= ".$comp_id;
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.date_of_invoice between '".$minRange."' and '".$maxRange. "'";
		
		$sql="SELECT i.item_code,i.Item_name,i.packing,
			sum(i.qty) as qty,sum(i.taxable_amount) AS taxable_amount,
			sum(i.net_amount)  AS net_amount ,c.company_name 
			FROM ((purchase_invoice_item i JOIN purchase_invoice m on i.purchase_id=m.id) 
			JOIN med_product_master p ON i.item_code=p.id) JOIN med_company c ON p.company_id=c.id 
			Where ".$sql_where." GROUP BY i.item_code ORDER BY i.Item_name";
		
		echo $sql;
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="2" cellspacing="0">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Company Name</th>
					<th>Item Name</th>
					<th>Purchase Qty</th>
					<th>Pur. Tax. Amt</th>
					<th>Pur. Net Amt</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		$tot_taxable_amount=0;
		$tot_net_amount=0;
		
		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;

			$tot_taxable_amount=$tot_taxable_amount+$row->taxable_amount;
			$tot_net_amount=$tot_net_amount+$row->net_amount;

			$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->company_name.'</td>
							<td >'.$row->Item_name.'</td>
							<td align="right">'.$row->qty.'</td>
							<td align="right">'.$row->taxable_amount.'</td>
							<td align="right">'.$row->net_amount.'</td>
						</tr>';
		}

		$content.='<tr>
							<th  width="50px">#</th>
							<th></th>
							<th ></th>
							<th >Total </th>
							<th align="right">'.$tot_taxable_amount.'</th>
							<th align="right">'.$tot_net_amount.'</th>
						</tr>';
		
		$content.='</tbody></table>';

		//echo $content;	
		if($output==0)
		{
			
			$this->load->library('m_pdf');
			
			$file_name="Report-MedicalCompanyWisePurcase-".date('Ymdhis').".pdf";
	
			$filepath=$file_name;
				
			$this->m_pdf->pdf->WriteHTML($content);
	 
			$this->m_pdf->pdf->Output($filepath,"I");

			
		}else{
			$file_name="Report-MedicalCompanyWisePurcase-".date('Ymdhis').".pdf";

			ExportExcel($content,$file_name);
		}
	
	}
  
	// Pending Invoice Report

	public function Report_2_OPD_Pending()
	{
		
		$this->load->view('Medical/Report/Med_Report_2_Pending');
	}

	public function Report_2_Bal_pending_data($sale_date_range,$output=0)
	{
		$sql_where=" 1=1 ";

		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange."'";
		
		$Report_Heading="Date (YYYY-MM-DD) From  :".$minRange." To ".$maxRange."  / Data Filter : Pending Balance Invoice";

		$sql="Select m.id,m.inv_med_code, m.patient_id,m.inv_name,m.patient_code,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		Sum(m.net_amount) as tnet_amount ,
		Sum(m.payment_received) as payment_received,
		Sum(m.payment_balance) as payment_balance
		FROM invoice_med_master m 
		where  ".$sql_where." 
		and  (m.payment_balance >1 OR  m.payment_balance  <-1) 
		AND m.ipd_id=0 and m.case_id=0
		 group by inv_date,m.id with ROLLUP";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		//echo $sql;
		
		$content='<p>'.$Report_Heading.'</p>';
		$content.='<table border="1" cellspacing=0 style="width:100%">';

		$content.='<thead><tr>
					<th >#</th>
					<th>Date</th>
					<th width="120px">Invoice ID</th>
					<th width="150px">Patients Name</th>
					<th>Net Amt.</th>
					<th>Amt. Rec.</th>
					<th>Amt. Bal.</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		
		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			
			if($row->inv_date=='')
			{
				$content.='<tr>
							<td >#</td>
							<td colspan="3"><b>Grand Total : </b></td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->payment_received.'</td>
							<td align="right">'.$row->payment_balance.'</td>
						</tr>';
			}elseif($row->id=='')
			{
				$content.='<tr>
							<td>#</td>
							<td colspan="3"><i>Total Date : '.$row->inv_date_str.'</i></td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->payment_received.'</td>
							<td align="right">'.$row->payment_balance.'</td>
						</tr>';
				
			}else{

				$content.='<tr>
							<td  >'.$sr_no.'</td>
							<td>'.$row->inv_date_str.'</td>
							<td width="120px">'.$row->inv_med_code.'</td>
							<td width="150px">'.$row->inv_name.'<br>'.$row->patient_code.'</td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->payment_received.'</td>
							<td align="right">'.$row->payment_balance.'</td>
						</tr>';
			}
		}
		
		$content.='</tbody>
		<tfoot>
			<tr>
				<th >#</th>
				<th>Date</th>
				<th width="120px">Invoice ID</th>
				<th width="150px">Patients Name</th>
				<th>Net Amt.</th>
				<th>Amt. Rec.</th>
				<th>Amt. Bal.</th>
			</tr>
		</tfoot>
		</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
	
			
	
			$file_name='Report-MedicalBill_Pending-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			ExportExcel($content,'Med_Sale_day0001');
		}
		
		//echo $content;
		
		
	}
	
	
	

	// OPD Pending Balance Paid or OLD Payment Received

	public function Report_2_OPD_Pending_Paid()
	{
		
		$this->load->view('Medical/Report/Med_Report_2_Pending_paid');
	}
	
	public function Report_2_Bal_pending_payment($sale_date_range,$output=0)
	{
		$sql_where=" 1=1 ";

		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and Date(h.payment_date)= '".$minRange."' and m.inv_date < '".$minRange."'";
		
		$Report_Heading="Date (YYYY-MM-DD)   :".$minRange."   / Data Filter : Payment of OLD OPD Invoice";

		$sql="Select m.id,m.inv_med_code, m.patient_id,m.inv_name,m.patient_code, m.inv_date,
		Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str, 
		(m.net_amount) as tnet_amount , 
		(m.payment_received) as payment_received, 
		(m.payment_balance) as payment_balance,
		if(h.credit_debit=0, h.amount,h.amount*-1) AS tran_amt
		FROM (invoice_med_master m join payment_history_medical h on m.id=h.Medical_invoice_id ) 
		where ".$sql_where." ";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		//echo $sql;
		
		$content='<p>'.$Report_Heading.'</p>';
		$content.='<table border="1" cellspacing=0 style="width:100%">';

		$content.='<thead><tr>
					<th >#</th>
					<th>Date</th>
					<th width="120px">Invoice ID</th>
					<th width="150px">Patients Name</th>
					<th>Net Amt.</th>
					<th>Amt. Rec.</th>
					<th>Amt. Bal.</th>
					<th>Payment Amt.</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		
		$tot_payment=0;

		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td  >'.$sr_no.'</td>
							<td>'.$row->inv_date_str.'</td>
							<td width="120px">'.$row->inv_med_code.'</td>
							<td width="150px">'.$row->inv_name.'<br>'.$row->patient_code.'</td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->payment_received.'</td>
							<td align="right">'.$row->payment_balance.'</td>
							<td align="right">'.$row->tran_amt.'</td>
						</tr>';
			$tot_payment+=$row->tran_amt;
		}
		
		$content.='</tbody>
		<tfoot>
			<tr>
				<th >#</th>
				<th></th>
				<th width="120px"></th>
				<th width="150px"></th>
				<th></th>
				<th></th>
				<th>Total</th>
				<th>'.$tot_payment.'</th>
			</tr>
		</tfoot>
		</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;

	
			$file_name='Report-MedicalBill_Pending-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			ExportExcel($content,'Med_Sale_day0001');
		}
		
		//echo $content;

	}
	//Daily Cash Received List
	
	public function Report_2()
	{
		
		$this->load->view('Medical/Report/Med_Report_2');
	}

	
	public function Report_2_data($sale_date_range,$bill_type,$output=0)
	{
		$sql_where=" 1=1 ";

		$minRange = $sale_date_range;
		
		$sql_where.=" and m.inv_date = '".$minRange."' ";

		$Filter_type="ALL";

		if($bill_type==1){
			$sql_where.=" and (m.ipd_id=0 and m.case_id=0 )";
			$Filter_type="OPD CASH";
		}elseif($bill_type==2){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=0 )";
			$Filter_type="IPD CASH";
		}elseif($bill_type==3){
			$sql_where.=" and (m.ipd_id=0 and m.case_id>1 )";
			$Filter_type="OPD Org.";
		}elseif($bill_type==4){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1 )";
			$Filter_type="IPD Cr";
		}elseif($bill_type==5){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0 )";
			$Filter_type="IPD Pkg Cr";
		}elseif($bill_type==6){
			$sql_where.=" and ((m.ipd_id>0 and m.ipd_credit=1) or (m.ipd_id=0 and m.case_id>1 ))";
			$Filter_type="Hospital Cr";
		}
		
		$Report_Heading="Date (YYYY-MM-DD) :".$minRange."   / Data Filter :".$Filter_type;

		$sql="Select m.id,m.inv_med_code,m.ipd_code, m.patient_id,m.inv_name,m.patient_code,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		Sum(i.twdisc_amount) as tnet_amount,
		sum(if(m.ipd_id=0 and m.case_id=0,i.twdisc_amount,0)) as OPD_Cash_Bill_amount,
		sum(if(m.ipd_id=0 and m.case_id>1,i.twdisc_amount,0)) as OPD_Org_Bill_amount,

		if(o1.short_name IS null,	o2.short_name,	o1.short_name) AS Ins_name,
		sum(if(m.ipd_id>0 and m.ipd_credit=0,i.twdisc_amount,0)) as IPD_Cash_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1,i.twdisc_amount,0)) as IPD_Credit_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0,i.twdisc_amount,0)) as IPD_Package_Bill_amount

		FROM (((invoice_med_master m join inv_med_item i on m.id=i.inv_med_id) 
		left join patient_master p on m.patient_id=p.id)
		left join (Select org.id,ins.short_name,org.ipd_id ,org.case_type
					from  organization_case_master org join  hc_insurance ins 
							on org.insurance_id=ins.id) o1 ON m.ipd_id=o1.ipd_id AND o1.case_type=1 and m.ipd_id>0)
		left join (Select org.id,ins.short_name,org.ipd_id ,org.case_type
					from  organization_case_master org join  hc_insurance ins 
							on org.insurance_id=ins.id) o2 ON m.case_id=o2.id AND o2.case_type=0 and m.ipd_id=0
		where ".$sql_where."    group by inv_date,m.id with ROLLUP";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		//echo $sql;
		
		$content='<p>'.$Report_Heading.'</p>';
		$content.='<table border="1" cellspacing=0 style="width:100%">';

		$content.='<thead><tr>
					<th >#</th>
					<th>Date</th>
					<th width="120px">Invoice ID</th>
					<th width="150px">Patients Name</th>
					<th>Net Amt.</th>
					<th>OPD Cash</th>
					<th>IPD Cash Amount</th>
					<th>Org.Name</th>
					<th>OPD Org.</th>
					<th>IPD Cr Hospital</th>
					<th>IPD Cr Pkg. Hospital</th>
					<th>Tot. Cr Hospital</th>
				 </tr></thead><tbody>';
		$sr_no=0;
		$grand_date_tot_row=0;
		$grand_tot_row=0;
		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			
			if($row->inv_date=='')
			{
				$content.='<tr>
							<td >#</td>
							<td colspan="3"><b>Grand Total : </b></td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
							<td > </td>
							<td align="right">'.$row->OPD_Org_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Credit_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Package_Bill_amount.'</td>
							<td align="right">'.$grand_date_tot_row.'</td>
						</tr>';
			}elseif($row->id=='')
			{
				$content.='<tr>
							<td>#</td>
							<td colspan="3"><i>Total Date : '.$row->inv_date_str.'</i></td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
							<td > </td>
							<td align="right">'.$row->OPD_Org_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Credit_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Package_Bill_amount.'</td>
							<td align="right">'.$grand_tot_row.'</td>
						</tr>';
				$grand_date_tot_row+=$grand_tot_row;
				$grand_tot_row=0;
			}else{
				$tot_row=$row->OPD_Org_Bill_amount+$row->IPD_Credit_Bill_amount+$row->IPD_Package_Bill_amount;
				$grand_tot_row+=$tot_row;

				$content.='<tr>
							<td  >'.$sr_no.'</td>
							<td>'.$row->inv_date_str.'</td>
							<td width="120px">'.$row->inv_med_code.'</td>
							<td width="150px">'.$row->inv_name.'<br>'.$row->patient_code.'<br>'.$row->ipd_code.'</td>
							<td align="right">'.$row->tnet_amount.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
							<td align="left">'.$row->Ins_name.'</td>
							<td align="right">'.$row->OPD_Org_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Credit_Bill_amount.'</td>
							<td align="right">'.$row->IPD_Package_Bill_amount.'</td>
							<td align="right">'.$tot_row.'</td>
						</tr>';
			}
		}
		
		$content.='</tbody>
		<tfoot>
			<tr>
				<th >#</th>
				<th>Date</th>
				<th width="120px">Invoice ID</th>
				<th width="150px">Patients Name</th>
				<th>Net Amt.</th>
				<th>OPD Cash</th>
				<th>IPD Cash Amount</th>
				<th>Org.Name</th>
				<th>OPD Org.</th>
				<th>IPD Cr Hospital</th>
				<th>IPD Cr Pkg. Hospital</th>
				<th>Tot. Cr Hospital</th>
			</tr>
		</tfoot>
		</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
	
			
	
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			ExportExcel($content,'Med_Sale_day0001');
		}
		
		//echo $content;

		
	}

	public function Report_2_short_data($sale_date_range,$bill_type)
	{
		$sql_where=" m.net_amount is not null ";

		$minRange = $sale_date_range;
		
		$sql_where.=" and m.inv_date = '".$minRange."' ";

		$Filter_type="ALL";

		if($bill_type==1){
			$sql_where.=" and (m.ipd_id=0 and m.case_id=0 )";
			$Filter_type="OPD CASH";
		}elseif($bill_type==2){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=0 )";
			$Filter_type="IPD CASH";
		}elseif($bill_type==3){
			$sql_where.=" and (m.ipd_id=0 and m.case_id>1 )";
			$Filter_type="OPD Org.";
		}elseif($bill_type==4){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1 )";
			$Filter_type="IPD Cr";
		}elseif($bill_type==5){
			$sql_where.=" and (m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0 )";
			$Filter_type="IPD Pkg Cr";
		}elseif($bill_type==6){
			$sql_where.=" and ((m.ipd_id>0 and m.ipd_credit=1) or (m.ipd_id=0 and m.case_id>1 ))";
			$Filter_type="Hospital Cr";
		}
		
		$Report_Heading="Date (YYYY-MM-DD) :".$minRange."   / Data Filter :".$Filter_type;

		$sql="select count(DISTINCT m.id) as No_invoice,
		count( DISTINCT  m.patient_id) as No_Patients,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		Sum(m.net_amount) as tnet_amount,
		sum(if(m.ipd_id=0 and m.case_id=0,m.net_amount,0)) as OPD_Cash_Bill_amount,
		sum(if(m.ipd_id=0 and m.case_id>1,m.net_amount,0)) as OPD_Org_Bill_amount,

		sum(if(m.ipd_id>0 and m.ipd_credit=0,m.net_amount,0)) as IPD_Cash_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1,m.net_amount,0)) as IPD_Credit_Bill_amount,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0,m.net_amount,0)) as IPD_Package_Bill_amount,
		
		sum(if(m.ipd_id=0 and m.case_id=0,1,0)) as OPD_Cash_Bill_invoice,
		sum(if(m.ipd_id=0 and m.case_id>1,1,0)) as OPD_Org_Bill_invoice,

		sum(if(m.ipd_id>0 and m.ipd_credit=0,1,0)) as IPD_Cash_Bill_invoice,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=1,1,0)) as IPD_Credit_Bill_invoice,
		sum(if(m.ipd_id>0 and m.ipd_credit=1 and m.ipd_credit_type=0,1,0)) as IPD_Package_Bill_invoice
		
		from invoice_med_master m  
				
		where ".$sql_where." group by m.inv_date ";

		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		//echo $sql;
		
		$content='<p>'.$Report_Heading.'</p>';
		$content.='<table border="1" cellspacing=0 style="width:100%">';

		$content.='<thead><tr>
					<th width="20px" >Sr.No.</th>
					<th>Date</th>
					<th >No. of Invoice</th>
					<th width="200px">Invoice Type</th>
					<th >CASH/Direct</th>
					<th>Credit to Hospital</th>
				</tr></thead><tbody>';
			
		foreach($med_inv_total as $row)
		{
			$content.='<tr>
							<td width="20px" >1</td>
							<td >'.$row->inv_date_str.'</td>
							<td align="right">'.$row->OPD_Cash_Bill_invoice.'</td>
							<td align="center" width="200px">OPD CASH</td>
							<td align="right">'.$row->OPD_Cash_Bill_amount.'</td>
							<td align="right"> </td>
						</tr>';
			
			$content.='<tr>
						<td width="20px" >2</td>
						<td align="right">'.$row->inv_date_str.'</td>
						<td align="right">'.$row->IPD_Cash_Bill_invoice.'</td>
						<td align="center" width="200px">IPD CASH</td>
						<td align="right">'.$row->IPD_Cash_Bill_amount.'</td>
						<td align="right"> </td>
					</tr>';
			
			$content.='<tr>
					<td width="20px" >3</td>
					<td >'.$row->inv_date_str.'</td>
					<td align="right">'.$row->OPD_Org_Bill_invoice.'</td>
					<td align="center" width="200px">OPD Org.</td>
					<td align="right"></td>
					<td align="right">'.$row->OPD_Org_Bill_amount.' </td>
				</tr>';
			
			$content.='<tr>
				<td width="20px" >4</td>
				<td >'.$row->inv_date_str.'</td>
				<td align="right">'.$row->IPD_Credit_Bill_invoice.'</td>
				<td align="center" width="200px">IPD Credit</td>
				<td align="right"></td>
				<td align="right">'.$row->IPD_Credit_Bill_amount.' </td>
			</tr>';
		
			$content.='<tr>
				<td >5</td>
				<td >'.$row->inv_date_str.'</td>
				<td align="right">'.$row->IPD_Package_Bill_invoice.'</td>
				<td align="right" width="200px">IPD Credit Pkg.</td>
				<td align="center"></td>
				<td align="right">'.$row->IPD_Package_Bill_amount.' </td>
			</tr>';

			$total_Cash_Bill_amount=$row->OPD_Cash_Bill_amount+$row->IPD_Cash_Bill_amount;
			$total_Credit_Bill_amount=$row->OPD_Org_Bill_amount+$row->IPD_Credit_Bill_amount+$row->IPD_Package_Bill_amount;

			$content.='<tr>
				<td colspan="2" align="right">Total</td>
				<td align="right">'.$row->No_invoice.'</td>
				<td align="right" width="200px"></td>
				<td align="right">'.$total_Cash_Bill_amount.'</td>
				<td align="right">'.$total_Credit_Bill_amount.' </td>
			</tr>';
	
		}
		
		$content.='</tbody>		
		</table>';
		
		//load mPDF library
		$this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

		$this->m_pdf->pdf->SetWatermarkText(M_store);
		$this->m_pdf->pdf->showWatermarkText = true;

		

		$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";

		$filepath=$file_name;

		//generate the PDF from the given html
		$this->m_pdf->pdf->WriteHTML($content);
 
		//download it.
		$this->m_pdf->pdf->Output($filepath,"I");
		//echo $content;
		
		
	}

	//IPD Discharge Report

	public function ipd_discharge()
    {
		$this->load->view('Medical/Report/IPD_Discharge_Report');
	}


	public function Report_6_data($sale_date_range,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where=" m.ipd_id>0 and p.ipd_status=1   " ;
		
		$where.=" and  p.discharge_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="	SELECT p.ipd_code,p.p_code,p.p_fname,
				DATE_FORMAT(p.register_date,'%d-%m-%Y') as ipd_admit_date_str,
				DATE_FORMAT(p.discharge_date,'%d-%m-%Y') as ipd_discharge_date_str,
				p.admit_type,				
				sum(if(m.ipd_credit=1 and m.ipd_credit_type=1,m.net_amount,0)) as IPD_Credit_Amount,
				sum(if(m.ipd_credit=1 and m.ipd_credit_type=0,m.net_amount,0)) as IPD_Package_Amount,
				sum(if(m.ipd_credit=0,m.net_amount,0)) as IPD_CASH_Amount,
				pay_his.Total_Paid_Amount as Paid_Amount
				FROM (invoice_med_master m join v_ipd_list p on m.ipd_id=p.id )
				LEFT JOIN 
						(SELECT SUM(if(h.credit_debit=0,h.amount,h.amount*-1)) AS Total_Paid_Amount,
						h.ipd_id FROM payment_history_medical h WHERE  h.Customerof_type=2
						GROUP BY h.ipd_id) AS pay_his ON p.id=pay_his.ipd_id 
				where ".$where." group by m.ipd_id ";
		$query = $this->db->query($sql);
		$stock_list= $query->result();

		//echo $sql;

		$content="<style> 
				@page { sheet-size: A4-L; }
		</style>";
		
		$content.='<table border="1" cellspacing=0 width="100%"  >
						<tr>
							<th align="center">IPD Code</th>
							<th align="center" >Patient Code</th>
							<th align="center">Patient Name</th>
							<th align="center">Admit dt.</th>
							<th align="center">Discharge Dt.</th>
							<th align="center">TPA Name</th>
							<th align="center">Cash</th>
							<th align="center">Credit</th>
							<th align="center">Package</th>
							<th align="center">Paid Amt.</th>
						</tr>';
		
		$Head_Content="";									
		foreach ($stock_list as $row) 
		{
			$content.=  '<tr>';
			$content.=  '	<td>'.$row->ipd_code.'</td>';
			$content.=  '	<td >'.$row->p_code.'</td>';
			$content.=  '	<td >'.$row->p_fname.'</td>';
			$content.=  '	<td >'.$row->ipd_admit_date_str.'</td>';
			$content.=  '	<td >'.$row->ipd_discharge_date_str.'</td>';
			$content.=  '	<td >'.$row->admit_type.'</td>';
			$content.=  '	<td align="right">'.$row->IPD_CASH_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->IPD_Credit_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->IPD_Package_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->Paid_Amount.'</td>';
			$content.=  '</tr>';
			
		}
		
		$content.= '</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
	
			
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medical');
		}
		
	}
	

	//Daily Cash Received List
	
	public function Report_Payment_Recieved()
	{
		
		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();

		$sql="select * from payment_mode where id in (1,2) ";
        $query = $this->db->query($sql);
		$data['paymodelist']= $query->result();
		
		$this->load->view('Medical/Report/Med_Payment_Recevie',$data);
	}
	
	public function Report_Payment_Recieved_data($sale_date_range,$emp_name_id=0,$output=0)
	{
		$sql_where=" 1=1 ";

		$rangeArray = explode("S",$sale_date_range);
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
				
		$sql="SELECT p.id,
				if(p.Customerof_type=1,u.p_code,if(p.Customerof_type =3,m.inv_med_code,i.ipd_code)) AS UHID_IPD,
				if(p.Customerof_type=1,u.p_fname,if(p.Customerof_type =3,m.inv_name,i.p_fname)) AS NAME_PERSON,
				
				if(p.Customerof_type IN (1,3) AND p.payment_mode=1 ,if(p.credit_debit=0,p.amount,p.amount*-1),0) AS OPD_Amt_CASH,
				if(p.Customerof_type IN (1,3) AND p.payment_mode=2,if(p.credit_debit=0,p.amount,p.amount*-1),0) AS OPD_Amt_BANK,
				
				if(p.Customerof_type IN (2) AND p.payment_mode=1,if(p.credit_debit=0,p.amount,p.amount*-1),0) AS IPD_Amt_CASH,
				if(p.Customerof_type IN (2) AND p.payment_mode=2,if(p.credit_debit=0,p.amount,p.amount*-1),0) AS IPD_Amt_BANK,
				p.payment_date,p.credit_debit,p.update_by,p.update_by_id
				FROM ((payment_history_medical p Left JOIN patient_master u ON p.Customerof_id=u.id AND p.Customerof_type=1)
				Left JOIN 
					(SELECT ipd_master.id,ipd_master.ipd_code,patient_master.p_fname 
					from ipd_master JOIN  patient_master ON ipd_master.p_id=patient_master.id ) i ON p.ipd_id=i.id AND p.Customerof_type=2)
				LEFT JOIN invoice_med_master m ON p.Medical_invoice_id=m.id
				where ".$sql_where." order by p.id";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		
		$content='';
		
		$content.='<table border="1" cellspacing=0 style="width:800px">';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Payment Date</th>
					<th width="150px">UHID/IPD/Inv.ID</th>
					<th width="120px">Name of Person</th>
					<th>OPD Cash</th>
					<th>OPD Bank</th>
					<th>IPD Cash</th>
					<th>IPD BANK</th>
					<th>Update By</th>
				 </tr></thead><tbody>';
		
		$sr_no=0;
		$OPD_Amt_CASH=0;
		$OPD_Amt_BANK=0;
		$IPD_Amt_CASH=0;
		$IPD_Amt_BANK=0;
		$Total_Amt=0;

		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;

			$OPD_Amt_CASH+=$row->OPD_Amt_CASH;
			$OPD_Amt_BANK+=$row->OPD_Amt_BANK;
			$IPD_Amt_CASH+=$row->IPD_Amt_CASH;
			$IPD_Amt_BANK+=$row->IPD_Amt_BANK;
						
			$content.='<tr>
						<td  width="50px">'.$row->id.'</td>
						<td>'.$row->payment_date.'</td>
						<td width="120px">'.$row->UHID_IPD.'</td>
						<td width="150px">'.$row->NAME_PERSON.'</td>
						<td align="right">'.$row->OPD_Amt_CASH.'</td>
						<td align="right">'.$row->OPD_Amt_BANK.'</td>
						<td align="right">'.$row->IPD_Amt_CASH.'</td>
						<td align="right">'.$row->IPD_Amt_BANK.'</td>
						<td align="right">'.$row->update_by.'</td>
					</tr>';
		}

		$content.='<tr>
						<td  width="50px">#</td>
						<td> </td>
						<td width="120px"></td>
						<td width="150px">Total Amt.</td>
						<td align="right">'.$OPD_Amt_CASH.'</td>
						<td align="right">'.$OPD_Amt_BANK.'</td>
						<td align="right">'.$IPD_Amt_CASH.'</td>
						<td align="right">'.$IPD_Amt_BANK.'</td>
						<td ></td>
					</tr>';
		$content.='</tbody></table>';

		$GTotal=$OPD_Amt_CASH+$OPD_Amt_BANK+$IPD_Amt_CASH+$IPD_Amt_BANK;

		$content.='Total Amount : '.$GTotal;
		
				
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
	
			
	
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
			
		}else{
			ExportExcel($content,'Med_Sale_day0001');
		}
		
		//echo $content;
	
	}
  
	public function Report_3()
	{
		$this->load->view('Medical/Report/Med_Report_3');
	}
	
	public function Report_3_data($sale_date_range,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="Select t.CGST_per,sum(t.qty) as t_qty,
				sum(t.CGST) as TCGST,
				t.SGST_per,
				sum(t.SGST) as TSGST,
				sum(t.CGST+t.SGST) as TGST,
				sum(t.TaxableAmount) as TaxableAmount,
				sum(t.twdisc_amount) as Amount
				from invoice_med_master m join inv_med_item t
				on m.id=t.inv_med_id
				where ".$sql_where." group by t.CGST_per with ROLLUP";
		
		$query = $this->db->query($sql);
        $med_inv_total= $query->result();
		//echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%" >';

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>CGST Per</th>
					<th>T. CGST</th>
					<th>SGST Per</th>
					<th>T. SGST</th>
					<th>Total GST</th>
					<th>Taxable Amount</th>
					<th>Amount</th>
					<th>Item Qty</th>
				</tr></thead><tbody>';
		
		$sr_no=0;
		foreach($med_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			
			if($row->CGST_per=='')
			{
				$content.='<tr>
							<td width="50px">#</td>
							<td ><b>Grand Total : </b></td>
							<td align="right">'.$row->TCGST.'</td>
							<td align="right"></td>
							<td align="right">'.$row->TSGST.'</td>
							<td align="right">'.$row->TGST.'</td>
							<td align="right">'.$row->TaxableAmount.'</td>
							<td align="right">'.$row->Amount.'</td>
							<td align="right">'.$row->t_qty.'</td>
							</tr>';
			}else{
				$content.='<tr>
							<td  width="50px">'.$sr_no.'</td>
							<td>'.$row->CGST_per.'</td>
							<td align="right">'.$row->TCGST.'</td>
							<td align="right">'.$row->SGST_per.'</td>
							<td align="right">'.$row->TSGST.'</td>
							<td align="right">'.$row->TGST.'</td>
							<td align="right">'.$row->TaxableAmount.'</td>
							<td align="right">'.$row->Amount.'</td>
							<td align="right">'.$row->t_qty.'</td>
						</tr>';
			}
		}
		
		$content.='</tbody></table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
			
	
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Med_Sale_day0001');
		}
		
		//echo $content;
	
	}
	
	
	public function Report_4()
	{
		$this->load->view('Medical/Report/Med_Report_4');
	}
	
	public function Report_4_data($sale_date_range,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where=" m.ipd_id>0 and p.ipd_status=1   " ;
		
		$where.=" and  p.discharge_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="select m.id,m.ipd_id,m.inv_med_code,m.ipd_code, m.patient_id,m.inv_name,m.patient_code,
				m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,p.ins_company_name,

				sum(if(m.ipd_credit=1 and m.ipd_credit_type=1,m.net_amount,0)) as IPD_Credit_Amount,
				sum(if(m.ipd_credit=1 and m.ipd_credit_type=0,m.net_amount,0)) as IPD_Package_Amount,
				sum(if(m.ipd_credit=0,m.net_amount,0)) as IPD_CASH_Amount,
				
				sum(m.net_amount) as IPD_Total_Amount
				from invoice_med_master m join v_ipd_list p on m.ipd_id=p.id 	
				where ".$where." group by m.ipd_id,m.id with ROLLUP";
		$query = $this->db->query($sql);
		$stock_list= $query->result();

		//echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%"  >
						<tr>
							<th colspan="1" align="center">IPD Code</th>
							<th colspan="1" align="center" >Patient Code</th>
							<th colspan="2" align="center">Patient Name</th>
							<th colspan="3" align="center">TPA Name</th>
						</tr>
						<tr>
							<th></th>
							<th align="center">Inv.No.</th>
							<th align="center">Inv.Date</th>
							<th align="center">Cash</th>
							<th align="center">Credit</th>
							<th align="center">Package</th>
							<th align="center">Total</th>
						</tr>';
		
		$Head_Content="";									
		foreach ($stock_list as $row) 
		{
			if($row->id=='')
			{
				$content.=  '<tr>';
				$content.=  '<td colspan="3">IPD Total</td>';
				$content.=  '	<td align="right">'.$row->IPD_CASH_Amount.'</td>';
				$content.=  '	<td align="right">'.$row->IPD_Credit_Amount.'</td>';
				$content.=  '	<td align="right">'.$row->IPD_Package_Amount.'</td>';
				$content.=  '	<td align="right">'.$row->IPD_Total_Amount.'</td>';
				$content.=  '</tr>';
			}else{
				if($Head_Content<>$row->ipd_code)
				{
					$content.=  '<tr><td colspan="7"></td></tr>';
					
					$content.=  '<tr>';
					$content.=  '';
					$content.=  '	<td colspan="1">'.$row->ipd_code.'</td>';
					$content.=  '	<td colspan="1">'.$row->patient_code.'</td>';
					$content.=  '	<td colspan="2">'.$row->inv_name.'</td>';
					$content.=  '	<td colspan="3">'.$row->ins_company_name.'</td>';
					$content.=  '</tr>';
					$Head_Content=$row->ipd_code;
				}
			
					$content.=  '<tr>';
					$content.=  '<td></td>';
					$content.=  '	<td >'.$row->inv_med_code.'</td>';
					$content.=  '	<td >'.$row->inv_date_str.'</td>';
					$content.=  '	<td align="right">'.$row->IPD_CASH_Amount.'</td>';
					$content.=  '	<td align="right">'.$row->IPD_Credit_Amount.'</td>';
					$content.=  '	<td align="right">'.$row->IPD_Package_Amount.'</td>';
					$content.=  '	<td align="right">'.$row->IPD_Total_Amount.'</td>';
					$content.=  '</tr>';
			
			}
			
		}
		
		$content.= '</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
	
			
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medical');
		}
		
	}
	
	//IPD Credit Bills
	
	public function Report_IPD_Credit_data($sale_date_range,$billtype=0,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];

		$where=" m.ipd_id>0 and p.ipd_status=1   " ;
		
		$where.=" and  p.discharge_date between '".$minRange."' and '".$maxRange. "'";
		
		if($billtype==0)
		{
			
		}elseif($billtype==1)
		{
			$where.=" and  m.ipd_credit=0 ";
		}elseif($billtype==2)
		{
			$where.=" and  m.ipd_credit=1 and m.ipd_credit_type=1 ";
		}elseif($billtype==3)
		{
			$where.=" and  m.ipd_credit=1 and m.ipd_credit_type=0 ";
		}else{
			$where.=" 1<>1";
		}
		
		$sql="select m.id,m.ipd_id,m.inv_med_code,m.ipd_code, m.patient_id,m.inv_name,m.patient_code,
				m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,p.ins_company_name,
				if(m.ipd_credit=0,'CASH',if(m.ipd_credit_type=1,'Credit','Package')) bill_type,
				sum(m.net_amount) as IPD_Total_Amount
				from invoice_med_master m join v_ipd_list p on m.ipd_id=p.id 	
				where ".$where." group by m.ipd_id,m.id with ROLLUP";
		$query = $this->db->query($sql);
		$stock_list= $query->result();

		echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%"   >
						<tr>
							<th  align="center">IPD Code</th>
							<th  align="center" >Patient Code</th>
							<th  align="center">Patient Name</th>
							<th  align="center">TPA Name</th>
						</tr>
						<tr>
							<th align="center">Inv.No.</th>
							<th align="center">Inv.Date</th>
							<th align="center">Amount</th>
							<th align="center">Type</th>
						</tr>';
		
		$Head_Content="";

		$stock_list_count=count($stock_list);
		$i=0;
		foreach ($stock_list as $row) 
		{

			if($row->id=='')
			{
				if($stock_list_count==$i+1) {
					$content.=  '<tr><td colspan="4"></td></tr>';
					$content.=  '<tr>';
					$content.=  '<td colspan="2">Grand  Total</td>';
					$content.=  '<td align="right">'.$row->IPD_Total_Amount.'</td>';
					$content.=  '<td ></td>';
					$content.=  '</tr>';
				}else{
					$content.=  '<tr>';
					$content.=  '<td colspan="2">Total</td>';
					$content.=  '<td align="right">'.$row->IPD_Total_Amount.'</td>';
					$content.=  '<td ></td>';
					$content.=  '</tr>';
				}
			
			}else{
				if($Head_Content<>$row->ipd_code)
				{
					$content.=  '<tr><td colspan="7"></td></tr>';
					$content.=  '<tr>';
					$content.=  '	<td >'.$row->ipd_code.'</td>';
					$content.=  '	<td >'.$row->patient_code.'</td>';
					$content.=  '	<td >'.$row->inv_name.'</td>';
					$content.=  '	<td >'.$row->ins_company_name.'</td>';
					$content.=  '</tr>';
					$Head_Content=$row->ipd_code;
				}
			
					$content.=  '<tr>';
					$content.=  '	<td >'.$row->inv_med_code.'</td>';
					$content.=  '	<td >'.$row->inv_date_str.'</td>';
					$content.=  '	<td align="right">'.$row->IPD_Total_Amount.'</td>';
					$content.=  '<td>'.$row->bill_type.'</td>';
					$content.=  '</tr>';
			
			}
		
			$i=$i+1;
		}
		
		$content.= '</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(M_store);
			$this->m_pdf->pdf->showWatermarkText = true;
		
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medical');
		}
	}

	
	
	public function purchase_invoice_report()
	{
		$sql="select * from med_supplier order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$this->load->view('Medical/Report/Purchase_Invoice',$data);
	}
	
	public function purchase_invoice_data($sale_date_range,$supplier_id,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and p.date_of_invoice between '".$minRange."' and '".$maxRange. "'";
		
		if($supplier_id>0)
		{
			$sql_where.=" and s.sid= '".$supplier_id."' ";
		}
		
		$sql="select p.id,p.Invoice_no,p.date_of_invoice,p.sid,s.name_supplier,s.short_name,s.gst_no,
			sum(if(item_return=0,t.net_amount,t.net_amount*-1)) as tamount,count(t.id) as no_item,
			p.Taxable_Amt,(p.CGST_Amt+p.SGST_Amt) as gst,
			
			SUM(if(t.CGST_per=0,t.CGST,0)) AS CGST_per_0,
			SUM(if(t.SGST_per=0,t.SGST,0)) AS SGST_per_0,
			SUM(if(t.SGST_per=0,t.taxable_amount,0)) AS TotGST_per_0,
			
			SUM(if(t.CGST_per=2.5,t.CGST,0)) AS CGST_per_2_5,
			SUM(if(t.SGST_per=2.5,t.SGST,0)) AS SGST_per_2_5,
			SUM(if(t.SGST_per=2.5,t.taxable_amount,0)) AS TotGST_per_5,
			
			SUM(if(t.CGST_per=6,t.CGST,0)) AS CGST_per_6,
			SUM(if(t.SGST_per=6,t.SGST,0)) AS SGST_per_6,
			SUM(if(t.SGST_per=6,t.taxable_amount,0)) AS TotGST_per_12,
			
			SUM(if(t.CGST_per=9,t.CGST,0)) AS CGST_per_9,
			SUM(if(t.SGST_per=9,t.SGST,0)) AS SGST_per_9,
			SUM(if(t.SGST_per=9,t.taxable_amount,0)) AS TotGST_per_18,
			
			SUM(if(t.CGST_per=14,t.CGST,0)) AS CGST_per_14,
			SUM(if(t.SGST_per=14,t.SGST,0)) AS SGST_per_14,
			SUM(if(t.SGST_per=14,t.taxable_amount,0)) AS TotGST_per_28

			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			left join purchase_invoice_item t on p.id=t.purchase_id
			where ".$sql_where." group by p.id 	order by p.id";
		
		$query = $this->db->query($sql);
        $pur_inv_total= $query->result();
		
		$content='<table border="1" width="100%" cellpadding="3">';

		$xcontent_head='';

		if($output==1)
			{
			$xcontent_head='	<td align="right">TotGST_per_0</td>
								<td align="right">CGST_per_0</td>
								<td align="right">SGST_per_0</td>
								<td align="right">TotGST_per_5</td>
								<td align="right">CGST_per_2_5</td>
								<td align="right">SGST_per_2_5</td>
								<td align="right">TotGST_per_12</td>
								<td align="right">CGST_per_6</td>
								<td align="right">SGST_per_6</td>
								<td align="right">TotGST_per_18</td>
								<td align="right">CGST_per_9</td>
								<td align="right">SGST_per_9</td>
								<td align="right">TotGST_per_28</td>
								<td align="right">CGST_per_14</td>
								<td align="right">SGST_per_14</td>';

			}

		$content.='<thead><tr>
					<th width="50px">#</th>
					<th>Invoice ID</th>
					<th>Supplier</th>
					<th>Date</th>
					<th>Amount</th>
					<th>R Amount</th>
					<th>GST</th>
					'.$xcontent_head.'
				 </tr></thead><tbody>';
		$sr_no=0;
		$tamt=0;
		$tramt=0;
		$tgst=0;
		
		foreach($pur_inv_total as $row)
		{
			$sr_no=$sr_no+1;
			$tamt=$tamt+$row->tamount;
			$tramt=$tramt+round($row->tamount);
			$tgst=$tgst+round($row->gst);

			$xcontent='';

			if($output==1)
			{
			$xcontent='			<td align="right">'.round($row->TotGST_per_0).'</td>
								<td align="right">'.round($row->CGST_per_0).'</td>
								<td align="right">'.round($row->SGST_per_0).'</td>
								<td align="right">'.round($row->TotGST_per_5).'</td>
								<td align="right">'.round($row->CGST_per_2_5).'</td>
								<td align="right">'.round($row->SGST_per_2_5).'</td>
								<td align="right">'.round($row->TotGST_per_12).'</td>
								<td align="right">'.round($row->CGST_per_6).'</td>
								<td align="right">'.round($row->SGST_per_6).'</td>
								<td align="right">'.round($row->TotGST_per_18).'</td>
								<td align="right">'.round($row->CGST_per_9).'</td>
								<td align="right">'.round($row->SGST_per_9).'</td>
								<td align="right">'.round($row->TotGST_per_28).'</td>
								<td align="right">'.round($row->CGST_per_14).'</td>
								<td align="right">'.round($row->SGST_per_14).'</td>';
			}
						
			$content.='<tr>
					<td  width="50px">'.$sr_no.'</td>
					<td>'.$row->Invoice_no.'</td>
					<td >'.$row->name_supplier.'</td>
					<td align="right">'.$row->date_of_invoice.'</td>
					<td align="right">'.$row->tamount.'</td>
					<td align="right">'.round($row->tamount).'</td>
					<td align="right">'.round($row->gst).'</td>
					'.$xcontent.'

				</tr>';
		}

		if($output==1)
			{
			$xcontent_total='<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>
								<td align="right"></td>';
			}
		
		$content.='</tbody>
						<tfoot>
							<tr>
								<th width="50px">#</th>
								<th></th>
								<th></th>
								<th></th>
								<th align="right">'.$tamt.'</th>
								<th align="right">'.$tramt.'</th>
								<th align="right">'.$tgst.'</th>
								'.$xcontent_total.'
							</tr>
						</tfoot>
		</table>';
		
		//echo $content;
		if($output==0)
		{
			create_report_pdf_landscape($content,'Med_Invoice');
		}else{
			ExportExcel($content,'Report_Medical');
		}
	
	}

	public function purchase_invoice_data_pdf($sale_date_range,$supplier_id,$output=0)
	{
		$sql_where=" 1=1 ";
		
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$sql_where.=" and p.date_of_invoice between '".$minRange."' and '".$maxRange. "'";
		
		if($supplier_id>0)
		{
			$sql_where.=" and s.sid= '".$supplier_id."' ";
		}
		
		$sql="select p.id,trim(s.name_supplier) AS name_supplier,p.sid,p.Invoice_no, 
				date_format(p.date_of_invoice,'%d-%m-%Y')AS str_date_of_invoice, 
				p.date_of_invoice, s.short_name,s.gst_no,
				SUM(round(p.T_Net_Amount)) as tamount, 
				sum(p.Taxable_Amt) AS Taxable_Amt, 
				SUM(p.CGST_Amt+p.SGST_Amt) as gst 
				from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			where ".$sql_where." group by trim(s.name_supplier),p.date_of_invoice,p.id   WITH rollup";
				
		$query = $this->db->query($sql);
        $pur_inv_total= $query->result();
		$content='';

		//$content=$sql;

		$content.='<table border="1" width="100%" cellpadding="2" cellspacing="0">';

		$xcontent_head='';
				
		$head_content="";
		$Total_Amount_Round=0;
		$Total_Grand_Amount_Round=0;

		foreach($pur_inv_total as $row)
		{
			
				if($row->id!='' && $row->name_supplier!='' && $row->date_of_invoice!='')
				{
					if($head_content!=$row->name_supplier)
					{
						$sr_no=0;
						$Total_Amount_Round=0;
						$content.='<tr>
								<td colspan="8">_</td>
							</tr>';
						$content.='<tr>
								<td colspan="8">'.$row->name_supplier.' / GST No. '.$row->gst_no.'   </td>
							</tr>';
						$content.='<tr>
								<td colspan="2"></td>
								<td>#</td>
								<td>Purchase. Inv. ID</td>
								<td>Inv. Date</td>
								<td>Taxable Amt</td>
								<td>Tax Amt</td>
								<td>Inv. Amount</td>
							</tr>';
					}
					$sr_no=$sr_no+1;
					
					$content.='<tr>
								<td colspan="2"></td>
								<td>'.$sr_no.'</td>
								<td>'.$row->Invoice_no.'</td>
								<td>'.$row->str_date_of_invoice.'</td>
								<td align="right" >'.$row->Taxable_Amt.'</td>
								<td align="right" >'.$row->gst.'</td>
								<td align="right" >'.$row->tamount.'</td>
							</tr>';
								
				}elseif($row->id=='' && $row->name_supplier=='' && $row->date_of_invoice==''){
					$content.='<tr>
								<td colspan="2"></td>
								<td>#</td>
								<td></td>
								<td>Grand Total</td>
								<td align="right" >'.$row->Taxable_Amt.'</td>
								<td align="right" >'.$row->gst.'</td>
								<td align="right" >'.$row->tamount.'</td>
							</tr>';
				}elseif($row->id=='' && $row->date_of_invoice==''){
					$content.='<tr>
								<td colspan="2"></td>
								<td>#</td>
								<td colspan="2">Total of : '.$row->name_supplier.'</td>
								<td align="right" >'.$row->Taxable_Amt.'</td>
								<td align="right" >'.$row->gst.'</td>
								<td align="right" >'.$row->tamount.'</td>
							</tr>';
		
				}
			
			$head_content=$row->name_supplier;
		}
		
		$content.='</table>';
		
		//echo $content;
		if($output==0)
		{
			$this->load->library('m_pdf');
			
			$file_name='Report-MedicalBill-'.date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medical');
		}

	}


	//GST Report

	public function Report_5()
	{
		$this->load->view('Medical/Report/Med_Report_5');
	}
	
	public function Report_5_data($sale_date_range,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where="  m.inv_date between '".$minRange."' and '".$maxRange. "'";
		
		$sql="select m.inv_med_code,Date_Format(m.inv_date,'%d-%m-%Y') as Inv_Date,m.inv_name,
				get_med_type(m.ipd_id,m.ipd_credit,m.ipd_credit_type,m.case_id,m.case_credit) as Invoice_type,
				m.ipd_code,p.p_code,o.case_id_code,
				count(i.id) as No_of_Items,
				sum(i.qty) as No_of_Qty,

				round(sum(i.twdisc_amount),0) as Total_Amount,

				round(sum(if(i.CGST_per=2.5,i.TaxableAmount,0)),0) as Sale_5_Amount,
				sum(if(i.CGST_per=2.5,i.CGST,0)) as CGST_2_5,
				sum(if(i.CGST_per=2.5,i.SGST,0)) as SGST_2_5,

				round(sum(if(i.CGST_per=5,i.TaxableAmount,0)),0) as Sale_10_Amount,
				sum(if(i.CGST_per=5,i.CGST,0)) as CGST_5,
				sum(if(i.CGST_per=5,i.SGST,0)) as SGST_5,

				round(sum(if(i.CGST_per=6,i.TaxableAmount,0)),0) as Sale_12_Amount,
				sum(if(i.CGST_per=6,i.CGST,0)) as CGST_6,
				sum(if(i.CGST_per=6,i.SGST,0)) as SGST_6,

				round(sum(if(i.CGST_per=9,i.TaxableAmount,0)),0) as Sale_18_Amount,
				sum(if(i.CGST_per=9,i.CGST,0)) as CGST_9,
				sum(if(i.CGST_per=9,i.SGST,0)) as SGST_9,

				round(sum(if(i.CGST_per=14,i.TaxableAmount,0)),0) as Sale_28_Amount,
				sum(if(i.CGST_per=14,i.CGST,0)) as CGST_14,
				sum(if(i.CGST_per=14,i.SGST,0)) as SGST_14,

				round(sum(if(i.CGST_per=0,i.TaxableAmount,0)),0) as Sale_0_Amount,

				round(sum(i.TaxableAmount),0) as Total_TaxableAmount

				from ((invoice_med_master m join inv_med_item i on m.id=i.inv_med_id) 
				left join patient_master p on m.patient_id=p.id)
				left join organization_case_master o on m.case_id=o.id
				where ".$where." group by m.id 	";

		$query = $this->db->query($sql);
		$stock_list= $query->result();

		//echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%"  >
						<tr>
							<th align="center">Invoice ID</th>
							<th align="center" >Inv.Date</th>
							<th align="center">inv.Name</th>
							<th align="center">Invoice Type</th>
							<th align="center">Tot.Amt.</th>
							<th align="center">Tot.Tax.Amt</th>
							<th align="center">Tax.Amt.5%</th>
							<th align="center">CGST 2.5%</th>
							<th align="center">SCGT 2.5%</th>
							<th align="center">Tax.Amt.12%</th>
							<th align="center">CGST 6%</th>
							<th align="center">SCGT 6%</th>
							<th align="center">Tax.Amt.18%</th>
							<th align="center">CGST 9%</th>
							<th align="center">SCGT 9%</th>
							<th align="center">Tax.Amt.28%</th>
							<th align="center">CGST 14%</th>
							<th align="center">SCGT 14%</th>
							<th align="center">Tax.Amt.0%</th>
						</tr>';
		
		$Head_Content="";									
		foreach ($stock_list as $row) 
		{
			$content.=  '<tr>';
			$content.=  '	<td align="left">'.$row->inv_med_code.'</td>';
			$content.=  '	<td align="left">'.$row->Inv_Date.'</td>';
			$content.=  '	<td align="left">'.$row->inv_name.'</td>';
			$content.=  '	<td align="left">'.$row->Invoice_type.'</td>';
			$content.=  '	<td align="right">'.$row->Total_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->Total_TaxableAmount.'</td>';
			$content.=  '	<td align="right">'.$row->Sale_5_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->CGST_2_5.'</td>';
			$content.=  '	<td align="right">'.$row->SGST_2_5.'</td>';
			$content.=  '	<td align="right">'.$row->Sale_12_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->CGST_6.'</td>';
			$content.=  '	<td align="right">'.$row->SGST_6.'</td>';
			$content.=  '	<td align="right">'.$row->Sale_18_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->CGST_9.'</td>';
			$content.=  '	<td align="right">'.$row->SGST_9.'</td>';
			$content.=  '	<td align="right">'.$row->Sale_28_Amount.'</td>';
			$content.=  '	<td align="right">'.$row->CGST_14.'</td>';
			$content.=  '	<td align="right">'.$row->SGST_14.'</td>';
			$content.=  '	<td align="right">'.$row->Sale_0_Amount.'</td>';
			$content.=  '</tr>';
		}
		
		$content.= '</table>';
		
		ExportExcel($content,'Report_Medical_gst_invoice');
		
		
	}
  
	//Bank Ledger Account

	public function Report_7()
	{
		$sql="select * from med_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$sql="select * from bank_account_master";
		$query = $this->db->query($sql);
		$data['bank_account_data']= $query->result();

		$this->load->view('Medical/Report/Med_Report_7',$data);
	}

	public function Report_7_data($sale_date_range,$supplier_id,$account_id,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where="  l.tran_date between '".$minRange."' and '".$maxRange. "'";

		if($supplier_id>0)
		{
			$where.=" and m.sid=$supplier_id ";
		}

		if($account_id>0)
		{
			$where.=" and b.bank_id=$account_id ";
		}
		
		$sql="SELECT b.bank_account_name,m.name_supplier,
		l.tran_date,l.credit_debit,l.tran_desc,
		Date_Format(l.tran_date,'%d-%m-%Y') as tran_date_str,
		if(l.credit_debit=0,l.amount,0) AS cr_amt,
		if(l.credit_debit=1,l.amount,0) AS dr_amt,l.id
		FROM  (bank_account_master b JOIN med_supplier_ledger l
		ON l.bank_id=b.bank_id AND b.bank_id>1)
		JOIN med_supplier m ON l.supplier_id=m.sid
		where ".$where." order by l.tran_date, l.id 	";

		$query = $this->db->query($sql);
		$stock_list= $query->result();

		//echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%"  >
						<tr>
							<th align="center">Led. ID</th>
							<th align="center" >Date</th>
							<th align="center">Bank/Cash</th>
							<th align="center">Ledger</th>
							<th align="center">Cr.Amt.</th>
							<th align="center">Dr.Amt</th>
						</tr>';
		
		$Head_Content="";
		$total_cr_amt=0;
		$total_dr_amt=0;

		foreach ($stock_list as $row) 
		{
			$content.=  '<tr>';
			$content.=  '	<td align="left">'.$row->id.'</td>';
			$content.=  '	<td align="left">'.$row->tran_date_str.'</td>';
			$content.=  '	<td align="left">'.$row->bank_account_name.'</td>';
			$content.=  '	<td align="left">'.$row->name_supplier.'</td>';
			$content.=  '	<td align="right">'.$row->cr_amt.'</td>';
			$content.=  '	<td align="right">'.$row->dr_amt.'</td>';
			$content.=  '</tr>';
			$total_cr_amt+=$row->cr_amt;
			$total_dr_amt+=$row->dr_amt;
		}
			
			$content.=  '<tr>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left">Total</th>';
			$content.=  '	<th align="right">'.$total_cr_amt.'</th>';
			$content.=  '	<th align="right">'.$total_dr_amt.'</th>';
			$content.=  '</tr>';
		
		$content.= '</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');
	
			$file_name='Report_Medical_Ledger_'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medical_Ledger_'.date('d-m-Y'));
		}
		
		
		
	}

	//Lost Medicine Data
	
	public function Lost_medicine()
    {
		$this->load->view('Medical/Stock/Lost_medicine');
	}

	public function Lost_medicine_data($sale_date_range,$output=0)
	{
		$rangeArray = explode("S",$sale_date_range);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where="  a.date_of_adjust between '".$minRange."' and '".$maxRange. "'";

		
		$sql="SELECT a.id,Date_Format(a.date_of_adjust,'%d-%m-%Y') as adjust_date_str,a.update_by,
				i.item_code,i.Item_name,a.qty,
				i.packing,i.purchase_price,i.purchase_unit_rate,(i.purchase_unit_rate*a.qty) AS cost,
				(i.selling_unit_rate*a.qty) AS sale_value
				FROM purchase_item_stock_adjust a JOIN purchase_invoice_item i ON a.item_id=i.id
				where ".$where." order by a.date_of_adjust, a.id 	";

		$query = $this->db->query($sql);
		$stock_list= $query->result();

		//echo $sql;
		
		$content='<table border="1" cellspacing=0 width="100%"  >
						<tr>
							<th align="center">ID</th>
							<th align="center" >Date</th>
							<th align="center">Item Name</th>
							<th align="center">Qty</th>
							<th align="center">Cost</th>
							<th align="center">Sale Value</th>
						</tr>';
		
		$Head_Content="";
		$total_cost=0;
		$total_sale_value=0;

		foreach ($stock_list as $row) 
		{
			$content.=  '<tr>';
			$content.=  '	<td align="left">'.$row->id.'</td>';
			$content.=  '	<td align="left">'.$row->adjust_date_str.'</td>';
			$content.=  '	<td align="left">'.$row->Item_name.'</td>';
			$content.=  '	<td align="left">'.$row->qty.'</td>';
			$content.=  '	<td align="right">'.$row->cost.'</td>';
			$content.=  '	<td align="right">'.$row->sale_value.'</td>';
			$content.=  '</tr>';
			$total_cost+=$row->cost;
			$total_sale_value+=$row->sale_value;
		}
			
			$content.=  '<tr>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left"></th>';
			$content.=  '	<th align="left">Total</th>';
			$content.=  '	<th align="right">'.$total_cost.'</th>';
			$content.=  '	<th align="right">'.$total_sale_value.'</th>';
			$content.=  '</tr>';
		
		$content.= '</table>';
		
		if($output==0)
		{
			//load mPDF library
			$this->load->library('m_pdf');
	
			$file_name='Report_Medicine_Lost_'.date('Ymdhis').".pdf";
	
			$filepath=$file_name;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'Report_Medicine_Lost_'.date('d-m-Y'));
		}
		
		
		
	}
  
	
}