<?php

namespace App\Models;

use CodeIgniter\Model;

class FinancePoDocumentModel extends Model
{
    protected $table = 'finance_po_documents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'po_id',
        'file_name',
        'file_path',
        'uploaded_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';
}
