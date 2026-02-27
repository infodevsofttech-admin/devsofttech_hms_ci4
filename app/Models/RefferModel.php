<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class RefferModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function getAll(): array
    {
        return $this->db->table('refer_master r')
            ->select("r.*, DATE_FORMAT(r.date_of_add, '%d-%m-%Y') as str_dateadd, t.type_desc")
            ->join('refer_type t', 't.id = r.refer_type', 'left')
            ->orderBy('r.f_name')
            ->get()
            ->getResult();
    }

    public function getById(int $id): array
    {
        return $this->db->table('refer_master')
            ->where('id', $id)
            ->get()
            ->getResult();
    }

    public function getTypes(): array
    {
        return $this->db->table('refer_type')->get()->getResult();
    }

    public function insertType(string $name): int
    {
        $this->db->table('refer_type')->insert(['type_desc' => $name]);

        return (int) $this->db->insertID();
    }

    public function updateType(int $id, string $name): bool
    {
        return (bool) $this->db->table('refer_type')
            ->where('id', $id)
            ->update(['type_desc' => $name]);
    }

    public function deleteType(int $id): bool
    {
        return (bool) $this->db->table('refer_type')->where('id', $id)->delete();
    }

    public function insert(array $data): int
    {
        $this->db->table('refer_master')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updateReffer(array $data, int $id): bool
    {
        return (bool) $this->db->table('refer_master')->where('id', $id)->update($data);
    }

    public function updateStatus(int $id, int $active): bool
    {
        return (bool) $this->db->table('refer_master')
            ->where('id', $id)
            ->update(['active' => $active]);
    }
}
