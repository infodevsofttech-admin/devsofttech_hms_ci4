<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancePhase1Tables extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if (! $this->hasTable('finance_vendors')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'vendor_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => false,
                ],
                'vendor_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 160,
                    'null' => false,
                ],
                'contact_person' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'gst_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'pan_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'address' => [
                    'type' => 'TEXT',
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
            $this->forge->addUniqueKey('vendor_code');
            $this->forge->addKey('vendor_name');
            $this->forge->createTable('finance_vendors', true);
        }

        if (! $this->hasTable('finance_purchase_orders')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'po_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => false,
                ],
                'po_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'vendor_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'department' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'approval_status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'draft',
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
            $this->forge->addUniqueKey('po_no');
            $this->forge->addKey('vendor_id');
            $this->forge->addKey('po_date');
            $this->forge->addKey('approval_status');
            $this->forge->createTable('finance_purchase_orders', true);
        }

        if (! $this->hasTable('finance_grns')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'grn_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => false,
                ],
                'grn_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'po_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'received_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'received_by' => [
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
            $this->forge->addUniqueKey('grn_no');
            $this->forge->addKey('po_id');
            $this->forge->addKey('grn_date');
            $this->forge->createTable('finance_grns', true);
        }

        if (! $this->hasTable('finance_vendor_invoices')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'invoice_no' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => false,
                ],
                'invoice_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'vendor_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false,
                ],
                'po_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'grn_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'invoice_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'payment_status' => [
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
            $this->forge->addKey(['vendor_id', 'invoice_date']);
            $this->forge->addKey('payment_status');
            $this->forge->createTable('finance_vendor_invoices', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('finance_vendor_invoices', true);
        $this->forge->dropTable('finance_grns', true);
        $this->forge->dropTable('finance_purchase_orders', true);
        $this->forge->dropTable('finance_vendors', true);
    }
}
