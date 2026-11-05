# NDHM Information Security Policy 2020 (Part 2 – External Ecosystem) — HMS Compliance & Action Plan

**Saved Session Date:** 2026-11-05  
**Source Policy Document:** *National Digital Health Mission: Information Security Policy 2020 (Part 2 – External Ecosystem)*  
**Target Codebase:** `d:\Workplace\HMS_CI4_OLD`

---

## 1. Executive Summary

This document captures the compliance assessment of HMS against the **NDHM Information Security Policy 2020 (Part 2 – External Ecosystem)** for EMR/EHR, HIP (M1/M2), and HIU (M3) certification and WASA / Functional Audit readiness.

---

## 2. Already Implemented in HMS (Compliant)

| Policy Clause | NDHM Requirement | HMS Implementation Reference |
| :--- | :--- | :--- |
| **7.4.1 / 7.4.2 / 7.4.3** | Role-Based Access Control (RBAC), unique user IDs, least privilege for EMR/EHR & ABDM modules | Implemented via CodeIgniter Shield in `app/Config/AuthGroups.php` (`superadmin`, `admin`, `doctor`, `nurse`, `billing_cashier`, etc.) and API auth in `app/Filters/ApiAuthFilter.php`. |
| **7.4.14** | Session security controls to prevent session hijacking / concurrent session abuse | `HttpOnly = true`, `SameSite = Lax`, `Secure = true` (on HTTPS) in `app/Config/Cookie.php`, CSRF protection in `app/Config/Filters.php`, and single-active-session enforcement via `app/Filters/SingleActiveSessionFilter.php` & `app/Libraries/UserSessionRegistry.php`. |
| **7.4.20** | Mandatory double-check of patient identity before creating/updating EMR records or linking ABHA so one patient's data is never inserted into another's | Verhoeff Aadhaar checksum check (`validateVerhoeff` in `app/Controllers/Abha.php`), `AbhaDemographicValidator`, and side-by-side Patient vs. ABHA Demographic Comparison Modal before linking/updating. |
| **7.5.1** | Cryptography in-transit (TLS/HTTPS) & end-to-end encryption for health record exchange and ABHA payloads | Global `forcehttps` & HSTS in `app/Filters/SecureHeadersFilter.php`; RSA-OAEP-256 encryption for ABDM M1 Aadhaar/OTP payloads; ECDH Curve25519 (Fidelius / X25519 + HKDF + AES-GCM-256) for M2/M3 FHIR health record transfer. |
| **7.7.8 / 7.9.3 / 7.9.15** | Web Application & Open API Security (OWASP Top 10, rate limiting, CAPTCHA, strict CSP, HSTS, OAuth/Bearer token verification) | `app/Filters/LoginThrottleFilter.php` (CAPTCHA + IP rate limiting), `app/Filters/SecureHeadersFilter.php` (CSP, HSTS, X-Frame-Options, nosniff), and Bearer/Gateway signature validation on ABDM endpoints. |
| **7.7.17 / 7.7.18(c,e,f)** | Audit logs for consent creation/modification, ABHA validation, and health record import/export | `app/Libraries/AbdmAuditService.php` logs `push_record`, `link_record`, `consent_request`, `consent_grant`, `consent_revoke`, `consent_callback`, `abha_validate`, `DISCOVERY`, `FETCH`, and `SHARE` with actor ID, IP, user agent, and timestamp (`Asia/Kolkata`). |
| **7.7.21** | Raw Aadhaar number must never be stored in database or sent back to browser | `app/Controllers/Abha.php` only keeps Aadhaar in transient session for `verifyOtp()` matching and immediately removes it (`session()->remove('abha_aadhaar_txn_' . $txnId)`), storing only masked Aadhaar/mobile. |
| **7.9.7** | Data integrity check (checksums/hash verification on health records) | SHA-256 / checksum verification on FHIR bundles during M2 push and M3 HIU reception (`app/Libraries/Abdm/M3HiuDocumentRepository.php`). |

---

## 3. Pending HMS Code & Configuration Tasks (To Implement Next Session)

### Task 1: Idle Session Timeout = 30 Minutes or Less (Clause 7.4.4)
* **Policy Requirement:** *"All information systems (EHR, EMR, PHR...) shall have idle session time out feature i.e. after a certain time of inactivity (recommended duration is 30 mins or less), the system should log out."*
* **Current State:**
  * `app/Config/Session.php`: `public int $expiration = 7200;` (2 hours).
  * `app/Libraries/UserSessionRegistry.php`: `cleanup()` uses `time() - 7200`.
* **Action Plan:**
  * Update `$expiration = 1800;` in `app/Config/Session.php`.
  * Update session cleanup cutoff to `1800` seconds in `app/Libraries/UserSessionRegistry.php`.

### Task 2: Account Lockout for 30 Minutes After 3 Consecutive Login Failures (Clause 7.4.11)
* **Policy Requirement:** *"For applications, there should be an auto lock out period of 30 mins in case of three consecutive login failures or as per the access control policy/password policy of the organization."*
* **Current State:**
  * `app/Filters/LoginThrottleFilter.php` throttles at 5 attempts per 60 seconds per IP.
* **Action Plan:**
  * Update `app/Filters/LoginThrottleFilter.php` (or Shield login listener) to check consecutive failed login attempts per username/IP (via `auth_logins` or cache) and enforce a **30-minute (`1800` seconds)** lockout after **3 consecutive failures**.

### Task 3: Stringent Password Policy & History Validator (Clause 7.4.19)
* **Policy Requirement:**
  * Min length 8 characters (already set in `app/Config/Auth.php`).
  * Must contain at least **1 uppercase**, **1 lowercase**, **1 numeric**, and **1 special character**.
  * Must not be based on username/personal info (`NothingPersonalValidator`).
  * Must not contain consecutive identical characters.
  * Disallow reuse of the **last 5 passwords**.
* **Action Plan:**
  * Uncomment `NothingPersonalValidator::class` in `app/Config/Auth.php`.
  * Create `app/Authentication/Passwords/NdhmPasswordValidator.php` implementing `CodeIgniter\Shield\Authentication\Passwords\ValidatorInterface` to enforce character complexity, no consecutive identical characters, and password history check (last 5 passwords).

### Task 4: EMR Clinical Access, Print/Export & Admin Audit Logging (Clauses 7.7.18 & 7.7.25)
* **Policy Requirement:**
  * Log `(a)` patient record creation/view/update, `(f)` health data printing/export, `(g)` user/role management activities, and `(h)` access to the audit log screen.
  * Prevent deletion or modification of audit log records (Clause **7.7.25**).
* **Action Plan:**
  * Add `AbdmAuditService::log()` hooks to clinical view/print endpoints, user/role management controllers, and audit log viewer controllers (`AbdmBridgeLog`, `AbdmFhirInspector`).

### Task 5: Aadhaar Log Retention (2 Years Active) & PII Scrubbing (Clauses 7.7.20 – 7.7.24)
* **Policy Requirement:**
  * Logs must never retain sensitive personal data / raw health data / Aadhaar numbers (Clause **7.7.21**).
  * Non-PII Aadhaar/ABDM authentication metadata (`txn_id`, timestamp, response code, consent flag) must be retained for **2 years active (730 days)** and 5 years archive (Clauses **7.7.23 – 7.7.24**).
* **Current State:**
  * `app/Commands/AbdmPruneLogs.php` scrubs `request_json` and `response_json` after 2 days, but deletes rows after `--days=30` by default.
* **Action Plan:**
  * Change default retention in `app/Commands/AbdmPruneLogs.php` from `30` days to `730` days (2 years) so non-PII transaction metadata is preserved for audit compliance while payload JSON continues to be scrubbed after 2 days.

---

## 4. Live Server Context (`abdmlive.e-atria.net`)

* **Server:** `hms-live` (`10.8.0.1`)
* **URL:** `https://abdmlive.e-atria.net`
* **Path:** `/var/www/html/eatria_client/abdmlive`
* **Database:** `hms_abdmlive`
* **Hospital / Facility Name:** `Integrator Testing Lab`
* **Facility / HFR ID (`hfr_id`):** `IN0110005723`
* **Bridge `hospital_id`:** `21`

