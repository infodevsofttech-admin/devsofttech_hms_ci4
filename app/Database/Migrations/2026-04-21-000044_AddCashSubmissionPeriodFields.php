<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashSubmissionPeriodFields extends Migration
{
    private function tableExists(string $table): bool
    {
        return $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getNumRows() > 0;
    }

    private function fieldExists(string $table, string $field): bool
    {
        return $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($field))->getNumRows() > 0;
    }

    public function up()
    {
        $table = 'finance_scroll_submissions';
        if (! $this->tableExists($table)) {
            return;
        }

        if (! $this->fieldExists($table, 'start_datetime')) {
            $this->forge->addColumn($table, [
                'start_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'scroll_date',
                ],
            ]);
        }

        if (! $this->fieldExists($table, 'end_datetime')) {
            $this->forge->addColumn($table, [
                'end_datetime' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'start_datetime',
                ],
            ]);
        }

        if (! $this->fieldExists($table, 'collected_by')) {
            $this->forge->addColumn($table, [
                'collected_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                    'after' => 'department',
                ],
            ]);
        }

        if (! $this->fieldExists($table, 'payment_count')) {
            $this->forge->addColumn($table, [
                'payment_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0,
                    'null' => false,
                    'after' => 'submitted_amount',
                ],
            ]);
        }
    }

    public function down()
    {
        $table = 'finance_scroll_submissions';
        if (! $this->tableExists($table)) {
            return;
        }

        if ($this->fieldExists($table, 'payment_count')) {
            $this->forge->dropColumn($table, 'payment_count');
        }
        if ($this->fieldExists($table, 'collected_by')) {
            $this->forge->dropColumn($table, 'collected_by');
        }
        if ($this->fieldExists($table, 'end_datetime')) {
            $this->forge->dropColumn($table, 'end_datetime');
        }
        if ($this->fieldExists($table, 'start_datetime')) {
            $this->forge->dropColumn($table, 'start_datetime');
        }
    }
}
