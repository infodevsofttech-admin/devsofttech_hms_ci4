# ABDM M2 Test Session Handover & Verification Guide

**Date:** 2026-09-29  
**Branch:** `main`  
**Latest Commits:**  
- `6bd2430` — `fix(abdm-m1): sanitize otp error text, hide bridge request id, add beneficiary name to consent`  
- `a33b376` — `feat(abdm-m2): add HIP link modal and care context discovery on patient registration`  
**Live Environment:** [demo.e-atria.net](https://demo.e-atria.net/) (`10.8.0.1` / `/var/www/html/hms_etria`)

---

## 1. Executive Summary

This session verified and configured end-to-end readiness for **ABDM Milestone 2 (M2)** testing, starting from Patient Registration through care context discovery, HIP-initiated linking, user-initiated linking, and FHIR data sharing.

All changes have been unit tested (47/47 tests passing), committed, pushed to `origin/main`, and deployed live to `https://demo.e-atria.net/`.

---

## 2. Key Changes Implemented

### A. Patient Registration & ABHA Management Integration
- **File:** [Patient_V.php](file:///d:/Workplace/HMS_CI4_OLD/app/Views/billing/Patient_V.php)
  - Included `partials/abdm_hip_link_modal.php` on the Patient Registration view so the HIP link modal can be opened from registration screens.
  - Added hidden `input_abha_address` to `form.form1` so ABHA addresses are seamlessly submitted and saved to `patient_master`.
  - Added a dedicated 4th card under **Patient Management &rarr; ABHA Management** tab:
    - **Link Records to ABHA (HIP)** button (`openAbdmHipLinkModal()`)
    - **Send Deep Link SMS** button (`openAbdmHipSmsModal()`)

### B. Instant Care Context Discovery for New Patients
- **File:** [AbdmGateway.php](file:///d:/Workplace/HMS_CI4_OLD/app/Controllers/AbdmGateway.php)
  - Updated `findCareContextsForPatient()` to query `opd_master` in addition to `opd_prescription`.
  - Previously, a care context was only discoverable after a doctor wrote a prescription or finalized a lab report.
  - Now, as soon as an OPD consultation appointment / slip is created at the reception desk, the visit is instantly discoverable and linkable as `OPD-{patientId}-S{opdId}-{YYYYMMDD}` with title `OPConsultRecord - Dr. {DoctorName} - {Date}`.
  - Added unit test `testDiscoversOpdMasterVisitWhenPrescriptionNotCreated` in [CareContextDiscoveryTest.php](file:///d:/Workplace/HMS_CI4_OLD/tests/unit/Abdm/CareContextDiscoveryTest.php).

### C. 14-Digit ABHA Number & `@sbx` Address Dual Resolution
- **File:** [AbdmGateway.php](file:///d:/Workplace/HMS_CI4_OLD/app/Controllers/AbdmGateway.php)
  - Enhanced `hipPatientCareContexts()` so that if an ABHA address field contains a 14-digit numeric ABHA ID (or formatted `XX-XXXX-XXXX-XXXX`), it queries both `abha_address` and `abha_id` in `patient_master`.

### D. M1 Feedback Updates (Retained & Verified)
- Normalized invalid OTP error alerts strictly to `"Incorrect OTP"` (or attempts exceeded message).
- Removed internal `Bridge Request ID: REQ-...` from user-facing screens.
- Added healthcare worker declaration and beneficiary name consent input field before Aadhaar OTP dispatch.

---

## 3. M2 Testing Step-by-Step Walkthrough

### Step 1: Register Patient & Create Visit Slip
1. Open **Patient Management &rarr; New Person** on `https://demo.e-atria.net/`.
2. Register patient with Mobile Number, Name, Gender, DOB.
3. On patient record screen, click **Appointment For OPD** and assign a doctor to generate the OPD slip.
4. Care context `OPD-{patientId}-S{opdId}-{YYYYMMDD}` is now live and linkable.

### Step 2: Test HIP-Initiated Linking (Method 4 Demographic Auth)
1. In patient profile or **ABHA Management**, click **Link Records to ABHA (HIP)**.
2. Enter the patient's ABHA address (e.g., `user@sbx`).
3. Patient demographics (Full Name, Gender, YOB) are pre-filled/verified.
4. Select the OPD visit care context.
5. Click **Link Selected Records (Demographic Auth)**.
6. The gateway returns a JWT Link Token and links the care context to the ABHA account.

### Step 3: Test Deep Link SMS (`sms/notify2`)
1. On the same modal, switch to **Deep Link SMS (sms/notify2)** tab.
2. Confirm the 10-digit mobile number and click **Send Deep Link SMS**.
3. ABDM dispatches an SMS with an app deep link to the patient's mobile device.

### Step 4: Test User-Initiated Discovery & Linking (from ABHA PHR App)
1. Open the ABDM Sandbox ABHA / PHR app on a smartphone.
2. Search for the facility: **Dev Softtech** (Facility ID: `IN0510000871`).
3. Search by patient mobile number or ABHA address.
4. Select the discovered care contexts and request OTP.
5. Verify OTP to confirm the linking.

### Step 5: Test Health Record View (FHIR Bundles in PHR App)
1. Doctor submits clinical notes/prescription in **OPD Prescription** editor (or saves findings in **Radiology Report Editor**).
2. In the PHR app, tap on the linked hospital and tap **Fetch Records** / **Pull Records**.
3. The HMS generates and transfers the encrypted FHIR bundle (`OPConsultRecord` or `DiagnosticReportRecord`).
4. Patient views findings and medications directly in the PHR app.

---

## 4. Test Suite & Verification Results

```bash
# Local Unit Test Execution
vendor/bin/phpunit tests/unit/Abdm/

# Result
Tests: 47, Assertions: 472, PHPUnit Warnings: 1 (Xdebug coverage). 100% PASS.
```

---

## 5. Deployment Commands Reference

```powershell
# Windows deployment to remote server (10.8.0.1)
& "C:\Program Files\PuTTY\plink.exe" -ssh -batch -pw "bishT@9720958717H" root@10.8.0.1 "cd /var/www/html/hms_etria && git pull origin main && php spark cache:clear"
```
