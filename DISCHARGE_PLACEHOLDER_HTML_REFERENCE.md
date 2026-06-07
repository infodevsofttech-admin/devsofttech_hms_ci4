# Discharge Summary Placeholder HTML Reference

This document shows the exact HTML structure used for each discharge summary placeholder.

---

## {{DISCHARGE_SUMMARY}}

**Source:** Auto-generated from IPD data  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Discharge Summary</h4>
<!-- OR dynamic header based on ipd_status -->
<h4 style="margin:16px 0 8px 0;">Leave Against Medical Advice</h4>
<div>
    <b>Department:</b> General Surgery<br>
    <b>Treating Doctor(s):</b> Dr. Name [Specialization]<br>
    <b>Date of Admission:</b> 04-06-2026
</div>
```

**Status-based headers:**
- ipd_status = 0 → "Discharge Summary"
- ipd_status = 1 → "Discharge Summary" (from DB)
- ipd_status = 2 → "Leave Against Medical Advice"
- ipd_status = 3 → "Refer To Higher Centre"
- ipd_status = 5 → "Dead Summary"
- ipd_status = 7 → "Discharge on Request"

---

## {{PRESENTING_COMPLAINTS}}

**Source:** `ipd_discharge_complaint` + `ipd_discharge_complaint_remark`  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Presenting Complaints and Reason for Admission</h4>
<ul style="margin:0 0 10px 20px;">
    <li>Complaint 1 <span style="color:#475569;">(Remark)</span></li>
    <li>Complaint 2 <span style="color:#475569;">(Remark)</span></li>
</ul>
<div>Additional remark text from comp_remark field (rich text)</div>
```

---

## {{PAIN_MEASUREMENT_SCALE}}

**Source:** Pain measurement data from complaint metadata  
**HTML Structure:**
```html
<div><b>Pain Measurement Scale:</b> Moderate (5)</div>
```

**If no data:** Empty string (not shown)

---

## {{GENERAL_EXAM_ADMISSION}}

**Source:** Physical examination data  
**HTML Structure:**
```html
<p><b>General Examination on Admission : </b><br/>
BP: 120/80 mmHg, Pulse: 78 bpm, Temp: 98.6°F, Weight: 65 kg
</p>
```

---

## {{PERSONAL_HISTORY}}

**Source:** `patient_master` table personal history flags  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Personal History</h4>
<div>Smoking, Hypertension, Type 2 diabetes mellitus (DM)</div>
```

**Fields checked:**
- is_smoking → "Smoking"
- is_alcohol → "Alcohol"
- is_drug_abuse → "Drug abuse"
- is_tobacoo → "Tobacco"
- is_hypertesion → "Hypertension"
- is_niddm → "Type 2 diabetes mellitus (DM)"
- is_hbsag → "HBsAg"
- is_hcv → "HCV"
- is_hiv_I_II → "HIV I & II"

---

## {{DRUG_ALLERGY_ADR}}

**Source:** OPD history data  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Drug Allergy / ADR</h4>
<div><b>Drug Allergy Status:</b> Known allergies</div>
<div><b>Drug Allergy Details:</b> Penicillin, Aspirin</div>
<div><b>ADR History:</b> Rash after penicillin</div>
<div><b>Current Medications:</b> Metformin 500mg BD</div>
```

**If no data:** Empty string (not shown)

---

## {{CO_MORBIDITIES}}

**Source:** OPD history `co_morbidities` field  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Co-Morbidities</h4>
<div>Diabetes Mellitus, Hypertension, Chronic Kidney Disease</div>
```

---

## {{CLINICAL_INVESTIGATION_REPORTS}}

**Source:** Lab pathology + non-pathology reports  
**HTML Structure:**

### With Pathology Matrix:
```html
<h4 style="margin:16px 0 8px 0;">Clinical Investigation Reports</h4>
<div><b>In-Hospital Lab:</b></div>
<table style="width:100%;border-collapse:collapse;margin:4px 0 8px 0;" border="1" cellpadding="6">
    <tr>
        <th style="text-align:left;">Test</th>
        <th style="text-align:left;">Fixed Normals</th>
        <th style="text-align:left;">23-05-2026</th>
        <th style="text-align:left;">25-05-2026</th>
    </tr>
    <tr>
        <td>Hemoglobin</td>
        <td>12-16 g/dL</td>
        <td>11.5</td>
        <td>13.2</td>
    </tr>
</table>
```

### Non-Pathology Reports:
```html
<div><b>X-Ray / ECG / Sonography / CT / MRI:</b></div>
<ul style="margin:4px 0 8px 20px;">
    <li>[24-05-2026] X-Ray - Chest PA View
        <br><span style="color:#475569;">Impression: Normal study</span>
    </li>
</ul>
```

### Other Examinations:
```html
<div><b>Other Examinations / Provisional Diagnosis:</b><br>
Additional examination notes
</div>
```

---

## {{FINAL_DIAGNOSIS}}

**Source:** `ipd_discharge_diagnosis` + `ipd_discharge_diagnosis_remark`  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Final Diagnosis</h4>
<ul style="margin:0 0 10px 20px;">
    <li>Diagnosis 1 <span style="color:#475569;">(Remark)</span></li>
    <li>Diagnosis 2</li>
</ul>
<div>Additional diagnosis remarks (rich text)</div>
```

---

## {{COURSE_IN_HOSPITAL}}

**Source:** `ipd_discharge_course` + `ipd_discharge_course_remark`  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Course in the hospital</h4>
<ul style="margin:0 0 10px 20px;">
    <li>Patient admitted with complaints of...</li>
    <li>Treatment started with...</li>
</ul>
<div>Additional course remarks (rich text)</div>
```

---

## {{EXAMINATION_ON_DISCHARGE}}

**Source:** Discharge examination data  
**HTML Structure:**
```html
<p><b>Examination on Discharge : </b>
BP: 130/85 mmHg, Pulse: 72 bpm, General condition: Stable
</p>
```

---

## {{SURGERY}}

**Source:** `ipd_discharge_surgery` table  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Surgery</h4>
<table style="width:100%;border-collapse:collapse;margin:4px 0 8px 0;" border="1" cellpadding="6">
    <tr>
        <th style="text-align:left;">Name</th>
        <th style="text-align:left;">Date</th>
        <th style="text-align:left;">Remark</th>
    </tr>
    <tr>
        <td>Laparoscopy</td>
        <td>05-06-2026</td>
        <td>I & D WITH L. N. EXCISION DONE UNDER GA</td>
    </tr>
</table>
```

**Alternative format (if no table structure):**
```html
<h4 style="margin:16px 0 8px 0;">Surgery</h4>
<ul style="margin:0 0 10px 20px;">
    <li>Laparoscopy <span style="color:#475569;">/ Date of Surgery : 2026-06-05</span></li>
</ul>
```

---

## {{PROCEDURE}}

**Source:** `ipd_discharge_procedure` table  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Procedure</h4>
<table style="width:100%;border-collapse:collapse;margin:4px 0 8px 0;" border="1" cellpadding="6">
    <tr>
        <th style="text-align:left;">Name</th>
        <th style="text-align:left;">Date</th>
        <th style="text-align:left;">Remark</th>
    </tr>
    <tr>
        <td>Procedure TEST</td>
        <td>03-06-2026</td>
        <td></td>
    </tr>
</table>
```

---

## {{DISCHARGE_MEDICATIONS}}

**Source:** `ipd_discharge_prescrption_prescribed` or `ipd_discharge_drug`  

### Format 1 (New prescriptions with full details):
```html
<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4>
<table style="width:100%;border-style: inset;;margin-bottom:10px;" border="0" cellpadding="6">
    <tr>
        <th style="width:40px;">#</th>
        <th>Medicine</th>
        <th style="width:90px;">Qty</th>
        <th style="width:100px;">Days</th>
        <th>Notes</th>
    </tr>
    <tr>
        <td>1</td>
        <td>Tab Paracetamol 500mg</td>
        <td>30</td>
        <td>5 days</td>
        <td>1-0-1 After Food</td>
    </tr>
</table>
```

### Format 2 (Simple drug list):
```html
<h4 style="margin:16px 0 8px 0;">Discharge Medications</h4>
<ol style="margin:0 0 10px 20px;">
    <li>Paracetamol <span style="color:#475569;">500mg</span> <span style="color:#475569;">[5 days]</span></li>
    <li>Amoxicillin <span style="color:#475569;">250mg</span> <span style="color:#475569;">[7 days]</span></li>
</ol>
```

---

## {{DIETARY_ADVICE}}

**Source:** Dietary master data selected for patient  
**HTML Structure:**
```html
<div style="margin-bottom:8px;"><strong>Dietary Advice:</strong></div>
<ol style="margin:0 0 10px 20px;">
    <li><strong>High Protein Diet:</strong> Include eggs, chicken, fish, pulses</li>
    <li><strong>Avoid Spicy Food:</strong> No chilli, pepper, masala</li>
    <li><strong>Low Salt:</strong> Reduce salt intake to less than 5g per day</li>
</ol>
```

**If no data:** Empty string (not shown)

---

## {{DISCHARGE_INSTRUCTIONS}}

**Source:** `ipd_discharge_instructions` table  
**HTML Structure:**
```html
<h4 style="margin:16px 0 8px 0;">Discharge Advice/Instructions/Summary</h4>

<!-- Dietary Advice section (if food IDs selected) -->
<div style="margin-bottom:8px;"><strong>Dietary Advice:</strong></div>
<ol style="margin:0 0 10px 20px;">
    <li><strong>Low Fat:</strong> Avoid fried foods</li>
</ol>

<!-- Other Advice (if present) -->
<div style="margin-bottom:8px;"><strong>Other Advice:</strong> Take adequate rest</div>

<!-- Main instructions (rich text from comp_remark) -->
<div>
    Additional discharge instructions and advice text (can contain HTML formatting)
</div>

<!-- Review After (if present) -->
<div style="margin-top:6px;">Review after 7 Days (14-06-2026) days / as and when required</div>

<!-- Footer text (if present) -->
<div style="margin-top:6px;">Emergency contact information</div>
```

---

## {{SIGNATURE_BLOCK}}

**Source:** Auto-generated signature table  
**HTML Structure:**
```html
<table border="0" cellpadding="1" cellspacing="1" style="width:100%">
    <tbody>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr>
            <td style="text-align:left; vertical-align:middle">_________________________</td>
            <td>_________________________</td>
            <td style="text-align:right; vertical-align:middle">_________________________</td>
        </tr>
        <tr>
            <td style="text-align:center; vertical-align:middle">Signature of Consultant</td>
            <td style="text-align:center; vertical-align:middle">Signature of Medical Officer</td>
            <td style="text-align:center; vertical-align:middle">Signature of Receiver / Date</td>
        </tr>
    </tbody>
</table>
```

---

## Additional Placeholders (Not in Section Content)

### {{PATIENT_INFO_TABLE}}

**Source:** `buildAutoDischargeSummaryTable()`  
**HTML Structure:**
```html
<h2 style="text-align:center;margin:1px;padding:0px;">Discharge Summary</h2>
<!-- OR dynamic header based on ipd_status -->
<h2 style="text-align:center;margin:1px;padding:0px;">Leave Against Medical Advice</h2>

<hr style="margin:1px;padding:0px;" />
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td width="150px"><b>Name</b></td>
        <td width="250px">SUNITA DEVI</td>
        <td width="150px"><b>UHID</b></td>
        <td width="250px">P26061331171</td>
    </tr>
    <tr>
        <td width="150px"><b>Age & Gender</b></td>
        <td width="250px">40 Year / Female</td>
        <td width="150px"><b>IPD No.</b></td>
        <td width="250px">A26061007144</td>
    </tr>
    <tr>
        <td width="150px"><b>Guardian</b></td>
        <td width="250px">Wife of JOGENDRA KUMAR</td>
        <td width="150px"><b>Admission</b></td>
        <td width="250px">04-06-2026</td>
    </tr>
    <tr>
        <td width="150px"><b>Phone No.</b></td>
        <td width="250px">9520497324</td>
        <td width="150px"><b>Discharge</b></td>
        <td width="250px">07-06-2026</td>
    </tr>
    <tr>
        <td width="150px"><b>Address</b></td>
        <td width="250px">MALDHAN*****</td>
        <td width="150px"><b>Org. Name</b></td>
        <td width="250px">Direct</td>
    </tr>
    <tr>
        <td width="150px"><b>Department</b></td>
        <td width="250px">General Surgery</td>
        <td width="150px"></td>
        <td width="250px"></td>
    </tr>
</table>
<hr style="margin:1px;padding:0px;" />
```

---

## Style Guidelines

### Common CSS Classes Used:
- `margin:16px 0 8px 0` - Standard heading margins
- `margin:0 0 10px 20px` - List indentation
- `color:#475569` - Secondary/remark text color
- `border-collapse:collapse` - Table borders
- `text-align:left` - Default text alignment
- `vertical-align:middle` - Table cell alignment

### HTML Tags Used:
- `<h4>` - Section headers (16px top margin, 8px bottom)
- `<h2>` - Main title headers
- `<div>` - Content blocks
- `<ul>` / `<ol>` - Lists
- `<table>` - Structured data
- `<b>` / `<strong>` - Bold text
- `<span>` - Inline styling (remarks)
- `<br>` - Line breaks

---

## Debug Endpoints

To view the actual rendered HTML:

1. **View full HTML source:**
   ```
   /Ipd_discharge/debug_discharge_html/{ipdId}
   ```
   Example: `http://localhost:8080/Ipd_discharge/debug_discharge_html/1`

2. **View database field values:**
   ```
   /Ipd_discharge/debug_ipd_fields/{ipdId}
   ```
   Example: `http://localhost:8080/Ipd_discharge/debug_ipd_fields/1`

---

## Notes

- All placeholders support both `{{TOKEN}}` and `{TOKEN}` formats
- All placeholders also support lowercase and Ucfirst variants
- Empty sections are NOT rendered (no HTML output)
- Rich text fields preserve HTML formatting from CKEditor
- All user input is escaped using `esc()` for XSS protection
- Date formats: `dd-mm-yyyy` (e.g., 07-06-2026)
- Time formats: `hh:mm AM/PM` (e.g., 02:44 PM)

---

**Generated:** 2026-06-07  
**File Location:** `d:\Workplace\HMS_CI4_OLD\DISCHARGE_PLACEHOLDER_HTML_REFERENCE.md`
