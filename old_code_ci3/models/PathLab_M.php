<?php 
   class PathLab_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
		
    }
	
	public function update_report($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("mstRepoKey", $old_id_no); 
         $this->db->update("lab_repo", $data); 
    }
	
	public function insert_report($data)
	{
		$this->db->insert("lab_repo", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ;
	}

	public function update_ultrasound_report($data,$old_id_no) { 
		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("radiology_ultrasound_template", $data); 
   }
   
   public function insert_ultrasound_report($data)
   {
	   $this->db->insert("radiology_ultrasound_template", $data);
	   $insert_id = $this->db->insert_id();
	   return $insert_id ;
   }
	
	public function update_item_parameter($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("mstTestKey", $old_id_no); 
         $this->db->update("lab_tests", $data); 
    }
	
	public function insert_item_parameter($data)
	{
		$this->db->insert("lab_tests", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ;
	}
	
	public function update_item_parameter_option($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_tests_option", $data); 
    }
	
	public function insert_item_parameter_option($data)
	{
		$this->db->insert("lab_tests_option", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ;
	}
	
	public function insert_item_sortorder($data) { 
        $this->db->insert("lab_repotests", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ; 
    }
	
	public function update_item_sortorder($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_repotests", $data); 
    }
	
	public function insert_test_request($data) { 
        $this->db->insert("lab_request", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ; 
    }
	
	public function update_test_request($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_request", $data); 
    }
	
	public function insert_test_entry($data) { 
        $this->db->insert("lab_request_item", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ; 
    }
	
	public function update_test_entry($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_request_item", $data); 
    }
	
	public function delete_request_entry($delete_id_no) { 
       $this->db->where('id', $delete_id_no);
		
	   $q = $this->db->get('lab_request');
		// if id is unique, we want to return just one row
		$data = $q->row_array();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name . '['.date("d-m-Y h:i").']';
	
		$data['remove_update_by']=$user_name;
				
		if ($this->db->insert("lab_request_delete", $data)) { 
            $this->db->where("id", $delete_id_no); 
			$this->db->delete("lab_request");
			return 1;
         }else{
			return 0; 
		 }
    }
	
	public function insert_invoice_report($data) { 
        $this->db->insert("lab_invoice_request", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ; 
    }
	
	public function update_invoice_report($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_invoice_request", $data); 
	}
	
	public function insert_lab_log($data) { 
        $this->db->insert("lab_log", $data);
		$insert_id = $this->db->insert_id();
		return $insert_id ; 
    }
	
	public function update_lab_log($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("lab_log", $data); 
    }
} 
?>
