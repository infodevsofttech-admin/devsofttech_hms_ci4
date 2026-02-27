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

<htmlpageheader name="myHeader">
    <table style="font-size: 12px;" cellpadding="5">
	<tr>
		<td style="width: 20%;vertical-align: top;">
            <img style="width: 75px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="width: 60%;vertical-align: top;">
            <p align="center" style="font-size: 22px;" ><?=H_Name?></p>
            <p align="center" style="font-size: 12px" ><?=H_address_1?>, Uttarakhand<br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
        </td>
        <td style="width: 20%;vertical-align: top;text-align: right;">
            <?php
            $bar_content=$person_info[0]->p_code.':'.$ipd_info[0]->ipd_code .':P-'.date('Y-m-d H:i:s').':C-'.$person_info[0]->p_fname;
            ?>
            <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
	</tr>
</table>
<h3 align="center">Clearance Vheck List</h3>
</htmlpageheader>
		<table  cellspacing="0"  style="font-size: 12px;width:100%;border-style: none;" >	
			<?php
				echo '<tr>';
					$Insurance='';
					if(count($case_master)>0){ 
						$Insurance= '<br/><b>Insurance : </b>'.$case_master[0]->insurance_company_name;
					}

					echo '<td ><b>Patient Information :</b><br/>
								<strong>Patient Name :</strong>'.$person_info[0]->p_fname.'<br>
								<b>'.$person_info[0]->p_relative.'</b>'.$person_info[0]->p_rname.'<br>
								<b>Age :</b> '.$person_info[0]->age .'<br>
								<b>Gender :</b> '.$person_info[0]->xgender.'<br>
								<b>UHID/Patient Code :</b> '.$person_info[0]->p_code.'<br>
								<b>IPD Code :</b> '.$ipd_info[0]->ipd_code.'<br>
								<b>Phone No : </b>'.$person_info[0]->mphone1.$Insurance.'
								
					</td>';

					echo '<td align="Left">
								<b>Contact Person Name :</b> '.$ipd_info[0]->contact_person_Name.'<br>
								<b>Relation  :</b> '.$ipd_info[0]->relation.'<br>
								<b>Relative Phone No :</b> '.$ipd_info[0]->P_mobile1.'<br>
								<strong>Patient Address <br/></strong>
										'.$person_info[0]->add1.'<br /> , 
										'.$person_info[0]->add2.'<br /> ,
										'.$person_info[0]->city.'<br /> ,
										'.$person_info[0]->state.'
						</td>';

					$srno=1;
					$ipd_doc_list_show='';;
					foreach($ipd_doc_list as $row)
					{ 
						$ipd_doc_list_show.= ' Dr. '.$row->p_fname.' / ';
						$srno=$srno+1;
					}


					echo '<td align="Left">
							<strong>Date of Admission :</strong> '.$ipd_info[0]->str_register_date.'<br/>
							<strong>Admission Time :</strong> '.$ipd_info[0]->reg_time.'<br/>
							<b>Date of Discharge : </b>'.$ipd_info[0]->discharge_date.'<br>
							<b>Time of Discharge : </b>'.$ipd_info[0]->discharge_time.'<br>
							<b>Case Type </b> : '.$ipd_info[0]->case_type_s.'<br />
							<b>Doctors : </b> : '.$ipd_doc_list_show.'
					</td>';
					
				echo '</tr>';
			?>
		</table>
        <hr/>
        <br/><br/>
		<table  cellspacing="0" cellpadding=5  style="font-size: 16px;width:100%;" border="1">	
			<tr>
				<th align="Center" width="40%">
					Check Point
				</th>
				<th align="Center">
					Checked By
				</th>
			</tr>
            <tr>
				<td align="Left" valign="middle"   width="40%">
					<br/><br/>Medical Store<br/><br/>.
				</td>
				<td align="Center">
                    <br/><br/>____________________________________________<br/><br/>.
				</td>
			</tr>
            <tr>
				<td align="Left" width="40%">
					<br/><br/>Pathology<br/><br/>.
				</td>
				<td align="Center">
                    <br/><br/>____________________________________________<br/><br/>.  
				</td>
			</tr>
            <tr>
				<td align="Left" width="40%">
					<br/><br/>Radiology<br/><br/>.
				</td>
				<td align="Center">
                    <br/><br/>____________________________________________<br/><br/>. 
				</td>
			</tr>
            <tr>
				<td align="Left" width="40%">
					<br/><br/>Physiotherapy<br/><br/>.
				</td>
				<td align="Center">
                    <br/><br/>____________________________________________<br/><br/>. 
				</td>
			</tr>
            <tr>
				<td align="Left" width="40%">
					<br/><br/>Private ward<br/><br/>.
				</td>
				<td align="Center">
                    <br/><br/>____________________________________________<br/><br/>. 
				</td>
			</tr>
            <tr>
				<td align="Left" width="40%">
					<br/><br/><br/><br/>
				</td>
				<td align="Center">
                    <br/><br/><br/><br/>
				</td>
			</tr>
		</table>
		