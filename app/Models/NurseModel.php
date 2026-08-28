<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class NurseModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
        $this->ensureNurseMasterTable();
    }

    public function ensureNurseMasterTable(): bool
    {
        if (! $this->db->tableExists('nurse_master')) {
            $sql = "CREATE TABLE IF NOT EXISTS nurse_master (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nurse_code VARCHAR(50) NOT NULL,
                name VARCHAR(255) NOT NULL,
                hpr_id VARCHAR(100) NULL,
                registration_no VARCHAR(100) NULL,
                gender VARCHAR(20) NULL,
                designation VARCHAR(100) NULL,
                qualification VARCHAR(255) NULL,
                contact_no VARCHAR(50) NULL,
                email VARCHAR(100) NULL,
                department VARCHAR(100) DEFAULT 'Nursing',
                is_active TINYINT DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                INDEX idx_nurse_code (nurse_code),
                INDEX idx_hpr_id (hpr_id),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->db->query($sql);
        }

        $fields = $this->db->getFieldNames('nurse_master') ?? [];
        if (! in_array('hpr_id', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN hpr_id VARCHAR(100) NULL AFTER name");
        }
        if (! in_array('registration_no', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN registration_no VARCHAR(100) NULL AFTER hpr_id");
        }
        if (! in_array('gender', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN gender VARCHAR(20) NULL AFTER registration_no");
        }
        if (! in_array('designation', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN designation VARCHAR(100) NULL AFTER gender");
        }
        if (! in_array('app_pin', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN app_pin VARCHAR(255) NULL AFTER email");
        }

        return true;
    }

    public function getNurses(string $q = ''): array
    {
        $builder = $this->db->table('nurse_master');
        if ($q !== '') {
            $builder->groupStart()
                ->like('name', $q)
                ->orLike('nurse_code', $q)
                ->orLike('hpr_id', $q)
                ->orLike('registration_no', $q)
                ->orLike('qualification', $q)
                ->orLike('contact_no', $q)
                ->groupEnd();
        }
        return $builder->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function getActiveNurses(): array
    {
        return $this->db->table('nurse_master')
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getNurseById(int $id): ?array
    {
        $row = $this->db->table('nurse_master')->where('id', $id)->get()->getRowArray();
        return $row ?: null;
    }

    public function insertNurse(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('nurse_master')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateNurse(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return (bool) $this->db->table('nurse_master')->where('id', $id)->update($data);
    }

    public function deleteNurse(int $id): bool
    {
        return (bool) $this->db->table('nurse_master')->where('id', $id)->delete();
    }
}
