<?php

namespace App\Models;

use CodeIgniter\Model;

class BedMasterModel extends Model
{
    protected $table      = 'bed_master';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'bed_code',
        'bed_number',
        'ward_id',
        'bed_category_id',
        'bed_status',
        'bed_position',
        'has_oxygen',
        'has_suction',
        'has_monitor',
        'has_ventilator',
        'is_isolation_bed',
        'remarks',
        'base_charge_override',
        'nursing_charge_override',
        'status',
    ];

    public function getAllWithRelations(): array
    {
        return $this->select('bed_master.*, ward_master.ward_name, bed_category_master.category_name')
            ->join('ward_master', 'ward_master.id = bed_master.ward_id', 'left')
            ->join('bed_category_master', 'bed_category_master.id = bed_master.bed_category_id', 'left')
            ->orderBy('bed_master.id', 'DESC')
            ->findAll();
    }
}
