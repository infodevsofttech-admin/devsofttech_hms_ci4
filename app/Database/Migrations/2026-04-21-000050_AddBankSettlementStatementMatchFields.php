<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBankSettlementStatementMatchFields extends Migration
{
    public function up(): void
    {
        if (! $this->hasTable('finance_bank_settlement_entries')) {
            return;
        }

        if (! $this->hasField('finance_bank_settlement_entries', 'reconciliation_status')) {
            $this->forge->addColumn('finance_bank_settlement_entries', [
                'reconciliation_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => false,
                    'default'    => 'unmatched',
                    'after'      => 'total_amount',
                ],
            ]);
        }

        if (! $this->hasField('finance_bank_settlement_entries', 'statement_matched_by')) {
            $this->forge->addColumn('finance_bank_settlement_entries', [
                'statement_matched_by' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'reconciliation_status',
                ],
            ]);
        }

        if (! $this->hasField('finance_bank_settlement_entries', 'statement_matched_at')) {
            $this->forge->addColumn('finance_bank_settlement_entries', [
                'statement_matched_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'statement_matched_by',
                ],
            ]);
        }

        if (! $this->hasField('finance_bank_settlement_entries', 'statement_match_remarks')) {
            $this->forge->addColumn('finance_bank_settlement_entries', [
                'statement_match_remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'statement_matched_at',
                ],
            ]);
        }

        $this->db->query("UPDATE finance_bank_settlement_entries SET reconciliation_status = 'unmatched' WHERE reconciliation_status IS NULL OR reconciliation_status = ''");
    }

    public function down(): void
    {
        if (! $this->hasTable('finance_bank_settlement_entries')) {
            return;
        }

        if ($this->hasField('finance_bank_settlement_entries', 'statement_match_remarks')) {
            $this->forge->dropColumn('finance_bank_settlement_entries', 'statement_match_remarks');
        }
        if ($this->hasField('finance_bank_settlement_entries', 'statement_matched_at')) {
            $this->forge->dropColumn('finance_bank_settlement_entries', 'statement_matched_at');
        }
        if ($this->hasField('finance_bank_settlement_entries', 'statement_matched_by')) {
            $this->forge->dropColumn('finance_bank_settlement_entries', 'statement_matched_by');
        }
        if ($this->hasField('finance_bank_settlement_entries', 'reconciliation_status')) {
            $this->forge->dropColumn('finance_bank_settlement_entries', 'reconciliation_status');
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $rows = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();
            return ! empty($rows);
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
            $rows = $this->db->query($sql)->getResultArray();
            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
