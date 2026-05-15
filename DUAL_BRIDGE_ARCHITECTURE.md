# Dual-Bridge Architecture Configuration Guide

## Architecture Overview

```
HMS CI4 Queues Events
    ↓ Routes by event_type prefix
    ├─→ snomed.* events
    │    ↓ POST to BRIDGE_SYNC_URL
    │    https://csnotk.e-atria.in/api/bridge
    │    (CSNOtk Bridge: Codes diagnosis → FHIR)
    │
    └─→ abdm.* events
         ↓ POST to ABDM_BRIDGE_URL  
         https://abdm-bridge.e-atria.in/api/v1/bridge
         (ABDM Bridge: Routes to ABDM M1 server)
```

---

## Event Type Routing Table

| Event Type | Destination | Purpose |
|-----------|-------------|---------|
| `snomed.diagnosis.validate` | CSNOtk `/api/bridge` | Validate & code diagnosis with SNOMED CT |
| `snomed.medication.validate` | CSNOtk `/api/bridge` | Validate & code medication with SNOMED CT |
| `abdm.abha.validate` | ABDM Bridge `/api/v1/bridge` | Validate ABHA number with ABDM M1 |
| `abdm.scan_share.lookup` | ABDM Bridge `/api/v1/bridge` | QR code lookup for health records |
| `abdm.fhir.share.requested` | ABDM Bridge `/api/v1/bridge` | Push FHIR bundle to ABDM M1 |
| `abdm.opd.prescription.share.requested` | ABDM Bridge `/api/v1/bridge` | Push OPD prescription bundle |
| `abdm.ipd.discharge.share.requested` | ABDM Bridge `/api/v1/bridge` | Push IPD discharge bundle |
| `abdm.diagnosis.report.share.requested` | ABDM Bridge `/api/v1/bridge` | Push diagnosis report bundle |
| `abdm.consent.requested` | ABDM Bridge `/api/v1/bridge` | Request patient consent for data access |

---

## HMS .env Configuration Required

### Step 1: Dual Bridge URLs

Replace the current BRIDGE configuration with this:

```env
# ==================== CSNOtk Bridge (SNOMED CT Terminology) ====================
# Purpose: Receive diagnosis/medication events, validate with SNOMED CT, return FHIR-coded data
# Endpoint: POST /api/bridge
# Used by: BridgeSyncService when routing snomed.* events
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = 
BRIDGE_SYNC_TIMEOUT = 20

# ==================== ABDM Bridge (ABDM M1 Communication) ====================
# Purpose: Receive ABDM events, route to ABDM Live/Sandbox M1 server
# Endpoint: POST /api/v1/bridge
# Used by: BridgeSyncService when routing abdm.* events
# Architecture: Multiple HMS instances → Single ABDM Bridge → ABDM M1 Server
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
ABDM_BRIDGE_TOKEN = 

# ==================== Bridge Configuration ====================
BRIDGE_SOURCE_CODE = SBXID_033661
BRIDGE_SYNC_PROVIDER = bridge

# ==================== SNOMED CT Terminology Service (Direct REST API) ====================
# Purpose: Direct access to CSNOtk REST API for autocomplete (OPD diagnosis search)
# Endpoints: /rest/search/suggest, /rest/search/lookup, /rest/search/validate/id
# Used by: CsnotkTerminologyService in OPD_Prescription controller
snomed.csnotk.baseUrl = https://csnotk.e-atria.in/csnoserv
snomed.csnotk.enabled = true
snomed.csnotk.timeoutSec = 10
```

### Step 2: Generate Bearer Tokens

Generate TWO random tokens (one for each bridge):

```bash
# Token 1 for CSNOtk Bridge
openssl rand -hex 32
# Example: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2

# Token 2 for ABDM Bridge  
openssl rand -hex 32
# Example: f2e1d0c9b8a7z6y5x4w3v2u1t0s9r8q7p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1
```

### Step 3: Update .env Tokens

```env
BRIDGE_SYNC_TOKEN = a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
ABDM_BRIDGE_TOKEN = f2e1d0c9b8a7z6y5x4w3v2u1t0s9r8q7p6o5n4m3l2k1j0i9h8g7f6e5d4c3b2a1
```

---

## BridgeSyncService Routing Logic (To Be Implemented)

Update `app/Libraries/BridgeSyncService.php` buildBridgeDispatchContext() to route based on event prefix:

### Pseudocode Logic

```php
private function buildBridgeDispatchContext(array $row, array $payload): array
{
    $eventType = (string) ($row['event_type'] ?? '');
    
    // Route snomed.* events to CSNOtk
    if (str_starts_with($eventType, 'snomed.')) {
        return [
            'ok' => true,
            'channel' => 'csnotk',
            'endpoint' => $this->readSetting('BRIDGE_SYNC_URL'),  // CSNOtk bridge
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->readSetting('BRIDGE_SYNC_TOKEN'),
            ],
            'body' => [
                'queue_id' => (int) ($row['id'] ?? 0),
                'source' => $source,
                'event_type' => $eventType,
                'payload' => $payload,
                'occurred_at' => (string) ($row['created_at'] ?? ''),
            ],
        ];
    }
    
    // Route abdm.* events to ABDM Bridge
    if (str_starts_with($eventType, 'abdm.')) {
        return [
            'ok' => true,
            'channel' => 'abdm',
            'endpoint' => $this->readSetting('ABDM_BRIDGE_URL'),  // ABDM bridge
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->readSetting('ABDM_BRIDGE_TOKEN'),
            ],
            'body' => [
                'queue_id' => (int) ($row['id'] ?? 0),
                'source' => $source,
                'event_type' => $eventType,
                'payload' => $payload,
                'occurred_at' => (string) ($row['created_at'] ?? ''),
            ],
        ];
    }
    
    // Unknown event type
    return ['ok' => false, 'error' => 'Unsupported event_type prefix'];
}
```

---

## CSNOtk Bridge `/api/bridge` Contract

### Request Format

```json
{
    "queue_id": 12345,
    "source": "SBXID_033661",
    "event_type": "snomed.diagnosis.validate",
    "payload": {
        "diagnosis_text": "fever",
        "return_limit": 10
    },
    "occurred_at": "2024-05-13T10:30:00+05:30"
}
```

### Response Format (Expected)

```json
{
    "ok": 1,
    "event_type": "snomed.diagnosis.validate",
    "data": [
        {
            "concept_id": "25064002",
            "term": "Headache",
            "hierarchy": "disorder",
            "is_preferred": 1,
            "concept_fsn": "Headache (disorder)"
        }
    ],
    "request_id": "REQ-abc123"
}
```

---

## ABDM Bridge `/api/v1/bridge` Contract

**(See `gateway-php-ci4/README.md` for full API contract)**

### Request Format

```json
{
    "queue_id": 12345,
    "source": "SBXID_033661",
    "event_type": "abdm.abha.validate",
    "payload": {
        "abha_id": "00-0000-0000-0000"
    },
    "occurred_at": "2024-05-13T10:30:00+05:30"
}
```

### Response Format (Expected)

```json
{
    "ok": 1,
    "event_type": "abdm.abha.validate",
    "request_id": "REQ-abc123",
    "data": {
        "abha": "00-0000-0000-0000",
        "status": "VALID"
    }
}
```

---

## Implementation Checklist

### Phase 1: Configuration
- [ ] Update HMS `.env` with both `BRIDGE_SYNC_URL` (CSNOtk) and `ABDM_BRIDGE_URL` (ABDM)
- [ ] Generate two separate bearer tokens
- [ ] Set `BRIDGE_SYNC_TOKEN` and `ABDM_BRIDGE_TOKEN` in .env
- [ ] Document tokens in secure location (password manager)

### Phase 2: Code Changes
- [ ] Update `BridgeSyncService::buildBridgeDispatchContext()` with routing logic
- [ ] Update `BridgeSyncService::buildDispatchContext()` to handle snomed.* routing
- [ ] Update `BridgeSyncService::readSetting()` to support `ABDM_BRIDGE_URL` and `ABDM_BRIDGE_TOKEN`
- [ ] Add logging to distinguish between CSNOtk and ABDM bridge calls in `abdm_api_logs` table

### Phase 3: Testing
- [ ] Queue a `snomed.diagnosis.validate` event and verify it goes to CSNOtk
- [ ] Queue an `abdm.abha.validate` event and verify it goes to ABDM Bridge
- [ ] Check `abdm_api_logs` for successful routing
- [ ] Verify CSNOtk returns SNOMED-coded data
- [ ] Verify ABDM Bridge routes to ABDM M1 correctly

### Phase 4: Deployment
- [ ] Deploy PHP gateway to https://abdm-bridge.e-atria.in (if not already live)
- [ ] Configure gateway `.env` with `GATEWAY_BEARER_TOKEN` matching HMS `ABDM_BRIDGE_TOKEN`
- [ ] Run `php spark bridge:sync --limit 10` to test both bridge types
- [ ] Monitor logs for any routing or authentication errors

---

## Database Logging

The `abdm_api_logs` table will now record:
- `channel` = 'csnotk' or 'abdm' (for routing differentiation)
- `event_type` = snomed.* or abdm.*
- `endpoint` = CSNOtk or ABDM bridge URL used
- `status` = 'success' or 'error'
- `error_message` = 403 Unauthorized, 404 Not Found, etc.

---

## Troubleshooting

### Problem: 403 Unauthorized on CSNOtk
- **Check:** BRIDGE_SYNC_TOKEN matches CSNOtk Bridge expectation
- **Verify:** Request to `https://csnotk.e-atria.in/api/bridge` includes bearer token

### Problem: 403 Unauthorized on ABDM Bridge
- **Check:** ABDM_BRIDGE_TOKEN matches PHP gateway `GATEWAY_BEARER_TOKEN`
- **Verify:** PHP gateway `.env` has the same token as HMS `ABDM_BRIDGE_TOKEN`

### Problem: Events stuck in pending status
- **Check:** Routing logic correctly identifies event prefix (snomed.* vs abdm.*)
- **Query:** `SELECT * FROM bridge_sync_queue WHERE status='pending' ORDER BY created_at DESC`
- **Debug:** Add logging to see which endpoint was called

### Problem: CSNOtk returns 404 on /api/bridge
- **Cause:** `/api/bridge` endpoint may not be exposed on the CSNOtk public server
- **Fallback:** Check if CSNOtk only exposes `/rest/search/*` endpoints (direct API calls only)
- **Action:** If needed, create intermediate queue processor for CSNOtk terminology service

---

## References

- **CSNOtk REST API Reference:** `docs/CSNOTK_API_REFERENCE.md`
- **CSNOtk Database:** `SnomedCT_InternationalRF2_PRODUCTION_20260501T120000Z/`
- **ABDM PHP Gateway:** `gateway-php-ci4/` (deployment guide: `ABDM_GATEWAY_PHP_DEPLOYMENT.md`)
- **Current CSNOtk Direct API Integration:** `app/Libraries/CsnotkTerminologyService.php`
- **Bridge Service:** `app/Libraries/BridgeSyncService.php`
