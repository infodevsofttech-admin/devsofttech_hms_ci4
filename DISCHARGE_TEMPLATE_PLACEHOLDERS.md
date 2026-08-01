# IPD Discharge Template Placeholders

This document lists all available placeholders that can be used in IPD discharge templates.

## How to Use Placeholders

Placeholders can be used in templates using double curly braces: `{{PLACEHOLDER_NAME}}`

Example:
```html
<h2>{{HOSPITAL_NAME}}</h2>
<p>Patient: {{PATIENT_NAME}} ({{UHID}})</p>
{{CONTENT}}
{{SIGNATURE_BLOCK}}
```

---

## Hospital Information

| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{H_Name}}` or `{{HOSPITAL_NAME}}` | Hospital name | "City Hospital" |
| `{{H_address_1}}` | Hospital address line 1 | "123 Main Street" |
| `{{H_address_2}}` | Hospital address line 2 | "Medical District" |
| `{{H_phone_No}}` or `{{HOSPITAL_PHONE}}` | Hospital phone number | "+91-123-4567890" |
| `{{H_Email}}` or `{{HOSPITAL_EMAIL}}` | Hospital email | "info@hospital.com" |
| `{{H_logo}}` | Hospital logo filename | "logo.png" |
| `{{H_logo_abs}}` | Absolute path to logo | "/path/to/public/assets/images/logo.png" |
| `{{HOSPITAL_ADDRESS}}` | Combined address (line 1 + line 2) | "123 Main Street, Medical District" |

---

## Patient Information

| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{PATIENT_TITLE}}` | Patient title/prefix | "Mr." / "Mrs." / "Miss" |
| `{{PATIENT_NAME}}` | Patient full name | "John Doe" |
| `{{UHID}}` | Patient UHID/Registration number | "UHID123456" |
| `{{IPD_CODE}}` | IPD admission code | "IPD001234" |
| `{{AGE_GENDER}}` | Age and gender | "45 Years / Male" |
| `{{GUARDIAN}}` or `{{GUARDIAN_NAME}}` | Guardian/relative name | "S/o Ramesh Kumar" |
| `{{GUARDIAN_RELATION}}` | Guardian relation | "S/o" or "W/o" |
| `{{PATIENT_ADDRESS}}` | Patient full address | "456 Park Lane, City" |
| `{{PATIENT_PHONE}}` | Patient phone number | "+91-9876543210" |
| `{{INSURANCE_COMPANY}}` | Insurance company name | "HDFC Ergo" or "Direct" |
| `{{PATIENT_INFO_TABLE}}` | Auto-generated patient info table | (HTML table) |

---

## Admission & Discharge Dates

| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{ADMIT_DATE}}` | Admission date | "05-06-2026" |
| `{{DISCHARGE_DATE}}` | Discharge date | "07-06-2026" |
| `{{ADMIT_DATE_ONLY}}` | Admission date (alternative) | "05-06-2026" |
| `{{DISCHARGE_DATE_ONLY}}` | Discharge date (alternative) | "07-06-2026" |
| `{{ADMISSION_TIME}}` | Admission time | "10:30 AM" |
| `{{DISCHARGE_TIME}}` | Discharge time | "04:15 PM" |
| `{{ADMIT_TIME}}` | Admission time (alternative) | "10:30 AM" |
| `{{CURRENT_DATE}}` | Current date (when PDF generated) | "07-06-2026" |
| `{{PRINT_TIME}}` | PDF generation timestamp | "07-06-2026 16:45:30" |

---

## Medical Information

| Placeholder | Description | Example |
|------------|-------------|---------|
| `{{DEPARTMENT}}` | Admission department | "General Medicine" |
| `{{DOCTOR_NAMES}}` or `{{DOCTOR_NAME}}` | Treating doctor(s) | "Dr. Sharma, Dr. Patel" |

---

## Clinical Content Sections

### Full Content
| Placeholder | Description |
|------------|-------------|
| `{{CONTENT}}` | **Complete discharge content** - includes all sections below |

### Individual Sections
| Placeholder | Description |
|------------|-------------|
| `{{DISCHARGE_SUMMARY}}` | Discharge status header (e.g., "Discharged", "LAMA") |
| `{{PRESENTING_COMPLAINTS}}` | Presenting complaints and reason for admission |
| `{{PAIN_MEASUREMENT_SCALE}}` | Pain scale measurement (if recorded) |
| `{{GENERAL_EXAM_ADMISSION}}` | General examination findings on admission |
| `{{PERSONAL_HISTORY}}` | Personal history (smoking, alcohol, etc.) |
| `{{DRUG_ALLERGY_ADR}}` | Drug allergy status and ADR history |
| `{{CO_MORBIDITIES}}` | Co-morbidities |
| `{{CLINICAL_INVESTIGATION_REPORTS}}` | Lab reports, X-rays, CT/MRI results |
| `{{FINAL_DIAGNOSIS}}` | Final diagnosis |
| `{{COURSE_IN_HOSPITAL}}` | Course in the hospital / treatment details |
| `{{EXAMINATION_ON_DISCHARGE}}` | Physical examination on discharge |
| `{{SURGERY}}` | Surgical procedures performed |
| `{{PROCEDURE}}` | Other procedures performed |
| `{{DISCHARGE_MEDICATIONS}}` | Discharge medications table |

### Discharge Instructions
| Placeholder | Description |
|------------|-------------|
| `{{DISCHARGE_INSTRUCTIONS}}` | **Full instruction section** (Dietary + Other + Discharge Summary) |
| `{{DIETARY_ADVICE}}` | Dietary advice (selected food items) |
| `{{OTHER_ADVICE}}` | Other Advice field only |
| `{{INSTRUCTION_REMARK}}` or `{{DISCHARGE_ADVICE}}` | Discharge Summary field only |

**NEW FEATURE:** You can now use `{{OTHER_ADVICE}}` and `{{DISCHARGE_ADVICE}}` separately to control layout!

Example:
```html
<h4>Other Advice</h4>
{{OTHER_ADVICE}}

<h4>Discharge Summary</h4>
{{DISCHARGE_ADVICE}}
```

Or use the combined section:
```html
{{DISCHARGE_INSTRUCTIONS}}
```

### Signature Block
| Placeholder | Description |
|------------|-------------|
| `{{SIGNATURE_BLOCK}}` | Signature table for Consultant, Medical Officer, and Receiver |

---

## Legacy Aliases

These are provided for CI3 compatibility:

| Legacy Placeholder | Modern Equivalent |
|-------------------|------------------|
| `{FinalDiagnosis}` | `{{FINAL_DIAGNOSIS}}` |
| `{Surgery}` | `{{SURGERY}}` |
| `{Procedure}` | `{{PROCEDURE}}` |
| `{personal_history}` | `{{PERSONAL_HISTORY}}` |
| `{discharge_complaint}` | `{{PRESENTING_COMPLAINTS}}` |
| `{discharge_general_exam}` | `{{GENERAL_EXAM_ADMISSION}}` |
| `{Course_in_the_hospital}` | `{{COURSE_IN_HOSPITAL}}` |
| `{Discharge_Medications}` | `{{DISCHARGE_MEDICATIONS}}` |
| `{diet_advice}` | `{{DIETARY_ADVICE}}` |
| `{Discharge_Instructions}` | `{{DISCHARGE_INSTRUCTIONS}}` |

---

## Example Template

```html
<!DOCTYPE html>
<html>
<head>
    <title>Discharge Summary</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .patient-info { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{HOSPITAL_NAME}}</h2>
        <p>{{HOSPITAL_ADDRESS}}</p>
        <p>Phone: {{HOSPITAL_PHONE}} | Email: {{HOSPITAL_EMAIL}}</p>
    </div>
    
    <div class="patient-info">
        <table>
            <tr>
                <td><strong>Patient Name:</strong></td>
                <td>{{PATIENT_NAME}}</td>
                <td><strong>UHID:</strong></td>
                <td>{{UHID}}</td>
            </tr>
            <tr>
                <td><strong>Age/Gender:</strong></td>
                <td>{{AGE_GENDER}}</td>
                <td><strong>IPD Code:</strong></td>
                <td>{{IPD_CODE}}</td>
            </tr>
            <tr>
                <td><strong>Admission Date:</strong></td>
                <td>{{ADMIT_DATE}} {{ADMISSION_TIME}}</td>
                <td><strong>Discharge Date:</strong></td>
                <td>{{DISCHARGE_DATE}} {{DISCHARGE_TIME}}</td>
            </tr>
            <tr>
                <td><strong>Department:</strong></td>
                <td>{{DEPARTMENT}}</td>
                <td><strong>Doctor:</strong></td>
                <td>{{DOCTOR_NAMES}}</td>
            </tr>
        </table>
    </div>
    
    {{CONTENT}}
    
    <div style="margin-top: 40px;">
        {{SIGNATURE_BLOCK}}
    </div>
</body>
</html>
```

---

## Notes

- All placeholders are **case-insensitive**: `{{PATIENT_NAME}}`, `{{patient_name}}`, and `{{Patient_Name}}` all work
- Use `{{CONTENT}}` for the complete discharge content, or use individual section placeholders for custom layouts
- If a placeholder has no data, it will be replaced with an empty string
- HTML content is preserved - sections already include proper formatting
