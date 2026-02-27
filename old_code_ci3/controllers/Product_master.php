<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_master extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}

	public function drug_master_list()
	{
		$this->load->view('Medical/Purchase/drug_master_list');
	}
	
	public function Product_search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="select *  from med_product_master	order by id desc limit 100";
		}else{
			$sql="select *  from med_product_master			
			where (item_name LIKE '%".$sdata."%' 
			OR genericname LIKE '%".$sdata."%' 
			OR id = '".$sdata."')";
		}

		$query = $this->db->query($sql);
        $data['product_list']= $query->result();
		
		$this->load->view('Medical/Purchase/product_search_result',$data);
	}

	public function Product_edit($Product_id)
	{
		$sql="SELECT m.* ,group_concat(c.med_cat_id) as cat_list
			from med_product_master m left join med_product_cat_assign c on m.id=c.med_product_id 
			where m.id=".$Product_id;
		$query = $this->db->query($sql);
        $data['product_data']= $query->result();

		$sql="Select * from med_formulation order by formulation_length";
		$query = $this->db->query($sql);
        $data['med_formulation']= $query->result();

		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();

		$sql="Select * from med_product_cat_master order by med_cat_desc";
		$query = $this->db->query($sql);
        $data['med_product_cat_master']= $query->result();
	

		$this->load->view('Medical/Purchase/product_edit',$data);
	}

	public function Product_delete($Product_id)
	{
		$sql="Select count(*) as no_rec from inv_med_item where item_code=".$Product_id;
		$query = $this->db->query($sql);
		$product_sale_data= $query->result();

		$sql="Select count(*) as no_rec from purchase_invoice_item where item_code=".$Product_id;
		$query = $this->db->query($sql);
		$product_purchase_data= $query->result();

		$is_delete_master=0;

		$data_show="";

		if($product_sale_data[0]->no_rec>0 || $product_purchase_data[0]->no_rec>0)
		{
			if($product_sale_data[0]->no_rec>0){
				$data_show.="<p>This Product in Sale Invoice, So cann't Delete</p>";
			}

			if($product_purchase_data[0]->no_rec>0){
				$data_show.="<p>This Product in Purchase Invoice, So cann't Delete</p>";
			}

		}else{

			$this->load->model('product_master_M');
			$is_delete_master=$this->product_master_M->delete_master($Product_id);

			if($is_delete_master==0)
			{
				$data_show.="<p>Some Error on Delete this product</p>";
			}else{
				$data_show.="<p>Deleted</p>";
			}
			
		}

		echo $data_show;
		

	}
	
	public function NewProduct()
	{
		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();

		$sql="Select * from med_formulation order by formulation_length";
		$query = $this->db->query($sql);
        $data['med_formulation']= $query->result();

		$sql="Select * from med_product_cat_master order by med_cat_desc";
		$query = $this->db->query($sql);
        $data['med_product_cat_master']= $query->result();
	

		$this->load->view('Medical/Purchase/product_new',$data);
	}

	public function get_drug_master(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from drug_temp 
				where itemname like '".$q."%'";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['itemname'])).' | '.htmlentities(stripslashes($row['formulation_length'])).' | '.htmlentities(stripslashes($row['genericname']));
				$new_row['value']=htmlentities(stripslashes($row['itemname'])).' | '.htmlentities(stripslashes($row['formulation_length'])).' | '.htmlentities(stripslashes($row['genericname']));
				
				$new_row['l_itemname']=htmlentities(stripslashes($row['itemname']));
				
				$new_row['l_genericname']=htmlentities(stripslashes($row['genericname']));
				
				$new_row['l_formulation']=htmlentities(stripslashes($row['formulation']));

				$new_row['l_company_name']=htmlentities(stripslashes($row['company_name']));

				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}else{
			echo 'No Data';
		}
	}

	public function get_formulation_desc(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from med_formulation where formulation like '".$q."%'";
			$query = $this->db->query($sql);

			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['formulation_length']));
				$new_row['value']=htmlentities(stripslashes($row['formulation']));
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function get_genericname(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$where="";

			if(strlen($q)>3)
			{
				$where=" or gen_desc like '%".$q."%'";
			}

			$sql="select * from med_genric_name 
			where gen_desc like '".$q."%'".$where;
			$query = $this->db->query($sql);

			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['gen_desc']));
				$new_row['value']=htmlentities(stripslashes($row['gen_desc']));
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}

	public function get_company_name(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from med_company where company_name like '".$q."%'";
			$query = $this->db->query($sql);

			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['company_name']));
				$new_row['value']=htmlentities(stripslashes($row['company_name']));
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	public function product_master_update($product_id=0)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

		$is_unique =  '';

		if($product_id>0)
		{

		 	$original_value = $this->db->query("SELECT item_name FROM med_product_master WHERE id = ".$product_id)->row()->item_name ;

			 if(strtoupper($this->input->post('input_item_name')) != strtoupper($original_value)) {
	           		$is_unique =  '|is_unique[med_product_master.item_name]';
	        	} else {
	           		$is_unique =  '';
	        	}
		}
		
		
		
        $FormRules = array(
                array(
                    'field' => 'input_item_name',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[100]'.$is_unique
                ),
				array(
                    'field' => 'input_formulation',
                    'label' => 'Formulation',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_packing_type',
                    'label' => 'Packing',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_re_order_qty',
                    'label' => 'Re-Order Qty',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_HSNCODE',
                    'label' => 'HSNCODE',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_CGST',
                    'label' => 'CGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_CGST',
                    'label' => 'CGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_SGST',
                    'label' => 'SGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
				$related_drug_id=$this->input->post('related_drug_id');
			
				$ban_flag_id = ($this->input->post('chk_ban_flag_id')=='on'?1:0);

				$narcotic = ($this->input->post('chk_narcotic')=='on'?1:0);
				$schedule_h = ($this->input->post('chk_schedule_h')=='on'?1:0);
				$schedule_h1 = ($this->input->post('chk_schedule_h1')=='on'?1:0);
				$schedule_x = ($this->input->post('chk_schedule_x')=='on'?1:0);
				$schedule_g = ($this->input->post('chk_schedule_g')=='on'?1:0);
				$high_risk = ($this->input->post('chk_high_risk')=='on'?1:0);
			

				$batch_applicable = ($this->input->post('chk_batch_applicable')=='on'?1:0);
				$is_continue = ($this->input->post('chk_is_continue')=='on'?1:0);
				$exp_date_applicable = ($this->input->post('chk_exp_date_applicable')=='on'?1:0);
								
				$this->load->model('product_master_M');

				$Pdata = array(
                    'related_drug_id' => $related_drug_id, 
                	'item_name' => $this->input->post('input_item_name'),
					'formulation' => $this->input->post('input_formulation'),
					'genericname' => $this->input->post('input_genericname'),
					'packing' => $this->input->post('input_packing_type'),
                	're_order_qty' => $this->input->post('input_re_order_qty'),
					'CGST_per' => $this->input->post('input_CGST'),
					'SGST_per' => $this->input->post('input_SGST'),
					'HSNCODE' => $this->input->post('input_HSNCODE'),

					'ban_flag_id' => $ban_flag_id,
					'batch_applicable' => $batch_applicable,
					'is_continue' => $is_continue,
					'exp_date_applicable' => $exp_date_applicable,

					'rack_no' => $this->input->post('input_rack_no'),
					'shelf_no' => $this->input->post('input_shelf_no'),
					'cold_storage' => $this->input->post('input_cold_storage'),

					'company_id' => $this->input->post('input_company_name'),

					'schedule_h' => $schedule_h,
					'schedule_h1' => $schedule_h1,
					'narcotic' => $narcotic,
					'schedule_x' => $schedule_x,
					'schedule_g' => $schedule_g,
					'high_risk' => $high_risk,
                ); 
				
                $show_text='';
				
				$is_update_stock=0;
				
				if($product_id<1)
				{
					$Pdata['insert_by'] = $user_name_info;
					$is_update_stock=$this->product_master_M->insert($Pdata);
					$product_id_update=$is_update_stock;
				}else{
					$Pdata['update_by'] = $user_name_info;
					$is_update_stock=$this->product_master_M->update($Pdata,$product_id);
					$product_id_update=$product_id;
				}

				$cat_id_list=$this->input->post('med_cat_id');

				if($cat_id_list<>''){
					$cat_id_list_string = implode(',', $cat_id_list);
				}else{
					$cat_id_list_string ="0";
				}
				
				$cat_assingn=$this->product_master_M->insert_update_product_cat_assign($cat_id_list_string,$product_id_update);
				
				if($is_update_stock>0)
				{
					$send_msg="Updated";
				}else{
					$send_msg="Error : Update in Stock Table";
				}
				
				if($is_update_stock>0)
				{
					$show_text=Show_Alert('success','Updated',$send_msg);
				}else{
					$show_text=Show_Alert('danger','Error',$send_msg);
				}
				
                $rvar=array(
					'is_update_stock' =>$is_update_stock,
					'show_text'=>$show_text
					);
				
				$encode_data = json_encode($rvar);
				echo $encode_data;
              
            }
            else
            {
                $send_msg=validation_errors();
                $rvar=array(
                'is_update_stock' =>0,
                'show_text'=>Show_Alert('danger','Error',$send_msg)
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}
	
	public function company_master_list()
	{
		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();
		
		$this->load->view('Medical/Purchase/company_list',$data);
	}

	public function CompanyListSub()
    {
		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();
		
		$this->load->view('Medical/Purchase/company_list_sub',$data);
		
	}

	public function CompanyEdit($cid=0)
    {
		$sql="select * from med_company where id=".$cid;
		$query = $this->db->query($sql);
		$data['med_company']= $query->result();

		

		$this->load->view('Medical/Purchase/company_edit',$data);
	}

	public function CompanyUpdate()
    {
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_company_name',
                    'label' => 'Company Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
            );

         $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			$cid=$this->input->post('hid_cid');
			$company_name=$this->input->post('input_company_name');
			$contact_person_name=$this->input->post('input_contact_person_name');
			$contact_phone_no=$this->input->post('input_contact_phone_no');
			
			$Udata = array( 
                    'company_name'=> $company_name,
					'contact_person_name'=> $contact_person_name,
					'contact_phone_no'=> $contact_phone_no,
				);
			
			$this->load->model('Medical_M');
			
			if($cid>0)
			{
				$this->Medical_M->update_company($Udata,$cid);
				$inser_id=$cid;
				$send_msg="Saved Successfully";
			}else{
				$inser_id=$this->Medical_M->insert_company($Udata);
				$send_msg="Added Successfully";
			}

			if($inser_id>0)
			{
				$show_text=$send_msg;
			}else{
				$show_text=$send_msg;
			}
			
			$rvar=array(
				'insertid' =>$inser_id,
				'show_text'=>$show_text
				);
			
			$encode_data = json_encode($rvar);
			echo $encode_data;
			
		}
		else{
			$send_msg=validation_errors();
			$rvar=array(
			'insertid' =>0,
			'show_text'=>$send_msg
			);
			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
	
	}

	public function medicine_category()
	{
		$sql="Select * from med_product_cat_master order by med_cat_desc";
		$query = $this->db->query($sql);
        $data['med_product_cat_master']= $query->result();
		
		$this->load->view('Medical/Purchase/med_product_cat_list',$data);
	}

	public function medicine_category_edit($cid=0)
    {
		$sql="select * from med_product_cat_master where id=".$cid;
		$query = $this->db->query($sql);
		$data['med_product_cat_master']= $query->result();

		$this->load->view('Medical/Purchase/medicine_category_edit',$data);
	}

	public function medicine_category_Sub()
    {
		$sql="Select * from med_product_cat_master order by med_cat_desc";
		$query = $this->db->query($sql);
        $data['med_product_cat_master']= $query->result();
		
		$this->load->view('Medical/Purchase/medicine_category_Sub',$data);
		
	}

	public function medicine_category_Update()
    {
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_med_cat_desc',
                    'label' => 'Medicince Category Name',
                    'rules' => 'required|min_length[3]|max_length[100]'
                ),
            );

        $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			$cid=$this->input->post('hid_cid');
			$med_cat_desc=$this->input->post('input_med_cat_desc');
			
			$Udata = array( 
                    'med_cat_desc'=> $med_cat_desc,
			);
			
			$this->load->model('Medical_M');
			
			if($cid>0)
			{
				$this->Medical_M->update_med_product_cat_master($Udata,$cid);
				$inser_id=$cid;
				$send_msg="Saved Successfully";
			}else{
				$inser_id=$this->Medical_M->insert_med_product_cat_master($Udata);
				$send_msg="Added Successfully";
			}

			if($inser_id>0)
			{
				$show_text=$send_msg;
			}else{
				$show_text=$send_msg;
			}
			
			$rvar=array(
				'insertid' =>$inser_id,
				'show_text'=>$show_text
				);
			
			$encode_data = json_encode($rvar);
			echo $encode_data;
			
		}
		else{
			$send_msg=validation_errors();
			$rvar=array(
			'insertid' =>0,
			'show_text'=>$send_msg
			);
			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
	
	}
	
	
	
	public function get_product_master(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from med_product_master 
				where item_name like '".$q."%'";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
								
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['item_name'])).' | '.htmlentities(stripslashes($row['formulation'])).' | '.htmlentities(stripslashes($row['genericname']));
				$new_row['value']=htmlentities(stripslashes($row['item_name'])).' | '.htmlentities(stripslashes($row['formulation']));
				//$new_row['value']=htmlentities(stripslashes($row['itemname']));
				
				$new_row['l_item_code']=htmlentities(stripslashes($row['id']));
				
				$new_row['l_itemname']=htmlentities(stripslashes($row['item_name']));
				
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				$new_row['l_CGST_per']=htmlentities(stripslashes($row['CGST_per']));
				$new_row['l_SGST_per']=htmlentities(stripslashes($row['SGST_per']));
				$new_row['l_HSNCODE']=htmlentities(stripslashes($row['HSNCODE']));
				
				$new_row['l_genericname']=htmlentities(stripslashes($row['genericname']));
				
				$new_row['l_formulation']=htmlentities(stripslashes($row['formulation']));

				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}else{
			echo 'No Data';
		}
	}
	
	

	
}