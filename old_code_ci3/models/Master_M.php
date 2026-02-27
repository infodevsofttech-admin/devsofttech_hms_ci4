<?php 
	class Master_M extends CI_Model {

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
//Location Master
    public function insert_location_master($data) { 
        
        if ($this->db->insert("location_master", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
        }else
            return 0; 
    }

    public function update_location_master($data,$old_id_no) { 
		$this->db->set($data); 
        $this->db->where("l_id", $old_id_no); 
        if ($this->db->update("location_master", $data)) { 
            return 1; 
        }else
            return 0;
    } 

    //Employee Master
    public function insert_employee_master($data) { 
        
        if ($this->db->insert("employee_master", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
        }else
            return 0; 
    }

    public function update_employee_master($data,$old_id_no) { 
		$this->db->set($data); 
        $this->db->where("emp_id", $old_id_no); 
        if ($this->db->update("employee_master", $data)) { 
            return 1; 
        }else
            return 0;
    } 
}