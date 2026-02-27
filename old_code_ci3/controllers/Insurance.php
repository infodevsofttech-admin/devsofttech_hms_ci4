<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Insurance extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function search()
	{
        $sql = "SELECT *,if(active=1,'Active','Inactive') as activestatus FROM hc_insurance where id>1 ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
        $this->load->view('insurance/insurance_search_v',$data);
	}
	
	function insurance_record($id)
    {
               
        $sql="select * from hc_insurance where id=".$id;
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();
                
        $this->load->view('insurance/insurance_profile_V',$data);
    }
	
	public function UpdateRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$this->load->model('insurance_M');
        $data = array( 
                    'ins_company_name' => $this->input->post('input_comp_name'),
					'short_name' => $this->input->post('input_short_name'),
					'ins_contact_number' => $this->input->post('input_mphone1'), 
                	'ins_contact_person_name' => $this->input->post('input_cname'),
                	'ins_email' => $this->input->post('input_email'),
					'active' => $this->input->post('chk_active'),
					'opd_allowed' => $this->input->post('chk_opd_allowed'),
					'opd_fee' => $this->input->post('input_opd_fee'),
					'opd_rate_direct' => $this->input->post('optionsRadios_opd_rate_direct'),
					'opd_desc' => $this->input->post('input_opd_fee_desc'),
					'opd_master_rate_discount' => $this->input->post('input_opd_master_rate_discount'),
					'opd_credit' => $this->input->post('chk_opd_credit'),
					'opd_cash' => $this->input->post('chk_opd_cash'),
					'charge_credit' => $this->input->post('chk_charge_credit'),
					'charge_rate_direct' => $this->input->post('optionsRadios_charge_rate_direct'),
					'charge_rate_dicount' => $this->input->post('input_charge_rate_dicount'),
					'med_credit' => $this->input->post('chk_med_credit'),
					
                 );
        $old_id=$this->input->post('p_id'); 
        $update=$this->insurance_M->update($data,$old_id);
		
		if($update>0)
		{
			$rvar=array(
                'update' =>1,
				'showcontent'=> Show_Alert('success','Saved','Data Saved successfully')
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
	
	public function AddRecord() 
	{
		 $this->load->view('insurance/insurance_create_V');
	}
	
	public function CreateRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        		
		$this->load->model('insurance_M');
        $data = array( 
                    'ins_company_name' => $this->input->post('input_comp_name'),
					'ins_contact_number' => $this->input->post('input_mphone1'), 
                	'ins_contact_person_name' => $this->input->post('input_cname'),
                	'ins_email' => $this->input->post('input_email')
                 );
        
        $inser_id=$this->insurance_M->insert($data);
		
		if($inser_id>0)
		{
			
                $rvar=array(
                'insertid' =>$inser_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
}