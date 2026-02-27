<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class InsuranceModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getAll(): array
    {
        return $this->db->table('hc_insurance')
            ->select("*, IF(active=1,'Active','Inactive') as activestatus")
            ->where('id >', 1)
            ->get()
            ->getResult();
    }

    public function getById(int $id): array
    {
        return $this->db->table('hc_insurance')
            ->where('id', $id)
            ->get()
            ->getResult();
    }

    public function insert(array $data): int
    {
        $this->db->table('hc_insurance')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updateInsurance(array $data, int $id): bool
    {
        return (bool) $this->db->table('hc_insurance')->where('id', $id)->update($data);
    }
}
