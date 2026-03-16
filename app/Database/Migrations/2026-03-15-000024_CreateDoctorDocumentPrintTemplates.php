<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDoctorDocumentPrintTemplates extends Migration
{
    private function hasTable(string $table): bool
    {
        $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table) ?? '';
        $row = $this->db->query("SHOW TABLES LIKE '" . $safeTable . "'")->getRowArray();
        return is_array($row);
    }

    public function up()
    {
        if ($this->hasTable('doc_print_templates')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 150,
            ],
            'is_default' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'page_size' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'A4',
            ],
            'print_on_type' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'page_margin_top_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 6.10,
            ],
            'page_margin_bottom_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 2.50,
            ],
            'page_margin_left_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.70,
            ],
            'page_margin_right_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.70,
            ],
            'margin_header_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.50,
            ],
            'margin_footer_cm' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 1.50,
            ],
            'header_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'footer_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_default', 'status']);
        $this->forge->createTable('doc_print_templates', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);

        $this->db->table('doc_print_templates')->insert([
            'template_name' => 'Default Document Print Template',
            'is_default' => 1,
            'status' => 1,
            'page_size' => 'A4',
            'print_on_type' => 1,
            'page_margin_top_cm' => 6.10,
            'page_margin_bottom_cm' => 2.50,
            'page_margin_left_cm' => 0.70,
            'page_margin_right_cm' => 0.70,
            'margin_header_cm' => 0.50,
            'margin_footer_cm' => 1.50,
            'header_html' => '',
            'footer_html' => '',
        ]);
    }

    public function down()
    {
        if ($this->hasTable('doc_print_templates')) {
            $this->forge->dropTable('doc_print_templates', true);
        }
    }
}
