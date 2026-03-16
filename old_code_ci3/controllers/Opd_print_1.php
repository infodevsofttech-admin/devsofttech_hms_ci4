<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opd_print extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}


public function opd_PDF_print($opdid)
	{
		$sql="SELECT o.*,DATE_FORMAT(o.apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(o.apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date,
		(case o.payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str ,
		d.doc_sign
		from  opd_master o JOIN doctor_master d ON o.doc_id=d.id  where opd_id=".$opdid;
        $query = $this->db->query($sql);
		$opd_master= $query->result();
		
		$data['opd_master']=$opd_master;

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
		$patient_master= $query->result();
		
		$data['patient_master']=$patient_master;

        $sql="select *,date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date
        from opd_master where p_id=".$opd_master[0]->p_id." and opd_id < ".$opdid." 
        order by opd_id desc limit 1";
        $query = $this->db->query($sql);
		$old_opd= $query->result();

		$data['old_opd']=$old_opd;

		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();

		$data['insurance']=$insurance;
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();

			$data['case_master']=$case_master;
		}

		$old_uhid='';
		if($patient_master[0]->udai<>"")
	    {
	    	$old_uhid= ' /'.$patient_master[0]->udai;
		}
		$data['old_uhid']=$old_uhid;
		
	    $exp_date="";

	    if($opd_master[0]->opd_fee_type=='3'){
                if(count($old_opd)>0){
                  $exp_date= 'OPD Start Date : '.$old_opd[0]->s_opd_date.'<br>Valid Upto :'.$old_opd[0]->opd_Exp_Date.'<br>';
                }
         }else{
        		$exp_date="<b>Valid Upto : ".$opd_master[0]->opd_Exp_Date ."</b>";
		}
		
		$data['exp_date']=$exp_date;

		$content='
				<table width="100%" border="0">
						<tr>
							<td width="33.3%">
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								P.No. :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'							
							</td>
							<td width="33.3%">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Sr No.: '.$opd_master[0]->opd_no .' <br/>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'
							</td>
							<td width="33.3%">
								<B>Consultant : Dr. '.$opd_master[0]->doc_name .'</B><br/>
							</td>
						</tr>
					<table>	
					<hr/>';
		//echo $content;
		create_OPD_Letter_pdf($content,'opd_parchi-'.$opdid.'-'.date('Yms'));

	}

	

	public function opd_Cont_print($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $opd_master= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
        $patient_master= $query->result();

        $sql="select *,date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date
        from opd_master where p_id=".$opd_master[0]->p_id." and opd_id < ".$opdid." 
        order by opd_id desc limit 1";
        $query = $this->db->query($sql);
		$old_opd= $query->result();

		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();
		}

		$old_uhid='';
		if($patient_master[0]->udai<>"")
	    {
	    	$old_uhid= ' /'.$patient_master[0]->udai;
	    }

	    $exp_date="";

	    if($opd_master[0]->opd_fee_type=='3'){
                if(count($old_opd)>0){
                  $exp_date= 'OPD Start Date : '.$old_opd[0]->s_opd_date.'<br>Valid Upto :'.$old_opd[0]->opd_Exp_Date.'<br>';
                }
         }else{
        		$exp_date="<b>Valid Upto : ".$opd_master[0]->opd_Exp_Date ."</b>";
        } 

		$content='<hr/>
					<table width="100%" border="1">
									<tr>
											<td width="33.3%">
													UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
													Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
													'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
													Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b><br>
											</td>
											<td width="33.3%">
													OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
													OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
													<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'
											</td>
											<td width="33.3%">
													Sr No.: '.$opd_master[0]->opd_no .' <br/>
													P.No. :'.$patient_master[0]->mphone1 .'<br>
													Address : '.$patient_master[0]->add1.','.$patient_master[0]->city.'<br>
											</td>
									</tr>
							</table> <hr/>';

		//echo $content;
		create_OPD_cont_pdf($content,'opd_parchi-'.$opdid.'-'.date('Yms'));

    }

    public function invoice_print_pdf($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $opd_master= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age 
		from patient_master where id='".$opd_master[0]->p_id."' ";

        $query = $this->db->query($sql);
        $patient_master= $query->result();
		
		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();
		}

		// Content Start  From here

		$content='<h3 align="center">OPD Charges Invoice</h3>';

        $itemlist='Items:';

		$content.='	<table width="100%">
						<tr>
							<td width="50%">
								To <br/> 
								UHID : '.$patient_master[0]->p_code.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
								Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b> P.No. :'.$patient_master[0]->mphone1 .'<br>
								Address : '.$patient_master[0]->add1.','.$patient_master[0]->city.'<br>
							</td>
							<td width="30%">
								Invoice No.  : '.$opd_master[0]->opd_code.'<br>
								Date : <strong>'.$opd_master[0]->str_apointment_date.'</strong><br>
							</td>
							<td width="20%">
								
							</td>
						</tr>
					<table>	';
		$content.='<table border="0.5" cellspacing="1" cellpadding="1.5">
			<tr>
				<th >Date </th>
				<th  align="center">Charge Name</th>
				<th  align="center">Doctor Name</th>
				<th  align="center">Description</th>
				<th  align="center">Amount</th>
			</tr>';
			
		$content.= '<tr>';
					$content.= '<td>'.$opd_master[0]->str_apointment_date.'</td>';
					$content.= '<td>Consultation  Fee</td>';
					$content.= '<td>Dr.'.$opd_master[0]->doc_name.'</td>';
					$content.= '<td>'.$opd_master[0]->opd_fee_desc.'</td>';
					$content.= '<td align="right">Rs.'.$opd_master[0]->opd_fee_gross_amount.'</td>';
					$content.= '</tr>';

		if($opd_master[0]->opd_discount>0 )
		{
			$content.= '<tr>';
					$content.= '<td>#</td>';
					$content.= '<td> Deduction </td>';
					$content.= '<td colspan="2">'.$opd_master[0]->opd_disc_remark.'</td>';
					$content.= '<td align="right"> Rs.'.$opd_master[0]->opd_discount.'</td>';
					$content.= '</tr>';

		}

		$content.= '<tr>';
					$content.= '<td>#</td>';
					$content.= '<td> </td>';
					$content.= '<td colspan="2">Net Amount</td>';
					$content.= '<td align="right"> Rs.'.$opd_master[0]->opd_fee_amount.'</td>';
					$content.= '</tr>';
		
		$content.='</table>';

		// Content End Here

		if($opd_master[0]->payment_id>0 )
		{
				$content.='<strong>Payment No. :</strong>'.$opd_master[0]->payment_id.'<br>';
		}

		$content.='	
		<br/>
		<table width="100%">
			<tr>
				<td width="66.3%">
					<b>Amount in Words : </b>Rs. '.number_to_word($opd_master[0]->opd_fee_amount).'
				</td>
				<td width="33.3%">
													
				</td>
			</tr>
			<tr>
				<td width="66.3%">
					<b>Prepared By :</b>'.$opd_master[0]->prepared_by.'
				</td>
				<td width="33.3%" align="center">
					<b>Signature</b>								
				</td>
			</tr>
		<table>	';

		
		$bar_content=$patient_master[0]->p_code.':OPD-'.$opd_master[0]->opd_code.':T'.date('dmYhms');
		//echo $content;
		$this->load->library("Pdf");
		create_Invoice_pdf($content,$bar_content,'opd_invoice-'.$opdid.'-'.date('Yms'));
	}
    
}
