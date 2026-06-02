<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPatientAbhaAddressField extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $fields = $this->getColumns('patient_master');

        if (! in_array('abha_address', $fields, true)) {
            $this->forge->addColumn('patient_master', [
                'abha_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                    'null'       => true,
                ],
            ]);
            $fields[] = 'abha_address';
        }

        if (in_array('abha_id', $fields, true) && in_array('abha_address', $fields, true)) {
            // Backfill address from legacy rows where abha_id accidentally stored ABHA Address.
            $this->db->query("UPDATE patient_master SET abha_address = IFNULL(NULLIF(abha_address,''), NULLIF(abha_id,'')) WHERE abha_id LIKE '%@%'");
            // Keep abha_id as numeric ABHA number only.
            $this->db->query("UPDATE patient_master SET abha_id = NULL WHERE abha_id LIKE '%@%'");
        }
    }

    public function down(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $fields = $this->getColumns('patient_master');
        if (in_array('abha_address', $fields, true)) {
            $this->forge->dropColumn('patient_master', 'abha_address');
        }
    }

    /**
     * @return string[]
     */
    private function getColumns(string $table): array
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $result = $this->db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();

        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $result);
    }

    private function tableExists(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }

        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();

        return ! empty($row);
    }
}
