<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDiagnosisPrintTemplates extends Migration
{
    private function hasTemplateTable(): bool
    {
        $query = $this->db->query("SHOW TABLES LIKE 'diagnosis_print_templates'");
        return $query->getNumRows() > 0;
    }

    public function up()
    {
        if ($this->hasTemplateTable()) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'modality' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 3,
            ],
            'template_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
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
            'page_background_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'watermark_type' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'default' => 'none',
            ],
            'watermark_text' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'watermark_image' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'watermark_alpha' => [
                'type' => 'DECIMAL',
                'constraint' => '4,2',
                'default' => '0.12',
            ],
            'header_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'first_page_header_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'content_prefix_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'content_suffix_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'footer_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'last_page_footer_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'patient_info_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'mpdf_prefix_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'mpdf_suffix_html' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at DATETIME DEFAULT CURRENT_TIMESTAMP',
            'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['modality', 'status']);
        $this->forge->createTable('diagnosis_print_templates', true, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4']);
    }

    public function down()
    {
        if ($this->hasTemplateTable()) {
            $this->forge->dropTable('diagnosis_print_templates', true);
        }
    }
}
