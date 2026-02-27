<?php 
   class Ipd_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	function readopd(){
		$sql = "SELECT * FROM ipd_master;";
		$query = $this->db->query($sql);
		$result	= $query->result_array();
		//print_r($result);
		return $result;
	}
    
    public function insert($data) { 
        
		$sql = "SELECT * FROM ipd_master where ipd_status=0 and p_id=".$data['master']['p_id'];
		$query = $this->db->query($sql);
		$ipd_info= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' ['.date('d-m-Y H:i:s');
		
		if(count($ipd_info)<1)
		{
			if ($this->db->insert("ipd_master", $data['master'])) { 
            $insert_id = $this->db->insert_id();
			
			$sql="SELECT count(*) as xtimes from ipd_master where id<=".$insert_id." and p_id=".$data['master']['p_id'];
			$query = $this->db->query($sql);
			$ipd_times= $query->result();

			$pid=str_pad(substr($insert_id,-7,7),7,0,STR_PAD_LEFT);
			$pid='A'.date('ym').$pid;
			$udata = array( 
                    'ipd_code' => $pid,
                    'ipd_times' => $ipd_times[0]->xtimes,
			);				
            
			$this->db->set($udata); 
			$this->db->where("id", $insert_id); 
			$this->db->update("ipd_master", $udata);
			
			foreach ($data['doc_list'] as $doc_id)
			{
				$doc_data=array(
					'doc_id' => $doc_id, 
                	'ipd_id' => $insert_id,
					'log'=>'Insert By :'.$user_name
					);
				$this->db->insert("ipd_master_doc_list", $doc_data);
			}
			
            return $insert_id; 
         }else
         return 0; 
		}else{
			return $ipd_info[0]->id;
		}
		
    }
	
	public function add_ipd_doc($doc_data)
	{
		$insert_id=0;
		if($this->db->insert("ipd_master_doc_list", $doc_data))
		{
			$insert_id = $this->db->insert_id();
		}
		return $insert_id;
	}


	public function remove_ipd_doc($ipd_doc_id)
	{
		$this->db->where('id', $del_id);
		$q = $this->db->get('ipd_master_doc_list');
		$data = $q->row_array();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$data['update_by_id']=$user_id;
		$data['update_by']=$user_name.'['.date('Y-m-d H:i:s').']';
		
		if ($this->db->insert("ipd_master_doc_list_delete", $data)) { 
            //$this->db->where("id", $del_id); 
			$this->db->delete("ipd_master_doc_list","id=".$del_id);
			return 1;
         }else{
			return 0; 
		 }
	}
    
	public function bedassign($data) { 
	    if ($this->db->insert("ipd_bed_assign", $data)) { 
            $insert_id = $this->db->insert_id();
			
			$datau = array( 
                    		'bed_used_p_id' => 0
			);				
            
			$this->db->set($datau); 
			$this->db->where("bed_used_p_id",$data['ipd_id']); 
			$this->db->update("hc_bed_master", $datau);
			
			$datau = array( 
                    'bed_used_p_id' => $data['ipd_id']
			);

			$this->db->set($datau); 
			$this->db->where("id", $data['bed_id']); 
			$this->db->update("hc_bed_master", $datau);
			
            return $insert_id; 
         }else
         return 0; 
    }
	
	public function calculate_IPD($IPD_NO)
	{
		$this->db->query("CALL p_cal_ipd_master($IPD_NO)");
	}

	public function update($data,$old_id_no) { 
				
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$sql="select * from ipd_master where id=".$old_id_no;
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
			}
            
        }

        $this->db->where("id", $old_id_no); 
        $this->db->update("ipd_master", $data); 
    } 
    
	public function insert_ipd_item($data) { 
        
         if ($this->db->insert("ipd_invoice_item", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
    }
   
	public function update_ipd_item($data,$old_id_no) { 
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$sql="select * from ipd_invoice_item where id=".$old_id_no;
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
			}
        }

        $this->db->where("id", $old_id_no); 
        $this->db->update("ipd_invoice_item", $data); 

    } 
	
	public function deleteIpdinvoiceitem($del_id)
	{
		$this->db->where('id', $del_id);
		$q = $this->db->get('ipd_invoice_item');
		$data = $q->row_array();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$data['update_by_id']=$user_id;
		$data['update_by']=$user_name;
		$data['update_action']='1';
		
		if ($this->db->insert("ipd_invoice_item_update", $data)) { 
            //$this->db->where("id", $del_id); 
			$this->db->delete("ipd_invoice_item","id=".$del_id);
			return 1;
         }else{
			return 0; 
		 }
	}
	
	public function insert_package($data) { 
        
         if ($this->db->insert("ipd_package", $data)) { 
            $insert_id = $this->db->insert_id();
			return $insert_id; 
         }else
         return 0; 
    }
   
	public function update_package($data,$old_id_no) { 
		$this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        $this->db->update("ipd_package", $data); 
    } 
	
	public function deletepackage($del_id)
	{
		$this->db->where('id', $del_id);
		$q = $this->db->get('ipd_package');
		$data = $q->row_array();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' D:'.date('d-m-Y h:i:s');
	
		$data['update_by_id']=$user_id;
		$data['update_by_action']=$user_name;
		$data['update_action']='1';
		
		if ($this->db->insert("ipd_package_update", $data)) { 
            //$this->db->where("id", $del_id); 
			$this->db->delete("ipd_package","id=".$del_id);
			return 1;
         }else{
			return 0; 
		 }
	}


	public function insert_refer_ipd($data) { 
		if ($this->db->insert("ipd_refer", $data)) { 
		   $insert_id = $this->db->insert_id();
		   return $insert_id; 
		}else
		return 0; 
   }
  
   public function remove_refer_ipd($del_id)
   {
	   $this->db->where('id', $del_id);
	   $q = $this->db->get('ipd_refer');
	   $data = $q->row_array();
	   
	   $user = $this->ion_auth->user()->row();
	   $user_id = $user->id;
	   $user_name = $user->first_name.''. $user->last_name.' D:'.date('d-m-Y h:i:s');
   
	   $data['update_by_id']=$user_id;
	   $data['update_by_action']=$user_name;
	   $data['update_action']='1';
	   
	   if ($this->db->insert("ipd_refer_update", $data)) { 
		   //$this->db->where("id", $del_id); 
		   $this->db->delete("ipd_refer","id=".$del_id);
		   return 1;
		}else{
		   return 0; 
		}
   }
	
} 
?>