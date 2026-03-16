<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFinancePoDocumentColumns extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    private function hasColumn(string $table, string $column): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        $sql = 'SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->escape($column);
        $row = $this->db->query($sql)->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if (! $this->hasTable('finance_purchase_orders')) {
            return;
        }

        if (! $this->hasColumn('finance_purchase_orders', 'po_document_name')) {
            $this->forge->addColumn('finance_purchase_orders', [
                'po_document_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'approval_status',
                ],
            ]);
        }

        if (! $this->hasColumn('finance_purchase_orders', 'po_document_path')) {
            $this->forge->addColumn('finance_purchase_orders', [
                'po_document_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'po_document_name',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->hasTable('finance_purchase_orders')) {
            return;
        }

        if ($this->hasColumn('finance_purchase_orders', 'po_document_path')) {
            $this->forge->dropColumn('finance_purchase_orders', 'po_document_path');
        }

        if ($this->hasColumn('finance_purchase_orders', 'po_document_name')) {
            $this->forge->dropColumn('finance_purchase_orders', 'po_document_name');
        }
    }
}
