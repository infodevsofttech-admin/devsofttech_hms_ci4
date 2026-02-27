<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PhoneHMS extends CI_Controller
{    
    public function __construct()
    {
        parent::__construct();
		$this->load->database();
		$this->load->library(array('ion_auth','form_validation'));
		$this->load->helper(array('url','language'));

		$this->form_validation->set_error_delimiters($this->config->item('error_start_delimiter', 'ion_auth'), $this->config->item('error_end_delimiter', 'ion_auth'));

		$this->lang->load('auth');

		$this->load->model('SMS_M');
	}

    public function Login()
    {
		$login_id=$this->input->post('login');
		$mobilecode=$this->input->post('mcode');
				
		if ($this->ion_auth->login($login_id, $mobilecode, true))
		{
			$rvar=array(
				'loginID' => 1,
				'msg' => 'Wrong User Id or Password'
			);
		}
		else
		{
			$rvar=array(
			'loginID' => 0,
			'msg' => 'Wrong User Id or Password'
			);
		}
		
		$encode_data = json_encode($rvar);
		echo header("Access-Control-Allow-Origin: *");
		echo $encode_data;
    }

	public function PhoneGapProcess($reqcode=0)
    {
		if($reqcode==0)
		{
			if ($this->ion_auth->logged_in())
			{
				$user = $this->ion_auth->user()->row();
				$user_id = $user->id;
				$user_name = $user->first_name.''. $user->last_name;
				
				$rvar=array(
				'loginName' => $user_name,
				'user_id' => $user_id
				);
			}else{
				$rvar=array(
				'loginName' => '',
				'user_id' => 0
				);
			}

			$encode_data = json_encode($rvar);
			echo header("Access-Control-Allow-Origin: *");
			echo $encode_data;
		}
		
		// Search OPD in Database
		
		if($reqcode==1)
		{
			$sdata=$this->input->post('txtsearch');
        
			$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
			
			$sql = "select  o.opd_id, o.doc_name,o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
			p.p_code,p.mphone1,p.email1 ,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,
			m.mode_desc as PaymentMode,o.payment_mode
			from (opd_master o join patient_master p on o.p_id=p.id ) join payment_mode m on o.payment_mode=m.id
			WHERE (opd_code like '%".$sdata."' or p_code like '%".$sdata."' or P_name like '%".$sdata."%' or
			mphone1 = '".$sdata."' or email1 = '".$sdata."') and o.apointment_date>Date_Add(curdate(),interval -7 day)   order by o.opd_id desc  limit 10";

			
			$query = $this->db->query($sql);
			$opd_list= $query->result_array();
			
			$columns = array( 
				0 =>'opd_id',
				1 => 'doc_name',
				2 => 'opd_code',
				3 => 'P_name',
				4=> 'App_Date',
				5=> 'p_code',
				6=> 'mphone1',
				7=> 'Inv_Type'
			);
			
			$output = array(
				"recordsTotal"    => intval( count($opd_list) ),  // total number of records
				"recdata"            =>$opd_list,
				"url"  => substr($_SERVER['PHP_SELF'],0,19)
				);
			
			$encode_data = json_encode($output);
			echo header("Access-Control-Allow-Origin: *");
			echo $encode_data; 
			
		}
		
	}
	
	public function SelectOPD($opdid)
	{
		$sql = "select  o.opd_id, o.doc_name,o.opd_code,o.P_name,o.p_id,Date_Format(o.apointment_date,'%d-%m-%Y') as App_Date,
		p.p_code,p.mphone1,p.email1 ,if(o.insurance_id>1,'Org.','Direct') as Inv_Type,
		m.mode_desc as PaymentMode,o.payment_mode
		from (opd_master o join patient_master p on o.p_id=p.id ) join payment_mode m on o.payment_mode=m.id
		WHERE o.opd_id=".$opdid;

		$query = $this->db->query($sql);
        $opd_data= $query->result_array();

		$output = array(
			"recordsTotal"    => intval( count($opd_data) ),  // total number of records
			"recdata"            =>$opd_data
			);
		
		$encode_data = json_encode($output);
		echo header("Access-Control-Allow-Origin: *");
		echo $encode_data; 
	}
	
	public function save_image_opd($opdid)
	{
		$filename =  'OPD-'.$opdid.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
		}
				
		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		if (!$this->upload->do_upload('file')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
			
			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
			
			$sql="select * from opd_master  where opd_id=".$opdid;
			$query = $this->db->query($sql);
			$opd_master= $query->result();

			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			if(count($opd_master)>0)
			{
				$pid=$opd_master[0]->p_id;
				$ipd_id=$opd_master[0]->ipd_id;
				$org_id=$opd_master[0]->insurance_case_id;
			}

			$udata = array( 
					'upload_by'=> 'PhoneGap',
					'pid'=>$pid,
					'opd_id'=>$opdid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'upload_by_id'=>'0'
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
					
				//$user_name = $user->first_name.''. $user->last_name.':T-'.date('d-m-Y h:m:s');
				$user_name ='PhoneGap';
				
				$opdstatus=2;
				
				if($opdstatus=='2')
				{
					$status_str='Visit Done';
				}elseif($opdstatus=='3')
				{
					$status_str='Visit Cancel';
				}
	
				$opd_status_remark=$status_str.' Update By:'.$user_name;
				
				$dataupdate = array( 
				'opd_status' => $opdstatus,
				'opd_status_remark' => $opd_status_remark
				);

				$this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$opdid);
			}
			
			$rvar=array(
				'filename' => $filename,
				'upload' => 1
				);
			
			$encode_data = json_encode($rvar);
			echo header("Access-Control-Allow-Origin: *");
			echo $encode_data;
			
		}
	}

	public function save_sms()
	{
		echo 'Hello';
		
		$data = array( 
			'sender' => $this->input->post('sender'),
			'inNumber' => $this->input->post('inNumber'),
			'content' => $this->input->post('content'),
			'keyword' => $this->input->post('keyword'),
			'comments' => trim(str_replace('JC','',$this->input->post('comments'))),
			'msgId' => $this->input->post('msgId'),
			'rcvd' => $this->input->post('rcvd'),
		);

		$insert_id=$this->SMS_M->insert_sms_inbox($data);
		
		
		if(strlen($comments)>0)
		{
			$data_array=explode(" ",$comments);
	
			$cmd_ID='';
			
			if(isset($data_array[0]))
			{
				$cmd_ID=$data_array[0];
			}
			
		}
		
		
	} 
	

	
}
?>