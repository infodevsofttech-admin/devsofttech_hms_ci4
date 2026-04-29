<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePayoutRequestModel extends Model
{
    protected $table = 'finance_payout_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'request_no',
        'request_type',
        'requester_unit',
        'beneficiary_unit',
        'request_date',
        'requested_amount',
        'approved_amount',
        'paid_amount',
        'pending_amount',
        'status',
        'priority',
        'remarks',
        'submitted_at',
        'finance_reviewed_at',
        'approved_at',
        'closed_at',
        'created_by',
        'created_by_id',
        'updated_by',
        'updated_by_id',
        'created_at',
        'updated_at',
    ];
}
