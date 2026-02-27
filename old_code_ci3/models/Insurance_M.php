<?php 
	class Insurance_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) { 
        
         if ($this->db->insert("hc_insurance", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
         }else
         return 0; 
    }
    
    public function update($data,$old_id_no) { 
        	 
		$this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("hc_insurance", $data)) { 
           return 1; 
         }else
        return 0;
    } 
} 
?>