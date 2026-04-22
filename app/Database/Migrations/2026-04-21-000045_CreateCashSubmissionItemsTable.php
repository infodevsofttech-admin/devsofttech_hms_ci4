<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCashSubmissionItemsTable extends Migration
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
        if ($this->tableExists('finance_scroll_submission_items')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'scroll_submission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'payment_history_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'payment_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'amount' => [
                'type' => 'DECIMAL',
                'constraint' => '12,2',
                'default' => 0,
            ],
            'payment_mode' => [
                'type' => 'TINYINT',
                'constraint' => 2,
                'default' => 0,
            ],
            'payof_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'payof_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'payof_code' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'update_by_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 0,
            ],
            'update_by' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'snapshot_json' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('scroll_submission_id');
        $this->forge->addKey('payment_history_id');
        $this->forge->createTable('finance_scroll_submission_items', true);
    }

    public function down()
    {
        if ($this->tableExists('finance_scroll_submission_items')) {
            $this->forge->dropTable('finance_scroll_submission_items', true);
        }
    }
}
