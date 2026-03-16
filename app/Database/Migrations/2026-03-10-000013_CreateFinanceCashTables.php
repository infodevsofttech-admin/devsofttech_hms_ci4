<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceCashTables extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if (! $this->hasTable('finance_cash_transactions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'txn_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'txn_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'flow_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'department' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'reference_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'null' => true,
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'mode' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'party_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => true,
                ],
                'narration' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'flag_269st' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'flag_40a3' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'is_compliance_hold' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'compliance_note' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'created_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
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
            $this->forge->addKey(['txn_date', 'txn_type']);
            $this->forge->addKey('department');
            $this->forge->addKey('is_compliance_hold');
            $this->forge->createTable('finance_cash_transactions', true);
        }

        if (! $this->hasTable('finance_scroll_submissions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'scroll_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'department' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'total_receipts' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'submitted_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'variance_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'reconciliation_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pending',
                ],
                'submitted_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'remarks' => [
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
            $this->forge->addKey('id', true);
            $this->forge->addKey(['scroll_date', 'department']);
            $this->forge->addKey('reconciliation_status');
            $this->forge->createTable('finance_scroll_submissions', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_scroll_submissions', true);
        $this->forge->dropTable('finance_cash_transactions', true);
    }
}
