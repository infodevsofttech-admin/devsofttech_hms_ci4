# E-Atria Hospital Management System (HMS)
## Complete Feature Overview — Presentation Reference

---

## 1. Patient Management
> Unified patient registry — single source of truth for every patient interaction across all departments.

- **Patient Registration** — UHID auto-generation, demographics (name, DOB, gender, address, contact)
- **Multi-mode Search** — by name, UHID, phone, Aadhaar, old UHID (migrated legacy records)
- **Identity Documents** — Aadhaar, insurance card, ABHA card storage and display
- **Patient Photo Capture** — webcam or upload; displayed across all modules
- **Document Attachments** — scan and attach files to patient record
- **Family Tracking** — link relatives/guardians
- **ABHA Integration** — create, verify, and link National Health ID (ABHA)

---

## 2. OPD — Outpatient Department
> End-to-end outpatient workflow from appointment to e-prescription.

### Appointments
- Doctor-wise appointment scheduling and calendar
- Walk-in and pre-booked slots
- ABDM Scan & Share QR-based token check-in
- OPD queue management with token-based access display

### Clinical Documentation
- **Vital signs** — BP, pulse, temperature, SpO₂, weight, height, BMI
- **Chief complaints** — structured master + free text
- **Diagnosis** — ICD-linked disease master with SNOMED-CT coding
- **Prescription** — medicine, dosage, frequency, duration; group prescriptions for common regimens
- **Lab investigations** — inline requisition from consultation
- **Clinical advice** — reusable templates
- **Drug allergy tracking** — ADR history with structured capture
- **Previous visit history** — timeline across consultations

### AI Clinical Assistance
- AI-drafted chief complaints
- AI-drafted full prescription
- Auto-fill clinical sections from patient history
- AI extraction of lab values from scanned reports

### Templates & Configuration
- Custom print template builder (letterhead, plain paper)
- Section templates for reuse across visits
- Investigation shortcut groups
- Prescription group management

### Printing & Sharing
- OPD invoice generation and printing
- Prescription PDF (letterhead & plain paper formats independently)
- Referral letter generation (PDF)
- FHIR e-Prescription bundle generation and ABDM sharing

---

## 3. IPD — Inpatient / Admission Management
> Full hospital admission lifecycle including nursing, charges, and discharge.

### Admission
- Patient admission registration linked to bed
- Admission history / HPI (History of Present Illness) documentation
- Multiple attending doctor assignment

### Nursing & Clinical Care
- Nursing notes / charting by shift
- Bedside item charges (consumables, nursing items)
- Doctor visit charge recording
- Paper-based nursing form scanning and digitisation

### Bed Management
- Multi-level hierarchy: Department → Ward → Category → Individual Bed
- Real-time bed occupancy dashboard
- Bed transfer tracking with history
- Maintenance log for beds

### IPD Billing
- Charges: bed, nursing, doctor visits, investigations, procedures, consumables
- Package-based billing (fixed-rate procedure bundles)
- Ayushman Bharat / insurance package mapping and claim sheet generation
- Multiple payment modes: cash, bank, TPA
- Payment deduction / adjustment
- Cash balance report and export

### Discharge
- Discharge summary generation with configurable HTML editor
- Section templates for clinical course, diagnosis, procedures, dietary advice
- ICD diagnosis coding on discharge
- Surgery / procedure documentation
- Discharge PDF with letterhead / plain paper template selection
- FHIR discharge summary bundle for ABDM sharing

---

## 4. Diagnosis — Laboratory & Imaging
> Covers all diagnostic modalities from pathology to advanced imaging.

| Modality | Type |
|---|---|
| Pathology | Lab tests, panels, reference ranges |
| Biopsy | Histopathology reporting |
| X-Ray | Imaging report + file upload |
| Ultrasound | Report + image gallery |
| MRI | Report + DICOM viewer |
| CT Scan | Report + DICOM viewer |
| Echo | Echocardiography report |

### Core Lab Features
- Lab request from OPD, IPD, or walk-in
- Sample collection tracking
- Test parameter entry with normal range validation
- Combined / compiled multi-test PDF reports
- Lab number and timing tracking
- Report verification workflow (technician → doctor sign-off)
- NABH-compliant audit trail for edits (reason required)

### Imaging Features
- Image and scan file upload per report
- DICOM image viewer
- AI-assisted DICOM diagnosis suggestions
- Image gallery per patient

### Reporting
- Professional print-quality PDF reports (letterhead and plain paper)
- Compiled multi-modality reports
- AI extraction of values from scanned/photographed lab slips

---

## 5. Pharmacy / Medical Store
> Comprehensive pharmacy operations from purchasing to dispensing.

### Dispensing
- OPD, IPD, and counter-sale pharmacy billing
- Patient-linked, IPD-linked, and organisation-linked invoices
- Medicine search with batch, expiry, and stock visibility
- Medicine return / credit note

### Inventory Management
- Drug master: generic name, brand, formulation, strength, category
- Batch-level tracking with expiry dates
- Expiry alert reports (near-expiry and expired)
- Stock transfer between locations
- Stock reconciliation

### Purchasing
- Purchase order creation and tracking
- Supplier invoice management
- Goods receipt and stock update
- Purchase return to supplier
- Challan / delivery note generation

### Masters & Reference Data
- Pharmaceutical company master
- Formulation / dosage form master
- Medicine category master
- Supplier master with ledger accounts

### Reports
- Current stock report (by item, category, location)
- Date-wise stock movement
- Expiry report (PDF export)
- Supplier payment ledger
- Pharmacy billing audit log

---

## 6. Billing & Finance
> Integrated revenue cycle management.

### OPD / Procedure Billing
- Charge-type invoices (consultation, procedures, diagnostics)
- Discount application (percentage or fixed)
- Invoice cancellation / void
- Payment request and refund workflow
- Organisation / insurance case billing
- Contingent billing (pending case outcome)

### IPD Billing
- See IPD section above

### Finance Module
- Cash book — daily collections by department
- Bank deposit tracking and status management
- Bank reconciliation (statement matching to system entries)
- POS settlement management
- Cash scroll / bundle submission workflow
- Doctor payout / commission agreements and disbursement
- Vendor management (purchase orders, goods receipt, vendor invoices)
- Compliance and audit reports

---

## 7. Insurance & Organisation Cases
> Managed care and corporate billing.

- Organisation (corporate / insurance) case creation
- Case-linked OPD, IPD, and diagnostic charges
- Contingent billing
- Payment request to organisation
- Refund management
- Ayushman Bharat package search and mapping
- Insurance claim sheet generation
- NHCX digital insurance claim submission (via ABDM)

---

## 8. ABDM — Ayushman Bharat Digital Mission Integration
> Fully integrated with India's national digital health ecosystem.

### ABHA (Health ID)
- ABHA creation via Aadhaar OTP
- ABHA creation via mobile OTP
- ABHA verification and profile fetch
- ABHA card display and download
- Facility QR code generation

### Care Context & Linking
- Patient discovery through ABDM gateway
- Care context creation and linking per visit/admission
- Scan & Share QR-based patient linking at OPD
- HIP-initiated linking tokens

### Health Record Sharing
| Record Type | Source |
|---|---|
| e-Prescription (OP) | OPD prescription |
| Discharge Summary | IPD discharge |
| Diagnostic Report | Pathology / Radiology |
| Immunisation Record | Patient immunisations |
| Wellness Record | Preventive / wellness |
| Health Document | Uploaded files |
| Invoice / Billing | OPD/IPD invoices |

### Consent Management
- Consent request initiation (patient-directed)
- Webhook-based consent notification
- Consent status tracking and reconciliation
- HIU (Health Information User) data fetch

### FHIR Compliance
- FHIR R4 bundle generation for every record type
- SNOMED-CT clinical coding panel
- ICD-10 diagnosis coding
- FHIR bundle preview before sharing
- Coding review and verification workflow

### Monitoring & Operations
- ABDM gateway connectivity health check
- Bridge log viewer with full request/response trace
- Task board for pending ABDM actions
- Background sync queue with dead-letter replay
- Real-time sharing status per record

---

## 9. Reports
> Ready-to-use clinical and administrative reports.

| Report | Type |
|---|---|
| OPD Total Report | Daily/period OPD summary |
| Diagnosis Report | Lab workload and TAT |
| Insurance Credit Report | Organisation dues |
| NABH Audit Report | Compliance audit trail |
| IPD Cash Balance | IPD collection summary |
| Stock Report | Pharmacy inventory |
| Expiry Report | Near-expiry medicines |
| Supplier Ledger | Supplier account statement |
| Bank Audit | Reconciliation report |
| Doctor Payout | Commission and disbursement |
| Compliance Report | Finance compliance |

---

## 10. Admin & Settings
> Centralised configuration for the entire hospital.

### Master Data
- Doctor master (specialisation, consultation fee, schedule)
- User management (role-based access control, permissions)
- Hospital profile (name, logo, address, registration details)
- Bank & payment source master
- Insurance master
- Referral doctor master

### Template Configuration
- Diagnosis print settings (letterhead and plain paper templates per modality)
- Discharge summary templates
- OPD prescription templates
- Watermark and logo configuration

### System Integration Settings
- AI settings (model, API key, usage monitoring)
- HealthPlix integration
- ABDM gateway configuration (HFR ID, bridge token)
- ABDM report doctor mapping

### System Operations Panel *(New)*
- Server overview (hostname, OS, uptime)
- Resource usage with live gauges: CPU, RAM, Disk
- Services status: nginx, apache, PHP-FPM, MariaDB, MySQL, sshd
- RAID status
- **Update HMS** — one-click `git pull` with 60-second timeout protection
- Web server restart / PHP-FPM restart
- Server reboot / shutdown actions (confirmation required)
- Update history log with full error detail viewer
- AJAX auto-refresh every 30 seconds (no page reload)

---

## 11. Technology Stack

| Layer | Technology |
|---|---|
| Backend Framework | CodeIgniter 4 (PHP 8.3) |
| Database | MySQL / MariaDB |
| Frontend UI | NiceAdmin (Bootstrap 5) |
| PDF Generation | mPDF (multilingual, Hindi font support) |
| AI Engine | Configurable API (OpenAI-compatible) |
| FHIR Standard | HL7 FHIR R4 |
| DICOM Viewer | Integrated DICOM viewer |
| Health Identity | ABHA / ABDM National Gateway |
| Coding Standards | SNOMED-CT, ICD-10 |
| Insurance Claim | NHCX (National Health Claim Exchange) |

---

## 12. Compliance & Standards

- **NABH** — National Accreditation Board for Hospitals audit trail support
- **ABDM** — Ayushman Bharat Digital Mission certified integration
- **FHIR R4** — HL7-compliant health record exchange
- **SNOMED-CT** — Clinical terminology coding
- **ICD-10** — Diagnosis and procedure coding
- **NHCX** — Digital insurance claim submission
- **Aadhaar** — Identity verification for ABHA creation

---

*E-Atria HMS — Built for modern Indian hospitals, compliant with national digital health standards.*
