<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPrintMarginsToDocFormatMaster extends Migration
{
    private function hasTable(string $table): bool
    {
        $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table) ?? '';
        $row = $this->db->query("SHOW TABLES LIKE '" . $safeTable . "'")->getRowArray();
        return is_array($row);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $safeTable = preg_replace('/[^A-Za-z0-9_]/', '', $table) ?? '';
        $safeColumn = preg_replace('/[^A-Za-z0-9_]/', '', $column) ?? '';
        $row = $this->db->query("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'")->getRowArray();
        return is_array($row);
    }

    public function up()
    {
        if (! $this->hasTable('doc_format_master')) {
            return;
        }

        $columns = [
            'print_top_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 6.10,
                'null' => false,
                'after' => 'default_print_type',
            ],
            'print_bottom_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 2.50,
                'null' => false,
                'after' => 'print_top_margin',
            ],
            'print_left_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.70,
                'null' => false,
                'after' => 'print_bottom_margin',
            ],
            'print_right_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.70,
                'null' => false,
                'after' => 'print_left_margin',
            ],
            'print_header_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 0.50,
                'null' => false,
                'after' => 'print_right_margin',
            ],
            'print_footer_margin' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'default' => 1.50,
                'null' => false,
                'after' => 'print_header_margin',
            ],
        ];

        foreach ($columns as $column => $definition) {
            if (! $this->hasColumn('doc_format_master', $column)) {
                $this->forge->addColumn('doc_format_master', [$column => $definition]);
            }
        }
    }

    public function down()
    {
        if (! $this->hasTable('doc_format_master')) {
            return;
        }

        $columns = [
            'print_footer_margin',
            'print_header_margin',
            'print_right_margin',
            'print_left_margin',
            'print_bottom_margin',
            'print_top_margin',
        ];

        foreach ($columns as $column) {
            if ($this->hasColumn('doc_format_master', $column)) {
                $this->forge->dropColumn('doc_format_master', $column);
            }
        }
    }
}
