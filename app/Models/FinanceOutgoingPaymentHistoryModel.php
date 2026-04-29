<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceOutgoingPaymentHistoryModel extends Model
{
    protected $table = 'finance_outgoing_payment_history';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;
    protected $allowedFields = [
        'payment_no',
        'payout_type',
        'payee_label',
        'request_id',
        'payment_date',
        'amount',
        'payment_mode',
        'pay_bank_id',
        'card_bank',
        'card_remark',
        'cust_card',
        'card_tran_id',
        'bankcard_machine',
        'insert_code',
        'credit_debit',
        'remark',
        'status',
        'bank_reconcile_status',
        'bank_statement_entry_id',
        'bank_reconcile_batch_ref',
        'bank_settlement_entry_id',
        'created_by',
        'created_by_id',
        'created_at',
        'updated_at',
    ];
}
