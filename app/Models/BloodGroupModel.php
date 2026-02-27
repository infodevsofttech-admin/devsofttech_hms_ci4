<?php

namespace App\Models;

use CodeIgniter\Model;

class BloodGroupModel extends Model
{
    protected $table = 'blood_group';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
}
