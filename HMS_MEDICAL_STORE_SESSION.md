# HMS Multi-Building Medical Store & ABDM Pharmacy System
**Session Name:** `HMS Multi-Store Pharmacy & ABDM Integration`  
**Antigravity Conversation ID:** `3731d0c5-f4df-435e-b54f-f690562e18e3`  
**Last Updated:** September 8, 2026

---

## Quick Resume Instructions After Computer Restart

### 1. In Antigravity IDE
1. Open Antigravity IDE and reopen the workspace `HMS_CI4_OLD`.
2. In the Chat panel, click the **Chat History / Clock icon** (or open the left conversation drawer).
3. Select this session: **`HMS Multi-Store Pharmacy & ABDM Integration`** (ID: `3731d0c5-f4df-435e-b54f-f690562e18e3`).
4. Type your message and continue seamlessly!

*Tip: If you ever open a new chat, you can type `@HMS_MEDICAL_STORE_SESSION.md` or `@conversation` to instantly restore all context.*

---

## System Access & Credentials Summary

### Development Server
- Command: `php spark serve` (in `d:\Workplace\HMS_CI4_OLD`)
- Server URL: `http://localhost:8080`

### HMS Admin Management
- **URL:** [http://localhost:8080/setting/admin/medical-store](http://localhost:8080/setting/admin/medical-store)
- **Features:** Store configuration, DL 20B/21B licenses, GSTIN, Pharmacist details, ABDM HFR/HIP/HPR registries, 60-min OTP generator, terminal device authorization, and Marg ERP CSV import.

### Direct Counter Desks (React PWA)
- **Counter A (Main Hospital Block A):**
  - Direct Link: [http://localhost:8080/MedicalStore/storeA](http://localhost:8080/MedicalStore/storeA)
  - Store Code: `ST-MAIN`
  - Permanent Security Key: `STORE-SEC-A91B4C72`
  - ABDM HFR ID: `IN0710001234`
  - Pharmacist HPR ID: `91-8822-4411-9901`
- **Counter B (OPD Block B):**
  - Direct Link: [http://localhost:8080/MedicalStore/storeB](http://localhost:8080/MedicalStore/storeB)
  - Store Code: `ST-OPD-B2`
  - Permanent Security Key: `STORE-SEC-B84F29D1`
  - ABDM HFR ID: `IN0710001234-OPD`
  - Pharmacist HPR ID: `91-8822-4411-9902`

---

## Core Technical Reference

1. **Zero Legacy Disturbance:**
   - Legacy pharmacy tables (`invoice_med_master`, `inv_med_item`, `med_*`) and controllers remain 100% untouched.
   - All new tables use the **`mst_*`** prefix (15 tables).
2. **ABDM Compatibility (M1 & M2):**
   - Patient search by 14-digit ABHA Number or ABHA Address.
   - Care Context generation: `PHARM-{store_code}-{invoice_no}`.
   - NRCES FHIR R4 Bundle profile: `https://nrces.in/ndhm/fhir/r4/StructureDefinition/MedicationDispenseDocument`.
   - Coded with SNOMED-CT clinical terminology.
   - Gateway Bridge queue integration: `abdm_sync_record`.
   - Bundle preview endpoint: `GET /api/v1/medical-store/abdm/bundle/{sale_id}`.
3. **Multi-Format Invoices:**
   - 80mm Thermal Slip (Fast 1–5 item OTC/OPD).
   - A5 Invoice (Normal OPD/Walk-in with GST summary).
   - A4 Hospital Tax Invoice (IPD admissions & large bills).
4. **Marg ERP Migration:**
   - Supported CSV import with auto-detected columns. Sample test file: `scratch/test_marg_sample.csv`.
5. **Double-Entry Accounting & Statutory Drug Register:**
   - Automated ledger postings, Daily Cash Book, GSTR-1 outward reports, and mandatory Schedule H1 drug register.
6. **Retail Loose Tablet & Unit Dispensing:**
   - Exact per-tablet pricing (`Strip MRP / units_per_pack`), exact stock deduction in individual tablets, and support for Loose Tab, Strip, and Combo sales.
7. **Split / Mixed Payment Mode & Audit:**
   - Supports partial payments (e.g. ₹35 Cash + ₹100 UPI) with 12-digit UTR and bank name capturing for bank reconciliation and audit.
8. **Keyboard-First Zero-Mouse POS Workflow (Marg ERP Standard):**
   - High-contrast single-line Quick Keys ribbon.
   - Hotkeys: `F2` (Medicine Search), `F4` (Patient Search), `F7` (Whole Discount), `F8` (Payment Mode), `F9 / Ctrl+Enter` (Complete Sale & Print), `Alt+T` (Loose Tab), `Alt+S` (Strip), `Shift+Delete` (Remove Item).
   - Auto-focus on Medicine Search on page load; `Enter` on quantity immediately commits and jumps focus back to Medicine Lookup for the next scan.
9. **Compact "Qty & Unit" Cell:**
   - Single-line input group with type selector dropdown (`Tab`, `Strip`, `Str+Tab`) positioned before the quantity box.
   - Automatically defaults to `Tab` (loose units).

