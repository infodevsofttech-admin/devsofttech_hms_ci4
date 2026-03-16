<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinanceDoctorPayoutTables extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if (! $this->hasTable('finance_doctor_agreements')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'doctor_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => false,
                ],
                'doctor_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false,
                ],
                'specialization' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'consultation_rate' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'surgery_rate' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'agreement_start_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'agreement_end_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'status' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->forge->addUniqueKey('doctor_code');
            $this->forge->addKey('doctor_name');
            $this->forge->createTable('finance_doctor_agreements', true);
        }

        if (! $this->hasTable('finance_doctor_payouts')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'payout_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'doctor_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'case_reference' => [
                    'type' => 'VARCHAR',
                    'constraint' => 80,
                    'null' => true,
                ],
                'payout_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false,
                ],
                'units' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 1,
                ],
                'rate' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'calculated_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'approved_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'default' => 'draft',
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'hr_submitted_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'finance_approved_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'finance_approved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'ceo_approved_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'ceo_approved_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'paid_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey(['doctor_id', 'payout_date']);
            $this->forge->addKey('status');
            $this->forge->createTable('finance_doctor_payouts', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_doctor_payouts', true);
        $this->forge->dropTable('finance_doctor_agreements', true);
    }
}
