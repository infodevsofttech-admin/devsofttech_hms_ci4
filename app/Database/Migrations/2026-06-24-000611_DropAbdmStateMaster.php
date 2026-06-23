<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Drop abdm_state_master — superseded by the existing india_state table
 * which already has all 38 states/UTs and is used as the authoritative
 * reference for the patient registration State dropdown.
 */
class DropAbdmStateMaster extends Migration
{
    public function up(): void
    {
        $row = $this->db->query("SHOW TABLES LIKE 'abdm_state_master'")->getRowArray();
        if (! empty($row)) {
            $this->forge->dropTable('abdm_state_master', true);
        }
    }

    public function down(): void
    {
        // Recreate the table if rolled back (data is not restored)
        if (! empty($this->db->query("SHOW TABLES LIKE 'abdm_state_master'")->getRowArray())) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'state_code' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'state_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('state_code');
        $this->forge->createTable('abdm_state_master', true);
    }
}
