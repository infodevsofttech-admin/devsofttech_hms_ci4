<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationCaseModel extends Model
{
    protected $table = 'organization_case_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    public function insertCase(array $data): int
    {
        if (! $this->db->table($this->table)->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $pid = str_pad(substr((string) $insertId, -7, 7), 7, '0', STR_PAD_LEFT);
        $caseCode = 'C' . date('ym') . $pid;

        $this->db->table($this->table)
            ->where('id', $insertId)
            ->update(['case_id_code' => $caseCode]);

        return $insertId;
    }

    public function updateCase(array $data, int $id): bool
    {
        return (bool) $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }
}
