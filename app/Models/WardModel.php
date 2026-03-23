<?php

namespace App\Models;

use CodeIgniter\Model;

class WardModel extends Model
{
    protected $table      = 'ward_master';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'ward_code',
        'ward_name',
        'department_id',
        'building_id',
        'floor_number',
        'ward_type',
        'gender_type',
        'ward_category',
        'total_capacity',
        'nurse_station_location',
        'has_oxygen',
        'has_suction',
        'has_monitor',
        'status',
        'remarks',
        'created_by',
    ];

    public function getAll(): array
    {
        return $this->orderBy('ward_name', 'ASC')->findAll();
    }

    public function getAllActive(): array
    {
        return $this->where('status', 'active')
            ->orderBy('ward_name', 'ASC')
            ->findAll();
    }
}
