# NABH Hospital Accreditation Compliance Rules

This document specifies the NABH (National Accreditation Board for Hospitals & Healthcare Providers, 5th Edition) standards embedded in the E-Atria HMS architecture.

---

## 1. 13 Mandatory Statutory Consent Forms

All IPD admissions and specialized clinical procedures must record explicit, informed patient consent before interventions:

| Consent Form ID | Title | Key Mandatory Requirement |
| :--- | :--- | :--- |
| `NABH-IPD-01` | Admission Request & Initial Assessment Form | Demographics, provisional diagnosis, treating consultant, signature within 24h |
| `NABH-IPD-02` | General Informed Consent for Medical Treatment | Risks, benefits, hospital policies, patient/attendant signature |
| `NABH-IPD-03` | Informed Consent for Surgical Procedure / Operation | Operating surgeon, specific procedure name, alternatives, anesthesia type |
| `NABH-IPD-04` | Informed Consent for Administration of Anesthesia | General / Spinal / Epidural / Regional, fasting status, ASA grade |
| `NABH-IPD-05` | High-Risk Informed Consent | Explicit documentation of mortality risk, ICU requirement, signed by 2 attendants |
| `NABH-IPD-06` | Blood & Blood Component Transfusion Consent | PRBC, FFP, Platelets, adverse reaction warning, refusal rights |
| `NABH-IPD-07` | ICU / HDU Admission Informed Consent | Critical care protocols, invasive monitoring, ventilator consent |
| `NABH-IPD-08` | Discharge Against Medical Advice (DAMA / LAMA) | Declaration of voluntary discharge against advice, liability waiver |
| `NABH-IPD-09` | Consent for Invasive Diagnostic / Bedside Procedure | Endoscopy, Lumbar puncture, CVC, Pleural tap |
| `NABH-IPD-10` | Refusal of Medical Treatment / Procedure Consent | Explicit informed refusal documenting medical risks of refusal |
| `NABH-IPD-11` | Chemotherapy / Dialysis Specialized Consent | Specific drug protocols, cumulative toxicity risks |
| `NABH-IPD-12` | Restraint Application Consent | Physical/chemical restraint reason, alternative trials, periodic review |
| `NABH-IPD-13` | Release of Medical Records / Photography Consent | Confidentiality waiver, medico-legal or insurance release |

---

## 2. Inpatient Clinical Timelines & Standards

1. **Initial Assessment Timeline**:
   - Initial Medical Assessment by doctor must be completed within **24 hours of admission** (or immediately for emergency/ICU admissions).
   - Initial Nursing Assessment must be completed within **2 to 4 hours of admission**.

2. **Daily Clinical Progress Notes (SOAP Format)**:
   - Every inpatient must have daily dated and timed notes adhering to:
     - **S (Subjective)**: Patient complaints, comfort, pain score (1-10).
     - **O (Objective)**: Vitals (BP, PR, RR, SpO2, Temp), systemic physical examination, lab/imaging review.
     - **A (Assessment)**: Current clinical status and diagnosis evolution.
     - **P (Plan)**: Medication changes, investigations ordered, diet, discharge planning.

3. **Discharge Summary Mandatory Criteria**:
   - Under NABH, a discharge summary MUST contain:
     1. Admission & Discharge Dates & Times.
     2. Primary & Secondary Final Diagnoses (with ICD-10 codes).
     3. Significant Clinical History & Physical Findings on admission.
     4. Results of Significant Diagnostic Tests & Interventions.
     5. Summary of Hospital Course & Treatment Provided.
     6. Condition of the Patient at Discharge.
     7. Discharge Medications (generic name, strength, dosage, route, frequency, duration).
     8. Urgent Warning Signs / Red Flags necessitating immediate emergency return.
     9. Specific Follow-up Date and Consulting Doctor Name.

4. **Digital Scanning & Retention (`nabh_ipd_scans`)**:
   - Physical paper consent forms and bedside charts scanned into the system are stored in `nabh_ipd_scans` with proper categorization (`NABH-IPD-01` to `13`).
   - Scanned records must be retained in accordance with the hospital's medico-legal retention period (minimum 3 years for adult IPD, till age of majority + 3 years for pediatric cases).
