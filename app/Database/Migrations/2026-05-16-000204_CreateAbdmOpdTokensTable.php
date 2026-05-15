<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmOpdTokensTable extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('abdm_opd_tokens')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // Gateway's own token id (unique per date)
            'gateway_token_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'token_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'queue_date' => [
                'type' => 'DATE',
            ],
            'patient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'abha_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'abha_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'gender' => [
                'type'       => 'VARCHAR',
                'constraint' => 1,
                'null'       => true,
            ],
            'dob' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 15,
                'null'       => true,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => 'General OPD',
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'manual',
                // scan_share | manual
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['PENDING', 'CALLED', 'COMPLETED', 'CANCELLED'],
                'default'    => 'PENDING',
            ],
            // HMS links — filled after processing
            'patient_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'opd_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'processed_at' => [
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
        $this->forge->addUniqueKey(['gateway_token_id', 'queue_date'], 'uq_gateway_token_date');
        $this->forge->addKey('queue_date');
        $this->forge->addKey('status');
        $this->forge->addKey('patient_id');
        $this->forge->addKey('source');

        $this->forge->createTable('abdm_opd_tokens');
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_opd_tokens', true);
    }
}
