<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Room_bed extends MY_Controller
{    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function index()
    {
		$sql="SELECT b.* ,v.p_fname,v.p_relative,v.p_rname,v.doc_name,v.ipd_code,v.p_code,v.admit_type
                From hc_bed_master b left JOIN v_ipd_list v ON b.bed_used_p_id=v.id";
		$query = $this->db->query($sql);
		$data['bed_master']= $query->result();
		
		$this->load->view('IPD/Bed_panel',$data);
    }

	public function ipd_list()
	{
        $sql="select * from  v_ipd_list where ipd_status=0";
		
		$query = $this->db->query($sql);
        $ipd_bed_list= $query->result();
		
		
    }
    
    public function get_admit_ipd_list(){
		
		if (isset($_GET['term'])){
			$q = strtolower($_GET['term']);

			$sql="SELECT * 
                from  v_ipd_list 
                where ipd_status=0 and 
					(p_fname LIKE '$q%'  or p_code like '%$q' or ipd_code like '%$q')
					order BY p_fname ";

			$query = $this->db->query($sql);
			
			if($query->num_rows() > 0){
			  foreach ($query->result_array() as $row){
				$new_row['label']=htmlentities(stripslashes($row['ipd_code'])).' | '.htmlentities(stripslashes($row['p_fname'])).' | '.htmlentities(stripslashes($row['str_register_date']));
				$new_row['value']=htmlentities(stripslashes($row['id']));
				
				$row_set[] = $new_row; //build an array
			  }
			  echo json_encode($row_set); //format the array into json data
			}
		}
	}
	
}
?>