<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePolicySettingModel extends Model
{
    protected $table = 'finance_policy_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'description',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
