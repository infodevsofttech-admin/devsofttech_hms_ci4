<?php 
   class Prescription_M extends CI_Model {
	
    function __construct() { 
		parent::__construct(); 
    }
	
	public function insert_opd_prescription($data) { 
        if ($this->db->insert("opd_prescription", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_opd_prescription($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescription", $data); 
    } 
        
	
	
	public function insert_p_investigation($data) { 
		if ($this->db->insert("opd_prescription_investigation", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
        
	public function remove_p_investigation($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescription_investigation");
    }
	

	// Prescription Surgery
	
    public function insert_p_surgery($data) { 
		if ($this->db->insert("opd_prescription_procedures", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_p_surgery($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescription_procedures", $data); 
    }
	
	public function remove_p_surgery($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescription_procedures");
    }
	
	// Prescription Complaint
	
    public function insert_p_complaint($data) { 
		if ($this->db->insert("opd_prescription_complaint", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_p_complaint($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescription_complaint", $data); 
    }
	
	public function remove_p_complaint($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescription_complaint");
    }
	
	
	// Prescription Diagnosis 
	
    public function insert_p_diagnosis($data) { 
		if ($this->db->insert("opd_prescription_diagnosis", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_p_diagnosis($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescription_diagnosis", $data); 
    }
	
	public function remove_p_diagnosis($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescription_diagnosis");
    }
	
	// Prescription Medical 
	
    public function insert_p_medical($data) { 
		if ($this->db->insert("opd_prescrption_prescribed", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_p_medical($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescrption_prescribed", $data); 
    }
	
	public function remove_p_medical($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescrption_prescribed");
    }

    //OPD Med Master

    public function insert_med_master($data) { 
        if ($this->db->insert("opd_med_master", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
        return 0; 
    }

    public function update_med_master($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("opd_med_master", $data)) { 
            return 1; 
        }else
            return 0; 
    }

    public function remove_med_master($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_med_master");
    }

    // opd advice 

    public function insert_opd_patient_advice($data) { 
		if ($this->db->insert("opd_prescription_advice", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_opd_patient_advice($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("opd_prescription_advice", $data); 
    }
	
	public function remove_opd_patient_advice($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("opd_prescription_advice");
    }

    public function insert_patient_glucose_chart($data) { 
		if ($this->db->insert("patient_glucose_chart", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_patient_glucose_chart($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("patient_glucose_chart", $data); 
    }
	
	public function remove_patient_glucose_chart($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("patient_glucose_chart");
    }


    public function insert_patient_investigation($data) { 
		if ($this->db->insert("patient_investigation", $data)) { 
			$insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
            return 0; 
    }
	
	public function update_patient_investigation($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("patient_investigation", $data); 
    }
	
	public function remove_patient_investigation($delid) { 
		$this->db->where("id", $delid); 
		$this->db->delete("patient_investigation");
    }

    // Update 
    public function update_patient_morbidities($p_id,$morbidities,$remove=0) { 
        $sql="SELECT * FROM patient_morbidities WHERE p_id = $p_id and morbidities = $morbidities";
        $query = $this->db->query($sql);
        $result = $query->result();

        $count = count($result);

        $m_id=0;

        if($count>0){
            $m_id=$result[0]->id;
        }
        
        $data=array(
            'p_id' => $p_id,
            'morbidities'=> $morbidities,
            'update_date'=> date('Y-m-d h:m:i'),
        );

        if($remove==0){
            if ($m_id==0) {
                $m_id=$this->db->insert('patient_morbidities', $data); 
            }
        }elseif($remove==1){
            $this->db->where("id", $m_id); 
		    $this->db->delete("patient_morbidities");
        }
       
        return $m_id;
        
    }
} 
?> 