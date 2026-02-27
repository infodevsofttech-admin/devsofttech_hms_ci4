<?php 
   class File_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	 public function insert($data) { 
         if ($this->db->insert("file_upload_data", $data)) { 
            $insert_id = $this->db->insert_id();

			return $insert_id; 
         }else{
            echo $this->db->_error_message();
            return  0;
         }
    }
	
	public function update($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("file_upload_data", $data); 
    } 

    function get_filedetail($id)
    {
        return $this->db->get_where('file_upload_data',array('id'=>$id))->row_array();
    }

    function get_filedetail_file_url($id)
    {
        if($id=='' || $id==0 )
        {
            $file_url="No File";

        }else{
            $file_url=$this->db->get_where('file_upload_data',array('id'=>$id))->row()->full_path;
        }

        return $file_url;
    }

    function delete_fileupload($id)
    {
		$this->db->where('id', $id);
		$q = $this->db->get('file_upload_data');
		// if id is unique, we want to return just one row
		$data = $q->row_array();
		
		$user_name=$this->session->userdata('username').'['.$this->session->userdata('agent_id').']'.date('d-m-Y H:i:s');
		$data['delete_by']=$user_name;
		
		if ($this->db->insert("file_upload_data_delete", $data))
		{
			return $this->db->delete('file_upload_data',array('id'=>$id));
		}else{
			return 0;
		}
    }
	
}
?>