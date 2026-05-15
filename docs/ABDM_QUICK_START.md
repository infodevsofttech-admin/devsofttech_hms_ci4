# ABDM Sandbox Integration — Quick Start Guide

**Approved:** May 12, 2026  
**Client ID:** `SBXID_033661`  
**Integration Mode:** DreamSoft Middleware (csnotk.e-atria.in)

---

## 🎯 What's Ready

Your HMS application now has:

✅ **ABDM Sandbox Credentials Configured**
- Client ID: `SBXID_033661`
- Client Secret: Configured in `.env`
- Middleware Gateway: `https://csnotk.e-atria.in`

✅ **ABDM Controllers Ready**
- ABHA validation endpoint
- Consent request/callback handlers
- Bundle push for OPD, Lab, Discharge
- FHIR bundle generation

✅ **OPD Enhancements for ABDM**
- ABHA number input field
- SNOMED CT diagnosis/complaint coding
- Vitals with proper units
- Medicine SNOMED/ATC coding
- Recent patient entries chips (for faster data reuse)

✅ **Documentation Created**
- `docs/ABDM_SANDBOX_SETUP.md` — Complete integration guide
- `docs/ABDM_SANDBOX_CHECKLIST.md` — Testing checklist
- This quick start guide

---

## 🚀 Immediate Next Steps

### Step 1: Get Missing Bearer Token (BLOCKING)
Contact your middleware provider (CSNOtk/DreamSoft) to obtain:
```
BRIDGE_SYNC_TOKEN = <your-bearer-token>
```

Once received, update `.env`:
```env
BRIDGE_SYNC_TOKEN = your-token-here
```

### Step 2: Register in ABDM Sandbox
1. Go to: https://sandbox.abdm.gov.in
2. Register your organization with Client ID: `SBXID_033661`
3. Note your **HFR ID** (Health Facility Registry ID)
4. Note your **NPI** (National Practitioner Identifier)

### Step 3: Update .env with Your IDs
```env
ABDM_HFR_ID = your-hfr-id
ABDM_NPI_ID = your-npi-id
```

### Step 4: Get Test ABHA Numbers
From ABDM sandbox portal, get 2-3 test ABHA numbers, e.g.:
```
14-0061-0000-0001
14-0061-0000-0002
14-0061-0000-0003
```

### Step 5: Create Test Patient in HMS
```
Patient Name: Test Patient
ABHA Number: 14-0061-0000-0001
Gender: Male
Age: 35
```

---

## 🧪 Testing Sequence

### Test 1: Verify Middleware Connectivity (2 min)
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://csnotk.e-atria.in/api/bridge/status
# Should return: {"status": "ok"}
```

### Test 2: ABHA Validation (5 min)
1. Open OPD Consult form in HMS
2. Create new patient → Enter ABHA: `14-0061-0000-0001`
3. Click "Validate ABHA"
4. **Expected:** ✅ ABHA linked successfully

### Test 3: SNOMED Terminology (3 min)
1. In Diagnosis field → Type: `fever`
2. Click search icon
3. **Expected:** Dropdown shows SNOMED codes via csnotk
4. Select one → SNOMED ID auto-fills

### Test 4: OPD Consult Creation (10 min)
1. Fill OPD form:
   - Complaints: `Fever and cough`
   - Vital Signs: BP 120/80, Pulse 72, Temp 37.5°C
   - Diagnosis: Select fever (SNOMED)
   - Prescription: Add 1-2 medicines
2. Click "Save & Submit"
3. Check database:
   ```sql
   SELECT opd_id, fhir_bundle_id, abdm_push_status 
   FROM opd_prescription 
   ORDER BY id DESC LIMIT 1;
   ```
4. **Expected:** `fhir_bundle_id` populated, `abdm_push_status = 0`

### Test 5: Consent Request (5 min)
1. Open saved OPD record
2. Scroll to "ABDM Actions" section
3. Click "Request Consent"
4. Check sandbox portal for consent request
5. **Expected:** Consent request appears in ABDM sandbox

### Test 6: Accept Consent in Sandbox (2 min)
1. Login to https://sandbox.abdm.gov.in with test account
2. Navigate to "Consent Requests"
3. Accept the request from your HMS
4. **Expected:** Status changes to "GRANTED"

### Test 7: Push Bundle to ABDM (3 min)
1. After consent granted in HMS, refresh page
2. Click "Share Records" → "Push to ABDM"
3. Check ABDM sandbox for record appearance
4. **Expected:** ✅ Record pushed successfully

---

## 📊 Dashboard Access

Once configured, access ABDM features via:

1. **OPD Consult** → Bottom section "ABDM Actions"
   - Link ABHA
   - Request Consent
   - Share Records

2. **ABDM Task Board** → Menu → "ABDM"
   - View all ABDM operations
   - Monitor consent status
   - Check bundle push history

3. **Patient Profile** → "ABHA" tab
   - View linked ABHA
   - History of shares

---

## 🔑 Key API Endpoints (for your reference)

| Operation | Endpoint | Method |
|-----------|----------|--------|
| ABHA Validate | `/AbdmGateway/abha_validate` | POST |
| Consent Request | `/AbdmGateway/consent_request` | POST |
| Push OPD | `/AbdmGateway/share_prescription_bundle` | POST |
| Push Lab | `/AbdmGateway/share_diagnosis_report_bundle` | POST |
| Push Discharge | `/AbdmGateway/share_ipd_discharge_bundle` | POST |

---

## ⚠️ Common Issues & Fixes

### Issue: "Middleware unreachable"
```
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = <missing-or-invalid>
```
**Fix:** 
1. Verify `.env` has correct URL and token
2. Contact middleware provider for token
3. Check firewall allows HTTPS outbound

### Issue: "ABHA validation stuck in 'pending'"
**Fix:**
1. Verify ABHA number exists in sandbox
2. Check token has permission for ABHA operations
3. Check logs: `writable/logs/log-*.log`

### Issue: "SNOMED code not found in diagnosis"
**Fix:**
1. Ensure CSNOtk service is responding
2. Try common term: `fever`, `cough`, `headache`
3. Check: `snomed.csnotk.baseUrl` in `.env`

### Issue: "Consent never shows 'GRANTED' status"
**Fix:**
1. Login to ABDM sandbox portal
2. Navigate to "Consent Requests" section
3. Manually accept the request (for testing)
4. Refresh HMS page — status updates

---

## 📋 Configuration Summary

**Current State:**
```
Environment:         development
ABDM Connector:      dreamsoft
Middleware URL:      https://csnotk.e-atria.in
SNOMED Service:      https://csnotk.e-atria.in/csnoserv
Direct ABDM URL:     https://dev.abdm.gov.in/api/v3
```

**Sandbox Credentials:**
```
Client ID:           SBXID_033661
Client Secret:       656f79f1-ef99-495f-9f37-713219ecbbcf
Mode:                Approved & Ready
```

**Still Needed:**
- [ ] Bearer token for middleware
- [ ] HFR ID from facility registration
- [ ] NPI ID for practitioner
- [ ] Test ABHA numbers from sandbox

---

## 📞 Support

### Middleware Support
- **Provider:** CSNOtk / DreamSoft
- **URL:** https://csnotk.e-atria.in
- **Contact:** Check middleware provider documentation

### ABDM Sandbox Support
- **Portal:** https://sandbox.abdm.gov.in
- **Docs:** https://sandbox.abdm.gov.in/sandbox/v3/new-documentation
- **API Docs:** NHA ABDM M3 Integration Guide

### Application Logs
Check for errors:
```bash
tail -f writable/logs/log-*.log
```

---

## 🎓 Learning Path

1. **Day 1:** Complete steps 1-4 (get credentials)
2. **Day 2:** Run Tests 1-2 (connectivity & ABHA)
3. **Day 3:** Run Tests 3-4 (SNOMED & OPD creation)
4. **Day 4:** Run Tests 5-7 (full consent → push flow)
5. **Day 5+:** Move to Phase 2 (database schema additions)

---

**Status:** 🟢 Ready for Sandbox Testing  
**Last Updated:** May 12, 2026

---

## Quick Reference: .env Configuration

```env
# ABDM Sandbox Mode
abdm.connector = dreamsoft

# Direct API Fallback
abdm.directClientId = SBXID_033661
abdm.directClientSecret = 656f79f1-ef99-495f-9f37-713219ecbbcf
abdm.directBaseUrl = https://dev.abdm.gov.in/api/v3

# Middleware Gateway (DreamSoft)
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = <TO_BE_OBTAINED>
BRIDGE_SOURCE_CODE = SBXID_033661
BRIDGE_SYNC_TIMEOUT = 20

# SNOMED CT Terminology
snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
snomed.csnotk.enabled = true
snomed.csnotk.timeoutSec = 10

# Facility Details
ABDM_HFR_ID = <TO_BE_OBTAINED>
ABDM_NPI_ID = <TO_BE_OBTAINED>
```

---

**Ready to proceed? → Start with Step 1 above!** 🚀
