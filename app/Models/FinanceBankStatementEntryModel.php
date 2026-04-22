<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceBankStatementEntryModel extends Model
{
    protected $table            = 'finance_bank_statement_entries';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'entry_date',
        'reference_no',
        'narration',
        'amount',
        'transaction_type',
        'matched_payment_id',
        'reconciliation_status',
        'matched_by',
        'matched_at',
        'remarks',
        'created_by',
    ];
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
}
