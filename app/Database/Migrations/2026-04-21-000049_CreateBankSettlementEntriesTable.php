<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBankSettlementEntriesTable extends Migration
{
    public function up()
    {
        if (! $this->hasTable('finance_bank_settlement_entries')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'settlement_ref' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'settlement_date' => [
                    'type' => 'DATE',
                ],
                'payment_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => '0.00',
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('settlement_ref');
            $this->forge->addKey('settlement_date');
            $this->forge->createTable('finance_bank_settlement_entries', true);
        }

        if ($this->hasTable('payment_history') && ! $this->hasField('payment_history', 'bank_settlement_entry_id')) {
            $this->forge->addColumn('payment_history', [
                'bank_settlement_entry_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'bank_reconcile_batch_ref',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->hasTable('payment_history') && $this->hasField('payment_history', 'bank_settlement_entry_id')) {
            $this->forge->dropColumn('payment_history', 'bank_settlement_entry_id');
        }

        if ($this->hasTable('finance_bank_settlement_entries')) {
            $this->forge->dropTable('finance_bank_settlement_entries', true);
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $result = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();
            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasField(string $table, string $field): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        try {
            $sql = 'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $this->db->escape($field);
            $result = $this->db->query($sql)->getResultArray();
            return ! empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
