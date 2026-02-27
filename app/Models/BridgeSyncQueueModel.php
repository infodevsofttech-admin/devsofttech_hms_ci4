<?php

namespace App\Models;

use CodeIgniter\Model;

class BridgeSyncQueueModel extends Model
{
    protected $table = 'bridge_sync_queue';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'channel',
        'event_type',
        'entity_type',
        'entity_id',
        'payload_json',
        'payload_hash',
        'status',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'last_error',
        'locked_at',
        'locked_by',
        'sent_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
