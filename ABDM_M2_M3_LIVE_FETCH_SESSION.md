# ABDM M2/M3 Architecture Transition & Live Fetch Handover Session

**Date:** 2026-09-28  
**Scope:** HMS (`devsofttech_hms_ci4`) & ABDM Bridge Gateway (`HMS-ABDM-Gateway`)  
**Status:** Completed & Verified Live  

---

## 1. Executive Summary & Problem Resolution

### Initial Issue
When patients requested their medical records in the ABHA PHR app (e.g., ABHA SBX / Eka Care), OPD consultations were either failing to display under `OPConsultation` or showing incomplete information (such as missing medicines).

### Root Causes Identified
1. **Misconception of ABDM M2 vs M3 Architecture:**
   - Previously, the workflow relied on "pre-pushing" medical bundles to the cloud bridge gateway in advance using manual "Submit to ABDM" buttons.
   - If an OPD prescription was created, auto-saved, and later had medicines added/updated, pre-pushed bundles became stale or incomplete.
2. **ABDM NRCeS Profile Naming:**
   - ABDM defines the Health Information Type as `OPConsultation` (or `OPConsultRecord` for the FHIR profile `https://nrces.in/ndhm/fhir/r4/StructureDefinition/OPConsultRecord`). The gateway and HMS were previously defaulting to `Prescription`, causing the records to appear in wrong categories or get filtered out.
3. **M3 On-Demand Pull Mechanism:**
   - In true ABDM architecture:
     - **Milestone 2 (M2):** Care Context Discovery & Linking. HMS only notifies the ABDM Gateway of the patient's care context reference (e.g., `OPD-15357-S33716-20260928`). **No medical data is pushed to ABDM or the cloud at this stage.**
     - **Milestone 3 (M3):** Data Transfer / Health Information Exchange. When the patient approves consent and pulls data in their PHR app, the ABDM Gateway sends `health-information/hip/request`. At that exact moment, the Bridge Gateway queries HMS over the secure VPN tunnel (`/records/fetch`), dynamically generating the latest, 100% fresh FHIR bundle containing all current medicines, encrypting it, and transferring it to the PHR app.

---

## 2. Key Code Changes

### A. HMS Codebase (`devsofttech_hms_ci4`)

1. **`app/Libraries/FhirR4Builder.php`**:
   - Added `buildOpConsultBundle()` conforming to the NRCeS `OPConsultRecord` profile:
     - Includes `Bundle.type = "document"`.
     - Generates `Composition` with type `OP Consultation Record` (`371530004` / `Consultation report`).
     - Generates `Patient`, `Practitioner`, and `Encounter` resources.
     - Maps chief complaints and diagnoses to `Condition` resources.
     - Maps prescribed medicines from `opd_prescrption_prescribed` to `MedicationRequest` resources.

2. **`app/Controllers/Opd_prescription.php`**:
   - Made `regenerateFhirBundleInternal($opd_p_id)` public so it can be invoked on-demand by the gateway handler.
   - Hooked automatic FHIR bundle regeneration directly into:
     - `medicine_add()`
     - `medicine_update()`
     - `medicine_remove()`
   - Guaranteed that whenever a doctor adds or removes medications in HMS, the cached FHIR bundle is instantly regenerated.

3. **`app/Controllers/AbdmGateway.php`**:
   - Added **Strategy 0** in `recordsFetch()`:
     - Parses the requested care context (e.g. `OPD-15357-S33716-20260928`).
     - Locates the prescription and calls `regenerateFhirBundleInternal()`.
     - Returns the fresh `OPConsultRecord` FHIR bundle in real time to the bridge gateway over the VPN.

4. **`app/Views/abdm/task_board.php`**:
   - **Removed all legacy "Submit to ABDM" / "Push to Gateway" buttons**:
     - Removed `btn-opd-consult-submit-gateway` ("Submit to ABDM" / "Submit Again").
     - Removed `btn-invoice-push` from Invoices table.
     - Removed `btnSubmitFhirToAbdm` ("Force gateway push") from FHIR preview modal header.
     - Removed "Submit" push action buttons from the Pending Tasks table.
   - **Added "Link Context" (M2)**:
     - If care context is unlinked, shows a clean "Link Context" button invoking `window.openAbdmHipLinkModal()`.
     - If care context is already linked, displays a green `Linked` badge.
   - **Updated Metric Cards**:
     - Renamed "Records Pushed" to "Care Contexts Linked".
   - **Preserved "Preview FHIR"**:
     - Doctors and administrators can still preview the FHIR JSON bundle directly in the UI.

---

### B. ABDM Bridge Gateway (`HMS-ABDM-Gateway`)

1. **`app/Controllers/AbdmGateway.php`**:
   - **`hipHealthInfoRequest()`**:
     - Updated logic so that whenever a hospital has a reachable VPN endpoint/IP, it **always** requests live fresh FHIR data from the hospital HMS (`forwardToHms($hipId, 'records/fetch', ...)`).
     - Keeps the gateway database cache strictly as an offline fallback.
   - **ABDM-1017 Handling**:
     - Addressed the National Health Authority (NHA) gateway propagation race condition where requesting encrypted health data immediately caused `HTTP 400 ABDM-1017: Invalid Transaction Id`. Added a 1.5-second propagation pause followed by automatic 2-second retry.
   - **Timezone Correction**:
     - Fixed UTC vs IST discrepancies in date range queries for requested care contexts.
   - **HI Type Normalization**:
     - Normalized `hiTypes` in care context notifications (`OPConsultRecord` -> `OPConsultation`).

---

## 3. Live End-to-End Verification

A live verification test was executed between the Bridge Gateway on `10.8.0.1` and the DevSoft Tech HMS instance (`IN0510000828`):

- **Care Context:** `OPD-15357-S33716-20260928`
- **Result:**
  - `FORWARD_RESULT_OK = 1`
  - `HI_TYPE = OPConsultRecord`
  - `BUNDLE_TYPE = document`
  - `ENTRIES_COUNT = 21`
  - Prescribed medicines dynamically confirmed present in the generated bundle:
    - `ACILOC (RANITIDINE 50 MG)`
    - `CAP PANTOP DSR (Pantoprazole 40mg + Domperidone 30mg)`
    - `TAB PARACETAMOL 500 MG`

---

## 4. Git Repositories & Commit Log

### 1. HMS Application (`devsofttech_hms_ci4`)
- **Remote:** `https://github.com/infodevsofttech-admin/devsofttech_hms_ci4.git`
- **Branch:** `main`
- **Key Commits:**
  - `90e63d2`: `fix(abdm): generate and publish OPD consultations as OPConsultRecord and support dynamic hi_type`
  - `d0f7aa4`: `feat(abdm): enable live fresh FHIR fetch on pull, remove old push options, and keep care context linking`
- **Live Deployment Path:** `/var/www/html/hms_etria` (synced to `origin/main`)

### 2. ABDM Bridge Gateway (`HMS-ABDM-Gateway`)
- **Remote:** `https://github.com/infodevsofttech-admin/HMS-ABDM-Gateway.git`
- **Branch:** `AG_version`
- **Server Path:** `/mnt/volume_blr1_1790148792836_eatria/www/abdm-bridge-gateway`
- **Key Commits:**
  - `212938d`: `fix(transfer): add retry on ABDM-1017, fix IST timezone parsing, and normalize notify hiTypes`
  - `25eb7f0`: `feat(bridge): always fetch fresh live FHIR bundle from HMS on PHR pull request`
- **Status:** Pushed to GitHub and active in production.

---

## 5. Next Operational Steps & Best Practices

1. **Care Context Linking (M2):**
   - Use the ABDM Task Board or automated cron job to link patient care contexts (`Link Context` button).
   - This registers the token with ABDM so patient apps can discover their visits.
2. **Health Records Fetching (M3):**
   - No manual intervention or pushing required!
   - When the patient opens their ABHA app and views records, the Bridge Gateway automatically pulls live data from HMS via VPN, encrypts it with ECDH keys, and delivers it securely.
