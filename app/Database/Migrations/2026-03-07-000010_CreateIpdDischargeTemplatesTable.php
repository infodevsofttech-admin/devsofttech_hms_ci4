<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateIpdDischargeTemplatesTable extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if ($this->hasTable('ipd_discharge_templates')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'template_html' => [
                'type' => 'LONGTEXT',
                'null' => false,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->addKey('is_default');
        $this->forge->addKey('status');
        $this->forge->createTable('ipd_discharge_templates', true);
    }

    public function down()
    {
        $this->forge->dropTable('ipd_discharge_templates', true);
    }
}
