# ABDM Integration Rules & Standards

## Rule 1: Care Context & Patient Reference Formatting in ABDM Linking Payloads

### Background & PHR Table Display Behavior
In the ABDM PHR app (`phrsbx.abdm.gov.in` and official PHR apps), the records table renders the payload's `patient[].referenceNumber` field directly under the column header:
**`Patient reference` | `Health Document Type` | `Date`**

### Strict Constraint
1. **NEVER** set or overwrite `patient[].referenceNumber` with the internal hospital UHID or patient code (e.g. `P26081015350`).
2. **ALWAYS** set `patient[].referenceNumber` to the exact Care Context Reference (e.g. `PRESC-15350-S33765-20261023`, `OPD-15350-S33765-20261023`).
3. If linking multiple care contexts in a single request, provide each care context with its own item in the `patient[]` array so each record displays its unique reference number in the PHR app.

### Canonical Care Context Reference Standard
- **Prescription**: `PRESC-{patient_id}-S{session_id}-{YYYYMMDD}`
- **OPD Consultation**: `OPD-{patient_id}-S{session_id}-{YYYYMMDD}`
- **Discharge Summary**: `DISCHARGE-{ipd_id}-{YYYYMMDD}`
- **Diagnostic Lab**: `LAB-{patient_id}-{test_id}-{YYYYMMDD}`
- **Diagnostic Radiology**: `RAD-{patient_id}-{rad_id}-{YYYYMMDD}`
- **Invoice**: `INVOICE-OPD-{opd_id}-{YYYY-MM-DD}` or `INVOICE-CHG-{charge_id}-{YYYY-MM-DD}`
- **Wellness**: `WELLNESS-{patient_id}-{YYYYMMDD}`
- **Immunization**: `IMM-{patient_id}-{vaccine_id}-{YYYYMMDD}`

---

## Rule 2: Never Overwrite Structured References in Code
In `app/Controllers/AbdmGateway.php` and the bridge gateway:
- Do NOT use regex to detect `OPD-`, `PRESC-`, etc., and replace them with `p_code`.
- If a caller supplies a structured care context reference as `patient_ref`, keep it intact.
- Only fall back to `p_code` or `P-{id}` if no care context reference is provided or available.

---

## Rule 3: FHIR Document Generation Standards
- All clinical FHIR documents must conform to NRCeS FHIR R4 profiles.
- Entry[0] must always be the `Composition` resource.
- Use valid LOINC codes for composition type and section codes.
- Use SNOMED CT for clinical diagnoses, findings, and conditions.
- Refer to `docs/abdm/CARE_CONTEXT_AND_FHIR_STANDARDS.md` for full implementation specifications.

---

## Rule 4: Patient Name Handling (`patient_master.p_fname`)
- Patient full name is stored strictly in `patient_master.p_fname`.
- The column `patient_master.p_lname` has been permanently dropped and deleted from `patient_master` in all databases.
- NEVER query, select, join, insert, or reference `patient_master.p_lname` in any controllers, models, queries, or FHIR generators.
- When generating FHIR Patient or Composition resources, map the patient's full name directly from `patient_master.p_fname`.
- Note: Doctors in `doctor_master` continue to use `p_fname`, `p_mname`, and `p_lname`.

