# HMS Clinical & Medical Record Standards

This document defines the clinical data model, doctor naming conventions, laboratory reporting, radiology reporting, and electronic medical record (EMR) rules for the E-Atria HMS.

---

## 1. Demographic & Clinical Name Rules

1. **Patient Full Name Standard (`patient_master.p_fname`)**:
   - The patient's full name is stored strictly and exclusively in `patient_master.p_fname`.
   - The column `patient_master.p_lname` has been permanently dropped from the database.
   - **NEVER** use, select, insert, update, or reference `patient_master.p_lname` anywhere in controllers, models, views, or queries.

2. **Doctor Name Standard (`doctor_master`)**:
   - For doctors, the database retains multi-part names: `p_title`, `p_fname`, `p_mname`, `p_lname`.
   - When displaying a doctor's full name on UI, prescription prints, or reports:
     ```php
     $doctorFullName = trim(($doctor->p_title ?? 'Dr.') . ' ' . trim(($doctor->p_fname ?? '') . ' ' . ($doctor->p_mname ?? '') . ' ' . ($doctor->p_lname ?? '')));
     ```
   - Always display qualification (`doc_sign`) and medical council registration number (`doctor_reg_no`) on all prescription and diagnostic report footers.

---

## 2. Clinical Terminology & Code Mappings

1. **SNOMED CT Coding**:
   - All chief complaints, physical findings, and diagnoses recorded in OPD consultation must link to SNOMED CT Concept IDs whenever available.
   - The local CSNOTK bridge is configured at `snomed.csnotk.baseUrl` in `.env`.

2. **LOINC Coding for Pathology**:
   - All tests in `lab_tests` should have corresponding LOINC codes mapped in `lab_tests.loinc_num` or `lab_tests.loinc_code`.
   - When generating NRCeS FHIR Document Bundles (DiagnosticReport), LOINC is the mandatory primary coding system.

3. **Ayushman Bharat & TPA Packages**:
   - Standard package procedures must map to `ayushman_package_master` (1,401 standardized PM-JAY packages).

---

## 3. Diagnostic & Imaging Workflow Integrity

1. **Pathology Lifecycle**:
   $$\text{Test Prescribed} \longrightarrow \text{Sample Collected} \longrightarrow \text{Accession/Barcode} \longrightarrow \text{Result Entry} \longrightarrow \text{Doctor Verification} \longrightarrow \text{Published Report}$$
   - No diagnostic report can be released to a patient portal or ABDM bridge without formal verification (`verified_by` / `doctor_sign`).
   - Out-of-range critical/panic values must be visually highlighted in bold red with critical alert indicators.

2. **Radiology Lifecycle**:
   $$\text{Scan Ordered} \longrightarrow \text{Procedure Completed} \longrightarrow \text{Drafting Findings} \longrightarrow \text{Impression} \longrightarrow \text{Radiologist Sign-off}$$
   - Templates stored in `doc_print_templates` / `radiology_ultrasound_template` must preserve standardized headings:
     1. Clinical Indication / History
     2. Modality & Protocol
     3. Observations / Findings
     4. Impression / Conclusion
     5. Advice / Recommendations
