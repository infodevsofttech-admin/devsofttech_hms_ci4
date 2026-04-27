<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHealthplixDoctorIdentifierToDoctorMaster extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('doctor_master')) {
            return;
        }

        if (! $this->fieldExists('doctor_master', 'healthplix_doctor_identifier')) {
            $this->forge->addColumn('doctor_master', [
                'healthplix_doctor_identifier' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                    'after' => 'email1',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->tableExists('doctor_master')
            && $this->fieldExists('doctor_master', 'healthplix_doctor_identifier')) {
            $this->forge->dropColumn('doctor_master', 'healthplix_doctor_identifier');
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
