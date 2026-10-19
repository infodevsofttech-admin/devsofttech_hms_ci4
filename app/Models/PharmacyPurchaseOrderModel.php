<?php

namespace App\Models;

use CodeIgniter\Model;

class PharmacyPurchaseOrderModel extends Model
{
    protected $table = 'mst_purchase_orders';
    protected $primaryKey = 'po_id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'store_id',
        'supplier_id',
        'po_number',
        'po_date',
        'expected_delivery_date',
        'payment_terms',
        'total_items',
        'total_estimated_amount',
        'tax_amount',
        'grand_total',
        'status',
        'remarks',
        'created_by',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancelled_reason',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function generatePoNumber(): string
    {
        $prefix = 'PO-' . date('ym');
        $row = $this->like('po_number', $prefix, 'after')
            ->orderBy('po_id', 'DESC')
            ->first();

        $seq = 1;
        if ($row && !empty($row['po_number'])) {
            $parts = explode('-', $row['po_number']);
            $lastSeq = (int) end($parts);
            if ($lastSeq > 0) {
                $seq = $lastSeq + 1;
            }
        }

        return $prefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
