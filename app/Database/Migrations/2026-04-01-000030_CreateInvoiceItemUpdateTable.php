<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoiceItemUpdateTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'audit_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'inv_master_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
            ],
            'item_type' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
            ],
            'item_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
            ],
            'item_name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'default' => '0',
            ],
            'item_rate' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => '0.00',
            ],
            'ins_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
                'default' => 0,
            ],
            'org_code' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'item_added_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'item_qty' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => '0.00',
            ],
            'item_amount' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => '0.00',
            ],
            'item_amount1' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'default' => '0.00',
            ],
            'update_by_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'update_by' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'update_action' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
        ]);

        $this->forge->addKey('audit_id', true);
        $this->forge->addKey('id');
        $this->forge->addKey('inv_master_id');
        $this->forge->createTable('invoice_item_update', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('invoice_item_update', true);
    }
}