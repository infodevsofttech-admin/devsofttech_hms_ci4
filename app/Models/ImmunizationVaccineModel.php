<?php

namespace App\Models;

use CodeIgniter\Model;

class ImmunizationVaccineModel extends Model
{
    protected $table = 'immunization_vaccine_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'vaccine_name',
        'vaccine_code',
        'vaccine_code_system',
        'vaccine_display',
        'target_disease_code',
        'target_disease_name',
        'route_code',
        'route_name',
        'site_code',
        'site_name',
        'is_active',
        'created_at',
        'updated_at',
    ];
}