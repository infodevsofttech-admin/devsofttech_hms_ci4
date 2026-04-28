<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserSettingsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
                'null' => false,
            ],
            'setting_value' => [
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'setting_key'], 'uk_user_setting');
        $this->forge->addKey('user_id');
        $this->forge->addKey('setting_key');

        $this->forge->createTable('user_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_settings', true);
    }
}
