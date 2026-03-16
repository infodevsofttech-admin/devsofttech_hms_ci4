<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report2 extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
    }
	
	function report_Diagnosis()
	{
		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from hc_item_type where is_ipd_opd in (0,1)";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();
		
		$this->load->view('Report/rep_diagnosis',$data);
	}
	
	function report_Diagnosis_CASH()
	{
		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from hc_item_type where is_ipd_opd in (0,1)";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();
		
		$this->load->view('Report/rep_diagnosis_cash',$data);
	}
	
	
	function report_Diagnosis_EMP()
	{
		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();
		
		$sql="select * from hc_item_type where is_ipd_opd in (0,1)";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();
		
		$this->load->view('Report/rep_diagnosis_empwise',$data);
	}
	
	function report_Diagnosis_EMP_2()
	{
		$sql="select * from users where active=1 order by first_name";
        $query = $this->db->query($sql);
        $data['emplist']= $query->result();
		
		$sql="select * from hc_item_type where is_ipd_opd in (0,1)";
		$query = $this->db->query($sql);
        $data['item_type_list']= $query->result();
		
		$this->load->view('Report/rep_diagnosis_empwise_2',$data);
	}
	
	function ipd_invoice_print($ipdno,$print=0) {
		
		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
        $ipdmaster= $query->result();
		
		$pno=$ipdmaster[0]->p_id;
		$discount=$ipdmaster[0]->Discount;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $person_info= $query->result();
		
		$total_med=0.00;
		$total_charges=0.00;
		
		if($ipdmaster[0]->case_id>0)
		{
			$sql="select * from organization_case_master where id='".$ipdmaster[0]->case_id."'";
			$query = $this->db->query($sql);
			$orgcase= $query->result();

			$ins_comp_id=$orgcase[0]->insurance_id;
			$inc_card_id=$orgcase[0]->insurance_card_id;

			$sql="select * from hc_insurance_card where id='".$inc_card_id."'";
			$query = $this->db->query($sql);
			$hc_insurance_card= $query->result();

			$sql="select * from hc_insurance where id='".$ins_comp_id."'";
			$query = $this->db->query($sql);
			$insurance= $query->result();
		}
		
		$sql="select * from v_ipd_bill_invoice v
				where v.ipd_id=".$ipdno."  ";

		$query = $this->db->query($sql);
        $showinvoice= $query->result();
		
		$sql="select sum(net_amount) as tnet_amount from invoice_med_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$inv_med_total= $query->result();
		
		$total_med=$inv_med_total[0]->tnet_amount;
		
		$sql="select sum(net_amount) as tnet_amount from invoice_master where  payment_status=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$inv_charge_total= $query->result();
		
		$total_charges=$inv_charge_total[0]->tnet_amount;
		
		$sql="select *,case payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' else 'Other' end  as pay_mode from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $ipd_payment= $query->result();
		
		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as t_ipd_pay from ipd_payment where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $ipd_payment_total= $query->result();
		
		$total_payment=$ipd_payment_total[0]->t_ipd_pay;
		
		$net_amount=($total_charges+$total_med)-$discount;
		
		$balance=$total_payment-$net_amount;
		
		$inv_total=array(
		'total_med' => $total_med,
		'total_charges' => $total_charges,
		'total_payment' => $total_payment,
		'ipd_id' => $ipdno,
		'discount' => $discount,
		'balance' => $balance,
		'net_amount' => $net_amount
		);
		
		// make string html string
		
		$htmlcontent='<table border="1" cellpadding="0" cellspacing="0" style="width:100%" >
			<tr>
				<th style="width: 50px;">#</th>
				<th style="width: 300px;">Description</th>
				<th style="width: 100px;">Unit</th>
				<th style="width: 100px;">Rate</th>
				<th style="width: 100px;">Amount</th>
			</tr>';
		
			$srno=1;
			$headdesc='';
			foreach($showinvoice as $row)
				{ 
					if($headdesc!=$row->Charge_type)
					{
						$htmlcontent.= '<tr>';
						$htmlcontent.= '<td></td>';
						$htmlcontent.= '<td><b>'.$row->Charge_type.'</b></td>';
						$htmlcontent.= '<td colspan="3"></td></tr>';
						$headdesc=$row->Charge_type;
					}	
					$htmlcontent.= '<tr>';
					$htmlcontent.= '<td>'.$srno.'</td>';
					$htmlcontent.= '<td>'.$row->idesc.'</td>';
					$htmlcontent.= '<td>'.$row->no_qty.'</td>';
					$htmlcontent.= '<td align="right">'.$row->item_rate.'</td>';
					$htmlcontent.= '<td align="right">'.$row->amount.'</td>';
					$srno=$srno+1;
					$htmlcontent.= '</tr>';
				}
			

			// Total Show 
			$htmlcontent.='<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th align="right" style="text-align:right">'.$inv_total['total_charges'].'</th>
			</tr>';
			
			if($ipdmaster[0]->Discount>0) { 
			$htmlcontent.='<tr>
				<th style="width: 10px">#</th>
				<th>Discount Remark :</br>'.$ipdmaster[0]->Discount_Remark.'</th>
				<th></th>
				<th>Discount </th>
				<th align="right" style="text-align:right">'.$ipdmaster[0]->Discount.'</th>
			</tr>';
			}
			
			$htmlcontent.='<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th>Net Amount</th>
				<th align="right" style="text-align:right">'.$inv_total['net_amount'].'</th>
			</tr>';
			
			$htmlcontent.='<tr>
				<th style="width: 10px">#</th>
				<th >
				Payment Recd.<br/>';
			
				$i=1;
				foreach($ipd_payment as $row)
				{ 
					$i=$i+1;
					$htmlcontent.= '['.$row->id.':'.$row->pay_mode.':'.$row->amount.'] /';
					if($i & 1)
					{
						$htmlcontent.= '<br/>';
					}
				}
					
				$htmlcontent.='</th>
				<th></th>
				<th></th>
				<th align="right" style="text-align:right">'.$ipd_payment_total[0]->t_ipd_pay.'</th>
			</tr>';
			
			
			$htmlcontent.='<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th>Balance</th>
				<th align="right" style="text-align:right">'.$inv_total['balance'].'</th>
			</tr>';
			
		$htmlcontent.='</table>';
		
		$content=$htmlcontent;
		$this->load->library('m_pdf');

		
		$file_name="Report-".date('Ymdhis').".pdf";
		$filepath=$file_name;
		$this->m_pdf->pdf->WriteHTML($content);
		$this->m_pdf->pdf->Output($filepath,"I");
  
		
    }
	
	public function report_Diagnosis_total($opd_date_range,$doc_id,$Diagnosis_id)
		{
			$sql_f_all = "select m.refer_by_other,y.group_desc,t.item_name,count(t.id) as No_test,
			sum(t.item_qty) as No_qty,
			sum(Round(if(m.invoice_status=1,(t.item_amount-(t.item_amount*m.discount_amount/m.total_amount)),0),2)) as Total_Amount,
			sum(if(m.invoice_status=1,t.item_amount,0)) as Total_Act_Amount	";

			$sql_from=" from invoice_master m join invoice_item t join hc_item_type y
					on m.id=t.inv_master_id and t.item_type=y.itype_id and y.is_ipd_opd in (0,1) 
					and m.invoice_status=1";

			$sql_where=" Where 1=1 ";
			
			$sql_group_by=" group by m.refer_by_id,m.refer_by_other,t.item_id ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
						
			if($doc_id>0)
			{
				$doc_id=str_replace('S',',',$doc_id);
				
				$sql_where.=" and m.refer_by_id  in (".$doc_id.")";
			}
			
			if($Diagnosis_id>0)
			{
				$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
				$sql_where.=" and t.item_type in (".$Diagnosis_id.")";
			}
			
			$sql_order=" Order by m.refer_by_other,y.group_desc,t.item_name";
			
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="1000">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th width="50px"></th>';
			$table_head.='<th align="center" width="50px"></th>';
			$table_head.='<th align="center" >Test Name</th>';
			$table_head.='<th align="center" >No. of Test</th>';
			$table_head.='<th align="center" >Amount</th>';
			$table_head.='<th align="center" >Cost Amt</th>';
			$table_head.='</tr>';
						
			$table_footer='';
			
			
					
					$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order);
					$rowdata= $query->result();
					
					//echo $sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order;
					
					$table_body='';
					$table_Doctor_Total=0.00;
					$table_Diagnosis_Head_Total=0.00;
					$table_Grand_Total=0.00;

					$table_Doctor_act_Total=0.00;
					$table_Diagnosis_act_Head_Total=0.00;
					$table_Grand_act_Total=0.00;

					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					$doc_name_head='';
					$Diagnosis_head='';
									
					
					for( $i = 0; $i<count($rowdata); $i++)
					{
						if($rowdata[$i]->refer_by_other<>$doc_name_head)
						{
							$table_body.='<tr style="background-color:#FFFF00;color:#000000;">';
							$table_body.='<td colspan="6">Refer By : '.$rowdata[$i]->refer_by_other.'</td>';
							$table_body.='</tr>';
						}
						
						$doc_name_head=$rowdata[$i]->refer_by_other;
						
						if($rowdata[$i]->group_desc<>$Diagnosis_head)
						{
					
							$table_body.='<tr style="background-color:#FFFF00;color:#FF00FF;">';
							$table_body.='<td></td>';
							$table_body.='<td colspan="5">'.$rowdata[$i]->group_desc.'</td>';
							$table_body.='</tr>';
						}
						
						$Diagnosis_head=$rowdata[$i]->group_desc;
												
						$table_body.='<tr>';
						$table_body.='<td></td>';
						$table_body.='<td></td>';
						$table_body.='<td align="left">'.$rowdata[$i]->item_name.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->No_qty.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->Total_Amount.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->Total_Act_Amount.'</td>';
						$table_body.='</tr>';
						
						$table_Doctor_Total=$table_Doctor_Total+$rowdata[$i]->Total_Amount;
						$table_Diagnosis_Head_Total=$table_Diagnosis_Head_Total+$rowdata[$i]->Total_Amount;
						$table_Grand_Total=$table_Grand_Total+$rowdata[$i]->Total_Amount;

						$table_Doctor_act_Total=$table_Doctor_act_Total+$rowdata[$i]->Total_Act_Amount;
						$table_Diagnosis_act_Head_Total=$table_Diagnosis_act_Head_Total+$rowdata[$i]->Total_Act_Amount;
						$table_Grand_act_Total=$table_Grand_act_Total+$rowdata[$i]->Total_Act_Amount;
						
						if($Diagnosis_id<1)
						{
						if($i<count($rowdata)-1)
							{
								if($rowdata[$i+1]->group_desc<>$Diagnosis_head)
								{
									$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_act_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
									$table_Diagnosis_act_Head_Total=0.00;
								}
							}else{
								$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_act_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
									$table_Diagnosis_act_Head_Total=0.00;
							}
						
						}
						
						if($i<count($rowdata)-1)
						{
							if($rowdata[$i+1]->refer_by_other<>$doc_name_head)
							{
								$table_body.='<tr>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td>Total Doctor Head</td>';
								$table_body.='<td align="right">'.$table_Doctor_Total.'</td>';
								$table_body.='<td align="right">'.$table_Doctor_act_Total.'</td>';
								$table_body.='</tr>';
								
								$table_Doctor_Total=0.00;
								$table_Doctor_act_Total=0.00;
								
							}
						}else{
								$table_body.='<tr>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td>Total Doctor Head</td>';
								$table_body.='<td align="right">'.$table_Doctor_Total.'</td>';
								$table_body.='<td align="right">'.$table_Doctor_act_Total.'</td>';
								$table_body.='</tr>';
								
								$table_Doctor_Total=0.00;
								$table_Doctor_act_Total=0.00;
						}
						
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right">'.$table_Grand_Total.'</td>';
					$table_footer.='<td align="right">'.$table_Grand_act_Total.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					$content=$data_show;
					$this->load->library('m_pdf');
					$file_name="Report-".date('Ymdhis').".pdf";
					$filepath=$file_name;
					$this->m_pdf->pdf->WriteHTML($content);
					$this->m_pdf->pdf->Output($filepath,"I");
		
		}
	
		
	public function report_Diagnosis_Emp_total($opd_date_range,$emp_name_id,$Diagnosis_id)
		{
			$sql_f_all = "select m.invoice_code,p.p_code,p.p_fname,y.group_desc,t.item_name,t.item_qty,Round(if(m.invoice_status=1,(t.item_amount-(t.item_amount*m.discount_amount/m.total_amount)),0),2) as item_amount,
				ph.RPaidAmount,ph.gupdate_by,ph.gupdate_by_id ";

			$sql_from=" from ((invoice_master m join invoice_item t join hc_item_type y join patient_master p
				on m.id=t.inv_master_id and t.item_type=y.itype_id and p.id=m.attach_id )
				 join (select p.payof_type,p.payof_id,p.payof_code,sum(if(p.credit_debit=0,p.amount,p.amount*-1)) as RPaidAmount,
				group_concat(distinct p.update_by) as  gupdate_by,group_concat(distinct p.update_by_id) as  gupdate_by_id
				from payment_history p where p.payof_type=2
				group by p.payof_type,p.payof_id) as ph on m.id=ph.payof_id)  ";

			$sql_where=" Where 1=1 ";
			
			$sql_group_by="  ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
						
			if($emp_name_id<>0)
			{
				$emp_name_id=str_replace('S',',',$emp_name_id);
				
				$sql_where.=" and m.prepared_by_id  in (".$emp_name_id.")";
			}
			
			if($Diagnosis_id>0)
			{
				$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
				
				$sql_where.=" and t.item_type in (".$Diagnosis_id.") ";
			}
			
			$sql_order=" Order by ph.gupdate_by,y.group_desc,t.item_name";
			
				
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="100%">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th width="50px"></th>';
			$table_head.='<th align="center" width="50px"></th>';
			$table_head.='<th align="center" >Patient Code/Name</th>';
			$table_head.='<th align="center" >Test Name</th>';
			$table_head.='<th align="center" >Amount</th>';
			$table_head.='</tr>';

			$table_footer='';

				$sql="SELECT m.invoice_code,p.p_code,p.p_fname,y.group_desc,t.item_name,t.item_qty, Round((t.item_amount-(t.item_amount*m.discount_amount/m.total_amount)),2) as item_amount, 
						m.prepared_by,m.prepared_by_id 
						FROM ((invoice_master m join invoice_item t ON m.id=t.inv_master_id AND m.invoice_status=1) 
						join hc_item_type y ON t.item_type=y.itype_id)
						JOIN patient_master p ON m.attach_id=p.id
						$sql_where
						GROUP BY m.prepared_by_id,y.itype_id,t.id 
						Order By m.prepared_by,y.group_desc,m.invoice_code";
						
				$query = $this->db->query($sql);
				$rowdata= $query->result();
					
					$table_body='';
					$table_emp_Total=0.00;
					$table_Diagnosis_Head_Total=0.00;
					$table_Grand_Total=0.00;
										
					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					$emp_name_head='';
					$Diagnosis_head='';
					
					for( $i = 0; $i<count($rowdata); $i++)
					{
						if($rowdata[$i]->prepared_by<>$emp_name_head)
						{
							$table_body.='<tr style="background-color:#FFFF00;color:#000000;">';
							$table_body.='<td colspan="5">Emp.Name : '.$rowdata[$i]->prepared_by.'</td>';
							$table_body.='</tr>';
						}
						
						$emp_name_head=$rowdata[$i]->prepared_by;
						
						if($rowdata[$i]->group_desc<>$Diagnosis_head)
						{
							$table_body.='<tr style="background-color:#FFFF00;color:#FF00FF;">';
							$table_body.='<td></td>';
							$table_body.='<td colspan="4">'.$rowdata[$i]->group_desc.'</td>';
							$table_body.='</tr>';
						}
						
						$Diagnosis_head=$rowdata[$i]->group_desc;
												
						$table_body.='<tr>';
						$table_body.='<td></td>';
						$table_body.='<td align="left">'.$rowdata[$i]->p_code.'/'.$rowdata[$i]->p_fname.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->invoice_code.'</td>';
						$table_body.='<td>'.$rowdata[$i]->item_name.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->item_amount.'</td>';
						$table_body.='</tr>';
						
						$table_emp_Total=$table_emp_Total+$rowdata[$i]->item_amount;
						$table_Diagnosis_Head_Total=$table_Diagnosis_Head_Total+$rowdata[$i]->item_amount;
						$table_Grand_Total=$table_Grand_Total+$rowdata[$i]->item_amount;
						
						if($Diagnosis_id<1)
						{
						if($i<count($rowdata)-1)
							{
								if($rowdata[$i+1]->group_desc<>$Diagnosis_head)
								{
									$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
								}
							}else{
								$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
							}
						
						}
						
						if($i<count($rowdata)-1)
						{
							if($rowdata[$i+1]->prepared_by<>$emp_name_head)
							{
								$table_body.='<tr>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td>Total Emp Head</td>';
								$table_body.='<td align="right">'.$table_emp_Total.'</td>';
								$table_body.='</tr>';
								
								$table_emp_Total=0.00;
								
							}
						}else{
								$table_body.='<tr>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td></td>';
								$table_body.='<td>Total Emp. Head</td>';
								$table_body.='<td align="right">'.$table_emp_Total.'</td>';
								$table_body.='</tr>';
								
								$table_emp_Total=0.00;
						}

					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right">'.$table_Grand_Total.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					$sql_where.=" and (m.payment_part_balance>0 or  m.discount_amount>0)  ";
					//Foot Data Describe Discount
					$sql_f_all_2="select m.invoice_code,p.p_code,p.p_fname,m.discount_amount,m.discount_desc,m.disc_update_by,m.total_amount,
					ph.RPaidAmount,ph.gupdate_by,ph.gupdate_by_id ,m.payment_part_received,m.payment_part_balance";
					
					//echo $sql_f_all_2.$sql_from.$sql_where.'  group by m.id'.$sql_order;
					$query2=$sql_f_all_2.$sql_from.$sql_where.'  group by m.id'.$sql_order;
					$query = $this->db->query($sql_f_all_2.$sql_from.$sql_where.'  group by m.id'.$sql_order);
					$rowfootdata= $query->result();
					
					if(count($rowfootdata)>0)
					{
					
						$data_show.='<br><br><br>';
						
						$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
						$table_head.='<th width="50px">Invoice ID</th>';
						$table_head.='<th align="center" >Patient Code/Name</th>';
						$table_head.='<th align="center" >T Amount</th>';
						$table_head.='<th align="center" >Disc.Amt</th>';
						$table_head.='<th align="center" >Disc.By</th>';
						$table_head.='<th align="center" >Rec.Amt.</th>';
						$table_head.='<th align="center" >Balance</th>';
						$table_head.='</tr>';
						
						$T_Discount_Amt=0.00;
						$T_Balance_Amt=0.00;
						
						$table_body="";
						
						for( $i = 0; $i<count($rowfootdata); $i++)
						{
							$table_body.='<tr>';
							$table_body.='<td>'.$rowfootdata[$i]->invoice_code.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->p_code.'/'.$rowfootdata[$i]->p_fname.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->total_amount.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->discount_amount.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->discount_desc.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->RPaidAmount.'</td>';
							$table_body.='<td>'.$rowfootdata[$i]->payment_part_balance.'</td>';
							$table_body.='</tr>';
							
							$T_Discount_Amt=$T_Discount_Amt+$rowfootdata[$i]->discount_amount;
							$T_Balance_Amt=$T_Balance_Amt+$rowfootdata[$i]->payment_part_balance;
						}
						
						$table_footer='<tr>';
						$table_footer.='<td></td>';
						$table_footer.='<td></td>';
						$table_footer.='<td></td>';
						$table_footer.='<td>'.$T_Discount_Amt.'</td>';
						$table_footer.='<td></td>';
						$table_footer.='<td></td>';
						$table_footer.='<td>'.$T_Balance_Amt.'</td>';
						$table_footer.='</tr>';
						
						
						$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					}
					
					$content=$data_show;
					$this->load->library('m_pdf');

					$file_name="Report-".date('Ymdhis').".pdf";
					$filepath=$file_name;
					$this->m_pdf->pdf->WriteHTML($content);
					$this->m_pdf->pdf->Output($filepath,"I");
		
	}
	
	public function report_Diagnosis_Emp_total_2($opd_date_range,$emp_name_id,$Diagnosis_id)
		{
			$sql_f_all = "select m.invoice_code,p.p_code,p.p_fname,y.group_desc,t.item_name,t.item_qty,
					Round(if(m.invoice_status=1,(t.item_amount-(t.item_amount*m.discount_amount/m.total_amount)),0),2) as item_amount,
					m.invoice_status ";
			$sql_from=" from (invoice_master m join invoice_item t join hc_item_type y join patient_master p
					on m.id=t.inv_master_id and t.item_type=y.itype_id and p.id=m.attach_id  
					and payment_status=1 and m.invoice_status=1)  ";

			$sql_where=" Where 1=1 ";
			
			$sql_group_by="  ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
			
			if($Diagnosis_id>0)
			{
				$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
				
				$sql_where.=" and t.item_type in (".$Diagnosis_id.") ";
			}
			
			$sql_order=" Order by y.group_desc,t.item_name";
			

			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="100%">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th width="50px"></th>';
			$table_head.='<th align="center" width="50px"></th>';
			$table_head.='<th align="center" >Patient Code/Name</th>';
			$table_head.='<th align="center" >Test Name</th>';
			$table_head.='<th align="center" >Amount</th>';
			$table_head.='</tr>';

			$table_footer='';
			
					$query1=$sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order;
					$query = $this->db->query($query1);
					$rowdata= $query->result();
					
					 
					$table_body='';
					$table_emp_Total=0.00;
					$table_Diagnosis_Head_Total=0.00;
					$table_Grand_Total=0.00;
										
					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					$emp_name_head='';
					$Diagnosis_head='';
					
					for( $i = 0; $i<count($rowdata); $i++)
					{
						if($rowdata[$i]->group_desc<>$Diagnosis_head)
						{
							$table_body.='<tr style="background-color:#FFFF00;color:#FF00FF;">';
							$table_body.='<td></td>';
							$table_body.='<td colspan="4">'.$rowdata[$i]->group_desc.'</td>';
							$table_body.='</tr>';
						}
						
						$Diagnosis_head=$rowdata[$i]->group_desc;
												
						$table_body.='<tr>';
						$table_body.='<td></td>';
						$table_body.='<td></td>';
						$table_body.='<td align="left">'.$rowdata[$i]->p_code.'/'.$rowdata[$i]->p_fname.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->invoice_code.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->item_amount.'</td>';
						$table_body.='</tr>';
						
						
						$table_Diagnosis_Head_Total=$table_Diagnosis_Head_Total+$rowdata[$i]->item_amount;
						$table_Grand_Total=$table_Grand_Total+$rowdata[$i]->item_amount;
						
						if($Diagnosis_id<1)
						{
							if($i<count($rowdata)-1)
							{
								if($rowdata[$i+1]->group_desc<>$Diagnosis_head)
								{
									$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
								}
							}else{
								$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
							}
						
						}
						
						
					
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right">'.$table_Grand_Total.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					$content=$data_show;
					$this->load->library('m_pdf');
					
					$file_name="Report-".date('Ymdhis').".pdf";
					$filepath=$file_name;
					$this->m_pdf->pdf->WriteHTML($content);
					$this->m_pdf->pdf->Output($filepath,"I");
					
	}
	
	public function report_Diagnosis_item_count($opd_date_range,$emp_name_id,$Diagnosis_id)
		{
			$sql_f_all = "select m.invoice_code,p.p_code,p.p_fname,y.group_desc,t.item_name,t.item_qty, 
				if(m.invoice_status=1,t.item_amount,0) as item_amount,m.invoice_status ";
			$sql_from=" from (invoice_master m join invoice_item t join hc_item_type y join patient_master p
					on m.id=t.inv_master_id and t.item_type=y.itype_id and p.id=m.attach_id  
					and payment_status=1  and m.invoice_status=1)  ";

			$sql_where=" Where 1=1 ";
			
			$sql_group_by="  ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';
			
			if($Diagnosis_id>0)
			{
				$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
				
				$sql_where.=" and t.item_type in (".$Diagnosis_id.") ";
			}
			
			$sql_order=" Order by y.group_desc,t.item_name";
			
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="100%">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th width="50px"></th>';
			$table_head.='<th align="center" width="50px"></th>';
			$table_head.='<th align="center" >Patient Code/Name</th>';
			$table_head.='<th align="center" >Test Name</th>';
			$table_head.='<th align="center" >Amount</th>';
			$table_head.='</tr>';

			$table_footer='';
			
			// Add a page
			// This method has several options, check the source code documentation for more information.
					
					
					
					$query1=$sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order;
					$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order);
					$rowdata= $query->result();
					
					$table_body='';
					$table_emp_Total=0.00;
					$table_Diagnosis_Head_Total=0.00;
					$table_Grand_Total=0.00;
										
					//echo $sql_f_all.$sql_from.$sql_where.$gwhere.'  '.$sql_order.'<br><hr>';
					$emp_name_head='';
					$Diagnosis_head='';
					
					for( $i = 0; $i<count($rowdata); $i++)
					{
						if($rowdata[$i]->group_desc<>$Diagnosis_head)
						{
							$table_body.='<tr style="background-color:#FFFF00;color:#FF00FF;">';
							$table_body.='<td></td>';
							$table_body.='<td colspan="4">'.$rowdata[$i]->group_desc.'</td>';
							$table_body.='</tr>';
						}
						
						$Diagnosis_head=$rowdata[$i]->group_desc;
												
						$table_body.='<tr>';
						$table_body.='<td></td>';
						$table_body.='<td></td>';
						$table_body.='<td align="left">'.$rowdata[$i]->p_code.'/'.$rowdata[$i]->p_fname.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->invoice_code.'</td>';
						$table_body.='<td align="right">'.$rowdata[$i]->item_amount.'</td>';
						$table_body.='</tr>';
						
						
						$table_Diagnosis_Head_Total=$table_Diagnosis_Head_Total+$rowdata[$i]->item_amount;
						$table_Grand_Total=$table_Grand_Total+$rowdata[$i]->item_amount;
						
						if($Diagnosis_id<1)
						{
							if($i<count($rowdata)-1)
							{
								if($rowdata[$i+1]->group_desc<>$Diagnosis_head)
								{
									$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
								}
							}else{
								$table_body.='<tr>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td></td>';
									$table_body.='<td>Total of '.$Diagnosis_head.'</td>';
									$table_body.='<td align="right">'.$table_Diagnosis_Head_Total.'</td>';
									$table_body.='</tr>';
									
									$table_Diagnosis_Head_Total=0.00;
							}
						
						}
						
						
					
					}
					
					$table_footer='<tr style="background-color:#FFFF00;color:#0000FF;">';
					$table_footer.='<td ><b>Total</b></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right"></td>';
					$table_footer.='<td align="right">'.$table_Grand_Total.'</td>';
					$table_footer.='</tr>';
					
					$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
					
					$content=$data_show;
					$this->load->library('m_pdf');
				
					$file_name="Report-".date('Ymdhis').".pdf";
					$filepath=$file_name;
					$this->m_pdf->pdf->WriteHTML($content);
					$this->m_pdf->pdf->Output($filepath,"I");
					
	}
	
	
	
	
	
	public function report_Diagnosis_total_cash($opd_date_range,$doc_id,$Diagnosis_id,$output=0)
		{
			$sql_f_all = "select m.refer_by_id,d.p_fname,t.item_id,t.item_type,m.refer_by_other, 
						y.group_desc,t.item_name,count(t.id) as No_test,
						sum(t.item_qty) as No_qty,sum(t.item_amount) as Total_Amount,
						Round(sum(t.item_amount*m.net_amount/m.total_amount),2) as net_total";

			$sql_from=" from (invoice_master m join invoice_item t join hc_item_type y
						on m.id=t.inv_master_id and t.item_type=y.itype_id and y.is_ipd_opd in (0,1))
						join doctor_master d on m.refer_by_id=d.id";

			$sql_where=" Where m.payment_mode in (1,2) and m.payment_status=1 and m.invoice_status=1 and refer_by_id>0 ";
			
			$sql_group_by=" group by m.refer_by_id  Asc ,t.item_type Asc,t.item_id  Asc with rollup  ";
			
			$rangeArray = explode("S",$opd_date_range);
			$minRange = str_replace('T',' ',$rangeArray[0]);
			$maxRange = str_replace('T',' ',$rangeArray[1]);
			
			if($minRange==$maxRange)
			{
				$sql_where.=" and Date(m.inv_date) between Date('".$minRange."') and Date('".$maxRange. "')";
			}else{
				$sql_where.=" and m.inv_date between '".$minRange."' and '".$maxRange. "'";
			}
			
			$data_show='<p>Date(YYYY-MM-DD h:m) between '.$minRange.' and '.$maxRange.' </p>';

			if($doc_id>0)
			{
				$doc_id=str_replace('S',',',$doc_id);
				
				$sql_where.=" and m.refer_by_id  in (".$doc_id.")";
			}
			
			if($Diagnosis_id>0)
			{
				$Diagnosis_id=str_replace('S',',',$Diagnosis_id);
				$sql_where.=" and t.item_type in (".$Diagnosis_id.")";
			}
			
			$sql_order=" ";
			
			$table_body='';
			
			$table_start='<table border="1" cellpadding="2" cellspacing="0" width="1000">';
			$table_end='</table>';
			
			$table_head='<tr style="background-color:#FFFF00;color:#0000FF;" >';
			$table_head.='<th width="50px"></th>';
			$table_head.='<th align="center" width="50px"></th>';
			$table_head.='<th align="center" >Test Name</th>';
			$table_head.='<th align="center" >No. of Test</th>';
			$table_head.='<th align="center" >Amount (with Discount Amt.)</th>';
			$table_head.='</tr>';

			$table_footer='';
			
			$query = $this->db->query($sql_f_all.$sql_from.$sql_where.'  '.$sql_group_by.$sql_order);
			$rowdata= $query->result();
			
			for( $i = 0; $i<count($rowdata); $i++)
			{
				if($i==0)
				{
					$table_body.='<tr style="background-color:#FF0000;color:#000000;">';
					$table_body.='<td colspan="5">Dr. '.$rowdata[$i]->p_fname.'</td>';
					$table_body.='</tr>';
					
					$table_body.='<tr style="background-color:#00FF00;color:#FF00FF;">';
					$table_body.='<td></td>';
					$table_body.='<td colspan="4">'.$rowdata[$i]->group_desc.'</td>';
					$table_body.='</tr>';
				}
				
				if($rowdata[$i]->item_id==null)
				{
					if($rowdata[$i]->item_type==null)
					{
						if($rowdata[$i]->refer_by_id==null)
						{
							$table_body.='<tr style="background-color:#FFBBCC;color:#000000;">';
							$table_body.='<td colspan="4">Grand Total of All Doctors</td>';
							$table_body.='<td align="right">'.$rowdata[$i]->net_total.'</td>';
							$table_body.='</tr>';
						}else{
							$table_body.='<tr style="background-color:#FFBB00;color:#000000;">';
							$table_body.='<td colspan="4">Total of : Dr. '.$rowdata[$i]->p_fname.'</td>';
							$table_body.='<td align="right">'.$rowdata[$i]->net_total.'</td>';
							$table_body.='</tr>';
							
							if(count($rowdata)>$i && $rowdata[$i+1]->refer_by_id !=null)
							{
								$table_body.='<tr style="background-color:#FF0000;color:#000000;">';
								$table_body.='<td colspan="5">Dr. '.$rowdata[$i+1]->p_fname.'</td>';
								$table_body.='</tr>';
								
								$table_body.='<tr style="background-color:#00FF00;color:#FF00FF;">';
								$table_body.='<td></td>';
								$table_body.='<td colspan="4">'.$rowdata[$i+1]->group_desc.'</td>';
								$table_body.='</tr>';
							}
						}
						
					}else{
							$table_body.='<tr style="background-color:#FFFF00;color:#FF00FF;">';
							$table_body.='<td></td>';
							$table_body.='<td colspan="3">Total of :'.$rowdata[$i]->group_desc.'</td>';
							$table_body.='<td align="right">'.$rowdata[$i]->net_total.'</td>';
							$table_body.='</tr>';
							
							if(count($rowdata)>$i && $rowdata[$i+1]->refer_by_id <>null)
							{
								$table_body.='<tr style="background-color:#00FF00;color:#FF00FF;">';
								$table_body.='<td></td>';
								$table_body.='<td colspan="4">'.$rowdata[$i+1]->group_desc.'</td>';
								$table_body.='</tr>';
							}
					}
				}else{
					$table_body.='<tr>';
					$table_body.='<td></td>';
					$table_body.='<td></td>';
					$table_body.='<td align="left">'.$rowdata[$i]->item_name.'</td>';
					$table_body.='<td align="right">'.$rowdata[$i]->No_qty.'</td>';
					$table_body.='<td align="right">'.$rowdata[$i]->net_total.'</td>';
					$table_body.='</tr>';
				}
			}
			
			$data_show.=$table_start.$table_head.$table_body.$table_footer.$table_end;
			
			//echo $data_show;
			
			if($output==0)
			{
				$content=$data_show;
				$this->load->library('m_pdf');
				
				$file_name="Report-".date('Ymdhis').".pdf";
				$filepath=$file_name;
				$this->m_pdf->pdf->WriteHTML($content);
				$this->m_pdf->pdf->Output($filepath,"I");
		
			}else{
				
				ExportExcel($data_show,'Diagnosis_Qty_cash'.date('Ymd'));
			}
		}
	
  
  }