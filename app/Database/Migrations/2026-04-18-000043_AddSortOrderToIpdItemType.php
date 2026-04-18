<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSortOrderToIpdItemType extends Migration
{
    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getNumRows() > 0;
    }

    private function fieldExists(string $table, string $field): bool
    {
        return $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($field))->getNumRows() > 0;
    }

    public function up()
    {
        if (! $this->tableExists('ipd_item_type')) {
            return;
        }

        if (! $this->fieldExists('ipd_item_type', 'sort_order')) {
            $this->forge->addColumn('ipd_item_type', [
                'sort_order' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => false,
                    'after' => 'group_desc',
                ],
            ]);
        }

        $rows = $this->db->table('ipd_item_type')
            ->select('itype_id')
            ->orderBy('group_desc', 'ASC')
            ->get()
            ->getResult();

        $sortOrder = 1;
        foreach ($rows as $row) {
            $this->db->table('ipd_item_type')
                ->where('itype_id', (int) ($row->itype_id ?? 0))
                ->update(['sort_order' => $sortOrder]);
            $sortOrder++;
        }
    }

    public function down()
    {
        if ($this->tableExists('ipd_item_type') && $this->fieldExists('ipd_item_type', 'sort_order')) {
            $this->forge->dropColumn('ipd_item_type', 'sort_order');
        }
    }
}