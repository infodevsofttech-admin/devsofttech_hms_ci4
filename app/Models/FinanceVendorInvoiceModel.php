<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceVendorInvoiceModel extends Model
{
    protected $table = 'finance_vendor_invoices';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'invoice_no',
        'invoice_date',
        'vendor_id',
        'po_id',
        'grn_id',
        'invoice_amount',
        'payment_status',
        'match_status',
        'variance_amount',
        'match_note',
        'is_compliance_hold',
        'match_checked_at',
        'remarks',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
