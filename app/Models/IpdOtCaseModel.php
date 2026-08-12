<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdOtCaseModel extends Model
{
    protected $table = 'ipd_ot_cases';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'request_no', 'ipd_id', 'patient_id', 'department_id', 'department_name_snapshot',
        'procedure_name', 'procedure_side', 'priority', 'requested_date', 'requested_time',
        'requested_notes', 'scheduled_start_at', 'surgeon_id', 'surgeon_name_snapshot',
        'anesthetist_id', 'anesthetist_name_snapshot', 'status', 'call_status', 'called_at',
        'called_by', 'confirmed_at', 'confirmed_by', 'consent_verified', 'site_side_verified',
        'allergy_verified', 'npo_verified', 'investigations_verified', 'anesthesia_clearance',
        'blood_availability', 'who_sign_in', 'who_time_out', 'who_sign_out',
        'actual_start_at', 'actual_end_at', 'lock_version', 'created_by', 'updated_by',
    ];
}