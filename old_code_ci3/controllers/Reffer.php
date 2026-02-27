<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reffer extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
		$this->load->model('Reffer_M');

    }
    
    function index()
    {
        $sql="select *,date_format(date_of_add,'%d-%m-%Y') as str_dateadd from refer_master  order by f_name";
        $query = $this->db->query($sql);
        $data['refer_master']= $query->result();

        $this->load->view('hospital_refer/reffer_index',$data);
    }

    function reffer_add()
    {
        $sql="select * from refer_type ";
        $query = $this->db->query($sql);
        $data['refer_type']= $query->result();
        
        $this->load->view('hospital_refer/reffer_add',$data);
    }

    function reffer_edit($refer_id)
    {
        $sql="select * from refer_master  where id=$refer_id";
        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $sql="select * from refer_type ";
        $query = $this->db->query($sql);
        $data['refer_type']= $query->result();
        

        $this->load->view('hospital_refer/reffer_edit',$data);
    }

    function save_new_user()
    {
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$cbo_title=$this->input->post('cbo_title');
        $input_name=$this->input->post('input_name');
		$cbo_refer_type=$this->input->post('cbo_refer_type');
        $input_phone_number=$this->input->post('input_phone_number');

        

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        

		$FormRules = array(
                array(
                    'field' => 'input_name',
                    'label' => 'Name of Patient',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
            ); 

		
		$this->form_validation->set_rules($FormRules);

		if ($this->form_validation->run() == TRUE)
            {

                $data = array( 
                    'title' => $cbo_title, 
					'f_name' => strtoupper($input_name),
					'refer_type' => $cbo_refer_type,
					'date_of_add' => date('Y-m-d H:i:s'),
					'insert_by' => $user_name,
                    'phone_number'=> $input_phone_number,
					'active' => 1,
                );
						
				
                $inser_id=$this->Reffer_M->insert($data); 
                $rvar=array(
                'insertid' =>$inser_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
		
    }


    function save_user($reffer_id)
    {
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$cbo_title=$this->input->post('cbo_title');
        $input_name=$this->input->post('input_name');
		$cbo_refer_type=$this->input->post('cbo_refer_type');
        $input_phone_number=$this->input->post('input_phone_number');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        

		$FormRules = array(
                array(
                    'field' => 'input_name',
                    'label' => 'Name of Patient',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
            ); 

		
		$this->form_validation->set_rules($FormRules);

		if ($this->form_validation->run() == TRUE)
            {

                $data = array( 
                    'title' => $cbo_title, 
					'f_name' => strtoupper($input_name),
					'refer_type' => $cbo_refer_type,
					'date_of_add' => date('Y-m-d H:i:s'),
					'insert_by' => $user_name,
                    'phone_number'=> $input_phone_number,
					'active' => 1,
                );
				
                $this->Reffer_M->update($data,$reffer_id); 
                $rvar=array(
                    'update' =>1
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
		
    }
}