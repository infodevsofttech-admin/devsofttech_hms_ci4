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

