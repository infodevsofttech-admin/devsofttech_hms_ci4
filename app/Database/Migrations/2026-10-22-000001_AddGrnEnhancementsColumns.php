<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGrnEnhancementsColumns extends Migration
{
    public function up(): void
    {
        // 1. Enhancements to mst_grns
        if ($this->tableExists('mst_grns')) {
            $colsToAdd = [];
            if (!$this->fieldExists('mst_grns', 'cgst_amount')) {
                $colsToAdd['cgst_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'total_tax_amount',
                ];
            }
            if (!$this->fieldExists('mst_grns', 'sgst_amount')) {
                $colsToAdd['sgst_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'cgst_amount',
                ];
            }
            if (!$this->fieldExists('mst_grns', 'igst_amount')) {
                $colsToAdd['igst_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'sgst_amount',
                ];
            }
            if (!$this->fieldExists('mst_grns', 'round_off')) {
                $colsToAdd['round_off'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0.00,
                    'after'      => 'igst_amount',
                ];
            }
            if (!$this->fieldExists('mst_grns', 'gst_type')) {
                $colsToAdd['gst_type'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'intra_state',
                    'after'      => 'round_off',
                ];
            }
            if (!empty($colsToAdd)) {
                $this->forge->addColumn('mst_grns', $colsToAdd);
            }
        }

        // 2. Enhancements to mst_grn_items
        if ($this->tableExists('mst_grn_items')) {
            $itemColsToAdd = [];
            if (!$this->fieldExists('mst_grn_items', 'gross_amount')) {
                $itemColsToAdd['gross_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'unit_purchase_rate',
                ];
            }
            if (!$this->fieldExists('mst_grn_items', 'sch_disc_per')) {
                $itemColsToAdd['sch_disc_per'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00,
                    'after'      => 'discount_percent',
                ];
            }
            if (!$this->fieldExists('mst_grn_items', 'sch_disc_amt')) {
                $itemColsToAdd['sch_disc_amt'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00,
                    'after'      => 'sch_disc_per',
                ];
            }
            if (!empty($itemColsToAdd)) {
                $this->forge->addColumn('mst_grn_items', $itemColsToAdd);
            }
        }
    }

    public function down(): void
    {
        if ($this->tableExists('mst_grn_items')) {
            foreach (['sch_disc_amt', 'sch_disc_per', 'gross_amount'] as $col) {
                if ($this->fieldExists('mst_grn_items', $col)) {
                    $this->forge->dropColumn('mst_grn_items', $col);
                }
            }
        }

        if ($this->tableExists('mst_grns')) {
            foreach (['gst_type', 'round_off', 'igst_amount', 'sgst_amount', 'cgst_amount'] as $col) {
                if ($this->fieldExists('mst_grns', $col)) {
                    $this->forge->dropColumn('mst_grns', $col);
                }
            }
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }

    private function fieldExists(string $table, string $field): bool
    {
        return $this->db->fieldExists($field, $table);
    }
}
