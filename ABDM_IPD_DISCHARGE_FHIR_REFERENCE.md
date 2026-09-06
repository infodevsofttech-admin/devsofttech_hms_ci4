# ABDM IPD Discharge Summary FHIR R4 Integration & Fixes

**Last Updated:** 2026-09-06  
**Module:** ABDM Gateway / IPD Discharge FHIR Generator  
**Files Modified:**
- `app/Libraries/Abdm/Fhir/Generators/DischargeFhirGenerator.php`
- `app/Controllers/Ipd_discharge.php`
- `app/Controllers/AbdmGateway.php`
- `tests/unit/Abdm/FhirGeneratorsTest.php`

---

## 1. Problem Summary
When generating and pushing an IPD Discharge Summary FHIR Bundle with attached PDF documents (Discharge Summary & IPD Bill) to an ABDM PHR app (e.g. ABHA / Aarogya Setu / Driefcase / Eka Care), the PHR application failed with:
> **"Error in parsing the data. your data is incorrect"**

---

## 2. Root Cause Analysis (ABDM NRCES FHIR R4 Profile)

According to the official NRCES India ABDM FHIR R4 profile `https://nrces.in/ndhm/fhir/r4/StructureDefinition/DischargeSummaryRecord`:

1. **Composition Section Slicing Rules**:
   - The discriminator for `Composition.section` is `code.coding.code`.
   - Every section slice has a strict cardinality of **`0..1` (Max: 1)**.
   - Using non-standard codes or repeating slice codes (e.g., multiple sections with code `425044008`) violates the profile.

2. **Code Discrepancies**:
   - **Medications Section**: Previously assigned `721981007` (SNOMED for *Diagnostic studies report / Investigations*). Because PHR parsers expect `DiagnosticReport` entries under `721981007`, receiving `MedicationRequest` entries triggered a schema validation error.
   - **Discharge Diagnosis**: Previously used code `397659008`. In ABDM `DischargeSummaryRecord`, diagnoses must be grouped under the standard `MedicalHistory` slice (`1003642006`).
   - **Procedures**: Previously used code `371525003` instead of profile slice code `1003640003`.
   - **Care Plan / Advice**: Previously used code `736271009` instead of profile slice code `734163000`.

3. **Standard ABDM Discharge Summary Section Slices**:
   | Slice Name | Standard SNOMED Code | Standard Display | Target Profile |
   | :--- | :--- | :--- | :--- |
   | **ChiefComplaints** | `422843007` | Chief complaint section | `Condition` |
   | **MedicalHistory** | `1003642006` | Past medical history section | `Condition`, `Procedure` |
   | **PhysicalExamination** | `425044008` | Physical exam section | `Observation` |
   | **Procedures** | `1003640003` | History of past procedure section | `Procedure` |
   | **Medications** | `1003606003` | Medication history section | `MedicationRequest` |
   | **Investigations** | `721981007` | Diagnostic studies report | `DiagnosticReportLab`, `DiagnosticReportImaging` |
   | **CarePlan** | `734163000` | Care plan | `CarePlan` |
   | **DocumentReference** | `373942005` | Discharge summary | `DocumentReference` |
   | **Allergies** | `722446000` | Allergy record | `AllergyIntolerance` |
   | **FamilyHistory** | `422432008` | Family history | `FamilyMemberHistory` |

4. **DocumentReference Requirements**:
   - `DocumentReference` requires valid `status`, `type`, `subject`, `content[0].attachment` (`contentType`, `data`, `title`, `creation`), `description`, and `text` (XHTML narrative).

---

## 3. Implemented Fixes

1. **`DischargeFhirGenerator.php`**:
   - Aligned all `Composition.section` slices to standard ABDM SNOMED codes and displays.
   - Merged diagnoses and clinical history under `Medical History` (`1003642006`).
   - Consolidated physical exam observations (admission/discharge) under single `Physical Examination` (`425044008`) slice.
   - Assigned correct `Medications` code (`1003606003`) to `MedicationRequest` entries.
   - Assigned `Care Plan` code (`734163000`) for follow-up and discharge advice.
   - Ensured `DocumentReference` includes `description` and valid `text` narrative.

2. **`Ipd_discharge.php`**:
   - Added `generateDischargeSummaryPdfBinary(int $ipdId, bool $withHeader = true)` to render mPDF binaries and cache to `writable/uploads/abdm/ipd/{ipdId}/discharge-summary.pdf`.
   - Linked `buildIpdPdfDocumentsList()` to auto-generate and include the PDF binary in ABDM FHIR requests.

3. **`AbdmGateway.php`**:
   - Integrated automatic generation of discharge summary PDFs when bundling or serving IPD care context records.

4. **Unit Tests**:
   - Updated `tests/unit/Abdm/FhirGeneratorsTest.php` assertions to match standard section slice titles.
   - All 42 unit tests pass.

---

## 4. Verification Command
To dump and verify the IPD discharge bundle from CLI:
```bash
php spark fhir:dump
```
Output is saved to `writable/ipd9_bundle.json`.
