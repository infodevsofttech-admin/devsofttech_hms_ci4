<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbhaCardToPatientMaster extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $fields = $this->getColumns('patient_master');
        if (! in_array('abha_card_base64', $fields, true)) {
            $this->forge->addColumn('patient_master', [
                'abha_card_base64' => [
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->tableExists('patient_master') && in_array('abha_card_base64', $this->getColumns('patient_master'), true)) {
            $this->forge->dropColumn('patient_master', 'abha_card_base64');
        }
    }

    private function getColumns(string $table): array
    {
        $result = $this->db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();
        return array_map(static fn (array $row): string => (string) ($row['Field'] ?? ''), $result);
    }

    private function tableExists(string $table): bool
    {
        return ! empty($this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray());
    }
}
