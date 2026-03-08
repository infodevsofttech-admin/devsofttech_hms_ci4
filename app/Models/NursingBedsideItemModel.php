<?php

namespace App\Models;

use CodeIgniter\Model;

class NursingBedsideItemModel extends Model
{
    protected $table = 'nursing_bedside_items';
    protected $primaryKey = 'item_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'item_code',
        'item_name',
        'item_type',
        'category',
        'default_rate',
        'unit',
        'description',
        'is_active',
        'is_billable',
        'created_by',
        'updated_by',
    ];
    protected $useTimestamps = false;

    public function getBillableGroupedByCategory(int $insuranceId = 0): array
    {
        $builder = $this->db->table('nursing_bedside_items b')
            ->select('b.item_id, b.item_code, b.item_name, b.item_type, b.category, b.default_rate, b.unit')
            ->where('b.is_active', 1)
            ->where('b.is_billable', 1)
            ->orderBy('b.category', 'ASC')
            ->orderBy('b.item_name', 'ASC');

        if ($insuranceId > 0 && $this->insuranceTableExists()) {
            $builder->select('i.amount1 as insurance_rate, i.code as insurance_code');
            $builder->join(
                'nursing_bedside_items_insurance i',
                'i.bedside_item_id = b.item_id and i.hc_insurance_id = ' . (int) $insuranceId . ' and i.isdelete = 0',
                'left'
            );
        }

        $rows = $builder->get()->getResultArray();

        foreach ($rows as &$row) {
            if (isset($row['insurance_rate']) && $row['insurance_rate'] !== null) {
                $row['default_rate'] = (float) $row['insurance_rate'];
            }
            if (! isset($row['insurance_code'])) {
                $row['insurance_code'] = null;
            }
        }
        unset($row);

        $grouped = [];
        foreach ($rows as $row) {
            $category = trim((string) ($row['category'] ?? ''));
            if ($category === '') {
                $category = 'General';
            }
            if (! isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $row;
        }

        return $grouped;
    }

    public function getMasterList(): array
    {
        return $this->builder()
            ->select('*')
            ->orderBy('category', 'ASC')
            ->orderBy('item_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getInsuranceList(): array
    {
        return $this->db->table('hc_insurance')
            ->select('id, ins_company_name')
            ->where('id >', 1)
            ->orderBy('ins_company_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getInsuranceRatesForItem(int $itemId): array
    {
        if (! $this->insuranceTableExists()) {
            return [];
        }

        $latestSubQuery = $this->db->table('nursing_bedside_items_insurance')
            ->select('MAX(id) AS id')
            ->where('bedside_item_id', $itemId)
            ->where('isdelete', 0)
            ->groupBy('hc_insurance_id')
            ->getCompiledSelect();

        return $this->db->table('nursing_bedside_items_insurance i')
            ->select('i.id, i.bedside_item_id, i.hc_insurance_id, i.amount1, i.code, c.ins_company_name')
            ->join('hc_insurance c', 'c.id = i.hc_insurance_id', 'left')
            ->where('i.id IN (' . $latestSubQuery . ')', null, false)
            ->orderBy('c.ins_company_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function upsertInsuranceRate(int $itemId, int $insuranceId, float $amount, string $code): bool
    {
        if (! $this->insuranceTableExists()) {
            return false;
        }

        $existing = $this->db->table('nursing_bedside_items_insurance')
            ->where('bedside_item_id', $itemId)
            ->where('hc_insurance_id', $insuranceId)
            ->where('isdelete', 0)
            ->get()
            ->getRowArray();

        if (! empty($existing)) {
            return (bool) $this->db->table('nursing_bedside_items_insurance')
                ->where('id', (int) $existing['id'])
                ->update([
                    'amount1' => $amount,
                    'code' => $code,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return (bool) $this->db->table('nursing_bedside_items_insurance')->insert([
            'bedside_item_id' => $itemId,
            'hc_insurance_id' => $insuranceId,
            'amount1' => $amount,
            'code' => $code,
            'isdelete' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function removeInsuranceRate(int $mappingId): bool
    {
        if (! $this->insuranceTableExists()) {
            return false;
        }

        return (bool) $this->db->table('nursing_bedside_items_insurance')
            ->where('id', $mappingId)
            ->update([
                'isdelete' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getBillableItemForInsurance(int $itemId, int $insuranceId): ?array
    {
        $builder = $this->db->table('nursing_bedside_items b');
        $builder->select([
            'b.item_id',
            'b.item_code',
            'b.item_name',
            'b.item_type',
            'b.category',
            'b.default_rate',
            'b.unit',
            'b.is_active',
            'b.is_billable',
        ]);
        if ($insuranceId > 0 && $this->insuranceTableExists()) {
            $builder->select('i.amount1 as insurance_rate, i.code as insurance_code');
            $builder->join(
                'nursing_bedside_items_insurance i',
                'i.bedside_item_id = b.item_id and i.hc_insurance_id = ' . (int) $insuranceId . ' and i.isdelete = 0',
                'left'
            );
        } else {
            $builder->select('NULL as insurance_rate, NULL as insurance_code', false);
        }
        $builder->where('b.item_id', $itemId);
        $builder->where('b.is_active', 1);
        $builder->where('b.is_billable', 1);

        $row = $builder->get()->getRowArray();

        return empty($row) ? null : $row;
    }

    private function insuranceTableExists(): bool
    {
        return $this->db->tableExists('nursing_bedside_items_insurance');
    }
}
