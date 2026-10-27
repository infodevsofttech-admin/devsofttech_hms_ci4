<?php

namespace App\Database\Seeds\Data;

/**
 * Standard Inpatient Clinical Documents & NABH Master Forms for `ipd_document_templates`.
 */
class IpdDocumentSeedData
{
    public static function getForms(): array
    {
        return [
            // =================================================================
            // Form #1: Face Sheet / Inpatient Admission Record
            // =================================================================
            [
                'form_no'             => 1,
                'template_name'       => 'Inpatient Admission Face Sheet & Registration Record',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:11px; line-height:1.4;">
    <table style="width:100%; border-bottom:2px solid #0f172a; margin-bottom:12px; padding-bottom:6px;">
        <tr>
            <td style="width:15%;"><img src="{{H_logo_abs}}" style="max-height:55px; max-width:110px;"></td>
            <td style="width:85%; text-align:center;">
                <h2 style="margin:0; font-size:18px; color:#0f172a;">{{H_Name}}</h2>
                <div style="font-size:10px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
                <div style="font-size:12px; font-weight:bold; margin-top:4px; text-transform:uppercase;">INPATIENT ADMISSION RECORD & FACE SHEET</div>
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;" border="1" cellpadding="5">
        <tr style="background:#f1f5f9;">
            <td style="width:25%;"><b>UHID / Patient ID:</b> {{UHID}}</td>
            <td style="width:25%;"><b>IPD Admission No:</b> {{IPD_CODE}}</td>
            <td style="width:25%;"><b>Admission Date & Time:</b> {{ADMIT_DATETIME}}</td>
            <td style="width:25%;"><b>Ward / Bed No:</b> {{WARD_BED}}</td>
        </tr>
        <tr>
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>Age / Gender:</b> {{AGE_GENDER}}</td>
            <td><b>Marital Status:</b> {{MARITAL_STATUS}}</td>
            <td><b>Contact No:</b> {{MOBILE_NO}}</td>
        </tr>
        <tr>
            <td><b>Father / Spouse:</b> {{FATHER_SPOUSE}}</td>
            <td><b>Relation:</b> {{RELATION}}</td>
            <td colspan="2"><b>Residential Address:</b> {{PATIENT_ADDRESS}}</td>
        </tr>
        <tr>
            <td><b>Treating Doctor:</b> {{DOCTORS}}</td>
            <td><b>Department:</b> {{DEPARTMENT}}</td>
            <td><b>Admission Type:</b> Emergency [ ] / Elective [ ]</td>
            <td><b>MLC Case:</b> Yes [ ] / No [ ]</td>
        </tr>
        <tr>
            <td colspan="2"><b>Referred By:</b> {{REFERRED_BY}}</td>
            <td colspan="2"><b>Insurance / TPA:</b> {{TPA_NAME}} (Policy / Card: {{POLICY_NO}})</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:12px;" border="1" cellpadding="5">
        <tr style="background:#f1f5f9;"><th colspan="2" style="text-align:left;">PROVISIONAL DIAGNOSIS & ALLERGY STATUS</th></tr>
        <tr>
            <td style="width:70%;"><b>Provisional Diagnosis at Admission:</b><br>{{PROVISIONAL_DIAGNOSIS}}</td>
            <td style="width:30%; color:#dc2626;"><b>Known Drug Allergies:</b><br>[ ] None Known<br>[ ] Penicillin [ ] Sulfa [ ] NSAIDs<br>Others: ____________________</td>
        </tr>
    </table>

    <table style="width:100%; margin-top:30px; font-size:10px;">
        <tr>
            <td style="width:33%; text-align:center;">___________________________<br><b>Admission Staff Signature</b></td>
            <td style="width:34%; text-align:center;">___________________________<br><b>Patient / Attendant Signature</b></td>
            <td style="width:33%; text-align:center;">___________________________<br><b>Admitting Doctor Signature</b></td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #3: Informed Consent for Admission & Treatment (Bilingual)
            // =================================================================
            [
                'form_no'             => 3,
                'template_name'       => 'General Informed Consent for Admission & Treatment (Bilingual)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10.5px; line-height:1.45;">
    <div style="text-align:center; border-bottom:1.5px solid #0f172a; padding-bottom:6px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">GENERAL INFORMED CONSENT FOR ADMISSION, INVESTIGATION & MEDICAL TREATMENT</div>
        <div style="font-size:10px; color:#475569;">सामान्य भर्ती, जांच एवं चिकित्सा उपचार सहमति प्रपत्र</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr>
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Gender:</b> {{AGE_GENDER}}</td>
        </tr>
    </table>

    <div style="margin-bottom:8px;">
        <b>1. Consent for Medical Care & Nursing / सामान्य चिकित्सा सहमति:</b><br>
        I voluntarily authorize the physicians, nursing staff, and medical personnel of {{H_Name}} to administer medical care, perform diagnostic examinations, diagnostic blood/radiology tests, nursing procedures, administration of prescribed intravenous fluids and medications deemed medically necessary.<br>
        <i>मैं स्वेच्छा से अस्पताल के डॉक्टरों और नर्सिंग स्टाफ को मेरे/रोगी के आवश्यक नैदानिक परीक्षण, रक्त जांच, एक्स-रे, आवश्यक दवाइयां तथा चिकित्सा उपचार करने की अनुमति देता/देती हूँ।</i>
    </div>

    <div style="margin-bottom:8px;">
        <b>2. Emergency Procedures / आपातकालीन स्थिति में उपचार:</b><br>
        I understand that during hospitalization, medical emergencies may arise requiring immediate life-saving interventions (such as CPR, endotracheal intubation, defibrillation, or emergency medications). I authorize the clinical team to perform such emergent interventions.<br>
        <i>मैं समझता/समझती हूँ कि अस्पताल में भर्ती के दौरान आपातकालीन स्थिति उत्पन्न होने पर आवश्यक जीवनरक्षक उपचार करने का अधिकार डॉक्टरों को होगा।</i>
    </div>

    <div style="margin-bottom:8px;">
        <b>3. Valuables & Hospital Rules / कीमती सामान व अस्पताल के नियम:</b><br>
        The hospital will not be responsible for loss or damage of money, jewelry, mobile phones, or personal belongings kept by patient or attendant in ward rooms.<br>
        <i>रोगी अथवा तीमारदार के पास रखे कीमती सामान (रुपये, गहने आदि) की सुरक्षा की जिम्मेदारी स्वयं की होगी।</i>
    </div>

    <table style="width:100%; margin-top:25px; font-size:10px; border-collapse:collapse;" border="1" cellpadding="6">
        <tr>
            <td style="width:50%;">
                <b>Patient / Legal Guardian's Declaration:</b><br>
                Name / नाम: ___________________________<br>
                Relationship / सम्बंध: __________________<br>
                Signature / Thumb Impression / हस्ताक्षर:<br><br>
                Date / दिनांक: {{CURRENT_DATE}} Time / समय: ______
            </td>
            <td style="width:50%;">
                <b>Healthcare Professional Witness:</b><br>
                Name / नाम: ___________________________<br>
                Designation / पद: ______________________<br>
                Signature / हस्ताक्षर:<br><br>
                Date / दिनांक: {{CURRENT_DATE}} Time / समय: ______
            </td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #5: Initial Nursing & Clinical Admission Assessment
            // =================================================================
            [
                'form_no'             => 5,
                'template_name'       => 'Initial Nursing & Clinical Admission Assessment',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10.5px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #0f172a; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">INITIAL NURSING & MEDICAL ADMISSION ASSESSMENT (NABH)</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f8fafc;">
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Bed / Room:</b> {{WARD_BED}}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f1f5f9;"><th colspan="6" style="text-align:left;">1. BASELINE VITAL SIGNS AT ADMISSION</th></tr>
        <tr>
            <td><b>Temp:</b> ___ °F</td>
            <td><b>Pulse:</b> ___ /min</td>
            <td><b>BP:</b> ___ / ___ mmHg</td>
            <td><b>Resp Rate:</b> ___ /min</td>
            <td><b>SpO2:</b> ___ % (RA/O2)</td>
            <td><b>GRBS:</b> ___ mg/dL</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f1f5f9;"><th colspan="2" style="text-align:left;">2. NURSING CLINICAL ASSESSMENT</th></tr>
        <tr>
            <td style="width:50%;">
                <b>Consciousness:</b> [ ] Alert & Oriented [ ] Drowsy [ ] Unconscious<br>
                <b>Mobility / Gait:</b> [ ] Ambulatory [ ] Assisted [ ] Bedridden<br>
                <b>Vision / Hearing:</b> [ ] Normal [ ] Impaired<br>
                <b>Bowel / Bladder:</b> [ ] Normal [ ] Incontinent [ ] Foley Catheterized
            </td>
            <td style="width:50%;">
                <b>Pain Assessment (0-10 Scale):</b> Score: ___ / 10<br>
                <b>Pain Site:</b> ___________________ Type: Dull / Sharp / Colicky<br>
                <b>Skin Condition:</b> [ ] Intact [ ] Pallor [ ] Cyanosis [ ] Jaundice<br>
                <b>Pressure Ulcer (Bedsore):</b> [ ] None [ ] Stage I/II/III/IV (Site: ___)
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f1f5f9;"><th colspan="2" style="text-align:left;">3. SAFETY & RISK ASSESSMENTS</th></tr>
        <tr>
            <td style="width:50%;">
                <b>Fall Risk (Morse Fall Scale):</b><br>
                [ ] Low Risk (0-24) [ ] Moderate (25-44) [ ] High Risk (>= 45)<br>
                Side rails up: Yes [ ] Call bell within reach: Yes [ ]
            </td>
            <td style="width:50%;">
                <b>Nutritional Screening:</b><br>
                Weight: ___ kg | Height: ___ cm | BMI: ___ kg/m2<br>
                Diet: [ ] Regular [ ] Diabetic [ ] Renal [ ] Liquid / NPO
            </td>
        </tr>
    </table>

    <table style="width:100%; margin-top:25px; font-size:10px;">
        <tr>
            <td style="width:50%;">Assessed by (Staff Nurse): __________________ Sign: ___________ Date/Time: _________</td>
            <td style="width:50%; text-align:right;">Counter-signed by (Doctor): __________________ Sign: ___________</td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #6: Medication Administration Record (MAR / Nurse Drug Chart)
            // =================================================================
            [
                'form_no'             => 6,
                'template_name'       => 'Medication Administration Record (MAR / 24-Hour Nurse Drug Chart)',
                'page_size'           => 'A4-L',
                'custom_width_mm'     => 297,
                'custom_height_mm'    => 210,
                'page_margin_top_cm'  => 0.70,
                'page_margin_bottom_cm'=> 0.70,
                'page_margin_left_cm' => 0.70,
                'page_margin_right_cm'=> 0.70,
                'margin_header_cm'    => 0.40,
                'margin_footer_cm'    => 0.40,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px;">
    <table style="width:100%; border-bottom:1.5px solid #0f172a; margin-bottom:6px; padding-bottom:4px;">
        <tr>
            <td style="width:70%;">
                <h3 style="margin:0; font-size:15px;">{{H_Name}} - MEDICATION ADMINISTRATION RECORD (MAR)</h3>
                <b>Patient:</b> {{PATIENT_NAME}} | <b>UHID:</b> {{UHID}} | <b>IPD No:</b> {{IPD_CODE}} | <b>Bed:</b> {{WARD_BED}} | <b>Age/Sex:</b> {{AGE_GENDER}}
            </td>
            <td style="width:30%; text-align:right; color:#dc2626; font-weight:bold;">
                ALLERGIES: [ ] NONE KNOWN [ ] YES: _________________
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="3">
        <tr style="background:#e2e8f0; text-align:center;">
            <th style="width:3%;">S.No</th>
            <th style="width:20%;">Medication Name & Strength</th>
            <th style="width:6%;">Dose</th>
            <th style="width:6%;">Route</th>
            <th style="width:6%;">Freq</th>
            <th style="width:6%;">Start Date</th>
            <th style="width:7%;">06:00 AM</th>
            <th style="width:7%;">10:00 AM</th>
            <th style="width:7%;">02:00 PM</th>
            <th style="width:7%;">06:00 PM</th>
            <th style="width:7%;">10:00 PM</th>
            <th style="width:7%;">02:00 AM</th>
            <th style="width:11%;">Doctor Signature</th>
        </tr>
        <tr><td>1</td><td>Inj. Ceftriaxone 1g</td><td>1 g</td><td>IV</td><td>BD</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>2</td><td>Inj. Pantoprazole 40mg</td><td>40 mg</td><td>IV</td><td>OD</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>3</td><td>Inj. Paracetamol 100ml IV</td><td>1 g</td><td>IV</td><td>SOS</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>4</td><td>Inj. Ondansetron 4mg</td><td>4 mg</td><td>IV</td><td>TDS</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>5</td><td>IV Fluids: RL / DNS 500ml</td><td>500 ml</td><td>IV</td><td>@ 75ml/hr</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>6</td><td>Tab. Pantocid 40mg</td><td>1 Tab</td><td>Oral</td><td>OD (Empty stomach)</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>7</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>8</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    </table>
    <div style="font-size:9px; color:#475569;">* Nurse must sign with initials upon administration of each dose. Circle time if dose is omitted/refused with documented reason on reverse.</div>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #8: Physician Daily Progress Notes (SOAP Format) & Order Sheet
            // =================================================================
            [
                'form_no'             => 8,
                'template_name'       => 'Physician Daily Progress Notes (SOAP Format) & Doctor Order Sheet',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:11px; line-height:1.4;">
    <table style="width:100%; border-bottom:2px solid #0f172a; margin-bottom:8px; padding-bottom:4px;">
        <tr>
            <td style="width:60%;">
                <h3 style="margin:0; font-size:16px;">{{H_Name}}</h3>
                <div style="font-size:11px; font-weight:bold;">DOCTOR'S DAILY PROGRESS NOTES & ORDER SHEET</div>
            </td>
            <td style="width:40%; text-align:right; font-size:10px;">
                <b>Patient:</b> {{PATIENT_NAME}} ({{AGE_GENDER}})<br>
                <b>UHID:</b> {{UHID}} | <b>IPD No:</b> {{IPD_CODE}}<br>
                <b>Bed:</b> {{WARD_BED}} | <b>Doctor:</b> {{DOCTORS}}
            </td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="5">
        <tr style="background:#f1f5f9; text-align:center;">
            <th style="width:14%;">Date & Time</th>
            <th style="width:46%;">CLINICAL PROGRESS NOTES (S-O-A-P)</th>
            <th style="width:40%;">DOCTOR'S ORDERS / TREATMENT PLAN</th>
        </tr>
        <tr style="height:140px; vertical-align:top;">
            <td><b>Date:</b> ___/___/2026<br><b>Time:</b> ___:___ AM/PM</td>
            <td>
                <b>S (Subjective):</b> Patient complaints / symptoms...<br>
                <b>O (Objective):</b> Vitals: BP ___/___, P ___, T ___, SpO2 ___%<br>
                Systemic exam: CVS / RS / PA / CNS...<br>
                <b>A (Assessment):</b> Clinical impression / status...<br>
                <b>P (Plan):</b> Investigations / consultation...
            </td>
            <td>
                1. Diet: ___________________________<br>
                2. IV Fluids: ______________________<br>
                3. Medications: ____________________<br>
                4. Investigations: __________________<br>
                5. Nursing monitoring: ______________<br><br>
                <b>Doctor Signature:</b> ________________
            </td>
        </tr>
        <tr style="height:140px; vertical-align:top;">
            <td><b>Date:</b> ___/___/2026<br><b>Time:</b> ___:___ AM/PM</td>
            <td>
                <b>S:</b><br><br>
                <b>O:</b><br><br>
                <b>A:</b><br><br>
                <b>P:</b>
            </td>
            <td>
                1.<br>2.<br>3.<br>4.<br><br>
                <b>Doctor Signature:</b> ________________
            </td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #12: High Risk Surgical & Anaesthesia Consent (Bilingual)
            // =================================================================
            [
                'form_no'             => 12,
                'template_name'       => 'High-Risk Surgical & Anaesthesia Informed Consent (Bilingual)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #b91c1c; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px; color:#b91c1c;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">INFORMED CONSENT FOR SURGERY, ANAESTHESIA & SPECIAL PROCEDURES</div>
        <div style="font-size:10px; color:#475569;">शल्य चिकित्सा (ऑपरेशन) एवं बेहोशी (एनेस्थीसिया) विशिष्ट सहमति प्रपत्र</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#fef2f2;">
            <td><b>Patient:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
        </tr>
        <tr>
            <td colspan="2"><b>Proposed Procedure:</b> {{PROCEDURE_NAME}}</td>
            <td colspan="2"><b>Operating Surgeon:</b> {{DOCTORS}}</td>
        </tr>
        <tr>
            <td colspan="2"><b>Proposed Anaesthesia:</b> [ ] General [ ] Spinal [ ] Epidural [ ] Local/Block</td>
            <td colspan="2"><b>Anaesthetist:</b> ___________________________</td>
        </tr>
    </table>

    <div style="margin-bottom:6px;">
        <b>1. Nature & Purpose of Surgery / ऑपरेशन का विवरण एवं उद्देश्य:</b><br>
        The operating surgeon has explained in understandable language the nature, anticipated benefits, alternative treatment options, and inherent risks associated with the procedure.<br>
        <i>ऑपरेशन के उद्देश्य, लाभ, विकल्प एवं उससे जुड़े सम्भावित जोखिमों के बारे में डॉक्टर द्वारा मुझे सरल भाषा में पूरी तरह समझा दिया गया है।</i>
    </div>

    <div style="margin-bottom:6px;">
        <b>2. Surgical & Anaesthetic Risks / सम्भावित जोखिम व जटिलताएं:</b><br>
        I understand that surgery and anaesthesia involve unforeseen complications including but not limited to hemorrhage, wound infection, adverse drug reaction, deep vein thrombosis, cardiovascular collapse, and respiratory depression.<br>
        <i>मैं समझता/समझती हूँ कि किसी भी सर्जरी व एनेस्थीसिया में रक्तस्राव, संक्रमण, दवाओं का प्रतिकूल प्रभाव अथवा अन्य जटिलताओं का जोखिम हो सकता है।</i>
    </div>

    <div style="margin-bottom:6px;">
        <b>3. Blood Transfusion Consent / रक्त चढ़ाने की सहमति:</b><br>
        I consent to the administration of blood or blood products if deemed life-saving by the operating surgeon/anaesthetist.<br>
        <i>आवश्यकता पड़ने पर रक्त अथवा रक्त घटक चढ़ाने की भी सहमति देता/देती हूँ।</i>
    </div>

    <table style="width:100%; margin-top:20px; font-size:9.5px; border-collapse:collapse;" border="1" cellpadding="5">
        <tr>
            <td style="width:50%;">
                <b>Patient / Attendant Signature / हस्ताक्षर:</b><br><br>
                Name: ___________________________<br>
                Relation with patient: ____________<br>
                Contact: ________________________<br>
                Date & Time: {{CURRENT_DATE}}
            </td>
            <td style="width:50%;">
                <b>Surgeon / Anaesthetist Certification:</b><br>
                I confirm that I have explained the procedure, risks, benefits, and alternatives to the patient/guardian.<br><br>
                Doctor Name: _____________________<br>
                Signature: _______________________<br>
                Date & Time: {{CURRENT_DATE}}
            </td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #13: WHO Surgical Safety Checklist
            // =================================================================
            [
                'form_no'             => 13,
                'template_name'       => 'WHO Surgical Safety Checklist (OT Safety Protocol)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.70,
                'page_margin_bottom_cm'=> 0.70,
                'page_margin_left_cm' => 0.70,
                'page_margin_right_cm'=> 0.70,
                'margin_header_cm'    => 0.40,
                'margin_footer_cm'    => 0.40,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:9.5px; line-height:1.35;">
    <div style="text-align:center; border-bottom:2px solid #047857; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:15px; color:#047857;">{{H_Name}} - OPERATION THEATRE</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">WHO SURGICAL SAFETY CHECKLIST (NABH SAFE SURGERY PROTOCOL)</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="3">
        <tr>
            <td><b>Patient:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD:</b> {{IPD_CODE}}</td>
            <td><b>OT Room:</b> OT-___</td>
            <td><b>Planned Procedure:</b> {{PROCEDURE_NAME}}</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:9px;" border="1" cellpadding="5">
        <tr style="background:#e2e8f0; text-align:center;">
            <th style="width:33%;">1. SIGN IN<br>(Before Induction of Anaesthesia)</th>
            <th style="width:34%;">2. TIME OUT<br>(Before Skin Incision)</th>
            <th style="width:33%;">3. SIGN OUT<br>(Before Patient Leaves OT)</th>
        </tr>
        <tr style="vertical-align:top;">
            <td>
                [ ] Patient identity, surgical site & procedure confirmed.<br>
                [ ] Surgical site marked: Yes [ ] N/A [ ]<br>
                [ ] Anaesthesia safety check completed.<br>
                [ ] Pulse oximeter on patient & functioning.<br><br>
                <b>Allergy check:</b><br>
                [ ] Known allergy: No [ ] Yes [ ]<br><br>
                <b>Difficult Airway / Aspiration Risk:</b><br>
                [ ] No [ ] Yes, equipment available<br><br>
                <b>Risk of > 500 mL Blood Loss:</b><br>
                [ ] No [ ] Yes, IV access & blood ready.<br><br>
                <i>Nurse/Anaesthetist Sign: _________</i>
            </td>
            <td>
                [ ] All team members introduced by name & role.<br>
                [ ] Surgeon, Anaesthetist & Nurse verbally confirm:<br>
                &nbsp;&nbsp; - Patient name<br>
                &nbsp;&nbsp; - Procedure<br>
                &nbsp;&nbsp; - Incision site<br><br>
                <b>Anticipated Critical Events:</b><br>
                - Surgeon: Critical steps, duration, blood loss.<br>
                - Anaesthetist: Patient-specific concerns.<br>
                - Nursing team: Sterility indicators confirmed.<br><br>
                <b>Antibiotic Prophylaxis:</b><br>
                [ ] Administered within past 60 min.<br><br>
                <b>Essential Imaging Displayed:</b><br>
                [ ] Yes [ ] N/A<br><br>
                <i>Team Leader Sign: ____________</i>
            </td>
            <td>
                <b>Nurse Verbally Confirms with Team:</b><br>
                [ ] Name of the procedure recorded.<br>
                [ ] Instrument, sponge & needle counts are correct.<br>
                [ ] Specimen labeled correctly with patient name & UHID.<br>
                [ ] Equipment problems addressed.<br><br>
                <b>Key Concerns for Recovery:</b><br>
                - Post-op vitals & airway plan reviewed.<br>
                - Drain / pack details recorded.<br>
                - Analgesia order written.<br><br><br>
                <i>Circulating Nurse Sign: _________<br>
                Surgeon Sign: __________________</i>
            </td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #14: Diabetic Blood Sugar & Insulin Sliding Scale Chart
            // =================================================================
            [
                'form_no'             => 14,
                'template_name'       => 'Diabetic Blood Glucose & Insulin Sliding Scale Monitoring Flowsheet',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:1.5px solid #0f172a; padding-bottom:4px; margin-bottom:8px;">
        <h3 style="margin:0; font-size:15px;">{{H_Name}} - INPATIENT DIABETES MONITORING</h3>
        <div style="font-size:11px; font-weight:bold;">BLOOD GLUCOSE & INSULIN SLIDING SCALE FLOWSHEET</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="3">
        <tr>
            <td><b>Patient:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD:</b> {{IPD_CODE}}</td>
            <td><b>Bed:</b> {{WARD_BED}}</td>
            <td><b>Target Glucose:</b> 110 - 180 mg/dL</td>
        </tr>
    </table>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9px;" border="1" cellpadding="3">
        <tr style="background:#f1f5f9;"><th colspan="3" style="text-align:left;">PHYSICIAN INSULIN SLIDING SCALE PROTOCOL (Regular / Rapid Acting Insulin SC)</th></tr>
        <tr><td>Blood Sugar < 70 mg/dL</td><td>Hypoglycemia Protocol: Give 15-20g oral glucose / 25% Dextrose 100ml IV stat; recheck in 15 min.</td></tr>
        <tr><td>Blood Sugar 70 - 140 mg/dL</td><td>No insulin correction required.</td></tr>
        <tr><td>Blood Sugar 141 - 180 mg/dL</td><td>Give 2 Units Regular Insulin SC.</td></tr>
        <tr><td>Blood Sugar 181 - 220 mg/dL</td><td>Give 4 Units Regular Insulin SC.</td></tr>
        <tr><td>Blood Sugar 221 - 260 mg/dL</td><td>Give 6 Units Regular Insulin SC.</td></tr>
        <tr><td>Blood Sugar 261 - 300 mg/dL</td><td>Give 8 Units Regular Insulin SC.</td></tr>
        <tr><td>Blood Sugar > 300 mg/dL</td><td>Give 10 Units Regular Insulin SC and Notify Treating Doctor immediately.</td></tr>
    </table>

    <table style="width:100%; border-collapse:collapse; font-size:9px;" border="1" cellpadding="3">
        <tr style="background:#e2e8f0; text-align:center;">
            <th style="width:12%;">Date</th>
            <th style="width:10%;">Time</th>
            <th style="width:18%;">Timing (Fasting/Pre-meal/Post)</th>
            <th style="width:15%;">GRBS (mg/dL)</th>
            <th style="width:15%;">Insulin Units Given</th>
            <th style="width:15%;">Nurse Initial</th>
            <th style="width:15%;">Remarks</th>
        </tr>
        <tr><td></td><td>07:00 AM</td><td>Fasting</td><td></td><td></td><td></td><td></td></tr>
        <tr><td></td><td>12:30 PM</td><td>Pre-Lunch</td><td></td><td></td><td></td><td></td></tr>
        <tr><td></td><td>07:30 PM</td><td>Pre-Dinner</td><td></td><td></td><td></td><td></td></tr>
        <tr><td></td><td>11:00 PM</td><td>Bedtime / Post-meal</td><td></td><td></td><td></td><td></td></tr>
        <tr><td></td><td>03:00 AM</td><td>Night check</td><td></td><td></td><td></td><td></td></tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #15: Blood Transfusion Monitoring Record
            // =================================================================
            [
                'form_no'             => 15,
                'template_name'       => 'Blood & Blood Component Transfusion Monitoring Record',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #b91c1c; padding-bottom:4px; margin-bottom:8px;">
        <h3 style="margin:0; font-size:15px; color:#b91c1c;">{{H_Name}} - TRANSFUSION SAFETY RECORD</h3>
        <div style="font-size:11px; font-weight:bold;">BLOOD & BLOOD COMPONENT TRANSFUSION MONITORING SHEET</div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="3">
        <tr>
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD:</b> {{IPD_CODE}}</td>
            <td><b>Ward/Bed:</b> {{WARD_BED}}</td>
        </tr>
        <tr style="background:#fef2f2;">
            <td><b>Patient Blood Group:</b> ____ Rh: ____</td>
            <td><b>Bag Blood Group:</b> ____ Rh: ____</td>
            <td><b>Bag / Unit No:</b> _______________</td>
            <td><b>Component:</b> PRBC / FFP / Platelet / Cryo</td>
        </tr>
    </table>

    <div style="background:#f8fafc; padding:6px; border:1px solid #cbd5e1; margin-bottom:8px; font-size:9px;">
        <b>PRE-TRANSFUSION DOUBLE-CHECK (By 2 Qualified Healthcare Professionals at Bedside):</b><br>
        [ ] Patient Name & UHID verified against wristband.<br>
        [ ] Blood group & Rh compatibility confirmed on crossmatch tag.<br>
        [ ] Bag expiry date, physical appearance, and seal checked.<br>
        Staff 1 Name & Sign: ________________________ Staff 2 Name & Sign: ________________________
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:9px;" border="1" cellpadding="3">
        <tr style="background:#e2e8f0; text-align:center;">
            <th>Monitoring Interval</th>
            <th>Time</th>
            <th>Temp (°F)</th>
            <th>Pulse (/min)</th>
            <th>BP (mmHg)</th>
            <th>Resp (/min)</th>
            <th>SpO2 (%)</th>
            <th>Nurse Initial</th>
        </tr>
        <tr><td>Pre-Transfusion Baseline</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>15 Minutes Post Start</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>30 Minutes Post Start</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>60 Minutes Post Start</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>Every 1 Hour Until End</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        <tr><td>Post-Transfusion End</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    </table>
    <div style="font-size:9px; color:#dc2626; margin-top:6px;">
        <b>ADVERSE REACTION WARNING:</b> If fever, chills, rigors, rash, tachycardia, dyspnea, or loin pain occurs: STOP TRANSFUSION IMMEDIATELY, keep IV line open with 0.9% Normal Saline, notify Doctor and Blood Bank.
    </div>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #2: Initial Medical Assessment & Clinical History
            // =================================================================
            [
                'form_no'             => 2,
                'template_name'       => 'Initial Medical Assessment & Clinical History (Doctor Admission Note)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #1e3a8a; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px; color:#1e3a8a;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">INITIAL MEDICAL ASSESSMENT & CLINICAL ADMISSION RECORD (NABH)</div>
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f1f5f9;">
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
        </tr>
        <tr>
            <td><b>Admit Date & Time:</b> {{ADMIT_DATE}}</td>
            <td><b>Ward / Bed:</b> {{WARD_BED}}</td>
            <td colspan="2"><b>Admitting Consultant:</b> {{DOCTORS}}</td>
        </tr>
    </table>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="4">
        <tr><td style="width:25%; background:#f8fafc;"><b>CHIEF COMPLAINTS:</b></td><td style="width:75%;">[Presenting symptoms with duration]</td></tr>
        <tr><td style="background:#f8fafc;"><b>HISTORY OF PRESENT ILLNESS:</b></td><td>[Detailed chronological narrative of symptoms, progression, and treatment taken prior to admission]</td></tr>
        <tr><td style="background:#f8fafc;"><b>PAST MEDICAL / SURGICAL HISTORY:</b></td><td>[ ] HTN [ ] DM [ ] CAD [ ] Asthma/COPD [ ] CKD [ ] Previous Surgeries: [Details]</td></tr>
        <tr><td style="background:#f8fafc;"><b>ALLERGIES:</b></td><td>[ ] No Known Drug Allergies (NKDA) [ ] Known Allergy: ________________________________</td></tr>
        <tr><td style="background:#f8fafc;"><b>GENERAL PHYSICAL EXAMINATION:</b></td><td>Temp: ____ °F | Pulse: ____ /min | BP: ____/____ mmHg | RR: ____ /min | SpO2: ____ % on RA<br>Pallor: [ ] Icterus: [ ] Cyanosis: [ ] Clubbing: [ ] Edema: [ ] Lymphadenopathy: [ ]</td></tr>
        <tr><td style="background:#f8fafc;"><b>SYSTEMIC EXAMINATION:</b></td><td><b>CVS:</b> S1S2 heard, no murmurs.<br><b>RS:</b> Bilateral vesicular breath sounds, no added sounds.<br><b>P/A:</b> Soft, non-tender, no organomegaly.<br><b>CNS:</b> Conscious, oriented, no focal deficit. GCS: 15/15.</td></tr>
        <tr><td style="background:#f8fafc;"><b>PROVISIONAL DIAGNOSIS:</b></td><td><b>1.</b> ________________________________________________________________<br><b>2.</b> ________________________________________________________________</td></tr>
        <tr><td style="background:#f8fafc;"><b>INITIAL PLAN OF CARE:</b></td><td>[Investigations ordered, IV fluids, medications, dietary instructions, monitoring frequency]</td></tr>
    </table>
    <div style="margin-top:20px; text-align:right;">
        <b>Doctor Name & Reg. No:</b> ___________________________ <b>Sign:</b> ______________
    </div>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #4: Pre-Anesthesia Checkup (PAC) Record
            // =================================================================
            [
                'form_no'             => 4,
                'template_name'       => 'Pre-Anesthesia Checkup (PAC) Evaluation & Clearance Record',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #0891b2; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px; color:#0891b2;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">PRE-ANESTHESIA EVALUATION & CLEARANCE RECORD (PAC)</div>
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#ecfeff;">
            <td><b>Patient:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
        </tr>
        <tr>
            <td colspan="2"><b>Proposed Surgery:</b> {{PROCEDURE_NAME}}</td>
            <td colspan="2"><b>Operating Surgeon:</b> {{DOCTORS}}</td>
        </tr>
    </table>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9px;" border="1" cellpadding="3">
        <tr style="background:#f8fafc;"><th colspan="4" style="text-align:left;">AIRWAY ASSESSMENT:</th></tr>
        <tr>
            <td>Mallampati Class: [ ] I [ ] II [ ] III [ ] IV</td>
            <td>Mouth Opening: ____ cm (>3.5 cm)</td>
            <td>Thyromental Distance: ____ cm (>6 cm)</td>
            <td>Neck Mobility: [ ] Full [ ] Restricted</td>
        </tr>
        <tr>
            <td>Teeth: [ ] Intact [ ] Loose [ ] Dentures</td>
            <td colspan="3">Anticipated Difficult Airway: [ ] No [ ] Yes (Preparation: Video laryngoscope/FOB)</td>
        </tr>
        <tr style="background:#f8fafc;"><th colspan="4" style="text-align:left;">SYSTEMIC COMORBIDITIES & INVESTIGATIONS:</th></tr>
        <tr>
            <td colspan="2">Hb: ____ g/dL | Platelets: ____ /cumm | Blood Group: ____</td>
            <td colspan="2">PT/INR: ____ / ____ | Blood Urea: ____ | S. Creatinine: ____</td>
        </tr>
        <tr>
            <td colspan="2">ECG Findings: ________________________________</td>
            <td colspan="2">Chest X-Ray / Echo: ________________________________</td>
        </tr>
        <tr style="background:#f8fafc;"><th colspan="4" style="text-align:left;">ASA PHYSICAL STATUS CLASSIFICATION:</th></tr>
        <tr>
            <td colspan="4">
                [ ] <b>ASA I:</b> Normal healthy patient<br>
                [ ] <b>ASA II:</b> Patient with mild systemic disease (controlled HTN/DM)<br>
                [ ] <b>ASA III:</b> Patient with severe systemic disease (stable angina, COPD)<br>
                [ ] <b>ASA IV:</b> Severe systemic disease that is a constant threat to life<br>
                [ ] <b>ASA E:</b> Emergency surgery modifier
            </td>
        </tr>
        <tr style="background:#ecfeff;"><th colspan="4" style="text-align:left;">PRE-OPERATIVE ANESTHETIC PLAN & INSTRUCTIONS:</th></tr>
        <tr>
            <td colspan="4">
                Proposed Anesthesia: [ ] GA [ ] Spinal [ ] Epidural [ ] Regional Nerve Block [ ] MAC/Sedation<br>
                <b>NPO Orders:</b> Solids: 6 hours prior | Clear fluids: 2 hours prior.<br>
                <b>Premedication / Medications to continue on morning of surgery:</b> ___________________________<br>
                <b>PAC STATUS:</b> [ ] FIT FOR SURGERY UNDER PROPOSED ANESTHESIA [ ] HIGH RISK [ ] PENDING CLEARANCE
            </td>
        </tr>
    </table>
    <div style="margin-top:15px; text-align:right;">
        <b>Consultant Anesthesiologist Name & Sign:</b> ___________________________
    </div>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #16: LAMA / DAMA Declaration Form (Bilingual)
            // =================================================================
            [
                'form_no'             => 16,
                'template_name'       => 'Discharge Against Medical Advice (LAMA / DAMA) Form (Bilingual)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #b91c1c; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px; color:#b91c1c;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">LEAVING / DISCHARGE AGAINST MEDICAL ADVICE (LAMA / DAMA) DECLARATION</div>
        <div style="font-size:10px; color:#475569;">चिकित्सीय परामर्श के विरुद्ध अस्पताल छोड़ने (LAMA) का घोषणा पत्र</div>
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#fef2f2;">
            <td><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
        </tr>
        <tr>
            <td><b>Admission Date:</b> {{ADMIT_DATE}}</td>
            <td><b>LAMA Date & Time:</b> ____________________</td>
            <td colspan="2"><b>Treating Doctor:</b> {{DOCTORS}}</td>
        </tr>
    </table>
    <div style="border:1px solid #ef4444; padding:8px; background:#fff5f5; margin-bottom:10px; font-size:9.5px;">
        <p><b>ENGLISH DECLARATION:</b><br>
        I, the undersigned patient / legal guardian / attendant, hereby declare that I am demanding discharge from {{H_Name}} against the explicit medical advice of the treating doctors. The attending physician has thoroughly explained to me the clinical condition, the ongoing treatment requirements, and the severe medical risks involved in premature discharge—including the possibility of worsening disease, permanent disability, organ failure, or death. I take full responsibility for any consequences arising from this decision and discharge the hospital and treating medical team of all clinical and legal liabilities.</p>
        <p style="margin-top:8px;"><b>हिन्दी घोषणा (HINDI DECLARATION):</b><br>
        मैं, अधोहस्ताक्षरी मरीज / अभिभावक, यह घोषित करता/करती हूँ कि मैं अपनी इच्छा से मरीज को डॉक्टरी सलाह के विरुद्ध (LAMA) ले जा रहा/रही हूँ। मुझे डॉक्टरों द्वारा मरीज की गंभीर बीमारी, आवश्यक इलाज तथा अस्पताल छोड़ने के दुष्परिणामों (जैसे कि बीमारी का अत्यधिक बिगड़ना, अपंगता या मृत्यु का खतरा) के बारे में भली-भांति समझा दिया गया है। मरीज के स्वास्थ्य पर पड़ने वाले किसी भी प्रतिकूल प्रभाव की पूरी जिम्मेदारी मेरी स्वयं की होगी, जिसके लिए अस्पताल या डॉक्टर जिम्मेदार नहीं होंगे।</p>
    </div>
    <table style="width:100%; margin-top:20px; font-size:10px;">
        <tr>
            <td style="width:50%;">
                <b>Signature / Thumb Impression of Patient / Attendant:</b><br><br>
                Name: ____________________________________<br>
                Relationship with Patient: ___________________<br>
                Phone / Address: ___________________________
            </td>
            <td style="width:50%; text-align:right;">
                <b>Treating Doctor / Resident Sign:</b> ___________________<br><br>
                <b>Witness 1 (Staff):</b> _______________________________<br><br>
                <b>Witness 2 (Attendant):</b> ___________________________
            </td>
        </tr>
    </table>
</div>
HTML
                ,
                'status'              => 1,
            ],

            // =================================================================
            // Form #19: Clinical Mortality & Death Summary Record
            // =================================================================
            [
                'form_no'             => 19,
                'template_name'       => 'Clinical Mortality & Death Summary Record (Form 4/4A Standards)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'template_html'       => <<<HTML
<div style="font-family: Arial, sans-serif; font-size:10px; line-height:1.4;">
    <div style="text-align:center; border-bottom:2px solid #0f172a; padding-bottom:4px; margin-bottom:8px;">
        <h2 style="margin:0; font-size:16px; color:#0f172a;">{{H_Name}}</h2>
        <div style="font-size:11px; font-weight:bold; text-transform:uppercase;">CLINICAL DEATH SUMMARY & MEDICAL CERTIFICATE OF CAUSE OF DEATH</div>
    </div>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:10px;" border="1" cellpadding="4">
        <tr style="background:#f1f5f9;">
            <td><b>Deceased Patient:</b> {{PATIENT_NAME}}</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>IPD No:</b> {{IPD_CODE}}</td>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
        </tr>
        <tr>
            <td><b>Admit Date & Time:</b> {{ADMIT_DATE}}</td>
            <td><b>Date & Time of Death:</b> [DEATH_DATETIME]</td>
            <td colspan="2"><b>Attending Doctor:</b> {{DOCTORS}}</td>
        </tr>
    </table>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px; font-size:9.5px;" border="1" cellpadding="4">
        <tr style="background:#f8fafc;"><th colspan="2" style="text-align:left;">CAUSE OF DEATH (WHO Standard / Form 4/4A Format):</th></tr>
        <tr>
            <td style="width:30%; background:#f8fafc;"><b>I. Immediate Cause of Death (a):</b></td>
            <td style="width:70%;">[Due to or as a consequence of] ________________________________</td>
        </tr>
        <tr>
            <td style="background:#f8fafc;"><b>Antecedent Cause (b):</b></td>
            <td>[Due to or as a consequence of] ________________________________</td>
        </tr>
        <tr>
            <td style="background:#f8fafc;"><b>Underlying Cause (c):</b></td>
            <td>________________________________________________________________</td>
        </tr>
        <tr>
            <td style="background:#f8fafc;"><b>II. Other Significant Conditions:</b></td>
            <td>[Contributing to death but not related to disease causing it] ________________</td>
        </tr>
        <tr style="background:#f8fafc;"><th colspan="2" style="text-align:left;">RESUSCITATION (CPR) DETAILS:</th></tr>
        <tr>
            <td colspan="2">
                Cardiac arrest rhythm: [ ] Asystole [ ] VF/VT [ ] PEA.<br>
                CPR initiated at: ____:____ hrs. Duration of CPR: ____ minutes.<br>
                DC Shock delivered: [ ] Yes ([NUMBER] shocks) [ ] No.<br>
                Intubation: [ ] Yes [ ] Already intubated. Emergency drugs administered (Adrenaline, Atropine, Bicarbonate): Yes.<br>
                Despite full ACLS resuscitation measures, spontaneous circulation could not be restored. Pupils fixed and dilated, bilateral absent breath sounds, flat-line asystole on continuous ECG monitor.<br>
                <b>Patient declared clinically dead at: ____:____ hrs on [DATE].</b>
            </td>
        </tr>
        <tr style="background:#f8fafc;"><th colspan="2" style="text-align:left;">MEDICO-LEGAL STATUS:</th></tr>
        <tr>
            <td colspan="2">
                [ ] Non-MLC Case (Natural death) - Body handed over to family.<br>
                [ ] Medico-Legal Case (MLC) - Local Police Station notified: ____________________. Body sent for Post-Mortem.
            </td>
        </tr>
    </table>
    <div style="margin-top:20px; text-align:right;">
        <b>Doctor Name & Medical Council Reg. No:</b> ___________________________ <b>Sign:</b> ______________
    </div>
</div>
HTML
                ,
                'status'              => 1,
            ],
        ];
    }
}

