# ABDM M3 HIU QA Report

## Build Info
- Date:
- Branch/Commit:
- Tester:

## Test Matrix

### 1. Happy path
- request -> status -> fetch -> health-information request
- Expected: workflow reaches COMPLETED
- Result:
- Evidence:

### 2. Missing token
- Remove/blank EATRIA_BRIDGE_TOKEN
- Expected: unauthorized/fail-fast error, no gateway call
- Result:
- Evidence:

### 3. Missing hfr_id
- Remove/blank ABDM_HFR_ID
- Expected: validation fail-fast
- Result:
- Evidence:

### 4. Wrong hfr_id/token mismatch
- Send payload hfr_id that mismatches configured ABDM_HFR_ID
- Expected: blocked with clear actionable message
- Result:
- Evidence:

### 5. Duplicate request_id replay
- Re-send same request_id + operation
- Expected: idempotent response, no duplicate row
- Result:
- Evidence:

### 6. Duplicate transaction_id replay
- Re-send same transaction_id + operation
- Expected: idempotent response, no duplicate row
- Result:
- Evidence:

### 7. Timeout/5xx retry behavior
- Simulate transient gateway failure
- Expected: retryable=1; queued for retry without duplicate workflow rows
- Result:
- Evidence:

### 8. Consent revoked flow
- status returns revoked
- Expected: workflow state REVOKED
- Result:
- Evidence:

### 9. Consent expired flow
- status returns expired
- Expected: workflow state EXPIRED
- Result:
- Evidence:

### 10. Audit completeness
- Verify request/response/status/error/hospital context persisted
- Result:
- Evidence:

## Defects
| ID | Severity | Scenario | Description | Status |
|----|----------|----------|-------------|--------|

## Final Signoff
- [ ] All 4 APIs integrated
- [ ] Full audit trail verified
- [ ] Hospital isolation by hfr_id verified
- [ ] All QA scenarios passed
- [ ] Runbook shared with support team

Signoff By:
Date:
