# ABDM M2 Push Sync Implementation

## Architecture Summary
- Added dedicated sync tables: `abdm_sync_patient`, `abdm_sync_record`, `abdm_sync_outbox`.
- Added queue/outbox service and worker command for reliable push delivery.
- Added Gateway client for `/api/v1/abdm/gateway/care-context/link` with validation and response mapping.
- Added FHIR generator framework with module-specific generators.
- Added advanced coding resolver with LOINC/SNOMED/UCUM fallback logic and validation scoring.
- Added observability endpoints:
  - `GET /AbdmSync/summary`
  - `POST /AbdmSync/replay_dead/{id}`

## HMS Field Mapping Contract (Core)
- `patient.id` -> `Patient.identifier[system=https://hms.local/patient-id].value` (mandatory)
- `patient.abha_id` -> `Patient.identifier[system=https://healthid.ndhm.gov.in].value` (optional)
- `patient.abha_address` -> `Patient.identifier[system=https://abdm.gov.in/health-address].value` (optional)
- `encounter.id` -> `Encounter.identifier.value` (optional/mandatory if linked visit)
- `record_id` -> Composition/diagnostic/claim resource IDs (mandatory)
- `completed_at` -> `Composition.date`, `Bundle.timestamp`, report `issued` (mandatory)
- `visit_date` -> gateway payload `visit_date` (mandatory for sync payload)

## HI Type Mapping
- OPD -> `OPConsultRecord`
- Lab -> `DiagnosticReportRecord`
- Discharge -> `DischargeSummaryRecord`
- Invoice -> `InvoiceRecord`
- Radiology -> `DiagnosticReportRecord`

## Quality Policy
- Score >= 85: allow push
- Score 70-84: allow push with warnings
- Score < 70: mark review-needed (policy hook available)

## Outbox Retry Policy
- Care-context outbound default retries: 10s, 30s, 60s
- Legacy schedule fallback still supported for older outbox rows

## Security Notes
- Bearer token never logged.
- ABHA masked in logs (last 4 visible).
- Payload logs exclude full FHIR bundle at info level.

## Sample Generator Outputs
### OPD
- Source fixture: `tests/unit/Abdm/fixtures/opd_source.json`
- Generator: `App\\Libraries\\Abdm\\Fhir\\Generators\\OpdFhirGenerator`
- Output: `hi_type=OPConsultRecord`, deterministic care-context.

### Lab
- Source fixture: `tests/unit/Abdm/fixtures/lab_source.json`
- Generator: `App\\Libraries\\Abdm\\Fhir\\Generators\\LabFhirGenerator`
- Includes `DiagnosticReport` + linked `Observation` resources.

### Discharge
- Source fixture: `tests/unit/Abdm/fixtures/discharge_source.json`
- Generator: `App\\Libraries\\Abdm\\Fhir\\Generators\\DischargeFhirGenerator`

### Invoice
- Source fixture: `tests/unit/Abdm/fixtures/invoice_source.json`
- Generator: `App\\Libraries\\Abdm\\Fhir\\Generators\\InvoiceFhirGenerator`

## Commands
- Worker: `php spark abdm:push-sync --limit=20`
- Existing bridge worker remains separate: `php spark bridge:sync`

## Cron Setup (HMS Server)
- Use `bash scripts/install_hms_cron.sh /var/www/html/hms_etria`
- Deploy script now runs this installer automatically after migrate.
- Managed cron block includes:
  - `abdm:push-sync` every minute
  - `bridge:sync` every minute
  - `snomed:process-coding` every minute
  - `abdm:hiu-poll` every 2 minutes
  - `abdm:hiu-retry` every 5 minutes

## Manual Replay
- Endpoint: `POST /AbdmSync/replay_dead/{id}`
- Resets dead row to `pending` for reprocessing.
