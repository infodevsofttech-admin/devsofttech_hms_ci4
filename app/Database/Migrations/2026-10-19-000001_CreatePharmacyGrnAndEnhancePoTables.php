<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePharmacyGrnAndEnhancePoTables extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Enhance mst_purchase_orders
        if ($db->tableExists('mst_purchase_orders')) {
            $existingFields = $db->getFieldNames('mst_purchase_orders');
            $addFields = [];

            if (!in_array('payment_terms', $existingFields, true)) {
                $addFields['payment_terms'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'expected_delivery_date',
                ];
            }
            if (!in_array('tax_amount', $existingFields, true)) {
                $addFields['tax_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'total_estimated_amount',
                ];
            }
            if (!in_array('grand_total', $existingFields, true)) {
                $addFields['grand_total'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'tax_amount',
                ];
            }
            if (!in_array('approved_by', $existingFields, true)) {
                $addFields['approved_by'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'created_by',
                ];
            }
            if (!in_array('approved_at', $existingFields, true)) {
                $addFields['approved_at'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'approved_by',
                ];
            }
            if (!in_array('cancelled_by', $existingFields, true)) {
                $addFields['cancelled_by'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'approved_at',
                ];
            }
            if (!in_array('cancelled_at', $existingFields, true)) {
                $addFields['cancelled_at'] = [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'after'   => 'cancelled_by',
                ];
            }
            if (!in_array('cancelled_reason', $existingFields, true)) {
                $addFields['cancelled_reason'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'cancelled_at',
                ];
            }

            if (!empty($addFields)) {
                $this->forge->addColumn('mst_purchase_orders', $addFields);
            }
        }

        // 2. Enhance mst_purchase_order_items
        if ($db->tableExists('mst_purchase_order_items')) {
            $existingItemFields = $db->getFieldNames('mst_purchase_order_items');
            $addItemFields = [];

            if (!in_array('received_packs', $existingItemFields, true)) {
                $addItemFields['received_packs'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'units_per_pack',
                ];
            }
            if (!in_array('remaining_packs', $existingItemFields, true)) {
                $addItemFields['remaining_packs'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'received_packs',
                ];
            }
            if (!in_array('status', $existingItemFields, true)) {
                $addItemFields['status'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'pending',
                    'after'      => 'estimated_amount',
                ];
            }

            if (!empty($addItemFields)) {
                $this->forge->addColumn('mst_purchase_order_items', $addItemFields);
            }
        }

        // 3. Create mst_grns (Goods Received Note Header)
        if (!$db->tableExists('mst_grns')) {
            $this->forge->addField([
                'grn_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'grn_number' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'store_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'po_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'supplier_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'challan_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                ],
                'supplier_invoice_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'invoice_date' => [
                    'type' => 'DATE',
                ],
                'grn_date' => [
                    'type' => 'DATE',
                ],
                'total_received_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'total_tax_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'net_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'draft', // draft, verified, posted_to_inventory, rejected, cancelled
                ],
                'purchase_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                ],
                'received_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                ],
                'verified_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                ],
                'verified_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
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
            $this->forge->addKey('grn_id', true);
            $this->forge->addKey('grn_number');
            $this->forge->addKey('po_id');
            $this->forge->addKey('supplier_id');
            $this->forge->addKey('status');
            $this->forge->addKey('purchase_id');
            $this->forge->createTable('mst_grns', true);
        }

        // 4. Create mst_grn_items (Goods Received Note Items)
        if (!$db->tableExists('mst_grn_items')) {
            $this->forge->addField([
                'grn_item_id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'grn_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'po_item_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
                'item_code' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                ],
                'item_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 200,
                ],
                'batch_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                ],
                'expiry_date' => [
                    'type' => 'DATE',
                ],
                'packing' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 1,
                ],
                'order_packs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'received_packs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'free_packs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'rejected_packs' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'rejection_reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                ],
                'unit_purchase_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'mrp' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'selling_unit_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'discount_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'taxable_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'cgst_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'cgst_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'sgst_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'sgst_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'igst_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                ],
                'igst_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                ],
                'hsn_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'default'    => null,
                ],
                'total_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                ],
                'rack_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'default'    => null,
                ],
                'shelf_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'default'    => null,
                ],
                'cold_storage' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'default'    => null,
                ],
                'purchase_item_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
            $this->forge->addKey('grn_item_id', true);
            $this->forge->addKey('grn_id');
            $this->forge->addKey('item_code');
            $this->forge->addKey('po_item_id');
            $this->forge->addKey('purchase_item_id');
            $this->forge->createTable('mst_grn_items', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('mst_grn_items', true);
        $this->forge->dropTable('mst_grns', true);
    }
}
