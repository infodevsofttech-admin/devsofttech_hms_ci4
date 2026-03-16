<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceGrnModel extends Model
{
    protected $table = 'finance_grns';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'grn_no',
        'grn_date',
        'po_id',
        'received_amount',
        'received_by',
        'remarks',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
