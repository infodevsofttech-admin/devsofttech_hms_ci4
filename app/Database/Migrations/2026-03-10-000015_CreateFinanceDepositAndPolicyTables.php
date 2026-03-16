<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceDepositAndPolicyTables extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if (! $this->hasTable('finance_bank_deposits')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'deposit_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'department' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'bank_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => false,
                ],
                'slip_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'deposited_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'related_scroll_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'reconciliation_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'pending',
                ],
                'remarks' => [
                    'type' => 'TEXT',
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
            $this->forge->addKey(['deposit_date', 'department']);
            $this->forge->addKey('reconciliation_status');
            $this->forge->createTable('finance_bank_deposits', true);
        }

        if (! $this->hasTable('finance_policy_settings')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'setting_key' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => false,
                ],
                'setting_value' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => false,
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
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
            $this->forge->addUniqueKey('setting_key');
            $this->forge->createTable('finance_policy_settings', true);
        }

        $this->seedSetting('petty_cash_daily_limit', '50000', 'Daily petty cash disbursement limit per department');
        $this->seedSetting('safe_cash_max_limit', '500000', 'Maximum recommended physical cash retention in safe');
    }

    public function down()
    {
        $this->forge->dropTable('finance_policy_settings', true);
        $this->forge->dropTable('finance_bank_deposits', true);
    }

    private function seedSetting(string $key, string $value, string $description): void
    {
        if (! $this->hasTable('finance_policy_settings')) {
            return;
        }

        $exists = $this->db->table('finance_policy_settings')->where('setting_key', $key)->countAllResults();
        if ($exists > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('finance_policy_settings')->insert([
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
