<style>@page {
        margin-top: 6.2cm;
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
            <img style="width: 75px;vertical-align: top;"  src="/assets/images/<?=H_logo?>" />
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
<h3 align="center">Face Sheet</h3>
<h3 align="center">Admission to Covid Ward</h3>
</htmlpageheader>

		<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: none;" >	
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
		<h3 align="center">सहमति  पत्र</h3>
		<p style="font-size: 12px;width:100%;border-style: none;">
			मैं ईलाज कराने का/की  इक्छुक हूँ | मैं  <?=H_Name_Hindi?>(<?=H_Name?>)   के चिकित्सक और उनके सहायको को अपना उपचार , परीक्षण, परामर्श, जाँच, औषधि देने , चिकित्सा बेहोशी की दवा लगवाने की स्वीकृति देता हूँ | 
			मुझे मेरी /मेरे मरीज की बीमारी के बारे मे उसके खतरों से जटिलताओं (complications ) से अवगत करा दिया गया है | मुझे ईलाज मे होने वाले खर्च की जानकारी दे दी गई है | मैंने अपनी सभी शंकाओं का निवारण कर लिया है । इस स्वीक्रति पत्र को मैंने ठीक प्रकार से पड़कर स्वयं समझकर  हस्ताक्षर के द्वारा अपनी  सहमति  प्रदान  करता / करती हूँ । 
		<p/>
		<p style="font-size: 12px;width:100%;border-style: none;">
			COVID वार्ड में भर्ती होने वाले  मरीजों से  विनर्म निवेदन है की   दिए गए निर्देशों का पालन करे  :
		<ul style="font-size: 12px;width:100%;border-style: none;">
			<li>सभी मरीजों की देखभाल और भोजन अस्पताल द्वारा किया  जाएगा।</li>
			<li>COVID वार्ड में मरीजों को  सलाह दी जाती है कि वार्ड में कोई फोन और कोई भी उपकरण न  दिया जाए।</li>
			<li>परिचारक आधिकारिक फोन के माध्यम से अपने मरीजों से शाम 4:00 बजे से 6:00 बजे के बीच बात कर सकेंगे।</li>
			<li>यह सुनिश्चित करने के लिए कि भुगतान सुचारू तरीके से किया जाए, आपको  5 दिनों के लिए अग्रिम भुगतान  Rs.80000(Rs.16000/दिन) करना होगा ।</li>
			<li>अस्पताल से  remdesivir, fabiflu, low molecular weight heparin, tocilizumab, steroids जैसे दवाइयां का का भुगतान अलग से करना होगा। </li>
			<li>मरीज से संबंधित सभी प्रकार की जांचो  का का भुगतान अलग से करना होगा। </li>
			<li>अपनी TPA/ स्वास्थ्य बीमा की जानकारी के  लिए अस्पताल में श्रीमती अंजू मोंगिया (मुख्य व्यवसाय विकास अधिकारी) (कमरा नंबर 11) से संपर्क करें ।</li>
			<li>किसी भी तरह के अस्थिरता के लिए कानूनी कार्रवाई की जाएगी ।</li>
			<li>किसी भी प्रकार की अन्य जानकारी या सहायता के लिए  निम्नलिखित नंबरों पर संपर्क कर सकते हैं ।<br/>
				7351212111, 9536006200, 9837003915, 7351111146, 9927024103
			</li>
		</ul>
		</p>

		<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: none;" >	
			<tr>
				<td align="left">
					<br/>Prepared By : <?=$user->first_name.' '.$user->last_name.'['.$user->id.']' ?>
				</td>
				<td align="right">
				<br/> 
				</td>
				<td align="Left">
					हस्ताक्षर /  अंगूठा  निशान
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
		