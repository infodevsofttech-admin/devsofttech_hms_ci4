<?php

namespace App\Models;

use CodeIgniter\Model;

class FinanceDoctorAgreementModel extends Model
{
    protected $table = 'finance_doctor_agreements';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'doctor_code',
        'doctor_name',
        'specialization',
        'consultation_rate',
        'surgery_rate',
        'agreement_start_date',
        'agreement_end_date',
        'status',
        'created_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
