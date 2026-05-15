# BridgeSyncService Routing Implementation

## Current Issue

The `BridgeSyncService::buildBridgeDispatchContext()` method treats all events the same, sending them to a single `BRIDGE_SYNC_URL`. 

**Current behavior (WRONG):**
```
All events → BRIDGE_SYNC_URL (only one URL)
```

**Required behavior (CORRECT):**
```
snomed.* events → BRIDGE_SYNC_URL (CSNOtk)
abdm.* events → ABDM_BRIDGE_URL (ABDM Bridge)
```

---

## Implementation Steps

### Step 1: Update `readSetting()` to Support New Keys

**File:** `app/Libraries/BridgeSyncService.php`

**Current:** `readSetting()` already handles priority order (env → constant → config → DB), so no changes needed. Just ensure both config keys are recognized.

**Verify in `app/Config/AbdmConnector.php`:**
```php
// Add if not present:
public string $dreamsoftBridgeUrl = 'https://csnotk.e-atria.in/api/bridge';
public string $abdmBridgeUrl = 'https://abdm-bridge.e-atria.in/api/v1/bridge';
public string $dreamsoftBridgeToken = '';
public string $abdmBridgeToken = '';
```

---

### Step 2: Create New Method `buildDispatchContextByType()`

**Replace or update the current** `buildDispatchContext()` **method in** `app/Libraries/BridgeSyncService.php`

#### Current Code (Lines ~335-365)

```php
private function buildDispatchContext(array $row, array $payload): array
{
    $eventType = (string) ($row['event_type'] ?? '');
    if ($eventType === '') {
        return ['ok' => false, 'error' => 'Missing event_type'];
    }

    $isAbdmOrNhcx = str_starts_with($eventType, 'abdm.') || str_starts_with($eventType, 'nhcx.');
    $provider = strtolower($this->readSetting('ABDM_SYNC_PROVIDER'));
    if ($provider === '' || ! $isAbdmOrNhcx) {
        $provider = strtolower($this->readSetting('BRIDGE_SYNC_PROVIDER'));
    }
    if ($provider === '') {
        $provider = 'bridge';
    }

    if ($provider === 'eka' && $isAbdmOrNhcx) {
        return $this->buildEkaDispatchContext($row, $payload);
    }

    return $this->buildBridgeDispatchContext($row, $payload);
}
```

#### Updated Code (ADD THIS)

```php
private function buildDispatchContext(array $row, array $payload): array
{
    $eventType = (string) ($row['event_type'] ?? '');
    if ($eventType === '') {
        return ['ok' => false, 'error' => 'Missing event_type'];
    }

    // NEW ROUTING LOGIC: Route by event type prefix
    if (str_starts_with($eventType, 'snomed.')) {
        return $this->buildCsnotkDispatchContext($row, $payload);
    }

    if (str_starts_with($eventType, 'abdm.') || str_starts_with($eventType, 'nhcx.')) {
        $provider = strtolower($this->readSetting('ABDM_SYNC_PROVIDER'));
        if ($provider === 'eka') {
            return $this->buildEkaDispatchContext($row, $payload);
        }
        return $this->buildAbdmDispatchContext($row, $payload);
    }

    // Fallback for other event types
    $provider = strtolower($this->readSetting('BRIDGE_SYNC_PROVIDER'));
    if ($provider === '') {
        $provider = 'bridge';
    }

    return $this->buildBridgeDispatchContext($row, $payload);
}
```

---

### Step 3: Rename & Update `buildBridgeDispatchContext()` → `buildCsnotkDispatchContext()`

**File:** `app/Libraries/BridgeSyncService.php` (Lines ~365-400)

#### Current Method
```php
private function buildBridgeDispatchContext(array $row, array $payload): array
{
    $endpoint = $this->readSetting('BRIDGE_SYNC_URL');
    if ($endpoint === '') {
        return ['ok' => false, 'error' => 'BRIDGE_SYNC_URL is not configured'];
    }

    $token = $this->readSetting('BRIDGE_SYNC_TOKEN');
    $source = $this->readSetting('BRIDGE_SOURCE_CODE');
    if ($source === '') {
        $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');
    }

    $headers = ['Content-Type' => 'application/json'];
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    return [
        'ok' => true,
        'channel' => 'bridge',
        'endpoint' => $endpoint,
        'method' => 'POST',
        'headers' => $headers,
        'body' => [
            'queue_id' => (int) ($row['id'] ?? 0),
            'source' => $source,
            'event_type' => (string) ($row['event_type'] ?? ''),
            'entity_type' => (string) ($row['entity_type'] ?? ''),
            'entity_id' => (string) ($row['entity_id'] ?? ''),
            'payload' => $payload,
            'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
        ],
    ];
}
```

#### Rename to `buildCsnotkDispatchContext()` (Keep as is)

```php
private function buildCsnotkDispatchContext(array $row, array $payload): array
{
    $endpoint = $this->readSetting('BRIDGE_SYNC_URL');
    if ($endpoint === '') {
        return ['ok' => false, 'error' => 'BRIDGE_SYNC_URL (CSNOtk) is not configured'];
    }

    $token = $this->readSetting('BRIDGE_SYNC_TOKEN');
    $source = $this->readSetting('BRIDGE_SOURCE_CODE');
    if ($source === '') {
        $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');
    }

    $headers = ['Content-Type' => 'application/json'];
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    return [
        'ok' => true,
        'channel' => 'csnotk',  // Changed from 'bridge' to 'csnotk'
        'endpoint' => $endpoint,
        'method' => 'POST',
        'headers' => $headers,
        'body' => [
            'queue_id' => (int) ($row['id'] ?? 0),
            'source' => $source,
            'event_type' => (string) ($row['event_type'] ?? ''),
            'entity_type' => (string) ($row['entity_type'] ?? ''),
            'entity_id' => (string) ($row['entity_id'] ?? ''),
            'payload' => $payload,
            'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
        ],
    ];
}
```

---

### Step 4: Create New Method `buildAbdmDispatchContext()`

**File:** `app/Libraries/BridgeSyncService.php` (Add after `buildCsnotkDispatchContext()`)

```php
/**
 * Build dispatch context for ABDM Bridge (routes to https://abdm-bridge.e-atria.in/api/v1/bridge)
 *
 * @param array<string, mixed> $row
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
private function buildAbdmDispatchContext(array $row, array $payload): array
{
    $endpoint = $this->readSetting('ABDM_BRIDGE_URL');
    if ($endpoint === '') {
        return ['ok' => false, 'error' => 'ABDM_BRIDGE_URL is not configured'];
    }

    $token = $this->readSetting('ABDM_BRIDGE_TOKEN');
    $source = $this->readSetting('BRIDGE_SOURCE_CODE');
    if ($source === '') {
        $source = (string) ($this->readSetting('HOSPITAL_CODE') ?: 'hms-local');
    }

    $headers = ['Content-Type' => 'application/json'];
    if ($token !== '') {
        $headers['Authorization'] = 'Bearer ' . $token;
    }

    return [
        'ok' => true,
        'channel' => 'abdm',  // Distinct from 'csnotk'
        'endpoint' => $endpoint,
        'method' => 'POST',
        'headers' => $headers,
        'body' => [
            'queue_id' => (int) ($row['id'] ?? 0),
            'source' => $source,
            'event_type' => (string) ($row['event_type'] ?? ''),
            'entity_type' => (string) ($row['entity_type'] ?? ''),
            'entity_id' => (string) ($row['entity_id'] ?? ''),
            'payload' => $payload,
            'occurred_at' => (string) ($row['created_at'] ?? Time::now('Asia/Kolkata')->toDateTimeString()),
        ],
    ];
}
```

---

### Step 5: Rename OLD `buildBridgeDispatchContext()` to `buildLegacyDispatchContext()`

**For backward compatibility,** keep the old method but rename it:

```php
/**
 * Legacy bridge context (fallback for unknown event types)
 * @deprecated Use buildCsnotkDispatchContext() or buildAbdmDispatchContext()
 *
 * @param array<string, mixed> $row
 * @param array<string, mixed> $payload
 * @return array<string, mixed>
 */
private function buildLegacyDispatchContext(array $row, array $payload): array
{
    // ... original buildBridgeDispatchContext() code ...
}
```

---

## Testing Checklist

### Test 1: Queue SNOMED Event
```bash
# In HMS database, insert test record:
INSERT INTO bridge_sync_queue (
    channel, event_type, entity_type, entity_id,
    payload_json, payload_hash, status, attempts, max_attempts
) VALUES (
    'bridge',
    'snomed.diagnosis.validate',
    'diagnosis',
    '0',
    '{"diagnosis_text":"fever","return_limit":10}',
    SHA2('snomed.diagnosis.validate|diagnosis|0|...', 256),
    'pending',
    0,
    10
);

# Run queue processor
php spark bridge:sync --limit 5

# Check logs
SELECT * FROM abdm_api_logs WHERE channel='csnotk' AND event_type='snomed.diagnosis.validate' ORDER BY created_at DESC LIMIT 1;
```

**Expected:**
- Queue record status changed to 'sent' (or 'retry' if CSNOtk unavailable)
- `abdm_api_logs.endpoint` = `https://csnotk.e-atria.in/api/bridge`
- `abdm_api_logs.channel` = 'csnotk'

### Test 2: Queue ABDM Event
```bash
# In HMS database, insert test record:
INSERT INTO bridge_sync_queue (
    channel, event_type, entity_type, entity_id,
    payload_json, payload_hash, status, attempts, max_attempts
) VALUES (
    'bridge',
    'abdm.abha.validate',
    'patient',
    '12345',
    '{"abha_id":"00-0000-0000-0000"}',
    SHA2('abdm.abha.validate|patient|12345|...', 256),
    'pending',
    0,
    10
);

# Run queue processor
php spark bridge:sync --limit 5

# Check logs
SELECT * FROM abdm_api_logs WHERE channel='abdm' AND event_type='abdm.abha.validate' ORDER BY created_at DESC LIMIT 1;
```

**Expected:**
- Queue record status changed to 'sent' (or 'retry' if gateway unavailable)
- `abdm_api_logs.endpoint` = `https://abdm-bridge.e-atria.in/api/v1/bridge`
- `abdm_api_logs.channel` = 'abdm'

---

## Configuration Before Testing

**Update HMS `.env`:**
```env
BRIDGE_SYNC_URL = https://csnotk.e-atria.in/api/bridge
BRIDGE_SYNC_TOKEN = <32-char-hex-token-1>
ABDM_BRIDGE_URL = https://abdm-bridge.e-atria.in/api/v1/bridge
ABDM_BRIDGE_TOKEN = <32-char-hex-token-2>
BRIDGE_SOURCE_CODE = SBXID_033661
```

**Generate tokens:**
```bash
openssl rand -hex 32  # Token 1
openssl rand -hex 32  # Token 2
```

---

## Code Review Checklist

- [ ] `buildDispatchContext()` correctly identifies event prefix (snomed.* vs abdm.*)
- [ ] `buildCsnotkDispatchContext()` uses correct URL and token keys
- [ ] `buildAbdmDispatchContext()` uses correct URL and token keys
- [ ] Channel is set correctly ('csnotk' or 'abdm') for logging
- [ ] Both methods return same structure for `processPending()` to consume
- [ ] Error handling for missing config keys
- [ ] Backward compatibility maintained for legacy event types

---

## References

- **Dual-Bridge Architecture:** `DUAL_BRIDGE_ARCHITECTURE.md`
- **BridgeSyncService:** `app/Libraries/BridgeSyncService.php`
- **AbdmConnector Config:** `app/Config/AbdmConnector.php`
- **API Logs Table:** `bridge_sync_queue`, `abdm_api_logs`
