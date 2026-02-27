<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report6 extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
        
    }
  
	function lab_logs()
	{
		$data['lab_type_data']=array(
            '1'=>'ULTRASOUND',
            '2'=>'MRI',
            '3'=>'X-RAY',
            '4'=>'CT SCAN',
            '5'=>'PATHOLOGY ',
        );

		$this->load->view('Report6/Report_Lab_Log',$data);
	}
	

  
	function lab_log_data($daterange,$lab_type,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;
				
		if($lab_type>0)
		{
			$where.=" and r.lab_type =".$lab_type;
		}
		
		$where.="   and  l.log_insert_time between '".$minRange."' and '".$maxRange."' " ;
		
		$sql="SELECT l.*,r.invoice_code,r.patient_name,r.lab_type
                FROM lab_log l JOIN lab_request r ON l.lab_repo_id=r.id
                WHERE ".$where." ";
		
		//echo $sql;
		
		$query = $this->db->query($sql);
        $doclist= $query->result();

        $lab_type_data=array(
            '1'=>'ULTRASOUND',
            '2'=>'MRI',
            '3'=>'X-RAY',
            '4'=>'CT SCAN',
            '5'=>'PATHOLOGY ',
        );
		$content="Data Between from ".$minRange."' to '".$maxRange."' "		;
		
        $content.='<table border="1" width="100%" cellpadding="3">';

		$content.='<tr  >
					<th width="50px">#</th>
                    <th width="50px">Lab</th>
					<th >Date Time</th>
					<th>Invoice /Lab No.</th>
                    <th width="200px">Person Name</th>
					<th>Log Type</th>
					<th>Log Faults</th>
					<th>UpdateBy</th>
					<th>Remark/Comment</th>
				 </tr>';
		$sr_no=0;
		foreach($doclist as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$sr_no.'</td>
                            <td >'.$lab_type_data[$row->lab_type].'</td>
							<td >'.$row->log_insert_time.'</td>
                            <td>'.$row->invoice_code.'</td>
							<td>'.$row->patient_name.'</td>
							<td>'.$row->log_type.'</td>
							<td>'.$row->log_Faults.'</td>
							<td>'.$row->log_by.'</td>
							<td>'.$row->comments.'</td>
					 </tr>';
		}
				
				$content.='<tr>
                            <th width="50px">#</th>
                            <th width="50px">Lab</th>
                            <th >Date Time</th>
                            <th>Invoice /Lab No.</th>
                            <th width="200px">Person Name</th>
                            <th>Log Type</th>
                            <th>Log Faults</th>
							<th>UpdateBy</th>
                            <th>Remark/Comment</th>
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

	
	
	
}
  