# ABDM Care Context Reference & FHIR Standards Guide

> **CRITICAL STANDARD FOR DEVELOPERS & AI ASSISTANTS**  
> This document defines the canonical reference formatting, payload structure, and FHIR standards for all ABDM integrations in this hospital management system (HMS) and the ABDM Bridge Gateway. Any code modifications touching care contexts, linking APIs (M1, M2, M3), or FHIR bundles **must** strictly adhere to this specification.

---

## 1. Executive Summary: The PHR UI Table Behavior

In the official ABDM PHR App (`phrsbx.abdm.gov.in` and production ABHA apps), when a user opens their linked records from a facility, the records are displayed in a table with the following headers:

| Patient reference | Health Document Type | Date |
| :--- | :--- | :--- |
| `PRESC-15350-S33765-20261023` | Prescription | 23-October-2026 |
| `OPD-15350-S33765-20261023` | OPConsultation | 23-October-2026 |
| `PRESC-15350-S33764-20261022` | Prescription | 22-October-2026 |

### Why `Patient reference` Displays the Care Context Reference
In ABDM's linking API specification (`/v3/hip/link/carecontext`, `/v0.5/links/link/add-contexts`, and user-initiated `/v3/hip/link/patient/care-context/confirm`):
```json
{
  "patient": [
    {
      "referenceNumber": "PRESC-15350-S33765-20261023",
      "display": "Devender Singh",
      "careContexts": [
        {
          "referenceNumber": "PRESC-15350-S33765-20261023",
          "display": "Prescription - 2026-10-23"
        }
      ],
      "hiType": "Prescription",
      "count": 1
    }
  ]
}
```

> [!CAUTION]
> **DO NOT SET `patient[].referenceNumber` TO INTERNAL HOSPITAL UHID (`P26081015350`)!**  
> If `patient[].referenceNumber` is populated with the internal UHID/patient code (`P26081015350`), the PHR application will display `P26081015350` in the "Patient reference" column instead of the distinct record reference.
>
> **MANDATORY RULE:**  
> In all ABDM linking payloads (both M1 HIP-initiated and M2 user-initiated confirm), `patient[].referenceNumber` **MUST** be set to the care context reference (`PRESC-...`, `OPD-...`, `INVOICE-...`, `DISCHARGE-...`). When linking multiple contexts, create an individual patient block per care context so each record renders its exact reference number in the PHR app.

---

## 2. Canonical Care Context Reference Formats

All care context references must follow these deterministic formats:

| HI Type | Prefix | Format Pattern | Example |
| :--- | :--- | :--- | :--- |
| **Prescription** | `PRESC-` | `PRESC-{patient_id}-S{session_id}-{YYYYMMDD}` | `PRESC-15350-S33765-20261023` |
| **OPD Consultation** | `OPD-` | `OPD-{patient_id}-S{session_id}-{YYYYMMDD}` | `OPD-15350-S33765-20261023` |
| **Discharge Summary** | `DISCHARGE-` | `DISCHARGE-{ipd_id}-{YYYYMMDD}` | `DISCHARGE-450-20261023` |
| **Diagnostic Lab** | `LAB-` | `LAB-{patient_id}-{test_id}-{YYYYMMDD}` | `LAB-15350-1082-20261023` |
| **Diagnostic Radiology**| `RAD-` | `RAD-{patient_id}-{rad_id}-{YYYYMMDD}` | `RAD-15350-402-20261023` |
| **Invoice / Receipt** | `INVOICE-` | `INVOICE-OPD-{opd_id}-{YYYY-MM-DD}` | `INVOICE-OPD-9102-2026-10-23` |
| **Wellness Record** | `WELLNESS-` | `WELLNESS-{patient_id}-{YYYYMMDD}` | `WELLNESS-15350-20261023` |
| **Immunization** | `IMM-` | `IMM-{patient_id}-{vaccine_id}-{YYYYMMDD}` | `IMM-15350-12-20261023` |

### Field Components
- `{patient_id}`: Primary key integer from `patient_master.id` (e.g. `15350`).
- `S{session_id}`: Primary key integer from `opd_session.id` or `opd_details.id` prefixed with `S` (e.g. `S33765`). If session is absent, use `S0`.
- `{YYYYMMDD}`: Date of visit or service in compact 8-digit ISO format (e.g. `20261023`).

---

## 3. Implementation Rules in Code

### A. HMS Backend (`app/Controllers/AbdmGateway.php`)
1. **Never Overwrite in `hipLinkCareContext`**:
   Do NOT inspect `patient_ref` with regex and strip `PRESC-` or `OPD-` to replace with `p_code`.
   ```php
   // CORRECT:
   if (empty($body['patient_ref']) || $body['patient_ref'] === $body['abha_address']) {
       $firstContextRef = (string) ($body['care_contexts'][0]['ref'] ?? $body['care_contexts'][0]['referenceNumber'] ?? '');
       $body['patient_ref'] = $firstContextRef !== '' ? $firstContextRef : (!empty($patientRow['p_code']) ? $patientRow['p_code'] : 'P-' . $patientId);
   }
   ```
2. **Preserve in `m2LinkConfirm`**:
   The response patient block must use the care context reference:
   ```php
   $confirmedPatientRef = (! empty($confirmedContexts[0]['referenceNumber'])) ? (string) $confirmedContexts[0]['referenceNumber'] : $patientRef;
   return $this->response->setJSON([
       'ok' => 1,
       'message' => 'Care contexts linked successfully',
       'patient' => [
           'referenceNumber' => $confirmedPatientRef,
           'display' => $patientDisplay,
           'careContexts' => $confirmedContexts,
       ],
   ]);
   ```

### B. Bridge Gateway (`dispatchCareContextLink` & `hipLinkCareContextSubmit`)
In the bridge gateway (`/mnt/volume_blr1_1790148792836_eatria/www/abdm-bridge-gateway/app/Controllers/AbdmGateway.php`):
1. In `dispatchCareContextLink()`, `$patientRefNumber` is resolved to `$record->care_context_reference`.
2. In `hipLinkCareContextSubmit()`, each care context in `$careContexts` is mapped to its own `$patientList` item where:
   `'referenceNumber' => $ref`
   `'careContexts' => [['referenceNumber' => $ref, 'display' => $cleanDisp]]`

---

## 4. FHIR R4 Bundle Standards

All health records pushed to the bridge and shared with ABDM HIU/PHR must conform to **NRCeS FHIR Release 4**:

1. **Bundle Structure**:
   - `resourceType`: `"Bundle"`
   - `type`: `"document"`
   - `identifier`: System `https://www.e-atria.in/bundle-id`, value matching `{hiType}-{recordId}-{timestamp}`.
   - `timestamp`: ISO 8601 UTC / IST with timezone offset.
   - First entry (`entry[0]`) **must** be the `Composition` resource.

2. **Composition Resource**:
   - `status`: `"final"`
   - `type`: Appropriate LOINC code (e.g. `57833-6` for Prescription, `11488-4` for Consultation Note, `18842-5` for Discharge Summary).
   - `subject`: Reference to `Patient` resource (`urn:uuid:patient-{id}`).
   - `author`: Reference to `Practitioner` and/or `Organization`.
   - `section`: Structured sections with LOINC narrative codes and entries referencing clinical resources (`MedicationRequest`, `Condition`, `Observation`, etc.).

3. **Terminology Standards**:
   - Diagnoses / Findings: **SNOMED CT** (`http://snomed.info/sct`).
   - Lab / Radiology Tests: **LOINC** (`http://loinc.org`).
   - Medications: Clean Indian Pharmacopoeia / brand / generic names with dosage instructions.

---

## 5. Verification Checklist

Whenever modifying any linking, sync, or FHIR generation code:
- [ ] Are care context references structured as `{PREFIX}-{PATIENT_ID}-S{SESSION_ID}-{YYYYMMDD}`?
- [ ] Is `patient[].referenceNumber` set to the care context reference rather than the hospital UHID (`P26081015350`)?
- [ ] Does the PHR App display `PRESC-...` or `OPD-...` under the "Patient reference" column?
- [ ] Does `php -l` pass with zero syntax errors on both HMS and Bridge controllers?
