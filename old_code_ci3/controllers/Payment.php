<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
		$this->load->model('Patient_M');

    }
    
    function index()
    {
        $this->load->view('Invoice/payment_search');
    }

    function payment_record()
    {
        $sdata=$this->input->post('txtsearch');
        
		$rec_no = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata));

       	$sql="select * from payment_history where  id='".$rec_no."'";
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();

        $sql="select * from users where  active=1";
		$query = $this->db->query($sql);
        $data['all_user_list']= $query->result_array();


        $data['invoice_no']='';

        if(count($data['payment_history'])>0)
        {
            $charge_type=$data['payment_history'][0]->payof_type;
            $payof_id=$data['payment_history'][0]->payof_id;


            if($charge_type==1)
            {
                $sql="select * from opd_master where  opd_id=".$payof_id;
                $query = $this->db->query($sql);
                $data['opd_master']= $query->result();

                $pid=$data['opd_master'][0]->p_id;

                $data['invoice_no']=$data['opd_master'][0]->opd_code;

                $data['inv_Type']='OPD';
            }

            if($charge_type==2)
            {
                $sql="select * from invoice_master where  id=".$payof_id;
                $query = $this->db->query($sql);
                $data['invoice_master']= $query->result();

                $pid=$data['invoice_master'][0]->attach_id;

                $data['invoice_no']=$data['invoice_master'][0]->invoice_code;

                $data['inv_Type']='OPD Charge';
            }

            if($charge_type==3)
            {
                $sql="select * from org_payment_request where  id=".$payof_id;
                $query = $this->db->query($sql);
                $data['org_payment_request']= $query->result();

                $pid=$data['org_payment_request'][0]->patient_id;

                $data['invoice_no']=$data['org_payment_request'][0]->org_code;

                $data['inv_Type']='ORG Charge';
            }

            if($charge_type==4)
            {
                $sql="select * from ipd_master where  id=".$payof_id;
                $query = $this->db->query($sql);
                $data['ipd_master']= $query->result();

                $pid=$data['ipd_master'][0]->p_id;

                $data['invoice_no']=$data['ipd_master'][0]->ipd_code;

                $data['inv_Type']='IPD Payment';
            }

            $sql="select * from patient_master_exten where  id=".$pid;
            $query = $this->db->query($sql);
            $data['patient_master']= $query->result();

            $data['rec_no']=$rec_no;

            $sql="select s.id,s.pay_type,m.bank_name
				From hospital_bank m join hospital_bank_payment_source s on m.id=s.bank_id";
            $query = $this->db->query($sql);
            $data['bank_data']= $query->result();

            $this->load->view('Invoice/payment_edit',$data);

        }else{
            echo 'No Record Found';
        }
    }

	public function change_to_bank()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        
        $pay_id=$this->input->post('pay_id');

        $sql="select * from payment_history where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            $payof_id=$payment_history[0]->payof_id;

            $paydata = array( 
                'payment_mode'=> '2',
                'pay_bank_id'=>$this->input->post('cbo_pay_type'),
                'card_tran_id'=>$this->input->post('input_card_tran'),
                'update_remark'=>'Change To Bank'
            );
            
            $paydata_log = array( 
                'pay_id'=> $pay_id,
                'update_type'=>'1',
                'update_by'=> $user_name,
            );
                
            $this->load->model('Payment_M');

            $insert_id=$this->Payment_M->insert_payment_history_log($paydata_log);
            $this->Payment_M->update($paydata,$pay_id);

            if($payment_history[0]->payof_type==1)
            {
                $dataupdate = array( 
                    'payment_mode'=> '2',
                    'payment_mode_desc'=>'BANK',
                );

                $this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$payof_id);

            }elseif($payment_history[0]->payof_type==4)
            {
                $dataupdate = array( 
                    'payment_mode'=> '2',
                    'payment_mode_desc'=>'BANK',
                );
                $this->load->model('Ipd_M');
                //$this->Ipd_M->update_payment_ipd($dataupdate,$payof_id);
            }

        }
    }
    
    public function change_to_cash()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        
        $pay_id=$this->input->post('pay_id');

        $sql="select * from payment_history where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            $payof_id=$payment_history[0]->payof_id;

            $paydata = array( 
                'payment_mode'=> '1',
            );
            
            $paydata_log = array( 
                'pay_id'=> $payof_id,
                'update_type'=>'1',
                'update_by'=> $user_name,
                'update_remark'=>'Change To CASH'
            );
            
            $this->load->model('Payment_M');
            $insert_id=$this->Payment_M->insert_payment_history_log($paydata_log);
            $this->Payment_M->update($paydata,$pay_id);

            if($payment_history[0]->payof_type==1)
            {
                $dataupdate = array( 
                    'payment_mode'=> '1',
                    'payment_mode_desc'=>'CASH',
                );

                $this->load->model('Opd_M');
				$this->Opd_M->update($dataupdate,$payof_id);

            }elseif($payment_history[0]->payof_type==4)
            {
                $dataupdate = array( 
                    'payment_mode'=> '1',
                    'payment_mode_desc'=>'CASH',
                );

                $this->load->model('Ipd_M');
                //$this->Ipd_M->update_payment_ipd($dataupdate,$payof_id);
            }

        }


	}
 
    public function change_user()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';

        $pay_id=$this->input->post('pay_id');
        $user_list=$this->input->post('user_list');

        $sql="select * from payment_history where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        $sql="select * from users where  id=".$user_list;
        $query = $this->db->query($sql);
        $user_data= $query->result();


        if(count($payment_history)>0)
        {
            $payof_id=$payment_history[0]->payof_id;

            $paydata = array( 
                'update_by'=> $user_data[0]->first_name.' '.$user_data[0]->last_name.'['.$payment_history[0]->insert_time.']['.$user_list.']',
                'update_by_id'=> $user_list,
            );

            $paydata_log = array( 
                'pay_id'=> $payof_id,
                'update_type'=>'3',
                'update_by'=> $user_name,
                'update_remark'=>'Change Update User From :'.$payment_history[0]->update_by
            );

            $this->load->model('Payment_M');
            $insert_id=$this->Payment_M->insert_payment_history_log($paydata_log);
            $this->Payment_M->update($paydata,$pay_id);


        }

	}

    public function change_amount()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';

        $pay_id=$this->input->post('pay_id');
        $change_value=$this->input->post('change_value');

        $sql="select * from payment_history where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            $payof_id=$payment_history[0]->payof_id;

            $paydata = array( 
                'amount'=> $change_value,
            );
            
            $paydata_log = array( 
                'pay_id'=> $payof_id,
                'update_type'=>'4',
                'update_by'=> $user_name,
                'update_remark'=>'Change Amount , OLD Amt.'.$payment_history[0]->amount
            );
            
            $this->load->model('Payment_M');
            $insert_id=$this->Payment_M->insert_payment_history_log($paydata_log);
            $this->Payment_M->update($paydata,$pay_id);
          

        }
        
	}
}

