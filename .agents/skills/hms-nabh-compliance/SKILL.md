---
name: hms-nabh-compliance
description: Use when building, testing, or auditing hospital workflows for NABH (National Accreditation Board for Hospitals & Healthcare Providers) compliance, including the 13 statutory informed consent forms, initial medical/nursing assessments, clinical audit criteria, SBAR nursing handovers, and discharge summary quality checks.
---

# HMS NABH Accreditation & Clinical Governance Skill

This skill guides the agent through implementing, maintaining, and auditing hospital processes against **NABH (5th Edition)** hospital accreditation standards in E-Atria HMS.

---

## 1. 13 Statutory Informed Consent Templates

NABH mandates that all procedures, admissions, high-risk interventions, and surgical operations have documented informed consent signed by the patient (or legal guardian) and doctor.

The 13 statutory templates are seeded in the database via `App\Database\Seeds\Data\IpdDocumentSeedData`:

| Code | Template Title | Key NABH Mandatory Fields |
| :--- | :--- | :--- |
| `NABH-IPD-01` | Admission Request & Initial Assessment Form | Provisional diagnosis, primary consultant, vitals, allergy history, patient/attendant declaration |
| `NABH-IPD-02` | General Informed Consent for Medical Treatment | Explanation of treatment plan, potential risks, rights of patient, attendant signature |
| `NABH-IPD-03` | Informed Consent for Surgical Procedure / Operation | Named procedure, operating surgeon, alternative therapies explained, possibility of conversion/extension |
| `NABH-IPD-04` | Informed Consent for Administration of Anesthesia | General / Spinal / Epidural / Regional / Sedation, ASA physical status grade, fasting hours confirmed |
| `NABH-IPD-05` | High-Risk Informed Consent | Documented severe comorbidities, ICU requirement, explicit mortality risk disclosure, two witness signatures |
| `NABH-IPD-06` | Blood & Blood Component Transfusion Consent | PRBC / FFP / Platelets / Cryoprecipitate, adverse reaction disclosure, right to refuse |
| `NABH-IPD-07` | ICU / HDU Admission Informed Consent | Critical care management, central venous line, invasive arterial monitoring, mechanical ventilation |
| `NABH-IPD-08` | Discharge Against Medical Advice (DAMA / LAMA) | Declaration acknowledging refusal of medical advice, clinical risks of premature discharge, legal indemnity waiver |
| `NABH-IPD-09` | Consent for Bedside / Invasive Diagnostic Procedure | Pleural tap, Ascitic tap, Lumbar puncture, Endoscopy, Bone marrow biopsy |
| `NABH-IPD-10` | Refusal of Treatment / Specific Procedure Consent | Informed refusal documentation detailing potential health deterioration resulting from refusal |
| `NABH-IPD-11` | Chemotherapy / Specialized Medical Therapy Consent | Chemotherapeutic agents, cycle number, cumulative toxicity, extravasation risk |
| `NABH-IPD-12` | Restraint Application Consent | Physical / chemical restraint indication, patient safety rationale, alternative interventions attempted |
| `NABH-IPD-13` | Release of Medical Information / Records Consent | Third-party insurance / TPA disclosure authorization, medico-legal release |

---

## 2. Inpatient Clinical Care Standards (COP - Care of Patients)

1. **Initial Assessment Timelines**:
   - **Doctor Assessment**: Must be completed within **24 hours** of admission (or immediately in Emergency/ICU). Contains past history, allergies, personal history, systemic examination, provisional diagnosis, and initial care plan.
   - **Nursing Assessment**: Must be completed within **2 to 4 hours** of admission. Contains nutritional screening, fall risk assessment (Morse Fall Scale / Hendrich II), pressure ulcer risk assessment (Braden Scale), and pain assessment.
2. **Nursing Handover Protocol (SBAR)**:
   - Shift handovers must adhere to the **SBAR Framework**:
     - **S (Situation)**: Patient name, age, bed no, admitting consultant, current diagnosis.
     - **B (Background)**: Admission date, surgical history, known allergies, baseline vitals.
     - **A (Assessment)**: Current shift vitals, pain status, abnormal lab values, pending reports.
     - **R (Recommendation)**: Pending doctor orders, next medication doses, scheduled scans.
3. **Medication Administration & Safety (MOM)**:
   - High-alert medications (e.g. Concentrated Electrolytes, Insulins, Anticoagulants, Chemotherapy) must require **double-nursing check** before administration.
   - Look-Alike Sound-Alike (LASA) drugs must be segregated with tall-man lettering.

---

## 3. Discharge Summary Quality Standard (AAC - Access, Assessment & Continuity of Care)

Under NABH, a discharge summary is a medico-legal document. It must be handed over to the patient at the time of discharge and contain:
1. Hospital UHID, IPD Number, Admission Date & Time, Discharge Date & Time.
2. Admitting Consultant, Department, and Operating Surgeon (if surgical).
3. Primary & Secondary Final Diagnoses with ICD-10 codes.
4. Reason for Admission & Brief Clinical History.
5. Key Physical Findings on Admission.
6. Summary of Pertinent Laboratory, Imaging & Diagnostic Reports.
7. Detailed Hospital Course, Surgical Procedures with Operative Findings.
8. Patient's Condition at Discharge (Vitals, Wound Status, Ambulation).
9. Discharge Medication Chart (Generic Name, Strength, Dosage, Route, Frequency, Duration).
10. Urgent Warning Signs / Emergency Red Flags (e.g., severe chest pain, breathlessness, sudden high fever, wound bleeding).
11. Scheduled Follow-up Date, Time, and OPD Room / Consultant Name.
12. Treating Consultant Doctor's Name, Qualifications, and Signature.

---

## 4. Digital Scans & Document Archiving (`nabh_ipd_scans`)

- Physical signed consent forms, outside lab reports, and signed anesthetic fitness sheets uploaded via `DoctorDocument.php` are stored in `nabh_ipd_scans`.
- Categorized strictly by standard NABH code (`NABH-IPD-01` to `13`).
- Linked to `ipd_id`, `patient_id`, `uploaded_by`, and timestamped for clinical audit trail.
