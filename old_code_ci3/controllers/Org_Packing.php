<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Org_Packing extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
    
    public function OrgPacking()
    {
		$this->load->view('Org/Org_packing');
	}

	public function Search_packing()
	{
		$sdata=trim($this->input->post('txtsearch'));

		if(trim($sdata)=='')
		{
			$sql="SELECT p.id,date_format(p.date_of_create,'%d-%m-%Y') AS d_o_c,
			p.label_no,p.files_status,COUNT(c.id) AS No_Rec,
			if(p.org_type=0,'OPD','IPD') as list_type
			FROM org_packing p left JOIN organization_case_master c ON p.id=c.packing_id
			GROUP BY p.id
			ORDER BY p.date_of_create desc limit 50";
		}else{

			$numric_condition="";

			if(is_numeric($sdata))
			{
				$numric_condition=" OR p.Invoice_no=".$sdata ;
			}

			$sql="SELECT p.id,date_format(p.date_of_create,'%d-%m-%Y') AS d_o_c,
			p.label_no,p.files_status,COUNT(c.id) AS No_Rec,
			if(p.org_type=0,'OPD','IPD') as list_type
			FROM org_packing p left JOIN organization_case_master c ON p.id=c.packing_id
			where (p.label_no LIKE '%".$sdata."%'  ".$numric_condition." )
			GROUP BY p.id
			ORDER BY p.date_of_create desc limit 50";
		}

		$query = $this->db->query($sql);
        $data['packing_list']= $query->result();

		$this->load->view('Org/Search_result',$data);

	}

	public function PackingNew()
    {
		$this->load->view('Org/new_Org_Packing');
	
	}

	public function PackingEdit($id)
    {
		$data['id']=$id;

		$sql="select * from org_packing where id=$id";
		$query = $this->db->query($sql);
		$data['org_packing']= $query->result();

		$this->load->view('Org/edit_Org_Packing',$data);
	
	}

	public function CreatePackNew()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_packingcode',
                    'label' => 'Invoice No',
                    'rules' => 'required|min_length[3]|max_length[30]'
                )
            );

         $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			
			$invoice_code=$this->input->post('input_packingcode');
			$org_type=$this->input->post('cbo_billtype');
			$datepicker_packing=$this->input->post('datepicker_packing');

			if(is_numeric($invoice_code))
				$where=" where  label_no=".$invoice_code ;
			else
				$where=" where  label_no='".$invoice_code."'";

			$sql="select count(*) as no_rec from org_packing ".$where;
			$query = $this->db->query($sql);
			$chk_rec= $query->result();

			if($chk_rec[0]->no_rec==0)
			{
				$Udata = array( 
					'date_of_create'=> str_to_MysqlDate($datepicker_packing),
					'label_no'=> $invoice_code,
					'org_type'=>$org_type
				);
			
				$this->load->model('Ocasemaster_M');
			
				$inser_id=$this->Ocasemaster_M->insert_org_pack($Udata);
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

	public function EditPack()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
         
         $FormRules = array(
                array(
                    'field' => 'input_packingcode',
                    'label' => 'Invoice No',
                    'rules' => 'required|min_length[3]|max_length[30]'
                )
            );

         $this->form_validation->set_rules($FormRules);
		 
		if ($this->form_validation->run() == TRUE)
        {
			
			$invoice_code=$this->input->post('input_packingcode');
			$org_type=$this->input->post('cbo_billtype');
			$datepicker_packing=$this->input->post('datepicker_packing');
			$id=$this->input->post('packing_id');

			if(is_numeric($invoice_code))
				$where="   label_no=".$invoice_code ;
			else
				$where="   label_no='".$invoice_code."'";

			$sql="select count(*) as no_rec from org_packing where id<>$id and ".$where;
			$query = $this->db->query($sql);
			$chk_rec= $query->result();

			if($chk_rec[0]->no_rec==0)
			{
				$Udata = array( 
					'date_of_create'=> str_to_MysqlDate($datepicker_packing),
					'label_no'=> $invoice_code,
					'org_type'=>$org_type
				);
			
				$this->load->model('Ocasemaster_M');
			
				$inser_id=$this->Ocasemaster_M->update_org_pack($Udata,$id);
				$send_msg="Update Successfully";

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

	public function PackNewEdit($id)
	{
		$sql="select *
			from org_packing
			where id=".$id;
		$query = $this->db->query($sql);
		$data['org_packing']= $query->result();	

		$sql="SELECT o.*,o.case_id_code,o.insurance_company_name,
			p.p_code,p.p_fname,p.age,o.insurance_no,o.insurance_no_1,
			if(o.case_type=0,'OPD','IPD') as IPD_OPD
			FROM organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id
			WHERE o.packing_id=$id
			ORDER BY o.packing_short";
		$query = $this->db->query($sql);
		$data['org_packing_list']= $query->result();

		$data['packing_id']=$id;

		$data['org_packing_list']=$this->load->view('Org/org_packing_list',$data,true);

		$this->load->view('Org/Org_item_Edit',$data);
	}

	public function PackMasterEdit($id)
	{
		$sql="select p.*,s.name_supplier from purchase_invoice p join med_supplier s on p.sid=s.sid where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_master_data']= $query->result();

		$sql="select * from purchase_invoice_item where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
		$data['purchase_item']= $query->result();
	
		$this->load->view('Medical/Purchase/purchase_invoice_master_edit',$data);

	}

	public function Pack_list($packing_id)
	{
		$sql="SELECT o.*,o.case_id_code,o.insurance_company_name,
			p.p_code,p.p_fname,p.age,o.insurance_no,o.insurance_no_1,
			if(o.case_type=0,'OPD','IPD') as IPD_OPD
			FROM organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id
			WHERE o.packing_id=$packing_id
			ORDER BY o.packing_short";
		$query = $this->db->query($sql);
		$data['org_packing_list']= $query->result();

		$data['packing_id']=$packing_id;

		$this->load->view('Org/org_packing_list',$data);

	}
   

	public function Add_list($org_id,$packing_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' ['.date('d-m-Y h:i:s').']';

		$sql="SELECT *
		FROM organization_case_master o 
		WHERE o.packing_id=$packing_id and id=$org_id";
		$query = $this->db->query($sql);
		$org_check= $query->result();
	

		$sql="SELECT Max(o.packing_short) as m_short
			FROM organization_case_master o 
			WHERE o.packing_id=$packing_id
			ORDER BY o.packing_short";
		$query = $this->db->query($sql);
		$org_short_max= $query->result();

		$max_short_no=$org_short_max[0]->m_short;

		$max_short_no=$max_short_no+1;

		$dataupdate = array( 
				'packing_id' => $packing_id,
				'packing_update_by'=> $user_name,
				'packing_short' => $max_short_no		
			);
		
		$this->load->model('Ocasemaster_M');
	
		if(count($org_check)>0)
		{
			$send_msg="Already Added";
		}else{
			$this->Ocasemaster_M->update($dataupdate,$org_id);
			$send_msg="Added Successfully";
		}

		$sql="SELECT o.*,o.case_id_code,o.insurance_company_name,
			p.p_code,p.p_fname,p.age,o.insurance_no,o.insurance_no_1,
			if(o.case_type=0,'OPD','IPD') as IPD_OPD
			FROM organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id
			WHERE o.packing_id=$packing_id
			ORDER BY o.packing_short";
		$query = $this->db->query($sql);
		$data['org_packing_list']= $query->result();

		$data['packing_id']=$packing_id;

		$this->load->view('Org/org_packing_list',$data);
	}

	public function remove_item_packing($org_id,$packing_id)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name.' ['.date('d-m-Y h:i:s').']';

		$dataupdate = array( 
			'packing_id' => 0,
			'packing_update_by'=> $user_name,
			'packing_short' => 0		
		);
	
		$this->load->model('Ocasemaster_M');

		$this->Ocasemaster_M->update($dataupdate,$org_id);
		$send_msg="Remove Successfully";

		$sql="SELECT o.*,o.case_id_code,o.insurance_company_name,
			p.p_code,p.p_fname,p.age,o.insurance_no,o.insurance_no_1,
			if(o.case_type=0,'OPD','IPD') as IPD_OPD
			FROM organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id
			WHERE o.packing_id=$packing_id
			ORDER BY o.packing_short";
		$query = $this->db->query($sql);
		$data['org_packing_list']= $query->result();

		$data['packing_id']=$packing_id;

		$this->load->view('Org/org_packing_list',$data);
	}

	public function  Print_List($Packing_id,$output=0)
	{
		$sql="SELECT *
			FROM organization_case_master
			WHERE packing_id=$Packing_id";
		$query = $this->db->query($sql);
		$org_master= $query->result();

		if($org_master[0]->case_type==0)
		{
			redirect('/Org_Packing/Print_opd_List/'.$Packing_id.'/'.$output);
		}else{
			redirect('/Org_Packing/Print_ipd_List/'.$Packing_id.'/'.$output);
		}
	}

	function Print_opd_List($Packing_id,$output=0)
	{
		$sql="SELECT org.*, group_concat(opd.doc_name) AS Doc_name,
			p.p_code,p.p_fname,p.age,
			if(org.case_type=0,'OPD','IPD') as IPD_OPD,
			(org.inv_opd_amt+org.inv_opd_charge_amt+inv_opd_med_amt) AS claim_amt,
			if(opd.opd_id IS NULL,Date_Format(MIN(i.inv_date),'%d-%m-%Y'),Date_Format(MIN(opd.apointment_date),'%d-%m-%Y')) AS opd_date
			FROM ((organization_case_master org JOIN patient_master_exten p ON org.p_id=p.id)
			LEFT JOIN opd_master opd ON org.id=opd.insurance_case_id)
			LEFT JOIN invoice_master i ON org.id=i.insurance_case_id
			WHERE org.packing_id=$Packing_id
			GROUP BY org.id
			ORDER BY org.packing_short";
		$query = $this->db->query($sql);
		$org_packing_list= $query->result();

		$data['Packing_id']=$Packing_id;
		
		$content="<style>@page {

						sheet-size: A4-L; 
						
						margin-top: 0.5cm;
						margin-bottom: 0.5cm;
						margin-left: 0.5cm;
						margin-right: 0.5cm;
						
				}
				</style>";
		 
		$content.='<table border="1" width="100%" cellpadding="3">';

		$content.='<thead>
						<tr>
							<th>Set No.</th>
							<th>Sr.No</th>
							<th>Claim ID</th>
							<th>Bill No.</th>
							<th>Name of Patient</th>
							<th>OPD Date</th>
							<th>Consultant Name</th>
							<th>Sub. Date</th>
							<th>Amount Claimed</th>
							<th>Passed Amt.</th>
							<th>Ded. amt</th>
						</tr>
					</thead>';
		$sr_no=0;
		
		foreach($org_packing_list as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$row->packing_short.'</td>
							<td>'.$sr_no.'</td>
							<td>'.$row->insurance_no_1.'</td>
							<td>'.$row->id.'</td>
							<td >'.$row->p_fname.'</td>
							<td >'.$row->opd_date.'</td>
							<td >'.$row->Doc_name.'</td>
							<td >'.$row->org_submit_date.'</td>
							<td >'.$row->claim_amt.'</td>
							<td >'.$row->final_approve_amount.'</td>
							<td></td>
					 	</tr>';
		}
		
		$content.="</table>";

		if($output==0)
		{
			$this->load->library('m_pdf');
			
			$file_name='Report-ORG-'.date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'OPD_Org_List'.date('Ymdhis'));
		}
	}

	function Print_ipd_List($Packing_id,$output=0)
	{
		$sql="SELECT org.*,ipd.doc_name  AS Doc_name,
			p.p_code,p.p_fname,p.age,
			if(org.case_type=0,'OPD','IPD') as IPD_OPD,
			ipd.net_amount AS claim_amt,
			ipd.str_register_date,ipd.str_discharge_date,org.org_submit_date,ipd.ipd_code,
			DATE_FORMAT(org.org_submit_date,'%d-%m-%Y') AS str_org_submit_date,
			ipd.id as ipd_id
		FROM ((organization_case_master org JOIN patient_master_exten p ON org.p_id=p.id)
			JOIN v_ipd_list ipd ON ipd.id=org.ipd_id)
		WHERE org.packing_id=$Packing_id
		ORDER BY org.packing_short";
		$query = $this->db->query($sql);
		$org_packing_list= $query->result();

		$sql="Select * from org_packing where id=$Packing_id";
		$query = $this->db->query($sql);
		$org_packing= $query->result();

		$org_packing_set=$org_packing[0]->label_no;

		$data['Packing_id']=$Packing_id;
		
		$content="<style>@page {
						sheet-size: A4-L; 
						margin-top: 0.5cm;
						margin-bottom: 0.5cm;
						margin-left: 0.5cm;
						margin-right: 0.5cm;
			}
		</style>";
		 
		$content.='<table border="1" width="100%" cellpadding="3">';

		$content.='<thead>
						<tr>
							<th>Set No.</th>
							<th>Sr.No</th>
							<th>IPD No.</th>
							<th>Claim ID</th>
							<th>Bill No.</th>
							<th>Name of Patient</th>
							<th>Admit. Dt.</th>
							<th>Discharge Dt.</th>
							<th>Sub. Date</th>
							<th>Consultant Name</th>
							<th>Amount Claimed</th>
							<th>Passed Amt.</th>
							<th>Ded. amt</th>
						</tr>
					</thead>';
		$sr_no=0;
		
		foreach($org_packing_list as $row)
		{
			$sr_no=$sr_no+1;
			$content.='<tr>
							<td>'.$org_packing_set.'</td>
							<td>'.$sr_no.'</td>
							<td>'.$row->ipd_code.'</td>
							<td>'.$row->insurance_no_1.'</td>
							<td>'.$row->ipd_id.'</td>
							<td >'.$row->p_fname.'</td>
							<td >'.$row->str_register_date.'</td>
							<td >'.$row->str_discharge_date.'</td>
							<td >'.$row->str_org_submit_date.'</td>
							<td >'.$row->Doc_name.'</td>
							<td >'.$row->claim_amt.'</td>
							<td >'.$row->final_approve_amount.'</td>
							<td></td>
					 	</tr>';
		}
		
		$content.="</table>";

		if($output==0)
		{
			$this->load->library('m_pdf');
			
			$file_name='Report-ipd_org-'.date('Ymdhis').".pdf";
			$filepath=$file_name;
			$this->m_pdf->pdf->WriteHTML($content);
			$this->m_pdf->pdf->Output($filepath,"I");
		}else{
			ExportExcel($content,'OPD_Org_List'.date('Ymdhis'));
		}
	
	}

	public function org_case_search()
    {
		
        $search_string = $this->input->post('data_search');
		$data['packing_id']=$this->input->post('packing_id');

		$sql="select *
			from org_packing
			where id=".$data['packing_id'];
		$query = $this->db->query($sql);
		$data['org_packing']= $query->result();	

		$list_type=$data['org_packing'][0]->org_type;
		$data['list_type']=$list_type;
        
        if(strlen($search_string)>3)
        {

			if($list_type==0)
			{
				$sql="SELECT o.*,o.case_id_code,o.insurance_company_name,
				p.p_code,p.p_fname,p.age,o.insurance_no,o.insurance_no_1,
				if(o.case_type=0,'OPD','IPD') as IPD_OPD
				FROM organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id
				WHERE   o.case_type=0 and (o.case_id_code LIKE '%$search_string' 
				OR o.insurance_no='$search_string' 
				OR o.insurance_no_1='$search_string'
				OR o.ipd_id='$search_string')
				ORDER BY o.id Limit 10";
			}else{
				$sql="SELECT o.*,o.case_id_code,o.insurance_company_name, p.p_code,p.p_fname,p.age,o.insurance_no,
				o.insurance_no_1, if(o.case_type=0,'OPD','IPD') as IPD_OPD 
				FROM (organization_case_master o JOIN patient_master_exten p ON o.p_id=p.id )
				JOIN ipd_master i ON o.ipd_id=i.id
				WHERE   o.case_type=1 and (o.case_id_code LIKE '%$search_string' 
				OR o.insurance_no='$search_string' 
				OR o.insurance_no_1='$search_string'
				OR i.ipd_code LIKE '%$search_string')
				ORDER BY o.id Limit 10";
			}
            
            $query = $this->db->query($sql);
            $data['org_list']= $query->result();

			$this->load->view('Org/org_list',$data);


        }else{
            echo 'Search with Min 4 Char.';
        }
     
    }


}
