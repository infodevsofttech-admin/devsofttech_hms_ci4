<?php
 
class Mobile_app extends MY_Controller{
    function __construct()
    {
        parent::__construct();

        //$this->load->database();
        $this->load->helper(array('cookie','url','language'));
      
        // Load form helper library
        //$this->load->helper('form');

        // Load form validation library
        //$this->load->library('form_validation');

        // Load session library
        $this->load->library('session');

        $this->load->helper('security');

    }
  
    public function index()
    {
        $this->load->view('Mobile_app/index');
    }

    public function load_dash()
    {
        $session_set_value = $this->session->all_userdata();
        if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1") {
            $this->load->view('Mobile_app/dash_main');
        } else {   
            redirect('Mobile_app/select_doctor_login');
        } 
    }

    public function select_doctor_login()
    {
        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id 
            and s.med_spec_id =m.id
            where d.active=1 group by d.id ";
        $query = $this->db->query($sql);
        
        $data['doctor_data']= $query->result();

        $this->load->view('Mobile_app/select_doctor',$data);
    }

    public function login($doc_id=0)
    {
        // Retrieve session data
        $session_set_value = $this->session->all_userdata();

        $data['doc_id'] = $doc_id;

        $sql="select d.id ,d.p_fname ,group_concat(m.SpecName) as SpecName
            from doctor_master d join doc_spec s join med_spec m on d.id =s.doc_id and s.med_spec_id =m.id
            where d.active=1 and d.id=$doc_id group by d.id ";
        $query = $this->db->query($sql);
        $data['doctor_data_profile']= $query->result();

        // Check for remember_me data in retrieved session data
        if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1") {
            //$this->load->view('admin_page');
            $this->load->view('Mobile_app/dash_main',$data);
        } else {
            
            // Check for validation
           $this->form_validation->set_rules('password', 'Pin Number', 'trim|required|xss_clean');

            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('login_form');
                $this->load->view('Mobile_app/login',$data);
            } else {
                $username = $doc_id;
                $password = $this->input->post('password');

                $sql = "SELECT *
                FROM doctor_master 
                WHERE id=$username and login_pin='$password'";
              
                $query = $this->db->query($sql);
                $data_login= $query->result();

                if (count($data_login)>0) {
                    $this->session->set_userdata('remember_me', TRUE);
                    
                    $sess_data = array(
                        'p_fname'=> $data_login[0]->p_fname,
                        'p_mname'=> $data_login[0]->p_mname,
                        'doc_id' => $data_login[0]->id,
                        'username' => $username,
                        'password' => $password
                    );
            
                    $this->session->set_userdata('logged_in', $sess_data);
                    //$this->load->view('admin_page');

                   
                    
                    $data['user'] = 1;
                    $this->load->view('Mobile_app/dash_main',$data);

                } else {
                    $data['error_message'] = 'Invalid Pin';

                    $this->load->view('Mobile_app/login', $data);
                }
            }
        }
    }

    public function logout()
    {
        // Destroy session data
            $this->session->sess_destroy();
            $data['message_display'] = 'Successfully Logout';
            redirect('Mobile_app/select_doctor_login');
    }
   

    //Customer Search
    function patient_index_all($day=-1,$doc_id=0)
    {
        $data['doc_id']=$doc_id;
        $data['day']=$day;
        
        $data['_view'] = '';
 
        $this->load->view('Mobile_app/patient_page',$data);
    }

    function patient_index($day=0,$doc_id=0)
    {
        if($doc_id==0)
        {
            $where="";
        }else{
            $where=" and  o.doc_id = ".$doc_id;
        }

        if($day>=0){
            $where_date=" and  date(o.opd_book_date) = DATE_ADD(CURDATE(),INTERVAL -".$day." DAY)";
        }

        $sql = "SELECT p.*,Date_Format(o.last_opdvisit_date,'%d-%m-%Y %H:%i') AS last_date_visit,
                o.*,
                if(date(o.opd_book_date)=curdate(),1,0) as opd_today
                FROM (patient_master_exten p 
                JOIN opd_master o ON p.id=o.p_id)
                WHERE 1=1 $where_date  $where		
                Group by  p.id 
                order by  o.opd_id desc";
              
        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $data['doc_id']=$doc_id;
        $data['day']=$day;

        $data['sql']= $sql;
        
        $data['_view'] = 'Mobile_app/patient_search_v';
 
        $this->load->view('Mobile_app/patient_page',$data);
    }

    function patient_search()
    {
        $doc_id=$this->input->post('doc_id');
        $day=$this->input->post('day');
        $sdata=$this->input->post('data_search');

        $where="1=1 ";

        if($doc_id==0)
        {
            $where.="";
        }else{
            $where.=" and  o.doc_id = ".$doc_id;
        }

        if($day>=0){
            $where_date=" and  date(o.opd_book_date) = DATE_ADD(CURDATE(),INTERVAL -".$day." DAY)";
        }else{
            $where_date="";
        }
        
        $sdata = preg_replace('/[^A-Za-z0-9 _\-]/', '', trim($sdata));

        if(is_numeric($sdata))
        {
           $where.=" and   (mphone1 = '".$sdata."' or udai='".$sdata."' or p_code like '%".$sdata."')" ;
        }elseif (filter_var($sdata, FILTER_VALIDATE_EMAIL)) {
            $where.="   email1 = '".$sdata."'" ;
        }else{
            $where.=" and  (p_fname  like '%".$sdata."%' or
                    SUBSTRING_INDEX(p_fname,' ',1) sounds like '".$sdata."')";
        }
        
        $sql = "SELECT p.*,Date_Format(o.last_opdvisit_date,'%d-%m-%Y %H:%i') AS last_date_visit,
                o.*,
                if(date(o.opd_book_date)=curdate(),1,0) as opd_today
                FROM (patient_master_exten p 
                JOIN opd_master o ON p.id=o.p_id)
                WHERE ".$where." ".$where_date." 
                order by  o.opd_id desc ";

        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $data['sql']= $sql;

        $this->load->view('Mobile_app/patient_search_v',$data);

        

    }

    function patient_index_opd()
    {
        if (isset($_POST['search'])){
            
            $doc_id=$this->input->post('doc_id');
            $day=$this->input->post('day');
            $sdata=$this->input->post('search');

            if($doc_id==0)
            {
                $where="1=1 ";
            }else{
                $where="   o.doc_id = ".$doc_id;
            }

            if($day>=0){
                $where_date=" and  date(o.opd_book_date) = DATE_ADD(CURDATE(),INTERVAL -".$day." DAY)";
            }else{
                $where_date=" and  date(o.opd_book_date) > DATE_ADD(CURDATE(),INTERVAL -180 DAY)";
            }
            
            $sdata = preg_replace('/[^A-Za-z0-9 _\-]/', '', trim($sdata));

            if(is_numeric($sdata))
            {
                $where.=" and  (mphone1 = '".$sdata."' or udai='".$sdata."' or p_code like '%".$sdata."')" ;
            }elseif (filter_var($sdata, FILTER_VALIDATE_EMAIL)) {
                $where.=" and  email1 = '".$sdata."'" ;
            }else{
                $where.=" and (p_fname  like '%".$sdata."%' or
                        SUBSTRING_INDEX(p_fname,' ',1) sounds like '".$sdata."')";
            }
            
            $sql = "SELECT p.*,Date_Format(p.last_visit,'%d-%m-%Y %H:%i') AS last_date_visit,
                    o.doc_name,
                    if(date(o.opd_book_date)=curdate(),1,0) as opd_today
                    FROM (patient_master_exten p 
                    JOIN opd_master o ON p.id=o.p_id)
                    WHERE ".$where." ".$where_date."  Group by  p.id
                    order by  o.opd_id desc ";
                    
            $query = $this->db->query($sql);
            $data['data']= $query->result();

            $data['sql']= $sql;

            $content=$this->load->view('Mobile_app/patient_search_v',$data,TRUE);

            echo $content;
            echo $sql;

        }
    }


    function person_record($pno)
    {
        $sql="select * from patient_master_exten where  id=".$pno;
        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $sql="select *,date_format(apointment_date,'%d-%m-%Y') as str_apointment_date 
        from opd_master where p_id=".$pno;
        $query = $this->db->query($sql);
        $data['opd_List']= $query->result();
        
        $sql="select m.id,m.invoice_code,Date_Format(m.inv_date,'%d-%m-%y') as str_inv_date,m.inv_name,
                if(count(t.id)>0,group_concat(t.item_name SEPARATOR ' / '),'No-Item') as Item_List, m.net_amount,m.invoice_status
                from invoice_master m left join invoice_item t on m.id=t.inv_master_id 
                where attach_type=0 and  attach_id=".$pno." group by m.id order by m.id desc";
        $query = $this->db->query($sql);
        $data['invoice_list']= $query->result();
        
        $sql="select i.*,m.ins_company_name,m.opd_allowed,m.charge_cash 
        from hc_insurance_card i join hc_insurance m on i.insurance_id=m.id 
        where  m.active=1 and i.p_id=".$pno;
        $query = $this->db->query($sql);
        $data['data_insurance_card']= $query->result();
    
        $sql="select *,Date_Format(date_registration,'%d-%m-%Y') as str_date_registration from  organization_case_master  where status=0 and p_id=".$pno;
        $query = $this->db->query($sql);
        $data['case_master']= $query->result();

        $sql="select * from  file_upload_data  where id=".$data['data'][0]->profile_file_id;
        $query = $this->db->query($sql);
        $file_data= $query->result();

        $profile_file_path="/assets/images/no_image.jpg";

        if(count($file_data)>0)
        {
            $pos=strpos($file_data[0]->full_path,'/uploads/',1) ;
            $profile_file_path=substr($file_data[0]->full_path,$pos);
        }

        $data['profile_file_path']=$profile_file_path;

        $sql="select * from file_upload_data where  pid=".$pno." order by id desc";
        $query = $this->db->query($sql);
        $data['opd_file_list']= $query->result();

        $this->load->view('Mobile_app/patient_opd_profile',$data);
       
    }


    public function show_profile_opd($p_id)
    {
        $data['p_id']=$p_id;

        $sql="select * from patient_master_exten where  id=".$p_id;
        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $sql="select o.* from opd_master o where o.p_id=$p_id order by o.opd_id desc";
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();

        $sql="select o.* from opd_master o where o.p_id=$p_id and date(o.apointment_date)=curdate()";
        $query = $this->db->query($sql);
        $data['opd_master_current']= $query->result();

        $this->load->view('Mobile_app/person_file_show',$data);
    }

    public function take_opd_picture($opd_id,$pno)
    {
        $data['opd_id']=$opd_id;
        $data['pno']=$pno;

        $sql="select * from patient_master_exten where  id=".$pno;
        $query = $this->db->query($sql);
        $data['data']= $query->result();

        $sql="select o.* from opd_master o where o.opd_id=$opd_id";
        $query = $this->db->query($sql);
        $data['opd_master']= $query->result();


        $this->load->view('Mobile_app/opd_scan',$data);
    }

    //IPD Panel

    public function ipd_panel($doc_id=0)
    {
        $sql="select * from v_ipd_list 
            where FIND_IN_SET($doc_id,doc_list)>0  
            and (ipd_status=0 or (ipd_status=1 and discharge_date>date_add(sysdate(),interval -15 day)))" ;
        $query = $this->db->query($sql);
        $data['ipd_data']= $query->result();

        $sql="select * from v_ipd_list 
            where (ipd_status=0 or (ipd_status=1 and discharge_date>date_add(sysdate(),interval -15 day)))" ;
        $query = $this->db->query($sql);
        $data['ipd_data_all']= $query->result();

        $this->load->view('Mobile_app/ipd_panel',$data);
    }

}

