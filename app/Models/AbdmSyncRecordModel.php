<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmSyncRecordModel extends Model
{
    protected $table = 'abdm_sync_record';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'local_record_id',
        'local_patient_id',
        'hi_type',
        'care_context_reference',
        'care_context_display',
        'visit_date',
        'department',
        'doctor_name',
        'fhir_bundle_json',
        'consent_id',
        'hfr_id',
        'source_updated_at',
        'sync_status',
        'gateway_record_id',
        'gateway_queue_id',
        'last_synced_at',
        'last_error',
        'retry_count',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
