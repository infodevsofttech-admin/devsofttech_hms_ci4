<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PathLab extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function confirm_payment()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('invoice_M');
		
		$showcontent1='<div class="row no-print">
						<div class="col-xs-6">
							<a href="/index.php/Opd/invoice_print/'.$this->input->post('oid').'" target="_blank" class="btn btn-default"><i class="fa fa-print"></i> Print</a>
						</div>
						<div class="col-xs-6">
							Payment Method by : ';          
		$showcontent2='</div></div>';				
				
		if($this->input->post('mode')==1)
		{
			
			$data = array( 
                    
					'payment_mode'=> '1',
					'payment_status'=>'1'
					
			);

			$this->invoice_M->update( $data,$this->input->post('oid'));
				
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
                    'field' => 'input_card_tran',
					'label' => 'Card Transcation ID',
                    'rules' => 'required|min_length[3]|max_length[15]'
                )
            );
			$this->form_validation->set_rules($FormRules);
			if ($this->form_validation->run() == TRUE)
            {
				$data = array( 
						'payment_mode'=> '2',
						'payment_status'=>'1',
						'card_bank'=>$this->input->post('input_card_mac'),
						'card_remark'=>$this->input->post('input_card_digit'),
						'card_tran_id'=>$this->input->post('input_card_tran')
				);
				$this->Opd_M->update( $data,$this->input->post('oid'));
				
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
					'ech_clam_id'=>$this->input->post('input_claim_id'),
					
			);
			
			$this->invoice_M->update( $data,$this->input->post('oid'));
				
				$status='ECHS Credit';
				$showcontent=$showcontent1.$status.$showcontent2;
				
				$rvar=array(
                'update' =>1,
				'showcontent'=>$showcontent
                );
				
				$encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
		if($this->input->post('mode')==0)
		{
			
		}
	}
	
	public function addPathTest($pno,$ins_id=0)
	{
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)  AS age from patient_master where id='".$pno."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select * from hc_item_type";

        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
		
		$ins_comp_id=0;
		
		if($ins_id>1)
		{
			$sql="select * from hc_insurance_card where id=".$ins_id;
			$query = $this->db->query($sql);
			$data['hc_insurance_card']= $query->result();
			
			$ins_comp_id=$data['hc_insurance_card'][0]->insurance_id;
			
			$sql="select * from hc_insurance where id=".$ins_comp_id;
			$query = $this->db->query($sql);
			$data['insurance']= $query->result();
		}
						
		if($ins_comp_id<2)
		{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=1";
		}else{
			$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc from v_hc_items_with_insurance
					where itype=1 and hc_insurance_id in (0,".$ins_comp_id.")";
		}
		
		$query = $this->db->query($sql);
        $data['labitem']= $query->result();
		
		$this->load->model('invoice_M');

		$sql="select count(*) as no_invoice from invoice_master where invoice_status=0 and attach_id=".$pno;
        $query = $this->db->query($sql);
        $data['chk_draft_invoice']= $query->result();
		
		if($data['chk_draft_invoice'][0]->no_invoice<1)
		{
			$data['insert_invoice'] = array( 
            'attach_type' => '0',
			'attach_id' => $pno,			
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $data['person_info'][0]->p_fname,
			'ins_id' => $ins_comp_id,
			'inv_a_code' => $data['person_info'][0]->p_code
			);
			$inser_id=$this->invoice_M->create_invoice( $data['insert_invoice']);
		}else{
			$sql="select id from invoice_master where invoice_status=0 and attach_id=".$pno;
			$query = $this->db->query($sql);
			$data['invoice_id']= $query->result();
		
			$inser_id=$data['invoice_id'][0]->id;
		}
		
		if($inser_id)
		{
			$sql="select * from invoice_master where id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceMaster']= $query->result();
			
			$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceDetails']= $query->result();
			
			$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$inser_id;
			$query = $this->db->query($sql);
			$data['invoiceGtotal']= $query->result();
		}
				
		$this->load->view('Invoice_PathLab',$data);
    }
	
	public function list_pathtest_bytype()
	{
		$tid=$this->input->post('itype_idv');
		$ins_id=$this->input->post('ins_id');
		
		$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=".$tid;
        
		if($ins_id>1)
		{
			$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc from v_hc_items_with_insurance
					where itype=".$tid." and hc_insurance_id in (0,".$ins_id.")";
			
		}else{
			$sql="select id,Concat(idesc,' : [',amount,']') as sdesc from hc_items where itype=".$tid;
		}

		$query = $this->db->query($sql);
        $data['labitem']= $query->result();

		echo '<div class="form-group">
				<label>Test Name</label>
					<select class="form-control" id="itype_name_id" name="itype_name_id"  >	';				
		foreach($data['labitem'] as $row)
		{ 
			echo '<option value='.$row->id.'>'.$row->sdesc.'</option>';
		}
		echo '</select></div>';
		
	}
	
	
	
	public function showinvoice($invoice_id)
	{
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select * from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();
		
			
		$this->load->view('Invoice_PathLab_final_V',$data);
	}
	
	public function showitem($type)
	{
		$this->load->model('invoice_M');
		
		if($type>0)
		{
			$ins_id=$this->input->post('ins_id');
			
			$sql="select * from hc_items where id=".$this->input->post('itype_name_id');
			if($ins_id>1)
			{
				$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id=0,'','-INS')) as sdesc,amount1 from v_hc_items_with_insurance
						where id=".$this->input->post('itype_name_id')." and hc_insurance_id in (0,".$ins_id.")";
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
			
				$item_rate=$data['itemlist'][0]->amount1;
				
				$amount_value=$this->input->post('input_qty')*$item_rate;
				
			}
			else
			{
				$sql="select id,Concat(idesc,' : [',amount,']') as sdesc,amount from hc_items where id=".$this->input->post('itype_name_id');
				
				$query = $this->db->query($sql);
				$data['itemlist']= $query->result();
				
				$item_rate=$data['itemlist'][0]->amount;
				
				$amount_value=$this->input->post('input_qty')*$item_rate;
			}

			$data['insert_invoice_item'] = array( 
				'inv_master_id' => $this->input->post('lab_invoice_id'),
				'item_type' => $this->input->post('itype_idv'),
				'item_id' => $this->input->post('itype_name_id'),				
				'item_name' => $data['itemlist'][0]->sdesc,
				'item_rate' => $item_rate,
				'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
				'item_qty' => $this->input->post('input_qty'),
				'item_amount' => $amount_value
				);
				
			$inser_id=$this->invoice_M->addinvoiceitem( $data['insert_invoice_item']);
		
		}else{
			$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
		
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		echo '<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Type</th>
				<th>Test Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Amount</th>
				<th></th>
			</tr>';

		$srno=1;

		foreach($data['invoiceDetails'] as $row)
		{ 
			echo '<tr>';
			echo '<td>'.$srno.'</td>';
			echo '<td>'.$row->desc.'</td>';
			echo '<td>'.$row->item_name.'</td>';
			echo '<td>'.$row->item_rate.'</td>';
			echo '<td>'.$row->item_qty.'</td>';
			echo '<td>'.$row->item_amount.'</td>';
			echo '<td><button type="button" class="btn btn-danger" id="btn_add_fee" onclick="remove_item_invoice('.$row->id.')">-Remove</button></td>';
			$srno=$srno+1;
			echo '</tr>';
		}	
			
		echo '<tr>
				<th style="width: 10px">#</th>
				<th></th>
				<th></th>
				<th></th>
				<th>Gross Total</th>
				<th>'.$data['invoiceGtotal'][0]->Gtotal.'</th>
				<th></th>
			</tr>
		</table>';
	}
	
	
	
}