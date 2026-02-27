<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class ItemModel extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::connect();
    }

    public function getItemTypes(): array
    {
        $sql = "SELECT *, (CASE is_ipd_opd WHEN 0 THEN 'OPD-IPD' WHEN 1 THEN 'OPD' WHEN 2 THEN 'IPD' END) AS isIPD_OPD FROM hc_item_type ORDER BY group_desc";

        return $this->db->query($sql)->getResult();
    }

    public function getItemTypesList(): array
    {
        return $this->db->table('hc_item_type')
            ->orderBy('group_desc', 'ASC')
            ->get()
            ->getResult();
    }

    public function getItemTypeById(int $id): array
    {
        $sql = "SELECT *, (CASE is_ipd_opd WHEN 0 THEN 'OPD-IPD' WHEN 1 THEN 'OPD' WHEN 2 THEN 'IPD' END) AS isIPD_OPD FROM hc_item_type WHERE itype_id = ? ORDER BY group_desc";

        return $this->db->query($sql, [$id])->getResult();
    }

    public function getItemsByType(int $typeId): array
    {
        $builder = $this->db->table('hc_items m');
        $builder->select([
            'm.id',
            'm.itype',
            'm.idesc',
            'm.idesc_detail',
            'm.amount',
            't.itype_id',
            't.is_ipd_opd',
            't.group_desc',
            "CASE t.is_ipd_opd WHEN 0 THEN 'OPD-IPD' WHEN 1 THEN 'OPD' WHEN 2 THEN 'IPD' END AS isIPD_OPD",
        ]);
        $builder->join('hc_item_type t', 'm.itype = t.itype_id', 'inner');
        $builder->where('m.itype', $typeId);

        return $builder->get()->getResult();
    }

    public function getItemById(int $id): array
    {
        $builder = $this->db->table('hc_items m');
        $builder->select([
            'm.id',
            'm.itype',
            'm.idesc',
            'm.idesc_detail',
            'm.amount',
            't.itype_id',
            't.is_ipd_opd',
            't.group_desc',
            "CASE t.is_ipd_opd WHEN 0 THEN 'OPD-IPD' WHEN 1 THEN 'OPD' WHEN 2 THEN 'IPD' END AS isIPD_OPD",
        ]);
        $builder->join('hc_item_type t', 'm.itype = t.itype_id', 'inner');
        $builder->where('m.id', $id);

        return $builder->get()->getResult();
    }

    public function getInsuranceItemList(int $itemId): array
    {
        $builder = $this->db->table('hc_items_insurance i');
        $builder->select([
            'i.id AS i_item_id',
            'i.hc_items_id AS c_item_id',
            'i.amount1 AS i_amount',
            'i.code',
            'c.ins_company_name',
        ]);
        $builder->join('hc_insurance c', 'c.id = i.hc_insurance_id', 'left');
        $builder->where('i.hc_items_id', $itemId);
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

    public function getItemsByTypeWithInsurance(int $typeId, int $insuranceId): array
    {
        $builder = $this->db->table('hc_items m');
        $builder->select([
            'm.id',
            'm.itype',
            'm.idesc',
            'm.idesc_detail',
            'm.amount',
            't.itype_id',
            't.is_ipd_opd',
            't.group_desc',
            "CASE t.is_ipd_opd WHEN 0 THEN 'OPD-IPD' WHEN 1 THEN 'OPD' WHEN 2 THEN 'IPD' END AS isIPD_OPD",
            'c.ins_company_name',
            'i.amount1 AS i_amount',
            'i.code',
            'COALESCE(i.amount1, m.amount) AS display_amount',
        ]);
        $builder->join('hc_item_type t', 'm.itype = t.itype_id', 'inner');
        $builder->join(
            'hc_items_insurance i',
            'm.id = i.hc_items_id AND i.isdelete = 0 AND i.hc_insurance_id = ' . (int) $insuranceId,
            'left'
        );
        $builder->join('hc_insurance c', 'c.id = i.hc_insurance_id', 'left');
        $builder->where('m.itype', $typeId);

        return $builder->get()->getResult();
    }

    public function insertItem(array $data): int
    {
        $this->db->table('hc_items')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updateItem(array $data, int $id): bool
    {
        $this->insertItemAudit($id, 1);

        return (bool) $this->db->table('hc_items')
            ->where('id', $id)
            ->update($data);
    }

    public function insertItemType(array $data): int
    {
        $this->db->table('hc_item_type')->insert($data);

        return (int) $this->db->insertID();
    }

    public function updateItemType(array $data, int $id): bool
    {
        return (bool) $this->db->table('hc_item_type')
            ->where('itype_id', $id)
            ->update($data);
    }

    public function insertItemInsurance(array $data): int
    {
        $this->db->table('hc_items_insurance')->insert($data);

        return (int) $this->db->insertID();
    }

    public function deleteItemInsurance(int $id): bool
    {
        $this->insertItemInsuranceAudit($id, 2);

        return (bool) $this->db->table('hc_items_insurance')
            ->where('id', $id)
            ->delete();
    }

    protected function insertItemAudit(int $id, int $action): void
    {
        $item = $this->db->table('hc_items')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($item)) {
            return;
        }

        $item['action'] = $action;
        $item['action_by'] = $this->getActionBy();

        $this->db->table('hc_items_update')->insert($item);
    }

    protected function insertItemInsuranceAudit(int $id, int $action): void
    {
        $item = $this->db->table('hc_items_insurance')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        if (empty($item)) {
            return;
        }

        $item['action'] = $action;
        $item['action_by'] = $this->getActionBy();

        $this->db->table('hc_items_insurance_update')->insert($item);
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
