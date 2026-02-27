<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBridgeSyncQueueTable extends Migration
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
            'channel' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'bridge',
            ],
            'event_type' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
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
            'payload_json' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'payload_hash' => [
                'type' => 'CHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
                'null' => false,
            ],
            'attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
                'null' => false,
            ],
            'max_attempts' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 10,
                'null' => false,
            ],
            'next_attempt_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'locked_by' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
            ],
            'sent_at' => [
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
        $this->forge->addKey('status');
        $this->forge->addKey('next_attempt_at');
        $this->forge->addKey('payload_hash');
        $this->forge->addKey(['channel', 'event_type']);
        $this->forge->createTable('bridge_sync_queue', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('bridge_sync_queue', true);
    }
}
