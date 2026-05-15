# SNOMED + CSNOtk Decision Note (May 2026)

## Executive Decision
Adopt a hybrid approach.

1. Keep the current HMS-native SNOMED import and query path as the primary production path.
2. Use CSNOtk as a secondary terminology service for advanced lookup/explore/map/disb capabilities and India extension expansion.
3. Do not replace the existing HMS SNOMED tables or FHIR generation flow at this stage.

This gives low migration risk, faster delivery, and clear ABDM data quality gains.

## Why this decision fits current HMS
The current codebase already has working SNOMED ingestion and integration:

1. Import command exists and loads RF2 snapshot into local SNOMED tables:
   - app/Commands/ImportSnomedSnapshot.php
2. Core SNOMED schema and consult columns are already migrated:
   - app/Database/Migrations/2026-05-12-000202_CreateSnomedCoreTables.php
3. OPD diagnosis lookup and SNOMED resolution are already implemented:
   - app/Controllers/Opd_prescription.php
4. FHIR generation already emits SNOMED coding for conditions:
   - app/Libraries/FhirR4Builder.php

Because this is already in use, replacing it with a full CSNOtk-only path now would create avoidable regression risk.

## What CSNOtk adds (and why still adopt it)
Your local package includes CSNOtk service and APIs:

1. REST service module and deployment docs:
   - CSNOtk-Source-Code-v9.0/CSNOtk-Source-Code-v9.0/src-csno/csnoserv/README.md
2. Rich API surface for terminology operations:
   - search, suggest, lookup, map, explore, validate, disb
   - controllers under csnoserv/rest
3. National extension workflows and ICD/simple map support.

CSNOtk is best used as an adjunct terminology service for advanced operations, not an immediate replacement for existing HMS data model.

## Implementation Recommendation (phased)

### Phase 1 (Now, low risk)
1. Keep current importer and FHIR pipeline as-is.
2. Run latest international release import via existing HMS command.
3. Optionally import India extension files into current local SNOMED store where supported.
4. Add data quality checks at save-time for diagnosis SNOMED concept validity.

### Phase 2 (Next, service augmentation)
1. Deploy CSNOServ separately (Tomcat/JRE11) as terminology sidecar.
2. Add a CI4 bridge client to call CSNOServ APIs for:
   - suggest/search for diagnosis UI
   - validate concept ID
   - lookup preferred terms
3. Keep local DB fallback when CSNOServ is down.

### Phase 2 activation in HMS (.env)
Add these keys:

1. snomed.csnotk.enabled = true
2. snomed.csnotk.baseUrl = http://127.0.0.1:8080/csnoserv
3. snomed.csnotk.timeoutSec = 5

Quick verification URL after login:

1. /Opd_prescription/provisional_diagnosis_search?q=headache

Expected behavior:

1. If CSNOtk responds, results include source as csnotk/csnotk-suggest.
2. If CSNOtk is unavailable, local HMS SNOMED + disease_master fallback still returns rows.

### Phase 3 (Extended India adoption)
1. Use NRCeS national releases (AYUSH, CDCI, other extensions).
2. Wire DISB endpoints for pharmacy knowledge support.
3. Use map/explore APIs for coding assistance and analytics enhancements.

## Go/No-Go Matrix

1. For immediate ABDM stability: GO with current HMS SNOMED path.
2. For richer terminology functions: GO with CSNOtk as sidecar service.
3. For full replacement of local SNOMED stack today: NO-GO (high regression risk, low immediate gain).

## Operational Notes

1. Keep release provenance logs (already supported in current import path).
2. Keep SNOMED Affiliate license and NRCeS release usage compliant.
3. Avoid committing real API keys or credentials in repo snapshots.
