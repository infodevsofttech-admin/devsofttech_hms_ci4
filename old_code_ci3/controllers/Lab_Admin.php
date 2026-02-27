<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lab_Admin extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}
	
	public function report_list()
	{
		$sql="select g.RepoGrp,r.Title,r.mstRepoKey
				FROM (lab_repo r join lab_rgroups g on r.GrpKey=g.mstRGrpKey)
				JOIN hc_items i ON r.charge_id=i.id AND i.itype in (5,6)";
		$query = $this->db->query($sql);
        $data['labReport_master']= $query->result();

		$this->load->view('PathLab_Report/lab_report_list',$data);
	}

	public function report_ultrasound_list($modality=2)
	{
		$sql="select * 	from radiology_ultrasound_template where modality=".$modality;
		$query = $this->db->query($sql);
        $data['labReport_master']= $query->result();

		$data['modality']=$modality;

		$this->load->view('PathLab_Report/ultrasound_template_list',$data);
	}
	
	public function report_test_list($repo_id)
	{
		$sql="select j.id,r.mstRepoKey,t.mstTestKey,t.Test,t.TestID,t.Result,j.EOrder
			from lab_repo r join lab_repotests j join lab_tests t 
			on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey where r.mstRepoKey=".$repo_id." order by j.EOrder" ;
		$query = $this->db->query($sql);
        $data['lab_Rep_Item_List']= $query->result();
		
		$data['mstRepoKey']=$repo_id;
		
		$this->load->view('PathLab_Report/lab_report_test_list',$data);
	}
	
	public function test_search_page($repo_id)
	{
		$data['repo_id']=$repo_id;
		$this->load->view('PathLab_Report/lab_test_search',$data);
	}

	public function test_parameter_load($mstTestKey,$mstRepoKey)
	{
		$sql="select * from lab_tests where mstTestKey=".$mstTestKey ;
		$query = $this->db->query($sql);
        $data['lab_test_parameter']= $query->result();
		
		$sql="select *,if(option_bold=1,'Bold','') as option_bold_str  from lab_tests_option where mstTestKey=".$mstTestKey. " order by sort_id " ;
		$query = $this->db->query($sql);
        $data['lab_test_option']= $query->result();
		
		$data['mstRepoKey']=$mstRepoKey;
		
		$this->load->view('PathLab_Report/lab_report_item_edit',$data);
	}
	public function remove_test_option($option_id,$id_key)
	{
		$this->db->delete("lab_tests_option", "id=".$option_id);
	
		$sql="select *,if(option_bold=1,'Bold','') as option_bold_str  from lab_tests_option where mstTestKey=".$id_key. " order by sort_id " ;
			$query = $this->db->query($sql);
			$lab_test_option= $query->result();

			$option_content='<table id="example2" class="table table-bordered table-striped TableData">
						<thead>
						<tr>
							<th>#</th>
							<th>Code</th>
							<th>Bold</th>
							<th>Action</th>
						 </tr>
						</thead>
						<tbody>';
			for ($i = 0; $i < count($lab_test_option); ++$i) {
			$option_content=$option_content.'<tr>
				<td>'.$lab_test_option[$i]->sort_id.'</td>
				<td>'.$lab_test_option[$i]->option_value.'</td>
				<td>'.$lab_test_option[$i]->option_bold_str.'</td>
				<td>
					<div class="btn-group-horizontal">
						<button type="button" class="btn btn-default" onclick="remove_option('.$lab_test_option[$i]->id.','.$id_key.')" >
						<i class="fa fa-remove"></i></button>
				';
				
				$option_current=$lab_test_option[$i]->id;
				$sort_current=$lab_test_option[$i]->sort_id;
				
				if($i+1 < count($lab_test_option))
				{
					$option_next=$lab_test_option[$i+1]->id;
					$sort_next=$lab_test_option[$i+1]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
							<i class="fa fa-level-down"></i></button>';
				}
			
				if($i>0)
				{
					$option_prev=$lab_test_option[$i-1]->id;
					$sort_prev=$lab_test_option[$i-1]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
							<i class="fa fa-level-up"></i></button>';

				}
				
				$option_content=$option_content.'</div>
				</td>
			</tr>';
			} 
			$option_content=$option_content.'</tbody></table>';
			
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
	
			$rvar=array(
			'insert_id' => 1,
			'showcontent'=> $showcontent,
			'option_content' => $option_content
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	
	}
	
	public function test_parameter_edit()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('PathLab_M');
		
		$id_key=$this->input->post('mstTestKey');
		
		$sql="select * from lab_tests where mstTestKey=".$id_key;
		$query = $this->db->query($sql);
        $lab_tests= $query->result();
		
		$TestID=$lab_tests[0]->TestID;
		$TestID_post=$this->input->post('input_test_code');
		
		$test_chk='';
		
		if($TestID!=$TestID_post || $TestID=='')
		{
			$test_chk='|is_unique[lab_tests.TestID]';
		}
		
		$FormRules = array(
                array(
                    'field' => 'input_Test_name',
                    'label' => 'Test Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_test_code',
                    'label' => 'Test Code',
                    'rules' => 'required|min_length[1]|max_length[30]'.$test_chk
                )
        );
		
		$this->form_validation->set_rules($FormRules);
		
		$update_value=1;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$idChecked=$this->input->post('input_isChecked');

			$data = array( 
					'Test'=> $this->input->post('input_Test_name'),
					'TestID'=> $this->input->post('input_test_code'),
					'Result'=> $this->input->post('input_Default'),
					'Formula'=> $this->input->post('input_Formula'),
					'VRule'=> $this->input->post('input_Validation'),
					'VMsg'=> $this->input->post('input_Message'),
					'Unit'=> $this->input->post('input_Unit'),
					'FixedNormals'=> $this->input->post('input_Fixed'),
					'isGenderSpecific'=> $idChecked,
					'FixedNormalsWomen'=> $this->input->post('input_FixedNormalsWomen')
				);

			$this->PathLab_M->update_item_parameter($data,$id_key);
			
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$update_value=0;
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}

		
		
		$rvar=array(
		'update_value' => $update_value,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
		
	}
	
	public function test_parameter_add()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('PathLab_M');
		
		$id_key=$this->input->post('mstTestKey');
		$mstRepoKey=$this->input->post('mstRepoKey');
		
		$sql="select * from lab_tests where mstTestKey=".$id_key;
		$query = $this->db->query($sql);
        $lab_tests= $query->result();
		
		$TestID="";
		
		if(count($lab_tests)>0)
		{
			$TestID=$lab_tests[0]->TestID;
		}
		
		$TestID_post=$this->input->post('input_test_code');
		
		$test_chk='';
		
		if($TestID!=$TestID_post || $TestID=='')
		{
			$test_chk='|is_unique[lab_tests.TestID]';
		}
		
		$FormRules = array(
                array(
                    'field' => 'input_Test_name',
                    'label' => 'Test Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_test_code',
                    'label' => 'Test Code',
                    'rules' => 'required|min_length[1]|max_length[30]'.$test_chk
                )
        );
		
		$this->form_validation->set_rules($FormRules);
		
		$insert_id=0;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$udata = array( 
					'Test'=> $this->input->post('input_Test_name'),
					'TestID'=> $this->input->post('input_test_code'),
					'Result'=> $this->input->post('input_Default'),
					'Formula'=> $this->input->post('input_Formula'),
					'VRule'=> $this->input->post('input_Validation'),
					'VMsg'=> $this->input->post('input_Message'),
					'Unit'=> $this->input->post('input_Unit'),
					'FixedNormals'=> $this->input->post('input_Fixed')
					);

			$insert_id=$this->PathLab_M->insert_item_parameter($udata,$id_key);
			
			$sql="select Max(EOrder) as MEOrder from lab_repotests where mstRepoKey=".$mstRepoKey;
			$query = $this->db->query($sql);
			$data['lab_repotests']= $query->result();
			
			$MEOrder=0;
			if(count($data['lab_repotests'])>0)
			{
				$MEOrder=$data['lab_repotests'][0]->MEOrder;
			}
			
			if($MEOrder === NULL)
			{
				$MEOrder=1;
			}else{
				$MEOrder=$MEOrder+1;
			}
			
			$udata = array( 
					'mstRepoKey'=> $mstRepoKey,
					'mstTestKey'=> $insert_id,
					'EOrder'=> $MEOrder
					);
			
			$sortorder_insert_id=$this->PathLab_M->insert_item_sortorder($udata);
			
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$insert_id=0;
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
	
		
		$rvar=array(
		'insert_id' => $insert_id,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
		
	}
	
	public function test_parameter_option_add()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('PathLab_M');
		
		$FormRules = array(
                array(
                    'field' => 'input_op_value',
                    'label' => 'Code',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		$sort_id=1;
		$id_key=$this->input->post('mstTestKey');
		
		$sql="select Max(sort_id) as msort_id from lab_tests_option where mstTestKey=".$id_key;
		$query = $this->db->query($sql);
        $lab_tests_option= $query->result();
		
		if(count($lab_tests_option)>0)
		{
			$sort_id=$lab_tests_option[0]->msort_id;
			$sort_id=$sort_id+1;
		}
		
		$insert_id=0;
		$showcontent="";
		$option_content="";
		if ($this->form_validation->run() == TRUE)
        {
			$data = array( 
					'mstTestKey'=> $id_key,
					'sort_id'=> $sort_id,
					'option_value'=> $this->input->post('input_op_value'),
					'option_text'=> $this->input->post('input_op_value'),
					'option_bold'=> $this->input->post('chk_bold')
					);

			$insert_id=$this->PathLab_M->insert_item_parameter_option($data);

			$sql="select *,if(option_bold=1,'Bold','') as option_bold_str  from lab_tests_option where mstTestKey=".$id_key. " order by sort_id " ;
			$query = $this->db->query($sql);
			$lab_test_option= $query->result();

			$option_content='<table id="example2" class="table table-bordered table-striped TableData">
						<thead>
						<tr>
							<th>#</th>
							<th>Code</th>
							<th>Bold</th>
							<th>Action</th>
						 </tr>
						</thead>
						<tbody>';
			for ($i = 0; $i < count($lab_test_option); ++$i) {
				
			$option_content=$option_content.'<tr>
				<td>'.$lab_test_option[$i]->sort_id.'</td>
				<td>'.$lab_test_option[$i]->option_value.'</td>
				<td>'.$lab_test_option[$i]->option_bold_str.'</td>
				<td><div class="btn-group-horizontal">
						<button type="button" class="btn btn-default" onclick="remove_option('.$lab_test_option[$i]->id.','.$id_key.')" >
										<i class="fa fa-remove"></i></button>';

										$option_current=$lab_test_option[$i]->id;
				$sort_current=$lab_test_option[$i]->sort_id;
				
				if($i+1 < count($lab_test_option))
				{
					$option_next=$lab_test_option[$i+1]->id;
					$sort_next=$lab_test_option[$i+1]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
							<i class="fa fa-level-down"></i></button>';
				}
			
				if($i>0)
				{
					$option_prev=$lab_test_option[$i]->id;
					$sort_prev=$lab_test_option[$i]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
							<i class="fa fa-level-up"></i></button>';

				}
				
				$option_content=$option_content.'</div>
				</td>
			</tr>';
			 } 
			$option_content=$option_content.'</tbody></table>';
			
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$$insert_id=0;
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
			
		$rvar=array(
			'insert_id' => $insert_id,
			'showcontent'=> $showcontent,
			'option_content' => $option_content
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	
	public function change_sort($id_key,$option_id,$current,$change_option_id,$change)
	{
		$this->load->model('PathLab_M');
		
		$udata = array( 
					'sort_id'=> 0
					);
		
		$this->PathLab_M->update_item_parameter_option($udata,$option_id);
		
		$udata = array( 
					'sort_id'=> $current
					);
		$this->PathLab_M->update_item_parameter_option($udata,$change_option_id);
		
		
		$udata = array( 
					'sort_id'=> $change
					);
		$this->PathLab_M->update_item_parameter_option($udata,$option_id);
		
		
		
		$sql="select *,if(option_bold=1,'Bold','') as option_bold_str from lab_tests_option where mstTestKey=".$id_key. " order by sort_id " ;
			$query = $this->db->query($sql);
			$lab_test_option= $query->result();

			$option_content='<table id="example2" class="table table-bordered table-striped TableData">
						<thead>
						<tr>
							<th>#</th>
							<th>Code</th>
							<th>Bold</th>
							<th>Action</th>
						 </tr>
						</thead>
						<tbody>';
			for ($i = 0; $i < count($lab_test_option); ++$i) {
			$option_content=$option_content.'<tr>
				<td>'.$lab_test_option[$i]->sort_id.'</td>
				<td>'.$lab_test_option[$i]->option_value.'</td>
				<td>'.$lab_test_option[$i]->option_bold_str.'</td>
				<td>
					<div class="btn-group-horizontal">
						<button type="button" class="btn btn-default" onclick="remove_option('.$lab_test_option[$i]->id.','.$id_key.')" >
										<i class="fa fa-remove"></i></button>';
				
				$option_current=$lab_test_option[$i]->id;
				$sort_current=$lab_test_option[$i]->sort_id;
				
				if($i+1 < count($lab_test_option))
				{
					$option_next=$lab_test_option[$i+1]->id;
					$sort_next=$lab_test_option[$i+1]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_next.','.$sort_next.')">
							<i class="fa fa-level-down"></i></button>';
				}
			
				if($i>0)
				{
					$option_prev=$lab_test_option[$i-1]->id;
					$sort_prev=$lab_test_option[$i-1]->sort_id;
					
					$option_content=$option_content. '<button type="button" class="btn btn-default" onclick="sortchange('.$id_key.','.$option_current.','.$sort_current.','.$option_prev.','.$sort_prev.')">
							<i class="fa fa-level-up"></i></button>';

				}
				
				$option_content=$option_content.'</div>
				</td>
			</tr>';
			 } 
			$option_content=$option_content.'</tbody></table>';
			
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
	
			$rvar=array(
			'insert_id' => 1,
			'showcontent'=> $showcontent,
			'option_content' => $option_content
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	
	}
	
	public function change_sort_item($repo_id,$option_id,$current,$change_option_id,$change)
	{
		$this->load->model('PathLab_M');
		
		$udata = array( 
					'EOrder'=> 0
					);
		$this->PathLab_M->update_item_sortorder($udata,$option_id);
		
		$udata = array( 
					'EOrder'=> $current
					);
		$this->PathLab_M->update_item_sortorder($udata,$change_option_id);
				
		$udata = array( 
					'EOrder'=> $change
					);
		$this->PathLab_M->update_item_sortorder($udata,$option_id);
		
		$sql="select j.id,r.mstRepoKey,t.mstTestKey,t.Test,t.TestID,t.Result,j.EOrder
			from lab_repo r join lab_repotests j join lab_tests t 
			on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey where r.mstRepoKey=".$repo_id." order by j.EOrder" ;
		$query = $this->db->query($sql);
        $data['lab_Rep_Item_List']= $query->result();
		
		$data['mstRepoKey']=$repo_id;
		
		$this->load->view('PathLab_Report/lab_report_test_list',$data);
		
	}
	
	
	public function reportedit_load($repo_id=0)
	{
		$sql="select * from lab_repo where mstRepoKey=".$repo_id;
		$query = $this->db->query($sql);
        $data['labReport_master']= $query->result();
		
		$item_id=0;
		
		if($repo_id>0)
		{
			$item_id=$data['labReport_master'][0]->charge_id;
		}
		
		//$sql="select * from hc_items where itype=5 and  id not  in (select charge_id from lab_repo where charge_id>0 and charge_id<>".$item_id.")";
		$sql="select * from hc_items where itype in (5,30,6) and  id not  in (select charge_id from lab_repo where charge_id>0 and charge_id<>".$item_id.") order by idesc ";
		
		$query = $this->db->query($sql);
        $data['hc_items']= $query->result();
		
		$sql="select * from lab_rgroups ";
		$query = $this->db->query($sql);
        $data['lab_rgroups']= $query->result();
		
		$sql="select j.id,r.mstRepoKey,t.mstTestKey,t.Test,t.TestID,t.Result,j.EOrder
			from lab_repo r join lab_repotests j join lab_tests t 
			on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey where r.mstRepoKey=".$repo_id." order by j.EOrder" ;
		$query = $this->db->query($sql);
        $data['lab_Rep_Item_List']= $query->result();
		
		$sql="select * from color ";
		$query = $this->db->query($sql);	
		$data['color_name']= $query->result();

		$data['repo_id']=$repo_id;
		
		$this->load->view('PathLab_Report/lab_report_edit',$data);
	}

	public function reportedit_ultrasound_load($modality=2,$repo_id=0)
	{
		$sql="select * from radiology_ultrasound_template where modality=$modality and id=".$repo_id;
		$query = $this->db->query($sql);
        $data['labReport_master']= $query->result();
		
		$item_id=0;
		
		if($repo_id>0)
		{
			$item_id=$data['labReport_master'][0]->charge_id;
		}

		
		
		$sql="select * from hc_items where itype=$modality order by idesc ";
		
		$query = $this->db->query($sql);
        $data['hc_items']= $query->result();
		
		$data['repo_id']=$repo_id;

		$data['modality']=$modality;
		
		$this->load->view('PathLab_Report/ultrasound_report_edit',$data);
	}
	
	public function report_update()
	{
		
		$this->load->model('PathLab_M');
		
		$id_key=$this->input->post('repo_id');
		
		$sql="select * from lab_repo where mstRepoKey=".$id_key;
		$query = $this->db->query($sql);
        $lab_repo= $query->result();
		
		$repo_title=$lab_repo[0]->Title;
		$repo_title_post=$this->input->post('input_Reportname');
		
		$repo_chk='';
		
		if($repo_title!=$repo_title_post)
		{
			$repo_chk='|is_unique[lab_repo.Title]';
		}
		
		$charge_id=$lab_repo[0]->charge_id;
		$charge_id_post=$this->input->post('charge_id');
		$charge_chk='';
		
		if($charge_id_post>0)
		{
			if($charge_id_post!=$charge_id)
			{
				$charge_chk='|is_unique[lab_repo.charge_id]';
			}
		}
		$update_record=0;
		
		$FormRules = array(
                array(
                    'field' => 'input_Reportname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]'.$repo_chk
                ),
				array(
                    'field' => 'charge_id',
                    'label' => 'Charge Name',
                    'rules' => 'required|min_length[1]|max_length[30]'.$charge_chk
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		if ($this->form_validation->run() == TRUE)
        {
			$data = array( 
					'Title'=> $this->input->post('input_Reportname'),
					'GrpKey'=> $this->input->post('group_id'),
					'charge_id'=> $this->input->post('charge_id'),
					'HTMLData'=> $this->input->post('HTMLData'),
					);

			$this->PathLab_M->update_report($data,$id_key);
			
			$update_record=1;
		
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');	
		
		}else{
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
		
		
		$rvar=array(
		'update_record' => $update_record,
		'showcontent' => $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	
	public function report_ultrasound_insert($modality)
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('PathLab_M');
		
		$FormRules = array(
                array(
                    'field' => 'input_Reportname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]|is_unique[lab_repo.Title]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		$insertid=0;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$id_key=$this->input->post('repo_id');
			$data = array( 
						'template_name'=> $this->input->post('input_Reportname'),
						'title'=> $this->input->post('group_id'),
						'charge_id'=> $this->input->post('charge_id'),
						'Findings'=> $this->input->post('HTMLData'),
						'Impression'=> $this->input->post('Impression'),
						'modality'=> $modality
						);
			$insertid=$this->PathLab_M->insert_ultrasound_report($data);
			$showcontent="Data Saved successfully";
			
		}else{
			$showcontent =validation_errors();
		}
		
		$rvar=array(
		'insertid' => $insertid,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function report_ultrasound_update($modality=2)
	{
		
		$this->load->model('PathLab_M');
		
		$id_key=$this->input->post('repo_id');
		
		$sql="select * from radiology_ultrasound_template where id=".$id_key;
		$query = $this->db->query($sql);
        $lab_repo= $query->result();
		
		$repo_title=$lab_repo[0]->title;
		$repo_title_post=$this->input->post('input_Reportname');
		
		$repo_chk='';
		
		$charge_id_post=$this->input->post('charge_id');
		
		
		$update_record=0;
		
		$FormRules = array(
                array(
                    'field' => 'input_Reportname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]'
                ),
				array(
                    'field' => 'charge_id',
                    'label' => 'Charge Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		if ($this->form_validation->run() == TRUE)
        {
			$data = array( 
					'template_name'=> $this->input->post('input_Reportname'),
					'title'=> $this->input->post('group_id'),
					'charge_id'=> $charge_id_post,
					'Findings'=> $this->input->post('HTMLData'),
					'Impression'=> $this->input->post('Impression'),
					'modality'=> $modality
					);

			$this->PathLab_M->update_ultrasound_report($data,$id_key);
			
			$update_record=1;
		
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');	
		
		}else{
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
		
		
		$rvar=array(
		'update_record' => $update_record,
		'showcontent' => $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
	}
	
	public function report_insert()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('PathLab_M');
		
		$FormRules = array(
                array(
                    'field' => 'input_Reportname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]|is_unique[lab_repo.Title]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		$insertid=0;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$id_key=$this->input->post('repo_id');
			$data = array( 
						'Title'=> $this->input->post('input_Reportname'),
						'GrpKey'=> $this->input->post('group_id'),
						'charge_id'=> $this->input->post('charge_id'),
					'	HTMLData'=> $this->input->post('HTMLData')
						);
			$insertid=$this->PathLab_M->insert_report($data);
			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
					
			
		}else{
			$showcontent =Show_Alert('danger','Error',validation_errors());
		}
		
		$rvar=array(
		'insertid' => $insertid,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	
	public function lab_master()
	{
		$this->load->view('PathLab_Report/lab_main');
	}
	
	public function lab_path($lab_type)
	{
		$data['lab_type']=$lab_type;
		$this->load->view('PathLab_Report/pathlab_request_list',$data);
	}
	
	
	public function Entry_Update()
	{
		$this->load->model('PathLab_M');
		
		$test_id=$this->input->post('test_id');
		$test_value=$this->input->post('test_value');
		
		$udata = array( 
						'lab_test_value'=> $test_value
					);
		$this->PathLab_M->update_test_entry($udata,$test_id);

	
		$sql="select lab_test_value from  lab_request_item where id=".$test_id;
		$query = $this->db->query($sql);
		$data_value= $query->result();
		
		echo $data_value[0]->lab_test_value;
		
	}
	
	public function Remark_Update($req_id)
	{
		$this->load->model('PathLab_M');
		
		$udata = array( 
						'Remark'=> $this->input->post('HTMLData')
					);

					$this->PathLab_M->update_test_request($udata,$req_id);
		
		echo "Saved";
		
	}
	
	public function Final_Update($req_id)
	{
		$this->load->model('PathLab_M');
		
		$udata = array( 
						'Report_Data'=> $this->input->post('HTMLData')
					);

		$this->PathLab_M->update_test_request($udata,$req_id);
		
		echo "Saved";
		
	}
	
	public function Report_Final_print($inv_req_id)
	{
		$this->load->model('PathLab_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:m:s').']';

		$udata = array( 
						'Report_Data'=> $this->input->post('HTMLData'),
						'report_update'=> $user_name
					);

		$sortorder_insert_id=$this->PathLab_M->update_invoice_report($udata,$inv_req_id);
		echo 'Report Update';
		
	}

	public function Report_single_print($lab_req_id)
	{
		$this->load->model('PathLab_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:m:s').']';

		$udata = array( 
						'Report_Data'=> $this->input->post('HTMLData'),
						'report_update'=> $user_name
					);

		$sortorder_insert_id=$this->PathLab_M->update_test_request($udata,$lab_req_id);
		echo 'Report Update';
		
		
	}
	
	public function confirm_report($req_id)
	{
		$this->load->model('PathLab_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$udata = array( 
						'confirm_by'=> $user_name,
						'status'=>2
				);

		$this->PathLab_M->update_test_request($udata,$req_id);
		
		echo "Verified and Ready for Print";
		
	}
	
	
	
	public function create_report($req_id) {
    
		$this->load->model('PathLab_M');
		
		$sql="select * from lab_request where id=".$req_id;
		$query = $this->db->query($sql);
		$data_lab_request= $query->result();

		$p_id=$data_lab_request[0]->patient_id;

		$sql="select * from patient_master_exten where id=$p_id";
		$query = $this->db->query($sql);
		$data_patient_data= $query->result();

		$gender=$data_patient_data[0]->gender;
			

		$sql="select d.mstTestKey,d.Test,d.TestID,d.Result,d.Formula,d.VRule,d.VMsg,d.Unit, d.FixedNormals,
		i.lab_test_value,i.lab_test_remark,i.id ,s.EOrder,d.isGenderSpecific,d.FixedNormalsWomen,
		group_concat(concat(o.id,':',o.option_text,':',o.option_value) ORDER BY o.sort_id) as option_value
		from (lab_request_item i join lab_tests d join lab_repotests s 
		on i.lab_test_id=d.mstTestKey and d.mstTestKey=s.mstTestKey and i.lab_repo_id=s.mstRepoKey)
		left join lab_tests_option o on d.mstTestKey=o.mstTestKey
		where i.lab_request_id=".$req_id." group by d.mstTestKey order by s.EOrder";
		$query = $this->db->query($sql);
		$data_lab_test_list= $query->result();
		
		$sql="select mstRepoKey,Title,HTMLData,GrpKey,charge_id,RepoGrp 
		from lab_repo  join lab_rgroups on lab_repo.GrpKey=lab_rgroups.mstRGrpKey
		where mstRepoKey=".$data_lab_request[0]->lab_repo_id;
		
		
		$query = $this->db->query($sql);
		$data_report_format= $query->result();
		
		$sql="select * from invoice_master where attach_type=0 and id=".$data_lab_request[0]->charge_id;
		$query = $this->db->query($sql);
		$data_invoice_master= $query->result();
		
		$sql="select id from lab_invoice_request where invoice_id=".$data_lab_request[0]->charge_id." and lab_type=".$data_lab_request[0]->lab_type ;
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();
			
		if(count($data_report_format)>0)
		{
			
			$Report_Head_H3='<h3>'.$data_report_format[0]->Title.'</h3>';
			$Report_string=$data_report_format[0]->HTMLData;
			
		}else{
			
			$Report_Head_H3='<h3>'.$data_lab_request[0]->report_name.'</h3>';
			$Report_string="";
		}
		
		$Report_Header='<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
							<tbody>
								<tr>
									<td >'.$Report_Head_H3.'</td>
								</tr>
							</tbody>
						</table>
						';
		
		
		$Report_Footer=$data_lab_request[0]->Remark;

		//update report_string with test parameter

		$lab_test_array=array();
		for($j = 0; $j < count($data_lab_test_list); ++$j) { 
			$lab_test_array[$data_lab_test_list[$j]->TestID]=$data_lab_test_list[$j]->lab_test_value;
		}

				
		for ($i = 0; $i < count($data_lab_test_list); ++$i) { 
			
			$FixedNormals=$data_lab_test_list[$i]->FixedNormals;
			$isGenderSpecific=$data_lab_test_list[$i]->isGenderSpecific;
			$FixedNormalsWomen=$data_lab_test_list[$i]->FixedNormalsWomen;
			
			$Test_Formula=$data_lab_test_list[$i]->Formula;
			
			//$LabTestValue=$data_lab_test_list[$i]->lab_test_value;
			
			if(strlen($Test_Formula)>0){
				foreach($lab_test_array as $key=>$value){
					if(is_numeric($value))
					{
						$Test_Formula=str_replace('{'.$key.'}',$value,$Test_Formula);
					}else{
						$Test_Formula=str_replace('{'.$key.'}','0',$Test_Formula);
					}
					
				}
				
				//echo $Test_Formula;
				
				$LabTestValue=Round(cal_exp(trim($Test_Formula)),2);
			

			}else{
				$LabTestValue=$data_lab_test_list[$i]->lab_test_value;
			}


			if($isGenderSpecific==1)
			{
				if($gender==1)
				{
					if(strlen($FixedNormals)>0 && is_numeric($LabTestValue))
					{
						$FixedNormals_array=explode('-',$FixedNormals);
						
						if(is_array($FixedNormals_array))
						{
							$FixedNormals_min=$FixedNormals_array[0];
							$FixedNormals_max=$FixedNormals_array[1];
							if(is_numeric($FixedNormals_min)&&is_numeric($FixedNormals_min))
							{
								if($LabTestValue>=$FixedNormals_min && $LabTestValue<=$FixedNormals_max )
								{
									
								}else{
									$LabTestValue='<b>'.$LabTestValue.'</b>';
								}
							}
						}
						
					}
				}else{
					if(strlen($FixedNormalsWomen)>0 && is_numeric($LabTestValue))
					{
						$FixedNormals_array=explode('-',$FixedNormalsWomen);
						
						if(is_array($FixedNormals_array))
						{
							$FixedNormals_min=$FixedNormals_array[0];
							$FixedNormals_max=$FixedNormals_array[1];
							if(is_numeric($FixedNormals_min)&&is_numeric($FixedNormals_max))
							{
								if($LabTestValue>=$FixedNormals_min && $LabTestValue<=$FixedNormals_max )
								{
									
								}else{
									$LabTestValue='<b>'.$LabTestValue.'</b>';
								}
							}
						}
						
					}
				}

			}else{
				if(strlen($FixedNormals)>0 && is_numeric($LabTestValue))
				{
					$FixedNormals_array=explode('-',$FixedNormals);
					
					if(is_array($FixedNormals_array))
					{
						$FixedNormals_min=$FixedNormals_array[0];
						$FixedNormals_max=$FixedNormals_array[1];
						if(is_numeric($FixedNormals_min)&&is_numeric($FixedNormals_min))
						{
							if($LabTestValue>=$FixedNormals_min && $LabTestValue<=$FixedNormals_max )
							{
								
							}else{
								$LabTestValue='<b>'.$LabTestValue.'</b>';
							}
						}
					}
					
				}
			}
			
			$Report_string=str_replace('{'.$data_lab_test_list[$i]->TestID.'}',$LabTestValue,$Report_string);
		}
		
		$complete_report=$Report_Header.$Report_string.$Report_Footer;
		
		$udata = array( 
						'Report_Data'=> $complete_report,
						'status'=>1,
						'reported_time'=> date('Y-m-d H:i:s')
					);

					$this->PathLab_M->update_test_request($udata,$req_id);
		
		$udata = array( 
						'reported_time'=> date('Y-m-d H:i:s')
					);
		$this->PathLab_M->update_invoice_report($udata,$lab_invoice_request[0]->id);
		
		//echo 'Report Created'.$lab_invoice_request[0]->id;

		redirect('Lab_Admin/show_report_final/'.$req_id);
    }
	
	
	
	public function show_report_final($req_id)
	{
		$sql="select * from lab_request where id=".$req_id;
		$query = $this->db->query($sql);
		$data['report_format']= $query->result();

		$this->load->view('PathLab_Report/lab_final_report_show',$data);
	}
	
	public function report_compile1($inv_id,$lab_type)
	{
		$sql="select l.*,g.RepoGrp,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
			FROM (lab_request l join lab_repo r on l.lab_repo_id=r.mstRepoKey)
			left join lab_rgroups g  on r.GrpKey=g.mstRGrpKey
			where l.print_combine=1 and status=2 and l.charge_id=".$inv_id." and l.lab_type=".$lab_type."	order by g.sort_order";

		$query = $this->db->query($sql);
		$data_lab_request= $query->result();

		$sql="Select max(report_edit_req_no) as no 
		from lab_request where  charge_id=".$inv_id." and lab_type=".$lab_type."	";
		$query = $this->db->query($sql);
		$data_lab_request_edit= $query->result();

		if($data_lab_request_edit[0]->no>0)
		{
			$data_lab_request_edit_str='/'.$data_lab_request_edit[0]->no;
			$note_edit_str='Caution : Please ignore the earlier report.';
		}else{
			$data_lab_request_edit_str='';
			$note_edit_str='';
		}


		$sql="select * ,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
		from lab_invoice_request l
		where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();

		if(count($data_lab_request)==0)
		{
			exit( 'No Record Ready for print Report');
		}

		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select * 
		from patient_master_exten p where  id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result();
	
		if($invoice_master[0]->refer_by_id>0)
		{
			$sql="select * from doctor_master where id=".$invoice_master[0]->refer_by_id;
			$query = $this->db->query($sql);
			$doc_master= $query->result();
			$rdoc_name='Dr.'.$doc_master[0]->p_fname;
		}else{
			$rdoc_name=$invoice_master[0]->refer_by_other;
		}
		
		$sql="select * from ipd_master where ipd_status=0 and p_id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();
		
		

		$insurance_case_id=$invoice_master[0]->insurance_case_id;
		
		$org_ID='';

		if($insurance_case_id>1)
		{
			$sql="select o.* ,i.short_name
			from organization_case_master o join hc_insurance i on o.insurance_id=i.id 
			 where o.id=".$insurance_case_id;
			$query = $this->db->query($sql);
			$org_master= $query->result();

			if(count($org_master)>0){
				$org_ID='<b>Org. ID:</b> '.$org_master[0]->case_id_code.'<br/>';
				$org_ID.='<b>Org.Name :</b>'.$org_master[0]->short_name.'<br/>';
			}
		}
		
		$ipd_id='';
		
		if(count($ipd_master)>0)
		{
			$ipd_id='<b>IPD No. :</b> '. $ipd_master[0]->ipd_code.'<br/>';
		}

		//$print_datetime=$data_lab_request[0]->Request_Date;
		
		$print_datetime=date('d-m-Y h:i:s A');
				
	
		if($lab_invoice_request[0]->collected_time<>'')
		{
			$Collection_data='<br/>
				<b>Collected : </b>'.$lab_invoice_request[0]->str_collected_time.'<br/>
				<b>Reported : </b>'.$lab_invoice_request[0]->str_reported_time;
		}
		
		$Header='
		<table border="0" cellpadding="2" cellspacing="1" style="width:100%">
				<tr>
					<td  width="50%" style="vertical-align:top" >
						<b>Inovice ID : </b>'.$invoice_master[0]->invoice_code.' '.$data_lab_request_edit_str.'<br/>
						<b>Patient Name : </b>'.$data_patient_master[0]->title.' '.$data_patient_master[0]->p_fname.'<br/>
						'.$data_patient_master[0]->p_relative.' '.$data_patient_master[0]->p_rname.'<br/>
						<b>Age/Sex : </b>'.$data_patient_master[0]->str_age.'/'.$data_patient_master[0]->xgender.'<br/>
						<b>UHID : </b>'.$data_patient_master[0]->p_code.'<br/>
					</td>
					<td  width="50%" style="vertical-align:top" >'.$ipd_id.$org_ID.'
						<b>Print Date : </b>'.$print_datetime.'<br/>
						'.$Collection_data.'
						<b>Reffered By : </b>'.$rdoc_name.'<br/>
					</td>
				</tr>
		';
		
		$Header.='
		</table>'.$note_edit_str.'
		<hr/>';
		
		$RawData="";
		$group_name='';
		$first_header=1;
		
		for($i=0;$i<count($data_lab_request);++$i)
		{
			if($data_lab_request[$i]->RepoGrp==$group_name)
			{
				$groupHead='';
			}else{
				if($first_header==0)
				{
					//$groupHead='<br pagebreak="true" />';
					$groupHead='';
				}else{
					$groupHead='';
				}
				
				$groupHead.='
				<h1 style="text-align:center; vertical-align:middle">'.$data_lab_request[$i]->RepoGrp.'</h1>';
	
				$first_header=0;
			}
			
			$RawData=$RawData.$groupHead;
			$RawData=$RawData.$data_lab_request[$i]->Report_Data;
			
			$group_name=$data_lab_request[$i]->RepoGrp;
			
		}

		$complete_report=$RawData;
		
		$inv_req_id=0;
		if(count($lab_invoice_request)>0)
		{
			$inv_req_id=$lab_invoice_request[0]->id;
		}
		
		$this->load->model('PathLab_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:m:s').']';
	
		$udata = array( 
					'report_data'=> $complete_report,
					'report_compile'=> $user_name,
					'report_header'=> $Header,
					);

		$sortorder_insert_id=$this->PathLab_M->update_invoice_report($udata,$inv_req_id);
		echo 'Data Compile';

		$filename =  $inv_id.'-'.$lab_type.'-'.time() . '.pdf';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$lab_file_name=$folder_name.'/'.$filename;

	}
	
	public function report_compile($inv_id,$lab_type)
	{
		$sql="select l.*,g.RepoGrp,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
			FROM (lab_request l join lab_repo r on l.lab_repo_id=r.mstRepoKey)
			left join lab_rgroups g  on r.GrpKey=g.mstRGrpKey
			where l.print_combine=1 and status=2 and l.charge_id=".$inv_id." and l.lab_type=".$lab_type."	order by g.sort_order";

		$query = $this->db->query($sql);
		$data_lab_request= $query->result();

		$sql="Select max(report_edit_req_no) as no 
		from lab_request where  charge_id=".$inv_id." and lab_type=".$lab_type."	";
		$query = $this->db->query($sql);
		$data_lab_request_edit= $query->result();

		if($data_lab_request_edit[0]->no>0)
		{
			$sql="SELECT GROUP_CONCAT(CONCAT_WS('/',l.log_type,l.log_Faults,l.comments,'Report:',r.report_name)) AS report_log
				from lab_log l JOIN  lab_request r ON l.lab_repo_id=r.id
				WHERE r.charge_id=$inv_id AND r.lab_type=$lab_type";

			$query = $this->db->query($sql);
			$data_lab_edit_error= $query->result();

			$report_log='';

			if(count($data_lab_edit_error)>0){
				$report_log=$data_lab_edit_error[0]->report_log;
			}

			$data_lab_request_edit_str='/'.$data_lab_request_edit[0]->no;
			$note_edit_str='Caution : The previous report has been corrected. Please ignore the previous report.<br/>Error Type : '.$report_log;
			//$note_edit_str='';
		}else{
			$data_lab_request_edit_str='';
			$note_edit_str='';
		}


		$sql="select * ,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
		from lab_invoice_request l
		where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();

		if(count($data_lab_request)==0)
		{
			exit( 'No Record Ready for print Report');
		}

		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select * 
		from patient_master_exten p where  id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result();

	
		if($invoice_master[0]->refer_by_id>0)
		{
			$sql="select * from doctor_master where id=".$invoice_master[0]->refer_by_id;
			$query = $this->db->query($sql);
			$doc_master= $query->result();
			$rdoc_name='Dr.'.$doc_master[0]->p_fname;
		}else{
			$rdoc_name=$invoice_master[0]->refer_by_other;
		}
		
		$sql="select * from ipd_master where ipd_status=0 and p_id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();
		
		

		$insurance_case_id=$invoice_master[0]->insurance_case_id;
		
		$org_ID='';

		if($insurance_case_id>1)
		{
			$sql="select o.* ,i.short_name
			from organization_case_master o join hc_insurance i on o.insurance_id=i.id 
			 where o.id=".$insurance_case_id;
			$query = $this->db->query($sql);
			$org_master= $query->result();

			if(count($org_master)>0){
				$org_ID='<b>Org. ID:</b> '.$org_master[0]->case_id_code.'<br/>';
				$org_ID.='<b>Org.Name :</b>'.$org_master[0]->short_name.'<br/>';
			}
		}
		
		$ipd_id='';
		
		if(count($ipd_master)>0)
		{
			$ipd_id='<b>IPD No. :</b> '. $ipd_master[0]->ipd_code.'';
		}

		//$print_datetime=$data_lab_request[0]->Request_Date;
		
		$print_datetime=date('d-m-Y h:i:s A');
				
	
		if($lab_invoice_request[0]->collected_time<>'')
		{
			$Collection_data='<br/>
				<b>Collected : </b>'.$lab_invoice_request[0]->str_collected_time.'<br/>
				<b>Reported : </b>'.$lab_invoice_request[0]->str_reported_time;
		}
		
		$Header='
		<table border="0" cellpadding="2" cellspacing="1" style="width:100%">
				<tr>
					<td  width="50%" style="vertical-align:top" >
						<b>Inovice ID : </b>'.$invoice_master[0]->invoice_code.' '.$data_lab_request_edit_str.'<br/>
						<b>Patient Name : </b>'.$data_patient_master[0]->title.' '.$data_patient_master[0]->p_fname.'<br/>
						'.$data_patient_master[0]->p_relative.' '.$data_patient_master[0]->p_rname.'<br/>
						<b>Age/Sex : </b>'.$data_patient_master[0]->str_age.'/'.$data_patient_master[0]->xgender.'<br/>
						<b>UHID : </b>'.$data_patient_master[0]->p_code.'<br/>
					</td>
					<td  width="50%" style="vertical-align:top" >'.$ipd_id.'
						'.$Collection_data.'
						<br/><b>Reffered By : </b>'.$rdoc_name.'<br/>
						Page No. : {PAGENO} / {nbpg}
					</td>
					<td>

					</td>
				</tr>
		';
		
		$Header.='
		</table>'.$note_edit_str.'
		<hr/>';
		
		$RawData="";
		$group_name='';
		$first_header=1;
		
		for($i=0;$i<count($data_lab_request);++$i)
		{
			if($data_lab_request[$i]->RepoGrp==$group_name)
			{
				$groupHead='';
			}else{
				if($first_header==0)
				{
					//$groupHead='<br pagebreak="true" />';
					$groupHead='';
				}else{
					$groupHead='';
				}
				
				$groupHead.='
				<h1 style="text-align:center; vertical-align:middle">'.$data_lab_request[$i]->RepoGrp.'</h1>';
	
				$first_header=0;
			}
			
			$RawData=$RawData.$groupHead;
			$RawData=$RawData.$data_lab_request[$i]->Report_Data;
			
			$group_name=$data_lab_request[$i]->RepoGrp;
			
		}

		$complete_report=$RawData;
		
		$inv_req_id=0;
		if(count($lab_invoice_request)>0)
		{
			$inv_req_id=$lab_invoice_request[0]->id;
		}
		
		$this->load->model('PathLab_M');
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:m:s').']';
	
		$udata = array( 
					'report_data'=> $complete_report,
					'report_compile'=> $user_name,
					'report_header'=> $Header,
					);

		$sortorder_insert_id=$this->PathLab_M->update_invoice_report($udata,$inv_req_id);
		echo 'Data Compile';

		$filename =  $inv_id.'-'.$lab_type.'-'.time() . '.pdf';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}

		$lab_file_name=$folder_name.'/'.$filename;

	}

	public function report_compile_single($inv_id,$lab_type,$req_id,$print=1)
	{
		$sql="select l.*,g.RepoGrp,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
			from lab_request l left join (lab_repo r  join lab_rgroups g on r.GrpKey=g.mstRGrpKey)
			on l.lab_repo_id=r.mstRepoKey
			where l.id=".$req_id." and l.charge_id=".$inv_id." and l.lab_type=".$lab_type."	order by g.sort_order";
		$query = $this->db->query($sql);
		$data['lab_request']=$query->result();

		$data_lab_request=$data['lab_request'];

		$sql="select * ,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
		from lab_invoice_request l
		where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();

		$sql="SELECT max(r.report_edit_req_no) as no 
			from lab_request r JOIN lab_log l ON r.id=l.lab_repo_id 
			Where r.id=$req_id";
		$query = $this->db->query($sql);
		$data_lab_request_edit= $query->result();

		if($data_lab_request_edit[0]->no>0)
		{
			$sql="SELECT GROUP_CONCAT(CONCAT_WS('/',l.log_type,l.log_Faults,l.comments,'Report:',r.report_name)) AS report_log
				from lab_log l JOIN  lab_request r ON l.lab_repo_id=r.id
				WHERE r.charge_id=$inv_id AND r.lab_type=$lab_type and l.lab_repo_id=$req_id";

			$query = $this->db->query($sql);
			$data_lab_edit_error= $query->result();

			$report_log='';

			if(count($data_lab_edit_error)>0){
				$report_log=$data_lab_edit_error[0]->report_log;
			}

			$data_lab_request_edit_str='/'.$data_lab_request_edit[0]->no;
			$note_edit_str='Caution : The previous report has been corrected. Please ignore the previous report.<br/>Error Type : '.$report_log;
			//$note_edit_str='';
		}else{
			$data_lab_request_edit_str='';
			$note_edit_str='';
		}

		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select *
		from patient_master_exten p where  id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result();
		
		if($invoice_master[0]->refer_by_id>0)
		{
			$sql="select * from doctor_master where id=".$invoice_master[0]->refer_by_id;
			$query = $this->db->query($sql);
			$doc_master= $query->result();
			$rdoc_name='Dr.'.$doc_master[0]->p_fname;
		}else{
			$rdoc_name=$invoice_master[0]->refer_by_other;
		}
		
		$sql="select * from ipd_master where ipd_status=0 and p_id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();
		
		$ipd_id='';
		
		if(count($ipd_master)>0)
		{
			$ipd_id='<b>IPD No. :</b> '. $ipd_master[0]->ipd_code.'<br/>';
		}

		$insurance_case_id=$invoice_master[0]->insurance_case_id;
		
		$org_ID='';

		if($insurance_case_id>1)
		{
			$sql="select o.* ,i.short_name
			from organization_case_master o join hc_insurance i on o.insurance_id=i.id 
			 where o.id=".$insurance_case_id;
			$query = $this->db->query($sql);
			$org_master= $query->result();

			if(count($org_master)>0){
				$org_ID='<b>Org. ID:</b> '.$org_master[0]->case_id_code.'<br/>';
				$org_ID.='<b>Org.Name :</b>'.$org_master[0]->short_name.'<br/>';
			}
		}

		$print_datetime=$data_lab_request[0]->Request_Date;
		
		$print_datetime=date('d-m-Y h:i:sa');

		if($lab_invoice_request[0]->collected_time<>'')
		{
			$Collection_data='
				<b>Collected : </b>'.$lab_invoice_request[0]->str_collected_time.'<br/>
				<b>Reported : </b>'.$lab_invoice_request[0]->str_reported_time;
		}
		
		
		$Header='
		<table border="0" cellpadding="2" cellspacing="1" style="width:100%">
				<tr>
					<td  width="50%" style="vertical-align:top" >
						<b>Inovice ID : </b>'.$invoice_master[0]->invoice_code.'<br/>
						<b>Patient Name : </b>'.$data_patient_master[0]->title.' '.$data_patient_master[0]->p_fname.'<br/>
						'.$data_patient_master[0]->p_relative.' '.$data_patient_master[0]->p_rname.'<br/>
						<b>Age/Sex : </b>'.$data_patient_master[0]->str_age.'/'.$data_patient_master[0]->xgender.'<br/>
						<b>UHID : </b>'.$data_patient_master[0]->p_code.'<br/>
						<b>Reffered By : </b>'.$rdoc_name.'<br/>
					</td>
					<td  width="50%" style="vertical-align:top" >'.$ipd_id.$org_ID.'
						
						'.$Collection_data.'
					</td>
				</tr>
		';
		
		$Header.='
		</table>'.$note_edit_str.'
		<hr/>';
		$RawData="";
		$group_name='';
		$first_header=1;
		
		for($i=0;$i<count($data_lab_request);++$i)
		{
			if($data_lab_request[$i]->RepoGrp==$group_name)
			{
				$groupHead='';
			}else{
				if($first_header==0)
				{
					//$groupHead='<br pagebreak="true" />';
					$groupHead='';
				}else{
					$groupHead='';
				}
				
				$groupHead.='
				<h1 style="text-align:center; vertical-align:middle">'.$data_lab_request[$i]->RepoGrp.'</h1>';
	
				$first_header=0;
			}
			
			$RawData=$RawData.$groupHead;
			$RawData=$RawData.$data_lab_request[$i]->Report_Data;
			$group_name=$data_lab_request[$i]->RepoGrp;
		}

		$complete_report=$RawData;
		
		
		$inv_req_id=0;
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		//Report Show in PDF
		
		$inv_req_id=$lab_invoice_request[0]->id;
		//$complete_report=$data['lab_invoice_request'][0]->report_data;
			
		$sql="select * from hc_item_type where itype_id=".$lab_invoice_request[0]->lab_type;
		$query = $this->db->query($sql);
		$report_head_row= $query->result();
			
		$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$invoice_master[0]->invoice_code.' / Person Name :'.$data_patient_master[0]->p_fname;
			
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
			
		if($print>0)
		{
			if(count($data['lab_head_name'])>0)
			{
				$data['docname']=$data['lab_head_name'][0]->doc_name;
				$data['docedu']=$data['lab_head_name'][0]->doc_edu;
				$data['tech_name']=$data['lab_head_name'][0]->tech_name;
			}else{
				$data['docname']='';
				$data['docedu']='';
				$data['tech_name']='';
			}
			
			$folder_name='uploads/'.date('Ymd');
			
			if (!file_exists($folder_name)) {
				mkdir($folder_name, 0777, true);
				chmod($folder_name, 0777);
			}
		
			$file_name='Report'.$inv_id."-".$lab_type."-".date('dmYhis').".pdf";
			
			$filepath=$folder_name.'/'.$file_name;
			
			$data['complete_report']=$complete_report;
			$data['report_head']=$report_head;

			$data['report_header']=$Header;


			$data['print_on_type']="0";
			$data['bar_content']=$inv_id.'-'.$lab_type;
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(H_Name);
			$this->m_pdf->pdf->showWatermarkText = false;
			
	
			$filepath=$file_name;
	
			//$this->load->view('Medical/medical_bill_print_format',$data);
			$content=$this->load->view('Lab_Panel/patient_lab_report',$data,TRUE);
	
			//echo $content;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");   
			
			
		}else{
			$this->load->view('Lab_Panel/lab_single_report',$data);
		}
	}

	public function report_compile_xray_single($inv_id,$lab_type,$req_id,$print=1,$print_on_type=0)
	{
		$sql="select l.*,g.RepoGrp,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
			from lab_request l left join (lab_repo r  join lab_rgroups g on r.GrpKey=g.mstRGrpKey)
			on l.lab_repo_id=r.mstRepoKey
			where l.id=".$req_id." and l.charge_id=".$inv_id." and l.lab_type=".$lab_type."	order by g.sort_order";
		$query = $this->db->query($sql);
		$data['lab_request']=$query->result();

		$data_lab_request=$data['lab_request'];

		$sql="select * ,
			date_format(l.collected_time,'%d-%m-%Y %h:%i %p') as str_collected_time,
			date_format(l.reported_time,'%d-%m-%Y %h:%i %p') as str_reported_time
		from lab_invoice_request l
		where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();

		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select *
		from patient_master_exten p where  id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result();
		
		if($invoice_master[0]->refer_by_id>0)
		{
			$sql="select * from doctor_master where id=".$invoice_master[0]->refer_by_id;
			$query = $this->db->query($sql);
			$doc_master= $query->result();
			$rdoc_name='Dr.'.$doc_master[0]->p_fname;
		}else{
			$rdoc_name=$invoice_master[0]->refer_by_other;
		}
		
		$sql="select * from ipd_master where ipd_status=0 and p_id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();
		
		$ipd_id='';
		
		if(count($ipd_master)>0)
		{
			$ipd_id='<b>IPD No. :</b> '. $ipd_master[0]->ipd_code.'<br/>';
		}

		$insurance_case_id=$invoice_master[0]->insurance_case_id;
		
		$org_ID='';

		if($insurance_case_id>1)
		{
			$sql="select o.* ,i.short_name
			from organization_case_master o join hc_insurance i on o.insurance_id=i.id 
			 where o.id=".$insurance_case_id;
			$query = $this->db->query($sql);
			$org_master= $query->result();

			if(count($org_master)>0){
				$org_ID='<b>Org. ID:</b> '.$org_master[0]->case_id_code.'<br/>';
				$org_ID.='<b>Org.Name :</b>'.$org_master[0]->short_name.'<br/>';
			}
		}

		$print_datetime=$data_lab_request[0]->Request_Date;
		$print_datetime=date('d-m-Y h:i:sa');

		if($lab_invoice_request[0]->collected_time<>'')
		{
			$Collection_data='
				<b>Report Time : </b>'.$lab_invoice_request[0]->str_reported_time;
		}
		
		$Header='
		<table border="0" cellpadding="2" cellspacing="1" style="width:100%">
				<tr>
					<td  width="50%" style="vertical-align:top" >
						<b>Patient Name : </b>'.$data_patient_master[0]->title.' '.$data_patient_master[0]->p_fname.'<br/>
						'.$data_patient_master[0]->p_relative.' '.$data_patient_master[0]->p_rname.'<br/>
						<b>Age/Sex : </b>'.$data_patient_master[0]->str_age.'/'.$data_patient_master[0]->xgender.'<br/>
						<b>Reffered By : </b>'.$rdoc_name.'<br/>
					</td>
					<td  width="50%" style="vertical-align:top" >
						<b>Inovice ID : </b>'.$invoice_master[0]->invoice_code.'<br/>
						<b>UHID : </b>'.$data_patient_master[0]->p_code.'<br/>
						'.$ipd_id.$org_ID.'
						'.$Collection_data.'
					</td>
				</tr>
		';
		
		$Header.='
		</table>
		<hr/>';
		$RawData="";

		if(count($data_lab_request)>0)
		{
			$groupHead='<h3 style="text-align:center; vertical-align:middle">'.$data_lab_request[0]->report_name.'</h3>';

			$RawData=$RawData.$groupHead;
			$RawData=trim($RawData.$data_lab_request[0]->Report_Data);
			$report_data_Impression="<p><b>Impression :</b> ".nl2br($data_lab_request[0]->report_data_Impression).'</p>';
			$RawData=$RawData.$report_data_Impression;
		}

		$complete_report=$RawData;
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		//Report Show in PDF
		
		$inv_req_id=$lab_invoice_request[0]->id;
		//$complete_report=$data['lab_invoice_request'][0]->report_data;
			
		$sql="select * from hc_item_type where itype_id=".$lab_invoice_request[0]->lab_type;
		$query = $this->db->query($sql);
		$report_head_row= $query->result();
			
		$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$invoice_master[0]->invoice_code.' / Person Name :'.$data_patient_master[0]->p_fname;
			
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
			
		if($print>0)
		{
			if(count($data['lab_head_name'])>0)
			{
				$data['docname']=$data['lab_head_name'][0]->doc_name;
				$data['docedu']=$data['lab_head_name'][0]->doc_edu;
				$data['tech_name']=$data['lab_head_name'][0]->tech_name;
				$data['print_format_head_page']=$data['lab_head_name'][0]->print_format_head_page;
				$data['print_page_direct']=$data['lab_head_name'][0]->print_format_head_page;
			}else{
				$data['docname']='';
				$data['docedu']='';
				$data['tech_name']='';
				$data['print_format_head_page']='patient_lab_xray_report';
				$data['print_page_direct']='patient_lab_xray_report';
			}
			
			$folder_name='uploads/'.date('Ymd');
			
			if (!file_exists($folder_name)) {
				mkdir($folder_name, 0777, true);
				chmod($folder_name, 0777);
			}
		
			$file_name='Report'.$inv_id."-".$lab_type."-".date('dmYhis').".pdf";
			
			$filepath=$folder_name.'/'.$file_name;
			
			$data['complete_report']=$complete_report;
			$data['report_head']=$report_head;
			$data['report_header']=$Header;


			$data['print_on_type']=$print_on_type;
			$data['bar_content']=$inv_id.'-'.$lab_type;
			//load mPDF library
			$this->load->library('m_pdf');

			//$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
	
			$this->m_pdf->pdf->SetWatermarkText(H_Name);
			$this->m_pdf->pdf->showWatermarkText = false;

			$this->m_pdf->pdf->setAutoBottomMargin='pad';
				
			$filepath=$file_name;
	
			if($print_on_type=0){
				$content=$this->load->view('Lab_Panel/'.$data['print_format_head_page'],$data,TRUE);
			}else{
				$content=$this->load->view('Lab_Panel/'.$data['print_page_direct'],$data,TRUE);
			}
			
			//echo $content;
	
			//generate the PDF from the given html
			$this->m_pdf->pdf->WriteHTML($content);
	 
			//download it.
			$this->m_pdf->pdf->Output($filepath,"I");   
		}else{
			$this->load->view('Lab_Panel/lab_single_report',$data);
		}
	}
	
	public function report_compile_edit($inv_id,$lab_type,$req_id,$print=1)
	{
		$sql="select l.*,g.RepoGrp
			from lab_request l left join (lab_repo r  join lab_rgroups g on r.GrpKey=g.mstRGrpKey)
			on l.lab_repo_id=r.mstRepoKey
			where l.id=".$req_id." and l.charge_id=".$inv_id." and l.lab_type=".$lab_type."	order by g.sort_order";
		//echo $sql;
		$query = $this->db->query($sql);
		$data_lab_request= $query->result();
		
		$sql="select * from invoice_master where id=".$inv_id;
		$query = $this->db->query($sql);
		$invoice_master= $query->result();
		
		$sql="select *  
		from patient_master_exten p where  id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$data_patient_master= $query->result();

		

		
		if($invoice_master[0]->refer_by_id>0)
		{
			$sql="select * from doctor_master where id=".$invoice_master[0]->refer_by_id;
			$query = $this->db->query($sql);
			$doc_master= $query->result();
			$rdoc_name='Dr.'.$doc_master[0]->p_fname;
		}else{
			$rdoc_name=$invoice_master[0]->refer_by_other;
		}
		
		$sql="select * from ipd_master where ipd_status=0 and p_id=".$invoice_master[0]->attach_id;
		$query = $this->db->query($sql);
		$ipd_master= $query->result();
		
		$ipd_id='';
		
		if(count($ipd_master)>0)
		{
			$ipd_id='<b>IPD No. :</b> '. $ipd_master[0]->ipd_code.'<br/>';
		}
		$print_datetime=$data_lab_request[0]->Request_Date;
		
		$print_datetime=date('d-m-Y h:i:sa');
		
		if($data_lab_request[0]->collected_time<>'')
		{
			$Collection_data='
				<b>Collected : </b>'.$data_lab_request[0]->collected_time.'<br/>
				<b>Reported : </b>'.$data_lab_request[0]->reported_time;
				
		}

		$Header='
		<table border="0" cellpadding="2" cellspacing="1" style="width:100%">
				<tr>
					<td  width="50%" >
						<b>Inovice ID : </b>'.$invoice_master[0]->invoice_code.'<br/>
						<b>Patient Name : </b>'.$data_patient_master[0]->p_fname.'<br/>
						<b>Age/Sex : </b>'.$data_patient_master[0]->str_age.'/'.$data_patient_master[0]->xgender.'<br/>
						<b>UHID : </b>'.$data_patient_master[0]->p_code.'<br/>
						<b>Reffered By : </b>'.$rdoc_name.'<br/>
					</td>
					<td  width="50%" >'.$ipd_id.'<br/>
						<b>Print Date : </b>'.$print_datetime.'<br/>
						'.$Collection_data.'
					</td>
				</tr>
		';

		$Header.='
		</table>
		<hr/>';
		$RawData="";
		$group_name='';
		$first_header=1;
		
		for($i=0;$i<count($data_lab_request);++$i)
		{
			if($data_lab_request[$i]->RepoGrp==$group_name)
			{
				$groupHead='';
			}else{
				if($first_header==0)
				{
					//$groupHead='<br pagebreak="true" />';
					$groupHead='';
				}else{
					$groupHead='';
				}
				
				$groupHead.='
				<h1 style="text-align:center; vertical-align:middle">'.$data_lab_request[$i]->RepoGrp.'</h1>';
	
				$first_header=0;
			}
			
			$RawData=$RawData.$groupHead;
			$RawData=$RawData.$data_lab_request[$i]->Report_Data;
			
			$group_name=$data_lab_request[$i]->RepoGrp;
			
		}

		$complete_report=$Header.' <br/>'.$RawData;
		
		$sql="select * from lab_invoice_request where  invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request']= $query->result();
		
		$inv_req_id=0;
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		//Report Show in PDF
		
		$inv_req_id=$data['lab_invoice_request'][0]->id;
		//$complete_report=$data['lab_invoice_request'][0]->report_data;
			
		$sql="select * from hc_item_type where itype_id=".$data['lab_invoice_request'][0]->lab_type;
		$query = $this->db->query($sql);
		$report_head_row= $query->result();
			
		$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$invoice_master[0]->invoice_code.' / Person Name :'.$data_patient_master[0]->p_fname;
			
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
			
		if($print>0)
		{
			if(count($data['lab_head_name'])>0)
			{
				$docname=$data['lab_head_name'][0]->doc_name;
				$docedu=$data['lab_head_name'][0]->doc_edu;
				$tech_name=$data['lab_head_name'][0]->tech_name;
			}else{
				$docname='';
				$docedu='';
				$tech_name='';
			}
			
			$folder_name='uploads/'.date('Ymd');
			
			if (!file_exists($folder_name)) {
				mkdir($folder_name, 0777, true);
				chmod($folder_name, 0777);
			}
		
			$file_name='Report'.$inv_id."-".$lab_type."-".date('dmYhis').".pdf";
			
			$filepath=$folder_name.'/'.$file_name;
			
			$udata = array( 
						'file_name'=>$file_name,
						'file_type'=>'pdf',
						'file_path'=>$folder_name,
						'full_path'=>$filepath,
						'orig_name'=>$file_name,
						'client_name'=>'system_genrate',
						'file_ext'=>'.pdf',
						'upload_by'=> $user_name,
						'pid'=>$invoice_master[0]->attach_id,
						'ipd_id'=>$invoice_master[0]->ipd_id,
						'case_id'=>$invoice_master[0]->insurance_case_id,
						'file_desc'=>$report_head,
						'charge_id'=>$invoice_master[0]->id,
						'charge_type' => $lab_type
						);
			
			$this->load->model('File_M');
			
			$sql="select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='".$file_name."'";
			$query = $this->db->query($sql);
			$check_file_exist= $query->result();
			
			if(count($check_file_exist)<1){
				$this->File_M->insert($udata);
			}else
			{
				$this->File_M->update($udata,$check_file_exist[0]->id);
			}
			
			create_lab_report_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu,$tech_name);
			
		}else{
			$this->load->view('PathLab_Report/lab_final_print',$data);
		}
	}
	
	public function report_remove($inv_id,$lab_type,$item_id)
	{
		$this->load->model('PathLab_M');
		$this->PathLab_M->delete_request_entry($item_id);
		
		echo 'Item Removed';

	}
	
	public function Update_CombineReport()
	{
		$this->load->model('PathLab_M');
		
		$item_id=$this->input->post('item_id');
		$checked=$this->input->post('checked');
			
		$udata = array( 
						'print_combine'=> $checked
				);

		$this->PathLab_M->update_test_request($udata,$item_id);
		
		echo "Saved";

	}
	
	public function Update_Report_time()
	{
		$this->load->model('PathLab_M');
		
		$datetimepicker1=$this->input->post('datetimepicker1');
		$datetimepicker2=$this->input->post('datetimepicker2');
		$inv_id=$this->input->post('inv_id');
		$lab_type=$this->input->post('lab_type');
		
		$sql="select * from lab_invoice_request where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();
		
		if(count($lab_invoice_request)>0)
		{
			$rec_id=$lab_invoice_request[0]->id;
			
			$udata = array( 
					'collected_time'=> $datetimepicker1,
					'reported_time'=> $datetimepicker2
				);

			$this->PathLab_M->update_invoice_report($udata,$rec_id);
		}
	
		echo "Saved";
	}
	
	public function show_print_final_edit($inv_id,$lab_type,$print=0)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$sql="select * from lab_invoice_request where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request']= $query->result();
		
		$sql="select * from invoice_master where id='".$inv_id."'";
		$query = $this->db->query($sql);
		$data_invoice_master= $query->result();
		
		$sql="select * from patient_master_exten where id='".$data_invoice_master[0]->attach_id."'";
		$query = $this->db->query($sql);
		$data_person_master= $query->result();
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		$inv_req_id=0;
		if(count($data['lab_invoice_request'])>0)
		{
			$inv_req_id=$data['lab_invoice_request'][0]->id;
			$complete_report=$data['lab_invoice_request'][0]->report_data;
			
			$sql="select * from hc_item_type where itype_id=".$data['lab_invoice_request'][0]->lab_type;
			$query = $this->db->query($sql);
			$report_head_row= $query->result();
			
			$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$data_invoice_master[0]->invoice_code.' / Person Name :'.$data_person_master[0]->p_fname;
			
			$sql="select * from invoice_master where id=".$data['lab_invoice_request'][0]->invoice_id;
			$query = $this->db->query($sql);
			$report_invoice_info= $query->result();
			
			if($print>0)
			{
				if(count($data['lab_head_name'])>0)
				{
					$docname=$data['lab_head_name'][0]->doc_name;
					$docedu=$data['lab_head_name'][0]->doc_edu;
					$tech_name=$data['lab_head_name'][0]->tech_name;
				}else{
					$docname='';
					$docedu='';
					$tech_name='';
				}
				
				$folder_name='uploads/'.date('Ymd');
				
				if (!file_exists($folder_name)) {
					mkdir($folder_name, 0777, true);
					chmod($folder_name, 0777);
				}
			
				$file_name='Report'.$inv_id."-".$lab_type."-".date('Ymdhis').".pdf";
				
				$filepath=$folder_name.'/'.$file_name;
				
				$udata = array( 
							'file_name'=>$file_name,
							'file_type'=>'pdf',
							'file_path'=>$folder_name,
							'full_path'=>$filepath,
							'orig_name'=>$file_name,
							'client_name'=>'system_genrate',
							'file_ext'=>'.pdf',
							'upload_by'=> $user_name,
							'pid'=>$data_invoice_master[0]->attach_id,
							'ipd_id'=>$data_invoice_master[0]->ipd_id,
							'case_id'=>$data_invoice_master[0]->insurance_case_id,
							'file_desc'=>$report_head,
							'charge_id'=>$data_invoice_master[0]->id
							);
				
				$this->load->model('File_M');
				
				$sql="select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='".$file_name."'";
				$query = $this->db->query($sql);
				$check_file_exist= $query->result();
				
				if(count($check_file_exist)<1){
					$this->File_M->insert($udata);
				}else
				{
					$this->File_M->update($udata,$check_file_exist[0]->id);
				}

				$this->load->model('PathLab_M');
				
				$rec_id=$data['lab_invoice_request'][0]->id;
			
				$udata = array( 
						'report_print_status'=> '1',
						'report_print_time'=> date('Y-m-d h:i:s'),
				);

				$this->PathLab_M->update_invoice_report($udata,$rec_id);
				
				//create_lab_report_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu,$tech_name);
				create_Document_logo_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu,$tech_name);

				 //load mPDF library

			}else{
				$this->load->view('PathLab_Report/lab_final_print',$data);
			}
		}else{
			echo 'Error : Record not Exist in lab_invoice_request';
		}

	}

	public function final_edit_single_report($inv_id,$lab_type,$print=0)
	{
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$sql="select * from lab_invoice_request where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request']= $query->result();
		
		$sql="select * from invoice_master where id='".$inv_id."'";
		$query = $this->db->query($sql);
		$data_invoice_master= $query->result();
		
		$sql="select * from patient_master_exten where id='".$data_invoice_master[0]->attach_id."'";
		$query = $this->db->query($sql);
		$data_person_master= $query->result();
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		$inv_req_id=0;
		if(count($data['lab_invoice_request'])>0)
		{
			$inv_req_id=$data['lab_invoice_request'][0]->id;
			$complete_report=$data['lab_invoice_request'][0]->report_data;
			
			$sql="select * from hc_item_type where itype_id=".$data['lab_invoice_request'][0]->lab_type;
			$query = $this->db->query($sql);
			$report_head_row= $query->result();
			
			$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$data_invoice_master[0]->invoice_code.' / Person Name :'.$data_person_master[0]->p_fname;
			
			$sql="select * from invoice_master where id=".$data['lab_invoice_request'][0]->invoice_id;
			$query = $this->db->query($sql);
			$report_invoice_info= $query->result();
			
			if($print>0)
			{
				if(count($data['lab_head_name'])>0)
				{
					$docname=$data['lab_head_name'][0]->doc_name;
					$docedu=$data['lab_head_name'][0]->doc_edu;
					$tech_name=$data['lab_head_name'][0]->tech_name;
				}else{
					$docname='';
					$docedu='';
					$tech_name='';
				}
				
				$folder_name='uploads/'.date('Ymd');
				
				if (!file_exists($folder_name)) {
					mkdir($folder_name, 0777, true);
					chmod($folder_name, 0777);
				}
			
				$file_name='Report'.$inv_id."-".$lab_type."-".date('Ymdhis').".pdf";
				
				$filepath=$folder_name.'/'.$file_name;
				
				$udata = array( 
							'file_name'=>$file_name,
							'file_type'=>'pdf',
							'file_path'=>$folder_name,
							'full_path'=>$filepath,
							'orig_name'=>$file_name,
							'client_name'=>'system_genrate',
							'file_ext'=>'.pdf',
							'upload_by'=> $user_name,
							'pid'=>$data_invoice_master[0]->attach_id,
							'ipd_id'=>$data_invoice_master[0]->ipd_id,
							'case_id'=>$data_invoice_master[0]->insurance_case_id,
							'file_desc'=>$report_head,
							'charge_id'=>$data_invoice_master[0]->id
							);
				
				$this->load->model('File_M');
				
				$sql="select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='".$file_name."'";
				$query = $this->db->query($sql);
				$check_file_exist= $query->result();
				
				if(count($check_file_exist)<1){
					$this->File_M->insert($udata);
				}else
				{
					$this->File_M->update($udata,$check_file_exist[0]->id);
				}
				
				//create_lab_report_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu,$tech_name);
				create_Document_logo_pdf($complete_report,$filepath,'',$report_head,$docname,$docedu,$tech_name);

				 //load mPDF library

			}else{
				$this->load->view('PathLab_Report/lab_final_print',$data);
			}
		}else{
			echo 'Error : Record not Exist in lab_invoice_request';
		}

	}
	
	
	
	public function print_pdf_create($inv_id,$lab_type,$print=0,$print_on_type=0)
	{
		
		$this->load->model('PathLab_M');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		$sql="select * from lab_invoice_request where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$data['lab_invoice_request']= $query->result();

		$sql="Select max(report_edit_req_no) as no 
		from lab_request where  charge_id=".$inv_id." and lab_type=".$lab_type."	";
		$query = $this->db->query($sql);
		$data_lab_request_edit= $query->result();

		if($data_lab_request_edit[0]->no>0)
		{
			$data_lab_request_edit_str='/'.$data_lab_request_edit[0]->no;
			$note_edit_str='Caution : Please ignore the earlier report.';
		}else{
			$data_lab_request_edit_str='';
			$note_edit_str='';
		}
		
		$sql="select * from invoice_master where id='".$inv_id."'";
		$query = $this->db->query($sql);
		$data_invoice_master= $query->result();
		
		$sql="select * from patient_master_exten where id='".$data_invoice_master[0]->attach_id."'";
		$query = $this->db->query($sql);
		$data_person_master= $query->result();
		
		$sql="select * from diagnosis_head_name where d_type=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_head_name']= $query->result();
		
		$inv_req_id=0;
		if(count($data['lab_invoice_request'])>0)
		{
			$inv_req_id=$data['lab_invoice_request'][0]->id;
			$data['complete_report']=$data['lab_invoice_request'][0]->report_data;
			$data['report_header']=$data['lab_invoice_request'][0]->report_header;
			
			$sql="select * from hc_item_type where itype_id=".$data['lab_invoice_request'][0]->lab_type;
			$query = $this->db->query($sql);
			$report_head_row= $query->result();
			
			$report_head=$report_head_row[0]->group_desc.' / Invoice ID :'.$data_invoice_master[0]->invoice_code.$data_lab_request_edit_str.' / Person Name :'.$data_person_master[0]->p_fname;
			
			$sql="select * from invoice_master where id=".$data['lab_invoice_request'][0]->invoice_id;
			$query = $this->db->query($sql);
			$report_invoice_info= $query->result();
			
			if($print>0)
			{
				$page_format='patient_lab_report';
				$page_format_head='patient_lab_report';

				if(count($data['lab_head_name'])>0)
				{
					$docname=$data['lab_head_name'][0]->doc_name;
					$docedu=$data['lab_head_name'][0]->doc_edu;
					$tech_name=$data['lab_head_name'][0]->tech_name;
					$page_format=$data['lab_head_name'][0]->print_format_head_page;
					$page_format_head=$data['lab_head_name'][0]->print_page_direct;
				}else{
					$docname='';
					$docedu='';
					$tech_name='';
				}

				$data['docname']=$docname;
				$data['docedu']=$docedu;
				$data['tech_name']=$tech_name;
				
				$folder_name='uploads/'.date('Ymd');
				
				if (!file_exists($folder_name)) {
					mkdir($folder_name, 0777, true);
					chmod($folder_name, 0777);
				}
			
				$file_name='Report-'.$inv_id."-".$lab_type."-".date('Ymd-his').".pdf";
				
				$filepath=$folder_name.'/'.$file_name;
				
				$udata = array( 
							'file_name'=>$file_name,
							'file_type'=>'pdf',
							'file_path'=>$folder_name,
							'full_path'=>$filepath,
							'orig_name'=>$file_name,
							'client_name'=>'system_genrate',
							'file_ext'=>'.pdf',
							'upload_by'=> $user_name,
							'pid'=>$data_invoice_master[0]->attach_id,
							'ipd_id'=>$data_invoice_master[0]->ipd_id,
							'case_id'=>$data_invoice_master[0]->insurance_case_id,
							'file_desc'=>$report_head,
							'charge_id'=>$data_invoice_master[0]->id
							);
				
				$this->load->model('File_M');
				
				$sql="select * from file_upload_data where Date(insert_date)=Date(sysdate()) and  file_name='".$file_name."'";
				$query = $this->db->query($sql);
				$check_file_exist= $query->result();
				
				$file_ID=0;

				if(count($check_file_exist)<1){
					$file_ID=$this->File_M->insert($udata);

				}else
				{
					$this->File_M->update($udata,$check_file_exist[0]->id);
					$file_ID=$check_file_exist[0]->id;
				}

				$Lab_reg_data = array( 
					'file_id'=>$file_ID,
				);

				$this->PathLab_M->update_invoice_report($Lab_reg_data,$data['lab_invoice_request'][0]->id);

				$data['report_head']=$report_head;
				
				//load mPDF library
				$this->load->library('m_pdf');


				$data['bar_content']=$inv_id.'-'.$lab_type;
				$data["print_on_type"]=$print_on_type;
				$data["data_lab_request_edit_str"]=$data_lab_request_edit_str;
				$data['note_edit_str']=$note_edit_str;

				if($print_on_type==1)
				{
					$content=$this->load->view('Lab_Panel/'.$page_format_head,$data,TRUE);
				}else{
					$content=$this->load->view('Lab_Panel/'.$page_format,$data,TRUE);
				}
				

				//$this->m_pdf->pdf->curlAllowUnsafeSslRequests = true;

				$this->m_pdf->pdf->shrink_tables_to_fit = 1;

		        //generate the PDF from the given html
		        $this->m_pdf->pdf->WriteHTML($content);
		 		        
		        $this->m_pdf->pdf->Output($filepath,"I");
		      
				//echo $content;
			}else{
				$this->load->view('PathLab_Report/lab_final_print',$data);
			}
		}else{
			echo 'Error : Record not Exist in lab_invoice_request';
		}

	}
	

	public function add_test_repo($repo_id,$test_id)
	{
		$sql="select * from lab_repotests where mstRepoKey=".$repo_id." and mstTestKey=".$test_id;
		$query = $this->db->query($sql);
		$data['lab_repotests_chk']= $query->result();

		if(count($data['lab_repotests_chk'])<1)
		{
			$this->load->model('PathLab_M');
			
			$sql="select Max(EOrder) as MEOrder from lab_repotests where mstRepoKey=".$repo_id;
			$query = $this->db->query($sql);
			$data['lab_repotests']= $query->result();
			
			$MEOrder=0;
			if(count($data['lab_repotests'])>0)
			{
				$MEOrder=$data['lab_repotests'][0]->MEOrder;
			}
			
			if($MEOrder === NULL)
			{
				$MEOrder=1;
			}else{
				$MEOrder=$MEOrder+1;
			}
			
			$udata = array( 
					'mstRepoKey'=> $repo_id,
					'mstTestKey'=> $test_id,
					'EOrder'=> $MEOrder
					);
			
			$sortorder_insert_id=$this->PathLab_M->insert_item_sortorder($udata);
			
			$showcontent=Show_Alert('success','Saved','Add successfully');
			
		}else{
			$sortorder_insert_id=0;
			$showcontent=Show_Alert('error','Already....','Already Added');
		}
		
		$rvar=array(
		'insertid' => $sortorder_insert_id,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function remove_test_item($repo_id,$test_id)
	{
		$this->db->delete("lab_repotests", "mstRepoKey=".$repo_id." and mstTestKey=".$test_id);
		
	}
	
	public function test_item_search()
	{
		$Test_Name=$this->input->post('input_Test_name');
		$repo_id=$this->input->post('repo_id');
		
		$sql="select * from lab_tests where Test like '%".$Test_Name."%'";
		$query = $this->db->query($sql);
		$search_result= $query->result();

		echo '<table>';
		for($i=0;$i<count($search_result);++$i)
		{
			echo '<tr><td><a href="javascript:add_test('.$repo_id.','.$search_result[$i]->mstTestKey.');">'.$search_result[$i]->Test.'</a></td><td>[ '.$search_result[$i]->TestID.' ]</td></tr>';
		}
		
		echo '</table>';
		
	}
	
	public function lab_file_scan($repo_id,$test_id)
	{
		$data['repo_id']=$repo_id;
		$data['test_id']=$test_id;
		
		$this->load->view('PathLab_Report/lab_file_scan',$data);
	}

	public function lab_file_scan_complete($repo_id,$lab_type)
	{
		$data['repo_id']=$repo_id;
		$data['lab_type']=$lab_type;
		
		$this->load->view('PathLab_Report/lab_file_scan_whole',$data);
	}
	
	public function lab_file_upload($repo_id,$test_id)
	{
		$data['repo_id']=$repo_id;
		$data['test_id']=$test_id;
		
		$this->load->view('PathLab_Report/lab_file_upload',$data);
	}

	public function lab_file_upload_complete($inv_id,$lab_type)
	{
		$data['inv_id']=$inv_id;
		$data['lab_type']=$lab_type;
		
		$this->load->view('PathLab_Report/lab_file_upload_complete',$data);
	}
	
	public function save_image($repo_id,$test_id)
	{
		$filename =  $repo_id.'-'.$test_id.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg|dcm';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		if (!$this->upload->do_upload('webcam')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
			
			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
			
			$sql="select * from lab_request where id=".$repo_id;
			$query = $this->db->query($sql);
			$lab_request_result= $query->result();
						
			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			if(count($lab_request_result)>0)
			{
				$pid=$lab_request_result[0]->patient_id;
				$ipd_id=$lab_request_result[0]->ipd_id;
				$org_id=$lab_request_result[0]->org_id;
				$charge_type=$lab_request_result[0]->lab_type;
				$inv_id=$lab_request_result[0]->charge_id;
			}

			$udata = array( 
					'repo_id'=> $repo_id,
					'repo_test_id'=> $test_id,
					'upload_by'=> $user_name,
					'pid'=>$pid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'charge_type'=> $charge_type,
					'charge_id'=>$inv_id,
				);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
			}
			
			echo $filename;
		}
	}

	public function save_image_complete($inv_id,$lab_type)
	{
		$filename =  $inv_id.'-'.$lab_type.'-'.time() . '.jpg';

		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;

		//echo $folder_name;
		
		$config['upload_path'] = $folder_name;
		$config['allowed_types'] = 'gif|jpg|png|jpeg';
		
		//$new_name = time().$_FILES["webcam"]['name'];
		$config['file_name'] = $filename;

		$this->load->library('upload', $config);
		
		if (!$this->upload->do_upload('webcam')) {
			$error = array('error' => $this->upload->display_errors());
			echo $error['error'];
		}else{
			$data = array('upload_data' => $this->upload->data()); 
			
			$this->load->model('File_M');
			
			$file_insert_id=$this->File_M->insert($data['upload_data']);
			
			$sql="select * from lab_request where charge_id=$inv_id and lab_type=$lab_type" ;
			$query = $this->db->query($sql);
			$lab_request_result= $query->result();
	
						
			$pid=0;
			$ipd_id=0;
			$org_id=0;
			
			
			if(count($lab_request_result)>0)
			{
				$pid=$lab_request_result[0]->patient_id;
				$ipd_id=$lab_request_result[0]->ipd_id;
				$org_id=$lab_request_result[0]->org_id;
				$charge_type=$lab_request_result[0]->lab_type;
				$repo_id=$lab_request_result[0]->id;
			}

			$udata = array( 
					'repo_id'=> $repo_id,
					'upload_by'=> $user_name,
					'pid'=>$pid,
					'ipd_id'=>$ipd_id,
					'case_id'=>$org_id,
					'charge_type'=> $lab_type,
					'charge_id'=>$inv_id,
					);

			if($file_insert_id>0)
			{
				$this->File_M->update($udata,$file_insert_id);
			}
			
			echo $filename;
		}
	}
	
	public function process_ajax()
	{
		$output_dir = "uploads/";

		if(isset($_FILES["myfile"]))
		{
			$ret = array();

			$error = $_FILES["myfile"]["error"];

			// upload single file
			if(!is_array($_FILES["myfile"]["name"])) //single file
			{
				$fileName = $_FILES["myfile"]["name"];
				move_uploaded_file($_FILES["myfile"]["tmp_name"],$output_dir.$fileName);
				$ret[]= $fileName;
			}
			else
			{
				// Handle Multiple files
				$fileCount = count($_FILES["myfile"]["name"]);
				for($i=0; $i<$fileCount; $i++)
				{
					$fileName = $_FILES["myfile"]["name"][$i];
					move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$output_dir.$fileName);
					$ret[]= $fileName;
				}
			}
			// output file names as comma seperated strings to display status
			echo json_encode($ret);
		}
	}
	
	public function lab_file_list($repo_id,$lab_type)
	{
		$sql="select * from file_upload_data 
		where repo_id=".$repo_id." and repo_test_id=".$lab_type;
		$query = $this->db->query($sql);
		$data['lab_file_list']= $query->result();
		
		$this->load->view('PathLab_Report/lab_report_file_show',$data);

	}

	public function lab_file_list_all($inv_id,$lab_type)
	{
		$sql="select * from file_upload_data 
		where charge_id=$inv_id and charge_type=$lab_type";
		$query = $this->db->query($sql);
		$data['lab_file_list']= $query->result();
		
		

		$this->load->view('PathLab_Report/lab_report_file_show',$data);

	}
	
	public function render_file($file_id)
	{
		$sql="select * from file_upload_data where id=".$file_id;
		$query = $this->db->query($sql);
		$data['lab_file_details']= $query->result();

		$this->load->view('PathLab_Report/lab_file_show',$data);
	}
	
	public function lab_files_upload($repo_id,$test_id)
	{
		
		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$output_dir = $folder_name;

		if(isset($_FILES["myfile"]))
		{
			$ret = array();

			$error = $_FILES["myfile"]["error"];

			// upload single file
			if(!is_array($_FILES["myfile"]["name"])) //single file
			{
				$filename =  $repo_id.'-'.$test_id.'-'.time() .$_FILES["myfile"]["name"];
				$config['upload_path'] = $folder_name;
				$config['file_name'] = $filename;
				$config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc';
				
				$this->load->library('upload', $config);
				echo $filename;
				//move_uploaded_file($_FILES["myfile"]["tmp_name"],$output_dir.$fileName);
				
				if (!$this->upload->do_upload('myfile')) {
					$error = array('error' => $this->upload->display_errors());
					echo $error['error'];
				}else{
					$data = array('upload_data' => $this->upload->data()); 
					$this->load->model('File_M');
					$file_insert_id=$this->File_M->insert($data['upload_data']);
					
					$sql="select * from lab_request where id=".$repo_id;
					$query = $this->db->query($sql);
					$lab_request_result= $query->result();

					$pid=0;
					$ipd_id=0;
					$org_id=0;
					
					if(count($lab_request_result)>0)
					{
						$pid=$lab_request_result[0]->patient_id;
						$ipd_id=$lab_request_result[0]->ipd_id;
						$org_id=$lab_request_result[0]->org_id;
						$charge_type=$lab_request_result[0]->lab_type;
						$inv_id=$lab_request_result[0]->charge_id;
					}

					$udata = array( 
							'repo_id'=> $repo_id,
							'repo_test_id'=> $test_id,
							'charge_type'=> $charge_type,
							'charge_id'=>$inv_id,
							'upload_by'=> $user_name,
							'pid'=>$pid,
							'ipd_id'=>$ipd_id,
							'case_id'=>$org_id
							);

					if($file_insert_id>0)
					{
						$this->File_M->update($udata,$file_insert_id);
					}
					
				}
				
				$ret[]= $_FILES["myfile"]["name"];
			}
			else
			{
				// Handle Multiple files
				$fileCount = count($_FILES["myfile"]["name"]);
				for($i=0; $i<$fileCount; $i++)
				{
					$filename =  $repo_id.'-'.$test_id.'-'.time() .$_FILES["myfile"]["name"][$i];
					
					//$fileName = $_FILES["myfile"]["name"][$i];
					//move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$output_dir.$fileName);
					
					if (!$this->upload->do_upload($_FILES["myfile"]["tmp_name"][$i])) {
						$error = array('error' => $this->upload->display_errors());
						echo $error['error'];
					}else{
						$data = array('upload_data' => $this->upload->data()); 
						$this->load->model('File_M');
						$file_insert_id=$this->File_M->insert($data['upload_data']);
						
						$sql="select * from lab_request where id=".$repo_id;
						$query = $this->db->query($sql);
						$lab_request_result= $query->result();

						$pid=0;
						$ipd_id=0;
						$org_id=0;
						
						if(count($lab_request_result)>0)
						{
							$pid=$lab_request_result[0]->patient_id;
							$ipd_id=$lab_request_result[0]->ipd_id;
							$org_id=$lab_request_result[0]->org_id;
							$charge_type=$lab_request_result[0]->lab_type;
							$inv_id=$lab_request_result[0]->charge_id;
						}

						$udata = array( 
								'repo_id'=> $repo_id,
								'repo_test_id'=> $test_id,
								'charge_type'=> $charge_type,
								'charge_id'=>$inv_id,
								'upload_by'=> $user_name,
								'pid'=>$pid,
								'ipd_id'=>$ipd_id,
								'case_id'=>$org_id
								);

						if($file_insert_id>0)
						{
							$this->File_M->update($udata,$file_insert_id);
						}
					}
					
					$ret[]= $_FILES["myfile"]["name"][$i];
				}
			}
			// output file names as comma seperated strings to display status
			echo json_encode($ret);
		}
	}

	public function lab_files_upload_complete($inv_id,$lab_type)
	{
		
		$folder_name='uploads/'.date('Ymd');
		
		if (!file_exists($folder_name)) {
			mkdir($folder_name, 0777, true);
			chmod($folder_name, 0777);
		}
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$output_dir = $folder_name;

		if(isset($_FILES["myfile"]))
		{
			$ret = array();

			$error = $_FILES["myfile"]["error"];

			// upload single file
			if(!is_array($_FILES["myfile"]["name"])) //single file
			{
				$filename =  $inv_id.'-'.$lab_type.'-'.time() .$_FILES["myfile"]["name"];
				$config['upload_path'] = $folder_name;
				$config['file_name'] = $filename;
				$config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc';
				
				$this->load->library('upload', $config);
				echo $filename;
				//move_uploaded_file($_FILES["myfile"]["tmp_name"],$output_dir.$fileName);
				
				if (!$this->upload->do_upload('myfile')) {
					$error = array('error' => $this->upload->display_errors());
					echo $error['error'];
				}else{
					$data = array('upload_data' => $this->upload->data()); 
					$this->load->model('File_M');
					$file_insert_id=$this->File_M->insert($data['upload_data']);
					
					$sql="select * from lab_request where charge_id=$inv_id and lab_type=$lab_type" ;
					$query = $this->db->query($sql);
					$lab_request_result= $query->result();

					$pid=0;
					$ipd_id=0;
					$org_id=0;
					
					if(count($lab_request_result)>0)
					{
						$pid=$lab_request_result[0]->patient_id;
						$ipd_id=$lab_request_result[0]->ipd_id;
						$org_id=$lab_request_result[0]->org_id;
						$repo_id=$lab_request_result[0]->id;

						
					}

					$udata = array( 
							'repo_id'=> $repo_id,
							'charge_type'=> $lab_type,
							'charge_id'=>$inv_id,
							'upload_by'=> $user_name,
							'pid'=>$pid,
							'ipd_id'=>$ipd_id,
							'case_id'=>$org_id
							);

					if($file_insert_id>0)
					{
						$this->File_M->update($udata,$file_insert_id);
					}
					
				}
				
				$ret[]= $_FILES["myfile"]["name"];
			}
			else
			{
				// Handle Multiple files
				$fileCount = count($_FILES["myfile"]["name"]);
				for($i=0; $i<$fileCount; $i++)
				{
					$filename =  $inv_id.'-'.$lab_type.'-'.time() .$_FILES["myfile"]["name"][$i];
					
					//$fileName = $_FILES["myfile"]["name"][$i];
					//move_uploaded_file($_FILES["myfile"]["tmp_name"][$i],$output_dir.$fileName);
					
					if (!$this->upload->do_upload($_FILES["myfile"]["tmp_name"][$i])) {
						$error = array('error' => $this->upload->display_errors());
						echo $error['error'];
					}else{
						$data = array('upload_data' => $this->upload->data()); 
						$this->load->model('File_M');
						$file_insert_id=$this->File_M->insert($data['upload_data']);
						
						$sql="select * from lab_request where id=".$inv_id;
						$query = $this->db->query($sql);
						$lab_request_result= $query->result();

						$pid=0;
						$ipd_id=0;
						$org_id=0;
						
						if(count($lab_request_result)>0)
						{
							$pid=$lab_request_result[0]->patient_id;
							$ipd_id=$lab_request_result[0]->ipd_id;
							$org_id=$lab_request_result[0]->org_id;
						}

						$udata = array( 
								'repo_id'=> $repo_id,
								'charge_type'=> $lab_type,
								'upload_by'=> $user_name,
								'pid'=>$pid,
								'ipd_id'=>$ipd_id,
								'case_id'=>$org_id
								);

						if($file_insert_id>0)
						{
							$this->File_M->update($udata,$file_insert_id);
						}
					}
					
					$ret[]= $_FILES["myfile"]["name"][$i];
				}
			}
			// output file names as comma seperated strings to display status
			echo json_encode($ret);
		}
	}
	
	
	 public function send_sms_lab_report($inv_id,$lab_type)
    {
		$sql="select * from lab_invoice_request where invoice_id='".$inv_id."' and lab_type='".$lab_type."'";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();

		$file_id=0;

		if(count($lab_invoice_request)>0)
		{
			$file_id=$lab_invoice_request[0]->file_id;
		
			$file_id_en=encode_dst($file_id);

			$message="Your Report is ready. click on ".base_url()."PhoneSMS/show_lab_report/".$file_id_en.' From Apollo Diag.&Path. Lab';
			$message = urlencode($message);// urlencode your message

			//echo $message;

			$tomobile=$this->input->post('sms_number');

			//Send SMS			
			$url="https://www.way2sms.com/api/v1/sendCampaign";
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_POST, 1);// set post data to true
			curl_setopt($curl, CURLOPT_POSTFIELDS, "apikey=B7DYP7GU8K92YJ8ZQGE6DWCUM1DXGOBS&secret=JG534P9WPHQ0Z9H7&usetype=prod&phone=$tomobile&senderid=DSTECH&message=$message");// post data
			// query parameter values must be given without squarebrackets.
			 // Optional Authentication:
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl);
			curl_close($curl);

			echo 'Message Send.';
		}else{
			echo 'Report Not Ready';
		}

	}
	

	public function lab_tab_2_process($req_id)
	{
		$sql="select * from lab_request where id=".$req_id;
		$query = $this->db->query($sql);
        $labreport_entry= $query->result();
		
		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.''. $user->last_name;
		
		$insert_id=0;
		
		$this->load->model('PathLab_M');
		
		if(count($labreport_entry)>0)
		{
			
			$sql="select r.mstRepoKey,t.mstTestKey,j.EOrder,t.Result
				from lab_repo r join lab_repotests j join lab_tests t 
				on r.mstRepoKey=j.mstRepoKey and j.mstTestKey=t.mstTestKey
				where r.mstRepoKey=".$labreport_entry[0]->lab_repo_id."	order by j.EOrder";
		
			$query = $this->db->query($sql);
			$chkdata= $query->result();
			
			for ($i = 0; $i < count($chkdata); ++$i)
			{
			
				$sql="select * from lab_request_item 
				where lab_request_id='".$req_id."' 	and lab_repo_id='".$chkdata[$i]->mstRepoKey."' 
				and lab_test_id='".$chkdata[$i]->mstTestKey."'";
					

				$query = $this->db->query($sql);
				$chktestlist= $query->result();
				
				if(count($chktestlist)<1)
				{
					$udata = array( 
						'lab_request_id'=> $req_id,
						'lab_repo_id'=> $chkdata[$i]->mstRepoKey,
						'lab_test_id'=> $chkdata[$i]->mstTestKey,
						);
					
					$insert_id=$this->PathLab_M->insert_test_entry($udata);
				}
			}
		}

		$sql=	"select d.mstTestKey,d.Test,d.TestID,d.Result,d.Formula,d.VRule,d.VMsg,d.Unit, d.FixedNormals,
				i.lab_test_value,i.lab_test_remark,i.id ,s.EOrder,
				group_concat(concat(o.id,':',o.option_text,':',o.option_value) ORDER BY o.sort_id) as option_value,
				d.Formula
				from (lab_request_item i join lab_tests d join lab_repotests s 
				on i.lab_test_id=d.mstTestKey and d.mstTestKey=s.mstTestKey and i.lab_repo_id=s.mstRepoKey)
				left join lab_tests_option o on d.mstTestKey=o.mstTestKey
				where i.lab_request_id=".$req_id." group by d.mstTestKey order by s.EOrder";

		$query = $this->db->query($sql);
		$data['lab_request_item_entry']= $query->result();
		
		$sql="select * from lab_request where id=".$req_id;
		$query = $this->db->query($sql);
		$data['lab_request_master']= $query->result();
		
		$this->load->view('PathLab_Report/lab_test_entry',$data);

	}


	public function report_edit_request_show($repo_id)
	{
		$sql="select * from lab_request where id=".$repo_id;
		$query = $this->db->query($sql);
		$data['lab_request']= $query->result();

		$sql="SELECT s.id,m.analytical_id,
			Concat(s.analytical_faults,'[',m.analytical_name,']' ) AS log_type
			from   lab_log_analytical_type m JOIN lab_log_type_master s 
			ON m.analytical_id=s.analytical_faults_type";
		$query = $this->db->query($sql);
		$data['lab_log_type_master']= $query->result_array();

		$data['Faults_id']=1;

		$data['repo_id']=$repo_id;
		
		$this->load->view('PathLab_Report/lab_report_edit_request',$data);

	}


	public function report_edit_request()
	{
		$this->load->model('PathLab_M');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:i:s').']';
		
		$sql="select * from lab_log_type_master where id=".$this->input->post('cbo_sub_Analytical');
		$query = $this->db->query($sql);
		$lab_log_type_master= $query->result();

		$Analytical_text="";
		$sub_Analytical_text="";
		$log_type_id="0";

		if(count($lab_log_type_master)>0)
		{
			$log_type_id=$lab_log_type_master[0]->analytical_faults_type;
			$sub_Analytical_text=$lab_log_type_master[0]->analytical_faults;
			

			$sql="select * from lab_log_analytical_type where analytical_id=".$lab_log_type_master[0]->analytical_faults_type;
			$query = $this->db->query($sql);
			$lab_log_analytical_type= $query->result();

			$Analytical_text=$lab_log_analytical_type[0]->analytical_name; 
		}
		
		$udata = array( 
						'log_by_id'=> $user_id,
						'log_by'=> $user_name,
						'log_type_id'=> $log_type_id,
						'log_type'=> $Analytical_text,
						'log_Faults_id'=> $this->input->post('cbo_sub_Analytical'),
						'log_Faults'=> $sub_Analytical_text,
						'comments'=> $this->input->post('other_reason'),
						'lab_repo_id'=>$this->input->post('repo_id'),
					);

		$insert_id=$this->PathLab_M->insert_lab_log($udata);
		if($insert_id>0)
		{
			$showcontent="Request to OPEN Report Done";
			$id_key=$this->input->post('repo_id');

			$sql="select * from lab_request where id=".$id_key;
			$query = $this->db->query($sql);
			$lab_request= $query->result();

			$report_edit_req_no=$lab_request[0]->report_edit_req_no;

			$report_edit_req_no =$report_edit_req_no+1;

			$data = array( 
				'status'=> '1',
				'report_edit_req_no'=>$report_edit_req_no,
			);

			$this->PathLab_M->update_test_request($data,$id_key);

		}else{
			$showcontent="Some Error, Please Contact to Admin";
		}

		$rvar=array(
		'insertid' => $insert_id,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;

	}

	public function update_lab_no()
	{
		$this->load->model('PathLab_M');
		
		$lab_req_id=$this->input->post('lab_req_id');
		$inputLabNo=$this->input->post('inputLabNo');

		$user = $this->ion_auth->user()->row();
		$user_id = $user->id;
		$user_name = $user->first_name.' '. $user->last_name.'['.$user_id.']['.date('d-m-Y h:i:s').']';
		
		$sql="select * from lab_invoice_request where id=$lab_req_id ";
		$query = $this->db->query($sql);
		$lab_invoice_request= $query->result();
		
		if(count($lab_invoice_request)>0)
		{
			$udata = array( 
					'lab_test_no'=> $inputLabNo,
			);

			$this->PathLab_M->update_invoice_report($udata,$lab_req_id);
		}
	
		echo "Saved";
	}
	
}