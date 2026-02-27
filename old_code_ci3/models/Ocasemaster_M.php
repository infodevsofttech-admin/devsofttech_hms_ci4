<?php 
	class Ocasemaster_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) { 
        
        if ($this->db->insert("organization_case_master", $data)) { 
            $insert_id = $this->db->insert_id();
			
			$pid=str_pad(substr($insert_id,-7,7),7, '0', STR_PAD_LEFT);
			$pid='C'.date('ym').$pid;
			$data1 = array( 
                    'case_id_code' => $pid
			);				
            
			$this->db->set($data1); 
			$this->db->where("id", $insert_id); 
			$this->db->update("organization_case_master", $data1);
						
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("organization_case_master", $data);
        
        
    }


    public function insert_org_pack($data) { 
        if ($this->db->insert("org_packing", $data)) { 
            $insert_id = $this->db->insert_id();           
            return $insert_id; 
        }else
        return 0; 
   }
   
   public function update_org_pack($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("org_packing", $data); 
        return $old_id_no;
   }
}
?>