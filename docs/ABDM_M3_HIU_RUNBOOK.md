# ABDM M3 HIU Runbook

## Scope
This runbook covers HMS-side M3 HIU integration to gateway endpoints:
- POST /api/v3/hiu/consent/request
- POST /api/v3/hiu/consent/request/status
- POST /api/v3/hiu/consent/request/fetch
- POST /api/v3/hiu/health-information/request

## Prerequisites
1. Hospital settings must include:
- EATRIA_BRIDGE_TOKEN
- ABDM_HFR_ID
- ABDM_HOSPITAL_ID (optional but recommended)
2. Permissions required:
- abdm.access OR abdm.taskboard.access OR abdm.gateway.use

## Deploy Steps
1. Pull latest code.
2. Run migration:
```bash
php spark migrate --namespace App
```
3. Verify new table exists:
- abdm_hiu_workflows
4. Open HIU UI:
- /AbdmHiu

## Operational Flow
1. Create Consent Request -> state REQUESTED
2. Check Consent Status -> state STATUS_CHECKED
3. Fetch Consent Artifact -> state CONSENT_FETCHED
4. Request Health Information -> state DATA_REQUESTED / COMPLETED

## Workflow States
- CREATED
- REQUESTED
- STATUS_CHECKED
- CONSENT_FETCHED
- DATA_REQUESTED
- COMPLETED
- FAILED
- REVOKED
- EXPIRED

## Idempotency
- request_id + operation: unique
- transaction_id + operation: unique
- Duplicate calls return previous result with duplicate=1

## Retry Policy
Only transient errors are retried:
- network/curl errors
- timeout
- HTTP 5xx

Command:
```bash
php spark abdm:hiu-retry --limit 20
```

## Audit Trail
All calls are logged in abdm_api_logs with:
- request_json
- response_json
- response_code
- status
- error_message
- endpoint
- event_type prefixed with abdm.m3.hiu.*

Workflow rows are stored in abdm_hiu_workflows including:
- request_id
- transaction_id
- consent_id
- abha_address
- hfr_id
- state/status timestamps

## Failure Handling
1. Missing token:
- Error: Missing token: set hospital_setting.EATRIA_BRIDGE_TOKEN
2. Missing hfr_id:
- Error: Missing hfr_id: set hospital_setting.ABDM_HFR_ID
3. Token/HFR mismatch:
- Error: hfr_id mismatch with configured ABDM_HFR_ID

## Support Checklist
1. Confirm token and hfr_id in hospital_setting.
2. Reproduce in /AbdmHiu.
3. Check timeline filters by hfr_id/consent_id/transaction_id/date.
4. Check abdm_api_logs for request_id and endpoint error.
5. Retry transient failures using spark command.
