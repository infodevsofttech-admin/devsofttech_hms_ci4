# Developer & Agent Guidelines

## ABDM Standards
For all ABDM (Ayushman Bharat Digital Mission) development, linking, Care Context management, and FHIR generation:
- Strictly follow the standards defined in [.agents/rules/abdm_standards.md](file:///d:/Workplace/HMS_CI4_OLD/.agents/rules/abdm_standards.md)
- Full documentation and API contract explanations: [docs/abdm/CARE_CONTEXT_AND_FHIR_STANDARDS.md](file:///d:/Workplace/HMS_CI4_OLD/docs/abdm/CARE_CONTEXT_AND_FHIR_STANDARDS.md)

### Key Rules at a Glance:
1. **Never set `patient[].referenceNumber` to internal hospital UHID (e.g. `P26081015350`).**  
   The ABDM PHR app displays this field under its "Patient reference" table column. It MUST always be set to the specific care context reference (`PRESC-{patient_id}-S{session_id}-{YYYYMMDD}`, `OPD-{patient_id}-S{session_id}-{YYYYMMDD}`, etc.).
2. **Never overwrite structured references with `p_code` in `AbdmGateway.php`.**
3. **All FHIR bundles must conform to NRCeS FHIR R4 document bundle specifications.**
4. **Patient Full Name Standard (`patient_master.p_fname`):**
   - The patient's full name is stored strictly in `patient_master.p_fname`.
   - The column `patient_master.p_lname` has been permanently dropped from the database table.
   - **DO NOT** use, select, update, or reference `patient_master.p_lname` anywhere in the application.
   - Note: For doctors, `doctor_master` continues to use `p_fname`, `p_mname`, and `p_lname`.

## HMS Core & Clinical Standards
For all Hospital Management System (HMS) development, billing, diagnostics, inpatient care, and NABH compliance:
- **Billing Standards**: Follow [.agents/rules/hms_billing_standards.md](file:///d:/Workplace/HMS_CI4_OLD/.agents/rules/hms_billing_standards.md) (Net = Total - Discount + Tax; Paid must equal sum of `payment_history`).
- **Clinical Standards**: Follow [.agents/rules/hms_clinical_standards.md](file:///d:/Workplace/HMS_CI4_OLD/.agents/rules/hms_clinical_standards.md) (LOINC for pathology, SNOMED CT for OPD, 5-section radiology structure).
- **NABH Standards**: Follow [.agents/rules/nabh_compliance_rules.md](file:///d:/Workplace/HMS_CI4_OLD/.agents/rules/nabh_compliance_rules.md) (13 statutory consent forms `NABH-IPD-01` to `13`, 24h initial assessment, SOAP daily rounds, 11-point discharge summary checklist).

### HMS Module Skills Available:
- [`hms-billing`](file:///d:/Workplace/HMS_CI4_OLD/.agents/skills/hms-billing/SKILL.md) — OPD, IPD, Diagnostics, Pharmacy billing and ledger integrity.
- [`hms-pathology`](file:///d:/Workplace/HMS_CI4_OLD/.agents/skills/hms-pathology/SKILL.md) — Lab tests, panels, reference ranges, barcode workflow, and report publishing.
- [`hms-radiology`](file:///d:/Workplace/HMS_CI4_OLD/.agents/skills/hms-radiology/SKILL.md) — Imaging modalities, structured reporting, findings, and impressions.
- [`hms-ipd`](file:///d:/Workplace/HMS_CI4_OLD/.agents/skills/hms-ipd/SKILL.md) — Inpatient admission, bed management, SOAP notes, and discharge workflows.
- [`hms-nabh-compliance`](file:///d:/Workplace/HMS_CI4_OLD/.agents/skills/hms-nabh-compliance/SKILL.md) — NABH 5th edition consent forms, nursing handovers, and clinical governance.


