<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
	
	public function indent_list()
    {
		$this->load->view('Medical/Stock/indent_request');
	}
	
	public function Indent_Request_Search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="	SELECT m.indent_id,m.indent_code,s.store_name,
					m.request_status,m.created_date,
					GROUP_CONCAT(t.product_name) AS product_list,
					i.indent_id  AS process_indent_id,
					(case m.request_status 
						when 0 then 'Pending' 
						when 1 then 'Send To Store' 
						when 2 then 'Accept and Process'
						when 3 then 'Processed and Verification Pending'
						when 4 then concat('Send to Counter :',s.store_name)
						when 5 then concat('Accept By Counter :',s.store_name)
						else 'UnKnown' end) as request_status_str,
					(case m.indent_type when 0 then 'Auto' when 1 then 'By Counter Store' when 2 then 'Main Store' else 'UnKnown' end) as indent_request_type_str
					FROM (((med_indent_request m Left JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
					JOIN med_product_master p ON t.product_id=p.id)
					JOIN store_master s ON m.store_id=s.store_id)
					LEFT JOIN med_indent_process i ON m.indent_id=i.indent_id
					where m.request_status >0 and m.indent_type in (0,1)
					GROUP BY m.indent_id order by m.indent_id desc limit 50";
		}else{
			$sql="	SELECT m.indent_id,m.indent_code,s.store_name,
					m.request_status,m.created_date,
					GROUP_CONCAT(t.product_name) AS product_list,
					i.indent_id  AS process_indent_id,
					(case m.request_status 
						when 0 then 'Pending' 
						when 1 then 'Send To Store' 
						when 2 then 'Accept and Process'
						when 3 then 'Processed and Verification Pending'
						when 4 then concat('Send to Counter :',s.store_name)
						when 5 then concat('Accept By Counter :',s.store_name)
						else 'UnKnown' end) as request_status_str,
					(case m.indent_type when 0 then 'Auto' when 1 then 'By Counter Store' when 2 then 'Main Store' else 'UnKnown' end) as indent_request_type_str
					FROM (((med_indent_request m Left JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
					JOIN med_product_master p ON t.product_id=p.id)
					JOIN store_master s ON m.store_id=s.store_id)
					LEFT JOIN med_indent_process i ON m.indent_id=i.indent_id
					WHERE t.product_name LIKE '%".$sdata."%' OR m.indent_code LIKE '%".$sdata."'
					and m.request_status >0   and m.indent_type in (0,1)
					GROUP BY m.indent_id order by m.indent_id desc limit 50";
		}
		
		$query = $this->db->query($sql);
        $data['indent_req_list']= $query->result();
		
		$this->load->view('Medical/Stock/indent_request_search_result',$data);
	}
	
	
	public function indent_request_item_store_list_view($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	

		$sql="SELECT t.*,p.item_name,p.formulation,p.genericname,p.packing
			FROM ((med_indent_request m JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
			JOIN med_product_master p ON t.product_id=p.id)
			WHERE m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request_items']= $query->result();

		$data['indent_status_cont']=array(
			'Pending','Send to Store','Accept By Store','Processing Items','Send to Counter','Accept By Counter'
		);

		$this->load->view('Medical/Stock/indent_req_view_with_store',$data);
	}
	
	public function indent_issue_items($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	

		$sql="select * from med_indent_process where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_process']= $query->result();

		$sql="SELECT t.*,p.item_name,p.formulation,p.genericname,p.packing
			FROM ((med_indent_request m JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
			JOIN med_product_master p ON t.product_id=p.id)
			WHERE m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request_items']= $query->result();

		$data['indent_status_cont']=array(
			'Pending','Send to Store','Accept By Store','Processing Items','Send to Counter','Accept By Counter'
		);

		$this->load->view('Medical/Stock/indent_issue_items',$data);

	}

	public function indent_issue_items_view($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	

		$sql="select * from med_indent_process where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_process']= $query->result();

		$data['indent_status_cont']=array(
			'Pending','Send to Store','Accept By Store','Processing Items','Send to Counter','Accept By Counter'
		);

		$this->load->view('Medical/Stock/indent_issue_item_view',$data);

	}
	
	public function indent_issue_items_sub($indent_id,$indent_view=0)
	{
		$sql="SELECT t.*,p.item_name,p.formulation,p.genericname,p.packing ,SUM(a.qty) AS issued_qty
			FROM ((med_indent_request m JOIN med_indent_request_items t ON m.indent_id=t.indent_id) 
			JOIN med_product_master p ON t.product_id=p.id) 
			LEFT JOIN med_indent_process_items a ON m.indent_id=a.indent_id AND t.id=a.indent_item_id
			WHERE m.indent_id=".$indent_id." GROUP BY m.indent_id,t.id";
			
		$query = $this->db->query($sql);
		$med_indent_request_items= $query->result();
		
		$srno=0;
		$content='<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Item Name</th>
						<th>Formulation</th>
						<th>Qty.</th>
						<th>Min Qty</th>
						<th>Issued Qty</th>
					</tr>';
		foreach($med_indent_request_items as $row)
		{ 
			$srno=$srno+1;
			$content.= '<tr>';
			$content.= '<td>'.$srno.'</td>';
			if($indent_view==0)
			{
				$content.= '<td><a href="javascript:load_form_div(\'stock/indent_issue_items_stock/'.$row->product_id.'/'.$row->indent_id.'/'.$row->id.'\',\'show_store\') ">'.$row->item_name.'</a></td>';
			}else{
				$content.= '<td><a href="javascript:load_form_div(\'stock/indent_accept_items_list/'.$row->indent_id.'/'.$row->product_id.'\',\'show_store\') ">'.$row->item_name.'</a></td>';;
			}
			
			$content.= '<td>'.$row->formulation.'</td>';
			$content.= '<td>'.$row->request_qty.'</td>';
			$content.= '<td>'.$row->min_requirement.'</td>';
			$content.= '<td>'.$row->issued_qty.'</td>';
			$content.= '</tr>';
		}

		$content.='<tr>
							<th style="width: 10px">#</th>
							<th>Item Name</th>
							<th>Formulation</th>
							<th>Qty.</th>
							<th>Min Qty</th>
							<th>Issued Qty</th>
						</tr>
						</table>';
		echo $content;
	}
	
	
	public function indent_issue_items_stock($product_id,$indent_id,$indent_item_id)
	{
		$sql="SELECT s.id,s.purchase_id,s.Item_name,s.batch_no,s.expiry_date,s.tqty,i.qty as issue_item,i.store_stock_id
				FROM (purchase_invoice_item s JOIN med_product_master p ON s.item_code=p.id)
				LEFT JOIN med_indent_process_items i ON i.ssno=s.id 
				AND i.indent_id=".$indent_id." AND i.indent_item_id=".$indent_item_id." 	WHERE  s.item_code=".$product_id;
		
		$query = $this->db->query($sql);
		$data['stock_list']= $query->result();

		$data['product_id']=$product_id;
		$data['indent_id']=$indent_id;
		$data['indent_item_id']=$indent_item_id;
	
		$this->load->view('Medical/Stock/indent_stock_list',$data);

	}
	
	public function indent_process_update_Qty()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('product_master_M');
		
		
		$product_id=$this->input->post('hid_product_id');
		$indent_id=$this->input->post('hid_indent_id');
		$indent_item_id=$this->input->post('hid_indent_item_id');
		
		$ssno=$this->input->post('pur_item_id');
		$qty_issue=$this->input->post('qty_issue');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

		$sql="select * from med_indent_process where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_process= $query->result();
		
		if(count($med_indent_process)<1)
		{
			$Pdata = array(
				'store_accept_time' => date('d-m-Y H:i:s'),
				'store_accept_by' => $user_id,
				'store_accept_by_name' => $user_name_info,
				'indent_id' => $indent_id,
				'indent_process_status' =>0,
				'verify_status'=>0,
			); 
			$inser_id=$this->product_master_M->insert_med_indent_process($Pdata);
		}
		
		$Pdata = array(
				'indent_id' => $indent_id,
				'indent_item_id' => $indent_item_id,
				'product_id' => $product_id,
				'qty' => $qty_issue,
				'ssno' => $ssno
			); 

		$sql="select * from med_indent_process_items 
				where indent_id=".$indent_id." and 
				indent_item_id=".$indent_item_id." 
				and product_id=".$product_id." and ssno=".$ssno;
		$query = $this->db->query($sql);
		$med_indent_process_items= $query->result();

		//echo $sql;
		
		if(count($med_indent_process_items)>0)
		{
			$indent_process_item_id=$med_indent_process_items[0]->id;
			$this->product_master_M->update_med_indent_process_items($Pdata,$indent_process_item_id);
		}else{
			$inser_id=$this->product_master_M->insert_med_indent_process_items($Pdata);
		}

		$rvar=array(
			'is_update_stock' =>1,
			'show_text'=>'Updated'
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}
	
	function indent_process_items_list($indent_id)
	{
		$sql="SELECT i.indent_id,i.id AS request_item_id,t.id AS process_item_id,i.product_name,t.ssno,p.batch_no,p.expiry_date,i.request_qty,
			SUM(t.qty) AS t_qty
			FROM ((med_indent_process m join med_indent_process_items t ON m.indent_id=t.indent_id)
			JOIN med_indent_request_items i ON m.indent_id=i.indent_id AND i.id=t.indent_item_id)
			JOIN purchase_invoice_item p ON t.ssno=p.id
			where i.indent_id=".$indent_id."
			GROUP BY i.indent_id,i.id,t.id WITH rollup";
		$query = $this->db->query($sql);
		$med_indent_process_items= $query->result();
		
		$content='<div class="col-md-6" >';
		
		$content.="<h2>Issued Items </h2>";
		
		$content.='<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Batch No.</th>
						<th>Expiry Date</th>
						<th>Issued Qty.</th>
					</tr>';
		$srno=0;
		$header_name="";
		foreach($med_indent_process_items as $row)
		{
			if($row->indent_id=='')
			{
				
			}elseif($row->request_item_id==''){
				
			}elseif($row->process_item_id==''){
				$content.=' <tr>';
				$content.= '<td colspan="3" align="Right">Item Name : <b>Total Qty of <i>'.$row->product_name.'</i></b></td>';
				$content.= '<td>'.$row->t_qty.'</td>';
				$content.= '<td></td>';
				$content.= '</tr>';
			}else{
				if($header_name!=$row->product_name)
				{
					$content.=' <tr>';
					$content.= '<td colspan="3" align="Left"><b>'.$row->product_name.'</b></td>';
					$content.= '<td></td>';
					$content.= '</tr>';
				}
				
					$header_name=$row->product_name;
					
					$srno=$srno+1;
					$content.=' <tr>';
					$content.= '<td>'.$srno.'</td>';
					$content.= '<td>'.$row->batch_no.'</td>';
					$content.= '<td>'.$row->expiry_date.'</td>';
					$content.= '<td>'.$row->t_qty.'</td>';
					$content.= '</tr>';
			}
		}
		
		$content.= '</table>';
		
		$content.= '</div>';
		$content.='<div class="col-md-6" >';
		$content.='<div class="col-md-3">
					
				</div>';
		$content.= '</div>';
		echo $content;
		
	}
	
	//indent Accept By Store
	
	function indent_store_accept_items_list($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	
		
		$this->load->view('Medical/Stock/indent_accept_items_list',$data);
	}
	
	
	function indent_accept_items_list($indent_id,$item_id=0)
	{
		$where=" i.indent_id=$indent_id";
		if($item_id>0)
		{
			$where.=" and i.id =$item_id";
		}
		
		$sql="SELECT i.indent_id,i.id AS request_item_id,t.id AS process_item_id,i.product_name,
			t.ssno,p.batch_no,p.expiry_date,i.request_qty,t.store_stock_id,
			SUM(t.qty) AS t_qty,r.request_status
			FROM (((med_indent_process m join med_indent_process_items t ON m.indent_id=t.indent_id)
			JOIN med_indent_request_items i ON m.indent_id=i.indent_id AND i.id=t.indent_item_id)
			JOIN purchase_invoice_item p ON t.ssno=p.id)
			JOIN med_indent_request r on i.indent_id=r.indent_id
			where $where
			GROUP BY i.indent_id,i.id,t.id WITH rollup";
	
		$query = $this->db->query($sql);
		$med_indent_process_items= $query->result();
		
		$content="";
		
		$content.='<table class="table table-striped ">
					<tr>
						<th style="width: 10px">#</th>
						<th>Batch No.</th>
						<th>Expiry Date</th>
						<th>Issued Qty.</th>
						<th><button onclick="accept_item_all('.$indent_id.')">Accept All</button></th>
					</tr>';
		$srno=0;
		$header_name="";
		foreach($med_indent_process_items as $row)
		{
			if($row->indent_id=='')
			{
				
			}elseif($row->request_item_id==''){
				
			}elseif($row->process_item_id==''){
				$content.=' <tr>';
				$content.= '<td colspan="3" align="Right"> <b>Total Qty of <i>'.$row->product_name.'</i> : </b></td>';
				$content.= '<td>'.$row->t_qty.'</td>';
				$content.= '<td></td>';
				$content.= '</tr>';
			}else{
				if($header_name!=$row->product_name)
				{
					$content.=' <tr>';
					$content.= '<td colspan="4" align="Left"><b>'.$row->product_name.'</b></td>';
					$content.= '<td></td>';
					$content.= '</tr>';
				}
				
					$header_name=$row->product_name;
					
					$srno=$srno+1;
					$content.=' <tr>';
					$content.= '<td>'.$srno.'</td>';
					$content.= '<td>'.$row->batch_no.'</td>';
					$content.= '<td>'.$row->expiry_date.'</td>';
					$content.= '<td>'.$row->t_qty.'</td>';
					if($row->store_stock_id==0 && $row->request_status==4)
					{
						$content.= '<td><button onclick="accept_item('.$row->indent_id.','.$row->process_item_id.')">Accept</button></td>';
					}else{
						$content.= '<td></td>';
					}
					
					$content.= '</tr>';
			}
		}
		
		$content.= '</table>';
		
		$content.= '</div>';
		
		echo $content;
		
	}

	
	
	public function indent_process_accept_Qty()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('product_master_M');

		$indent_id=$this->input->post('indent_id');
		$process_item_id=$this->input->post('process_item_id');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

		$sql="select * from med_indent_request where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_request= $query->result();
		
		$store_id=$med_indent_request[0]->store_id;

		$sql="select * from med_indent_process_items where id=".$process_item_id." and indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_process_items= $query->result();

		if(count($med_indent_process_items)>0)
		{
			$sql="select * from purchase_invoice_item where id=".$med_indent_process_items[0]->ssno;
			$query = $this->db->query($sql);
			$purchase_invoice_item= $query->result();

			$item_unit_qty=$med_indent_process_items[0]->qty*$purchase_invoice_item[0]->packing;
			
			$Pdata = array(
				'store_id' => $store_id,
				'ind_proc_item_id' => $process_item_id,
				'product_id' => $med_indent_process_items[0]->product_id,
				'ssno' => $med_indent_process_items[0]->ssno,
				'item_qty' => $med_indent_process_items[0]->qty,
				'item_unit_qty' => $item_unit_qty,
				'insert_date' => date('Y-m-d H:i:s'),
				'accept_by' => $user_id,
				'accept_by_name' => $user_name_info,
				'accept_time' => date('Y-m-d H:i:s'),
			);
			
			$insert_id=$this->product_master_M->insert_store_stock_items($Pdata);
			
			if($insert_id>0)
			{
				$Pdata = array(
				'store_stock_id' => $insert_id,
				);
			
				$insert_id=$this->product_master_M->update_med_indent_process_items($Pdata,$process_item_id);
			}
		}
		
		$rvar=array(
			'is_update_stock' =>1,
			'show_text'=>'Updated'
			);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}


	public function indent_process_accept_Qty_all($indent_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('product_master_M');
		
				
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

		$sql="select * from med_indent_request where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_request= $query->result();
		
		$store_id=$med_indent_request[0]->store_id;

		$sql="select * from med_indent_process_items where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_process_items= $query->result();

		foreach($med_indent_process_items as $row)
		{
			$sql="select * from purchase_invoice_item where id=".$row->ssno;
			$query = $this->db->query($sql);
			$purchase_invoice_item= $query->result();

			$process_item_id=$row->id;

			$item_unit_qty=$row->qty*$purchase_invoice_item[0]->packing;
			
			$Pdata = array(
				'store_id' => $store_id,
				'ind_proc_item_id' => $process_item_id,
				'product_id' => $row->product_id,
				'ssno' => $row->ssno,
				'item_qty' => $row->qty,
				'item_unit_qty' => $item_unit_qty,
				'insert_date' => date('Y-m-d H:i:s'),
				'accept_by' => $user_id,
				'accept_by_name' => $user_name_info,
				'accept_time' => date('Y-m-d H:i:s'),
			);
			
			$insert_id=$this->product_master_M->insert_store_stock_items($Pdata);
			
			if($insert_id>0)
			{
				$Pdata = array(
				'store_stock_id' => $insert_id,
				);
			
				$insert_id=$this->product_master_M->update_med_indent_process_items($Pdata,$process_item_id);
			}
		}
		
		$rvar=array(
			'is_update_stock' =>1,
			'show_text'=>'Updated'
			);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	

	//Store Indent Transfer Direct

	public function indent_counter_list()
    {
		$sql="select * from store_master order by store_name";
		$query = $this->db->query($sql);
		$data['store_master']= $query->result();
		
		$this->load->view('Medical/Stock/indent_counter_list',$data);
	}

	public function Indent_counter_Search()
	{
		$sdata=$this->input->post('txtsearch');
        
		$sdata = preg_replace('/[^A-Za-z0-9_ \-]/', '', $sdata);
		
		if(trim($sdata)=='')
		{
			$sql="	SELECT m.indent_id,m.indent_code,s.store_name,
					m.request_status,m.created_date,
					GROUP_CONCAT(t.product_name) AS product_list,
					i.indent_id  AS process_indent_id,
					(case m.request_status 
						when 0 then 'Pending' 
						when 1 then 'Send To Store' 
						when 2 then 'Accept and Process'
						when 3 then 'Processed and Verification Pending'
						when 4 then concat('Send to Counter :',s.store_name)
						when 5 then concat('Accept By Counter :',s.store_name)
						else 'UnKnown' end) as request_status_str,
					(case m.indent_type when 0 then 'Auto' when 1 then 'By Counter Store' when 2 then 'Main Store' else 'UnKnown' end) as indent_request_type_str
					FROM (((med_indent_request m Left JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
					JOIN med_product_master p ON t.product_id=p.id)
					JOIN store_master s ON m.store_id=s.store_id)
					LEFT JOIN med_indent_process i ON m.indent_id=i.indent_id
					where  m.indent_type = 2 
					GROUP BY m.indent_id order by m.indent_id desc limit 50";
		}else{
			$sql="	SELECT m.indent_id,m.indent_code,s.store_name,
					m.request_status,m.created_date,
					GROUP_CONCAT(t.product_name) AS product_list,
					i.indent_id  AS process_indent_id,
					(case m.request_status 
						when 0 then 'Pending' 
						when 1 then 'Send To Store' 
						when 2 then 'Accept and Process'
						when 3 then 'Processed and Verification Pending'
						when 4 then concat('Send to Counter :',s.store_name)
						when 5 then concat('Accept By Counter :',s.store_name)
						else 'UnKnown' end) as request_status_str,
					(case m.indent_type when 0 then 'Auto' when 1 then 'By Counter Store' when 2 then 'Main Store' else 'UnKnown' end) as indent_request_type_str
					FROM (((med_indent_request m Left JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
					JOIN med_product_master p ON t.product_id=p.id)
					JOIN store_master s ON m.store_id=s.store_id)
					LEFT JOIN med_indent_process i ON m.indent_id=i.indent_id
					WHERE t.product_name LIKE '%".$sdata."%' OR m.indent_code LIKE '%".$sdata."'
					and  m.indent_type = 2 
					GROUP BY m.indent_id order by m.indent_id desc limit 50";
		}
			
		$query = $this->db->query($sql);
        $data['indent_req_list']= $query->result();
		
		$this->load->view('Medical/Stock/indent_counter_search_result',$data);
	}

	public function Create_Indent_counter($store_id=3)
    {
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');
		

		$sql="SELECT m.indent_id,COUNT(t.id) AS No_item
		from med_indent_request m LEFT join med_indent_request_items t ON m.indent_id=t.id
		where store_id='".$store_id."' and indent_type <>2
		GROUP BY m.indent_id having COUNT(t.id)=0";
		$query = $this->db->query($sql);
		$pending_nil_indent= $query->result();

		$user_update_log='Indent Create By '.$user_name_info.'.';

		$Udata = array( 
			'store_id'=> $store_id,
			'indent_type'=> '2',
			'created_date'=> date('Y-m-d H:i:s'),
			'request_status'=>'0',
			'request_status_log'=>$user_update_log,
		);

		$this->load->model('product_master_M');
		
		if(count($pending_nil_indent)>0)
		{
			$this->product_master_M->update_med_indent_request($Udata,$pending_nil_indent[0]->indent_id);
			$inser_id=$pending_nil_indent[0]->indent_id;
		}else{
			$inser_id=$this->product_master_M->insert_med_indent_request($Udata);
		}

		$send_msg="Added Successfully";
	
		if($inser_id>0)
		{
			redirect('Stock/Indent_counter_Edit/'.$inser_id);
		}else{
			echo $send_msg;
		}

	}

	public function Indent_counter_Edit($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m Left JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
 
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	

		$sql="SELECT t.*,p.item_name,p.formulation,p.genericname,p.packing
			FROM ((med_indent_request m JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
			JOIN med_product_master p ON t.product_id=p.id)
			WHERE m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request_items']= $query->result();

		$data['indent_status_cont']=array(
			'Pending','Send to Store','Accept By Store','Processing Items','Send to Counter','Accept By Counter'
		);

		$this->load->view('Medical/Stock/indent_counter_edit',$data);
	}

	public function get_product_master(){
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$where_str="  i.item_name like '".$q."%'";

			if(strlen($q)>3)
			{	
				$where_str=" or batch_no like '".$q."%'";
			}

			$sql="SELECT i.Item_name,i.tqty,i.id,i.item_code,i.batch_no,date_format(p.date_of_invoice,'%d-%m-%Y') as purchase_date,
				date_format(i.expiry_date,'%m-%Y') AS exp_date ,m.formulation,i.mrp,m.packing,
				(i.tqty-sum(COALESCE(s.item_qty,0))) as Pending_stock_invoice 
				FROM ((purchase_invoice_item i JOIN purchase_invoice p ON i.purchase_id=p.id) 
				left JOIN store_stock s ON i.id=s.ssno )
				JOIN med_product_master m ON i.item_code=m.id 
				WHERE $where_str
				GROUP BY i.item_code,i.id 
				HAVING (i.tqty-sum(COALESCE(s.item_qty,0)))>0 
				ORDER BY i.Item_name,i.id ";

			$sql=$sql." limit 20";

			$query = $this->db->query($sql);
								
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['Item_name'])).' | '.htmlentities(stripslashes($row['batch_no'])).' | '.htmlentities(stripslashes($row['formulation'])).' | '.htmlentities(stripslashes($row['exp_date']));
				$new_row['value']=htmlentities(stripslashes($row['Item_name'])).' | '.htmlentities(stripslashes($row['formulation']));
				//$new_row['value']=htmlentities(stripslashes($row['itemname']));
								
				$new_row['l_item_code']=htmlentities(stripslashes($row['item_code']));
				$new_row['l_item_ssno']=htmlentities(stripslashes($row['id']));
				$new_row['l_item_qty']=htmlentities(stripslashes($row['Pending_stock_invoice']));
				$new_row['l_packing']=htmlentities(stripslashes($row['packing']));
				$new_row['l_itemname']=htmlentities(stripslashes($row['Item_name'])).' | '.htmlentities(stripslashes($row['formulation']));

				$new_row['l_batch_mrp_exp']=htmlentities(stripslashes($row['batch_no'])).' / '.htmlentities(stripslashes($row['mrp'])).' / '.htmlentities(stripslashes($row['exp_date']));


				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}else{
				$new_row['sql']=$sql;
				$row_set[] = $new_row; //build an array
				echo json_encode($row_set);
			}

		}else{
			echo 'No Data';
		}
	}

	public function Indent_product_stock($product_id)
	{
		$sql="SELECT i.Item_name,i.qty,i.id,i.item_code,i.batch_no,p.date_of_invoice,
				date_format(i.expiry_date,'%m-%Y') AS exp_date ,
				(i.qty-sum(COALESCE(s.item_qty,0))) as Pending_stock_invoice 
				FROM (purchase_invoice_item i JOIN purchase_invoice p ON i.purchase_id=p.id) 
				left JOIN store_stock s ON i.id=s.ssno 
				WHERE i.item_code=$product_id 
				GROUP BY i.item_code,i.id 
				HAVING (i.qty-sum(COALESCE(s.item_qty,0)))>0 
				ORDER BY i.Item_name,i.id ";
		$query = $this->db->query($sql);
		$data['product_store']= $query->result();	

		$this->load->view('Medical/Stock/indent_counter_edit_stock_list',$data);
	}

	public function indent_request_item_list($indent_id)
	{
		$sql="SELECT m.*,s.store_name 
		from med_indent_request m JOIN store_master s ON m.store_id=s.store_id 
		where m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request']= $query->result();	

		$sql="SELECT t.*,p.item_name,p.formulation,p.genericname,p.packing
			FROM ((med_indent_request m JOIN med_indent_request_items t ON m.indent_id=t.indent_id)
			JOIN med_product_master p ON t.product_id=p.id)
			WHERE m.indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$data['med_indent_request_items']= $query->result();
		
		$this->load->view('Medical/Stock/indent_counter_item_list',$data);
		
	}

	public function indent_update_item($indent_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql="select * from med_indent_request where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_request= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

        $FormRules = array(
				array(
                    'field' => 'input_item_name',
                    'label' => 'Product Name',
                    'rules' => 'required|min_length[3]|max_length[30]'
                ),
				
            );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
                $indent_id=$this->input->post('indent_id');
				$indent_item_id=$this->input->post('med_indent_request_items_id');
				
				$this->load->model('product_master_M');

				$Pdata = array(
					'indent_id' => $indent_id,
                    'product_id' => $this->input->post('product_id'),
                	'product_name' => $this->input->post('input_item_name'),
					'request_qty' => $this->input->post('input_Qty'),
                	'cat' => '1',
					'insert_by' => $user_name_info,
                ); 

                $show_text='';
				
				$is_update_stock=0;
				
				if($indent_item_id<1)
				{
					$is_update_stock=$this->product_master_M->insert_med_indent_request_items($Pdata);
				}else{
					$is_update_stock=$this->product_master_M->update_med_indent_request_items($Pdata,$indent_item_id);
				}
				
				if($is_update_stock>0)
				{
					$send_msg="Updated";
				}else{
					$send_msg="Error : Update in Indent Request Table";
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


	public function Transfer_invoice_to_counter($inv_id,$store_id) { 
		$this->load->model('product_master_M');
		
		$sql="SELECT COUNT(*) AS no_record
                FROM (purchase_invoice p JOIN purchase_invoice_item i ON p.id=i.purchase_id)
                WHERE p.id=$inv_id";
        $query = $this->db->query($sql);
        $sql_result= $query->result();

        $msg="";

        $user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');
        
        if(count($sql_result)>0)
        {
            $no_record=$sql_result[0]->no_record;
            if($no_record>0)
            {
                $sql="SELECT (SUM(i.qty+COALESCE(i.qty_free,0))-sum(COALESCE(s.item_qty,0))) as Pending_stock_invoice
                FROM (purchase_invoice_item i left JOIN store_stock s ON i.id=s.ssno)
                WHERE i.purchase_id=$inv_id
                GROUP BY i.purchase_id";
                $query = $this->db->query($sql);
                $sql_result= $query->result();

                if(count($sql_result)>0)
                {
                    $Pending_stock_invoice=$sql_result[0]->Pending_stock_invoice;

					if($Pending_stock_invoice>0)
					{
						//Check Pending  Indent: Create on Direct Purchase Invoice

						/*
						$sql="SELECT m.indent_id,COUNT(t.id) AS No_item
						from med_indent_request m LEFT join med_indent_request_items t ON m.indent_id=t.id
						where store_id='".$store_id."' and indent_type=2
						GROUP BY m.indent_id having COUNT(t.id)=0";
						$query = $this->db->query($sql);
						$pending_nil_indent= $query->result();

						echo $sql;

						*/

						$user_update_log='Indent Create By '.$user_name_info.'.';

						$Udata = array( 
							'store_id'=> $store_id,
							'indent_type'=> '2',
							'created_date'=> date('Y-m-d H:i:s'),
							'request_status'=>'3',
							'request_status_log'=>$user_update_log,
						);
													
						/*
						
						if(count($pending_nil_indent)>0)
						{
							$this->product_master_M->update_med_indent_request($Udata,$pending_nil_indent[0]->indent_id);
							$indent_id=$pending_nil_indent[0]->indent_id;
						}else{
							$indent_id=$this->product_master_M->insert_med_indent_request($Udata);
						}

						*/

						$indent_id=$this->product_master_M->insert_med_indent_request($Udata);


						//Indent Process Create here
						$Pdata = array(
							'store_accept_time' => date('d-m-Y H:i:s'),
							'store_accept_by' => $user_id,
							'store_accept_by_name' => $user_name_info,
							'indent_id' => $indent_id,
							'remark' => $user_update_log,
						);

						$process_indent_id=$this->product_master_M->insert_med_indent_process($Pdata);

						// Get Purchase Invoice List
						$sql="SELECT i.Item_name,i.qty,i.id,i.item_code,
							sum((i.qty+COALESCE(i.qty_free,0))-sum(COALESCE(s.item_qty,0))) as Pending_stock_invoice
							FROM (purchase_invoice_item i left JOIN store_stock s ON i.id=s.ssno)
							WHERE i.purchase_id=$inv_id
							GROUP BY i.purchase_id,i.item_code";
						$query = $this->db->query($sql);
						$sql_result= $query->result();
 
						foreach($sql_result as $row)
						{
							//Entry in indent_request_list
							
							$Udata = array( 
								'indent_id'=> $indent_id,
								'product_id'=> $row->item_code,
								'product_name'=> $row->Item_name,
								'request_qty'=> $row->Pending_stock_invoice,
								'datetime_before'=> date('Y-m-d H:i:s'),
								'insert_by'=> $user_id,
								'update_by'=> $user_id,
							);
							$indent_item_id=$this->product_master_M->insert_med_indent_request_items($Udata);

							//Entry in indent_process_items

							$Pdata = array(
								'indent_id' => $indent_id,
								'indent_item_id' => $indent_item_id,
								'product_id' => $row->item_code,
								'qty' =>$row->Pending_stock_invoice,
								'ssno' => $row->id,
							);
							
							$inser_id=$this->product_master_M->insert_med_indent_process_items($Pdata);

						}
					
						$msg="Indent Created and Process";
						
						/*  Code For Indent Verified and Send to Store And Accept By Store .
						Remove or Comment , if Indent Verification Step Require
						*/

						/* $indent_id Already Define   */
						$request_status = '4';
						
						$sql="select * from med_indent_request where indent_id=".$indent_id;
						$query = $this->db->query($sql);
						$med_indent_request= $query->result();
						
						// 0 Pending , 1 For Send,2 Accept and Process,3 Processed and Verification Pending,4 Verifired & Send by Main Store,5 Accept By Counter
						$indent_status_cont=array(
							'Pending','Send to Store','Accept By Store','Processing Items','Send to Counter','Accept By Counter'
						);
						
						$request_status_log="";
						
						if(count($med_indent_request)>0)
						{
							$request_status_log.=$med_indent_request[0]->request_status_log;
						}
						
						$request_status_log.='Status Change Direct to Store Wih Verification By  '.$indent_status_cont[$request_status].' By '.$user_name_info;
						
						$Pdata = array(
							'request_status' => $request_status,
							'last_update_time' => date('Y-m-d H:i:s'),
							'request_status_log'=>$request_status_log,
						);

						if(count($med_indent_request)>0){
							$is_update_stock=$this->product_master_M->update_med_indent_request($Pdata,$indent_id);
							$msg= $indent_status_cont[$request_status];
						}else{
							$msg= 'Indent Record Not Found';
						}

						/* Indent Accept by Counter Auto  */

						redirect('Stock/indent_process_accept_Qty_all/'.indent_id);

						/* Code End Here  */


					}else{
						$msg="No Items found in Purchase Item";
					}

                }else{
                    $msg="No Items found in Purchase Item";
                }

            }else{
                $msg="No Items found in Purchase Item";
            }

        }else{
            $msg="No Invoice Found";
		}
		
		echo $msg;
	}

	public function indent_request_item_delete($indent_id,$indent_item_id_del)
	{
		$this->load->model('product_master_M');
		
		$sql="SELECT r.id,r.product_name,s.ssno,s.store_id
		FROM (med_indent_request_items r JOIN med_indent_process_items p ON r.id=p.indent_item_id)
		LEFT JOIN  store_stock s ON s.ind_proc_item_id=p.id
		WHERE r.id=$indent_item_id_del";
		$query = $this->db->query($sql);
		$result	= $query->result();
		
		$rvar=array(
			'is_delete' =>0,
			'show_text'=>'Nothing Happen',
			);

		if(count($result)>0)
		{
			$ssno=$result[0]->ssno;
			
			if($ssno=='')
			{
				$is_delete=$this->product_master_M->delete_med_indent_request_item($indent_id,$indent_item_id_del);
			
				$rvar=array(
					'is_delete' =>1,
					'show_text'=>'Removed Successfully',
					);
			}else{
				$rvar=array(
					'is_delete' =>0,
					'show_text'=>'After Indent Process, Cannot Delete',
					);
			}
		}else{
			$rvar=array(
				'is_delete' =>0,
				'show_text'=>'No Record Found',
				);
		}
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	
	public function indent_update_item_direct($indent_id)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$sql="select * from med_indent_request where indent_id=".$indent_id;
		$query = $this->db->query($sql);
		$med_indent_request= $query->result();

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name_info = $user->first_name.''. $user->last_name .'['.$user_id.']'.date('d-m-Y H:i:s');

        $FormRules = array(
				array(
                    'field' => 'input_add_qty',
                    'label' => 'Product Name',
                    'rules' => 'required|numeric|greater_than[0]',
                ),
        );

         $this->form_validation->set_rules($FormRules);
         
         if ($this->form_validation->run() == TRUE)
            {
				$indent_id=$this->input->post('indent_id');
				$product_id=$this->input->post('product_id');
				$product_name=$this->input->post('product_name');
				$add_qty=$this->input->post('input_add_qty');
				$inv_ssno=$this->input->post('inv_ssno');
				$indent_item_id=$this->input->post('med_indent_request_items_id');
				
				$this->load->model('product_master_M');

				#Check indent Process
				$sql="select indent_process_id
				from med_indent_process where indent_id=$indent_id";
				$query = $this->db->query($sql);
				$sql_result= $query->result();

				$user_update_log='ADD By :'.$user_name_info;

				if(count($sql_result)==0)
				{
					$Pdata = array(
						'store_accept_time' => date('d-m-Y H:i:s'),
						'store_accept_by' => $user_id,
						'store_accept_by_name' => $user_name_info,
						'indent_id' => $indent_id,
						'remark' => $user_update_log,
					);
	
					$process_indent_id=$this->product_master_M->insert_med_indent_process($Pdata);
				}
				
				$Udata = array( 
					'indent_id'=> $indent_id,
					'product_id'=> $product_id,
					'product_name'=> $product_name,
					'request_qty'=> $add_qty,
					'datetime_before'=> date('Y-m-d H:i:s'),
					'insert_by'=> $user_id,
					'update_by'=> $user_id,
				);

				if($indent_item_id>0)
				{
					$this->product_master_M->update_med_indent_request_items($Udata,$indent_item_id);
				}else{
					$indent_item_id=$this->product_master_M->insert_med_indent_request_items($Udata);
				}

				$Pdata = array(
					'indent_id' => $indent_id,
					'indent_item_id' => $indent_item_id,
					'product_id' => $product_id,
					'qty' =>$add_qty,
					'ssno' => $inv_ssno,
				); 

				$sql="select id
				from med_indent_process_items 
				where indent_id=$indent_id and indent_item_id=$indent_item_id and ssno=$inv_ssno";
				$query = $this->db->query($sql);
				$sql_result= $query->result();

				if(count($sql_result)>0)
				{
					$med_indent_process_items_id=$sql_result[0]->id;
					$this->product_master_M->update_med_indent_process_items($Pdata,$med_indent_process_items_id);
				}else{
					$med_indent_process_items_id=$this->product_master_M->insert_med_indent_process_items($Pdata);
				}
				
                $show_text='Update Done';
				
				$is_update_stock=1;
								
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

	
}