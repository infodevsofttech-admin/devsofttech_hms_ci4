<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPharmacyStockReportIndexes extends Migration
{
    private function tableExists(string $table): bool
    {
        return $this->db->tableExists($table);
    }

    private function fieldExists(string $table, string $field): bool
    {
        return $this->db->fieldExists($field, $table);
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (! $this->tableExists($table)) {
            return false;
        }

        $rows = $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$indexName])->getResultArray();
        return ! empty($rows);
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columns): void
    {
        if (! $this->tableExists($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        $this->db->query('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (' . $columns . ')');
    }

    public function up(): void
    {
        if ($this->tableExists('purchase_invoice_item')) {
            $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_item_code', '`item_code`');
            $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_purchase_id', '`purchase_id`');

            if ($this->fieldExists('purchase_invoice_item', 'stock_date')) {
                $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_stock_date', '`stock_date`');
            }

            if ($this->fieldExists('purchase_invoice_item', 'remove_item') && $this->fieldExists('purchase_invoice_item', 'item_return')) {
                $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_status_flags', '`remove_item`,`item_return`');
            }

            if ($this->fieldExists('purchase_invoice_item', 'expiry_date')) {
                $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_expiry_date', '`expiry_date`');
            }

            if ($this->fieldExists('purchase_invoice_item', 'batch_no')) {
                $this->addIndexIfMissing('purchase_invoice_item', 'idx_pii_item_batch', '`item_code`,`batch_no`');
            }
        }

        if ($this->tableExists('purchase_invoice')) {
            if ($this->fieldExists('purchase_invoice', 'date_of_invoice') && $this->fieldExists('purchase_invoice', 'sid')) {
                $this->addIndexIfMissing('purchase_invoice', 'idx_pi_date_supplier', '`date_of_invoice`,`sid`');
            }
            if ($this->fieldExists('purchase_invoice', 'sid')) {
                $this->addIndexIfMissing('purchase_invoice', 'idx_pi_supplier', '`sid`');
            }
        }

        if ($this->tableExists('inv_med_item')) {
            if ($this->fieldExists('inv_med_item', 'inv_med_id') && $this->fieldExists('inv_med_item', 'item_code')) {
                $this->addIndexIfMissing('inv_med_item', 'idx_imi_invoice_item', '`inv_med_id`,`item_code`');
            }
            if ($this->fieldExists('inv_med_item', 'item_code')) {
                $this->addIndexIfMissing('inv_med_item', 'idx_imi_item_code', '`item_code`');
            }
            if ($this->fieldExists('inv_med_item', 'sale_return')) {
                $this->addIndexIfMissing('inv_med_item', 'idx_imi_sale_return', '`sale_return`');
            }
            if ($this->fieldExists('inv_med_item', 'item_return')) {
                $this->addIndexIfMissing('inv_med_item', 'idx_imi_item_return', '`item_return`');
            }
        }

        if ($this->tableExists('invoice_med_master')) {
            if ($this->fieldExists('invoice_med_master', 'inv_date')) {
                $this->addIndexIfMissing('invoice_med_master', 'idx_imm_inv_date', '`inv_date`');
            }
            if ($this->fieldExists('invoice_med_master', 'sale_return') && $this->fieldExists('invoice_med_master', 'inv_date')) {
                $this->addIndexIfMissing('invoice_med_master', 'idx_imm_sale_return_date', '`sale_return`,`inv_date`');
            }
        }
    }

    public function down(): void
    {
        $drops = [
            'purchase_invoice_item' => [
                'idx_pii_item_code',
                'idx_pii_purchase_id',
                'idx_pii_stock_date',
                'idx_pii_status_flags',
                'idx_pii_expiry_date',
                'idx_pii_item_batch',
            ],
            'purchase_invoice' => [
                'idx_pi_date_supplier',
                'idx_pi_supplier',
            ],
            'inv_med_item' => [
                'idx_imi_invoice_item',
                'idx_imi_item_code',
                'idx_imi_sale_return',
                'idx_imi_item_return',
            ],
            'invoice_med_master' => [
                'idx_imm_inv_date',
                'idx_imm_sale_return_date',
            ],
        ];

        foreach ($drops as $table => $indexes) {
            if (! $this->tableExists($table)) {
                continue;
            }
            foreach ($indexes as $index) {
                if ($this->indexExists($table, $index)) {
                    $this->db->query('ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`');
                }
            }
        }
    }
}
