<?php 
   class FileOPDRec_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	public function insert($data,$file_v_no) { 
        
        $sql="select * from file_opd_rec where v_no=$file_v_no";
        $query = $this->db->query($sql);
        $chk_file= $query->result();

        if(count($chk_file)>0)
        {
            $this->db->set($data); 
            $this->db->where("v_no",$file_v_no); 
            $this->db->update("file_opd_rec", $data); 
        }else{
            $data['v_no']=$file_v_no;
            if ($this->db->insert("file_opd_rec", $data)) { 
                $insert_id = $this->db->insert_id();
                return $insert_id; 
            }else{
                echo $this->db->_error_message();
                return  0;
            }
        }
    }

    public function file_complie($file_v_no)
    {
        $sql="select * from file_opd_rec where v_no=$file_v_no";
        $query = $this->db->query($sql);
        $chk_file= $query->result();

        if(count($chk_file)>0)
        {
            $full_path=$chk_file[0]->full_path;
            $start_no=stripos($full_path,$file_v_no);
            $end_no=strlen($full_path);

            $folder_name=substr($full_path,0,$start_no);

            $files_info = get_dir_file_info($folder_name);

            Echo '<pre>';
		        print_r($files_info) ;
		    Echo '</pre>';
        }

    }
	
	public function update($data,$old_id_no) { 
         $this->db->set($data); 
         $this->db->where("id", $old_id_no); 
         $this->db->update("file_opd_rec", $data); 
    } 
	
}
?>