# HMS Billing & Revenue Standards

This document defines the architectural and database rules for all OPD, IPD, Diagnostics, and Pharmacy billing in the E-Atria HMS.

---

## 1. Core Billing Table Schema & Roles

| Table Name | Primary Role | Key Columns |
| :--- | :--- | :--- |
| `invoice_master` | Master bill/invoice record for OPD, IPD, and Lab visits | `id`, `invoice_no`, `patient_id`, `doctor_id`, `total_amount`, `discount_amount`, `tax_amount`, `net_amount`, `paid_amount`, `balance_amount`, `status`, `payment_mode`, `created_date` |
| `invoice_item` | Line items for services, doctor visits, procedures, lab investigations | `id`, `invoice_id`, `item_type`, `item_id`, `item_name`, `item_rate`, `qty`, `amount`, `discount`, `net_amount`, `doctor_id`, `created_at` |
| `payment_history` | Multi-tender transaction log (Cash, UPI, Card, NetBanking, Cheque) | `id`, `invoice_id`, `patient_id`, `amount`, `payment_mode`, `transaction_ref`, `bank_source_id`, `received_by`, `payment_date` |
| `ipd_invoice_item` | Dynamic daily IPD charges (room rent, nursing, doctor rounds, procedures) | `id`, `ipd_id`, `item_type`, `item_id`, `item_name`, `item_rate`, `item_qty`, `item_amount`, `doc_id`, `doc_name`, `date_item` |
| `doc_opd_fee` | OPD consultation fee slab configured per doctor | `id`, `doc_id`, `doc_fee_type`, `doc_fee_desc`, `amount` |
| `doc_ipd_fee` | IPD visit fee slab configured per doctor | `id`, `doc_id`, `doc_fee_type`, `doc_fee_desc`, `amount`, `isdelete` |
| `sales_master` | Pharmacy sales invoice master | `id`, `bill_no`, `patient_id`, `opd_id`, `ipd_id`, `total`, `discount`, `net_amount`, `paid_amount`, `created_at` |

---

## 2. Inviolable Financial Integrity Rules

1. **Balance Equation Strictness**:
   For every invoice in `invoice_master`:
   $$\text{net\_amount} = \text{total\_amount} - \text{discount\_amount} + \text{tax\_amount}$$
   $$\text{balance\_amount} = \text{net\_amount} - \text{paid\_amount}$$
   - Any transaction modifying line items or applying discounts MUST atomically update `invoice_master.net_amount` and `invoice_master.balance_amount`.

2. **Every Receipt Must Have a Ledger Entry**:
   - Whenever money is collected (cash, UPI, card, bank transfer, advance), an entry MUST be inserted into `payment_history`.
   - Never update `invoice_master.paid_amount` without inserting a corresponding row in `payment_history`.
   - The sum of payments in `payment_history` for an `invoice_id` must always equal `invoice_master.paid_amount`.

3. **Status Transitions**:
   - `UNPAID`: $\text{paid\_amount} = 0$
   - `PARTIAL`: $0 < \text{paid\_amount} < \text{net\_amount}$
   - `PAID`: $\text{paid\_amount} \ge \text{net\_amount}$
   - `CANCELLED`: Only permitted when reversed with reason log and audit user ID.

4. **IPD Running Charges to Final Invoice**:
   - IPD patients accumulate daily charges in `ipd_invoice_item` (room tariff, daily doctor rounds, medication, nursing care).
   - Upon discharge billing, all unbilled items from `ipd_invoice_item` and approved advances from `payment_history` are aggregated into the final settlement invoice in `invoice_master`.
   - Once finalized, items in `ipd_invoice_item` must be marked as billed/settled to prevent double billing.

5. **Discount Governance**:
   - Any discount greater than zero requires `discount_reason` and must be authorized by an authorized user (`discount_approved_by`).
   - Doctors or cashiers cannot grant unauthorized ad-hoc discounts without an audit trail.

6. **Patient Full Name Standard**:
   - In invoices and receipts, the patient's name MUST be fetched from `patient_master.p_fname`.
   - Never query or write to `patient_master.p_lname` (permanently dropped).
