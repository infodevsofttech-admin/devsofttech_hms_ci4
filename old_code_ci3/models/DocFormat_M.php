<?php 
   class DocFormat_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }

    public function insert_master($data) { 
        
         if ($this->db->insert("doc_format_master", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
    
    public function insert_sub($data) { 
        
         if ($this->db->insert("doc_format_sub", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
    
    public function insert_sub_item($data) { 
        
         if ($this->db->insert("doc_format_sub_item", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
    
    public function update_master($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("df_id", $old_id_no); 
        if ($this->db->update("doc_format_master", $data)) { 
           return 1; 
         }else
         return 0; 
    }
    
    public function update_sub($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("doc_format_sub", $data)) { 
           return 1; 
         }else
         return 0;
    }
    
    public function update_sub_item($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("doc_format_sub_item", $data)) { 
           return 1; 
         }else
         return 0;
    }
    
    public function insert_patient_doc($data) { 
         if ($this->db->insert("patient_doc", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }
    
    public function update_patient_doc($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("patient_doc", $data)) { 
           return 1; 
         }else
         return 0; 
    }
    
    public function insert_patient_doc_raw($data) { 
        $this->db->insert("patient_doc_raw", $data);
        $insert_id = $this->db->insert_id();
        return $insert_id ; 
    }
    
    public function update_patient_doc_raw($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("patient_doc_raw", $data); 
    }
    
} 
?>