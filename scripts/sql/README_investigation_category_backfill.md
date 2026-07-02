# Investigation Category Backfill (Remote)

Run this SQL on remote DB:

scripts/sql/2026-07-02_backfill_investigation_category.sql

## Example (MySQL CLI)

```bash
mysql -u <db_user> -p <db_name> < scripts/sql/2026-07-02_backfill_investigation_category.sql
```

## What it does

- Updates only blank category values in investigation.category_name
- Uses name-based rules to map into:
  - Pathology
  - X-Ray
  - Ultra Sound
  - CT-Scan
  - MRI
  - ECHO
  - Cardiology
- Prints updated row count and final category distribution

## Idempotency

Safe to re-run. It only touches rows where category_name is blank/null.
