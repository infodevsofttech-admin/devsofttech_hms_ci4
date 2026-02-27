<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ocasemaster extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
    
	public function addPathTest($caseid)
	{
		$sql="select * from organization_case_master where id='".$caseid."' ";

        $query = $this->db->query($sql);
        $data['or_case_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) AS age from patient_master where id='".$data['or_case_master'][0]->p_id."' ";

        $query = $this->db->query($sql);
        $data['person_info']= $query->result();
		
		$sql="select * from hc_item_type";

        $query = $this->db->query($sql);
        $data['labitemtype']= $query->result();
		
		$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id>0,'Ins.','Cash')) as sdesc from v_hc_items_with_insurance where itype=1 and hc_insurance_id in (0,".$data['or_case_master'][0]->insurance_id.")";
        $query = $this->db->query($sql);
        $data['labitem']= $query->result();
		
		$this->load->model('invoice_M');
		
		$sql="select count(*) as no_invoice from invoice_master where invoice_status=0 and case_id=".$caseid;
        $query = $this->db->query($sql);
        $data['chk_draft_invoice']= $query->result();

		if($data['chk_draft_invoice'][0]->no_invoice<1)
		{
			$data['insert_invoice'] = array( 
            'attach_type' => '0',
			'attach_id' => $data['or_case_master'][0]->p_id,
			'case_id' => $caseid,
        	'inv_date' => str_to_MysqlDate(date('d/m/Y')),
			'inv_name' => $data['person_info'][0]->p_fname,
			'inv_a_code' => $data['person_info'][0]->p_code
			);
			$inser_id=$this->invoice_M->create_invoice( $data['insert_invoice']);
		}else{
			$sql="select id from invoice_master where invoice_status=0 and attach_id=".$data['or_case_master'][0]->p_id;
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

		$this->load->view('Invoice/Organization_Invoice_PathLab',$data);
    }
	
	public function create() { 
         if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_mphone1',
                    'label' => 'Phone Number',
                    'rules' => 'required|min_length[10]|max_length[30]'
                ),
				 array(
                    'field' => 'input_name',
                    'label' => 'Name',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
				 array(
                    'field' => 'input_insurance_id',
                    'label' => 'Insurance No',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
                array(
                    'field' => 'input_card_holder_name',
					'label' => 'Insurance Card Holder Name',
                    'rules' => 'required|min_length[2]|max_length[50]'
                )
            );

         $this->form_validation->set_rules($FormRules);

         if ($this->form_validation->run() == TRUE)
            {
				$ins_id=$this->input->post('Insurance_id');
				$case_type=$this->input->post('case_type');

				$sql="select * from hc_insurance where id=".$ins_id;
				$query = $this->db->query($sql);
				$data['data_insurance']= $query->result();
				
				$ins_id_name = $data['data_insurance'][0]->ins_company_name;

                $this->load->model('Ocasemaster_M');
                $data = array( 
                    'p_phone_number' => $this->input->post('input_mphone1'), 
                	'p_name' => $this->input->post('input_name'),
                	'p_gender' => $this->input->post('optionsRadios_gender'),
                	'date_registration' => str_to_MysqlDate($this->input->post('datepicker_dob')),
					'p_id' => $this->input->post('p_id'),
					'case_type' => $this->input->post('case_type'),
					'insurance_card_id'=>$this->input->post('inc_card_id'),
					'insurance_no' => $this->input->post('input_insurance_id'),
					'insurance_no_1' => $this->input->post('input_insurance_no_1'),
					'insurance_no_2' => $this->input->post('input_insurance_no_2'),
					'insurance_no_3' => $this->input->post('input_insurance_no_3'),
					'insurance_card_name' => $this->input->post('input_card_holder_name'),
					'insurance_id' => $this->input->post('Insurance_id'),
					'insurance_company_name' => $ins_id_name
                ); 
			    
				$sql="select * from organization_case_master 
				where status=0 and case_type=$case_type and p_id=".$this->input->post('p_id');
				$query = $this->db->query($sql);
				$chk_data= $query->result();
                
				if(count($chk_data)>0)
				{
					$inser_id=$chk_data[0]->id;
					
				}else{
					$inser_id=$this->Ocasemaster_M->insert($data);
				}
				
				if($case_type==1)
				{
					$sql="select * from ipd_master 
					where case_id=0 and ipd_status=0 and p_id=".$this->input->post('p_id');
					$query = $this->db->query($sql);
					$ipd_master= $query->result();
				
					if(count($ipd_master)>0)
					{
						$this->load->model('Ipd_M');
						$update_ipd=array(
							'case_id'=>$inser_id
						);
						$this->Ipd_M->update($update_ipd,$ipd_master[0]->id);

						$update_org=array(
							'ipd_id'=>$ipd_master[0]->id
						);
						$this->Ocasemaster_M->update($update_org,$inser_id);
					}
				}		
				
                $rvar=array(
                'insertid' =>$inser_id,
				'p_id'=> $this->input->post('p_id'),
				'ins_id'=>$ins_id
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'insertid' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
	}

	public function load($caseid)
	{
		$sql="select * from organization_case_master where id='".$caseid."' ";
        $query = $this->db->query($sql);
        $data['or_case_master']= $query->result();

		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$data['or_case_master'][0]->p_id."' ";
		$query = $this->db->query($sql);
		$data['data']= $query->result();
		
		$sql="select * from hc_insurance";
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();
		
		$this->load->view('Invoice/Case_Form_profile_V',$data);
	}
	
	public function newcase($p_no,$ins_id,$case_type=0)
	{
		$sql="select * from hc_insurance where id=".$ins_id;
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();
			
		$sql="select * from hc_insurance_card where p_id=".$p_no." and insurance_id=".$ins_id;
        $query = $this->db->query($sql);
        $data['hc_insurance_card']= $query->result();
		
		$ins_comp_id=$data['hc_insurance_card'][0]->insurance_id;
		
		$sql="select * from organization_case_master 
		where case_type=$case_type and p_id='".$p_no."' and status=0 ";
        $query = $this->db->query($sql);
        $data['org_case_master']= $query->result();
		
		if($query->num_rows()<1)
		{
			$new_case=1;
		}else{
			$new_case=0;
		}

		$data['case_type']=$case_type;

		$this->db->where('id', $p_no);
        $this->db->from('patient_master');
        $query = $this->db->get();
        $data['data']= $query->result();

		$sql="select * from hc_insurance ";
        $query = $this->db->query($sql);
        $data['insurance_list']= $query->result();
		
		
		if($new_case==1)
		{
			$this->load->view('Invoice/Case_Form_V',$data);
		}
		else{
			$this->load->view('Invoice/Case_Form_profile_V',$data);
		}
		
	}

	

	public function open_case($org_ID,$case_type=0)
	{
		$sql="select * from organization_case_master 
		where case_type=$case_type and id= $org_ID";
        $query = $this->db->query($sql);
		$data['org_case_master']= $query->result();
		
		$ins_id_org=$data['org_case_master'][0]->insurance_id;
		$p_no=$data['org_case_master'][0]->p_id;

		$sql="select * from hc_insurance_card where p_id=".$p_no." order by id desc";
        $query = $this->db->query($sql);
        $data['hc_insurance_card']= $query->result();
		
		$ins_id=$data['hc_insurance_card'][0]->insurance_id;

		$sql="select * from hc_insurance where id=".$ins_id;
        $query = $this->db->query($sql);
        $data['data_insurance']= $query->result();


		$sql="select * from hc_insurance ";
        $query = $this->db->query($sql);
        $data['insurance_list']= $query->result();

		$data['ipd_id']=0;
		$data['case_type']=$case_type;
		
		if($case_type==1)
		{
			$sql="select * from ipd_master where case_id=$org_ID";
			$query = $this->db->query($sql);
			$ipd_master= $query->result();

			if(count($ipd_master)>0)
			{
				$data['ipd_id']=$ipd_master[0]->id;
			}
		}
			
		$this->db->where('id', $p_no);
        $this->db->from('patient_master');
        $query = $this->db->get();
        $data['data']= $query->result();
		
		$this->load->view('Invoice/Case_Form_profile_V',$data);
		
	}
	
	public function update() { 
    if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

		$FormRules = array(
                array(
                    'field' => 'input_mphone1',
                    'label' => 'Phone Number',
                    'rules' => 'required|min_length[10]|max_length[30]'
                ),
				array(
                    'field' => 'input_name',
                    'label' => 'Name',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
				array(
                    'field' => 'input_insurance_id',
                    'label' => 'Insurance No',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
				array(
                    'field' => 'datepicker_dob',
                    'label' => 'Date of Registration',
                    'rules' => 'required|min_length[2]|max_length[30]'
                ),
                array(
                    'field' => 'input_card_holder_name',
					'label' => 'Insurance Card Holder Name',
                    'rules' => 'required|min_length[2]|max_length[50]'
                )
            );

		$this->form_validation->set_rules($FormRules);

		if ($this->form_validation->run() == TRUE)
            {
				$ins_id=$this->input->post('Insurance_id');
				$p_id=$this->input->post('patient_id');
				$case_type=$this->input->post('case_type');
				$case_id=$this->input->post('c_id');
				
				$sql="select * from hc_insurance where id=".$ins_id;
				$query = $this->db->query($sql);
				$data['data_insurance']= $query->result();
				
				$ins_id_name = $data['data_insurance'][0]->ins_company_name;

				$sql="select * from organization_case_master where id=$case_id";
				$query = $this->db->query($sql);
				$org_case_master= $query->result();
				
                $this->load->model('Ocasemaster_M');
                $data = array( 
                    'p_phone_number' => $this->input->post('input_mphone1'), 
					'p_name' => $this->input->post('input_name'),
					'p_gender' => $this->input->post('optionsRadios_gender'),
					'date_registration' => str_to_MysqlDate($this->input->post('datepicker_dob')),
					'remark' => $this->input->post('remark'),
					'insurance_no' => $this->input->post('input_insurance_id'),
					'insurance_no_1' => $this->input->post('input_insurance_no_1'),
					'insurance_no_2' => $this->input->post('input_insurance_no_2'),
					'insurance_no_3' => $this->input->post('input_insurance_no_3'),
					'insurance_card_name' => $this->input->post('input_card_holder_name'),
					'insurance_id' => $ins_id,
					'insurance_company_name' => $ins_id_name
                );
		
                $inser_id=$this->Ocasemaster_M->update($data,$case_id);
				
				if($case_type==0)
				{
					$this->db->query("CALL p_org_update($case_id)");
				}

				if($case_type==1 && $org_case_master[0]->ipd_id==0)
				{
					$sql="select * from ipd_master where ipd_status=0 and case_id=0  and p_id=".$p_id;
					$query = $this->db->query($sql);
					$ipd_master= $query->result();

					if(count($ipd_master)>0)
					{
						$this->load->model('Ipd_M');
						$update_ipd=array(
							'case_id'=>$this->input->post('c_id')
						);
						$this->Ipd_M->update($update_ipd,$ipd_master[0]->id);

						$update_org=array(
							'ipd_id'=>$ipd_master[0]->id
						);
						$this->Ocasemaster_M->update($update_org,$inser_id);

						$update_ins_card=array(
							'insurance_id'=>$ins_id,
							'insurance_id'=>$ins_id_name,
						);
						$this->Ocasemaster_M->update($update_org,$inser_id);
					}
				}
				
				
				$rvar=array(
                'update' =>1,
				'showcontent'=> Show_Alert('success','Saved','Data Saved successfully')
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
            else
            {
                $send_error=validation_errors();
                $rvar=array(
                'update' =>0,
                'error_text'=>$send_error
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
            }
		
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
	
	public function list_pathtest_bytype()
	{
		$tid=$this->input->post('itype_idv');
		$insid=$this->input->post('insurance_id');
		
		$sql="select id,Concat(idesc,' : [',amount1,']',if(hc_insurance_id>0,'Ins.','Cash')) as sdesc from v_hc_items_with_insurance where itype='".$tid."' and hc_insurance_id in (0,".$insid.")";
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
			$sql="select * from v_hc_items_with_insurance where id=".$this->input->post('itype_name_id');
			$query = $this->db->query($sql);
			$data['itemlist']= $query->result();
			
			$amount_value=$this->input->post('input_qty')*$data['itemlist'][0]->amount;
			$amount_value2=$this->input->post('input_qty')*$data['itemlist'][0]->amount1;
			
			$data['insert_invoice_item'] = array( 
				'inv_master_id' => $this->input->post('lab_invoice_id'),
				'item_type' => $this->input->post('itype_idv'),
				'item_id' => $this->input->post('itype_name_id'),				
				'item_name' => $data['itemlist'][0]->idesc,
				'item_rate' => $data['itemlist'][0]->amount,
				'item_added_date' => str_to_MysqlDate(date('d/m/Y')),
				'item_qty' => $this->input->post('input_qty'),
				'item_amount' => $amount_value,
				'item_amount1' => $amount_value2
				);
				
			$inser_id=$this->invoice_M->addinvoiceitem( $data['insert_invoice_item']);
		
		}else{
			
			$this->db->delete("invoice_item", "id=".$this->input->post('itemid'));
		}
		
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(if(item_amount1>0,item_amount1,item_amount)) as Gtotal from invoice_item where inv_master_id=".$this->input->post('lab_invoice_id');
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();

		echo '<table class="table table-striped ">
			<tr>
				<th style="width: 10px">#</th>
				<th>Type</th>
				<th>Test Name</th>
				<th>Rate</th>
				<th>Qty</th>
				<th>Cash Amt</th>
				<th>INS Amount</th>
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
			echo '<td>'.$row->item_amount1.'</td>';
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