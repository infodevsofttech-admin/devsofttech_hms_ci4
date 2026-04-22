<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCashSubmissionFieldsToPaymentHistory extends Migration
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
        if (! $this->tableExists('payment_history')) {
            return;
        }

        if (! $this->fieldExists('payment_history', 'cash_submission_status')) {
            $this->forge->addColumn('payment_history', [
                'cash_submission_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                    'default' => 'open',
                    'after' => 'payment_mode',
                ],
            ]);
        }

        if (! $this->fieldExists('payment_history', 'cash_submission_scroll_id')) {
            $this->forge->addColumn('payment_history', [
                'cash_submission_scroll_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'cash_submission_status',
                ],
            ]);
        }

        if (! $this->fieldExists('payment_history', 'cash_submission_updated_at')) {
            $this->forge->addColumn('payment_history', [
                'cash_submission_updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'cash_submission_scroll_id',
                ],
            ]);
        }

        $this->db->query('UPDATE payment_history SET cash_submission_status = "open" WHERE cash_submission_status IS NULL OR cash_submission_status = ""');
    }

    public function down()
    {
        if (! $this->tableExists('payment_history')) {
            return;
        }

        if ($this->fieldExists('payment_history', 'cash_submission_updated_at')) {
            $this->forge->dropColumn('payment_history', 'cash_submission_updated_at');
        }

        if ($this->fieldExists('payment_history', 'cash_submission_scroll_id')) {
            $this->forge->dropColumn('payment_history', 'cash_submission_scroll_id');
        }

        if ($this->fieldExists('payment_history', 'cash_submission_status')) {
            $this->forge->dropColumn('payment_history', 'cash_submission_status');
        }
    }
}
