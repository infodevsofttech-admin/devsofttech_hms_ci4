<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicalModel extends Model
{
    protected $DBGroup = 'default';

    public function updatePurchaseStockById(int $purchaseItemId): bool
    {
        if ($purchaseItemId <= 0 || ! $this->db->tableExists('purchase_invoice_item')) {
            return false;
        }

        try {
            // Recompute total sold units directly from sale items if available
            if ($this->db->tableExists('inv_med_item')) {
                $itemFields = $this->db->getFieldNames('inv_med_item') ?? [];
                $sumSql = in_array('sale_return', $itemFields, true)
                    ? 'SUM(CASE WHEN IFNULL(sale_return,0)=1 OR IFNULL(amount,0)<0 THEN IFNULL(qty,0)*-1 ELSE IFNULL(qty,0) END) AS sold_qty'
                    : 'SUM(CASE WHEN IFNULL(amount,0)<0 THEN IFNULL(qty,0)*-1 ELSE IFNULL(qty,0) END) AS sold_qty';

                $soldRow = $this->db->table('inv_med_item')
                    ->select($sumSql, false)
                    ->where('store_stock_id', $purchaseItemId)
                    ->get()
                    ->getRow();
                $soldQty = max(0.0, (float) ($soldRow->sold_qty ?? 0));

                $stockRow = $this->db->table('purchase_invoice_item')
                    ->select('id, total_sale_unit')
                    ->where('id', $purchaseItemId)
                    ->get()
                    ->getRow();

                if ($stockRow) {
                    $currentSaleQty = (float) ($stockRow->total_sale_unit ?? 0);
                    if (abs($currentSaleQty - $soldQty) >= 0.00001) {
                        $this->db->table('purchase_invoice_item')
                            ->where('id', $purchaseItemId)
                            ->update(['total_sale_unit' => $soldQty]);
                    }
                }
            }
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'updatePurchaseStockById failed: ' . $e->getMessage());
            return false;
        }
    }

    public function transferSaleSsno(int $oldSsno, int $newSsno, int $qty): bool
    {
        if ($oldSsno <= 0 || $newSsno <= 0 || $qty <= 0) {
            return false;
        }

        try {
            if ($this->db->tableExists('inv_med_item')) {
                $this->db->table('inv_med_item')
                    ->where('store_stock_id', $oldSsno)
                    ->limit(1)
                    ->update(['store_stock_id' => $newSsno]);

                $this->updatePurchaseStockById($oldSsno);
                $this->updatePurchaseStockById($newSsno);
            }
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'transferSaleSsno failed: ' . $e->getMessage());
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
