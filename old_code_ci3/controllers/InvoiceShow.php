<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class InvoiceShow extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}

	public function invoice_med($inv_id)
	{
		$sql="select * from invoice_med_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();

		$sql="select * from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_items']= $query->result();
		
		$sql="select sum(tamount) as Gtotal,sum(amount) as amt,sum(vamount) as vamt from inv_med_item where inv_med_id=".$inv_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
			
		$sql="select * from  ipd_master  where ipd_status=0 and p_id=".$data['invoice_master'][0]->patient_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
		
		$sql="select * from organization_case_master where status=0 and  p_id=".$data['invoice_master'][0]->patient_id;
		$query = $this->db->query($sql);
        $data['orgcase']= $query->result();
		
		$this->load->view('invoiceshow/invoice_medical',$data);
	}
	
	
	public function invoice_charges($invoice_id)
	{
		$sql="select i.*,t.desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceDetails']= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoiceGtotal']= $query->result();
		
		$sql="select *,(case payment_status when  1 Then 'cash' when  2 then 'Bank Card' when 3 then 'Org Credit' else 'Pending' end) as Payment_type_str  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$data['invoice_master']= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age) AS age from patient_master where id=".$data['invoice_master'][0]->attach_id;
        $query = $this->db->query($sql);
        $data['patient_master']= $query->result();

		$this->load->view('invoiceshow/invoice_charges',$data);
	}
	
	
	
}