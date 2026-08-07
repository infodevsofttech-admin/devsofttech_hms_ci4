<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAddressFieldsToReferMaster extends Migration
{
    public function up(): void
    {
        $table = 'refer_master';

        if (! $this->hasTable($table)) {
            return;
        }

        if (! $this->hasField($table, 'place')) {
            $this->forge->addColumn($table, [
                'place' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
            ]);
        }

        if (! $this->hasField($table, 'city')) {
            $this->forge->addColumn($table, [
                'city' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }

        if (! $this->hasField($table, 'district')) {
            $this->forge->addColumn($table, [
                'district' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }

        if (! $this->hasField($table, 'state')) {
            $this->forge->addColumn($table, [
                'state' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        $table = 'refer_master';

        if (! $this->hasTable($table)) {
            return;
        }

        if ($this->hasField($table, 'state')) {
            $this->forge->dropColumn($table, 'state');
        }

        if ($this->hasField($table, 'district')) {
            $this->forge->dropColumn($table, 'district');
        }

        if ($this->hasField($table, 'city')) {
            $this->forge->dropColumn($table, 'city');
        }

        if ($this->hasField($table, 'place')) {
            $this->forge->dropColumn($table, 'place');
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $rows = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getResultArray();
            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function hasField(string $table, string $field): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        try {
            $safeTable = str_replace('`', '``', $table);
            $sql = 'SHOW COLUMNS FROM `' . $safeTable . '` LIKE ' . $this->db->escape($field);
            $rows = $this->db->query($sql)->getResultArray();
            return ! empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
