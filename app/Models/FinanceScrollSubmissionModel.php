<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceScrollSubmissionModel extends Model
{
    protected $table = 'finance_scroll_submissions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'scroll_date',
        'start_datetime',
        'end_datetime',
        'department',
        'collected_by',
        'payment_count',
        'total_receipts',
        'submitted_amount',
        'variance_amount',
        'reconciliation_status',
        'submitted_by',
        'remarks',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
