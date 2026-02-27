<style>@page {
        margin-top: 2.5cm;
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
    <table style="font-size: 14px;" cellpadding="0" width="100%">
	<tr>
		<td style="vertical-align: top;" width="15%">
            <img style="width: 35px;vertical-align: top;"  src="assets/images/<?=H_logo?>" />
        </td>
        <td style="vertical-align: top;">
            <p align="center" style="font-size: 15px;" ><?=H_Name?></p>
            <p align="center" style="font-size: 10px" ><?=H_address_1?>, Uttarakhand<br><br>
			IPD No.:<?=$ipd_info[0]->ipd_code?> / UHID:<?=$person_info[0]->p_code?> /Name : <?=strtoupper($person_info[0]->p_fname)?></p>
        </td>
        <td style="vertical-align: top;" align="right" width="30%">
        <?php
        $bar_content=$ipd_info[0]->ipd_code;
        $bar_content=$ipd_info[0]->id;
        ?>
        <barcode code="<?=$bar_content?>" size="0.6" type="C128A" error="M" class="barcode" />
        Page : {PAGENO}/{nbpg}
        </td>
	</tr>
</table>
<hr/>
</htmlpageheader>
<htmlpagefooter name="myFooter">
<table width="100%" style="font-size: 10px;">
<tr>
    <td width="15%" >Page : {PAGENO}/{nbpg}</td>
    <td width="85%" style="text-align: right;">IPD No.:<?=$ipd_info[0]->ipd_code?> / UHID:<?=$person_info[0]->p_code?> /Name : <?=strtoupper($person_info[0]->p_fname)?></td>
</tr>
</table>
</htmlpagefooter>
<h3 align="center">ADMISSION HISTORY AND PHYSICAL ASSESSMENT FORM</h3>
<p><strong>Patient Name :</strong> <?=strtoupper($person_info[0]->p_fname)?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Age :</strong> <?=$person_info[0]->age?> &nbsp; &nbsp; &nbsp; <strong>Gender :</strong> <?=$person_info[0]->xgender?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp;&nbsp; ]MLC&nbsp; /&nbsp; [ &nbsp;&nbsp; ]Non MLC</p>

<p><strong>UHID :</strong> <?=$person_info[0]->p_code?> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<strong> IPD No. :</strong> <?=$ipd_info[0]->ipd_code?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Date of Admission :</strong> <?=$ipd_info[0]->str_register_date?></p>

<p><strong>Diagnosis </strong>:</p>

<p><strong>Time of Patient Arrival :</strong> ______________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Time of Doctor&#39;s Assessment : </strong>________________________</p>

<p><strong>Consciousness :</strong> [ &nbsp;&nbsp; ]Awake /&nbsp; [ &nbsp;&nbsp; ]Alert /&nbsp; [ &nbsp;&nbsp; ]In pain &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; [ &nbsp;&nbsp; ]Response to verbalcommands / [ &nbsp;&nbsp; ]Unresponsive Pain</p>

<p><strong>GCS :</strong> E___&nbsp;&nbsp; V___ &nbsp; M___&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Airway &amp; Breathing :</strong> [ &nbsp;&nbsp; ]Clear / [ &nbsp;&nbsp; ]Noisy Breathing / [ &nbsp;&nbsp; ]Stridor / [ &nbsp;&nbsp; ]Obstruction</p>

<p><strong>R R :</strong>&nbsp; _____________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Accessory Muscle:</strong>&nbsp;&nbsp;&nbsp;&nbsp; + &nbsp; /&nbsp;&nbsp; -</p>

<p><strong>CPR / Code Blue : </strong>________________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Attended by S/N :</strong> ______________________</p>

<p><strong>Allergic To :</strong><br />
<br />
<strong>Direct to Department :</strong> ____________________________ <strong>Consultant In charge : </strong>____________________________</p>

<p><strong>Present Complaints :</strong><br />
<br />
<br />
<br />
&nbsp;</p>

<p>Last Urine Passed : ______________</p>

<p><strong>Past Medical History :</strong></p>

<table border="1" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td style="width:25%">Diabetes / Matabolic BS</td>
			<td style="width:25%">&nbsp;</td>
			<td style="width:25%">Jaundice / CLD</td>
			<td style="width:25%">&nbsp;</td>
		</tr>
		<tr>
			<td style="width:25%">Hypertension / IHD</td>
			<td style="width:25%">&nbsp;</td>
			<td style="width:25%">Osteoarthritis</td>
			<td style="width:25%">&nbsp;</td>
		</tr>
		<tr>
			<td style="width:25%">Asthma / COPD / PB</td>
			<td style="width:25%">&nbsp;</td>
			<td style="width:25%">Tuberculosis</td>
			<td style="width:25%">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4">Others :<br />
			<br />
			<br />
			&nbsp;</td>
		</tr>
	</tbody>
</table>

<p><strong>Drug History :</strong><br />
<br />
<br />
<br />
&nbsp;</p>

<p><strong>Past Surgical History :</strong><br />
<pagebreak>
<strong>Current Treatment:</strong></p>

<p>&nbsp;</p>

<p><strong>Personal History</strong></p>

<table border="1" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td>Smoking</td>
			<td>[ &nbsp;&nbsp; ]Yes / [ &nbsp;&nbsp; ]No</td>
			<td>Since :</td>
			<td>Per Day</td>
		</tr>
		<tr>
			<td>Alcohol</td>
			<td>[ &nbsp;&nbsp; ]Yes / [ &nbsp;&nbsp; ]No</td>
			<td>Since :</td>
			<td>(frequency)</td>
		</tr>
		<tr>
			<td>Drugs</td>
			<td>[ &nbsp;&nbsp; ]Yes / [ &nbsp;&nbsp; ]No</td>
			<td>Since :</td>
			<td>(frequency)</td>
		</tr>
		<tr>
			<td>Chewing Tobacco</td>
			<td>[ &nbsp;&nbsp; ]Yes / [ &nbsp;&nbsp; ]No</td>
			<td>Since :</td>
			<td>(frequency)</td>
		</tr>
		<tr>
			<td>Diet</td>
			<td>[ &nbsp;&nbsp; ]Veg / [ &nbsp;&nbsp; ]Non Veg</td>
			<td colspan="2">Sleep Pattern : [ &nbsp;&nbsp; ]Normal / [ &nbsp;&nbsp; ]Inadequate / [ &nbsp;&nbsp; ]Adequate</td>
		</tr>
		<tr>
			<td>Bowel Habits</td>
			<td colspan="3">[ &nbsp;&nbsp; ]Regular / [ &nbsp;&nbsp; ]Irregular</td>
		</tr>
		<tr>
			<td>Bladder Habits</td>
			<td colspan="3">[ &nbsp;&nbsp; ]Normal / [ &nbsp;&nbsp; ]Regular / [ &nbsp;&nbsp; ]Irregular</td>
		</tr>
		<tr>
			<td>Occupation</td>
			<td colspan="3">&nbsp;</td>
		</tr>
	</tbody>
</table>

<h3><strong>For Female Patients :</strong></h3>

<p>Age of Menarchy&nbsp; ______________ &nbsp; &nbsp; &nbsp; Menopause : [ &nbsp;&nbsp; ]Yes/[ &nbsp;&nbsp; ]No&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Age of Menopause _____________________</p>

<p>Manstrual History &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; [ &nbsp;&nbsp; ]Reuglar / [ &nbsp;&nbsp; ]Irregular / [ &nbsp;&nbsp; ]Dysmenorrhea / [ &nbsp;&nbsp; ]Vaginal discharge</p>

<p>Pregnancy&nbsp; [ &nbsp;&nbsp; ] Yes&nbsp; /&nbsp; [ &nbsp;&nbsp; ] No&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; if yes Gravids ________________ LMP ______________________</p>

<p>[ &nbsp;&nbsp; ]Abortion&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ &nbsp;&nbsp; ]Spontaneous Threatened&nbsp;&nbsp;&nbsp; [ &nbsp;&nbsp; ]Missed &nbsp; &nbsp; [ &nbsp;&nbsp; ]Other specify ______________</p>

<p>Others :<br />
&nbsp;</p>

<p><strong>Family History</strong></p>

<table border="1" cellpadding="5" cellspacing="0" style="width:100%">
	<tbody>
		<tr>
			<td>&nbsp;</td>
			<td>YES</td>
			<td>NO</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>YES</td>
			<td>NO</td>
		</tr>
		<tr>
			<td>Hypertension / IHB / BYS Dyslipidema</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>Arthritis / Gout</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Diabetes / Thyroid DS</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>Cancer</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Tuberculosis / Asthma / COPD</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>Any Othe</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>Epilepsy / Mental illness</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
	</tbody>
</table>

<hr />
<h3>Declaration by the Patient / Relatives Accompany person</h3>

<p>I hereby declare that the facts recorded above are based on my own narration and accurate to the best of my knowledge.</p>

<p>Name of Patient / Relative / Accompanying Person ______________________________________________</p>

<p><br />
Relationship ____________________________ Singnature ______________________________</p>

<p>Date : _______________________ Time : ___________________</p>

<pagebreak>
<h2 style="text-align:center">General Examination</h2>

<h3>Vital Sign</h3>

<p>RBS : ______&nbsp;&nbsp; Temp.: ______&nbsp;&nbsp; Pulse: ______ /min&nbsp;&nbsp; RR: ______ /min &nbsp; BP: ______ /mm hz&nbsp;&nbsp; SPO2: ______&nbsp; %</p>

<p>BP : Standing ___________ mm of Hg&nbsp;&nbsp; Sitting ___________mm of Hg Supine ___________mm of Hg</p>

<p>P______R.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Rythm _________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Chorecter__________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Palpabl/Non Palble</p>

<hr />
<p>Pain : [ &nbsp; ]Yes / [ &nbsp; ]No</p>

Score : [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ]&nbsp;&nbsp;&nbsp; (<em>0-No Pain -----------------5 Moderate Pain --------------------- 10 Worst Pain</em>)</p>

<p>Location</p>

<hr />
<p><strong>General Appearance :&nbsp;&nbsp; </strong> [ &nbsp; ]Normal / [ &nbsp; ]Pallor / [ &nbsp; ]Icterus / [ &nbsp; ]Cyanosis / [ &nbsp; ]Edema / [ &nbsp; ]Clubbing / [ &nbsp; ]J.V.P.</p>

<p><strong>General Condition :</strong> &nbsp; &nbsp; &nbsp;&nbsp; [ &nbsp; ]Poor /&nbsp;&nbsp; [ &nbsp; ]Fair / [ &nbsp; ]Semi Conscious / [ &nbsp; ]Delirious / [ &nbsp; ]Drowsy / [ &nbsp; ]Unconscious</p>

<p>&nbsp;</p>

<h3><strong>Systemic Examination</strong></h3>

<p><strong>1. ENT :</strong> &nbsp;&nbsp; [ &nbsp; ] NAD / [ &nbsp; ] Ear ache / [ &nbsp; ] Ear discharge / [ &nbsp; ] Tinnitus / [ &nbsp; ] Vertigo / [ &nbsp; ] Voice Change</p>

<p><strong>2. EYE : &nbsp;&nbsp;</strong> [ &nbsp; ] NAD / [ &nbsp; ] Redness / [ &nbsp; ] Discharge / [ &nbsp; ] Pain / [ &nbsp; ] Vision / [ &nbsp; ] Conjunctival hemorrhage</p>

<p>Other : ________________________________________________________________________________</p>

<p><strong>3. CARDIOVASCULAR SYSTEM :&nbsp;&nbsp; </strong>[ &nbsp; ] NAD / [ &nbsp; ] Chest Pain / [ &nbsp; ] Hypertension / [ &nbsp; ] Syncope / [ &nbsp; ] Palpitation</p>

<p>Apex beat : [ &nbsp; ] S1 / [ &nbsp; ] S2 / [ &nbsp; ] S3 / [ &nbsp; ] S4 / [ &nbsp; ] Gallop / [ &nbsp; ] Thrill / [ &nbsp; ] Bruits</p>

<p>&nbsp;</p>

<p><strong>4. RESPIRATORY SYSTEM : </strong>[ &nbsp; ] NAD / [ &nbsp; ] Cough / [ &nbsp; ] Breathlessness / [ &nbsp; ] Hemoptysis</p>

<p>Shape of Chest : [ &nbsp; ] Normal / [ &nbsp; ] Barrel / [ &nbsp; ] Pigeon / ______________________________</p>

<p>Breath Sound : [ &nbsp; ] Rales / [ &nbsp; ] Crackles / [ &nbsp; ] Inspiratory Wheeze / [ &nbsp; ] Expiratory Wheeze / [ &nbsp; ] Crepts</p>

<p>Specify other : ________________________________________________________________________</p>

<p>&nbsp;</p>

<p><strong>5. ABDOMEN :</strong>[ &nbsp; ] NAD / [ &nbsp; ] Hematuria / [ &nbsp; ] Diarrhoea / [ &nbsp; ] Constipation / [ &nbsp; ] Vomiting / [ &nbsp; ] Bleeding P/R / [ &nbsp; ] Malena</p>

<p>Others : ______________________________________________________________________________</p>

<p>&nbsp;</p>

<p><strong>6. GENITOURINARY SYSTEM:</strong>&nbsp;&nbsp;&nbsp; [ &nbsp; ] NAD / [ &nbsp; ] Hematuria / [ &nbsp; ] Dysuria / [ &nbsp; ] Nocturiaa / [ &nbsp; ] Bladder distension / [ &nbsp; ] H/O Renal distance</p>

<p><strong>7. HEMATOLOGICAL SYSTEM:</strong> &nbsp;[ &nbsp; ] NAD / [ &nbsp; ] Anemia / [ &nbsp; ] Chemotherapy / [ &nbsp; ] Blood transfusice /[ &nbsp; ] Thalassaemia&nbsp; /[ &nbsp; ] Sickle cell disease</p>

<p>Other ________________________________________________________________</p>

<p>&nbsp;</p>

<p><strong>8. INTEGUMENTARY SYSTEM:</strong>&nbsp;&nbsp;&nbsp;[ &nbsp; ] NAD / [ &nbsp; ] Dry / [ &nbsp; ] Well hydrated / [ &nbsp; ] Rash / [ &nbsp; ] Itching / [ &nbsp; ] Discoloration /</p>

<p>[ &nbsp; ] Breast Changes / [ &nbsp; ] Old surgical scars /</p>

<p>[ &nbsp; ] Specify skin disorder present __________________________</p>

<p><strong>9. MUSCULOSKELETAL SYSTEM:</strong>&nbsp; [ &nbsp; ] NAD / [ &nbsp; ] Weakness / [ &nbsp; ] Numness / [ &nbsp; ] Burning Pain / [ &nbsp; ] Claudication / [ &nbsp; ] Rest pain in legs</p>

<p>Upper Extrernities _________________________________ Lower Extremities__________________________________________</p>

<p>Spine Scoliosis / Kyphosis / Lordosis Tenderness Others ____________________________</p>

<p>&nbsp;</p>

<p><strong>10. NEUROLOGICAL ASSESSMENT:&nbsp;&nbsp; </strong>[ &nbsp; ] NAD / [ &nbsp; ] Alert / [ &nbsp; ] Oriented / [ &nbsp; ] Slurring of speech / [ &nbsp; ] Tremors / [ &nbsp; ] Confused</p>

<p>Movement co ordinated&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[ &nbsp; ] YES / &nbsp;&nbsp; [ &nbsp; ] NO</p>

<p>Gait ___________________ Reflexes ________________________ Tremors_________________________</p>

<p>Other _____________________________________________________________________________________</p>

<hr />
<p><strong>PROVISIONAL DIAGNOSIS</strong></p>

<p>&nbsp;</p>

<p>&nbsp;</p>

<p><strong>PLAN OF CARE DURING HOSPITALIZATION</strong></p>
