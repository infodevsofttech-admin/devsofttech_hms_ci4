<?php 
   class Payment_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) { 
        
         if ($this->db->insert("payment_history", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
    }
	
	public function update($data,$old_id_no) { 
         
		 $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("payment_history", $data); 
   } 

   public function insert_payment_history_log($data) { 
        
      if ($this->db->insert("payment_history_log", $data)) { 
         $insert_id = $this->db->insert_id();

      return $insert_id; 
      }else
      return 0; 
 }
	
	public function insert_refundorder($data) { 
        
         if ($this->db->insert("refund_order", $data)) { 
            $insert_id = $this->db->insert_id();

			return $insert_id; 
         }else
         return 0; 
    }


    public function update_ipd_payment_to_admin($Payment_data,$ipd_master) { 
      
      $ipd_no=$Payment_data['ipd_id'];
      $group_id=$Payment_data['Med_Group_id'];
      $amount=$Payment_data['amount'];

      $sql="Select * from inv_med_group where med_group_id=$group_id";
      $query = $this->db->query($sql);
      $inv_med_group= $query->result();

      $net_amount=0;
      $payment_received=0;
      if($Payment_data['cr_dr']==0){
         $last_payment=$amount;
      }else{
         $last_payment=$amount*-1;
      }
      $pay_datetime=$Payment_data['payment_date'];
      $pharmacy_name="'".M_store."'";
      $admin_phone_no="'".M_Admin_PhoneNo."'";
      $recevied_user=$Payment_data['update_by'];

      
      if(count($inv_med_group)>0){
         $net_amount=$inv_med_group[0]->net_amount;
         $payment_received=$inv_med_group[0]->payment_received;
      }

      $web_msg='Payment Received from IPD No. :'.$ipd_no.'<br/>';
      $web_msg.='Patient Name :'.$ipd_master[0]->P_name.'<br/>';
      $web_msg.='Total Billed amt. :'.$net_amount.'<br/>';
      $web_msg.='Total Paid amt. :'.$payment_received.'<br/>';
      $web_msg.='Last Paid amt. :'.$last_payment.'<br/>';
      $web_msg.='Pay Date Time :'.$pay_datetime.'<br/>';
      $web_msg.='Recevied By :'.$recevied_user.'<br/>';
      $web_msg.='Pharmacy Name :'.$pharmacy_name.'<br/>';

      $data = array( 
         'to_number'=> $admin_phone_no,
         'message'=>$web_msg,
      );

      if ($this->db->insert("sms_outbox", $data)) { 
         $insert_id = $this->db->insert_id();

      return $insert_id; 
      }else
      return 0; 
 }
	
	public function update_refundorder($data,$rid) { 
         $this->db->set($data); 
         $this->db->where("id", $rid); 
         $this->db->update("refund_order", $data); 
    }

    public function insert_org_payment($data) { 
        
         if ($this->db->insert("org_payment_request", $data)) { 
            $insert_id = $this->db->insert_id();

            return $insert_id; 
         }else
         return 0; 
    }
    
    public function update_org_payment($data,$rid) { 
        
         $this->db->set($data); 
         $this->db->where("id", $rid); 
         $this->db->update("org_payment_request", $data); 
    }

   public function cancel_in_payment_history($old_id_no) { 
         $user = $this->ion_auth->user()->row();
         $user_id = $user->id;
         $user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
    
         $sql="select *,2 as action,'".$user_name."' as cancel_by,,'".$user_id."' as cancel_by_id
               from payment_history where id=".$old_id_no;
         $query = $this->db->query($sql);
         $insertdata= $query->result();
    
         if(count($insertdata)>0){
            $this->db->insert("payment_history_cancel", $insertdata[0]);
            $this->db->delete("payment_history", "id=".$old_id_no);
         }
         
         return 0;
  }
	
	
	
}
?>