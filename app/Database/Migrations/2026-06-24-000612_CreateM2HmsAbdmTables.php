<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateM2HmsAbdmTables extends Migration
{
    public function up(): void
    {
        $this->createPatientRecordsTable();
        $this->createConsentLogsTable();
        $this->createAuditLogsTable();
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('consent_logs', true);
        $this->forge->dropTable('patient_records', true);
    }

    private function createPatientRecordsTable(): void
    {
        if ($this->hasTable('patient_records')) {
            return;
        }

        $this->forge->addField([
            'record_id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'consent_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'record_type' => [
                'type'       => 'ENUM',
                'constraint' => ['OPD', 'IPD', 'LAB', 'DISCHARGE', 'MLC', 'OTHER'],
                'null'       => false,
            ],
            'fhir_resource' => [
                'type' => 'JSON',
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
            'expiry_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['ACTIVE', 'ARCHIVED'],
                'default'    => 'ACTIVE',
                'null'       => false,
            ],
        ]);

        $this->forge->addKey('record_id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('record_type');
        $this->forge->addKey('expiry_date');
        $this->forge->addKey('status');
        $this->forge->createTable('patient_records', true);
    }

    private function createConsentLogsTable(): void
    {
        if ($this->hasTable('consent_logs')) {
            return;
        }

        $this->forge->addField([
            'log_id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'consent_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'purpose' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['GRANTED', 'REVOKED', 'EXPIRED'],
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('log_id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('status');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('consent_logs', true);
    }

    private function createAuditLogsTable(): void
    {
        if ($this->hasTable('audit_logs')) {
            return;
        }

        $this->forge->addField([
            'audit_id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'patient_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'abha_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => false,
            ],
            'action' => [
                'type'       => 'ENUM',
                'constraint' => ['DISCOVERY', 'FETCH', 'SHARE'],
                'null'       => false,
            ],
            'consent_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'transaction_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'timestamp' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('audit_id', true);
        $this->forge->addKey('patient_id');
        $this->forge->addKey('abha_id');
        $this->forge->addKey('action');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('transaction_id');
        $this->forge->addKey('timestamp');
        $this->forge->createTable('audit_logs', true);
    }

    private function hasTable(string $table): bool
    {
        try {
            $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();
            return ! empty($row);
        } catch (\Throwable) {
            return false;
        }
    }
}
