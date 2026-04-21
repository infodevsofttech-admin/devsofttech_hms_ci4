<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceBankSettlementEntryModel extends Model
{
    protected $table = 'finance_bank_settlement_entries';

    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $useAutoIncrement = true;

    protected $allowedFields = [
        'settlement_ref',
        'settlement_date',
        'payment_count',
        'total_amount',
        'reconciliation_status',
        'statement_matched_by',
        'statement_matched_at',
        'statement_match_remarks',
        'remarks',
        'created_by',
        'created_at',
    ];

    protected $useTimestamps = false;
}
