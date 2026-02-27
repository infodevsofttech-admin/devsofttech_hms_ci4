<?php 
   class Invoice_M extends CI_Model 
   {

    function __construct()
    {
        parent::__construct();
    }
	
	public function create_invoice($data)
	{
		if ($this->db->insert("invoice_master", $data)) { 
            $insert_id = $this->db->insert_id();
			
			$pid=str_pad(substr($insert_id,-7,7),7,0,STR_PAD_LEFT);

			$pid='N'.date('ym').$pid;
			$data1 = array( 
                    'invoice_code' => $pid
			);				
            
			$this->db->set($data1); 
			$this->db->where("id", $insert_id); 
			$this->db->update("invoice_master", $data1);

            return $insert_id; 
         }else
         return 0; 
	}
	
	public function addinvoiceitem($data)
	{
		if ($this->db->insert("invoice_item", $data)) { 
			$insert_id = $this->db->insert_id();

			$this->db->query("CALL p_cal_invoice_byItem(".$insert_id.")");									
			
			return $insert_id; 
         }else
         return 0; 	
	}
	
	public function deleteinvoiceitem($del_id)
	{
		$this->db->where('id', $del_id);
		$q = $this->db->get('invoice_item');
		// if id is unique, we want to return just one row
		$data = $q->row_array();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$data['update_by_id']=$user_id;
		$data['update_by']=$user_name;
		$data['update_action']='1';
		
		if ($this->db->insert("invoice_item_update", $data)) { 
            $this->db->where("id", $del_id); 
			$this->db->delete("invoice_item");
			return 1;
         }else{
			return 0; 
		 }

		 $this->db->query("CALL p_cal_invoice_byItem(".$data['inv_master_id'].")");
	}
	
	public function update($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
		 $this->db->update("invoice_master", $data);
		 
		 $this->db->query("CALL p_cal_invoice_byItem(".$old_id_no.")");
    } 
	
	public function update_item($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
		 $this->db->update("invoice_item", $data);

		$this->db->where('id', $old_id_no);
		$q = $this->db->get('invoice_item');
		// if id is unique, we want to return just one row
		$data = $q->row_array();
		 
		$this->db->query("CALL p_cal_invoice_byItem(".$data['inv_master_id'].")");
    } 
	
	public function update_invoice_final($inv_id)
	{
		$this->db->query("CALL p_cal_invoice_byItem(".$inv_id.")");

	}
}