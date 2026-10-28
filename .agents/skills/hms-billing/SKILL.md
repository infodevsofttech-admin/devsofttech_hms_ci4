---
name: hms-billing
description: Use when building, debugging, modifying, or testing HMS billing workflows, including OPD consultation fees, IPD running charges, final discharge settlement, lab and radiology billing, pharmacy sales, multi-tender payments, and package management.
---

# HMS Billing & Financial Management Skill

This skill guides the agent through the complete billing workflows, ledger integrity, database structures, and calculation formulas in E-Atria HMS.

---

## 1. Overview of Billing Workflows

E-Atria HMS manages four distinct revenue streams:
1. **OPD Billing**: Doctor consultation fees, registration charges, minor OPD procedures, emergency day care.
2. **IPD Billing**: Bed charges, nursing fees, doctor daily rounds, ICU monitoring, OT/Surgery fees, package tariffs, final discharge settlement.
3. **Diagnostics Billing**: Laboratory pathology investigations, radiology imaging procedures.
4. **Pharmacy Sales**: Inpatient and outpatient drug dispensing, prescription fulfillment.

---

## 2. Key Database Tables

### Master & Transactional Tables
- `invoice_master`: Central invoice header containing patient reference, dates, totals, discounts, taxes, paid amount, and outstanding balance.
- `invoice_item`: Line items for services billed under an `invoice_master` record (`item_type`: 1=Consultation, 2=Procedure, 3=Lab, 4=Radiology, 5=Nursing, etc.).
- `payment_history`: Ledger recording every monetary transaction (Cash, UPI, Credit/Debit Card, NetBanking, Cheque, Advance Deposit).
- `ipd_invoice_item`: Running IPD ledger updated dynamically as daily nursing charges, doctor visits, and medications are consumed.
- `doc_opd_fee`: Doctor consultation fee schedule by fee type (`1`=New Patient, `2`=Revisit, `3`=Running, `4`=Emergency, `5`=Night/Off-time).
- `doc_ipd_fee`: Doctor IPD visit fee schedule (`1`=Regular Ward Visit, `2`=ICU Visit, etc.).
- `doc_fee_type`: Descriptive lookup for fee categories.
- `hospital_bank_payment_source`: Configured bank accounts / POS machines / UPI QR accounts for financial auditing.
- `ayushman_package_master`: Standard PM-JAY package procedure pricing.

---

## 3. OPD Consultation Billing Workflow

1. **Patient Lookup / Registration**:
   - Check if patient exists via `patient_master.p_fname`, `mphone1`, or ABHA ID.
   - If new, insert into `patient_master` (storing full name strictly in `p_fname`).
2. **Doctor & Fee Selection**:
   - Select doctor from `doctor_master`.
   - Fetch active consultation fee from `doc_opd_fee` for selected `doc_fee_type` (e.g. New Patient = ₹500, Revisit = ₹300).
3. **Invoice Generation**:
   - Insert invoice in `invoice_master` with `status = 'PAID'` (or `'PARTIAL'`).
   - Insert corresponding item row into `invoice_item`.
4. **Payment Recording**:
   - Insert transaction row in `payment_history` with `payment_mode`, `amount`, and `received_by`.
5. **OPD Token / Receipt Printing**:
   - Generate thermal or A4 receipt with hospital header, UHID, Doctor Name (`Dr. {p_fname}`), Token No, and QR code.

---

## 4. IPD Running Charges to Final Settlement

1. **Daily Accumulation**:
   - Nursing staff and ward managers post daily items into `ipd_invoice_item`:
     - Room/Bed tariff (based on `bed_master.bed_category_id`).
     - Daily doctor visit charges (from `doc_ipd_fee`).
     - Bedside monitoring, injections, consumables (`nursing_ipd_charges`).
2. **Advance Payments**:
   - Inpatients make advance deposits during hospital stay.
   - Advances are logged in `payment_history` linked to the patient's `ipd_id`.
3. **Discharge Settlement**:
   - Upon discharge approval, the billing clerk generates the final settlement:
     $$\text{Gross Total} = \sum \text{ipd\_invoice\_item.item\_amount}$$
     $$\text{Net Due} = \text{Gross Total} - \text{Approved Discount} + \text{Taxes} - \sum \text{Advance Payments}$$
   - The final invoice is created in `invoice_master`, and remaining balance is collected via `payment_history`.
   - The `ipd_master.discharge_date` and `ipd_master.status = 'discharged'` are marked.

---

## 5. Safe Modification & Debugging Rules

- **Zero-Sum Balance Rule**: Never update `invoice_master.paid_amount` or `balance_amount` manually without validating that $\sum \text{payment\_history.amount} == \text{invoice\_master.paid\_amount}$.
- **Patient Name**: Always select `patient_master.p_fname`. Column `patient_master.p_lname` does not exist.
- **Doctor Share**: Doctor commission calculations must be derived from `invoice_item.doctor_id` and verified against active `finance_doctor_agreements`.
