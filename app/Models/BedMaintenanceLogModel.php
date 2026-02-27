<?php

namespace App\Models;

use CodeIgniter\Model;

class BedMaintenanceLogModel extends Model
{
    protected $table = 'bed_maintenance_log';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'bed_id',
        'maintenance_type',
        'scheduled_date',
        'completed_date',
        'performed_by',
        'issue_description',
        'action_taken',
        'next_maintenance_date',
        'cost',
        'status',
        'created_by',
    ];

    public function getAllWithRelations(): array
    {
        return $this->select('bed_maintenance_log.*, bed_master.bed_code, bed_master.bed_number, ward_master.ward_name')
            ->join('bed_master', 'bed_master.id = bed_maintenance_log.bed_id', 'left')
            ->join('ward_master', 'ward_master.id = bed_master.ward_id', 'left')
            ->orderBy('bed_maintenance_log.id', 'DESC')
            ->findAll();
    }
}
