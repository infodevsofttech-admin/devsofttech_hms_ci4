<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Doc_Admin extends MY_Controller {
    public function __construct()
	{
		parent::__construct();
	}

	

	public function doc_list()
	{
		$sql="select * from doc_format_master where active=1";
		$query = $this->db->query($sql);
        $data['doc_master']= $query->result();

		echo 'Test';


		$this->load->view('DocAdmin/doc_list',$data);
	}
	
	public function docedit_load($doc_id=0)
	{
		$sql="select * from doc_format_master where df_id=".$doc_id;
		$query = $this->db->query($sql);
        $data['doc_master']= $query->result();
		
		$sql="select m.df_id,m.doc_name,s.input_name,s.input_code,s.short_order,s.id as item_id
			from (doc_format_master m join doc_format_sub s on m.df_id=s.doc_format_id)
			where m.df_id=".$doc_id. " order by s.short_order" ;
		$query = $this->db->query($sql);
        $data['doc_Item_List']= $query->result();

        $sql="select * from doc_pre_input";
		$query = $this->db->query($sql);
        $data['doc_pre_input']= $query->result();
		
		
		$sql="select * from color ";
		$query = $this->db->query($sql);	
		$data['color_name']= $query->result();
		
		$this->load->view('DocAdmin/doc_master_edit',$data);
	}
	
	public function report_insert()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('DocFormat_M');
		
		$FormRules = array(
                array(
                    'field' => 'input_docname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]|is_unique[lab_repo.Title]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		$insertid=0;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$id_key=$this->input->post('df_id');
			$data = array( 
						'doc_name'=> $this->input->post('input_docname'),
						'doc_desc'=> $this->input->post('input_doc_desc'),
						'doc_raw_format'=> $this->input->post('HTMLData')
						);
			$insertid=$this->DocFormat_M->insert_master($data);
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
	
	public function report_update()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('DocFormat_M');
		
		$id_key=$this->input->post('df_id');
		
		$update_record=0;
		
		$FormRules = array(
                array(
                    'field' => 'input_docname',
                    'label' => 'Name',
                    'rules' => 'required|min_length[1]|max_length[100]|is_unique[lab_repo.Title]'
                )
        );

		$this->form_validation->set_rules($FormRules);
		
		if ($this->form_validation->run() == TRUE)
        {
			$data = array( 
					'doc_name'=> $this->input->post('input_docname'),
					'doc_desc'=> $this->input->post('input_doc_desc'),
					'doc_raw_format'=> $this->input->post('HTMLData')
					);

			$this->DocFormat_M->update_master($data,$id_key);
			
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
	
	public function doc_input_list($doc_id)
	{
		$sql="select m.df_id,s.id,m.doc_name,s.input_name,s.input_code,s.short_order,s.id as item_id
			from (doc_format_master m join doc_format_sub s on m.df_id=s.doc_format_id)
			where m.df_id=".$doc_id. " order by s.short_order" ;
		$query = $this->db->query($sql);
        $data['doc_Item_List']= $query->result();
		
		$data['doc_id']=$doc_id;
		
		$this->load->view('DocAdmin/doc_input_list',$data);
	}
	
	public function input_parameter_load($item_id,$doc_id)
	{
		$sql="select m.df_id,s.id,m.doc_name,s.input_name,s.input_code,s.short_order,s.id as item_id,s.input_type,s.input_default_value
			from (doc_format_master m join doc_format_sub s on m.df_id=s.doc_format_id)
			where m.df_id=".$doc_id. " and s.id=".$item_id." order by s.short_order" ;
		$query = $this->db->query($sql);
        $data['input_parameter']= $query->result();
				
		$data['doc_id']=$doc_id;
		
		$this->load->view('DocAdmin/doc_item_edit',$data);
	}
	
	public function input_parameter_edit()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('DocFormat_M');
		
		$doc_id=$this->input->post('doc_id');
		$doc_sub_id=$this->input->post('doc_sub_id');
		$input_input_code=$this->input->post('input_input_code');
		
		$sql="select * from doc_format_sub where id=".$doc_sub_id." and input_code='".$input_input_code."' and doc_format_id=".$doc_id;
		$query = $this->db->query($sql);
        $doc_input_list= $query->result();
				
		$FormRules = array(
                array(
                    'field' => 'input_input_name',
                    'label' => 'Input Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_input_code',
                    'label' => 'Input Code',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
        );
		
		$this->form_validation->set_rules($FormRules);
		
		$update_value=1;
		$showcontent="";
		if ($this->form_validation->run() == TRUE)
        {
			$udata = array( 
					'doc_format_id'=> $this->input->post('doc_id'),
					'input_name'=> $this->input->post('input_input_name'),
					'input_code'=> $this->input->post('input_input_code'),
					'input_type'=> $this->input->post('input_type'),
					'input_default_value'=> $this->input->post('input_default_value')   
					);

			$this->DocFormat_M->update_sub($udata,$doc_sub_id);

			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$update_value=0;
			
			if(count($doc_input_list)>0)
			{
				$showcontent =Show_Alert('danger','Error','Already Exist');
			}else{
				$showcontent =Show_Alert('danger','Error',validation_errors());
			}
			
		}
		
		$rvar=array(
		'update_value' => $update_value,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function input_parameter_add()
	{
		if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
		
		$this->load->model('DocFormat_M');
		
		$doc_id=$this->input->post('doc_id');
		$input_input_code=$this->input->post('input_input_code');
		
		$sql="select * from doc_format_sub where input_code='".$input_input_code."' and doc_format_id=".$doc_id;
		$query = $this->db->query($sql);
        $doc_input_list= $query->result();
		
		$FormRules = array(
                array(
                    'field' => 'input_input_name',
                    'label' => 'Input Name',
                    'rules' => 'required|min_length[1]|max_length[30]'
                ),
				array(
                    'field' => 'input_input_code',
                    'label' => 'Input Code',
                    'rules' => 'required|min_length[1]|max_length[30]'
                )
        );
		
		$this->form_validation->set_rules($FormRules);
		
		$insert_id=0;
		$showcontent="";
		if ($this->form_validation->run() == TRUE  && count($doc_input_list)==0 )
        {
			$sql="select max(short_order) as max_short_order from doc_format_sub where doc_format_id=".$doc_id;
			$query = $this->db->query($sql);
			$doc_short_order= $query->result();
			
			$MEOrder=0;
			if(count($doc_short_order)>0)
			{
				$MEOrder=$doc_short_order[0]->max_short_order;
			}
			
			if($MEOrder === NULL)
			{
				$MEOrder=1;
			}else{
				$MEOrder=$MEOrder+1;
			}
			
			$udata = array( 
					'doc_format_id'=> $this->input->post('doc_id'),
					'input_name'=> $this->input->post('input_input_name'),
					'input_code'=> $this->input->post('input_input_code'),
					'input_type'=> $this->input->post('input_type'),
					'input_default_value'=> $this->input->post('input_default_value') ,
					'short_order'=> $MEOrder
					);

			$insert_id=$this->DocFormat_M->insert_sub($udata);

			$showcontent=Show_Alert('success','Saved','Data Saved successfully');
		}else{
			$insert_id=0;
			if(count($doc_input_list)>0)
			{
				$showcontent =Show_Alert('danger','Error','Already Exist');
			}else{
				$showcontent =Show_Alert('danger','Error',validation_errors());
			}
			
		}
	
		
		$rvar=array(
		'insert_id' => $insert_id,
		'showcontent'=> $showcontent
		);
		
		$encode_data = json_encode($rvar);
		echo $encode_data;
		
	}
	
	public function change_sort_item($doc_id,$option_id,$current,$change_option_id,$change)
	{
		$this->load->model('DocFormat_M');
		
		$udata = array( 
					'short_order'=> 0
					);
		$this->DocFormat_M->update_sub($udata,$option_id);
		
		$udata = array( 
					'short_order'=> $current
					);
		$this->DocFormat_M->update_sub($udata,$change_option_id);
				
		$udata = array( 
					'short_order'=> $change
					);
		$this->DocFormat_M->update_sub($udata,$option_id);
		
		$sql="select m.df_id,m.doc_name,s.input_name,s.input_code,s.short_order,s.id as item_id
			from (doc_format_master m join doc_format_sub s on m.df_id=s.doc_format_id)
			where m.df_id=".$doc_id. " order by s.short_order" ;
		$query = $this->db->query($sql);
        $data['doc_Item_List']= $query->result();
		
		$data['doc_id']=$doc_id;
		
		$this->load->view('DocAdmin/doc_input_list',$data);
		
	}
	
	public function remove_input_item($doc_id,$doc_sub_id)
	{
		$this->db->delete("doc_format_sub", "doc_format_id=".$doc_id." and id=".$doc_sub_id);
		
	}
	
	
}