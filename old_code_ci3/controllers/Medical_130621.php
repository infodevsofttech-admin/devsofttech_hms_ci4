<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical extends MY_Controller {
	public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
    {
			
		$this->load->view('Medical/Dashboard');
	}
	
	public function select_store($store_id)
    {
		$sql="select * from store_master where store_id=".$store_id;
		$query = $this->db->query($sql);
		$data['store_master']= $query->result();

		$this->load->view('Medical/Store_Dashboard',$data);
	}

	public function main_store()
    {
		$this->load->view('Medical/Stock/main_store_dashboard');
	}

	
	public function DrugList()
    {
		$this->load->view('Medical/Drug_Details');

		//$sql="select item_id,itemname,dosage from drug_temp_25072017";
		//$query = $this->db->query($sql);
		//$data['drug_data']= $query->result();

		//$this->load->view('Medical/Drug_Details_all',$data);
	}

	public function Purchase()
    {
		$this->load->view('Medical/Purchase/supplier_list');
	}
	
	public function PurchaseMasterEdit($inv_id)
	{
		$sql="select * from med_supplier order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$sql="select p.*,s.name_supplier from purchase_invoice p join med_supplier s on p.sid=s.sid where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_master_data']= $query->result();

		$sql="select * from purchase_invoice_item where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
		$data['purchase_item']= $query->result();
	
		$this->load->view('Medical/Purchase/purchase_invoice_master_edit',$data);

	}
	
	public function UpdatePurchase()
	{
		$rec_id=$this->input->post('hid_purchaseid');
		$sid=$this->input->post('input_supplier');
		$invoice_code=$this->input->post('input_invoicecode');
		$d_invoice=$this->input->post('datepicker_invoice');
		
		$Udata = array( 
				'sid'=> $sid,
				'date_of_invoice'=> str_to_MysqlDate($d_invoice),
				'Invoice_no'=> $invoice_code
			);
		
		$this->load->model('Medical_M');
		
		$update_id=$this->Medical_M->update_purchase($Udata,$rec_id);
		
		$rvar=array(
				'insertid' =>1,
				'show_text'=>'Data Update'
				);
			
			$encode_data = json_encode($rvar);
			echo $encode_data;
		
	}

	public function UpdatePurchaseInvoiceStatus($purchase_inv_id,$inv_status)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');
       		
		$Udata = array( 
				'inv_status'=> $inv_status,
				'inv_status_update_by'=> $user_name_info,
			);
		
		$this->load->model('Medical_M');
		
		$update_id=$this->Medical_M->update_purchase($Udata,$purchase_inv_id);
		
		redirect('Medical/PurchaseMasterEdit/'.$purchase_inv_id);
		
	}

	public function PurchaseNew()
    {
		$sql="select * from med_supplier  order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$this->load->view('Medical/Purchase/new_purchase_invoice',$data);
	}
	
	public function SupplierList()
    {
		$sql="select * from med_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$this->load->view('Medical/Purchase/Supplier_master',$data);
	}
	
	public function PurchaseInvoice()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="select p.id,p.Invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,p.sid,s.name_supplier,
			s.short_name,s.gst_no,
			p.T_Net_Amount as tamount
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			
			group by p.id
			order by p.id desc limit 50";
		}else{

			$numric_condition="";

			if(is_numeric($sdata))
			{
				$numric_condition=" OR p.Invoice_no=".$sdata ;
			}

			$sql="select p.id,p.Invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no,
			p.T_Net_Amount as tamount
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			where (p.Invoice_no LIKE '%".$sdata."'  ".$numric_condition." )
			group by p.id order by p.id desc limit 50";
		}

		$query = $this->db->query($sql);
        $data['purchase_list']= $query->result();
		
		$this->load->view('Medical/Purchase/purchase_supp_list',$data);
	}
	
	public function PurchaseInvoiceEdit($inv_id)
	{
		$sql="select p.id,p.Invoice_no,p.date_of_invoice,date_format(p.date_of_invoice,'%d/%m/%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no,p.inv_status
			from purchase_invoice p join med_supplier s on p.sid=s.sid
			where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice']= $query->result();	

		$sql="select * from purchase_invoice_item where purchase_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice_item']= $query->result();

		$this->load->view('Medical/Purchase/Purchase_Invoice_Edit',$data);
	}

	public function purchase_invoice_delete($inv_id)
	{
		
		$sql="select * from purchase_invoice_item  where purchase_id=$inv_id";
		$query = $this->db->query($sql);
		$purchase_invoice_item= $query->result();

		if (count($purchase_invoice_item)>0){
			$rvar=array(
				'is_delete' =>0,
				'show_text'=>"Invoice has Items, Empty the item list",
			);
		}else{
			$this->load->model('Medical_M');
			$is_delete=$this->Medical_M->delete_purchase($inv_id);
			$rvar=array(
				'is_delete' =>1,
				'show_text'=>"Invoice has been deleted",
			);
		}
	
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}
		
	public function SupplierListSub()
    {
		$sql="select * from med_supplier  order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$this->load->view('Medical/Purchase/Supplier_master_sub',$data);
	}
	
	public function SupplierEdit($sid=0)
    {
		$sql="select * from med_supplier where sid=".$sid;
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$this->load->view('Medical/Purchase/Supplier_Edit',$data);
	}
	
	public function CreatePurchase()
    {
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_invoicecode',
                    'label' => 'Invoice No',
                    'rules' => 'required|min_length[3]|max_length[30]'
                )
            );

         $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			$sid=$this->input->post('input_supplier');
			$invoice_code=$this->input->post('input_invoicecode');
			$d_invoice=$this->input->post('datepicker_invoice');

			$invoice_date=date_create(str_to_MysqlDate($d_invoice));

			$where=" where sid=$sid";

			if(is_numeric($invoice_code))
				$where.=" and Invoice_no=".$invoice_code ;
			else
				$where.=" and Invoice_no='".$invoice_code."'";

			$where.=" and date_of_invoice BETWEEN get_financial_year_start('".str_to_MysqlDate($d_invoice)."') AND get_financial_year_end('".str_to_MysqlDate($d_invoice)."')";
			
			$sql="select count(*) as no_rec from  purchase_invoice ".$where;
			$query = $this->db->query($sql);
			$chk_rec= $query->result();

			if($chk_rec[0]->no_rec==0)
			{
				$Udata = array( 
                    'sid'=> $sid,
					'date_of_invoice'=> str_to_MysqlDate($d_invoice),
					'Invoice_no'=> $invoice_code,
				);
			
				$this->load->model('Medical_M');
			
				$inser_id=$this->Medical_M->insert_purchase($Udata);
				$send_msg="Added Successfully";

				if($inser_id>0)
				{
					$show_text=Show_Alert('success','Success',$send_msg);
				}else{
					$show_text=Show_Alert('danger','Error',$send_msg);
				}
			}else{
				$show_text=Show_Alert('danger','Error','  Record Already Exist');
				$inser_id=0;
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
			'show_text'=>Show_Alert('danger','Error',$send_msg)
			);
			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
	}
	
	
	public function SupplierUpdate($sid=0)
    {
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_name_supplier',
                    'label' => 'Supplier Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
				array(
                    'field' => 'input_short_name',
                    'label' => 'Short Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_gst_no',
                    'label' => 'GST No.',
                    'rules' => 'required|min_length[5]|max_length[15]'
                ),
				array(
                    'field' => 'input_state',
                    'label' => 'State',
                    'rules' => 'required|min_length[1]|max_length[20]'
                )
            );

         $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			$sid=$this->input->post('hid_sid');
			$name_supplier=$this->input->post('input_name_supplier');
			$short_name=$this->input->post('input_short_name');
			$contact_no=$this->input->post('input_contact_no');
			$gst_no=$this->input->post('input_gst_no');
			$city=$this->input->post('input_city');
			$state=$this->input->post('input_state');
			
			$Udata = array( 
                    'name_supplier'=> $name_supplier,
					'short_name'=> $short_name,
					'contact_no'=> $contact_no,
					'gst_no'=> $gst_no,
					'city'=> $city,
					'state'=> $state
				);
			
			$this->load->model('Medical_M');
			
			if($sid>0)
			{
				$inser_id=$this->Medical_M->update_supplier($Udata,$sid);
				$send_msg="Saved Successfully";
			}else{
				$inser_id=$this->Medical_M->insert_supplier($Udata);
				$send_msg="Added Successfully";
			}

			if($inser_id>0)
			{
				$show_text=Show_Alert('success','Success',$send_msg);
			}else{
				$show_text=Show_Alert('danger','Error',$send_msg);
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
			'show_text'=>Show_Alert('danger','Error',$send_msg)
			);
			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
	
	}
	
	public function  StockDrugList()
    {
		$this->load->view('Medical/Medical_stock_drug');
	}
	
	public function add_stock()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_product_code',
                    'label' => 'Product Code',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
				array(
                    'field' => 'input_product_name',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[100]'
                ),
				array(
                    'field' => 'input_product_mrp',
                    'label' => 'Product MRP',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_selling_price',
                    'label' => 'Selling Price',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_qty',
                    'label' => 'Product Qty',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_package',
                    'label' => 'Package',
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
                    'field' => 'input_SGST',
                    'label' => 'SGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
                $item_code=$this->input->post('input_product_code');
				
				if($item_code=='IC0000000')
				{
					$item_code='MC'.date('ymdhis');
				}
				
				$selling_price=$this->input->post('input_selling_price');
				
				$package=$this->input->post('input_package');
				$qty=$this->input->post('input_qty');
				$total_unit=$package*$qty;
				$unit_rate=$selling_price/$qty;

				$datepicker_doe_month=$this->input->post('datepicker_doe_month');
				$datepicker_doe_year=$this->input->post('datepicker_doe_year');

				$datepicker_doe="20".$datepicker_doe_year."-".$datepicker_doe_month."-01";

				$this->load->model('Medical_M');
                $data = array( 
                    'item_code' => $item_code, 
                	'item_name' => $this->input->post('input_product_name'),
                	'rack_no' => $this->input->post('input_rack_no'),
                	'shelf_no' => $this->input->post('input_shelf_no'),
                	'cold_storage' => $this->input->post('input_storage'),
					'batch_no' => $this->input->post('input_batch_code'),
					'qty' => $this->input->post('input_qty'),
					'packing' => $this->input->post('input_package'),
					'expiry_date' => $datepicker_doe,
					'mrp' => $this->input->post('input_product_mrp'),
					'purchase_price' => $this->input->post('input_purchase_price'),
					'selling_price' => $this->input->post('input_selling_price'),
					'total_unit' => $total_unit,
					'unit_rate' => $unit_rate,
					'margin' => $this->input->post('input_margin'),
					'formulation' => $this->input->post('input_formulation'),
					'stock_date' => str_to_MysqlDate($this->input->post('datepicker_stock')),
					'min_qty' => $this->input->post('input_minqty'),
					'HSNCODE' => $this->input->post('input_HSNCODE'),
					'CGST' => $this->input->post('input_CGST'),
					'SGST' => $this->input->post('input_SGST'),
					'IGST' => $this->input->post('input_IGST'),
					'dosage' => $this->input->post('input_dosage'),
					'supplier_id' => $this->input->post('input_supplier')
                ); 
				
				$data_drug = array( 
                    'item_id' => $item_code, 
                	'itemname' => $this->input->post('input_product_name'),
					'mrp' => $this->input->post('input_product_mrp'),
					'formulation' => $this->input->post('input_formulation'),
					'HSNCODE' => $this->input->post('input_HSNCODE'),
					'CGST' => $this->input->post('input_CGST'),
					'SGST' => $this->input->post('input_SGST'),
					'IGST' => $this->input->post('input_IGST'),
					'dosage' => $this->input->post('input_dosage')
                ); 
                $show_text='';
				
				$sql="select * from drug_temp where item_id='".$item_code."'";
				$query = $this->db->query($sql);
				$drug_item= $query->result();
				
				if(count($drug_item)>0)
				{
					$inser_id=$this->Medical_M->update_drug($data_drug,$item_code);
				}else{
					$inser_id=$this->Medical_M->insert_drug_add($data_drug);
				}
				
				if($inser_id>0)
				{
					$inser_id=0;
					$inser_id=$this->Medical_M->insert_stock_add($data);
					if($inser_id>0)
					{
						$send_msg="Added";
					}else{
						$send_msg="Error : Add in Stock Table";
					}
				}else{
					$send_msg="Error : Addition data in  Master Drug Table";
				}
				
				if($inser_id>0)
				{
					$show_text=Show_Alert('success','Success',$send_msg);
				}else{
					$show_text=Show_Alert('danger','Error',$send_msg);
				}
				
                $rvar=array(
					'insertid' =>$inser_id,
					'product_code' => $item_code,
					'show_text'=>$show_text
					);
				
				$encode_data = json_encode($rvar);
				echo $encode_data;
              
            }
            else
            {
                $send_msg=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'show_text'=>Show_Alert('danger','Error',$send_msg)
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}
	
	public function update_master()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_product_code',
                    'label' => 'Product Code',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
				array(
                    'field' => 'input_product_name',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[100]'
                ),
				array(
                    'field' => 'input_product_mrp',
                    'label' => 'Product MRP',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_selling_price',
                    'label' => 'Selling Price',
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
                    'field' => 'input_SGST',
                    'label' => 'SGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
                $item_code=$this->input->post('input_product_code');

				$this->load->model('Medical_M');
				
				$data_drug = array( 
                    'item_id' => $item_code, 
                	'itemname' => $this->input->post('input_product_name'),
					'mrp' => $this->input->post('input_product_mrp'),
					'formulation' => $this->input->post('input_formulation'),
					'HSNCODE' => $this->input->post('input_HSNCODE'),
					'CGST' => $this->input->post('input_CGST'),
					'SGST' => $this->input->post('input_SGST'),
					'IGST' => $this->input->post('input_IGST'),
					'dosage' => $this->input->post('input_dosage')
                ); 
                $show_text='';
				
				$sql="select * from drug_temp where item_id='".$item_code."'";
				$query = $this->db->query($sql);
				$drug_item= $query->result();
				
				if(count($drug_item)>0)
				{
					$inser_id=$this->Medical_M->update_drug($data_drug,$item_code);
				}else{
					$inser_id=$this->Medical_M->insert_drug_add($data_drug);
				}
				
				if($inser_id>0)
				{
					$inser_id=0;
					$inser_id=$this->Medical_M->insert_stock_add($data);
					if($inser_id>0)
					{
						$send_msg="Update";
					}else{
						$send_msg="Error : Edit in Drug Master";
					}
				}else{
					$send_msg="Error : Addition data in  Master Drug Table";
				}
				
				if($inser_id>0)
				{
					$show_text=Show_Alert('success','Success',$send_msg);
				}else{
					$show_text=Show_Alert('danger','Error',$send_msg);
				}
				
                $rvar=array(
					'insertid' =>$inser_id,
					'product_code' => $item_code,
					'show_text'=>$show_text
					);
				
				$encode_data = json_encode($rvar);
				echo $encode_data;
              
            }
            else
            {
                $send_msg=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'show_text'=>Show_Alert('danger','Error',$send_msg)
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}
	
	public function update_stock($ss_no)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_product_code',
                    'label' => 'Product Code',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
				array(
                    'field' => 'input_product_name',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[100]'
                )
				,
				array(
                    'field' => 'input_product_mrp',
                    'label' => 'Product MRP',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_selling_price',
                    'label' => 'Selling Price',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_qty',
                    'label' => 'Product Qty',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_package',
                    'label' => 'Package',
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
                    'field' => 'input_SGST',
                    'label' => 'SGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
                $item_code=$this->input->post('input_product_code');
				
				$selling_price=$this->input->post('input_selling_price');
				
				$package=$this->input->post('input_package');
				$qty=$this->input->post('input_qty');
				$total_unit=$package*$qty;
				$unit_rate=$selling_price/$qty;

				$datepicker_doe_month=$this->input->post('datepicker_doe_month');
				$datepicker_doe_year=$this->input->post('datepicker_doe_year');

				$datepicker_doe="20".$datepicker_doe_year."-".$datepicker_doe_month."-01";

				$this->load->model('Medical_M');
                $data = array( 
                    'item_code' => $item_code, 
                	'item_name' => $this->input->post('input_product_name'),
                	'rack_no' => $this->input->post('input_rack_no'),
                	'shelf_no' => $this->input->post('input_shelf_no'),
                	'cold_storage' => $this->input->post('input_storage'),
					'batch_no' => $this->input->post('input_batch_code'),
					'qty' => $this->input->post('input_qty'),
					'packing' => $this->input->post('input_package'),
					'expiry_date' => $datepicker_doe,
					'mrp' => $this->input->post('input_product_mrp'),
					'purchase_price' => $this->input->post('input_purchase_price'),
					'selling_price' => $this->input->post('input_selling_price'),
					'total_unit' => $total_unit,
					'unit_rate' => $unit_rate,
					'margin' => $this->input->post('input_margin'),
					'formulation' => $this->input->post('input_formulation'),
					'stock_date' => str_to_MysqlDate($this->input->post('datepicker_stock')),
					'min_qty' => $this->input->post('input_minqty'),
					'HSNCODE' => $this->input->post('input_HSNCODE'),
					'CGST' => $this->input->post('input_CGST'),
					'SGST' => $this->input->post('input_SGST'),
					'IGST' => $this->input->post('input_IGST'),
					'supplier_id' => $this->input->post('input_supplier')
                ); 
								
                $show_text='';
				
				$is_update_stock=0;
				
				$is_update_stock=$this->Medical_M->update_stock_upd($data,$ss_no);
				
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
					'product_code' => $item_code,
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
		
	public function purchase_update_stock($inv_id,$return_stock=0)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql="select * from purchase_invoice where id=".$inv_id;
		$query = $this->db->query($sql);
		$purchase_invoice= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

        $FormRules = array(
				array(
					'field' => 'input_product_code',
					'label' => 'Not Found in Database',
					'rules' => 'required|min_length[1]|max_length[30]'
				),
				array(
                    'field' => 'input_drug_hid',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[100]'
                ),
				array(
                    'field' => 'input_product_mrp',
                    'label' => 'Product MRP',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_selling_price',
                    'label' => 'Selling Price',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_Qty',
                    'label' => 'Product Qty',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_package',
                    'label' => 'Package',
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
                    'field' => 'input_SGST',
                    'label' => 'SGST',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				array(
                    'field' => 'input_purchase_price',
                    'label' => 'Purchase Price',
                    'rules' => 'required|min_length[1]|max_length[10]'
                )
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
                $item_code=$this->input->post('input_product_code');
				$item_id=$this->input->post('invoice_item_id');

				$datepicker_doe_month=$this->input->post('datepicker_doe_month');
				$datepicker_doe_year=$this->input->post('datepicker_doe_year');

				$datepicker_doe="20".$datepicker_doe_year."-".$datepicker_doe_month."-01";
				
				$this->load->model('Medical_M');

				$Pdata = array(
					'purchase_id' => $inv_id,
                    'item_code' => $item_code, 
                	'item_name' => $this->input->post('input_drug_hid'),
					'packing' => $this->input->post('input_package'),
                	'batch_no' => $this->input->post('input_batch_code'),
					'purchase_price' => $this->input->post('input_purchase_price'),
					'expiry_date' => $datepicker_doe,
					'mrp' => $this->input->post('input_product_mrp'),
					
					'qty' => $this->input->post('input_Qty'),
					'qty_free' => $this->input->post('input_Qty_Free'),
					
					'discount' => $this->input->post('input_disc_price'),
					
					'CGST_per' => $this->input->post('input_CGST'),
					'SGST_per' => $this->input->post('input_SGST'),

					'HSNCODE' => $this->input->post('input_HSNCODE'),
					
					'cold_storage' => $this->input->post('input_storage'),
					'shelf_no' => $this->input->post('input_shelf_no'),
					'rack_no' => $this->input->post('input_rack_no'),
					'stock_date' => $purchase_invoice[0]->date_of_invoice,
	
					'selling_price' => $this->input->post('input_selling_price'),
					
					'sch_disc_amt' => $this->input->post('input_sch_amount'),
					'sch_disc_per' => $this->input->post('input_sch_disc'),

					'insert_by' => $user_name_info,
					
					'item_return' => $return_stock
                ); 

                $show_text='';
				
				$is_update_stock=0;
				
				if($item_id<1)
				{
					$is_update_stock=$this->Medical_M->insert_purchase_item($Pdata);
				}else{
					$is_update_stock=$this->Medical_M->update_purchase_item($Pdata,$item_id);
				}
				
				if($is_update_stock>0)
				{
					$send_msg="Updated";
				}else{
					$send_msg="Error : Update in Stock Table";
				}
								
				if($is_update_stock>0)
				{
					$show_text=$send_msg;
				}else{
					$show_text=$send_msg;
				}
				
                $rvar=array(
					'is_update_stock' =>$is_update_stock,
					'product_code' => $item_code,
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
                'show_text'=>$send_msg
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}
	
	function purchase_invoice_item_edit($inv_id,$inv_item_id) 
	{
		$sql="select *,
		date_format(expiry_date,'%m') as str_expiry_month ,
		date_format(expiry_date,'%y') as str_expiry_year 
		from purchase_invoice_item where id=".$inv_item_id." and purchase_id=".$inv_id;
		$query = $this->db->query($sql);
		$purchase_invoice_item= $query->result();
		
		if(count($purchase_invoice_item)>0)
		{
				$rvar=array(
					'is_update_stock'=>'1',
					'item_id' =>$purchase_invoice_item[0]->id,
					'product_code' =>$purchase_invoice_item[0]->item_code,
					'product_mrp' => $purchase_invoice_item[0]->mrp,
					'selling_price'=>$purchase_invoice_item[0]->selling_price,
					'batch_code'=>$purchase_invoice_item[0]->batch_no,
					'disc_price'=>$purchase_invoice_item[0]->discount,
					'qty'=>$purchase_invoice_item[0]->qty,
					'qty_free'=>$purchase_invoice_item[0]->qty_free,
					'package'=>$purchase_invoice_item[0]->packing,
					'purchase_price'=>$purchase_invoice_item[0]->purchase_price,
					'datepicker_doe_month'=>$purchase_invoice_item[0]->str_expiry_month,
					'datepicker_doe_year'=>$purchase_invoice_item[0]->str_expiry_year,
					'drug'=>$purchase_invoice_item[0]->Item_name,

					'HSNCODE'=>$purchase_invoice_item[0]->HSNCODE,
					'CGST_per'=>$purchase_invoice_item[0]->CGST_per,
					'SGST_per'=>$purchase_invoice_item[0]->SGST_per,

					'cold_storage'=>$purchase_invoice_item[0]->cold_storage,
					'shelf_no'=>$purchase_invoice_item[0]->shelf_no,
					'rack_no'=>$purchase_invoice_item[0]->rack_no,

				);
		}else{
			$rvar=array(
					'is_update_stock'=>'0',
					'show_text'=>$sql
				);
		}
				
				$encode_data = json_encode($rvar);
				echo $encode_data;
		
	}
	
	public function purchase_invoice_item_list($inv_id)
	{
		$sql="select *,date_format(expiry_date,'%m/%y') as exp_date_str from purchase_invoice_item where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_item']= $query->result();
		
		$sql="select * from purchase_invoice where id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_invoice']= $query->result();
		
		$this->load->view('Medical/Purchase/purchase_invoice_item',$data);
		
	}

	public function purchase_invoice_item_list_old($item_code)
	{
		$sql="SELECT s.name_supplier, date_format(p.date_of_invoice,'%d-%m-%Y') as date_of_invoice_str,
		i.Item_name,i.packing,i.purchase_price,i.qty,i.qty_free,i.mrp
		FROM (purchase_invoice p JOIN purchase_invoice_item i ON p.id=i.purchase_id)
		JOIN med_supplier s ON p.sid=s.sid
		WHERE i.item_code=$item_code ORDER BY i.id DESC LIMIT 5 ";
        $query = $this->db->query($sql);
        $data['purchase_item_old']= $query->result();
		
		$this->load->view('Medical/Purchase/purchase_item_old',$data);
		
	}
	
	public function purchase_invoice_item_delete($inv_id,$inv_item_del)
	{
		$this->load->model('Medical_M');
		
		$is_update_stock=$this->Medical_M->delete_purchase_item($inv_id,$inv_item_del);
		
		if($is_update_stock==0)
		{
			$rvar=array(
				'is_update_stock' =>0,
				'show_text'=>'Sold some Quantity of this Item',
				);
		}else{
			$rvar=array(
				'is_update_stock' =>1,
				'show_text'=>'Removed Successfully',
				);
		}
	
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	
	public function AddDrugStock($drug_item_code)
    {
		$sql="select * from drug_temp where item_id='".$drug_item_code."' ";
        $query = $this->db->query($sql);
        $data['drug_item']= $query->result();
		
		$sql="select * from stock_addition where item_code='".$drug_item_code."' ";
        $query = $this->db->query($sql);
        $data['drug_stock_item']= $query->result();
		
		$sql="select * from med_supplier ";
        $query = $this->db->query($sql);
        $data['med_supplier']= $query->result();

		$this->load->view('Medical/Drug_Stock_Add',$data);
	}
	
	public function UpdateDrugStock($ss_no)
    {
		$sql="select * from stock_addition where ss_no='".$ss_no."' ";
        $query = $this->db->query($sql);
        $data['stock_item']= $query->result();
		
		$sql="select * from med_supplier ";
        $query = $this->db->query($sql);
        $data['med_supplier']= $query->result();
		
		$this->load->view('Medical/Stock_Update',$data);
	}
	
	public function load_stock_data($item_code)
	{
		$sql="select * from stock_addition where item_code='".$item_code."' ";
		$query = $this->db->query($sql);
		$data['drug_stock_item']= $query->result();

		echo '<table id="example1" class="table table-bordered table-striped TableData">
					<thead>
					<tr>
					  <th>Item Code</th>
					  <th>Item Name</th>
					  <th>Formulation</th>
					  <th>Storage/Shelf/Rack No.</th>
					  <th>Batch No.</th>
					  <th>Qty /Sale Qty</th>
					  <th>Expiry</th>
					  <th>MRP</th>
					  <th>Rate/Unit</th>
					</tr>
					</thead>
					<tbody>';

		$srno=0;

		foreach($data['drug_stock_item'] as $row)
		{ 
			$srno=$srno+1;
			echo '<tr>';
			echo '<td><a href="javascript:load_form_div(\'/Medical/UpdateDrugStock/'.$row->ss_no.'\',\'maindiv\');">'.$row->item_code.'</a></td>';
			echo '<td>'.$row->item_name.'</td>';
			echo '<td>'.$row->formulation.'</td>';
			echo '<td>'.$row->cold_storage.'/'.$row->shelf_no.'/'.$row->rack_no.'</td>';
			echo '<td>'.$row->batch_no.'</td>';
			echo '<td>'.$row->total_unit.'/'.$row->total_sale_unit.'</td>';
			echo '<td>'.$row->expiry_date.'</td>';
			echo '<td>'.$row->mrp.'</td>';
			echo '<td>'.$row->unit_rate.'</td>';
			echo '</tr>';
		}	
		
		echo '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
		
		echo '</tbody>
					<tfoot>
					<tr>
					  <th>Item Code</th>
					  <th>Item Name</th>
					  <th>Formulation</th>
					  <th>Storage/Shelf/Rack No.</th>
					  <th>Batch No.</th>
					  <th>Qty /Sale Qty</th>
					  <th>Expiry</th>
					  <th>MRP</th>
					  <th>Rate/Unit</th>
					</tr>
					</tfoot></table>';
	}
	
	public function Invoice_counter()
    {
		$sql="select * from invoice_med_master";
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		
		$sql="select * from inv_med_item";
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="select sum(amount) as Gtotal from inv_med_item";
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist']= $query->result();
		
		if($data['invoiceMaster'][0]->case_id > 0)
		{
			$sql="select * from organization_case_master where id=".$data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();
		}

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;
			
		$this->load->view('Medical/Medical_Invoice_V',$data);
		
	}
	
	public function Invoice_Med_Draft()
	{
		$this->load->view('Medical/Draft_Med_Invoice');
	}

	public function Invoice_Med_Return()
	{
		$this->load->view('Medical/Draft_Med_Return_Invoice');
	}

	
	public function Invoice_med_show($inv_id)
	{
		$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,
		if(case_credit>0,'Credit to Org','CASH') as credit_org_status  from invoice_med_master 
		where id=$inv_id";
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();

		$patient_id=$data['invoiceMaster'][0]->patient_id;
		$inv_phone_number=$data['invoiceMaster'][0]->inv_phone_number;

		if($patient_id==0)
		{
			
		}

		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist']= $query->result();

		$sql="select *,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$data['org_Name']="";
		$ipd_id=$data['invoiceMaster'][0]->ipd_id;
		
		if($ipd_id>0)
		{
			$sql="select * from  ipd_master  where id=".$ipd_id;
			$query = $this->db->query($sql);
			$ipd_master= $query->result();

			$sql="select * from  v_ipd_list m join patient_master p on m.p_id=p.id 
			where m.id=".$ipd_id;
			$query = $this->db->query($sql);
			$data['ipd_list']= $query->result();

			$data['ipd_master']=$ipd_master;
			
			if(count($ipd_master)>0)
			{
				$p_ipd_id=$ipd_master[0]->id;
				$p_ipd_code=$ipd_master[0]->ipd_code;
				
				if($ipd_master[0]->case_id>0)
				{
					$sql="select * from  organization_case_master  where id=".$ipd_master[0]->case_id;
					$query = $this->db->query($sql);
					$org_master= $query->result();
					
					if(count($org_master)>0)
					{
						$data['org_Name']=$org_master[0]->insurance_company_name;
					}
				}
			}
		}
		
		if($data['invoiceMaster'][0]->case_id > 0)
		{
			$sql="select * from organization_case_master where id=".$data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();
		}

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$data['sale_return']=$data['invoiceMaster'][0]->sale_return;

		$data['content']=$this->load->view('Medical/Med_Item_List',$data,true);

		if($data['invoiceMaster'][0]->sale_return==0)
		{
			$this->load->view('Medical/Medical_Invoice_V',$data);
		}else{
			$this->load->view('Medical/Medical_Invoice_Return_V',$data);
		}
			
	}

	//New Invoice Edit
	public function Invoice_med_datasheet($inv_id)
	{
		$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,
		if(case_credit>0,'Credit to Org','CASH') as credit_org_status  from invoice_med_master 
		where id=$inv_id";
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();

		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist']= $query->result();

		$sql="select *,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$data['org_Name']="";
		$ipd_id=$data['invoiceMaster'][0]->ipd_id;
		
		if($ipd_id>0)
		{
			$sql="select * from  ipd_master  where id=".$ipd_id;
			$query = $this->db->query($sql);
			$ipd_master= $query->result();

			$sql="select * from  v_ipd_list m join patient_master p on m.p_id=p.id 
			where m.id=".$ipd_id;
			$query = $this->db->query($sql);
			$data['ipd_list']= $query->result();

			$data['ipd_master']=$ipd_master;
			
			if(count($ipd_master)>0)
			{
				$p_ipd_id=$ipd_master[0]->id;
				$p_ipd_code=$ipd_master[0]->ipd_code;
				
				if($ipd_master[0]->case_id>0)
				{
					$sql="select * from  organization_case_master  where id=".$ipd_master[0]->case_id;
					$query = $this->db->query($sql);
					$org_master= $query->result();
					
					if(count($org_master)>0)
					{
						$data['org_Name']=$org_master[0]->insurance_company_name;
					}
				}
			}
		}
		
		if($data['invoiceMaster'][0]->case_id > 0)
		{
			$sql="select * from organization_case_master where id=".$data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();
		}

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$data['sale_return']=$data['invoiceMaster'][0]->sale_return;

		$data['content']=$this->load->view('Medical/Med_Item_List',$data,true);

		if($data['invoiceMaster'][0]->sale_return==0)
		{
			$this->load->view('Medical/Medical_Invoice_V',$data);
		}else{
			$this->load->view('Medical/Medical_Invoice_Return_V',$data);
		}
		
		
	}


	// Update Medical Invoice Master

	public function update_uhid()
	{
		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$inv_id=$this->input->post('med_invoice_id');
		$pid=$this->input->post('pid');

		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();

		$sql="select * from patient_master_exten where id='".$pid."' ";
		$query = $this->db->query($sql);
		$person_info= $query->result();

		if(count($person_info)>0)
		{
			$patient_id=$person_info[0]->id;
			$patient_code_u=$person_info[0]->p_code;
			$custmer_Name=$person_info[0]->p_fname;

			$data = array(
				'customer_type'=>1, 
				'patient_id' => $patient_id,
				'patient_code' => $patient_code_u, 					
				'inv_name' => $custmer_Name,
				'ipd_credit'=>0,
				'case_credit'=>0,
				'case_credit'=>0,
			); 

			$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
			$this->Medical_M->update_invoice_group($inv_id);
			$this->Medical_M->update_invoice_final($inv_id);
			$status= 1;
			$remark="Done";

		}else{
			$status=0;
			$remark="Not Done";
		}

		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function update_ipd()
	{
		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$inv_id=$this->input->post('med_invoice_id');
		$ipd_id=$this->input->post('ipd_id');

		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();

		$sql="select * from ipd_master where id=".$ipd_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();

		$pid=$ipd_master[0]->p_id;

		$sql="select * from patient_master_exten where id='".$pid."' ";
		$query = $this->db->query($sql);
		$person_info= $query->result();

		if(count($person_info)>0)
		{ 
			$patient_id=$person_info[0]->id;
			$patient_code_u=$person_info[0]->p_code;
			$custmer_Name=$person_info[0]->p_fname;

			$ipd_id=$ipd_master[0]->id;
			$ipd_code=$ipd_master[0]->ipd_code;

			$data = array(
				'customer_type'=>1,  
				'patient_id' => $patient_id,
				'patient_code' => $patient_code_u, 					
				'inv_name' => $custmer_Name,
				'ipd_credit'=>0,
				'case_credit'=>0,
				'ipd_id'=> $ipd_id,
				'ipd_code'=>$ipd_code,
			); 

			$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
			$this->Medical_M->update_invoice_group($inv_id);
			$this->Medical_M->update_invoice_final($inv_id);
			$status= 1;
			$remark="Done";

		}else{
			$status=0;
			$remark="Not Done";
		}

		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function update_org()
	{
		$this->load->model('Medical_M');

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$inv_id=$this->input->post('med_invoice_id');
		$org_id=$this->input->post('org_id');

		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();

		$sql="select * from organization_case_master where case_type=0 and id=".$org_id;
		$query = $this->db->query($sql);
		$org_master= $query->result();

		$pid=$org_master[0]->p_id;

		$sql="select * from patient_master_exten where id='".$pid."' ";
		$query = $this->db->query($sql);
		$person_info= $query->result();

		if(count($person_info)>0)
		{ 
			$patient_id=$person_info[0]->id;
			$patient_code_u=$person_info[0]->p_code;
			$custmer_Name=$person_info[0]->p_fname;

			$org_id=$org_master[0]->id;
			$org_code=$org_master[0]->case_id_code;

			$data = array(
				'customer_type'=>1, 
				'patient_id' => $patient_id,
				'patient_code' => $patient_code_u, 					
				'inv_name' => $custmer_Name,
				'ipd_credit'=>0,
				'case_credit'=>1,
				'ipd_id'=> 0,
				'ipd_code'=>'',
				'case_id'=>$org_id,
			); 

			$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
			$this->Medical_M->update_invoice_group($inv_id);
			$this->Medical_M->update_invoice_final($inv_id);
			$status= 1;
			$remark="Done";
            
		}else{
			$status=0;
			$remark="Not Done";
		}

		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function update_invdate()
	{
		$this->load->model('Medical_M');

		$inv_id=$this->input->post('med_invoice_id');
		$inv_date=$this->input->post('inv_date');

		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();

		$inv_date=str_to_MysqlDate($inv_date);
		
		$data = array(
			'inv_date' => $inv_date,
		); 

		$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
		
		$status= 1;
		$remark="Done";
        
		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function update_name_phone()
	{
		$this->load->model('Medical_M');

		$inv_id=$this->input->post('med_invoice_id');
		$pid=$this->input->post('pid');
		$customer_type=$this->input->post('customer_type');
		$P_Name=$this->input->post('P_Name');
		$P_Phone=$this->input->post('P_Phone');

		$doc_id=$this->input->post('doc_id');
		$doc_name=$this->input->post('doc_name');
		
		$refername=$doc_name;

		$sql="select * from doctor_master where id='".$doc_id."' ";
		$query = $this->db->query($sql);
		$doc_info= $query->result();
	
		if(count($doc_info)>0)
		{
			$refername=$doc_info[0]->p_fname;
		}

		
		$data = array(
			'customer_type'=>$customer_type, 
			'patient_id' => '0',
			'patient_code' => '', 					
			'inv_name' => $P_Name,
			'inv_phone_number' => $P_Phone,
			'ipd_credit'=>0,
			'case_credit'=>0,
			'ipd_id'=> 0,
			'ipd_code'=>'',
			'case_id'=>'0',
			'doc_id' => $doc_id,
            'doc_name' => $refername,
		); 

		$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
		
		$status= 1;
		$remark="Done";
        
		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function update_doctor()
	{
		$this->load->model('Medical_M');

		$inv_id=$this->input->post('med_invoice_id');
		
		$doc_id=$this->input->post('doc_id');
		$doc_name=$this->input->post('doc_name');
		
		$refername=$doc_name;

		$sql="select * from doctor_master where id='".$doc_id."' ";
		$query = $this->db->query($sql);
		$doc_info= $query->result();
	
		if(count($doc_info)>0)
		{
			$refername=$doc_info[0]->p_fname;
		}
		
		$data = array(
			'doc_id' => $doc_id,
            'doc_name' => $refername,
		); 

		$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
		
		$status= 1;
		$remark="Done";
        
		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}


	public function update_cr_status_ipd()
	{
		$this->load->model('Medical_M');

		$inv_id=$this->input->post('med_invoice_id');
		$credit_ipd=$this->input->post('credit_ipd');

		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();

		$ipd_id=$invoice_master[0]->ipd_id;
		
		$data = array(
			'ipd_credit' => $credit_ipd,
		); 

		$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
		
		$status= 1;
		$remark="Done";

		$this->Medical_M->update_invoice_group_gst($ipd_id,'1');
		$this->Medical_M->update_invoice_group_gst($ipd_id,'2');
		$this->Medical_M->update_invoice_group_gst($ipd_id,'3');
        
		$rvar=array(
			'status' =>$status,
			'remark'=>$remark
			);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	// End Update Medical Invoice Master
 	
	public function search_customer($sale=0)
	{
		$data['sale']=$sale;
		$this->load->view('Medical/Search_Patient',$data);
	}
	
	public function Invoice_counter_new($pno,$ipd_id,$case_id)
    {
		
		$select_doc=0;
		$p_fname='';
		$p_pcode='';
		$p_pid=$pno;
		$p_ipd_id=$ipd_id;
		$p_ipd_code='';
		$customer_type=0;
		
		$select_doc=0;
		$data['org_Name']="";
		$data['content']="";

		if($pno>0)
		{
			$sql="select *,if(gender=1,'Male','Female') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$pno."' ";
			$query = $this->db->query($sql);
			$person_info= $query->result();

			$sql="select doc_id from opd_master where p_id='".$pno."' order by  opd_id desc limit 1";
			$query = $this->db->query($sql);
			$data['opd_master']= $query->result();

			$p_fname=$person_info[0]->p_fname;
			$p_pcode=$person_info[0]->p_code;

			if(count($data['opd_master'])>0)
			{
				$select_doc=$data['opd_master'][0]->doc_id;
			}else{
				$select_doc=0;
			}

			$customer_type=1;
		}
				
		if($ipd_id>0)
		{
			$sql="select * from  ipd_master  where id=".$ipd_id;
			$query = $this->db->query($sql);
			$data['ipd_master']= $query->result();

			$sql="select * from  v_ipd_list m join patient_master p on m.p_id=p.id 
			where m.id=".$ipd_id;
			$query = $this->db->query($sql);
			$data['ipd_list']= $query->result();

			$p_ipd_code=$data['ipd_master'][0]->ipd_code;

			if($data['ipd_master'][0]->case_id>0)
			{
				$sql="select * from  organization_case_master  where id=".$data['ipd_master'][0]->case_id;
				$query = $this->db->query($sql);
				$org_master= $query->result();
				
				if(count($org_master)>0)
				{
					$data['org_Name']=$org_master[0]->insurance_company_name;
				}
			}
		}
		
		$this->load->model('Medical_M');
		
		$data['invoice_med_master'] = array( 
           	'patient_id' => $p_pid,
			'ipd_id' => $p_ipd_id,
			'ipd_code' => $p_ipd_code,
			'case_id' => $case_id,
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $p_fname,
			'patient_code' => $p_pcode,
			'doc_id' => $select_doc,
			'sale_return'=>0,
			'case_credit'=>0,
			'customer_type'=>$customer_type,
		);

		if($case_id>0)
		{
			$data['invoice_med_master']['case_credit']=1;
		}
		
		$inser_id=$this->Medical_M->add_invoice( $data['invoice_med_master']);

		if($inser_id)
		{
			$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status  from invoice_med_master where id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();

			$sql="select * from inv_med_item where inv_med_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['inv_items']= $query->result();

			$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();

			$sql="select * from doctor_master where active=1";
			$query = $this->db->query($sql);
			$data['doclist']= $query->result();

			$sql="select * from organization_case_master where id=".$case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();

			$data['sale_return']=$data['invoiceMaster'][0]->sale_return;

			$data['content']=$this->load->view('Medical/Med_Item_List',$data,true);
			
			$this->load->view('Medical/Medical_Invoice_V',$data);
		}
	}

	public function Invoice_Return_new($pno)
    {
		
		$select_doc=0;
		$p_fname='';
		$p_pcode='';
		$p_pid=$pno;
		$inv_phone_number='';

		$data['content']='';
		
		if($pno>0)
		{
			$sql="select *,if(gender=1,'Male','Female') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$pno."' ";
			$query = $this->db->query($sql);
			$person_info= $query->result();

			$p_fname=$person_info[0]->p_fname;
			$p_pcode=$person_info[0]->p_code;
			$inv_phone_number=$person_info[0]->mphone1;

			$select_doc=0;
		}

		$this->load->model('Medical_M');
		
		$data['invoice_med_master'] = array( 
           	'patient_id' => $p_pid,
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $p_fname,
			'patient_code' => $p_pcode,
			'doc_id' => $select_doc,
			'inv_phone_number'=>$inv_phone_number,
			'sale_return'=>1
		);
		
		$inser_id=$this->Medical_M->add_invoice( $data['invoice_med_master']);

		if($inser_id)
		{
			$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status  from invoice_med_master where id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();

			$sql="select * from inv_med_item where inv_med_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['inv_items']= $query->result();

			$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();

			$data['sale_return']=$data['invoiceMaster'][0]->sale_return;

			$data['content']=$this->load->view('Medical/Med_Item_List',$data,true);

			$this->load->view('Medical/Medical_Invoice_Return_V',$data);
		}
	}

	public function item_stock_list($item_code)
	{
		$sql="SELECT p.item_name,t.batch_no,t.expiry_date as expiry_date_str,t.expiry_date,
				(t.total_unit-t.total_sale_unit) AS c_qty,
				t.id,t.mrp,t.selling_unit_rate,p.id AS item_code
				FROM med_product_master p 
				JOIN purchase_invoice_item t ON p.id=t.item_code
				where (t.total_unit-t.total_sale_unit)>0 
					and (expiry_date>date_add(sysdate(),interval 1 month) or p.exp_date_applicable=0) 
					and p.id =$item_code";

		$query = $this->db->query($sql);
		$data['Item_list']=$query->result_array() ;

		$this->load->view('Medical/sale_item_list_for_add',$data);
	}
	
	public function get_drug(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="SELECT p.id as item_code, p.item_name,
			IFNULL(s.item_code,0) AS item_found,
			date_format(s.expiry_date,'%m-%y') as expiry_date_str,
			s.batch_no,    
			s.mrp,
			ifnull(s.packing,p.packing) as packing,
			s.Cur_Qty,
			p.HSNCODE as HSNCODE,
			p.CGST_per as CGST_per,
			p.SGST_per as SGST_per,
			s.mrp as selling_price,
			(s.mrp/p.packing) as sell_unit_rate			
			FROM med_product_master p LEFT JOIN sale_purchase_stock_tran s ON p.id=s.item_code AND s.Cur_Qty>0
			Where (p.item_name like '".$q."%' ";
			
			if(strlen($q)>5)
			{
				$sql=$sql. " or p.item_name SOUNDS LIKE '".$q."'";
			}

			$sql=$sql.") order by p.item_name limit 100";
		
			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				
				if($row['item_found']>0)
				{
					$new_row['label']=htmlentities(stripslashes($row['item_name'])).' |B:'.htmlentities(stripslashes($row['batch_no'])).' |Pak:'.htmlentities(stripslashes($row['packing'])).' |Rs.'.htmlentities(stripslashes($row['mrp'])). ' |Qty:'.htmlentities(stripslashes($row['Cur_Qty']));
				}else{
					$new_row['label']=htmlentities(stripslashes($row['item_name']));
				}
					
				
				$new_row['value']=htmlentities(stripslashes($row['item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_Batch']=htmlentities(stripslashes($row['batch_no']));
				$new_row['l_Expiry']=htmlentities(stripslashes($row['expiry_date_str']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				$new_row['l_unit_rate']=htmlentities(stripslashes($row['sell_unit_rate']));
				$new_row['l_c_qty']=htmlentities(stripslashes($row['Cur_Qty']));
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				$new_row['l_item_found']=htmlentities(stripslashes($row['item_found']));

				$new_row['l_HSNCODE']=htmlentities(stripslashes($row['HSNCODE']));
				$new_row['l_CGST_per']=htmlentities(stripslashes($row['CGST_per']));
				$new_row['l_SGST_per']=htmlentities(stripslashes($row['SGST_per']));
				$new_row['sql']="";
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}else{
				$new_row['sql']="";
				$row_set[] = $new_row;
				echo json_encode($row_set);
			}
		}
	}
	
	public function get_drug_master(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from purchase_invoice_item p
				where p.id in (select max(id) from purchase_invoice_item t
				where t.Item_name like '".$q."%'  group by t.Item_name )
				order by p.Item_name ";
				
			$sql="SELECT m.* ,p.id as ss_no,m.packing as p_packing,p.mrp,p.purchase_price,p.discount, 
						p.batch_no,date_format(expiry_date,'%m') as str_expiry_month ,
						date_format(expiry_date,'%y') as str_expiry_year ,
						m.batch_applicable,m.exp_date_applicable, 
						if(p.id is null,m.CGST_per,p.CGST_per) as p_CGST_per, 
						if(p.id is null,m.SGST_per,p.SGST_per) as p_SGST_per, 
						if(p.id is null,m.rack_no,p.rack_no) as p_rack_no, 
						if(p.id is null,m.shelf_no,p.shelf_no) as p_shelf_no, 
						if(p.id is null,m.cold_storage,p.cold_storage) as p_cold_storage 

					FROM med_product_master m  
					LEFT JOIN (SELECT * from purchase_invoice_item 
					WHERE id in (select max(id) from purchase_invoice_item
					WHERE Item_name LIKE '".$q."%'
					group BY item_code ) ) as p ON m.id=p.item_code
					WHERE m.Item_name LIKE '".$q."%'  
					order BY m.item_name ";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['item_name'])).' | '.htmlentities(stripslashes($row['p_packing'])).' | '.htmlentities(stripslashes($row['mrp']));
				$new_row['value']=htmlentities(stripslashes($row['item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['id']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				$new_row['l_CGST_per']=htmlentities(stripslashes($row['p_CGST_per']));
				$new_row['l_SGST_per']=htmlentities(stripslashes($row['p_SGST_per']));
				$new_row['l_HSNCODE']=htmlentities(stripslashes($row['HSNCODE']));

				$new_row['l_batch_no']=htmlentities(stripslashes($row['batch_no']));
				
				$new_row['l_purchase_price']=htmlentities(stripslashes($row['purchase_price']));
				
				$new_row['l_package']=htmlentities(stripslashes($row['p_packing']));
				$new_row['l_disc_price']=htmlentities(stripslashes($row['discount']));

				$new_row['l_rack_no']=htmlentities(stripslashes($row['p_rack_no']));
				$new_row['l_shelf_no']=htmlentities(stripslashes($row['p_shelf_no']));
				$new_row['l_cold_storage']=htmlentities(stripslashes($row['p_cold_storage']));

				$new_row['batch_applicable']=htmlentities(stripslashes($row['batch_applicable']));
				$new_row['exp_date_applicable']=htmlentities(stripslashes($row['exp_date_applicable']));

				$new_row['datepicker_doe_month']=htmlentities(stripslashes($row['str_expiry_month']));
				$new_row['datepicker_doe_year']=htmlentities(stripslashes($row['str_expiry_year']));
				
				$row_set[] = $new_row; //build an array
			  }
			  	echo json_encode($row_set); //format the array into json data
			}else{
				$row_set[] = array();
				echo json_encode($row_set);
			}
		}
	}
	
	public function get_formulation_desc(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from drugs_formulation where formulation_desc like '".$q."%'";
			$query = $this->db->query($sql);

			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['formulation_desc']));
				$new_row['value']=htmlentities(stripslashes($row['formulation_desc']));
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	public function go_final()
	{
		$this->load->model('Medical_M');
		
		$inv_id=$this->input->post('inv_id');
		$doc_id=$this->input->post('doc_id');
		$doc_name=$this->input->post('doc_name');
		$remark_ipd=$this->input->post('input_remark_ipd');
		$patient_code=$this->input->post('patient_code');
		$custmer_Name=$this->input->post('custmer_Name');
		$ipd_credit=$this->input->post('ipd_credit');

		$org_credit=$this->input->post('org_credit');
		$inv_date=$this->input->post('inv_date');

		$sql="select * from patient_master where p_code='".$patient_code."' ";
		$query = $this->db->query($sql);
		$person_info= $query->result();

		$sql="select * from doctor_master where id='".$doc_id."' ";
		$query = $this->db->query($sql);
		$doc_info= $query->result();
		
		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();

		if($this->ion_auth->in_group('InvoiceDateChanged') || $this->ion_auth->in_group('admin'))
		{
			$inv_date=str_to_MysqlDate($inv_date);
		}else{
			$inv_date=$data['invoice_master'][0]->inv_date;
		}
		
		if(count($person_info)>0)
		{
			$patient_id=$person_info[0]->id;
			$patient_code_u=$patient_code;
			$custmer_Name=$person_info[0]->p_fname;
		}else{
			$patient_id=0;
			$patient_code_u='';
		}
		
		$refername=$doc_name;
		
		if(count($doc_info)>0)
		{
			$refername=$doc_info[0]->p_fname;
		}

		$data = array( 
                    'patient_id' => $patient_id,
					'patient_code' => $patient_code_u, 					
                	'doc_id' => $doc_id,
                	'doc_name' => $refername,
                	'inv_name' => $custmer_Name,
					'remark_ipd'=>$remark_ipd,
					'ipd_credit'=>$ipd_credit,
					'case_credit'=>$org_credit,
					'inv_date' => $inv_date
                ); 

		$inser_id=$this->Medical_M->update_in_upv_item($data,$inv_id);
		$this->Medical_M->update_invoice_group($inv_id);
		$this->Medical_M->update_invoice_final($inv_id);

	}
	
	public function getDrugTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'item_id', 
			1 => 'itemname',
			2 => 'formulation',
			3=> 'genericname',
			5=> 'company_name_idx',
			4=> 'mrp'
			);

		// getting total number records without any search
		$sql_f_all = "select * ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from drug_temp ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //item_id
			$sql_where.=" AND item_id LIKE '".$requestData['columns'][0]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //itemname
			$sql_where.=" AND itemname LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		  
		if( !empty($requestData['columns'][2]['search']['value']) ){  //dosage
			$sql_where.=" AND formulation LIKE '%".$requestData['columns'][2]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][3]['search']['value']) ){  //formulation
			$sql_where.=" AND genericname LIKE '%".$requestData['columns'][3]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][4]['search']['value']) ){  //therapeutic
			$sql_where.=" AND company_name_idx LIKE '%".$requestData['columns'][4]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;
		

		$sql_order=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format
	}
	
	public function getInvoiceTable()
	{
		//Single Store Remove Store ID feild

		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 => 'inv_med_code', 
			1 => 'inv_name',
			2 => 'Code',
			3=> 'inv_date_str',
			4=> 'net_amount',
			5=> 'payment_received',
			6=> 'payment_balance',
			7=> 'id',
			8=> 'ipd_status',
			9=> 'org_status',
			10=> 'ipd_credit_type',
			11=> 'org_credit_type'
			);

		// getting total number records without any search
		$sql_f_all = "select m.inv_med_code,m.inv_name,
		Concat(if(m.patient_code<>'',Concat(m.patient_code,'<br>'),''),
		if(m.ipd_code<>'',Concat('<a href=\'javascript:load_form_div(\"/Medical/list_med_inv/',i.id,'\",\"maindiv\")\'>',m.ipd_code,'</a><br>'),''),
		if(m.case_id>0,Concat(o.case_id_code,'<br>'),'')) as Code ,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		m.doc_name,m.net_amount,m.payment_received,m.payment_balance,m.id,i.ipd_status,
		o.status as org_status,
		m.ipd_credit as ipd_credit_type,m.case_credit as org_credit_type ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from (invoice_med_master m left join ipd_master i on m.ipd_id=i.id  ) 
		left join organization_case_master o on m.case_id=o.id ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		if($this->ion_auth->in_group('MedicalStoreAdmin'))
		{
			$sql_where=" WHERE sale_return=0 ";	
		}else{
			$sql_where=" WHERE sale_return=0 and inv_date >= date_add(sysdate(),interval -50 day) ";
		}
		

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //inv_med_code
			$sql_where.=" AND inv_med_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][1]['search']['value']) ){  //inv_name
			$stext=$requestData['columns'][1]['search']['value'];
			if(is_numeric($stext) && strlen($stext)>3)
			{
				$sql_where.=" AND inv_phone_number LIKE '".$stext."%' ";
			}else{
				$sql_where.=" AND inv_name LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			}
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][2]['search']['value']) ){  //patient_code
			$sql_where.=" AND (patient_code LIKE '%".$requestData['columns'][2]['search']['value']."' OR 
				m.ipd_code LIKE '%".$requestData['columns'][2]['search']['value']."' OR 
				o.case_id_code LIKE '%".$requestData['columns'][2]['search']['value']."' )";
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;

		$sql_order=" ORDER BY id desc,". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format

	}
	
	public function getReturnInvoiceTable()
	{
		//Single Store Remove Store ID feild

		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 => 'inv_med_code', 
			1 => 'inv_name',
			2 => 'Code',
			3=> 'inv_date_str',
			4=> 'net_amount',
			5=> 'payment_received',
			6=> 'payment_balance',
			7=> 'id',
			8=> 'ipd_status',
			9=> 'org_status',
			10=> 'ipd_credit_type',
			11=> 'org_credit_type'
			);

		// getting total number records without any search
		$sql_f_all = "select m.inv_med_code,m.inv_name,
		Concat(if(m.patient_code<>'',Concat(m.patient_code,'<br>'),''),
		if(m.ipd_code<>'',Concat('<a href=\'javascript:load_form_div(\"/Medical/list_med_inv/',i.id,'\",\"maindiv\")\'>',m.ipd_code,'</a><br>'),''),
		if(m.case_id>0,Concat(o.case_id_code,'<br>'),'')) as Code ,
		m.inv_date,Date_Format(m.inv_date,'%d-%m-%Y') as inv_date_str,
		m.doc_name,m.net_amount,m.payment_received,m.payment_balance,m.id,i.ipd_status,
		o.status as org_status,
		m.ipd_credit as ipd_credit_type,m.case_credit as org_credit_type ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from (invoice_med_master m left join ipd_master i on m.ipd_id=i.id  ) 
		left join organization_case_master o on m.case_id=o.id ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		if($this->ion_auth->in_group('MedicalStoreAdmin'))
		{
			$sql_where=" WHERE sale_return=1 ";	
		}else{
			$sql_where=" WHERE sale_return=1 and inv_date >= date_add(sysdate(),interval -50 day) ";
		}
		

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //inv_med_code
			$sql_where.=" AND inv_med_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][1]['search']['value']) ){  //inv_name
			$stext=$requestData['columns'][1]['search']['value'];
			if(is_numeric($stext) && strlen($stext)>3)
			{
				$sql_where.=" AND inv_phone_number LIKE '".$stext."%' ";
			}else{
				$sql_where.=" AND inv_name LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			}
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][2]['search']['value']) ){  //patient_code
			$sql_where.=" AND (patient_code LIKE '%".$requestData['columns'][2]['search']['value']."' OR 
				m.ipd_code LIKE '%".$requestData['columns'][2]['search']['value']."' OR 
				o.case_id_code LIKE '%".$requestData['columns'][2]['search']['value']."' )";
			$sql_where_flag=1;
		}
		
		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;

		$sql_order=" ORDER BY id desc,". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format

	}
	
	
	public function getStockDrugTable()
	{
	
		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 =>'ss_no',
			1 =>'item_code', 
			2 => 'item_name',
			3 => 'formulation',
			4 => 'rack_no',
			5 => 'shelf_no',
			6 => 'batch_no',
			7 => 'total_unit',
			8 => 'expiry_date',
			9 => 'mrp',
			10 => 'update_stock_qty'
			);

		// getting total number records without any search
		$sql_f_all = "select * ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from stock_addition ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1 ";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //item_id
			$sql_where.=" AND item_code LIKE '".$requestData['columns'][0]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][1]['search']['value']) ){  //itemname
			$sql_where.=" AND item_name LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][2]['search']['value']) ){  //dosage
			$sql_where.=" AND formulation LIKE '%".$requestData['columns'][2]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][3]['search']['value']) ){  //dosage
			$sql_where.=" AND rack_no LIKE '%".$requestData['columns'][3]['search']['value']."%' ";
			$sql_where_flag=1;
		}
		
		if( !empty($requestData['columns'][4]['search']['value']) ){  //dosage
			$sql_where.=" AND shelf_no LIKE '%".$requestData['columns'][4]['search']['value']."%' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][5]['search']['value']) ){  //dosage
			$sql_where.=" AND batch_no LIKE '%".$requestData['columns'][5]['search']['value']."%' ";
			$sql_where_flag=1;
		}

		$total_filer_sql=$sql_count.$sql_from.$sql_where;
		
		$query = $this->db->query($total_filer_sql);
		$data['total_rec_filter']= $query->result();// when there is a search parameter then we have to modify total number filtered rows as per search result.
		$totalFiltered=$data['total_rec_filter'][0]->no_rec;

		$sql_order=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."   LIMIT ".$requestData['start']." ,".$requestData['length']."   ";  // adding length
		
		$Result_sql=$sql_f_all.$sql_from.$sql_where.$sql_order;
		
		$query = $this->db->query($Result_sql);
		$rdata= $query->result_array();

		$output = array(
				"draw"            => intval( $requestData['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
				"recordsTotal"    => intval( $totalData ),  // total number of records
				"recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
				"data"            => array(),
				"sql" => $Result_sql
				);

		foreach($rdata as $aRow)
		{
			$row = array();
			
			foreach($columns as $col)
			{
				$row[] = $aRow[$col];
			}

			$output['data'][] = $row;
		}

		echo json_encode($output);  // send data as json format

	}
	
	public function confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Medical_M');

		$invoice_id=$this->input->post('med_invoice_id');
		$input_amount_paid=$this->input->post('input_amount_paid');
		
		$sql="select * from invoice_med_master where id='".$invoice_id."'";
		$query = $this->db->query($sql);
		$inv_master= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$showcontent1='<div class="row no-print">
						<div class="col-xs-6">
							<a href="/index.php/Medical/invoice_print/'.$invoice_id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
						</div>
						<div class="col-xs-6">
							Payment Method by : ';          

		$showcontent2='</div></div>';	

		$pay_remark='';
				
		if($inv_master[0]->patient_id>0)
		{
			$Customerof_type=1;
		}else{
			$Customerof_type=3;
		}

		if($this->input->post('mode')==1)
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'Customerof_type'=>$Customerof_type,
					'Customerof_id'=>$inv_master[0]->patient_id,
					'Customerof_code'=>$inv_master[0]->patient_code,
					'Medical_invoice_id'=>$inv_master[0]->id,
					'Medical_invoice_code'=>$inv_master[0]->inv_med_code,
					'credit_debit'=>'0',
					'amount'=>$input_amount_paid,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Medical_M->insert_payment($paydata);

			$data = array( 
                    
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Cash',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'prepared_by'=>$user_name.'['.$user_id.']'
			);

			$this->Medical_M->update_in_upv_item( $data,$invoice_id);
				
				$status='CASH';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );

				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==2)
		{
			$FormRules = array(
                array(
                    'field' => 'input_card_mac',
                    'label' => 'Card Bank Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[4]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
                array(
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[3]|max_length[15]'
				)
            );
			
			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
				$paydata = array( 
					'payment_mode'=> '2',
					'Customerof_type'=>$Customerof_type,
					'Customerof_id'=>$inv_master[0]->patient_id,
					'Customerof_code'=>$inv_master[0]->patient_code,
					'Medical_invoice_id'=>$inv_master[0]->id,
					'Medical_invoice_code'=>$inv_master[0]->inv_med_code,
					'credit_debit'=>'0',
					'amount'=>$input_amount_paid,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id,
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran')
				);
				
				$insert_id=$this->Medical_M->insert_payment($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'invoice_status'=>'1',
						'payment_mode_desc'=>'Bank Card',
						'confirm_invoice'=>date('Y-m-d H:i:s'),
						'payment_id'=>$insert_id,
						'card_bank'=>$this->input->post('input_card_mac'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_card_bank'),
						'card_tran_id'=>$this->input->post('input_card_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']',
						'prepared_by_id'=>$user_id
				);
				
				$this->Medical_M->update_in_upv_item( $data,$invoice_id);
				
				$status='Bank Card';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				$encode_data = json_encode($rvar);
                echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}
		
		if($this->input->post('mode')==3)
		{
			$data = array( 
                    
					'payment_mode'=> '3',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'confirm_invoice'=>date('d/m/Y H:m:s'),
					'payment_mode_desc'=>'IPD Credit',
					'ipd_id'=>$this->input->post('ipd_id'),
					'prepared_by'=>$user_name.'['.$user_id.']'
			);
			
			$this->Medical_M->update_in_upv_item( $data,$invoice_id);
				
				$status='IPD Credit';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==4)
		{
			$data = array( 
					'payment_mode'=> '4',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'confirm_invoice'=>date('d/m/Y H:m:s'),
					'payment_mode_desc'=>'Org Credit',
					'case_id'=>$this->input->post('ipd_id'),
					'prepared_by'=>$user_name.'['.$user_id.']'
			);
			
			$this->Medical_M->update_in_upv_item( $data,$invoice_id);
				
				$status='Org Credit';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==5)
		{
			if($input_amount_paid<0)
			{
				$input_amount_paid=$input_amount_paid*-1;
			}
			
			$paydata = array( 
					'payment_mode'=> '1',
					'Customerof_type'=>$Customerof_type,
					'Customerof_id'=>$inv_master[0]->patient_id,
					'Customerof_code'=>$inv_master[0]->patient_code,
					'Medical_invoice_id'=>$inv_master[0]->id,
					'Medical_invoice_code'=>$inv_master[0]->inv_med_code,
					'credit_debit'=>'1',
					'amount'=>$input_amount_paid,
					'payment_date'=>date('Y-m-d H:i:s'),
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Medical_M->insert_payment($paydata);

			$data = array( 
                    
					'payment_mode'=> '1',
					'payment_status'=>'1',
					'invoice_status'=>'1',
					'payment_id'=>$insert_id,
					'payment_mode_desc'=>'Cash Return',
					'confirm_invoice'=>date('Y-m-d H:i:s'),
					'prepared_by'=>$user_name.'['.$user_id.']'
			);

			$this->Medical_M->update_in_upv_item( $data,$invoice_id);
				
				$status='CASH';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );

				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
	}
	
	public function invoice_print($inv_id,$print_format=0)
	{
		$this->load->model('Medical_M');
		$this->Medical_M->update_invoice_final($inv_id);
		
		$sql="select *,(discount_amount+item_discount_amount) as inv_disc_total,(CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,(disc_amount+disc_whole) as d_amt,(CGST+SGST) as gst,(CGST_per+SGST_per) as gst_per from inv_med_item 
		where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="select * from  ipd_master  where id=".$data['invoice_master'][0]->ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from organization_case_master where id=".$data['invoice_master'][0]->case_id;
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history_medical where Medical_invoice_id=".$inv_id;
		$query = $this->db->query($sql);
        $data['payment_history_medical']= $query->result();
		
		$sql="select *,if(credit_debit>0,amount*-1,amount) as paid_amount,
		(case payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' when 5 then 'Cash Return' else 'Other' end) as Payment_type_str
		from payment_history_medical where Medical_invoice_id=".$inv_id;
		$query = $this->db->query($sql);
        $data['payment_history']= $query->result();
		
		if($print_format==0)
		{
			$this->load->view('Medical/Medical_invoice_print_2',$data);
		}else{
			$this->load->view('Medical/Medical_invoice_print',$data);
		}
		
	}
	
	
	public function invoice_print_all($ipd_id,$cash=0)
	{
		//$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
		$this->load->model('Medical_M');
		
		$cash_where="  ";
		
		if($cash==0)
		{
			$cash_where.=" and ipd_credit=0 ";
			$med_type=1;
		}elseif($cash==1)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
			$med_type=2;
		}elseif($cash==2)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
			$med_type=3;
		}

		$this->Medical_M->update_invoice_group_gst($ipd_id,$med_type);

		
		$sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
			i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
			i.price,i.qty,sum(i.amount) as amount,i.HSNCODE,
			m.inv_med_code,m.id as m_id,
			date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
			sum(twdisc_amount) as twdisc_amount,
			sum(i.disc_amount+i.disc_whole) as d_amt,
			sum(i.CGST+i.SGST) as gst,
			(i.CGST_per+i.SGST_per) as gst_per 
			from inv_med_item i join invoice_med_master m
			on i.inv_med_id=m.id
			where m.group_invoice_id>0 and m.ipd_id=".$ipd_id.$cash_where."	group by i.inv_med_id,i.id WITH ROLLUP";
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="select sum(CGST_Tamount) as CGST_Tamount,
		sum(SGST_Tamount) as SGST_Tamount,
		sum(TaxableAmount) as TaxableAmount,
		sum(net_amount) as net_amount,
		sum(payment_received) as payment_received,
		sum(payment_balance) as payment_balance,
		sum(discount_amount+item_discount_amount) as inv_disc_total,
		(CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
		where group_invoice_id>0 and ipd_id=".$ipd_id.$cash_where." group by ipd_id";
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
		m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
		where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$sql="select sum(if(p.credit_debit>0,p.amount*-1,p.amount)) as paid_amount 
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
		$query = $this->db->query($sql);
		$data['payment_Total']= $query->result();
		
		$sql="select p.*,if(p.credit_debit>0,p.amount*-1,p.amount) as paid_amount,
		(case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' when 5 then 'Cash Return' else 'Other' end) as Payment_type_str
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();
		
		$sql="select * from inv_med_group 
		where ipd_id='".$ipd_id."' and med_type='".$med_type."'";
		$query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();

        if($cash==0)
		{
			if($data['inv_med_group'][0]->bill_final==1)
	        {
	        	$this->load->view('Medical/Medical_invoice_print_all',$data);
	        }else{
	        	$this->load->view('Medical/Medical_invoice_print_all_provisional',$data);
	        }
		}else{
			$this->load->view('Medical/Medical_invoice_print_all',$data);
		}

	}
	
	
	public function final_invoice($inv_id)
	{
		$this->load->model('Medical_M');
		
		$this->Medical_M->update_invoice_group($inv_id);
		$this->Medical_M->update_invoice_final($inv_id);
		
		$sql="select *,(discount_amount+item_discount_amount) as inv_disc_total,(CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		 
		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
			
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age 
		from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$sql="select * from  ipd_master  where ipd_status=0 and id=".$data['invoiceMaster'][0]->ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
				
		if($data['invoiceMaster'][0]->case_id > 0)
		{
			$sql="select * from organization_case_master where id=".$data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();
		}

		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history_medical where Medical_invoice_id=".$inv_id;
		$query = $this->db->query($sql);
        $data['payment_history_medical']= $query->result();
		
		if(count($data['invoiceMaster'])>0)
		{
			if($data['invoiceMaster'][0]->ipd_id>0)
			{
				redirect('Medical/list_med_inv/'.$data['invoiceMaster'][0]->ipd_id);
			}else if($data['invoiceMaster'][0]->case_id>0 && $data['invoiceMaster'][0]->case_credit>0)
			{
				redirect('Medical/list_med_orginv/'.$data['invoiceMaster'][0]->case_id);
			}else{
				$this->load->view('Medical/Medical_Final_Invoice',$data);
			}
		}
	}

	public function final_return_invoice($inv_id)
	{
		$this->load->model('Medical_M');
		
		$sql="select *,(discount_amount+item_discount_amount) as inv_disc_total,(CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		 
		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
			
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age 
		from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$sql="select sum(if(credit_debit>0,amount*-1,amount)) as paid_amount from payment_history_medical where Medical_invoice_id=".$inv_id;
		$query = $this->db->query($sql);
        $data['payment_history_medical']= $query->result();
		
		if(count($data['invoiceMaster'])>0)
		{
			$this->load->view('Medical/Medical_Final_return_Invoice',$data);
		}else{
			echo 'Error In Inv. No.';
		}
	}
	
	public function add_item($type,$req=0)
	{
		$this->load->model('Medical_M');
		
		$inser_id=1;
		$Error_Show='';
		
		$store_stock_id=$this->input->post('l_ssno');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');
		
		if($type==1)
		{
			$inv_id=$this->input->post('inv_id');
			$qty=$this->input->post('qty');
			$disc=$this->input->post('disc');
			$mrp=$this->input->post('mrp');
			$product_unit_rate=$this->input->post('product_unit_rate');
			$item_code=$this->input->post('item_code');
			$Batch_no=$this->input->post('Batch_no');
			$ExpDt=$this->input->post('ExpDt');

			$packing=$this->input->post('packing');

			$CGST=$this->input->post('CGST');
			$SGST=$this->input->post('SGST');
			$HSNCODE=$this->input->post('HSNCODE');
			
			$sql="SELECT  *
					FROM med_product_master i
					where id= $item_code ";
			$query = $this->db->query($sql);
			$data['itemlist']= $query->result();

			$sql="select * from inv_med_item 
			where inv_med_id='$inv_id' and batch_no='$Batch_no' and item_code='$item_code'";
			$query = $this->db->query($sql);
			$itemExist= $query->result();
			
			$item_rate=$product_unit_rate;

			$amount_value=$qty*$item_rate;
			
			$disc_amount=$amount_value*$disc/100;
			
			$Tamount_value=$amount_value-$disc_amount;
			
			if($inser_id>0)
			{
				$data['insert_inv_med_item'] = array( 
					'inv_med_id' => $inv_id,
					'item_code' => $item_code,
					'item_Name' => $data['itemlist'][0]->item_name,				
					'formulation' => $data['itemlist'][0]->formulation,
					'qty' => $qty,
					'batch_no' => $Batch_no,
					'expiry' => $ExpDt,
					'price' => $item_rate,
					'price2' => $item_rate,
					'mrp' => $mrp,
					'disc_per' => $disc,
					'disc_amount' => $disc_amount,
					'amount' => $amount_value,
					'tamount' => $Tamount_value,
					'CGST_per'=> $CGST,
					'SGST_per'=> $SGST,
					'HSNCODE'=> $HSNCODE,
					'packing'=> $packing,
					'update_by_id'=>$user_id,
					'update_by_remark'=>$user_name_info,
				);

				if(count($itemExist)>0)
				{
					$inser_id=0;
					$Error_Show='Already in Item List';
				}else{
					$inser_id=$this->Medical_M->add_invoiceitem( $data['insert_inv_med_item']);
					
				}
			}
		}
		else if($type==2)
		{
			$update_qty=$this->input->post('u_qty');
			$itemid=$this->input->post('itemid');

			$sql="select * from inv_med_item where id=".$itemid;
			$query = $this->db->query($sql);
			$data['inv_med_item']= $query->result();
			
			$CGST=$data['inv_med_item'][0]->CGST_per;
			$SGST=$data['inv_med_item'][0]->SGST_per;
			$disc=$data['inv_med_item'][0]->disc_per;
			
			$inv_id=$data['inv_med_item'][0]->inv_med_id;
			
			$stock_qty=0;
			
			$old_qty=$data['inv_med_item'][0]->qty;
			$diff_qty=$update_qty-$old_qty;
		
						
			$item_rate=$data['inv_med_item'][0]->price;
			$amount_value=$update_qty*$item_rate;
			
			$disc_amount=$amount_value*$disc/100;
			
			$Tamount_value=$amount_value-$disc_amount;

			$update_inv_med_item = array( 
				'qty' => $update_qty,
				'disc_amount' => $disc_amount,
				'amount' => $amount_value,
				'tamount' => $Tamount_value,
				'item_code' => $data['inv_med_item'][0]->item_code,
				'batch_no' => $data['inv_med_item'][0]->batch_no,
				'mrp' => $data['inv_med_item'][0]->mrp,
				'packing' => $data['inv_med_item'][0]->packing,
			);
			
			$this->Medical_M->update_invoiceitem($update_inv_med_item,$itemid);
			$inser_id=$itemid;
			
			$this->Medical_M->update_invoice_group($inv_id);
			$this->Medical_M->update_invoice_final($inv_id);
				
		}else{
			$inv_id=$this->input->post('inv_id');
			$itemid=$this->input->post('itemid');
			
			$this->Medical_M->delete_invoiceitem($itemid);
		
		}

		$content='';
		
		if($inser_id>0)
		{
			$this->Medical_M->update_invoice_final($inv_id);
			
			$sql="select * from inv_med_item  where inv_med_id=".$inv_id;
			$query = $this->db->query($sql);
			$data['inv_items']= $query->result();
			
			$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,
				sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();

			$sql="select * from  invoice_med_master where id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();

			//$user = $this->ion_auth->user()->row();
			//$data['user_id'] = $user->id;

			$data['sale_return']=$data['invoiceMaster'][0]->sale_return;
			
			$content=$this->load->view('Medical/Med_Item_List',$data,true);
		}
	
		
		$rvar=array(
		'insertid' =>$inser_id,
		'content'=>$content,
		'error'=>$Error_Show,
		'csrf_dst_name_value'=>$this->security->get_csrf_hash()
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	
	
	public function list_org_ipd()
	{
		$sql = "select *,(paid_amount-(charge_amount+med_amount)) as balance 
		from v_ipd_list where ipd_status=0 ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();

		$this->load->view('Medical/Medical_ipd_org_list',$data);
	}
	
	
	public function list_org()
	{
        $sql = "select p.p_fname,p.p_code,p.p_relative,p.p_rname,o.insurance_company_name,o.case_id_code,o.id as org_id,o.p_id ,
				o.date_registration,Date_Format(o.date_registration,'%d-%m-%Y') as str_register_date,o.insurance_id
				from organization_case_master o join patient_master p on o.p_id=p.id 
				and o.insurance_id >1 and o.`status`=0 and o.case_type=0 ";
        $query = $this->db->query($sql);
        $data['organization_case_master']= $query->result();

     	
		$this->load->view('Medical/Medical_org_list',$data);
	}
		
	public function list_med_inv($ipd_id)
	{
		$sql="select *,if(ipd_credit=0,'CASH','CREDIT') as credit,
		if(ipd_credit=1 and ipd_credit_type=0,'Yes','No')  as Package 
		from invoice_med_master where  ipd_id = ".$ipd_id." Order by inv_date,id";
		$query = $this->db->query($sql);
		$data['inv_master']= $query->result();
		
		$sql="select sum(if(ipd_credit=0,net_amount,0)) as cash_netAmount,
		sum(if(ipd_credit=1 and ipd_credit_type=1,net_amount,0))  as IPDCrAmount ,
		sum(if(ipd_credit=1 and ipd_credit_type=0,net_amount,0))  as PackageCrAmount,
		sum(payment_balance) as t_payment_balance ,sum(net_amount) as t_net_amount
		from invoice_med_master where  ipd_id = ".$ipd_id;
		$query = $this->db->query($sql);
		$data['inv_master_total']= $query->result();
		
		$sql="select * from ipd_master where  id = ".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age from patient_master where id='".$data['ipd_master'][0]->p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$sql="select * from v_ipd_list where id=".$ipd_id;
		$query = $this->db->query($sql);
        $data['ipd_list']= $query->result();
		
		$sql="select * from inv_med_group where med_type=1 and ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
        $data['med_group_list']= $query->result();
		
		$sql="select b.ipd_id,b.bed_id,b.TDate,m.room_name,m.bed_no
				from ipd_bed_assign b left join hc_bed_master m on b.bed_id=m.id
				where  b.ipd_id=".$ipd_id." order by TDate desc";
		$query = $this->db->query($sql);
        $data['bed_list']= $query->result();

		if($data['ipd_master'][0]->case_id>0)
		{
			$sql="select * from organization_case_master where id='".$data['ipd_master'][0]->case_id."'";
			$query = $this->db->query($sql);
			$data['orgcase']= $query->result();

			$ins_comp_id=$data['orgcase'][0]->insurance_id;
			$inc_card_id=$data['orgcase'][0]->insurance_card_id;

			$sql="select * from hc_insurance_card where id='".$inc_card_id."'";
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();

			$sql="select * from hc_insurance where id='".$ins_comp_id."'";
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
		}
		
		$this->load->view('Medical/Medical_invoice_list',$data);
	}
	
	public function list_med_orginv($org_id)
	{
		
		$sql="select *,if(case_credit=0,'CASH','CREDIT') as credit 
		from invoice_med_master where  case_id = ".$org_id." order by inv_date,id";
		$query = $this->db->query($sql);
		$data['inv_master']= $query->result();
		
		$sql="select * from organization_case_master where  id = ".$org_id;
		$query = $this->db->query($sql);
		$data['orgcase_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['orgcase_master'][0]->p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();

		$this->load->view('Medical/Medical_org_invoicelist',$data);
	}
	
	public function update_discount()
	{
		$invoice_id=$this->input->post('med_invoice_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$dataupdate = array( 
				'discount_amount' => $this->input->post('input_dis_amt'),
				'discount_remark' => $this->input->post('input_dis_desc'),
				'discount_by'=>$user_name.'['.$user_id.']'				
			);
		
		$this->load->model('Medical_M');
		$this->Medical_M->update_in_upv_item($dataupdate,$invoice_id);
		
		$this->Medical_M->update_invoice_final($invoice_id);
		
	}
	
	public function BillFinal($group_id)
	{
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$dataupdate = array( 
				'bill_final' => '1',
				'bill_final_by'=>$user_name.'['.$user_id.']'				
				);
		
		$this->load->model('Medical_M');
		$this->Medical_M->update_group_invoice($dataupdate,$group_id);
		
		$rvar=array(
					'update' =>1,
					);
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function InvBillFinal($invoice_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$dataupdate = array( 
				'bill_final' => '1',
				'bill_final_by'=>$user_name.'['.$user_id.']'				
				);
		
		$this->load->model('Medical_M');
		$this->Medical_M->update_in_upv_item($dataupdate,$invoice_id);
		
		$rvar=array(
					'update' =>1,
					);
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	
	public function search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9 _.@\-]/', '', trim($sdata));

		if(strlen($sdata)==0)
        {
        	$sql = "SELECT p.*,GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age , 
				Max(o.apointment_date) as Last_OPD ,
				date_format(if(Max(o.apointment_date) IS NULL,p.insert_date,Max(o.apointment_date)),'%d-%m-%Y') AS Last_Visit
				FROM patient_master p left join opd_master o on p.id=o.p_id
				group BY p.id 
				order BY if(Max(o.apointment_date) IS NULL,p.insert_date,Max(o.apointment_date)) DESC
				LIMIT 100";
        }else{

        	$sdate_array=explode(" ",$sdata);
        	$search_string=" 1=1 " ;

        	foreach($sdate_array as $row_data)
        	{
				if(is_numeric($row_data))
				{
					$search_string.=" and (p.p_code like '%$row_data' 
									or p.mphone1 = '$row_data' 
									or p.udai='$row_data' )";
				}elseif(ctype_alpha($row_data)){
					$search_string.=" and (p.p_fname like '%$row_data%' 
						or p.email1 = '$row_data' 
						or SUBSTRING_INDEX(p.p_fname,' ',1) sounds like '".$row_data."')";
				}else{
					$search_string.=" and (p.p_code like '$row_data' 
						or p.email1 = '$row_data' )";
				}
        	}

		    $sql = "SELECT p.*,GET_AGE_1(dob,age,age_in_month,estimate_dob) AS age , 
					Max(o.apointment_date) as Last_OPD ,
					date_format(if(Max(o.apointment_date) IS NULL,p.insert_date,Max(o.apointment_date)),'%d-%m-%Y') AS Last_Visit
					FROM patient_master p left join opd_master o on p.id=o.p_id
					WHERE  $search_string
					group BY p.id 
					order BY if(Max(o.apointment_date) IS NULL,p.insert_date,Max(o.apointment_date)) DESC ";
		}


		$query = $this->db->query($sql);
        $data['data']= $query->result();

        $this->load->view('Medical/Patient_Search_Result',$data);
	}
	
	public function Update_Invoice_ipd_credit_type()
	{
		$ipd_credit_type= $this->input->post('ipd_credit_type');
		$inv_med_id = $this->input->post('inv_med_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.'['.$user_id.']T:'.date('d-m-Y');

		if($inv_med_id=='1')
		{
			$ipd_credit_type_update='Include by : '.$user_name;
		}else{
			$ipd_credit_type_update='Packeage by '.$user_name;
		}

		$dataupdate = array( 
			'ipd_credit_type' => $ipd_credit_type,
			'ipd_credit_type_update'=>$ipd_credit_type_update,
		);
		
		$this->load->model('Medical_M');
		$this->Medical_M->update_in_upv_item($dataupdate,$inv_med_id);
	}
	
	
	
	public function Med_Inv_Group($inv_med_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql = "select * from invoice_med_master where id=".$inv_med_id;
		$query = $this->db->query($sql);
        $invoice_med_master= $query->result();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$old_remark=$invoice_med_master[0]->update_history;
		
		$new_remark='Group By : '.$user_name.'['.$user_id.']'.' Date:'.date('d-m-Y h:i:s').PHP_EOL;
	
		$new_remark.= $old_remark;
		
		$dataupdate = array( 
				'med_group_id' => 1,
				'update_history' => $new_remark
				);
		
		$this->load->model('Medical_M');
		$this->Medical_M->update_in_upv_item($dataupdate,$inv_med_id);
		$this->Medical_M->update_invoice_group($inv_med_id);
		
		$rvar=array(
					'update' =>1,
					);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	
	}

	public function Update_Group_Discount($med_group_id)
	{
		$input_discount= $this->input->post('input_discount');
		$Ipd_ID= $this->input->post('ipd_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$dataupdate = array( 
				'discount_group' => $input_discount,
				'discount_by_group' => $user_name.'['.$user_id.']'.' Date:'.date('d-m-Y h:i:s')
				);

		$this->load->model('Medical_M');
		$this->Medical_M->update_group_invoice($dataupdate,$med_group_id);
		$this->Medical_M->update_invoice_group_gst($Ipd_ID);
		
		$rvar=array(
					'update' =>1,
					);

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function edit_invoice_edit($inv_id)
    {
		$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,
		if(case_credit>0,'Credit to Org','CASH') as credit_org_status  from invoice_med_master 
		where id=$inv_id";
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist']= $query->result();

		$sql="select *,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$data['org_Name']="";
		$ipd_id=$data['invoiceMaster'][0]->ipd_id;
		
		if($ipd_id>0)
		{
			$sql="select * from  ipd_master  where id=".$ipd_id;
			$query = $this->db->query($sql);
			$ipd_master= $query->result();

			$sql="select * from  v_ipd_list m join patient_master p on m.p_id=p.id 
			where m.id=".$ipd_id;
			$query = $this->db->query($sql);
			$data['ipd_list']= $query->result();

			$data['ipd_master']=$ipd_master;
			
			if(count($ipd_master)>0)
			{
				$p_ipd_id=$ipd_master[0]->id;
				$p_ipd_code=$ipd_master[0]->ipd_code;
				
				if($ipd_master[0]->case_id>0)
				{
					$sql="select * from  organization_case_master  where id=".$ipd_master[0]->case_id;
					$query = $this->db->query($sql);
					$org_master= $query->result();
					
					if(count($org_master)>0)
					{
						$data['org_Name']=$org_master[0]->insurance_company_name;
					}
					
				}
			}
		}
		
		if($data['invoiceMaster'][0]->case_id > 0)
		{
			$sql="select * from organization_case_master where id=".$data['invoiceMaster'][0]->case_id;
			$query = $this->db->query($sql);
			$data['OCaseMaster']= $query->result();
		}

		

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;
		
		$this->load->view('Medical/invoice_master_edit',$data);
		
	}
	
	public function LockIPD($inv_med_id,$lock=1)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql = "select * from ipd_master where id=".$inv_med_id;
		$query = $this->db->query($sql);
        $ipd_master= $query->result();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$old_remark=$ipd_master[0]->lock_remark;
		
		if($lock==1)
		{
			$new_remark='Locked By : '.$user_name.'['.$user_id.']'.' Date:'.date('d-m-Y h:i:s').PHP_EOL;
		
		}else{
			$new_remark='Unlocked By : '.$user_name.'['.$user_id.']'.' Date:'.date('d-m-Y h:i:s').PHP_EOL;
		}
		
		$new_remark.= $old_remark;
		
		$dataupdate = array( 
				'lock_medical' => $lock,
				'lock_remark' => $new_remark
				);
		
		$this->load->model('Ipd_M');
		$this->Ipd_M->update($dataupdate,$inv_med_id);
		
		$rvar=array(
					'update' =>1,
					);
				
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
		
	}

	public function load_model_box($ipd_id)
	{
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),
		DATEDIFF(discharge_date,register_date)) as no_days,
		if(case_type=1,'MLC','Non MLC') as case_type_s, 
		Date_format(register_date,'%d-%m-%Y') as str_register_date from ipd_master 
		where id='".$ipd_id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age 
		from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$sql="select * from inv_med_group where med_type=1 and ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
        $data['med_group_list']= $query->result();
		
		$sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
		Date_Format(payment_date,'%d-%m-%Y') as str_payment_date,
		Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=1";
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();
		
		$sql="select * from inv_med_group where med_type=1 and ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
		$data['inv_med_group']= $query->result();
		
		
		$phone_number_list=array($data['person_info'][0]->mphone1,$data['ipd_info'][0]->P_mobile1,$data['ipd_info'][0]->P_mobile2);

		$data['phone_number_list']=array_unique($phone_number_list);
		
		$this->load->view('Medical/Med_Model_Payment',$data);
	}
	
	public function payment_receipt($ipd_id,$cash=0)
	{
		$cash_where="  ";
		
		if($cash==0)
		{
			$cash_where.=" and ipd_credit=0 ";
			$med_type=1;
		}elseif($cash==1)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
			$med_type=2;
		}elseif($cash==2)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
			$med_type=3;
		}
		
		$sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
		m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
		where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$sql="select * from inv_med_group 
		where ipd_id='".$ipd_id."' and med_type=".$med_type;
		$query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();

		$sql="select p.*,if(p.credit_debit>0,p.amount*-1,p.amount) as paid_amount,
		Date_Format(payment_date,'%d-%m-%Y') as str_payment_date,
		(case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' when 5 then 'Cash Return' else 'Other' end) as Payment_type_str
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();
		
		$this->load->view('Medical/Med_Payment_Receipt',$data);

		//load mPDF library
        $this->load->library('m_pdf');
        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;

        $file_name='Report-payment_receipt-'.$ipd_id."-".date('Ymdhis').".pdf";
        $filepath=$file_name;
	    $content=$this->load->view('Medical/Med_Payment_Receipt',$data,TRUE);
        $this->m_pdf->pdf->WriteHTML($content);
        $this->m_pdf->pdf->Output($filepath,"I");

	}

	public function payment_receipt_sms($ipd_id,$cash=0)
	{
		$inv_phone_number=$this->input->post('phone_number');

		$cash_where="  ";
		
		if($cash==0)
		{
			$cash_where.=" and ipd_credit=0 ";
			$med_type=1;
		}elseif($cash==1)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
			$med_type=2;
		}elseif($cash==2)
		{
			$cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
			$med_type=3;
		}
		
		$sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
		m.org_id,m.P_mobile1,m.P_mobile2 from  v_ipd_list m join patient_master p on m.p_id=p.id 
		where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $ipd_master= $query->result();
		
		$sql="select * from inv_med_group 
		where ipd_id='".$ipd_id."' and med_type=".$med_type;
		$query = $this->db->query($sql);
		$inv_med_group= $query->result();
		
		$sql="select * from invoice_med_master 
		where ipd_id='".$ipd_id."' and ipd_credit=0 order by id desc limit 1";
		$query = $this->db->query($sql);
        $inv_med_master= $query->result();

		$sql="select p.*,if(p.credit_debit>0,p.amount*-1,p.amount) as paid_amount,
		Date_Format(payment_date,'%d-%m-%Y') as str_payment_date,
		(case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' when 5 then 'Cash Return' else 'Other' end) as Payment_type_str
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=".$med_type." order by id desc limit 1";
		$query = $this->db->query($sql);
		$payment_history_last= $query->result();

		$Last_Inv_Amt=0;
		$Last_pay_Amt=0;

		if(count($inv_med_master)>0)
		{
			$Last_Inv_Amt=$inv_med_master[0]->net_amount;
		}

		if(count($payment_history_last)>0)
		{
			$Last_pay_Amt=$payment_history_last[0]->paid_amount;
		}
		
		$SMS_SEND= M_store_short." STORE BILL STATUS%nIPD No.: ".$ipd_master[0]->ipd_code."%nPName: ".Trim($ipd_master[0]->p_fname)."%nTot. Med Amt.: ".Round($inv_med_group[0]->net_amount,0)."%nLast Inv. Amt.: ".Round($Last_Inv_Amt,0)."%nTot. Paid Amt.: ".Round($inv_med_group[0]->payment_received)."%nLast Paid Amt.: ".Round($Last_pay_Amt,0)."%nBal. Amt.: ".Round($inv_med_group[0]->payment_balance,0)."%nFrom: DevSoftTech HMS";

		

		//$inv_phone_number="9720958717";

		$this->load->model('SMS_M');

		$data=array(
			'to_number'=>$inv_phone_number,
			'message'=>$SMS_SEND
		);

		$insert_id=$this->SMS_M->insert_sms_outbox($data);

		
		$rvar=array(
			'update' =>$insert_id,
			'msg_text'=>"Message Send"
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	public function group_confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('Ipd_M');
		$this->load->model('Medical_M');
		
		$ipd_id=$this->input->post('ipd_id');
		$amount=$this->input->post('amount');
		$Med_Group_id=$this->input->post('Med_Group_id');
		
		$sql="select * from ipd_master where id=".$ipd_id;
		$query = $this->db->query($sql);
        $ipd_master= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
	
		$pay_remark=$this->input->post('cash_remark');
		$cr_dr=$this->input->post('cr_dr');

		
		$pay_datetime=str_to_MysqlDate($this->input->post('date_payment')).' '.date('H:i:s');
		
		if($this->input->post('mode')==1)
		{
			$paydata = array( 
					'payment_mode'=> '1',
					'Customerof_type'=>'2',
					'Customerof_id'=>$ipd_master[0]->p_id,
					'group_id'=>$Med_Group_id,
					'ipd_id'=>$ipd_id,
					'med_type'=>0,
					'credit_debit'=>$cr_dr,
					'amount'=>$amount,
					'payment_date'=>$pay_datetime,
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id
			);

			$insert_id=$this->Medical_M->insert_payment($paydata);

			$this->Medical_M->update_invoice_group_gst($ipd_id);
			
			$rvar=array(
			'update' =>1,
			'ipd_id'=> $ipd_id,
			'payid' => $insert_id,
			'pay_date' =>$pay_datetime
			);	
			$encode_data = json_encode($rvar);
			echo $encode_data;
		}
		
		if($this->input->post('mode')==2)
		{
			
			$FormRules = array(
                array(
                    'field' => 'input_card_mac',
                    'label' => 'Card Bank Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
				/*,
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
                array(
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[1]|max_length[15]'
                )*/
            );
			
			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
            	

				$paydata = array( 
					'payment_mode'=> '2',
					'Customerof_type'=>'2',
					'Customerof_id'=>$ipd_master[0]->p_id,
					'group_id'=>$Med_Group_id,
					'ipd_id'=>$ipd_id,
					'med_type'=>0,
					'credit_debit'=>$cr_dr,
					'amount'=>$amount,
					'payment_date'=>$pay_datetime,
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'update_by_id'=>$user_id,
					'card_bank'=>$this->input->post('input_card_mac'),
					'cust_card'=>$this->input->post('input_card_bank'),
					'card_remark'=>$this->input->post('input_card_digit'),
					'card_tran_id'=>$this->input->post('input_card_tran'),
					
				);
				
				$insert_id=$this->Medical_M->insert_payment($paydata);

				$this->Medical_M->update_invoice_group_gst($ipd_id);
				
				$rvar=array(
				'update' =>1,
				'ipd_id'=> $ipd_id,
				'payid' => $insert_id,
				'pay_date' =>$pay_datetime
				);
				$encode_data = json_encode($rvar);
				echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}
		
		
		if($this->input->post('mode')==5)
		{
			$FormRules = array(
                array(
                    'field' => 'input_person_name',
                    'label' => 'Name on Chq.',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_bank_tran',
					'label' => 'Chq. Number',
                    'rules' => 'required|min_length[1]|max_length[15]'
                )
				/*,
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[1]|max_length[10]'
                ),
				 array(
                    'field' => 'input_card_bank',
					'label' => 'Bank Name ',
                    'rules' => 'required|min_length[1]|max_length[50]'
                ),
                */
            );
			
			$this->form_validation->set_rules($FormRules);
			
			if ($this->form_validation->run() == TRUE)
            {
				$pay_remark='Chq Payment';
				
				$paydata = array( 
					'payment_mode'=> '2',
					'payof_type'=>'4',
					'payof_id'=>$ipd_master[0]->id,
					'payof_code'=>$ipd_master[0]->ipd_code,
					'credit_debit'=>$cr_dr,
					'amount' => $amount,
					'payment_date'=>$pay_datetime,
					'remark'=>$pay_remark,
					'update_by'=>$user_name.'['.$user_id.']',
					'card_bank'=>$this->input->post('input_bank_hospital'),
					'cust_card'=>$this->input->post('input_bank_name'),
					'card_remark'=>$this->input->post('input_person_name'),
					'card_tran_id'=>$this->input->post('input_bank_tran'),
					'update_by_id'=>$user_id
				);
				
				$insert_id=$this->Payment_M->insert($paydata);

				$data = array( 
						'payment_mode'=> '2',
						'payment_mode_desc'=>'Bank Chq.-Draft',
						'payment_id'=>$insert_id,
						'pay_date'=>$pay_datetime,
						'enter_pay_date' => date('Y-m-d H:i:s'),
						'ipd_id' => $ipd_id,
						'amount' => $amount,
						'card_bank'=>$this->input->post('input_bank_hospital'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'cust_card'=>$this->input->post('input_bank_name'),
						'card_tran_id'=>$this->input->post('input_bank_tran'),
						'prepared_by'=>$user_name.'['.$user_id.']',
						'prepared_by_id'=>$user_id
				);
				
				$insert_ipdpay_id=$this->Ipd_M->insert_payment_ipd( $data);
				
				$status='Bank Card';
				
								
				$rvar=array(
				'update' =>1,
				'ipd_id'=> $ipd_id,
				'payid' => $insert_ipdpay_id
				);
								
				$encode_data = json_encode($rvar);
                echo $encode_data;
			}
			else{
				$send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
			}
		}
	}
	
	public function group_cash_print($ipd_id,$payid)
	{
		$sql="select *,date_format(payment_date,'%d-%m-%Y') as pay_date,
		(case payment_mode when 1 then 'CASH' when 2 then 'Bank' Else 'Other' End) as payment_mode_desc
		from payment_history_medical where id=".$payid;
		$query = $this->db->query($sql);
		$data['med_payment']= $query->result();
		
		$sql="select * from ipd_master where id =".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$pno=$data['ipd_master'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age 
		from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$this->load->view('Medical/Med_Payment_receipt',$data);
		
	}

	public function Invoice_Item_Return($inv_type_id,$inv_type=0)
	{
		$data['inv_type_id']=$inv_type_id;
		$data['inv_type']=$inv_type;

		$p_id=0;
		
		if($inv_type==0)
		{
			$sql="select *	from ipd_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['ipd_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
			t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
			t.amount,t.disc_amount,t.tamount,r.r_qty,r.id as return_id
			from (invoice_med_master m join inv_med_item t on m.id=t.inv_med_id ) left join inv_med_item_return r on t.id=r.inv_item_id and return_status=0
			where m.ipd_id=".$inv_type_id. " order by t.item_Name,t.batch_no";
		
			$p_id=$data['ipd_master'][0]->p_id;
			
		}elseif($inv_type==1){
			$sql="select *	from organization_case_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['org_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_amount,t.tamount,r.r_qty,r.id as return_id
				from (invoice_med_master m join inv_med_item t on m.id=t.inv_med_id ) left join inv_med_item_return r on t.id=r.inv_item_id and return_status=0
				where m.case_id=".$inv_type_id. "  order by t.item_Name,t.batch_no";
			
			$p_id=$data['org_master'][0]->p_id;
		}else{
			$sql="select *	from invoice_med_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['invoice_med_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_amount,t.tamount,r.r_qty,r.id as return_id
				from (invoice_med_master m join inv_med_item t on m.id=t.inv_med_id ) left join inv_med_item_return r on t.id=r.inv_item_id and return_status=0
				where m.id=".$inv_type_id. "   order by t.item_Name,t.batch_no";
			
			$p_id=$data['invoice_med_master'][0]->patient_id;
		}
		
		$sql="select * 	from patient_master_exten 
		where id='".$p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$query = $this->db->query($sql_items);
		$data['inv_items']= $query->result();

		$this->load->view('Medical/Medical_Invoice_Return',$data);
	}

	public function Med_Return_invoice_search($r_inv_id)
	{
		$data['r_inv_id']=$r_inv_id;
		$this->load->view('Medical/Med_Return_Invoice_search',$data);
	}

	public function Invoice_Item_old()
	{
		$search_text=trim($this->input->post('search_text'));
		$search_type=$this->input->post('search_type');

		if($search_type==1){
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where  m.inv_phone_number='$search_text'  and m.ipd_id=0 and m.case_id=0 ";
			$query = $this->db->query($sql);
        	$data['med_inv_item_list']= $query->result();
		}

		if($search_type==2){
			$sql="SELECT count(id) as no_rec,group_concat(id) as p_id_list 
			From patient_master where p_code like '".$search_text."' ";
			$query = $this->db->query($sql);
			$patient_master= $query->result();

			$search_id_list="0";
			if($patient_master[0]->no_rec)
			{
				$search_id_list=$patient_master[0]->p_id_list;
			}
			
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where m.patient_id in ($search_id_list) and m.ipd_id=0 and m.case_id=0";
			$query = $this->db->query($sql);
        	$data['med_inv_item_list']= $query->result();
		}

		if($search_type==3){
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where m.inv_med_code like '".$search_text."%' and m.ipd_id=0 and m.case_id=0";
			$query = $this->db->query($sql);
        	$data['med_inv_item_list']= $query->result();
		}

		if($search_type==4){
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where i.item_Name like '".$search_text."%' ";
			$query = $this->db->query($sql);
        	$data['med_inv_item_list']= $query->result();
		}

		$this->load->view('Medical/Med_Item_old_list',$data);
	}

	public function Invoice_old($inv_id)
	{
		$sql="Select * from invoice_med_master where id=$inv_id";
		$query = $this->db->query($sql);
		$invoice_med_master= $query->result();

		$phone_no='';
		$p_id=0;
		$ipd_id=0;

		if(count($invoice_med_master)>0)
		{
			$phone_no=$invoice_med_master[0]->inv_phone_number;
			$p_id=$invoice_med_master[0]->patient_id;
			$ipd_id=$invoice_med_master[0]->ipd_id;
		}

		if($ipd_id>0){
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount,
			m.net_amount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where    m.id<>$inv_id   and m.ipd_id=$ipd_id and m.case_id=0 
			order by m.id desc";
		}else if($p_id>0)
		{
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount,
			m.net_amount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where  m.patient_id='$p_id' and m.id<>$inv_id and m.ipd_id=0 and m.case_id=0 order by m.id desc";
		}else if($phone_no!=''){
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount,
			m.net_amount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where  m.inv_phone_number='$phone_no' and m.id<>$inv_id   and m.ipd_id=0 and m.case_id=0 order by m.id desc";
		}else{
			$sql="SELECT m.id AS inv_id,m.inv_med_code,m.inv_date,m.inv_name,m.inv_phone_number,
			i.*,date_format(i.expiry,'%m-%Y') as exp_date,(i.disc_amount+disc_whole) as t_discount,
			m.net_amount
			FROM (invoice_med_master m JOIN inv_med_item i ON m.id=i.inv_med_id AND m.sale_return=0)
			Where  1<>1 ";
		}
		
		$query = $this->db->query($sql);
		$data['med_inv_item_list']= $query->result();		

		$this->load->view('Medical/Med_old_invoice_list',$data);
	}

	public function add_remove_item()
	{
		$this->load->model('Medical_M');

		$inv_id=$this->input->post('inv_id');
		$item_id=$this->input->post('itemid');
		$rqty=$this->input->post('rqty');

		$user = $this->ion_auth->user()->row();
		$user_update_info = $user->first_name.''. $user->last_name.'['.$user->id.']'.date('d-m-Y H:i:s');

		$sql="select * from inv_med_item where id='".$item_id."' ";
        $query = $this->db->query($sql);
        $inv_med_item_result= $query->result_array();

		$update=0;
		$msg_box="";

        if(count($inv_med_item_result)>0)
        {
        	$inv_med_item=$inv_med_item_result[0];

        	$CGST=$inv_med_item['CGST_per'];
			$SGST=$inv_med_item['SGST_per'];

			$item_rate=$inv_med_item['price'];
			$amount_value=$rqty*$item_rate*-1;

			$disc_total=$inv_med_item['disc_amount']+$inv_med_item['disc_whole'];
			$amount=$inv_med_item['amount'];
			$twdisc_amount=$inv_med_item['twdisc_amount'];

			$margin=$amount-$twdisc_amount;
			$desc_per=$margin*100/$amount;

			$disc_amount=$amount_value*$desc_per/100;
			$Tamount_value=$amount_value-$disc_amount;

			unset($inv_med_item['id']);

			$inv_med_item['inv_med_id'] =$inv_id;

			//Old Qty Sale
			$old_qty=$inv_med_item['qty'];
			$inv_med_item['qty'] =$rqty;

			$inv_med_item['return_item_id']=$item_id;
			$inv_med_item['sale_return']=1;

			$inv_med_item['amount'] = $amount_value;
			$inv_med_item['tamount'] = $Tamount_value;

			$inv_med_item['disc_amount']=$disc_amount;
			$inv_med_item['disc_per']=$desc_per;

			$inv_med_item['disc_whole']='0';
			$inv_med_item['twdisc_amount']=$Tamount_value;

			$inv_med_item['update_by_remark']=$user_update_info;
			$inv_med_item['update_by_id']=$user->id;

			$sql="select * from inv_med_item 
				where sale_return=1 and inv_med_id='$inv_id' and return_item_id='$item_id' ";
	        $query = $this->db->query($sql);
			$inv_med_item_return= $query->result();
			
			$sql="select sum(qty) as t_r_qty from inv_med_item 
				where sale_return=1 and return_item_id='$item_id' ";
	        $query = $this->db->query($sql);
			$med_item_return_total= $query->result();

			$tot_qty=0;

			if(count($med_item_return_total)>0)
			{
				$tot_return=$med_item_return_total[0]->t_r_qty;
				$tot_qty=$old_qty-$tot_return;
			}

			if($tot_qty>=$rqty)
			{
				if(count($inv_med_item_return)==0)
				{
					$update=$this->Medical_M->add_invoiceitem( $inv_med_item);
					$msg_box="Added Return list";
				}else{
					$update=$inv_med_item_return[0]->id;
					
					$this->Medical_M->update_invoiceitem( $inv_med_item,$update);
					$msg_box="Update Return ";
				}
			}else{
				$update=0;
				$msg_box="Item Already Returned";
			}
        
        }else{
        	$update=0;
			$msg_box="No item found";
		}
		
		$this->Medical_M->update_invoice_final($inv_id);
			
		$sql="select * from inv_med_item  where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="Select sum(amount) as Gtotal,sum(tamount) as tamt,
			sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt 
			from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$user = $this->ion_auth->user()->row();
		$data['user_id'] = $user->id;

		$sql="select * from  invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();


		$data['sale_return']=$data['invoiceMaster'][0]->sale_return;
		
		$content=$this->load->view('Medical/Med_Item_List',$data,true);

		$rvar=array(
                'update' =>$update,
				'msg_text'=>$msg_box,
				'content'=>$content
            );

	    $encode_data = json_encode($rvar);
	    echo $encode_data;
		
	}

	public function Update_Return()
	{
		$this->load->model('Medical_M');

		$r_qty=$this->input->post('u_qty');
		$item_id=$this->input->post('itemid');

		$sql="select * from inv_med_item where id='".$item_id."' ";
        $query = $this->db->query($sql);
        $inv_med_item_result= $query->result_array();

        $user = $this->ion_auth->user()->row();
		$user_update_info = $user->first_name.''. $user->last_name.'['.$user->id.']'.date('d-m-Y H:i:s');

		$update=0;
		$msg_box="";

        if(count($inv_med_item_result)>0)
        {
        	$inv_med_item=$inv_med_item_result[0];

        	$CGST=$inv_med_item['CGST_per'];
			$SGST=$inv_med_item['SGST_per'];

			$item_rate=$inv_med_item['price'];
			$amount_value=$r_qty*$item_rate;

			$disc=$inv_med_item['disc_per'];
			$disc_amount=$amount_value*$disc/100;

			$Tamount_value=$amount_value-$disc_amount;

        	$inv_med_item['inv_item_id'] = $inv_med_item['id'];
			unset($inv_med_item['id']);
			$inv_med_item['r_qty'] =$r_qty;

			$inv_med_item['amount'] = $amount_value;
			$inv_med_item['tamount'] = $Tamount_value;

			$inv_med_item['update_remark']=$user_update_info;

			$sql="select * from inv_med_item_return where return_status=0 and inv_item_id='".$item_id."' ";
	        $query = $this->db->query($sql);
	        $inv_med_item_return= $query->result_array();

	        if(count($inv_med_item_return)==0)
	        {
	        	$update=$this->Medical_M->insert_inv_med_item_return_add( $inv_med_item);
	        	$msg_box="Added in  Pending Return list";
	        }else{
	        	$update=1;
	        	$this->Medical_M->delete_return_item($item_id);
	        	$update=$this->Medical_M->insert_inv_med_item_return_add($inv_med_item);
	        	$msg_box="Alerady in Pending Return list, Now Update Again ";
	        }

        }else{
        	$update=0;
	        $msg_box="No item found";
        }

		$rvar=array(
                'update' =>$update,
                'msg_text'=>$msg_box
                );

	    $encode_data = json_encode($rvar);
	    echo $encode_data;
	}
	

	public function undo_Return()
	{
		$this->load->model('Medical_M');

		$item_id=$this->input->post('itemid');

		$update=1;
		$msg_box="";

       	$this->Medical_M->delete_return_item($item_id);

		$rvar=array(
                'update' =>$update,
                'msg_text'=>$msg_box
                );

	    $encode_data = json_encode($rvar);
	    echo $encode_data;
	}


	public function pre_reurn_item_list($inv_type_id,$inv_type=0)
	{
		$data['inv_type_id']=$inv_type_id;
		$data['inv_type']=$inv_type;

		$p_id=0;
		
		if($inv_type==0)
		{
			$sql="select *	from ipd_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['ipd_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
			t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
			t.amount,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
			from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0)  
			where m.ipd_id=".$inv_type_id. " order by t.item_Name,t.batch_no";
		
			$p_id=$data['ipd_master'][0]->p_id;
			
		}elseif($inv_type==1){
			$sql="select *	from organization_case_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['org_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0) 
				where m.case_id=".$inv_type_id. "  order by t.item_Name,t.batch_no";
			
			$p_id=$data['org_master'][0]->p_id;
		}else{
			$sql="select *	from invoice_med_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['invoice_med_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0) 
				where m.id=".$inv_type_id. "   order by t.item_Name,t.batch_no";
			
			$p_id=$data['invoice_med_master'][0]->patient_id;
		}
		
		$sql="select * 	from patient_master_exten 
		where id='".$p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$query = $this->db->query($sql_items);
		$data['inv_items']= $query->result();

		$this->load->view('Medical/Medical_Invoice_Return_pre',$data);
	}


	public function Med_Return_final($inv_type_id,$inv_type=0)
	{
		$this->load->model('Medical_M');

		$data['inv_type_id']=$inv_type_id;
		$data['inv_type']=$inv_type;

		$p_id=0;
		
		if($inv_type==0)
		{
			$sql="select *	from ipd_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['ipd_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
			t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
			t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
			from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0)  
			where m.ipd_id=".$inv_type_id. " order by t.item_Name,t.batch_no";
		
			$p_id=$data['ipd_master'][0]->p_id;
			
		}elseif($inv_type==1){
			$sql="select *	from organization_case_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['org_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0) 
				where m.case_id=".$inv_type_id. "  order by t.item_Name,t.batch_no";
			
			$p_id=$data['org_master'][0]->p_id;
		}else{
			$sql="select *	from invoice_med_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['invoice_med_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,
				t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=0) 
				where m.id=".$inv_type_id. "   order by t.item_Name,t.batch_no";
			
			$p_id=$data['invoice_med_master'][0]->patient_id;
		}
		
		$sql="select * 	from patient_master_exten 
		where id='".$p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$query = $this->db->query($sql_items);
		$data['inv_items']= $query->result();

		foreach($data['inv_items'] as $row)
		{
			$ritem_id=$row->ritem_id;

			$itemid=$row->inv_item_id;

			$item_rate=$row->price;
			
			$current_qty=$row->qty-$row->r_qty;

			$inv_id=$row->inv_id;
			
			$amount_value=$current_qty*$item_rate;

			$disc_per=$row->disc_per;
			
			$disc_amount=$amount_value*$disc_per/100;
			
			$Tamount_value=$amount_value-$disc_amount;

			$data['update_inv_med_item'] = array( 
				'qty' => $current_qty,
				'disc_amount' => $disc_amount,
				'amount' => $amount_value,
				'tamount' => $Tamount_value
			);

			$update_return_item = array( 
				'return_status' => '1',
			);

			$this->Medical_M->update_return_item($update_return_item,$ritem_id);

			$this->Medical_M->update_invoiceitem($data['update_inv_med_item'],$itemid);
			
			$this->Medical_M->update_invoice_final($inv_id);
		}

		redirect('/Medical/pre_reurn_item_list/'.$inv_type_id.'/'.$inv_type=0);
	}


	public function Med_Return_print($inv_type_id,$inv_type=0)
	{
		$this->load->model('Medical_M');

		$data['inv_type_id']=$inv_type_id;
		$data['inv_type']=$inv_type;

		$p_id=0;
		
		if($inv_type==0)
		{
			$sql="select *	from v_ipd_list where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['ipd_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
			t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,t.update_remark,t.return_date_time,
			t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
			from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=1)  
			where m.ipd_id=".$inv_type_id. " order by t.item_Name,t.batch_no";
		
			$p_id=$data['ipd_master'][0]->p_id;
			
		}elseif($inv_type==1){
			$sql="select *	from organization_case_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['org_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,t.update_remark,t.return_date_time,
				t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=1) 
				where m.case_id=".$inv_type_id. "  order by t.item_Name,t.batch_no";
			
			$p_id=$data['org_master'][0]->p_id;
		}else{
			$sql="select *	from invoice_med_master where id='".$inv_type_id."' ";
			$query = $this->db->query($sql);
			$data['invoice_med_master']= $query->result();
			
			$sql_items="select m.id as inv_id,m.inv_med_code,date_format(inv_date,'%d-%m-%Y') as str_inv_date,m.ipd_id,m.patient_id,m.group_invoice_id,m.case_id,
				t.id as ritem_id,t.inv_item_id,t.item_Name,t.qty,t.batch_no,t.expiry,t.store_stock_id as ss_no,t.item_code,t.mrp,t.price,t.update_remark,t.return_date_time,
				t.amount,t.disc_per,t.disc_amount,t.tamount,t.r_qty,t.id as return_id
				from (invoice_med_master m join inv_med_item_return t on m.id=t.inv_med_id and t.return_status=1) 
				where m.id=".$inv_type_id. "   order by t.item_Name,t.batch_no";
			
			$p_id=$data['invoice_med_master'][0]->patient_id;
		}
		
		$sql="select * 	from patient_master_exten 
		where id='".$p_id."' ";
		$query = $this->db->query($sql);
		$data['person_info']= $query->result();
		
		$query = $this->db->query($sql_items);
		$data['inv_items']= $query->result();

		
		$this->load->view('Medical/Medical_Invoice_Return_print',$data);
	}


	

	
}