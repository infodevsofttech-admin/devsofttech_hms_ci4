<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpdPerformanceIndexes extends Migration
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
        if ($this->tableExists('opd_master')) {
            if ($this->fieldExists('opd_master', 'apointment_date')
                && $this->fieldExists('opd_master', 'doc_id')
                && $this->fieldExists('opd_master', 'opd_status')) {
                $this->addIndexIfMissing('opd_master', 'idx_opd_master_date_doc_status', '`apointment_date`,`doc_id`,`opd_status`');
            }

            if ($this->fieldExists('opd_master', 'doc_id')
                && $this->fieldExists('opd_master', 'apointment_date')) {
                $this->addIndexIfMissing('opd_master', 'idx_opd_master_doc_date', '`doc_id`,`apointment_date`');
            }

            if ($this->fieldExists('opd_master', 'opd_id')) {
                $this->addIndexIfMissing('opd_master', 'idx_opd_master_opd_id', '`opd_id`');
            }
        }

        if ($this->tableExists('opd_prescription')) {
            if ($this->fieldExists('opd_prescription', 'opd_id')) {
                $this->addIndexIfMissing('opd_prescription', 'idx_opd_prescription_opd_id', '`opd_id`');
            }

            if ($this->fieldExists('opd_prescription', 'opd_id') && $this->fieldExists('opd_prescription', 'queue_no')) {
                $this->addIndexIfMissing('opd_prescription', 'idx_opd_prescription_opd_queue', '`opd_id`,`queue_no`');
            }
        }
    }

    public function down(): void
    {
        $drops = [
            'opd_master' => [
                'idx_opd_master_date_doc_status',
                'idx_opd_master_doc_date',
                'idx_opd_master_opd_id',
            ],
            'opd_prescription' => [
                'idx_opd_prescription_opd_id',
                'idx_opd_prescription_opd_queue',
            ],
        ];

        foreach ($drops as $table => $indexes) {
            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($indexes as $index) {
                if ($this->indexExists($table, $index)) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
                }
            }
        }
    }
}
