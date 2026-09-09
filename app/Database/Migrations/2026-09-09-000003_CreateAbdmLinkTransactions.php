<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmLinkTransactions extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('abdm_link_transactions')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'txn_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'patient_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'patient_ref' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'null'       => true,
            ],
            'abha_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'otp' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => false,
            ],
            'care_contexts' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'INITIATED',
                'null'       => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('txn_id');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('abha_address');
        $this->forge->addKey('status');
        $this->forge->createTable('abdm_link_transactions', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_link_transactions', true);
    }
}
