<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePayoutRequestAuditModel extends Model
{
    protected $table = 'finance_payout_request_audit';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'request_id',
        'action_type',
        'old_status',
        'new_status',
        'action_note',
        'action_by',
        'action_by_id',
        'action_at',
    ];
}
