<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBankReconciliationAuditTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'payment_history_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => false,
            ],
            'action_type' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => false,
                'default' => 'matched',
            ],
            'old_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'new_status' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
            ],
            'batch_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'remarks' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'action_by' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'action_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('payment_history_id');
        $this->forge->addKey('action_type');
        $this->forge->addKey('batch_ref');
        $this->forge->createTable('finance_bank_reconciliation_audit', true);

        $this->forge->addColumn('payment_history', [
            'bank_reconcile_batch_ref' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'after' => 'bank_statement_entry_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('finance_bank_reconciliation_audit', true);
        $this->forge->dropColumn('payment_history', 'bank_reconcile_batch_ref');
    }
}
