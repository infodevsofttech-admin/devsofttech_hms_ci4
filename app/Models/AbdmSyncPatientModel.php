<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmSyncPatientModel extends Model
{
    protected $table = 'abdm_sync_patient';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'local_patient_id',
        'abha_id',
        'abha_address',
        'name',
        'gender',
        'dob',
        'mobile',
        'email',
        'source_updated_at',
        'sync_status',
        'last_synced_at',
        'last_error',
        'retry_count',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
