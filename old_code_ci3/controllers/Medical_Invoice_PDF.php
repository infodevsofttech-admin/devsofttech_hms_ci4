<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Medical_Invoice_PDF extends CI_Controller {
  
    function __construct()
    {
        parent::__construct();
        $this->load->library("Pdf");
		
    }
	
	//Sale Report 
	
	public function Report_1()
	{
		$this->load->view('Medical/Report/Med_Report_1');
	}
	
	public function Invoice_PDF($sale_date_range,$output=0)
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
		
		
		
	}
  
	
  
	
}