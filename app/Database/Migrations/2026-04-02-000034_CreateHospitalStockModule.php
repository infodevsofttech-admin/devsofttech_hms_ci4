<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHospitalStockModule extends Migration
{
    public function up()
    {
        $this->createCategoriesTable();
        $this->createSuppliersTable();
        $this->createItemsTable();
        $this->createIndentsTable();
        $this->createIndentItemsTable();
        $this->createIssuesTable();
        $this->createIssueItemsTable();
        $this->createPurchaseOrdersTable();
        $this->createPurchaseOrderItemsTable();
        $this->createStockLedgerTable();
        $this->createAuditLogTable();
    }

    public function down()
    {
        $this->forge->dropTable('hsm_audit_log', true);
        $this->forge->dropTable('hsm_stock_ledger', true);
        $this->forge->dropTable('hsm_purchase_order_items', true);
        $this->forge->dropTable('hsm_purchase_orders', true);
        $this->forge->dropTable('hsm_issue_items', true);
        $this->forge->dropTable('hsm_issues', true);
        $this->forge->dropTable('hsm_indent_items', true);
        $this->forge->dropTable('hsm_indents', true);
        $this->forge->dropTable('hsm_items', true);
        $this->forge->dropTable('hsm_suppliers', true);
        $this->forge->dropTable('hsm_categories', true);
    }

    private function createCategoriesTable(): void
    {
        if ($this->hasTable('hsm_categories')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('hsm_categories', true);
    }

    private function createSuppliersTable(): void
    {
        if ($this->hasTable('hsm_suppliers')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'email' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'address' => ['type' => 'TEXT', 'null' => true],
            'gst_no' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->createTable('hsm_suppliers', true);
    }

    private function createItemsTable(): void
    {
        if ($this->hasTable('hsm_items')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'uom' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'Unit'],
            'barcode' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'qr_code' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'current_stock' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'min_stock_level' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'reorder_level' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('item_code');
        $this->forge->addKey('name');
        $this->forge->addKey('category_id');
        $this->forge->createTable('hsm_items', true);
    }

    private function createIndentsTable(): void
    {
        if ($this->hasTable('hsm_indents')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'indent_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'department_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'requested_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'partial_issued', 'issued', 'rejected', 'cancelled'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('indent_code');
        $this->forge->addKey('status');
        $this->forge->createTable('hsm_indents', true);
    }

    private function createIndentItemsTable(): void
    {
        if ($this->hasTable('hsm_indent_items')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'indent_id' => ['type' => 'INT', 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'requested_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'approved_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'issued_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('indent_id');
        $this->forge->addKey('item_id');
        $this->forge->createTable('hsm_indent_items', true);
    }

    private function createIssuesTable(): void
    {
        if ($this->hasTable('hsm_issues')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'issue_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'indent_id' => ['type' => 'INT', 'unsigned' => true],
            'issued_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'department_name' => ['type' => 'VARCHAR', 'constraint' => 120],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('issue_code');
        $this->forge->addKey('indent_id');
        $this->forge->createTable('hsm_issues', true);
    }

    private function createIssueItemsTable(): void
    {
        if ($this->hasTable('hsm_issue_items')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'issue_id' => ['type' => 'INT', 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('issue_id');
        $this->forge->addKey('item_id');
        $this->forge->createTable('hsm_issue_items', true);
    }

    private function createPurchaseOrdersTable(): void
    {
        if ($this->hasTable('hsm_purchase_orders')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'po_code' => ['type' => 'VARCHAR', 'constraint' => 40],
            'supplier_id' => ['type' => 'INT', 'unsigned' => true],
            'order_date' => ['type' => 'DATE', 'null' => true],
            'expected_date' => ['type' => 'DATE', 'null' => true],
            'ordered_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'ordered', 'partial_received', 'completed', 'cancelled'], 'default' => 'ordered'],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('po_code');
        $this->forge->addKey('supplier_id');
        $this->forge->addKey('status');
        $this->forge->createTable('hsm_purchase_orders', true);
    }

    private function createPurchaseOrderItemsTable(): void
    {
        if ($this->hasTable('hsm_purchase_order_items')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'purchase_order_id' => ['type' => 'INT', 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'ordered_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'received_qty' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'expiry_date' => ['type' => 'DATE', 'null' => true],
            'batch_no' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('purchase_order_id');
        $this->forge->addKey('item_id');
        $this->forge->createTable('hsm_purchase_order_items', true);
    }

    private function createStockLedgerTable(): void
    {
        if ($this->hasTable('hsm_stock_ledger')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'txn_type' => ['type' => 'ENUM', 'constraint' => ['purchase', 'issue', 'adjustment_in', 'adjustment_out'], 'default' => 'adjustment_in'],
            'ref_table' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'qty_in' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'qty_out' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'balance_after' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'remarks' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('item_id');
        $this->forge->addKey('txn_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('hsm_stock_ledger', true);
    }

    private function createAuditLogTable(): void
    {
        if ($this->hasTable('hsm_audit_log')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'module' => ['type' => 'VARCHAR', 'constraint' => 60],
            'action' => ['type' => 'VARCHAR', 'constraint' => 60],
            'entity_table' => ['type' => 'VARCHAR', 'constraint' => 60],
            'entity_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT', 'null' => true],
            'meta_json' => ['type' => 'LONGTEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('module');
        $this->forge->addKey('entity_table');
        $this->forge->createTable('hsm_audit_log', true);
    }

    private function hasTable(string $table): bool
    {
        $query = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table));

        return $query->getNumRows() > 0;
    }
}