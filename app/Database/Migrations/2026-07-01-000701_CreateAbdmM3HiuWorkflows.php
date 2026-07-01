<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmM3HiuWorkflows extends Migration
{
    public function up(): void
    {
        if ($this->db->tableExists('abdm_hiu_workflows')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'hospital_id' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'hfr_id' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
            ],
            'abha_address' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'consent_id' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'request_id' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 160,
                'null' => true,
            ],
            'operation' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
                'comment' => 'consent_request|consent_status|consent_fetch|hi_request',
            ],
            'workflow_state' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'default' => 'CREATED',
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'created',
            ],
            'http_code' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'is_retryable' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'retry_count' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'next_retry_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'request_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'response_json' => [
                'type' => 'LONGTEXT',
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
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expired_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['request_id', 'operation'], 'uniq_abdm_hiu_req_op');
        $this->forge->addUniqueKey(['transaction_id', 'operation'], 'uniq_abdm_hiu_txn_op');
        $this->forge->addKey(['hfr_id', 'created_at'], false, false, 'idx_abdm_hiu_hfr_created');
        $this->forge->addKey(['consent_id', 'created_at'], false, false, 'idx_abdm_hiu_consent_created');
        $this->forge->addKey(['status', 'next_retry_at'], false, false, 'idx_abdm_hiu_retry');
        $this->forge->addKey(['workflow_state', 'created_at'], false, false, 'idx_abdm_hiu_state_created');
        $this->forge->createTable('abdm_hiu_workflows', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_hiu_workflows', true);
    }
}
