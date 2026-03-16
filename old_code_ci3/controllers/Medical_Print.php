<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Medical_Print extends MY_Controller {
    
    public function __construct()
	{
		parent::__construct();
	}

    public function invoice_print_single_bill($inv_id,$print_format=0)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
		
		$this->Medical_M->update_invoice_group($inv_id);
		$this->Medical_M->update_invoice_final($inv_id);
           
       $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
           i.batch_no,Date_Format(i.expiry,'%m-%y')as expiry,
           i.price,i.qty,(i.amount) as amount,i.HSNCODE,
           m.inv_med_code,m.id as m_id,
           date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
           (twdisc_amount) as twdisc_amount,
           (i.disc_amount+i.disc_whole) as d_amt,
           (i.CGST+i.SGST) as gst,
           (i.CGST_per+i.SGST_per) as gst_per ,i.sale_return,
           i.TaxableAmount
           from inv_med_item i join invoice_med_master m
           on i.inv_med_id=m.id
           where  m.id=$inv_id order by i.sale_return,id";
       $query = $this->db->query($sql);
       $data['inv_items']= $query->result();
       
       $sql="select *,
       date_format(inv_date,'%d-%m-%Y') as str_inv_date,
       (discount_amount+item_discount_amount) as inv_disc_total,
       (CGST_Tamount+SGST_Tamount) as TGST 
       from invoice_med_master 
       where  id=$inv_id";
       $query = $this->db->query($sql);
       $data['invoice_med_master']= $query->result();

       $data['Doc_name']="";

       if($data['invoice_med_master'][0]->doc_id>0)
            {
                $doc_id=$data['invoice_med_master'][0]->doc_id;

                $sql="select * from doctor_master 
                where  id=$doc_id";
                $query = $this->db->query($sql);
                $doctor_master= $query->result();

                $data['Doc_name']=$doctor_master[0]->p_title.' '.$doctor_master[0]->p_fname;
            }else{
                $data['Doc_name']=$data['invoice_med_master'][0]->doc_name;
            }
     
       if($data['invoice_med_master'][0]->patient_id>0)
       {
            $p_id=$data['invoice_med_master'][0]->patient_id;

            $sql="select * from patient_master_exten 
            where  id=$p_id";
            $query = $this->db->query($sql);
            $data['patient_master']= $query->result();

            
                 

            if($data['invoice_med_master'][0]->ipd_id>0)
            {
                $ipd_id=$data['invoice_med_master'][0]->ipd_id;
                
                $sql="select * from v_ipd_list 
                where  id=$ipd_id";
                $query = $this->db->query($sql);
                $data['ipd_master']= $query->result();

                $org_id=$data['ipd_master'][0]->case_id;

                $sql="select * from organization_case_master 
                where  id=$org_id";
                $query = $this->db->query($sql);
                $data['org_master']= $query->result();


            }else if( $data['invoice_med_master'][0]->case_id>0)
            {
                    
                    $org_id=$data['invoice_med_master'][0]->case_id;

                    $sql="select * from organization_case_master 
                    where  id=$org_id";
                    $query = $this->db->query($sql);
                    $data['org_master']= $query->result();
            }

        }

        $sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
        Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
        from payment_history_medical p 
        where p.Customerof_type in (1,3) and Medical_invoice_id=$inv_id";
        $query = $this->db->query($sql);
        $data['payment_history']= $query->result();
                    
       //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = false;

        $file_name='Report-MedicalBill_'.$inv_id.'_'.date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);

        if($print_format==0)
        {
            $content=$this->load->view('Medical/Print/medical_bill_print_format_single',$data,TRUE);
        }elseif($print_format==1){

            $content=$this->load->view('Medical/Print/medical_bill_print_a6',$data,TRUE);
            $this->m_pdf->pdf->SetJS('this.print();');
        }elseif($print_format==2){

            $content=$this->load->view('Medical/Print/medical_bill_print_a5',$data,TRUE);
            $this->m_pdf->pdf->SetJS('this.print();');
        }elseif($print_format==3){

            $content=$this->load->view('Medical/Print/medical_bill_print_a5_discount',$data,TRUE);
            $this->m_pdf->pdf->SetJS('this.print();');
        }
        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");

      
        
    }
    
	
    public function invoice_print_all($ipd_id,$cash=0,$salt_name=0)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
        
        $cash_where="  ";
        
        if($cash==0)
        {
            $cash_where.=" and ipd_credit=0 ";
            $med_type=1;
        }elseif($cash==1)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
            $med_type=2;
        }elseif($cash==2)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
            $med_type=3;
        }

        $this->Medical_M->update_invoice_group_gst($ipd_id,$med_type);

        
        $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
            i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
            i.price,i.qty,sum(i.amount) as amount,i.HSNCODE,
            m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
            sum(twdisc_amount) as twdisc_amount,
            sum(i.disc_amount+i.disc_whole) as d_amt,
            sum(i.CGST+i.SGST) as gst,
            (i.CGST_per+i.SGST_per) as gst_per ,
            p.genericname
            from (inv_med_item i join invoice_med_master m  on i.inv_med_id=m.id)
            Left join med_product_master p on i.item_code=p.id
            where m.group_invoice_id>0 and i.qty>0 and m.ipd_id=".$ipd_id.$cash_where." 
            group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
        
        $sql="select sum(CGST_Tamount) as CGST_Tamount,
        sum(SGST_Tamount) as SGST_Tamount,
        sum(TaxableAmount) as TaxableAmount,
        sum(net_amount) as net_amount,
        sum(payment_received) as payment_received,
        sum(payment_balance) as payment_balance,
        sum(discount_amount+item_discount_amount) as inv_disc_total,
        (CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
        where  ipd_id=".$ipd_id.$cash_where." group by ipd_id";
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
        
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,m.p_relative,m.p_rname,
        m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
        $query = $this->db->query($sql);
        $data['orgcase']= $query->result();
        
        $sql="select sum(if(p.credit_debit>0,p.amount*-1,p.amount)) as paid_amount 
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_Total']= $query->result();
        
        $sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
        Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_history']= $query->result();
        
        $sql="select * from inv_med_group 
        where ipd_id='".$ipd_id."' and med_type='".$med_type."'";
        $query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();


        $data['salt_name']=$salt_name;
        
        //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;
        
        $file_name='Report-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/medical_bill_print_format',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
        //$this->m_pdf->pdf->Output('uploads/'.$filepath,"F"); 
        
    }

    public function invoice_print_salt_all($ipd_id,$cash=0,$print_size=0)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
        
        $cash_where="  ";
        
        if($cash==0)
        {
            $cash_where.=" and ipd_credit=0 ";
            $med_type=1;
        }elseif($cash==1)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
            $med_type=2;
        }elseif($cash==2)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
            $med_type=3;
        }

        $this->Medical_M->update_invoice_group_gst($ipd_id,$med_type);

        
        $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
            i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
            i.price,i.qty,sum(i.amount) as amount,i.HSNCODE,
            m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
            sum(twdisc_amount) as twdisc_amount,
            sum(i.disc_amount+i.disc_whole) as d_amt,
            sum(i.CGST+i.SGST) as gst,
            (i.CGST_per+i.SGST_per) as gst_per,
            p.genericname
            from (inv_med_item i join invoice_med_master m
            on i.inv_med_id=m.id)
            join med_product_master p on i.item_code=p.id
            where m.group_invoice_id>0 and i.qty>0 and m.ipd_id=".$ipd_id.$cash_where." 
            group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
        
        $sql="select sum(CGST_Tamount) as CGST_Tamount,
        sum(SGST_Tamount) as SGST_Tamount,
        sum(TaxableAmount) as TaxableAmount,
        sum(net_amount) as net_amount,
        sum(payment_received) as payment_received,
        sum(payment_balance) as payment_balance,
        sum(discount_amount+item_discount_amount) as inv_disc_total,
        (CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
        where  ipd_id=".$ipd_id.$cash_where." group by ipd_id";
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
        
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
        $query = $this->db->query($sql);
        $data['orgcase']= $query->result();
        
        $sql="select sum(if(p.credit_debit>0,p.amount*-1,p.amount)) as paid_amount 
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_Total']= $query->result();
        
        $sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
        Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_history']= $query->result();
        
        $sql="select * from inv_med_group 
        where ipd_id='".$ipd_id."' and med_type='".$med_type."'";
        $query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();
        
        //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;
        
        $file_name='Report-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/medical_bill_print_format',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
        //$this->m_pdf->pdf->Output('uploads/'.$filepath,"F"); 
        
    }

    public function invoice_print_with_return_all($ipd_id,$cash=0,$print_size=0)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
        
        $cash_where="  ";
        
        if($cash==0)
        {
            $med_type=1;
        }elseif($cash==1)
        {
            $med_type=2;
        }elseif($cash==2)
        {
            $med_type=3;
        }

        $this->Medical_M->update_invoice_group_gst($ipd_id,$med_type);
        
        /* $sql="SELECT *,SUM(i.amount) AS t_amount
            FROM v_med_item_with_return i
            where i.ipd_id=".$ipd_id.$cash_where." 
            group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql); */
        
        //$query = $this->db->query('call p_get_list_ipd_med_item('.$ipd_id.')');
        //$query->next_result();
        $sql="Select i.inv_med_id AS inv_med_id,i.id AS id,m.ipd_id AS ipd_id,i.item_Name AS item_Name,
        i.formulation AS formulation,i.batch_no AS batch_no,date_format(i.expiry,'%m-%Y') AS expiry,
        i.price AS price,i.qty AS Cur_qty,if(isnull(r.r_qty),i.qty,(i.qty + sum(r.r_qty))) AS qty,
        (if(isnull(r.r_qty),i.qty,(i.qty + sum(r.r_qty)))*i.price) AS amount,i.HSNCODE AS HSNCODE,
        m.inv_med_code AS inv_med_code,m.id AS m_id,date_format(m.inv_date,'%d-%m-%Y') AS str_inv_date,
        r.id AS r_id,sum(r.r_qty) AS tot_return 
        from ((inv_med_item i join invoice_med_master m on((m.id = i.inv_med_id))) 
        left join inv_med_item_return r on((i.id = r.inv_item_id)))
        WHERE m.ipd_credit=0 AND m.ipd_id=$ipd_id 
        group by i.inv_med_id,i.id WITH ROLLUP ";
        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();

        $sql="SELECT *,SUM(i.amount) AS t_amount
            FROM v_med_item_return i
            where i.ipd_id=".$ipd_id.$cash_where." 
            group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql);
        $data['inv_items_return']= $query->result();
                
        $sql="select sum(CGST_Tamount) as CGST_Tamount,
        sum(SGST_Tamount) as SGST_Tamount,
        sum(TaxableAmount) as TaxableAmount,
        sum(net_amount) as net_amount,
        sum(payment_received) as payment_received,
        sum(payment_balance) as payment_balance,
        sum(discount_amount+item_discount_amount) as inv_disc_total,
        (CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
        where  ipd_id=".$ipd_id.$cash_where." group by ipd_id";
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
        
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
        $query = $this->db->query($sql);
        $data['orgcase']= $query->result();
        
        $sql="select sum(if(p.credit_debit>0,p.amount*-1,p.amount)) as paid_amount 
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_Total']= $query->result();
        
        $sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
        Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_history']= $query->result();
        
        $sql="select * from inv_med_group 
        where ipd_id='".$ipd_id."' and med_type='".$med_type."'";
        $query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();
        
        //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;
        
        $file_name='Report-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/medical_bill_print_format_with_return',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
        //$this->m_pdf->pdf->Output('uploads/'.$filepath,"F"); 
        
    }
  
  
    public function invoice_print_all_bill($ipd_id)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
        
        $cash_where="  ";
              

        $this->Medical_M->update_invoice_group_gst($ipd_id,'1');
        $this->Medical_M->update_invoice_group_gst($ipd_id,'2');
        $this->Medical_M->update_invoice_group_gst($ipd_id,'3');
        
        $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
            i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
            i.price,i.qty,sum(i.amount) as amount,i.HSNCODE,
            m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
            sum(twdisc_amount) as twdisc_amount,
            sum(i.disc_amount+i.disc_whole) as d_amt,
            sum(i.CGST+i.SGST) as gst,
            (i.CGST_per+i.SGST_per) as gst_per 
            from inv_med_item i join invoice_med_master m
            on i.inv_med_id=m.id
            where m.group_invoice_id>0 and i.qty>0 and m.ipd_id=".$ipd_id.$cash_where." group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
        
        $sql="select sum(CGST_Tamount) as CGST_Tamount,
        sum(SGST_Tamount) as SGST_Tamount,
        sum(TaxableAmount) as TaxableAmount,
        sum(net_amount) as net_amount,
        sum(payment_received) as payment_received,
        sum(payment_balance) as payment_balance,
        sum(discount_amount+item_discount_amount) as inv_disc_total,
        (CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
        where group_invoice_id>0 and ipd_id=".$ipd_id.$cash_where." group by ipd_id";
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
        
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
        $query = $this->db->query($sql);
        $data['orgcase']= $query->result();
               
        //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;
      

        $file_name='Report-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/medical_bill_print_format_newpage',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");   
        
    }
      
  
    public function medicine_list_print_all($ipd_id,$cash=-1)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
                     
        $sql="select i.item_Name,i.formulation,p.genericname, SUM(i.qty) AS i_qty_total
                FROM (inv_med_item i join invoice_med_master m  on i.inv_med_id=m.id)
                JOIN med_product_master p ON i.item_code=p.id
            where m.group_invoice_id>0 and i.qty>0 
                and m.ipd_id=$ipd_id AND i.formulation <>'SUR' 
            group BY i.item_code  order BY i.item_Name";

        //echo $sql;

        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();

        $sql="select i.item_Name,i.formulation,p.genericname, SUM(i.qty) AS i_qty_total
                FROM (inv_med_item i join invoice_med_master m  on i.inv_med_id=m.id)
                JOIN med_product_master p ON i.item_code=p.id
            where m.group_invoice_id>0 and i.qty>0 
                and m.ipd_id=$ipd_id AND i.formulation ='SUR' 
            group BY i.item_code  order BY i.item_Name";

        //echo $sql;

        $query = $this->db->query($sql);
        $data['inv_items_sur']= $query->result();
                
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id,m.doc_list from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $data['doc_list_sign']="";
        $data['doc_list_main_sign']="";

        if(count($data['ipd_master'])>0)
        {
            $doc_list=$data['ipd_master'][0]->doc_list;
        
            $sql="select d.id,d.p_fname,d.p_title,d.mphone1,d.mphone2,
            if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age,
            d.dob,d.zip,d.email1,d.email2,
            group_concat(distinct  m.SpecName) as SpecName,d.doc_sign
            from (doctor_master d left join  (doc_spec s join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
            where d.id in (".$doc_list.") group by d.id";
            $query = $this->db->query($sql);
            $doc_master= $query->result();
           
            $doc_list="";
                      
            foreach($doc_master as $row)
            {
                $doc_list.="Dr. ".$row->p_fname." [".$row->SpecName."] <br/><br/>";
            }
            
            $data['doc_list']=$doc_list;
            
        }
        

        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
        
        $file_name='MedicineList-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/Medicine_consolidated_List',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");   
        
    }

    public function medicine_list_print($ipd_id,$cash=-1)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
                      
        $sql="SELECT i.inv_med_id,i.id,i.item_Name,i.formulation,p.genericname,
            sum(i.qty) as tot_qty,  m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date
            FROM (inv_med_item i join invoice_med_master m on i.inv_med_id=m.id)
            JOIN med_product_master p ON i.item_code=p.id
            where m.group_invoice_id>0 and i.qty>0 
            and m.ipd_id=$ipd_id AND i.formulation <>'SUR' 
			GROUP BY i.item_code
			order by  m.inv_date,i.id";

        //echo $sql;

        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
                
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id,m.doc_list from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $data['doc_list_sign']="";
        $data['doc_list_main_sign']="";

        if(count($data['ipd_master'])>0)
        {
            $doc_list=$data['ipd_master'][0]->doc_list;
        
            $sql="select d.id,d.p_fname,d.p_title,d.mphone1,d.mphone2,
            if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age,
            d.dob,d.zip,d.email1,d.email2,
            group_concat(distinct  m.SpecName) as SpecName,d.doc_sign
            from (doctor_master d left join  (doc_spec s join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
            where d.id in (".$doc_list.") group by d.id";
            $query = $this->db->query($sql);
            $doc_master= $query->result();
           
            $doc_list="";
            $doc_list_sign='<table  style="border:1px;font-size: 10px;width:100%;border-style: solid ;"><tr><td colspan="2" align="center"><h2>Treating Consultant / Authorized Team Doctor</h2></td></tr> ';

            $doc_list_main_sign='<table  style="border:1px;font-size: 10px;width:100%;border-style: solid ;"><tr><td colspan="2" align="center"><h3>Treating Consultant/Department/Specialty </h3></td></tr> ';
        
            $row_num=0;
            foreach($doc_master as $row)
            {
                $row_num=$row_num+1;

                $doc_list.="Dr. ".$row->p_fname." [".$row->SpecName."] <br/>";
                
                $doc_list_sign.='
                    <tr >
                        <td style="border: 1px solid black;" ><b>Dr. '.$row->p_fname.'</b>
                        <br /><i>'.$row->SpecName.'</i>
                        <br />'.nl2br($row->doc_sign).'
                        <br />
                        <br /></td>
                        <td style="border: 1px solid black;text-align:center; vertical-align:top">
                        Signature of Consultant
                        <br /><br /><br /><br /><br />
                        </td>
                    </tr>';

                $mod_r=$row_num % 2;
                if($mod_r>0)
                {
                    $doc_list_main_sign.='<tr>';
                }
                
                $doc_list_main_sign.='
                    <td style="width:50%"><b>Dr. '.$row->p_fname.'</b><br/>['.$row->SpecName.'] <br/>
                        '.nl2br($row->doc_sign).'
                    </td>';

                if($mod_r==0)
                {
                    $doc_list_main_sign.='</tr>';
                }
            }
            
            $row_num=$row_num+1;
            $mod_r=$row_num % 2;
        
            if($mod_r==0)
            {
                
                $doc_list_main_sign.='<td style="width:50%"></td>
                </tr>';
            }
      
            $doc_list_sign.="</table>";
            $doc_list_main_sign.="</table>";
            
            $data['doc_list_sign']=$doc_list_sign;
            $data['doc_list_main_sign']=$doc_list_main_sign;
        }
        

        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
        
        $file_name='MedicineList-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/Medicine_List',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");   
        
    }

    public function medicine_list_surgical_print($ipd_id,$cash=-1)
    {
       //$cash 0 for Cash Only,1 for Credit only,2 Package Credit only,3 for All
        $this->load->model('Medical_M');
        
        $cash_where=" ";
                      
        $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
            i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
            i.price,i.qty,(i.amount) as amount,i.HSNCODE,
            m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
            (twdisc_amount) as twdisc_amount,
            (i.disc_amount+i.disc_whole) as d_amt,
            (i.CGST+i.SGST) as gst,
            (i.CGST_per+i.SGST_per) as gst_per 
            from inv_med_item i join invoice_med_master m
            on i.inv_med_id=m.id
            where m.group_invoice_id>0 and i.qty>0 
            and m.ipd_id=$ipd_id and formulation ='SUR'  order by  m.inv_date,i.id";

        //echo $sql;

        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
                
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id,m.doc_list from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $data['doc_list_sign']="";
        $data['doc_list_main_sign']="";

        if(count($data['ipd_master'])>0)
        {
            $doc_list=$data['ipd_master'][0]->doc_list;
        
            $sql="select d.id,d.p_fname,d.p_title,d.mphone1,d.mphone2,
            if(d.gender=1,'Male','Female') as xgender,Get_Age(d.dob) as age,
            d.dob,d.zip,d.email1,d.email2,
            group_concat(distinct  m.SpecName) as SpecName,d.doc_sign
            from (doctor_master d left join  (doc_spec s join med_spec m on s.med_spec_id=m.id) on d.id=s.doc_id)
            where d.id in (".$doc_list.") group by d.id";
            $query = $this->db->query($sql);
            $doc_master= $query->result();
           
            $doc_list="";
            $doc_list_sign='<table  style="border:1px;font-size: 10px;width:100%;border-style: solid ;"><tr><td colspan="2" align="center"><h2>Treating Consultant / Authorized Team Doctor</h2></td></tr> ';

            $doc_list_main_sign='<table  style="border:1px;font-size: 10px;width:100%;border-style: solid ;"><tr><td colspan="2" align="center"><h3>Treating Consultant/Department/Specialty </h3></td></tr> ';
        
            $row_num=0;
            foreach($doc_master as $row)
            {
                $row_num=$row_num+1;

                $doc_list.="Dr. ".$row->p_fname." [".$row->SpecName."] <br/>";
                
                $doc_list_sign.='
                    <tr >
                        <td style="border: 1px solid black;" ><b>Dr. '.$row->p_fname.'</b>
                        <br /><i>'.$row->SpecName.'</i>
                        <br />'.nl2br($row->doc_sign).'
                        <br />
                        <br /></td>
                        <td style="border: 1px solid black;text-align:center; vertical-align:top">
                        Signature of Consultant
                        <br /><br /><br /><br /><br />
                        </td>
                    </tr>';

                $mod_r=$row_num % 2;
                if($mod_r>0)
                {
                    $doc_list_main_sign.='<tr>';
                }
                
                $doc_list_main_sign.='
                    <td style="width:50%"><b>Dr. '.$row->p_fname.'</b><br/>['.$row->SpecName.'] <br/>
                        '.nl2br($row->doc_sign).'
                    </td>';

                if($mod_r==0)
                {
                    $doc_list_main_sign.='</tr>';
                }
            }
            
            $row_num=$row_num+1;
            $mod_r=$row_num % 2;
        
            if($mod_r==0)
            {
                
                $doc_list_main_sign.='<td style="width:50%"></td>
                </tr>';
            }
      
            $doc_list_sign.="</table>";
            $doc_list_main_sign.="</table>";
            
            $data['doc_list_sign']=$doc_list_sign;
            $data['doc_list_main_sign']=$doc_list_main_sign;
        }
        

        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');
        
        $file_name='MedicineList-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/Medicine_List',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");   
        
    }

    public function ipd_cash_print_pdf($ipd_id,$payid)
	{
		$sql="select *,date_format(payment_date,'%d-%m-%Y %h:%i %p') as payment_date_str ,
		if(credit_debit=0,'','Return') as Amount_str
		from payment_history_medical where Customerof_type=2 and ipd_id=$ipd_id and id=$payid";
		$query = $this->db->query($sql);
		$data['ipd_payment']= $query->result();
		
		$sql="select * from ipd_master where id =".$ipd_id;
		$query = $this->db->query($sql);
		$data['ipd_master']= $query->result();
		
		$pno=$data['ipd_master'][0]->p_id;
		
		$sql="select *,if(gender=1,'Male','Female') as xgender,
		IFNULL(GET_AGE_BY_DOB(dob),age)   AS age from patient_master where id='".$pno."' ";
        $query = $this->db->query($sql);
		$data['patient_master']= $query->result();
		
		$data['ipd_id']=$ipd_id;
		$data['payid']=$payid;

		//load mPDF library
				
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = false;
        
        $file_name='Charge_Invoice-'.date('Ymdhis').".pdf";

        $filepath=$file_name;

		$content=$this->load->view('Medical/Print//medical_cash_received_bill',$data,TRUE);
		
       	//echo $content;
		
		//$this->m_pdf->pdf->debug = true;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output("TEST.pdf","I");
	}

    public function Print_bill_on_uhid()
    {
        
    }

    public function Print_bill_on_uhid_print($uhis_id,$cash=0,$print_size=0)
    {
       //$cash 0 for Cash Only,1 for OPD Cridit
        $this->load->model('Medical_M');
        
        $cash_where="  ";
        
        if($cash==0)
        {
            $cash_where.=" and ipd_credit=0 ";
            $med_type=1;
        }elseif($cash==1)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=1 ";
            $med_type=2;
        }elseif($cash==2)
        {
            $cash_where.=" and ipd_credit=1 and  ipd_credit_type=0 ";
            $med_type=3;
        }

        $this->Medical_M->update_invoice_group_gst($ipd_id,$med_type);

        
        $sql="select i.inv_med_id,i.id,i.item_Name,i.formulation,
            i.batch_no,Date_Format(i.expiry,'%m-%Y')as expiry,
            i.price,i.qty,sum(i.amount) as amount,i.HSNCODE,
            m.inv_med_code,m.id as m_id,
            date_format(m.inv_date,'%d-%m-%Y') as str_inv_date,
            sum(twdisc_amount) as twdisc_amount,
            sum(i.disc_amount+i.disc_whole) as d_amt,
            sum(i.CGST+i.SGST) as gst,
            (i.CGST_per+i.SGST_per) as gst_per 
            from inv_med_item i join invoice_med_master m
            on i.inv_med_id=m.id
            where m.group_invoice_id>0 and i.qty>0 and m.ipd_id=".$ipd_id.$cash_where." 
            group by i.inv_med_id,i.id WITH ROLLUP";
        $query = $this->db->query($sql);
        $data['inv_items']= $query->result();
        
        $sql="select sum(CGST_Tamount) as CGST_Tamount,
        sum(SGST_Tamount) as SGST_Tamount,
        sum(TaxableAmount) as TaxableAmount,
        sum(net_amount) as net_amount,
        sum(payment_received) as payment_received,
        sum(payment_balance) as payment_balance,
        sum(discount_amount+item_discount_amount) as inv_disc_total,
        (CGST_Tamount+SGST_Tamount) as TGST from invoice_med_master 
        where  ipd_id=".$ipd_id.$cash_where." group by ipd_id";
        $query = $this->db->query($sql);
        $data['invoice_master']= $query->result();
        
        $sql="select m.p_id,p.p_fname,m.ipd_code,p.p_code, m.doc_name,
        m.org_id from  v_ipd_list m join patient_master p on m.p_id=p.id 
        where m.id=".$ipd_id;
        $query = $this->db->query($sql);
        $data['ipd_master']= $query->result();
        
        $sql="select * from organization_case_master where id='".$data['ipd_master'][0]->org_id."'";
        $query = $this->db->query($sql);
        $data['orgcase']= $query->result();
        
        $sql="select sum(if(p.credit_debit>0,p.amount*-1,p.amount)) as paid_amount 
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_Total']= $query->result();
        
        $sql="select p.*,if(p.credit_debit=0,p.amount,p.amount*-1) as paid_amount,
        Concat((case p.payment_mode when 1 then 'Cash' when 2 then 'Bank Card' else 'Other' end),if(p.credit_debit=0,'',' Return')) as Payment_type_str
        from payment_history_medical p join inv_med_group m
        on p.group_id=m.med_group_id
        where m.ipd_id=".$ipd_id." and m.med_type=".$med_type;
        $query = $this->db->query($sql);
        $data['payment_history']= $query->result();
        
        $sql="select * from inv_med_group 
        where ipd_id='".$ipd_id."' and med_type='".$med_type."'";
        $query = $this->db->query($sql);
        $data['inv_med_group']= $query->result();
        
        //load mPDF library
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(M_store);
        $this->m_pdf->pdf->showWatermarkText = true;
        
        $file_name='Report-MedicalBill-'.$ipd_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;

        //$this->load->view('Medical/medical_bill_print_format',$data);
        $content=$this->load->view('Medical/Print/medical_bill_print_format',$data,TRUE);

        //echo $content;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
        $this->m_pdf->pdf->Output($filepath,"I");
        //$this->m_pdf->pdf->Output('uploads/'.$filepath,"F"); 
        
    }
    

    public function print_purchase_return($inv_id)
	{
		$sql = "select p.id,p.p_r_invoice_no as Invoice_no,p.date_of_invoice,
			date_format(p.date_of_invoice,'%d/%m/%Y') as str_date_of_invoice,
			p.sid,s.name_supplier,s.short_name,s.gst_no,p.status as inv_status
			from purchase_return_invoice p join med_supplier s on p.sid=s.sid
			where p.id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice'] = $query->result();

		$sql = "select p.*,r.purchase_inv_id,r.qty as r_qty,Round(r.qty/p.packing,2) AS qty_pak,r.id as r_id,
        if(r.batch_no_r='',p.batch_no,r.batch_no_r) as  batch_no_r_s, 
		date_format(p.expiry_date,'%m/%y') as exp_date_str ,p.purchase_unit_rate*r.qty AS r_amount,p.purchase_unit_rate,
        if(p.CGST_per_old IS NULL,CGST_per,CGST_per_old)*2 AS gst_per
		from purchase_return_invoice_item r join purchase_invoice_item p on r.purchase_item_id=p.id
		where purchase_inv_id=" . $inv_id;
		$query = $this->db->query($sql);
		$data['purchase_return_invoice_item'] = $query->result();

        $content = $this->load->view('Medical/Stock/purchase_return_invoice_item_print', $data, true);

        //load mPDF library
				
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = false;
        
        $file_name='Return_Invoice-'.$inv_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;
		
       	//echo $content;
		
		$this->m_pdf->pdf->debug = true;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output($filepath,"I");
	}

    public function print_purchase($inv_id)
	{
		$sql="select * from med_supplier order by name_supplier";
		$query = $this->db->query($sql);
		$data['supplier_data']= $query->result();
		
		$sql="select p.*,s.name_supplier from purchase_invoice p join med_supplier s on p.sid=s.sid where p.id=".$inv_id;
		$query = $this->db->query($sql);
		$data['inv_master_data']= $query->result();

		$sql="select * from purchase_invoice_item where purchase_id='".$inv_id."' ";
        $query = $this->db->query($sql);
		$data['purchase_item']= $query->result();

        $content = $this->load->view('Medical/Stock/purchase_invoice_item_print', $data, true);

        //load mPDF library
				
        $this->load->library('m_pdf');

        //$this->m_pdf->pdf->SetProtection(array(), '1234567', '277395');

        $this->m_pdf->pdf->SetWatermarkText(H_Name);
        $this->m_pdf->pdf->showWatermarkText = false;
        
        $file_name='Return_Invoice-'.$inv_id."-".date('Ymdhis').".pdf";

        $filepath=$file_name;
		
       	//echo $content;
		
		$this->m_pdf->pdf->debug = true;

        //generate the PDF from the given html
        $this->m_pdf->pdf->WriteHTML($content);
 
        //download it.
		$this->m_pdf->pdf->Output("TEST.pdf","I");
	}
 
}

