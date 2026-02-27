<?php 
   class Doctor_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	function readdoctors(){
		$sql = "SELECT * FROM doctor_master;";
		$query = $this->db->query($sql);
		$result	= $query->result_array();
		//print_r($result);
		return $result;
	}
    
    public function insert($data) { 
        
         if ($this->db->insert("doctor_master", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
    
     public function insert_spec($data) { 
        
         if ($this->db->insert("doc_spec", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function insert_fee($data) { 
        
         if ($this->db->insert("doc_opd_fee", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function update_fee($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("doc_opd_fee", $data)) { 
           return 1; 
         }else
         return 0; 
    }
    
    public function update($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("doctor_master", $data)) { 
           return 1; 
         }else
         return 0;
    }

     


    public function delete_fee($delete_id_no) { 
       $this->db->where('id', $delete_id_no);
        $q = $this->db->get('doc_opd_fee');
        // if id is unique, we want to return just one row
        $data = $q->row_array();
        
        $user = $this->ion_auth->user()->row();
        $user_id = $user->id;
        $user_name = $user->first_name.''. $user->last_name . '['.date("d-m-Y h:i").']';
    
        $data['remove_update_by']=$user_name;

        if ($this->db->insert("doc_opd_fee_update", $data)) { 
            $this->db->where("id", $delete_id_no); 
            $this->db->delete("doc_opd_fee");
            return 1;
         }else{
            return 0; 
         }
   }

    //opd_prescription_template

   public function insert_opd_prescription_template($data) { 
      if ($this->db->insert("opd_prescription_template", $data)) { 
         $insert_id = $this->db->insert_id();
         return $insert_id; 
      }else
      return 0; 
   }

   //opd_prescription_template  -> Sub opd_prescrption_prescribed_template
   public function insert_opd_prescrption_prescribed_template($data) { 
      if ($this->db->insert("opd_prescrption_prescribed_template", $data)) { 
         $insert_id = $this->db->insert_id();
         return $insert_id; 
      }else
      return 0; 
   }

   public function remove_p_medical($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescrption_prescribed_template");
    }
       


   public function update_opd_prescription_template($data,$old_id_no) { 
      $this->db->set($data); 
      $this->db->where("id", $old_id_no); 
      if ($this->db->update("opd_prescription_template", $data)) { 
         return 1; 
       }else
       return 0;
  }

  public function update_opd_prescrption_prescribed_template($data,$old_id_no) { 
   $this->db->set($data); 
   $this->db->where("id", $old_id_no); 
   if ($this->db->update("opd_prescrption_prescribed_template", $data)) { 
      return 1; 
    }else
    return 0;
}
   


} 
?>