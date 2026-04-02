<?php

namespace App\Models;

use CodeIgniter\Model;

class HospitalStockSupplierModel extends Model
{
    protected $table = 'hsm_suppliers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'gst_no',
        'status',
    ];
    protected $useTimestamps = true;
}
