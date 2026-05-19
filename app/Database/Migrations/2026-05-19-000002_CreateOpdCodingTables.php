<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpdCodingTables extends Migration
{
    public function up(): void
    {
        // Queue: one row per OPD session to process
        if (! $this->db->tableExists('opd_coding_queue')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'opd_id'         => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'opd_session_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'status'         => ['type' => 'ENUM', 'constraint' => ['pending', 'processing', 'done', 'failed'], 'default' => 'pending'],
                'has_suggestions'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'queued_at'      => ['type' => 'DATETIME', 'null' => true],
                'processed_at'   => ['type' => 'DATETIME', 'null' => true],
                'error_message'  => ['type' => 'TEXT', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey('opd_session_id');
            $this->forge->addKey(['status', 'queued_at']);
            $this->forge->createTable('opd_coding_queue');
        }

        // Suggestions: AI/SNOMED suggestions per phrase per session
        if (! $this->db->tableExists('opd_snomed_suggestions')) {
            $this->forge->addField([
                'id'                   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'opd_id'               => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'opd_session_id'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'source_field'         => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => ''],
                'source_phrase'        => ['type' => 'VARCHAR', 'constraint' => 500, 'default' => ''],
                'concept_id'           => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => ''],
                'snomed_term'          => ['type' => 'VARCHAR', 'constraint' => 500, 'default' => ''],
                'semantic_tag'         => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
                'confidence'           => ['type' => 'DECIMAL', 'constraint' => '5,3', 'default' => 0],
                'status'               => ['type' => 'ENUM', 'constraint' => ['pending_review', 'confirmed', 'rejected', 'corrected'], 'default' => 'pending_review'],
                'corrected_concept_id' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => ''],
                'corrected_term'       => ['type' => 'VARCHAR', 'constraint' => 500, 'default' => ''],
                'reviewed_by'          => ['type' => 'INT', 'null' => true],
                'reviewed_at'          => ['type' => 'DATETIME', 'null' => true],
                'created_at'           => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey(['opd_session_id', 'source_field']);
            $this->forge->addKey('status');
            $this->forge->createTable('opd_snomed_suggestions');
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('opd_snomed_suggestions', true);
        $this->forge->dropTable('opd_coding_queue', true);
    }
}
