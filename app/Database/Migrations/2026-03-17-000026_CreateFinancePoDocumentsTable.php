<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFinancePoDocumentsTable extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    public function up()
    {
        if ($this->hasTable('finance_po_documents')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'po_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'uploaded_by' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('po_id');
        $this->forge->createTable('finance_po_documents', true);
    }

    public function down()
    {
        $this->forge->dropTable('finance_po_documents', true);
    }
}
