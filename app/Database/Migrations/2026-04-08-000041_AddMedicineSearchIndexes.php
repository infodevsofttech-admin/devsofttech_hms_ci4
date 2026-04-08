<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMedicineSearchIndexes extends Migration
{
    private function tableExists(string $table): bool
    {
        $rows = $this->db->query('SHOW TABLES LIKE ?', [$table])->getResultArray();

        return ! empty($rows);
    }

    private function fieldExists(string $table, string $field): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        $rows = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$field])->getResultArray();

        return ! empty($rows);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        $rows = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])->getResultArray();

        return ! empty($rows);
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columns): void
    {
        if (! $this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (' . $columns . ')');
    }

    public function up(): void
    {
        if (! $this->tableExists('opd_med_master')) {
            return;
        }

        if ($this->fieldExists('opd_med_master', 'item_name')) {
            $this->addIndexIfMissing('opd_med_master', 'idx_opd_med_master_item_name', '`item_name`');
        }

        if ($this->fieldExists('opd_med_master', 'formulation')) {
            $this->addIndexIfMissing('opd_med_master', 'idx_opd_med_master_formulation', '`formulation`');
        }

        if ($this->fieldExists('opd_med_master', 'item_name') && $this->fieldExists('opd_med_master', 'formulation')) {
            $this->addIndexIfMissing('opd_med_master', 'idx_opd_med_master_item_form', '`item_name`,`formulation`');
        }
    }

    public function down(): void
    {
        if (! $this->tableExists('opd_med_master')) {
            return;
        }

        $indexes = [
            'idx_opd_med_master_item_name',
            'idx_opd_med_master_formulation',
            'idx_opd_med_master_item_form',
        ];

        foreach ($indexes as $index) {
            if ($this->indexExists('opd_med_master', $index)) {
                $this->db->query('ALTER TABLE `opd_med_master` DROP INDEX `' . $index . '`');
            }
        }
    }
}
