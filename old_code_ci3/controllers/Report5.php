<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report5 extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
    }
  
	function document_list()
	{
		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
        $data['doclist']= $query->result();

		$this->load->view('Report5/Report_Document',$data);
	}
	  
	function document_list_data($daterange,$dr_id,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;

		if($dr_id>0)
		{
			$where.=" and  doc.id =".$dr_id;
		}
		
		$where.="   and  d.date_issue between '".$minRange."' and '".$maxRange."' " ;
		
		$sql="SELECT p.id,p.p_fname,p.p_rname,p.p_relative,p.p_code,
			d.doc_subject,d.date_issue,date_format(d.date_issue,'%d-%m-%Y') AS str_date_issue,
			d.id AS Doc_id,f.doc_name,
			doc.p_fname AS dr_name,doc.id AS dr_id
			FROM ((patient_master_exten p JOIN patient_doc d ON p.id=d.p_id)
			JOIN  doctor_master doc ON d.dr_id=doc.id)
			Join doc_format_master f ON d.doc_format_id=f.df_id   where ".$where." ";
		
		//echo $sql;
		
		$query = $this->db->query($sql);
        $doclist= $query->result();
				
		$content='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr  >
					<th width="50px">#</th>
					<th width="50px" align="right">Doc. ID</th>
					<th width="200px">Person Name / Relative Name</th>
					<th>PCode/UHID</th>
					<th>Doctor Name</th>
					<th>Issue Date</th>
					<th>Document Name</th>
				 </tr>';
		$sr_no=0;
		foreach($doclist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
							<td align="right">'.$row->Doc_id.'</td>
							<td>'.$row->p_fname.'{'.$row->p_relative.' '.$row->p_rname.'}</td>
							<td>'.$row->p_code.'</td>
							<td>'.$row->dr_name.'</td>
							<td>'.$row->str_date_issue.'</td>
							<td>'.$row->doc_name.'</td>
					 </tr>';
		}
				
				$content.='<tr>
							<td>#</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
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
			ExportExcel($content,'document_List'.date('Yms'));
		}
	
	}

	// Xray Chest List
	public function Report_XRay_chest()
	{
		$this->load->view('Report5/Report_XRay_chest');
	}
	
	public function XRay_chest_data($daterange,$output=0){
		 $rangeArray = explode("S",$daterange);
		 $minRange = $rangeArray[0];
		 $maxRange = $rangeArray[1];
		 
		 
		 $sql="SELECT p.p_code,p.p_fname,p.xgender,p.str_age,p.mphone1,p_address,
		 		date_format(m.inv_date,'%d-%m-%Y') AS str_test_date,i.item_name,t.group_desc,i.item_id
		 		FROM ((patient_master_exten p JOIN invoice_master m ON p.id=m.attach_id)
		 		JOIN invoice_item i ON m.id=i.inv_master_id)
		 		JOIN hc_item_type t ON i.item_type=t.itype_id
		 		WHERE m.inv_date BETWEEN '".$minRange."' AND '".$maxRange."'
		 		AND i.item_id=61 ";
		 
		 //echo $sql;
		 
		 $query = $this->db->query($sql);
		 $doclist= $query->result();

		 $content='<h1>'.H_Name.'</h1>';
		 $content.='<h2>'.H_address_1.'</h2>';

		 $content.='<h3>X-Ray Chest  </h3>';
				 
		 $content.='<table border="1" width="100%" cellpadding="3">';
 
		 $content.='<tr>
					 <th >#</th>
					 <th align="right">PCode/UHID</th>
					 <th align="right">Patient Name</th>
					 <th>Age</th>
					 <th >Gender</th>
					 <th>Address</th>
					 <th>Phone Number</th>
					 <th>Test Date</th>
					 <th>Test Name</th>
				  </tr>';
		 $sr_no=0;
		 foreach($doclist as $row)
		 {
			 $sr_no=$sr_no+1;
			 $content.='<tr>
							 <td style="vertical-align:top">'.$sr_no.'</td>
							 <td style="vertical-align:top">'.$row->p_code.'</td>
							 <td style="vertical-align:top">'.$row->p_fname.'</td>
							 <td style="vertical-align:top">'.$row->str_age.'</td>
							 <td style="vertical-align:top">'.$row->xgender.'</td>
							 <td style="vertical-align:top">'.$row->p_address.'</td>
							 <td style="vertical-align:top">'.$row->mphone1.'</td>
							 <td style="vertical-align:top">'.$row->str_test_date.'</td>
							 <td style="vertical-align:top">'.$row->item_name.'</td>
					  </tr>';
		 }
		 $content.="</table>";
 
		 if($output==0)
		 {
			 $this->load->library('m_pdf');
			 $file_name="Report-".date('Ymdhis').".pdf";
			 $filepath=$file_name;
			 $this->m_pdf->pdf->WriteHTML($content);
			 $this->m_pdf->pdf->Output($filepath,"I");
		 }else{
			 ExportExcel($content,'XRay_chest_list'.date('Yms'));
		 }
	}


	// Corona
	public function corona_suspected()
	{
		$this->load->view('Report5/Report_corona_suspected');
	}
	
	function corona_suspected_data($daterange,$output=0)
	{
		 $rangeArray = explode("S",$daterange);
		 $minRange = $rangeArray[0];
		 $maxRange = $rangeArray[1];
		 
		 $where =" " ;
		 
		 $where.="   and  m.apointment_date between '".$minRange."' and '".$maxRange."' " ;
		 
		 $sql="SELECT p.p_code,p.p_fname,p.xgender,p.str_age,p.mphone1,p_address,
		 		date_format(m.apointment_date,'%d-%m-%Y') AS str_apointment_date,o.complaints
		 		FROM (opd_prescription o JOIN opd_master m ON m.opd_id=o.opd_id)
		 		JOIN patient_master_exten p ON m.p_id=p.id
		 		WHERE o.corona_suspected=1 ".$where." ";
		 
		 //echo $sql;
		 
		 $query = $this->db->query($sql);
		 $doclist= $query->result();

		 $content='<h1>'.H_Name.'</h1>';
		 $content.='<h2>'.H_address_1.'</h2>';

		 $content.='<h3>Corona Suspected Data  </h3>';
				 
		 $content.='<table border="1" width="100%" cellpadding="3">';
 
		 $content.='<tr>
					 <th >#</th>
					 <th align="right">Patient Name</th>
					 <th>Age</th>
					 <th >Gender</th>
					 <th>Address</th>
					 <th>Phone Number</th>
					 <th>Visit Date</th>
					 <th>Symptoms</th>
				  </tr>';
		 $sr_no=0;
		 foreach($doclist as $row)
		 {
			 $sr_no=$sr_no+1;
			 $content.='<tr>
							 <td style="vertical-align:top">'.$sr_no.'</td>
							 <td style="vertical-align:top">'.$row->p_fname.'</td>
							 <td style="vertical-align:top">'.$row->str_age.'</td>
							 <td style="vertical-align:top">'.$row->xgender.'</td>
							 <td style="vertical-align:top">'.$row->p_address.'</td>
							 <td style="vertical-align:top">'.$row->mphone1.'</td>
							 <td style="vertical-align:top">'.$row->str_apointment_date.'</td>
							 <td style="vertical-align:top">'.$row->complaints.'</td>
					  </tr>';
		 }
		 $content.="</table>";
 
		 if($output==0)
		 {
			 $this->load->library('m_pdf');
			 $file_name="Report-".date('Ymdhis').".pdf";
			 $filepath=$file_name;
			 $this->m_pdf->pdf->WriteHTML($content);
			 $this->m_pdf->pdf->Output($filepath,"I");
		 }else{
			 ExportExcel($content,'corona_suspected'.date('Yms'));
		 }
	}

	// pregnancy
	public function pregnancy_search()
	{
		$this->load->view('Report5/Report_pregnancy_suspected');
	}
	
	function pregnancy_data($daterange,$output=0)
	{
		 $rangeArray = explode("S",$daterange);
		 $minRange = $rangeArray[0];
		 $maxRange = $rangeArray[1];
		 
		 $where =" " ;
		 
		 $where.="   and  m.apointment_date between '".$minRange."' and '".$maxRange."' " ;
		 
		 $sql="SELECT p.p_code,p.p_fname,p.xgender,p.str_age,p.mphone1,p_address,
		 		date_format(m.apointment_date,'%d-%m-%Y') AS str_apointment_date,o.complaints
		 		FROM (opd_prescription o JOIN opd_master m ON m.opd_id=o.opd_id)
		 		JOIN patient_master_exten p ON m.p_id=p.id
		 		WHERE o.pregnancy=1 ".$where." ";
		 
		 //echo $sql;
		 
		 $query = $this->db->query($sql);
		 $doclist= $query->result();

		 $content='<h1>'.H_Name.'</h1>';
		 $content.='<h2>'.H_address_1.'</h2>';

		 $content.='<h3>Pregnancy Data  </h3>';
				 
		 $content.='<table border="1" width="100%" cellpadding="3">';
 
		 $content.='<tr>
					 <th >#</th>
					 <th align="right">Patient Name</th>
					 <th>Age</th>
					 <th >Gender</th>
					 <th>Address</th>
					 <th>Phone Number</th>
					 <th>Visit Date</th>
					 <th>Symptoms</th>
				  </tr>';
		 $sr_no=0;
		 foreach($doclist as $row)
		 {
			 $sr_no=$sr_no+1;
			 $content.='<tr>
							 <td style="vertical-align:top">'.$sr_no.'</td>
							 <td style="vertical-align:top">'.$row->p_fname.'</td>
							 <td style="vertical-align:top">'.$row->str_age.'</td>
							 <td style="vertical-align:top">'.$row->xgender.'</td>
							 <td style="vertical-align:top">'.$row->p_address.'</td>
							 <td style="vertical-align:top">'.$row->mphone1.'</td>
							 <td style="vertical-align:top">'.$row->str_apointment_date.'</td>
							 <td style="vertical-align:top">'.$row->complaints.'</td>
					  </tr>';
		 }
		 $content.="</table>";
 
		 if($output==0)
		 {
			 $this->load->library('m_pdf');
			 $file_name="Report-".date('Ymdhis').".pdf";
			 $filepath=$file_name;
			 $this->m_pdf->pdf->WriteHTML($content);
			 $this->m_pdf->pdf->Output($filepath,"I");
		 }else{
			 ExportExcel($content,'pregnancy_data'.date('Yms'));
		 }
	}


	//Dengue
	public function dengue()
	{
		$this->load->view('Report5/Report_dengue');
	}
	
	function dengue_data($daterange,$output=0)
	{
		 $rangeArray = explode("S",$daterange);
		 $minRange = $rangeArray[0];
		 $maxRange = $rangeArray[1];
		 
		 $where =" " ;
		 
		 $where.="   and  m.apointment_date between '".$minRange."' and '".$maxRange."' " ;
		 
		 $sql="SELECT p.p_code,p.p_fname,p.xgender,p.str_age,p.mphone1,p_address,
		 		date_format(m.apointment_date,'%d-%m-%Y') AS str_apointment_date,o.complaints
		 		FROM (opd_prescription o JOIN opd_master m ON m.opd_id=o.opd_id)
		 		JOIN patient_master_exten p ON m.p_id=p.id
		 		WHERE o.dengue=1 ".$where." ";
		 
		 //echo $sql;
		 
		 $query = $this->db->query($sql);
		 $doclist= $query->result();

		 $content='<h1>'.H_Name.'</h1>';
		 $content.='<h2>'.H_address_1.'</h2>';

		 $content.='<h3>Dengue Patient Data  </h3>';
				 
		 $content.='<table border="1" width="100%" cellpadding="3">';
 
		 $content.='<tr>
					 <th >#</th>
					 <th align="right">Patient Name</th>
					 <th>Age</th>
					 <th >Gender</th>
					 <th>Address</th>
					 <th>Phone Number</th>
					 <th>Visit Date</th>
					 <th>Symptoms</th>
				  </tr>';
		 $sr_no=0;
		 foreach($doclist as $row)
		 {
			 $sr_no=$sr_no+1;
			 $content.='<tr>
							 <td style="vertical-align:top">'.$sr_no.'</td>
							 <td style="vertical-align:top">'.$row->p_fname.'</td>
							 <td style="vertical-align:top">'.$row->str_age.'</td>
							 <td style="vertical-align:top">'.$row->xgender.'</td>
							 <td style="vertical-align:top">'.$row->p_address.'</td>
							 <td style="vertical-align:top">'.$row->mphone1.'</td>
							 <td style="vertical-align:top">'.$row->str_apointment_date.'</td>
							 <td style="vertical-align:top">'.$row->complaints.'</td>
					  </tr>';
		 }
		 $content.="</table>";
 
		 if($output==0)
		 {
			 $this->load->library('m_pdf');
			 $file_name="Report-".date('Ymdhis').".pdf";
			 $filepath=$file_name;
			 $this->m_pdf->pdf->WriteHTML($content);
			 $this->m_pdf->pdf->Output($filepath,"I");
		 }else{
			 ExportExcel($content,'dengue_data'.date('Yms'));
		 }
	}

	
}
  