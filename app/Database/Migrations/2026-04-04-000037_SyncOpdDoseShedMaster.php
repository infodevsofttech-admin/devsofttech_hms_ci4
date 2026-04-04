<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SyncOpdDoseShedMaster extends Migration
{
    public function up()
    {
        if (! $this->tableExists('opd_dose_shed')) {
            return;
        }

        $this->db->table('opd_dose_shed')->truncate();

        $rows = [
            [
                'dose_shed_id' => 1,
                'dose_show_sign' => '1 - 0 - 0',
                'dose_show_desc' => 'सुबह',
            ],
            [
                'dose_shed_id' => 2,
                'dose_show_sign' => '0 - 0 - 1',
                'dose_show_desc' => 'रात को',
            ],
            [
                'dose_shed_id' => 3,
                'dose_show_sign' => '1 - 0 - 1',
                'dose_show_desc' => 'सुबह - शाम',
            ],
            [
                'dose_shed_id' => 4,
                'dose_show_sign' => '1 - 1 - 1',
                'dose_show_desc' => 'सुबह - दोपहर - शाम',
            ],
            [
                'dose_shed_id' => 5,
                'dose_show_sign' => '1 - 1 - 0',
                'dose_show_desc' => 'सुबह - दोपहर',
            ],
            [
                'dose_shed_id' => 6,
                'dose_show_sign' => '0 - 1 - 0',
                'dose_show_desc' => 'दोपहर',
            ],
            [
                'dose_shed_id' => 7,
                'dose_show_sign' => '0 - 1 - 1',
                'dose_show_desc' => 'दोपहर - शाम',
            ],
            [
                'dose_shed_id' => 8,
                'dose_show_sign' => '0 - 0 - 0',
                'dose_show_desc' => '',
            ],
        ];

        $this->db->table('opd_dose_shed')->insertBatch($rows);

        $this->resetAutoIncrement('opd_dose_shed', 9);

    }

    public function down()
    {
        // Intentionally left as no-op because previous master seed values are environment-specific.
    }

    private function tableExists(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();
        return ! empty($row);
    }

    private function resetAutoIncrement(string $table, int $nextId): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return;
        }

        $nextId = max(1, $nextId);
        $this->db->query('ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . $nextId);
    }
}