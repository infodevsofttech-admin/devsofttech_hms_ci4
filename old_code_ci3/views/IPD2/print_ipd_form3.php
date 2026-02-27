<style>@page {
        margin-top: 4.5cm;
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
<h3 align="center">SELF DECLARATOION FROM HEALTH INSURANCE CARD HOLDER</h3>
</htmlpageheader>

		<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: none;" >	
			<?php
				echo '<tr>';
					$Insurance='';
					$insurance_company_name='_____________________________';
					if(count($case_master)>0){ 
						$Insurance= '<br/><b>Insurance : </b>'.$case_master[0]->insurance_company_name.'<br/>'.$case_master[0]->insurance_no.'<br/>'.$case_master[0]->insurance_no_1;
						$insurance_company_name=$case_master[0]->insurance_company_name;
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
		<h3 align="center">मेडिक्लेम बीमा कार्ड धारक द्वारा  स्व:घोषणा	</h3>
		<p style="font-size: 12px;width:100%;border-style: none;">
			मैं  _________________________________ यह घोषणा  करता / करती हूँ  कि मैं /मेरा/ मेरी/ मरीज  <?=$person_info[0]->p_fname?>   का 
			<?=$insurance_company_name?>  द्वारा मेडिक्लैम इनश्योरेंस है 
			जो दिनांक  <?=$ipd_info[0]->str_register_date?> को <?=H_Name_Hindi?> में इलाज हेतु भर्ती हुआ /किया गया है |
			मैंने स्वयँ अपना/ अपने मरीज का इनश्योरेंस कार्ड, आधार कार्ड एवं पैन कार्ड की फोटो कॉपी टी0 पी0 ए0 ऑफिस   में जमा कर रहा/ रही हूँ/ जमा  कर दिया है! 
			हमारे मेडिक्लैम इनश्योरेंस की रूम लिमिट रु0 ____________________________ हैं |
			यदि इनश्योरेंस कम्पनी से मरीज का क्लेम पास नहीं होता है  अथवा क्लेम किया गया बिल पूरा पास नहीं होता है तो पूरा बिल अथवा कटी हुई धनराशि जो भी देय है का भुगतान मैं स्वयं करूँगा/ करुँगी!

		<p/>
		<table  cellspacing="0"  style="font-size: 10px;width:100%;border-style: none;" >
			<tr>
				<td align="left">
					<br/>Prepared By : <?=$user->first_name.' '.$user->last_name.'['.$user->id.']' ?>
				</td>
				<td align="right">
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
		