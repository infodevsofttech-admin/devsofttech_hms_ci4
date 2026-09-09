<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSupplierLedgersAndPurchaseOrders extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Table: mst_supplier_payments
        if (!$db->tableExists('mst_supplier_payments')) {
            $this->forge->addField([
                'payment_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'store_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'supplier_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'purchase_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                ],
                'payment_date' => [
                    'type' => 'DATE',
                ],
                'amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'payment_mode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Bank',
                ],
                'reference_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
                'bank_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
                'voucher_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                ],
            ]);
            $this->forge->addKey('payment_id', true);
            $this->forge->addKey('supplier_id');
            $this->forge->addKey('purchase_id');
            $this->forge->addKey('payment_date');
            $this->forge->createTable('mst_supplier_payments', true);
        }

        // 2. Table: mst_purchase_orders
        if (!$db->tableExists('mst_purchase_orders')) {
            $this->forge->addField([
                'po_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'store_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'supplier_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'po_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'po_date' => [
                    'type' => 'DATE',
                ],
                'expected_delivery_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'total_items' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_estimated_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'ordered', // draft, ordered, received, cancelled
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
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
            $this->forge->addKey('po_id', true);
            $this->forge->addKey('po_number');
            $this->forge->addKey('supplier_id');
            $this->forge->addKey('status');
            $this->forge->createTable('mst_purchase_orders', true);
        }

        // 3. Table: mst_purchase_order_items
        if (!$db->tableExists('mst_purchase_order_items')) {
            $this->forge->addField([
                'po_item_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'po_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'item_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'item_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 200,
                ],
                'generic_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 250,
                    'null'       => true,
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'order_packs' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'units_per_pack' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 10,
                ],
                'estimated_ptr' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'gst_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 12.00,
                ],
                'estimated_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
            ]);
            $this->forge->addKey('po_item_id', true);
            $this->forge->addKey('po_id');
            $this->forge->addKey('item_id');
            $this->forge->createTable('mst_purchase_order_items', true);
        }

        // 4. Ensure index on mst_purchases for payment_status and supplier_id
        if ($db->tableExists('mst_purchases')) {
            try {
                $db->query("ALTER TABLE `mst_purchases` ADD INDEX `idx_mst_purchases_supp_status` (`supplier_id`, `payment_status`)");
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('mst_purchase_order_items', true);
        $this->forge->dropTable('mst_purchase_orders', true);
        $this->forge->dropTable('mst_supplier_payments', true);
    }
}
