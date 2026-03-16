<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePurchaseOrderModel extends Model
{
    protected $table = 'finance_purchase_orders';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'po_no',
        'po_date',
        'vendor_id',
        'department',
        'amount',
        'approval_status',
        'po_document_name',
        'po_document_path',
        'remarks',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
