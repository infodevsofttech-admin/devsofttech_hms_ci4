# ABDM Sandbox Integration - HMS Setup Guide

**Date:** May 12, 2026  
**Status:** Sandbox Approved  
**Client ID:** `SBXID_033661`  
**Integration Mode:** DreamSoft Middleware Gateway

---

## 1. Overview

This HMS application integrates with **ABDM (Ayushman Bharat Digital Mission)** sandbox environment to support:

- **ABHA (Ayushman Bharat Health Account)** linking and validation
- **Health Information** exchange and consent management
- **FHIR R4** bundle creation for OPD, Lab, and Discharge records
- **Direct prescription/report sharing** with ABDM ecosystem

The integration supports two modes:
- **DreamSoft (Current)**: Routes through middleware gateway (csnotk.e-atria.in)
- **Direct ABDM**: Direct API calls to NHA ABDM M3 (future/testing)

---

## 2. Sandbox Credentials

```
Client ID:       SBXID_033661
Client Secret:   656f79f1-ef99-495f-9f37-713219ecbbcf
Mode:            DreamSoft Middleware Gateway
Middleware URL:  https://csnotk.e-atria.in
```

### Credential Storage
- **Production**: Store in secure vault/secrets manager
- **Sandbox**: Currently in `.env` (do NOT commit to source control)
- **CI/CD**: Use environment variables or secret management system

---

## 3. Configuration Details

### 3.1 .env File Setup

```env
# ABDM Connector Mode
abdm.connector = dreamsoft

# Direct ABDM M3 API (for future direct mode)
abdm.directClientId = SBXID_033661
abdm.directClientSecret = 656f79f1-ef99-495f-9f37-713219ecbbcf
abdm.directBaseUrl = https://dev.abdm.gov.in/api/v3
abdm.directTimeoutSec = 30

# DreamSoft Middleware Gateway
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = <your-bearer-token-here>
BRIDGE_SOURCE_CODE = SBXID_033661
BRIDGE_SYNC_TIMEOUT = 20
BRIDGE_SYNC_PROVIDER = bridge

# SNOMED CT Terminology Service
snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
snomed.csnotk.enabled = true
snomed.csnotk.timeoutSec = 10

# ABDM Sandbox Test Users
ABDM_SANDBOX_USERNAME = <your-sandbox-username>
ABDM_SANDBOX_PASSWORD = <your-sandbox-password>
ABDM_HFR_ID = <health-facility-registry-id>
ABDM_NPI_ID = <national-practitioner-identifier>
```

### 3.2 Config File: `app/Config/AbdmConnector.php`

Located at: `app/Config/AbdmConnector.php`

```php
class AbdmConnector extends BaseConfig
{
    public string $connector = 'dreamsoft';  // or 'direct_abdm'
    public string $dreamsoftBridgeUrl = '';   // Loaded from .env
    public string $dreamsoftBridgeToken = '';
    public string $dreamsoftSourceCode = '';
    public int $dreamsoftTimeoutSec = 20;
    
    // Direct ABDM settings
    public string $directAbdmBaseUrl = 'https://dev.abdm.gov.in/api/v3';
    public string $directAbdmClientId = '';
    public string $directAbdmClientSecret = '';
}
```

---

## 4. Available ABDM API Endpoints

All ABDM endpoints require permission: `abdm.access`, `abdm.taskboard.access`, or `abdm.gateway.use`

### 4.1 ABHA Validation
```
POST /AbdmGateway/abha_validate
```
**Purpose:** Validate and link an ABHA number/address to a patient record.

**Request:**
```json
{
  "patient_id": 12345,
  "abha_number": "14-0061-0000-0001",
  "abha_address": "patient123@abdm"
}
```

**Response:**
```json
{
  "update": 1,
  "message": "ABHA linked successfully",
  "patient_abha_id": "14-0061-0000-0001"
}
```

### 4.2 Consent Request
```
POST /AbdmGateway/consent_request
```
**Purpose:** Initiate consent request with a patient to share their health records.

**Request:**
```json
{
  "patient_id": 12345,
  "consent_purpose": "treatment",
  "hi_types": ["OPConsultRecord", "PrescriptionRecord"],
  "date_range_from": "2024-01-01",
  "date_range_to": "2026-05-12"
}
```

**Response:**
```json
{
  "update": 1,
  "consent_request_id": "REQ-2026-001",
  "consent_url": "https://sandbox.abdm.gov.in/consent/REQ-2026-001"
}
```

### 4.3 Share Prescription Bundle (OPD Consult)
```
POST /AbdmGateway/share_prescription_bundle
```
**Purpose:** Push OPD prescription/consult record to ABDM in FHIR format.

**Request:**
```json
{
  "opd_id": 5678,
  "patient_id": 12345,
  "consent_id": "CST-2026-001"
}
```

**Response:**
```json
{
  "update": 1,
  "bundle_id": "2c49bc31-b79f-43c7-838f-e5c8c61f50df",
  "status": "pushed",
  "timestamp": "2026-05-12T10:30:00Z"
}
```

### 4.4 Share Lab Report Bundle (Diagnostic)
```
POST /AbdmGateway/share_diagnosis_report_bundle
```
**Purpose:** Push lab/diagnostic reports to ABDM.

**Request:**
```json
{
  "lab_report_id": 9012,
  "patient_id": 12345,
  "consent_id": "CST-2026-001"
}
```

### 4.5 Share Discharge Summary (IPD)
```
POST /AbdmGateway/share_ipd_discharge_bundle
```
**Purpose:** Push IPD discharge summary to ABDM.

---

## 5. Sandbox Testing Workflow

### Step 1: Register Patient with ABHA
1. Go to **OPD → Consult Form**
2. Enter patient details
3. In "ABHA Link" section, enter test ABHA number (e.g., `14-0061-0000-0001`)
4. Click "Validate ABHA"
5. System connects to ABDM sandbox via middleware to link ABHA

### Step 2: Create OPD Consult
1. Fill in OPD consult form with:
   - Complaints
   - Diagnosis (with SNOMED CT codes)
   - Vital signs (BP, pulse, temp, SpO2)
   - Prescriptions
   - Investigations
2. Click "Save & Submit"
3. System auto-generates FHIR bundle locally

### Step 3: Initiate Consent Request
1. Patient record loaded → "ABDM Actions" panel
2. Click "Request Consent"
3. Select HI types (OPConsultRecord, PrescriptionRecord)
4. Choose date range
5. System sends consent request to ABDM sandbox
6. Patient receives consent link (sandbox email/SMS)

### Step 4: Patient Grants Consent (Sandbox Only)
1. Patient logs into ABDM sandbox (https://sandbox.abdm.gov.in)
2. Accepts the consent request from your HMS
3. Consent status changes to "GRANTED" in system

### Step 5: Push Records to ABDM
1. After consent granted, click "Share Records"
2. Select records to push (OPD, Lab, Discharge)
3. System creates FHIR bundle and pushes via middleware
4. Receive bundle ID and push timestamp confirmation

---

## 6. Database Schema Requirements for ABDM

The following fields must exist in respective tables:

### opd_prescription table
```sql
ALTER TABLE opd_prescription ADD COLUMN (
  abha_id VARCHAR(50),
  abha_address VARCHAR(100),
  encounter_id VARCHAR(100),
  care_provider_npi VARCHAR(50),
  hfr_id VARCHAR(50),
  fhir_bundle_id VARCHAR(100),
  abdm_push_status INT DEFAULT 0,  -- 0=pending, 1=pushed, 2=failed
  abdm_push_timestamp DATETIME,
  diagnosis_snomed_id VARCHAR(50),
  diagnosis_snomed_term VARCHAR(255),
  complaint_onset VARCHAR(100),
  complaint_duration_days INT,
  complaint_severity VARCHAR(50)
);
```

### patient_master table
```sql
ALTER TABLE patient_master ADD COLUMN (
  abha_number VARCHAR(50) UNIQUE,
  abha_address VARCHAR(100) UNIQUE,
  abha_linked_at DATETIME,
  abha_link_status VARCHAR(20)  -- pending, confirmed, failed
);
```

### New: abdm_consent table
```sql
CREATE TABLE abdm_consent (
  id INT PRIMARY KEY AUTO_INCREMENT,
  patient_id INT,
  consent_request_id VARCHAR(100) UNIQUE,
  consent_id VARCHAR(100),
  purpose VARCHAR(100),
  hi_types JSON,
  date_from DATE,
  date_to DATE,
  status VARCHAR(20),  -- pending, granted, denied, expired
  requested_at DATETIME,
  granted_at DATETIME,
  expires_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patient_master(id)
);
```

---

## 7. FHIR Bundle Structure

The system generates FHIR R4 bundles for:

### OPConsultRecord Bundle
- **Composition**: Document wrapper
- **Bundle**: Contains multiple resources
- **Encounter**: OPD visit details
- **Patient**: Demographics
- **Practitioner**: Doctor info
- **Observation**: Vitals, physical exam
- **Condition**: Complaints, diagnosis
- **AllergyIntolerance**: Drug allergies
- **ServiceRequest**: Investigations
- **MedicationRequest**: Prescriptions

### PrescriptionRecord Bundle
- **Composition**: Prescription document
- **MedicationRequest**: Each medicine prescribed
- **Dosage**: Strength, frequency, route
- **Performer**: Dispensing info (if available)

---

## 8. Middleware Gateway Integration

### CSNOtk.e-atria.in Gateway

**Purpose:** Acts as bridge between HMS and ABDM sandbox.

**Key Features:**
- ABHA validation proxy
- Consent request relay
- FHIR bundle routing
- SNOMED CT terminology service

**Authentication:**
- Bearer token required in `BRIDGE_SYNC_TOKEN`
- Source code (HFR/Client ID) in headers
- TLS 1.2+ required

**Endpoints Exposed:**
```
POST /api/bridge/abha/validate
POST /api/bridge/consent/request
POST /api/bridge/consent/status
POST /api/bridge/bundle/push
GET  /api/bridge/bundle/{bundleId}/status
GET  /csnoserv/search/suggest?term=<query>
```

---

## 9. Testing Credentials (Sandbox)

Contact ABDM Sandbox Support:
- **Website:** https://sandbox.abdm.gov.in
- **Portal:** Login with your organization credentials
- **Test Patients:** Available in sandbox portal
- **Test ABHA Numbers:** Examples provided in portal

### Sample Test ABHA Numbers
```
14-0061-0000-0001  (Patient A)
14-0061-0000-0002  (Patient B)
14-0061-0000-0003  (Patient C)
```

---

## 10. Permissions & Access Control

User roles required for ABDM operations:

```php
'abdm.access'              => 'Can view ABDM module',
'abdm.taskboard.access'    => 'Can access ABDM task board',
'abdm.gateway.use'         => 'Can run ABDM gateway actions',
```

**Recommended Role:** Admin, Doctor, ABDM Coordinator

---

## 11. Troubleshooting

### Issue: ABHA Validation Fails
**Cause:** Middleware unreachable or credentials invalid
**Fix:**
1. Check `BRIDGE_SYNC_URL` is correct
2. Verify `BRIDGE_SYNC_TOKEN` with middleware provider
3. Check firewall/proxy allows outbound HTTPS

### Issue: Consent Request Stuck in "Pending"
**Cause:** Patient hasn't accepted in sandbox portal
**Fix:**
1. Log into ABDM sandbox portal
2. Navigate to "Consent Requests"
3. Manually accept request for testing
4. System polls for status updates

### Issue: FHIR Bundle Rejected
**Cause:** Missing required SNOMED codes or invalid structure
**Fix:**
1. Ensure diagnosis has SNOMED ID (not just text)
2. Ensure vitals in proper FHIR units (mmHg, BPM, etc.)
3. Check bundle in `/logs` for validation errors

---

## 12. Next Steps

1. **Set Bearer Token:** Contact ABDM/middleware provider to get `BRIDGE_SYNC_TOKEN`
2. **Configure HFR/NPI IDs:** Update `.env` with your facility identifiers
3. **Create Test User:** Register test user in ABDM sandbox portal
4. **Test ABHA Linking:** Use test ABHA from sandbox portal
5. **Run Consent Flow:** Test full consent → push workflow
6. **Verify FHIR Bundles:** Check generated bundles in database logs

---

## 13. Reference Documentation

- **ABDM Official Docs:** https://sandbox.abdm.gov.in/sandbox/v3/new-documentation
- **FHIR R4 Spec:** http://hl7.org/fhir/R4
- **NHA Integration Guide:** https://nha.gov.in/abdm/guidelines
- **Local Implementation:** [app/Controllers/AbdmGateway.php](../../app/Controllers/AbdmGateway.php)

---

**Last Updated:** May 12, 2026  
**Maintained By:** HMS Development Team
