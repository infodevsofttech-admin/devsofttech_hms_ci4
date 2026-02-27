<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opd_print extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}


	public function opd_PDF_print($opdid)
	{
		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select *
            from doctor_master 
            where id=$doc_id";
			$query = $this->db->query($sql);
			$doctor_master= $query->result();

			$sql="select d.* ,group_concat(m.SpecName) as SpecName 
			FROM (doctor_master d left JOIN   doc_spec s on d.id =s.doc_id)
			join med_spec m  on s.med_spec_id =m.id 
			WHERE d.id=$doc_id ";
        	$query = $this->db->query($sql);
			$doctor_master= $query->result();

			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}

		$sql="SELECT o.*,DATE_FORMAT(o.apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(opd_book_date,'%d-%m-%Y %H:%i') as  str_opd_book_date ,
		date_format(date_add(o.apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date,
		(case o.payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str ,
		d.doc_sign,DATE_FORMAT(o.last_opdvisit_date,'%d-%m-%Y') as str_last_opdvisit_date,
		d.opd_print_format
		from  opd_master o JOIN doctor_master d ON o.doc_id=d.id  where opd_id=".$opdid;
        $query = $this->db->query($sql);
		$opd_master= $query->result();

		$data['opd_master']=$opd_master;

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age ,
		date_format(last_visit,'%d-%m-%Y') as last_visit_str
		from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
		$patient_master= $query->result();

		$pid=$opd_master[0]->p_id;
		
		$sql="Select count(*) as rec_opd from opd_master where p_id=$pid";
		$query = $this->db->query($sql);
		$no_opd= $query->result();

		$data['patient_master']=$patient_master;

		$total_no_visit=$no_opd[0]->rec_opd+$patient_master[0]->no_of_visit;

        $sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date
        from opd_master where p_id=".$opd_master[0]->p_id." and doc_id=".$opd_master[0]->doc_id." 
		and  apointment_date >= date_add(sysdate(),interval -$no_opd_days day) and opd_fee_type<>3";
		$query = $this->db->query($sql);
		$old_opd= $query->result();

		$data['old_opd']=$old_opd;

		$sql="select * from  hc_insurance  where id=".$opd_master[0]->insurance_id;
		$query = $this->db->query($sql);
		$insurance= $query->result();

		$data['insurance']=$insurance;

		$data['case_id']='';
		
		if($opd_master[0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$opd_master[0]->insurance_case_id;
			$query = $this->db->query($sql);
			$case_master= $query->result();

			$data['case_master']=$case_master;

			if(count($case_master)>0){
				$data['case_id']="Org. Case ID : ".$case_master[0]->case_id_code;
			}
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
			$exp_date="<b>Valid Upto : </b>".$opd_master[0]->opd_Exp_Date ."";
		}

		if($opd_master[0]->last_opdvisit_date=='')
		{
			if($patient_master[0]->last_visit=='')
			{
				$last_opdvisit_date="";
			}else{
				$last_opdvisit_date='<br>Last Visit :'.$patient_master[0]->last_visit_str;
			}
			
			
		}else{
			$last_opdvisit_date='<br>Last Visit :'.$opd_master[0]->str_last_opdvisit_date;
		}

		$data['last_opdvisit_date']=$last_opdvisit_date;
		
		$data['exp_date']=$exp_date;

		$data['doc_info']='Dr. '.$opd_master[0]->doc_name.'<br/>'.nl2br($opd_master[0]->doc_sign);
		$data['SpecName']=$doctor_master[0]->SpecName;
		$data['str_opd_book_date']=$opd_master[0]->str_opd_book_date;

		$content='
				<table width="100%" border="0" style="font-size:10pt;">
						<tr>
							<td width="35%" VALIGN="top" >
								<span style="font-size:12pt;">Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong></span><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								Mob :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
							</td>
							<td width="30%" VALIGN="top">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'<br>
								<b>OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .'] </b>
		
							</td>
							<td width="35%" VALIGN="top">
								<span style="font-size:14pt;"><b>Dr. '.$opd_master[0]->doc_name.'</b></span><br>
									'.$doctor_master[0]->SpecName.'<br>
									'.nl2br($doctor_master[0]->doc_sign).'
									<br>
									<br>No. of Visit : '.$total_no_visit.'<br>
							</td>
						</tr>
					</table>';
		//echo $content;
	

		$data['content']=$content;

		$content_3='
				<table width="100%" style="font-size:10pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
						<tr>
							<td width="60%" VALIGN="top">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								Phone No. :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
							</td>
							<td width="40%" VALIGN="top">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b>
								'.$last_opdvisit_date.'<br>
								No. of Visit : '.$total_no_visit.$exp_date.'
							</td>
						</tr>
					</table>
					';

		$data['content_3']=$content_3;


		$content_4='
				<table width="100%" style="font-size:10pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
						<tr>
							<td width="60%" VALIGN="top">
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								Phone No. :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
							</td>
							<td width="40%" VALIGN="top">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b>
								'.$last_opdvisit_date.'<br>
								No. of Visit : '.$total_no_visit.'<br/>'.$exp_date.'
							</td>
						</tr>
					</table>
					';
		
		$data['content_4']=$content_4;

		$data['pName']=$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname) ;
		$data['pRelative']=$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname) ;
		$data['age_sex']=$patient_master[0]->age.' / '.$patient_master[0]->xgender;
		$data['opd_no']=$opd_master[0]->opd_code;
		$data['opd_date']=$opd_master[0]->str_apointment_date;
		
		$data['opd_fee']= 'Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc ;

		$data['opd_fee_desc']= $opd_master[0]->opd_fee_desc ;
		
		$data['opd_sr_no']=$opd_master[0]->opd_no;

		$data['short_info']='Sr No.: <b>'.$opd_master[0]->opd_no .'</b> 
							<br/>OPD Fee : Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc .' <br>
							UHID : '.$patient_master[0]->p_code.'<br/>
							P Add. :'.$patient_master[0]->add1.','.$patient_master[0]->city.'';

		$data['short_info_1']='Sr No.: <b>'.$opd_master[0]->opd_no .'</b> 
		/ OPD Fee : Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc .'  / 
		UHID : '.$patient_master[0]->p_code.' / <b>No. of Visit</b> : '.$total_no_visit.' <br/>'.$last_opdvisit_date;
		
		$data['uhid_no']=$patient_master[0]->p_code;
		$data['phoneno']=$patient_master[0]->mphone1;
		$data['p_address']=$patient_master[0]->add1.','.$patient_master[0]->city.'';
		$data['opd_fee']= 'Rs.'.$opd_master[0]->opd_fee_amount .' '.$opd_master[0]->opd_fee_desc ;
		$data['total_no_visit']=$total_no_visit;
		$data['exp_date']=$exp_date;

		$data['opd_book_date']=$opd_master[0]->opd_book_date;

		$this->load->library('m_pdf');

        $file_name='opd_parchi-'.$opdid.'-'.date('Ymdhis').".pdf";

        $filepath=$file_name;
		
		if($opd_master[0]->opd_print_format==''){
			$print_content=$this->load->view('dashboard/opd_print_parcha',$data,TRUE);
		}else{
			$print_content=$this->load->view('dashboard/'.$opd_master[0]->opd_print_format,$data,TRUE);
		}
		
		//echo $print_content;

        $this->m_pdf->pdf->WriteHTML($print_content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");

	}

	public function opd_blank_print($opdid)
	{
		$bar_content="";

		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();

		$p_id=$opd_master[0]->p_id;

		$sql="Select count(*) as No_visit from opd_master where p_id=$p_id";
		$query = $this->db->query($sql);
		$no_of_opd_data= $query->result();

		$no_of_visit=$no_of_opd_data[0]->No_visit;

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select *
            from doctor_master 
            where id=$doc_id";
			$query = $this->db->query($sql);
			$doctor_master= $query->result();

			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}


		$sql="SELECT o.*,DATE_FORMAT(o.apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(o.apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date,
		(case o.payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str ,
		d.doc_sign,DATE_FORMAT(o.last_opdvisit_date,'%d-%m-%Y') as str_last_opdvisit_date,
		d.opd_print_format,d.opd_blank_print
		from  opd_master o JOIN doctor_master d ON o.doc_id=d.id  where opd_id=".$opdid;
        $query = $this->db->query($sql);
		$opd_master= $query->result();
		
		$data['opd_master']=$opd_master;

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
		$patient_master= $query->result();
		
		$data['patient_master']=$patient_master;
        
		$sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date 
		from opd_master where p_id=".$opd_master[0]->p_id." and doc_id=".$opd_master[0]->doc_id." 
        	 and  apointment_date >= date_add(sysdate(),interval -$no_opd_days day) and opd_fee_type<>3";
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

		if($opd_master[0]->last_opdvisit_date=='')
		{
			$last_opdvisit_date="";
		}else{
			$last_opdvisit_date='Last Visit :'.$opd_master[0]->str_last_opdvisit_date;
		}
		
		$data['exp_date']=$exp_date;

		$bar_content.=$patient_master[0]->p_code.'/'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname);
		$bar_content.='/'.$opd_master[0]->opd_no.'/'.$opd_master[0]->str_apointment_date.'/'.date('dmYHis');

		$content='
				<table width="100%" style="font-size:12pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
						<tr>
							<td width="400px">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								Phone No. :'.$patient_master[0]->mphone1 .'<br/>
							</td>
							<td width="400px">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'<br>
							</td>
							<td width="300px">
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
								'.$last_opdvisit_date.'<br>
								No. of Visit : '.$no_of_visit.'
							</td>
						</tr>
					</table>
					';
		$data['content']=$content;

		$content_2='
				<table width="100%" style="font-size:10pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
						<tr>
							<td width="300px" style="vertical-align:top;">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								Gender : <b>'.$patient_master[0]->xgender.'</b> / <b>Age : '.$patient_master[0]->age.' </b><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
							</td>
							<td width="300px" style="vertical-align:top;">
								Phone No. :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.'<br>'.$patient_master[0]->city.'
								<br>No. of Visit : '.$no_of_visit.'
								<br>'.$last_opdvisit_date.'
							</td>
							<td width="200px" style="vertical-align:top;">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b>
							</td>
						</tr>
					</table>
					';
		//echo $content;

		$data['content_2']=$content_2;

		$content_3='
				<table width="100%" style="font-size:12pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
						<tr>
							<td width="60%">
								UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
								Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
								Phone No. :'.$patient_master[0]->mphone1 .'<br/>
								Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
							</td>
							<td width="40%">
								Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
								OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
								OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
								<b>Date: '.$opd_master[0]->str_apointment_date .'</b>
								'.$last_opdvisit_date.'<br>
								No. of Visit : '.$no_of_visit.'
							</td>
						</tr>
					</table>
					';

		$data['content_3']=$content_3;

		$content_4='
		<table width="100%" style="font-size:12pt;border-style: inset;border-bottom-width: 0px;border-color: green;">
				<tr>
					<td width="60%">
						UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
						Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
						'.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
						Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
						Phone No. :'.$patient_master[0]->mphone1 .'<br/>
						Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.' ,'.$patient_master[0]->city.'
					</td>
					<td width="40%">
						Sr No.: <b>'.$opd_master[0]->opd_no .'</b> <br/>
						OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
						<b>Date: '.$opd_master[0]->str_apointment_date .'</b>
						'.$last_opdvisit_date.'<br>
						No. of Visit : '.$no_of_visit.'
					</td>
				</tr>
			</table>
			';
			
		$data['content_4']=$content_4;

	
		$data['patient_master']=$patient_master;
		$data['opd_master']=$opd_master;

		$this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetWatermarkText(H_Name);
        //$this->m_pdf->pdf->showWatermarkText = false;

        $file_name='opd_parchi-'.$opdid.'-'.date('Ymdhis').".pdf";

        $filepath=$file_name;
		
		//$print_content=$this->load->view('dashboard/opd_letter_head_print',$data,TRUE);

		$print_content=$this->load->view('dashboard/'.$opd_master[0]->opd_blank_print,$data,TRUE);
		
		//echo $print_content;

        $this->m_pdf->pdf->WriteHTML($print_content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");

	}

	

	public function opd_day_care($opdid)
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

		$sql="select *,date_format(date_add(apointment_date,interval 4 day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date 
			from opd_master where p_id=".$opd_master[0]->p_id." and doc_id=".$opd_master[0]->doc_id." 
        	 and  apointment_date > date_add(sysdate(),interval -5 day) and opd_fee_type<>3";
		$query = $this->db->query($sql);
		$old_opd= $query->result();

		$data['old_opd']=$old_opd;

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

	    
		$content='<hr/>
					<table width="100%" >
									<tr>
											<td width="50%">
													UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
													Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
													'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
													Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b><br>
											</td>
											<td width="50%">
													<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>
													Admit Time: <B>____________________</B><br>
													Discharge Time: ___________________<br>
											</td>
									</tr>
							</table> ';

		//echo $content;
		$data['content']=$content;
		$data['patient_master']=$patient_master;
		$data['opd_master']=$opd_master;

		$this->load->library('m_pdf');

        $file_name='opd_parchi-'.$opdid.'-'.date('Ymdhis').".pdf";

        $filepath=$file_name;
		
		$print_content=$this->load->view('dashboard/day_care_format',$data,TRUE);
		
		//echo $print_content;

        $this->m_pdf->pdf->WriteHTML($print_content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");

    }

	public function opd_Cont_print($opdid)
	{
		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select *
            from doctor_master 
            where id=$doc_id";
			$query = $this->db->query($sql);
			$doctor_master= $query->result();

			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}

		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date,
		(case payment_status when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'Org. Credit' else 'Pending' end) as Payment_type_str 
		from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $opd_master= $query->result();

		$sql="select *,if(gender=1,'M','F') as xgender,
		GET_AGE_2(dob,age,age_in_month,estimate_dob,'".$opd_master[0]->apointment_date."')  AS age from patient_master where id='".$opd_master[0]->p_id."' ";
        $query = $this->db->query($sql);
        $patient_master= $query->result();

        $sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date
        from opd_master where p_id=".$opd_master[0]->p_id." and opd_id < ".$opdid." 
        order by opd_id desc limit 1";
        $query = $this->db->query($sql);
		$old_opd= $query->result();

		$sql="select *,date_format(date_add(apointment_date,interval $no_opd_days day),'%d-%m-%Y') as opd_Exp_Date ,
        date_format(apointment_date,'%d-%m-%Y') as s_opd_date 
			from opd_master where p_id=".$opd_master[0]->p_id." and doc_id=".$opd_master[0]->doc_id." 
        	 and  apointment_date >= date_add(sysdate(),interval -$no_opd_days day) and opd_fee_type<>3";
		$query = $this->db->query($sql);
		$old_opd= $query->result();

		$data['old_opd']=$old_opd;

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

		$content='
			<style>@page {

			margin-top: 1cm;
			margin-bottom: 1.2cm;
			margin-left: 0.5cm;
			margin-right: 0.5cm;
			
			margin-header:0.5cm;
			margin-footer:0.5cm;
			
			}
			</style>
		
					<table width="100%" style="border:1px solid">
									<tr>
											<td width="33.3%">
													UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
													Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
													'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
													Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b><br>
													P.No. :'.$patient_master[0]->mphone1 .'<br>
											</td>
											<td width="33.3%">
													Sr No.: '.$opd_master[0]->opd_no .' <br/>
													OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
													OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
													<b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'
											</td>
											<td width="33.3%" style="text-align: left;vertical-align:top;">
												Dr. '.$opd_master[0]->doc_name.'
												'.nl2br($opd_master[0]->doc_sign).'
											</td>
									</tr>
							</table>';

		//echo $content;

		$this->load->library('m_pdf');

        $file_name='opd_parchi-'.$opdid.'-'.date('Ymdhis').".pdf";

        $filepath=$file_name;
		
		//$print_content=$this->load->view('dashboard/day_care_format',$data,TRUE);
		
		//echo $print_content;

        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");

    }

    public function invoice_print_pdf($opdid)
	{
		$sql="Select * from opd_master where opd_id=$opdid";
		$query = $this->db->query($sql);
		$opd_master= $query->result();

		$doc_id=0;
		$no_opd_days=5-1;

		if(count($opd_master)>0){

			$doc_id=$opd_master[0]->doc_id;

			$sql="select *
            from doctor_master 
            where id=$doc_id";
			$query = $this->db->query($sql);
			$doctor_master= $query->result();

			if(count($doctor_master)>0)
			{
				$no_opd_days=$doctor_master[0]->opd_valid_no_days-1;
			}
		}

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

		$content='<h3 align="center">OPD Invoice Receipt</h3>';

        $itemlist='Items:';

		$content.='	<table width="100%">
						<tr>
							<td width="50%">
								To <br/> 
								UHID : '.$patient_master[0]->p_code.'<br>
								Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
								'.$patient_master[0]->p_relative.' '.strtoupper($patient_master[0]->p_rname).'<br>
								Sex : <b>'.$patient_master[0]->xgender.'  '.$patient_master[0]->age.' </b><br> P.No. :'.$patient_master[0]->mphone1 .'<br>
								Address : '.$patient_master[0]->add1.','.$patient_master[0]->city.'<br>
							</td>
							<td width="30%">
								Invoice No.  : '.$opd_master[0]->opd_code.'<br>
								Date : <strong>'.$opd_master[0]->str_apointment_date.'</strong><br>
								Print Time : '.date('d-m-Y H:i:s').'
							</td>
							<td width="20%">
								
							</td>
						</tr>
					</table>	';
		$content.='<table border="1" cellspacing="0" cellpadding="1.5" width="100%">
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
		</table>	';

		$data['content']=$content;
	
		$data['bar_content']=$patient_master[0]->p_code.':OPD-'.$opd_master[0]->opd_code.':T'.date('dmYhms');
		
		//echo $content;
		//$this->load->library("Pdf");
		//create_Invoice_pdf($content,$bar_content,'opd_invoice-'.$opdid.'-'.date('Yms'));

		//load mPDF library
				
        $this->load->library('m_pdf');

        $file_name='opd_parchi-'.$opdid.'-'.date('Ymdhis').".pdf";

		$filepath=$file_name;
		
		$print_content=$this->load->view('dashboard/opd_invoice_print',$data,TRUE);
		
		//$print_content=$content;
	
		
		//echo $print_content;
		$this->m_pdf->pdf->SetJS('this.print();');
		
        $this->m_pdf->pdf->WriteHTML($print_content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");
	}
    
}
