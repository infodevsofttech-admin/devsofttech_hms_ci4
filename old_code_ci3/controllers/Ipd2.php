<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ipd2 extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	
	private function Ipd_invoice_text($ipdno)
	{
		$sql="select * from ipd_master where id=".$ipdno;
		$query = $this->db->query($sql);
		$ipdmaster= $query->result();
		
		$pno=$ipdmaster[0]->p_id;
		$case_id=$ipdmaster[0]->case_id;
		
		$sql="select * from v_ipd_list where id=".$ipdno;
		$query = $this->db->query($sql);
		$ipd_list= $query->result();

		$sql="select *,if(gender=1,'Male','FeMale') as xgender,if(age is null,
		Get_Age(dob),Concat(age,' ',age_in))   AS age 
		from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
        $person_info= $query->result();
		
		
		$sql="select * from  ipd_package 
		where  ipd_id=".$ipdno;
		$query = $this->db->query($sql);
		$ipd_package= $query->result();

		

		//Check Items in Package
		$No_Pack_items=0;

		$sql1=	'Select count(*) as no_rec from ipd_invoice_item i 
				where i.package_id>0 and i.ipd_id='.$ipdno;
		
		$sql2=	'Select count(*) as no_rec from invoice_med_master 
				Where ipd_credit=1 and ipd_credit_type=0 and ipd_id = '.$ipdno;
		
		$sql3=	'Select count(*) as no_rec from v_ipd_invoice b 
				where b.ipd_id='.$ipdno.'  and ipd_include=0';

		$query = $this->db->query($sql1);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		$query = $this->db->query($sql2);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		$query = $this->db->query($sql3);
		$Package_Items= $query->result();
		$No_Pack_items+=$Package_Items[0]->no_rec;

		if(count($ipd_package)>0)
		{
			$sql="select t.group_desc,i.*,sum(i.item_amount) as xAmount
			from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
			where i.package_id=0 and i.ipd_id=".$ipdno."
			group by i.item_type,i.id order by i.item_type";
			$query = $this->db->query($sql);
			$ipd_invoice_item= $query->result();
		}else{
			$sql="select t.group_desc,i.*,sum(i.item_amount) as xAmount
			from ipd_invoice_item i join ipd_item_type t on i.item_type=t.itype_id 
			where  i.ipd_id=".$ipdno."
			group by i.item_type,i.id order by i.item_type";
			$query = $this->db->query($sql);
			$ipd_invoice_item= $query->result();
		}

		$sql="select * 
		from invoice_med_master 
		where  ipd_credit=1 and ipd_credit_type=1 
		and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$inv_med_list= $query->result();

		$sql="select b.ipd_id AS ipd_id,b.Charge_type AS Charge_type,b.idesc AS idesc,
			b.item_rate AS item_rate,b.orgcode AS orgcode,sum(b.item_qty) AS no_qty,sum(b.Amount) AS amount 
		from v_ipd_invoice b where b.ipd_id=".$ipdno."  and ipd_include=1
		group by b.ipd_id,b.item_id,b.item_rate order by b.Charge_type ";
		
		$query = $this->db->query($sql);
		$showinvoice= $query->result();
		
		if($ipdmaster[0]->case_id>0)
		{
			$sql="select * from organization_case_master 
			where id='".$ipdmaster[0]->case_id."'";
			$query = $this->db->query($sql);
			$orgcase= $query->result();

			$ins_comp_id=$orgcase[0]->insurance_id;
			$inc_card_id=$orgcase[0]->insurance_card_id;

			$sql="select * from hc_insurance_card where id='".$inc_card_id."'";
			$query = $this->db->query($sql);
			$hc_insurance_card= $query->result();

			$sql="select * from hc_insurance where id='".$ins_comp_id."'";
			$query = $this->db->query($sql);
			$insurance= $query->result();
		}
	
		
		$sql="select sum(net_amount) as tnet_amount 
		from invoice_med_master 
		where  ipd_credit=1 and ipd_credit_type=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$inv_med_total= $query->result();
		
		$total_med=$inv_med_total[0]->tnet_amount;
		
		$sql="select sum(total_amount) as tnet_amount from invoice_master 
		where  payment_status=1 and ipd_include=1 and ipd_id = ".$ipdno;
		$query = $this->db->query($sql);
		$inv_charge_total= $query->result();

		$sql="SELECT h.*,DATE_FORMAT(h.payment_date,'%d-%m-%Y') as pay_date_str,
		concat(if(h.credit_debit=0,'','Return '),if(h.payment_mode=1,'CASH','BANK')) as pay_mode
		from payment_history h    where h.payof_type=4 AND h.payof_id='".$ipdno."' ";
        $query = $this->db->query($sql);
        $ipd_payment= $query->result();
	
				
		$PrintData='IPD ID : '.$ipdmaster[0]->ipd_code.' / PCode : '.$person_info[0]->p_code. ' / PName : '.$person_info[0]->p_fname .' / Age : '.$person_info[0]->age .PHP_EOL;
		$PrintData.='----------------------------------------------------------------------------------------------------------'.PHP_EOL;
		
		$PrintData.=str_pad('Doctor Name : '. $ipd_list[0]->doc_name,35,' ',STR_PAD_RIGHT);
		
		if($ipdmaster[0]->case_id>0) {
		$PrintData.=str_pad('Organisation : '.$insurance[0]->ins_company_name,35,' ',STR_PAD_RIGHT);		
		}
		$PrintData.=PHP_EOL;
		
		$PrintData.=str_pad('Admit Date  : '.$ipd_list[0]->str_register_date,35,' ',STR_PAD_RIGHT);
		$PrintData.=str_pad('Discharge Date : '.$ipd_list[0]->str_discharge_date,35,' ',STR_PAD_RIGHT).PHP_EOL;	
	
		$PrintData.='----------------------------------------------------------------------------------------------------------'.PHP_EOL;
		$PrintData.=' '.PHP_EOL;
		$PrintData.='#  Description                                                       Rate            Qty        Amount '.PHP_EOL;
		$PrintData.='----------------------------------------------------------------------------------------------------------'.PHP_EOL;
		
		$srno=1;
		$headdesc='';
		
		if(Count($ipd_package)>0){
			$PrintData.='Package'.PHP_EOL;
			$headdesc='Package';
			for($i=0;$i<Count($ipd_package);$i++)
			{ 
				$line='';
				$line.=str_pad($srno,3,' ',STR_PAD_RIGHT);
				
				$line.=str_pad($ipd_package[$i]->package_name,60,' ',STR_PAD_RIGHT);
				$line.=str_pad('',10,' ',STR_PAD_LEFT);
				$line.=str_pad('',15,' ',STR_PAD_LEFT);
				$line.=str_pad($ipd_package[$i]->package_Amount,15,' ',STR_PAD_LEFT);
				$srno=$srno+1;
				$PrintData.=$line.PHP_EOL;
			}
		}

		for($i=0;$i<Count($ipd_invoice_item);$i++)
		{
			if($headdesc!=$ipd_invoice_item[$i]->group_desc)
			{
				$PrintData.=''. $ipd_invoice_item[$i]->group_desc .''.PHP_EOL;
				$headdesc=$ipd_invoice_item[$i]->group_desc;
			}	
				
			$line='';
			$line.=str_pad($srno,3,' ',STR_PAD_RIGHT);
			
			$line.=str_pad($ipd_invoice_item[$i]->item_name.' '.$ipd_invoice_item[$i]->comment,60,' ',STR_PAD_RIGHT);
			$line.=str_pad($ipd_invoice_item[$i]->item_qty,10,' ',STR_PAD_LEFT);
			$line.=str_pad($ipd_invoice_item[$i]->item_rate,15,' ',STR_PAD_LEFT);
			$line.=str_pad($ipd_invoice_item[$i]->item_amount,15,' ',STR_PAD_LEFT);
			$srno=$srno+1;
			$PrintData.=$line.PHP_EOL;

		}

		for($i=0;$i<Count($showinvoice);$i++)
		{ 
				if($headdesc!=$showinvoice[$i]->Charge_type)
				{
					$PrintData.=''. $showinvoice[$i]->Charge_type .''.PHP_EOL;
					$headdesc=$showinvoice[$i]->Charge_type;
				}	
				
				$line='';
				$line.=str_pad($srno,3,' ',STR_PAD_RIGHT);
				
				$line.=str_pad($showinvoice[$i]->idesc,60,' ',STR_PAD_RIGHT);
				$line.=str_pad($showinvoice[$i]->no_qty,10,' ',STR_PAD_LEFT);
				$line.=str_pad($showinvoice[$i]->item_rate,15,' ',STR_PAD_LEFT);
				$line.=str_pad($showinvoice[$i]->amount,15,' ',STR_PAD_LEFT);
				$srno=$srno+1;
				$PrintData.=$line.PHP_EOL;
		}
		
		if(count($inv_med_list)>0)
		{
			$PrintData.='----------------------------  Medical Bill ----------------------------------------------------------'.PHP_EOL;
		
			$med_total=0.00;
			foreach($inv_med_list as $row)
			{
				$line='';
				$line.=str_pad($srno,3,' ',STR_PAD_RIGHT);
				$line.=str_pad($row->inv_med_code,40,' ',STR_PAD_RIGHT);
				$line.=str_pad($row->net_amount,25,' ',STR_PAD_RIGHT);
				
				$srno=$srno+1;
				$med_total +=$row->net_amount;
				$PrintData.=$line.PHP_EOL;
			}
		}
		
		$PrintData.='----------------------------------------------------------------------------------------------------------'.PHP_EOL;
		$PrintData.=str_pad('Gross Total : '.$ipdmaster[0]->gross_amount,100,' ',STR_PAD_LEFT).PHP_EOL;
		
		if($ipdmaster[0]->chargeamount1>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Charge : '.$ipdmaster[0]->charge1,90,' ',STR_PAD_RIGHT).PHP_EOL ;
			$PrintData.=str_pad($ipdmaster[0]->chargeamount1,10,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		if($ipdmaster[0]->chargeamount2>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Charge : '.$ipdmaster[0]->charge2,90,' ',STR_PAD_RIGHT).PHP_EOL ;
			$PrintData.=str_pad($ipdmaster[0]->chargeamount2,10,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		if($ipdmaster[0]->Discount>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Deduction : '.$ipdmaster[0]->Discount_Remark,90,' ',STR_PAD_RIGHT).PHP_EOL ;
			$PrintData.=str_pad($ipdmaster[0]->Discount,10,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		if($ipdmaster[0]->Discount2>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Deduction : '.$ipdmaster[0]->Discount_Remark2,90,' ',STR_PAD_RIGHT).PHP_EOL ;
			$PrintData.=str_pad($ipdmaster[0]->Discount2,10,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		if($ipdmaster[0]->Discount3>0)
		{
			$PrintData.=PHP_EOL;
			$PrintData.=str_pad('Deduction : '.$ipdmaster[0]->Discount_Remark3,90,' ',STR_PAD_RIGHT).PHP_EOL ;
			$PrintData.=str_pad($ipdmaster[0]->Discount3,10,' ',STR_PAD_LEFT).PHP_EOL ;
		}
		
		$PrintData.='----------------------------------------------------------------------------------------------------------'.PHP_EOL;
		$PrintData.=str_pad('Net Amount :  '.$ipdmaster[0]->net_amount,100,' ',STR_PAD_LEFT).PHP_EOL;
		$PrintData.=str_pad('Payment Recd : '.$ipdmaster[0]->total_paid_amount,100,' ',STR_PAD_LEFT).PHP_EOL;
		$PrintData.=str_pad('Balance : '.$ipdmaster[0]->balance_amount,100,' ',STR_PAD_LEFT).PHP_EOL;
		$PrintData.=str_pad('Balance Remark : '.$ipdmaster[0]->discharge_balance_remark,70,' ',STR_PAD_RIGHT);
		$PrintData.=str_pad('Print Time  : '.date('d-m-Y h:i:s'),30,' ',STR_PAD_LEFT).PHP_EOL;
		
		$PrintData.='xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'.PHP_EOL .PHP_EOL .PHP_EOL;
		
		return $PrintData;
	}

	

	function Ipd_invoice_print_list($daterange,$doc_name_id,$ipd_type,$ipd_status,$ipd_date,$output=0)
	{
		$rangeArray = explode("S",$daterange);
		$minRange = $rangeArray[0];
		$maxRange = $rangeArray[1];
		
		$where =" 1=1 " ;
		
		if($ipd_status>-1)
		{
			$where.=" and  p.ipd_status=".$ipd_status;
		}
		
		if($ipd_date==0)
		{
			$where.="   and  p.register_date between '".$minRange."' and '".$maxRange."' " ;
		}elseif($ipd_date==1)
		{
			$where.="   and  p.discharge_date between '".$minRange."' and '".$maxRange."' " ;
		}
		
		if($doc_name_id>0)
		{
			$where.=" and FIND_IN_SET('".$doc_name_id."',doc_list) ";
		}
		
		if($ipd_type==6){
			$where.=" and   (group_ins is null or  group_ins=6)";
		}elseif($ipd_type==0){
			$where.="  ";
		}else	{
			$where.=" and  group_ins=$ipd_type ";
		}
	
	
		$sql="select p.id,p.ipd_code,p.p_fname,Date_Format(p.register_date,'%d-%m-%Y') as str_register_date,
			Date_Format(p.discharge_date,'%d-%m-%Y') as str_discharge_date,p.admit_type,p.doc_name,p.Bed_Desc,
			charge_amount,paid_amount
			from v_ipd_list p  where ".$where."	group by p.id,p.doc_list  order by id";

		$query = $this->db->query($sql);
        $dischargelist= $query->result();
		
		$PrintData='';
		
		foreach($dischargelist as $row)
		{
			$PrintData.=$this->Ipd_invoice_text($row->id);
		}
		
		echo '<pre>'.$PrintData.'</pre>';
	
	}
	
}