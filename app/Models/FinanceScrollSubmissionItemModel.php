<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceScrollSubmissionItemModel extends Model
{
    protected $table = 'finance_scroll_submission_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'scroll_submission_id',
        'payment_history_id',
        'payment_date',
        'amount',
        'payment_mode',
        'payof_type',
        'payof_id',
        'payof_code',
        'update_by_id',
        'update_by',
        'snapshot_json',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
