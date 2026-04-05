<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbdmWorkTasksTable extends Migration
{
    public function up(): void
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

    public function down(): void
    {
        $this->forge->dropTable('abdm_work_tasks', true);
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
