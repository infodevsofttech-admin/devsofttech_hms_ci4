<?php 
   class Product_master_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data) { 
         if ($this->db->insert("med_product_master", $data)) { 
            $insert_id = $this->db->insert_id();
            return $insert_id; 
        }else
         return 0; 
    }
   
	

	public function update($data,$old_id_no) { 
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,'".$user_id."' as update_by_id,'".$user_name."' as update_by  
		from med_product_master where id=".$old_id_no;
        $query = $this->db->query($sql);
        $insertdata= $query->result();
		
		if(count($insertdata)>0){
			$this->db->insert("med_product_master_update", $insertdata[0]);
		}
		
        $this->db->set($data); 
        $this->db->where("id", $old_id_no); 
        if ($this->db->update("med_product_master", $data)) { 
           return 1; 
         }else
        return 0; 
	}

	public function delete_master($old_id_no) { 
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
		
		$sql="select *,'".$user_id."' as update_by_id,'".$user_name."' as update_by  
		from med_product_master where id=".$old_id_no;
        $query = $this->db->query($sql);
        $insertdata= $query->result();
		
		if(count($insertdata)>0){
			$this->db->insert("med_product_master_update", $insertdata[0]);
			$this->db->where("id", $old_id_no); 
			if ($this->db->delete("med_product_master")) { 
				return 1; 
			}else
				return 0; 
		}else{
			return 0; 
		}

	}


	public function insert_update_product_cat_assign($data,$med_id) {
		
		$sql="select m.id,m.med_cat_desc,a.med_cat_id AS a_id,t.id AS t_id , if(a.id=t.id,0,if(a.id IS NULL AND t.id=m.id,1,if(a.med_cat_id=m.id AND t.id IS NULL,2,0))) AS ins_del_action,a.id AS rec_id
			FROM (med_product_cat_master m Left join med_product_cat_assign a on m.id=a.med_cat_id and a.med_product_id=$med_id  )
			LEFT JOIN med_product_cat_master t ON m.id=t.id and t.id in ($data)";
        $query = $this->db->query($sql);
        $insertdata= $query->result();

		

		foreach($insertdata as $row)
		{
			if ($row->ins_del_action==1) { 
				$data_ins['med_product_id']=$med_id;
				$data_ins['med_cat_id']=$row->id;

				$this->db->insert("med_product_cat_assign", $data_ins);
			}elseif($row->ins_del_action==2){
				$this->db->where("id", $row->rec_id); 
				$this->db->delete("med_product_cat_assign");
			}
		}
		
		 return 1;
   	}
  

	public function insert_med_indent_request($data) { 
		 if ($this->db->insert("med_indent_request", $data)) { 
			$insert_id = $this->db->insert_id();
			
			$Ind_code=str_pad(substr($insert_id,-5,5),5,0,STR_PAD_LEFT);
			
			$Ind_code='R'.date('ymd').$Ind_code;
			
			$data = array( 
                'indent_code' => $Ind_code,
			);				

			$this->db->set($data); 
			$this->db->where("indent_id", $insert_id); 
			$this->db->update("med_indent_request", $data);

			return $insert_id; 
		}else
		 return 0; 
	}

	public function update_med_indent_request($data,$old_id_no) { 
		$this->db->set($data); 
		$this->db->where("indent_id", $old_id_no); 
		$this->db->update("med_indent_request", $data);
		 
	}

	public function insert_med_indent_request_items($data) { 
		 if ($this->db->insert("med_indent_request_items", $data)) { 
			$insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
		 return 0; 
	}

	public function update_med_indent_request_items($data,$old_id_no) { 
		 $this->db->set($data); 
		 $this->db->where("id", $old_id_no); 
		 $this->db->update("med_indent_request_items", $data); 
	}

	public function delete_med_indent_request_item($indent_id,$del_indent_item_id) { 

			$this->db->where("id", $del_indent_item_id); 
			$this->db->delete("med_indent_request_items");

			$this->db->where("indent_item_id", $del_indent_item_id); 
			$this->db->delete("med_indent_process_items");

			return 1;
	}
	
	//For Med_indent_process
	public function insert_med_indent_process($data) { 
		 if ($this->db->insert("med_indent_process", $data)) { 
			return 1; 
		}else
		 return 0; 
	}

	public function update_med_indent_process($data,$old_id_no) { 
		 $this->db->set($data); 
		 $this->db->where("indent_id", $old_id_no); 
		 $this->db->update("med_indent_process", $data); 
	}
	
	// Med_indent_process Items
	
	public function insert_med_indent_process_items($data) { 
		 if ($this->db->insert("med_indent_process_items", $data)) { 
			$insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
		 return 0; 
	}

	public function update_med_indent_process_items($data,$old_id_no) { 
		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("med_indent_process_items", $data); 
	}

	public function delete_med_indent_process_item($indent_id,$del_indent_item_id) { 
		$this->db->where("id", $del_indent_item_id); 
		$this->db->delete("med_indent_process_items");
		return 1;

	}
	
	// Med_indent_accept Items Table : store_stock
	
	public function insert_store_stock_items($data) { 
		 if ($this->db->insert("store_stock", $data)) { 
			$insert_id = $this->db->insert_id();
			return $insert_id; 
		}else
		 return 0; 
	}

	public function update_store_stock_items($data,$old_id_no) { 
		$this->db->set($data); 
		$this->db->where("id", $old_id_no); 
		$this->db->update("store_stock", $data); 
	}

	public function delete_store_stock_item($id) { 
		$this->db->where("id", $id); 
		$this->db->delete("store_stock");
		return 1;

	}
} 
?>