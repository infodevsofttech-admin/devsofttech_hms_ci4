<?php

namespace App\Models;

use CodeIgniter\Model;

class ImmunizationRecordModel extends Model
{
    protected $table = 'immunization_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'patient_id',
        'opd_id',
        'ipd_id',
        'schedule_id',
        'vaccine_master_id',
        'vaccine_name',
        'vaccine_code',
        'vaccine_code_system',
        'dose_number',
        'due_date',
        'given_date',
        'status',
        'lot_number',
        'expiry_date',
        'manufacturer',
        'performer_id',
        'location_name',
        'route_code',
        'route_name',
        'site_code',
        'site_name',
        'reaction_notes',
        'notes',
        'abdm_care_context_reference',
        'abdm_health_record_id',
        'abdm_push_status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];
}