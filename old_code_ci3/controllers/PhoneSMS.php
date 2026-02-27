<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class PhoneSMS extends CI_Controller
{    
    public function __construct()
    {
        parent::__construct();
		
		$this->load->model('SMS_M');
	}

    public function show_lab_report($file_id_encrpt)
    {
		$file_id=decode_dst($file_id_encrpt);

		$sql="select * from file_upload_data where id=".$file_id;
		$query = $this->db->query($sql);
		$data['lab_file_details']= $query->result();

		$this->load->view('PhoneSMS/lab_report_show',$data);
    }

   
		

	
}
?>