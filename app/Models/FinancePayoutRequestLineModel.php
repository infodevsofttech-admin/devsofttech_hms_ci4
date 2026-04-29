<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePayoutRequestLineModel extends Model
{
    protected $table = 'finance_payout_request_lines';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'request_id',
        'source_domain',
        'source_type',
        'source_ref_id',
        'invoice_id',
        'invoice_code',
        'ipd_id',
        'ipd_code',
        'case_id',
        'case_code',
        'credit_category',
        'line_amount',
        'allocated_amount',
        'pending_amount',
        'line_status',
        'line_remarks',
        'line_order',
        'created_at',
        'updated_at',
    ];
}
