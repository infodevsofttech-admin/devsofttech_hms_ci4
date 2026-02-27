<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class PackageModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect();
    }

    public function getPackageGroups(): array
    {
        return $this->db->table('package_group')
            ->orderBy('pakage_group_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function getPackageGroupById(int $id): array
    {
        return $this->db->table('package_group')
            ->where('pak_id', $id)
            ->get()
            ->getResult();
    }

    public function insertPackageGroup(array $data): int
    {
        $this->db->table('package_group')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updatePackageGroup(array $data, int $id): bool
    {
        return (bool) $this->db->table('package_group')
            ->where('pak_id', $id)
            ->update($data);
    }

    public function getPackagesByGroup(int $groupId): array
    {
        return $this->db->table('package')
            ->where('pakage_group_id', $groupId)
            ->get()
            ->getResult();
    }

    public function getPackageById(int $id): array
    {
        return $this->db->table('package')
            ->where('id', $id)
            ->get()
            ->getResult();
    }

    public function getPackagesByGroupWithInsurance(int $groupId, int $insuranceId): array
    {
        $builder = $this->db->table('package p');
        $builder->select([
            'p.id',
            'p.pakage_group_id',
            'p.ipd_pakage_name',
            'p.Pakage_description',
            'p.Pakage_Min_Amount',
            'g.pakage_group_name',
            'c.ins_company_name',
            'i.i_amount',
            'i.code',
            'COALESCE(i.i_amount, p.Pakage_Min_Amount) AS display_amount',
        ]);
        $builder->join('package_group g', 'p.pakage_group_id = g.pak_id', 'left');
        $builder->join(
            'package_insurance i',
            'p.id = i.hc_items_id AND i.isdelete = 0 AND i.hc_insurance_id = ' . (int) $insuranceId,
            'left'
        );
        $builder->join('hc_insurance c', 'c.id = i.hc_insurance_id', 'left');
        $builder->where('p.pakage_group_id', $groupId);

        return $builder->get()->getResult();
    }

    public function getPackageInsuranceList(int $packageId): array
    {
        $builder = $this->db->table('package_insurance i');
        $builder->select([
            'i.id AS i_item_id',
            'i.hc_items_id AS c_item_id',
            'i.i_amount',
            'i.code',
            'c.ins_company_name',
        ]);
        $builder->join('hc_insurance c', 'c.id = i.hc_insurance_id', 'left');
        $builder->where('i.hc_items_id', $packageId);
        $builder->where('i.isdelete', 0);

        return $builder->get()->getResult();
    }

    public function getInsuranceList(): array
    {
        return $this->db->table('hc_insurance')
            ->where('id >', 1)
            ->orderBy('ins_company_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function getInsuranceById(int $id): ?object
    {
        return $this->db->table('hc_insurance')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    public function insertPackage(array $data): int
    {
        $this->db->table('package')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updatePackage(array $data, int $id): bool
    {
        $this->insertPackageAudit($id, 1);

        return (bool) $this->db->table('package')
            ->where('id', $id)
            ->update($data);
    }

    public function insertPackageInsurance(array $data): int
    {
        $this->db->table('package_insurance')->insert($data);

        return (int) $this->db->insertID();
    }

    public function deletePackageInsurance(int $id): bool
    {
        $this->insertPackageInsuranceAudit($id, 2);

        return (bool) $this->db->table('package_insurance')
            ->where('id', $id)
            ->delete();
    }

    protected function insertPackageAudit(int $id, int $action): void
    {
        if (! $this->db->tableExists('package_update')) {
            return;
        }

        $item = $this->db->table('package')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($item)) {
            return;
        }

        $item['action'] = $action;
        $item['action_by'] = $this->getActionBy();

        $this->db->table('package_update')->insert($item);
    }

    protected function insertPackageInsuranceAudit(int $id, int $action): void
    {
        if (! $this->db->tableExists('package_insurance_update')) {
            return;
        }

        $item = $this->db->table('package_insurance')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($item)) {
            return;
        }

        $item['action'] = $action;
        $item['action_by'] = $this->getActionBy();

        $this->db->table('package_insurance_update')->insert($item);
    }

    protected function getActionBy(): string
    {
        $user = function_exists('auth') ? auth()->user() : null;
        if ($user === null) {
            return 'System';
        }

        $userId = $user->id ?? '';
        $userName = $user->username ?? $user->email ?? 'User';

        return trim($userName . '[' . $userId . ']');
    }
}
