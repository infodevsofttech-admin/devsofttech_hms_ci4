<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class DotPrint extends MY_Controller {
  
    function __construct()
    {
        parent::__construct();
       
    }
	
	function dotprint_invoice($invoice_id)
	{
		$sql="select i.*,t.group_desc from invoice_item i join hc_item_type t on i.item_type=t.itype_id where i.inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$invoiceDetails= $query->result();
		
		$sql="select sum(item_amount) as Gtotal from invoice_item where inv_master_id=".$invoice_id;
		$query = $this->db->query($sql);
		$invoiceGtotal= $query->result();
		
		$sql="select *,(case payment_mode when  1 Then 'Cash' when  2 then 'Bank Card' when 3 then 'IPD Credit' when 4 then 'Org. Credit' else 'Pending' end) as Payment_type_str  from invoice_master where id=".$invoice_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select *,if(gender=1,'Male','FeMale') as xgender,if(age is null,Get_Age(dob),Concat(age,' ',age_in))   AS age from patient_master where id=".$invoice_master[0]->attach_id;
        $query = $this->db->query($sql);
        $patient_master= $query->result();
		
		$sql="select *,(case payment_mode when 1 then 'Cash' when 2 then 'Bank Card' when 3 then 'Return Cash ' when 4 then 'Bank Return' else 'Other' end) as Payment_type_str from payment_history where payof_type=2 and payof_id=".$invoice_id;
		$query = $this->db->query($sql);
        $payment_history= $query->result();
				
		$PrintData='              SOBAN SINGH JEEN BASE HOSPITAL , HALDWANI'.PHP_EOL;
		$PrintData.='------------------------------------------------------------------------------'.PHP_EOL;
		
		$PatientID=  'Patient ID   : '.$patient_master[0]->p_code;
		$PatientName='Patient Name : '.$patient_master[0]->p_fname;
		$PatientGA='Gender / Age : '.$patient_master[0]->xgender.'/'.$patient_master[0]->age;
		$InoviceID=  'Invoice ID   : '.$invoice_master[0]->invoice_code;
		
		
		$PrintData.=str_pad($PatientID,35,' ',STR_PAD_RIGHT);
		$PrintData.=str_pad($PatientName,35,' ',STR_PAD_RIGHT).PHP_EOL;		
		
		$PrintData.=str_pad($InoviceID,35,' ',STR_PAD_RIGHT);
		$PrintData.=str_pad($PatientGA,35,' ',STR_PAD_RIGHT).PHP_EOL;	
		
		$PrintData.='------------------------------------------------------------------------------'.PHP_EOL;
		$PrintData.=' '.PHP_EOL;
		$PrintData.='#  LAB         Charge Name                            Rate     Qty    Amount '.PHP_EOL;
		$PrintData.='------------------------------------------------------------------------------'.PHP_EOL;
		
		$srno=1;
		foreach($invoiceDetails as $row)
		{ 
			$line='';
			$line.=str_pad($srno,3,' ',STR_PAD_RIGHT);
			$line.=str_pad($row->group_desc,12,' ',STR_PAD_RIGHT);
			$line.=str_pad($row->item_name,35,' ',STR_PAD_RIGHT);
			$line.=str_pad($row->item_rate,10,' ',STR_PAD_LEFT);
			$line.=str_pad($row->item_qty,7,' ',STR_PAD_LEFT);
			$line.=str_pad($row->item_amount,10,' ',STR_PAD_LEFT);
			
			$srno=$srno+1;
			$PrintData.=$line.PHP_EOL;
		}
		
		$PrintData.='------------------------------------------------------------------------------'.PHP_EOL;
		
		$PrintData.=str_pad('Gross Total : '.$invoiceGtotal[0]->Gtotal,77,' ',STR_PAD_LEFT).PHP_EOL;
		
		if($invoice_master[0]->discount_amount>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Gross Total : '.$invoice_master[0]->discount_amount,77,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		if($invoice_master[0]->ipd_id<1) {
			
			$PrintData.=str_pad('Received Amt.:'.$invoice_master[0]->payment_part_received,26,' ',STR_PAD_RIGHT);
			$PrintData.=str_pad('Balance Amt : '.$invoice_master[0]->payment_part_balance,26,' ',STR_PAD_RIGHT);
			$PrintData.=str_pad('Net Amt : '.$invoice_master[0]->net_amount,26,' ',STR_PAD_LEFT).PHP_EOL;
		}
		
		if($invoice_master[0]->correction_amount>0)
		{
			$PrintData.=str_pad('Correction : '.$invoice_master[0]->correction_remark,44,' ',STR_PAD_RIGHT);
			$PrintData.=str_pad('Correction Amt : '.$invoice_master[0]->correction_amount,22,' ',STR_PAD_Left);
			
			$PrintData.=str_pad('Final Amount : '.$invoice_master[0]->correction_net_amount,77,' ',STR_PAD_LEFT).PHP_EOL;
		}
		
	
		$folder_name='uploads/printtemp';
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$output_dir = $folder_name;
		$filename=date('Ymdhis').$invoice_master[0]->invoice_code.'.TXT';
		$PrintData.=PHP_EOL;
		if($invoice_master[0]->payment_mode>2) {
			$PrintData.='Payment Details : '.$invoice_master[0]->Payment_type_str.' '.PHP_EOL;
		}else{
			$PrintData.='Payment Details :';
			
			foreach($payment_history as $row)
				{ 
					$PrintData.='['.$row->id.':'.$row->Payment_type_str.':'.$row->amount.']/';
				}
			$PrintData.=' '.PHP_EOL .PHP_EOL .PHP_EOL ;
		}
		
		$PrintData.=str_pad('Prepared By : '.$invoice_master[0]->prepared_by,40,' ',STR_PAD_RIGHT).' ';
		$PrintData.=str_pad('  Signature  ',35,' ',STR_PAD_LEFT).' '.PHP_EOL;
		
		$myfile = fopen($output_dir.'/'.$filename, "w") or die("Unable to open file!");
		fwrite($myfile, $PrintData);
		fclose($myfile);
		
		$this->load->model('DotPrint_M');
		
		$insertdata = array( 
		'created_by'=> $user_id,
		'file_type'=> 'TEXT',
		'file_name'=> $filename,
		'file_location'=>$folder_name
		);
		
		$inser_id=$this->DotPrint_M->insert( $insertdata);
		
		echo 'File Send To DotMatrix Printer /  File ID : '.$inser_id;
	}
	
	function read_dot_date($userid)
	{
		$user_id=str_replace('S',',',$userid);
		
		$sql="select id,file_type,file_name,file_location from dot_printer where print_status=0 and created_by in (".$user_id.")";
        $query = $this->db->query($sql);
        $file_list= $query->result();
		
		header('Content-type: application/json');
		echo json_encode($file_list);
	}
	
	function printdone_dot($fileid,$print_by)
	{
		$this->load->model('DotPrint_M');
		
		$updatedata = array( 
		'print_status'=> '1',
		'print_by'=> $print_by
		);
		
		$updatestatus=$this->DotPrint_M->update( $updatedata,$fileid);
		echo $updatestatus;
	}
	
}