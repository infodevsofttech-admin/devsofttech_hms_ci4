<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opdcase extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}

    public function addopd($pno)
    {
        
		$sql="select p.*,if(p.gender=1,'Male','Female') as xgender,
		i.ins_company_name,i.short_name,i.opd_rate_direct,i.charge_rate_direct,
		i.opd_credit,i.charge_credit,i.opd_allowed,i.charge_cash,i.id as ins_id,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age 
		from patient_master p left join hc_insurance i on p.insurance_id=i.id  where p.id=".$pno;
		$query = $this->db->query($sql);
        $data['person_info']= $query->result();

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 group by d.id ";
        $query = $this->db->query($sql);
        $data['doc_spec_l']= $query->result();

		$sql="select * from hc_insurance_card where p_id='".$pno."' ";
		$query = $this->db->query($sql);
        $data['insurance_card']= $query->result();

        $this->load->view('opd_app_insurance_V',$data);
    }
    
    public function showfee()
    {
        $sql="select * from  doc_opd_fee  where  doc_id=".$this->input->post('doc_id');
        $query = $this->db->query($sql);
        $data['doc_fee_a']= $query->result();

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$this->input->post('doc_id')." group by d.id ";
        $query = $this->db->query($sql);
        $doc_info= $query->result();

        $content='';

        $content.= '<H4><strong>Name :</strong> '.$doc_info[0]->p_fname.' <strong>/ Specialization:</strong> '.$doc_info[0]->SpecName.' <strong>/ 
        Gender :</strong> '.$doc_info[0]->xGender.' <strong></H4>';
        $content.= '<input type="hidden" name="doc_id" id="doc_id" value="'.$doc_info[0]->id.'" />';

		$sql="select * from  hc_insurance where id=".$this->input->post('insurance_id');
		$query = $this->db->query($sql);
		$data['opd_fee_a']= $query->result();

		foreach($data['opd_fee_a'] as $row)
		{ 
            $content.= '<label>';
			$content.= '<input type="radio" name="fee_id" id="fee_id" class="flat-red" checked value='.$row->id.'> ';
			$content.= ' Rs. '.$row->opd_fee.'[Insurance OPD Fee Apply]';
			$content.= '</label><br/>';
		}

		$content.="</select>";

		$rvar=array(
            'csrfName' => $this->security->get_csrf_token_name(),
            'csrfHash' => $this->security->get_csrf_hash(),
            'content'=>$content
        );
        
        $encode_data = json_encode($rvar);

        echo $encode_data ;
    }
	
	public function invoice($opdid)
	{
		$sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date,
		(case payment_status when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'ECHS Credit' else 'Pending' end) as Payment_type_str  from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";

        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

		$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$data['opd_master'][0]->p_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from  organization_case_master  
			where status=0 and case_type=0 and p_id=".$data['patient_master'][0]->id;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();

		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();

		$sql="select * from  refund_order  where refund_process=0 and refund_type=1 and  refund_type_id=".$opdid;
		$query = $this->db->query($sql);
		$data['refund_order']= $query->result();

		if(count($data['refund_order'])>0)
		{
			$data['refund_status']=1; // Refund Pending
		}else{
			$data['refund_status']=0 ; //No Pending
		}

		$sql="select sum(if(credit_debit=0,amount,amount*-1)) as paid_amount from  payment_history  where payof_type=1 and  payof_id=".$opdid;
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();

		$data['paid_amount']=0;

		if($data['payment_history'][0]->paid_amount=='' || $data['payment_history'][0]->paid_amount==0 )
		{
			$data['paid_amount']=0;
		}else{
			$data['paid_amount']=$data['payment_history'][0]->paid_amount;
		}

		$opd_fee_gross_amount=$data['opd_master'][0]->opd_fee_amount;

		if($data['opd_master'][0]->payment_mode==4)
		{
			$data['pending_amount']=0;
		}else{
			$data['pending_amount']=$opd_fee_gross_amount-$data['paid_amount'];
		}
	

		$this->load->view('opd_invoice_V',$data);
	}
	
	public function invoice_print($opdid)
	{
		$sql="select * from  opd_master  where opd_id=".$opdid;
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$data['opd_master'][0]->p_id."' ";

        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

		$sql="select * from  hc_insurance  where id=".$data['opd_master'][0]->insurance_id;
		$query = $this->db->query($sql);
		$data['insurance']= $query->result();
		
		if($data['opd_master'][0]->insurance_case_id>0)
		{
			$sql="select * from  organization_case_master  where id=".$data['opd_master'][0]->insurance_case_id;
			$query = $this->db->query($sql);
			$data['case_master']= $query->result();
		}

		$this->load->view('invoice_print_v',$data);
	}
	
	public function confirm_opd()
    {
        $this->load->model('Opd_M');

        $sql="select * from  patient_master  where id=".$this->input->post('pid');
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$this->input->post('doc_id')." group by d.id ";
        $query = $this->db->query($sql);
        $data['doc_info']= $query->result();

		$sql="select * from  hc_insurance  where id=".$this->input->post('insurance_id');
		$query = $this->db->query($sql);
		$data['doc_fee']= $query->result();
	
		$opd_fee_amount=$data['doc_fee'][0]->opd_fee;
		$opd_fee_desc=$data['doc_fee'][0]->opd_desc;

        $data['insert'] = array( 
            'p_id' => $this->input->post('pid'),
			'P_name' => $data['person_info'][0]->p_fname,			
        	'doc_id' => $this->input->post('doc_id'),
			'insurance_id' => $this->input->post('insurance_id'),
			'opd_fee_id' => $this->input->post('fee_id'),
            'opd_fee_amount' => $opd_fee_amount,
			'opd_fee_gross_amount' => $opd_fee_amount,
            'opd_fee_desc' => $opd_fee_desc,
            'doc_name' => $data['doc_info'][0]->p_fname,
            'doc_spec' => $data['doc_info'][0]->SpecName,
			'apointment_date' =>str_to_MysqlDate($this->input->post('datepicker_appointment')),
			'opd_fee_type' => '1'
        );
	
        $inser_id=$this->Opd_M->insert( $data['insert']);
 		
		$rvar=array(
        'insertid' =>$inser_id
        );
		
        $encode_data = json_encode($rvar);
        echo $encode_data;

    }
	
	public function confirm_payment()
	{
		
	}
 }