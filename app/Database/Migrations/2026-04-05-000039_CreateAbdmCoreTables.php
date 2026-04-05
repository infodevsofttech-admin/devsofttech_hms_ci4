<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmCoreTables extends Migration
{
    public function up(): void
    {
        $this->createAbdmApiLogsTable();
        $this->createAbdmConsentRecordsTable();
        $this->createAbdmWorkTasksTable();
        $this->createNhcxClaimDocumentsTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('nhcx_claim_documents', true);
        $this->forge->dropTable('abdm_work_tasks', true);
        $this->forge->dropTable('abdm_consent_records', true);
        $this->forge->dropTable('abdm_api_logs', true);
    }

    private function createAbdmApiLogsTable(): void
    {
        if ($this->hasTable('abdm_api_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'channel' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'bridge',
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'endpoint' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'http_method' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'entity_id' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'request_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'response_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'response_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('channel');
        $this->forge->addKey('status');
        $this->forge->addKey('event_type');
        $this->forge->createTable('abdm_api_logs', true);
    }

    private function createAbdmConsentRecordsTable(): void
    {
        if ($this->hasTable('abdm_consent_records')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'abha_id' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'consent_handle' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'consent_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'requested',
            ],
            'purpose_code' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'granted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'raw_payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('consent_handle');
        $this->forge->addKey('consent_status');
        $this->forge->createTable('abdm_consent_records', true);
    }

    private function createNhcxClaimDocumentsTable(): void
    {
        if ($this->hasTable('nhcx_claim_documents')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'ipd_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'case_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'patient_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'claim_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'default' => 'institutional',
            ],
            'claim_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'claim_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'default' => 'draft',
            ],
            'external_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pushed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('ipd_id');
        $this->forge->addKey('case_id');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('claim_status');
        $this->forge->createTable('nhcx_claim_documents', true);
    }

    private function createAbdmWorkTasksTable(): void
    {
        if ($this->hasTable('abdm_work_tasks')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'task_code' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
            ],
            'task_type' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'source_module' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'entity_id' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'patient_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'patient_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'abha_id' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => true,
            ],
            'action_mode' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'priority' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'normal',
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'last_action_result' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('task_code');
        $this->forge->addKey('task_type');
        $this->forge->addKey('status');
        $this->forge->addKey('patient_id');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('abdm_work_tasks', true);
    }

    private function hasTable(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();
        return ! empty($row);
    }
}
