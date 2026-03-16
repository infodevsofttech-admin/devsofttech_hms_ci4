<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceCashTransactionModel extends Model
{
    protected $table = 'finance_cash_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'txn_date',
        'txn_type',
        'flow_type',
        'department',
        'reference_no',
        'amount',
        'mode',
        'party_name',
        'narration',
        'flag_269st',
        'flag_40a3',
        'is_compliance_hold',
        'compliance_note',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
