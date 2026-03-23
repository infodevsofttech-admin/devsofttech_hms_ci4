<?php

namespace App\Models;

use CodeIgniter\Model;

class BedCategoryModel extends Model
{
    protected $table       = 'bed_category_master';
    protected $primaryKey  = 'id';
    protected $returnType  = 'object';
    protected $allowedFields = [
        'category_code',
        'category_name',
        'category_type',
        'base_charge_per_day',
        'nursing_charge_per_day',
        'amenities',
        'description',
        'status',
    ];

    public function getAll(): array
    {
        return $this->orderBy('category_name', 'ASC')->findAll();
    }
}
