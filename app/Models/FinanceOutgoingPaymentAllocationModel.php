<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceOutgoingPaymentAllocationModel extends Model
{
    protected $table = 'finance_outgoing_payment_allocations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'payment_id',
        'request_id',
        'request_line_id',
        'allocated_amount',
        'allocation_order',
        'allocation_note',
        'created_by',
        'created_by_id',
        'created_at',
    ];
}
