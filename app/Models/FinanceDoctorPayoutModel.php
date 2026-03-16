<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceDoctorPayoutModel extends Model
{
    protected $table = 'finance_doctor_payouts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'payout_date',
        'doctor_id',
        'case_reference',
        'payout_type',
        'units',
        'rate',
        'calculated_amount',
        'approved_amount',
        'status',
        'remarks',
        'hr_submitted_by',
        'finance_approved_by',
        'finance_approved_at',
        'ceo_approved_by',
        'ceo_approved_at',
        'paid_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
