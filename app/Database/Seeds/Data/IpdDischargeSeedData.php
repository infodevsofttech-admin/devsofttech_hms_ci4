<?php

namespace App\Database\Seeds\Data;

/**
 * Standard IPD Discharge Templates & Master Clinical Narratives.
 */
class IpdDischargeSeedData
{
    /**
     * IPD Discharge Document Templates (`ipd_discharge_templates`)
     */
    public static function getTemplates(): array
    {
        return [
            [
                'template_name'       => 'General Medicine Discharge Summary (NABH Standard)',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #1e3a8a; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#1e3a8a; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">CLINICAL DISCHARGE SUMMARY (NABH)</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:33%;">Generated on: {{CURRENT_DATE}}</td>
        <td style="width:34%; text-align:center;">NABH Accredited Healthcare Facility</td>
        <td style="width:33%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<!-- PATIENT DEMOGRAPHICS -->
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#f8fafc;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Admission Date:</b> {{ADMIT_DATE}}</td>
        <td><b>Discharge Date:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Ward / Bed:</b> {{WARD_BED}}</td>
        <td><b>Consultant:</b> {{DOCTORS}}</td>
    </tr>
    <tr>
        <td colspan="4"><b>Final Diagnosis:</b> <span style="color:#b91c1c; font-weight:bold;">{{DIAGNOSIS}}</span></td>
    </tr>
</table>

<!-- MAIN CLINICAL CONTENT -->
<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<!-- DOCTOR SIGNATURE BLOCK -->
<table style="width:100%; margin-top:40px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:33%; text-align:center;">
            <br><br>
            _______________________<br>
            <b>Duty Medical Officer (RMO)</b>
        </td>
        <td style="width:34%; text-align:center;">
            <br><br>
            _______________________<br>
            <b>Treating Consultant</b>
        </td>
        <td style="width:33%; text-align:center;">
            <br><br>
            _______________________<br>
            <b>Patient / Attendant Signature</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'General Surgery & Operative Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #047857; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#047857; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">SURGICAL DISCHARGE & OPERATIVE SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:33%;">Printed: {{CURRENT_DATE}}</td>
        <td style="width:34%; text-align:center;">Emergency 24x7: {{H_phone_No}}</td>
        <td style="width:33%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#f0fdf4;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Date of Admission:</b> {{ADMIT_DATE}}</td>
        <td><b>Date of Surgery:</b> {{SURGERY_DATE}}</td>
        <td><b>Date of Discharge:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Surgeon / Anaesthetist:</b> {{DOCTORS}}</td>
    </tr>
    <tr>
        <td colspan="4"><b>Procedure Performed:</b> <span style="font-weight:bold; color:#065f46;">{{PROCEDURE_NAME}}</span></td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:40px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%; text-align:left;">
            <b>Wound Care & Suture Removal:</b> In 7-10 days at OPD Room.<br>
            <b>Emergency Red Flags:</b> High fever, wound soakage, severe pain, vomiting.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Operating Surgeon / Consultant</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'Obstetrics & Gynaecology / Delivery Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #be185d; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#be185d; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">MATERNITY & DELIVERY DISCHARGE SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:50%;">Printed: {{CURRENT_DATE}} | Pediatric Helpline: {{H_phone_No}}</td>
        <td style="width:50%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#fdf2f8;">
        <td style="width:25%;"><b>Mother Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Admit Date:</b> {{ADMIT_DATE}}</td>
        <td><b>Delivery Date & Time:</b> {{DELIVERY_DATETIME}}</td>
        <td><b>Discharge Date:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Obstetrician:</b> {{DOCTORS}}</td>
    </tr>
    <tr style="background:#fff1f2;">
        <td colspan="2"><b>Mode of Delivery:</b> {{DELIVERY_MODE}} (FTND / LSCS)</td>
        <td colspan="2"><b>Baby Details:</b> {{BABY_GENDER}}, Birth Wt: {{BIRTH_WEIGHT}}, APGAR: {{APGAR_SCORE}}</td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:35px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%;">
            <b>Neonatal Immunization Given:</b> BCG, OPV-0, Hepatitis B-Birth Dose.<br>
            <b>Lactation:</b> Exclusive Breastfeeding advised for 6 months.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Consultant Obstetrician & Gynaecologist</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'Day Care / Dialysis / Chemotherapy Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #0284c7; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#0284c7; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">DAY CARE PROCEDURAL / DIALYSIS SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:50%;">Printed: {{CURRENT_DATE}}</td>
        <td style="width:50%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#f0f9ff;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Session Date:</b> {{ADMIT_DATE}}</td>
        <td><b>Procedure:</b> Hemodialysis / Day Procedure</td>
        <td><b>Vascular Access:</b> AV Fistula / Dual Lumen Catheter</td>
        <td><b>Treating Nephrologist:</b> {{DOCTORS}}</td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:35px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%;">
            <b>Next Dialysis Schedule:</b> As per weekly plan.<br>
            <b>Dietary Restrictions:</b> Low potassium, restricted fluids (< 1 L/day), low sodium.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Consultant Nephrologist / Dialysis Physician</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'Critical Care & ICU Step-Down Discharge Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #b91c1c; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#b91c1c; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">INTENSIVE CARE UNIT (ICU) / STEP-DOWN DISCHARGE SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:50%;">Printed: {{CURRENT_DATE}} | 24x7 ICU Helpline: {{H_phone_No}}</td>
        <td style="width:50%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#fef2f2;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD / ICU No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>ICU Admission:</b> {{ADMIT_DATE}}</td>
        <td><b>Step-Down Date:</b> {{STEPDOWN_DATE}}</td>
        <td><b>Discharge Date:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Intensivist:</b> {{DOCTORS}}</td>
    </tr>
    <tr style="background:#fff5f5;">
        <td colspan="2"><b>Invasive Procedures in ICU:</b> CVC Line / Arterial Line / Intubation & Mechanical Ventilation / RRT</td>
        <td colspan="2"><b>Apache-II / SOFA Score on Admission:</b> [SOFA_SCORE]</td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:35px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%;">
            <b>Lines / Drains Removed Prior to Discharge:</b> All lines removed intact.<br>
            <b>Hemodynamic Status:</b> Stable on room air, off all inotropic/vasopressor support.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Consultant Critical Care Medicine (Intensivist)</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'Cardiology & Cath Lab Post-Procedure Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #0d9488; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#0d9488; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">CARDIOLOGY & CATH LAB INTERVENTION DISCHARGE SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:50%;">Printed: {{CURRENT_DATE}} | Cardiac Emergency: {{H_phone_No}}</td>
        <td style="width:50%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#f0fdfa;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Admit Date:</b> {{ADMIT_DATE}}</td>
        <td><b>CAG / PCI Date:</b> {{PROCEDURE_DATE}}</td>
        <td><b>Discharge Date:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Interventional Cardiologist:</b> {{DOCTORS}}</td>
    </tr>
    <tr style="background:#e6fffa;">
        <td colspan="2"><b>Procedure Performed:</b> Coronary Angiography (CAG) / Primary PCI with Drug-Eluting Stent (DES)</td>
        <td colspan="2"><b>Vascular Access Site:</b> Right Radial Artery / TR Band (Hemostasis secured)</td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:35px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%;">
            <b>Echocardiography at Discharge:</b> LVEF: [LVEF]%, Regional Wall Motion: [RWMA]<br>
            <b>CRITICAL:</b> Continue Dual Antiplatelet Therapy (DAPT) without interruption.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Consultant Interventional Cardiologist</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
            [
                'template_name'       => 'Orthopaedic & Joint Replacement Discharge Summary',
                'page_size'           => 'A4',
                'custom_width_mm'     => 210,
                'custom_height_mm'    => 297,
                'page_margin_top_cm'  => 0.80,
                'page_margin_bottom_cm'=> 0.80,
                'page_margin_left_cm' => 0.80,
                'page_margin_right_cm'=> 0.80,
                'margin_header_cm'    => 0.50,
                'margin_footer_cm'    => 0.50,
                'header_html'         => <<<HTML
<table style="width:100%; border-bottom:2px solid #4338ca; padding-bottom:8px; margin-bottom:10px;">
    <tr>
        <td style="width:15%; vertical-align:middle;">
            <img src="{{H_logo_abs}}" style="max-height:60px; max-width:120px;" alt="Hospital Logo">
        </td>
        <td style="width:85%; vertical-align:middle; text-align:center;">
            <h2 style="margin:0; color:#4338ca; font-size:20px; font-weight:bold;">{{H_Name}}</h2>
            <div style="font-size:11px; color:#475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
            <div style="font-size:13px; font-weight:bold; color:#0f172a; margin-top:4px; text-transform:uppercase; letter-spacing:1px;">ORTHOPAEDICS & ARTHROPLASTY DISCHARGE SUMMARY</div>
        </td>
    </tr>
</table>
HTML
                ,
                'footer_html'         => <<<HTML
<table style="width:100%; border-top:1px solid #cbd5e1; font-size:10px; color:#64748b; padding-top:6px;">
    <tr>
        <td style="width:50%;">Printed: {{CURRENT_DATE}} | Ortho Helpline: {{H_phone_No}}</td>
        <td style="width:50%; text-align:right;">Page {PAGENO} of {nbpg}</td>
    </tr>
</table>
HTML
                ,
                'template_html'       => <<<HTML
<table style="width:100%; border-collapse:collapse; margin-bottom:12px; font-size:11px;" border="1" cellpadding="5">
    <tr style="background:#eef2ff;">
        <td style="width:25%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
        <td style="width:25%;"><b>UHID:</b> {{UHID}}</td>
        <td style="width:25%;"><b>IPD No:</b> {{IPD_CODE}}</td>
        <td style="width:25%;"><b>Age / Gender:</b> {{AGE_GENDER}}</td>
    </tr>
    <tr>
        <td><b>Admit Date:</b> {{ADMIT_DATE}}</td>
        <td><b>Surgery Date:</b> {{SURGERY_DATE}}</td>
        <td><b>Discharge Date:</b> {{DISCHARGE_DATE}}</td>
        <td><b>Orthopaedic Surgeon:</b> {{DOCTORS}}</td>
    </tr>
    <tr style="background:#f5f7ff;">
        <td colspan="2"><b>Surgical Procedure:</b> Total Knee Arthroplasty (TKR) / Total Hip (THR) / Fracture ORIF</td>
        <td colspan="2"><b>Implant Details & Barcode:</b> [IMPLANT_DETAILS] (Recorded in register)</td>
    </tr>
</table>

<div style="font-size:11px; line-height:1.5;">
    {{CONTENT}}
</div>

<table style="width:100%; margin-top:35px; font-size:11px; page-break-inside:avoid;">
    <tr>
        <td style="width:50%;">
            <b>DVT Prophylaxis:</b> Continue LMWH / DOAC as prescribed.<br>
            <b>Mobilization Status:</b> Full / Partial weight bearing with walker.
        </td>
        <td style="width:50%; text-align:right;">
            <br><br>
            _______________________________<br>
            <b>Consultant Orthopaedic & Joint Replacement Surgeon</b>
        </td>
    </tr>
</table>
HTML
                ,
                'is_default'          => 0,
                'is_audit_only'       => 0,
                'is_abdm'             => 1,
                'status'              => 1,
            ],
        ];
    }

    /**
     * Master Clinical Narratives for `ipd_discharge_narrative_templates`
     */
    public static function getNarratives(): array
    {
        return [
            // =================================================================
            // DIAGNOSIS REMARKS (`diagnosis_remark`)
            // =================================================================
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Gastroenteritis with Moderate Dehydration',
                'template_text' => "Acute Gastroenteritis (Infective) with Moderate Dehydration and Mild Hypokalemia (ICD-10: A09 / E86.0).\nPresented with recurrent watery loose stools, vomiting, abdominal colic, and low-grade fever.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Type 2 Diabetes Mellitus with Severe Hyperglycemia',
                'template_text' => "Type 2 Diabetes Mellitus with Uncontrolled Hyperglycemia and Hyperosmolar State (without DKA) (ICD-10: E11.65).\nKnown diabetic for 8 years presenting with fatigue, polyuria, polydipsia, and blood glucose > 450 mg/dL.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Appendicitis - Post Laparoscopic Appendectomy',
                'template_text' => "Acute Phlegmonous Appendicitis (ICD-10: K35.80).\nStatus Post Emergency Laparoscopic Appendectomy performed under General Anaesthesia on [SURGERY_DATE].",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Calculous Cholecystitis - Post Lap Cholecystectomy',
                'template_text' => "Acute Calculous Cholecystitis with Symptomatic Cholelithiasis (ICD-10: K80.00).\nStatus Post Elective 4-Port Laparoscopic Cholecystectomy with cystic duct/artery clipping under GA.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Severe Dengue Fever with Thrombocytopenia',
                'template_text' => "Severe Dengue Fever with Warning Signs, Thrombocytopenia (Nadir Platelet count 28,000/cumm), and Mild Hepatosplenomegaly (ICD-10: A97.1).\nNS1 Antigen and IgM Positive.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Coronary Syndrome (NSTEMI) - Post PTCA Stenting',
                'template_text' => "Coronary Artery Disease - Acute Coronary Syndrome (Non-ST Elevation Myocardial Infarction - NSTEMI, Troponin-I Positive) (ICD-10: I21.4).\nStatus Post Coronary Angiography and Successful Percutaneous Transluminal Coronary Angioplasty (PTCA) with Drug-Eluting Stent (DES) to mid-LAD.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Community Acquired Pneumonia (CAP)',
                'template_text' => "Community Acquired Pneumonia (Right Middle & Lower Lobe Consolidation) with Type-I Respiratory Failure (ICD-10: J18.9).\nAdmitted with productive cough, high-grade fever with chills, pleuritic chest pain, and tachypnea.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Full Term Normal Vaginal Delivery (FTND)',
                'template_text' => "Full Term Pregnancy at 39 Weeks with Cephalic Presentation in Active Labor (ICD-10: O80).\nDelivered a healthy live male/female infant via Spontaneous Normal Vaginal Delivery with right mediolateral episiotomy under local anaesthesia.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Full Term LSCS for Cephalopelvic Disproportion',
                'template_text' => "Full Term Pregnancy at 38 Weeks 4 Days with Cephalopelvic Disproportion (CPD) and Failure to Progress in Labor (ICD-10: O65.4).\nDelivered via Emergency Lower Segment Caesarean Section (LSCS) under Spinal Anaesthesia.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Closed Fracture Shaft of Femur - Post ORIF',
                'template_text' => "Closed Comminuted Fracture Shaft of Right Femur (Winquist-Hansen Type II) secondary to road traffic accident (ICD-10: S72.301A).\nStatus Post Closed Reduction and Internal Fixation (CRIF) with Interlocking Intramedullary Nailing under Spinal Anaesthesia.",
            ],

            // =================================================================
            // HOSPITAL COURSE REMARKS (`course_remark`)
            // =================================================================
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Medical Inpatient Conservative Management Course',
                'template_text' => "Patient was admitted with acute symptoms and managed conservatively with IV fluids, empirical parenteral broad-spectrum antibiotics, analgesics, antiemetics, and supportive care. Vitals and oxygen saturation were monitored regularly. Blood investigations (CBC, RFT, LFT, Electrolytes) showed progressive resolution of inflammation and normalization of counts. Patient became afebrile on Day 3, started tolerating full oral diet and fluids comfortably, with normal bowel and bladder habits. Hemodynamically stable at discharge.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Surgical Laparoscopic Peri-Operative Course',
                'template_text' => "Patient underwent pre-operative optimization, cardiothoracic clearance, and anaesthetic evaluation. Surgery was performed uneventfully under general endotracheal anaesthesia. Intra-operative blood loss was minimal and specimen sent for histopathological examination. Post-operative recovery in PACU and ward was smooth with appropriate analgesia and IV antibiotics. Abdominal drain was serosanguinous (< 30 mL) and removed on Post-Op Day 2. Patient was mobilized, tolerated liquid then soft diet well, and surgical port sites are healthy with no discharge or erythema.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Maternity Labor & Delivery Course',
                'template_text' => "Mother was admitted in active labor. Partograph was plotted and fetal heart sounds monitored continuously. Delivered a live infant crying vigorously immediately at birth with APGAR score 8/10 at 1 min and 9/10 at 5 mins. Birth weight was adequate. Episiotomy / abdominal incision was sutured in layers with absorbable sutures and hemostasis secured. Post-partum vitals remained stable. Lochia rubra was normal in amount. Baby was kept in skin-to-skin contact, initiated on direct breast milk feeding within one hour of birth, and voided urine/meconium normally.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Orthopaedic Fracture Fixation & Rehabilitation Course',
                'template_text' => "Patient presented following trauma with limb swelling, deformity, and tenderness. Initial immobilization done with Thomas splint / skin traction. After pre-anesthetic clearance, patient underwent operative fixation under spinal anaesthesia. Check post-operative X-ray showed anatomical reduction and rigid stable internal fixation. Limb was kept elevated with ice packs. Distal neurovascular status (dorsalis pedis pulse, capillary refill, toe movements) remained intact throughout. Static quadriceps exercises and non-weight bearing walker mobilization initiated under physiotherapist supervision.",
            ],

            // =================================================================
            // DISCHARGE ADVICE & WARNING INSTRUCTIONS (`instruction_other`)
            // =================================================================
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Post-Surgical Wound Care & Suture Removal',
                'template_text' => "1. Keep the surgical dressing clean and completely dry. Do not wet the wound while bathing until sutures/staples are removed.\n2. Suture / Staple removal scheduled on [DATE] at General Surgery OPD.\n3. High protein balanced diet (pulses, eggs, paneer, green vegetables) with plenty of oral fluids.\n4. Avoid strenuous physical exertion, heavy weight lifting (> 5 kg), and abdominal straining for 4 weeks.\n5. EMERGENCY WARNING SIGNS: Immediately report to hospital ER if experiencing high fever (> 101 F), persistent vomiting, severe pain not relieved by medication, or active redness/soakage from surgical dressing.",
            ],
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Diabetic & Hypertensive Lifestyle Protocol',
                'template_text' => "1. Strictly adhere to prescribed oral hypoglycemic agents / insulin injections as per sliding scale timing.\n2. Daily home blood glucose monitoring (Fasting and 2-Hour Post-Meal) with a logbook.\n3. Diabetic diet: Avoid refined sugar, sweets, potatoes, white bread, and sweetened beverages. Take small, frequent, complex-carbohydrate meals.\n4. Low salt diet (< 3-5 g/day for hypertension control). Monitor BP thrice weekly.\n5. Maintain brisk walking 30 minutes daily once cleared by physician.\n6. Emergency signs of Hypoglycemia (shivering, sweating, dizziness, confusion): Immediately ingest 3 teaspoons of sugar or fruit juice and seek emergency medical care.",
            ],
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Post-Cardiac & Antiplatelet Compliance Advice',
                'template_text' => "1. MANDATORY: Do NOT stop Dual Antiplatelet Therapy (Aspirin + Clopidogrel / Ticagrelor) under any circumstances without consulting treating cardiologist (risk of acute stent thrombosis).\n2. Low salt, low cholesterol, heart-healthy Mediterranean diet.\n3. Avoid smoking, alcohol, and emotional/physical over-exertion.\n4. Emergency warning signs: Chest pain, radiating pain to jaw/left arm, sudden shortness of breath, profuse cold sweating, or syncopal episode — rush to nearest Cardiac Emergency immediately.",
            ],
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Postnatal Mother & Neonate Care Advice',
                'template_text' => "1. Exclusive Breastfeeding on demand every 2-3 hours day and night for the first 6 months. No water, honey, or formula milk.\n2. Keep newborn warm (Kangaroo Mother Care) and keep umbilical cord stump clean and dry.\n3. Maintain perineal hygiene with antiseptic wash. Episiotomy site care.\n4. Neonatal Danger Signs: Poor feeding, lethargy, hypothermia, fever, yellowish discoloration of palms/soles (severe jaundice), or rapid chest indrawing — contact Pediatrician immediately.",
            ],

            // =================================================================
            // ADDITIONAL EXPANDED DIAGNOSIS REMARKS (`diagnosis_remark`)
            // =================================================================
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Anterior Wall STEMI - Post Primary PCI',
                'template_text' => "Coronary Artery Disease: Acute Extensive Anterior Wall ST-Elevation Myocardial Infarction (AWMI) (ICD-10: I21.0).\nStatus Post Emergency Coronary Angiography (CAG) and Primary Percutaneous Coronary Intervention (PCI) with Drug-Eluting Stent (DES) to proximal Left Anterior Descending (LAD) artery via Right Radial approach.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Decompensated Heart Failure (HFrEF)',
                'template_text' => "Acute Decompensated Heart Failure with Reduced Ejection Fraction (HFrEF, LVEF 30-35%) (ICD-10: I50.21).\nNYHA Class-III dyspnea, bilateral pitting pedal edema, basal pulmonary crepitations, and elevated NT-proBNP.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Appendicitis - Post Laparoscopic Appendectomy',
                'template_text' => "Acute Suppurative Appendicitis with localized peritonitis (ICD-10: K35.80).\nStatus Post Laparoscopic Appendectomy under General Anaesthesia. Histopathology sent.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Right Inguinal Hernia - Post Lichtenstein Hernioplasty',
                'template_text' => "Right Complete Indirect Inguinoscrotal Hernia (Uncomplicated) (ICD-10: K40.90).\nStatus Post Open Right Inguinal Hernioplasty with Polypropylene Mesh repair under Spinal Anaesthesia.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Severe Dengue Fever with Thrombocytopenia',
                'template_text' => "Severe Dengue Fever with Warning Signs (Dengue NS1 Antigen Positive) with marked Thrombocytopenia (Nadir Platelets: 22,000/cumm) and mild transaminitis (ICD-10: A97.1).\nNow recovered with ascending platelet count (> 100,000/cumm).",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Ischemic Stroke (MCA Territory)',
                'template_text' => "Acute Ischemic Stroke involving Left Middle Cerebral Artery (MCA) territory with Right Hemiparesis and Motor Dysphasia (ICD-10: I63.512).\nBrain MRI showed acute infarction in left frontoparietal cortex. Managed with antiplatelets, statins, and neuro-rehabilitation.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Kidney Injury (Resolved) on CKD',
                'template_text' => "Acute Kidney Injury (Prerenal etiology secondary to severe volume depletion) superimposed on early Chronic Kidney Disease (ICD-10: N17.9 / N18.3).\nRenal parameters normalized from Admission Creatinine 4.8 mg/dL to Discharge Creatinine 1.3 mg/dL.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Total Knee Arthroplasty (TKR) for Severe OA',
                'template_text' => "Primary Osteoarthritis of Right Knee (Kellgren-Lawrence Grade-IV) with severe tricompartmental degeneration and varus deformity (ICD-10: M17.11).\nStatus Post Right Total Knee Arthroplasty (TKR) with cemented posterior stabilized prosthesis under Combined Spinal-Epidural Anaesthesia.",
            ],
            [
                'section_key'   => 'diagnosis_remark',
                'template_name' => 'Acute Pancreatitis (Biliary / Interstitial)',
                'template_text' => "Acute Interstitial Edematous Pancreatitis (Gallstone etiology) (ICD-10: K85.10).\nPresented with severe epigastric pain radiating to back, serum amylase 850 U/L, lipase 1200 U/L. Contrast CT showed Balthazar Grade-C acute pancreatitis without necrosis.",
            ],

            // =================================================================
            // ADDITIONAL EXPANDED COURSE REMARKS (`course_remark`)
            // =================================================================
            [
                'section_key'   => 'course_remark',
                'template_name' => 'ICU Sepsis Resuscitation & Step-Down Course',
                'template_text' => "Patient was admitted to ICU in septic shock secondary to severe urosepsis. Resuscitated per Surviving Sepsis Campaign guidelines with balanced crystalloid fluid resuscitation, blood and urine cultures drawn, empirical IV Piperacillin-Tazobactam + Amikacin initiated, and temporary low-dose Noradrenaline infusion titrated via central venous line. Hemodynamics stabilized within 24 hours. Noradrenaline was successfully tapered off. Urine output maintained > 0.8 mL/kg/hr. Inflammatory markers (Procalcitonin, CRP, TLC) declined significantly. Central line removed on Day 4. Patient stepped down to High Dependency Unit (HDU) and then general ward on room air.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Cardiac ICU Post-PCI Stent Care Course',
                'template_text' => "Patient presented to Emergency with crushing retrosternal chest pain and ECG evidence of acute anterior STEMI. Immediate loading with Aspirin 325 mg, Ticagrelor 180 mg, and Rosuvastatin 40 mg was administered. Shifted immediately to Cardiac Cath Lab. Primary PCI with drug-eluting stent (DES) to proximal LAD was performed successfully with TIMI-III distal coronary flow. Right radial puncture site closed with TR band; radial pulse was palpated strong and bounding post-deflation. Maintained in ICCU for 48 hours with continuous telemetry monitoring. No ventricular arrhythmias or post-infarction angina. Transthoracic Echo at discharge showed LVEF 45% with anterior hypokinesia. Mobilized safely and ready for discharge on guideline-directed medical therapy.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Acute Stroke Inpatient & Neuro-Rehabilitation Course',
                'template_text' => "Patient presented within window period with right hemiparesis (power 2/5 in right upper limb, 3/5 in right lower limb) and slurred speech. Non-contrast CT and MRI brain confirmed non-hemorrhagic acute infarct. Started on Dual Antiplatelet Therapy (DAPT), high-intensity statin, neuroprotective agents, and deep vein thrombosis prophylaxis with pneumatic compression devices. Swallowing assessment done by speech therapist. Inpatient physical therapy and occupational therapy initiated on Day 2. Patient demonstrated progressive motor improvement (power 4/5 at discharge) and can ambulate with minimal assistance. Speech clarity significantly improved.",
            ],
            [
                'section_key'   => 'course_remark',
                'template_name' => 'Pediatric Bronchiolitis Inpatient Course',
                'template_text' => "Admitted with tachypnea, wheezing, intercostal retractions, and oxygen desaturation (SpO2 88% on room air). Managed with humidified low-flow nasal cannula oxygen, hypertonic 3% saline nebulization, paracetamol drops for fever, and adequate oral/IV maintenance hydration. Viral respiratory swab sent. Oxygen requirement gradually weaned over 48 hours. Oral feeds tolerated well without vomiting or aspiration. At discharge, infant is active, afebrile, feeding eagerly, with respiratory rate 34/min and SpO2 98% on room air.",
            ],

            // =================================================================
            // IN-HOSPITAL TREATMENT SUMMARY REMARKS (`inhos_remark`)
            // =================================================================
            [
                'section_key'   => 'inhos_remark',
                'template_name' => 'Standard Inpatient Medical Treatment Summary',
                'template_text' => "1. IV Fluids: Ringer Lactate and Normal Saline as per hydration requirement.\n2. Parenteral Antibiotics: IV Ceftriaxone 1g BD x 5 days.\n3. Gastric Protection: IV Pantoprazole 40mg OD.\n4. Analgesics/Antipyretics: IV Paracetamol 1g TDS SOS.\n5. Anti-emetic: IV Ondansetron 4mg TDS SOS.\n6. Vitals, intake-output, and capillary blood glucose charted regularly.\n7. Daily laboratory trends monitored and documented in medical records.",
            ],
            [
                'section_key'   => 'inhos_remark',
                'template_name' => 'Standard Peri-Operative Surgical Treatment Summary',
                'template_text' => "1. Pre-Op: NPO after midnight, bowel preparation, IV Cefuroxime 1.5g on induction.\n2. Intra-Op: Anesthesia record maintained, fluids infused 1500 mL, blood loss < 50 mL.\n3. Post-Op IV Antibiotics: IV Cefuroxime 750mg TDS + IV Metronidazole 500mg TDS x 3 days.\n4. Post-Op Analgesia: IV Tramadol / Paracetamol infusion + epidural top-up.\n5. Thromboembolism Prophylaxis: Subcutaneous Enoxaparin 40mg OD started 12 hours post-op.\n6. Wound inspected daily; dressed under strict aseptic technique.",
            ],

            // =================================================================
            // EXPANDED DISCHARGE INSTRUCTIONS (`instruction_other` / `instruction_remark`)
            // =================================================================
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Stroke Care & Anticoagulation Precaution Protocol',
                'template_text' => "1. Continue Aspirin / Clopidogrel and Atorvastatin daily at bedtime without missing doses.\n2. Blood pressure target: Maintain systolic BP < 130 mmHg and diastolic < 80 mmHg.\n3. Continue home physiotherapy: Active and passive limb exercises twice daily.\n4. Swallowing precautions: Eat in upright 90-degree sitting position, chew thoroughly, avoid lying down immediately after meals.\n5. Watch for bleeding gums, black tarry stools, or unusual bruising.\n6. Emergency signs: Sudden facial drooping, arm weakness, slurred speech, or difficulty walking — call 108 / Emergency immediately.",
            ],
            [
                'section_key'   => 'instruction_other',
                'template_name' => 'Total Joint Replacement (TKR/THR) Home Rehabilitation',
                'template_text' => "1. Suture / Staple removal on Day 14 at Orthopaedic OPD.\n2. Keep surgical incision dry. Use waterproof dressing if advised.\n3. Continue deep vein thrombosis (DVT) prophylaxis tablet / injection as prescribed for 14-28 days.\n4. Ankle pumps 20 times every hour while awake. Static quadriceps and straight leg raises as guided by physiotherapist.\n5. Walker-assisted ambulation; do NOT twist the operated leg, cross legs, or sit on low chairs.\n6. Apply ice packs wrapped in a towel over the joint for 15 minutes, 3-4 times daily for swelling.",
            ],
            [
                'section_key'   => 'instruction_remark',
                'template_name' => 'NABH Standard Emergency Return Precautions',
                'template_text' => "Patient and attendants were counseled at discharge in their primary language regarding red-flag symptoms. Contact hospital Emergency (Phone: {{H_phone_No}}) immediately if experiencing: (a) High fever (> 101 F) with rigors, (b) Sudden shortness of breath, chest pain, or palpitations, (c) Persistent vomiting or inability to retain fluids, (d) Altered sensorium, confusion, or convulsions, (e) Active bleeding, oozing, or redness at surgical site. Follow-up consultation scheduled on [DATE] in OPD.",
            ],
        ];
    }
}

