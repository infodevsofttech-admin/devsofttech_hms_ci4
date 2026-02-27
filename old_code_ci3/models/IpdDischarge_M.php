<?php 
   class IpdDischarge_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	    
    public function insert_ipdDischarge($data) { 
        
         if ($this->db->insert("ipd_discharge", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
    }
    
    public function update_ipdDischarge($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge", $data); 
    }
	
	
	public function insert_ipd_discharge_1_a($data) { 
        
         if ($this->db->insert("ipd_discharge_1_a", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
    }
    
    public function update_ipd_discharge_1_a($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_1_a", $data); 
    }
	

    public function update_insert_ipd_discharge_1_b($data,$ipd_id,$gen_exam_id) { 
        $sql="select id from ipd_discharge_1_b where ipd_d_id=".$ipd_id." and col_id=".$gen_exam_id;
		$query = $this->db->query($sql);
        $discharge_general_exam= $query->result();
		
		if(count($discharge_general_exam)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $discharge_general_exam[0]->id); 
			$this->db->update("ipd_discharge_1_b", $data);

			return $discharge_general_exam[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_1_b", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }

	public function update_insert_ipd_discharge_1_d($data,$ipd_id,$gen_exam_id) { 
        $sql="select id from ipd_discharge_1_d where ipd_d_id=".$ipd_id." and col_id=".$gen_exam_id;
		$query = $this->db->query($sql);
        $discharge_general_exam= $query->result();
		
		if(count($discharge_general_exam)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $discharge_general_exam[0]->id); 
			$this->db->update("ipd_discharge_1_d", $data);

			return $discharge_general_exam[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_1_d", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }

	public function update_insert_ipd_discharge_1_e($data,$ipd_id,$gen_exam_id) { 
        $sql="select id from ipd_discharge_1_e where ipd_d_id=".$ipd_id." and col_id=".$gen_exam_id;
		$query = $this->db->query($sql);
        $discharge_general_exam= $query->result();
		
		if(count($discharge_general_exam)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $discharge_general_exam[0]->id); 
			$this->db->update("ipd_discharge_1_e", $data);

			return $discharge_general_exam[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_1_e", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }

    

	public function update_insert_ipd_discharge_1_b_final($data,$ipd_id,$gen_exam_id) { 
        $sql="select id from ipd_discharge_1_b_final where ipd_d_id=".$ipd_id." and col_id=".$gen_exam_id;
		$query = $this->db->query($sql);
        $discharge_general_exam= $query->result();
		
		if(count($discharge_general_exam)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $discharge_general_exam[0]->id); 
			$this->db->update("ipd_discharge_1_b_final", $data);

			return $discharge_general_exam[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_1_b_final", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	public function update_insert_ipd_discharge_2($data,$ipd_id) { 
        $sql="select id from ipd_discharge_2 where ipd_d_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $discharge_general_exam= $query->result();
		
		if(count($discharge_general_exam)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $discharge_general_exam[0]->id); 
			$this->db->update("ipd_discharge_2", $data);

			return $discharge_general_exam[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_2", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }

    //ipd_discharge_drug_food_interaction

    public function update_insert_ipd_discharge_drug_food_interaction($data,$ipd_id) { 
        $sql="select id from ipd_discharge_drug_food_interaction where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $ipd_discharge_drug_food_interaction= $query->result();
		
		if(count($ipd_discharge_drug_food_interaction)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $ipd_discharge_drug_food_interaction[0]->id); 
			$this->db->update("ipd_discharge_drug_food_interaction", $data);

			return $ipd_discharge_drug_food_interaction[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_drug_food_interaction", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	// Surgery Table

		
    public function insert_p_surgery($data) { 
		if ($this->db->insert("ipd_discharge_surgery", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_surgery($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_surgery", $data); 
    }
	
	public function remove_p_surgery($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_surgery");
    }
	
	// Prosedure Table

		
    public function insert_p_procedure($data) { 
		if ($this->db->insert("ipd_discharge_procedure", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_procedure($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_procedure", $data); 
    }
	
	public function remove_p_procedure($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_procedure");
    }
	


	// Prescription Complaint
	
    public function insert_p_complaint($data) { 
		if ($this->db->insert("ipd_discharge_complaint", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_complaint($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_complaint", $data); 
    }
	
	public function remove_p_complaint($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_complaint");
    }
	
	public function update_insert_ipd_discharge_complaint_remark($data,$ipd_id) { 
        $sql="select id from ipd_discharge_complaint_remark where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $data_rec= $query->result();
		
		if(count($data_rec)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $data_rec[0]->id); 
			$this->db->update("ipd_discharge_complaint_remark", $data);

			return $data_rec[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_complaint_remark", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	
	// Diagnosis 
	
    public function insert_p_diagnosis($data) { 
		if ($this->db->insert("ipd_discharge_diagnosis", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_diagnosis($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_diagnosis", $data); 
    }
	
	public function remove_p_diagnosis($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_diagnosis");
    }
	
	public function update_insert_ipd_discharge_diagnosis_remark($data,$ipd_id) { 
        $sql="select id from ipd_discharge_diagnosis_remark where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $data_rec= $query->result();
		
		if(count($data_rec)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $data_rec[0]->id); 
			$this->db->update("ipd_discharge_diagnosis_remark", $data);

			return $data_rec[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_diagnosis_remark", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	//Investigtions
	
	public function update_insert_ipd_discharge_investigtions_inhos($data,$ipd_id) { 
        $sql="select id from ipd_discharge_investigtions_inhos where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $data_rec= $query->result();
		
		if(count($data_rec)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $data_rec[0]->id); 
			$this->db->update("ipd_discharge_investigtions_inhos", $data);

			return $data_rec[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_investigtions_inhos", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	//Course in the hospital

	
	
	public function insert_p_course($data) { 
		if ($this->db->insert("ipd_discharge_course", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_course($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_course", $data); 
    }
	
	public function remove_p_course($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_course");
    }
	
	
	public function update_insert_ipd_discharge_course_remark($data,$ipd_id) { 
        $sql="select id from ipd_discharge_course_remark where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $data_rec= $query->result();
		
		if(count($data_rec)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $data_rec[0]->id); 
			$this->db->update("ipd_discharge_course_remark", $data);

			return $data_rec[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_course_remark", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	//Discharge Instructions 
	
	public function update_insert_ipd_discharge_instructions($data,$ipd_id) { 
        $sql="select id from ipd_discharge_instructions where ipd_id=".$ipd_id."";
		$query = $this->db->query($sql);
        $data_rec= $query->result();
		
		if(count($data_rec)>0)
		{
			$this->db->set($data); 
			$this->db->where("id", $data_rec[0]->id); 
			$this->db->update("ipd_discharge_instructions", $data);

			return $data_rec[0]->id; 
		}else{
			if ($this->db->insert("ipd_discharge_instructions", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			}else
			 return 0; 
		}
    }
	
	//Discharge Drugs
	
	public function insert_p_drug($data) { 
		if ($this->db->insert("ipd_discharge_prescrption_prescribed", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
	
	public function update_p_drug($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("ipd_discharge_prescrption_prescribed", $data); 
    }
	
	public function remove_p_drug($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("ipd_discharge_prescrption_prescribed");
    }
	
	
} 
?>