<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
	{
		$sql='select o.insurance_id,
				if(o.insurance_id>1,i.ins_company_name,\'Direct\') as Ins_name,
				if(o.insurance_id>1,i.short_name,\'Direct\') as Ins_short_name,
				count(o.opd_id) as No_OPD
				from opd_master o left join hc_insurance i on o.insurance_id=i.id 
				where date(o.apointment_date) =curdate() and   (payment_mode>0 or running_opd=1)
				group by o.insurance_id';

		$query = $this->db->query($sql);
        $data['opd_count']= $query->result();
		
		$sql='select count(*) as T_opd
				from opd_master o left join hc_insurance i on o.insurance_id=i.id 
				where date(o.apointment_date) =curdate() and  (payment_mode>0 or running_opd=1)	';

		$query = $this->db->query($sql);
        $data['opd_count_total']= $query->result();
		
		$sql="select v.doc_name,v.doc_list,count(v.id) as No_patient,
				sum(if(v.ipd_status=0,1,0)) as Admit_Current,
				sum(if(v.discharge_date=curdate(),1,0)) as Discharge_Day,
				sum(if(v.register_date=curdate(),1,0)) as Admit_Day
				from v_ipd_list v
				where curdate() between v.register_date and if(v.discharge_date is null,sysdate(),v.discharge_date)
				group by v.doc_list
				order by v.doc_name";
		$query = $this->db->query($sql);
        $data['ipd_doc_total']= $query->result();
		
		
		$sql="select d.id as doc_id, d.p_fname,count(DISTINCT o.opd_id) as No_opd, 
				sum(if(o.payment_mode=4,1,0)) as Org_OPD,
				sum(if(o.payment_mode IN (1,2),1,0)) as Direct_OPD,
				MOD(d.id,5) as color_code
				from (doctor_master d 
				join opd_master o on d.id=o.doc_id and o.apointment_date =CURDATE())
			group by d.id
			having count(DISTINCT o.opd_id)>0
			order by  d.p_fname";
		$query = $this->db->query($sql);
        $data['opd_doc_wise']= $query->result();
		
		if (stristr(PHP_OS, 'win')) {
            $load = "3";
			$memory_usage="4";
			$disksize="";
			$disksizetotal="";
			$bytesPer="";
        } else {
			//Disk Size Status
			$bytes = disk_free_space(".");
			$bytestotal=disk_total_space(".");
			
			$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
			$base = 1024;
			$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
			
			$bytesPer=round($bytes*100/$bytestotal,2);
			
			$disksize= sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class] . '<br />';
			$disksizetotal= sprintf('%1.2f' , $bytestotal / pow($base,$class)) . ' ' . $si_prefix[$class] . '<br />';
			
			
			$free = shell_exec('free');
			$free = (string)trim($free);
			$free_arr = explode("\n", $free);
			$mem = explode(" ", $free_arr[1]);
			$mem = array_filter($mem);
			$mem = array_merge($mem);
			$memory_usage = round($mem[2]/$mem[1]*100,2);
	   
            $sys_load = sys_getloadavg();
            $load = $sys_load[0];

        }
		
		$data['load'] = $load;
		$data['mem'] = $memory_usage ;
		$data['dsize'] = $disksize ;
		$data['dsizetot'] = $disksizetotal ;
		$data['bytesPer'] = $bytesPer ;
		
		$sql="select count(*) as no_rec from ipd_master where ipd_status=0";
		$query = $this->db->query($sql);
        $data['ipd_count']= $query->result();
		
		$data['no_admit_ipd']=$data['ipd_count'][0]->no_rec;
		
		$sql="select count(*) as no_rec from ipd_master where ipd_status=1 and discharge_date=Date(sysdate())";
		$query = $this->db->query($sql);
        $data['ipd_count']= $query->result();
		
		$data['no_admit_ipd_discharge']=$data['ipd_count'][0]->no_rec;
		
		$sql="select count(*) as no_rec from ipd_master where  register_date=Date(sysdate())";
		$query = $this->db->query($sql);
        $data['ipd_count']= $query->result();
		
		$data['no_admit_ipd_admit']=$data['ipd_count'][0]->no_rec;
		
		$sql="select count(*) as no_rec from ipd_master where  ipd_status=0 and case_id>0";
		$query = $this->db->query($sql);
        $data['ipd_count']= $query->result();
		
		$data['no_admit_ipd_org']=$data['ipd_count'][0]->no_rec;
		
		$sql="select o.insurance_id,
				i.ins_company_name ,
				i.short_name,
				count(o.id) as No_IPD
				from (organization_case_master o join hc_insurance i on o.insurance_id=i.id)
				join ipd_master m  on o.id=m.case_id
				where ipd_status=0 and m.case_id>0 and o.insurance_id>0
				group by o.insurance_id";
		$query = $this->db->query($sql);
        $data['ipd_org_list']= $query->result();	

        $this->load->view('dashboard/dash1',$data);
    }
		
	
}