<?php 
	class Patient_M extends CI_Model {
	
	function __construct() { 
		parent::__construct(); 
	}
			
    public function insert($data) { 
        
		if ($this->db->insert("patient_master", $data)) { 
            $insert_id = $this->db->insert_id();
			$pid=1000000+$insert_id;
			
			$pid='P'.date('ym').$pid;
			$data = array( 
                    'p_code' => $pid
			);				
            
			$this->db->set($data); 
			$this->db->where("id", $insert_id); 
			$this->db->update("patient_master", $data);
			
            return $insert_id; 
		}else
			return 0; 
	}
    
	public function update($data,$old_id_no) { 
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$sql="select * from patient_master where id=".$old_id_no;
        $query = $this->db->query($sql);
        $data1=$query->result_array();

		if(count($data1)>0)
        {
            $update_emp_name=$user_id.'['.$user_name.']'.date('Y-m-d H:i:s');
            $Old_Update_data=($data1[0]['log']==''?' ':$data1[0]['log']).PHP_EOL;

			$change_data=compare_arrays($data1[0],$data);
			if(strlen($change_data)>0)
			{
				$data['log']=$Old_Update_data.$change_data.'Update By :'.$update_emp_name;
				$data['last_update']=date('Y-m-d H:i:s');
			}
            
        }

		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("patient_master", $data);
	}

	public function update_online($data,$old_id_no) { 
		
		$user_id = 0;
		$user_name = "online OPD";

		$sql="select * from patient_master where id=".$old_id_no;
        $query = $this->db->query($sql);
        $data1=$query->result_array();

		if(count($data1)>0)
        {
            $update_emp_name=$user_id.'['.$user_name.']'.date('Y-m-d H:i:s');
            $Old_Update_data=($data1[0]['log']==''?' ':$data1[0]['log']).PHP_EOL;

			$change_data=compare_arrays($data1[0],$data);
			if(strlen($change_data)>0)
			{
				$data['log']=$Old_Update_data.$change_data.'Update By :'.$update_emp_name;
				$data['last_update']=date('Y-m-d H:i:s');
			}
            
        }

		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("patient_master", $data);
	}
		
    public function insert_card($data) { 
        
		if ($this->db->insert("hc_insurance_card", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
			return 0; 
	}

	public function insert_log_patient_multiple($data) { 
        
		if ($this->db->insert("log_patient_multiple", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
			return 0; 
	}
		
	public function update_card($data,$old_id_no) { 
		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("hc_insurance_card", $data); 
	}

	function get_city($q){
		$this->db->select('*');
		$this->db->like('city', $q);
		$query = $this->db->get('city_auto_u');
		if($query->num_rows() > 0){
			foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['city'])).' | '.htmlentities(stripslashes($row['district'])).' | '.htmlentities(stripslashes($row['state']));
				$new_row['value']=htmlentities(stripslashes($row['city']));
				$new_row['l_city']=htmlentities(stripslashes($row['city']));
				$new_row['l_district']=htmlentities(stripslashes($row['district']));
				$new_row['l_state']=htmlentities(stripslashes($row['state']));
				$row_set[] = $new_row; //build an array
			}
		  echo json_encode($row_set); //format the array into json data
		}
	}


	public function insert_remark($data) { 
        
		if ($this->db->insert("patient_remark", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
			return 0; 
	}

}
?> 