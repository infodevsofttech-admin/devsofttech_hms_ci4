<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFinanceInvoiceMatchColumns extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        $row = $this->db->query(
            'SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($column)
        )->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        $table = 'finance_vendor_invoices';
        if (! $this->hasTable($table)) {
            return;
        }

        if (! $this->hasColumn($table, 'match_status')) {
            $this->forge->addColumn($table, [
                'match_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'not_checked',
                    'after' => 'payment_status',
                ],
            ]);
        }

        if (! $this->hasColumn($table, 'variance_amount')) {
            $this->forge->addColumn($table, [
                'variance_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                    'after' => 'match_status',
                ],
            ]);
        }

        if (! $this->hasColumn($table, 'match_note')) {
            $this->forge->addColumn($table, [
                'match_note' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'variance_amount',
                ],
            ]);
        }

        if (! $this->hasColumn($table, 'is_compliance_hold')) {
            $this->forge->addColumn($table, [
                'is_compliance_hold' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'match_note',
                ],
            ]);
        }

        if (! $this->hasColumn($table, 'match_checked_at')) {
            $this->forge->addColumn($table, [
                'match_checked_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'is_compliance_hold',
                ],
            ]);
        }

        $this->db->query("UPDATE {$table} SET match_status = 'not_checked' WHERE match_status IS NULL OR match_status = ''");
    }

    public function down()
    {
        $table = 'finance_vendor_invoices';
        if (! $this->hasTable($table)) {
            return;
        }

        foreach (['match_checked_at', 'is_compliance_hold', 'match_note', 'variance_amount', 'match_status'] as $column) {
            if ($this->hasColumn($table, $column)) {
                $this->forge->dropColumn($table, $column);
            }
        }
    }
}
