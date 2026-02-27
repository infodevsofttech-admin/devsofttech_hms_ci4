<?php 
   class Patienttag_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    public function insert_tag_master($data) { 
        
         if ($this->db->insert("tag_master", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function update_tag_master($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("tag_master", $data)) { 
           return 1; 
         }else
         return 0; 
   }
	
	public function insert_patient_tag_assign($data) { 
        
         if ($this->db->insert("patient_tag_assign", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function update_patient_tag_assign($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("cpatient_tag_assign", $data)) { 
           return 1; 
         }else
         return 0; 
    }
    
} 
?>