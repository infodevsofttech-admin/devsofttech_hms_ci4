# IPD Discharge Template Placeholders - Quick Reference

## 1. Hospital Information
*From Hospital Setting table*

```
{{H_Name}}              - Hospital name
{{H_address_1}}         - Hospital address line 1
{{H_address_2}}         - Hospital address line 2  
{{H_phone_No}}          - Hospital phone number
{{H_Email}}             - Hospital email
{{H_logo}}              - Logo filename
{{H_logo_abs}}          - Absolute path to logo file
{{hospital_name}}       - Hospital name (same as H_Name)
{{hospital_address}}    - Combined address (address_1 + address_2)
{{hospital_phone}}      - Hospital phone (same as H_phone_No)
{{hospital_email}}      - Hospital email (same as H_Email)
```

---

## 2. Patient Information
*From Patient_Master table*

```
{{PATIENT_NAME}}        - Full patient name
{{PATIENT_TITLE}}       - Patient title/prefix (e.g., "Mr.", "Mrs.", "Miss")
{{UHID}}                - Patient UHID/registration code
{{AGE_GENDER}}          - Age and gender combined (e.g., "26 Year / Female")
{{GUARDIAN}}            - Guardian relation and name (e.g., "W/O RAM SINGH")
{{GUARDIAN_RELATION}}   - Guardian relation only (e.g., "W/O")
{{GUARDIAN_NAME}}       - Guardian name only (e.g., "RAM SINGH")
{{PATIENT_ADDRESS}}     - Full patient address
{{PATIENT_PHONE}}       - Patient phone number ✨ NEW
```

---

## 3. IPD Information
*From IPD_Master table*

```
{{IPD_CODE}}            - IPD admission number (e.g., "A26050000001")
{{DEPARTMENT}}          - Department name (e.g., "General Medicine")
{{ADMIT_DATE}}          - Admission date (dd-mm-yyyy format)
{{DISCHARGE_DATE}}      - Discharge date (dd-mm-yyyy format)
{{ADMIT_DATE_ONLY}}     - Admission date (same as ADMIT_DATE)
{{DISCHARGE_DATE_ONLY}} - Discharge date (same as DISCHARGE_DATE)
{{ADMISSION_TIME}}      - Admission time (HH:MM AM/PM)
{{DISCHARGE_TIME}}      - Discharge time (HH:MM AM/PM)
{{ADMIT_TIME}}          - Admission time (same as ADMISSION_TIME)
{{ISDELIVERY}}          - Delivery case flag (1 for yes, 0 for no)
{{INSURANCE_COMPANY}}   - Insurance company name (defaults to "Direct") ✨ NEW
{{DOCTOR_NAMES}}        - Treating doctors with specialties ✨ NEW
{{DOCTOR_NAME}}         - Same as DOCTOR_NAMES ✨ NEW
```

---

## 4. IPD Discharge Content

### 4.1 Complete Content Token

```
{{CONTENT}}             - All clinical sections combined (NO patient table)
```

### 4.2 Section Placeholders for Custom Layout/Order

```
{{DISCHARGE_SUMMARY}}             - High-level summary (department, treating doctors, dates)
{{FINAL_DIAGNOSIS}}               - Final diagnosis at discharge
{{SURGERY}}                       - Surgical procedures performed
{{PROCEDURE}}                     - Medical procedures
{{PERSONAL_HISTORY}}              - Personal history (smoking, alcohol, etc.)
{{PRESENTING_COMPLAINTS}}         - Complaints with duration and reason for admission
{{PAIN_MEASUREMENT_SCALE}}        - Pain assessment scale
{{GENERAL_EXAM_ADMISSION}}        - General examination on admission
{{CLINICAL_INVESTIGATION_REPORTS}} - Lab and imaging investigation reports
{{COURSE_IN_HOSPITAL}}            - Course during hospitalization
{{EXAMINATION_ON_DISCHARGE}}      - Examination findings at discharge
{{DRUG_ALLERGY_ADR}}              - Drug allergies and adverse drug reactions
{{CO_MORBIDITIES}}                - Co-morbid conditions
{{DISCHARGE_MEDICATIONS}}         - Medications prescribed at discharge
{{DIETARY_ADVICE}}                - Dietary advice and instructions
{{DISCHARGE_INSTRUCTIONS}}        - Discharge instructions and follow-up advice
{{SIGNATURE_BLOCK}}               - Signature block for consultant
```

**Note:** `{{DISCHARGE_SUMMARY}}` section now includes:
- Department name (from `ipd_master.dept_id` or `department_id`)
- Treating doctor(s) with specializations (from `ipd_master.doc_list`)
- Date of admission
- Date of discharge

### 4.3 Pre-Built Patient Table

```
{{PATIENT_INFO_TABLE}}   - Complete patient demographic table (HTML)
                          Includes: Name, UHID, Age/Gender, IPD No, Guardian,
                          Phone, Admission, Discharge, Address, Insurance, Department
```

---

## 5. Common / Meta Tokens

```
{{CURRENT_DATE}}        - Current date (dd-mm-yyyy format)
{{PRINT_TIME}}          - Current date and time (dd-mm-yyyy HH:MM:SS)
```

---

## Token Usage Examples

### Example 1: Minimal Template (Just Content)
```html
{{CONTENT}}
```
**Result:** Shows only clinical sections (no patient table)

---

### Example 2: With Patient Table
```html
{{PATIENT_INFO_TABLE}}
{{CONTENT}}
```
**Result:** Patient demographics + all clinical sections

---

### Example 3: Custom Layout with New Tokens
```html
<h2 style="text-align:center;">{{H_Name}}</h2>
<h3 style="text-align:center;">Discharge Summary</h3>

<table border="1" width="100%">
  <tr>
    <td><b>Patient Name:</b></td>
    <td>{{PATIENT_NAME}}</td>
    <td><b>UHID:</b></td>
    <td>{{UHID}}</td>
  </tr>
  <tr>
    <td><b>Phone:</b></td>
    <td>{{PATIENT_PHONE}}</td>
    <td><b>Insurance:</b></td>
    <td>{{INSURANCE_COMPANY}}</td>
  </tr>
  <tr>
    <td colspan="4"><b>Treating Doctor(s):</b> {{DOCTOR_NAMES}}</td>
  </tr>
</table>

<hr>

{{CONTENT}}
```

---

### Example 4: Selective Sections Only
```html
<h2>Discharge Summary</h2>

{{PATIENT_INFO_TABLE}}

<h3>Clinical Information</h3>
{{FINAL_DIAGNOSIS}}
{{SURGERY}}
{{PROCEDURE}}

<h3>Treatment Plan</h3>
{{DISCHARGE_MEDICATIONS}}
{{DIETARY_ADVICE}}
{{DISCHARGE_INSTRUCTIONS}}
```
**Result:** Shows only selected sections, omits complaints, examination, etc.

---

## Notes

- **All tokens are case-insensitive** ({{PATIENT_NAME}}, {{patient_name}}, {{Patient_Name}} all work)
- **Empty values show nothing** - No need for conditionals, tokens auto-hide if data is missing
- **{{CONTENT}} never includes patient table** - Add {{PATIENT_INFO_TABLE}} explicitly if needed
- **Doctor names from ipd_master.doc_list** - Automatically formatted as "Dr. Name [Specialty]"
- **Insurance defaults to "Direct"** - Shows "Direct" when no insurance company assigned

---

## Testing Your Template

1. Create/edit template in `discharge_template` table
2. Clear cached content: `php clear_discharge_content.php [ipd_id]`
3. View discharge: `http://localhost:8080/Ipd_discharge/show_discharge/[ipd_id]/1?tpl=[template_id]`
4. Check output for correct token replacement

---

## Complete Template Example (Full Featured)

```html
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
    table td { padding: 8px; border: 1px solid #333; }
    h2 { text-align: center; color: #2c3e50; margin: 10px 0; }
    h3 { color: #34495e; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
    .header { text-align: center; margin-bottom: 20px; }
    .footer { margin-top: 30px; text-align: center; font-size: 10pt; color: #7f8c8d; }
  </style>
</head>
<body>
  <!-- Hospital Header -->
  <div class="header">
    <h2>{{H_Name}}</h2>
    <p>{{hospital_address}}<br>
    Phone: {{hospital_phone}} | Email: {{hospital_email}}</p>
    <h3>DISCHARGE SUMMARY</h3>
  </div>

  <!-- Patient Demographics -->
  <table>
    <tr>
      <td width="20%"><b>Patient Name</b></td>
      <td width="30%">{{PATIENT_NAME}}</td>
      <td width="20%"><b>UHID</b></td>
      <td width="30%">{{UHID}}</td>
    </tr>
    <tr>
      <td><b>Age / Gender</b></td>
      <td>{{AGE_GENDER}}</td>
      <td><b>IPD No.</b></td>
      <td>{{IPD_CODE}}</td>
    </tr>
    <tr>
      <td><b>Guardian</b></td>
      <td>{{GUARDIAN}}</td>
      <td><b>Phone</b></td>
      <td>{{PATIENT_PHONE}}</td>
    </tr>
    <tr>
      <td><b>Address</b></td>
      <td colspan="3">{{PATIENT_ADDRESS}}</td>
    </tr>
    <tr>
      <td><b>Department</b></td>
      <td>{{DEPARTMENT}}</td>
      <td><b>Insurance</b></td>
      <td>{{INSURANCE_COMPANY}}</td>
    </tr>
    <tr>
      <td><b>Admission Date/Time</b></td>
      <td>{{ADMIT_DATE}} {{ADMISSION_TIME}}</td>
      <td><b>Discharge Date/Time</b></td>
      <td>{{DISCHARGE_DATE}} {{DISCHARGE_TIME}}</td>
    </tr>
    <tr>
      <td><b>Treating Doctor(s)</b></td>
      <td colspan="3">{{DOCTOR_NAMES}}</td>
    </tr>
  </table>

  <!-- Clinical Content -->
  {{CONTENT}}

  <!-- Footer -->
  <div class="footer">
    <p>Generated on {{PRINT_TIME}}</p>
  </div>
</body>
</html>
```

This template includes:
- ✅ All new tokens (PATIENT_PHONE, INSURANCE_COMPANY, DOCTOR_NAMES)
- ✅ Complete patient demographics in custom table
- ✅ All clinical sections via {{CONTENT}}
- ✅ Professional styling
- ✅ Hospital header and footer
