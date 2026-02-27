<?php 
    class SMS_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
        
    }
	
	public function insert_sms_inbox($data) { 
        
         if ($this->db->insert("sms_inbox", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }

    public function update_sms_inbox($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("sms_inbox", $data)) { 
           return 1; 
         }else
         return 0;
    }
	
	public function insert_sms_outbox($data) { 
         if ($this->db->insert("sms_outbox", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
         }else
         return 0; 
    }

    public function update_sms_outbox($data,$old_id_no) { 
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("sms_outbox", $data)) { 
           return 1; 
         }else
         return 0;
    }
} 
?>