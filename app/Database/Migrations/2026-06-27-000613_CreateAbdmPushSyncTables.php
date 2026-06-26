<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmPushSyncTables extends Migration
{
    public function up(): void
    {
        $this->createSyncPatientTable();
        $this->createSyncRecordTable();
        $this->createSyncOutboxTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_sync_outbox', true);
        $this->forge->dropTable('abdm_sync_record', true);
        $this->forge->dropTable('abdm_sync_patient', true);
    }

    private function createSyncPatientTable(): void
    {
        if ($this->db->tableExists('abdm_sync_patient')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'local_patient_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'abha_id' => [
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => true,
            ],
            'abha_address' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'gender' => [
                'type' => 'VARCHAR',
                'constraint' => 16,
                'null' => true,
            ],
            'dob' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'mobile' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'source_updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'sync_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
                'null' => false,
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'retry_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
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
        $this->forge->addUniqueKey('local_patient_id', 'uniq_abdm_sync_patient_local_patient_id');
        $this->forge->addKey('sync_status');
        $this->forge->addKey('source_updated_at');
        $this->forge->createTable('abdm_sync_patient', true);
    }

    private function createSyncRecordTable(): void
    {
        if ($this->db->tableExists('abdm_sync_record')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'local_record_id' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'local_patient_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'hi_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'care_context_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'care_context_display' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'visit_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'department' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'doctor_name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'fhir_bundle_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'consent_id' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'hfr_id' => [
                'type' => 'VARCHAR',
                'constraint' => 60,
                'null' => false,
            ],
            'source_updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'sync_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
                'null' => false,
            ],
            'gateway_record_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'gateway_queue_id' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'last_synced_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'retry_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
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
        $this->forge->addUniqueKey('local_record_id', 'uniq_abdm_sync_record_local_record_id');
        $this->forge->addUniqueKey('care_context_reference', 'uniq_abdm_sync_record_care_context_reference');
        $this->forge->addKey('sync_status');
        $this->forge->addKey(['local_patient_id', 'hi_type'], false, false, 'idx_abdm_sync_record_patient_type');
        $this->forge->createTable('abdm_sync_record', true);
    }

    private function createSyncOutboxTable(): void
    {
        if ($this->db->tableExists('abdm_sync_outbox')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'entity_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
            ],
            'entity_id' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'idempotency_key' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => false,
            ],
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
                'null' => false,
            ],
            'next_retry_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'retry_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'worker_id' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
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
        $this->forge->addUniqueKey('idempotency_key', 'uniq_abdm_sync_outbox_idempotency_key');
        $this->forge->addKey(['status', 'next_retry_at'], false, false, 'idx_abdm_sync_outbox_status_retry');
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_abdm_sync_outbox_entity');
        $this->forge->createTable('abdm_sync_outbox', true);
    }
}
