<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancePharmacyBillsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('finance_pharmacy_bills')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'bill_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 80,
                'default'    => '',
            ],
            'bill_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'pharmacy_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'default'    => '',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'default'    => '',
            ],
            'bill_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'tax_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'net_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'payment_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'pending',
            ],
            'paid_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'payment_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'payment_mode' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'default'    => '',
            ],
            'payment_ref' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => '',
            ],
            'remarks' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'default'    => '',
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

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('bill_date');
        $this->forge->addKey('payment_status');
        $this->forge->createTable('finance_pharmacy_bills', true);
    }

    public function down()
    {
        $this->forge->dropTable('finance_pharmacy_bills', true);
    }
}
