<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Doctor extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
	}
        
    public function adddoctor()
    {
        $this->load->view('Doctor_Profile/Doctor_V');
    } 
       
    public function search()
	{
	
        $sql = "SELECT * FROM doctor_master ";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
        
        $this->load->view('Doctor_Profile/Doctor_Search_V',$data);
      
	}
	
    function doctor_record($pno)
    {
        
        $sql="select * from doctor_master where id=$pno";
        $query = $this->db->query($sql);
        $data['data']= $query->result();
        
        $sql="select m.SpecName,m.id,d.doc_id,d.med_spec_id,d.id as doc_spec_id 
        from med_spec m join doc_spec d on m.id =d.med_spec_id where d.doc_id=".$pno;
        $query = $this->db->query($sql);
        $data['doc_spec_a']= $query->result();
        
        $sql="select * from med_spec order by SpecName";
		$query = $this->db->query($sql);
        $data['doc_spec_l']= $query->result();
		
		$sql="select * from doc_fee_type order by fee_type";
		$query = $this->db->query($sql);
        $data['doc_fee_type']= $query->result();
		
		$sql="select d.id ,f.fee_type ,d.doc_fee_desc,d.amount
				from doc_opd_fee d right join doc_fee_type f on d.doc_fee_type=f.id and d.doc_id=".$pno;
		$query = $this->db->query($sql);
        $data['doc_fee_list']= $query->result();

               
        $this->load->view('Doctor_Profile/Doctor_profile_V',$data);
    }
    
    function doctor_record_spec()
    {
        
        $this->load->model('Doctor_M');
                
        if($this->input->post('isadd')==1)
        {
            $sql="select * from doc_spec where doc_id='".$this->input->post('doc_id')."' and med_spec_id='".$this->input->post('doc_spec')."'";
            $query = $this->db->query($sql);
            $no_rec= $query->num_rows();
            if($no_rec==0)
            {
                $data = array( 
                'doc_id' => $this->input->post('doc_id'), 
                'med_spec_id' => $this->input->post('doc_spec')
    	       ); 
                $inser_id=$this->Doctor_M->insert_spec($data); 
                $rvar=array(
                'insertid' =>$inser_id
                ); 
            }
                
        }else{
             
            $this->db->delete("doc_spec", "id=".$this->input->post('doc_spec_id'));
        }
        
        
        $sql="select m.SpecName,m.id,d.doc_id,d.med_spec_id,d.id as doc_spec_id from med_spec m join doc_spec d on m.id =d.med_spec_id where d.doc_id=".$this->input->post('doc_id');
        $query = $this->db->query($sql);
        $data['doc_spec_a']= $query->result();

        $show_Specility_list="";
        
        foreach($data['doc_spec_a'] as $row)
        {
            
            $show_Specility_list.= '<div class="input-group input-group-sm">';
            $show_Specility_list.= '	<input class="form-control" type="text" value="'.$row->SpecName.'" readonly />';
            $show_Specility_list.= '	<span class="input-group-btn">';
            $show_Specility_list.= '	<button type="button" class="btn btn-info btn-flat" onclick="remove_doc_spec('.$row->doc_spec_id.')">Remove -</button></span>';
            $show_Specility_list.= '</div>';
        }

        $rvar=array(
                'update' =>1,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
                'show_Specility_list' => $show_Specility_list
                );
                
        $encode_data = json_encode($rvar);
        echo $encode_data;
        
        
    }
    
    public function UpdateRecord() 
	{
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
        		
		$this->load->model('Doctor_M');
        $data = array( 
                    'p_title' => $this->input->post('select_title'),
					'mphone1' => $this->input->post('input_mphone1'), 
                	'p_fname' => $this->input->post('input_name'),
                	'gender' => $this->input->post('optionsRadios_gender'),
                	'dob' => str_to_MysqlDate($this->input->post('datepicker_dob')),
                	'zip' => $this->input->post('input_zip'),
                    'doc_sign' => $this->input->post('txt_doc_sign'),
                	'email1' => $this->input->post('input_email')
                 );
        $old_id=$this->input->post('doc_id'); 
        $update=$this->Doctor_M->update($data,$old_id);
		
		if($update>0)
		{
			$rvar=array(
                'update' =>1,
				'showcontent'=> Show_Alert('success','Saved','Data Saved successfully')
                );
                $encode_data = json_encode($rvar);
                echo $encode_data;
		}
		
    }
    
    public function AddNew() 
	{ 
        if (!$this->input->is_ajax_request()) { exit('no valid req.'); }

        $this->load->library('form_validation');
        
        $this->form_validation->set_rules('p_fname','Dr. Name','alpha_numeric_spaces|');
        $this->form_validation->set_rules('email1','Email','valid_email');
        $this->form_validation->set_rules('mphone1','MPhone','numeric');
        
        $this->load->model('Doctor_M');
        
        if($this->form_validation->run())     
        { 
            $data = array( 
    			'p_title' => $this->input->post('select_title'),
                'mphone1' => $this->input->post('input_mphone1'), 
            	'p_fname' => $this->input->post('input_name'),
            	'gender' => $this->input->post('optionsRadios_gender'),
            	'dob' => str_to_MysqlDate($this->input->post('datepicker_dob')),
            	'zip' => $this->input->post('input_zip'),
            	'email1' => $this->input->post('input_email')
            ); 
    	                 
            $inser_id=$this->Doctor_M->insert($data); 

            redirect('Doctor/doctor_record/'.$inser_id);
            
        }else{
             $this->load->view('Doctor_Profile/Doctor_V');
        }
	}
	
	public function remove_fee() 
	{
		$this->load->model('Doctor_M');
        
        $old_id=$this->input->post('rid'); 
        $update=$this->Doctor_M->delete_fee($old_id);
		
		$sql="select d.id ,f.fee_type ,d.doc_fee_desc,d.amount
		from doc_opd_fee d join doc_fee_type f on d.doc_fee_type=f.id and  d.doc_id=".$this->input->post('doc_id');;
		$query = $this->db->query($sql);
		$data['doc_fee_list']= $query->result();

		$table_property = array('table_open' => '<table class="table table-striped">');
		$this->table->set_template($table_property);
		$this->table->set_heading('Fee Type', 'description', 'Amount');
		foreach($data['doc_fee_list'] as $row)
		{
			if($row->id=='')
            {
                $button_code=' Not Define';
            }else{
                $button_code='<a href="javascript:remove_fees('.$row->id.')">Remove</a>';
            }

            $this->table->add_row($row->fee_type, $row->doc_fee_desc, $row->amount,$button_code);
		}
		$show_fee_list= $this->table->generate();
		
		if($update>0)
		{
			$rvar=array(
				'update' =>1,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
				'showcontent'=> Show_Alert('success','Remove','Fee Removed successfully'),
				'show_fee_list' => $show_fee_list
				);
				$encode_data = json_encode($rvar);
				echo $encode_data;
		}else{
			$rvar=array(
				'update' =>0,
                'csrfName' => $this->security->get_csrf_token_name(),
                'csrfHash' => $this->security->get_csrf_hash(),
				'showcontent'=> Show_Alert('danger','Error','Please Check')
				);
				$encode_data = json_encode($rvar);
				echo $encode_data;
		}
	}
	
	public function AddNew_fee() 
	{ 
         if (!$this->input->is_ajax_request()) { exit('no valid req.'); }
                $this->load->model('Doctor_M');
                $data = array( 
					'doc_id' => $this->input->post('doc_id'),
                    'doc_fee_desc' => $this->input->post('input_fee_desc'), 
                	'doc_fee_type' => $this->input->post('fee_type'),
                	'amount' => $this->input->post('input_fee_amount')
                 ); 


                $sql="select *  from doc_opd_fee where 
                    doc_id=".$this->input->post('doc_id'). " and doc_fee_type = ".$this->input->post('fee_type');
                $query = $this->db->query($sql);
                $doc_fee_update= $query->result();

                if(count($doc_fee_update)>0)
                {
                    $old_id=$doc_fee_update[0]->id; 
                    $update=$this->Doctor_M->delete_fee($old_id);
                }
                                
                $inser_id=$this->Doctor_M->insert_fee($data); 
				
				$sql="select d.id ,f.fee_type ,d.doc_fee_desc,d.amount
				from doc_opd_fee d join doc_fee_type f on d.doc_fee_type=f.id  
                and d.doc_id=".$this->input->post('doc_id')." order by f.id";
				$query = $this->db->query($sql);
				$data['doc_fee_list']= $query->result();
		
				$table_property = array('table_open' => '<table class="table table-striped">');
				$this->table->set_template($table_property);
				$this->table->set_heading('Fee Type', 'description', 'Amount');
				foreach($data['doc_fee_list'] as $row)
				{
					if($row->id=='')
                    {
                        $button_code=' Not Define';
                    }else{
                        $button_code='<a href="javascript:remove_fees('.$row->id.')">Remove</a>';
                    }

                    $this->table->add_row($row->fee_type, $row->doc_fee_desc, $row->amount,$button_code);
                    
				}

				$show_fee_list= $this->table->generate();
								
                if($inser_id>0)
				{
					$rvar=array(
						'inser_id' =>1,
                        'csrfName' => $this->security->get_csrf_token_name(),
                        'csrfHash' => $this->security->get_csrf_hash(),
						'showcontent'=> Show_Alert('success','Added','Fee added successfully'),
						'show_fee_list' => $show_fee_list
						);
						$encode_data = json_encode($rvar);
						echo $encode_data;
				}else{
					$rvar=array(
						'inser_id' =>0,
                        'csrfName' => $this->security->get_csrf_token_name(),
                        'csrfHash' => $this->security->get_csrf_hash(),
						'showcontent'=> Show_Alert('danger','Error','Please Check')
						);
						$encode_data = json_encode($rvar);
						echo $encode_data;
				}
    } 
    
    
   
 
}

