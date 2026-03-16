<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePharmacyBillModel extends Model
{
    protected $table         = 'finance_pharmacy_bills';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'bill_no', 'bill_date', 'pharmacy_name', 'description',
        'bill_amount', 'tax_amount', 'net_amount',
        'payment_status', 'paid_amount', 'payment_date',
        'payment_mode', 'payment_ref', 'remarks',
        'created_by', 'created_at', 'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
