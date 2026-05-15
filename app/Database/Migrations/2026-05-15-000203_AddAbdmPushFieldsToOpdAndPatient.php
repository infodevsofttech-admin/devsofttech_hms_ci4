<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbdmPushFieldsToOpdAndPatient extends Migration
{
    public function up(): void
    {
        $this->addOpdPrescriptionFields();
        $this->addPatientMasterFields();
    }

    public function down(): void
    {
        if ($this->hasTable('opd_prescription')) {
            $fields = $this->getTableFields('opd_prescription');
            foreach (['abdm_push_status', 'abdm_push_at'] as $col) {
                if (in_array($col, $fields, true)) {
                    $this->forge->dropColumn('opd_prescription', $col);
                }
            }
        }

        if ($this->hasTable('patient_master')) {
            $fields = $this->getTableFields('patient_master');
            if (in_array('abdm_linked_at', $fields, true)) {
                $this->forge->dropColumn('patient_master', 'abdm_linked_at');
            }
        }
    }

    // -------------------------------------------------------------------------

    private function addOpdPrescriptionFields(): void
    {
        if (! $this->hasTable('opd_prescription')) {
            return;
        }

        $fields  = $this->getTableFields('opd_prescription');
        $toAdd   = [];

        if (! in_array('abdm_push_status', $fields, true)) {
            $toAdd['abdm_push_status'] = [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
                // 0 = not pushed, 1 = pushed, 2 = failed
            ];
        }

        if (! in_array('abdm_push_at', $fields, true)) {
            $toAdd['abdm_push_at'] = [
                'type' => 'DATETIME',
                'null' => true,
            ];
        }

        if (! empty($toAdd)) {
            $this->forge->addColumn('opd_prescription', $toAdd);
        }
    }

    private function addPatientMasterFields(): void
    {
        if (! $this->hasTable('patient_master')) {
            return;
        }

        $fields = $this->getTableFields('patient_master');

        if (! in_array('abdm_linked_at', $fields, true)) {
            $this->forge->addColumn('patient_master', [
                'abdm_linked_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
        }
    }

    // -------------------------------------------------------------------------

    private function hasTable(string $table): bool
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $row = $this->db->query("SHOW TABLES LIKE '" . $table . "'")->getRowArray();
        return ! empty($row);
    }

    /** @return string[] */
    private function getTableFields(string $table): array
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $result = $this->db->query('SHOW COLUMNS FROM `' . $table . '`')->getResultArray();
        return array_map(static fn (array $r): string => (string) ($r['Field'] ?? ''), $result);
    }
}
