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
        if (! in_array('device_code', $fields, true)) {
            $this->db->query("ALTER TABLE nurse_master ADD COLUMN device_code VARCHAR(50) NULL AFTER app_pin");
        }

        return true;
    }

    public function getNurses(string $q = ''): array
    {
        $builder = $this->db->table('nurse_master n');
        if ($this->db->tableExists('user_profiles')) {
            $builder->select('n.*, u.id as linked_user_id, u.username as linked_username, u.active as linked_user_active')
                ->join('user_profiles up', 'up.nurse_id = n.id', 'left')
                ->join('users u', 'u.id = up.user_id AND u.deleted_at IS NULL', 'left');
        } else {
            $builder->select('n.*');
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('n.name', $q)
                ->orLike('n.nurse_code', $q)
                ->orLike('n.hpr_id', $q)
                ->orLike('n.registration_no', $q)
                ->orLike('n.qualification', $q)
                ->orLike('n.contact_no', $q)
                ->groupEnd();
        }

        $list = $builder->orderBy('n.name', 'ASC')->get()->getResultArray();
        foreach ($list as &$nurse) {
            if (empty($nurse['device_code'])) {
                $newCode = (string) random_int(100000, 999999);
                $this->db->table('nurse_master')->where('id', $nurse['id'])->update(['device_code' => $newCode]);
                $nurse['device_code'] = $newCode;
            }
        }
        unset($nurse);

        return $list;
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
        if ($row && empty($row['device_code'])) {
            $newCode = (string) random_int(100000, 999999);
            $this->db->table('nurse_master')->where('id', $id)->update(['device_code' => $newCode]);
            $row['device_code'] = $newCode;
        }
        return $row ?: null;
    }

    public function insertNurse(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (empty($data['device_code'])) {
            $data['device_code'] = (string) random_int(100000, 999999);
        }
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
