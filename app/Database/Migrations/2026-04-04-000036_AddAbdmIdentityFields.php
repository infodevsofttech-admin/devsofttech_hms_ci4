<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbdmIdentityFields extends Migration
{
    public function up()
    {
        $this->addPatientAbhaId();
        $this->addDoctorHprId();
    }

    public function down()
    {
        if ($this->tableExists('patient_master') && in_array('abha_id', $this->getColumns('patient_master'), true)) {
            $this->forge->dropColumn('patient_master', 'abha_id');
        }

        if ($this->tableExists('doctor_master') && in_array('hpr_id', $this->getColumns('doctor_master'), true)) {
            $this->forge->dropColumn('doctor_master', 'hpr_id');
        }
    }

    private function addPatientAbhaId(): void
    {
        if (! $this->tableExists('patient_master')) {
            return;
        }

        $columns = $this->getColumns('patient_master');
        if (! in_array('abha_id', $columns, true)) {
            $this->forge->addColumn('patient_master', [
                'abha_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                ],
            ]);
            $columns[] = 'abha_id';
        }

        if (in_array('abha_id', $columns, true) && in_array('abha_no', $columns, true)) {
            $this->db->query("UPDATE patient_master SET abha_id = IFNULL(NULLIF(abha_id,''), NULLIF(abha_no,''))");
        }

        if (in_array('abha_id', $columns, true) && in_array('abha', $columns, true)) {
            $this->db->query("UPDATE patient_master SET abha_id = IFNULL(NULLIF(abha_id,''), NULLIF(abha,''))");
        }

        if (in_array('abha_id', $columns, true) && in_array('abha_address', $columns, true)) {
            $this->db->query("UPDATE patient_master SET abha_id = IFNULL(NULLIF(abha_id,''), NULLIF(abha_address,''))");
        }
    }

    private function addDoctorHprId(): void
    {
        if (! $this->tableExists('doctor_master')) {
            return;
        }

        if (! in_array('hpr_id', $this->getColumns('doctor_master'), true)) {
            $this->forge->addColumn('doctor_master', [
                'hpr_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 40,
                    'null' => true,
                ],
            ]);
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
