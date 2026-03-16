<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Document_Patient extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
		
		$this->load->model('DocFormat_M');
	}

    function p_doc_record($pno)
    {
       $sql="select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,Date_Format(insert_date,'%Y-%m-%d') as str_regdate,if(gender=1,'Male','Female') as xgender from patient_master where  id=".$pno;
		$query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		
		$sql="select * from doc_format_master where active=1 ";
		$query = $this->db->query($sql);
        $data['doc_format']= $query->result();

		$sql="select * from doctor_master where active=1  order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select patient_doc.*,date_format(patient_doc.date_issue,'%d-%m-%Y') as str_date_issue,doc_format_master.doc_name 
			from patient_doc join doc_format_master
			on patient_doc.doc_format_id = doc_format_master.df_id
			where p_id=".$pno."  order by date_issue desc";
        $query = $this->db->query($sql);
        $data['patient_doc']= $query->result();


		$this->load->view('DocumentPatient/Patient_Doc_master',$data);
    }
	
	function create_doc()
	{
		
		$pno=$this->input->post('patient_id');
		$document_format_id=$this->input->post('document_format_id');
		$dr_id=$this->input->post('doc_id');
		$doc_issue_date=$this->input->post('doc_issue_date');
		
		$sql="Select * from doc_format_master	where df_id=".$document_format_id;
		$query = $this->db->query($sql);
		$data_report_format= $query->result();
		
		if(count($data_report_format)>0){
			$Report_string=$data_report_format[0]->doc_raw_format;
		}else{
			$Report_string="";
		}
		
		$sql="Select * from doc_pre_input where table_name='patient_master_exten'";
		$query = $this->db->query($sql);
		$data_pre_input= $query->result();

		$sql="Select * from doctor_master where id='".$dr_id."'";
		$query = $this->db->query($sql);
		$data_doctor_master= $query->result();

		$sql="Select * from patient_master_exten where id=".$pno;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result_array();;

		if(count($data_doctor_master)>0)
		{
			$Report_string=str_replace('{dr_name}',''.$data_doctor_master[0]->p_fname,$Report_string);
			$Report_string=str_replace('{dr_sign}',''.$data_doctor_master[0]->doc_sign,$Report_string);
		}

		$Report_string=str_replace('{current_date}',date('d-m-Y'),$Report_string);
		$Report_string=str_replace('{issue_date}',$doc_issue_date,$Report_string);

		for ($i = 0; $i < count($data_pre_input); ++$i) {
			if(isset($data_patient_master[0][$data_pre_input[$i]->field_name]))
			{
				$Change_Value=$data_patient_master[0][$data_pre_input[$i]->field_name];
				$Report_string=str_replace('{'.$data_pre_input[$i]->input_code.'}',$Change_Value,$Report_string);
			}
		}
		
		$udata = array( 
						'raw_data'=> $Report_string,
						'doc_format_id'=>$document_format_id,
						'p_id'=> $pno,
						'dr_id'=> $dr_id,
						'date_issue'=>str_to_MysqlDate($doc_issue_date)
					);
		
		$insert_id=$this->DocFormat_M->insert_patient_doc($udata);
		
		$sql="select * from doc_format_sub s where doc_format_id=".$document_format_id;
		$query = $this->db->query($sql);
		$doc_format_sub= $query->result_array();;
	
		foreach($doc_format_sub as $row)
			{
				$sql="select * from patient_doc_raw
				where p_id=".$pno." and p_doc_id=".$insert_id." and p_doc_sub_id='".$row['id']."' ";
				$query = $this->db->query($sql);
				$chktestlist= $query->result();

				if(count($chktestlist)<1)
				{
					$udata = array( 
						'p_id'=> $pno,
						'p_doc_id'=> $insert_id,
						'p_doc_sub_id'=> $row['id'],
						'p_doc_raw_value'=> $row['input_default_value'],
						);

					$insert_sub_id=$this->DocFormat_M->insert_patient_doc_raw($udata);
				}
			}
		
		echo $insert_id;
	}

	function re_create_doc($doc_id)
	{
		$sql="Select *,date_format(date_issue,'%d-%m-%Y') as str_date_issue from patient_doc where id=".$doc_id;
		$query = $this->db->query($sql);
		$patient_doc= $query->result();
		
		$pno=$patient_doc[0]->p_id;
		$document_format_id=$patient_doc[0]->doc_format_id;
		$dr_id=$patient_doc[0]->dr_id;
		$doc_issue_date=$patient_doc[0]->str_date_issue;

		$sql="Select * from doc_format_master	where df_id=".$document_format_id;
		$query = $this->db->query($sql);
		$data_report_format= $query->result();
		
		if(count($data_report_format)>0){
			$Report_string=$data_report_format[0]->doc_raw_format;
		}else{
			$Report_string="";
		}
		
		$sql="Select * from doc_pre_input where table_name='patient_master_exten'";
		$query = $this->db->query($sql);
		$data_pre_input= $query->result();

		$sql="Select * from doctor_master where id='".$dr_id."'";
		$query = $this->db->query($sql);
		$data_doctor_master= $query->result();

		$sql="Select * from patient_master_exten where id=".$pno;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result_array();;

		if(count($data_doctor_master)>0)
		{
			$Report_string=str_replace('{dr_name}',''.$data_doctor_master[0]->p_fname,$Report_string);
			$Report_string=str_replace('{dr_sign}',''.nl2br($data_doctor_master[0]->doc_sign),$Report_string);
		}

		$Report_string=str_replace('{current_date}',date('d-m-Y'),$Report_string);
		$Report_string=str_replace('{issue_date}',$doc_issue_date,$Report_string);

		for ($i = 0; $i < count($data_pre_input); ++$i) {
			if(isset($data_patient_master[0][$data_pre_input[$i]->field_name]))
			{
				$Change_Value=$data_patient_master[0][$data_pre_input[$i]->field_name];
				$Report_string=str_replace('{'.$data_pre_input[$i]->input_code.'}',$Change_Value,$Report_string);
			}
		}
		
		$udata = array( 
						'raw_data'=> $Report_string,
						'doc_format_id'=>$document_format_id,
						'p_id'=> $pno,
					);
		
		$update_id=$this->DocFormat_M->update_patient_doc($udata,$doc_id);
		
		redirect('/Document_Patient/Pre_Data/'.$doc_id);
	}

	function Pre_Data($patient_doc_id)
	{
		$sql="Select * from patient_doc where id=".$patient_doc_id;
		$query = $this->db->query($sql);
		$data['patient_doc']= $query->result();
		
		$doc_format_id=$data['patient_doc'][0]->doc_format_id;
		$pno=$data['patient_doc'][0]->p_id;

		$sql="select p.*,s.input_name,s.input_type  
				from (patient_doc_raw p join patient_doc m on m.id=p.p_doc_id)
				join doc_format_sub s on s.doc_format_id=m.doc_format_id and p.p_doc_sub_id=s.id 
		where p.p_doc_id=".$patient_doc_id;
		$query = $this->db->query($sql);
		$data['doc_format_sub']= $query->result();
				
		$sql="select *,GET_AGE_1(dob,age,age_in_month,estimate_dob) as str_age,
		Date_Format(insert_date,'%Y-%m-%d') as str_regdate,if(gender=1,'Male','Female') as xgender 
		from patient_master where  id=".$pno;
		$query = $this->db->query($sql);
        $data['person_info']= $query->result();

        $data['patient_doc_id']=$patient_doc_id;
		
		$this->load->view('DocumentPatient/Doc_update_pre_value',$data);
	}

	public function Entry_Update()
	{
		$test_id=$this->input->post('test_id');
		$test_value=$this->input->post('test_value');
		
		$udata = array( 
						'p_doc_raw_value'=> $test_value,
						'update_data'=> '1',
					);
		
		$this->DocFormat_M->update_patient_doc_raw($udata,$test_id);
		
		$sql="select p_doc_raw_value from  patient_doc_raw where id=".$test_id;
		$query = $this->db->query($sql);
		$data_value= $query->result();
		
		echo $data_value[0]->p_doc_raw_value;
		
	}
	
	public function update_doc_field($p_doc_id)
	{
		$sql="SELECT * from  patient_doc_raw  where update_data=0 and p_doc_id=".$p_doc_id;
		$query = $this->db->query($sql);
		$data_value_pending= $query->result();

		if(count($data_value_pending)>0)
		{
			$rvar=array(
                'update' =>0,
                'error_text'=>'Some Field Pending',
                );
                
             $encode_data = json_encode($rvar);
             echo $encode_data;

		}else{
			$sql="Select * from patient_doc	where id=".$p_doc_id;
			$query = $this->db->query($sql);
			$data_report_format= $query->result();

			if(count($data_report_format)>0){
				$Report_string=$data_report_format[0]->raw_data;
			}else{
				$Report_string="";
			}

			$sql="SELECT p.*,s.input_name,s.input_code from  patient_doc_raw p join doc_format_sub s ON p.p_doc_sub_id=s.id  
			where p_doc_id=".$p_doc_id;
			$query = $this->db->query($sql);
			$data_value= $query->result();
				
			for ($i = 0; $i < count($data_value); ++$i) {
				if(isset($data_value[$i]->p_doc_raw_value))
				{
					$Change_Value=nl2br($data_value[$i]->p_doc_raw_value);
					$Report_string=str_replace('{'.$data_value[$i]->input_code.'}',$Change_Value,$Report_string);
				}
			}
			
			$udata = array( 
							'raw_data'=> $Report_string,
							'update_pre_value'=>1,
						);

			$this->DocFormat_M->update_patient_doc($udata,$p_doc_id);

			$rvar=array(
                'update' =>1,
                'error_text'=>'Complie Done',
                );
                
            $encode_data = json_encode($rvar);
            echo $encode_data;
		}
	
	}
	
	
	
	function load_doc($patient_doc_id)
	{
		$sql="Select * from patient_doc where id=".$patient_doc_id;
		$query = $this->db->query($sql);
		$data['patient_doc']= $query->result();
		
		$pno=0;
		
		if(count($data['patient_doc'])>0)
		{
			$pno=$data['patient_doc'][0]->p_id;
		}
		
		$sql="Select * from patient_master_exten where id=".$pno;
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$this->load->view('DocumentPatient/Patient_Doc_Edit',$data);
	}
	
	function create_final($patient_doc_id,$print_on_type=0)
	{
		$sql="Select *,date_format(date_issue,'%d-%m-%Y') as str_date_issue from patient_doc where id=".$patient_doc_id;
		$query = $this->db->query($sql);
		$patient_doc= $query->result();
		
		$sql="Select count(*) as No_Rec from file_upload_data where doc_id=".$patient_doc_id;
		$query = $this->db->query($sql);
		$print_doc= $query->result();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$print_no_rec=$print_doc[0]->No_Rec+1;
		$doc_issue_date=$patient_doc[0]->str_date_issue;
		
		$content='';
		if(count($patient_doc)>0)
		{
			$content='<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
						<tbody>
							<tr>
								<td>Document Ref. No.'.date('Y').'/'.$print_no_rec.'/'.$patient_doc_id.'</td>
								<td style="text-align:right">Date : '.$doc_issue_date.'</td>
							</tr>
						</tbody>
					</table>';
			
			$content.=$patient_doc[0]->raw_data;
			$data['bar_content']='Document Ref. No.'.date('Y').'/'.$print_no_rec.'/'.$patient_doc_id.'/'.$doc_issue_date;

		}
		
		$data['content']=$content;
		$data['print_on_type']=$print_on_type;
		
		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$report_head='Patient Document';
		
		$file_name='Document-'.$patient_doc_id."-".$print_no_rec."-".date('Ymdhis').".pdf";

		$filepath=$folder_name.'/'.$file_name;

		$udata = array( 
					'file_name'=>$file_name,
					'file_type'=>'pdf',
					'file_path'=>$folder_name,
					'full_path'=>$filepath,
					'orig_name'=>$file_name,
					'client_name'=>'system_genrate',
					'file_ext'=>'.pdf',
					'upload_by'=> $user_name,
					'pid'=>$patient_doc[0]->p_id,
					'doc_id'=>$patient_doc_id,
					'case_id'=>0,
					'file_desc'=>$report_head,
					'charge_id'=>0
					);

		$this->load->model('File_M');

		$this->File_M->insert($udata);
				
		//load mPDF library
        $this->load->library('m_pdf');

		//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
		
		if($print_on_type==1)
		{
			$this->m_pdf->pdf->SetWatermarkText(H_Name);
        	$this->m_pdf->pdf->showWatermarkText = true;
		}

		$doc_content=$this->load->view('DocAdmin/doc_letterhead_print',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($doc_content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");

		
		
	}
	
	function update_doc()
	{
		$document_id=$this->input->post('document_id');
		$HTMLData=$this->input->post('HTMLData');
		
		$udata = array( 
						'raw_data'=> $HTMLData
					);
		
		$update_value=1;
		$showcontent="";
		
		$update_value=$this->DocFormat_M->update_patient_doc($udata,$document_id);
		
		if($update_value>0)
		{
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
		
		$rvar=array(
		'update_value' => $update_value,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
 
}

