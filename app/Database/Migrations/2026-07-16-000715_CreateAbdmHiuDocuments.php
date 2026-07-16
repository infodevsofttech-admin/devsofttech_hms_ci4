<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmHiuDocuments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'workflow_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => true,
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'consent_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 220,
                'null' => true,
            ],
            'consent_artifact_id' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'abha_address' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
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
                'constraint' => 190,
                'null' => true,
            ],
            'care_context_reference' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'media' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'bundle_id' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'bundle_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'document_title' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'document_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'practitioner_name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'organization_name' => [
                'type' => 'VARCHAR',
                'constraint' => 190,
                'null' => true,
            ],
            'summary_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'raw_bundle' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'doc_hash' => [
                'type' => 'CHAR',
                'constraint' => 40,
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
        $this->forge->addUniqueKey('doc_hash', 'uniq_abdm_hiu_doc_hash');
        $this->forge->addKey(['patient_id', 'document_date'], false, false, 'idx_abdm_hiu_doc_patient_date');
        $this->forge->addKey(['abha_address', 'document_date'], false, false, 'idx_abdm_hiu_doc_abha_date');
        $this->forge->addKey(['request_id', 'created_at'], false, false, 'idx_abdm_hiu_doc_request');
        $this->forge->createTable('abdm_hiu_documents', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_hiu_documents', true);
    }
}
