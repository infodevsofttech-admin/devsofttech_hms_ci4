<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicalModel extends Model
{
    protected $DBGroup = 'default';

    public function updatePurchaseStockById(int $purchaseItemId): bool
    {
        if ($purchaseItemId <= 0) {
            return false;
        }

        try {
            $this->db->query('CALL p_stock_update_purchase_id(?)', [$purchaseItemId]);
            $this->clearProcedureResults();
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'p_stock_update_purchase_id failed: ' . $e->getMessage());
            return false;
        }
    }

    public function transferSaleSsno(int $oldSsno, int $newSsno, int $qty): bool
    {
        if ($oldSsno <= 0 || $newSsno <= 0 || $qty <= 0) {
            return false;
        }

        try {
            $this->db->query('CALL p_sale_transfer_ssno(?,?,?)', [$oldSsno, $newSsno, $qty]);
            $this->clearProcedureResults();
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'p_sale_transfer_ssno failed: ' . $e->getMessage());
            return false;
        }
    }

    private function clearProcedureResults(): void
    {
        $conn = $this->db->connID;
        if (! $conn instanceof \mysqli) {
            return;
        }

        while ($conn->more_results() && $conn->next_result()) {
            $result = $conn->use_result();
            if ($result instanceof \mysqli_result) {
                $result->free();
            }
        }
    }
}
