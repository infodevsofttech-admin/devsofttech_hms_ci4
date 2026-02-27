<?php 
   class opd_M extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
	
	function readopd(){
		$sql = "SELECT * FROM opd_master;";
		$query = $this->db->query($sql);
		$result	= $query->result_array();
		//print_r($result);
		return $result;
	}
    
    public function insert($data) { 
        
        $p_id=$data['p_id'];
        $opd_dt=$data['apointment_date'];
        $doc_id=$data['doc_id'];

        $sql="select * from  opd_master  
        where apointment_date=curdate() and (payment_status=0 or opd_status=1 ) and p_id=$p_id  and doc_id=$doc_id";
        $query = $this->db->query($sql);
        $no_duplicate= $query->result();

        $insert_id=0;

        if(count($no_duplicate)==0)
        {
            if($this->db->insert("opd_master", $data)) 
            { 
                $insert_id = $this->db->insert_id();

                $sql="SELECT count(*) as xtimes from opd_master where opd_id<=".$insert_id." and p_id=".$p_id;
                $query = $this->db->query($sql);
                $opd_times= $query->result();

                $sql="select count(*) as no_max from  opd_master  where apointment_date=curdate() and  doc_id=".$doc_id;
                $query = $this->db->query($sql);
                $opd_no_data= $query->result();
                
                $opd_no=1;
                if(count($opd_no_data)>0)
                {
                    $opd_no=$opd_no_data[0]->no_max;
                }
                //$opd_no=$opd_no+1;
                
                $pid=str_pad(substr($insert_id,-7,7),7,0,STR_PAD_LEFT);
                $doc_id=str_pad(substr($insert_id,-3,3),3,0,STR_PAD_LEFT);
                
                $Oid='D'.date('ym').$pid;

                $running_opd=0;
                $last_opd_id=0;

                if($data['opd_fee_type']==3)
                {
                    $running_opd = 1;

                    $sql="select max(opd_id) as last_opd_id from  opd_master 
                    where apointment_date between date_add(curdate(),interval -4 day) and curdate() 
                    and running_opd=0 and doc_id=".$doc_id. " and p_id=".$p_id;
                    $query = $this->db->query($sql);
                    $opd_no_data= $query->result();

                    if(count($opd_no_data)>0)
                    {
                        if($opd_no_data[0]->last_opd_id=='' || $opd_no_data[0]->last_opd_id=='0')
                        {
                            $last_opd_id=0;
                        }else{
                            $last_opd_id=$opd_no_data[0]->last_opd_id;
                        }
                    }
                }
                
                $dataupadate = array( 
                    'opd_code' => $Oid,
                    'no_visit' => $opd_times[0]->xtimes,
                    'running_opd' => $running_opd,
                    'running_opd_id' => $last_opd_id,
                    'opd_no'=>$opd_no,
                );

                $this->db->set($dataupadate); 
                $this->db->where("opd_id", $insert_id); 
                $this->db->update("opd_master", $dataupadate);

                
            }
        }

		return $insert_id; 
    }


    public function insert_bak($data) { 
        
        $p_id=$data['p_id'];
        $opd_dt=$data['apointment_date'];
        $doc_id=$data['doc_id'];

        $insert_id=0;

        if($data['opd_fee_type']==3)
        {
            $sql="SELECT * from opd_master where p_id=".$p_id." and doc_id=".$doc_id." 
                    and  apointment_date > date_add(sysdate(),interval -5 day) 
                    order by apointment_date desc ";
            $query = $this->db->query($sql);
            $opd_running= $query->result();

            if(count($opd_running)>0)
            {
                $insert_id=$opd_running[0]->opd_id;
                $revisit_opd_id=$opd_running[0]->revisit_opd_id;

                $data_visit = array( 
                    'revisit_opd_id' => $revisit_opd_id+1,
                );              

                $this->db->set($data_visit); 
                $this->db->where("opd_id", $insert_id); 
                $this->db->update("opd_master", $data_visit);

            }
        }else{
                if($this->db->insert("opd_master", $data)) 
                { 
                    $insert_id = $this->db->insert_id();

                    $sql="SELECT count(*) as xtimes from opd_master where opd_id<=".$insert_id." and p_id=".$p_id;
                    $query = $this->db->query($sql);
                    $opd_times= $query->result();
                    
                    $pid=str_pad(substr($insert_id,-7,7),7,0,STR_PAD_LEFT);
                    
                    $pid='D'.date('ym').$pid;
                    
                    $dataupadate = array( 
                        'opd_code' => $pid,
                        'revisit' => $opd_times[0]->xtimes,
                    );              

                    $this->db->set($dataupadate); 
                    $this->db->where("opd_id", $insert_id); 
                    $this->db->update("opd_master", $dataupadate);
                }
        }
   
        $sql="SELECT * from opd_visit_list where date_opd=".$opd_dt." and p_id=".$p_id;
        $query = $this->db->query($sql);
        $opd_visit= $query->result();

        if(count($opd_visit)>0)
        {
            $data_visit = array( 
                'p_name' => $data['P_name'],
                'opd_id' => $data['opd_id'],
                'opd_type' => $data['opd_fee_type'],
                'opd_status' => '1',
            );

            $this->db->set($data_visit); 
            $this->db->where("id", $opd_visit[0]->id); 
            $this->db->update("opd_visit_list", $data_visit); 

        }else{
            $data_visit = array( 
                'p_id' => $p_id,
                'p_name' => $data['P_name'],
                'opd_id' => $insert_id,
                'opd_type' => $data['opd_fee_type'],
                'date_opd' => $data['apointment_date'],
                'opd_no' => '0',
                'update_by' => $data['prepared_by'],
                'opd_status' => '1',
            );

            $this->db->insert("opd_visit_list", $data_visit);
        }

        return $insert_id; 
    }
    
    public function update($data,$old_id_no) { 
         
         /* Auto Que  */
         $user = $this->ion_auth->user()->row();
         
         if(isset($user->id))
         {
            $user_id = $user->id;
            $user_name = $user->first_name.''. $user->last_name.'['.$user_id.']';
         }else{
            $user_id = '0';
            $user_name = 'Mobile User';
         }
         

        
        $this->db->set($data); 
        $this->db->where("opd_id", $old_id_no); 
        $this->db->update("opd_master", $data); 

        $this->db->where('opd_id', $old_id_no);
        $q = $this->db->get('opd_master');
        // if id is unique, we want to return just one row
        $data = $q->row_array();

        $pno=$data['p_id'];
        $doc_id=$data['doc_id'];
        $opd_date=$data['apointment_date'];
        $opd_status=$data['opd_status'];

        $sql="select max(queue_no) as max_queue from opd_prescription 
        where date_opd_visit=curdate() and doc_id='".$doc_id."'";
        $query = $this->db->query($sql);
        $max_queue= $query->result();

        if(count($max_queue)>0)
        {
            $max_queue_no=$max_queue[0]->max_queue;
        }else{
            $max_queue_no=0;
        }
        
        $max_queue_no=$max_queue_no+1;
        
        $sql="select * from opd_prescription where visit_status=0 and opd_id=".$old_id_no;
        $query = $this->db->query($sql);
        $opd_prescription= $query->result();

        if(count($opd_prescription)==0 )
        {
            
            if($opd_status==1)
            {
                $datainsert = array( 
                'opd_id' => $old_id_no,
                'p_id' => $pno,
                'revisit'=>'0',
                'queue_no'=>$max_queue_no,
                'doc_id'=>$doc_id,
                'date_opd_visit'=>date('Y-m-d'),
                'update_by'=>$user_name
                );
            
                $this->load->model('Prescription_M');
                $this->Prescription_M->insert_opd_prescription($datainsert);

                $msg_send="OPD in Queue Now";
            }else{
                $msg_send="OPD Already Done, Create Revisit";
            }
            
        }
    } 

    public function update_opd_queue_no($opd_session_id,$doc_id,$date_opd_visit) { 
		$result = "update opd_prescription 
                    SET current_queue_no=0 
                    where doc_id='".$doc_id."' and date_opd_visit='".$date_opd_visit."'";
        $qyt = $this->db->query($result);
        
        $result = "update opd_prescription 
                    SET current_queue_no=1 
                    where id='".$opd_session_id."'";
        $qyt = $this->db->query($result);

        return 0;
    } 
   
    
} 
?>