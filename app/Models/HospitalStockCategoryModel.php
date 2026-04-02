<?php

namespace App\Models;

use CodeIgniter\Model;

class HospitalStockCategoryModel extends Model
{
    protected $table = 'hsm_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'description', 'status'];
    protected $useTimestamps = true;
}
