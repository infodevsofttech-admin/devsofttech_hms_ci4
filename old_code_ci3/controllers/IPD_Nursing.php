<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class IPD_Nursing extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}

	public function IpdList()
	{
		$sql = "select *,(paid_amount-(charge_amount+med_amount)) as balance from v_ipd_list where ipd_status=0 ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
        $this->load->view('IPD_Nursing/IPDList_V',$data);
    }

	public function ipd_panel($ipdcode,$stype=0)
    {
			
		if($stype > 0)
		{
			$sql="select id from ipd_master where ipd_code='".$ipdcode."' ";
			$query = $this->db->query($sql);
			$get_ipd_no= $query->result();
			
			$ipdno=$get_ipd_no[0]->id;
		}else{
			$ipdno=$ipdcode;
		}

		//calculate_IPD

		$this->load->model('Ipd_M');
		$this->Ipd_M->calculate_IPD($ipdno);

		$sql="select * from v_ipd_list where id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$data['ipd_info']= $query->result();
		
		$p_id=$data['ipd_info'][0]->p_id;

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age 
		from patient_master where id='".$p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
				
		$sql="select * 
		from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id 
		where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();

		$sql="select * 
		from  organization_case_master  
		where status=0 and case_type=1 and ipd_id=$ipdno and p_id=".$p_id;
        $query = $this->db->query($sql);
		$data['case_master']= $query->result();
				
		$sql="select * from ipd_master where id='".$ipdno."' ";
		$query = $this->db->query($sql);
		$ipd_master= $query->result();

		$data['ipd_master']=$ipd_master;

		$sql="select *,Date_Format(payment_date,'%d-%M-%Y') as pay_date_str,
		Concat(if(payment_mode=1,'Cash','BANK'),if(credit_debit=0,'','-Return')) as pay_mode 
		from payment_history 
		where  payof_type=4 and payof_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_payment_history']= $query->result();

		$this->load->view('IPD_Nursing/IPD_Panel',$data);
	}

	public function ipd_main_panel($ipdno)
	{
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days from ipd_master where id='".$ipdno."' ";
		$query = $this->db->query($sql);
        $data['ipd_info']= $query->result();
		
		$sql="select * from v_ipd_list where id='".$ipdno."' ";
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select *,concat_ws(' ','Dr.',p_fname,p_mname,p_lname) as doc_name from doctor_master where id='".$data['ipd_info'][0]->r_doc_id."' ";
		$query = $this->db->query($sql);
		$data['doc_info']= $query->result();
		
		$sql="select i.id,d.p_fname,d.id as doc_id from ipd_master_doc_list i join doctor_master d on i.doc_id = d.id where  ipd_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $data['ipd_doc_list']= $query->result();
		
		$sql="select * from  organization_case_master  
			where id=".$data['ipd_info'][0]->case_id;
        $query = $this->db->query($sql);
		$data['case_master']= $query->result();
		
		$sql="select * from  organization_case_master  
		where status=0 and case_type=1 and  ipd_id=0 and p_id=".$data['ipd_info'][0]->p_id;
        $query = $this->db->query($sql);
        $data['case_master_open']= $query->result();
		
		$sql="select * from doctor_master where active=1 order by p_fname";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$sql="select * from org_approved_status ";
        $query = $this->db->query($sql);
        $data['org_approved_status']= $query->result();

		$sql="select * from refer_master ";
        $query = $this->db->query($sql);
        $data['refer_master']= $query->result();

		

		$this->load->view('IPD_Nursing/ipd_main_panel',$data);
	}

	function ipd_treatment($ipd_id)
	{
		$sql="select * from ipd_treatment_chart where ipd_id=$ipd_id order by t_datetime";
        $query = $this->db->query($sql);
        $data['ipd_treatment_chart']= $query->result();
	

		$this->load->view('IPD_Nursing/ipd_treatment',$data);
	}


}