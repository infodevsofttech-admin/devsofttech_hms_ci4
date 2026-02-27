<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpdFhirDocumentTable extends Migration
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
            'opd_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'opd_session_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'bundle_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'bundle_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'generated_by' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'generated_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('opd_id');
        $this->forge->addKey('opd_session_id');
        $this->forge->addKey('bundle_type');
        $this->forge->createTable('opd_fhir_documents', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('opd_fhir_documents', true);
    }
}
