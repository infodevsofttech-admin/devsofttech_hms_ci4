<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_data extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
        $this->load->model('Master_M');
	}
	
	function tag_index()
    {
        $sql="select * from tag_master order by tag_name ";
		$query = $this->db->query($sql);
		$data['tag_master']= $query->result();

        $this->load->view('MasterData/tag_master_index',$data);
    }

    function tag_add()
    {
        $this->load->view('MasterData/tag_master_add');
    }

    function tag_save()
    {
        $input_tag_name = $this->input->post('input_tag_name');
        $input_tag_desc = $this->input->post('input_tag_desc');
        $hid_tag_id = $this->input->post('hid_tag_id');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

        $insert_data=array(
            'tag_name'=>$input_tag_name,
            'tag_type_id'=>'0',
            'tag_desc'=>$input_tag_desc,
            'insert_dateimte'=>date('Y-m-d H:i:s'),
            'insert_by'=>$user_name,
        );

        if($hid_tag_id==0){
            $inser_id=$this->Master_M->insert_tag_master($insert_data); 
        }else{
            $inser_id=$this->Master_M->update_tag_master($insert_data,$hid_tag_id); 
        }
        
        echo $inser_id;
    }

    // Employee Master
	public function Employee_master_list()
    {
		$sql="select * from employee_master order by emp_name ";
		$query = $this->db->query($sql);
		$data['employee_master']= $query->result();

		$this->load->view('MasterData/employee_master_list',$data);
	}

	function Employee_add()
    {
        $this->load->view('MasterData/emp_master_add');
    }

	function Employee_edit($emp_id)
    {
        $sql="select * from employee_master where emp_id= $emp_id";
		$query = $this->db->query($sql);
		$data['employee_master']= $query->result();

		$this->load->view('MasterData/emp_master_edit',$data);
    }

	function Employee_save()
    {
        $input_emp_code = $this->input->post('input_emp_code');
        $input_emp_name = $this->input->post('input_emp_name');
        $input_emp_dob = $this->input->post('input_emp_dob');
        $input_emp_joinning_date = $this->input->post('input_emp_joinning_date');
        $input_emp_phone_no = $this->input->post('input_emp_phone_no');
        $hid_emp_id = $this->input->post('hid_emp_id');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' ['.$user_id.']';

        $insert_data=array(
            'emp_code'=>$input_emp_code,
            'emp_name'=>$input_emp_name,
            'emp_dob'=>$input_emp_dob,
            'emp_joinning_date'=>$input_emp_joinning_date,
            'emp_phone_no'=>$input_emp_phone_no,

            'add_by'=>$user_name.' : '.date('Y-m-d H:i:s'),
        );

        if($hid_emp_id==0){
            $inser_id=$this->Master_M->insert_employee_master($insert_data); 
        }else{
            $inser_id=$this->Master_M->update_employee_master($insert_data,$hid_emp_id); 
        }
        
        echo $inser_id;
    }
}