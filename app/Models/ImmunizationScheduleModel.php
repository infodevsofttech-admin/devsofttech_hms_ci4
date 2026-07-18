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
        'schedule_code',
        'beneficiary_category',
        'gender_applicability',
        'age_label',
        'age_value',
        'age_unit',
        'age_offset_days',
        'min_age_days',
        'max_age_days',
        'gateway_schedule_id',
        'vaccine_master_id',
        'dose_number',
        'series_name',
        'series_doses',
        'notes',
        'sort_order',
        'is_uip',
        'is_active',
        'is_state_specific',
        'state_code',
        'district_code',
        'applicability_note',
        'source_version_code',
        'source_checksum',
        'source_name',
        'source_url',
        'source_document_name',
        'effective_from',
        'created_at',
        'updated_at',
    ];
}