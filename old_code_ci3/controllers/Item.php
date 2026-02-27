<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Item extends MY_Controller {
    
	public function __construct()
	{
		parent::__construct();
	}
			
	public function search_itemtype()
	{
		$sql = "SELECT *,(case is_ipd_opd when 0 then 'OPD-IPD' when 1 then 'OPD' when 2 then 'IPD' end) as isIPD_OPD FROM hc_item_type order by  group_desc";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
		$this->load->view('item/item_type_search_v',$data);
	}
	
	public function search_adv($itype_id=1)
	{
		
		$sql = "SELECT * FROM v_hc_items where itype=".$itype_id;
        $query = $this->db->query($sql);
        $data= $query->result();
		echo '<a href="/Item/search_print/'.$itype_id.'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a><br/><br/>';
		
		echo	'<table id="example1" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
				  <th>OPD or IPD</th>
				  <th>Group</th>
				  <th>Charge Name</th>
				  <th>Amount</th>
				  <th></th>
				</tr>
				</thead>
				<tbody>';
		if(count($data)>0){
			for ($i = 0; $i < count($data); ++$i) { 
				echo '<tr>
						<td>'.$data[$i]->isIPD_OPD.'</td>
						<td>'.$data[$i]->group_desc.'</td>
						<td>'.$data[$i]->idesc.'</td>
						<td>'.$data[$i]->amount.'</td>
					<td>
					<button  type="button" class="btn btn-primary"  data-toggle="modal" data-target="#tallModal_item" 
						data-testid="'.$data[$i]->id.'" data-testname="'.$data[$i]->group_desc.'">Edit It....</button>
					</td>
					</tr>';
			}	
		}	
		
		echo '</tbody>
				<tfoot>
				<tr>
				  <th>OPD or IPD</th>
				  <th>Group</th>
				  <th>Charge Name</th>
				  <th>Amount</th>
				  <th></th>
				</tr>
				</tfoot>
			  </table>';
		echo "<script>$('#example1').dataTable();</script>";
		
	}
	
	public function search()
	{
		$sql = "SELECT * FROM hc_item_type order by  group_desc";
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
				
		$sql = "SELECT * FROM v_hc_items where itype=1";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
		$this->load->view('item/item_search_V',$data);
		
	}


	public function search_ipd()
	{
		$sql = "SELECT * FROM ipd_item_type order by  group_desc";
        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
				
		$sql = "SELECT * FROM ipd_items where itype=1";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
		$this->load->view('item_ipd/item_search_V',$data);
		
	}
	
	public function search_print($itype_id=1)
	{
					
		$sql = "SELECT * FROM v_hc_items where itype=".$itype_id." order by  group_desc";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
		
		$this->load->view('item/item_item_list',$data);
		
	}
	
	function itemtype_record($id)
    {
        $sql = "SELECT *,(case is_ipd_opd when 0 then 'OPD-IPD' when 1 then 'OPD' when 2 then 'IPD' end) as isIPD_OPD 
		FROM hc_item_type where itype_id='".$id."' order by  group_desc";
        $query = $this->db->query($sql);
        $data['data_item']= $query->result();

        $this->load->view('item/item_type_profile',$data);
    }
	
	function item_record($id)
    {
        
        $sql="select * from v_hc_items where id=".$id;
        $query = $this->db->query($sql);
        $data['data_item']= $query->result();
		
		$sql="select * from hc_item_type";
        $query = $this->db->query($sql);
        $data['data_item_type']= $query->result();
		
		$sql="select * from v_insurance_item_list where c_item_id=".$id;
        $query = $this->db->query($sql);
        $data['data_insurance_item']= $query->result();
		
		$sql="select * from hc_insurance where id>1";
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();
        
        $this->load->view('item/item_profile_V',$data);
    }
		
	public function UpdateRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        		
		$this->load->model('item_M');
					
        $data = array( 
                    'itype' => $this->input->post('Item_Type'),
					'idesc' => $this->input->post('input_Item_name'), 
                	'amount' => $this->input->post('input_amount'),
					'update_date' => date('Y-m-d H:i:s')
                );
        $old_id=$this->input->post('p_id'); 
        $update=$this->item_M->update($data,$old_id);
		
		if($update>0)
		{
			$rvar=array(
                'update' =>1,
				'showcontent'=> Show_Alert('success','Saved','Data Saved successfully')
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
	
	public function AddRecord() 
	{
		$sql="select * from hc_item_type";
        $query = $this->db->query($sql);
        $data['data_item_type']= $query->result();
		
		$this->load->view('item/item_create_V',$data);
	}
	
	
	public function AddItemTypeRecord() 
	{
		$this->load->view('item/item_type_create');
	}
	
	public function CreateRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        		
		$this->load->model('item_M');
        $data = array( 
                    'itype' => $this->input->post('Item_Type'),
					'idesc' => $this->input->post('input_Item_name'), 
                	'amount' => $this->input->post('input_amount'),
					'update_date' => date('Y-m-d H:i:s')
                 );
        
        $inser_id=$this->item_M->insert($data);
		
		if($inser_id>0)
		{
			
                $rvar=array(
                'insertid' =>$inser_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
	
	public function AddInsuranceItemRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$this->load->model('item_M');
        $data = array( 
                    'hc_items_id' => $this->input->post('p_id'),
					'hc_insurance_id' => $this->input->post('ins_company_name'), 
                	'amount1' => $this->input->post('input_amount'),
					'code' => $this->input->post('input_item_code')
                 );
        
        $inser_id=$this->item_M->insert_in_item($data);
		
		if($inser_id>0)
		{
			$sql="select * from v_insurance_item_list where c_item_id=".$this->input->post('p_id');
			$query = $this->db->query($sql);
			$data_insurance_item= $query->result();
        
			echo '<table id="example1" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
				  <th>Company Name</th>
				  <th>Amount</th>
				  <th>Code</th>
				  <th>Action</th>
				</tr>
				</thead>
				<tbody>';
			for ($i = 0; $i < count($data_insurance_item); ++$i) { 
				echo '<tr>
				  <td>'.$data_insurance_item[$i]->ins_company_name.'</td>
				  <td>'.$data_insurance_item[$i]->i_amount.'</td>
				  <td>'.$data_insurance_item[$i]->code.'</td>
				  <td><button onclick="remove_item_spec('.$data_insurance_item[$i]->i_item_id.')" type="button" class="btn btn-primary">Delete</button></td>
				</tr>';
			}
			echo '</tbody></table>';
		}
		
    }
	
	public function remove_record_item() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$this->load->model('item_M');
        $data = array( 
                    'isdelete' => $this->input->post('in_remove_id')
					);
        
        $inser_id=$this->item_M->delete_in_item($data,$this->input->post('in_remove_id'));
		
		if($inser_id>0)
		{
			$sql="select * from v_insurance_item_list where c_item_id=".$this->input->post('p_id');
			$query = $this->db->query($sql);
			$data_insurance_item= $query->result();
        
			echo '<table id="example1" class="table table-bordered table-striped TableData">
				<thead>
				<tr>
				  <th>Company Name</th>
				  <th>Amount</th>
				  <th>Code</th>
				  <th>Action</th>
				</tr>
				</thead>
				<tbody>';
			for ($i = 0; $i < count($data_insurance_item); ++$i) { 
				echo '<tr>
				  <td>'.$data_insurance_item[$i]->ins_company_name.'</td>
				  <td>'.$data_insurance_item[$i]->i_amount.'</td>
				  <td>'.$data_insurance_item[$i]->code.'</td>
				  <td><button onclick="remove_item_spec('.$data_insurance_item[$i]->i_item_id.')" type="button" class="btn btn-primary">Delete</button></td>
				</tr>';
			}
			echo '</tbody></table>';
		}
		
    }
	
	
	public function _render_page($view, $data=null, $returnhtml=false)//I think this makes more sense
	{

		$this->viewdata = (empty($data)) ? $this->data: $data;

		$view_html = $this->load->view($view, $this->viewdata, $returnhtml);

		if ($returnhtml) return $view_html;//This will return html on 3rd argument being true
	}

	
	public function CreateItemTypeRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        		
		$this->load->model('item_M');
        $data = array( 
                    'is_ipd_opd' => $this->input->post('Item_Type_ipd'),
					'group_desc' => $this->input->post('input_Item_type')
                 );
        
        $inser_id=$this->item_M->insert_type($data);
		
		if($inser_id>0)
		{
			
                $rvar=array(
                'insertid' =>$inser_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
	
	public function UpdateItemTypeRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$this->load->model('item_M');

        $data = array( 
                    'is_ipd_opd' => $this->input->post('Item_Type_ipd'),
					'group_desc' => $this->input->post('input_Item_type')
                );

        $old_id=$this->input->post('itemtype_id'); 
        $update=$this->item_M->update_type($data,$old_id);
		
		if($update>0)
		{
			$rvar=array(
                'update' =>1,
				'showcontent'=> Show_Alert('success','Saved','Data Saved successfully')
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
	
}