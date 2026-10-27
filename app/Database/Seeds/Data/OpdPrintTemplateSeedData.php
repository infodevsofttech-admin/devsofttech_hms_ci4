<?php

namespace App\Database\Seeds\Data;

/**
 * Standard OPD Prescription Print Templates stored in `hospital_setting`
 * Keys: `OPD_PRINT_TEMPLATE__{name}`
 */
class OpdPrintTemplateSeedData
{
    public static function getTemplates(): array
    {
        return [
            // =================================================================
            // 1. STANDARD MULTISPECIALITY OPD PRESCRIPTION
            // =================================================================
            'standard' => [
                'name'  => 'standard',
                'title' => 'Standard Multispeciality Hospital OPD Prescription',
                'html'  => <<<HTML
<div style="font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #1e293b;">
    <!-- HOSPITAL LETTERHEAD HEADER -->
    <table style="width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 8px;">
        <tr>
            <td style="width: 15%; vertical-align: middle;">
                <img src="{{H_logo_abs}}" style="max-height: 55px; max-width: 110px;" alt="Logo">
            </td>
            <td style="width: 85%; vertical-align: middle; text-align: center;">
                <h2 style="margin: 0; color: #1e3a8a; font-size: 19px; font-weight: bold;">{{H_Name}}</h2>
                <div style="font-size: 10.5px; color: #475569;">{{H_address_1}}, {{H_address_2}} | Phone: {{H_phone_No}}</div>
                <div style="font-size: 11px; font-weight: bold; color: #2563eb; margin-top: 2px;">OUTPATIENT DEPARTMENT (OPD) PRESCRIPTION</div>
            </td>
        </tr>
    </table>

    <!-- PATIENT DEMOGRAPHICS & VITALS -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10.5px; background: #f8fafc;" border="1" cellpadding="4" bordercolor="#cbd5e1">
        <tr>
            <td style="width: 35%;"><b>Patient Name:</b> {{PATIENT_NAME}}</td>
            <td style="width: 25%;"><b>UHID:</b> {{UHID}}</td>
            <td style="width: 20%;"><b>OPD No:</b> {{OPD_CODE}}</td>
            <td style="width: 20%;"><b>Date:</b> {{VISIT_DATE}}</td>
        </tr>
        <tr>
            <td><b>Age / Sex:</b> {{AGE_GENDER}}</td>
            <td><b>Phone:</b> {{PHONE_NO}}</td>
            <td colspan="2"><b>Consultant:</b> {{DOCTOR_NAME}}</td>
        </tr>
        <tr style="background: #eff6ff;">
            <td colspan="4">
                <b>Vitals:</b> BP: {{BP}} mmHg | Pulse: {{PULSE}} /min | Temp: {{TEMP}} °F | SpO2: {{SPO2}} % | Wt: {{WEIGHT}} kg | Height: {{HEIGHT}} cm | BMI: {{BMI}}
            </td>
        </tr>
    </table>

    <!-- CLINICAL CONTENT & PRESCRIPTION BODY -->
    <div style="min-height: 480px; font-size: 11px; line-height: 1.5;">
        {{OPD_CONTENT}}
    </div>

    <!-- PRESCRIPTION FOOTER -->
    <table style="width: 100%; border-top: 1px solid #cbd5e1; margin-top: 20px; font-size: 10px; color: #64748b; padding-top: 6px;">
        <tr>
            <td style="width: 50%; vertical-align: bottom;">
                <b>Follow-up Visit:</b> {{FOLLOW_UP_DATE}}<br>
                <i>Emergency? Contact Hospital 24x7 Casualty: {{H_phone_No}}</i>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: bottom;">
                <br><br>
                ____________________________________<br>
                <b>{{DOCTOR_NAME}}</b><br>
                {{DOCTOR_DEGREE}} | Reg No: {{DOCTOR_REG_NO}}
            </td>
        </tr>
    </table>
</div>
HTML
            ],

            // =================================================================
            // 2. COMPACT SINGLE-PAGE OPD PRESCRIPTION
            // =================================================================
            'compact' => [
                'name'  => 'compact',
                'title' => 'Compact Single-Page OPD Prescription (High-Volume)',
                'html'  => <<<HTML
<div style="font-family: Arial, sans-serif; font-size: 10px; line-height: 1.35; color: #0f172a;">
    <table style="width: 100%; border-bottom: 1.5px solid #0f172a; padding-bottom: 4px; margin-bottom: 6px;">
        <tr>
            <td style="width: 65%;">
                <h3 style="margin: 0; color: #0f172a; font-size: 16px;">{{H_Name}}</h3>
                <div style="font-size: 9.5px; color: #475569;">{{H_address_1}} | Ph: {{H_phone_No}}</div>
            </td>
            <td style="width: 35%; text-align: right;">
                <b>{{DOCTOR_NAME}}</b><br>
                <span style="font-size: 9px; color: #64748b;">{{DOCTOR_DEGREE}} (Reg: {{DOCTOR_REG_NO}})</span>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 9.5px; background: #f8fafc;" border="1" cellpadding="3" bordercolor="#e2e8f0">
        <tr>
            <td><b>Patient:</b> {{PATIENT_NAME}} ({{AGE_GENDER}})</td>
            <td><b>UHID:</b> {{UHID}}</td>
            <td><b>OPD:</b> {{OPD_CODE}}</td>
            <td><b>Date:</b> {{VISIT_DATE}}</td>
            <td><b>BP:</b> {{BP}} | <b>Pulse:</b> {{PULSE}}</td>
        </tr>
    </table>

    <div style="min-height: 520px; font-size: 10px; line-height: 1.4;">
        {{OPD_CONTENT}}
    </div>

    <table style="width: 100%; border-top: 1px solid #cbd5e1; margin-top: 15px; font-size: 9px; color: #64748b;">
        <tr>
            <td>Next Review: {{FOLLOW_UP_DATE}} | Valid for 7 days</td>
            <td style="text-align: right;">Doctor Signature: ___________________</td>
        </tr>
    </table>
</div>
HTML
            ],

            // =================================================================
            // 3. PEDIATRIC & CHILD HEALTH OPD PRESCRIPTION
            // =================================================================
            'pediatric' => [
                'name'  => 'pediatric',
                'title' => 'Pediatric & Child Health OPD Prescription with Growth Grid',
                'html'  => <<<HTML
<div style="font-family: Arial, sans-serif; font-size: 10.5px; line-height: 1.4; color: #1e293b;">
    <table style="width: 100%; border-bottom: 2px solid #0284c7; padding-bottom: 6px; margin-bottom: 8px;">
        <tr>
            <td style="width: 15%;">
                <img src="{{H_logo_abs}}" style="max-height: 50px; max-width: 100px;" alt="Logo">
            </td>
            <td style="width: 85%; text-align: center;">
                <h2 style="margin: 0; color: #0369a1; font-size: 18px; font-weight: bold;">{{H_Name}} - PEDIATRIC CLINIC</h2>
                <div style="font-size: 10px; color: #64748b;">Child Health & Immunization Center | Ph: {{H_phone_No}}</div>
            </td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; background: #f0f9ff;" border="1" cellpadding="4" bordercolor="#bae6fd">
        <tr>
            <td style="width: 35%;"><b>Child Name:</b> {{PATIENT_NAME}}</td>
            <td style="width: 25%;"><b>UHID:</b> {{UHID}}</td>
            <td style="width: 20%;"><b>Age / Sex:</b> {{AGE_GENDER}}</td>
            <td style="width: 20%;"><b>Date:</b> {{VISIT_DATE}}</td>
        </tr>
        <tr>
            <td colspan="4" style="background: #e0f2fe;">
                <b>Growth Parameters:</b> Weight: <b>{{WEIGHT}} kg</b> | Height/Length: <b>{{HEIGHT}} cm</b> | Head Circ: <b>[HC_CM] cm</b> | Temp: <b>{{TEMP}} °F</b>
            </td>
        </tr>
    </table>

    <div style="min-height: 480px; font-size: 10.5px; line-height: 1.5;">
        {{OPD_CONTENT}}
    </div>

    <table style="width: 100%; border-top: 1px solid #cbd5e1; margin-top: 20px; font-size: 9.5px; color: #64748b;">
        <tr>
            <td style="width: 60%;">
                <b>Immunization Due:</b> Next vaccine due at [AGE/DATE].<br>
                <b>Emergency:</b> In case of poor feeding, high fever, or respiratory distress, report to Pediatric ER.
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom;">
                <br><br>
                ____________________________________<br>
                <b>Consultant Pediatrician</b>
            </td>
        </tr>
    </table>
</div>
HTML
            ],
        ];
    }
}
