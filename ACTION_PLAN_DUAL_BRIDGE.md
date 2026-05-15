# ABDM Integration Summary & Action Plan (May 13, 2026)

## Clarification Received

The user corrected a critical misunderstanding:

### ✅ What Was Clarified

There are **TWO separate, equally important bridges** in the HMS ABDM architecture:

**1. CSNOtk Bridge** (`https://csnotk.e-atria.in/api/bridge`)
- **Primary Purpose:** Terminology validation & FHIR formatting
- **Function:** Receives medical terms (fever, cough, etc.) → validates against SNOMED CT → returns FHIR-coded data
- **Use Case:** When HMS needs to ensure medical terminology is standardized per SNOMED CT
- **Event Types:** `snomed.diagnosis.validate`, `snomed.medication.validate`
- **Data Source:** SnomedCT_InternationalRF2_PRODUCTION_20260501T120000Z (SNOMED CT International Release Files)
- **Documentation:** `CSNOtk_API_v9.0/` folder + `docs/CSNOTK_API_REFERENCE.md`

**2. ABDM Bridge** (`https://abdm-bridge.e-atria.in/api/v1/bridge`)
- **Primary Purpose:** ABDM M1 communication (patient health record sharing)
- **Function:** Centralized gateway for multiple HMS installations to submit/retrieve data from ABDM Live/Sandbox
- **Use Case:** When HMS needs to share patient records with ABDM (e.g., OPD prescriptions, lab reports, consent management)
- **Event Types:** `abdm.abha.validate`, `abdm.fhir.share.requested`, `abdm.consent.requested`, etc.
- **Architecture:** Multiple HMS instances (deployed at client sites) → Single ABDM Bridge → ABDM M1 servers
- **Benefit:** Centralized token management, compliance, audit trail

---

## What Was WRONG (and Corrected)

### ❌ Previous Understanding
- Assumed we should replace CSNOtk with ABDM Bridge
- Thought both bridges did the same thing (ABDM communication)

### ✅ Corrected Understanding
- **Both bridges must coexist**
- They serve fundamentally different purposes
- Event routing logic needed to distinguish them

---

## Current State

### Documentation Created
- ✅ `DUAL_BRIDGE_ARCHITECTURE.md` - Full architecture explanation
- ✅ `ABDM_GATEWAY_PHP_DEPLOYMENT.md` - PHP gateway deployment steps
- ✅ `BRIDGE_SYNC_SERVICE_IMPLEMENTATION.md` - Code implementation guide
- ✅ `.copilot-instructions` - System prompt (auto-loaded for workspace)

### Configuration Status
```env
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge  # ✅ CORRECT (keep as is)
BRIDGE_SYNC_TOKEN =                                      # ⏳ Needs value
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge  # ⏳ Needs to be added
ABDM_BRIDGE_TOKEN =                                      # ⏳ Needs value
BRIDGE_SOURCE_CODE = SBXID_033661                        # ✅ CORRECT
```

### Code Status
- ✅ CsnotkTerminologyService exists (direct REST API integration for autocomplete)
- ✅ BridgeSyncService exists (core queue processing)
- ❌ BridgeSyncService routing logic NOT YET updated for dual-bridge support
  - **Need:** Conditional routing by event_type prefix (snomed.* vs abdm.*)

---

## Immediate Action Items (In Order)

### Phase 1: Configuration (No Code Changes)

**Task 1.1:** Generate TWO bearer tokens
```bash
# Token for CSNOtk Bridge
openssl rand -hex 32
# Result: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2

# Token for ABDM Bridge
openssl rand -hex 32
# Result: f2e1d0c9b8a7z6y5x4w3v2u1t0s9r8q7p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1
```

**Task 1.2:** Update HMS `.env`
```env
# Keep existing (no change)
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2

# Add new config keys
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
ABDM_BRIDGE_TOKEN = f2e1d0c9b8a7z6y5x4w3v2u1t0s9r8q7p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1
```

---

### Phase 2: Code Implementation

**Task 2.1:** Update `app/Libraries/BridgeSyncService.php`
- Modify `buildDispatchContext()` to route by event_type prefix
- Rename `buildBridgeDispatchContext()` → `buildCsnotkDispatchContext()`
- Add new `buildAbdmDispatchContext()` method
- Reference: `BRIDGE_SYNC_SERVICE_IMPLEMENTATION.md`

**Task 2.2:** Update `app/Config/AbdmConnector.php`
- Add two new config keys for ABDM bridge:
  ```php
  public string $abdmBridgeUrl = 'https://abdm-bridge.e-atria.in/api/v1/bridge';
  public string $abdmBridgeToken = '';
  ```

**Task 2.3:** Testing & Validation
- Queue test `snomed.diagnosis.validate` event
- Queue test `abdm.abha.validate` event
- Verify they route to correct endpoints
- Check `abdm_api_logs.channel` is 'csnotk' or 'abdm' respectively

---

### Phase 3: Deployment

**Task 3.1:** Deploy PHP ABDM Gateway
- Copy `gateway-php-ci4/` to server `/opt/abdm-gateway`
- Run composer install
- Configure `.env` with:
  - MySQL credentials
  - `GATEWAY_BEARER_TOKEN` = HMS `ABDM_BRIDGE_TOKEN`
  - ABDM M1 credentials
- Run migrations
- Configure Apache/SSL
- Reference: `ABDM_GATEWAY_PHP_DEPLOYMENT.md`

**Task 3.2:** Test End-to-End
```bash
cd /opt/hms-ci4
php spark bridge:sync --limit 10

# Should show both CSNOTK and ABDM events processed
```

---

## Deliverables Created (For User Reference)

### Documentation Files
1. **`DUAL_BRIDGE_ARCHITECTURE.md`**
   - Complete architecture overview
   - Event routing table
   - Configuration requirements
   - CSNOtk vs ABDM contract details
   - Database schema implications

2. **`BRIDGE_SYNC_SERVICE_IMPLEMENTATION.md`**
   - Exact code changes needed (with line numbers)
   - Step-by-step implementation guide
   - Testing procedures
   - Code review checklist

3. **`ABDM_GATEWAY_PHP_DEPLOYMENT.md`**
   - 10-step server deployment
   - Configuration template
   - Health check & smoke tests
   - Troubleshooting guide
   - Rollback procedures

4. **`.copilot-instructions`**
   - System prompt loaded automatically for this workspace
   - Captures critical architecture requirements
   - Prevents future confusion

### Memory Files
- **`/memories/session/php-gateway-architecture.md`** - Session working notes (auto-populated)

---

## FAQ (From User Clarifications)

**Q: Why are there TWO bridges?**
A: CSNOtk handles terminology (SNOMED CT coding), ABDM handles patient data sharing. They're separate concerns.

**Q: What's the advantage of centralized ABDM Bridge?**
A: Multiple HMS instances at different client sites all connect through one ABDM Bridge for:
- Centralized token management
- Audit trail consolidation
- Simplified client deployment

**Q: Can CSNOtk events bypass the bridge?**
A: Yes, CsnotkTerminologyService calls CSNOtk REST API directly (`/rest/search/suggest`) for autocomplete. The bridge is used for formal event processing.

**Q: Focus on M1 first?**
A: Yes, ABDM M1 is the priority. M2 (privacy) and M3 (interoperability) can be added later.

---

## Next Steps

1. **Generate bearer tokens** (Task 1.1)
2. **Update HMS .env** (Task 1.2)
3. **Implement BridgeSyncService routing** (Task 2.1-2.3)
4. **Test both event types** (Task 2.3)
5. **Deploy PHP gateway** (Task 3.1)
6. **Run end-to-end test** (Task 3.2)

---

## Questions for User (Before Implementation)

1. Should we prioritize CSNOtk bridge testing first, or ABDM bridge?
2. Are there existing test events in bridge_sync_queue to use for routing validation?
3. Should legacy event types (no prefix) still route to CSNOtk as fallback?
4. Is the PHP gateway already accessible at https://abdm-bridge.e-atria.in, or does it need to be deployed?

---

## Architecture Diagram (ASCII)

```
                       HMS CI4 (Main Application)
                               |
                    BridgeSyncService.processPending()
                      (Routes by event_type)
                               |
                    ___________|___________
                   |                       |
            snomed.* events         abdm.* events
                   |                       |
        [CSNOtk Bridge]          [ABDM Bridge]
             v                         v
    /api/bridge                   /api/v1/bridge
(CSNOtk Terminology)      (PHP Gateway at e-atria.in)
             v                         v
   SNOMED CT Database         (/api/v3/* handlers)
   (Validate & Code)                  v
             v                   ABDM M1 Server
  Return SNOMED-coded        (Live/Sandbox)
    FHIR Format                       
             |                         |
             └─────────────────────────┘
                        |
              [Patient Health Data]
```

---

**Status:** Architecture clarified, documentation created, awaiting implementation.
