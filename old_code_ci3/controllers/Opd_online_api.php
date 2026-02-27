<?php
    require APPPATH . 'libraries/REST_Controller.php';
    
    class Opd_online_api extends REST_Controller {

/**
     * Get All Data from this method.
     *
     * @return Response
    */

    public function __construct() {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        parent::__construct();

    }

    public function index_get($id=0)
	{
        if(!empty($id)){
            $sql="select * from patient_master where id=".$id;
            $query = $this->db->query($sql);
            $data= $query->result();

        }else{
            $sql="select * from patient_master limit 10";
            $query = $this->db->query($sql);
            $data= $query->result();
        }

        if(!$data)
        {
            $rvar=array(
                    "no_record" =>0,
                );
            $encode_data = json_encode($rvar);
            $data= $encode_data;
        }

        $this->response($data, REST_Controller::HTTP_OK);
	}

    public function patient_code_get($p_code="0")
	{
        $data=null;

        if(!empty($p_code)){
            $sql="select * from patient_master where p_code='$p_code'";
            $query = $this->db->query($sql);
            $data= $query->result();
        }

        if(!$data)
        {
            $rvar=array(
                'no_record' =>0,
                );
            $encode_data = json_encode($rvar);
            $data= $encode_data;
        }
        $this->response($data, REST_Controller::HTTP_OK);
	}

    public function patient_list_get($p_code="0")
	{
        $data=null;

        if(!empty($p_code) || strlen($p_code)>3){
            $sql="select * from patient_master where p_code like '%$p_code' order by id desc limit 20";
            $query = $this->db->query($sql);
            $data= $query->result();
        }

        if(!$data)
        {
            $rvar=array(
                'no_record' =>0,
                );
            $encode_data = json_encode($rvar);
            $data= $encode_data;
        }
        $this->response($data, REST_Controller::HTTP_OK);
	}

    public function patient_phone_get($phone_number)
	{
        $data=null;

        if(!empty($phone_number)){
            $sql="select * from patient_master where mphone1='$phone_number' and mphone1<>''";
            $query = $this->db->query($sql);
            $data= $query->result();
        }

        if(!$data)
        {
            $rvar=array(
                'no_record' =>0,
                );
            $encode_data = json_encode($rvar);
            $data= $encode_data;
        }
        $this->response($data, REST_Controller::HTTP_OK);
	}

    public function patient_phone_list_get($phone_number)
	{
        $data=null;

        if(!empty($phone_number)){
            $sql="select * from patient_master where mphone1 like '$phone_number%' and mphone1<>'' order by id desc limit 20 ";
            $query = $this->db->query($sql);
            $data= $query->result();
        }

        if(!$data)
        {
            $rvar=array(
                'no_record' =>0,
                );
            $encode_data = json_encode($rvar);
            $data= $encode_data;
        }
        $this->response($data, REST_Controller::HTTP_OK);
	}

    public function patient_create_post()
    {
        $data = array( 
            'mphone1' => $this->input->post('mphone1'), 
            'p_fname' => strtoupper($this->input->post('p_fname')),
            'gender' => $this->input->post('gender'),
            'p_relative' => $this->input->post('p_relative'),
            'p_rname' => strtoupper($this->input->post('p_rname')),
            'dob' => $this->input->post('dob'),
            'estimate_dob' => 'estimate_dob',

        );

        $this->load->model('Patient_M');

        $inser_id=$this->Patient_M->insert($data); 

        if($inser_id>0)
        {
            $sql="Select * from patient_master where id=".$inser_id;
            $query = $this->db->query($sql);
            $data= $query->result();
            
            if(count($data)>0)
            {
                $rvar=[
                    'New_UHID' =>$data[0]->id,
                    'p_code' => $data[0]->p_code,
                ];
            }else{
                $rvar=[
                    'New_UHID' =>0,
                ];
            }
        }else{
            $rvar=[
                'New_UHID' =>0,
            ];
        }
        
        $response_data = json_encode($rvar);
        $response_data = $rvar;
        //$response_data = $data;

        $this->response($response_data, REST_Controller::HTTP_OK);

    }

    public function opd_register_post()
    {
        
		$user_name_info = 'OnlineUser ['.date('d-m-Y H:i:s').']';
        $p_id= $this->input->post('hospital_patient_id');
        $doc_id= $this->input->post('hospital_doc_id');
        $appointment_date= $this->input->post('appointment_date');
        $opd_fee= $this->input->post('opd_fee');

        //var_dump($this->input->post());

        $sql="select * from  patient_master  where id=".$p_id;
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
        
        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName,if(d.gender=1,'Male','Female') as xGender
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=".$doc_id." group by d.id ";
        $query = $this->db->query($sql);
        $data['doc_info']= $query->result();

		$sql="select * from  opd_master  where p_id=".$p_id." order by opd_id desc";
        $query = $this->db->query($sql);
		$data['opd_master']= $query->result();

        $sql="select * from  opd_master  where p_id=$p_id and apointment_date='$appointment_date' and doc_id=$doc_id ";
        $query = $this->db->query($sql);
		$opd_master_exit= $query->result();

        $this->load->model('Opd_M');

		$last_Vist_date='';

		if(count($data['opd_master'])>0)
		{
			$last_Vist_date=$data['opd_master'][0]->apointment_date;
		}

		$opd_fee_amount=$this->input->post('opd_fee');
		$opd_fee_desc="Online Booked";
		

        $data['insert'] = array( 
            'p_id' => $p_id,
			'P_name' => strtoupper($data['person_info'][0]->p_fname),			
			'doc_id' => $doc_id,
			'insurance_id' => '0',
			'opd_fee_id' => '0',
            'opd_fee_amount' => $opd_fee_amount,
			'opd_fee_gross_amount' => $opd_fee_amount,
            'opd_fee_desc' => $opd_fee_desc,
			'doc_name' => $data['doc_info'][0]->p_fname,
			'opd_fee_type' =>0,
			'apointment_date' =>$appointment_date,
            'doc_spec' => $data['doc_info'][0]->SpecName,
			'prepared_by' => $user_name_info,
            'payment_status'=>1,
            'payment_mode_desc'=> 'Online',
            'online_book'=> 1,
		); 

		$data['patient_master'] = array( 
            'last_visit' => $appointment_date,
		); 
		
		if(count($data['opd_master'])>0)
		{
			$data['insert']=array_merge($data['insert'],array('last_opdvisit_date'=>$data['opd_master'][0]->apointment_date));
		}

        $inser_id=0;

        if(count($opd_master_exit)==0){
            $inser_id=$this->Opd_M->insert( $data['insert']);
        }else{
            $inser_id=$opd_master_exit[0]->opd_id;
        }
        

		if($inser_id>0)
		{
			$this->load->model('Patient_M');
			$this->Patient_M->update_online($data['patient_master'],$p_id);
		
            $sql="select * from  opd_master  where opd_id=".$inser_id;
            $query = $this->db->query($sql);
            $opd_book= $query->result();

            if(count($opd_book)>0)
            {
                $rvar=[
                    'OPD_ID' =>$opd_book[0]->opd_id,
                    'DOC_NAME' =>$opd_book[0]->doc_name,
                    'BOOKNO' =>$opd_book[0]->opd_id.'-'.$opd_book[0]->opd_no,
                    'BOOKDATE' =>MysqlDate_to_str($opd_book[0]->apointment_date),
                    'PHONENO' =>$data['person_info'][0]->mphone1,
                ];
            }else{
                $rvar=[
                    'OPD_ID' =>0,
                ];
            }
        }else{
            $rvar=[
                'OPD_ID' =>0,
            ];
        }
                
        $response_data = json_encode($rvar);
        $response_data = $rvar;

        $this->response($response_data, REST_Controller::HTTP_OK);
    }

}