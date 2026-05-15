# ABDM Sandbox Configuration Checklist

**Project:** HMS CI4 - ABDM Sandbox Integration  
**Date Created:** May 12, 2026  
**Status:** Approved Sandbox Credentials Received

## Configuration Status

### ✅ Completed
- [x] Client ID obtained: `SBXID_033661`
- [x] Client Secret received: `656f79f1-ef99-495f-9f37-713219ecbbcf`
- [x] .env configured with credentials
- [x] ABDM config file reviewed: `app/Config/AbdmConnector.php`
- [x] ABDM controllers present: `AbdmGateway.php`, `AbdmTaskBoard.php`
- [x] FHIR builder available: `app/Libraries/FhirR4Builder.php`
- [x] Sandbox documentation created: `docs/ABDM_SANDBOX_SETUP.md`
- [x] Recent OPD entry chips implemented (prerequisite feature)

### ⏳ Todo - Immediate (This Session)
- [ ] Get Bearer token from middleware provider (csnotk.e-atria.in)
- [ ] Update `.env` with `BRIDGE_SYNC_TOKEN`
- [ ] Register test user in ABDM sandbox portal
- [ ] Get test ABHA numbers from sandbox
- [ ] Get HFR ID and NPI from facility registration
- [ ] Verify middleware connectivity test
- [ ] Create test patient record in HMS

### ⏳ Todo - Phase 1 (Testing)
- [ ] Test ABHA validation endpoint
- [ ] Verify SNOMED CT terminology service (CSNOtk)
- [ ] Test OPD consult FHIR bundle generation
- [ ] Test consent request flow
- [ ] Test bundle push to sandbox
- [ ] Validate pushed bundles appear in ABDM portal

### ⏳ Todo - Phase 2 (Database Schema)
- [ ] Run migrations to add ABDM fields to opd_prescription
- [ ] Add ABHA fields to patient_master
- [ ] Create abdm_consent tracking table
- [ ] Create abdm_bundle_audit table
- [ ] Add abdm_consent_audit for trail

### ⏳ Todo - Phase 3 (UI/UX)
- [ ] Add ABHA link UI in patient registration
- [ ] Add ABDM actions panel to OPD consult
- [ ] Add consent request UI
- [ ] Add records sharing UI
- [ ] Add ABDM task board dashboard

### ⏳ Todo - Phase 4 (Additional HI Types)
- [ ] Implement Lab Report (DiagnosticReportRecord) bundle
- [ ] Implement Discharge Summary (DischargeSummaryRecord) bundle
- [ ] Implement Wellness Record (vitals, habits)
- [ ] Implement Immunization Record (if needed)

### ⏳ Todo - Phase 5 (Production Hardening)
- [ ] Set up error logging for ABDM API failures
- [ ] Create ABDM retry/reconciliation service
- [ ] Set up notifications for consent approvals
- [ ] Audit trail for ABDM operations
- [ ] Security: Encrypt stored client secret in DB
- [ ] Rate limiting for ABDM API calls

---

## Current Configuration

### Environment Setup
```env
abdm.connector = dreamsoft
abdm.directClientId = SBXID_033661
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SOURCE_CODE = SBXID_033661
snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
```

### Related Features Already Implemented
- OPD Consult FHIR bundle generation
- SNOMED CT complaint/diagnosis coding
- ABHA input field in OPD form
- Vitals normalization (numeric validation)
- Medicine master SNOMED/ATC coding
- Recent patient entries chips (for fast data reuse)

### Middleware Gateway
- **Provider:** CSNOtk / DreamSoft
- **URL:** https://csnotk.e-atria.in
- **Services:** ABHA validation, Consent relay, Bundle routing, SNOMED search
- **Status:** Requires bearer token configuration

### ABDM API Integration Points
1. **OPD Consult** → OPConsultRecord + PrescriptionRecord
2. **Lab Reports** → DiagnosticReportRecord (future)
3. **Discharge Summary** → DischargeSummaryRecord (future)
4. **Billing/Invoice** → InvoiceRecord (future)

---

## Contact & Credentials Safeguard

### Important Security Notes
- **NEVER commit credentials to git** (already in .env, add to .gitignore)
- **Rotate secrets periodically** in production
- **Use environment variables** for CI/CD deployment
- **Log ABDM API errors separately** (don't log secrets)
- **Audit ABDM operations** for compliance

### Credentials Storage (Current)
```
File: .env (local development)
Status: ❌ NOT for production
Next: Move to secure vault/environment variables
```

### Provider Contacts
- **Middleware Support:** https://csnotk.e-atria.in
- **ABDM Sandbox:** https://sandbox.abdm.gov.in
- **NHA Support:** https://nha.gov.in/contact

---

## Testing Quick Start

### 1. Verify Middleware Connectivity
```php
// In controller:
$response = $this->callAbdmGateway('test_connection');
if($response['status'] === 'ok') {
  echo "Middleware reachable ✓";
}
```

### 2. Test ABHA Validation
- Go to: http://localhost/Opd_prescription/Prescription/1
- In ABHA section: Enter test ABHA `14-0061-0000-0001`
- Click "Validate ABHA"
- Expected: ✓ ABHA linked

### 3. Test SNOMED Search
- In Diagnosis field: Type "fever"
- Expected: Dropdown shows SNOMED terms (via csnotk)
- Select one → SNOMED ID auto-fills

### 4. Test OPD Save
- Fill OPD consult with all fields
- Click "Save & Submit"
- Check logs for FHIR bundle generation
- Verify: `opd_prescription.fhir_bundle_id` populated

### 5. Test Consent Flow
- Open saved OPD
- Click "ABDM Actions" → "Request Consent"
- Verify: Consent request created in `abdm_consent` table
- Manually accept in ABDM sandbox portal

### 6. Test Bundle Push
- After consent granted
- Click "Share Records"
- Verify: Bundle pushed, `abdm_push_status = 1`
- Check ABDM sandbox portal for record appearance

---

## Known Limitations (Sandbox)

1. **Test ABHA Numbers Only** → Use only sandbox test ABHAs
2. **No Real Patient Data** → Sandbox is isolated
3. **Consent Always Pending** → Must manually accept in portal
4. **No PDF Generation** → Bundles are JSON only
5. **Rate Limits Exist** → ~100 calls/day per sandbox

---

## Success Metrics

- [ ] ABHA validation ≤ 2 seconds
- [ ] Consent request created ≤ 5 seconds
- [ ] FHIR bundle generation ≤ 3 seconds
- [ ] Bundle push successful 95% of time
- [ ] No uncaught ABDM API exceptions
- [ ] All SNOMED codes resolve within 2 attempts
- [ ] Zero compromise of credentials in logs

---

**Last Updated:** May 12, 2026  
**Next Review:** After Phase 1 testing completion
