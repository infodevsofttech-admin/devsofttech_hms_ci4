<style>@page {
        margin-top: 5.5cm;
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
<h3 align="center">General Consent for Cashless Treatment<br/>कॅशलेस उपचार हेतु सामान्य सहमति पत्र</h3>
</htmlpageheader>
<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: none;" >	
			<?php
				echo '<tr>';
					echo '<td ><strong>Patient Name (मरीज का नाम):</strong>'.$person_info[0]->p_fname.'<br>
								<b>'.$person_info[0]->p_relative.'</b>'.$person_info[0]->p_rname.'<br>
								<b>Age / Gender (आयु / लिंग): </b> '.$person_info[0]->age .' / '.$person_info[0]->xgender.'<br>
								<b>IPD / UHID No.(आईपीडी / यूएचआईडी नं.): </b> '.$person_info[0]->p_code.' / '.$ipd_info[0]->ipd_code.'<br>
								<strong>Date of Admission (प्रवेश की तिथि): </strong> '.$ipd_info[0]->str_register_date.' / TIME (समय) : '.$ipd_info[0]->reg_time.'<br/>
								<b>Case Type </b> : '.$ipd_info[0]->case_type_s.'<br />
								<b>Phone No : </b>'.$person_info[0]->mphone1.'
					</td>';

					echo '<td align="Left">
								<b>Contact Person Name (परिचारक का नाम) :</b> '.$ipd_info[0]->contact_person_Name.'<br>
								<b>Relation (परिचारक से संबंध) :</b> '.$ipd_info[0]->relation.'<br>
								<b>Relative Phone No (परिचारक का फोन नंबर):</b> '.$ipd_info[0]->P_mobile1.'<br>
								<strong>Patient Address (मरीज का पता)</strong><br/>
										'.$person_info[0]->add1.'<br /> , 
										'.$person_info[0]->add2.'<br /> ,
										'.$person_info[0]->city.'<br /> ,
										'.$person_info[0]->state.'
						</td>';
					
				echo '</tr>';
			?>
		</table>
		<h3 align="center">(Declaration by the Patient / Attendant   मरीज / परिचारक द्वारा घोषणा )</h3>
		<p style="font-size: 12px;width:100%;border-style: none;">
			I, Mr. / Ms. / Mrs. ______________________________________________________, S/o / D/o / W/o ______________________________________, 

resident of ______________________________________, hereby declare and give my consent as follows:<br/><br/>
मैं, श्री / श्रीमती / कु. ______________________________________________________, पुत्र / पुत्री / पत्नी ______________________________________, निवासी
 
______________________________________, निम्नलिखित घोषणा करता/करती हूँ और अपनी सहमति प्रदान करता/करती हूँ:

</p>
<p>
	1.	My treatment is being carried out under the TPA/AYUSHMAN/GOLDEN CARD scheme on a cashless basis, and no money is being taken from me or my attendants by the hospital for treatment, medicines, investigations, or any other charges.<br/><br/>
मेरा उपचार टी.पी.ए./आयुष्मान/गोल्डन कार्ड योजना के अंतर्गत कॅशलेस आधार पर किया जा रहा है, और अस्पताल द्वारा मुझसे या मेरे परिचारक से उपचार, दवाइयाँ, जाँच या अन्य किसी शुल्क के लिए कोई धनराशि नहीं ली जा रही है।<br/><br/>
2.	I have been informed that in case the pre-authorization is rejected or the claim is denied during the treatment period, I will be personally responsible for paying the treatment expenses to the hospital.<br/><br/>
मुझे सूचित किया गया है कि यदि प्री-ऑथराइजेशन अस्वीकृत हो जाता है या दावा अस्वीकृत कर दिया जाता है, तो उपचार का व्यय चुकाने की जिम्मेदारी मेरी व्यक्तिगत होगी।<br/><br/>
3.	This consent is given voluntarily, after being explained about the benefits, process, and possible liabilities under the scheme, without any pressure or misrepresentation.<br/><br/>
यह सहमति मैं पूरी जानकारी प्राप्त करने के बाद स्वेच्छा से दे रहा/रही हूँ। मुझे योजना के लाभ, प्रक्रिया एवं संभावित दायित्व समझा दिए गए हैं और इस पर कोई दबाव या गलत जानकारी नहीं दी गई है।<br/><br/>
</p>
<p>
	Patient / Attendant Details (मरीज / परिचारक का विवरण)<br/><br/>
•	Name (नाम): ___________________________________<br/><br/>
•	Relation with Patient (मरीज से संबंध): ___________________________________<br/><br/>
•	Signature / Thumb Impression (हस्ताक्षर / अंगूठे का निशान): ___________________________<br/><br/>
•	Contact Number (संपर्क नंबर): ___________________________________

</p>
		

		