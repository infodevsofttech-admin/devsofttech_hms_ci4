<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserActiveSessionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'session_token' => ['type' => 'CHAR', 'constraint' => 64],
            'session_id_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'login_at' => ['type' => 'DATETIME'],
            'last_activity' => ['type' => 'DATETIME'],
            'logout_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'revoked_reason' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'revoked_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('session_token', 'uk_user_active_session_token');
        $this->forge->addKey(['user_id', 'is_active'], false, false, 'idx_user_active_sessions_user');
        $this->forge->addKey(['is_active', 'last_activity'], false, false, 'idx_user_active_sessions_online');
        $this->forge->createTable('user_active_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_active_sessions', true);
    }
}