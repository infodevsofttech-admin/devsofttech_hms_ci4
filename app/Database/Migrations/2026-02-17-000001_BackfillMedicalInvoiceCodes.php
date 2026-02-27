<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class BackfillMedicalInvoiceCodes extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('invoice_med_master')) {
            return;
        }

        if (! $this->db->fieldExists('id', 'invoice_med_master') || ! $this->db->fieldExists('inv_med_code', 'invoice_med_master')) {
            return;
        }

        $invDateExpression = $this->db->fieldExists('inv_date', 'invoice_med_master')
            ? "COALESCE(NULLIF(inv_date, '0000-00-00'), CURDATE())"
            : 'CURDATE()';

        $sql = "UPDATE invoice_med_master
            SET inv_med_code = CONCAT(
                'M',
                DATE_FORMAT({$invDateExpression}, '%y%m'),
                LPAD(CAST(id AS CHAR), 7, '0')
            )
            WHERE (inv_med_code IS NULL OR TRIM(inv_med_code) = '')
              AND id IS NOT NULL
              AND id > 0";

        $this->db->query($sql);
    }

    public function down(): void
    {
        // Data backfill is intentionally not reversed.
    }
}
