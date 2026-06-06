# IPD Discharge Template System - CI3 to CI4 Migration Guide

## Overview

The CI4 discharge template system now **correctly matches** CI3 behavior where:
- **Patient demographic table is NOT auto-generated in content**
- **Content contains ONLY clinical sections** (complaints, diagnosis, medications, etc.)
- **Templates control the entire layout** using tokens

---

## How It Works (CI3 vs CI4)

### CI3 System (Old - chamunda_discharge_template_html.php)

```php
<!-- Patient table was HARDCODED in template -->
<table>
  <tr>
    <td><b>Name</b></td>
    <td><?= ucwords($ipd_master[0]->p_fname) ?></td>
    <td><b>UHID</b></td>
    <td><?= $ipd_master[0]->p_code ?></td>
  </tr>
  <!-- More rows... -->
</table>

<!-- Clinical sections were injected as variables -->
<?php if (isset($FinalDiagnosis)) { ?>
  <?= $FinalDiagnosis ?>
<?php } ?>

<?php if (isset($Surgery)) { ?>
  <?= $Surgery ?>
<?php } ?>
```

**Key Points:**
- Template was a **PHP file** with hardcoded HTML for patient info
- Content variables (`$FinalDiagnosis`, `$Surgery`, etc.) were **HTML fragments**
- Template decided what to show and in what order

### CI4 System (New - Database Templates with Tokens)

```html
<!-- Option 1: Build your own patient table using tokens -->
<table>
  <tr>
    <td><b>Name</b></td><td>{{PATIENT_NAME}}</td>
    <td><b>UHID</b></td><td>{{UHID}}</td>
  </tr>
  <tr>
    <td><b>Age/Gender</b></td><td>{{AGE_GENDER}}</td>
    <td><b>IPD No</b></td><td>{{IPD_CODE}}</td>
  </tr>
</table>

<!-- Option 2: Use pre-built patient table token -->
{{PATIENT_INFO_TABLE}}

<!-- All clinical sections -->
{{CONTENT}}

<!-- Or pick specific sections -->
{{FINAL_DIAGNOSIS}}
{{SURGERY}}
{{PROCEDURE}}
{{DISCHARGE_MEDICATIONS}}
```

**Key Points:**
- Templates are stored in **database** (`discharge_template` table)
- Use **{{TOKEN}}** syntax instead of PHP variables
- Content ({{CONTENT}}) contains **ONLY clinical sections** (NO patient table)
- Patient table available as `{{PATIENT_INFO_TABLE}}` token

---

## Available Tokens

### Patient Demographic Tokens

| Token | Description | Example Output |
|-------|-------------|----------------|
| `{{PATIENT_NAME}}` | Full patient name | KAMLA SINGH |
| `{{UHID}}` | Patient UHID/code | P26051000001 |
| `{{IPD_CODE}}` | IPD admission number | A26050000001 |
| `{{AGE_GENDER}}` | Age and gender | 26 Year / Female |
| `{{GUARDIAN}}` | Guardian relation and name | W/O of RAM SINGH |
| `{{GUARDIAN_RELATION}}` | Guardian relation only | W/O of |
| `{{GUARDIAN_NAME}}` | Guardian name only | RAM SINGH |
| `{{PATIENT_ADDRESS}}` | Full address | KASHIPUR, District, State |
| `{{PATIENT_PHONE}}` | Patient phone number | 9876543210 |

### IPD Information Tokens

| Token | Description | Example Output |
|-------|-------------|----------------|
| `{{DEPARTMENT}}` | Department name | General Medicine |
| `{{ADMIT_DATE}}` | Admission date | 23-05-2026 |
| `{{DISCHARGE_DATE}}` | Discharge date | 05-06-2026 |
| `{{ADMIT_DATE_ONLY}}` | Admission date (same as ADMIT_DATE) | 23-05-2026 |
| `{{DISCHARGE_DATE_ONLY}}` | Discharge date (same as DISCHARGE_DATE) | 05-06-2026 |
| `{{ADMISSION_TIME}}` | Admission time | 10:30 AM |
| `{{DISCHARGE_TIME}}` | Discharge time | 03:45 PM |
| `{{ADMIT_TIME}}` | Admission time (same as ADMISSION_TIME) | 10:30 AM |
| `{{INSURANCE_COMPANY}}` | Insurance company name | Star Health Insurance |
| `{{DOCTOR_NAMES}}` | Treating doctor(s) with specialties | Dr. Rajesh Kumar [General Medicine], Dr. Priya Sharma [Cardiology] |
| `{{DOCTOR_NAME}}` | Same as DOCTOR_NAMES | Dr. Rajesh Kumar [General Medicine] |

### Hospital Information Tokens

| Token | Description |
|-------|-------------|
| `{{H_Name}}` | Hospital name |
| `{{hospital_address}}` | Combined hospital address |
| `{{hospital_phone}}` | Hospital phone number |
| `{{hospital_email}}` | Hospital email |
| `{{H_logo}}` | Logo filename |
| `{{H_logo_abs}}` | Absolute path to logo |

### Pre-Built Table Token

| Token | Description |
|-------|-------------|
| `{{PATIENT_INFO_TABLE}}` | Complete patient demographic table (matches CI3 format) |

### Content Tokens

| Token | Description |
|-------|-------------|
| `{{CONTENT}}` | **All clinical sections** (complaints, diagnosis, course, medications, etc.) |

### Individual Section Tokens

| Token | Description |
|-------|-------------|
| `{{FINAL_DIAGNOSIS}}` | Final diagnosis at discharge |
| `{{SURGERY}}` | Surgical procedures performed |
| `{{PROCEDURE}}` | Medical procedures |
| `{{PRESENTING_COMPLAINTS}}` | Complaints with duration |
| `{{GENERAL_EXAM_ADMISSION}}` | General examination on admission |
| `{{CLINICAL_INVESTIGATION_REPORTS}}` | Lab and imaging reports |
| `{{COURSE_IN_HOSPITAL}}` | Course during hospitalization |
| `{{EXAMINATION_ON_DISCHARGE}}` | Examination findings at discharge |
| `{{DISCHARGE_MEDICATIONS}}` | Medications prescribed |
| `{{DIETARY_ADVICE}}` | Diet instructions |
| `{{DISCHARGE_INSTRUCTIONS}}` | Discharge instructions and advice |
| `{{PAIN_MEASUREMENT_SCALE}}` | Pain assessment |
| `{{DRUG_ALLERGY_ADR}}` | Drug allergies and ADR history |
| `{{CO_MORBIDITIES}}` | Co-morbid conditions |
| `{{PERSONAL_HISTORY}}` | Personal history (smoking, alcohol, etc.) |

### Meta Tokens

| Token | Description |
|-------|-------------|
| `{{CURRENT_DATE}}` | Current date (dd-mm-yyyy) |
| `{{PRINT_TIME}}` | Current timestamp |

---

## Template Examples

### Example 1: Blank Template (CI3-like, Just Content)

```html
<!-- Empty template -->
```

**Output:** Only clinical sections (complaints, diagnosis, medications, etc.)  
**No patient table** is shown automatically!

---

### Example 2: Minimal Template with Patient Table Token

```html
{{PATIENT_INFO_TABLE}}
{{CONTENT}}
```

**Output:** Patient demographic table + all clinical sections  
**Closest to CI3 default behavior**

---

### Example 3: Custom Layout with Individual Tokens

```html
<div style="border: 2px solid #333; padding: 20px;">
  <h1 style="text-align: center;">{{H_Name}}</h1>
  <h2 style="text-align: center;">Discharge Summary</h2>
  
  <table style="width: 100%; border-collapse: collapse;">
    <tr>
      <td width="25%"><b>Name:</b></td>
      <td width="25%">{{PATIENT_NAME}}</td>
      <td width="25%"><b>UHID:</b></td>
      <td width="25%">{{UHID}}</td>
    </tr>
    <tr>
      <td><b>Age/Gender:</b></td>
      <td>{{AGE_GENDER}}</td>
      <td><b>IPD No:</b></td>
      <td>{{IPD_CODE}}</td>
    </tr>
    <tr>
      <td><b>Admission:</b></td>
      <td>{{ADMIT_DATE}}</td>
      <td><b>Discharge:</b></td>
      <td>{{DISCHARGE_DATE}}</td>
    </tr>
    <tr>
      <td><b>Phone:</b></td>
      <td>{{PATIENT_PHONE}}</td>
      <td><b>Insurance:</b></td>
      <td>{{INSURANCE_COMPANY}}</td>
    </tr>
    <tr>
      <td><b>Department:</b></td>
      <td>{{DEPARTMENT}}</td>
      <td><b>Doctor(s):</b></td>
      <td>{{DOCTOR_NAMES}}</td>
    </tr>
  </table>
  
  <hr>
  
  {{CONTENT}}
</div>
```

**Output:** Custom-styled discharge with full control over layout, including phone, insurance, and doctor information

---

### Example 4: Selective Sections (Pick What to Show)

```html
<h2>Discharge Summary</h2>

{{PATIENT_INFO_TABLE}}

<div class="clinical-sections">
  {{FINAL_DIAGNOSIS}}
  {{SURGERY}}
  {{DISCHARGE_MEDICATIONS}}
  {{DISCHARGE_INSTRUCTIONS}}
</div>

<!-- Deliberately omitting other sections -->
```

**Output:** Shows ONLY diagnosis, surgery, medications, and instructions  
**Skips** complaints, course, examination, etc.

---

### Example 5: CI3 Chamunda Template Converted to CI4

```html
<style>
  body { font-family: Verdana, Geneva, Tahoma, sans-serif; }
  table { border-collapse: collapse; }
  td { padding: 0.35em; font-size: 10pt; }
</style>

<h2 style="text-align:center; margin:1px; padding:0px;">Discharge Summary</h2>
<hr style="margin:1px; padding:0px;" />

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="150px"><b>Name</b></td>
    <td width="250px">{{PATIENT_NAME}}</td>
    <td width="150px"><b>UHID</b></td>
    <td width="250px">{{UHID}}</td>
  </tr>
  <tr>
    <td width="150px"><b>Age & Gender</b></td>
    <td width="250px">{{AGE_GENDER}}</td>
    <td width="150px"><b>IPD No.</b></td>
    <td width="250px">{{IPD_CODE}}</td>
  </tr>
  <tr>
    <td width="150px"><b>Guardian</b></td>
    <td width="250px">{{GUARDIAN}}</td>
    <td width="150px"><b>Admission</b></td>
    <td width="250px">{{ADMIT_DATE}} {{ADMISSION_TIME}}</td>
  </tr>
  <tr>
    <td width="150px"><b>Phone No.</b></td>
    <td width="250px">{{hospital_phone}}</td>
    <td width="150px"><b>Discharge</b></td>
    <td width="250px">{{DISCHARGE_DATE}} {{DISCHARGE_TIME}}</td>
  </tr>
  <tr>
    <td width="150px"><b>Address</b></td>
    <td width="250px">{{PATIENT_ADDRESS}}</td>
    <td width="150px"><b>Org. Name</b></td>
    <td width="250px">Direct</td>
  </tr>
  <tr>
    <td width="150px"><b>Department</b></td>
    <td width="250px">{{DEPARTMENT}}</td>
    <td width="150px"></td>
    <td width="250px"></td>
  </tr>
</table>
<hr style="margin:1px; padding:0px;" />

{{CONTENT}}
```

**Output:** Exact replica of CI3 Chamunda template using CI4 tokens!

---

## Migration Steps from CI3 to CI4

### Step 1: Convert PHP Template to Token Template

**Old CI3 Template:**
```php
<h2><?= $h1_head ?></h2>
<p>Patient: <?= ucwords($ipd_master[0]->p_fname) ?></p>
<?php if (isset($FinalDiagnosis)) { ?>
  <?= $FinalDiagnosis ?>
<?php } ?>
```

**New CI4 Template:**
```html
<h2>Discharge Summary</h2>
<p>Patient: {{PATIENT_NAME}}</p>
{{FINAL_DIAGNOSIS}}
```

### Step 2: Replace PHP Variables with Tokens

| CI3 Variable | CI4 Token |
|--------------|-----------|
| `$ipd_master[0]->p_fname` | `{{PATIENT_NAME}}` |
| `$ipd_master[0]->p_code` | `{{UHID}}` |
| `$ipd_master[0]->ipd_code` | `{{IPD_CODE}}` |
| `$ipd_master[0]->str_age . ' / ' . $xgender` | `{{AGE_GENDER}}` |
| `$person->mphone1` | `{{PATIENT_PHONE}}` |
| `$ipd_master[0]->ins_company_name` | `{{INSURANCE_COMPANY}}` |
| `$doc_list_main_sign` (doctor names) | `{{DOCTOR_NAMES}}` |
| `$FinalDiagnosis` | `{{FINAL_DIAGNOSIS}}` |
| `$Surgery` | `{{SURGERY}}` |
| `$Procedure` | `{{PROCEDURE}}` |
| `$discharge_complaint` | `{{PRESENTING_COMPLAINTS}}` |
| `$Course_in_the_hospital` | `{{COURSE_IN_HOSPITAL}}` |
| `$Discharge_Medications` | `{{DISCHARGE_MEDICATIONS}}` |
| `$Discharge_Instructions` | `{{DISCHARGE_INSTRUCTIONS}}` |

### Step 3: Remove PHP Conditionals

**Before:**
```php
<?php if (isset($Surgery)) { ?>
  <?= $Surgery ?>
<?php } ?>
```

**After:**
```html
{{SURGERY}}
```

Tokens automatically handle empty values (nothing shown if no data).

### Step 4: Save to Database

1. Go to Template Management UI (if available)
2. **OR** Insert directly into `discharge_template` table:

```sql
INSERT INTO discharge_template (template_name, template_html, is_default, page_size) 
VALUES ('Chamunda Template', '<your converted template HTML>', 1, 'A4');
```

---

## Testing Your Template

### Test Case 1: Blank Template
1. Create template with NO content (empty string)
2. View discharge: `http://localhost:8080/Ipd_discharge/show_discharge/1/1?tpl=1`
3. **Expected:** ONLY clinical sections (no patient table)

### Test Case 2: Just {{CONTENT}}
1. Create template with: `{{CONTENT}}`
2. View discharge
3. **Expected:** ONLY clinical sections (no patient table)

### Test Case 3: Patient Table + Content
1. Create template with: `{{PATIENT_INFO_TABLE}}{{CONTENT}}`
2. View discharge
3. **Expected:** Patient table + clinical sections

### Test Case 4: Custom Layout
1. Create template with individual tokens
2. View discharge
3. **Expected:** Your custom layout with data filled in

---

## Common Issues and Solutions

### Issue 1: Patient table still showing when template is blank

**Cause:** Old cached discharge content in database  
**Solution:** Clear cached content:
```bash
php clear_discharge_content.php [ipd_id]
```

### Issue 2: Token not replaced (shows {{TOKEN_NAME}})

**Cause:** Token name is misspelled or doesn't exist  
**Solution:** Check available tokens list above (case-insensitive)

### Issue 3: Want old CI3 behavior exactly

**Solution:** Use this template:
```html
{{PATIENT_INFO_TABLE}}
{{CONTENT}}
```

### Issue 4: Need to show patient table in some templates but not others

**Solution:** 
- Template A: Include `{{PATIENT_INFO_TABLE}}`
- Template B: Omit it (blank or just `{{CONTENT}}`)

---

## Best Practices

### ✅ DO:
- Use `{{PATIENT_INFO_TABLE}}` for quick CI3-style patient demographics
- Use individual tokens for custom layouts
- Use `{{CONTENT}}` to show all clinical sections at once
- Clear cached content after template changes
- Test with different IPD cases (with/without surgery, delivery, etc.)

### ❌ DON'T:
- Don't expect patient table to auto-appear (it's a token now!)
- Don't use PHP code in templates (use tokens instead)
- Don't manually edit `ipd_discharge.content` column (use regenerate button)
- Don't forget to set `is_default=1` for your primary template

---

## Quick Reference

### Viewing Options

| URL | Description |
|-----|-------------|
| `/Ipd_discharge/show_discharge/{id}/1` | PDF with default template |
| `/Ipd_discharge/show_discharge/{id}/1?tpl=5` | PDF with template ID 5 |
| `/Ipd_discharge/preview_discharge_report/{id}` | Preview in browser |
| `/Ipd_discharge/preview_discharge_report/{id}?tpl=5` | Preview with template ID 5 |
| `/Ipd_discharge/preview_discharge_report/{id}?regen=1` | Force regenerate content |

### Database Tables

- `discharge_template` - Template definitions
- `ipd_discharge` - Generated content (cached)
- `ipd_discharge_complaint` - Complaints data
- `ipd_discharge_diagnosis` - Diagnosis data
- `ipd_discharge_drug` - Medications data
- etc.

---

## Summary

**CI3 Way:**
- Template = PHP file with hardcoded patient table
- Content = Variables injected into template

**CI4 Way (Corrected):**
- Template = Database record with {{TOKENS}}
- Content = ONLY clinical sections (no patient table by default)
- Patient table = Available as `{{PATIENT_INFO_TABLE}}` token

**Result:** Full control over layout, matches CI3 flexibility! 🎉
