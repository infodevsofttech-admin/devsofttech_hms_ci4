# ABDM Sandbox Exit — HTC Interview & Audit Demo Guide

**Saved Session Date:** 2026-10-29  
**Purpose:** Quick-reference readiness checklist, UI URLs, database log tables, and live demo script for the NHA / Empaneled Implementation Support Agency (ISA) Functional & Security Audit (HTC).

---

## 1. Readiness Matrix (HIP, HIU & Consent Flow)

### A. HIP (Health Information Provider — M1 & M2)
| Requirement | Status | HMS Implementation Reference |
| :--- | :---: | :--- |
| **Capture patient records digitally & link with ABHA** | Ready | Patient Registration & OPD/IPD Profile (`patient_master.abha_id`, `patient_master.abha_address`), plus Scan & Share Queue (`app/Controllers/AbdmOpdQueue.php`). |
| **Generate ABDM-compliant FHIR R4 records** | Ready | Generates NRCeS FHIR R4 `Bundle` (`type: "document"`, `Composition` first entry, SNOMED CT + LOINC coded) for all 7 HI types: `Prescription`, `OPConsultation`, `DischargeSummary`, `DiagnosticReport`, `ImmunizationRecord`, `WellnessRecord`, `Invoice`. |
| **Canonical Care Context Reference & Notification** | Ready | Structured care context references (`PRESC-{pid}-S{sid}-{YYYYMMDD}`, `OPD-...`, `DISCHARGE-...`, `LAB-...`, `RAD-...`, `INVOICE-...`, `WELLNESS-...`, `IMM-...`) notified via `/v3/hip/link/carecontext` and `/v3/hip/patients/sms/notify2` (`app/Controllers/AbdmGateway.php`). |
| **Share records only after valid patient consent** | Ready | HIP consent webhook (`POST /api/v3/hiu/consent/on-notify` -> `AbdmGateway::hipConsentNotifyCallback`) verifies `GRANTED` state and date range before Fidelius (ECDH Curve25519) encrypted health record push. |

### B. HIU (Health Information User — M3)
| Requirement | Status | HMS Implementation Reference |
| :--- | :---: | :--- |
| **Raise consent requests** | Ready | Available in Patient OPD Profile (`Patient::abdm_content_request_custom`) and HIU Console (`AbdmHiu::consentRequest`). Supports purpose (`CAREMGT`), date range, expiry (`dataEraseAt`), and HI type selection. |
| **Fetch records after consent approval** | Ready | Processes `/v3/hiu/consent/request/on-status`, `/on-notify`, and `/health-information/on-request` callbacks (`app/Libraries/Abdm/M3HiuWorkflowService.php`), decrypts payloads, and stores documents in `abdm_hiu_documents` (`app/Libraries/Abdm/M3HiuDocumentRepository.php`). |
| **Honor consent expiry and revocation** | Ready | Enforced in `Patient::abdm_documents` and `AbdmGateway::consentRevokedCallback`: when consent transitions to `REVOKED`, `DENIED`, or passes `dataEraseAt` (`EXPIRED`), cached HIU documents are automatically purged (`purgeAbdmDocumentsByRefs`) and UI access is blocked. |
| **Display fetched records in HMS** | Ready | Clinical summary viewer, attachment viewer, and printable PDF (`Patient::abdm_records_pdf`, `app/Views/abdm/hiu_documents.php`). |

---

## 2. Exact Screens, URLs & Database Tables for Audit Evidence

Keep these **5 browser tabs** open during the HTC interview:

### Tab 1: Live ABDM API & Bridge Logs
* **UI URL:** `/AbdmBridgeLog`
* **Controller / View:** `app/Controllers/AbdmBridgeLog.php` | `app/Views/abdm/bridge_log.php`
* **What to show the auditor:**
  * Filter by channel (`eatria_bridge`, `bridge`, `abdm`) or status (`success`, `error`).
  * Click **Detail** on any row to display the exact **Request JSON**, **Response JSON**, HTTP status code (`200` / `202`), and timestamp.
* **Database Table:** `abdm_api_logs`

### Tab 2: FHIR R4 Bundle Inspector & Care Context Link Verifier
* **UI URL:** `/AbdmFhirInspector`
* **Controller / View:** `app/Controllers/AbdmFhirInspector.php` | `app/Views/abdm/fhir_inspector.php`
* **What to show the auditor:**
  * Search any ABHA-linked patient.
  * Show all Care Context references (`OPD-...`, `PRESC-...`, `LAB-...`, `RAD-...`, `DISCHARGE-...`, `INVOICE-...`) and `LINKED` status.
  * Click **Preview FHIR Bundle** to display the NRCeS FHIR R4 `Bundle` JSON (`Composition`, `Patient`, `Practitioner`, `Organization`, `MedicationRequest`, `Observation`, etc.).
* **Database Tables:** `health_records`, `record_links`, `abdm_link_transactions`

### Tab 3: ABDM HIP Task Board (Record Creation & Sync Queue)
* **UI URL:** `/AbdmTaskBoard`
* **Controller / View:** `app/Controllers/AbdmTaskBoard.php` | `app/Views/abdm/task_board.php`
* **What to show the auditor:**
  * Real-time queue of OPD consultations, prescriptions, lab/radiology reports, discharge summaries, wellness, immunization, and invoices with their ABDM push and link status.
* **Database Tables:** `abdm_work_tasks`, `abdm_sync_outbox`

### Tab 4: HIU Consent Request List & Fetched Documents Console
* **UI URL 1 (Consent Request List):** `/AbdmHiu` (`app/Views/abdm/patient_request_list.php`)
  * Shows every consent request with `Status` (`REQUESTED`, `GRANTED`, `COMPLETED`, `REVOKED`, `EXPIRED`, `DENIED`), `HI Types`, `Requested On`, and `Expiry`.
* **UI URL 2 (Fetched Documents Viewer):** `/AbdmHiu/documents` (`app/Views/abdm/hiu_documents.php`)
  * Displays decrypted health records fetched via HIU consent.
* **UI URL 3 (Patient OPD Profile ABDM Tab):** `/billing/patient/show_profile_opd/<patient_id>`
  * Raise new consent requests, check live status, view fetched documents, or generate PDF (`/billing/patient/abdm_records_pdf/<patient_id>`).
* **Database Tables:**
  * `abdm_hiu_workflows` (tracks M3 lifecycle states, `request_id`, `transaction_id`, `abdm_consent_request_id`, `abdm_consent_artifact_id`, `revoked_at`, `expired_at`)
  * `abdm_hiu_consent_artifacts` (stores HIE-CM consent artefacts)
  * `abdm_hiu_documents` (stores decrypted FHIR bundles)
  * `abdm_consent_records` (stores HIP-side consent records)

### Tab 5: Security & Compliance Audit Logs
* **Service:** `app/Libraries/AbdmAuditService.php`
* **Database Tables:** `audit_logs` and `abdm_audit_logs`
  * Captures `actor_user_id`, `actor_name`, `ip_address`, `user_agent`, `abha_id`, `patient_id`, `consent_id`, `transaction_id`, `action` (`push_record`, `link_record`, `consent_request`, `consent_grant`, `consent_revoke`, `record_linked`, `consent_callback`, `abha_validate`), `outcome`, and JSON details.
* **Server File Logs:** `writable/logs/log-YYYY-MM-DD.log`

---

## 3. Step-by-Step Live Demo Script for the Auditor

1. **M1 — Patient Registration & ABHA Linkage:**
   * Open Patient Registration or `/AbdmOpdQueue` (Scan & Share).
   * Show a patient linked with an ABHA Number and `@sbx` ABHA Address.
2. **M2 (HIP) — Record Creation, FHIR R4 Generation & Care Context Linking:**
   * Create/save an OPD Consultation or Prescription for the patient.
   * Open `/AbdmFhirInspector` -> select the patient -> click **Preview Bundle** to show the NRCeS FHIR R4 JSON.
   * Link the Care Context and show it appearing inside the **ABHA PHR Sandbox App** (`phrsbx.abdm.gov.in`) under `PRESC-...` / `OPD-...`.
   * Show the corresponding API call log in `/AbdmBridgeLog`.
3. **M3 (HIU) — Consent Request, Patient Approval & Record Fetch:**
   * Open the patient's OPD Profile (or `/AbdmHiu`) and click **Request Consent**.
   * Approve the consent request in the **ABHA PHR Sandbox App**.
   * Show status changing to `GRANTED` / `COMPLETED` in `/AbdmHiu`.
   * Open the fetched records in the patient's ABDM Documents view (or `/AbdmHiu/documents`) to show the rendered clinical data.
4. **M3 Security Control — Consent Revocation & Expiry Enforcement:**
   * In the **ABHA PHR Sandbox App**, revoke the granted consent.
   * Refresh/click the consent in HMS — show that the status updates to `REVOKED`, cached documents for that consent are purged from `abdm_hiu_documents`, and HMS blocks viewing the records.

