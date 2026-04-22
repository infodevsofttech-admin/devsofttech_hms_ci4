<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceBankPosSettlementModel extends Model
{
    protected $table            = 'finance_bank_pos_settlements';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'settlement_date',
        'terminal_id',
        'terminal_name',
        'settlement_amount',
        'system_total',
        'variance',
        'payment_count',
        'status',
        'reconciled_by',
        'reconciled_at',
        'remarks',
        'created_by',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
}
