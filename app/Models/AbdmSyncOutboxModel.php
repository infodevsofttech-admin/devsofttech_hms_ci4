<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmSyncOutboxModel extends Model
{
    protected $table = 'abdm_sync_outbox';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'entity_type',
        'entity_id',
        'idempotency_key',
        'payload_json',
        'status',
        'next_retry_at',
        'retry_count',
        'last_error',
        'locked_at',
        'worker_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
