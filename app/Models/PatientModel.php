<?php

namespace App\Models;

use CodeIgniter\Model;

class PatientModel extends Model
{
    protected $table = 'patient_master';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    public function insertPatient(array $data): int
    {
        $builder = $this->db->table($this->table);
        if (!$builder->insert($data)) {
            return 0;
        }

        $insertId = (int) $this->db->insertID();
        $pid = 1000000 + $insertId;
        $pid = 'P' . date('ym') . $pid;

        $builder->where('id', $insertId)->update(['p_code' => $pid]);

        return $insertId;
    }

    public function updatePatient(array $data, int $oldId): void
    {
        $user = auth()->user();
        $userLabel = $user ? ($user->username ?? $user->email ?? 'User') : 'User';
        $userId = $user->id ?? '';
        $updateEmpName = $userId . '[' . $userLabel . ']' . date('Y-m-d H:i:s');

        $existing = $this->db->table($this->table)
            ->where('id', $oldId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $oldLog = $existing['log'] ?? '';
            $oldLog = $oldLog === '' ? ' ' : $oldLog;

            $changeData = $this->buildChangeLog($existing, $data);
            if ($changeData !== '') {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $updateEmpName;
                $data['last_update'] = date('Y-m-d H:i:s');
            }
        }

        $this->db->table($this->table)
            ->where('id', $oldId)
            ->update($data);
    }

    public function updatePatientOnline(array $data, int $oldId): void
    {
        $userId = 0;
        $userLabel = 'online OPD';
        $updateEmpName = $userId . '[' . $userLabel . ']' . date('Y-m-d H:i:s');

        $existing = $this->db->table($this->table)
            ->where('id', $oldId)
            ->get()
            ->getRowArray();

        if ($existing) {
            $oldLog = $existing['log'] ?? '';
            $oldLog = $oldLog === '' ? ' ' : $oldLog;

            $changeData = $this->buildChangeLog($existing, $data);
            if ($changeData !== '') {
                $data['log'] = $oldLog . PHP_EOL . $changeData . 'Update By :' . $updateEmpName;
                $data['last_update'] = date('Y-m-d H:i:s');
            }
        }

        $this->db->table($this->table)
            ->where('id', $oldId)
            ->update($data);
    }

    public function insertCard(array $data): int
    {
        $builder = $this->db->table('hc_insurance_card');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    public function insertDuplicateLog(array $data): int
    {
        $builder = $this->db->table('patient_duplicate_log');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    public function updateCard(array $data, int $oldId): void
    {
        $this->db->table('hc_insurance_card')
            ->where('id', $oldId)
            ->update($data);
    }

    public function getCitySuggestions(string $q): array
    {
        $rows = $this->db->table('city_auto_u')
            ->like('city', $q)
            ->get()
            ->getResultArray();

        $rowSet = [];
        foreach ($rows as $row) {
            $rowSet[] = [
                'label' => trim($row['city']) . ' | ' . trim($row['district']) . ' | ' . trim($row['state']),
                'value' => trim($row['city']),
                'l_city' => trim($row['city']),
                'l_district' => trim($row['district']),
                'l_state' => trim($row['state']),
            ];
        }

        return $rowSet;
    }

    public function insertRemark(array $data): int
    {
        $builder = $this->db->table('patient_remark');
        if (!$builder->insert($data)) {
            return 0;
        }

        return (int) $this->db->insertID();
    }

    private function buildChangeLog(array $old, array $new): string
    {
        $lines = [];
        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old)) {
                continue;
            }

            $oldValue = (string) $old[$key];
            $newValue = (string) $value;
            if ($oldValue === $newValue) {
                continue;
            }

            $lines[] = $key . ': ' . $oldValue . ' => ' . $newValue;
        }

        return $lines ? implode(PHP_EOL, $lines) . PHP_EOL : '';
    }
}
