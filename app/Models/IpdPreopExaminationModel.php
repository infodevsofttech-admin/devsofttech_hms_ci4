<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdPreopExaminationModel extends Model
{
    protected $table = 'ipd_preop_examinations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'ipd_id',
        'patient_id',
        'department_id',
        'department_name_snapshot',
        'form_key',
        'schema_version',
        'episode_no',
        'payload_json',
        'status',
        'examined_by',
        'examined_at',
        'created_by',
        'updated_by',
    ];

    public function findForAdmission(int $ipdId, string $formKey, int $episodeNo = 1): array
    {
        return $this->where('ipd_id', $ipdId)
            ->where('form_key', $formKey)
            ->where('episode_no', $episodeNo)
            ->first() ?? [];
    }
}