<?php

namespace App\Models;

use CodeIgniter\Model;

class ClinicalAuditTrailModel extends Model
{
    protected $table = 'clinical_audit_trail';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'module',
        'record_id',
        'field_name',
        'old_value',
        'new_value',
        'user_id',
        'action_at',
        'hash',
    ];
    protected $useTimestamps = true;

    protected $allowCallbacks = true;
    protected $beforeUpdate = ['blockMutation'];
    protected $beforeDelete = ['blockMutation'];

    protected function blockMutation(array $data): array
    {
        throw new \RuntimeException('clinical_audit_trail is immutable and cannot be updated or deleted.');
    }
}
