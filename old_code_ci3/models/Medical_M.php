<?php 
   class Medical_M extends CI_Model {
	
		function __construct() { 
			 parent::__construct(); 
		}
      
		public function insert_drug_add($data) { 
			 if ($this->db->insert("drug_temp", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			 }else
			 return 0; 
		}
		
		public function update_drug($data,$old_id_no) { 
			
			$this->db->set($data); 
			$this->db->where("item_id", $old_id_no); 
			
			if($this->db->update("drug_temp", $data))
			{
				return 1;
			}else{
				return 0;
			} 
		}
	  
		public function insert_stock_add($data) { 
			
			 if ($this->db->insert("stock_addition", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			 }else
			 return 0; 
		}
	  
		public function update_stock_upd($data,$old_id_no) { 
			 $this->db->set($data); 
			 $this->db->where("ss_no", $old_id_no); 
			 if($this->db->update("stock_addition", $data))
			 {
				 return 1;
			 } else{
				 return 0;
			 }
		}

		public function add_invoice($data)
		{
			if ($this->db->insert("invoice_med_master", $data)) { 
				$insert_id = $this->db->insert_id();
				
				$pid=str_pad(substr($insert_id,-7,7),7,0,STR_PAD_LEFT);

				$pid='M'.date('ym').$pid;
				$data1 = array( 
						'inv_med_code' => $pid
				);				
				
				$this->db->set($data1); 
				$this->db->where("id", $insert_id); 
				$this->db->update("invoice_med_master", $data1);

				return $insert_id; 
			 }else
			 return 0; 
		}
		
		public function add_invoiceitem($data)
		{
			if ($this->db->insert("inv_med_item", $data)) { 
				$insert_id = $this->db->insert_id();
				$store_stock_id=$data['store_stock_id'];
				$this->db->query("CALL p_stock_update_purchase_id(".$store_stock_id.")");
				$this->db->query("CALL p_update_med_GST(".$data['inv_med_id'].")");
				return $insert_id; 
			 }else
			 return 0; 	
		}

		public function update_invoiceitem($data,$old_id_no)
		{
			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 

			if($this->db->update("inv_med_item", $data))
			{
				$store_stock_id=$data['store_stock_id'];
				$this->db->query("CALL p_stock_update_purchase_id(".$store_stock_id.")");
				$this->db->query("CALL p_update_med_GST(".$data['inv_med_id'].")");
				return 1;
			} else{
				return 0;
			}
		}
		
		public function delete_invoiceitem($itemid,$inv_items)
		{
			$this->db->delete("inv_med_item", "id=".$itemid);

			$update_emp_name=$this->session->userdata('username').'['.$this->session->userdata('agent_id').']'.date('Y-m-d H:i:s');
				
			$add_data=array(
			'delete_by' => $update_emp_name,
			);

			$data=array_merge($inv_items,$add_data);

			$store_stock_id=$data['store_stock_id'];

			//echo  "Purchase Id :".$store_stock_id;

			$this->db->insert("inv_med_item_delete", $data);
			$this->db->query("CALL p_update_med_GST(".$data['inv_med_id'].")");

			$this->db->query("CALL p_stock_update_purchase_id(".$store_stock_id.")");
		}

		public function update_in_upv_item($data,$old_id_no) { 
			$sql="select * from invoice_med_master where id=".$old_id_no;
			$query = $this->db->query($sql);
			$data1=$query->result_array();

			if(count($data1)>0)
			{
				$update_emp_name=$this->session->userdata('username').'['.$this->session->userdata('agent_id').']'.date('Y-m-d H:i:s');
				$log=compare_arrays($data1[0],$data).PHP_EOL.'Update By :'.$update_emp_name;
				$old_log=$data1[0]['log'];
				if($old_log=='' || $old_log==null)
				{
					$old_log='';
				}
				
				$ulog=$old_log.PHP_EOL.$log;

				$this->db->set($data);
				$this->db->set('log',$ulog,true);
				$this->db->where("id", $old_id_no); 
				$this->db->update("invoice_med_master");
			}
		}

		//Start  Medical Company

		public function insert_company($data) { 
			if ($this->db->insert("med_company", $data)) { 
			   $insert_id = $this->db->insert_id();
			   return $insert_id; 
			}else
			return 0; 
	   	}

	   	public function update_company($data,$old_id_no) { 
		
		   $this->db->set($data); 
		   $this->db->where("id", $old_id_no); 
		   $this->db->update("med_company", $data); 
		 } 


		 //Start  Medical Company

		public function insert_med_product_cat_master($data) { 
			if ($this->db->insert("med_product_cat_master", $data)) { 
			   $insert_id = $this->db->insert_id();
			   return $insert_id; 
			}else
			return 0; 
	   	}

	   	public function update_med_product_cat_master($data,$old_id_no) { 
		
		   $this->db->set($data); 
		   $this->db->where("id", $old_id_no); 
		   $this->db->update("med_product_cat_master", $data); 
		 } 


		//Start  Medical Payment History

		public function insert_payment($data) {
			$data['amount_1']=$data['amount'];

			 if ($this->db->insert("payment_history_medical", $data)) { 
				$insert_id = $this->db->insert_id();
				return $insert_id; 
			 }else
			 return 0; 
		}

		public function update_payment($data,$old_id_no) { 
			$data['amount_1']=$data['amount'];
			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 
			$this->db->update("payment_history_medical", $data); 
	  	} 
   
	  public function insert_payment_history_log($data) { 
		 if ($this->db->insert("paymentmedical_history_log", $data)) { 
			$insert_id = $this->db->insert_id();
   			 return $insert_id; 
			}else
			 return 0; 
	  	}

		//End Medical Histoy
		
		public function update_group_invoice($data,$old_id_no) { 
			 $this->db->set($data); 
			 $this->db->where("med_group_id", $old_id_no); 
			 $this->db->update("inv_med_group", $data);
		}
		
		public function update_invoice_final($inv_id)
		{
			$this->db->query("CALL p_update_med_GST(".$inv_id.")");
		}
		
		public function update_invoice_group($inv_id)
		{
			$this->db->query("CALL p_update_med_group(".$inv_id.")");
		}
		
		public function update_invoice_group_gst($ipd,$bill_type=1)
		{
			$this->db->query("CALL p_update_med_group_GST(".$ipd.",".$bill_type.")");
		}
		
		public function update_invoice_rate_final($inv_id)
		{
			$this->db->query("CALL med_rate_update(".$inv_id.")");
		}

		public function transfer_ssno($from_ssno,$to_ssno,$tqty)
		{
			$this->db->query("CALL p_sale_transfer_ssno(".$from_ssno.",".$to_ssno.",".$tqty.")");
		}

		public function merge_product($from_prod_id,$to_prod_id)
		{
			$this->db->query("CALL p_combine_product_master(".$from_prod_id.",".$to_prod_id.")");
		}
		
		public function insert_supplier($data) { 
			
			 if ($this->db->insert("med_supplier", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			 }else
			 return 0; 
		}
	  
		public function update_supplier($data,$old_id_no) { 
			 $this->db->set($data); 
			 $this->db->where("sid", $old_id_no); 
			 if($this->db->update("med_supplier", $data))
			 {
				 return $old_id_no;
			 } else{
				 return 0;
			 }
		}
		
		public function insert_purchase($data) { 
			
			$this->db->db_debug = false;

			if($this->db->insert("purchase_invoice", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			}else
			 return 0; 
		}
		
		public function update_purchase($data,$old_id_no) { 
			 $this->db->set($data); 
			 $this->db->where("id", $old_id_no); 
			 if($this->db->update("purchase_invoice", $data))
			 {
				$this->db->query("CALL p_purchase_invoice(".$old_id_no.")");
				 return 1;
			 } else{
				 return 0;
			 }
		}

		public function delete_purchase($inv_id) { 
			
			$this->db->where("id", $inv_id); 
			$this->db->delete("purchase_invoice");
			
			return 1;
		}
		
		public function insert_purchase_item($data) { 
			 if ($this->db->insert("purchase_invoice_item", $data)) { 
				$insert_id = $this->db->insert_id();
				$this->db->query("CALL f_update_purchase_invoice(".$insert_id.")");
				  return $insert_id; 
			 }else
			 return 0; 
		}
		
		public function update_purchase_item($data,$old_id_no) { 
			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 
			if($this->db->update("purchase_invoice_item", $data))
			{
				$this->db->query("CALL f_update_purchase_invoice(".$old_id_no.")");
				return 1;
			} else{
				return 0;
			}
		}
		
		public function delete_purchase_item($inv_id,$del_inv_item_id) { 
			
			$this->db->where("id", $del_inv_item_id); 
			$this->db->delete("purchase_invoice_item");

			$this->db->query("CALL p_purchase_invoice(".$inv_id.")");
			
			return 1;
			
			/*
			$sql="select count(*) as no_rec from inv_med_item where ss_no=".$del_inv_item_id;
			$query = $this->db->query($sql);
			$result	= $query->result();
			
			$sale_qty=$result[0]->no_rec;
			
			if($sale_qty>0)
			{
				return 0;

			}else{
				$this->db->where("id", $del_inv_item_id); 
				$this->db->delete("purchase_invoice_item");

				$this->db->query("CALL f_update_purchase_invoice(".$inv_id.")");

				return 1;
			}
			*/
		}
		
		public function update_purchase_item_stock_adjust($data_stock_adjuct,$item_id) { 
			$sql="select sum(qty) as total_adjust 
			from purchase_item_stock_adjust 
			where item_id=".$item_id;
			$query = $this->db->query($sql);
			$result	= $query->result();
			
			$sql="select *,(total_unit-total_lost_unit-total_sale_unit) as Cur_unit_qty
			from purchase_invoice_item where id=".$item_id;
			$query = $this->db->query($sql);
			$result_item_master	= $query->result();
			
			$total_adjust=0;
			$total_lost_unit=0;
			$current_qty=0;
			
			$insert_flag=0;
			
			$qty=$data_stock_adjuct['qty'];
			
			if(count($result_item_master)>0)
			{
				$total_lost_unit=$result_item_master[0]->total_lost_unit;
				$current_qty=$result_item_master[0]->Cur_unit_qty ;
				
			}
			
			if(count($result)>0)
			{
				$total_adjust=$result[0]->total_adjust;
			}
			
			if($qty>0)
			{
				if($current_qty>=$qty)
				{
					$insert_flag=1;
				}
			}elseif($qty<0){
				if($total_adjust>=$qty*-1)
				{
					$insert_flag=1;
				}
			}
			
			if($insert_flag==1)
			{
				$total_adjust=$total_adjust+$qty;
				
				if ($this->db->insert("purchase_item_stock_adjust", $data_stock_adjuct)) { 
				$insert_id = $this->db->insert_id();
				
				$data = array( 
					'total_lost_unit' => $total_adjust
				);
				
				$this->db->set($data); 
				$this->db->where("id", $item_id); 
				$this->db->update("purchase_invoice_item", $data);
		
				return "Done : Re-Search Again for Update Records"; 
			 }else{ 	return "Not Insert in Stock Adjust";	 }
			  
			}else{
				return "Qty Not Correct: either Its greater then stock Qty or 0";
			}
		
		}
		
		public function update_remove_status_item($data_stock_adjuct,$item_id) { 

			$sql="select sum(qty) as total_adjust 
			from purchase_item_stock_adjust where item_id=".$item_id;
			$query = $this->db->query($sql);
			$result	= $query->result();
			
			$sql="select *,(total_unit-total_lost_unit-total_sale_unit) as Cur_unit_qty
			from purchase_invoice_item where item_return=0 and id=".$item_id;
			$query = $this->db->query($sql);
			$result_item_master	= $query->result();
			
			$total_adjust=0;
			$total_lost_unit=0;
			$current_qty=0;
			
			$insert_flag=0;
			
			if(count($result_item_master)>0)
			{
				$total_lost_unit=$result_item_master[0]->total_lost_unit;
				$current_qty=$result_item_master[0]->Cur_unit_qty ;
			}
			
			if(count($result)>0)
			{
				$total_adjust=$result[0]->total_adjust;
			}

			$total_adjust=$total_adjust+$current_qty;
			
			$data_stock_adjuct['qty']=$current_qty;
			
			if ($this->db->insert("purchase_item_stock_adjust", $data_stock_adjuct)) { 
				$insert_id = $this->db->insert_id();
			
				$data = array( 
					'total_lost_unit' => $total_adjust,
					'remove_item' => '1'
				);
				
				$this->db->set($data); 
				$this->db->where("id", $item_id); 
				$this->db->update("purchase_invoice_item", $data);
	
				return "Done : Remove ".$current_qty." Qty : Re-Search for Update Records"; 
			}else{ 	return "Not Insert in Stock Adjust";	 }
		}

		public function add_removed_item($data_stock_adjuct,$item_id)
		{
			if ($this->db->insert("purchase_item_stock_adjust", $data_stock_adjuct)) { 
				$insert_id = $this->db->insert_id();
				
				$data = array( 
					'remove_item' => '0'
				);
				
				$this->db->set($data); 
				$this->db->where("id", $item_id); 
				$this->db->update("purchase_invoice_item", $data);

				return "Add Item  in Stock ";
			}else{
				return "Something Wrong ";
			}
		}


		public function insert_inv_med_item_return_add($data) { 
			
			 if ($this->db->insert("inv_med_item_return", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			 }else
			 return 0; 
		}

		public function delete_return_item($inv_item_id) { 
			$this->db->where("inv_item_id", $inv_item_id);
			$this->db->where("return_status", '0');  
			$this->db->delete("inv_med_item_return");
		}

		public function update_return_item($data,$old_id_no) { 

			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 
			
			if($this->db->update("inv_med_item_return", $data))
			{
				return 1;
			}else{
				return 0;
			} 

		}

		public function insert_med_supplier_ledger($data) { 
			
			if($this->db->insert("med_supplier_ledger", $data)) { 
				$insert_id = $this->db->insert_id();
				  return $insert_id; 
			}else
			 return 0; 
		}

		public function update_med_supplier_ledger($data,$old_id_no) { 

			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 
			
			if($this->db->update("med_supplier_ledger", $data))
			{
				return 1;
			}else{
				return 0;
			} 

		}

		//Insert Update Table purchase_return_invoice / purchase_return_invoice_item

		public function add_purchase_return_invoice($data)
		{
			if ($this->db->insert("purchase_return_invoice", $data)) { 
				$insert_id = $this->db->insert_id();
				
				$pid=str_pad(substr($insert_id,-3,3),3,0,STR_PAD_LEFT);

				$pid='PR'.date('ym').$pid;
				$data1 = array( 
						'p_r_invoice_no' => $pid
				);				
				
				$this->db->set($data1); 
				$this->db->where("id", $insert_id); 
				$this->db->update("purchase_return_invoice", $data1);

				return $insert_id; 
			 }else
			 return 0; 
		}
		
		public function add_purchase_return_invoiceitem($data)
		{
			if ($this->db->insert("purchase_return_invoice_item", $data)) { 
				$insert_id = $this->db->insert_id();
				
				$this->db->query("CALL p_stock_purchase_return(".$insert_id.",0)");
				return $insert_id; 
			 }else
			 return 0; 	
		}

		public function update_purchase_return_invoiceitem($data,$old_id_no)
		{
			$this->db->set($data); 
			$this->db->where("id", $old_id_no); 

			if($this->db->update("purchase_return_invoice_item", $data))
			{
				$store_stock_id=$data['store_stock_id'];
				//$this->db->query("CALL p_stock_update_purchase_id(".$store_stock_id.")");
				return 1;
			} else{
				return 0;
			}
		}

		public function opd_reset($inv_id)
		{
			//$this->db->query("CALL p_cash_opd_pre_data(".$inv_id.")");
		}
		
		public function delete_purchase_return_invoiceitem($itemid)
		{
			$this->db->query("CALL p_stock_purchase_return(".$itemid.",1)");

			$this->db->delete("purchase_return_invoice_item", "id=".$itemid);

			$update_emp_name=$this->session->userdata('username').'['.$this->session->userdata('agent_id').']'.date('Y-m-d H:i:s');
				
			$add_data=array(
			'delete_by' => $update_emp_name,
			);
			
		}

		
	  
	}
?>