<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;

class OpdMedicineModel
{
    private BaseConnection $db;
    private string $table = 'opd_med_master';

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    public function tableExists(): bool
    {
        return $this->db->tableExists($this->table);
    }

    public function normalizeMedicineName(string $name): string
    {
        $name = trim($name);

        // Collapse all whitespace to single space
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        $name = mb_strtolower($name);

        // Remove space between number and dosage unit: "8 mg" → "8mg", "10 mcg" → "10mcg"
        // Covers: mg, mcg, iu, ml, g, kg, mmol, meq, units, tab, cap, drops, inj, puff
        $name = preg_replace(
            '/(\d)\s*(mcg|mmol|meq|units|drops|puffs|inj|tab|cap|ml|mg|iu|kg|g)\b/i',
            '$1$2',
            $name
        ) ?? $name;

        // Strip trailing dosage unit+number suffixes and all-trailing units
        // e.g. "zerodol th max 8mg" → "zerodol th max 8mg" → kept as-is but normalized

        // Collapse any double-spaces introduced
        $name = preg_replace('/\s{2,}/', ' ', $name) ?? $name;

        return trim($name);
    }

    /**
     * Compute a fuzzy key used for duplicate grouping.
     * Removes all dosage numbers and units so that "8mg", "8 mg", "8" all produce
     * the same key for the same base name.
     */
    public function normalizeMedicineNameFuzzy(string $name): string
    {
        $name = $this->normalizeMedicineName($name);

        // Remove standalone numbers and dosage unit combos (e.g. "8mg", "10 mcg", "500")
        $name = preg_replace(
            '/\b\d+\.?\d*\s*(mcg|mmol|meq|units|drops|puffs|inj|tab|cap|ml|mg|iu|kg|g)?\b/i',
            ' ',
            $name
        ) ?? $name;

        // Remove punctuation
        $name = preg_replace('/[\/\-\(\)\[\]\.]+/', ' ', $name) ?? $name;

        $name = preg_replace('/\s{2,}/', ' ', $name) ?? $name;

        return trim($name);
    }

    public function findDuplicateByName(string $itemName, int $excludeId = 0): ?array
    {
        if (! $this->tableExists()) {
            return null;
        }

        $rows = $this->db->table($this->table)
            ->select('id,item_name,formulation')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $needle = $this->normalizeMedicineNameFuzzy($itemName);
        if ($needle === '') {
            return null;
        }

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0 || ($excludeId > 0 && $id === $excludeId)) {
                continue;
            }

            $current = $this->normalizeMedicineNameFuzzy((string) ($row['item_name'] ?? ''));
            if ($current !== '' && $current === $needle) {
                return $row;
            }
        }

        return null;
    }

    public function getDuplicateGroups(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        $rows = $this->db->table($this->table)
            ->select('id,item_name,formulation,genericname,company_name')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        $groups = [];
        foreach ($rows as $row) {
            $norm = $this->normalizeMedicineNameFuzzy((string) ($row['item_name'] ?? ''));
            if ($norm === '') {
                continue;
            }

            if (! isset($groups[$norm])) {
                $groups[$norm] = [
                    'normalized_name' => $norm,
                    'display_name' => (string) ($row['item_name'] ?? ''),
                    'rows' => [],
                ];
            }
            $groups[$norm]['rows'][] = $row;
        }

        $result = [];
        foreach ($groups as $group) {
            $items = $group['rows'];
            if (count($items) <= 1) {
                continue;
            }

            usort($items, static function (array $a, array $b): int {
                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            });

            $keep = $items[0];
            $mergeIds = [];
            $allIds = [];
            foreach ($items as $idx => $item) {
                $id = (int) ($item['id'] ?? 0);
                if ($id > 0) {
                    $allIds[] = $id;
                    if ($idx > 0) {
                        $mergeIds[] = $id;
                    }
                }
            }

            $result[] = [
                'normalized_name' => $group['normalized_name'],
                'display_name' => $group['display_name'],
                'count' => count($items),
                'keep_id' => (int) ($keep['id'] ?? 0),
                'all_ids' => $allIds,
                'merge_ids' => $mergeIds,
            ];
        }

        usort($result, static function (array $a, array $b): int {
            return strcmp((string) ($a['display_name'] ?? ''), (string) ($b['display_name'] ?? ''));
        });

        return $result;
    }

    public function mergeDuplicates(int $keepId, array $mergeIds): array
    {
        if (! $this->tableExists() || $keepId <= 0) {
            return ['ok' => false, 'error' => 'Medicine table not found'];
        }

        $mergeIds = array_values(array_unique(array_filter(array_map('intval', $mergeIds), static fn(int $id): bool => $id > 0 && $id !== $keepId)));
        if (empty($mergeIds)) {
            return ['ok' => false, 'error' => 'No duplicate ids provided'];
        }

        $keepRow = $this->db->table($this->table)->where('id', $keepId)->get(1)->getRowArray();
        if (empty($keepRow)) {
            return ['ok' => false, 'error' => 'Keep medicine not found'];
        }

        $dupRows = $this->db->table($this->table)->whereIn('id', $mergeIds)->get()->getResultArray();
        if (empty($dupRows)) {
            return ['ok' => false, 'error' => 'Duplicate medicines not found'];
        }

        $this->db->transStart();

        $patch = [];
        foreach (['formulation', 'genericname', 'company_name'] as $field) {
            $current = trim((string) ($keepRow[$field] ?? ''));
            if ($current !== '') {
                continue;
            }
            foreach ($dupRows as $dup) {
                $value = trim((string) ($dup[$field] ?? ''));
                if ($value !== '') {
                    $patch[$field] = $value;
                    break;
                }
            }
        }

        if (! empty($patch)) {
            $this->db->table($this->table)->where('id', $keepId)->update($patch);
        }

        foreach (['opd_prescrption_prescribed', 'opd_prescription_prescribed', 'opd_prescrption_prescribed_template'] as $refTable) {
            if (! $this->db->tableExists($refTable)) {
                continue;
            }
            $fields = $this->db->getFieldNames($refTable);
            if (! in_array('med_id', $fields, true)) {
                continue;
            }

            $this->db->table($refTable)
                ->whereIn('med_id', $mergeIds)
                ->set('med_id', $keepId)
                ->update();
        }

        $this->db->table($this->table)->whereIn('id', $mergeIds)->delete();

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            return ['ok' => false, 'error' => 'Failed to merge duplicates'];
        }

        return ['ok' => true, 'merged_count' => count($mergeIds)];
    }
}
