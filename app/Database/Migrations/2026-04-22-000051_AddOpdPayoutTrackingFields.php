<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOpdPayoutTrackingFields extends Migration
{
    private function hasTable(string $table): bool
    {
        $row = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table))->getRowArray();

        return ! empty($row);
    }

    private function getTableFields(string $table): array
    {
        if (! $this->hasTable($table)) {
            return [];
        }

        try {
            $rows = $this->db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();
            return array_values(array_filter(array_map(static function (array $row): string {
                return (string) ($row['Field'] ?? '');
            }, $rows), static fn(string $field): bool => $field !== ''));
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function up()
    {
        if (! $this->hasTable('opd_master')) {
            return;
        }

        $fields = $this->getTableFields('opd_master');

        if (! in_array('payout_draft_id', $fields, true)) {
            $this->forge->addColumn('opd_master', [
                'payout_draft_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'doc_id',
                ],
            ]);
        }

        if (! in_array('payout_calculated_at', $fields, true)) {
            $this->forge->addColumn('opd_master', [
                'payout_calculated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'payout_draft_id',
                ],
            ]);
        }

        if (! in_array('payout_calculated_by', $fields, true)) {
            $this->forge->addColumn('opd_master', [
                'payout_calculated_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                    'after' => 'payout_calculated_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->hasTable('opd_master')) {
            return;
        }

        $fields = $this->getTableFields('opd_master');

        if (in_array('payout_calculated_by', $fields, true)) {
            $this->forge->dropColumn('opd_master', 'payout_calculated_by');
        }
        if (in_array('payout_calculated_at', $fields, true)) {
            $this->forge->dropColumn('opd_master', 'payout_calculated_at');
        }
        if (in_array('payout_draft_id', $fields, true)) {
            $this->forge->dropColumn('opd_master', 'payout_draft_id');
        }
    }
}
