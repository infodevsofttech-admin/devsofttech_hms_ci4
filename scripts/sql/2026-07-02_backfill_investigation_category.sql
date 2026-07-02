-- Backfill blank investigation.category_name values using name-based rules.
-- Safe to run multiple times: updates only rows where category_name is NULL/blank.

START TRANSACTION;

UPDATE investigation
SET category_name = CASE
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(x[[:space:]]*[-]?[[:space:]]*ray|xray|radiograph|radiology)([^a-z]|$)' THEN 'X-Ray'
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(ultra[[:space:]]*sound|ultrasound|u\\.?s\\.?g\\.?|usg|sonography|doppler)([^a-z]|$)' THEN 'Ultra Sound'
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(ct[[:space:]]*[-]?[[:space:]]*scan|cect|hrct|ncct|computed tomography)([^a-z]|$)' THEN 'CT-Scan'
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(mri|mr[[:space:]]*i|magnetic resonance)([^a-z]|$)' THEN 'MRI'
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(echo|echocardiography|2d[[:space:]]*echo|doppler[[:space:]]*echo)([^a-z]|$)' THEN 'ECHO'
    WHEN LOWER(Name) REGEXP '(^|[^a-z])(ecg|electrocardiogram|tmt|holter)([^a-z]|$)' THEN 'Cardiology'
    ELSE 'Pathology'
END
WHERE category_name IS NULL OR TRIM(category_name) = '';

SELECT ROW_COUNT() AS updated_rows;

SELECT category_name, COUNT(*) AS total_rows
FROM investigation
GROUP BY category_name
ORDER BY total_rows DESC, category_name ASC;

COMMIT;
