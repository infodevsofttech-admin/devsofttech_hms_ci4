# HMS Software Complete Help Guide

Version: 1.0  
Last Updated: 2026-04-02

## 1. Introduction
This document is the complete usage help for HMS CI4 software. It covers:
- Login and navigation
- OPD, IPD, Billing, Diagnosis, Pharmacy, Finance and Reports
- Hospital Stock Management
- Admin setup and permissions
- Security, compliance, backup and troubleshooting

## 2. Login and Navigation
1. Open the HMS login page.
2. Enter your authorized credentials.
3. After login, use:
- Top bar: profile, help, server time
- Left sidebar: module navigation
- Main panel: module workspace

## 3. User Roles and Permissions
HMS works with permission-based access.
Common groups:
- Superadmin/Admin/Developer: full access
- Billing users
- Diagnosis users
- Finance users
- Stock Manager
- Department Head
- Storekeeper

If you get "Access denied", ask admin to map proper permissions in user management.

## 4. Core Daily Workflow
1. Register or search patient.
2. Create OPD/IPD service entries.
3. Add charges and generate invoice.
4. Collect payment / post credit.
5. Process diagnostics and publish reports.
6. Manage medicines/stock issue/receipts.
7. Run end-of-day reconciliation reports.

## 5. Billing Module Usage
Sections:
- Patient List
- OPD Invoice
- Charges Invoice
- IPD Billing
- Payment Request
- Refund Request

Best practice:
- Verify patient identity before posting charges.
- Use correction/cancel flow with remark where applicable.
- Print and archive critical payment documents.

## 6. IPD and Nursing Usage
- Use IPD panel for admission lifecycle.
- Capture nursing entries and bedside/visit charges.
- Verify discharge process before invoice finalization.
- Use print modes for final discharge and billing outputs.

## 7. Diagnosis Module Usage
Supports pathology and imaging report workflows.
Typical flow:
1. Select lab invoice/test.
2. Enter or import findings.
3. Save draft.
4. Verify/finalize report.
5. Print final report.

Governance:
- Use verification flow for report authenticity.
- Keep edit reasons where audit policy requires.

## 8. Pharmacy/Medical Usage
- Search customer/patient and open invoice.
- Add items, apply discount per policy.
- Confirm payment and print invoice.
- Use return and stock tools for inventory control.

## 9. Finance Module Usage
Major sections:
- Vendor master
- Purchase order
- GRN entry
- Vendor invoice booking
- Cash and disbursement SOP
- Doctor payout workflow
- Bank deposit register
- Compliance report

Control points:
- Maker-checker where required.
- Keep documentary references in remarks.
- Run periodic compliance report.

## 10. Hospital Stock Management Usage
Path: Setting -> Hospital Stock

Menu sections:
- Dashboard
- Item Masters
- Indent & Issue
- Purchase
- Reports

### 10.1 Item Masters
Configure:
- Category, Supplier, Item
- Item code and item type
- UOM / Purchase UOM / Issue UOM
- Issue per Purchase ratio
- Min stock / reorder / current stock
- Unit cost / expiry / daily use / location

Example:
- Purchase UOM: Box
- Issue UOM: Piece
- Issue per Purchase: 100
=> 1 Box = 100 Pieces

### 10.2 Indent and Issue
1. Department creates indent.
2. Authorized user approves indent.
3. Storekeeper issues against approved quantity.
4. System marks as issued or partial_issued based on stock.

### 10.3 Purchase and Receive
1. Create PO with supplier.
2. Add PO item lines and rates.
3. Receive PO.
4. Stock and ledger update automatically.

### 10.4 Dashboard and Reports
Dashboard focus:
- New requests
- Near-expiry used items
- Short items without replenishment
- Daily-use item status

Reports:
- Department consumption
- Monthly issue
- Near expiry
- Low/reorder alerts

## 11. Settings and Master Configuration
From Settings module, admins can manage:
- Users and permissions
- Doctors and reference masters
- Bank and payment source masters
- Bed management
- Report/template settings
- Stock module access and masters

## 12. Report Usage
Use Report panel for:
- Billing collection reports
- Diagnosis reports
- Insurance/credit reports
- Audit/compliance reports

Guideline:
- Use date filters carefully.
- Export and archive monthly reports.

## 13. Security and Compliance Guidelines
- Do not share login credentials.
- Use individual user IDs only.
- Keep audit trail remarks meaningful.
- Follow local data protection and healthcare retention requirements.
- Restrict production DB direct edits.

## 14. Backup and Recovery SOP
Daily:
- Database backup
- Verify writable directory health

Weekly:
- Test backup restore on staging
- Check storage and log growth

Before deployment:
- Apply migrations in order
- Run smoke test for critical modules

## 15. Common Troubleshooting
1. Access denied:
- Verify role permissions.

2. Screen not loading:
- Check route URL, session validity, browser console/network.

3. Wrong totals/stock values:
- Verify workflow actions and unit conversion fields.

4. Report print mismatch:
- Verify template/print settings.

5. Upload or file errors:
- Check folder permissions and PHP upload limits.

## 16. Technical References
Main stock files:
- app/Controllers/Setting/HospitalStock.php
- app/Views/Setting/Stock/index.php
- app/Views/Setting/Stock/dashboard.php
- app/Views/Setting/Stock/masters.php
- app/Views/Setting/Stock/indents.php
- app/Views/Setting/Stock/purchase.php
- app/Views/Setting/Stock/reports.php

License references:
- SOFTWARE_USE_LICENSE.md
- OPEN_SOURCE_NOTICE.md
- LICENSE

## 17. Support Escalation Template
When raising support ticket, include:
- User ID and role
- Module name and URL
- Steps performed
- Expected vs actual behavior
- Screenshot/error text
- Time of incident
