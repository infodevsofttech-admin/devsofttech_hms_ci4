<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceBankReconciliationAuditModel extends Model
{
    protected $table            = 'finance_bank_reconciliation_audit';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'payment_history_id',
        'action_type',
        'old_status',
        'new_status',
        'batch_ref',
        'remarks',
        'action_by',
        'action_at',
    ];
    protected $useTimestamps = false;
}
