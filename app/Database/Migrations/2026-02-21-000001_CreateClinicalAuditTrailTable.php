<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateClinicalAuditTrailTable extends Migration
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
            'module' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'record_id' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'field_name' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'old_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'new_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => false,
            ],
            'action_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
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
        $this->forge->addKey(['module', 'record_id']);
        $this->forge->addKey('action_at');
        $this->forge->addUniqueKey('hash');
        $this->forge->createTable('clinical_audit_trail', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('clinical_audit_trail', true);
    }
}
