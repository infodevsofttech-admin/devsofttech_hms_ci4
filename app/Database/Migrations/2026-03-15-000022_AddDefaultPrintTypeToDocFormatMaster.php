<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDefaultPrintTypeToDocFormatMaster extends Migration
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

        if (! $this->hasColumn('doc_format_master', 'default_print_type')) {
            $this->forge->addColumn('doc_format_master', [
                'default_print_type' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'after' => 'doc_raw_format',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->hasTable('doc_format_master') && $this->hasColumn('doc_format_master', 'default_print_type')) {
            $this->forge->dropColumn('doc_format_master', 'default_print_type');
        }
    }
}
