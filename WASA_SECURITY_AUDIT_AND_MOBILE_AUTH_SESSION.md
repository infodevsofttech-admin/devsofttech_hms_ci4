# WASA Security Audit, Mobile Device Authorization & System Hardening Session Handover

**Date:** 2026-10-15  
**Branch:** `main`  
**Latest Commit:** `de4f658` — `feat(security): implement WASA audit mitigations, mobile anti-enumeration & device authorization, and user management fixes`  
**Live Environment:** [demo.e-atria.net](https://demo.e-atria.net/) (`10.8.0.1` / `/var/www/html/hms_etria`)  
**Status:** **All Fixes Implemented, Verified, Committed, Pushed, and Deployed Live**

---

## 1. Executive Summary

This session addressed critical security recommendations from the **WASA (Web Application Security Audit)** assessment (`scratch/E-atria_WASA_Observations.xlsx`), overhauled the mobile PWA authentication architecture for nursing and medical staff to eliminate identity enumeration, resolved user creation validation issues, and deployed all updates to the production demonstration server (`https://demo.e-atria.net/`).

---

## 2. Key Implementations & Resolved Areas

### A. WASA Audit Remediations (4 Key Observations)

| # | Observation | Severity / CWE | Key Technical Remediation |
|---|-------------|----------------|---------------------------|
| **1** | **Sensitive Information Exposure via API** | Medium / High<br>`CWE-200` | Permanently stripped `app_pin` from all API response payloads (`NursingApi.php` & `DoctorApi.php`). Replaced plain PINs with Bcrypt hashing (`PASSWORD_BCRYPT`). Enforced field minimization (only `id`, `name`, `department`). |
| **2** | **Use of Outdated / Unsupported JS Libraries** | Medium<br>`CWE-1104` | Isolated rich text editors (CKEditor 4.22.1 / TinyMCE) strictly to authenticated administrative template management. Added server-side HTML Purifier filtering and output encoding (`esc()`). Hardened DataTables callback rendering against DOM-based XSS. |
| **3** | **Hidden API Exposure & Sensitive Data Leakage** | Medium<br>`CWE-200` | Removed unauthenticated enumeration endpoints (`/api/v1/nursing/nurses`, `/api/v1/doctor/list`). Enforced HMAC `device_token` authorization, intranet subnet verification (`isHospitalIntranet()`), and brute-force rate-limiting (`checkRateLimit()`). |
| **4** | **Missing HTTP Security Headers** | Low / Medium<br>`CWE-693` | Created and globally registered `app/Filters/SecureHeadersFilter.php`. Injects: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: geolocation=(), camera=(self), microphone=()`, and `Strict-Transport-Security: max-age=31536000; includeSubDomains`. Enforced `HttpOnly`, `SameSite=Lax`, and `Secure` cookie flags. |

---

### B. Mobile App Anti-Enumeration & Device Security Redesign

- **Problem Addressed:** Anyone scanning a hospital mobile app QR code was presented with a staff profile dropdown listing doctor/nurse names and designations before authenticating.
- **Frontend Changes (`public/App/Nursing/index.html` & `public/App/Doctor/index.html`):**
  - Completely eliminated public staff dropdowns and unauthenticated profile-fetching API calls.
  - **First-Time Device Pairing:** Staff must enter their **Staff ID / Doctor ID** + a 6-digit **Device Security Code** (issued by the HMS Admin) + **PIN/Password**.
  - **Quick Unlock for Paired Devices:** Device stores an HMAC-signed `device_token`. The user simply inputs their 4-digit PIN to unlock. Includes a clean *"Switch User / Re-pair Device"* option.
- **Administrative Master Management:**
  - **Views:** `Nurse_Search_V.php`, `Doctor_Search_V.php`, and `Doctor_profile_V.php` now display 6-digit **Device Security Code** badges, copy buttons, clean pairing instruction cards in QR modals, and instant "Regenerate" actions.
  - **Models:** `NurseModel.php` and `DoctorModel.php` handle automatic generation and verification of unique 6-digit device security codes.
- **Header QR Standees (`app/Views/partials/header.php`, `welcome_message.php`, `login.php`):**
  - Removed staff pre-selection logic and cleaned QR standee printable posters.

---

### C. CSRF Protection & "Action Not Allowed" Fixes

- **CSRF Engine:** In `app/Config/Security.php`, enabled session-based CSRF protection (`$csrfProtection = 'session'`, `$regenerate = true`).
- **Global Interceptor (`public/assets/js/csrf-guard.js`):**
  - Intercepts all jQuery AJAX (`ajaxSend`, `ajaxComplete`), Axios, and native `window.fetch()` requests.
  - Injects `X-CSRF-TOKEN` and keeps form hidden inputs synchronized across SPA and AJAX actions.
- **Fixed Specific Endpoints:**
  - `app/Views/diagnosis/pathology_detail.php`: X-Ray removal AJAX actions now include CSRF tokens and handle dynamic token refreshes.
  - `app/Views/billing/ipd/panel_discharge.php`: IPD discharge form validation and submission secured with synchronized CSRF tokens.

---

### D. User Management & Creation Validation Fixes

- **Validation Rules (`app/Config/Auth.php`):**
  - Updated `$usernameValidationRules` to permit hyphens and underscores: `regex_match[/\A[a-zA-Z0-9\._-]+\z/]`.
- **Controller Logic (`app/Controllers/Setting/UserManagement.php`):**
  - Aligned username regex with Auth config.
  - Added automatic fallback email `<username>@hospital.local` when registering staff accounts without personal emails.
- **UI Enhancements (`app/Views/Setting/Admin/UserManagement/create.php`):**
  - Added password eye show/hide toggle.
  - Added a quick *"Set Staff@123"* button for rapid onboarding.
  - Added client-side pre-submit validation preventing cryptic backend submission failures.

---

### E. IDE Environment & MCP Configuration

- **Issue:** IDE showed `MCP Error: android-management-api: calling "initialize": rejected by transport: oauth2: "invalid_grant" "Bad Request"`.
- **Root Cause:** Global MCP config contained an entry for `android-management-api` configured with `google_credentials`. Since the Google OAuth refresh token for that specific enterprise API was expired/revoked, the IDE failed on initialization.
- **Resolution:** Removed the unused `android-management-api` entry from `C:/Users/d_s_b/.gemini/config/mcp_config.json`. Verified valid JSON format. Error banner resolved.

---

## 3. Deployment & Git Status

1. **Commit:** `de4f658` pushed to `origin/main`.
2. **Server:** Live deployment completed on `demo.e-atria.net` (`root@10.8.0.1` / `/var/www/html/hms_etria`):
   - `git pull origin main` executed (Fast-forward 26 files changed, 1640 insertions, 299 deletions).
   - CodeIgniter 4 CLI database migrations executed cleanly (`spark migrate --namespace App`).
   - Cache cleared (`spark cache:clear`).
   - Verified HTTP security headers (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`) live on `https://demo.e-atria.net/`.

---

## 4. Key Files Created / Modified

| File | Type | Purpose |
|------|------|---------|
| `app/Filters/SecureHeadersFilter.php` | New | Global response filter enforcing OWASP security headers |
| `public/assets/js/csrf-guard.js` | New | Global CSRF interceptor for jQuery, Axios, and Fetch |
| `app/Config/Filters.php` | Modified | Registered `secureheaders` filter globally |
| `app/Config/Security.php` | Modified | Session CSRF and secure cookie configuration |
| `app/Config/Auth.php` | Modified | Expanded username regex allowing `._-` characters |
| `app/Controllers/Api/v1/NursingApi.php` | Modified | Device code pairing, bcrypt PINs, token auth & rate limits |
| `app/Controllers/Api/v1/DoctorApi.php` | Modified | Device code pairing, bcrypt PINs, token auth & rate limits |
| `app/Controllers/Setting/UserManagement.php` | Modified | Fallback email generation and username validation |
| `app/Views/Setting/Admin/UserManagement/create.php` | Modified | Eye toggle, quick password helper, pre-validation |
| `app/Views/Setting/Nurse/Nurse_Search_V.php` | Modified | Device security code badges, copy & pairing modal |
| `app/Views/Setting/Doctor/Doctor_Search_V.php` | Modified | Device security code badges, copy & pairing modal |
| `app/Views/Setting/Doctor/Doctor_profile_V.php` | Modified | Device security code display & regeneration |
| `public/App/Nursing/index.html` | Modified | 3-factor device registration & quick PIN unlock PWA |
| `public/App/Doctor/index.html` | Modified | 3-factor device registration & quick PIN unlock PWA |
| `app/Views/diagnosis/pathology_detail.php` | Modified | CSRF token injection on X-ray delete actions |
| `app/Views/billing/ipd/panel_discharge.php` | Modified | CSRF token injection on IPD discharge form |
| `deploy.sh` | Modified | Added `hms-live` SSH host configuration support |
| `C:/Users/d_s_b/.gemini/config/mcp_config.json` | Modified | Removed faulty `android-management-api` MCP server |
