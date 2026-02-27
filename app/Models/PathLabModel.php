<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class PathLabModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function updateReport(array $data, int $id): bool
    {
        return (bool) $this->db->table('lab_repo')->where('mstRepoKey', $id)->update($data);
    }

    public function insertReport(array $data): int
    {
        $this->db->table('lab_repo')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateUltrasoundReport(array $data, int $id): bool
    {
        return (bool) $this->db->table('radiology_ultrasound_template')->where('id', $id)->update($data);
    }

    public function insertUltrasoundReport(array $data): int
    {
        $this->db->table('radiology_ultrasound_template')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateItemParameter(array $data, int $id): bool
    {
        return (bool) $this->db->table('lab_tests')->where('mstTestKey', $id)->update($data);
    }

    public function insertItemParameter(array $data): int
    {
        $this->db->table('lab_tests')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateItemParameterOption(array $data, int $id): bool
    {
        return (bool) $this->db->table('lab_tests_option')->where('id', $id)->update($data);
    }

    public function insertItemParameterOption(array $data): int
    {
        $this->db->table('lab_tests_option')->insert($data);
        return (int) $this->db->insertID();
    }

    public function insertItemSortorder(array $data): int
    {
        $this->db->table('lab_repotests')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateItemSortorder(array $data, int $id): bool
    {
        return (bool) $this->db->table('lab_repotests')->where('id', $id)->update($data);
    }
}
