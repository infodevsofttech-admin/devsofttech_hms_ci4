<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceVendorModel extends Model
{
    protected $table = 'finance_vendors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'vendor_code',
        'vendor_name',
        'contact_person',
        'phone',
        'email',
        'gst_no',
        'pan_no',
        'address',
        'status',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
