<?php 
   class ECHS_M extends CI_Model {
	
      function __construct() { 
         parent::__construct(); 
      }
      
      public function insert($data) { 
        
         if ($this->db->insert("echs_master", $data)) { 
            $insert_id = $this->db->insert_id();
			$pid=1000000+$insert_id;
			$pid=date('ym').$pid;
			
            return $insert_id; 
         }else
         return 0; 
      }
	  
	  public function insert_mem($data) { 
        
         if ($this->db->insert("echs_member", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
      }
	  
	  public function update_mem($data,$old_id_no) { 
        $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("echs_member", $data); 
      }
	  
      public function update($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("echs_master", $data); 
      } 
       
    
 } 
?> 