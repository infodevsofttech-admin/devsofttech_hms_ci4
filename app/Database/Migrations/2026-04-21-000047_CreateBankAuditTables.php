<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBankAuditTables extends Migration
{
    public function up(): void
    {
        // Table 1: Bank statement entries for direct transaction reconciliation
        // (UPI direct, NEFT, RTGS – each line visible individually in the bank statement)
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'entry_date'            => ['type' => 'DATE', 'null' => false],
            'reference_no'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'narration'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'amount'                => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'transaction_type'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'credit'],
            'matched_payment_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'reconciliation_status'=> ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'unmatched'],
            'matched_by'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'matched_at'           => ['type' => 'DATETIME', 'null' => true],
            'remarks'              => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_by'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => true],
            'updated_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('entry_date');
        $this->forge->addKey('reconciliation_status');
        $this->forge->createTable('finance_bank_statement_entries', true);

        // Table 2: POS terminal daily settlement records
        // (POS transactions don't appear individually in bank; the end-of-day settlement total does)
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'settlement_date'  => ['type' => 'DATE', 'null' => false],
            'terminal_id'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'terminal_name'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'settlement_amount'=> ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'system_total'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'variance'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => '0.00'],
            'payment_count'    => ['type' => 'INT', 'null' => false, 'default' => 0],
            'status'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'pending'],
            'reconciled_by'    => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'reconciled_at'    => ['type' => 'DATETIME', 'null' => true],
            'remarks'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_by'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('settlement_date');
        $this->forge->addKey(['settlement_date', 'terminal_id']);
        $this->forge->createTable('finance_bank_pos_settlements', true);

        // Add reconciliation tracking fields to payment_history
        $this->forge->addColumn('payment_history', [
            'bank_reconcile_status'     => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'default'    => null,
                'after'      => 'cash_submission_updated_at',
            ],
            'bank_statement_entry_id'   => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'bank_reconcile_status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('finance_bank_statement_entries', true);
        $this->forge->dropTable('finance_bank_pos_settlements', true);
        $this->forge->dropColumn('payment_history', 'bank_reconcile_status');
        $this->forge->dropColumn('payment_history', 'bank_statement_entry_id');
    }
}
