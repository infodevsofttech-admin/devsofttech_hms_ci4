<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceHospitalStockItems extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('hsm_items')) {
            return;
        }

        $columns = $this->getColumns('hsm_items');
        $fields = [];

        if (! in_array('item_type', $columns, true)) {
            $fields['item_type'] = ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'category_id'];
        }
        if (! in_array('purchase_uom', $columns, true)) {
            $fields['purchase_uom'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'uom'];
        }
        if (! in_array('issue_uom', $columns, true)) {
            $fields['issue_uom'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'purchase_uom'];
        }
        if (! in_array('issue_per_purchase', $columns, true)) {
            $fields['issue_per_purchase'] = ['type' => 'DECIMAL', 'constraint' => '14,4', 'default' => 1, 'after' => 'issue_uom'];
        }
        if (! in_array('is_daily_use', $columns, true)) {
            $fields['is_daily_use'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'issue_per_purchase'];
        }
        if (! in_array('store_location', $columns, true)) {
            $fields['store_location'] = ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'is_daily_use'];
        }

        if ($fields !== []) {
            $this->forge->addColumn('hsm_items', $fields);
        }

        // Backfill sensible defaults for unit conversion.
        $this->db->query("UPDATE hsm_items SET purchase_uom = IFNULL(NULLIF(purchase_uom,''), IFNULL(NULLIF(uom,''), 'Unit'))");
        $this->db->query("UPDATE hsm_items SET issue_uom = IFNULL(NULLIF(issue_uom,''), IFNULL(NULLIF(uom,''), 'Unit'))");
        $this->db->query("UPDATE hsm_items SET issue_per_purchase = IFNULL(NULLIF(issue_per_purchase,0), 1)");
    }

    public function down()
    {
        if (! $this->db->tableExists('hsm_items')) {
            return;
        }

        foreach (['store_location', 'is_daily_use', 'issue_per_purchase', 'issue_uom', 'purchase_uom', 'item_type'] as $column) {
            if (in_array($column, $this->getColumns('hsm_items'), true)) {
                $this->forge->dropColumn('hsm_items', $column);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getColumns(string $table): array
    {
        $result = $this->db->query('SHOW COLUMNS FROM ' . $this->db->protectIdentifiers($table))->getResultArray();

        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $result);
    }
}
