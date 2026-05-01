<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceItemPayoutTrackingFields extends Migration
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
        if (! $this->hasTable('invoice_item')) {
            return;
        }

        $fields = $this->getTableFields('invoice_item');

        if (! in_array('payout_draft_id', $fields, true)) {
            $this->forge->addColumn('invoice_item', [
                'payout_draft_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'inv_master_id',
                ],
            ]);
        }

        if (! in_array('payout_calculated_at', $fields, true)) {
            $this->forge->addColumn('invoice_item', [
                'payout_calculated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'payout_draft_id',
                ],
            ]);
        }

        if (! in_array('payout_calculated_by', $fields, true)) {
            $this->forge->addColumn('invoice_item', [
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
        if (! $this->hasTable('invoice_item')) {
            return;
        }

        $fields = $this->getTableFields('invoice_item');

        if (in_array('payout_calculated_by', $fields, true)) {
            $this->forge->dropColumn('invoice_item', 'payout_calculated_by');
        }
        if (in_array('payout_calculated_at', $fields, true)) {
            $this->forge->dropColumn('invoice_item', 'payout_calculated_at');
        }
        if (in_array('payout_draft_id', $fields, true)) {
            $this->forge->dropColumn('invoice_item', 'payout_draft_id');
        }
    }
}
