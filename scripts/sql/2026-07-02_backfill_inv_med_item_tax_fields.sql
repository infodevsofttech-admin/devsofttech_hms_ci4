-- Backfill GST breakup columns in inv_med_item from existing rate + net amount.
-- Safe to run multiple times; it only targets rows where tax values are currently zero/empty.

UPDATE inv_med_item
SET
    TaxableAmount = ROUND(
        CASE
            WHEN (IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0)) > 0
                THEN (IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0))) * 100)
                     / (100 + IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0))
            ELSE IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0)))
        END
    , 2),
    CGST = ROUND(
        (
            CASE
                WHEN (IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0)) > 0
                    THEN (IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0))) * 100)
                         / (100 + IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0))
                ELSE IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0)))
            END
        ) * IFNULL(CGST_per, 0) / 100
    , 2),
    SGST = ROUND(
        (
            CASE
                WHEN (IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0)) > 0
                    THEN (IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0))) * 100)
                         / (100 + IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0))
                ELSE IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0)))
            END
        ) * IFNULL(SGST_per, 0) / 100
    , 2),
    IGST = ROUND(
        (
            CASE
                WHEN (IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0)) > 0
                    THEN (IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0))) * 100)
                         / (100 + IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0))
                ELSE IFNULL(twdisc_amount, IFNULL(tamount, IFNULL(amount, 0)))
            END
        ) * IFNULL(IGST_per, 0) / 100
    , 2)
WHERE
    (IFNULL(CGST_per, 0) + IFNULL(SGST_per, 0) + IFNULL(IGST_per, 0)) >= 0
    AND (
        IFNULL(TaxableAmount, 0) = 0
        OR IFNULL(CGST, 0) = 0
        OR IFNULL(SGST, 0) = 0
        OR IFNULL(IGST, 0) = 0
    );

-- Quick verification sample:
-- SELECT id, inv_med_id, qty, amount, tamount, twdisc_amount, TaxableAmount, CGST_per, SGST_per, IGST_per, CGST, SGST, IGST
-- FROM inv_med_item
-- WHERE inv_med_id IN (1002584, 1002585, 1002586)
-- ORDER BY id;
