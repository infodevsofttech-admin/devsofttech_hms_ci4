<?php

namespace App\Models;

use CodeIgniter\Model;

class BedAssignmentHistoryModel extends Model
{
    protected $table = 'bed_assignment_history';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'ipd_id',
        'bed_id',
        'assignment_type',
        'assigned_date',
        'discharged_date',
        'assigned_by',
        'transfer_reason',
        'transfer_from_bed_id',
        'remarks',
        'ward_id',
        'released_date',
        'total_days',
        'release_reason',
    ];

    public function getAllWithRelations(): array
    {
        return $this->select('bed_assignment_history.*, bed_master.bed_code, bed_master.bed_number, ward_master.ward_name')
            ->join('bed_master', 'bed_master.id = bed_assignment_history.bed_id', 'left')
            ->join('ward_master', 'ward_master.id = bed_assignment_history.ward_id', 'left')
            ->orderBy('bed_assignment_history.id', 'DESC')
            ->get()
            ->getResult();
    }

    public function getByIpd(int $ipdId): array
    {
        $results = $this->select('bed_assignment_history.*, bed_master.bed_code, bed_master.bed_number, ward_master.ward_name')
            ->join('bed_master', 'bed_master.id = bed_assignment_history.bed_id', 'left')
            ->join('ward_master', 'ward_master.id = bed_assignment_history.ward_id', 'left')
            ->where('bed_assignment_history.ipd_id', $ipdId)
            ->orderBy('bed_assignment_history.id', 'DESC')
            ->get()
            ->getResult();

        // Calculate duration for each record
        foreach ($results as $record) {
            $record->duration_hours = $this->calculateDurationHours($record->assigned_date, $record->released_date);
        }

        return $results;
    }

    /**
     * Calculate duration in hours between two dates
     */
    private function calculateDurationHours(?string $startDate, ?string $endDate): ?float
    {
        if (empty($startDate)) {
            return null;
        }

        $start = new \DateTime($startDate);
        $end = $endDate ? new \DateTime($endDate) : new \DateTime();
        
        $interval = $start->diff($end);
        $hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
        
        return round($hours, 1);
    }
}
