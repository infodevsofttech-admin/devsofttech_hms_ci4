<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates three tables for ABDM health-record push lifecycle:
 *
 *  health_records  — centralised FHIR payload store (AES-256-GCM encrypted)
 *  record_links    — ABDM transaction / care-context tracking
 *  abdm_audit_logs — immutable audit trail (who pushed what, when, outcome)
 */
class CreateHealthRecordsAndAuditTables extends Migration
{
    public function up(): void
    {
        $this->createHealthRecordsTable();
        $this->createRecordLinksTable();
        $this->createAbdmAuditLogsTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_audit_logs', true);
        $this->forge->dropTable('record_links', true);
        $this->forge->dropTable('health_records', true);
    }

    // -------------------------------------------------------------------------

    private function createHealthRecordsTable(): void
    {
        if ($this->db->tableExists('health_records')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            // ABDM M3 HI type: OPConsultRecord | PrescriptionRecord |
            // DiagnosticReport | DischargeSummary | WellnessRecord |
            // ImmunizationRecord | HealthDocumentRecord | InvoiceRecord
            'hi_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            // Source module: opd | lab | ipd | wellness | document
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            // Primary key of the source record
            'entity_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            // AES-256-GCM encrypted FHIR R4 bundle JSON (base64 IV+TAG+ciphertext)
            'fhir_bundle_enc' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            // Optional path to scanned / uploaded attachment (relative to FCPATH)
            'attachment_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            // ABDM gateway queue_id returned after push
            'abdm_txn_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            // pending | queued | pushed | failed | linked
            'push_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'push_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'linked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // ABDM care-context reference returned on link callback
            'care_context_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            // Consent handle used when this record was pushed
            'consent_handle' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'created_by_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_by_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
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
        $this->forge->addKey('hi_type');
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_hr_entity');
        $this->forge->addKey('push_status');
        $this->forge->createTable('health_records', true);
    }

    private function createRecordLinksTable(): void
    {
        if ($this->db->tableExists('record_links')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // FK to health_records.id
            'health_record_id' => [
                'type'     => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null'     => true,
            ],
            // Queue/transaction ID from ABDM gateway
            'abdm_txn_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            // ABDM care-context reference
            'care_context_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            // pending | linked | failed
            'link_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            // Full JSON response from ABDM link callback
            'response_json' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'linked_at' => [
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
        $this->forge->addKey('health_record_id');
        $this->forge->addKey('abdm_txn_id');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('link_status');
        $this->forge->createTable('record_links', true);
    }

    private function createAbdmAuditLogsTable(): void
    {
        if ($this->db->tableExists('abdm_audit_logs')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'actor_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'actor_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            // push_record | link_record | consent_request | consent_grant |
            // consent_revoke | record_linked | consent_callback | abha_validate
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 60,
                'null'       => false,
            ],
            // opd | lab | ipd | wellness | consent
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
            ],
            'entity_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            // Sanitised request parameters (no PII tokens, no secrets)
            'request_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            // ABDM gateway response
            'response_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            // Requester IP (IPv4 or IPv6)
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            // success | failure
            'outcome' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'success',
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
        $this->forge->addKey('actor_user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('outcome');
        $this->forge->createTable('abdm_audit_logs', true);
    }
}
