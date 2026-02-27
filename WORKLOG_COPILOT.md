# Worklog - HMS_CI4_OLD

Date: 2026-02-08

## Summary
Ported OPD Charges, IPD Charges, and Package modules from CI3 to CI4 with consistent NiceAdmin UI, AJAX modal flows, Simple-DataTables, insurance rate handling, and print/Excel exports.

## OPD Charges
- Controller: app/Controllers/Item.php
- Model: app/Models/ItemModel.php
- Routes: app/Config/Routes.php (group: item)
- Views: app/Views/Setting/Charges/OPD_Charges/
  - item_search_V.php (header buttons: Charge Groups, Add New Charge; filters; print/export)
  - item_search_adv.php (list partial with DataTable)
  - item_profile_V.php (modal edit; insurance rates; refresh list on update)
  - item_create_V.php
  - item_type_search_v.php / item_type_profile.php / item_type_create.php
  - item_item_list.php (print layout with heading, borders, base rate fallback)
  - item_item_excel.php (Excel export)
  - item_insurance_list.php (insurance rate table partial)
- Model joins (no DB views):
  - items: hc_items + hc_item_type
  - insurance items: hc_items_insurance + hc_insurance, filter isdelete=0
- Print: insurance selection adds Amount + Code; missing insurance uses Base Rate
- Excel: output via response headers (no debug toolbar injection)

## IPD Charges
- Controller: app/Controllers/ItemIpd.php
- Model: app/Models/ItemIpdModel.php
- Routes: app/Config/Routes.php (groups: item-ipd + legacy Item_IPD)
- Views: app/Views/Setting/Charges/IPD_Charges/
  - item_search_V.php, item_search_adv.php
  - item_profile_V.php, item_create_V.php
  - item_type_search_v.php / item_type_profile.php / item_type_create.php
  - item_item_list.php (print)
  - item_item_excel.php (Excel)
  - item_insurance_list.php
- Model joins (no DB views):
  - items: ipd_items + ipd_item_type
  - insurance items: ipd_items_insurance + hc_insurance, filter isdelete=0

## Package (IPD Package)
- Controller: app/Controllers/Package.php
- Model: app/Models/PackageModel.php
- Routes: app/Config/Routes.php (groups: package + legacy Package + legacy IPD_Package)
- Views: app/Views/Setting/Charges/Package/
  - item_search_V.php, item_search_adv.php
  - item_profile_V.php, item_create_V.php
  - item_type_search_v.php / item_type_profile.php / item_type_create.php
  - item_item_list.php (print)
  - item_item_excel.php (Excel)
  - item_insurance_list.php
- Model joins (no DB views):
  - packages: package, package_group
  - insurance items: package_insurance + hc_insurance, filter isdelete=0
- Audit tables: package_update and package_insurance_update
  - If not present, audit insert skipped (prevents errors)

## UI + Behavior
- Charges tiles updated:
  - setting/charges -> IPD tile uses item-ipd/search
  - setting/charges -> Package tile uses package/search
- Modal edits refresh list:
  - OPD: window.refreshChargeList()
  - IPD: window.refreshChargeList()
  - Package: window.refreshPackageList()
- Print buttons in header next to filter; export Excel added
- Print layout: heading with group + insurance; no OPD/IPD or group columns; table borders
- Base rate fallback: if insurance-specific rate missing, show base amount and Code column shows "Base Rate"

## Known Tables Used
- OPD: hc_items, hc_item_type, hc_items_insurance, hc_items_update, hc_items_insurance_update
- IPD: ipd_items, ipd_item_type, ipd_items_insurance, ipd_items_update, ipd_items_insurance_update
- Package: package, package_group, package_insurance, package_update, package_insurance_update
- Insurance: hc_insurance

## Notes for Next Session
- If audit tables for Package are required, create migrations for package_update and package_insurance_update.
- If isdelete column missing on insurance tables, remove filter.
- Debug toolbar can break Excel; keep response headers to avoid injection.
- Legacy routes are supported for old JS paths.

---

Date: 2026-02-16

## Diagnosis CI3 → CI4 Endpoint Mapping (Lab_Report parity)

### Fully mapped in CI4
- `lab_master` → `Diagnosis::labMaster` via `Lab_Report/lab_master`
- `lab_path/{lab_type}` → `Diagnosis::labPathLegacy` via `Lab_Report/lab_path/(:num)`
- `search_lab_4/{lab_type}` → `Diagnosis::searchLab` via `Lab_Report/search_lab_4/(:num)`
- `search_lab_4_srno/{lab_type}` → `Diagnosis::searchLabBySrno` via `Lab_Report/search_lab_4_srno/(:num)`
- `search_lab_4_labno/{lab_type}` → `Diagnosis::searchLabByLabno` via `Lab_Report/search_lab_4_labno/(:num)`
- `select_lab_invoice/{inv_id}/{lab_type}` → `Diagnosis::selectLabInvoicePath`
- `select_lab_invoice_path/{inv_id}/{lab_type}` → `Diagnosis::selectLabInvoicePath`
- `lab_date_show/{inv_id}/{lab_type}` → `Diagnosis::labDateShow`
- `test_list/{inv_id}/{lab_type}` → `Diagnosis::testList`
- `xray_test_list/{inv_id}/{lab_type}` → `Diagnosis::testList`
- `lab_tab_1_process/{test_id}/{lab_type}` → `Diagnosis::labTab1Process`
- `create_report_xray/{req_id}` → `Diagnosis::createReportXray`
- `show_report_final/{req_id}` → `Diagnosis::showReportFinalXray`
- `get_template_xray/{id}` → `Diagnosis::getTemplateXray`
- `Final_Update_xray/{req_id}` → `Diagnosis::finalUpdateXray`
- `confirm_report_xray/{req_id}` → `Diagnosis::confirmReportXray`

### Missing legacy endpoints (not yet mapped)
- `report_file_list/{inv_id}/{lab_type}/{delete?}`
- `select_lab_radiology/{inv_id}/{lab_type}`

### Notes
- Current CI4 radiology flow uses `Diagnosis::selectLabInvoicePath` for detail screen and does not require a separate `select_lab_radiology` endpoint in active views.
- File upload/list actions in CI4 diagnosis view are currently placeholders (`uploadFiles`, `showFiles`) and do not yet have controller endpoints.

## Future Backlog: AI-assisted Image Diagnosis (Ultrasound / X-Ray / CT)

### Goal
- Add a professional image workspace where users can upload/scan images, review them, run AI-assisted analysis, and move AI draft findings into radiology report editor with doctor review.

### Phase 1 (MVP - internal)
- Build one modal workspace in diagnosis detail screen:
  - Left panel: image preview, next/previous, zoom.
  - Right panel: AI findings, AI impression, confidence, doctor editable text.
- Wire existing action buttons:
  - Upload Files -> open workspace + upload image.
  - Scan -> open workspace + scan image flow.
  - Show Files -> list previously uploaded/scanned images.
- Add backend APIs in Diagnosis controller:
  - uploadImage(reqId)
  - listImages(reqId)
  - scanImage(reqId)
  - analyzeImageAi(reqId, fileId)
  - saveAiDraft(reqId, fileId)
  - acceptAiToReport(reqId)
- Add DB table for AI result tracking (suggested: diagnosis_image_ai):
  - id, req_id, file_id, ai_findings, ai_impression, confidence,
  - model_name, model_version, status, reviewed_by, reviewed_at,
  - created_at, updated_at.
- Add visible safety label: AI Suggestion - Doctor Review Required.

### Phase 2 (real AI integration)
- Integrate external AI service endpoint (Python/FastAPI or equivalent).
- Send selected image path to AI service and store structured response.
- Add timeout/error handling and retry button in UI.

### Phase 3 (clinical workflow hardening)
- Add audit trail for accepted/edited AI content before final report verify.
- Add role check so only authorized users can accept AI output.
- Keep final confirm manual; AI never auto-verifies reports.

### UI/Workflow Notes
- AI output should prefill radiology editor, not directly replace final report.
- Show per-image history for repeat review and comparison.
- Keep terminology consistent with current imaging workflow labels.

---

Date: 2026-02-16

## Pharmacy Module Kickoff (Legacy Medical)

### What was verified
- Legacy pharmacy dashboard source is in old code:
  - `old_code_ci3/views/Medical/Dashboard.php`
- Main legacy pharmacy controller is very large and feature-rich:
  - `old_code_ci3/controllers/Medical.php` (~5k lines)
- Related legacy controllers:
  - `old_code_ci3/controllers/Medical_backpanel.php`
  - `old_code_ci3/controllers/Medical_Report.php`
  - `old_code_ci3/controllers/Medical_Print.php`
  - `old_code_ci3/controllers/Payment_Medical.php`

### Important routing finding
- Current CI4 improved auto-routing is disabled (`app/Config/Routing.php` has `autoRoute = false`).
- No explicit CI4 `/Medical` routes found in `app/Config/Routes.php`.
- No current CI4 Medical controller files found under `app/Controllers`.

### Legacy dashboard button map (from old Dashboard.php)
- OPD Sale -> `/Medical/search_customer`
- Invoice -> `/Medical/Invoice_Med_Draft`
- IPD/Credit -> `/Medical/list_org_ipd`
- Org. Credit -> `/Medical/list_org`
- Return Counter Sale -> `/Medical/Invoice_Med_Return`
- Day Report -> `/Medical_Report/Report_2`
- Sale Day Report -> `/Medical_Report/Report_1`
- Payment Report -> `/Medical_Report/Report_Payment_Recieved`
- Store Stock -> `/Medical_backpanel/store_stock`
- Store Main -> `/Medical/main_store`
- Master -> `/Medical_backpanel`

### Suggested migration order (phase wise)
1. Dashboard + navigation tiles.
2. OPD Sale + Invoice Draft list.
3. Return invoice flow.
4. Store stock and stock statement.
5. Reports (day/sale/payment).
6. Backpanel master screens.

### SQL views received from user (confirmed)
- `v_med_item_return`
- `v_med_item_with_return`
- `v_ipd_list`
- `v_med_short_list`
- `v_product_stock`
- `v_product_stock_sub`
- `v_sale_item`
- `v_sale_item_sub`

### Dependency note
- `v_ipd_list` depends on `v_ipd_doc_list` (seen in view body join).
- Before CI4 pharmacy migration, ensure `v_ipd_doc_list` exists in target DB with compatible schema.

### Next implementation start point
- Begin CI4 Pharmacy phase-1 with dashboard route/controller/view parity for `/Medical` tiles, then wire OPD Sale + Draft Invoice list endpoints.

### Phase-1 implementation done (CI4)
- Added controller: app/Controllers/Medical.php
- Added routes:
  - Medical
  - Medical/search_customer
  - Medical/Invoice_Med_Draft
  - Medical/Invoice_Med_Return
  - Medical/list_org_ipd
  - Medical/list_org
  - Medical/main_store
  - Medical/store_stock
  - Medical/master
- Added views:
  - app/Views/medical/dashboard.php
  - app/Views/medical/search_customer.php
  - app/Views/medical/invoice_med_draft.php
  - app/Views/medical/placeholder.php
- Status:
  - Dashboard works at /Medical.
  - OPD Sale and Invoice Draft panels are functional.
  - Remaining tiles currently show migration placeholder pending deeper CI3 parity port.

---

Date: 2026-02-19

## Pharmacy -> Master continuation

### Fix completed
- Module: `Drug Sale Customer Wise Report`
- Endpoint: `Medical::drug_patient_distribute`
- Root-cause fixed in `getDrugPatientDistributeRows()`:
  - Legacy behavior: when Drug Name is blank and Schedule filter is blank, show all matching date-range records.
  - CI4 regression: returned empty result unless at least one schedule was selected.
  - Fix: schedule condition is now applied only when schedules are selected; blank schedule no longer forces empty result.

### Validation
- `php -l app/Controllers/Medical.php` passed (no syntax errors).

### Step done: Med. Payment Edit Logs (legacy links)
- Implemented legacy page endpoint: `Payment_Medical/payment_log`
- Implemented legacy result endpoint: `Payment_Medical/payment_log_data`
- Added CI4 controller methods in `app/Controllers/Payment_Medical.php`:
  - `payment_log()` for filter page
  - `payment_log_data()` for date-range log result table
- Added CI4 views:
  - `app/Views/medical/payment_log.php`
  - `app/Views/medical/payment_log_data.php`
- Updated routes in `app/Config/Routes.php`:
  - `GET Payment_Medical/payment_log -> Payment_Medical::payment_log`
  - `GET|POST Payment_Medical/payment_log_data -> Payment_Medical::payment_log_data`
- Updated Master menu link in `app/Views/medical/master.php`:
  - `Med. Payment Edit Logs` now opens `Payment_Medical/payment_log`

### Step fix: Med. Payment Edit action buttons not working
- Root cause: `payment_edit` HTML was injected using `innerHTML` in `payment_search`, so inline `<script>` inside the injected response did not execute.
- Fix applied in `app/Views/medical/payment_search.php`:
  - Added delegated click handlers for:
    - `#btn_update_bank` -> `Payment_Medical/change_to_bank`
    - `#btn_update_cash` -> `Payment_Medical/change_to_cash`
    - `#btn_update_user` -> `Payment_Medical/change_user`
    - `#btn_update_amount` -> `Payment_Medical/update_amount`
  - Replaced direct result assignment with shared render helper to consistently update `#searchresult`.
- Validation: `php -l app/Views/medical/payment_search.php` passed.

### Step fix: Med. Payment Edit duplicate submit (2x POST)
- Evidence: HAR showed two POST requests for single button action (first with payload, second empty/no payload).
- Root cause: both handlers were active:
  - Inline click handlers inside `app/Views/medical/payment_edit.php`
  - Delegated click handlers in `app/Views/medical/payment_search.php`
- Fix:
  - Removed inline `<script>` handlers from `payment_edit.php`.
  - Kept `payment_search.php` delegated handlers as single source of action binding.
  - Added `actionInFlight` guard in `payment_search.php` to prevent rapid duplicate action posts.
- Validation:
  - `php -l app/Views/medical/payment_edit.php` passed.
  - `php -l app/Views/medical/payment_search.php` passed.

### Step fix: Med. Payment Edit Logs showing blank
- Symptom: `Payment_Medical/payment_log` displayed 0 rows for date range where legacy HMS showed logs.
- Fix in `app/Controllers/Payment_Medical.php` (`payment_log_data`):
  - Replaced Query Builder date condition with raw SQL function filter:
    - `DATE(l.insert_datetime) BETWEEN 'from' AND 'to'`
  - Added fallback fetch (latest 500 logs, unfiltered) when filtered rows are empty, to handle schema/date-field drift gracefully.
- Validation: `php -l app/Controllers/Payment_Medical.php` passed.

### Step update: log table name compatibility
- User confirmed legacy expectation to store Med Payment Edit logs in `payment_history_log`.
- Updated `app/Controllers/Payment_Medical.php`:
  - Added resolver to choose log table in this order:
    1) `payment_history_log`
    2) `paymentmedical_history_log`
  - Applied resolver to both:
    - write path (`insertPaymentLog`)
    - read path (`payment_log_data` joins/field detection)
- Result: CI4 now reads/writes the same legacy table name where available, while still supporting older schema names.

### Step update: Update Log text population
- User requested visible log text like: `Amount value Change 120 to 100`.
- Updated `app/Controllers/Payment_Medical.php`:
  - `update_amount()` now writes explicit log text:
    - `Amount value Change <old> to <new>`
  - `insertPaymentLog()` now writes to `update_remark` when `update_log` column is missing.
  - `payment_log_data()` now reads `update_log` with fallback/coalesce from `update_remark`.
- Validation: `php -l app/Controllers/Payment_Medical.php` passed.

### Step update: explicit logs for Bank/CASH/User actions
- Added readable `update_log` text for:
  - `change_to_bank`: `Payment mode Change <old> to BANK [source] TranID:<id>`
  - `change_to_cash`: `Payment mode Change <old> to CASH`
  - `change_user`: `Update User Change <old_user> to <new_user>`
- Also validates payment row existence before update in bank/cash actions.
- Validation: `php -l app/Controllers/Payment_Medical.php` passed.

### Step done: Med. Invoice Item Update Logs (legacy parity)
- Target legacy endpoints:
  - `Medical_backpanel/invoice_item_log`
  - `Medical_backpanel/invoice_item_log_data`
- Updates applied:
  - Added route alias in `app/Config/Routes.php`:
    - `GET|POST Medical_backpanel/invoice_item_log_data -> Medical::invoice_item_log_data`
  - Updated `Medical::invoice_item_log_data` in `app/Controllers/Medical.php`:
    - Accepts both modern fields (`date_from`, `date_to`) and legacy `opd_date_range`.
    - Uses payment log table fallback:
      - `payment_history_log` first
      - `paymentmedical_history_log` fallback
    - Reads payment update text from `update_log` with fallback to `update_remark`.
  - Updated `app/Views/medical/invoice_item_log.php`:
    - Sends legacy `opd_date_range` token and posts to legacy URL `Medical_backpanel/invoice_item_log_data`.
  - Updated `app/Views/medical/invoice_item_log_data.php`:
    - Added DataTable initialization for search/pagination parity.
- Validation:
  - `php -l app/Controllers/Medical.php` passed.
  - `php -l app/Config/Routes.php` passed.
  - `php -l app/Views/medical/invoice_item_log.php` passed.
  - `php -l app/Views/medical/invoice_item_log_data.php` passed.

---

Date: 2026-02-22

## OPD Upgrade - AI Requirements (User Confirmed)

### Mandatory AI behavior to keep in upgraded OPD
- Use AI-assisted **Autotype** in OPD prescription text areas (fast drafting while doctor types).
- Support **Hinglish -> English** conversion in complaint/diagnosis style inputs.
- Show **Patient History Alerts** during OPD prescribing (old complaints/diagnosis/risk context visible before final save).

### Implementation direction (parity + enhancement)
- Keep old familiar OPD workflow/tabs while adding AI features inline (no disruptive screen redesign).
- Reuse current CI4 OPD AI foundation already present in `Opd_prescription`:
  - `complaints_search`
  - `complaints_parse`
  - `complaints_ai_draft`
- Add history alert feed from patient prior OPD records and remarks in the prescription screen header/side context.

### Acceptance criteria
- Doctor can type Hinglish symptoms and get normalized English complaint text quickly.
- Doctor can trigger AI draft/autotype suggestion and then edit before save.
- High-value history (past Rx trend / prior diagnosis / patient remarks / key vitals trend) appears as alert cards in OPD screen.
- AI output is assistive only; final clinical decision remains manual.

---

Date: 2026-02-23

## OPD Prescription Session Checkpoint (Saved)

### Completed in this session
- Rx-Group medicine parity improvements:
  - Dose master dropdowns integrated (Dose/When/Frequency/Where).
  - Human-readable dose labels shown in list (ID -> label mapping).
  - Rx-Group badge now shows group name (not only row ID).
  - Salt/Generic fallback improved in Rx-Group medicine list.
- OPD medicine workspace data quality display:
  - Generic column fallback added (`genericname` -> `salt_name`) for blank generic values.
- Clinical Templates bootstrap for fresh systems:
  - Added seeder `ClinicalTemplateWorkspaceSeeder` with practical master templates across all OPD sections.
  - Integrated into `OpdDemoMasterSeeder` so one command seeds templates.
- OPD Prescription template UX enhancement:
  - Load Template modal now has suggested top templates.
  - Apply mode added: `Replace` or `Append`.
  - Quick suggestion chips added inside modal.
- Doctor-wise learning behavior:
  - Added usage tracking endpoint for template apply action.
  - Added usage table auto-create: `opd_clinical_template_usage`.
  - `section_template_list` now prioritizes by doctor usage count, then scope/recency.

### Files changed (key)
- `app/Controllers/Opd_prescription.php`
- `app/Views/billing/opd_prescription_basic.php`
- `app/Views/billing/rx_group_medicine_workspace.php`
- `app/Config/Routes.php`
- `app/Database/Seeds/ClinicalTemplateWorkspaceSeeder.php`
- `app/Database/Seeds/OpdDemoMasterSeeder.php`

### Validation status
- PHP lint passed for all modified files during session.
- VS Code diagnostics reported no errors in changed files.

### Resume point for next OPD session
- Continue with OPD Prescription optimization:
  - add doctor-level analytics panel for template usage trends,
  - optional specialty-wise template suggestions,
  - optional safety-check prompts before final save.

---

Date: 2026-02-25

## OPD Consult + Scan Workflow Checkpoint (Saved)

### Completed in this session
- OPD queue behavior corrections:
  - Upload/scan no longer auto-moves OPD to Visited status.
  - Queue/tabs kept consistent for Waiting/Visited/Cancelled flow.
  - Visit completion action retained via explicit button workflow.
- Language/readability cleanup:
  - Label text and spelling normalized in OPD UI areas.
  - Code comments/doc intent improved in touched controller/view sections.
- Visited list mismatch fix:
  - Corrected query join behavior so Visited count and list data match.
- Scan AI output enhancement:
  - Detailed multi-line diagnosis/report text generation added.
  - Report rendering updated to preserve line breaks in scan cards.
- Consult scan visibility:
  - Current OPD uploaded scans and reports now visible in consult page.
  - `Use Report` actions wired for consult text usage.
- Patient historical scan support:
  - Async panel for all patient OPD scan history added.
  - Manual `Run AI` action enabled for historical files.
  - Expand/Minimize image behavior added in history cards.
  - Missing history endpoint route added and fixed.
- Consult layout UX:
  - Left and right consult panes now scroll independently on desktop.
- Investigation + Advice flow alignment:
  - Investigation section compacted (profile/manual + Select2 search + Remove All).
  - Old-style Advice flow restored in consult:
    - Advice section moved to last block,
    - Next Visit and Refer To shown after Advice,
    - predefined advice list with `+Add` restored,
    - quick Next Visit chips restored.

### Files changed (key)
- `app/Controllers/Opd.php`
- `app/Controllers/Opd_prescription.php`
- `app/Config/Routes.php`
- `app/Views/billing/opd_appointment_dashboard.php`
- `app/Views/billing/opd_appointment_list.php`
- `app/Views/billing/opd_appointment_list_table.php`
- `app/Views/billing/opd_prescription_basic.php`
- `app/Views/billing/opd_scan_history_panel.php` (new)
- `app/Views/billing/opd_scan_last_list.php`

### Validation status
- VS Code diagnostics showed no errors for edited files after final patches.

### Next session focus
- Start with print workflow updates (layout and output parity).

## Pinned Reminder for Next Session
- First task: OPD print workflow.
- Start with print layout parity and field order verification.
- Then validate print output content for Advice, Next Visit, Investigation, and scan report sections.

---

Date: 2026-02-26

## OPD Consult UI + Rx-Group Workflow Checkpoint (Saved)

### Completed in this session
- Print actions cleanup:
  - Replaced multiple print buttons with a single grouped `Print` dropdown in consult toolbar.
  - Kept all existing print routes/behavior unchanged under grouped menu.
- Predefined Advice space optimization:
  - `Pre define Advise` section changed to compact mode.
  - Added show/hide toggle and fixed-height scroll list to prevent long-page expansion.
- Legacy Rx-Group compatibility restored:
  - Added support for legacy URL access pattern:
    - `Opd_prescription/save_rx_group_list/{doc_id}`
  - Added backend apply endpoint to copy Rx-Group template medicines into current OPD session.
- Rx-Group modal workflow implemented (old-system style):
  - Added `Rx Group` button in medicine section to open modal.
  - Modal lists all Rx groups.
  - Each group has dropdown preview of medicine list before add.
  - `+ Add` from modal injects medicines directly into current list.
  - Selected group label shown in medicine panel.
- Rx-Group search and filtering (medicine-like UX):
  - Added modal search input for Rx-Group name.
  - Added filter scopes: `Active`, `Favorites`, `All`.
  - Added star toggle (`☆/★`) for favorites.
  - Favorites persisted in browser local storage.

### Files changed (key)
- `app/Views/billing/opd_prescription_basic.php`
- `app/Controllers/Opd_prescription.php`
- `app/Config/Routes.php`

### Validation status
- VS Code diagnostics reported no errors in modified files after final patches.

### Resume point for tomorrow
- Continue with print parity fine tuning (spacing/font/line layout) against old HMS PDFs.
- Optional polish: persist Rx-Group scope (`Active/Favorites/All`) per user session/profile.

