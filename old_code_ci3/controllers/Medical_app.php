<?php
 
class Medical_app extends MY_Controller{
    function __construct()
    {
        parent::__construct();

        //$this->load->database();
        //$this->load->helper(array('cookie','url','language'));
      
        // Load form helper library
        //$this->load->helper('form');

        // Load form validation library
        //$this->load->library('form_validation');

        // Load session library
        //$this->load->library('session');

        $this->load->helper('security');

    }
  
    public function index()
    {
        $this->load->view('Medical_app/index');
    }

    public function load_dash()
    {
        $session_data = $this->session->userdata();
        print_r($session_data);
        if($session_data['u_id']=='')
        {
            redirect('Medical_app/select_user_login');
        }else{
            $this->load->view('Medical_app/dash_main');
        }
    }

    public function select_user_login()
    {
        $sql="select *
                from users
                where active=1 and medical_app=1";
        $query = $this->db->query($sql);
        
        $data['user_data']= $query->result();

        $this->load->view('Medical_app/select_doctor',$data);
    }

    public function login($user_id=0)
    {
        // Retrieve session data
        $session_set_value = $this->session->userdata();

        $data['user_id'] = $user_id;

        $sql="select *
            from users
            where active=1 and id=$user_id and medical_app=1 ";
        $query = $this->db->query($sql);
        $data['user_data_profile']= $query->result();

        // Check for remember_me data in retrieved session data
        if (isset($session_set_value['remember_me']) && $session_set_value['remember_me'] == "1") {
            //$this->load->view('admin_page');
            $this->load->view('Mobile_app/dash_main',$data);
        } else {
            
            // Check for validation
           $this->form_validation->set_rules('password', 'Pin Number', 'trim|required|xss_clean');

            if ($this->form_validation->run() == FALSE) {
                //$this->load->view('login_form');
                $this->load->view('Medical_app/login',$data);
            } else {
                $username = $user_id;
                $password = $this->input->post('password');

                $sql = "SELECT *
                FROM users 
                WHERE id=$username and login_pin='$password' and medical_app=1";
              
                $query = $this->db->query($sql);
                $data_login= $query->result();

                if (count($data_login)>0) {
                    $this->session->set_userdata('remember_me', TRUE);
                    
                    $session_data = array(
                        'p_fname'=> $data_login[0]->first_name,
                        'p_mname'=> $data_login[0]->last_name,
                        'u_id' => $data_login[0]->id,
                        'username' => $username,
                        'password' => $password
                    );
                   
                    $this->session->set_userdata($session_data);
                    
                    $data['user'] = 1;
                    $this->load->view('Medical_app/dash_main',$data);

                } else {
                    $data['error_message'] = 'Invalid Pin';

                    $this->load->view('Medical_app/login', $data);
                }
            }
        }
    }

    public function logout()
    {
        // Destroy session data
            $this->session->sess_destroy();
            $data['message_display'] = 'Successfully Logout';
            redirect('Mobile_app/select_user_login');
    }
   

    //Customer Search
    function patient_index_all()
    {
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

            //$data['sql']= $sql;

            $content=$this->load->view('Medical_app/ipd_panel_sub',$data,TRUE);

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

    public function ipd_panel()
    {
        $sql="SELECT i.*,p.p_fname,p.str_age,p.xgender,
		date_format(i.register_date,'%d-%m-%Y') AS admit_dt,
		DATE_FORMAT(i.discharge_date,'%d-%m-%Y') AS discharge_dt,
		m.gross_amount,m.discount_group,m.discount_group_2,m.net_amount,m.payment_received,
		if(m.med_type>1,'TPA','CASH') AS pay_type,m.payment_balance
		FROM (inv_med_group m JOIN ipd_master i ON m.ipd_id=i.id)
		JOIN patient_master_exten p ON i.p_id=p.id
		WHERE i.ipd_status=0  AND m.net_amount>0" ;
        
        $query = $this->db->query($sql);
        $data['ipd_data']= $query->result();

        $this->load->view('Medical_app/ipd_panel',$data);
    }

    public function ipd_panel_search()
    {
        $this->load->view('Medical_app/ipd_panel_search');
    }

    public function ipd_panel_search_data($search_text='',$pay_type=1)
    {
        $sdata=$this->input->post('data_search');

        $where="1=1 ";

        if(is_numeric($sdata))
        {
            $where.=" and  (p.mphone1 = '".$sdata."' or p.udai='".$sdata."' or p.p_code like '%".$sdata."' or i.ipd_code like '%".$sdata."')" ;
        }elseif (filter_var($sdata, FILTER_VALIDATE_EMAIL)) {
            $where.=" and  p.email1 = '".$sdata."'" ;
        }else{
            $where.=" and (p.p_fname  like '".$sdata."%' or
                    SUBSTRING_INDEX(p_fname,' ',1) sounds like '".$sdata."')";
        }

        $sql="SELECT i.*,p.p_fname,p.str_age,p.xgender,
		date_format(i.register_date,'%d-%m-%Y') AS admit_dt,
		DATE_FORMAT(i.discharge_date,'%d-%m-%Y') AS discharge_dt,
		m.gross_amount,m.discount_group,m.discount_group_2,m.net_amount,m.payment_received,
		if(m.med_type>1,'TPA','CASH') AS pay_type,m.payment_balance
		FROM (inv_med_group m JOIN ipd_master i ON m.ipd_id=i.id)
		JOIN patient_master_exten p ON i.p_id=p.id
		WHERE $where  AND m.net_amount>0" ;
        
        $query = $this->db->query($sql);
        $data['ipd_data']= $query->result();

        $content=$this->load->view('Medical_app/ipd_panel_sub',$data,TRUE);

        //echo $sql;
        echo $content;
    }

    public function open_ipd_bill($ipd_id)
    {
        $this->db->query("CALL p_revoke_bill(".$ipd_id.")");
        echo 'Done : Bill Opened';
    }

    public function get_ipd_bill_purchase($ipd_id)
    {
        $sql="select m.ipd_code,m.inv_name,sum(i.twdisc_amount) as Bill_Amount, 
        sum(Round(COALESCE(p.net_amount/p.total_unit,i.price2),2)*i.qty) as purchase_amount,
        SUM(i.disc_whole) as Dis_Amount
        from ((invoice_med_master m join inv_med_item i on m.id=i.inv_med_id)
        left join purchase_invoice_item p on p.id=i.store_stock_id)
        join ipd_master ipd on m.ipd_id=ipd.id
        WHERE ipd.id=$ipd_id group by m.ipd_id ";

        $query = $this->db->query($sql);
        $ipd_data= $query->result();

        if(count($ipd_data)>0){
            $content="IPD : ".$ipd_data[0]->ipd_code."<br/>".
                    "Name : ".$ipd_data[0]->inv_name."<br/>".
                    "Bill Amount : ".$ipd_data[0]->Bill_Amount."<br/>".
                    "Discount : ".$ipd_data[0]->Dis_Amount."<br/>".
                    "Purchase : ".$ipd_data[0]->purchase_amount."";
                    
        }else{
            $content="Not Found";
        }

        echo $content;

    }

}

