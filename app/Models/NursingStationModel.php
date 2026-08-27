<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class NursingStationModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
        $this->ensureNursingStationTable();
    }

    public function ensureNursingStationTable(): bool
    {
        if (! $this->db->tableExists('nursing_station_master')) {
            $sql = "CREATE TABLE IF NOT EXISTS nursing_station_master (
                id INT AUTO_INCREMENT PRIMARY KEY,
                station_code VARCHAR(50) NOT NULL,
                station_name VARCHAR(255) NOT NULL,
                floor_number VARCHAR(50) NULL,
                incharge_nurse_id INT NULL,
                incharge_nurse_name VARCHAR(255) NULL,
                contact_no VARCHAR(50) NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                remarks TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                INDEX idx_station_code (station_code),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $this->db->query($sql);
        }

        if ($this->db->tableExists('ward_master')) {
            $fields = $this->db->getFieldNames('ward_master') ?? [];
            if (! in_array('nursing_station_id', $fields, true)) {
                $this->db->query("ALTER TABLE ward_master ADD COLUMN nursing_station_id INT NULL AFTER department_id");
            }
        }

        return true;
    }

    public function getStations(string $q = ''): array
    {
        $builder = $this->db->table('nursing_station_master');
        if ($q !== '') {
            $builder->groupStart()
                ->like('station_name', $q)
                ->orLike('station_code', $q)
                ->orLike('floor_number', $q)
                ->orLike('incharge_nurse_name', $q)
                ->groupEnd();
        }
        return $builder->orderBy('station_name', 'ASC')->get()->getResultArray();
    }

    public function getActiveStations(): array
    {
        return $this->db->table('nursing_station_master')
            ->where('status', 'active')
            ->orderBy('station_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getStationById(int $id): ?array
    {
        $row = $this->db->table('nursing_station_master')->where('id', $id)->get()->getRowArray();
        return $row ?: null;
    }

    public function insertStation(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->table('nursing_station_master')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateStation(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return (bool) $this->db->table('nursing_station_master')->where('id', $id)->update($data);
    }

    public function deleteStation(int $id): bool
    {
        return (bool) $this->db->table('nursing_station_master')->where('id', $id)->delete();
    }
}
