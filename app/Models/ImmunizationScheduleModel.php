<?php

namespace App\Models;

use CodeIgniter\Model;

class ImmunizationScheduleModel extends Model
{
    protected $table = 'immunization_schedule_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'schedule_name',
        'age_label',
        'age_value',
        'age_unit',
        'age_offset_days',
        'vaccine_master_id',
        'dose_number',
        'series_name',
        'series_doses',
        'notes',
        'sort_order',
        'is_uip',
        'is_active',
        'created_at',
        'updated_at',
    ];
}