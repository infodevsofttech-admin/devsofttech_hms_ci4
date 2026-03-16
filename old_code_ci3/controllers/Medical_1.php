<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical extends CI_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
    {
		$this->load->view('Medical/Dashboard');
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
	
	public function PurchaseNew()
    {
		$sql="select * from med_supplier";
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
			$sql="select p.id,p.Invoice_no,p.date_of_invoice,p.sid,s.name_supplier,s.short_name,s.gst_no,
			sum(t.amount) as tamount,count(t.id) as no_item
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			left join purchase_invoice_item t on p.id=t.purchase_id
			group by p.id
			order by p.id desc limit 50";
		}else{
			$sql="select p.id,p.Invoice_no,p.date_of_invoice,p.sid,s.name_supplier,s.short_name,s.gst_no,
			sum(t.amount) as tamount,count(t.id) as no_item
			from (purchase_invoice p join med_supplier s on p.sid=s.sid)
			left join purchase_invoice_item t on p.id=t.purchase_id			
			where (p.Invoice_no LIKE '%".$sdata."' 
			OR s.name_supplier LIKE '%".$sdata."%' 
			OR s.short_name LIKE '%".$sdata."%'
			OR s.gst_no = '".$sdata."%')
			group by p.id order by p.id desc limit 50";
		}

		$query = $this->db->query($sql);
        $data['purchase_list']= $query->result();
		
		$this->load->view('Medical/Purchase/purchase_supp_list',$data);
	}
	
	public function PurchaseInvoiceEdit($inv_id)
	{
		$sql="select p.id,p.Invoice_no,p.date_of_invoice,date_format(p.date_of_invoice,'%d/%m/%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no
			from purchase_invoice p join med_supplier s on p.sid=s.sid
			where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice']= $query->result();	

		$sql="select * from purchase_invoice_item where purchase_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice_item']= $query->result();

		$this->load->view('Medical/Purchase/Purchase_Invoice_Edit',$data);
	}
		
	public function SupplierListSub()
    {
		$sql="select * from med_supplier";
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

			$Udata = array( 
                    'sid'=> $sid,
					'date_of_invoice'=> str_to_MysqlDate($d_invoice),
					'Invoice_no'=> $invoice_code
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
                    'rules' => 'required|min_length[3]|max_length[30]'
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
					'expiry_date' => str_to_MysqlDate($this->input->post('datepicker_doe')),
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
                    'rules' => 'required|min_length[3]|max_length[30]'
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
                    'rules' => 'required|min_length[3]|max_length[30]'
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
					'expiry_date' => str_to_MysqlDate($this->input->post('datepicker_doe')),
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

        $FormRules = array(
                
				array(
                    'field' => 'input_drug',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
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
				
				$this->load->model('Medical_M');

				$Pdata = array(
					'purchase_id' => $inv_id,
                    'item_code' => $item_code, 
                	'item_name' => $this->input->post('input_drug'),
					'packing' => $this->input->post('input_package'),
                	'batch_no' => $this->input->post('input_batch_code'),
					'purchase_price' => $this->input->post('input_purchase_price'),
					'expiry_date' => str_to_MysqlDate($this->input->post('datepicker_doe')),
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
	
	function purchase_invoice_item_edit($inv_id,$inv_item_id) 
	{
		$sql="select * from purchase_invoice_item where id=".$inv_item_id." and purchase_id=".$inv_id;
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
					'datepicker_doe'=>$purchase_invoice_item[0]->expiry_date,
					'drug'=>$purchase_invoice_item[0]->Item_name
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
		$sql="select * from purchase_invoice_item where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_item']= $query->result();
		
		$sql="select * from purchase_invoice where id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_invoice']= $query->result();
		
		$this->load->view('Medical/Purchase/purchase_invoice_item',$data);
		
	}
	
	public function purchase_invoice_item_delete($inv_id,$inv_item_del)
	{
		$this->load->model('Medical_M');
		
		$is_update_stock=$this->Medical_M->delete_purchase_item($inv_id,$inv_item_del);
				
		$rvar=array(
		'is_update_stock' =>1,
		'show_text'=>''
		);
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
		
		$this->load->view('Medical/Medical_Invoice_V',$data);
		
	}
	
	public function Invoice_Med_Draft()
	{
		$sql="select *,if(ipd_id>0,'IPD',if(case_id>0,'Org.','Cash')) as ipd_case_id from invoice_med_master where 1<>1 ";
        $query = $this->db->query($sql);
        $data['invoice_list']= $query->result();
		
		$this->load->view('Medical/Draft_Med_Invoice',$data);
	}
	
	public function Invoice_med_show($inv_id)
	{
		$sql="select *,if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,
		if(case_credit>0,'Credit to Org','CASH') as credit_org_status  from invoice_med_master where id=".$inv_id;
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
		
		$this->load->view('Medical/Medical_Invoice_V',$data);
	}
	
	public function search_customer()
	{
		$this->load->view('Medical/Search_Patient');
	}
	
	public function Invoice_counter_new($pno,$ipd_id,$case_id)
    {
		
		$p_fname='';
		$select_doc=0;
		
		if($pno>0){
			$sql="select *,if(gender=1,'Male','FeMale') as xgender,
			GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$pno."' ";
			$query = $this->db->query($sql);
			$person_info= $query->result();
			
			$sql="select doc_id from opd_master where p_id='".$pno."' order by  opd_id desc limit 1";
			$query = $this->db->query($sql);
			$data['opd_master']= $query->result();
			
			if(count($data['opd_master'])>0)
			{
				$select_doc=$data['opd_master'][0]->doc_id;
			}else{
				$select_doc=0;
			}
			
			$sql="select l.doc_id from ipd_master p join ipd_master_doc_list l on p.id=l.ipd_id 
			where p.ipd_status=0 and  p.p_id=".$pno." ";
			$query = $this->db->query($sql);
			$data['ipd_master_docid']= $query->result();
			
			if(count($data['ipd_master_docid'])>0)
			{
				$select_doc=$data['ipd_master_docid'][0]->doc_id;
			}
			
			$data['org_Name']="";
			
			if($ipd_id<1)
			{
				$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$person_info[0]->id;
				$query = $this->db->query($sql);
				$ipd_master= $query->result();
				
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
					
				}else{
					$p_ipd_id=0;
					$p_ipd_code='';
				}
			}else{
				$sql="select * from  ipd_master  where id=".$ipd_id;
				$query = $this->db->query($sql);
				$ipd_master= $query->result();
				
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
					
				}else{
					$p_ipd_id=0;
					$p_ipd_code='';
				}
			}

			$p_fname=$person_info[0]->p_fname;
			$p_pcode=$person_info[0]->p_code;
			$p_pid=$person_info[0]->id;
			

		}else{
			$p_fname='';
			$p_pcode='';
			$p_pid=0;
			$p_ipd_id=0;
			$p_ipd_code='';
		}
		
		$sql="select * from doctor_master where active=1";
        $query = $this->db->query($sql);
        $data['doclist']= $query->result();
		
		$this->load->model('Medical_M');
		
		$data['invoice_med_master'] = array( 
           	'patient_id' => $p_pid,
			'ipd_id' => $p_ipd_id,
			'ipd_code' => $p_ipd_code,
			'case_id' => $case_id,
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $p_fname,
			'patient_code' => $p_pcode,
			'doc_id' => $select_doc
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

			$sql="select * from doctor_master where active=1";
			$query = $this->db->query($sql);
			$data['doclist']= $query->result();
			
			$this->load->view('Medical/Medical_Invoice_V',$data);
		}
	}
	
	public function get_drug_old(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from stock_addition where expiry_date>date_add(sysdate(),interval 3 month)  and update_stock_qty<total_unit and item_code='".$q."' or item_name like '".$q."%'  ";

			if(strlen($q)>3)
			{
				$sql=$sql. " or batch_no like '".$q."%'";
			}

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['item_name'])).' | '.htmlentities(stripslashes($row['batch_no'])).' | '.htmlentities(stripslashes($row['expiry_date']));
				$new_row['value']=htmlentities(stripslashes($row['item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_ss_no']=htmlentities(stripslashes($row['ss_no']));
				$new_row['l_Batch']=htmlentities(stripslashes($row['batch_no']));
				$new_row['l_Expiry']=htmlentities(stripslashes($row['expiry_date']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				$new_row['l_unit_rate']=htmlentities(stripslashes($row['unit_rate']));
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
	public function get_drug(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="select * from purchase_invoice_item 
			where expiry_date>date_add(sysdate(),interval 3 month)  
			and (total_unit-total_sale_unit-total_return_unit-total_lost_unit)>0 
			and (item_name like '".$q."%' ";

			if(strlen($q)>3)
			{
				$sql=$sql. " or batch_no like '".$q."%'";
			}

			$sql=$sql.") limit 20";
		
			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Item_name'])).' | '.htmlentities(stripslashes($row['batch_no'])).' | '.htmlentities(stripslashes($row['expiry_date']));
				$new_row['value']=htmlentities(stripslashes($row['Item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_ss_no']=htmlentities(stripslashes($row['id']));
				$new_row['l_Batch']=htmlentities(stripslashes($row['batch_no']));
				$new_row['l_Expiry']=htmlentities(stripslashes($row['expiry_date']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				$new_row['l_unit_rate']=htmlentities(stripslashes($row['selling_unit_rate']));
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
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

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Item_name'])).' | '.htmlentities(stripslashes($row['packing'])).' | '.htmlentities(stripslashes($row['mrp']));
				$new_row['value']=htmlentities(stripslashes($row['Item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				$new_row['l_CGST_per']=htmlentities(stripslashes($row['CGST_per']));
				$new_row['l_SGST_per']=htmlentities(stripslashes($row['SGST_per']));
				$new_row['l_HSNCODE']=htmlentities(stripslashes($row['HSNCODE']));
				
				$new_row['l_purchase_price']=htmlentities(stripslashes($row['purchase_price']));
				
				$new_row['l_package']=htmlentities(stripslashes($row['packing']));
				$new_row['l_disc_price']=htmlentities(stripslashes($row['discount']));
				
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
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

		$sql_from=" from (invoice_med_master m left join ipd_master i on m.ipd_id=i.id) 
		left join organization_case_master o on m.case_id=o.id ";

		$total_sql=$sql_count.$sql_from;
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1 = 1";

		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //inv_med_code
			$sql_where.=" AND inv_med_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][1]['search']['value']) ){  //inv_name
			$sql_where.=" AND inv_name LIKE '%".$requestData['columns'][1]['search']['value']."%' ";
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
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
                array(
                    'field' => 'input_card_digit',
					'label' => 'Card Last 4 Digit ',
                    'rules' => 'required|min_length[4]|max_length[4]'
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
					'payment_mode'=> '5',
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
					'payment_mode'=> '5',
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
		$this->Medical_M->update_invoice_group_gst($ipd_id);

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
		where ipd_id='".$ipd_id."' and med_type=1";
		$query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();

		$this->load->view('Medical/Medical_invoice_print_all',$data);
		
	}
	
	
	public function final_invoice($inv_id)
	{
		$this->load->model('Medical_M');
		
		$this->Medical_M->update_invoice_group($inv_id);
		$this->Medical_M->update_invoice_final($inv_id);
		
		$sql="select *,(discount_amount+item_discount_amount) as inv_disc_total,(CGST_Tamount+SGST_Tamount) as TGST ,
		if(ipd_credit>0,'Credit to IPD','CASH') as credit_status,if(case_credit>0,'Credit to Org','CASH') as credit_org_status
		from invoice_med_master where id=".$inv_id;
		
		$query = $this->db->query($sql);
		$data['invoiceMaster']= $query->result();
		 
		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="select * from doctor_master where active=1";
		$query = $this->db->query($sql);
		$data['doclist']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age from patient_master where id='".$data['invoiceMaster'][0]->patient_id."' ";
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
	
		$this->load->view('Medical/Medical_Final_Invoice',$data);
	}
	
	public function add_item($type)
	{
		$this->load->model('Medical_M');
		
		$inser_id=1;
		
		$ssno=$this->input->post('l_ssno');
		
		if($type==1)
		{
			$inv_id=$this->input->post('inv_id');
			
			$qty=$this->input->post('qty');
			$disc=$this->input->post('disc');
			$product_unit_rate=$this->input->post('product_unit_rate');
		
			$sql="select * from purchase_invoice_item where id=".$ssno;
			$query = $this->db->query($sql);
			$data['itemlist']= $query->result();
			
			$sql="select * from inv_med_item where inv_med_id=".$inv_id."  and ss_no=".$ssno;
			$query = $this->db->query($sql);
			$itemExist= $query->result();
		
			$CGST=$data['itemlist'][0]->CGST_per;
			$SGST=$data['itemlist'][0]->SGST_per;
			
			//$item_rate=$data['itemlist'][0]->unit_rate;
			$item_rate=$product_unit_rate;

			$amount_value=$qty*$item_rate;
			
			$disc_amount=$amount_value*$disc/100;
			
			$Tamount_value=$amount_value-$disc_amount;

			$ss_no=$data['itemlist'][0]->id;
			$HSNCODE=$data['itemlist'][0]->HSNCODE;

		$data['insert_inv_med_item'] = array( 
			'inv_med_id' => $inv_id,
			'item_code' => $data['itemlist'][0]->item_code,
			'item_Name' => $data['itemlist'][0]->Item_name,				
			'formulation' => $data['itemlist'][0]->formulation,
			'qty' => $qty,
			'batch_no' => $data['itemlist'][0]->batch_no,
			'expiry' => $data['itemlist'][0]->expiry_date,
			'price' => $item_rate,
			'price2' => $item_rate,
			'mrp' => $data['itemlist'][0]->mrp,
			'disc_per' => $disc,
			'disc_amount' => $disc_amount,
			'amount' => $amount_value,
			'tamount' => $Tamount_value,
			'CGST_per'=> $CGST,
			'SGST_per'=> $SGST,
			'HSNCODE'=> $HSNCODE,
			'ss_no'=>$ss_no
			);

			if(count($itemExist)>0)
			{
				$inser_id=0;
			}else{
				$inser_id=$this->Medical_M->add_invoiceitem( $data['insert_inv_med_item']);
				$this->Medical_M->update_invoice_final($inv_id);
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
			$ssno=$data['inv_med_item'][0]->ss_no;
			$inv_id=$data['inv_med_item'][0]->inv_med_id;

			$item_rate=$data['inv_med_item'][0]->price;
			
			$amount_value=$update_qty*$item_rate;
			
			$disc_amount=$amount_value*$disc/100;
			
			$Tamount_value=$amount_value-$disc_amount;

			$data['update_inv_med_item'] = array( 
				'qty' => $update_qty,
				'disc_amount' => $disc_amount,
				'amount' => $amount_value,
				'tamount' => $Tamount_value
			);

			$this->Medical_M->update_invoiceitem( $data['update_inv_med_item'],$itemid);
			$inser_id=$itemid;
			
			$this->Medical_M->update_invoice_final($inv_id);

		}else{
			$inv_id=$this->input->post('inv_id');
			$itemid=$this->input->post('itemid');
			
			$sql="select * from inv_med_item where id=".$itemid;
			$query = $this->db->query($sql);
			$data['inv_med_item']= $query->result();
			
			$ssno=$data['inv_med_item'][0]->ss_no;			

			$this->Medical_M->delete_invoiceitem($itemid);
			
			$this->Medical_M->update_invoice_final($inv_id);
		}

		$content='';
		
		if($inser_id>0)
		{
			$this->Medical_M->update_invoice_final($inv_id);
			
			$sql="select * from inv_med_item  where inv_med_id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceDetails']= $query->result();
			
			$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,sum(disc_amount) as t_dis_amt from inv_med_item where inv_med_id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();
			
			/* $sql="select sum(qty) as total_qty from inv_med_item where ss_no=".$ssno;
			$query = $this->db->query($sql);
			$inv_med_item= $query->result();
			
			$update_item_qty = array( 
				'update_stock_qty' => $inv_med_item[0]->total_qty			
			);
			
			$this->Medical_M->update_stock_upd($update_item_qty,$ssno); */

			$content.='<table class="table table-striped ">
				<tr>
					<th style="width: 10px">#</th>
					<th>Item Name</th>	
					<th>Batch No</th>
					<th>Exp.</th>
					<th>Rate</th>
					<th>Saved Qty.</th>
					<th>Qty.</th>
					<th>Price</th>
					<th>Disc.</th>
					<th>Amount</th>
					<th></th>
				</tr>';

			$srno=0;

			foreach($data['invoiceDetails'] as $row)
			{ 
				$srno=$srno+1;
				$content.= '<tr>';
				$content.= '<td>'.$srno.'</td>';
				$content.= '<td>'.$row->item_Name.'['.$row->formulation.']</td>';
				$content.= '<td>'.$row->batch_no.'</td>';
				$content.= '<td>'.$row->expiry.'</td>';
				$content.= '<td>'.$row->price.'</td>';
				$content.= '<td>'.$row->qty.'</td>';
				$content.= '<td><input class="form-control" style="width:100px" name="input_qty_'.$row->id.'" id="input_qty_'.$row->id.'"  value="'.$row->qty.'" type="number" /></td>';
				$content.= '<td>'.$row->amount.'</td>';
				$content.= '<td>'.$row->disc_amount.'</td>';
				$content.= '<td>'.$row->tamount.'</td>';
				$content.= '<td>
				<button type="button" class="btn btn-primary" id="btn_update" onclick="update_qty('.$row->id.')"><i class="fa fa-edit"></i></button>
				<button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')"><i class="fa fa-remove"></i></button></td>';
				$content.= '</tr>';
			}	
			
			$content.= '<input type="hidden" id="srno" name="srno" value="'.$srno.'" />';
			
			$content.= '<tr>
					<th style="width: 10px">#</th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th></th>
					<th>Gross Total</th>
					<th>'.$data['invoiceGtotal'][0]->Gtotal.'</th>
					<th>'.$data['invoiceGtotal'][0]->t_dis_amt.'</th>
					<th>'.$data['invoiceGtotal'][0]->tamt.'</th>
					<th></th>
				</tr>
			</table>';
		}
		
		$rvar=array(
		'insertid' =>$inser_id,
		'content'=>$content,
		'error'=>'Already in Item List'
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function list_org_ipd()
	{
		$sql = "select *,(paid_amount-(charge_amount+med_amount)) as balance from v_ipd_list where ipd_status=0 ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		     
		$this->load->view('Medical/Medical_ipd_org_list',$data);
	}
	
	
	public function list_org()
	{
		$sql = "select p.p_fname,p.p_code,p.p_relative,p.p_rname,o.insurance_company_name,o.case_id_code,o.id as org_id,o.p_id ,
				o.date_registration,Date_Format(o.date_registration,'%d-%m-%Y') as str_register_date,o.insurance_id
				from organization_case_master o join patient_master p on o.p_id=p.id and o.insurance_id in (2,53) and o.`status`=0";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
     
		$this->load->view('Medical/Medical_org_list',$data);
	}
	
	
	
	public function list_med_inv($ipd_id)
	{
		$sql="select *,if(ipd_credit=0,'CASH','CREDIT') as credit,
		if(ipd_credit=1 and ipd_credit_type=0,'Yes','No')  as Package 
		from invoice_med_master where  ipd_id = ".$ipd_id;
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
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
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
		
		$sql="select *,if(case_credit=0,'CASH','CREDIT') as credit from invoice_med_master where  case_id = ".$org_id;
		$query = $this->db->query($sql);
		$data['inv_master']= $query->result();
		
		$sql="select * from organization_case_master where  id = ".$org_id;
		$query = $this->db->query($sql);
		$data['orgcase_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
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
	
	
	
	public function final_discount($ipd_id)
	{
		
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
	
	public function search()
	{
		$sdata=$this->input->post('txtsearch');
         
		$sdata = preg_replace('/[^A-Za-z0-9 _\-]/', '', $sdata);
		
        $sql = "SELECT *,GET_AGE_1(dob,age,age_in_month,estimate_dob)   AS age 
		FROM patient_master 
		WHERE p_code like '%".$sdata."' or p_fname like '%".$sdata."%' or
		mphone1 = '".$sdata."' or email1 = '".$sdata."' order by  id desc limit 20";

		$query = $this->db->query($sql);
        $data['data']= $query->result();

        $this->load->view('Medical/Patient_Search_Result',$data);
	}
	
	public function Update_Invoice_ipd_credit_type()
	{
		$ipd_credit_type= $this->input->post('ipd_credit_type');
		$inv_med_id = $this->input->post('inv_med_id');
		
		$dataupdate = array( 
				'ipd_credit_type' => $ipd_credit_type			
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
		$sql="select *,if(ipd_status=0,DATEDIFF(sysdate(),register_date),DATEDIFF(discharge_date,register_date)) as no_days,if(case_type=1,'MLC','Non MLC') as case_type_s, Date_format(register_date,'%d-%m-%Y') as str_register_date from ipd_master where id='".$ipd_id."' ";
        $query = $this->db->query($sql);
        $data['ipd_info']= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age from patient_master where id='".$data['ipd_info'][0]->p_id."' ";
        $query = $this->db->query($sql);
        $data['person_info']= $query->result();

		$sql="select * from inv_med_group where med_type=1 and ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
        $data['med_group_list']= $query->result();
		
		$sql="select p.*,if(p.credit_debit>0,p.amount*-1,p.amount) as paid_amount,
		Date_Format(payment_date,'%d-%m-%Y') as str_payment_date,
		(case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' when 5 then 'Cash Return' else 'Other' end) as Payment_type_str
		from payment_history_medical p join inv_med_group m
		on p.group_id=m.med_group_id
		where m.ipd_id=".$ipd_id." and m.med_type=1";
		$query = $this->db->query($sql);
		$data['payment_history']= $query->result();
		
		$sql="select * from inv_med_group where med_type=1 and ipd_id=".$ipd_id;
		$query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();
		
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
					'credit_debit'=>'0',
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
					'credit_debit'=>'0',
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
					'credit_debit'=>'0',
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
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		GET_AGE_1(dob,age,age_in_month,estimate_dob)  AS age 
		from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$this->load->view('Medical/Med_Payment_receipt',$data);
		
	}
	
}