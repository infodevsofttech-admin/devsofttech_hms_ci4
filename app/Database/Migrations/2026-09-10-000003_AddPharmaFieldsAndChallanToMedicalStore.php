<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPharmaFieldsAndChallanToMedicalStore extends Migration
{
    public function up()
    {
        // 1. mst_purchases: is_challan, challan_no, converted_to_invoice_id
        if ($this->db->tableExists('mst_purchases')) {
            $fields = $this->db->getFieldNames('mst_purchases');
            $forge = \Config\Database::forge();
            $addCols = [];

            if (!in_array('is_challan', $fields, true)) {
                $addCols['is_challan'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'payment_status'
                ];
            }
            if (!in_array('challan_no', $fields, true)) {
                $addCols['challan_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'supplier_invoice_no'
                ];
            }
            if (!in_array('converted_to_invoice_id', $fields, true)) {
                $addCols['converted_to_invoice_id'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'is_challan'
                ];
            }

            if (!empty($addCols)) {
                $forge->addColumn('mst_purchases', $addCols);
            }
        }

        // 2. mst_purchase_items: sch_disc_pct, sch_disc_amount, selling_price, storage_type, shelf_no, rack_no, challan_item_ref_id
        if ($this->db->tableExists('mst_purchase_items')) {
            $fields = $this->db->getFieldNames('mst_purchase_items');
            $forge = \Config\Database::forge();
            $addCols = [];

            if (!in_array('sch_disc_pct', $fields, true)) {
                $addCols['sch_disc_pct'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                    'after'      => 'discount_pct'
                ];
            }
            if (!in_array('sch_disc_amount', $fields, true)) {
                $addCols['sch_disc_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'sch_disc_pct'
                ];
            }
            if (!in_array('selling_price', $fields, true)) {
                $addCols['selling_price'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'ptr'
                ];
            }
            if (!in_array('storage_type', $fields, true)) {
                $addCols['storage_type'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Normal',
                    'after'      => 'net_unit_landing_cost'
                ];
            }
            if (!in_array('shelf_no', $fields, true)) {
                $addCols['shelf_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'storage_type'
                ];
            }
            if (!in_array('rack_no', $fields, true)) {
                $addCols['rack_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'shelf_no'
                ];
            }
            if (!in_array('challan_item_ref_id', $fields, true)) {
                $addCols['challan_item_ref_id'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'rack_no'
                ];
            }

            if (!empty($addCols)) {
                $forge->addColumn('mst_purchase_items', $addCols);
            }
        }

        // 3. mst_batches: shelf_no, rack_no, storage_type
        if ($this->db->tableExists('mst_batches')) {
            $fields = $this->db->getFieldNames('mst_batches');
            $forge = \Config\Database::forge();
            $addCols = [];

            if (!in_array('shelf_no', $fields, true)) {
                $addCols['shelf_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'barcode'
                ];
            }
            if (!in_array('rack_no', $fields, true)) {
                $addCols['rack_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'shelf_no'
                ];
            }
            if (!in_array('storage_type', $fields, true)) {
                $addCols['storage_type'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'Normal',
                    'after'      => 'rack_no'
                ];
            }

            if (!empty($addCols)) {
                $forge->addColumn('mst_batches', $addCols);
            }
        }
    }

    public function down()
    {
        // Keep intact to prevent loss of pharma audit data
    }
}
