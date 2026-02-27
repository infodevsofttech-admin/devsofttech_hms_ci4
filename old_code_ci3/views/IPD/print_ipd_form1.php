<style>@page {
        margin-top: 5.2cm;
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
    <table style="width: 100%;font-size: 12px;" cellpadding="5">
	<tr>
    	<td style="width: 60%;vertical-align: top;">
		    <p align="center" style="font-size: 16px;" ><?=H_Name?></p>
		    <p align="center" style="font-size: 12px" ><?=H_address_1?>,<br><?=H_address_2?><br>
            <?php 
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
                
                if(H_Email!='')
                {
                   echo ' ,Email: '.H_Email;
                }
            ?>
		 </td>
		 <td style="width: 40%;">
		 	<p style="font-size: 12px">
			 Patient Info :<br/>
			 	<strong>Patient Name :</strong> <?=$person_info[0]->p_fname?> <br>
				<b> <?=$person_info[0]->p_relative?> </b> <?=$person_info[0]->p_rname?> <br>
				<b>Age :</b> <?=$person_info[0]->age ?><br>
				<b>Gender :</b> <?=$person_info[0]->xgender ?><br>
			</p>
		 </td>
	</tr>
</table>
<h3 align="center">Face Sheet</h3>
</htmlpageheader>

		<table  cellspacing="0"  style="font-family: freesans;font-size: 12px;width:100%;border-style: none;" >	
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
		<h3 align="center" style="font-size: 16px;">सहमति  पत्र</h3>
		<p style="font-family: freesans">
			मैं ईलाज कराने का/की  इक्छुक हूँ | मैं  <?=H_Name_Hindi?>(<?=H_Name?>)   के चिकित्सक और उनके सहायको को अपना उपचार , परीक्षण, परामर्श, जाँच, औषधि देने , चिकित्सा दवा लगवाने की स्वीकृति देता हूँ | 
			मुझे मेरी /मेरे मरीज की बीमारी के बारे मे उसके खतरों से जटिलताओं (complications ) से अवगत करा दिया गया है | मुझे ईलाज मे होने वाले खर्च की जानकारी दे दी गई है | मैंने अपनी सभी शंकाओं का निवारण कर लिया है । इस स्वीक्रति पत्र को मैंने ठीक प्रकार से पड़कर स्वयं समझकर  हस्ताक्षर के द्वारा अपनी  सहमति  प्रदान  करता / करती हूँ । 
		<p/>
		<p>
		<br/><br/>
		<br/><br/>
		<br/><br/>
		<br/><br/>
		</p>
		

		<table width="800px"  style="font-size: 16px;border-style: none;font-family: freesans;" >
			<tr>
				<td align="left" width="400px">
					<br/>Prepared By : <?=$user->first_name.' '.$user->last_name.'['.$user->id.']' ?>
				</td>
				<td align="right">
				</td>
				<td align="Left" width="400px">
				हस्ताक्षर /  अंगूठा  निशान
					<br/>
					<br/>
					<br/>
					नाम : _______________________________________________
					<br/>
					<br/>
					<br/>
					मरीज / मरीज  से संबंध :  _____________________________
					<br/>
					<br/>
					<br/>
					पता : _______________________________________________
					<br/>
					<br/>
					<br/>
					<br/>
					फ़ोन नंबर  : _________________________________________
				</td>
			</tr>
		</table>
		