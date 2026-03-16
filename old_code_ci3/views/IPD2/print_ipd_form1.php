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
    <table style="font-size: 14px;width:100%;" autosize="1" >
	<tr>
		<td style="vertical-align: top;">
            <img style="width: 75px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="vertical-align: top;text-align:center;">
            <p style="font-size: 22px;text-align:left;" ><?=H_Name?></p>
            <p  style="font-size: 12px;text-align:left;" ><?=H_address_1?>, Uttarakhand<br>
            <?php
                if(H_phone_No!='')
                {
                    echo 'Phone: '.H_phone_No;
                } 
            ?>
			</p>
        </td>
        <td style="vertical-align: top;;text-align:right;" >
        <?php
        $bar_content=$person_info[0]->p_code.':'.$ipd_info[0]->ipd_code .':P-'.date('Y-m-d H:i:s').':C-'.$person_info[0]->p_fname;
        ?>
        <barcode code="<?=$bar_content?>" size="0.8" type="QR" error="M" class="barcode" />
        </td>
	</tr>
	</table>
	<h3 align="center">BED HEAD TICKET</h3>
</htmlpageheader>

		<table  cellspacing="5"  style="font-size: 12px;width:100%;border-style: none;" autosize="1" >	
			<?php
				echo '<tr>';
					$Insurance='';
					if(count($case_master)>0){ 
						$Insurance= '<br/><b>Insurance : </b>'.$case_master[0]->insurance_company_name;
					}

					echo '<td style="vertical-align: top;"><b>Patient Information :</b><br/><br>
								<strong>Patient Name :</strong>'.$person_info[0]->title.' '.$person_info[0]->p_fname.'<br>
								<b>'.$person_info[0]->p_relative.'</b> '.$person_info[0]->p_rname.'<br><br>
								<b>Age :</b> '.$person_info[0]->age .'<br><br>
								<b>Gender :</b> '.$person_info[0]->xgender.'<br><br>
								
								<b>Phone No : </b>'.$person_info[0]->mphone1.'<br>
								<b>Contact Person Name :</b> '.$ipd_info[0]->contact_person_Name.'<br><br>
								<b>Relation  :</b> '.$ipd_info[0]->relation.'<br><br>
								<b>Relative Phone No :</b> '.$ipd_info[0]->P_mobile2.'<br><br>

								<strong>Patient Address </strong><br>
										'.$person_info[0]->add1.'<br />
										'.$person_info[0]->city.'<br />
										'.$person_info[0]->state.'
					</td>';
		

					$srno=1;
					$ipd_doc_list_show='';
					foreach($ipd_doc_list as $row)
					{ 
						$ipd_doc_list_show.= ' Dr. '.$row->p_fname.' ';
						$srno=$srno+1;
					}


					echo '<td align="Left" style="vertical-align: top;">
							<b>UHID :</b> '.$person_info[0]->p_code.'<br><br>
							<b>IPD Code :</b> '.$ipd_info[0]->ipd_code.'<br><br>
							<b>Bed Desc :</b> '.$ipd_info_2[0]->Bed_Desc.'<br><br>'.$Insurance.'
							<strong>Date And Time of Admission :</strong> '.$ipd_info[0]->str_register_date.' '.$ipd_info[0]->reg_time.'<br/><br/>
							<b>Case Type </b> : '.$ipd_info[0]->case_type_s.'<br /><br/>
							<b>Doctors : </b> : '.$ipd_doc_list_show.'
					</td>';
					
				echo '</tr>';
			?>
		</table>
		<h3 align="center" style="font-size: 18px;">सहमति  पत्र</h3>
		<p style="font-size: 18px;">
			मैं ईलाज कराने का/की  इक्छुक हूँ | मैं <?=H_Name_Hindi?> (<?=H_Name?>) के चिकित्सक और उनके सहायको को अपना उपचार , परीक्षण, परामर्श, जाँच, औषधि देने , चिकित्सा बेहोशी की दवा लगवाने की स्वीकृति देता हूँ | 
			मुझे मेरी /मेरे मरीज की बीमारी के बारे मे उसके खतरों से जटिलताओं (complications ) से अवगत करा दिया गया है | मुझे ईलाज मे होने वाले खर्च की जानकारी दे दी गई है एवं मै इलाज में आने वाले खर्चो के भुगतान को वहन करने की सहमति प्रदान करता हूँ
| मैंने अपनी सभी शंकाओं का निवारण कर लिया है । इस स्वीक्रति पत्र को मैंने ठीक प्रकार से पड़कर स्वयं समझकर  हस्ताक्षर के द्वारा अपनी  सहमति  प्रदान  करता / करती हूँ । 
				</p>
<br/>
		<table  cellspacing="0"  style="font-size: 14px;width:100%;border-style: none;" >
			<tr>
				<td align="left">
					
				</td>
				<td align="right">
				</td>
				<td align="Left">
				<br/>
					<br/>
					<br/>
					<br/>
					<br/>
					<br/>
					हस्ताक्षर /  अंगूठा  निशान
					
					<br/>
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
		