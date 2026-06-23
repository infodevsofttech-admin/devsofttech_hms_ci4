<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmLocationMasters extends Migration
{
    public function up(): void
    {
        $this->createStateMaster();
        $this->createDistrictMaster();
    }

    public function down(): void
    {
        if ($this->hasTable('abdm_district_master')) {
            $this->forge->dropTable('abdm_district_master', true);
        }

        if ($this->hasTable('abdm_state_master')) {
            $this->forge->dropTable('abdm_state_master', true);
        }
    }

    private function createStateMaster(): void
    {
        if ($this->hasTable('abdm_state_master')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'state_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'state_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
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
        $this->forge->addUniqueKey('state_code');
        $this->forge->addKey('state_name');
        $this->forge->createTable('abdm_state_master', true);
    }

    private function createDistrictMaster(): void
    {
        if ($this->hasTable('abdm_district_master')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'district_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'district_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => false,
            ],
            'state_code' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
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
        $this->forge->addUniqueKey(['district_code', 'state_code']);
        $this->forge->addKey('district_name');
        $this->forge->addKey('state_code');
        $this->forge->createTable('abdm_district_master', true);
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
