<?php

namespace App\Models;

use CodeIgniter\Model;

class IpdNursingEntryModel extends Model
{
    protected $table = 'ipd_nursing_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'ipd_id',
        'entry_type',
        'recorded_at',
        'shift_name',
        'temperature_c',
        'pulse_rate',
        'resp_rate',
        'bp_systolic',
        'bp_diastolic',
        'spo2',
        'weight_kg',
        'fluid_direction',
        'fluid_route',
        'fluid_amount_ml',
        'treatment_text',
        'general_note',
        'recorded_by',
        'recorded_by_id',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    public function getByIpd(int $ipdId, int $limit = 100): array
    {
        return $this->builder()
            ->where('ipd_id', $ipdId)
            ->orderBy('recorded_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getByIpdPeriod(int $ipdId, string $startDateTime, string $endDateTime, string $nurse = ''): array
    {
        $builder = $this->builder()
            ->where('ipd_id', $ipdId)
            ->where('recorded_at >=', $startDateTime)
            ->where('recorded_at <=', $endDateTime)
            ->orderBy('recorded_at', 'ASC')
            ->orderBy('id', 'ASC');

        if ($nurse !== '') {
            $builder->where('recorded_by', $nurse);
        }

        return $builder->get()->getResultArray();
    }
}
