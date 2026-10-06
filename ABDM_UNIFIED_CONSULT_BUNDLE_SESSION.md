# Session Handover: Unified OPD Consultation Bundle & Single Care Context Integration

**Date:** October 6, 2026  
**Status:** ✅ Complete, Deployed & Verified on Live Environment  
**Target Repository:** `HMS_CI4_OLD` (`origin/main`) & `HMS-ABDM-Gateway` (`origin/AG_version`)  
**Live Server:** `hms-live` (`root@hms-live`)

---

## 1. Problem Statement & User Requests

1. **Default OTP Customization**:
   - The user requested updating the default sandbox OTP for ABDM care context linking to `654321` while preserving backward compatibility.
2. **Duplicate Records in PHR App (Image 5)**:
   - Previously, a single OPD consultation visit generated 3 distinct records/care contexts:
     - `OPD-15353-S33727-20261006`
     - `PRESC-15353-S33727-20261006`
     - `WELLNESS-33727-20261006`
   - In the PHR application (e.g. ABHA App), this caused duplicate entries where `OPConsultation` and `WellnessRecord` appeared separately for the exact same visit.
3. **Mislabeled Invoice Records (Images 2 & 3)**:
   - Older records for OPD invoices showed both `Prescription` and `Invoice` because historical rows had `hi_type = 'OPConsultRecord'` or lacked strict HI-type separation.
4. **Unified NRCES Bundle**:
   - The user requested consolidating into a **single unified bundle & care context** for the OPD visit.
   - The care context display must dynamically reflect the clinical contents:
     `OPD Consult, Wellness, Prescription, HealthDocument` (including vitals, prescribed medicines, and scanned/uploaded files).
5. **Auto-Generate / Update on Print**:
   - Automatically compile or update the FHIR record whenever the consultation form is saved or printed in HMS.

---

## 2. Technical Implementation Details

### A. NRCES FHIR R4 Specification Conformance
- **Profile**: `https://nrces.in/ndhm/fhir/r4/StructureDefinition/OPConsultRecord`
- Following the ABDM standard, an `OPConsultRecord` Document Bundle natively contains all clinical sections:
  1. **Chief Complaints**: `Condition` / complaint entries.
  2. **Physical Examination / Vitals**: `Observation` resources for Blood Pressure, Pulse, Temperature, $\text{SpO}_2$, Weight, and Height.
  3. **Problems & Diagnoses**: `Condition` resources with SNOMED CT and ICD-10 codings.
  4. **Medications / Prescriptions**: `MedicationRequest` resources with dose, frequency, and instructions.
  5. **Investigation Advice**: `ServiceRequest` for recommended tests/imaging.
  6. **Follow-Up Appointment**: `Appointment` resource for next visit date.
  7. **Document References**: `Binary` / `DocumentReference` for uploaded scans and generated prescription PDFs.
- **Bundle Meta Hardening**:
  - Added required `'versionId' => '1'` and `'lastUpdated' => $issuedAt` across all builder methods in `app/Libraries/FhirR4Builder.php`.
  - Validated using NRCES FHIR validator (`validate_fhir` tool): 0 structural errors.

### B. Single Care Context & Cleanup of Redundant Records
- **File**: `app/Controllers/Opd_prescription.php`
  - In `storePrescriptionFhirBundle()`, unified generation to compile only the single `OPConsultRecord`.
  - Removed generation of separate `PRESC-...` and `WELLNESS-...` records.
  - Automatically cleans up redundant standalone entries (`PRESC-...`, `WELLNESS-...`, and old alias patterns) from `health_records` and `opd_fhir_documents`.
  - In `resolveRecentFhirPushStatus()`, relaxed session reference search to `'-S' . $sessionId . '-'`.

### C. Clinical Component Labeling & Alias Deduplication
- **File**: `app/Controllers/AbdmGateway.php`
  - In `findCareContextsForPatient()`:
    - Automatically builds dynamic component labels: `OPD Consult, Wellness, Prescription - Dr. Name - Date` (dynamically appends `, HealthDocument` if external reports or scans exist).
    - Length guarded to 85 characters to ensure clean display across all PHR apps.
    - Registers all aliases in `$seenRefs` (`OPD-{patientId}-S{sessionId}-{cleanDate}`, `OPD-{opdId}-S{sessionId}-{visitDate}`, etc.).
    - Tracks `$seenOpdSessions`: step 6c fallback suppresses any orphaned or legacy alias references matching an already processed OPD session.

### D. ABDM Bridge Gateway HI-Type Resolution Fix
- **File**: `/var/www/html/abdm-bridge-gateway/app/Controllers/AbdmGateway.php` on `hms-live`
  - Modified `resolveAbdmHiTypeFromContext()` to apply **strict prefix checking** (`INVOICE-` $\rightarrow$ `Invoice`, `OPD-` $\rightarrow$ `OPConsultation`, `WELLNESS-` $\rightarrow$ `WellnessRecord`, etc.) before loose keyword searching on the display string.
  - This prevents composite titles like `"OPD Consult, Wellness"` from being misclassified into `WellnessRecord`.

### E. Task Board Synchronization Hardening
- **File**: `app/Libraries/Abdm/Sync/AbdmTaskBoardSyncService.php`
  - In `queryEligibleOpdVisits()`: sorted `opd_fhir_documents` to prioritize `OPConsultRecord` over legacy `PrescriptionRecord`.
  - Standardized care context reference derivation to canonical `OPD-{patientId}-S{sessionId}-{cleanDate}`.
  - In `linkAndPushOpdRecord()`: checks existing record by `care_context_reference` before inserting to prevent duplicate queued rows.
  - In `syncSingleWorkTask()`: applies canonical OPD reference format and `OPConsultRecord` prioritization.

### F. Auto-Generation on Consultation Print & PDF
- **File**: `app/Controllers/Opd.php`
  - Added auto-generation / updating hook inside `opd_lettre_print()` and `opd_lettre_pdf()`.
  - When the consultation letter is printed or downloaded, `storePrescriptionFhirBundle()` is invoked in the background, updating `opd_fhir_documents` and `health_records`.

---

## 3. Live Verification & Database Status

### Target Test Patient:
- **Patient ID**: `15353` (`P26081015353`)
- **ABHA Address**: `91310013085603@sbx`
- **OPD ID**: `33793`
- **Session ID**: `33727`

### Care Context Output:
```text
V3 Care Contexts Count: 2
  [0] ref=OPD-15353-S33727-20261006
      display=OPD Consult, Wellness, Prescription - Dr. Jayashri Joshi - 06 Oct 2026
      hiType=OPConsultation
  [1] ref=INVOICE-OPD-33793-2026-10-06
      display=Invoice D26100033793 (OPD - Rs 1,000.00)
      hiType=Invoice
```

### Database `health_records` State on `hms-live`:
| id | patient_id | hi_type | care_context_reference | push_status |
|---|---|---|---|---|
| 243 | 15353 | `InvoiceRecord` | `INVOICE-OPD-33793-2026-10-06` | `linked` |
| 244 | 15353 | `OPConsultRecord` | `OPD-15353-S33727-20261006` | `local_discovery_ready` |

*(Legacy duplicate queued rows 247, 248, 249 and stale PrescriptionRecord document 123 were deleted).*

### Print Hook Verification:
- Executing `opd_lettre_print(33727)` completes cleanly and updates `health_records` row `244` with latest timestamp.

---

## 4. Git Commits & Push Summary

### `HMS_CI4_OLD` (`main` branch):
- `04eff5e`: Standardize OPD care context formatting and prevent duplicate session aliases
- `b0a0811`: fix(fhir): add versionId and lastUpdated to DocumentBundle meta for full NRCES conformance
- `e2bb0b1`: fix(abdm): enrich OPD and INVOICE care contexts with clinical components and bill summary
- `f367603`: fix(abdm): unify consult visit into single OPConsultRecord bundle and auto-sync on print
- `d46f222`: fix(abdm): set default sandbox linking OTP to 654321 and accept both 654321 and 123456

### `abdm-bridge-gateway` (`AG_version` branch on `hms-live`):
- `eff0fe6`: fix(abdm): strict prefix precedence for hiType resolution to prevent multi-component display hijacking
- Pushed to `https://github.com/infodevsofttech-admin/HMS-ABDM-Gateway.git`
