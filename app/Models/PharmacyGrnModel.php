<?php

namespace App\Models;

use CodeIgniter\Model;

class PharmacyGrnModel extends Model
{
    protected $table = 'mst_grns';
    protected $primaryKey = 'grn_id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'grn_number',
        'store_id',
        'po_id',
        'supplier_id',
        'challan_no',
        'supplier_invoice_no',
        'invoice_date',
        'grn_date',
        'total_received_amount',
        'total_tax_amount',
        'net_amount',
        'status',
        'purchase_id',
        'received_by',
        'verified_by',
        'verified_at',
        'remarks',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function generateGrnNumber(): string
    {
        $prefix = 'GRN-' . date('ym');
        $row = $this->like('grn_number', $prefix, 'after')
            ->orderBy('grn_id', 'DESC')
            ->first();

        $seq = 1;
        if ($row && !empty($row['grn_number'])) {
            $parts = explode('-', $row['grn_number']);
            $lastSeq = (int) end($parts);
            if ($lastSeq > 0) {
                $seq = $lastSeq + 1;
            }
        }

        return $prefix . '-' . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
