<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLabAiExtractionTables extends Migration
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
            'invoice_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'lab_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'file_upload_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'panel_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'ai_provider' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'azure-openai',
            ],
            'model_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'ocr_text' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'raw_response_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'completed',
            ],
            'doctor_verified' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'verified_by' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'verified_at' => [
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
        $this->forge->addKey(['invoice_id', 'lab_type']);
        $this->forge->addKey('file_upload_id');
        $this->forge->addKey('doctor_verified');
        $this->forge->createTable('lab_ai_extraction_batches', true);

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'batch_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'null' => false,
            ],
            'test_name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => false,
            ],
            'test_value' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'unit' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'reference_range' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'abnormal_flag' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'raw_line' => [
                'type' => 'TEXT',
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
        $this->forge->addKey('batch_id');
        $this->forge->addKey('test_name');
        $this->forge->createTable('lab_ai_extraction_values', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('lab_ai_extraction_values', true);
        $this->forge->dropTable('lab_ai_extraction_batches', true);
    }
}
