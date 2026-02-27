<?php 
	class Package_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) { 
        
         if ($this->db->insert("package", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function insert_type($data) { 
        
         if ($this->db->insert("package", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function update_type($data,$old_id_no) { 
        	 
		$this->db->set($data); 
        $this->db->where("itype_id", $old_id_no); 
        if ($this->db->update("package", $data)) { 
           return 1; 
         }else
        return 0;
    } 
	
	public function insert_in_item($data) { 
        
         if ($this->db->insert("package_insurance", $data)) { 
            $insert_id = $this->db->insert_id();
					
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function update_in_item($data,$old_id_no) { 
        
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,2 as action,'".$user_name."' as action_by  from package_insurance where id=".$old_id_no;
        $query = $this->db->query($sql);
        $insertdata= $query->result();
		
		if(count($insertdata)>0){
			$this->db->insert("package_insurance_update", $insertdata[0]);
		}
		
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("package_insurance", $data)) { 
           return 1; 
         }else
        return 0;
    }
	
	public function delete_in_item($data,$old_id_no) { 
        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,2 as action,'".$user_name."' as action_by  from package_insurance where id=".$old_id_no;
        $query = $this->db->query($sql);
        $insertdata= $query->result();
		
		if(count($insertdata)>0){
			$this->db->insert("package_insurance_update", $insertdata[0]);
			$this->db->delete("package_insurance", "id=".$old_id_no);
		}
		
		return 0;
    }
    
    public function update($data,$old_id_no) { 
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,1 as action,'".$user_name."' as action_by  from package where id=".$old_id_no;
        $query = $this->db->query($sql);
        $insertdata= $query->result();
		
		if(count($insertdata)>0){
			$this->db->insert("package_update", $insertdata[0]);
		}
		
		$this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("package", $data)) { 
           return 1; 
         }else
        return 0;
    } 
} 
?>