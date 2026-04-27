<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHealthplixSyncIdFields extends Migration
{
    public function up(): void
    {
        $this->addPatientSyncField();
        $this->addOpdSyncField();
    }

    public function down(): void
    {
        if ($this->tableExists('patient_master')
            && $this->fieldExists('patient_master', 'healthplix_sync_id')) {
            $this->forge->dropColumn('patient_master', 'healthplix_sync_id');
        }

        if ($this->tableExists('opd_master')
            && $this->fieldExists('opd_master', 'healthplix_sync_id')) {
            $this->forge->dropColumn('opd_master', 'healthplix_sync_id');
        }
    }

    private function addPatientSyncField(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        if (! $this->fieldExists('patient_master', 'healthplix_sync_id')) {
            $this->forge->addColumn('patient_master', [
                'healthplix_sync_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
            ]);
        }
    }

    private function addOpdSyncField(): void
    {
        if (! $this->tableExists('opd_master')) {
            return;
        }

        if (! $this->fieldExists('opd_master', 'healthplix_sync_id')) {
            $this->forge->addColumn('opd_master', [
                'healthplix_sync_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        $dbName = (string) ($this->db->database ?? '');
        if ($dbName === '' || $table === '') {
            return false;
        }

        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$dbName, $table]
        )->getRowArray();

        return ((int) ($row['total'] ?? 0)) > 0;
    }

    private function fieldExists(string $table, string $field): bool
    {
        $dbName = (string) ($this->db->database ?? '');
        if ($dbName === '' || $table === '' || $field === '') {
            return false;
        }

        $row = $this->db->query(
            'SELECT COUNT(*) AS total FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$dbName, $table, $field]
        )->getRowArray();

        return ((int) ($row['total'] ?? 0)) > 0;
    }
}
