# Discharge Summary Template Placeholders Reference

## Overview
This document explains all available placeholders for discharge summary templates. Use these placeholders in your template HTML to insert dynamic content.

---

## 🔴 MAIN DISCHARGE SUMMARY

### `{{CLINICAL_SUMMARY}}` ⭐ RECOMMENDED
**Content:** The main discharge summary text from the HTML editor (instruction_remark field)  
**Source:** "Discharge Summary" section in the discharge form  
**Use this for:** The primary clinical narrative written by doctors

**Example:**
```html
<h2>Discharge Summary</h2>
{{CLINICAL_SUMMARY}}
```

### `{{DISCHARGE_SUMMARY}}` (Legacy)
**Content:** Same as `{{CLINICAL_SUMMARY}}`  
**Note:** Kept for backward compatibility. Use `{{CLINICAL_SUMMARY}}` for clarity.

---

## 🔵 CLINICAL SECTIONS

### `{{FINAL_DIAGNOSIS}}`
**Content:** Final diagnosis section  
**Source:** "Final Diagnosis" section

### `{{SURGERY}}`
**Content:** Surgery details section  
**Source:** "Surgery" section

### `{{PROCEDURE}}`
**Content:** Procedure details section  
**Source:** "Procedure" section

### `{{PRESENTING_COMPLAINTS}}`
**Content:** Presenting complaints and reason for admission  
**Source:** "Presenting Complaints and Reason for Admission" section

### `{{PERSONAL_HISTORY}}`
**Content:** Patient personal history  
**Source:** "Personal History" section

### `{{GENERAL_EXAM_ADMISSION}}`
**Content:** General examination findings at admission  
**Source:** "General Examination on Admission" section

### `{{CLINICAL_INVESTIGATION_REPORTS}}`
**Content:** Clinical investigation and lab test reports  
**Source:** "Clinical Investigation Reports" section

### `{{COURSE_IN_HOSPITAL}}`
**Content:** Course/progress during hospitalization  
**Source:** "Course in the hospital" section

### `{{EXAMINATION_ON_DISCHARGE}}`
**Content:** Examination findings at discharge  
**Source:** "Examination on Discharge" section

### `{{PAIN_MEASUREMENT_SCALE}}`
**Content:** Pain assessment scale details  
**Source:** "Pain Measurement Scale" section

### `{{DRUG_ALLERGY_ADR}}`
**Content:** Drug allergies and adverse drug reactions  
**Source:** "Drug Allergy / ADR" section

### `{{CO_MORBIDITIES}}`
**Content:** Co-existing medical conditions  
**Source:** "Co-Morbidities" section

---

## 🟢 DISCHARGE INSTRUCTIONS & ADVICE

### `{{DISCHARGE_MEDICATIONS}}`
**Content:** Medications prescribed at discharge  
**Source:** "Discharge Medications" section

### `{{DIETARY_ADVICE}}`
**Content:** Dietary recommendations  
**Source:** "Dietary Advice" section

### `{{DISCHARGE_INSTRUCTIONS}}`
**Content:** General discharge advice and instructions  
**Source:** "Discharge Advice/Instructions/Summary" section

### `{{OTHER_ADVICE}}`
**Content:** Other miscellaneous advice  
**Source:** "Other Advice:" section

### `{{FOLLOW_UP_INSTRUCTIONS}}`
**Content:** Follow-up appointment instructions  
**Source:** "Discharge Summary:" subsection (note the colon)

---

## 🟡 OTHER SECTIONS

### `{{SIGNATURE_BLOCK}}`
**Content:** Signature table with consultant details  
**Source:** "Signature of Consultant" section

---

## 📋 PATIENT INFO PLACEHOLDERS

Use `{{PATIENT_INFO_TABLE}}` for the complete patient information header table.

---

## 💡 RECOMMENDED TEMPLATE STRUCTURE

```html
<!-- Patient Information -->
{{PATIENT_INFO_TABLE}}

<!-- Main Clinical Summary -->
<h2>Clinical Summary</h2>
{{CLINICAL_SUMMARY}}

<!-- Clinical Sections -->
{{PRESENTING_COMPLAINTS}}
{{PAIN_MEASUREMENT_SCALE}}
{{DRUG_ALLERGY_ADR}}
{{CO_MORBIDITIES}}
{{GENERAL_EXAM_ADMISSION}}
{{PERSONAL_HISTORY}}
{{CLINICAL_INVESTIGATION_REPORTS}}

<!-- Diagnosis & Treatment -->
{{FINAL_DIAGNOSIS}}
{{SURGERY}}
{{PROCEDURE}}
{{COURSE_IN_HOSPITAL}}
{{EXAMINATION_ON_DISCHARGE}}

<!-- Discharge Instructions -->
{{DISCHARGE_MEDICATIONS}}
{{DIETARY_ADVICE}}
{{OTHER_ADVICE}}
{{DISCHARGE_INSTRUCTIONS}}
{{FOLLOW_UP_INSTRUCTIONS}}

<!-- Signature -->
{{SIGNATURE_BLOCK}}
```

---

## ⚠️ IMPORTANT NOTES

1. **Empty Sections:** If a section has no data, the placeholder will be replaced with empty string (won't show in PDF)

2. **HTML Formatting:** Content from HTML editors (like `{{CLINICAL_SUMMARY}}`) preserves formatting (bold, lists, etc.)

3. **Placeholder Case:** Placeholders are case-sensitive. Use UPPERCASE with underscores.

4. **Missing Placeholders:** If you forget a placeholder, that content simply won't appear in the PDF

5. **Duplicate Placeholders:** You can use the same placeholder multiple times in a template if needed

---

## 🔄 MIGRATION GUIDE

### Old Placeholder → New Placeholder

| Old (Confusing) | New (Clear) | Notes |
|----------------|-------------|-------|
| `{{DISCHARGE_SUMMARY}}` | `{{CLINICAL_SUMMARY}}` | Both work, but use `{{CLINICAL_SUMMARY}}` for clarity |
| `{{INSTRUCTION_REMARK}}` | `{{FOLLOW_UP_INSTRUCTIONS}}` | Old placeholder removed |
| `{{DISCHARGE_ADVICE}}` | `{{FOLLOW_UP_INSTRUCTIONS}}` | More descriptive name |

### What Changed?

**Before (Confusing):**
- `{{DISCHARGE_SUMMARY}}` - Main clinical summary
- `{{DISCHARGE_ADVICE}}` - Follow-up instructions
- `{{INSTRUCTION_REMARK}}` - Same as DISCHARGE_ADVICE

**After (Clear):**
- `{{CLINICAL_SUMMARY}}` - Main clinical summary ⭐ Use this!
- `{{FOLLOW_UP_INSTRUCTIONS}}` - Follow-up instructions
- `{{DISCHARGE_INSTRUCTIONS}}` - General discharge advice

---

## 📝 EXAMPLE TEMPLATES

### Minimal Template
```html
{{PATIENT_INFO_TABLE}}
{{CLINICAL_SUMMARY}}
{{FINAL_DIAGNOSIS}}
{{DISCHARGE_MEDICATIONS}}
{{SIGNATURE_BLOCK}}
```

### Comprehensive Template
```html
{{PATIENT_INFO_TABLE}}

<h2>Clinical Summary</h2>
{{CLINICAL_SUMMARY}}

{{PRESENTING_COMPLAINTS}}
{{GENERAL_EXAM_ADMISSION}}
{{CLINICAL_INVESTIGATION_REPORTS}}
{{FINAL_DIAGNOSIS}}
{{SURGERY}}
{{PROCEDURE}}
{{COURSE_IN_HOSPITAL}}
{{EXAMINATION_ON_DISCHARGE}}

<h2>Discharge Instructions</h2>
{{DISCHARGE_MEDICATIONS}}
{{DIETARY_ADVICE}}
{{OTHER_ADVICE}}
{{FOLLOW_UP_INSTRUCTIONS}}

{{SIGNATURE_BLOCK}}
```

---

## 🆘 TROUBLESHOOTING

**Problem:** Discharge summary text not appearing in PDF  
**Solution:** Make sure you have `{{CLINICAL_SUMMARY}}` or `{{DISCHARGE_SUMMARY}}` in your template

**Problem:** Section appears empty in PDF  
**Solution:** Check if that section was filled in the discharge form. Empty sections = empty output.

**Problem:** Formatting looks wrong  
**Solution:** Check your template HTML structure. Each placeholder inserts its content with existing HTML tags.

---

## 📞 SUPPORT

For template editing issues, check:
1. Template management page (ensure placeholders are spelled correctly)
2. HTML debug mode: `/Ipd_discharge/show_discharge/{ipdId}/1?html=1&tpl=3`
3. Content debug: `/Ipd_discharge/debug_discharge_html/{ipdId}`

---

**Last Updated:** 2026-06-11  
**Version:** 2.0 (Simplified naming)
