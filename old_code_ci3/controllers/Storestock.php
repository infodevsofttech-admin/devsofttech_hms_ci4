<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Storestock extends MY_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Stock_M');
		$this->load->model('Master_M');

	}

	public function index()
    {

		$this->load->view('Storestock/Dashboard');
	}
	
	public function Indent_List()
	{
		$this->load->view('Storestock/Draft_indent');
	}

	//Product Master
	public function Product_search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="select *  from med_store_product_master	order by id desc limit 100";
		}else{
			$sql="select *  from med_store_product_master			
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
		$sql="Select * from med_store_product_master where id=".$Product_id;
		$query = $this->db->query($sql);
        $data['product_data']= $query->result();

		$sql="Select * from med_formulation order by formulation_length";
		$query = $this->db->query($sql);
        $data['med_formulation']= $query->result();

		$sql="Select * from med_company order by company_name";
		$query = $this->db->query($sql);
        $data['med_company']= $query->result();

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

		$this->load->view('Medical/Purchase/product_new',$data);
	}




	//End Product Master

	public function getIndentTable()
	{
		//Single Store Remove Store ID feild

		$requestData= $_REQUEST;

		$columns = array( 
		// datatable column index  => database column name
			0 => 'indent_code', 
			1 => 'issued_name',
			2=> 'indent_date_str',
			3=> 'id',
		);

		// getting total number records without any search
		$sql_f_all = "SELECT m.id,m.indent_code,m.issued_name,m.indent_date,DATE_FORMAT(m.indent_date,'%d-%m-%Y') as indent_date_str ";
	
		$sql_count="Select count(*) as no_rec ";

		$sql_from=" from invoice_stock_master m";

		//$total_sql=$sql_count.$sql_from;
		$total_sql="Select count(*) as no_rec from invoice_stock_master m";
		
		$query = $this->db->query($total_sql);
		$data['total_rec']= $query->result();
		$totalData=$data['total_rec'][0]->no_rec;

		$totalFiltered = $totalData;  // when there is no search parameter then total number rows = total number filtered rows.

		$sql_where=" WHERE 1=1 ";	
		
		$sql_where_flag=0;
		
		// getting records as per search parameters
		if( !empty($requestData['columns'][0]['search']['value']) ){   //indent_code
			$sql_where.=" AND indent_code LIKE '%".$requestData['columns'][0]['search']['value']."' ";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][1]['search']['value']) ){  //issued_name
			$stext=$requestData['columns'][1]['search']['value'];
			$sql_where.=" AND (issued_name LIKE '%".$stext."' )";
			$sql_where_flag=1;
		}

		if( !empty($requestData['columns'][2]['search']['value']) ){  //indent_date
			$stext=$requestData['columns'][2]['search']['value'];
			$sql_where.=" AND (indent_date = '".$stext."' )";
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

		//$totalFiltered=count($rdata);

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

	public function new_indent()
	{
		$sql="select * from location_master ";
		$query = $this->db->query($sql);
		$data['location_master']= $query->result();

		$sql="select * from employee_master ";
		$query = $this->db->query($sql);
		$data['employee_master']= $query->result();

		$this->load->view('Storestock/new_indent',$data);
	}

	public function Indent_create($location_type)
	{
		
		$date_indent=$this->input->post('date_indent');
		$loc_id=$this->input->post('loc_id');

		$issued_name="";

		if($location_type==1){
			$sql="select * from location_master where l_id=$loc_id ";
			$query = $this->db->query($sql);
			$location_master= $query->result();

			if(count($location_master)>0){
				$issued_name=$location_master[0]->loc_name;
			}

		}elseif($location_type==2){
			$sql="select * from employee_master where emp_id=$loc_id ";
			$query = $this->db->query($sql);
			$employee_master= $query->result();

			if(count($employee_master)>0){
				$issued_name=$employee_master[0]->emp_name.' ['.$employee_master[0]->emp_code.']';
			}

		}
		
		$data['invoice_stock_master'] = array( 
			'indent_date' => str_to_MysqlDate($date_indent),
			'location_type' => $location_type,
			'location_id' => $loc_id,
			'issued_name' => $issued_name,
		);

		$inser_id=$this->Stock_M->insert_indent($data['invoice_stock_master']);

		redirect('Storestock/Indent_show/'.$inser_id);

	}

	public function Indent_show($indent_id)
	{
		
		$sql="select * from invoice_stock_master where id=$indent_id ";
		$query = $this->db->query($sql);
		$data['invoice_stock_master']= $query->result();

		$sql="SELECT i.*,ifnull(DATEDIFF(expiry,CURDATE()),1000) AS no_day,
			SUM(p.total_unit-p.total_sale_unit-total_return_unit-total_lost_unit)/p.packing AS cur_qty
			FROM (inv_stock_item i JOIN purchase_invoice_item_stock p ON i.item_code=p.item_code AND p.remove_item=0 )
			where indent_id=".$indent_id." GROUP BY i.id";
			$query = $this->db->query($sql);
			$data['inv_stock_item']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,
			sum(SGST) as TSGST 
			from inv_stock_item where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		$data['content']=$this->load->view('Storestock/indent_item_list',$data,true);

		$this->load->view('Storestock/indent_edit',$data);

	}

	public function Indent_Items_list($indent_id)
	{
		$sql="select * from inv_stock_item where indent_id=$indent_id Order by id";
		$query = $this->db->query($sql);
		$data['inv_stock_item']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,
			sum(SGST) as TSGST 
			from inv_stock_item where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		
		$content=$this->load->view('Storestock/indent_item_list',$data,true);

		echo $content;
	}


	// Purchase Stock

	public function Purchase()
    {
		$this->load->view('Storestock/Purchase/supplier_list');
	}
	
	public function PurchaseMasterEdit($inv_id)
	{
		$sql="select * from stock_supplier order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$sql="select p.*,s.name_supplier from purchase_invoice_stock p join stock_supplier s on p.sid=s.sid where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_master_data']= $query->result();

		$sql="select * from purchase_invoice_item_stock where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
		$data['purchase_item']= $query->result();
	
		$this->load->view('Storestock/Purchase/purchase_invoice_master_edit',$data);

	}


	public function UpdatePurchase()
	{
		$rec_id=$this->input->post('hid_purchaseid');
		$sid=$this->input->post('input_supplier');
		$invoice_code=$this->input->post('input_invoicecode');
		$d_invoice=$this->input->post('datepicker_invoice');
		$ischallan=$this->input->post('cbo_billtype');
		
		$Udata = array( 
				'sid'=> $sid,
				'date_of_invoice'=> str_to_MysqlDate($d_invoice),
				'Invoice_no'=> $invoice_code,
				'ischallan'=>$ischallan
			);
		
		$update_id=$this->Stock_M->update_purchase($Udata,$rec_id);
		
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
		
		$update_id=$this->Stock_M->update_purchase($Udata,$purchase_inv_id);
		
		redirect('Storestock/PurchaseMasterEdit/'.$purchase_inv_id);
		
	}

	public function PurchaseNew()
    {
		$sql="select * from stock_supplier where active=1 order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$this->load->view('Storestock/Purchase/new_purchase_invoice',$data);
	}

	public function SupplierList()
    {
		$sql="select * from stock_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$this->load->view('Storestock/Purchase/supplier_master',$data);
	}

	public function PurchaseInvoice()
	{
		$sdata=trim($this->input->post('txtsearch'));
        
		//$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="select p.id,p.Invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,p.sid,s.name_supplier,
			s.short_name,s.gst_no,
			p.T_Net_Amount as tamount,p.ischallan
			from (purchase_invoice_stock p join stock_supplier s on p.sid=s.sid)
			
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
			p.T_Net_Amount as tamount,p.ischallan
			from (purchase_invoice_stock p join stock_supplier s on p.sid=s.sid)
			where (p.Invoice_no LIKE '%".$sdata."%'  ".$numric_condition." )
			group by p.id order by p.id desc limit 50";
		}

		$query = $this->db->query($sql);
        $data['purchase_list']= $query->result();
		
		$this->load->view('Storestock/Purchase/purchase_supp_list',$data);
	}

	public function purchase_invoice_item_list_old($item_code)
	{
		$sql="SELECT s.name_supplier, date_format(p.date_of_invoice,'%d-%m-%Y') as date_of_invoice_str,
		i.Item_name,i.packing,i.purchase_price,i.qty,i.qty_free,i.mrp
		FROM (purchase_invoice_stock p JOIN purchase_invoice_item_stock i ON p.id=i.purchase_id)
		JOIN med_supplier s ON p.sid=s.sid
		WHERE i.item_code=$item_code ORDER BY i.id DESC LIMIT 5 ";
        $query = $this->db->query($sql);
        $data['purchase_item_old']= $query->result();
		
		$this->load->view('Storestock/Purchase/purchase_item_old',$data);
		
	}
	
	public function print_purchase($inv_id)
	{
		$sql="select * from stock_supplier order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$sql="select p.*,s.name_supplier from purchase_invoice_stock p join stock_supplier s on p.sid=s.sid where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_master_data']= $query->result();

		$sql="select * from purchase_invoice_item_stock where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
		$data['purchase_item']= $query->result();

        $content = $this->load->view('Storestock/Purchase/purchase_invoice_item_print', $data, true);

        //load mPDF library
				
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = false;
        
        $file_name='Return_Invoice-'.$inv_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;
		
       	//echo $content;
		
		$this->m_pdf->pdf->debug = true;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output("TEST.pdf","I");
	}
	
	public function PurchaseInvoiceEdit($inv_id)
	{
		$sql="select p.id,p.Invoice_no,p.date_of_invoice,date_format(p.date_of_invoice,'%d/%m/%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no,p.inv_status,p.ischallan
			from purchase_invoice_stock p join stock_supplier s on p.sid=s.sid
			where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice']= $query->result();	

		$sql="select * from purchase_invoice_item_stock where purchase_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['purchase_invoice_item']= $query->result();

		$sql="select * from med_gst_per ";
        $query = $this->db->query($sql);
        $data['med_gst_per']= $query->result();

		$this->load->view('Storestock/Purchase/purchase_invoice_edit',$data);
	}

	public function purchase_update_stock($inv_id,$return_stock=0)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql="select * from purchase_invoice_stock where id=".$inv_id;
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
				
				$send_msg="";
				
				if($item_id<1)
				{
					$is_update_stock=$this->Stock_M->insert_purchase_item($Pdata);
					$send_msg="Added";
				}else{
					$sql="select * from purchase_invoice_item_stock where id='$item_id' ";
					$query = $this->db->query($sql);
					$drug_item= $query->result();

					if(count($drug_item)>0){
						$tot_sale_qty=$drug_item[0]->total_sale_unit;

						$qty = $this->input->post('input_Qty');
						$qty_free= $this->input->post('input_Qty_Free');

						$packing=$this->input->post('input_package');

						$tot_qty=($qty+$qty_free)*$packing;

						if($tot_sale_qty<=$tot_qty)
						{
							$is_update_stock=$this->Stock_M->update_purchase_item($Pdata,$item_id);
							if($is_update_stock>0)
							{
								$send_msg="Updated";
							}else{
								$send_msg="Error : Update in Stock Table";
							}
						}else{
							$send_msg="Error : Update Qty is Less then saled Qty :".$tot_qty." / Saled :".$tot_sale_qty;
						
							//Update While Qty in Minus

							$is_update_stock=$this->Stock_M->update_purchase_item($Pdata,$item_id);
							if($is_update_stock>0)
							{
								$send_msg="Updated : But Error : Update Qty is Less then saled Qty :".$tot_qty." / Saled :".$tot_sale_qty;
							}else{
								$send_msg="Error : Update in Stock Table";
							}
						
						}
						
					}else{
						$send_msg="Error : Record Not Found in Stock Table";
					}
				}
								
                $rvar=array(
					'is_update_stock' =>$is_update_stock,
					'product_code' => $item_code,
					'show_text'=>$send_msg
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

	public function purchase_invoice_item_list($inv_id)
	{
		$sql="select *,date_format(expiry_date,'%m/%y') as exp_date_str from purchase_invoice_item_stock where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_item']= $query->result();
		
		$sql="select * from purchase_invoice_stock where id='".$inv_id."' ";
        $query = $this->db->query($sql);
        $data['purchase_invoice']= $query->result();
		
		
		$this->load->view('Storestock/Purchase/purchase_invoice_item',$data);
		
	}
	
	function purchase_invoice_item_edit($inv_id,$inv_item_id) 
	{
		$sql="select *,
		date_format(expiry_date,'%m') as str_expiry_month ,
		date_format(expiry_date,'%y') as str_expiry_year 
		from purchase_invoice_item_stock where id=".$inv_item_id." and purchase_id=".$inv_id;
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

					'sch_disc_amt'=>$purchase_invoice_item[0]->sch_disc_amt,
					'sch_disc_per'=>$purchase_invoice_item[0]->sch_disc_per,

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

	public function add_item($type,$req=0)
	{
				
		$inser_id=1;
		$Error_Show='';
		$exist=0;
		$input_id=0;
		
		$store_stock_id=$this->input->post('l_ssno');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');
		
		if($type==1)
		{
			$inv_id=$this->input->post('inv_id');
			$qty=$this->input->post('qty');
			$disc=$this->input->post('disc');
			$product_unit_rate=$this->input->post('product_unit_rate');
			
			$sql="SELECT  p.item_name as item_name,t.batch_no,t.expiry_date,
					(t.total_unit-t.total_sale_unit) AS S_qty,
					t.mrp,t.selling_unit_rate,p.id AS master_product_id,
					t.id as purchase_item_id,p.formulation,
					t.HSNCODE,t.CGST_per,t.SGST_per,t.packing
					FROM med_store_product_master p 
					JOIN purchase_invoice_item_stock t ON p.id=t.item_code
					where t.id= ".$store_stock_id;
			$query = $this->db->query($sql);
			$data['itemlist']= $query->result();

			$sql="select * from inv_stock_item where indent_id=".$inv_id."  
			and store_stock_id=".$store_stock_id;
			$query = $this->db->query($sql);
			$itemExist= $query->result();
		
			$CGST=$data['itemlist'][0]->CGST_per;
			$SGST=$data['itemlist'][0]->SGST_per;
			
			//$item_rate=$data['itemlist'][0]->unit_rate;
			$item_rate=$product_unit_rate;

			$amount_value=$qty*$item_rate;
			
			$disc_amount=$amount_value*$disc/100;
			
			$Tamount_value=$amount_value-$disc_amount;

			$HSNCODE=$data['itemlist'][0]->HSNCODE;
		
			$stock_qty=$data['itemlist'][0]->S_qty;
			
			if($qty>$stock_qty)
			{
				$inser_id=0;
				$Error_Show='Stock Qty. is less than Required Qty : Current Qty :'.$stock_qty;
			}elseif($stock_qty==0){
				$stock_qty=0;
				$inser_id=0;
				$Error_Show='Stock is Empty';
			}
		
			if($inser_id>0)
			{
				$data['insert_inv_med_item'] = array( 
				'indent_id' => $inv_id,
				'item_code' => $data['itemlist'][0]->master_product_id,
				'item_Name' => $data['itemlist'][0]->item_name,				
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
				'store_stock_id'=>$data['itemlist'][0]->purchase_item_id,
				'update_by_id'=>$user_id,
				'update_by_remark'=>$user_name_info,
				'packing'=>$data['itemlist'][0]->packing,
				);

				if(count($itemExist)>0)
				{
					$Error_Show='Please Check Also Same Item List';
					$exist=count($itemExist);
					$input_id=$itemExist[0]->id;
					$input_id=0;
				}

				$inser_id=$this->Stock_M->add_indentitem( $data['insert_inv_med_item']);
			}
		}
		else if($type==2)
		{
			$update_qty=$this->input->post('u_qty');
			$itemid=$this->input->post('itemid');

			$sql="select * from inv_stock_item where id=".$itemid;
			$query = $this->db->query($sql);
			$data['inv_med_item']= $query->result();
			
			$CGST=$data['inv_med_item'][0]->CGST_per;
			$SGST=$data['inv_med_item'][0]->SGST_per;
			$disc=$data['inv_med_item'][0]->disc_per;
			
			$inv_id=$data['inv_med_item'][0]->indent_id;

			$store_stock_id=$data['inv_med_item'][0]->store_stock_id;
			
			$sql="SELECT p.item_name,t.batch_no,t.expiry_date,
			(t.total_unit-t.total_sale_unit) AS S_qty,
			t.mrp,t.selling_unit_rate,p.id AS master_product_id,
			t.id as purchase_item_id,p.formulation,
			t.HSNCODE,t.CGST_per,t.SGST_per,t.packing
			FROM med_store_product_master p 
			JOIN purchase_invoice_item_stock t ON p.id=t.item_code
			where t.id= ".$store_stock_id;
	
			$query = $this->db->query($sql);
			$item_master= $query->result();
			
			$stock_qty=0;
			
			$old_qty=$data['inv_med_item'][0]->qty;
			$diff_qty=$update_qty-$old_qty;
		
			if($diff_qty>0)
			{
				if(count($item_master)>0)
				{
					$stock_qty=$item_master[0]->S_qty;
					if($diff_qty>$stock_qty)
					{
						$inser_id=0;
						$Error_Show='Stock Qty. is less than Required Qty : Current Qty :'.$stock_qty;
					}
				}else{
					$stock_qty=0;
					$inser_id=0;
					$Error_Show='Stock is Empty';
				}
			}
			
			if($inser_id>0)
			{
				$item_rate=$data['inv_med_item'][0]->price;
				$amount_value=$update_qty*$item_rate;
				
				$disc_amount=$amount_value*$disc/100;
				
				$Tamount_value=$amount_value-$disc_amount;

				$data['update_inv_med_item'] = array( 
					'qty' => $update_qty,
					'disc_amount' => $disc_amount,
					'amount' => $amount_value,
					'tamount' => $Tamount_value,
					'store_stock_id'=>$store_stock_id,
					'packing'=>$data['inv_med_item'][0]->packing,
					'indent_id' => $inv_id,
				);

				$this->Stock_M->update_indentitem($data['update_inv_med_item'],$itemid);
				$inser_id=$itemid;
			
			}
			
			//$this->Medical_M->update_invoice_group($inv_id);
			//$this->Medical_M->update_invoice_final($inv_id);
				
		}else{
			$itemid=$this->input->post('itemid');

			$sql="select * from inv_stock_item  where id=".$itemid;
			$query = $this->db->query($sql);
			$inv_items= $query->row_array();

			$inv_id=$inv_items['indent_id'];
			
			$this->Stock_M->delete_indentitem($itemid,$inv_items);
		
		}

		$content='';
		
		if($inser_id>0)
		{
			//$this->Medical_M->update_invoice_final($inv_id);
			
			$sql="SELECT i.*,ifnull(DATEDIFF(expiry,CURDATE()),1000) AS no_day,
			SUM(p.total_unit-p.total_sale_unit-total_return_unit-total_lost_unit)/p.packing AS cur_qty
			FROM (inv_stock_item i JOIN purchase_invoice_item_stock p ON i.item_code=p.item_code AND p.remove_item=0 )
			where indent_id=".$inv_id." GROUP BY i.id";
			$query = $this->db->query($sql);
			$data['inv_stock_item']= $query->result();

			$sql="select item_code
			from inv_stock_item   where indent_id=".$inv_id." Group by item_code Having count(id)>1";
			$query = $this->db->query($sql);
			$data['inv_items_multiple']= $query->result();
			
			$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,
				sum(disc_amount) as t_dis_amt from inv_stock_item where indent_id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();

			$sql="select * from  invoice_stock_master where id=".$inv_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();

			$user = $this->ion_auth->user()->row();
			$data['user_id'] = $user->id;

			$data['sale_return']=$data['invoiceMaster'][0]->sale_return;
			
			$content=$this->load->view('Storestock/indent_item_list',$data,true);
			//$content='';
		} 
			
		$rvar=array(
		'exist' =>$exist,
		'insertid' =>$inser_id,
		'content'=>$content,
		'error'=>$Error_Show,
		'input_id'=>$input_id,
		'csrf_dst_name_value'=>$this->security->get_csrf_hash()
		);
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}

	public function purchase_invoice_item_delete($inv_id,$inv_item_del)
	{

		$sql="select * from purchase_invoice_item_stock where id='$inv_item_del' ";
        $query = $this->db->query($sql);
        $drug_item= $query->result();
		
		if(count($drug_item)>0){
			$sale_qty=$drug_item[0]->total_sale_unit;

			if($sale_qty>0)
			{
				$rvar=array(
					'is_update_stock' =>0,
					'show_text'=>'Sold some Quantity of this Item',
				);

			}else{
				$is_update_stock=$this->Stock_M->delete_purchase_item($inv_id,$inv_item_del);
				$rvar=array(
					'is_update_stock' =>1,
					'show_text'=>'Removed Successfully',
				);
			}
		}

		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	

	public function final_invoice($inv_id)
	{
		
		$this->Stock_M->update_invoice_final($inv_id);
		
		$sql="select *,(discount_amount+item_discount_amount) as inv_disc_total,
		(CGST_Tamount+SGST_Tamount) as TGST from invoice_stock_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoice_stock_master']= $query->result();

		$sql="select * from inv_stock_item where indent_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();

		$sql="select sum(amount) as Gtotal,sum(tamount) as tamt,sum(CGST) as TCGST,sum(SGST) as TSGST,
				sum(disc_amount) as t_dis_amt from inv_stock_item where indent_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

						
		$this->load->view('Storestock/Store_Final_Invoice',$data);
	}


	
	public function get_drug(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="SELECT p.item_name as item_name,p.formulation,t.batch_no,t.expiry_date as expiry_date_str,t.expiry_date,
				(t.total_unit-t.total_sale_unit-total_lost_unit-t.total_return_unit) AS c_qty,
				t.id,t.mrp,t.selling_unit_rate,p.id AS item_code,t.packing
				FROM med_store_product_master p 
				JOIN purchase_invoice_item_stock t ON p.id=t.item_code 
				and (t.total_unit-t.total_sale_unit-t.total_lost_unit-t.total_return_unit)>0
				and (expiry_date>date_add(sysdate(),interval 1 day) or p.exp_date_applicable=0)
				and t.remove_item=0 and t.item_return=0
				where	(p.item_name like '".$q."%' ";

			if(strlen($q)>5)
			{
				$sql=$sql. " or p.item_name SOUNDS LIKE '".$q."'";
			}

			$sql=$sql.") order by p.item_name,id  limit 100";
		
			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['item_name'].' '.$row['formulation'])).' |B:'.htmlentities(stripslashes($row['batch_no'])). ' |Pak:'.htmlentities(stripslashes($row['packing'])).' |Rs.'.htmlentities(stripslashes($row['mrp'])).' |Qty:'.htmlentities(stripslashes($row['c_qty']));
				$new_row['value']=htmlentities(stripslashes($row['item_name']));
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_ss_no']=htmlentities(stripslashes($row['id']));
				$new_row['l_Batch']=htmlentities(stripslashes($row['batch_no']));
				$new_row['l_Expiry']=htmlentities(stripslashes($row['expiry_date_str']));
				$new_row['l_mrp']=htmlentities(stripslashes($row['mrp']));
				$new_row['l_unit_rate']=htmlentities(stripslashes($row['selling_unit_rate']));
				$new_row['l_c_qty']=htmlentities(stripslashes($row['c_qty']));
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				//$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}else{
				$new_row['sql']=$sql;
				$row_set[] = $new_row;
				echo json_encode($row_set);
			}
		}
	}
	
	public function get_drug_master(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);
	
			$sql="SELECT m.* ,p.id as ss_no,m.packing as p_packing,p.mrp,p.purchase_price,p.discount, 
						p.batch_no,date_format(expiry_date,'%m') as str_expiry_month ,
						date_format(expiry_date,'%y') as str_expiry_year ,
						m.batch_applicable,m.exp_date_applicable, 
						if(p.id is null,m.CGST_per,p.CGST_per) as p_CGST_per, 
						if(p.id is null,m.SGST_per,p.SGST_per) as p_SGST_per, 
						if(p.id is null,m.rack_no,p.rack_no) as p_rack_no, 
						if(p.id is null,m.shelf_no,p.shelf_no) as p_shelf_no, 
						if(p.id is null,m.cold_storage,p.cold_storage) as p_cold_storage 

					FROM med_store_product_master m  
					LEFT JOIN (SELECT * from purchase_invoice_item_stock 
					WHERE id in (select max(id) from purchase_invoice_item_stock
					WHERE Item_name LIKE '".$q."%'
					group BY item_code ) ) as p ON m.id=p.item_code
					WHERE m.is_continue=1 and m.Item_name LIKE '".$q."%'  
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
	
	public function challan_invoice($supplier_id)
	{
		$sql="SELECT  p.id,t.id AS ss_no,p.Invoice_no,p.date_of_invoice,
		date_format(p.date_of_invoice,'%d-%m-%Y') as str_date_of_invoice,p.sid,s.name_supplier,
		s.short_name,s.gst_no,
		p.T_Net_Amount as tamount,p.ischallan,
		t.Item_name,t.qty,t.qty_free,t.mrp
		FROM (purchase_invoice_stock p join stock_supplier s on p.sid=s.sid)
		JOIN purchase_invoice_item_stock t ON p.id=t.purchase_id
		where p.sid=$supplier_id and p.ischallan=1
		group by p.id,t.id ";

		$query = $this->db->query($sql);
		$data['purchase_challan_item']= $query->result();
	
		$this->load->view('Storestock/Purchase/purchase_challan_list',$data);
	}

	public function challan_item_to_purchase($ss_no,$purchase_id)
	{
		$sql="select * from purchase_invoice_item_stock  where id=$ss_no";
		$query = $this->db->query($sql);
		$purchase_invoice_item= $query->result();

		if(count($purchase_invoice_item)>0)
		{
			$old_purchase_id=$purchase_invoice_item[0]->purchase_id;

			$Pdata = array(
				'purchase_id' => $purchase_id,
				'old_purchase_id' => $old_purchase_id,
			); 
	
			$is_update_stock=$this->Stock_M->update_purchase_item($Pdata,$ss_no);
			//$this->db->query("CALL p_purchase_invoice(".$old_purchase_id.")");

			$rvar=array(
				'is_transfer' =>$is_update_stock,
				'show_text'=>"Item Addedd Successfully",
			);

		}else{
			$rvar=array(
				'is_transfer' =>0,
				'show_text'=>"Item Not Found ",
			);
		}

		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	public function challan_item_return($ss_no)
	{
		$sql="select * from purchase_invoice_item_stock  where old_purchase_id>0 and id=$ss_no";
		$query = $this->db->query($sql);
		$purchase_invoice_item= $query->result();

		if(count($purchase_invoice_item)>0)
		{
			$old_challan_id=$purchase_invoice_item[0]->old_purchase_id;
			$current_pur_id=$purchase_invoice_item[0]->purchase_id;

			$sql="select * from purchase_invoice_stock  where id=$old_challan_id";
			$query = $this->db->query($sql);
			$challan_invoice= $query->result();

			if(count($challan_invoice)>0)
			{
				$Pdata = array(
					'purchase_id' => $old_challan_id,
					'old_purchase_id' => 0,
				); 
		
				
				$is_update_stock=$this->Stock_M->update_purchase_item($Pdata,$ss_no);
				//$this->db->query("CALL p_purchase_invoice(".$current_pur_id.")");

				$rvar=array(
					'is_transfer' =>$is_update_stock,
					'show_text'=>"Item Addedd Successfully",
				);
			}else{

				$Pdata = array(
					'purchase_id' => $old_challan_id,
					'old_purchase_id' => 0,
				); 
		

				$is_update_stock=$this->Stock_M->update_purchase_item($Pdata,$ss_no);
				

				$rvar=array(
					'is_transfer' =>0,
					'show_text'=>"Challan Not Found ",
				);
			}

			
		}else{
			$rvar=array(
				'is_transfer' =>0,
				'show_text'=>"Item Not Found ",
			);
		}

		$encode_data = json_encode($rvar);
		echo $encode_data;

	}


	public function purchase_invoice_delete($inv_id)
	{
		
		$sql="select * from purchase_invoice_item_stock  where purchase_id=$inv_id";
		$query = $this->db->query($sql);
		$purchase_invoice_item= $query->result();

		if (count($purchase_invoice_item)>0){
			$rvar=array(
				'is_delete' =>0,
				'show_text'=>"Invoice has Items, Empty the item list",
			);
		}else{
			
			$is_delete=$this->Stock_M->delete_purchase($inv_id);
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
		$sql="select * from stock_supplier  order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$this->load->view('Storestock/Purchase/supplier_master_sub',$data);
	}

	public function SupplierEdit($sid=0)
    {
		$sql="select * from stock_supplier where sid=".$sid;
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$sql="select * from india_state ";
		$query = $this->db->query($sql);
		$data['india_state']= $query->result();

		$this->load->view('Storestock/Purchase/supplier_Edit',$data);
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
			$ischallan=$this->input->post('cbo_billtype');

			$where=" where sid=$sid";

			if(is_numeric($invoice_code))
				$where.=" and Invoice_no=".$invoice_code ;
			else
				$where.=" and Invoice_no='".$invoice_code."'";

			$sql="select count(*) as no_rec from purchase_invoice ".$where;
			$query = $this->db->query($sql);
			$chk_rec= $query->result();

			if($chk_rec[0]->no_rec==0)
			{
				$Udata = array( 
                    'sid'=> $sid,
					'date_of_invoice'=> str_to_MysqlDate($d_invoice),
					'Invoice_no'=> $invoice_code,
					'ischallan'=>$ischallan
				);
			
				$inser_id=$this->Stock_M->insert_purchase($Udata);
				$send_msg="Added Successfully";

				if($inser_id>0)
				{
					$show_text=$send_msg;
				}else{
					$show_text=$send_msg;
				}
			}else{
				$show_text='Record Already Exist';
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
			'show_text'=>$send_msg
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
                    'rules' => 'required|min_length[3]|max_length[100]'
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
			
						
			if($sid>0)
			{
				$inser_id=$this->Stock_M->update_supplier($Udata,$sid);
				$send_msg="Saved Successfully";
			}else{
				$inser_id=$this->Stock_M->insert_supplier($Udata);
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
		$this->load->view('Storestock/Medical_stock_drug');
	}

	// Store Main
	public function main_store()
    {
		$this->load->view('Storestock/Stock/main_store_dashboard');
	}


	// Location Master
	public function location_master_list()
    {
		$sql="select * from location_master order by loc_name ";
		$query = $this->db->query($sql);
		$data['location_master']= $query->result();

		$this->load->view('Storestock/Stock/location_master_list',$data);
	}

	function Location_add()
    {
        $this->load->view('MasterData/location_master_add');
    }

	function Location_edit($l_id)
    {
        $sql="select * from location_master where l_id= $l_id";
		$query = $this->db->query($sql);
		$data['location_master']= $query->result();

		$this->load->view('MasterData/location_master_edit',$data);
    }

	function Location_save()
    {
        $input_loc_name = $this->input->post('input_loc_name');
        $input_loc_desc = $this->input->post('input_loc_desc');
        $hid_l_id = $this->input->post('hid_location_id');

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' ['.$user_id.']';

        $insert_data=array(
            'loc_name'=>$input_loc_name,
            'loc_desc'=>$input_loc_desc,
            'loc_add_by'=>$user_name.' : '.date('Y-m-d H:i:s'),
        );

        if($hid_l_id==0){
            $inser_id=$this->Master_M->insert_location_master($insert_data); 
        }else{
            $inser_id=$this->Master_M->update_location_master($insert_data,$hid_l_id); 
        }
        
        echo $inser_id;
    }

	
	
	// Print Indent

	public function print_single_indent($inv_id,$print_format=0)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        
		$this->Stock_M->update_invoice_final($inv_id);
           
       	$sql="select i.indent_id,i.id,i.item_Name,i.formulation,
           i.batch_no,Date_Format(i.expiry,'%m-%y')as expiry,
           i.price,i.qty,(i.amount) as amount,i.HSNCODE,
           m.indent_code,m.id as m_id,
           date_format(m.indent_date,'%d-%m-%Y') as str_inv_date,
           (twdisc_amount) as twdisc_amount,
           (i.disc_amount+i.disc_whole) as d_amt,
           (i.CGST+i.SGST) as gst,
           (i.CGST_per+i.SGST_per) as gst_per ,i.sale_return
           from inv_stock_item i join invoice_stock_master m
           on i.indent_id=m.id
           where  m.id=$inv_id order by i.sale_return,id";
       $query = $this->db->query($sql);
       $data['inv_items']= $query->result();
       
       $sql="select *,
       date_format(indent_date,'%d-%m-%Y') as str_inv_date,
       (discount_amount+item_discount_amount) as inv_disc_total,
       (CGST_Tamount+SGST_Tamount) as TGST 
       from invoice_stock_master 
       where  id=$inv_id";
       $query = $this->db->query($sql);
       $data['invoice_stock_master']= $query->result();
     
                    
       //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = false;

        $file_name='Report-MedicalBill_'.$inv_id.'_'.date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);

        $content=$this->load->view('Storestock/Store_Print',$data,TRUE);
        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
        
    }

	// Store Stock

	public function store_stock()
	{
		$sql="select * from stock_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();

		$sql = "select * from med_formulation order by formulation_length";
		$query = $this->db->query($sql);
		$data['med_formulation'] = $query->result();

		$this->load->view('Storestock/store_stock_search', $data);
	}

	public function store_Stock_result()
	{
		$supplier_id = $this->input->post('input_supplier');
		$item_name = $this->input->post('txtsearch');
		$store_id = $this->input->post('input_store_id');
		$chk_reorder = $this->input->post('chk_reorder');

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}

		$where = " remove_item=0 ";

		if ($supplier_id > 0) {
			$where .= " and m.sid = " . $supplier_id;
		}


		if ($item_name <> '') {
			$where .= " and p.Item_name like '%" . $item_name . "%' ";
		}

		$sql = "SELECT i.Item_name,i.item_code ,sum(distinct i.tqty), 
				pm.re_order_qty,
				sum(i.total_unit) as T_unit,
				sum(if(ss.sale_qty is NULL,0,ss.sale_qty)) sal_qty,
				GROUP_CONCAT(distinct s.name_supplier) AS supplier_names
				FROM (((purchase_invoice_item_stock i join purchase_invoice_stock p on i.purchase_id=p.id ) 
				join stock_supplier s on s.sid=p.sid)
				JOIN med_store_product_master pm ON i.item_code=pm.id AND pm.is_continue=1)
				LEFT JOIN  
				(SELECT item_code,sum(qty) AS sale_qty FROM  inv_stock_item GROUP BY item_code ) 
					AS ss ON  i.item_code= ss.item_code
				Where " . $where . " group by i.item_code  " . $HAVING . " order by i.Item_name";


		$sql = "SELECT p.id, p.item_name,
		IFNULL(s.item_code,0) AS item_found,
		ifnull(s.packing,p.packing) as packing,
		SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit) as C_Unit_Stock_Qty,
		Concat(TRUNCATE(SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/s.packing,0),
		':',
		MOD(SUM(s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit),s.packing)) as C_Pak_Qty,
		sum(s.total_unit) as P_Unit_Qty,
		sum(s.total_unit)/s.packing as Pak_qty,
		sum(s.total_sale_unit) as sale_unit,
		sum(s.total_sale_unit)/s.packing as C_Pak_Sale_Qty,
		p.re_order_qty,
		s.total_lost_unit		
		FROM (med_store_product_master p 
		JOIN purchase_invoice_item_stock s ON p.id=s.item_code  AND s.remove_item=0 AND s.item_return=0)
		JOIN purchase_invoice_stock m ON s.purchase_id=m.id and p.is_continue=1
		WHERE " . $where . " group by p.id,s.packing " . $HAVING . " order by p.Item_name ";
		
		$query = $this->db->query($sql);
		$data['stock_list'] = $query->result();

		//echo $sql;

		$this->load->view('Storestock/Stock_search_qty_result', $data);
	}


	public function get_product_stock($product_id, $remove_item = 0)
	{
		$supplier_id = $this->input->post('input_supplier');
		$date_range = $this->input->post('date_range');

		$product_pak = $this->input->post('product_pak');

		$where = "1=1 ";

		if ($date_range <> '0') {
			$rangeArray = explode("S", $date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and p.date_of_invoice between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		$where .= " and i.item_code=$product_id and i.packing=$product_pak ";

		$sql = "SELECT i.id,s.name_supplier,s.short_name,i.Item_name,
		p.Invoice_no,p.date_of_invoice,i.mrp,i.packing,
		i.batch_no,i.expiry_date,i.purchase_price,i.purchase_unit_rate,
		i.qty,i.qty_free,i.tqty,i.total_unit,i.total_sale_unit,
		i.total_return_unit,i.total_lost_unit,
		(i.total_unit-i.total_sale_unit-i.total_lost_unit-i.total_return_unit) as cur_unit
		FROM (purchase_invoice_stock p JOIN purchase_invoice_item_stock i ON p.id=i.purchase_id and i.item_return=0)
		JOIN med_supplier s ON p.sid=s.sid
		WHERE $where and remove_item=$remove_item
		Order by p.id";

		$query = $this->db->query($sql);
		$data['product_purchase_detail'] = $query->result();

		$this->load->view('Storestock/Stock_Item_history', $data);
	}

	public function Stock_result_excel($chk_reorder, $item_name = "", $schedule_id = '')
	{
		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING (SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit)/p.packing,0)))<=m.re_order_qty";
		}

		$where = " 1=1  ";

		if ($item_name <> '-') {
			$where .= " and m.item_name like '%" . urldecode($item_name) . "%' ";
		}

		if ($schedule_id == '0') {
		} else {

			$schedule_id = explode("S", $schedule_id);
			if (count($schedule_id) > 0) {
				$where .= " ( ";
			}
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or m.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or m.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or m.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or m.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or m.narcotic=1 ";
				}
			}
			if (count($schedule_id) > 0) {
				$where .= " ) ";
			}
		}



		$where .= " and i.date_of_invoice>date_add(curdate(),interval -24 month) ";

		$sql = "SELECT m.item_name, m.id,m.genericname,
			SUM(p.tqty) AS Pak_qty,
			SUM(p.total_unit) AS P_Unit_Qty,
			m.packing,
			SUM(TRUNCATE((p.total_unit-p.total_sale_unit)/p.packing,0)) AS C_Pak_Qty,
			SUM(p.total_sale_unit) AS sale_unit,
			SUM(TRUNCATE((p.total_sale_unit)/p.packing,0)) AS C_Pak_Sale_Qty,  
			m.re_order_qty, 
			SUM(p.total_unit-p.total_sale_unit) AS C_Unit_Stock_Qty,
			SUM(TRUNCATE((p.total_unit-p.total_sale_unit-p.total_lost_unit-p.total_return_unit),0))*MAX(p.purchase_unit_rate) AS Stock_Cost,
			p.purchase_unit_rate 
			FROM (med_store_product_master m JOIN purchase_invoice_item_stock p 
			ON m.id=p.item_code and m.is_continue=1 and p.item_return=0 AND p.remove_item=0)
			JOIN purchase_invoice_stock i ON p.purchase_id=i.id
			WHERE " . $where . " group by m.id " . $HAVING . " order by m.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();



		$content = "";
		//$content.=$sql;
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th >Generic Name</th>
							<th>Current Pak.</th>
							<th>Current Unit Qty</th>
							<th>Total Sale Pak.</th>
							<th>Total Sale Unit Qty</th>
							<th>Package/Re-Order Qty</th>
							<th>Purchase Unit Rate</th>
							<th>Stock Cost</th>
						</tr>
					</thead>
					<tbody>';

		$total_stock_value = 0;

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="background-color: #999999;"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->genericname . '</td>
						<td valign="top">' . $row->C_Pak_Qty . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->C_Pak_Sale_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->purchase_unit_rate . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						</tr>';
			$total_stock_value += $row->Stock_Cost;
		}

		$content .= '<tr >
						<th colspan="7">Total Stock Cost</th>
						<th valign="top">' . $total_stock_value . '</th>
					</tr>';

		$content .= '</tbody></table>';

		ExportExcel($content, 'Report_Medical_store_stock');
	}

	public function Stock_result_excel_2($supplier_id, $opd_date_range, $item_name, $chk_reorder, $schedule_id = '')
	{

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}

		$where = " 1=1  ";

		if ($item_name <> '-') {
			$where .= " and p.item_name like '%" . $item_name . "%' ";
		}

		if (!($opd_date_range == '' || $opd_date_range <> '0')) {
			$rangeArray = explode("S", $opd_date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		if ($schedule_id == '') {
		} else {

			$where .= " and (1<>1 ";
			$schedule_id = explode("S", $schedule_id);
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}
			}
			$where .= ")";
		}


		$sql = "SELECT p.item_name, p.id,p.genericname,
				SUM(s.tqty) AS Pak_qty,
				SUM(s.total_unit) AS P_Unit_Qty,
				s.packing,
				SUM(TRUNCATE((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/s.packing,0)) AS C_Pak_Qty,
				SUM(s.total_sale_unit) AS sale_unit,
				SUM(TRUNCATE((s.total_sale_unit)/s.packing,0)) AS C_Pak_Sale_Qty,  
				p.re_order_qty, 
				SUM(s.total_unit-s.total_sale_unit) AS C_Unit_Stock_Qty,
				SUM(TRUNCATE((s.total_unit-s.total_sale_unit),0))*MAX(s.purchase_unit_rate) AS Stock_Cost 		
				FROM (med_store_product_master p JOIN purchase_invoice_item_stock s ON p.id=s.item_code and s.item_return=0)
 				JOIN purchase_invoice_stock m ON s.purchase_id=m.id 
			WHERE " . $where . " group by p.id,s.packing " . $HAVING . " order by p.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();


		$content = "";
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th>Total Unit Qty</th>
							<th>Current Unit Qty</th>
							<th>Total Sale Unit Qty</th>
							<th>Package/Re-Order Qty</th>
							<th>Stock Cost</th>
						</tr>
					</thead>
					<tbody>';

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="background-color: #999999;"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->P_Unit_Qty . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->packing . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						</tr>';
		}

		$content .= '</tbody></table>';

		//echo $content;
		ExportExcel($content, 'Report_Medical_store_stock');
	}

	public function Stock_result_excel_3($supplier_id, $opd_date_range, $item_name, $chk_reorder, $schedule_id = "")
	{

		$HAVING = "";

		if ($chk_reorder == "on") {
			$HAVING = " HAVING SUM(s.total_unit-s.total_lost_unit-s.total_sale_unit-s.total_return_unit)<=p.re_order_qty";
		}

		$where = " 1=1  ";

		if ($item_name <> '-') {
			$where .= " and p.item_name like '%" . $item_name . "%' ";
		}

		if (!($opd_date_range == '' || $opd_date_range == '0')) {
			$rangeArray = explode("S", $opd_date_range);
			$minRange = $rangeArray[0];
			$maxRange = $rangeArray[1];

			$where .= " and s.stock_date between  '" . $minRange . "' and '" . $maxRange . "'";
		}

		if ($supplier_id > 0) {
			$where .= " and m.sid=$supplier_id";
		}

		if ($schedule_id == '') {
		} else {

			$where .= " and (1<>1 ";
			$schedule_id = explode("S", $schedule_id);
			foreach ($schedule_id as $row) {
				if ($row == 1) {
					$where .= " or p.schedule_h=1 ";
				}

				if ($row == 2) {
					$where .= " or p.schedule_h1=1 ";
				}

				if ($row == 3) {
					$where .= " or p.schedule_x=1 ";
				}

				if ($row == 4) {
					$where .= " or p.schedule_g=1 ";
				}

				if ($row == 5) {
					$where .= " or p.narcotic=1 ";
				}
			}
			$where .= ")";
		}

		$sql = "SELECT p.item_name, p.id,p.genericname,
				SUM(s.tqty) AS Pak_qty,
				SUM(s.total_unit) AS P_Unit_Qty,
				s.packing,s.batch_no,date_format(s.expiry_date,'%m-%Y') as str_exp,
				s.mrp,
				TRIM(',' FROM Concat(if(p.schedule_h=1,'schedule_h,',''),
						if(p.schedule_h1=1,'schedule_h1,',''),
						if(p.narcotic=1,'narcotic,',''),
						if(p.schedule_x=1,'schedule_x,',''),
						if(p.schedule_g=1,'schedule_g,',''))) as shed_x_h,
						s.HSNCODE,
				SUM(TRUNCATE((s.total_unit-s.total_sale_unit-s.total_lost_unit-s.total_return_unit)/s.packing,0)) AS C_Pak_Qty,
				SUM(s.total_sale_unit) AS sale_unit,p.SGST_per,
				SUM(TRUNCATE((s.total_sale_unit)/s.packing,0)) AS C_Pak_Sale_Qty,  
				p.re_order_qty, 
				SUM(s.total_unit-s.total_sale_unit) AS C_Unit_Stock_Qty,s.purchase_unit_rate,
				SUM(TRUNCATE((s.total_unit-s.total_sale_unit-total_lost_unit-s.total_return_unit),0))*MAX(s.purchase_unit_rate) AS Stock_Cost ,
				v.name_supplier,date_format(date_of_invoice,'%d-%m-%Y') as P_Date
				FROM ((med_store_product_master p JOIN purchase_invoice_item_stock s ON p.id=s.item_code)
 				JOIN purchase_invoice_stock m ON s.purchase_id=m.id )
				JOIN stock_supplier v on v.sid=m.sid
			WHERE " . $where . " group by p.id,s.packing " . $HAVING . " order by p.Item_name";
		$query = $this->db->query($sql);
		$stock_list = $query->result();


		$content = "";
		$content .= '<table class="table table-bordered table-striped TableData" style="border: 1px solid black;">
					<thead>
						<tr>
							<th style="width:300px;">Item Name</th>
							<th>Compostion Name (Salt Name)</th>
							<th>Pur. Date</th>
							<th>Batch No.</th>
							<th>Expiry</th>
							<th>Purchase Unit Qty</th>
							<th>Sale Unit Qty</th>
							<th>Cur. Qty</th>
							<th>Shedule H / X</th>
							<th>SuppLier Name</th>
							<th>HSN Code</th>
							<th>GST Per</th>
							<th>Stock Cost</th>
							<th>Purchase Rate</th>
							<th>MRP</th>
						</tr>
					</thead>
					<tbody>';

		foreach ($stock_list as $row) {

			if ($row->C_Pak_Qty <= $row->re_order_qty) {
				$tr_color = 'style="background-color: #999999;"';
			} else {
				$tr_color = '';
			}

			$content .= '<tr ' . $tr_color . ' >
						<td valign="top">' . $row->item_name . '</td>
						<td valign="top">' . $row->genericname . '</td>
						<td valign="top">' . $row->P_Date . '</td>
						<td valign="top">' . $row->batch_no . '</td>
						<td valign="top">' . $row->str_exp . '</td>
						<td valign="top">' . $row->P_Unit_Qty . '</td>
						<td valign="top">' . $row->sale_unit . '</td>
						<td valign="top">' . $row->C_Unit_Stock_Qty . '</td>
						<td valign="top">' . $row->shed_x_h . '</td>
						<td valign="top">' . $row->name_supplier . '</td>
						<td valign="top">' . $row->HSNCODE . '</td>
						<td valign="top">' . $row->SGST_per . '</td>
						<td valign="top">' . $row->Stock_Cost . '</td>
						<td valign="top">' . $row->purchase_unit_rate . '</td>
						<td valign="top">' . $row->mrp . '</td>
					</tr>';
		}

		$content .= '</tbody></table>';

		//echo $content;
		ExportExcel($content, 'Report_Medical_store_stock');
	}
    
	
	





	
}