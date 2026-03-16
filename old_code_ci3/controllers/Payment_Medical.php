<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_Medical extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
		
    }
    
    function index()
    {
        $this->load->view('Medical/payment_search');
    }

    function payment_record()
    {
        $sdata=$this->input->post('txtsearch');
        
		$rec_no = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata));

       	$sql="select *,if(credit_debit=0,amount,amount*-1) as Amount_str from payment_history_medical where  id=".$rec_no;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();

        $sql="select * from users where  active=1";
		$query = $this->db->query($sql);
        $data['all_user_list']= $query->result_array();
      
        $data['invoice_no']='';

        if(count($data['payment_history'])>0)
        {
            $Customerof_type=$data['payment_history'][0]->Customerof_type;
            $Customerof_id=$data['payment_history'][0]->Customerof_id;
            $Medical_invoice_id=$data['payment_history'][0]->Medical_invoice_id;
            $ipd_id=$data['payment_history'][0]->ipd_id;
           
            if($Customerof_type==1) //OPD Bill
            {
                $sql="select * from invoice_med_master where  id=".$Medical_invoice_id;
                $query = $this->db->query($sql);
                $data['invoice_med_master']= $query->result();

                $data['invoice_to_name']=$data['invoice_med_master'][0]->inv_name;

                $data['invoice_no']=$data['invoice_med_master'][0]->inv_med_code;

                $data['inv_Type']='OPD';
            }

            if($Customerof_type==2) // IPD Bill
            {
                $sql="select * from ipd_master where  id=".$ipd_id;
                $query = $this->db->query($sql);
                $data['ipd_master']= $query->result();

                $data['invoice_to_name']=$data['ipd_master'][0]->P_name;

                $data['invoice_no']=$data['ipd_master'][0]->ipd_code;

                $data['inv_Type']='IPD Medical Bill';
            }

            if($Customerof_type==3) //CASH BILL
            {
                $sql="select * from invoice_med_master where  id=".$Medical_invoice_id;
                $query = $this->db->query($sql);
                $data['invoice_med_master']= $query->result();
                
                $data['invoice_to_name']=$data['invoice_med_master'][0]->inv_name;

                $data['invoice_no']=$data['invoice_med_master'][0]->inv_med_code;

                $data['inv_Type']='CASH';
            }

            $data['rec_no']=$rec_no;

            $this->load->view('Medical/payment_edit',$data);

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

        $sql="select * from payment_history_medical where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            

            $paydata = array( 
                'amount'=>$payment_history[0]->amount,
                'payment_mode'=> '2',
                'card_bank'=>$this->input->post('input_card_mac'),
                'cust_card'=>$this->input->post('input_card_bank'),
                'card_remark'=>$this->input->post('input_card_digit'),
                'card_tran_id'=>$this->input->post('input_card_tran'),
            );
            
            $paydata_log = array( 
                'pay_id'=> $pay_id,
                'update_type'=>'2',
                'update_by'=> $user_name,
            );
                
            $this->load->model('Medical_M');

            $insert_id=$this->Medical_M->insert_payment_history_log($paydata_log);
            $this->Medical_M->update_payment($paydata,$pay_id);
            
        }
    }
    
    public function change_to_cash()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        
        $pay_id=$this->input->post('pay_id');

        $sql="select * from payment_history_medical where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            

            $paydata = array( 
                'payment_mode'=> '1',
                'amount'=>$payment_history[0]->amount,
            );
            
            $paydata_log = array( 
                'pay_id'=> $pay_id,
                'update_type'=>'1',
                'update_by'=> $user_name,
            );
                
            $this->load->model('Medical_M');

            $insert_id=$this->Medical_M->insert_payment_history_log($paydata_log);
            $this->Medical_M->update_payment($paydata,$pay_id);
            
        }
        
	}

    public function update_amount()
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
        
        $pay_id=$this->input->post('pay_id');
        $change_value=$this->input->post('change_value');

        $sql="select * from payment_history_medical where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        if(count($payment_history)>0)
        {
            $paydata = array( 
                'amount'=> $change_value,
            );

            $old_amt=$payment_history[0]->amount;
            
            $paydata_log = array( 
                'pay_id'=> $pay_id,
                'update_type'=>'3',
                'update_by'=> $user_name,
                'update_log'=>'Old Amt. : '.$old_amt,
            );
                
            $this->load->model('Medical_M');

            $insert_id=$this->Medical_M->insert_payment_history_log($paydata_log);
            $this->Medical_M->update_payment($paydata,$pay_id);

            $customer_type=$payment_history[0]->Customerof_type;
            $inv_id=$payment_history[0]->Medical_invoice_id;
            $ipd_id=$payment_history[0]->Medical_invoice_id;

            if($customer_type==2){
                $this->Medical_M->update_invoice_group_gst($ipd_id,1);
            }else{
                
                $this->Medical_M->update_invoice_group($inv_id);
                $this->Medical_M->update_invoice_final($inv_id);
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

        $sql="select * from payment_history_medical where  id=".$pay_id;
        $query = $this->db->query($sql);
        $payment_history= $query->result();

        $sql="select * from users where  id=".$user_list;
        $query = $this->db->query($sql);
        $user_data= $query->result();


        if(count($payment_history)>0)
        {
            $amount = $payment_history[0]->amount;

            $paydata = array( 
                'update_by'=> $user_data[0]->first_name.' '.$user_data[0]->last_name.'['.$payment_history[0]->insert_time.']['.$user_list.']',
                'update_by_id'=> $user_list,
                'amount'=> $amount,
            );

            $paydata_log = array( 
                'pay_id'=> $pay_id,
                'update_type'=>'3',
                'update_by'=> $user_name,
                'update_remark'=>'Change Update User From :'.$payment_history[0]->update_by
            );

            $this->load->model('Medical_M');
            $insert_id=$this->Medical_M->insert_payment_history_log($paydata_log);
            $this->Medical_M->update_payment($paydata,$pay_id);


        }

	}

}

