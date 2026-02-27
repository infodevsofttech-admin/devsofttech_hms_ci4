<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller {
	
	
	public function show()
	{
		$this->load->view('welcome_message');
	}
	
	public function logout()
	{
		$this->ion_auth->logout();
		redirect('auth_dst/login', 'refresh');
	}
	
	public function index()
	{
        if (!$this->ion_auth->logged_in())
		{
			redirect('auth_dst/login', 'refresh');
		}
		
		$d= $this->ion_auth->user()->row();
		
		$data['user'] = $d;
		
		//print_r($d);

		$sql="SELECT count(opd_id) as no_opd_id 
		FROM opd_master WHERE online_book=1 and apointment_date=date(sysdate())";
        $query = $this->db->query($sql);
        $opd_master= $query->result();

        $data['No_of_online_opd']=$opd_master[0]->no_opd_id;
		
		$this->load->view('Header',$data);
        $this->load->view('LeftSide',$data);
		$this->load->view('AdminMain');
        //$this->load->view('ControlSide');
        $this->load->view('Footer');
	}
    
    public function loademail()
	{
		$this->load->view('TestForm');
	}
    public function loademail2()
	{
		$this->load->view('TestForm2');
	}

	public function check_login()
	{
		if (!$this->ion_auth->logged_in())
		{
			$rvar=array(
                'login' =>1               
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
	}
	
    public function Patient()
	{
		$this->load->view('Patient');
	}


	function update_online_opd(){
        
        $sql="SELECT count(opd_id) as no_opd_id 
		FROM opd_master 
		WHERE online_book=1 and apointment_date=date(sysdate())";
        $query = $this->db->query($sql);
        $opd_master= $query->result();

        $No_of_online_opd=$opd_master[0]->no_opd_id;
		
		$rvar=array(
            'online_opd' =>$No_of_online_opd,
        );
        
        $encode_data = json_encode($rvar);
        echo $encode_data;

    }


	function opd_online_list()
	{
		$sql="SELECT o.*,p.p_code,p.p_fname
		FROM opd_master o JOIN patient_master p on o.p_id=p.id 
		WHERE o.online_book=1 and o.apointment_date=date(sysdate())";
        $query = $this->db->query($sql);
        $data['opd_online_list']= $query->result();

		$this->load->view('dashboard/opd_line_list',$data);

	}

	
}
