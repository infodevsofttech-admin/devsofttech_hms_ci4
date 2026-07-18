<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBridgeMetadataToImmunizationMaster extends Migration
{
    public function up(): void
    {
        $this->addScheduleColumns();
        $this->createSyncLogTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('immunization_master_sync_log', true);

        if (! $this->tableExists('immunization_schedule_master')) {
            return;
        }

        foreach (array_keys($this->scheduleColumns()) as $column) {
            if ($this->columnExists('immunization_schedule_master', $column)) {
                $this->forge->dropColumn('immunization_schedule_master', $column);
            }
        }
    }

    private function addScheduleColumns(): void
    {
        if (! $this->tableExists('immunization_schedule_master')) {
            return;
        }

        foreach ($this->scheduleColumns() as $column => $definition) {
            if ($this->columnExists('immunization_schedule_master', $column)) {
                continue;
            }
            $this->forge->addColumn('immunization_schedule_master', [$column => $definition]);
        }
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function scheduleColumns(): array
    {
        return [
            'schedule_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'beneficiary_category' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'gender_applicability' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'min_age_days' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'max_age_days' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'gateway_schedule_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_state_specific' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'state_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'district_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'applicability_note' => ['type' => 'TEXT', 'null' => true],
            'source_version_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'source_checksum' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'source_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'source_document_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'effective_from' => ['type' => 'DATE', 'null' => true],
        ];
    }

    private function createSyncLogTable(): void
    {
        if ($this->tableExists('immunization_master_sync_log')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'master_type' => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'UIP_SCHEDULE'],
            'version_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'effective_from' => ['type' => 'DATE', 'null' => true],
            'checksum' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            'source_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'source_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'source_document_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'success'],
            'item_count' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'message' => ['type' => 'TEXT', 'null' => true],
            'synced_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('master_type');
        $this->forge->addKey('version_code');
        $this->forge->addKey('checksum');
        $this->forge->createTable('immunization_master_sync_log', true);
    }

    private function tableExists(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ?', [$table])->getRowArray();
        return ! empty($row);
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ?', [$column])->getRowArray();
        return ! empty($row);
    }
}