<style>@page {
        margin-top: 4.2cm;
        margin-bottom: 1.2cm;
        margin-left: 1cm;
        margin-right: 0.5cm;
        
        margin-header:0.5cm;
        margin-footer:0.5cm;
        header: html_myHeader;
        footer: html_myFooter;
}
</style>

<table width="100%" border="1">
    <tr>
        <td width="33.3%">
            UHID : '.$patient_master[0]->p_code.' '.$old_uhid.'<br>
            Name : <strong>'.$patient_master[0]->title .' '.strtoupper($patient_master[0]->p_fname).'</strong><br>
            '.$patient_master[0]->p_relative.'  '.strtoupper($patient_master[0]->p_rname).'<br>
            Gender/Age : <b>'.$patient_master[0]->xgender.' / '.$patient_master[0]->age.' </b><br>
        </td>
        <td width="33.3%">
            OPD No.: <B>'.$opd_master[0]->opd_code.'</B><br>
            OPD Fee: '.$opd_master[0]->opd_fee_amount .' ['.$opd_master[0]->opd_fee_desc .']<br>
            <b>Date: '.$opd_master[0]->str_apointment_date .'</b><br>'.$exp_date.'
        </td>
        <td width="33.3%">
            Sr No.: '.$opd_master[0]->opd_no .' <br/>
            P.No. :'.$patient_master[0]->mphone1 .'
            Address :'.$patient_master[0]->add1.','.$patient_master[0]->add2.
            '<Br>'.$patient_master[0]->city.' '.$patient_master[0]->district.
            ' '.$patient_master[0]->state.' 							
        </td>
    </tr>
<table>	