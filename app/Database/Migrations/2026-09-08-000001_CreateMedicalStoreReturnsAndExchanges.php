<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMedicalStoreReturnsAndExchanges extends Migration
{
    public function up(): void
    {
        // 1. Ensure mst_sales has return, refund, and split payment tracking columns
        if ($this->hasTable('mst_sales')) {
            $salesCols = [];
            if (!$this->hasField('mst_sales', 'return_amount')) {
                $salesCols['return_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'gross_amount'
                ];
            }
            if (!$this->hasField('mst_sales', 'is_exchange_bill')) {
                $salesCols['is_exchange_bill'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'after'      => 'return_amount'
                ];
            }
            if (!$this->hasField('mst_sales', 'refund_amount')) {
                $salesCols['refund_amount'] = [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00,
                    'after'      => 'net_amount'
                ];
            }
            if (!$this->hasField('mst_sales', 'refund_mode')) {
                $salesCols['refund_mode'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'refund_amount'
                ];
            }
            if (!$this->hasField('mst_sales', 'refund_ref_no')) {
                $salesCols['refund_ref_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'refund_mode'
                ];
            }

            // Split payment columns safety check
            if (!$this->hasField('mst_sales', 'cash_paid')) {
                $salesCols['cash_paid'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00];
            }
            if (!$this->hasField('mst_sales', 'upi_paid')) {
                $salesCols['upi_paid'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00];
            }
            if (!$this->hasField('mst_sales', 'card_paid')) {
                $salesCols['card_paid'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00];
            }
            if (!$this->hasField('mst_sales', 'credit_amount')) {
                $salesCols['credit_amount'] = ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00];
            }
            if (!$this->hasField('mst_sales', 'payment_reference')) {
                $salesCols['payment_reference'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'bank_name')) {
                $salesCols['bank_name'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'upi_ref_no')) {
                $salesCols['upi_ref_no'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'card_ref_no')) {
                $salesCols['card_ref_no'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'is_bank_reconciled')) {
                $salesCols['is_bank_reconciled'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (!$this->hasField('mst_sales', 'reconciled_at')) {
                $salesCols['reconciled_at'] = ['type' => 'DATETIME', 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'reconciled_by')) {
                $salesCols['reconciled_by'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
            }
            if (!$this->hasField('mst_sales', 'reconciled_notes')) {
                $salesCols['reconciled_notes'] = ['type' => 'TEXT', 'null' => true];
            }

            if (!empty($salesCols)) {
                $this->forge->addColumn('mst_sales', $salesCols);
            }
        }

        // 2. Ensure mst_sales_items has return item tracking columns
        if ($this->hasTable('mst_sales_items')) {
            $itemCols = [];
            if (!$this->hasField('mst_sales_items', 'item_type')) {
                $itemCols['item_type'] = [
                    'type'       => "ENUM('SALE','RETURN')",
                    'default'    => 'SALE',
                    'after'      => 'qty'
                ];
            }
            if (!$this->hasField('mst_sales_items', 'return_condition')) {
                $itemCols['return_condition'] = [
                    'type'       => "ENUM('RESTOCKED','DAMAGED','EXPIRED')",
                    'default'    => 'RESTOCKED',
                    'after'      => 'item_type'
                ];
            }
            if (!$this->hasField('mst_sales_items', 'ref_sale_id')) {
                $itemCols['ref_sale_id'] = [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'return_condition'
                ];
            }
            if (!$this->hasField('mst_sales_items', 'ref_invoice_no')) {
                $itemCols['ref_invoice_no'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'ref_sale_id'
                ];
            }
            if (!$this->hasField('mst_sales_items', 'return_reason')) {
                $itemCols['return_reason'] = [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'ref_invoice_no'
                ];
            }
            if (!$this->hasField('mst_sales_items', 'is_restocked')) {
                $itemCols['is_restocked'] = [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'after'      => 'return_reason'
                ];
            }

            if (!empty($itemCols)) {
                $this->forge->addColumn('mst_sales_items', $itemCols);
            }
        }

        // 3. Create mst_sale_returns (Sequential GST Credit Notes)
        if (!$this->hasTable('mst_sale_returns')) {
            $this->forge->addField([
                'return_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true
                ],
                'store_id' => [
                    'type'     => 'INT',
                    'unsigned' => true
                ],
                'credit_note_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50
                ],
                'return_date' => [
                    'type' => 'DATETIME'
                ],
                'original_sale_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true
                ],
                'original_invoice_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true
                ],
                'new_sale_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true
                ],
                'patient_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true
                ],
                'uhid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true
                ],
                'patient_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true
                ],
                'patient_mobile' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true
                ],
                'return_reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true
                ],
                'gross_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'discount_reversed_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'taxable_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'cgst_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'sgst_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'igst_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'round_off' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '6,2',
                    'default'    => 0.00
                ],
                'net_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2',
                    'default'    => 0.00
                ],
                'refund_mode' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'EXCHANGE_BILL_ADJUSTMENT'
                ],
                'refund_ref_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true
                ],
                'restock_condition' => [
                    'type'    => "ENUM('RESTOCKED','DAMAGED','EXPIRED')",
                    'default' => 'RESTOCKED'
                ],
                'remarks' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'created_by' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'default'  => 1
                ],
                'created_at' => [
                    'type' => 'DATETIME'
                ]
            ]);
            $this->forge->addKey('return_id', true);
            $this->forge->addUniqueKey('credit_note_no');
            $this->forge->addKey(['store_id', 'return_date']);
            $this->forge->addKey('original_sale_id');
            $this->forge->addKey('new_sale_id');
            $this->forge->createTable('mst_sale_returns', true, ['ENGINE' => 'InnoDB']);
        }

        // 4. Create mst_sale_return_items
        if (!$this->hasTable('mst_sale_return_items')) {
            $this->forge->addField([
                'return_item_id' => [
                    'type'           => 'INT',
                    'unsigned'       => true,
                    'auto_increment' => true
                ],
                'return_id' => [
                    'type'     => 'INT',
                    'unsigned' => true
                ],
                'sale_item_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true
                ],
                'item_id' => [
                    'type'     => 'INT',
                    'unsigned' => true
                ],
                'batch_id' => [
                    'type'     => 'INT',
                    'unsigned' => true
                ],
                'batch_no' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50
                ],
                'expiry_date' => [
                    'type' => 'DATE'
                ],
                'hsn_code' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true
                ],
                'units_per_pack' => [
                    'type'    => 'INT',
                    'default' => 1
                ],
                'return_sell_unit' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'Tablet'
                ],
                'return_qty' => [
                    'type'    => 'INT',
                    'default' => 1
                ],
                'return_total_units' => [
                    'type'    => 'INT',
                    'default' => 1
                ],
                'unit_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2'
                ],
                'taxable_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00
                ],
                'gst_rate' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'default'    => 0.00
                ],
                'cgst_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00
                ],
                'sgst_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'default'    => 0.00
                ],
                'total_refund_amount' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '12,2'
                ],
                'return_condition' => [
                    'type'    => "ENUM('RESTOCKED','DAMAGED','EXPIRED')",
                    'default' => 'RESTOCKED'
                ],
                'is_restocked' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1
                ],
                'created_at' => [
                    'type' => 'DATETIME'
                ]
            ]);
            $this->forge->addKey('return_item_id', true);
            $this->forge->addKey('return_id');
            $this->forge->addKey('item_id');
            $this->forge->addKey('batch_id');
            $this->forge->createTable('mst_sale_return_items', true, ['ENGINE' => 'InnoDB']);
        }

        // 5. Seed Account Head 3005: Sales Returns & Customer Refunds
        if ($this->hasTable('mst_account_heads')) {
            $existing = $this->db->table('mst_account_heads')
                ->where('head_code', '3005')
                ->get()
                ->getRowArray();

            if (!$existing) {
                $this->db->table('mst_account_heads')->insert([
                    'head_code'   => '3005',
                    'head_name'   => 'Sales Returns & Customer Refunds',
                    'head_type'   => 'Expense',
                    'is_system'   => 1,
                    'status'      => 'active',
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }
    }

    public function down(): void
    {
        if ($this->hasTable('mst_sale_return_items')) {
            $this->forge->dropTable('mst_sale_return_items', true);
        }

        if ($this->hasTable('mst_sale_returns')) {
            $this->forge->dropTable('mst_sale_returns', true);
        }

        if ($this->hasTable('mst_sales_items')) {
            $dropItemCols = ['item_type', 'return_condition', 'ref_sale_id', 'ref_invoice_no', 'return_reason', 'is_restocked'];
            foreach ($dropItemCols as $c) {
                if ($this->hasField('mst_sales_items', $c)) {
                    $this->forge->dropColumn('mst_sales_items', $c);
                }
            }
        }

        if ($this->hasTable('mst_sales')) {
            $dropSalesCols = ['return_amount', 'is_exchange_bill', 'refund_amount', 'refund_mode', 'refund_ref_no'];
            foreach ($dropSalesCols as $c) {
                if ($this->hasField('mst_sales', $c)) {
                    $this->forge->dropColumn('mst_sales', $c);
                }
            }
        }

        if ($this->hasTable('mst_account_heads')) {
            $this->db->table('mst_account_heads')->where('head_code', '3005')->delete();
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $rows = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasField(string $table, string $field): bool
    {
        if (!$this->hasTable($table)) {
            return false;
        }

        try {
            $safeTable = str_replace('`', '``', $table);
            $sql = 'SHOW COLUMNS FROM `' . $safeTable . '` LIKE ' . $this->db->escape($field);
            $rows = $this->db->query($sql)->getResultArray();
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
