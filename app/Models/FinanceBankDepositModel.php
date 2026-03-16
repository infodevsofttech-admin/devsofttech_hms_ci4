<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceBankDepositModel extends Model
{
    protected $table = 'finance_bank_deposits';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'deposit_date',
        'department',
        'bank_name',
        'slip_no',
        'deposited_amount',
        'related_scroll_id',
        'reconciliation_status',
        'remarks',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
